<?php

//
function auditLog_addEntry( $event, $additionalData = [], string $source = '' ) {

  global $SETTINGS;

  // check if audit log is enabled
  if (!$SETTINGS['advanced']['auditLog_enabled']) { return; }

  // make sure additional data is JSON encoded, or at least a string
  if (is_array( $additionalData ))   { $additionalData = json_encode( $additionalData ); }
  if (!is_string( $additionalData )) { $additionalData = serialize( $additionalData ); }

  $colsToValues = [
      'dateLogged='      => 'NOW()',
      'audit_event'      => $event,
      'event_source'     => $source,
      'additional_data'  => $additionalData,
      'url'              => thisPageUrl(),
      'user_cms'         => _errorlog_getCurrentUserSummaryFor('cms'),
      'user_web'         => _errorlog_getCurrentUserSummaryFor('web'),
      'http_user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '', // $_SERVER['HTTP_USER_AGENT'],
      'remote_addr'      => $_SERVER['REMOTE_ADDR'] ?? '',
  ];

  mysql_insert('_log_audit', $colsToValues);

}
