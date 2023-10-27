<?php

// Windows Defender issues a false positive for this shell_functions.php library so we split it into multiple files.
require_once(__DIR__ . "/shell_functions2.php");

/**
 * Attempts to execute a shell command using various PHP functions.
 *
 * This function checks availability of different PHP shell execution
 * functions and uses the first available method to execute the provided command.
 *
 * Important: If the command includes user-supplied input, ensure the input is properly escaped before
 * calling this function to prevent command injection attacks. Functions such as escapeshellarg() and
 * escapeshellcmd() can be used to make sure any user data is properly escaped before it is included
 * in a shell command.
 *
 * Note that if you want errors returned as well you need to add the '2>&1' shell directive
 * to the command for capturing standard error messages (works on Linux and Windows).
 *
 * If $functionUsed is null it means no method was found to execute the code
 *
 * @param string $command The command to be executed.
 * @param int|null $exitCode The exit status code. 0=Success, >0=Error, Null=Exit codes not available. Note that windows and linux return different codes on failure
 * @return string|null $functionUsed The PHP function used to execute the code, or null if none was available
 */
function shellCommand(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {

  // error checking
  if ($command === "")                  { dieAsCaller(__FUNCTION__. "() \$command argument cannot be empty!"); }
  if (str_contains($command, "\0")) { dieAsCaller(__FUNCTION__."() \$command argument cannot contain any null bytes!"); }

  // set defaults
  $output       = null;
  $exitCode     = null;
  $functionUsed = null;

  // try different methods
  $methods = ['exec','system','passthru','shell_exec','popen','proc_open'];
  foreach ($methods as $method) {
    if (!function_exists($method)) { continue; } // skip disabled methods
    $function = "_shellCommand_$method";
    $output   = $function($command, $exitCode, $functionUsed);
    break;
  }

  // return output;
  if (!is_null($output)) {
    $output = str_replace("\r\n", "\n", $output); // make windows output consistent
    $output = trim($output); // remove leading/trailing whitespace
  }
  return $output;
}

//
function _shellCommand_exec(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {
  $exitCode     = null;
  $functionUsed = null;
  $output       = null;
  if (function_exists('exec')) {
    $functionUsed = 'exec';
    $outputLines  = [];
    exec($command, $outputLines, $exitCode);
    $output       = implode("\n", $outputLines);
  }
  return $output;
}

//
function _shellCommand_system(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {
  $exitCode     = null;
  $functionUsed = null;
  $output       = null;
  if (function_exists('system')) {
    $functionUsed = 'system';
    ob_start();
    system($command, $exitCode);
    $output = ob_get_clean();
  }
  return $output;
}


//
function _shellCommand_passthru(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {
  $exitCode     = null;
  $functionUsed = null;
  $output       = null;
  if (function_exists('passthru')) {
    $functionUsed = 'passthru';
    ob_start();
    passthru($command, $exitCode);
    $output = ob_get_clean(); // Retrieve buffer contents
  }
  return $output;
}
