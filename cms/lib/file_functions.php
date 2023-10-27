<?php

// Return a normalized absolute filepath.  Resolves ./ and ../ references, removes double slashes, replace backslash with forward slash.
// Supports non-existent paths (unlike realpath()), C: style windows drive letters and \\UNC paths.
// Usage: $absPath = absPath($inputPath, $optionalBaseDir);  // returned path will not have a trailing slash
// Usage: $absPath = absPath('myfile.jpg');                     // get path relative to path of current directory (often script's directory, but not always in CLI)
// Usage: $absPath = absPath('myfile.jpg', __DIR__);  // get path relative to path of current php script
// Usage: $absPath = absPath('myfile.jpg', SCRIPT_DIR);         // get path relative to path of cms directory
function absPath($inputPath, $baseDir = '.') {

  // add basedir to inputPath if it's not absolute
  if (!isAbsPath($inputPath)) {
    if (!isAbsPath($baseDir)) { // make basedir absolute if it's not already
      $cwd = getcwd();
      if (!isAbsPath($cwd)) { die("getcwd() didn't return an absolute path '" .htmlencode($cwd). "'!"); }
      $baseDir = absPath($baseDir, $cwd);
    }
    $inputPath = "$baseDir/$inputPath";
  }

  // remove path prefixes: \\UNC-SERVER or C:
  $uncServerPrefix   = '';
  $driveLetterPrefix = '';
  $uncServerRegexp   = "|^\\\\\\\\[^\\\/]+|";   // matches \\SERVER-NAME UNC style prefixes
  $driveLetterRegexp = "|^[a-z]:(?=[\\\/])|i";  // matches W: windows drive letter prefixes
  if (preg_match($uncServerRegexp, $inputPath, $matches)) { // match prefix
    $uncServerPrefix = $matches[0];
    $inputPath       = preg_replace($uncServerRegexp, '', $inputPath, 1); // remove prefix
  }
  elseif (preg_match($driveLetterRegexp, $inputPath, $matches)) { // match prefix
    $driveLetterPrefix = $matches[0];
    $inputPath         = preg_replace($driveLetterRegexp, '', $inputPath, 1); // remove prefix
  }

  // normalize path components (replace backslashes, remove double-slashes, resolve . and ..)
  $inputPathComponents  = preg_split("|[\\\/]|" , $inputPath, -1, PREG_SPLIT_NO_EMPTY);
  $outputPathComponents = [];
  foreach ($inputPathComponents as $component) {
    if     ($component == '.')  { /* do nothing */ }                  // skip current dir references
    elseif ($component == '..') { array_pop($outputPathComponents); } // remove last path component for .. parent dir references
    else                        {$outputPathComponents[] = $component; }
  }
  $outputPath = implode('/', $outputPathComponents);

  // re-add path prefixes and root slash
  $absPath = $uncServerPrefix . $driveLetterPrefix . '/' . $outputPath;

  //
  return $absPath;
}

/**
 * Determines whether the provided path is absolute or not.
 *
 * @param string $path The path to be checked.
 *
 * @return bool Returns true if the path is absolute, i.e., if it begins with a leading slash/backslash (for Unix or
 *              Windows-style paths), a Windows drive letter followed by a colon and a slash/backslash, or a UNC server
 *              path. Returns false if the path is relative.
 *
 * @example isAbsPath('/leadingSlash') => true
 * @example isAbsPath('\\leadingBackslash') => true
 * @example isAbsPath('\\\\UNC-SERVERS') => true
 * @example isAbsPath('W:\\windows\\drives') => true
 * @example isAbsPath('relative/path') => false
 */
// Returns true on /leadingSlash, \leadingBackslash, \\UNC-SERVERS, and W:\windows\drives
function isAbsPath(string $path): bool
{
    if (preg_match('|^[\\\/]|', $path)) { // leading slash or backslash (for windows)
        return true;
    }
    if (preg_match('|^[a-zA-Z]:[\\\/]|', $path)) { // windows drive
        return true;
    }
    if (preg_match('|^\\\\[a-zA-Z]|', $path)) { // unc path
        return true;
    }
    return false;
}


//
function mkdir_recursive($dir, $mode = null)
{
    if (is_dir($dir)) {
        return true;
    }
    if (is_null($mode)) {
        $mode = octdec($GLOBALS['SETTINGS']['advanced']['permissions_dirs'] ?? '0755');
    }

    // create parent dir (if needed)
    $parentDir = dirname($dir);
    if ($parentDir && $parentDir != $dir) {
        mkdir_recursive($parentDir, $mode);
    }

    // create dir
    return @mkdir($dir, $mode);
}

// atomic file writing (where supported), dies on errors
// Note: Dies on permission errors, disk full, and file-size mismatches
//   ... and uses rename for atomic updates (mostly, not always on NFS or Windows PHP < 5.29)
function file_put_contents_atomic($filepath, $content): void {

  // save file to temp filename - this lets us check the write succeeded (fails on disk full, lack of write permissions, etc)
  $tempFilepath = $filepath .uniqid('.', true). '-temp.php';
  $bytesWritten = @file_put_contents($tempFilepath, $content);
  $dataLength   = strlen($content);
  if ($bytesWritten === false) { // Later: There is excess error checking here, we can simplify this if these aren't reported by Aug 2013
    dieAsCaller("Error writing $filepath: " . errorlog_lastError());
  }
  if ($bytesWritten !== $dataLength) {
    unlink($tempFilepath); // if error remove temp file
    dieAsCaller("Error writing $filepath: Only $bytesWritten out of $dataLength bytes written!");
  }
  if ($dataLength !== filesize($tempFilepath)) {
    unlink($tempFilepath); // if error remove temp file
    dieAsCaller("Error writing $filepath: $bytesWritten were written but temp file is " .filesize($tempFilepath). " bytes!");
  }

  // rename temp file over actual file
  if (!@rename($tempFilepath, $filepath)) {
    unlink($tempFilepath); // if error remove temp file
    dieAsCaller(__FUNCTION__. ": Error renaming over $filepath: " .errorlog_lastError());
  }
}

//
function isPathWritable($path) {

  if (!file_exists($path)) { return false; }

  else if (is_dir($path)) { // try and create test file
    $randomFilename = uniqid("temp_", true) . ".tmp";
    $randomFilepath = "$path/$randomFilename";

    $fh = @fopen($randomFilepath, 'x');
    if (!$fh) { return false; }
    fclose($fh);
    $erased = @unlink($randomFilepath);

    if (!$erased && file_exists($randomFilepath)) {
      $message  = "<b>Error:</b> There was a '<b>" .errorlog_lastError(). "</b>' error when trying to erase this temporary test file:<br><br>\n<i>$randomFilepath</i><br><br>\n";
      $message .= "This application needs permission to 'create' and 'remove' files on your server.<br><br>\n";
      $message .= "Please ask your server administrator to give erase permission (called 'modify' permission on Windows) to the above folder for the web server user account.<br><br>\n";
      die("$message");
    }
  }

  else if (is_file($path)) { // try and append to file
    $fh = @fopen($path, 'r+');
    if (!$fh) { return false; }
    else      { fclose($fh); }
  }

  else {
    die(__FUNCTION__ . ": Path isn't a file or a directory! '". htmlencode($path) ."'");
  }

  return true;
}

// Make path writable with minimal permissions possible, returns true on success or false on failure
// Note: Can't change permissions on Windows
function makeWritable($filepath) {

  // windows
  if (isWindows()) {
    return isPathWritable($filepath);
  }

  // linux, etc
  else {
    if (is_writable($filepath)) { return true; }

    foreach (array(0755,0775,0777) as $mode) {
      @chmod($filepath, $mode);
      if (is_writable($filepath)) { return true; }
    }

    return false;
  }
}


// save php data structure to a file
function saveStruct($filepath, $struct, $sortKeys = false, $headerMessage = ''): void
{ // $headerMessage added in 2.18
  if (!$filepath)         { dieAsCaller(__FUNCTION__ . ": no filepath specified!"); }
  if (!is_array($struct)) { dieAsCaller(__FUNCTION__ . ": structure isn't array!"); } // to debug add: . print_r($struct, true)

  // sort keys
  if ($sortKeys) { ksort($struct); }

  // create struct file data
  $header  = '<?php /* This is a PHP data file */ if (empty($LOADSTRUCT)) { die("This is not a program file."); }'; // DO NOT EDIT THIS - isStructFile() uses it to identify struct files
  if ($headerMessage != '') { $header .= "\n/*\n$headerMessage\n*/"; }
  $phpData = var_export($struct, true);
  $phpData = preg_replace("/=>\s*array\s*/", "=> array", $phpData); // reformat PHP so arrays start on the same line as keys (easier to read)
  $content = $header ."\nreturn $phpData;\n";

  // save file
  file_put_contents_atomic($filepath, $content);

  // opcache - invalidate old versions so opcache.file_update_protection won't prevent updated PHP file from being loaded
  if (function_exists('opcache_invalidate')) {

    // future: check if opcache.restrict_api is set (so we don't get errors)
    // NOTE: This code is untested and intended for future use if we need to further refine how opcache invalidation works
    //$opcache_api_access   = true;
    //if (ini_get('opcache.restrict_api') && $_SERVER['SCRIPT_FILENAME'] &&                 // if both values set
    //    mb_strpos(ini_get('opcache.restrict_api'), $_SERVER['SCRIPT_FILENAME']) !== 0) {  // and script_filename doesn't start with the path the opcache API is restricted to
    //  $opcache_api_access = false;
    //}
    //if (!$opcache_api_access) { /* invalidate here */ }

    @opcache_invalidate($filepath, true); // @hide warnings: Rackspace Cloud returns: E_WARNING: Zend OPcache API is restricted by "restrict_api" configuration directive.
  }

}

// load php data structure from a file
function loadStruct($filepath) {
  if (!$filepath)              { die(__FUNCTION__ . ": no filepath specified!"); }
  if (!file_exists($filepath)) { die(__FUNCTION__ . ": Couldn't find file '$filepath'"); } // error checking

  // opcache - invalidate cached versions of file so we get the latest version
  if (function_exists('opcache_invalidate')) { @opcache_invalidate($filepath, false); }

  // load file
  $LOADSTRUCT = true; // prevent struct data files from showing "Not a program file" warnings
  $struct     = include($filepath);

  //
  return $struct;
}

// loads data files that are in INI or struct format.  Loaded INI files are resaved as structs.
// now it coverts old files to the new format and calls loadStruct()
function loadStructOrINI($filepath) {

  // convert old files
  if (!isStructFile($filepath)) { saveStruct($filepath, loadINI($filepath)); }

  // load as struct file
  return loadStruct($filepath);
}

// check if a file is in the php "struct" format (otherwise assumes it's in the legacy INI file format)
function isStructFile($filepath): bool
{
  $isStruct       = false;
  $structHeader   = '<?php /* This is a PHP data file */ if ('; // This should match saveStruct() and shouldn't change (or we won't detect previous struct files
  $fileHandle     = @fopen($filepath, 'r');
  if ($fileHandle) {
    $fileHeader = @fread($fileHandle, strlen($structHeader));
    $isStruct   = ($fileHeader == $structHeader);
    fclose($fileHandle);
  }
  return $isStruct;
}


// depth of 0 gets just files in current directory
// $files = scandir_recursive($dir, '/\.php$/', 2);
// foreach ($files as $index => $file) { $myfiles[$index] = str_replace("$dir/", '', $file); } // remove basedir
function scandir_recursive($dir, $regexp = '', $depth = 99) {
  $matchingFiles = [];

  if ($dirHandle = @opendir($dir)) {
    while (($file = readdir($dirHandle)) !== false) {
      if ($file == '.' || $file == '..') { continue; }
      $filepath = "$dir/$file";

      if (is_file($filepath)) {   // skip symbolic links, etc
        if ($regexp && !preg_match($regexp, $filepath)) { continue; }
        $matchingFiles[] = $filepath;
      }
      elseif ($depth && is_dir($filepath)) {
        $newFiles      = scandir_recursive($filepath, $regexp, $depth - 1);
        $matchingFiles = array_merge($matchingFiles, $newFiles);
        continue;
      }
    }
    closedir($dirHandle);
  }

  return $matchingFiles;
}

// ensure a (temp) file gets unlinked after the current script execution, whether or not the script dies
// $tmpFile = tempnam($workingDir, 'temp_');
// unlink_on_shutdown($tmpFile);
function unlink_on_shutdown($filepath) {
  register_shutdown_function(function($filepath) {
    if (file_exists($filepath)) { @unlink($filepath); }
  }, $filepath);
}


/**
 * Function to get file contents while overcoming open_basedir restrictions.
 * Initially, it attempts to use file_get_contents(). If that fails due to open_basedir restrictions,
 * the function then resorts to executing shell commands specific to the server's OS.
 *
 * @param string $filepath The path to the file.
 * @return string|false File content if the file is successfully read, FALSE if not.
 */
function uber_file_get_contents(string $filepath): string|false {

  // try file_get_contents() first
  $r = @file_get_contents($filepath);
  if ($r !== FALSE) { return $r; }

  // if no open basedir restrictions, return value from file_get_contents
  $is_open_basedir_restrictions = ini_get('open_basedir') && !@file_exists("/"); // if root doesn't exist then open_basedir in effect.  In the future, we can check if target filepath matches open_basedir
  if (!$is_open_basedir_restrictions) { return $r; }

  // Fail here if shell commands aren't available
  if (!function_exists('escapeshellarg')) {
      return false;
  }

  // otherwise, try Windows shell commands
  if (isWindows()) {
    $filepath = str_replace("/", "\\", $filepath); // use windows slashes
    $command  = 'type ' .escapeshellarg($filepath);
    $response = shellCommand($command); // for debugging add: 2>&1
    return $response;
  }

  // else, try linux commands
  $command  = 'cat ' .escapeshellarg($filepath);
  $response = shellCommand("$command"); // for debugging add: 2>&1
  return $response;
}
