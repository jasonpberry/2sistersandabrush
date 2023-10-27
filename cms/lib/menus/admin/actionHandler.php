<?php
  global $SETTINGS, $CURRENT_USER;

  ### check access level
  if (!$CURRENT_USER['isAdmin']) {
    alert(t("You don't have permissions to access this menu."));
    showInterface('');
  }

  # error checking
  $isInvalidWebRoot = !@$SETTINGS['webRootDir'] || (!is_dir(@$SETTINGS['webRootDir']) && PHP_OS != 'WINNT'); // Windows returns false for is_dir if we don't have read access to that dir
  if (!@$SETTINGS['adminEmail'] && !@$_REQUEST['adminEmail'])         { alert("Please set 'Admin Email' under: Admin Menu > Email Settings"); }
  if (!@$SETTINGS['developerEmail'] && !@$_REQUEST['developerEmail']) { alert("Please set 'Developer Email' under: Admin Menu > Email Settings"); }
  if ($isInvalidWebRoot)                                              { alert("Please set 'Website Root Directory' under: Admin > General Settings"); }
  if (!@$SETTINGS['adminUrl'])                                        { alert("Please set 'Program Url' under: Admin > General Settings"); }


  # Dispatch actions
  $action = getRequestedAction();
  admin_dispatchAction($action);

//
function admin_dispatchAction($action) {

  if (!empty($_REQUEST['saved'])) { notice(t('Settings have been saved.')); }

  if     ($action == 'general')       { showInterface('admin/general.php'); }
  elseif ($action == 'adminSave')     { admin_saveSettings('admin/general.php'); }

  if     ($action == 'security')       { showInterface('admin/security.php'); }
  elseif ($action == 'securitySave')   { admin_saveSettings('admin/security.php'); }

  if     ($action == 'vendor' || // support legacy link name
          $action == 'branding')     { showInterface('admin/branding.php'); }
  elseif ($action == 'brandingSave') { admin_saveSettings('admin/branding.php'); }

  elseif ($action == 'bgtasks')       { showInterface('admin/backgroundTasks.php'); }
  elseif ($action == 'bgtasksSave')   { admin_saveSettings('admin/backgroundTasks.php'); }

  elseif ($action == 'email')         { showInterface('admin/emailSettings.php'); }
  elseif ($action == 'emailSave')     { admin_saveSettings('admin/emailSettings.php'); }

  elseif ($action == 'db_show_variables')    { admin_db_show_variables();  }
  elseif ($action == 'db_show_status')       { admin_db_show_variables('STATUS');  }
  elseif ($action == 'phpinfo')              { admin_phpinfo();  }
  elseif ($action == 'ulimit')               { admin_ulimit(); }
  elseif ($action == 'du')                   { admin_diskuse(); }
  elseif (in_array($action, ['ver','systeminfo','releases'])) {
    disableInDemoMode('', 'admin/general.php');

    if ($action == 'ver')        { print "<h2>$action (windows only)</h2>\n";  print "<xmp>" .`$action`. "</xmp>"; }
    if ($action == 'systeminfo') { print "<h2>$action (windows only)</h2>\n";  print "<xmp>" .`$action`. "</xmp>"; }
    if ($action == 'releases')   {
      $command = 'grep "" /etc/*-release 2>&1';
      $output  = shellCommand($command, $exitCode, $functionUsed);
      if (is_null($output)) { $output = "Unable to execute command, no PHP shell functions available."; }
      print "<h2>$action (unix only)</h2>\n";  print "<xmp>$output</xmp>";
    }
    exit;
  }

  elseif ($action == 'updateDate')           { getAjaxDate(); }
  elseif ($action == 'getUploadPathPreview') { getUploadPathPreview(@$_REQUEST['dirOrUrl'], @$_REQUEST['inputValue'], @$_REQUEST['isCustomField'], true); }
  elseif ($action == 'getMediaPathPreview')  { getMediaPathPreview(@$_REQUEST['dirOrUrl'], @$_REQUEST['inputValue'], @$_REQUEST['isCustomField'], true); }
  elseif ($action == 'plugins')              { admin_plugins(); }
  elseif ($action == 'pluginHooks')          { showInterface('admin/pluginHooks.php'); }
  elseif ($action == 'deactivatePlugin')     { admin_deactivatePlugin(); }
  elseif ($action == 'activatePlugin')       { admin_activatePlugin(); }

  // backup/restore
  elseif ($action == 'backuprestore')        { showInterface('admin/backupAndRestore.php'); }
  elseif ($action == 'backup')               { admin_backup(); }
  elseif ($action == 'restore')              { admin_restore(); }
  elseif ($action == 'restoreComplete')      { admin_restoreComplete(); }
  elseif ($action == 'backupDownload')       { admin_backupDownload(); }

  //
  elseif ($action == 'bgtasksLogsClear')     { admin_bgtasksLogsClear(); }

  // default
  else                              { showInterface('admin/general.php');  }
}


//
function admin_saveSettings($savePagePath) {
  global $SETTINGS, $APP;

  // error checking
  clearAlertsAndNotices(); // so previous alerts won't prevent saving of admin options

  // security checks
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  //
  disableInDemoMode('settings', $savePagePath);

  # program url / adminUrl
  if (array_key_exists('adminUrl', $_REQUEST)) {
    if  (!preg_match('/^http/i', $_REQUEST['adminUrl'])) { alert("Program URL must start with http:// or https://<br>\n"); }
    if  (preg_match('/\?/', $_REQUEST['adminUrl'])) { alert("Program URL can not contain a ?<br>\n"); }
  }

  # webPrefixUrl - v2.53
  if (@$_REQUEST['webPrefixUrl'] != '') {
    if  (!preg_match("|^(\w+:/)?/|", $_REQUEST['webPrefixUrl'])) { alert(t("Website Prefix URL must start with /") ."<br>\n"); }
    if  (str_ends_with($_REQUEST['webPrefixUrl'], "/"))          { alert(t("Website Prefix URL cannot end with /")."<br>\n"); }
  }

  # upload url/dir
  if (array_key_exists('uploadDir', $_REQUEST)) {
#    if      (!preg_match('/\/$/',      $_REQUEST['uploadDir'])) { alert("Upload Directory must end with a slash! (eg: /www/htdocs/uploads/)<br>\n"); }
  }
  if (array_key_exists('uploadUrl', $_REQUEST)) {
#    if      (preg_match('/^\w+:\/\//', $_REQUEST['uploadUrl'])) { alert("Upload Folder Url must be the web path only without a domain (eg: /uploads/)<br>\n"); }
#    else if (!preg_match('/^\//',      $_REQUEST['uploadUrl'])) { alert("Upload Folder Url must start with a slash! (eg: /uploads/)<br>\n"); }
#    if      (!preg_match('/\/$/',      $_REQUEST['uploadUrl'])) { alert("Upload Folder Url must end with a slash! (eg: /uploads/)<br>\n"); }
    $_REQUEST['uploadUrl'] = chop($_REQUEST['uploadUrl'], '\\\/'); // remove trailing slashes
  }

  # admin email
  if (array_key_exists('adminEmail', $_REQUEST) && !isValidEmail($_REQUEST['adminEmail'])) {
    alert("Admin Email must be a valid email (example: user@example.com)<br>\n");
  }

  # developer email
  if (array_key_exists('developerEmail', $_REQUEST) && !isValidEmail($_REQUEST['developerEmail'])) {
    alert("Developer Email must be a valid email (example: user@example.com)<br>\n");
  }

  // error checking - require HTTPS
  if (@$_REQUEST['requireHTTPS'] && !isHTTPS()) {
    alert("Require HTTPS: You must be logged in with a secure HTTPS url to set this option!<br>\n");
  }

  // error checking - require HTTPS
  if (@$_REQUEST['restrictByIP'] && !isIpAllowed(true, @$_REQUEST['restrictByIP_allowed'])) {
    alert(t("Restrict IP Access: You current IP address must be in the allowed IP list!") . "<br>\n");
  }

  // error checking - session values
  $sessionErrors = getCustomSessionErrors(@$_REQUEST['session_cookie_domain'], @$_REQUEST['session_save_path']);
  if ($sessionErrors) { alert($sessionErrors); }



  # show errors
  if (alert()) {
    showInterface($savePagePath);
    exit;
  }


  ### update global settings
  $globalSettings =& $SETTINGS;
  foreach (array_keys($globalSettings) as $key) {
    if (array_key_exists($key, $_REQUEST)) { $globalSettings[$key] = $_REQUEST[$key]; }
  }

  # update subsection settings
  $subsections = array('advanced', 'wysiwyg', 'mysql');
  foreach ($subsections as $subsection) {
    $sectionSettings =& $SETTINGS[$subsection];
    foreach (array_keys($sectionSettings) as $key) {
      if (array_key_exists($key, $_REQUEST)) { $sectionSettings[$key] = $_REQUEST[$key]; }
    }
  }

  # save to file
  saveSettings();

  # return to admin home
  notice(t('Settings have been saved.'));
  showInterface($savePagePath);
  exit;
}


//
function getAjaxDate() {
  global $SETTINGS;

  // error checking
  if (!@$_REQUEST['timezone']) { die("no timezone value specified!"); }

  // error checking
  $timeZoneOffsetSeconds = abs((int) date("Z"));
  if ($timeZoneOffsetSeconds > 12*60*60) {
    $error     = "Offset cannot be more than +/- 12 hours from GMT!";
    echo json_encode(array('', '', $error));
    exit;
  }

  // set timezones
  date_default_timezone_set($_REQUEST['timezone']) || die(__FUNCTION__ . ": error setting timezone to '{$_REQUEST['timezone']}' with date_default_timezone_set.  Invalid timezone name.");
  $error = setMySqlTimezone('returnError');

  // get local date
  $offsetSeconds = date("Z");
  $offsetString  = convertSecondsToTimezoneOffset($offsetSeconds);
  $localDate = date("D, M j, Y - g:i:s A") . " ($offsetString)";

  // get mysql date
  $result = mysqli()->query("SELECT NOW(), @@session.time_zone");
  list($mySqlDate, $mySqlOffset) = $result->fetch_row();
  $mysqlDate = date("D, M j, Y - g:i:s A", strtotime($mySqlDate)) . " ($mySqlOffset)";
  if (is_resource($result)) { mysqli_free_result($result); }

  // return dates
  echo json_encode(array($localDate, $mysqlDate, $error));
  exit;
}

//
function admin_phpinfo() {
  disableInDemoMode('', 'admin/general.php');

  // table of contents
  $sections = ['phpinfo','get_loaded_extensions','apache_get_modules','get_defined_constants','mb_get_info'];

  //
  print "php executable: " .htmlencode(_getPhpExecutablePath()). "<br>\n";
  echo t('PHP is running as user') . ": ". htmlencode(get_current_user());
  if (function_exists('posix_getpwuid')) {
    $processUser = posix_getpwuid(posix_geteuid());
    print " (" .$processUser['name']. ")";
  }
  print "<br>\n";

  //
  print "<h2>Sections</h2>\n";
  foreach ($sections as $section) {
    print "<a href='#$section' style='background-color: transparent; color: #FFF; text-decoration: underline'>$section</a><br>\n";
  }

  // php info
  print "<h2 id='phpinfo'>phpinfo()</h2>\n";

  phpinfo();

  // get_loaded_extensions
  print "<h2 id='get_loaded_extensions'>get_loaded_extensions()</h2>\n";
  $sortedList = get_loaded_extensions();
  natcasesort($sortedList);
  print implode("<br>\n", $sortedList) . "\n";

  // apache_get_modules
  print "<h2 id='apache_get_modules'>apache_get_modules()</h2>\n";
  if (function_exists('apache_get_modules')) {
    $sortedList = apache_get_modules();
    natcasesort($sortedList);
    print implode("<br>\n", $sortedList) . "\n";
  }
  else { print "Not available<br>\n"; }

  // get_defined_constants
  print "<h2 id='get_defined_constants'>get_defined_constants()</h2>\n";
  print "<xmp>" . print_r(get_defined_constants(), true) . "</xmp>\n";

  // mb_get_info
  print "<h2 id='mb_get_info'>mb_get_info()</h2>\n";
  $mbInfo = mb_get_info();
  ksort($mbInfo);
  print "<xmp>" . print_r($mbInfo, true) . "</xmp>\n";

  //
  print "Done!";
  exit;
}

//
function admin_ulimit() {
  disableInDemoMode('', 'admin/general.php');

  print "<h2>Soft Resource Limits (ulimit -a -S)</h2>\n";
  list($maxCpuSeconds, $memoryLimitKbytes, $maxProcessLimit, $ulimitOutput) = getUlimitValues('soft');
  showme($ulimitOutput);

  print "<h2>Hard Resource Limits (ulimit -a -H)</h2>\n";
  list($maxCpuSeconds, $memoryLimitKbytes, $maxProcessLimit, $ulimitOutput) = getUlimitValues('soft');
  showme($ulimitOutput);
  exit;
}

//
function admin_diskuse() {
  disableInDemoMode('', 'admin/general.php');

  // End the current session and store session data so locked session data doesn't prevent concurrent access to CMS by user while action in progress
  session_write_close();

  // get disk space
  $totalBytes    = @disk_total_space(__DIR__);
  $freeBytes     = @disk_free_space(__DIR__);
  $diskSpaceText = t('Disk Space') . ": ";
  if ($totalBytes) { $diskSpaceText .= sprintf(t('Free: %1$s, Total: %2$s'), formatBytes($freeBytes), formatBytes($totalBytes)); }
  else             { $diskSpaceText .= t("Unavailable"); }

  // get largest folders
  $command = "du -hSx / | sort -hr | head -n 30"; // -hSa to show files as well, add 2>/dev/null to hide errors/warnings
  print "<h2 style='margin: 0'>Show Largest Dirs</h2>\n";
  print "$diskSpaceText<br/>\n";
  print "Shell Command: $command<br/>\n";
  print "Note: Not all directories may be visible, run the above command as root from the shell to see full file list.<br>";

  $output = shellCommand("$command 2>&1", $exitCode, $functionUsed);
  if (is_null($output)) { $output = "Unable to execute command, no PHP shell functions available."; }
  print "<hr><xmp>$output</xmp><hr>\n";
  print showExecuteSeconds(). " seconds";
  exit;
}

//
function admin_plugins() {
  // allow disabling plugins
  if (file_exists("{$GLOBALS['PROGRAM_DIR']}/plugins/_disable_all_plugins.txt")) {
    alert('Development Mode: Plugins are disabled.  Remove or rename /plugins/_disable_all_plugins.txt to enable.<br>');
  }
  if (file_exists("{$GLOBALS['PROGRAM_DIR']}/plugins/_disable_sys_plugins.txt")) {
    alert('Development Mode: "System Plugins" flag is being ignored.  Remove or rename /plugins/_disable_sys_plugins.txt to enable.<br>');
  }

  getPluginList(); // preload plugin list (cached in function) to generate alerts about auto activating plugins
  showInterface('admin/plugins.php');
}

//
function admin_deactivatePlugin() {
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  disableInDemoMode('plugins', 'admin/plugins.php');
  deactivatePlugin(@$_REQUEST['file']);
  redirectBrowserToURL('?menu=admin&action=plugins', true);
  exit;
}

//
function admin_activatePlugin() {
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  disableInDemoMode('plugins', 'admin/plugins.php');
  activatePlugin(@$_REQUEST['file']);
  redirectBrowserToURL('?menu=admin&action=plugins', true);
  exit;
}

//
function admin_backup() {
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  disableInDemoMode('','admin/backupAndRestore.php');
  $filename = backupDatabase(null, @$_REQUEST['backupTable']);
  notice(sprintf(t('Created backup file %1$s (%2$s seconds)'), $filename, showExecuteSeconds(true)));
  showInterface('admin/backupAndRestore.php');
  exit;
}

//
function admin_restore() {
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  disableInDemoMode('','admin/backupAndRestore.php');

  $restoreDatabaseFilePath = $GLOBALS['BACKUP_DIR'] . @$_REQUEST['file'];
  incrementalRestore( $restoreDatabaseFilePath );
  exit;
}

//
function admin_restoreComplete() {
  disableInDemoMode('','admin/backupAndRestore.php');

  //
  $basename = !empty($_REQUEST['file']) ? basename($_REQUEST['file']) : '';
  notice("Restored backup file ".htmlencode($basename));
  showInterface('admin/backupAndRestore.php');
}

//
function admin_backupDownload() {
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  disableInDemoMode('','admin/backupAndRestore.php');

  // security check
  $filename = @$_REQUEST['file'];
  $filepath = $GLOBALS['BACKUP_DIR'] . $filename;
  $error    = '';
  if     (empty($filename))                               { $error .= "No file specified!" . "\n"; }
  elseif (!in_array($filename, getBackupFiles_asArray())) { $error .= htmlencodef("Invalid backup file '?'!", $filename) . "\n"; }
  elseif (!file_exists($filepath))                        { $error .= htmlencodef("File doesn't exists '?'!", $filename) . "\n"; }
  if ($error) {
    alert($error);
    showInterface('admin/backupAndRestore.php');
    exit;
  }

  // download file
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' .addcslashes($filename, '"\\'). '"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
  header('Pragma: public');
  header('Content-Length: ' . filesize($filepath));
  readfile($filepath);
  exit;
}

//
function admin_bgtasksLogsClear() {
  security_dieUnlessPostForm();
  security_dieUnlessInternalReferer();
  security_dieOnInvalidCsrfToken();

  disableInDemoMode('','admin/general.php');
  mysql_delete('_cron_log', null, 'true');
  notice(t("Scheduled Task logs have been cleared."));
  showInterface('admin/backgroundTasks.php');
  exit;
}

//
function getTimeZoneOptions($selectedTimezone = '') {
  global $SETTINGS;

  // get timezone name to offset
  $tzNameToOffset = [];
  foreach (timezone_abbreviations_list() as $zoneName => $abbrZones) {
    foreach ($abbrZones as $abbrZoneArray) {
      $name   = $abbrZoneArray['timezone_id'];
      if($zoneName == 'gmt' && isset($tzNameToOffset[ $name ])) { continue; } //Do not process the GMT options as these already appear in other timezone lists
      $offset = convertSecondsToTimezoneOffset($abbrZoneArray['offset']);
      $tzNameToOffset[ $name ] = $offset;
    }
  }

  // sort from GMT-11:00 to GMT+14:00
  $tzKeyValuesArray = [];
  foreach ($tzNameToOffset as $tzName => $tzOffset) {  $tzKeyValuesArray[] = array($tzName,$tzOffset); }
  uasort($tzKeyValuesArray, '_sortTimeZones');

  $tzNameToOffset = [];
  foreach ($tzKeyValuesArray as $keyAndValue) {
    list($key, $value) = $keyAndValue;
    $tzNameToOffset[$key] = $value;
  }

  // get options
  $options = '';
  foreach ($tzNameToOffset as $tzName => $tzOffset) {
    if (!$tzName) { continue; }
    $isSelected    = $tzName == $selectedTimezone;
    $selectedAttr  = $isSelected ? 'selected="selected"' : '';
    $options      .= "<option value='$tzName' $selectedAttr>(GMT $tzOffset) $tzName</option>\n";
  }

  return $options;
}

// return timezones sorted from GMT-11:00 to GMT+14:00, and then by name
// usage: uasort($tzKeyValuesArray, '_sortTimeZones');
function _sortTimeZones($arrayA, $arrayB) {
  list($nameA, $offsetA) = $arrayA;
  list($nameB, $offsetB) = $arrayB;

  // sort by -/+ offset first
  $isNegativeA = str_starts_with($offsetA, '-'); // True if offsetA starts with '-'
  $isNegativeB = str_starts_with($offsetB, '-'); // True if offsetB starts with '-'
  $cmp = $isNegativeB <=> $isNegativeA; // compare boolean values
  if ($cmp !== 0) { return $cmp; }

  // sort by offset value next
  $cmp = strcmp($offsetA, $offsetB);
  if ($isNegativeA) { $cmp *= -1; }        // sort negative offsets in reverse
  if ($cmp != 0) { return $cmp; }

  // sort by name last
  return strcasecmp($nameA, $nameB);
}

// list($maxCpuSeconds, $memoryLimitMegs, $maxProcessLimit, $ulimitOutput) = getUlimitValues('soft');
// Future: See if we can use php function posix_getrlimit() (if it's defined)
function getUlimitValues($type = 'soft') {
  $maxCpuSeconds     = '';
  $memoryLimitKbytes = '';
  $maxProcessLimit   = '';
  $output            = '';

  // get shell command
  if     ($type == 'soft') { $cmd = 'sh -c "ulimit -a -S" 2>&1'; }
  elseif ($type == 'hard') { $cmd = 'sh -c "ulimit -a -H" 2>&1'; }
  else                     { die(__FUNCTION__ . ": type must be either hard or soft"); }

  // get output
  $output = shellCommand($cmd) ?? '';

  // parse output
  if (preg_match("/^(time|cpu time).*?\s(\S*)$/m", $output, $matches))                  { $maxCpuSeconds = $matches[2]; }
  if (preg_match("/^(data|data seg).*?\s(\S*)$/m", $output, $matches))                  { $dataSegLimit  = $matches[2]; }
  if (preg_match("/^(vmemory|virtual mem).*?\s(\S*)$/m", $output, $matches))            { $vmemoryLimit  = $matches[2]; }
  if (preg_match("/^(concurrency|max user processes).*?\s(\S*)$/m", $output, $matches)) { $maxProcessLimit  = $matches[2]; }

  $memoryLimitKbytes = max(@$vmemoryLimit, @$dataSegLimit);

  //
  return array($maxCpuSeconds, $memoryLimitKbytes, $maxProcessLimit, $output);
}

// get binary path to PHP executable, eg: /usr/bin/php
function _getPhpExecutablePath() {
  static $phpFilepath, $isCached;
  if (isset($isCached)) { return $phpFilepath; } // caching
  $isCached = true;

  $phpFilepath = 'php';

  // First, try PHP_BINARY if we're CLI.  This won't work for apache2handler SAPI which are set to httpd.exe or other values
  if     (PHP_BINARY && PHP_SAPI == 'cli') {
    $phpFilepath = PHP_BINARY;
  }
  elseif (PHP_BINARY && PHP_SAPI == 'fpm-fcgi' && preg_match("/php(\.exe)?$/i", PHP_BINARY)) {  // can't run scripts with php-fpm, whitelist allowed filenames to php and php.exe
    $phpFilepath = PHP_BINARY;
  }

  // next, check above PHP extension dir for valid PHP binaries - eg: c:/wamp/bin/php/php5.5.12/ext/, check for ../bin/php.exe
  else {
    $extensionDir     = ini_get('extension_dir');
    $phpPossiblePaths = ['/php','/php.exe', '/bin/php','/bin/php.exe'];
    foreach (range(1,10) as $counter) { // limit to X recursions

      // check for valid php binaries
      foreach ($phpPossiblePaths as $possiblePath) {
        $testPath = "$extensionDir/$possiblePath";
        //print "DEBUG: Test: $testPath " .is_file($testPath). " - " .is_executable($testPath). "<br>\n";
        if (@is_file($testPath) && @is_executable($testPath)) { // Use @ to catch open_basedir errors
          $phpFilepath = absPath($testPath);
          //print "DEBUG: MATCH!!! $phpFilepath<br>\n";
          break 2; // found valid binary
        }
      }

      // continue and check parent directory - unless we're already at the root
      $parentDir = dirname($extensionDir);
      if ($parentDir == $extensionDir) { break; }  // stop once we've checked the root folder
      $extensionDir = $parentDir;
    }
  }

  return $phpFilepath;
}


//
function admin_db_show_variables($status = false) {
  disableInDemoMode('', 'admin/general.php');

  $showWhat = $status ? 'STATUS' : 'VARIABLES';

  //
  print <<<__HTML__
<!DOCTYPE html>
<html><head>
<style>
body {background-color: #fff; color: #222; font-family: sans-serif;}
pre {margin: 0; font-family: monospace;}
a:link {color: #009; text-decoration: underline; background-color: #fff;}
a:hover {text-decoration: underline;}
table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px rgba(0, 0, 0, 0.2);}
.center {text-align: center;}
.center table {margin: 1em auto; text-align: left;}
.center th {text-align: center !important;}
td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
th {position: sticky; top: 0; background: inherit;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
h2 a:link, h2 a:visited{color: inherit; background: inherit;}
.p {text-align: left;}
.e {background-color: #ccf; width: 300px; font-weight: bold;}
.h {background-color: #99c; font-weight: bold;}
.v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
.v i {color: #999;}
img {float: right; border: 0;}
hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
:root {--php-dark-grey: #333; --php-dark-blue: #4F5B93; --php-medium-blue: #8892BF; --php-light-blue: #E2E4EF; --php-accent-purple: #793862}@media (prefers-color-scheme: dark) {
  body {background: var(--php-dark-grey); color: var(--php-light-blue)}
  .h td, td.e, th {border-color: #606A90}
  td {border-color: #505153}
  .e {background-color: #404A77}
  .h {background-color: var(--php-dark-blue)}
  .v {background-color: var(--php-dark-grey)}
  hr {background-color: #505153}
}
</style>
__HTML__;

  // table header
  print "<h2>";
  print "<a href='?menu=admin&action=general'>Admin</a> &gt; ";
  print "<a href='?menu=admin&action=general'>General Settings</a> &gt; ";
  print "<a href='?menu=admin&action=general#server-info'>Server Info</a> &gt; ";
  print "Database Server &gt; SHOW $showWhat</h2>\n";

  print "<table border='1' cellspacing='0' cellpadding='1'>\n";
  print "<tr class='h'><th>Variable_name</th><th>Value</th></tr>\n";


  // show records
  $records = mysql_select_query("SHOW $showWhat", true);
  foreach ($records as [$variable, $value]) {
    print "<tr>\n";
    print "  <td class='e'>" .htmlencode($variable). "</td>\n";
    print "  <td class='v'>" .htmlencode($value). "</td>\n";
    print "</tr>\n";
  }

  // table footer
  print "</table>\n";

}
