<?php

/**
 * This function calculates the time in seconds it took for a PHP script to execute.
 * The execution time is formatted to two decimal places.
 * If $return is false, the function will echo the execution time, otherwise it will return it.
 *
 * @param bool $return If false, echoes the execution time; if true, returns it.
 *
 * @return string|null The execution time as a string, or null if $return is false.
 */
function showExecuteSeconds(bool $return = false): ?string
{
    $executeSeconds = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    $executeSeconds = sprintf("%0.2f", $executeSeconds);
    if (!$return) {
        echo $executeSeconds;
        return null;
    }

    return $executeSeconds;
}



//
function convertSecondsToTimezoneOffset($seconds): string
{

  $offsetAddMinus = ($seconds < 0) ? '-' : '+';
  $offsetHours    = (int) abs($seconds / (60*60));
  $offsetMinutes  = (int) abs(($seconds % (60*60)) / 60);
  $offsetString   = sprintf("%s%02d:%02d", $offsetAddMinus, $offsetHours, $offsetMinutes);

  return $offsetString;
}


/**
 * Evaluates the given PHP code contained in a string and returns the output. The PHP code must be inside the <?php tag.
 * All errors and exceptions are logged and displayed in accordance with CMS settings, and are also returned in $evalErrors.
 *
 * NOTE: As of v2.58, this function DOES NOT halt execution on fatal errors, so be sure to check $evalErrors after calling
 * this function. We do this so errors in user PHP code don't terminate the program, and we can display friendly error messages
 * which would allow end users to make corrections.
 *
 * @param ?string  $string               The PHP code to be evaluated. PHP code must be inside a <?php tag.
 * @param ?string &$evalErrors           A reference to a variable that will be set with error details if an error occurs. Pass this
 *                                       as an undefined variable to be able to check for errors after calling this function.
 *
 * @return string                 The output of the evaluated code or the original string if it contains no PHP code.
 *
 * @throws Exception
 * @global string  $ESCAPED_FILTER_VALUE A global variable made available to the eval code.
 * @global string  $CURRENT_USER         A global variable made available to the eval code.
 * @global string  $RECORD               A global variable made available to the eval code.
 * @global string  $TABLE_PREFIX         A global variable made available to the eval code.
 */
function getEvalOutput(?string $string, ?string &$evalErrors = null): string {
    global $TABLE_PREFIX, $ESCAPED_FILTER_VALUE, $CURRENT_USER, $RECORD; // make these variables available to the eval code
    $evalErrors = null; // clear any variable that is passed in
    $string     ??= ''; // if $string is undefined or null, set it to an empty string

    // if string contains no php code, skip and return string
    $containsNoPHPCode = (!str_contains($string, "php"));
    if ($containsNoPHPCode) {
        return $string;
    }

    // Temporarily override error handler, and turn all errors into exceptions, so we can catch them below
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        _errorlog_alreadySeenError($errfile, $errline);
        if (error_reporting() & $errno) {  // Check if the error is suppressed, and ignore it if so
            throw new Exception($errstr, 0);
        }
        return false; // Execute the original error handler, if needed
    });

    // eval php code
    ob_start();
    try {
        eval('?>'.$string);
    }
    catch (Throwable $e) { // catch all errors and exceptions
        $evalErrors = $e->getMessage() . " in eval code at line " . $e->getLine(); // Set $evalErrors so it gets returned by reference to the function called
        _errorlog_catchUncaughtExceptions($e, true); // display and log error
       // NOTE: This code CONTINUES execution, even for fatal errors, so we can return the output.  We sure to check $evalErrors after calling this function
    }
    $output = ob_get_clean();

    // Restore the original error handler
    restore_error_handler();

    //
    return $output;
}


// return an array or string in a human-readable "example" form
function showme($var, $fancy = false) {

  //
  if ($fancy) {
    $js = <<<__EOD__
   <a href="#" onclick="d=this.nextSibling.style;if(d.display=='none'){d.display='inline';this.innerHTML='-'}else{d.display='none';this.innerHTML='+'}return false">+</a><span style="display:none">
\\1
__EOD__;
    $dump = htmlencode(print_r($var, true));
    $dump = preg_replace('|\n(\s*\(\n)|', $js, $dump);
    $dump = preg_replace('|(\n\s*\))\n|', "\\1</span>", $dump);
    print "<pre>$dump</pre>";
  }

  elseif (is_object($var) && method_exists($var, 'showme')) {
    $var->showme();
  }

  else {
    print "<xmp>" . print_r($var, true) . "</xmp>";
  }

}


// we use files with a .default extension and then rename it if a file _doesn't_ already exist
// with the same name to prevent users from accidentally overwriting their data while upgrading
function renameOrRemoveDefaultFiles() {

  $dirs = [];
  $dirs[] = DATA_DIR;
  $dirs[] = DATA_DIR.'/schema';
  $dirs[] = DATA_DIR.'/schemaPresets';

  foreach ($dirs as $dir) {
    foreach (scandir($dir) as $filename) {
      $filepath = "$dir/$filename";
      if (!is_file($filepath))                    { continue; }
      if (!preg_match('/\.default$/', $filename)) { continue; }

      // rename default file if no target file exists
      $defaultFile = $filepath;
      $targetFile  = preg_replace('/\.default$/', '', $defaultFile);

      if (!is_file($targetFile)) {
        @rename($defaultFile, $targetFile) || die("Error renaming '$defaultFile'!<br>Make sure this file and its parent directory are writable!");
      }
      else {
        @unlink($defaultFile) || die("Error deleting '$defaultFile'!<br> Make sure this file and its parent directory are writable! PHP Error: " .errorlog_lastError() );
      }
    }
  }

}


// set new alert and return all alerts
function alert($message = ''): ?string
{
  global $APP;
  @$APP['alerts'] .= $message;
  return $APP['alerts'];
}

// set new notice and return all notices
function notice($message = '') {
  global $APP;
  @$APP['notices'] .= "$message";
  return $APP['notices'];
}

//
//
function displayAlertsAndNotices() {
  global $APP;

  //if (@$APP['errors']    != '') { _displayNotificationType('danger',    @$APP['errors']); }
  if (isset($APP['alerts'])  && $APP['alerts']  != '') { _displayNotificationType('warning', $APP['alerts']); }
  if (isset($APP['notices']) && $APP['notices'] != '') { _displayNotificationType('info',    $APP['notices']); }
  //if (@$APP['successes'] != '') { _displayNotificationType('success',   @$APP['successes']); }
}

//
function _displayNotificationType($type, $message) {
  $typeToIconClass = [
    'success' => 'fa-check-circle',
    'info'    => 'fa-info-circle',
    'warning' => 'fa-exclamation-triangle',
    'danger'  => 'fa-times-circle',
  ];
  print '<div class="alert alert-'.htmlencode($type).'">';
  print '<button class="close">&times;</button>';
  print '<i class="fa ' . @$typeToIconClass[$type] . '"></i> &nbsp;';
  print '<span>' . $message . '</span>';
  print '</div>';
}


// return the first array value, which may not have a key of [0] (this exists for function composition!)
// $firstProduct = array_first($productsByNum);
function array_first($array) {
  if (!is_array($array) || !$array) { return null; }
  return reset($array); // return first element
}

// array_groupBy:
//   eg. $recordsByNum = array_groupBy($records, 'num');
//   eg. $recordsByCategory = array_groupBy($records, 'category', true);
//       foreach ($recordsByCategory as $category => $categoryRecords) { ;;; }
// PHP Alternate: $recordsByNum = array_combine(array_column($menuRecords, 'num'), $menuRecords);
function array_groupBy($recordList, $indexField, $resultsAsArray = false) {
  $result = [];
  foreach ($recordList as $recordKey => $record) {

    // get index value or skip this record
    if (!array_key_exists($indexField, $record)) { continue; }
    $indexValue = $record[$indexField];

    // add this record to the result array
    if ($resultsAsArray) {
      if (!@$result[ $indexValue ]) { $result[ $indexValue ] = []; }
      $result[ $indexValue ][ $recordKey ] = $record;
    }
    else {
      $result[ $indexValue ] = $record;
    }

  }
  return $result;
}

// add a prefix to the keys in an array
// $array = array_keys_prefix('billing_', array('city' => 'Austin', 'state' => 'TX')); // returns array('billing_city' => 'Austin', 'billing_state' => 'TX')
/**
 * Add a prefix to the keys in an array.
 *
 * @param string $prefix The prefix to add to each key.
 * @param array  $array  The original array to process.
 *
 * @return array A new array where each key is prefixed with $prefix.
 *
 * @throws Exception if the $prefix is an array instead of a string.
 *
 * @example array_keys_prefix('billing_', array('city' => 'Austin', 'state' => 'TX')); // returns array('billing_city' => 'Austin', 'billing_state' => 'TX')
 */
function array_keys_prefix(string $prefix, array $array): array
{
  if (is_array($prefix)) { die(__FUNCTION__ . ": the first argument should be a string"); }
  if (!$array) { return []; }
  $newArray = [];
  foreach (array_keys($array) as $key) {
    $newArray["$prefix$key"] = &$array[$key];
    unset($array[$key]);
  }
  return $newArray;
}


// array_pluck: (aka array_column) utility function which returns specific key/column from an array
// alternate: array_column($recordList, $targetField)
// alternate2: array_column($recordList, $targetField, $keyField)
function array_pluck($recordList, $targetField) {
  if (!is_array($recordList)) { dieAsCaller(__FUNCTION__. ": First argument must be an array!"); }

  $result = [];
  foreach ($recordList as $recordKey => $record) {
    if     (is_array($record)  && array_key_exists($targetField, $record)) { $result[ $recordKey ] = $record[$targetField]; }
    elseif (is_object($record) && isset($record->$targetField))            { $result[ $recordKey ] = $record->$targetField; }
  }
  return $result;
}

// perl-style hash slicing
// $inventory = array('apple' => 14, 'orange' => 27, 'salmon' => 6);
// $fruitCounts = array_slice_keys($inventory, array('apple', 'orange')); // returns array('apple' => 14, 'orange' => 27)
// $fruitCounts = array_slice_keys($inventory, 'apple', 'orange'); // alternately
// NOTE: This function will create keys in returned array even if they don't exist in source array
// NOTE: Alternate PHP-only version that doesn't do that: array_intersect_key($sourceArray, array_flip($keysArray));
function array_slice_keys($array, $keys): array
{
  if (!is_array($keys)) {
    $keys = func_get_args();
    array_shift($keys); // get rid of the first element
  }
  $results = [];
  foreach ($keys as $key) {
    $results[$key] = @$array[$key];
  }
  return $results;
}


/**
 * Returns the specified array value by keys (this exists for function composition!)
 *
 * Usage:
 *     $num   = array_value($array, 'num'); // @$array['num']
 *     $width = array_value($record, 'photos', 0, 'width'); // @$record['photos'][0]['width']
 *
 * @param array $array The array to search.
 * @param int|string ...$keys The keys to search for.
 *
 * @return string|array|null The value found by the keys or null if not found.
 */
function array_value(array $array, ...$keys): string|array|null {
    $result = $array;
    foreach ($keys as $key) {
        $result = $result[$key] ?? null;
        if ($result === null) {
            return null;
        }
    }
    return $result;
}

// Returns the "records" in an array where the supplied key(s) match value(s)
// e.g. $blueOnes = array_where($records, array( 'colour' => 'blue' ));
function array_where($records, $conditions) {
  if(!is_array(@$records) || !@$records) { return []; }

  $matchArray = [];
  foreach ($records as $key => $record) {
    if (!is_array($record)) { continue; }
    $isMatch = TRUE;
    foreach ($conditions as $fieldname => $value) {
      if (@$record[$fieldname] != $value) {
        $isMatch = FALSE;
        break;
      }
    }
    if ($isMatch) {
      $matchArray[$key] = $record;
    }
  }
  return $matchArray;
}

//
function dieAsCaller($message, $depth = 1) {
  //$callers = debug_backtrace();
  //
  //// special case - remove functions that just call other functions (otherwise the fine/line number indicated isn't actual originating code)
  //$skipFunctions = ['mysql_select_query'];
  //foreach ($callers as $index => $array) {
  //  if (in_array($array['function'], $skipFunctions)) {
  //    array_splice($callers, $index, 1); // remove key (and re-order array indexes for code below)
  //  }
  //}
  //
  ////
  //$caller       = array_key_exists($depth, $callers) ? $callers[$depth] : array_pop($callers); // if specific frame doesn't exist return last frame
  //$locationInfo = '';
  //if (@$caller['file'])     { $locationInfo .= "in " . basename($caller['file']). " "; }
  //if (@$caller['line'])     { $locationInfo .= "on line " . $caller['line']. " "; }
  //if (@$caller['function']) { $locationInfo .= "by " .$caller['function']. "()"; }
  //$error = "$message - $locationInfo";

  // v3.60 - Display caller info in _errorlog_catchRuntimeErrors

  trigger_error($message, E_USER_NOTICE); // log error
  exit;
}


// $isPreviewMode = isBeingRunDirectly();
// returns true if caller's file is the URL being executed (false if it's being included)
// note: this is useful for templates to determine if they should preview themselves
function isBeingRunDirectly(): bool {
  global $CURRENT_USER;

  // if there's only one unique file in the backtrace, then it's being run directly
  // (this also handles cases where the script being executed calls a function in the same file)
  $callerFiles   = array_unique( array_pluck( debug_backtrace(), 'file' ) );
  $isPreviewMode = count($callerFiles) == 1;

  // error checking
  // 2.16 - not needed? - if ($isPreviewMode && !$CURRENT_USER['isAdmin']) { die("Preview Mode: You must be logged in as a CMS Admin user to preview email templates!"); }

  //
  return $isPreviewMode;
}


/**
 * Formats a size in bytes into a human-readable string.
 *
 * @param int|float|string|null $bytes The size in bytes to format.
 * @param int $precision The number of decimal places to include in the formatted string.
 *
 * @return string The formatted size string, including the appropriate units (B, KB, MB, GB, TB, PB).
 */
function formatBytes(int|float|string|null $bytes, int $precision = 2): string {
  $bytes  = max((float)$bytes, 0);
  $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

  $pow    = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow    = min($pow, count($units) - 1);
  $bytes /= 1024 ** $pow;

  return round($bytes, $precision) . ' ' . $units[$pow];
}


/**
 * Checks if the server's operating system is Windows.
 *
 * This function is based on the PHP_OS constant that contains the name of the operating system PHP was built on.
 * The constant may not relate to the system where the script is currently running as it is determined at compile time.
 *
 * @return bool Returns true if the server's operating system is Windows, false otherwise.
 */
function isWindows(): bool {
  return PHP_OS === 'WINNT';
}

/**
 * Checks if the server's operating system is MacOS (Darwin).
 *
 * This function is based on the PHP_OS constant that contains the name of the operating system PHP was built on.
 * The constant may not relate to the system where the script is currently running as it is determined at compile time.
 *
 * @return bool Returns true if the server's operating system is MacOS, false otherwise.
 */
function isMac(): bool {
  return PHP_OS === 'Darwin';
}

// turn off output buffering so output is returned and displayed in real-time.
// Attempts to disable or bypass php, and browser output buffering.
/**
 * Disables output buffering and bypasses browser buffering.
 *
 * This function is used to disable output buffering and bypass browser buffering to ensure immediate output is sent to the client.
 *
 * @param bool $forHTML - (optional) Flag indicating if the output is for HTML pages. Default is false.
 *
 * @return void
 */
function ob_disable($forHTML = false): void
{
  // Note: PHP only returns output in blocks of bytes whose size is defined by php.ini 'output_buffering', see: http://www.php.net/manual/en/outcontrol.configuration.php#ini.output-buffering
  // Note: Web Servers can also buffer the results for encoding (gzip, deflate)
  // Note: Browsers also buffer content and don't display anything until they've received x bytes, the entire page, or an html tag

  // Web Server Buffering: turn off output compression which requires all output be buffered in advance before being compressed
  // v2.50 uncommented these features
  ini_set('zlib.output_compression', '0'); // if this was previously turned on, it's output buffer is disabled below
  if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
    apache_setenv('no-mod_gzip_on', 'No');
  }

  // PHP Buffering: turn off _all_ PHP output-buffering, including user defined and internal PHP output buffering, see ob_get_status(true) for details
  while (@ob_end_flush()); // disable any and all output buffers (so they don't interfere)

  // Browser Buffering: bypass browser buffering by overflowing browser output buffer.
  if ($forHTML) { echo "<!--"; } // for html pages, hide whitespace in comment
  echo str_repeat(' ',1024) . "\n";
  if ($forHTML) { echo "-->\n"; }
}

/*
  Wrap a call to a function in ob_start/ob_get_clean so that a function designed to output html can return html instead.
  Intended for use with functions written in the outputting html style -- i.e. with ?>...<?php
  $buttonsHtml = ob_capture('generateSomeHtml');                     // calls generateSomeHtml() and returns its captured output
  $buttonsHtml = ob_capture(function() { generateSomeHtml(); });     // same thing
  $buttonsHtml = ob_capture(function() { generateSomeHtml(123); });  // if the function needs to be called with arguments, this form is required
  $buttonsHtml = ob_capture(function() { ?>Hello world!<?php });     // silly example, never do this!
  function generateSomeHtml() {                                      // sample function written in the outputting html style
    ?>
      <input name="<?php echo $GLOBALS['foo'] ?>" value="<?php echo htmlencode(@$_REQUEST['foo']) ?>">
      <input name="<?php echo $GLOBALS['bar'] ?>" value="<?php echo htmlencode(@$_REQUEST['bar']) ?>">
    <?php
  }
*/
function ob_capture($function): string
{

  // error checking - check named functions exist
  $isClosure = $function instanceof Closure;
  if (!$isClosure && !function_exists($function)) {
    dieAsCaller("Function '$function' doesn't exist!");
  }

  //
  $args = func_get_args();
  array_shift($args);

  //
  ob_start();
  call_user_func_array($function, $args);
  return ob_get_clean();
}


//
function _getRecordValuesFromFormInput($fieldPrefix = '') {
  global $schema, $CURRENT_USER, $tableName, $isMyAccountMenu;

  $recordValues = [];
  $specialFields = array('num', 'createdDate', 'createdByUserNum', 'updatedDate', 'updatedByUserNum');

  // load schema columns
  foreach (getSchemaFields($schema) as $fieldname => $fieldSchema) {
    if (!userHasFieldAccess($fieldSchema)) { continue; } // skip fields that the user has no access to
    if ($tableName == 'accounts' && $fieldname == 'isAdmin' && !$CURRENT_USER['isAdmin']) { continue; } // skip admin only fields

    // special cases: don't let user set values for:
    if (in_array($fieldname, $specialFields)) { continue; }
    if ($isMyAccountMenu) {
      if (@!$fieldSchema['myAccountField'])                                 { continue; } // my account - skip fields not displayed or allowed to be edited in "my account"
      if ($fieldname == 'password' && !@$_REQUEST[$fieldPrefix.'password']) { continue; } // my account - skip password field if no value submitted
    }

    //
    switch (@$fieldSchema['type']) {
      case 'textfield':
      case 'wysiwyg':
      case 'checkbox':
      case 'parentCategory':
      case 'hidden':
        $recordValues[$fieldname] = $_REQUEST[ $fieldPrefix.$fieldname ] ?? '';
        break;

      case 'textbox':
        $fieldValue = $_REQUEST[$fieldPrefix.$fieldname];
        if ($fieldSchema['autoFormat']) {
          $fieldValue = preg_replace("/\r\n|\n/", "<br>\n", $fieldValue); // add break tags
        }
        $recordValues[$fieldname] = $fieldValue;
        break;

      case 'date':
        $recordValues[$fieldname] = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $_REQUEST["$fieldPrefix$fieldname:year"], $_REQUEST["$fieldPrefix$fieldname:mon"], $_REQUEST["$fieldPrefix$fieldname:day"], _getHour24ValueFromDateInput($fieldPrefix.$fieldname), (int) @$_REQUEST["$fieldPrefix$fieldname:min"], (int) @$_REQUEST["$fieldPrefix$fieldname:sec"]);
        break;

      case 'list':
        if (is_array(@$_REQUEST[$fieldPrefix.$fieldname]) && @$_REQUEST[$fieldPrefix.$fieldname]) {
          // store multi-value fields as tab delimited with leading/trailing tabs
          // for easy matching of single values - LIKE "%\tvalue\t%"
          $recordValues[$fieldname] = "\t" . join("\t", $_REQUEST[$fieldPrefix.$fieldname]) . "\t";
        }
        else {
          $recordValues[$fieldname] = @$_REQUEST[$fieldPrefix.$fieldname];
        }
        break;

      case 'upload':
        // images need to be loaded with separate function call.
        break;

      case 'dateCalendar':
        _updateDateCalendar($fieldname);
        break;

      // ignored fields
      case '':               // ignore these fields when saving user input
      case 'none':           // ...
      case 'separator':      // ...
      case 'relatedRecords': // ...
      case 'accessList':     // ...
      case 'tabGroup':       // ...
        break;

      default:
        die(__FUNCTION__ . ": field '$fieldname' has unknown field type '" .@$fieldSchema['type']. "'");
        break;
    }
  }

  return $recordValues;
}

//
function _getHour24ValueFromDateInput($fieldname) {
  $hour24 = 0;

  // convert 12hour format to 24hour format
  if (array_key_exists("$fieldname:hour12", $_REQUEST)) {
    $hour12 = $_REQUEST["$fieldname:hour12"];
    $isPM   = $_REQUEST["$fieldname:isPM"];
    $isAM   = !$isPM;
    if     ($isAM && $hour12 == 12) { $hour24 = 0; }
    elseif ($isAM)                  { $hour24 = $hour12; }
    elseif ($isPM && $hour12 == 12) { $hour24 = 12; }
    elseif ($isPM)                  { $hour24 = $hour12 + 12; }
  }
  elseif (array_key_exists("$fieldname:hour24", $_REQUEST)) {
    $hour24 = $_REQUEST["$fieldname:hour24"];
  }
  else {
    $hour24 = '0';
  }


  return $hour24;
}

//
function _updateDateCalendar($fieldname) {
  global $TABLE_PREFIX, $tableName;
  $calendarTable = $TABLE_PREFIX . "_datecalendar";

  // call ONCE per field
  static $calledFor = [];
  if (@$calledFor[$fieldname]++) { return; }

  // check if table exists
  static $tableExists = false;
  if (!$tableExists) {
    $result      = mysqli()->query("SHOW TABLES LIKE '$calendarTable'") or die("MySQL Error: ". htmlencode(mysqli()->error) . "\n");
    $tableExists = $result->fetch_row()[0];
    if (is_resource($result)) { mysqli_free_result($result); }
  }

  // create table if it doesn't exists
  if (!$tableExists) {
    $createSql = "CREATE TABLE  `$calendarTable` (
                  `num` int(10) unsigned NOT NULL auto_increment,
                  `tableName` varchar(255) NOT NULL,
                  `fieldName` varchar(255) NOT NULL,
                  `recordNum` varchar(255) NOT NULL,
                  `date`      date,
                  PRIMARY KEY  (`num`)
                ) ENGINE=InnoDB CHARSET=utf8mb4 CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    mysqli()->query($createSql) or die("MySQL Error: ". htmlencode(mysqli()->error) . "\n");
  }

  // build queries
  $eraseDatesAsCSV = "0";
  $insertValues    = '';
  $recordNum       = (int) $_REQUEST['num'];
  foreach (array_keys($_REQUEST) as $formFieldname) {
    if (!preg_match("/^$fieldname:/", $formFieldname)) { continue; }
    [,$dateString] = explode(":", $formFieldname);
    if (!$dateString) { continue; }

    if ($_REQUEST[$formFieldname]) {
      if ($insertValues) { $insertValues .= ",\n"; }
      $insertValues .= "('" .mysql_escape($tableName). "','" .mysql_escape($fieldname). "','" .mysql_escape($recordNum). "','" .mysql_escape($dateString). "')";
    }
    else {
      $eraseDatesAsCSV .= ",'" . mysql_escape((int) $dateString) . "'";
    }
  }

  // remove dates
  $deleteQuery  = "DELETE FROM `$calendarTable` ";
  $deleteQuery .= "WHERE `tablename` = '" .mysql_escape($tableName). "' ";
  $deleteQuery .= "  AND `fieldname` = '" .mysql_escape($fieldname) . "' ";
  $deleteQuery .= "  AND `recordNum` = '".mysql_escape($_REQUEST['num'])."' ";
  $deleteQuery .= "  AND `date` IN ($eraseDatesAsCSV)";
  mysqli()->query($deleteQuery) or die("MySQL Error: ". htmlencode(mysqli()->error) . "\n");

  // add dates
  if ($insertValues) {
    $insertQuery  = "INSERT INTO `$calendarTable` (`tableName`,`fieldName`,`recordNum`,`date`) VALUES $insertValues";
    mysqli()->query($insertQuery) or die("MySQL Error: ". htmlencode(mysqli()->error) . "\n");
  }

}

//
function _addUndefinedDefaultsToNewRecord($colsToValues, $mySqlColsAndTypes) {
  global $schema;
  if (!$schema) { die("No \$schema defined!"); }



  $currentDate  = date("Y-m-d H:i:s"); // set default to required Format: YYYY-MM-DD HH:MM:SS
  $dateFieldDefault = $currentDate;

  foreach ($mySqlColsAndTypes as $colName => $colType) {

    // set special field values
    if      ($colName == 'createdDate')      { $colsToValues[$colName] = $currentDate;  }
    else if ($colName == 'createdByUserNum') { $colsToValues[$colName] = $GLOBALS['CURRENT_USER']['num']; }
    else if ($colName == 'dragSortOrder')    { $colsToValues[$colName] = @$_REQUEST['dragSortOrder'] ? $_REQUEST['dragSortOrder'] : time(); }
    else if ($colName == 'siblingOrder')     { $colsToValues[$colName] = time(); } // sort to bottom

    // skip fields with a value already
    if (array_key_exists($colName, $colsToValues))      { continue; } // skip if already defined

    //Pick a default date to use for date fields
    if ((@$schema[$colName]['type'] == 'date')) {
      if ((@$schema[$colName]['defaultDate']=='custom') && (@$schema[$colName]['defaultDateString'])) {
        $dateFieldDefault = date("Y-m-d H:i:s", strtotime($schema[$colName]['defaultDateString']));
      }
      elseif ($schema[$colName]['defaultDate']=='none') {
        $dateFieldDefault = "0000-00-00 00:00:00";
      }
      else {
        $dateFieldDefault = $currentDate;
      }
    }

    // set adminOnly fields to default value (they'd have a value assigned already if user was admin)
    $isAdminOnly    = @$schema[$colName]['adminOnly'];
    $fieldType      = @$schema[$colName]['type'];
    if ($isAdminOnly && $fieldType == 'textfield')      { $colsToValues[$colName] = getEvalOutput(@$schema[$colName]['defaultValue']); }
    else if ($isAdminOnly && $fieldType == 'list')      { $colsToValues[$colName] = getEvalOutput(@$schema[$colName]['defaultValue']); }
    else if ($isAdminOnly && $fieldType == 'textbox')   { $colsToValues[$colName] = getEvalOutput(@$schema[$colName]['defaultContent']); }
    else if ($isAdminOnly && $fieldType == 'wysiwyg')   { $colsToValues[$colName] = getEvalOutput(@$schema[$colName]['defaultContent']); }
    else if ($isAdminOnly && $fieldType == 'checkbox')  { $colsToValues[$colName] = (int) @$schema[$colName]['checkedByDefault']; }

    // set default values for insert
    else if (@$schema[$colName]['type'] == 'date')      { $colsToValues[$colName] = $dateFieldDefault; } // default date fields to default date
    else if (preg_match("/^\w*datetime/i", $colType))   { $colsToValues[$colName] = $currentDate; } // default date fields to current date
    else if (preg_match("/^\w*int/i", $colType))        { $colsToValues[$colName] = '0'; }          // default numeric fields to 0
    else                                                { $colsToValues[$colName] = ''; }           // default all other field to blank
  }
  return $colsToValues;
}

// removes and returns a suffix character from a string if present
// list($field, $suffixChar) = extractSuffixChar($field, '=');
function extractSuffixChar($string, $acceptableSuffixChars) {
  if ($string == '') { return array($string, null); }
  $suffixChar = substr($string, -1);
  if (!str_contains($acceptableSuffixChars, $suffixChar)) {
    return array($string, null);
  }
  $newString = substr($string, 0, -1);
  return array($newString, $suffixChar);
}


//
function saveSettings( $forceDevFile = false ) { // added arg in 2.53
  if (inCLI() && !isInstalled()) { return; }    // prevent cron.php from saving auto-generated settings until software has been installed (not all setting defaults can be set from CLI as $_SERVER values aren't available).

  // Custom sorting for easier manual editing of settings file
  $keysTop    = ['adminEmail','developerEmail',
                 'programName','programVersion','programBuild',
                 'adminUrl','installPath','webRootDir','webPrefixUrl','uploadDir','uploadUrl',
                 'mysql','wysiwyg','activePlugins'];
  $keysBottom = ['serverChangeLog_lastCheck','serverChangeLog'];

  // Custom sorting function
  $sortingFunction = function($a, $b) use ($keysTop, $keysBottom) {
    $flipTop = array_flip($keysTop);
    $flipBottom = array_flip($keysBottom);

    $aIndexTop = $flipTop[ $a ] ?? (isset($flipBottom[ $a ]) ? INF : PHP_INT_MAX);
    $bIndexTop = $flipTop[ $b ] ?? (isset($flipBottom[ $b ]) ? INF : PHP_INT_MAX);

    if ($aIndexTop === $bIndexTop && $aIndexTop === INF) {
      $aIndexBottom = $flipBottom[$a];
      $bIndexBottom = $flipBottom[$b];
      return $aIndexBottom <=> $bIndexBottom;
    } else {
      return $aIndexTop <=> $bIndexTop ?: strcmp($a, $b);
    }
  };
  uksort($GLOBALS['SETTINGS'], $sortingFunction);

  // Save settings
  $settingsPath = $forceDevFile ? SETTINGS_DEV_FILEPATH : SETTINGS_FILEPATH;
  saveStruct($settingsPath, $GLOBALS['SETTINGS']);

  // Reset the timezone for PHP and MYSQL as they could have changed.
  _init_setTimezone();
  setMySqlTimezone();
}

// Display human-readable date relative to current time, eg: a few seconds ago, about a minute ago, 3 hours ago, etc
// Doesn't yet handle future dates (just shows "in the future");
// Usage: echo prettyDate( 1307483052 );          // unixtime or PHP time()
// Usage: echo prettyDate('2011-06-07 16:04:23'); // MySQL date string
function prettyDate($dateStringOrUnixTime) {

  // 2.50 check for 0000-00-00 00:00:00
  // 2.51 check for '' and 0
  if ($dateStringOrUnixTime == '0000-00-00 00:00:00' || !$dateStringOrUnixTime) { return 'never'; }

  // get unixTime and secondsOld
  $isUnixTime = ((string)$dateStringOrUnixTime === (string)(int)$dateStringOrUnixTime);
  $time       = $isUnixTime ? $dateStringOrUnixTime : @strtotime($dateStringOrUnixTime); // strtotime returns false or -1 on failure
  if ($time <= 0) { dieAsCaller(__FUNCTION__ .": Unrecognized date string '" .htmlencode($dateStringOrUnixTime). "'"); }

  //
  $secondsOld   = time() - $time;
  $secondsOldSM = strtotime('00:00') - $time; // seconds old since midnight of current day
  $minutesOld   = intval($secondsOld / 60);
  $hoursOld     = intval($secondsOld / (60*60));
  $daysOld      = ceil($secondsOldSM / (60*60*24));   // since midnight, rounded up
  $monthsOld    = intval($daysOld    / 30);           // approx months (assumes 30 days/month)

  #print " - $secondsOld seconds old, $minutesOld min old, $hoursOld hours old, $daysOld daysOld, $monthsOld months Old - ";

  //
  if ($monthsOld  >= 6)   { return date('F j, Y', $time); }                         // March 9, 2010
  if ($daysOld    >= 5)   { return date('F j', $time); }                            // June 2
  if ($daysOld    >= 2)   { return date('l', $time) .' at '. date('g:ia', $time); } // Tuesday at 1:26pm
  if ($daysOld    >= 1)   { return 'Yesterday at ' . date('g:ia', $time); }         // Yesterday at 1:26pm
  if ($hoursOld   >= 2)   { return "$hoursOld hours ago"; }                         // 4 hours ago
  if ($minutesOld >= 46)  { return "about an hour ago"; }                           // about an hour ago
  if ($minutesOld >= 2)   { return "$minutesOld minutes ago"; }                     // 38 minutes ago
  if ($secondsOld >= 60)  { return "about a minute ago"; }                          // about a minute ago
  if ($secondsOld >= 3)   { return "$secondsOld seconds ago"; }                     // 47 seconds ago
  if ($secondsOld >= 0)   { return "a few seconds ago"; }                           // a few seconds ago
  if ($secondsOld <  0)   { return 'In the future'; }                               // in the future
}

// echo xxx_pluralize($count, '%d cow', '%d cows');
function xxx_pluralize($quantity, $singular, $plural) {
  return sprintf($quantity == 1 ? $singular : $plural, $quantity);
}

// functional interface for preg_match: works exactly like preg_match EXCEPT returns array with ($success, $brackets1, $brackets2, ...
// note that you can still use the $matches array, and if you need the entire matches string just surround your pattern in quotes.
// list($success, $brackets1, $brackets2, $brackets3) = preg_match_get('/(pa)(tt)(ern)?/', $subject);
// list(,$brackets1) = preg_match_get('/(pa)(tt)(ern)?/', $subject);
// $brackets1 = preg_match_get('/(pa)(tt)(ern)?/', $subject)[1];
function preg_match_get($pattern, $subject, &$matches = [], $flags = 0, $offset = 0) {
  $return = preg_match($pattern, $subject, $matches, $flags, $offset);
  array_shift($matches); // remove $matchedString since we want $brackets1 to correspond to array index[1]
  return array_merge(array($return), $matches, array_fill(0,99,null));
  // pad with nulls to avoid "Undefined offset:" errors if we return less than the user is expecting when we don't get a match
}


// get the currently logged in CMS user, even if user is logged into a session with a different plugin or application
// closes original session, starts CMS session, loads current user record, closes CMS session, then restores original session
// Usage: $CMS_USER = getCurrentUserFromCMS();
function getCurrentUserFromCMS() {
  // NOTE: Keep this in /lib/common.php, not login_functions.php or user_functions.php so no extra libraries need to be loaded to call it
  require_once SCRIPT_DIR . "/lib/login_functions.php";  // if not already loaded by a plugin - loads getCurrentUser() and accountsTable();

  // save old cookiespace and accounts table
  $oldCookiePrefix  = array_first(cookiePrefix(false, true)); // save old cookiespace
  $oldAccountsTable = accountsTable();                        // save old accounts table

  // switch to cms admin cookiespace and accounts table and load current CMS user
  cookiePrefix('cms');                                        // switch to CMS Admin cookiespace
  accountsTable('accounts');                                  // switch to CMS Admin accounts table

  //
  $cookieLoginDataEncoded = getPrefixedCookie( loginCookie_name() );
  if ($cookieLoginDataEncoded) { $cmsUser = getCurrentUser($loginExpired); }
  else { $cmsUser = []; }

  // 2.52 - load cms users accessList (needed by viewer_functions.php for previewing)
  if ($cmsUser && $cmsUser['num']) { // 2.64 - only add if user found
    $records = mysql_select('_accesslist', array('userNum' => $cmsUser['num']));
    foreach ($records as $record) {
      $cmsUser['accessList'][ $record['tableName'] ]['accessLevel'] = $record['accessLevel'];
      $cmsUser['accessList'][ $record['tableName'] ]['maxRecords']  = $record['maxRecords'];
    }
  }

  // switch back to previous cookiespace and accounts table
  cookiePrefix($oldCookiePrefix);
  accountsTable($oldAccountsTable);

  //
  return $cmsUser;
}


// return null if argument is boolean false - v2.52
function nullIfFalse($var) {
  return $var ?: null;
}

//
function getRequestedAction($defaultAction = '') {

  //
  doAction('admin_getRequestedAction');

  # parse action out of key format: name="action=sampleList" value="List"
  # (the submit button value is often used for display purposes and can't be used to specify an action value)
  foreach (array_keys($_REQUEST) as $key) {
    if (str_starts_with($key, 'action=') || str_starts_with($key, '_action=')) {
      [$stringActionEquals, $actionValue] = explode("=", $key, 2);
      $_REQUEST['_action'] = $actionValue;
    }
  }

  # get actions
  $action = '';
  if     (@$_REQUEST['_advancedActionSubmit'] && @$_REQUEST['_advancedAction']) { // advanced commands can be urls or action values
    if (startsWith('?', $_REQUEST['_advancedAction'])) { redirectBrowserToURL($_REQUEST['_advancedAction']); } // added in v2.15, previously support through javascript on edit/view but not list
    else                                               { $action = $_REQUEST['_advancedAction']; }
  }
  elseif (@$_REQUEST['_action'])                 { $action = $_REQUEST['_action']; }          # explicit action (move towards _action)
  elseif (@$_REQUEST['action'])                  { $action = $_REQUEST['action']; }           # explicit action (deprecate this one)
  elseif (@$_REQUEST['_defaultAction'])          { $action = $_REQUEST['_defaultAction']; }   # default action
  else                                           { $action = $defaultAction; }

  #
  return $action;
}

// output pretty JSON
function json_encode_pretty($struct) {
  $json = json_encode($struct, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_PARTIAL_OUTPUT_ON_ERROR );

  // check for errors
  $jsonErrorCode = json_last_error();
  $jsonErrorMsg  = json_last_error_msg();
  if ($jsonErrorCode) { @trigger_error(__FUNCTION__ . ": $jsonErrorMsg", E_USER_NOTICE); }

  //
  return $json;
}

/*
  utf8_force() - Force a string or array to be valid UTF-8 and:
    - automatically correct invalid UTF-8 (replacing invalid chars or sequences with ?), this helps when
      ... your otherwise valid UTF-8 data can get easily "corrupted" when you add data from executable programs
      ... output or other sources that isn't UTF-8!  A single high-ascii byte can render your content invalid to
      ... MySQL - CAUSING YOUR INSERT OR UPDATE TO FAIL!  So watch out for that.
    - supports taking arrays OR strings as input
    - optionally removes/replaces 4-byte UTF-8 chars (MySQL only supports 3-byte UTF-8 chars and fails on 4-byte UTF-8 chars)
      *** NOTE: $replace4ByteUTF8 isn't need for v3.11+ as we switched to utf8mb4 which supports 4-byte UTF8
    - optionally converts from alternate character encodings to UTF-8 (on invalid charset assumes UTF-8)
    *** NOTE: Binary data should not be encoded as UTF-8 or stored UTF-8 charset columns in MySQL as it
        ... can easily get corrupted if you don't encode and decode it in the exact same way.  Use a
        ... binary or varbinary column type instead.

  References:
    - UTF-8 Brief Overview: http://php.net/utf8_encode
    - UTF-8 Detailed Overview: http://en.wikipedia.org/wiki/UTF-8
    - Ascii table with binary to hex: http://www.ascii-code.com/
    - Emjoi table with examples of 3 and 4 byte UTF-8 sequences: http://apps.timwhitlock.info/emoji/tables/unicode
    - UTF-8 Examples page: http://www.columbia.edu/~kermit/utf8.html
    - Mysql docs on not supporting 4 bytes chars without special character set: http://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html

  Example:
      $string = utf8_force($string);                        // output valid utf-8
      $string = utf8_force($string, true);                  // output valid utf-8 that is mysql safe with 4-byte chars replaced with <?> replacement char
      $string = utf8_force($string, true, 'ISO-8859-1');    // convert from another character set.  Valid values: http://php.net/manual/en/mbstring.supported-encodings.php
      $string = utf8_force($string, true, 'Windows-1252');  // convert from another character set.  Valid values: http://php.net/manual/en/mbstring.supported-encodings.php
*/
function utf8_force($stringOrArray, $replace4ByteUTF8 = false, $convertFromEncoding = '') {
  // NOTE: $replace4ByteUTF8 isn't needed for v3.11+ as we switched to utf8mb4 which supports 4-byte UTF8

  // error checking
  if (mb_internal_encoding() != 'UTF-8') {
    // we default to assuming an incoming encoding of utf-8, so we want to check that's the case
    // we generally don't want to support scripts that run with some other internal encoding
    die(__FUNCTION__ . ": mb_internal_encoding() must be UTF-8, not '" .htmlencode(mb_internal_encoding()). "'");
  }
  if ($convertFromEncoding && !@mb_convert_encoding('1', $convertFromEncoding)) { // mb_convert_encoding() returns false if encoding isn't recognized
    die(__FUNCTION__ . ": unknown character set specified '" .htmlencode($convertFromEncoding). "'");
  }

  // support recursively converting arrays
  if (is_array($stringOrArray)) {
    foreach ($stringOrArray as $index => $string) {
      $stringOrArray[$index] = utf8_force($string, $replace4ByteUTF8, $convertFromEncoding);
    }
    return $stringOrArray;
  }

  // convert string to UTF-8
  $string     = $stringOrArray;
  $encoding   = $convertFromEncoding ?: 'UTF-8';                          // Note: encoding from utf-8 to utf-8 "fixes" invalid utf-8 (replaces invalid sequences with a replacement char ?)
  $utf8String = mb_convert_encoding(strval($string), 'UTF-8', $encoding); // mb_convert_encoding() replaces unknown/invalid sequences with ascii "?"

  // replace 4-byte utf-8 for mysql compatability, see: http://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html
  // for security replace chars don't remove, see: http://unicode.org/reports/tr36/#Deletion_of_Noncharacters
  if ($replace4ByteUTF8) {
    $replacementChar = "\xEF\xBF\xBD"; /* For security replace don't remove, official replacement char looks like this: <?> see: http://unicode.org/reports/tr36/#Deletion_of_Noncharacters */
    $utf8String = preg_replace('/[\xF0-\xF7].../s', $replacementChar, $utf8String);  // replace 4-byte utf-8 sequences that start with binary: 11110xxx
  }

  //
  return $utf8String;
}

// load composer autoloader and return errors if component is missing
// Usage: composerAutoload(['twilio/sdk']); // checks for /vendor/twilio/sdk
// NOTE: This still requires the composer components to be manually installed
function composerAutoload($requiredComponentDirs = []) {
  $composerPath  = SCRIPT_DIR . "/composer.phar";
  $vendorDir     = SCRIPT_DIR . "/vendor/";

  //
  if (!file_exists($composerPath)) { // check for composer.phar
    $error  = "Error: composer.phar not found.  You must install composer to continue.\n";
    $error .= "File not found: $composerPath\n";
    $error .= "Install Instructions: https://getcomposer.org/download/\n";
    die(nl2br($error));
  }

  //
  if (!file_exists($vendorDir)) {
    $error  = "Error: Composer vendor dir not found: $vendorDir\n";
    $error .= "Run the following commands to continue: composer update\n";
    die(nl2br($error));
  }

  //
  $missingComponentDirs = [];
  foreach ($requiredComponentDirs as $componentDir) {
    if (!file_exists(SCRIPT_DIR . "/vendor/$componentDir")) { $missingComponentDirs[] = $componentDir; }
  }
  if ($missingComponentDirs) {
    $error  = "Error: Composer components not found: " .implode(", ", $missingComponentDirs). "\n";
    $error .= "Run the following commands to continue: \n<ul>";
    foreach ($missingComponentDirs as $componentDir) {
      $error .= "<li>composer require $componentDir\n";
    }
    $error .= "</ul>\n";
    die(nl2br($error));
  }

  //
  require_once SCRIPT_DIR . "/vendor/autoload.php";
}

/**
 * Determines if the script is running in a Command Line Interface (CLI) environment.
 *
 * @return bool True if in CLI, False otherwise.
 */
function inCLI(): bool {
    $inCLI = false;

    // Check if the Server API (SAPI) is CLI
    if (PHP_SAPI === 'cli') { $inCLI = true; }

    // Check if the session name is 'Console' - Windows CLI condition
    if (($_SERVER['SESSIONNAME'] ?? '') === 'Console') { $inCLI = true; }

    // Check if SCRIPT_NAME is set, typically only done by web server
    if (!isset($_SERVER['SCRIPT_NAME'])) { $inCLI = true; }

    return $inCLI;
}
