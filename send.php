<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require the bundled autoload file - the path may need to change
// based on where you downloaded and unzipped the SDK
require __DIR__ . '/libs/src/Twilio/autoload.php';

// Your Account SID and Auth Token from console.twilio.com
$sid = "ACXXXXXX";
$token = "YYYYYY";
$client = new Twilio\Rest\Client($sid, $token);

// Use the Client to make requests to the Twilio REST API
$client->messages->create(
    // The number you'd like to send the message to
    '+14438072661',
    [
        // A Twilio phone number you purchased at https://console.twilio.com
        'from' => '+15017250604',
        // The body of the text message you'd like to send
        'body' => "Hey Jenny! Good luck on the bar exam!",
    ]
);

print_r($client);
