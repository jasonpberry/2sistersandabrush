<?php


/**
 * Send an email message with optional attachments.
 *
 * If both 'text' and 'html' options are specified, the email client will choose what to display.
 *
 * ```
 * // Minimal Example
 * $errors = sendMessage([
 *   'from'    => 'from@example.com',
 *   'to'      => 'to@example.com',
 *   'subject' => 'Enter subject here, supports utf-8 content',
 *   'text'    => 'Text message content'
 * ]);
 * if ($errors) { die($errors); }
 * ```
 *
 * ```
 * // Full Featured Example
 * $errors = sendMessage([
 *   'from'        => 'from@example.com',
 *   'to'          => 'to@example.com',
 *   'subject'     => 'Enter subject here, supports utf-8 content',
 *   'text'        => 'Text message content',
 *   'html'        => '<b>HTML</b> message content',
 *   'headers'     => [
 *     'CC'              => 'cc@example.com',
 *     'BCC'             => 'bcc@example.com',
 *     'Reply-To'        => 'rt@example.com',
 *     'Return-Path'     => 'rp@example.com',
 *     'x-custom-header' => '12345'
 *   ],
 *   'attachments' => [
 *     'simple.txt'  => 'A simple text file',
 *     'dynamic.csv' => $csvData,
 *     'archive.zip' => $binaryData,
 *     'image.jpg'   => file_get_contents($imagePath)
 *   ],
 *   //'disabled' => false, // Set to true to disable sending of message
 *   //'logging'  => false  // Set to false to disable logging (if logging is already enabled)
 * ]);
 * if ($errors) {
 *   die($errors);
 * }
 * ```
 *
 * @param array $options Associative array of email options
 *
 * @return string|null Error message if the email could not be sent, null otherwise
 */
function sendMessage(array $options = []): string {

    // Plugins: Allow plugins to cancel send and return error
    $eventState = ['cancelEvent' => false, 'returnValue' => null];
    $eventState = applyFilters('sendMessage', $eventState, $options);
    if ($eventState['cancelEvent']) {
        return $eventState['returnValue'];
    }

    // Plugins: Allow plugins to modify options
    $options = applyFilters('sendMessage_options', $options);

    // don't send if 'disabled' option set
    if (!empty($options['disabled'])) {
        return '';
    }

    // Error checking for $options
    $errorHTML = getMessageErrors($options);
    if ($errorHTML) {
        return $errorHTML;
    }

    // if logging enabled?
    $mode             = $GLOBALS['SETTINGS']['advanced']['outgoingMail']; // 'sendOnly', 'logOnly', 'sendAndLog'
    $isLoggingEnabled = match ($mode) {
        'sendOnly'   => false,
        'sendAndLog' => $options['logging'] ?? true, // true unless explicitly disabled with logging => false option
        'logOnly'    => $options['logging'] ?? true, // true unless explicitly disabled with logging => false option
        default      => throw new InvalidArgumentException("Invalid mode: $mode"),
    };

    // log message
    if ($isLoggingEnabled) {
        logMessage($options, false);
    }

    // send message
    if ($mode !== 'logOnly') {
        $errors = _sendMessage_phpMailer($options);
        return $errors;
    }

    return "";
}

// Alternate to sendMessage(), puts message in _outgoing_mail with 'backgroundSend' set to 1.
// Usage: $mailErrors = sendBackground($options);
// if ($mailErrors) { throw new Exception($mailErrors); }
// NOTE: Doesn't support headers or attachments yet and 'logging' setting is ignored.
// NOTE: Requires a background-mailer script that is running on a cron-job
// NOTE: For large volumes of email do a mail-merge via MySQL "INSERT FROM ... SELECT ... JOIN" to avoid timeouts
function sendBackground($options = []): string {
    // don't send if 'disabled' option set
    if (!empty($options['disabled'])) {
        return '';
    }

    // Error checking for $options
    $errorHTML = getMessageErrors($options);
    if ($errorHTML) {
        return $errorHTML;
    }

    // log message
    logMessage($options, true);
    return '';
}

/**
 * Get HTML encoded errors for sendMessage() and sendBackground() function parameters.
 *
 * @param array $options Array of options including 'from', 'to', 'subject', 'text', 'html', and 'headers'.
 *
 * @return string|null A string containing all the errors in the input options, blank if no errors.
 */
function getMessageErrors(array $options): string {
    $errors = '';

    // Validate required fields - returns first error
    $headers      = $options['headers'] ?? [];
    $hasTextOrHTML = array_key_exists('text', $options) || array_key_exists('html', $options);
    $errors .= match (true) {
        !array_key_exists('subject', $options)                                   => "'subject' must be defined!\n",
        !array_key_exists('from', $options)                                      => "'from' must be defined!\n",
        !isValidEmail($options['from'] ?? '')                                    => "'from' isn't a valid email!\n",
        !array_key_exists('to', $options)                                        => "'to' must be defined!\n",
        !isValidEmail($options['to'] ?? '', true)                                => "'to' isn't a valid email!\n",
        !$hasTextOrHTML                                                          => "Either 'text' or 'html' or both must be defined!\n",
        !empty($headers['CC']) && !isValidEmail($headers['CC'], true)             => "'CC' isn't a valid email!\n",
        !empty($headers['BCC']) && !isValidEmail($headers['BCC'], true)           => "'BCC' isn't a valid email!\n",
        !empty($headers['Reply-To']) && !isValidEmail($headers['Reply-To'])       => "'Reply-To' isn't a valid email!\n",
        !empty($headers['Return-Path']) && !isValidEmail($headers['Return-Path']) => "'Return-Path' isn't a valid email!\n",
        default                                                                  => ''
    };

    $errors = rtrim($errors, "\n");
    $htmlErrors = nl2br($errors);
    return $htmlErrors;
}

/**
 * Log an email message's details into a database table '_outgoing_mail'.
 *
 * @param array $options Associative array of email options
 *
 * @return bool True if logging was successful, false otherwise.
 */
function logMessage(array $options, bool $backgroundSend = false): void {

    // If 'logging' option is set to false, skip logging
    if (isset($options['logging']) && !$options['logging']) {
        return;
    }

    // Extract relevant email options and headers
    $headers         = $options['headers'] ?? [];
    $headersAsString = '';
    foreach ($headers as $name => $value) {
        $headersAsString .= "$name: $value\n";
    }

    // Build the data to insert into the '_outgoing_mail' table
    $mode         = $GLOBALS['SETTINGS']['advanced']['outgoingMail']; // 'sendOnly', 'logOnly', 'sendAndLog'
    $colsToValues = [
        'createdDate='   => 'NOW()',
        'sent='          => ($mode === 'logOnly') ? "''" : 'NOW()', // only log sent date if message was actually sent
        'from'           => $options['from'] ?? '',
        'to'             => $options['to'] ?? '',
        'subject'        => $options['subject'] ?? '',
        'text'           => $options['text'] ?? '',
        'html'           => $options['html'] ?? '',
        'reply-to'       => $headers['Reply-To'] ?? '',
        'cc'             => $headers['CC'] ?? '',
        'bcc'            => $headers['BCC'] ?? '',
        'headers'        => $headersAsString,
        'backgroundSend' => $backgroundSend ? '1' : '0',
    ];

    // Log the email details to the database
    $recordNum = mysql_insert('_outgoing_mail', $colsToValues, true);
    if (!$recordNum) { throw new RuntimeException("Couldn't log message!"); }

}

function _sendMessage_phpMailer($options): string {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'quoted-printable';
    $errors = '';

    try {
        // From - only allow one email address with false arg below
        foreach (isValidEmail($options['from'], false) as [$email, $name]) {
            $mail->setFrom($email, $name);
        }


        // To - allow multiple emails with true arg below
        foreach (isValidEmail($options['to'], true) as  [$email, $name]) {
            $mail->addAddress($email, $name);
        }

        // CC
        foreach (isValidEmail($options['headers']['CC'] ?? '', true) as [$email, $name]) {
            $mail->addCC($email, $name);
        }

        // BCC
        foreach (isValidEmail($options['headers']['BCC'] ?? '', true) as [$email, $name]) {
            $mail->addBCC($email, $name);
        }

        // Reply-to
        foreach (isValidEmail($options['headers']['Reply-To'] ?? '', false) as [$email, $name]) {
            $mail->addReplyTo($email, $name);
        }

        // Return-Path - Make sure we get bounces
        foreach (isValidEmail($options['headers']['Return-Path'] ?? $options['from'], false) as $emailNameArray) {
            $mail->Sender = $emailNameArray[0];
        }

        // Subject
        $mail->Subject = $options['subject'];

        // Message body
        if (isset($options['html'])) {
            $mail->isHTML(true);
            $mail->Body = $options['html'];
            if (isset($options['text'])) {
                $mail->AltBody = $options['text'];
            }
        }

        // If only text specified, send text message
        elseif (isset($options['text'])) {
            $mail->isHTML(false);
            $mail->Body = $options['text'];
        }

        // Attachments
        if (!empty($options['attachments'])) {
            foreach ($options['attachments'] as $filename => $filedata) {
                $mail->addStringAttachment($filedata, $filename);
            }
        }

        // Custom X-Headers - We only add X-headers, as this is the valid prefix for a custom header: https://tools.ietf.org/html/rfc822
        if (!empty($options['headers'])) {
            foreach ($options['headers'] as $headerName => $headerLabel) {
                if (startsWith('x-', strtolower($headerName))) {
                    $mail->addCustomHeader($headerName, $headerLabel);
                }
            }
        }

        // Define Send Method - defaults to PHP mail()
        $method = $GLOBALS['SETTINGS']['advanced']['smtp_method']; // 'php', 'unsecured', 'ssl', 'tls' - last 3 require SMTP settings
        if ($method === 'php') {
            $mail->isMail();  // Set mailer to use PHP mail() function
        }
        elseif (in_array($method, ['unsecured','ssl','tls'])) {
            $mail->isSMTP(); // Set mailer to use SMTP

            // set hostname
            $userDefinedHost = $GLOBALS['SETTINGS']['advanced']['smtp_hostname'];
            $mail->Host      = $userDefinedHost ?: get_cfg_var('SMTP');

            // set port
            $userDefinedPort = $GLOBALS['SETTINGS']['advanced']['smtp_port'] ?? null;
            $mail->Port      = match (true) {
                !empty($userDefinedPort) => $userDefinedPort,
                ($method === 'ssl')      => '587',
                ($method === 'tls')      => '465',
                get_cfg_var('smtp_port') => get_cfg_var('smtp_port'),
                default                  => '25',
            };

            // Set authentication options
            $mail->Username = $GLOBALS['SETTINGS']['advanced']['smtp_username'];
            $mail->Password = $GLOBALS['SETTINGS']['advanced']['smtp_password'];
            $mail->SMTPAuth = true;

            if ($method === 'unsecured') {
                $mail->SMTPAutoTLS = false;  // Disables TLS when server offers it
                $mail->SMTPSecure  = '';     // Set to empty string
            } elseif ($method === 'ssl') {
                $mail->SMTPSecure = 'ssl';
            } elseif ($method === 'tls') {
                $mail->SMTPSecure = 'tls';
            } else {
                throw new InvalidArgumentException("Invalid SMTP method: $method");
            }

        }
        else {
            throw new InvalidArgumentException("Invalid mail method: $method");
        }

        // Send message
        $mail->Timeout = 4;    // max time to wait for SMTP operations to complete
        $mail->XMailer = null; // remove X-Mailer header, e.g. X-Mailer: PHPMailer 6.8.1 (https://github.com/PHPMailer/PHPMailer)
//        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION; // Enables SMTP debug information (for level info, check PHPMailer docs)
//        $mail->Debugoutput = static function($str, $level) {
//            // Here you could write the debug output to a log file
//            file_put_contents("phpmailer.log", "$level: $str\n", FILE_APPEND);
//        };
        $mail->send();
    }
    catch (Throwable $e) {
        $errors = "Message could not be sent: {$e->getMessage()}";
        if (isset($mail->ErrorInfo) && $mail->ErrorInfo !== $e->getMessage()) { // output PHPMailer error info (if any)
            $errors .= "\nDetailed Error: $mail->ErrorInfo";
        }
//        if ($_SERVER['REMOTE_ADDR'] == 'x.x.x.x') {
//            showme($e);
//            showme($mail);
//        }
    }



    return $errors;
}



/*
  $emailHeaders = emailTemplate_loadFromDB(array(
    'template_id'        => 'USER-ACTION-NOTIFICATION',
    'placeholders'       => $placeholders,
  ));
 $mailErrors = sendMessage($emailHeaders);
 if ($mailErrors) { alert("Mail Error: $mailErrors"); }

  // optional options - you can also add these if needed
  'template_table'     => '_email_templates', // or leave blank for default: _email_templates
  'addHeaderAndFooter' => true,               // (default is true) set to false to disable automatic adding of HTML header and footer to email
*/
// v2.16 added 'logging' as a pass-thru field.
// v2.50 template_table now defaults to _email_templates
// v2.50 ID with language code is checked first if language set, eg: CMS-PASSWORD-RESET-EN then CMS-PASSWORD-RESET
function emailTemplate_loadFromDB($options) {

  // set defaults
  if (!@$options['template_table']) { $options['template_table'] = '_email_templates'; } // v2.50

  // error checking
  if (!$options['template_id']) { dieAsCaller(__FUNCTION__.": No 'template_id' set in options"); }
  if (!$options['placeholders']) { dieAsCaller(__FUNCTION__.": No 'placeholders' set in options"); }

  // load template
  $template = [];
  if (!$template) { // try and load custom translated TEMPLATE-ID with language suffix first, eg: MY-TEMPLATE-FR
    $template = mysql_get($options['template_table'], null, array('template_id' => $options['template_id'] .'-'. strtoupper($GLOBALS['SETTINGS']['language'])));
  }
  if (!$template) { // if not found, try loading default template next
    $template = mysql_get($options['template_table'], null, array('template_id' => $options['template_id']));
  }
  if (!$template) { // if not found, re-add default templates and try again unless template wasn't added or was removed
    emailTemplate_addDefaults();
    $template = mysql_get($options['template_table'], null, array('template_id' => $options['template_id']));
  }
  if (!$template) { // otherwise, die with error
    dieAsCaller(__FUNCTION__.": Couldn't find email template_id '" .htmlencode($options['template_id']). "'");
  }

  // get email values
  $emailHeaders = [];
  $emailHeaders['from']     = @$options['override-from'] ?: $template['from'];
  $emailHeaders['to']       = @$options['override-to']   ?: $template['to'];

  if ($template['reply-to'] || @$options['override-reply-to']) {
    $emailHeaders['headers']['Reply-To'] = @$options['override-reply-to'] ?: $template['reply-to'];
  }
  if ($template['cc'] || @$options['override-cc']) {
    $emailHeaders['headers']['CC'] = @$options['override-cc'] ?: $template['cc'];
  }
  if ($template['bcc'] || @$options['override-bcc']) {
    $emailHeaders['headers']['BCC'] = @$options['override-bcc'] ?: $template['bcc'];
  }

  $emailHeaders['subject']  = @$options['override-subject']  ?: $template['subject'];
  $emailHeaders['disabled'] = @$options['override-disabled'] ?: @$template['disabled'];
  $emailHeaders['html']     = @$options['override-html']     ?: $template['html'];
  $passThruFields = array('attachments','headers','logging');
  foreach ($passThruFields as $field) {
    if (!array_key_exists($field, $options)) { continue; }
    $emailHeaders[$field] = $options[$field];
  }

  // replace placeholders
  [$emailHeaders, $textPlaceholderList] = emailTemplate_replacePlaceholders($emailHeaders, @$options['placeholders']);

  // update template placeholder list
  if ($template['placeholders'] != $textPlaceholderList) {
    mysql_update($options['template_table'], $template['num'], null, array('placeholders' => $textPlaceholderList));
  }

  // error checking
  if (!$emailHeaders['from'])    { die(__FUNCTION__ . ": No 'From' set by program or email template id '" .htmlencode($options['template_id']). "'"); }
  if (!$emailHeaders['to'])      { die(__FUNCTION__ . ": No 'To' set by program or email template id '" .htmlencode($options['template_id']). "'"); }
  if (!$emailHeaders['subject']) { die(__FUNCTION__ . ": No 'Subject' set by program or email template id '" .htmlencode($options['template_id']). "'"); }
  if (!$emailHeaders['html'])    { die(__FUNCTION__ . ": No 'Message HTML' found by program or email template id '" .htmlencode($options['template_id']). "'"); }

  // add html header/footer
  if (@$options['addHeaderAndFooter'] !== false) { // added in 2.50
    $htmlTitle  = htmlencode($emailHeaders['subject']);
    $header = <<<__HTML__
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>$htmlTitle</title>
</head>
<body>

<style>
  p { margin-bottom: 1em; }
</style>


__HTML__;
// ***NOTE*** style tag is for Yahoo Mail which otherwise drops paragraph spacing - http://www.email-standards.org/blog/entry/yahoo-drops-paragraph-spacing/
// ... having a defined <title></title> helps get by spam filters

    $footer = <<<__HTML__
</body>
</html>
__HTML__;
  $emailHeaders['html'] = $header . $emailHeaders['html'] . $footer;
 }

  //
  return $emailHeaders;
}

// replace placeholders on specific email template fields
// Usage: list($emailHeaders, $textPlaceholderList) = emailTemplate_replacePlaceholders($emailHeaders, $placeholders);
function emailTemplate_replacePlaceholders($emailHeaders, $customPlaceholders): array
{
  $customPlaceholders = $customPlaceholders ?: [];
  $fieldnames = array('from','reply-to','to','cc','bcc','subject','html','headers');  // email header fields to replace placeholders in

  // set default placeholders (always available)
  $defaultPlaceholders = [];
  $defaultPlaceholders['server.http_host']         = $_SERVER['HTTP_HOST'] ?? null;
  $defaultPlaceholders['server.remote_addr']       = $_SERVER['REMOTE_ADDR'] ?? null;
  $defaultPlaceholders['settings.adminEmail']      = $GLOBALS['SETTINGS']['adminEmail'] ?? null;
  $defaultPlaceholders['settings.adminUrl']        = $GLOBALS['SETTINGS']['adminUrl'] ?? null;
  $defaultPlaceholders['settings.developerEmail']  = $GLOBALS['SETTINGS']['developerEmail'] ?? null;
  $defaultPlaceholders['settings.programName']     = $GLOBALS['SETTINGS']['programName'] ?? null;

  // create text and html placeholders
  $textPlaceholders = $customPlaceholders + $defaultPlaceholders;
  $htmlPlaceholders = $customPlaceholders + array_map('nl2br', array_map('htmlencode', $defaultPlaceholders));

  // replace placeholders
  $searchPlaceholders = [];
  foreach (array_keys($textPlaceholders) as $key) { $searchPlaceholders[] = "#$key#"; }
  $replacementText    = array_values($textPlaceholders);
  $replacementHtml    = array_values($htmlPlaceholders);
  foreach ($fieldnames as $fieldname) {
    if (!array_key_exists($fieldname, $emailHeaders)) { continue; }
    if ($fieldname === 'html') { $emailHeaders[$fieldname] = str_replace($searchPlaceholders, $replacementHtml, $emailHeaders[$fieldname]); }
    else                       { $emailHeaders[$fieldname] = str_replace($searchPlaceholders, $replacementText, $emailHeaders[$fieldname]); }
  }

  // update text placeholder list
  $textPlaceholderList = '';
  foreach (array_keys($customPlaceholders)  as $placeholder) { $textPlaceholderList .= "#$placeholder#\n"; }
  foreach (array_keys($defaultPlaceholders) as $placeholder) { $textPlaceholderList .= "\n#$placeholder#"; }

  //
  return array($emailHeaders, $textPlaceholderList);
}

// Add a new template into the _email_templates table if template_id doesn't already exist
/* Usage:

  emailTemplate_addToDB(array(
    'template_id'  => 'EMAIL-TEMPLATE-NAME',
    'description'  => 'Description of what email template is used for',
    'from'         => '#settings.adminEmail#',
    'to'           => '#user.email#',
    'subject'      => '#server.http_host# Application Alert',
    'html'         => "<b>Hello World!</b>",
    'placeholders' => array('user.email', 'user.fullname'), // array of placeholder names
  ));

  // Note: Placeholders can also be text

*/
function emailTemplate_addToDB($record) {
  if (!$record['template_id']) { dieAsCaller(__FUNCTION__.": No 'template_id' set in options"); }

  // check if template id exists
  $templateExists = mysql_count('_email_templates', array('template_id' => $record['template_id']));
  if ($templateExists) { return false; }

  // get placeholder text
  $placeholderText = '';
  if (is_array($record['placeholders'])) {
    if ($record['placeholders']) { // if array isn't empty
      // hijack emailTemplate_replacePlaceholders() get us a placeholder list (including server placeholders) from placeholder array
      $placeholderText = array_value(emailTemplate_replacePlaceholders([], array_combine($record['placeholders'], $record['placeholders'])), 1);
    }
  }
  else {
    $placeholderText = $record['placeholders'];
  }

  // add template
  $colsToValues = array(
    'createdDate='     => 'NOW()',
    'createdByUserNum' => '0',
    'updatedDate='     => 'NOW()',
    'updatedByUserNum' => '0',
    'template_id'      => $record['template_id'],
    'description'      => $record['description'],
    'from'             => $record['from'],
    'reply-to'         => @$record['from'],
    'to'               => $record['to'],
    'cc'               => @$record['cc'],
    'bcc'              => @$record['bcc'],
    'subject'          => $record['subject'],
    'html'             => $record['html'],
    'placeholders'     => $placeholderText,
  );
  mysql_insert('_email_templates', $colsToValues, true);

  // set notice
  //if ($showNotice) {
  //  notice(t("Adding email template:"). htmlencode($colsToValues['template_id']). "<br>\n");
  //}
  return true;
}


// Add CMS email templates -AND- add email templates used by plugins as well
// Note: Email templates are only created if they don't already exist.
// Note: This function is called on the email templates (list menu), and when sending an email
// ... and the template-id so the templates will be created just before you need them. (easier to implement that then checkin in CMS/Plugin install/upgrade/etc)
function emailTemplate_addDefaults() {

  ### Add Plugin Templates
  doAction('emailTemplate_addDefaults');

  ### NOTE: Make sure this file (admin_functions.php) is saved as UTF-8 or chars with accents may not get saved to MySQL on insert

  ### Note: If you need to update a template that already exists, have your upgrade code either:
  ###         - backup the existing template as "template-id (backup-YYYYMMDD-HHMMSS)
  ###         - or create/overwrite a new template as "template-id (ORIGINAL)"

  // debug - output current templates
  //showme(mysql_select('_email_templates')); exit;

  // CMS-PASSWORD-RESET
  emailTemplate_addToDB(array(
    'template_id'  => "CMS-PASSWORD-RESET",
    'description'  => "Sent to users when they request to reset their password",
    'placeholders' => array('user.num','user.email','user.username','user.fullname','resetUrl'), // v3.06 - added username and fullname
    'from'         => "#settings.adminEmail#",
    'to'           => "#user.email#",
    'subject'      => "#settings.programName# Password Reset",
    'html'         => <<<__HTML__
<p>Hi #user.email#,</p>
<p>You requested a password reset for #settings.programName#.</p>
<p>To reset your password click this link:<br><a href="#resetUrl#">#resetUrl#</a></p>
<p>This request was made from IP address: #server.remote_addr#</p>
__HTML__
  ));


  // CMS-PASSWORD-RESET-FR
  emailTemplate_addToDB(array(
    'template_id'  => "CMS-PASSWORD-RESET-FR",
    'description'  => "Sent to users when they request to reset their password (French)",
    'placeholders' => array('user.num','user.email','user.username','user.fullname','resetUrl'), // v3.06 - added username and fullname
    'from'         => "#settings.adminEmail#",
    'to'           => "#user.email#",
    'subject'      => "#settings.programName# Réinitialisation de votre mot de passe",
    'html'         => <<<__HTML__
<p>Bonjour #user.email#,</p>
<p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
<p>Pour réinitialiser votre mot de passe cliquez sur le lien ci-dessous:<br><a href="#resetUrl#">#resetUrl#</a></p>
<p></p>
<p>Cette demande a été faite à partir de l'adresse d'IP : #server.remote_addr#</p>
<p>Ne soyez pas inquiet si vous n'êtes pas à l'origine de cette demande, ces informations sont envoyées uniquement à votre adresse e-mail.</p>
<p>L'administrateur</p>
<p>#settings.programName#</p>
__HTML__
  ));

  // CMS-BGTASK-ERROR
  emailTemplate_addToDB(array(
    'template_id'  => "CMS-BGTASK-ERROR",
    'description'  => "Sent to admin when a scheduled task fails",
    'placeholders' => array('bgtask.date','bgtask.activity','bgtask.summary','bgtask.completed','bgtask.function','bgtask.output','bgtask.runtime','bgtask.settingsUrl','bgtask.logsUrl'), // array of placeholder names
    'from'         => "#settings.adminEmail#",
    'to'           => "#settings.developerEmail#",
    'subject'      => "Scheduled tasks did not complete",
    'html'         => <<<__HTML__
<p>The following Scheduled Task did not complete successfully: </p>
<table border="0">
<tbody>
<tr><td>Date/Time</td><td> : </td><td>#bgtask.date#</td></tr>
<tr><td>Activity</td><td> : </td><td>#bgtask.activity#</td></tr>
<tr><td>Summary</td><td> : </td><td>#bgtask.summary#</td></tr>
<tr><td>Completed</td><td> : </td><td>#bgtask.completed#</td></tr>
<tr><td>Function</td><td> : </td><td>#bgtask.function#</td></tr>
<tr><td>Output</td><td> : </td><td>#bgtask.output#</td></tr>
<tr><td>Runtime</td><td> : </td><td>#bgtask.runtime# seconds</td></tr>
</tbody>
</table>
<p>Please check the Scheduled Tasks logs here and check for additional errors:<br>#bgtasks.logsUrl#</p>
<p>You can review the Scheduled Tasks status &amp; settings here: <br>#bgtasks.settingsUrl#</p>
<p>*Please note, incomplete scheduled task alerts are only sent once an hour.</p>
__HTML__
  ));

  // CMS-ERRORLOG-ALERT
  emailTemplate_addToDB(array(
    'template_id'  => "CMS-ERRORLOG-ALERT",
    'description'  => "Sent to admin when a php error or warning is reported",
    'placeholders' => array('error.hostname','error.latestErrorsList','error.errorLogUrl'), // array of placeholder names
    'from'         => "#settings.adminEmail#",
    'reply-to'     => "#settings.adminEmail#",
    'to'           => "#settings.developerEmail#",
    'cc'           => "",
    'bcc'          => "",
    'subject'      => "Errors reported on: #error.hostname#",
    'html'         => <<<__HTML__
<p>One or more php errors have been reported on:<strong> #error.hostname# (#error.servername#)</strong></p>
<p>Check the error log for complete list and more details:<br><a href="#error.errorLogUrl#">#error.errorLogUrl#</a></p>
<p>Latest errors: </p>
<p style="padding-left: 30px;"><span style="color: #808080;">#error.latestErrorsList#</span></p>
<p><strong>*Note: Email notifications of new errors are only sent once an hour.</strong></p>
__HTML__
  ));
}


/**
 * Validates email addresses and returns an array of their components.
 *
 * The function can validate single or multiple email addresses. If provided with multiple addresses,
 * they must be separated by a comma (,) or semicolon (;).
 *
 * Valid formats for single email:
 * - user@example.com
 * - Display Name <user@example.com>
 * - "Display Name" <user@example.com>
 *
 * For multiple emails, use the same formats but separate the email addresses with , or ;.
 *
 * @param mixed $input The email or emails to validate. It can be a string containing one or multiple emails.
 * @param bool  $allowMultiple Whether multiple emails are allowed. Default is false.
 *
 * @return array If validation succeeds, returns an array of arrays, each containing:
 *               0 => The email address
 *               1 => The display name (if any)
 *               2 => The full matched string
 *               If validation fails or if an invalid input type is provided, returns an empty array [].
 *
 *@example
 * ```
 * $isValid = isValidEmail("user@example.com");
 * $isValid = isValidEmail("user@example.com, user2@example.com", true);
 * $emailParts = isValidEmail("user@example.com");
 * ```
 *
 * @version 3.62 - Now returns an empty array [] instead of false or "invalid input" for invalid types.
 *
 */
// Return an array of valid email components on success or array on (any) error

// valid single emails: user@example.com
// valid single emails: "Display Name" <user@example.com>
// valid multiple emails: same as above but separated by , or ;
// v3.62 - Function now returns [] instead of false or "invalid input" (for wrong var type).
function isValidEmail(mixed $input, bool $allowMultiple = false) {

    // Email formats described here: http://en.wikipedia.org/wiki/Email_address
    // John Smith <john.smith@example.org>.
    // "Display Name" <local-part@domain>

    // v3.16 error checking: return "invalid input" on arrays|objects input instead of calling dieAsCaller()
    // Developers often pass _REQUEST values to this function and security scanners often pass arrays values
    // ?name[]=test, which results in errors and false positives.  So instead of returning
    // an error we'll return a common string to avoid errors and false positives caused by scanners
    if (is_array($input) || is_object($input)) { return []; }
    $input = is_string($input) ? $input : (string) $input;

    // parse out emails
    $emails = [];
    $validUsernameRegexp = '(?:[\w\-]+[\w\-\.\+\'])*[\w\-\']+';
    $validHostnameRegexp = '@[A-Za-z0-9][A-Za-z0-9\-]*(?:\.[A-Za-z0-9][A-Za-z0-9\-]*)*\.[A-Za-z]{2,}';
    $validEmailRegexp    = $validUsernameRegexp . $validHostnameRegexp;
    while (preg_match("/\A()($validEmailRegexp)/", $input, $matches) ||
           preg_match("/\A([^<]*) <($validEmailRegexp)>/", $input, $matches)) {
        [$matchedString, $displayName, $email] = $matches; // matchedString can contain separator (eg: , or ;)
        $input = substr_replace($input, '', 0, strlen($matchedString)); // remove matched content
        $displayName = trim($displayName, '"'); // remove double quotes
        $emails[] = array($email, $displayName, $matchedString);

        // Remove separator
        $input = preg_replace("/\A\s*[,;]\s*/", '', $input, 1, $count);
        if (!$count) { // exit loop if no separator
            break;
        }
    }

    // check for errors
    if (!$emails)                              { return []; } // No valid emails or empty string is invalid
    if ($input !== '')                         { return []; } // remaining content means input has invalid email
    if (!$allowMultiple && count($emails) > 1) { return []; } // multiple emails in string but only one supported

    //
    return $emails;
}
