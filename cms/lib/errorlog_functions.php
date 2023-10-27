<?php
/**
  NOTES:
    - You can manually create an error log entry with this code and the error will be
      ... NOT be displayed to end user and code execution won't be interrupted:
      @trigger_error("Your error message", E_USER_NOTICE);

      To display an error (if show errors is enabled) and continue code execution, use this code:
      trigger_error("Your error message", E_USER_NOTICE);

      To display an error (if show errors is enabled) and die, use this code:
      trigger_error("Your error message", E_USER_ERROR);

    - *** NOTE: If "Hide Errors" is enabled under Admin > General, then the above will produce "(An unexpected error occurred: #1234)" where the number is the error number

    - We can't/don't catch E_PARSE compile time errors that are returned by the parser.
    - List of error types: http://php.net/manual/en/errorfunc.constants.php
*/

// enable error logging
if (isInstalled()) {
  //if ($_SERVER['REQUEST_METHOD'] != 'HEAD') { // prevents HEAD request errors being logged from _errorlog_getBrokenPhpFunctionsWarning()
  errorlog_enable();
  //}
}

// enable logging to text file for debugging purposes - this is in addition to other error logging methods
function _enable_errorlog_textfile(): bool {
  //return true;
  return false;
}

// check if function is being called from error log (to prevent recursive errors)
function errorlog_inCallerStack(): bool {
  $functionStack = array_column( debug_backtrace(), 'function' );
  $inCallerStack = in_array('_errorlog_logErrorRecord', $functionStack);
  return $inCallerStack;
}

// enable error logging - log error to internal mysql table
function errorlog_enable(): void {

    // error-checking
    if (error_reporting() !== -1) {
        die(__FUNCTION__.": error_reporting() must be set to -1, not ".error_reporting()."!");
    }

    // setup handlers
    set_error_handler('_errorlog_catchRuntimeErrors');
    set_exception_handler('_errorlog_catchUncaughtExceptions');

    // catch fatal errors by checking error_get_last() in register_shutdown_function() below and checking it's not an _errorlog_alreadySeenError
    // Register any error_get_last() errors that occur before register_shutdown_function() as seen
    $lastError = error_get_last();
    if (isset($lastError['file'], $lastError['line'])) {
        _errorlog_alreadySeenError($lastError['file'], $lastError['line']);
    }

    register_shutdown_function('_errorlog_catchFatalErrors');
}

// catch runtime errors - called by set_error_handler() above
// argument definitions: http://php.net/manual/en/function.set-error-handler.php
function _errorlog_catchRuntimeErrors($errno = '', $errstr = '', $errfile = '', $errline = ''): bool {
  errorlog_lastError($errstr, $errno);                                                                                 // log last error for display and reference
  _errorlog_alreadySeenError($errfile, $errline);                                                                      // track that this error was seen, so we don't report it again in _errorlog_catchFatalErrors()
  $is_E_USER_TYPE    = in_array($errno, array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_USER_DEPRECATED));        // E_USER_* errors are called explicitly, so we'll assume if they use @error-suppression they still want to be logged
  $isErrorSuppressed = !(error_reporting() & (int)$errno);                                                             // Detect if @ is being used by checking if error_reporting() value is changed.  Search for 'error_reporting' here: https://www.php.net/manual/en/migration80.incompatible.php
  $ignoreError       = $isErrorSuppressed && !$is_E_USER_TYPE;
  if ($ignoreError) {   // ignore '@' error control operator except for E_USER_
    error_clear_last(); // prevent future calls to error_get_last() to show this error
    return false;       // continue standard PHP execution, including built-in error handling.  Because we're using @ no error will be displayed, but error_get_last() will get set
  }

  // format errstr
  $errstr = htmlspecialchars_decode( $errstr, ENT_QUOTES|ENT_SUBSTITUTE|ENT_XHTML );                                   // Errors are htmlencoded to prevent XSS when they're output to the screen by PHP.  We decode back to plain text for storing in DB

  // log data
  $logData = array(
    'logType'     => 'runtime',
    'errno'       => $errno,
    'errstr'      => $errstr,
    'errfile'     => $errfile,
    'errline'     => $errline,
  );
  $errorRecordNum = _errorlog_logErrorRecord($logData);

    // show error message
    if ($isErrorSuppressed) { // this should only be $is_E_USER_TYPE at this point
        return true; // continue standard PHP execution and SKIP any built-in error handling
    }

    if (!ini_get('display_errors')) {  // or check: $GLOBALS['SETTINGS']['advanced']['phpHideErrors']
        print errorlog_messageWhenErrorsHidden($errorRecordNum); // don't show any error messages
    }
    else { // display default error message
        [$externalFilePath, $externalLineNum] = getExternalCaller($errfile, $errline);
        // don't double encode entitles and don't encode quotes
        $errorMessage = htmlspecialchars(trim($errstr), ENT_NOQUOTES|ENT_SUBSTITUTE|ENT_HTML5, 'UTF-8', false);
        $errorMessage .= " in $externalFilePath on line $externalLineNum";

        $hasErrorPrefix = preg_match("/^[\w ]{0,25}:/", $errorMessage);
        if (!$hasErrorPrefix) {
            $prefix       = _errorlog_errnoToConstantLabel($errno);
            $errorMessage = "$prefix: $errorMessage";
        }

        print "\n$errorMessage\n";
    }


    // Halt execution on fatal errors
    $isFatalError = in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true); // this handler function will only catch E_USER_ERROR and only without @
    if ($isFatalError) { exit; } // fatal errors should halt script execution

    // Prevent the execution of the internal PHP error handler
    return true;


}


// catch fatal errors - called by register_shutdown_function() above
function _errorlog_catchFatalErrors(): void {
  $error = error_get_last();
  if ($error === null) { return; } // no error
  if (_errorlog_alreadySeenError($error['file'], $error['line'])) { return; } // error already processed (or ignored for @hidden warnings)

  $logData = array(
    'logType'     => 'fatal',
    'errno'       => $error['type'],     // eg: 8   - from: http://php.net/manual/en/errorfunc.constants.php
    'errstr'      => $error['message'],  // eg: Undefined variable: a
    'errfile'     => $error['file'],     // eg: C:\WWW\index.php
    'errline'     => $error['line'],     // eg: 2
  );
  _errorlog_logErrorRecord($logData);

  // halt script execution
  exit;
}


// catch uncaught exceptions - called by set_error_handler() above
function _errorlog_catchUncaughtExceptions($exceptionObj, $allowResume=false): void {
  //$logData = (array) $exceptionObj; // http://php.net/manual/en/class.exception.php
  $logData = array(
    'logType'        => 'exception',
    'errno'          => 'Uncaught ' . get_class($exceptionObj),
    'errstr'         => $exceptionObj->getMessage(),  // method reference: http://php.net/manual/en/language.exceptions.extending.php
    'errfile'        => $exceptionObj->getFile(),
    'errline'        => $exceptionObj->getLine(),
    'exceptionCode'  => $exceptionObj->getCode(),
    'exceptionClass' => get_class($exceptionObj),
    'exceptionTrace' => "\n" .$exceptionObj->getTraceAsString(),
    'backtrace'      => _errorlog_getExceptionBacktraceText($exceptionObj),
  );
  if (!$allowResume) { $logData['errno'] = 'Fatal Error: ' . $logData['errno']; }
  $errorRecordNum = _errorlog_logErrorRecord($logData);

  // PHP will not continue or show any errors if we're catching exceptions, so show errors ourselves if it's enabled:
  if (ini_get('display_errors')) {  // or check: $GLOBALS['SETTINGS']['advanced']['phpHideErrors']
    [$externalFilePath, $externalLineNum] = getExternalCaller($exceptionObj->getFile(), $exceptionObj->getLine());
    $error  = $exceptionObj->getMessage(). "\n";
    $error .= "in $externalFilePath on line $externalLineNum. ";
  }
  else {
    $error = errorlog_messageWhenErrorsHidden($errorRecordNum);
  }
  print $error;

  // halt script execution after uncaught exceptions
  if (!$allowResume) { exit; }
}

// return true if error matches a previous one processed
// Background: Code that suppresses errors with @ still causes set_error_handler() to be called, but we can
// ... detect that scenario by checking if error_reporting() === 0, but when catching fatal errors with
// ... register_shutdown_function() error_reporting() can't be used to detect previous use of @, so we check
// ... errors reported there were previously seen (and ignored) by _errorlog_catchRuntimeErrors().
function _errorlog_alreadySeenError($filePath, $lineNum): bool {
  static $filesToLineNums = [];

  // have we seen this error
  $alreadySeenError = !empty($filesToLineNums[$filePath][$lineNum]);

  // record this as a seen error
  $filesToLineNums[$filePath][$lineNum] = 1;

  //
  return $alreadySeenError;
}

/**
 * Logs error records caught with: set_error_handler, set_exception_handler, register_shutdown_function
 *  - Error log debugging text file can be enabled in function: _enable_errorlog_textfile()
 *  - PHP default error log can be enabled by setting $php_error_log_file to true in init.php (line 60).
 *  - Max errors in log: 1000. Oldest records are removed when record count hits 1100.
 *  - Max errors to log per page: 25. Further errors won't be logged within the same page load.
 *  - Prevent duplicates by adding "[MAXLOG1]" to the end of 'errstr' to remove all previous error logs with the same error message leaving only 1
 *
 * @param array $logData An associative array of log data. It can contain the following keys:
 * <pre>
 * - 'errno'      => (optional) PHP Error Type Constant or custom prefix text string. See: https://www.php.net/manual/en/errorfunc.constants.php
 * - 'errstr'     => Error message.
 * - 'errfile'    => Filepath where the error occurred.
 * - 'errline'    => (optional) Line number where the error occurred.
 * - 'errurl'     => (optional) URL of script where error occurred. Automatically set if not defined.
 * - 'backtrace'  => (optional) Backtrace text. Automatically set if not defined.
 * </pre>
 *
 * @return int|null The record number of the error logged or null if the system is not installed or if the
 *                  maximum amount of loggable errors for the page has been reached.
 *
 * @throws Exception If there is an error writing to the error log.
 *
 * @see set_error_handler
 * @see set_exception_handler
 * @see register_shutdown_function
 *
 * @example
 * $errorRecordNum = _errorlog_logErrorRecord([
 *   'errno'   => 'CUSTOM ERROR [MAXLOG1]', // (OPTIONAL) PHP Error Type Constant or text string you'd like to use as a prefix - See: https://www.php.net/manual/en/errorfunc.constants.php
 *   'errstr'  => "Error message goes here",
 *   'errfile' => __FILE__,         // filepath of file error occurred in
 *   //'errline'    => __LINE__,    // (OPTIONAL) line number where error occurred
 *   //'errurl'     => null,        // (OPTIONAL) url of script where error occurred, automatically set if not defined
 *   //'backtrace'  => null,        // (OPTIONAL) backtrace text, automatically set if not defined
 * ]);
 */
function _errorlog_logErrorRecord(array $logData): ?int {
  if (!isInstalled()) { return null; }

  // limit errors logged per session (to prevent infinite loops from logging infinite errors)
  $maxErrorsPerPage = 25;
  $maxErrorsReached = false;
  static $totalErrorsLogged = 0;
  $totalErrorsLogged++;
  if ($totalErrorsLogged > ($maxErrorsPerPage+1)) { return null; } // ignore any errors after max error limit
  if ($totalErrorsLogged > $maxErrorsPerPage) { $maxErrorsReached = true; }

  // create error message
  if ($maxErrorsReached)        { $errorMessage = t(sprintf("Max error limit reached! Only the first %s errors per page will be logged.", $maxErrorsPerPage)); }
  else {
    $errorMessage = "";
    if (isset($logData['errno']))  { $errorMessage .= _errorlog_errnoToConstantLabel($logData['errno']). ": "; } // eg: "Warning: "
    if (isset($logData['errstr'])) { $errorMessage .= $logData['errstr']; }
  }

  // detect servers with broken print_r, etc. functions.  See function header for more details
  $errorMessage = rtrim($errorMessage, "\n");
  $errorMessage .= _errorlog_getBrokenPhpFunctionsWarning();

  // add to error summary
  $errfile = $logData['errfile'] ?? '';
  $errline = $logData['errline'] ?? '';

  // get backtrace text
  if (isset($logData['backtrace'])) { // created for exceptions by _errorlog_catchUncaughtExceptions
    $backtraceText = $logData['backtrace'];
    unset($logData['backtrace']); // we don't need to log this twice
  }
  else {
    // emulate debug_print_backtrace since we can't rely on ob_start being available (see: _errorlog_getBrokenPhpFunctionsWarning)
    $backtraceText = '';
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100) as $index => $frame) {
      $backtraceText .= "\n#$index  {$frame['function']}() called";
      if     (isset($frame['file']) && isset($frame['line'])) { $backtraceText .= " at [{$frame['file']}:{$frame['line']}]"; }
      elseif (isset($frame['file']))                          { $backtraceText .= " at [{$frame['file']}]"; }
    }
    $backtraceText = ltrim($backtraceText, "\n");
  }

  // check if we have a valid mysql connection
  if (mysql_isConnected() && !@mysqli()->ping()) { mysql_isConnected(false); } // flag as disconnected if ping fails, so isConnected checks below return correct value

  // get non-cms external caller filepath and line num
  [$externalFilePath, $externalLineNum] = getExternalCaller($errfile, $errline);

  // get $rawLogDataAsText
  $logDataSummary = $logData;
  $rawLogDataAsText  =  utf8_force(_errorlog_serialize($logDataSummary));

  // Our best guess at where the error originated
  $displayFilepath = $logData['errfile'] ?? '';
  $displayLineNum  = $logData['errline'] ?? '';
  if ($externalFilePath && $externalLineNum) {
    $displayFilepath = $externalFilePath;
    $displayLineNum  = $externalLineNum;
  }

  //  create log record data
  $colsToValues = array(
    'dateLogged='      => 'NOW()',

    'error'           => $errorMessage,
    'url'             => $logData['errurl'] ?? (inCLI() ? '' : thisPageUrl()),
    'filepath'        => $displayFilepath,
    'line_num'        => $displayLineNum,
    'user_cms'        => _errorlog_getCurrentUserSummaryFor('cms'),
    'user_web'        => _errorlog_getCurrentUserSummaryFor('web'),
    'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '', // $_SERVER['HTTP_USER_AGENT'],
    'remote_addr'     => $_SERVER['REMOTE_ADDR'] ?? '',

    'backtrace'       => $backtraceText,
    'request_vars'    => _errorlog_serialize($_REQUEST),
    'get_vars'        => _errorlog_serialize($_GET),
    'post_vars'       => _errorlog_serialize($_POST),
    'cookie_vars'     => _errorlog_serialize($_COOKIE),
    'session_vars'    => isset($_SESSION) ? _errorlog_serialize($_SESSION)  : '',
    'server_vars'     => _errorlog_serialize($_SERVER),
    'raw_log_data'    => $rawLogDataAsText, //
  );

  // debug - log error log output to text log
  if (_enable_errorlog_textfile()) {
    $debug_errorlog_data = '';
    $debug_errorlog_file = 'error_log_debug.log';
    $debug_errorlog_path = DATA_DIR."/$debug_errorlog_file";
    $debug_errorlog_data .= "\n\nError Log \$colsToValues:\n" . _errorlog_serialize($colsToValues);
    //$debug_errorlog_data .= "\n\nError Log ColsToValues (utf8_force):\n" . _errorlog_serialize(utf8_force($colsToValues));
    file_put_contents($debug_errorlog_path, $debug_errorlog_data, FILE_APPEND);
  }

  // Single log errors - if [MAXLOG1] string found, remove it and all errors with same title before adding error
  if (mysql_isConnected()) {
    $colsToValues['error']  = preg_replace("/\s*\[MAXLOG1\]/", "", $colsToValues['error'], -1, $foundMaxLogString);
    $isErrorLogEditViewPage = isset($_REQUEST['menu']) && $_REQUEST['menu'] == '_error_log' && in_array(getRequestedAction(), ['edit','view']);  // don't erase duplicate errors while viewing error_log, otherwise errors that get triggered on every page will get erased when we click from list -> edit page resulting in a blank/missing/empty record when we click view/modify
    if ($foundMaxLogString && !$isErrorLogEditViewPage) { mysql_delete('_error_log', null, ['error' => $colsToValues['error']]); }
  }

  // insert record - use manual code to catch errors
  $recordNum = 0;
  if (mysql_isConnected()) {
    $tableName    = $GLOBALS['TABLE_PREFIX'] . '_error_log';
    $insertQuery  = "INSERT INTO `$tableName` SET " . mysql_set(utf8_force($colsToValues));
    mysqlStrictMode(false); // hide errors in case of extra fields in _error_log that return "default value not set" warning
    $success = mysqli()->query($insertQuery);
    $recordNum = mysqli()->insert_id;
    if (!$success) {
      print "Error writing to error log!\nMySQL Error: ". mysqli()->error. "<br>\n<br>\n";
      print "Original error: ";
      if (ini_get('display_errors')) {
        print htmlencodef("?\n in ?:?<br>\n<br>\n", $colsToValues['error'], $colsToValues['filepath'], $colsToValues['line_num']);
      }
      else {
        print "Not shown because 'Hide PHP Errors' is enabled under Admin &gt; General.<br>\n";
      }
    }
    mysqlStrictMode(true);
  }

  // remove old log records
  if (mysql_isConnected()) {
    $maxRecords = 900;
    $buffer     = 100; // only erase records when we're this many over (to avoid erasing records every time)
    if (mysql_count('_error_log') > ($maxRecords + $buffer)) {
      $oldestRecordToSave_query = "SELECT * FROM `{$GLOBALS['TABLE_PREFIX']}_error_log` ORDER BY `num` DESC LIMIT 1 OFFSET " .($maxRecords-1);
      $oldestRecordToSave = mysql_get_query($oldestRecordToSave_query);
      if (!empty($oldestRecordToSave['num'])) {
        mysql_delete('_error_log', null, "num < {$oldestRecordToSave['num']}");
      }
    }
  }

  // send email update
  register_shutdown_function('_errorlog_sendEmailAlert');

  //
  return $recordNum;
}

// returns: E_WARNING, E_NOTICE, etc.
// list of constants: // http://php.net/manual/en/errorfunc.constants.php
function _errorlog_errnoToConstantName($errno) {
  static $numsToNames;

  // create index of nums to names
  if (!$numsToNames) {
    foreach (get_defined_constants() as $name => $num) {
      if (str_starts_with($name, "E_")) { $numsToNames[$num] = $name; }
    }
  }

    //
    if (array_key_exists($errno, $numsToNames)) {
        return $numsToNames[$errno];
    }
    return $errno;
}

// Returns Warning, Notice, etc.  These match what PHP outputs in error messages
// list of constants: // http://php.net/manual/en/errorfunc.constants.php
// Usage: $label = _errorlog_errnoToConstantLabel($errno);
function _errorlog_errnoToConstantLabel($errno): string {
  if (is_string($errno)) { return $errno; } // return string if already defined
  $label = "Unknown error";
  $labelToValues = [
    "Warning"               => [E_WARNING, E_USER_WARNING, E_COMPILE_WARNING, E_CORE_WARNING],
    "Notice"                => [E_NOTICE, E_USER_NOTICE],
    "Fatal error"           => [E_ERROR, E_USER_ERROR, E_COMPILE_ERROR, E_CORE_ERROR],
    "Parse error"           => [E_PARSE],
    "Deprecated"            => [E_DEPRECATED, E_USER_DEPRECATED],
    "Strict Standards"      => [E_STRICT],
    "Catchable fatal error" => [E_RECOVERABLE_ERROR]
  ];

  foreach ($labelToValues as $thisLabel => $values) {
    if (!in_array($errno, $values)) { continue; }
    $label = $thisLabel;
    break; // Exit the loop once we've found a match
  }

  return $label;
}

// Send an email after the script has finished executing, called by _errorlog_logErrorRecord function
// register this function to run with: register_shutdown_function('_errorlog_sendEmailAlert');
function _errorlog_sendEmailAlert(): void {
  if (!$GLOBALS['SETTINGS']['advanced']['phpEmailErrors']) { return; }
  if (!mysql_isConnected()) { return; } // can't load records if we can't connect to mysql

  // send hourly email alert about new errors
  $secondsAgo = time() - $GLOBALS['SETTINGS']['errorlog_lastEmail'];
  if ($secondsAgo >= (60*60)) { // don't email more than once an hour

    // get date format
    if     ($GLOBALS['SETTINGS']['dateFormat'] == 'dmy') { $dateFormat  = "jS M, Y - h:i:s A"; }
    elseif ($GLOBALS['SETTINGS']['dateFormat'] == 'mdy') { $dateFormat  = "M jS, Y - h:i:s A"; }
    else                                                 { $dateFormat  = "M jS, Y - h:i:s A"; }

    // load latest error list
    $latestErrors     = mysql_select('_error_log', "`dateLogged` > (NOW() - INTERVAL 1 HOUR) ORDER BY `dateLogged` DESC LIMIT 25");
    $latestErrorsList = '';
    foreach ($latestErrors as $thisError) {
      $latestErrorsList .= date($dateFormat, strtotime($thisError['dateLogged']))."\n";
      $latestErrorsList .= $thisError['error']."\n";
      $latestErrorsList .= $thisError['filepath']." (line ".$thisError['line_num'].")\n";
      $latestErrorsList .= $thisError['url']."\n\n";
    }

    // send email message
    $placeholders = array(
      'error.hostname'         => parse_url($GLOBALS['SETTINGS']['adminUrl'], PHP_URL_HOST),
      'error.latestErrorsList' => nl2br(htmlencode($latestErrorsList)),
      'error.errorLogUrl'      => realUrl("?menu=_error_log", $GLOBALS['SETTINGS']['adminUrl']),
      'error.servername'       => php_uname('n'),
    );
    $errors  = sendMessage(emailTemplate_loadFromDB(array(
      'template_id'  => 'CMS-ERRORLOG-ALERT',
      'placeholders' => $placeholders,
    )));

    // log/display email sending errors
    if ($errors) {
      trigger_error("Unable to send error notification email from " .__FUNCTION__ . ": $errors"); // E_USER_NOTICE is the default error_level which is used
      die(__FUNCTION__. ": $errors");
    }

    // update last emailed time
    $GLOBALS['SETTINGS']['errorlog_lastEmail'] = time();
    saveSettings();
  }

}


// Return a more detailed backtrace of exceptions
// Based on jTraceEx from: http://php.net/manual/en/exception.gettraceasstring.php#114980
// Usage: $stack = _errorlog_getExceptionBacktraceText($exceptionErrorObj)
function _errorlog_getExceptionBacktraceText(Throwable $exceptionErrorObj): string {
    $traceArray = $exceptionErrorObj->getTrace();
    $traceString = '';
    foreach ($traceArray as $key => $value) {
        $traceString .= "#" . $key;
        $traceString .= isset($value['class']) ? " " . $value['class'] : '';
        $traceString .= $value['type'] ?? '';
        $traceString .= " " . $value['function'] . "()";
        $traceString .= isset($value['file']) ? " called at [" . $value['file'] : '';
        $traceString .= isset($value['line']) ? ":" . $value['line'] : '';
        $traceString .= isset($value['file']) ? "]\n" : '';
    }
    return $traceString;
}

// get summary data of logged-in users
// Usage: _errorlog_getCurrentUserSummaryFor($userType); // cms or web
function _errorlog_getCurrentUserSummaryFor(string $userType = ''): string {
  if (!mysql_isConnected()) { return ''; }

  if     ($userType == 'cms') {
    $tablename     = "accounts";
    $userRecord    = getCurrentUserFromCMS();
  }
  elseif ($userType == 'web') {
    $tablename     = accountsTable();
    $userRecord    = getCurrentUser();
  }
  else {
    $tablename = '';
    $userRecord = [];  // these last two lines are to hide unused var warnings in IDE
    dieAsCaller("Unknown userType '" .htmlencode($userType). "'!");
  }

  // create summary data
  $summaryText   = "";
  if ($userRecord) {
    $summaryFields = array('num','username');
    $summaryRecord = array_intersect_key($userRecord, array_flip($summaryFields));
    $summaryRecord['_tableName'] = $tablename;
    $summaryText   = _errorlog_serialize($summaryRecord);
  }

  //
  return $summaryText;
}

// show this when errors are hidden
function errorlog_messageWhenErrorsHidden(?int $recordNum): string {
  $error = "\n" .sprintf(t("(An unexpected error occurred: #%s)"), $recordNum). "\n";
  return $error;
}

/**
 * Returns a text warning if broken PHP functions are detected on server, or blank otherwise.
 * This function is useful to detect servers with broken print_r. These issues have been seen:
 * - July 2015 - 1&1 UK Shared Hosting Package - occurs on HEAD request after any content sent
 * - July 2023 - Liquid Web - with HEAD requests: Broken functions: print_r, ob_get_clean
 *
 * These errors can be recreating by sending a HEAD request from the command line with curl as follows:
 * curl -I https://www.example.com/
 *
 * @return string A string with the names of the broken PHP functions detected, or an empty string if none are detected.
 */
function _errorlog_getBrokenPhpFunctionsWarning(): string {
  static $brokenFunctions; // cache result

  // if no cached result, check for broken functions
  if (!is_array($brokenFunctions)) {
    $brokenFunctions = [];

    // test if ob_start/ob_get_clean() is working.
    $ob_get_clean_response  = '1';
    $inOutputBufferCallback = 0;  // PHP throws an error if you call output buffering inside an output buffer callback
    foreach (ob_get_status(true) as $bufferStatus) { if ($bufferStatus['type'] == 1) { $inOutputBufferCallback = true; break; }}
    if (!$inOutputBufferCallback) { ob_start(); echo '1'; $ob_get_clean_response = ob_get_clean(); }

    //
    if (print_r(1, true) != '1')       { $brokenFunctions[] = "print_r"; }
    if ($ob_get_clean_response != '1') { $brokenFunctions[] = "ob_get_clean"; }
    if (var_export(1, true) != '1')    { $brokenFunctions[] = "var_export"; }   // no reported errors with this function yet
    if (json_encode(1) != '1')         { $brokenFunctions[] = "json_encode"; }  // no reported errors with this function yet
  }

  // return
  $errorMessage = '';
  if ($brokenFunctions) {
    $brokenFunctionsAsCSV = implode(', ', $brokenFunctions);
    $remoteAddr    = $_SERVER['REMOTE_ADDR'] ?? '';
    $errorMessage .= "\n\nNOTE: Server has broken PHP functions: $brokenFunctionsAsCSV. This is sometimes caused by HTTP HEAD requests. ";
    $errorMessage .= "Error occurred during a HTTP {$_SERVER['REQUEST_METHOD']} request from: $remoteAddr\n";
  }
  return $errorMessage;
}

// print_r doesn't work on all servers (see: _errorlog_getBrokenPhpFunctionsWarning) so this uses alternatives where needed
// NOTE: This function also REMOVED sensitive values (passwords, etc.) for security!
function _errorlog_serialize(&$var): string {

  // check for broken output functions
  static $brokenFunctions = null; // cache result
  if (is_null($brokenFunctions)) { $brokenFunctions = (bool) _errorlog_getBrokenPhpFunctionsWarning(); }

  //
  if ($brokenFunctions) {
    // json_encode returns NULL for recursive/circular references ONLY if it's already seen them, so passing [$var]
    // ... causes it to catch the first circular reference, whereas $var doesn't.  This comes up with $GLOBALS with
    // ... contains $GLOBALS['GLOBALS'] and so on.
    $output = json_encode([$var], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_PARTIAL_OUTPUT_ON_ERROR );
    $output = preg_replace("/^(\[$|    |\]$)/m", '', $output); // remove 4 space leading padding and surrounding [ and ]
    $output = str_replace('    ', '  ', $output); // replace 4 space padding with 2
    if (json_last_error()) { $output = "// JSON Encoding Error: " .json_last_error_msg(). "\n$output";  }
    return $output;
  }

  //
  $output = print_r($var, true);

  ### remove potentially sensitive values

  // replace known safe keys, replace them with temporary values, so they don't get obfuscated
  $safeKeysSearch  = ['exceptionCode','_CSRFToken','filepath_keyword'];
  $safeKeysReplace = [];
  foreach ($safeKeysSearch as $key => $value) { $safeKeysReplace[] = "_tempSafeVar{$key}_";  } // _tempSafeVar0_, _tempSafeVar1_, etc
  $output = str_replace($safeKeysSearch, $safeKeysReplace, $output);

  // hide all others
  $keysRegexp  = ".*?(api|code|key|password|secret|token).*?";
  $replaceText = "*** symbols containing '\\2' are not logged ***";
  $output  = preg_replace("/(\[$keysRegexp\])\s*=>\s*.*?$/im", "\\1 => $replaceText", $output);  // print_r style.  eg: [my_password_str] => value,
  $output  = preg_replace("/(\"$keysRegexp\")\s*:\s*.*?$/im",  "\\1 = $replaceText", $output);  // json_style.     eg: "my_password_str": "value",

  // put our known safe keys back
  $output = str_replace($safeKeysReplace, $safeKeysSearch, $output);

  //
  return $output;

}

/**
 * Get filepath and line number of the first caller whose filepath is above of the current /lib/ folder.
 * This function is used to find the most likely origin point of an error for debugging.
 *
 * @param string|null $errfile The error file.
 * @param int|null $errline The error line.
 *
 * @return array Returns an array with the external file path and line number.
 *
 * @example [$externalFilePath, $externalLineNum] = getExternalCaller($errfile, $errline);
 */
function getExternalCaller(?string $errfile, ?int $errline): array {

  // Use default error file/line if it's above /lib/ folder.
  if (isset($errfile, $errline)) {
    $inLibFolder = str_contains($errfile, __DIR__);
    if (!$inLibFolder) {
      return [$errfile, $errline]; // Return early if error file is not in /lib/ folder
    }
  }
  // get non-cms external caller filepath and line num
  $externalFilePath = '';
  $externalLineNum  = '';
  foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100) as $caller) {
    if (!isset($caller['file']) || !isset($caller['line'])) { continue; }
    if (str_contains($caller['file'], __DIR__)) { continue; } // skip if caller is under this /lib/ folder
      $externalFilePath = $caller['file'];
      $externalLineNum  = $caller['line'];
      break;
  }

  return [$externalFilePath, $externalLineNum];
}

/**
 * Returns the last PHP error message caught by the errorlog handlers (or '' if none).
 * If you pass in parameters it will update the last saved error message.
 *
 * This is a utility function to replace $php_errormsg which was removed in PHP 8.0.
 *
 * @param string|null $message Optional error message to store.
 * @param int|null $type Optional error type to store.
 *
 * @return string The last error message or an empty string if no error occurred.
 */
function errorlog_lastError(?string $message = null, ?int $type = null): string {
    static $lastMessage = '';

    if (isset($message)) {
        $lastMessage = $message;
        // FUTURE: We might want to check $type and ignore certain error, clearing the last error.
    }

    return $lastMessage;
}

/**
 * Returns the last PHP error message caught by error_get_last (or '' if none).
 * Only works with @ suppressed errors.
 *
 * This is a utility function to replace $php_errormsg which was removed in PHP 8.0.
 *
 * Note that because _errorlog_catchRuntimeErrors() overrides PHP's default error handling,
 * errors that aren't suppressed with @ won't be returned by error_get_last or this function.
 * - When the error handler detects @ suppressed errors it ignores then and returns false so PHP's
 *   default error handler can run, and this also sets error_get_last()
 * - For all other non @ suppressed errors it returns true to prevent PHP's default error handling
 *   (and output of default error messages) but this also has the side effect of PHP not setting
 *   error_get_last().
 *
 * @return string The last error message or an empty string if no error occurred.
 */
function error_get_last_message(): string {
    return error_get_last()['message'] ?? '';
}
