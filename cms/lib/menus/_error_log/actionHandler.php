<?php

// PHP Error Log Menu

// define globals
$GLOBALS['APP']['selectedMenu'] = 'admin'; // show admin menu as selected

// check access level - admin only!
if (!$GLOBALS['CURRENT_USER']['isAdmin']) {
  alert(t("You don't have permissions to access this menu."));
  showInterface('');
}

// menu plugin hooks
addAction('section_preDispatch',     '_pel_showModeNotice',  null, 2);
addFilter('listHeader_displayLabel', '_pel_cmsList_messageColumn', null, 3);
addFilter('listRow_displayValue',    '_pel_cmsList_messageColumn', null, 4);

// customize admin UI
addFilter('adminUI_args', function($adminUI) {
  global $tableName, $action, $menu;

  // PAGE TITLE: Prefix Menu with "Admin >"
  array_unshift($adminUI['PAGE_TITLE'], t('Admin'));

  // LIST PAGE: add "Filepath Report" buttons on list page
  $isListOrFilepathReport = in_array($action, ['list', 'filepathReport','eraseRecords']);
  if ($isListOrFilepathReport) {
    $isFilepathReport = $GLOBALS['action'] == 'filepathReport';
    $adminUI['BUTTONS'] = [
      [ 'label' => "Error Log",       'onclick' => "window.location='?menu=$tableName';",                       'type' => 'button', 'btn-type' => ($isFilepathReport ? 'default' : 'primary') ],
      [ 'label' => "Filepath Report", 'onclick' => "window.location='?menu=$tableName&action=filepathReport';", 'type' => 'button', 'btn-type' => ($isFilepathReport ? 'primary' : 'default') ],
    ];
  }

  // EDIT PAGE: remove save buttons on edit page (we don't want users modifying error logs)
  if ($action == 'edit') {
    // remove buttons containing "=save" since we're disabling save
    $adminUI['BUTTONS'] = array_filter($adminUI['BUTTONS'], function($button) {
      return !str_contains($button['name'], '=save');
    });

    // Unset edit form defaults: 'onsubmit' => 'if (typeof tinyMCE.triggerSave == "function") { tinyMCE.triggerSave(); }', 'autocomplete' => 'off', 'class' => 'preventLeavingOnChange'];
    $adminUI['FORM'] = [ 'onsubmit' => 'if ($.fn.ajaxFormUnbind) { $("form").ajaxFormUnbind(); }' ];

    // set default action to 'list' not 'save'
    $adminUI['HIDDEN_FIELDS'] = [
      [ 'name' => 'menu',           'value' => $menu,                       'id' => 'menu',          ],
      [ 'name' => '_defaultAction', 'value' => 'list',                                               ],
      [ 'name' => 'num',            'value' => $_REQUEST['num'] ?? '',      'id' => 'num',           ],
    ];

  }

  return $adminUI;
});

// Dispatch Actions
if ($GLOBALS['action'] == 'clearLog') { // clear error log
  mysql_delete($GLOBALS['schema']['_tableName'], null, 'true');
  redirectBrowserToURL("?menu=" . $GLOBALS['schema']['_tableName']);
}
if (!empty($_REQUEST['clearFilepathErrors']) && !empty($_REQUEST['filepath'])) { // clear error log for one filepath
  $deletedCount = mysql_delete('_error_log', null, [ 'filepath' => $_REQUEST['filepath'] ]);
  $notice       = htmlencodef("Cleared all ? error records for ?", $deletedCount, $_REQUEST['filepath']);
  notice($notice);
}
if ($GLOBALS['action'] == 'filepathReport') { errorLog_errorsByFilepath(); }
if ($GLOBALS['action'] == 'save')           { die("Modifying error logs is not supported."); }

// Let regular actionHandler run
$REDIRECT_FOR_CUSTOM_MENUS_DONT_EXIT = true;
return;


//
function _pel_showModeNotice($tableName, $action) {
  if ($action != 'list') { return; }

  #$notice = sprintf(t("Send &amp; Log - Send mail and save copies under <a href='%s'>Outgoing Mail</a>"), "?menu=_outgoing_mail");
  $notice = t("Any PHP errors or warnings from the website or CMS will be logged here.");
  $notice = t("Developer Log"). ": " . $notice . " (<a href='?menu=$tableName&action=clearLog'>" .t("Clear Log"). "</a>)";
  notice($notice);
}


//
function _pel_cmsList_messageColumn($displayValue, $tableName, $fieldname, $record = []) {
  if ($tableName != '_error_log')    { return $displayValue; } // skip all by our table

  //
  if ($fieldname == 'dateLogged') {
    if (!$record) { return t("Date / When"); } // header - we detect the header hook by checking if the 4th argument is set
    $displayValue = "<div title='" .htmlencode($record['dateLogged']). "'>";
    $displayValue .= str_replace(' ', '&nbsp;', prettyDate( $record['dateLogged']));  // row cell - we detect the row cell by checking if $record is set
    $displayValue .= "</div>";
  }

  //
  if ($fieldname == '_error_summary_') {
    if (!$record) { return t("Error Details"); } // header - we detect the header hook by checking if the 4th argument is set
    // row cell - we detect the row cell by checking if $record is set

    // get truncated url
    $truncatedUrl = $record['url'];
    $maxLength    = 90;
    if (preg_match("/^(.{0,$maxLength})(\s|$)/s", $truncatedUrl, $matches)) { $truncatedUrl = $matches[1]; } // chop at first whitespace break before max chars
    else { $truncatedUrl = mb_substr($truncatedUrl, 0, $maxLength); } // otherwise force cut at maxlength (for content with no whitespace such as malicious or non-english)
    if (strlen($truncatedUrl) < strlen($record['url'])) { $truncatedUrl .= " ..."; }

    //
    $lineNumSuffix = $record['line_num'] ? "on line {$record['line_num']}" : "";
    $errorSummary  = "#{$record['num']} - {$record['error']}\n";
    if ($record['filepath']) { $errorSummary .= "{$record['filepath']} $lineNumSuffix\n"; }
    if ($truncatedUrl)       { $errorSummary .= "$truncatedUrl\n"; }

    $displayValue .= "<div style='line-height:1.5em'>" .nl2br(htmlencode($errorSummary)). "</div>";
  }

  return $displayValue;
}

//
function errorLog_errorsByFilepath() {
  global $schema, $tableName;

  // prepare adminUI() placeholders
  $adminUI = [];
  $adminUI['PAGE_TITLE'] = [
    $schema['menuName'] => '?menu=' . $tableName,
    //t("Errors by Filepath") => "?menu=_$tableName&action=filepathReport",
  ];
  $adminUI['DESCRIPTION'] = "Removing errors from the log will remove them from this list as well.";
//  $adminUI['BUTTONS']     = ""; // set in addFilter at top of page
  $adminUI['CONTENT']     = ob_capture('errorLog_errorsByFilepath_listContent');
  adminUI($adminUI);  // compose and output the page
  exit;
}



//
function errorLog_errorsByFilepath_listContent() {
  global $schema, $menu, $TABLE_PREFIX;

  // get errors by filepath
  $query = "SELECT filepath,
                   GROUP_CONCAT(DISTINCT line_num ORDER BY line_num ASC SEPARATOR ', ') AS line_nums,
                   COUNT(*) AS error_count,
                   MAX(dateLogged) AS 'latestDate'
              FROM {$TABLE_PREFIX}_error_log
          GROUP BY filepath
          ORDER BY MAX(dateLogged) DESC";
  $records = mysql_select_query($query);

  // Get lineNum to lastError for each filepath
  $filepathsToErrors = [];
  $query = "SELECT filepath, line_num, error, COUNT(*) AS error_count, MAX(dateLogged) AS latestDate
            FROM {$TABLE_PREFIX}_error_log
            GROUP BY filepath, line_num
            ORDER BY MAX(dateLogged) DESC";
  $results = mysql_select_query($query);
  foreach ($results as $result) {
    $filepathsToErrors[$result['filepath']][] = [
        $result['line_num'],
        $result['error'],
        $result['error_count'],
        $result['latestDate']
    ];
  }

  // print header
  print <<<__HEADER__

<script>
function confirmClearErrors(e, filepath, lineNum) {
  e.preventDefault(); // Prevents the default action
  var confirmation = confirm("Clear ALL errors for this filepath:\\n" + filepath + "\\n\\nAre you sure?");
  if (confirmation) {
    var url = "?menu=_error_log&action=filepathReport&filepath=" + filepath + "&clearFilepathErrors=1";
    window.location.href = url;
  }
}
</script>

<div class="horizontal-autoscroll">
  <table class="data table table-striped table-hover">
    <thead>
      <tr>
        <th>Last Reported</th>
        <th>Filepath</th>
        <th style='padding: 0px; text-align: center'>Action</th>
      </tr>
    </thead>
    <tbody>
__HEADER__;

  ### print rows
  foreach ($records as $record) {
  $latestDateHTML = "<span title='{$record['latestDate']}'>" .prettyDate($record['latestDate']). "</span>";
  $showLink       = "?menu=_error_log&filepath_keyword=" . urlencode($record['filepath']). "&showAdvancedSearch=1&_resetSavedSearch=1";

  // Get errors by line number for this filepath and errors on each
  $lineNumToErrorHTML = '';
  $linesNumToError = $filepathsToErrors[ $record['filepath'] ] ?? [];
  foreach ($linesNumToError as $index => [$lineNum, $latestError, $errorCount, $lastDate]) {
    $lineNumToErrorHTML .= htmlencodef("&nbsp;&nbsp; <span title='Line ?: ? errors, last reported: ?'>&ndash; Line ?: ?</span><br>\n", $lineNum, $errorCount, $lastDate, $lineNum, $latestError);
  }

  $jsFilePath = jsEncode($record['filepath']);
  print <<<__ROW__
      <tr>
        <td style='text-align:left'>$latestDateHTML</td>
        <td style='text-align:left'>
          <span style="font-weight: bold;">{$record['filepath']}</span>
          <span style="color: #333">
            ({$record['error_count']} error reports)<br>
            $lineNumToErrorHTML
          </span>
        </td>
        <td class='listActions' style='white-space: nowrap; padding: 0px 12px;'>
          <a href='$showLink'>show</a>
          <a href='#' onclick="confirmClearErrors(event, '$jsFilePath');">clear</a>
        </td>
      </tr>
__ROW__;
}

  // print header
  print <<<__FOOTER__
    </tbody>
  </table>
</div>
__FOOTER__;

}
