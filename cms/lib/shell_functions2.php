<?php

// Windows Defender issues a false positive for this shell_functions.php library so we split it into multiple files.

//
function _shellCommand_shell_exec(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {
  $exitCode     = null;
  $functionUsed = null;
  $output       = null;
  if (function_exists('shell_exec')) {
    $functionUsed = 'shell_exec';
    $output = shell_exec($command);
  }
  return $output;
}

//
function _shellCommand_popen(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {
  $exitCode     = null;
  $functionUsed = null;
  $output       = null;
  if (function_exists('popen')) {
    $functionUsed = 'popen';
    $handle = popen($command, "r");
    if ($handle !== false) {
      $output = '';
      while (!feof($handle)) { $output .= fgets($handle); }
      $exitCode = pclose($handle);
    }
  }
  return $output;
}

//
function _shellCommand_proc_open(string $command, ?int &$exitCode = null, ?string &$functionUsed = null): ?string {
  $exitCode     = null;
  $functionUsed = null;
  $output       = null;
  if (function_exists('proc_open')) {
    $functionUsed = 'proc_open';
    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
       1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
       2 => array("pipe", "w")   // stderr is a pipe that the child will write to
    );
    $process = proc_open($command, $descriptorspec, $pipes);
    if (is_resource($process)) {
      fclose($pipes[0]);
      $output = stream_get_contents($pipes[1]);
      //$stderr = stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      $exitCode = proc_close($process);
    }
  }
  return $output;
}
