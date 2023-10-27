<?php

// prepare adminUI() placeholders
$adminUI = [];

// page title
$adminUI['PAGE_TITLE'] = [ t('Admin'), t('General Settings') => '?menu=admin&action=general' ];

// buttons
$adminUI['BUTTONS'] = [];
$adminUI['BUTTONS'][] = [ 'name' => 'action=adminSave', 'label' => t('Save'),   ];
$adminUI['BUTTONS'][] = [ 'name' => 'action=general',   'label' => t('Cancel'), ];

// form tag and hidden fields
$adminUI['FORM'] = [ 'autocomplete' => 'off' ];
$adminUI['HIDDEN_FIELDS'] = [
  [ 'name' => 'menu',           'value' => 'admin', ],
  [ 'name' => '_defaultAction', 'value' => 'adminSave', ],
];

// main content
$adminUI['CONTENT'] = ob_capture('_getContent');

// add extra html before form
$adminUI['PRE_FORM_HTML'] = ob_capture('_getPreFormContent');

// add extra html after the form
$adminUI['POST_FORM_HTML'] = ob_capture('_getPostFormContent');

// compose and output the page
adminUI($adminUI);

//
function _getPreFormContent() {

  ### CSS
?>
<style>
  ul > li { padding: 1px 4px; }
</style>
<?php


  ### SHOW OLD PHP/MYSQL WARNINGS
  $currentPhpVersion   = phpversion();
  $currentMySqlVersion = preg_replace("/[^0-9\.]/", '', mysqli()->server_info);

  // Reference - PHP Installed Versions: https://wordpress.org/about/stats/
  // Reference - PHP Installed Versions: https://w3techs.com/technologies/details/pl-php/all/all
  $nextPhpRequired = REQUIRED_PHP_VERSION; // Default to minimum version to recommend
  if (time() > strtotime('2023-11-26')) { $nextPhpRequired = '8.1'; } // Security support for previous version ends on this date: http://php.net/supported-versions.php
  if (time() > strtotime('2024-11-25')) { $nextPhpRequired = '8.2'; } // Security support for previous version ends on this date: http://php.net/supported-versions.php
  if (time() > strtotime('2025-12-08')) { $nextPhpRequired = '8.3'; } // Security support for previous version ends on this date: http://php.net/supported-versions.php

  $nextMySqlRequired   = '5.5'; // to support utf8mb4 : http://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html
  $isPhpUnsupported    = version_compare($currentPhpVersion, $nextPhpRequired) < 0;
  $isMySqlUnsupported  = version_compare($currentMySqlVersion, $nextMySqlRequired) < 0;
  $isSecurityIssue     = ($isPhpUnsupported || $isMySqlUnsupported);

  // Check for missing or soon to be required extensions
  $missingExtensions   = [];
  foreach (array('mysqli','openssl','curl') as $extension) {
    if (!extension_loaded($extension)) { $missingExtensions[] = $extension; }
  }

  if ($isSecurityIssue || $missingExtensions) {
    ?>
    <div style='color: #C00; border: solid 2px #C00; padding: 8px; background: #FFF; font-size: 14px; '>
      <?php if ($isSecurityIssue): ?>
        <b>Security Notice:</b>
        You are currently running old and unsupported server software that <b>no longer receives security updates</b>.
        To avoid being exposed to unpatched security vulnerabilities and to ensure compatibility with future CMS releases, please upgrade at your earliest convenience.<br>
      <?php else: ?>
        <b>Upgrade Warning:</b>
        You are currently missing some required PHP extensions.
        To ensure compatibility with future CMS releases, please have these extensions installed at your earliest convenience.<br>
      <?php endif ?>

      <div style="padding: 5px 5px 5px 25px;">
        <?php if ($isPhpUnsupported): ?>
          <li>Upgrade to <b>PHP v<?php echo $nextPhpRequired ?></b> or newer (Your server is running PHP v<?php echo $currentPhpVersion ?>)
        <?php endif ?>
        <?php if ($isMySqlUnsupported): ?>
          <li>Upgrade to <b>MySQL v<?php echo $nextMySqlRequired ?></b> or newer (Your server is running MySQL v<?php echo $currentMySqlVersion ?>)
        <?php endif ?>
        <?php foreach ($missingExtensions as $extension): ?>
          <li>Install missing PHP extension: <b><?php echo htmlencode($extension); ?></b> (required for future updates)
        <?php endforeach ?>
      </div>

      <?php if ($isSecurityIssue): ?>
        More information:
        <a href="http://php.net/supported-versions.php" target="_blank">PHP Supported Versions</a>,
        <a href="https://en.wikipedia.org/wiki/MySQL#Release_history" target="_blank">MySQL Supported Versions</a>
      <?php endif ?>
    </div><br>
    <?php
  }

}

function _getPostFormContent() {
  ?>
    <script>

      //
      function updateDatePreviews() {
        var url = "?menu=admin&action=updateDate";
        url    += "&timezone=" + escape( $('#timezone').val() );

        $.ajax({
          url: url,
          dataType: 'json',
          error:   function(XMLHttpRequest, textStatus, errorThrown){
            alert("There was an error sending the request! (" +XMLHttpRequest['status']+" "+XMLHttpRequest['statusText'] + ")\n" + errorThrown);
          },
          success: function(json){
            var error = json[2];
            if (error) { return alert(error); }
            $('#localDate').html(json[0]);
            $('#mysqlDate').html(json[1]);
            //$('#localDate, #mysqlDate').attr('style', 'background-color: #FFFFCC');
          }
        });
      }

    </script>
  <?php
}


function _getContent() {
  global $SETTINGS, $APP, $CURRENT_USER, $TABLE_PREFIX;

  $totalBytes    = @disk_total_space(__DIR__);
  $freeBytes     = @disk_free_space(__DIR__);
  [$maxCpuSeconds, $memoryLimitKbytes, $maxProcessLimit, $ulimitOutput] = getUlimitValues('soft');
  if     ($maxCpuSeconds == '')              { $maxCpuSeconds_formatted = t('none'); }
  elseif ($maxCpuSeconds == 'unlimited')     { $maxCpuSeconds_formatted = t('unlimited'); }
  elseif ($maxCpuSeconds == 'unknown')       { $maxCpuSeconds_formatted = t('unknown'); }
  else                                       { $maxCpuSeconds_formatted = "$maxCpuSeconds " . t('seconds'); }
  if     ($memoryLimitKbytes == '')          { $memoryLimit_formatted = t('none'); }
  elseif ($memoryLimitKbytes == 'unlimited') { $memoryLimit_formatted = t('unlimited'); }
  elseif ($memoryLimitKbytes == 'unknown')   { $memoryLimit_formatted = t('unknown'); }
  else                                       { $memoryLimit_formatted = formatBytes($memoryLimitKbytes*1024); }
  $ulimitLink = "?menu=admin&amp;action=ulimit";

  ?>
  <script>
    // redirect old links to sections that have moved elsewhere
    if (location.hash == '#background-tasks')  { window.location = '?menu=admin&action=bgtasks'; }        // redirect ?menu=admin&action=general#background-tasks
    if (location.hash == '#email-settings')    { window.location = '?menu=admin&action=email'; }          // redirect ?menu=admin&action=general#email-settings
    if (location.hash == '#backup-restore')    { window.location = '?menu=admin&action=backuprestore'; }  // redirect ?menu=admin&action=general#backup-restore
    if (location.hash == '#security-settings') { window.location = '?menu=admin&action=security'; }       // redirect ?menu=admin&action=general#security-settings
  </script>



      <?php echo adminUI_separator([
          'label' => t('Program Information'),
          'href'  => "?menu=admin&action=general#program-info",
          'id'    => "program-info",
        ]);
      ?>

    <div class="form-horizontal">

      <div class="form-group">
        <div class="col-sm-3 control-label"><?php et('Program Name');?></div>
        <div class="col-sm-8 form-control-static">
          <?php echo htmlencode($SETTINGS['programName']) ?>
          v<?php echo htmlencode($APP['version']) ?> (Build <?php echo htmlencode($APP['build']) ?>)
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-3 control-label"><?php et('Vendor'); ?></div>
        <div class="col-sm-8 form-control-static">
          <a href="<?php echo htmlencode($SETTINGS['vendorUrl']) ?>"><?php echo htmlencode($SETTINGS['vendorName']) ?></a>
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-3 control-label"><?php et('License Agreement');?></label>
        <div class="col-sm-8 form-control-static">
            <a href="?menu=license"><?php et('License Agreement');?> &gt;&gt;</a>
        </div>
      </div>

      <?php echo adminUI_separator([
          'label' => t('Directories & URLs'),
          'href'  => "?menu=admin&action=general#dirs-urls",
          'id'    => "dirs-urls",
        ]);
      ?>

      <div class="form-group">
        <label class="col-sm-3 control-label" for="null">
          <?php et('Program Directory');?>
        </label>
        <div class="col-sm-8">
          <input class="form-control" type="text" name="null" id="null" value="<?php echo htmlencode($GLOBALS['PROGRAM_DIR']) ?>/">
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="adminUrl">
          <?php et('Program Url');?>
        </label>
        <div class="col-sm-8">
            <input class="form-control" type="text" name="adminUrl" id="adminUrl" value="<?php echo htmlencode(@$SETTINGS['adminUrl']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="webRootDir">
          <?php et('Website Root Directory');?>
        </label>
        <div class="col-sm-8">
          <input class="form-control" type="text" name="webRootDir" id="webRootDir" value="<?php echo htmlencode(@$SETTINGS['webRootDir']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-3 control-label" for="uploadDir">
          <?php et('Upload Directory');?>
        </label>
        <div class="col-sm-8">
          <input class="form-control" type="text" name="uploadDir" id="uploadDir" value="<?php echo htmlencode(@$SETTINGS['uploadDir']) ?>" onkeyup="updateUploadPathPreviews('dir', this.value, 0)" onchange="updateUploadPathPreviews('dir', this.value, 0)">
          <p><?php et('Preview:'); ?> <code id="uploadDirPreview"><?php echo htmlencode(getUploadPathPreview('dir', $SETTINGS['uploadDir'], false, false)); ?></code></p>
          <p><?php et('Example:'); ?> <code>uploads</code> or <code>../uploads</code> (relative to program directory)</p>
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-3 control-label" for="uploadUrl">
          <?php et('Upload Folder URL');?>
        </label>
        <div class="col-sm-8">
          <input class="form-control" type="text" name="uploadUrl" id="uploadUrl" value="<?php echo htmlencode(@$SETTINGS['uploadUrl']) ?>" onkeyup="updateUploadPathPreviews('url', this.value, 0)" onchange="updateUploadPathPreviews('url', this.value, 0)">
          <p><?php et('Preview:'); ?> <code id="uploadUrlPreview"><?php echo htmlencode(getUploadPathPreview('url', $SETTINGS['uploadUrl'], false, false)); ?></code></p>
          <p><?php et('Example:'); ?> <code>uploads</code> or <code>../uploads</code> (relative to current URL)</p>
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-3 control-label">
          Server Upload Settings
        </div>
        <div class="col-sm-8">
          <div class="table-wrap">
          <table class="table table-bordered" id="sample-table-1">
            <thead>
              <tr>
                <th><?php et("Upload settings"); ?></th>
                <th><?php et("Upload time limits"); ?></th>
                <th><?php et("File size limits") ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><a href="http://php.net/manual/en/ini.core.php#ini.file-uploads" target="_blank">file_uploads</a>: <?php echo ini_get('file_uploads') ? t('enabled') : t('disabled'); ?></td>
                <td><a href="http://php.net/manual/en/info.configuration.php#ini.max-input-time" target="_blank">max_input_time</a>: <?php echo ini_get('max_input_time') ?></td>
                <td><a href="http://php.net/manual/en/function.disk-free-space.php" target="_blank">free disk space</a>: <?php echo $freeBytes ? formatBytes($freeBytes, 0) : t("Unavailable"); ?></td>
              </tr>
              <tr>
                <td><a href="http://php.net/manual/en/ini.core.php#ini.max-file-uploads" target="_blank">max_file_uploads</a>: <?php echo ini_get('max_file_uploads') ?></td>
                <td><a href="http://php.net/manual/en/info.configuration.php#ini.max-execution-time" target="_blank">max_execution_time</a>: <?php echo ini_get('max_execution_time') ?></td>
                <td><a href="http://php.net/manual/en/ini.core.php#ini.post-max-size" target="_blank">post_max_size</a>: <?php echo ini_get('post_max_size') ?></td>
              </tr>
              <tr>
                <td><a href="http://php.net/manual/en/ini.core.php#ini.upload-tmp-dir" target="_blank">upload_tmp_dir</a>: <?php echo ini_get('upload_tmp_dir'); ?></td>
                <td><a href="<?php echo $ulimitLink ?>" target="_blank">ulimit max cpu seconds</a>: <?php echo $maxCpuSeconds_formatted; ?></td>
                <td><a href="http://php.net/manual/en/ini.core.php#ini.upload-max-filesize" target="_blank">upload_max_filesize</a>: <?php echo ini_get('upload_max_filesize') ?></td>
              </tr>
              <tr>
                <td></td>
                <td></td>
                <td><a href="http://php.net/manual/en/ini.core.php#ini.memory-limit" target="_blank">memory_limit</a>: <?php echo ini_get('memory_limit') ?></td>
              </tr>
              <tr>
                <td></td>
                <td></td>
                <td><a href="<?php echo $ulimitLink ?>" target="_blank">ulimit memory limit</a>: <?php echo $memoryLimit_formatted; ?></td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3">
                  <a href="http://www.php.net/manual/en/features.file-upload.php" target="_blank"><?php et('How to configure PHP uploads')?></a>
                  (<?php et('for server admins')?>)
                </th>
              </tr>
            </tfoot>
          </table>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="webPrefixUrl">
          <?php et('Website Prefix URL (optional)');?>
        </label>
        <div class="col-sm-8">
          <input class="form-control" type="text" name="webPrefixUrl" id="webPrefixUrl" value="<?php echo htmlencode(@$SETTINGS['webPrefixUrl']) ?>">
          eg: <code><?php eht("eg: /~username or /development/client-name"); ?></code>
          <p>If your development server uses a different URL prefix than your live server you can specify it here. This prefix will be automatically added to Viewer URLs and can be displayed with <code>&lt;?php echo PREFIX_URL ?&gt;</code> for other urls. This will allow you to easily move files between a development and live server, even if they have different URL prefixes.</p>
        </div>
      </div>


      <div class="form-group">
        <label class="col-sm-3 control-label" for="helpUrl">
          <?php et('Help (?) URL') ?>
        </label>
        <div class="col-sm-8">
          <input name="helpUrl" type="text" id="helpUrl" class="form-control" value="<?php echo htmlencode($SETTINGS['helpUrl']); ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="websiteUrl">
          <?php et("'View Website' URL") ?>
        </label>
        <div class="col-sm-8">
          <input name="websiteUrl" type="text" id="websiteUrl" class="form-control" value="<?php echo htmlencode($SETTINGS['websiteUrl']) ?>">
        </div>
      </div>



      <?php echo adminUI_separator([
          'label' => t('Regional Settings'),
          'href'  => "?menu=admin&action=general#regional-settings",
          'id'    => "regional-settings",
        ]);
      ?>

      <div class="form-group">
        <label class="col-sm-3 control-label" for="timezone">
          <?php et('Timezone Name');?>
        </label>
        <div class="col-sm-8">
          <?php $timeZoneOptions = getTimeZoneOptions($SETTINGS['timezone']); ?>
          <select name="timezone" id="timezone" class="form-control" onchange="updateDatePreviews();">
            <?php echo $timeZoneOptions; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-3 control-label">
          <?php et('Local Time');?>
        </div>
        <div class="col-sm-8">
          <div class="form-control" id="localDate" >
            <?php
            $offsetSeconds = date("Z");
            $offsetString  = convertSecondsToTimezoneOffset($offsetSeconds);
            $localDate = date("D, M j, Y - g:i:s A") . " ($offsetString)";
            echo $localDate;
            ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-3 control-label">
          <?php et('MySQL Time');?>
        </div>
        <div class="col-sm-8">
          <div class="form-control"  id="mysqlDate" >
            <?php
            [$mySqlDate, $mySqlOffset] = mysql_get_query("SELECT NOW(), @@session.time_zone", true);
            echo date("D, M j, Y - g:i:s A", strtotime($mySqlDate)) . " ($mySqlOffset)";
            ?>
          </div>
        </div>
      </div>
      <?php if (!@$SETTINGS['advanced']['hideLanguageSettings']): ?>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="language">
          <?php et('Program Language');?>
        </label>
        <div class="col-sm-8">
          <?php // load language file names - do this here so errors are visible and not hidden in select tags
            $programLange   = []; // key = filename without ext, value = selected boolean
            $programLangDir = "{$GLOBALS['PROGRAM_DIR']}/lib/languages/";
            foreach (scandir($programLangDir) as $filename) {
              @list($basename, $ext) = explode(".", $filename, 2);
              if ($ext != 'php') { continue; }
              if (str_starts_with($basename, "_")) { continue; } // skip internal scripts
              $programLangs[$basename] = 1;
            }
          ?>
          <select name="language" id="language" class="form-control"><?php // 2.50 the ID is used for direct a-name links ?>
          <option value=''>&lt;select&gt;</option>
          <option value='' <?php selectedIf($SETTINGS['language'], ''); ?>>default</option>
            <?php
              foreach (array_keys($programLangs) as $lang) {
                $selectedAttr = $lang == $SETTINGS['language'] ? 'selected="selected"' : '';
                print "<option value=\"$lang\" $selectedAttr>$lang</option>\n";
              }
            ?>
          </select>
          <?php print sprintf(t('Languages are in %s'),'<code>/lib/languages/</code> or <code>/plugins/.../languages/</code>') ?>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="wysiwygLang">
          <?php et('WYSIWYG Language');?>
        </label>
        <div class="col-sm-8">
          <?php // load language file names - do this here so errors are visible and not hidden in select tags
            $wysiwygLangs   = []; // key = filename without ext, value = selected boolean
            $wysiwygLangDir = "{$GLOBALS['CMS_ASSETS_DIR']}/3rdParty/TinyMCE4/langs/";
            foreach (scandir($wysiwygLangDir) as $filename) {
              @list($basename, $ext) = explode(".", $filename, 2);
              if ($ext != 'js') { continue; }
              $wysiwygLangs[$basename] = 1;
            }
          ?>
          <select name="wysiwygLang" id="wysiwygLang" class="form-control">
          <option value="en">&lt;select&gt;</option>
            <?php
              foreach (array_keys($wysiwygLangs) as $lang) {
                $selectedAttr = $lang == $SETTINGS['wysiwyg']['wysiwygLang'] ? 'selected="selected"' : '';
                print "<option value=\"$lang\" $selectedAttr>$lang</option>\n";
              }
            ?>
          </select>
          <a href="http://tinymce.moxiecode.com/download_i18n.php" target="_BLANK"><?php eht("Download more languages..."); ?></a>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-3 control-label">
          <?php et('Developer Mode');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'languageDeveloperMode',
            'label'   => t("Automatically add new language strings to language files"),
            'checked' => $SETTINGS['advanced']['languageDeveloperMode'],
          ]) ?>
        </div>
      </div>
      <?php endif ?>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="dateFormat">
          <?php et('Date Field Format');?>
        </label>
        <div class="col-sm-8">
          <select name="dateFormat" id="dateFormat" class="form-control">
            <option value=''>&lt;select&gt;</option>
            <option value='' <?php selectedIf($SETTINGS['dateFormat'], '') ?>>default</option>
            <option value="dmy" <?php selectedIf($SETTINGS['dateFormat'], 'dmy') ?>>Day Month Year</option>
            <option value="mdy" <?php selectedIf($SETTINGS['dateFormat'], 'mdy') ?>>Month Day Year</option>
          </select>
        </div>
      </div>

      <?php echo adminUI_separator([
          'label' => t('Advanced Settings'),
          'href'  => "?menu=admin&action=general#advanced-settings",
          'id'    => "advanced-settings",
        ]);
      ?>

      <div class="form-group">
        <label class="col-sm-3 control-label" for="imageResizeQuality">
          <?php et('Image Resizing Quality');?>
        </label>
        <div class="col-sm-8">
          <select name="imageResizeQuality" id="imageResizeQuality" class="form-control">
            <option value="65" <?php selectedIf($SETTINGS['advanced']['imageResizeQuality'], '65'); ?>><?php et('Minimum - Smallest file size, some quality loss')?></option>
            <option value="80" <?php selectedIf($SETTINGS['advanced']['imageResizeQuality'], '80'); ?>><?php et('Normal - Good balance of quality and file size')?></option>
            <option value="90" <?php selectedIf($SETTINGS['advanced']['imageResizeQuality'], '90'); ?>><?php et('High - Larger file size, high quality')?></option>
            <option value="100" <?php selectedIf($SETTINGS['advanced']['imageResizeQuality'], '100'); ?>><?php et('Maximum - Very large file size, best quality')?></option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-3 control-label">
          <?php et('WYSIWYG Options');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'includeDomainInLinks',
            'label'   => t('Save full URL for local links and images (for viewers on other domains)'),
            'checked' => $SETTINGS['wysiwyg']['includeDomainInLinks'],
          ]) ?>
        </div>

        <div class="col-sm-3 control-label">
          <?php et('Use WebP');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'convertUploadsToWebp',
            'label'   => t('Automatically convert image uploads to WebP format'),
            'checked' => $SETTINGS['advanced']['convertUploadsToWebp'],
          ]) ?>
        </div>

        <div class="col-sm-3 control-label">
          <?php et('Code Generator');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'codeGeneratorExpertMode',
            'label'   => t('Expert mode - don\'t show instructions or extra html in Code Generator output'),
            'checked' => @$SETTINGS['advanced']['codeGeneratorExpertMode'],
          ]) ?>
        </div>

        <div class="col-sm-3 control-label">
          <?php et('Disable HTML5 Uploader');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'disableHTML5Uploader',
            'label'   => t('Disable HTML5 Uploader - attach one file at a time (doesn\'t require html5 support)'),
            'checked' => @$SETTINGS['advanced']['disableHTML5Uploader'],
          ]) ?>
        </div>

        <div class="col-sm-3 control-label">
          <?php et('Menu Options');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'showExpandedMenu',
            'label'   => t("Always show expanded menu (don't hide unselected menu groups)"),
            'checked' => $SETTINGS['advanced']['showExpandedMenu'],
          ]) ?>
        </div>

        <?php if (array_key_exists('showExpandedMenu', $CURRENT_USER)): ?>
          <div class="col-sm-3 control-label">
            <?php et('Updated');?>
          </div>
          <div class="col-sm-8">
            <?php et("This option is now being ignored and being set on a per user basis with the 'showExpandedMenu' field in")?> <a href="?menu=accounts"><?php et('User Accounts')?></a>.
          </div>
        <?php endif ?>

        <div class="col-sm-3 control-label">
          <?php et('Use Datepicker');?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'useDatepicker',
            'label'   => t("Display datepicker icon and popup calendar beside date fields"),
            'checked' => $SETTINGS['advanced']['useDatepicker'],
          ]) ?>
        </div>

        <div class="col-sm-3 control-label">
          <?php et('Use Media Library'); ?>
        </div>
        <div class="col-sm-8">
          <?php echo adminUI_checkbox([
            'name'    => 'useMediaLibrary',
            'label'   => t("Enable Media Library that allows using uploads in multiple sections") . " (BETA)",
            'checked' => $SETTINGS['advanced']['useMediaLibrary'],
          ]) ?>
        </div>

      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="session_save_path">
          <?php et('session.save_path');?>
        </label>
        <div class="col-sm-8">
          <input class="text-input wide-input form-control" type="text" name="session_save_path" id="session_save_path" value="<?php echo htmlencode(@$SETTINGS['advanced']['session_save_path']) ?>" size="60">
          <?php et("If your server is expiring login sessions too quickly set this to a new directory outside of your web root or leave blank for default value of:"); ?> <code><?php echo htmlencode(get_cfg_var('session.save_path')); ?></code>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-3 control-label" for="session_cookie_domain">
          <?php et('session.cookie_domain');?>
        </label>
        <div class="col-sm-8">
          <input class="text-input wide-input form-control" type="text" name="session_cookie_domain" id="session_cookie_domain" value="<?php echo htmlencode(@$SETTINGS['advanced']['session_cookie_domain']) ?>" size="60">
          <?php et("To support multiple subdomains set to parent domain (eg: example.com), or leave blank to default to current domain."); ?>
        </div>
      </div>

      <?php echo adminUI_separator([
          'label' => t('Server Info'),
          'href'  => "?menu=admin&action=general#server-info",
          'id'    => "server-info",
        ]);
      ?>



      <div class="form-group">

        <?php [$heading, $details] = __serverInfo_contentDeliveryNetwork(); ?>
        <div class="col-sm-3 control-label"><?php eht('Content Delivery Network'); ?></div>
        <div class="col-sm-8 form-control-static">
          <div class="text-left">
            <?php echo $heading; ?><br>
            <ul style="margin-bottom: 0px">
              <?php foreach ($details as $detail) { print "<li>$detail</li>\n"; } ?>
            <ul>
          </div>
        </div>

        <?php [$heading, $details] = __serverInfo_operatingSystem(); ?>
        <div class="col-sm-3 control-label"><?php eht('Operating System'); ?></div>
        <div class="col-sm-8 form-control-static">
          <div class="text-left">
            <?php echo $heading; ?><br>
            <ul style="margin-bottom: 0px">
              <?php foreach ($details as $detail) { print "<li>$detail</li>\n"; } ?>
            <ul>
          </div>
        </div>

        <?php [$heading, $details] = __serverInfo_webServer(); ?>
        <div class="col-sm-3 control-label"><?php eht('Web Server'); ?></div>
        <div class="col-sm-8 form-control-static">
          <div class="text-left">
            <?php echo $heading; ?><br>
            <ul style="margin-bottom: 0px">
              <?php foreach ($details as $detail) { print "<li>$detail</li>\n"; } ?>
            <ul>
          </div>
        </div>

        <?php [$heading, $details] = __serverInfo_databaseServer(); ?>
        <div class="col-sm-3 control-label"><?php eht('Database Server'); ?></div>
        <div class="col-sm-8 form-control-static">
          <div class="text-left">
            <?php echo $heading; ?><br>
            <ul id="serverInfo_databaseServer" style="margin-bottom: 0px">
              <?php foreach ($details as $detail) { print "<li>$detail</li>\n"; } ?>
            <ul>
            <script>
              $(document).ready(function() {
                hideExtraBullets('serverInfo_databaseServer', 3);
            });
            </script>
          </div>
        </div>


        <?php [$heading, $details] = __serverInfo_phpVersion(); ?>
        <div class="col-sm-3 control-label"><?php eht('PHP Version'); ?></div>
        <div class="col-sm-8 form-control-static">
          <div class="text-left">
            <?php echo $heading; ?><br>
            <ul style="margin-bottom: 0px">
              <?php foreach ($details as $detail) { print "<li>$detail</li>\n"; } ?>
            <ul>
          </div>
        </div>

        <?php [$heading, $details] = __serverInfo_recentChanges(); ?>
        <div class="col-sm-3 control-label"><?php eht('Recent Changes'); ?></div>
        <div class="col-sm-8 form-control-static">
          <div class="text-left">
            <?php echo $heading ? "$heading<br>" : ""; // don't show <br> if no heading ?>
            <ul id="serverInfo_recentChanges" style="margin-bottom: 0px">
              <?php foreach ($details as $detail) { print "<li>$detail</li>\n"; } ?>
            <ul>
            <script>
              $(document).ready(function() {
                hideExtraBullets('serverInfo_recentChanges', 3);
            });
            </script>
          </div>
        </div>

<script>
function hideExtraBullets(targetId, initialVisibleCount, linkText = 'More...'){
    var $ul = $('#' + targetId); // Your <ul> tag identified by its ID
    var $lis = $ul.find('li'); // All <li> items in your <ul>

    // Check if the total list items are greater than the initial visible count
    if ($lis.length > initialVisibleCount) {
        // Initially hide all <li> items over the limit
        $lis.slice(initialVisibleCount).hide();

        // Create the "More..." link
        var $moreLink = $('<a>', { href: '#', text: linkText });

        // Wrap "More..." link in <li> tag
        var $moreListItem = $('<li>').append($moreLink);

        // Append "More..." link to the end of the <ul>
        $ul.append($moreListItem);

        // Event handler for "More..." link click
        $moreLink.on('click', function(e) {
            e.preventDefault(); // Prevent default action (href="#")

            // Show all hidden <li> items and hide the "More..." link
            $lis.show();
            $moreListItem.hide();
        });
    }
}
</script>




      </div>
    </div>
  <?php
}


// Get an array of info about the caching server or CDN being used. the first line is displayed as a heading, following lines as bullets
// Usage: [$heading, $details] = __serverInfo_contentDeliveryNetwork();
function __serverInfo_contentDeliveryNetwork() {
  $heading = "";
  $details = [];

  ### get Content Delivery Network Name
  [$cdnName, $cdnMoreInfo] = serverInfo_contentDeliveryNetwork();
  if ($cdnName) { $heading = htmlencodef("<u title='?'>?</u>", $cdnMoreInfo, $cdnName); }
  else          { $heading = t("None detected"); }

  ### Get Caching and HTTP_X_ Headers
  // Example Headers
  //$_SERVER = $_SERVER + [
  //    // CDN specific headers
  //    'HTTP_CF_RAY'                          => '6aecf1234bfa82a1-JFK', // Cloudflare's Ray ID
  //    'HTTP_X_AMZ_CF_ID'                     => 'aNJRlWc7P8C9v0qzUOxRz6X8kgnYiJ.Aws00m6V2isXOeuQ==', // Amazon CloudFront's request ID
  //    'HTTP_X_CACHE'                         => 'HIT', // Akamai's cache HIT or MISS indicator
  //    'HTTP_CDN_LOOP'                        => 'fastly', // Fastly's loop prevention header
  //    'HTTP_X_SUCURI_ID'                     => '15015', // Sucuri's unique identifier for the request
  //    'HTTP_X_EDGE_CONNECT_ORIGIN_MEX_LATENCY' => '12', // EdgeCast (Verizon Media)'s latency in milliseconds
  //
  //    // General headers
  //    'HTTP_SERVER'                          => 'nginx/1.17.9', // The web server's software and version
  //    'HTTP_X_POWERED_BY'                    => 'PHP/7.4.3', // The server-side scripting language or framework in use
  //
  //    // Caching headers
  //    'HTTP_X_CACHE'                         => 'HIT', // General caching header, used by Akamai and others
  //    'HTTP_X_CDN'                           => 'StackPath', // General CDN header, used by StackPath and others
  //    'HTTP_X_CACHE_HIT'                     => 'Yes', // Cache hit indicator, used by some CDNs
  //    'HTTP_AGE'                             => '3600', // Age of cached content in seconds, used by some CDNs
  //    'HTTP_VIA'                             => '1.1 varnish', // Some CDNs and proxies use this header to indicate their presence
  //];
  $xHeadersHTML = "";
  $skipHeaders = [
    'HTTP_X_HTTPS',
  ];

  $headerCount = 0;
  foreach ($_SERVER as $key => $value) {
    if (preg_match("/^HTTP_(X|AGE|VIA|CDN|CF)_/i", $key)) {
      if (in_array($key, $skipHeaders)) { continue; }
      $xHeadersHTML .= htmlencodef("<li>?: ?</li>\n", $key, $value);
      $headerCount++;
    }
  }
  $detailHTML = "Caching &amp; Custom Headers ";
  $onclickJS = '$(".http_x_toggle").toggle(); return false;';
  if ($headerCount) {
    $detailHTML .= "(<a href='#' onclick='$onclickJS'><span class='http_x_toggle'>" .t('show') ." ". $headerCount. "</span>";
    $detailHTML .=  "<span class='http_x_toggle' style='display:none'>" .t('hide'). "</span></a>)\n";
    $detailHTML .= "<ul class='http_x_toggle' style='display: none'>\n$xHeadersHTML</ul>\n";
  }
  else {
    $detailHTML .= "(none)";
  }
  $details[] = $detailHTML;


  ###
  return [$heading, $details];
}



// Get an array of info about the web server, the first line is displayed as a heading, following lines as bullets
// Usage: [$heading, $details] = __serverInfo_webServer();
function __serverInfo_webServer() {
  $heading = "";
  $details = [];

  ### get web server name
  [$name, $moreInfo] = serverInfo_webServer();
  $heading .= htmlencodef("<u title='?'>?</u>", $moreInfo, $name);

  ### get web control panels
  [$name, $moreInfo, $links] = serverInfo_webServer_controlPanel();
  $controlPanelHTML  = htmlencode(t('Control Panel')). ": ";
  $controlPanelHTML .= $name  ? htmlencode($name) : t("None detected");
  $controlPanelHTML .= $links ? " $links" : '';
  $details[] = $controlPanelHTML;

  ###
  return [$heading, $details];
}


// Get an array of info about the OS, the first line is displayed as a heading, following lines as bullets
// Usage: [$heading, $details] = __serverInfo_operatingSystem();
function __serverInfo_operatingSystem() {
  $heading = "";
  $details = [];

  ### get os name and links
  [ $osName, $osMoreInfo ] = serverInfo_operatingSystem();
  $osNameHTML = htmlencodef("<u title='?'>?</u>", $osMoreInfo, $osName);
  if (isWindows()) {
    $osNameHTML .= " (<a href='?menu=admin&action=ver'>ver</a>, <a href='?menu=admin&action=systeminfo'>systeminfo</a>)";
  }
  if (!isWindows() && !isMac()) {
    $osNameHTML .= " (<a href='?menu=admin&action=releases'>release</a>)";
  }
  $heading = $osNameHTML;

  ### Get server name and IP address
  [ $serverName, $serverNameMoreInfo ] = serverInfo_serverName();
  [ $ServerAddr, $serverAddrMoreInfo ] = serverInfo_serverAddr();
  $serverNameText     = $serverName ? htmlencode($serverName) : t("Unknown");
  $serverAddrText     = $ServerAddr ? htmlencode($ServerAddr) : t("Unknown");
  $serverNameAddrHTML = sprintf("%s: <code title='%s'>%s</code>", t("Server Name"), htmlencode($serverNameMoreInfo), htmlencode($serverNameText));
  $serverNameAddrHTML .= sprintf(" %s: <code title='%s'>%s</code>", t("Server IP"), htmlencode($serverAddrMoreInfo), htmlencode($serverAddrText));
  $details[]          = $serverNameAddrHTML;

  ### Get disk space
  $diskSpaceHTML       = "<div style='float: left;'>".t('Disk Space Used').": </div>";
  $totalBytes          = @disk_total_space(__DIR__);
  $freeBytes           = @disk_free_space(__DIR__);
  $totalBytesFormatted = $totalBytes ? formatBytes($totalBytes) : t("Unavailable");
  $freeBytesFormatted  = $totalBytes ? formatBytes($freeBytes) : t("Unavailable");

  $percentFull = 0;
  if ($totalBytes && $freeBytes) {
    $usedBytes   = $totalBytes - $freeBytes;
    $percentFull = intval(($usedBytes / $totalBytes) * 100);
  }

  if ($percentFull >= 90) {
    $barStyle = "background-color: #dc3545;";
  }
  elseif ($percentFull >= 80) {
    $barStyle = "background-color: #ffc107; color: #000;";
  }
  else {
    $barStyle = "";
  }

  if ($totalBytes) {
    $diskSpaceStats = sprintf(t('%1$s free of %2$s'), $freeBytesFormatted, $totalBytesFormatted);
  }
  else {
    $diskSpaceStats = t("Unavailable");
  } // for servers that return 0 and "Warning: Value too large for defined data type" on big ints
  if (!isWindows() && !isMac()) {
    $diskSpaceStats .= " (<a href='?menu=admin&action=du' target='_blank'>largest dirs</a>)";
  }

  // show progress bar
  $diskSpaceTitle = "Space free: $freeBytesFormatted\nTotal size: $totalBytesFormatted";
  $diskSpaceHTML  .= <<<__HTML__
<div class="progress" style="height: 18px; width: 200px; margin-bottom: 0; margin: 0 0.5em; float: left;" title="$diskSpaceTitle">
  <div class="progress-bar" role="progressbar" style="width: $percentFull%; $barStyle" aria-valuenow="$percentFull" aria-valuemin="0" aria-valuemax="100">$percentFull%</div>
</div>
$diskSpaceStats

<div style="clear: both;"></div>
__HTML__;
  $details[]      = $diskSpaceHTML;

  ### Get server resource limits
  $limitsHTML = htmlencode(t('Resource Limits')).": ";

  [ $maxCpuSeconds, $memoryLimitKbytes, $maxProcessLimit, $ulimitOutput ] = getUlimitValues('soft');
  if ($maxCpuSeconds == '') {
    $maxCpuSeconds_formatted = t('none');
  }
  elseif ($maxCpuSeconds == 'unlimited') {
    $maxCpuSeconds_formatted = t('unlimited');
  }
  elseif ($maxCpuSeconds == 'unknown') {
    $maxCpuSeconds_formatted = t('unknown');
  }
  else {
    $maxCpuSeconds_formatted = "$maxCpuSeconds ".t('seconds');
  }
  if ($memoryLimitKbytes == '') {
    $memoryLimit_formatted = t('none');
  }
  elseif ($memoryLimitKbytes == 'unlimited') {
    $memoryLimit_formatted = t('unlimited');
  }
  elseif ($memoryLimitKbytes == 'unknown') {
    $memoryLimit_formatted = t('unknown');
  }
  else {
    $memoryLimit_formatted = formatBytes($memoryLimitKbytes * 1024);
  }
  $maxProcessLimit_formatted = $maxProcessLimit ?: t('unknown');
  $ulimitLink                = "?menu=admin&amp;action=ulimit";
  if ($maxCpuSeconds || $memoryLimitKbytes || $maxProcessLimit) {
    $limitsHTML .= "CPU Time: <code>$maxCpuSeconds_formatted</code> ";
    $limitsHTML .= "Memory Limit: <code>$memoryLimit_formatted</code> ";
    $limitsHTML .= "Processes: <code>$maxProcessLimit_formatted</code> (<a href='$ulimitLink'>ulimit</a>)";
    $details[]  = $limitsHTML; // don't show
  }
  else { // No resource limits could be detected
    // Don't show resource limits line on windows because they're never available
    // ... Show "unavailable" on other platforms
    if (!isWindows()) {
      $limitsHTML .= t("Unavailable");
      $details[]  = $limitsHTML;
    }
  }

  // Show system uptime
  $lastRebootTimestamp = null;
  if (isWindows() && extension_loaded('com_dotnet')) {
    $wmiService  = new COM('winmgmts:{impersonationLevel=impersonate}//./root/cimv2');
    $swbemObject = $wmiService->ExecQuery('SELECT * FROM Win32_OperatingSystem')->ItemIndex(0);
    $cimDateTime = $swbemObject->LastBootUpTime; // yyyyMMddHHmmss.xxxxxx±UUU
    if (preg_match("/\b(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\b/", $cimDateTime, $matches)) {
      $dateTimeObj         = new DateTime("{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}");
      $lastRebootTimestamp = $dateTimeObj->getTimestamp();
    }
  }
  elseif (isWindows()) {
    $output = shell_exec('wmic os get lastbootuptime') ?? '';
    if (preg_match("/\b(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\b/", $output, $matches)) { // match cimDateTime string
      $cimDateTime         = $matches[0];
      $dateTimeObj         = new DateTime("{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}:{$matches[5]}:{$matches[6]}");
      $lastRebootTimestamp = $dateTimeObj->getTimestamp();
    }
  }
  else {                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  // Linux, etc.
    $procUptimeSeconds   = trim(uber_file_get_contents('/proc/uptime') ?: '');                                                                                                                                                                                                                                                                                    // Get system uptime seconds from special /proc/uptime file. This file contains two numbers, the first one is the uptime of the system in seconds.
    $uptimeDateTime = shellCommand('uptime -s');                                                                                                                                                                                                                                                                                                                                                                                                                                                       // Get date and time when the system was last booted up, in a 'YYYY-MM-DD HH:MM:SS'
    $procUptimeTimestamp = $procUptimeSeconds ? time() - intval($procUptimeSeconds) : null;
    $uptimeTimestamp     = isset($uptimeDateTime) ? strtotime($uptimeDateTime) : null;
    $lastRebootTimestamp = $procUptimeTimestamp ?? $uptimeTimestamp;
  }

  // get title
  $title = !empty($lastRebootTimestamp) ? "Last Rebooted: ".mysql_datetime($lastRebootTimestamp)."\n" : '';
  if (isset($cimDateTime))       { $title .= "lastbootuptime: $cimDateTime\n"; }
  if (isset($procUptimeSeconds)) { $title .= "/proc/uptime: $procUptimeSeconds\n"; }
  if (isset($uptimeDateTime))    { $title .= "uptime -s: $uptimeDateTime\n"; }
  $title = trim($title);

  // get display line
  if (!empty($lastRebootTimestamp)) {
    $interval   = (new DateTime())->setTimestamp($lastRebootTimestamp)->diff(new DateTime());
    $uptimeText = $interval->format('%a days, %h hours, %i minutes');
    $details[]  = "<span title='".htmlencode($title)."'>".htmlencode(t('Last Rebooted')).": ".$uptimeText." ago</span>\n";
  }
//  else {
//    $details[]  = htmlencode(t('Last Rebooted')) . ": ".  t("Unavailable"). "\n";
//  }

  ###
  return [$heading, $details];
}




// Get an array of info about the database server, the first line is displayed as a heading, following lines as bullets
// Usage: [$heading, $details] = __serverInfo_databaseServer();
function __serverInfo_databaseServer() {
  global $SETTINGS, $TABLE_PREFIX;
  $heading = "";
  $details = [];
  $details2 = []; // These are hidden until shown

  ### get database server name/version
  [$name, $moreInfo] = serverInfo_databaseServer();
  $heading  = htmlencodef('<u title="?">?</u>', $moreInfo, $name);
  $heading .= " (<a href='?menu=admin&action=db_show_variables'>variables</a>, <a href='?menu=admin&action=db_show_status'>status</a>)";


  ### Get database hostname
  $details[] = t('Hostname')        .": <code>". (inDemoMode() ? 'demo' : htmlencode($SETTINGS['mysql']['hostname'])). "</code>";

  ### get database connection info
  //list($maxConnectionsTotal, $maxConnectionsPerUser) = mysql_get_query("SELECT @@max_connections, @@max_user_connections", true); // returns the session value if it exists and the global value otherwise
  //$maxConnections  = !empty($maxUserConnections) ? min($maxConnectionsTotal, $maxConnectionsPerUser) : $maxConnectionsTotal;
  $changeInstructions = sprintf(t('To change %1$s settings edit: %2$s'), 'database', "\n".DATA_DIR.'/'.SETTINGS_FILENAME);

  $dbInfoHTML  = t('Username')        .": <code>". (inDemoMode() ? 'demo' : htmlencode($SETTINGS['mysql']['username'])). "</code> ";
  $dbInfoHTML .= t('Database')        .": <code>". (inDemoMode() ? 'demo' : htmlencode($SETTINGS['mysql']['database'])). "</code> ";
  $dbInfoHTML .= t('Table Prefix')    .": <code>". (inDemoMode() ? 'demo' : htmlencode($TABLE_PREFIX)). "</code>";
  $dbInfoHTML .= " (<u title='$changeInstructions'>" .t('change'). "</u>)";
//  $dbInfoHTML .= t('Max Connections') .": <code>". htmlencode($maxConnections). "</code>";
  $details[] = $dbInfoHTML;


  ### Privileges
  $details[]   = "Privileges: " . __serverInfo_databaseServer_privilegesHTML();

  ### get encryption details
  // get db encryption status and support - UPDATE COPIES IN security.php and general.php
  $dbStatusRows      = mysql_select_query("SHOW STATUS WHERE Variable_name IN ('Ssl_cipher','Ssl_version')");
  $dbStatusVars      = array_column($dbStatusRows, 'Value', 'Variable_name');
  $dbUsingEncryption = $dbStatusVars['Ssl_cipher'] || $dbStatusVars['Ssl_version'];
  $dbHaveSSL         = mysql_get_query("SHOW VARIABLES WHERE Variable_name IN ('have_ssl')")['Value']; // Disabled, Yes, No

  $encryptionHTML  = t('Encrypted connection') .": ";
  if ($dbUsingEncryption) { $encryptionHTML .= htmlencodef("<u title='?'>?</u>", "{$dbStatusVars['Ssl_version']} : {$dbStatusVars['Ssl_cipher']}", t("Yes")); }
  else                    { $encryptionHTML .= "<span style='color: #C00;'>" .t("No"). "</span>"; }
  $encryptionHTML .= ",&nbsp; " .t('Encrypted connection support') . ": ";
  $encryptionHTML .= htmlencodef("<a href='https://dev.mysql.com/doc/refman/5.5/en/server-system-variables.html#sysvar_have_ssl' target='_blank'>?</a>", $dbHaveSSL);
  $details[]       = $encryptionHTML;

  ### general query log
  // enable/disable with: mysql_do("SET GLOBAL general_log = 1"); // requires SUPER permissions to set GLOBAL
  // check if enabled with: $isGeneralQueryLogEnabled = mysql_get_query("SHOW VARIABLES WHERE Variable_name = 'general_log'")['Value'] == 'ON';
  $rows             = mysql_select_query("SHOW VARIABLES WHERE Variable_name IN ('general_log', 'general_log_file', 'sql_log_off', 'log_output')");
  $mysqlVars        = array_column($rows, 'Value', 'Variable_name');
  $mysqlVarsAsText  = print_r($mysqlVars, true);
  $isEnabledText    = ($mysqlVars['general_log'] == 'ON') ? 'enabled' : 'disabled';
  $generalQueryHTML = htmlencodef("<u title='?'>?</u>", $mysqlVarsAsText, t($isEnabledText));
  //$details[]       = t('General Query Log') .': '. $generalQueryHTML;

  ### slow query log
  $rows            = mysql_select_query("SHOW VARIABLES WHERE Variable_name IN ('log_queries_not_using_indexes','log_slow_queries','long_query_time','slow_query_log','slow_query_log_file', 'log_output')");
  $mysqlVars       = array_column($rows, 'Value', 'Variable_name');
  $mysqlVarsAsText = print_r($mysqlVars, true);
  $isEnabledText   = ($mysqlVars['slow_query_log'] == 'ON') ? 'enabled' : 'disabled';
  $slowQueryHTML   = htmlencodef("<u title='?'>?</u>", $mysqlVarsAsText, t($isEnabledText));
  //$details[]       = t('Slow Query Log') .': '. $slowQueryHTML;

  // show both on one line
  $details[]       = t('General Query Log') .': '. $generalQueryHTML. ", ".
                     t('Slow Query Log')    .': '. $slowQueryHTML;

  ###
  return [$heading, $details];
}

// return HTML for privileges
function __serverInfo_databaseServer_privilegesHTML() {
  $privilegeListHTML = "";

  //
  $privilegeNamesToRequiredGrants = [
    'INDEX'        => ['INDEX'],
    'TRIGGERS'     => ['TRIGGER'],
    'VIEWS'        => ['CREATE VIEW', 'SHOW VIEW'],
    'EVENTS'       => ['EVENT'],
    'FUNCTIONS'    => ['CREATE ROUTINE','ALTER ROUTINE','EXECUTE'],
    'TEMP TABLES'  => ['CREATE TEMPORARY TABLES'],
    'LOCK TABLES'  => ['LOCK TABLES'],
    'FLUSH'        => ['RELOAD'],
    'PROCESS'      => ['PROCESS'],
    //'FOOBAR'       => ['FOOBAR'],                 // For debugging
  ];

  // get text for all GRANTS
  $showGrantsText  = "";
  $grantLevels = ['Global','Database', 'Table'];
  $showGrantsRows  = mysql_select_query("SHOW GRANTS");
  foreach ($showGrantsRows as $rowIndex => $row) {
    foreach ($row as $column => $value) {
      $grantLevel      = $grantLevels[$rowIndex] ?? '';
      $showGrantsText .= ltrim("$grantLevel $column: $value\n");
    }
  }
  // remove any password text from output
  $showGrantsText = preg_replace("/IDENTIFIED BY PASSWORD.*?$/im", "", $showGrantsText); // remove: IDENTIFIED BY PASSWORD '*7BC4A4C26ED4A0482018211663D6D0CC543C32F7'

  // check for each privileges
  $hasAllPrivileges = mb_stripos($showGrantsText, 'ALL PRIVILEGES');
  foreach ($privilegeNamesToRequiredGrants as $privilegeName => $grantsNeeded) {

    // check if we have all the grants needed for this privilege
    $hasGrants = false;
    foreach ($grantsNeeded as $grantName) {
      $hasGrants = $hasAllPrivileges || mb_stripos($showGrantsText, $grantName);
      if ($hasGrants) { break; }
    }

    // show privilege status
    $privilegeNameLc = strtolower($privilegeName);
    if ($hasGrants) { $privilegeListHTML .= "$privilegeNameLc"; }
    else            { $privilegeListHTML .= "<strike class='text-muted'>$privilegeNameLc</strike>"; }
    $privilegeListHTML .= ", ";
  }
  $privilegeListHTML = rtrim($privilegeListHTML, ", ");

  // show all grants on hover
  $privilegeListHTML = "<span title='" .htmlencode($showGrantsText). "'>$privilegeListHTML</span>";

  //
  return $privilegeListHTML;

}


// Get an array of info about the database server, the first line is displayed as a heading, following lines as bullets
// Usage: [$heading, $details] = __serverInfo_databaseServer();
function __serverInfo_phpVersion() {
  $heading = "";
  $details = [];

  ### get PHP Version
  $errorCount     = mysql_count('_error_log');
  $linkStyle      = ($errorCount > 0) ? "color: #C00; font-weight: bold;" : "";
  $errorLogLink   = "<a href='?menu=_error_log' style='$linkStyle'>" .t('View Errors and Warnings'). " ($errorCount) &gt;&gt;</a>";
  $phpVersionHTML = "PHP v" .phpversion();
  $heading = "$phpVersionHTML (<a href='?menu=admin&amp;action=phpinfo'>phpinfo</a>) - $errorLogLink";


  ### Running as user - get user PHP is running as
  [$name, $moreInfo] = serverInfo_phpUser();
  $runningAs = htmlencodef("<span title='?'>Running as <code>?</code></span>", $moreInfo, $name);


  ### PHP CONFIG FILES
  $configFilesHTML = "";
  $cmsbHtaccess  = absPath('.htaccess', SCRIPT_DIR);
  $cmsbUserIni   = absPath(ini_get('user_ini.filename'), SCRIPT_DIR);
  $cmsbPhpIni    = absPath('php.ini', SCRIPT_DIR);
  $loadedPhpIni  = absPath(php_ini_loaded_file(), SCRIPT_DIR);
  $configFiles = [  // check which local config files are in use
    $cmsbHtaccess  => contains('CMSB_CONFIG_HTACCESS', ini_get('highlight.html')  .ini_get('date.default_latitude')) || !empty($_SERVER['CMSB_APACHE_HTACCESS']), // || in_array('101M', $_maxSizes)
    $cmsbUserIni   => contains('CMSB_CONFIG_USER_INI', ini_get('highlight.string').ini_get('date.default_longitude')),    // || in_array('102M', $_maxSizes)
    $cmsbPhpIni    => contains('CMSB_CONFIG_PHP_INI',  ini_get('highlight.comment').ini_get('date.sunrise_zenith')),      // || in_array('103M', $_maxSizes)
    $loadedPhpIni  => 1, // add last as it will overwrite previous entry if they're the same and in that case we want isLoaded to be true
  ];
  $configFilesList = '';
  foreach ($configFiles as $configFile => $isLoaded) {
    $filePath           = absPath($configFile, SCRIPT_DIR);
    $fileExists         = @file_exists($filePath);
    $isOpenBaseDirError = preg_match("/open_basedir restriction in effect/i", errorlog_lastError());
    if     (isWindows()) { $filePath = str_replace('/', '\\', $filePath); } // display windows slashes for easier copy and pasting on windows
    if     (!$fileExists && !$isOpenBaseDirError) { $configFilesList .= "<li><code>$filePath</code> <span style='color: #C00;'>(removed)</span></li>\n"; }
    elseif ($isLoaded)                            { $configFilesList .= "<li><code>$filePath</code></li>\n"; }
  }
  //$configFilesHTML = "using config files:\n";
  $configFilesHTML = "$runningAs and using config files:\n";
  if ($configFilesList) { $configFilesHTML .= "<ul>$configFilesList</ul>"; }
  else                  { $configFilesHTML .= t('none'); }

  $details[] = $configFilesHTML;


  ### DISABLED FUNCTIONS
  $disabledFunctions = str_replace(',', ', ', (string) ini_get('disable_functions'));
  if ($disabledFunctions) {
    $details[] = "<span style='color: #C00;'>" .t('Disabled functions'). ": $disabledFunctions</span>\n";
  }


  // OPEN_BASEDIR RESTRICTIONS
  $open_basedir = ini_get('open_basedir');
  if ($open_basedir) {
    $details[] = htmlencodef("<span style='color: #C00;'>?: ?</span>\n", t('open_basedir restrictions'), $open_basedir);
  }


  // SECURITY MODULES - check for common security modules that interfere
  $hasModSecurity = isset($_SERVER['CMSB_MOD_SECURITY1']) || isset($_SERVER['CMSB_MOD_SECURITY2']) || isset($_SERVER['CMSB_MOD_SECURITY3']);
  if ($hasModSecurity) {
    $details[] = htmlencodef("<span style='color: #C00;'>?: ?</span>\n", t('Security Modules'), "ModSecurity");
  }

  // IMAGE MODULES
  $modulesToCheck  = ['gd','imagick'];
  $imageModulesCSV = '';
  foreach ($modulesToCheck as $module) {
    if ($imageModulesCSV) { $imageModulesCSV .= ", "; }
    if (extension_loaded($module)) { $imageModulesCSV .= "$module"; }
    else                           { $imageModulesCSV .= "<strike class='text-muted'>$module</strike>"; }
  }
  $details[] = sprintf("<span>%s: %s</span>\n", t('Image Modules'), $imageModulesCSV);

  // NOTE: We haven't used this in a long time so we're commenting it out for now, we can bring it back in future if needed
  /*
  // CACHING MODULES
  // Notes: We've seen servers where WinCache caching the PHP "output" of schema files, so they just return "Not a PHP file".
  // This can be resolved by disabling WinCache in .user.ini with: wincache.fcenabled = Off
  // Future: Look into exact cause and workarounds that let us continue using WinCache but avoid issue
  $cachingModules = [];
  if (ini_get('wincache.fcenabled') == '1') { $cachingModules[] = "WinCache"; }
  if (ini_get('opcache.enable') == '1')     { $cachingModules[] = "Zend OPCache"; }
  $cachingModulesCSV = implode(", ", $cachingModules);
  if ($cachingModulesCSV) {
    //$details[] = htmlencodef("<span style='color: #C00;'>?: ?</span>\n", t('Caching Modules'), $cachingModulesCSV);
    $details[] = htmlencodef("?: ?\n", t('Caching Modules'), $cachingModulesCSV);
  }
  */

  // PHP ERRORS AND WARNINGS
  // Moved to heading
  //$details[] = "<a href='?menu=_error_log'>" .t('View PHP Errors and Warnings'). " &gt;&gt;</a>\n";


  ###
  return [$heading, $details];
}



// Get an array of info about the recent changes to the server
// Usage: [$heading, $details] = __serverInfo_recentChanges();
function __serverInfo_recentChanges() {
  $heading = "";
  $details = [];

  // Force update settings so we're comparing the latest values
  updateServerChangeLog(true);

  // get an array of changes
  $serverChanges  = [];
  $lastSeenValues = [];
  $serverChangeLog  = json_decode($GLOBALS['SETTINGS']['serverChangeLog'], true);
  usort($serverChangeLog, function ($a, $b) { return $a[0] <=> $b[0]; }); // sort by date: oldest to newest
  foreach ($serverChangeLog as [$timestamp, $component, $value]) {
    // If this is the first time we've seen this component, store the value and the date
    // Or if the value has changed, add a change record and update the value
    if (!isset($lastSeenValues[$component])) {
      $lastSeenValues[$component] = ['value' => $value, 'firstSeenDate' => $timestamp];
    } elseif ($lastSeenValues[$component]['value'] !== $value) {
      // Add a change record: [timestamp, component, old value, new value, first seen date]
      $serverChanges[] = [$timestamp, $component, $lastSeenValues[$component]['value'], $value, $lastSeenValues[$component]['firstSeenDate']];
      $lastSeenValues[$component] = ['value' => $value, 'firstSeenDate' => $lastSeenValues[$component]['firstSeenDate']]; // Update the last seen value but keep the first seen date
    }
  }

  //
  foreach (array_reverse($serverChanges) as [$unixtime, $component, $from, $to, $firstSeenDate]) {
    $from      = $from ?: t("Unknown");
    $to        = $to   ?: t("Unknown");
    $dateHTML  = "<span title='First seen: " . date("M j, Y H:i:s", $unixtime) . "'>" .prettyDate($unixtime). "</span>";
    $details[] = "$dateHTML: <b>$component</b> changed from "
               . "<code title='First seen: " . date("M j, Y H:i:s", $firstSeenDate) . "'>" .htmlencode($from). "</code> to "
               . "<code title='First seen: " . date("M j, Y H:i:s", $unixtime) . "'>" .htmlencode($to). "</code>";
  }

  if (!$details) { $details[] = t("None detected"); }


  ###
  $heading = "This section is automatically updated every time you load this page.";
  return [$heading, $details];
}
