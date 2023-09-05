<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require the bundled autoload file - the path may need to change
// based on where you downloaded and unzipped the SDK
require __DIR__ . '/libs/src/Twilio/autoload.php';
require __DIR__ . '/libs/src/Twilio/.env.php';

$client = new Twilio\Rest\Client($sid, $token);

// Use the Client to make requests to the Twilio REST API
$message = $client->messages->create(
    // The number you'd like to send the message to
    '+14438072661',
    [
        // A Twilio phone number you purchased at https://console.twilio.com
        'from' => '+18339954202',
        // The body of the text message you'd like to send
        'body' => "8:32 - sent from local",
    ]
);

echo "<pre>";
print_r($message->sid);
echo "</pre>";
