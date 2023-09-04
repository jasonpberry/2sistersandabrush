<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require the bundled autoload file - the path may need to change
// based on where you downloaded and unzipped the SDK
require __DIR__ . '/libs/src/Twilio/autoload.php';

// Your Account SID and Auth Token from console.twilio.com
$sid = "ACb0a1ce542b61353c4d890ff7f47d7f0a";
$token = "93ccb648f3daded5ac299b1995455aac";
$client = new Twilio\Rest\Client($sid, $token);

// Use the Client to make requests to the Twilio REST API
$message = $client->messages->create(
    // The number you'd like to send the message to
    '+14438072661',
    [
        // A Twilio phone number you purchased at https://console.twilio.com
        'from' => '++18339954202',
        // The body of the text message you'd like to send
        'body' => "how long from 12:34 does this take?",
    ]
);

echo "<pre>";
print_r($message->sid);
echo "</pre>";
