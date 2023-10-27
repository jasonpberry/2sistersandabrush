<?php

// update server info once an hour (or next time admin script is accessed)
register_shutdown_function('updateServerChangeLog');

// update serverInfo in SETTINGS if it hasn't been updated in >= 1 hour
// We also force this update every time: Admin > General > Server Info is displayed
function updateServerChangeLog($forceUpdate = false): void
{
  global $SETTINGS;

  // Check if we need to do an update/check
  $doUpdate     = false;
  $lastChecked  = $SETTINGS['serverChangeLog_lastCheck'] ?? '';
  $oneHourAgo   = time() - (60*60);
  if     ($forceUpdate)               { $doUpdate = true; }
  elseif (!$lastChecked)              { $doUpdate = true; }
  elseif ($lastChecked < $oneHourAgo) { $doUpdate = true; }
  if (!$doUpdate) { return; }

  // get latest server info
  $latestServerInfo = [
    'Content Delivery Network'  => serverInfo_contentDeliveryNetwork()[0],
    'Operating System'          => serverInfo_operatingSystem()[0],
    'Server Name'               => serverInfo_serverName()[0],
    'Server IP'                 => serverInfo_serverAddr()[0],
    'Web Server'                => serverInfo_webServer()[0],
    'Control Panel'             => serverInfo_webServer_controlPanel()[0],
    'Database Server'           => serverInfo_databaseServer()[0],
    'Database Details'          => serverInfo_databaseConnection()[0],
    'PHP Version'               => 'v'. phpversion(),
    'PHP User'                  => serverInfo_phpUser()[0],
    'CMS Version'               => "v{$GLOBALS['APP']['version']} (Build: {$GLOBALS['APP']['build']})",
  ];

  // get last logged value for each component
  $lastLoggedValues = [];
  if (!isset($SETTINGS['serverChangeLog'])) { $SETTINGS['serverChangeLog'] = '[]'; }
  $serverChangeLog = json_decode($SETTINGS['serverChangeLog'], true) ?? []; // default to empty array on error
  usort($serverChangeLog, function ($a, $b) { return $b[0] <=> $a[0]; }); // sort by date - newest to oldest
  foreach ($serverChangeLog as [$logTime, $component, $value]) {
    if (array_key_exists($component, $lastLoggedValues)) { continue; }
    $lastLoggedValues[$component] = $value;
  }

  // Add log entry if current value doesn't match last logged value
  foreach (array_reverse($latestServerInfo, true) as $component => $value) { // reverse so component order is listed the same as above on first entry instead of reversed due to array_unshift
    if (isset($lastLoggedValues[$component]) && $lastLoggedValues[$component] === $value) { continue; }
    $newEntry = [ time(), $component, $value ];
    array_unshift($serverChangeLog, $newEntry);
  }

  // Sort entries and remove entries exceeding 100, but saving at least the two latest entry for each component type
  usort($serverChangeLog, function ($a, $b) { return $b[0] <=> $a[0]; }); // sort by date - newest to oldest

  // For each component, if there are less than 2 entries, add them to $requiredEntries array
  $requiredEntries = [];
  $componentCounts = array_count_values(array_column($serverChangeLog, 1));  // Get counts of entries for each component
  foreach($componentCounts as $component => $count) {
    if ($count < 2) {
      $componentEntries = array_filter($serverChangeLog, function ($entry) use ($component) {
        return $entry[1] === $component;
      });
      $requiredEntries = array_merge($requiredEntries, $componentEntries);
    }
  }

  // remove required entries from serverChangeLog
  $serverChangeLog = array_udiff($serverChangeLog, $requiredEntries, function ($a, $b) {
    return $a[0] - $b[0] ?: strcmp($a[1], $b[1]);
  });

  // Trim serverChangeLog to 100 - count($requiredEntries) entries and add the required entries
  $serverChangeLog = array_slice($serverChangeLog, 0, 100 - count($requiredEntries));
  $serverChangeLog = array_merge($serverChangeLog, $requiredEntries);

  // update settings
  $json = json_encode($serverChangeLog, JSON_UNESCAPED_SLASHES );
  $json = str_replace("],[", "],\n    [", $json); // show data on multiple lines for when we're viewing settings file directly
  $json = str_replace("[[", "[\n    [", $json);
  $SETTINGS['serverChangeLog'] = $json;
  $SETTINGS['serverChangeLog_lastCheck'] = time();
  saveSettings();
}

// [$name, $moreInfo] = serverInfo_contentDeliveryNetwork();
function serverInfo_contentDeliveryNetwork(): array
{
  $name     = "";
  $moreInfo = ""; // show as title="" hover text

  // get Content Delivery Network Name
  $CDN_LOOP_keywordToVendor = [       // detect with HTTP_CDN_LOOP
    'cloudflare' => 'Cloudflare',     // eg: HTTP_CDN_LOOP: cloudflare
    'StackCDN'   => 'StackPath',      // eg: HTTP_CDN_LOOP: StackCDN
    'fastly'     => 'Fastly',         // eg: HTTP_CDN_LOOP: fastly
    'akamai'     => 'Akamai',         // eg: HTTP_CDN_LOOP: akamai
    'aws'        => 'AWS CloudFront', // eg: HTTP_CDN_LOOP: aws
  ];
  foreach ($CDN_LOOP_keywordToVendor as $keyword => $vendor) {
    if (!isset($_SERVER['HTTP_CDN_LOOP'])) { break; } // skip if not defined
    if (stripos($_SERVER['HTTP_CDN_LOOP'], $keyword) === false) { continue; } // skip if no match
    $name     = $vendor;
    $moreInfo = "Reported by: \$_SERVER['HTTP_CDN_LOOP']";
    break; // Exit the loop once a match is found
  }

  // detect with custom vars
  if (!$name) {
    if (isset($_SERVER['HTTP_X-AMZ-CF-ID'])) {
      $name     = "AWS CloudFront";
      $moreInfo = "Reported by: \$_SERVER['HTTP_X-AMZ-CF-ID']";
    }
  }

  return [$name, $moreInfo];
}


// get operating system
// [$name, $moreInfo] = serverInfo_operatingSystem();
function serverInfo_operatingSystem(): array
{
    ### get os name
    $server  = @php_uname('s');  // eg: Windows NT, Linux, FreeBSD
    $release = @php_uname('r');   // eg: 5.1, 2.6.18-164.11.1.el5, 5.1.2-RELEASE.
    $version = @php_uname('v');   // eg: build 22621 (Windows 11), #23~22.04.1-Ubuntu SMP Fri Mar 17 15:38:24 UTC 2023
    $machine = @php_uname('m');   // eg: i386, x86_64
    $hostname = @php_uname('n');  // eg: localhost.example.com.

    $moreInfo = "Server: $server\n";
    $moreInfo .= "Release: $release\n";
    $moreInfo .= "Version: $version\n";
    $moreInfo .= "Machine: $machine\n";
    $moreInfo .= "Hostname: $hostname\n";

    if (isWindows()) {
        $command        = 'powershell "Get-WmiObject -Query \\"Select * from Win32_OperatingSystem\\" | foreach{ $_.Caption }"';
        $windowsEdition = shellCommand($command) ?? '';
        $name           = $windowsEdition ?: "$server $release";
    } elseif (isMac()) {
        $name = "$server $release";
        $sw_vers = shellCommand('sw_vers') ?? '';
        // ProductName: Mac OS X
        // ProductVersion: 10.12.6
        // BuildVersion: 16G1815
        if (preg_match("/.*?:\s*(.*?)\n.*?:\s*(.*?)\n.*?:\s*(.*?)\n/", $sw_vers, $matches)) {
            [,$productName, $productVersion, $buildVersion] = $matches;
            $name = "$productName $productVersion (Build $buildVersion)";
        }

    } else { // linux, etc
        $releaseData = trim(uber_file_get_contents("/etc/system-release") ?: '');
        $name        = $releaseData ?: "$server $release";

        $proc_version = trim(uber_file_get_contents("/proc/version") ?: '');
        if ($proc_version) { $moreInfo .= "/proc/version: $proc_version\n"; }
    }

    return [$name, $moreInfo];
}


// [$name, $moreInfo] = serverInfo_serverName();
function serverInfo_serverName(): array
{
  // server name
  $getHostName    = gethostname();
  $hostname_sh    = shellCommand('hostname') ?? '';
  $uname_n_php    = php_uname('n');

  //
  $name = $getHostName;
  $moreInfo  = "gethostname() : $getHostName\n";
  $moreInfo .= "`hostname` : $hostname_sh\n";
  $moreInfo .= "uname('n') : $uname_n_php";
  if (!isWindows()) {
    $uname_n_shell = shellCommand('uname -n') ?? '';
    $moreInfo .= "\n`uname -n` : $uname_n_shell";
  }

  return [$name, $moreInfo];
}

// [$name, $moreInfo] = serverInfo_serverAddr();
function serverInfo_serverAddr(): array
{
  $SERVER_ADDR = $_SERVER['SERVER_ADDR'] ?? '';

  // display details
  $name      = $SERVER_ADDR;
  $moreInfo  = "\$_SERVER['SERVER_ADDR'] : $SERVER_ADDR";

  // server ip
  if (isWindows()) {
    $ipconfig_ipv4 = shellCommand('ipconfig | findstr /R /C:"IPv4 Address') ?? '';
    $ipconfig_ipv6 = shellCommand('ipconfig | findstr /R /C:"IPv6 Address') ?? '';
    // netsh is also an option for windows

    $moreInfo .= "\n`ipconfig` : $ipconfig_ipv4";
    $moreInfo .= "\n`ipconfig` : $ipconfig_ipv6";
  }
  else { // non-windows
    $hostname_I = shellCommand('hostname -I') ?? '';
    if (str_contains($hostname_I, " ")) {                            // spaces separate multiple IPs
      $list = array_unique(array_filter(explode(" ", $hostname_I))); // sort multiple IP addresses separated by spaces
      sort($list);
      $hostname_I = "\n". join("\n", $list);
    }

    $moreInfo .= "\n`hostname -I` : $hostname_I";
  }

  //
  return [$name, $moreInfo];
}


// [$name, $moreInfo] = serverInfo_webServer();
function serverInfo_webServer(): array
{
  ### get web server name
  $name     = $_SERVER['SERVER_SOFTWARE'] ?? '';
  $moreInfo = "\$_SERVER['SERVER_SOFTWARE'] : $name\n";
  if (function_exists('apache_get_version')) {
    $moreInfo .= "apache_get_version() : " .apache_get_version(). "\n";
  }

  // get LiteSpeed version
  if (PHP_SAPI == 'litespeed' || $name == "LiteSpeed") {
    $version = trim(uber_file_get_contents("/usr/local/lsws/VERSION") ?: '');
    if ($version) {
      $name .= " v$version";
      $moreInfo .= "/usr/local/lsws/VERSION: $version\n";
    }
  }

  // PHP_SAPI
  $moreInfo .= "PHP_SAPI: " .PHP_SAPI. "\n";

  return [$name, $moreInfo];
}


// [$name, $moreInfo, $links] = serverInfo_webServer_controlPanel();
function serverInfo_webServer_controlPanel(): array
{
  $name     = "";
  $moreInfo = ""; // show as title="" hover text
  $links    = "";

  // Detect WampServer - use known path difference from PHP_BINARY constant to config file
  $wampServerConfigPath = realpath(dirname(PHP_BINARY) . "/../../../../wampmanager.conf"); // eg: C:\wamp64\bin\apache\apache2.4.27\bin\httpd.exe to C:\wamp64\wampmanager.conf
  if ($wampServerConfigPath) {
    $wampConfig = parse_ini_file($wampServerConfigPath, true);
    $name  .= "WampServer v" .$wampConfig['main']['wampserverVersion'];
    $links .= "(<a href='https://www.wampserver.com/en/'>vendor</a>, <a href='https://wampserver.aviatechno.net/?lang=en'>updates</a>)";
  }

  // Detect Plesk
  $pleskVersionData  = uber_file_get_contents("/usr/local/psa/version");
  if ($pleskVersionData) {
    $pleskVersionShort = $pleskVersionData; // eg: 17.8.11 CentOS 7 1708180920.15
    $pleskVersionShort = preg_replace("/ .*$/", "", $pleskVersionShort); // remove first space and everything after (leaving just version)
    $pleskVersionLong  = trim("/usr/sbin/plesk version: $pleskVersionData");
    $name  .= trim("Plesk v$pleskVersionShort");
    $links .= "(<u title='" .htmlencode($pleskVersionLong). "'>details</u>, <a href='https://" .urlencode($_SERVER['HTTP_HOST']). ":8443/'>login</a>)";
  }

  ### Detect cPanel
  // Possible alternative methods if needed for future: /usr/local/cpanel/cpanel -V // 76.0 (build 18)

  // check for cpanel dirs
  $cpanelDirFound = false;
  $cpanelDirs = [
    "/etc/cpanel",
    "/usr/local/cpanel",
    "/var/cpanel",
    "{$GLOBALS['SETTINGS']['webRootDir']}/../.cpanel",
  ];

  foreach ($cpanelDirs as $dir) {
    if (!@is_dir($dir)) { continue; } // suppress errors when directory is blocked by open_basedir restrictions
    $cpanelDirFound = true;
    break;
  }

  // try and load cpanel version file
  $cpanelVersion      = "";
  $cpanelVersionData  = uber_file_get_contents("/usr/local/cpanel/version");
  if ($cpanelVersionData) {
    [,$major,$minor,$build] = explode('.', $cpanelVersionData); // eg: 11.76.0.18 // $parent,$major,$minor,$build.  Parent isn't relevant anymore
    $cpanelVersion = "$major.$minor.$build";
  }

  //
  if ($cpanelDirFound || $cpanelVersion) {
    $name = "cPanel";
    if ($cpanelVersion) { $name .= " v$cpanelVersion"; }
    $links .= "(<a href='https://" .urlencode($_SERVER['HTTP_HOST']). ":2083/'>cPanel login</a>, <a href='https://" .urlencode($_SERVER['HTTP_HOST']). ":2087/'>WHM login</a>)";
  }

  return [$name, $moreInfo, $links];
}



// [$name, $moreInfo] = serverInfo_databaseServer();
function serverInfo_databaseServer(): array
{

  // get mysql vars that might indicate database server software and version
  $query = "SELECT @@version_comment, @@version, @@basedir, @@datadir";
  [$version_comment, $version, $basedir, $datadir] = mysql_get_query($query, true);
  $commentAndVersion = "$version_comment v$version";

  // get server name
  $isAmazonRDS = startsWith("/rdsdbbin/mysql", $basedir) || startsWith("/rdsdbdata/db/",  $datadir);
  if     (preg_match("/aurora/i",       $commentAndVersion))            { $databaseServer = "Amazon RDS (Aurora)"; }  // untested - remove when we confirm a match
  elseif ($isAmazonRDS && preg_match("/mariadb/i", $commentAndVersion)) { $databaseServer = "Amazon RDS (MariaDB)"; } // untested - remove when we confirm a match
  elseif ($isAmazonRDS)                                                 { $databaseServer = "Amazon RDS (MySQL)"; }
  elseif (preg_match("/percona/i",      $commentAndVersion))            { $databaseServer = "Percona"; }              // untested - remove when we confirm a match
  elseif (preg_match("/tencent/i",      $commentAndVersion))            { $databaseServer = "Tencent"; }              // untested - remove when we confirm a match
  elseif (preg_match("/mariadb/i",      $commentAndVersion))            { $databaseServer = "MariaDB"; }
  else                                                                  { $databaseServer = "MySQL"; }

  // get database version
  $versionNumeric = preg_replace("/[^0-9\.].*/", "", $version); // remove first non-numeric char to end of string.  eg: 10.5.8-MariaDB to 10.5.8
  $name           = "$databaseServer v$versionNumeric";

  //
  $moreInfo = $commentAndVersion; // get name and version as one string
  return [$name, $moreInfo];
}


// returns dbHostname, dbUsername, dbDatabase, dbTablePrefix - eg: "localhost | cmsb | mydb | cmsb_', 'cmsb_'"
// [$name, $moreInfo] = serverInfo_databaseConnection();
function serverInfo_databaseConnection(): array
{

  $name = implode(" | ", [$GLOBALS['SETTINGS']['mysql']['hostname'],
                          $GLOBALS['SETTINGS']['mysql']['username'],
                          $GLOBALS['SETTINGS']['mysql']['username'],
                          $GLOBALS['SETTINGS']['mysql']['tablePrefix']]);
  $moreInfo = ""; // show as title="" hover text

  //
  return [$name, $moreInfo];
}


// [$name, $moreInfo] = serverInfo_phpUser();
function serverInfo_phpUser(): array
{
  ### Running as user - get user PHP is running as
  if (isWindows()) {
    $whoami           = strtoupper(shellCommand('whoami') ?? '');
    $get_current_user = get_current_user(); // returns user executing script on windows and owner of script "file" everywhere else
    $hoverText        = "get_current_user: $get_current_user\nwhoami: $whoami";
    $user             = $get_current_user ?: $whoami ?: 'Unknown';
    //$group            = "";
  }
  else { // Linux, etc
    $whoami             = shellCommand('whoami') ?? '';
    $id                 = shellCommand('id') ?? '';
    $id_userName        = preg_match_get("/uid=.*?\((.*?)\)/", $id)[1]    ?? '';                           // match name inside groups=1234(groupname)
    //$id_groupName       = preg_match_get("/groups=.*?\((.*?)\)/", $id)[1] ?? '';                           // match name inside groups=1234(groupname)
    $posix_getpwuid     = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())         : []; // posix functions aren't available on windows or all platforms
    $posix_userName     = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : ''; // posix functions aren't available on windows or all platforms
    $posix_groupName    = function_exists('posix_getgrgid') ? posix_getgrgid(posix_getegid())['name'] : ''; // posix functions aren't available on windows or all platforms

    $posix_getpwuidText = "";
    foreach ($posix_getpwuid as $key => $value) { $posix_getpwuidText .= "$key=$value, "; }
    $posix_getpwuidText = trim($posix_getpwuidText);

    $hoverText          = "whoami: $whoami\nid: $id\nposix_getpwuid: $posix_getpwuidText\nposix_getgrgid: $posix_groupName";
    $user               = $posix_userName ?: $whoami ?: $id_userName ?: "Unknown";
    //$group              = $posix_groupName ?: $id_groupName ?: 'Unknown';
  }

  //
  $name = $user;
  $moreInfo = $hoverText;
  return [$name, $moreInfo];
}
