<?php

$errorMSG = "";

$fname = $_POST["fname"];
$lname = $_POST["lname"];
$emailFromForm = $_POST["email"];
$email = 'noreply@2sistersandabrush.beauty';
// $email = 'jasonpberry78@gmail.com'; // for local emails
$phone = $_POST["phone"];
$vname = $_POST["vname"];
$address = $_POST["address"];
$zipcode = $_POST["zipcode"];
$city = $_POST["city"];
$select_service = $_POST["service"];
$date = $_POST["date"];
$time = $_POST["time"];
$message = $_POST["message"];
$EmailTo = "jasonpberry78@gmail.com";
$Subject = $fname . " " . $lname . " -  Request for Bridal Services";

// prepare email body text
$Body = "";
$Body .= "First Name: ";
$Body .= $fname;
$Body .= "\n";
$Body .= "Last Name: ";
$Body .= $lname;
$Body .= "\n";
$Body .= "Email: ";
$Body .= $emailFromForm;
$Body .= "\n";
$Body .= "Phone Number: ";
$Body .= $phone;
$Body .= "\n";
$Body .= "Venue Name: ";
$Body .= $vname;
$Body .= "\n";
$Body .= "Address: ";
$Body .= $address;
$Body .= "\n";
$Body .= "Zip Code: ";
$Body .= $zipcode;
$Body .= "\n";
$Body .= "City: ";
$Body .= $city;
$Body .= "\n";
$Body .= "Service: ";
$Body .= $select_service;
$Body .= "\n";
$Body .= "Reservation Date: ";
$Body .= $date;
$Body .= "\n";
$Body .= "Reservation Time: ";
$Body .= $time;
$Body .= "\n";
$Body .= "Additional Info: ";
$Body .= "\n";
$Body .= "\n";
$Body .= $message;
$Body .= "\n";
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "From: 2 Sisters & A Brush) " . $email . "\r\n";
$headers .= "Cc: 4438072661@mms.att.net" . "\r\n";
$headers .= "BCC: Ldiesel45@gmail.com" . "\r\n";

$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Return-Path: " . $email . "\r\n";
$headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";

// send email

// local
// $success = mail($EmailTo, $Subject, $Body, $headers);

// prod / godaddy
$success = mail($EmailTo, $Subject, $Body, $headers, "-f " . $email);

// redirect to success page
if ($success && $errorMSG == "") {
    sendTextMessage('+14438072661', $Subject);
    echo "success";
} else {
    if ($errorMSG == "") {
        echo "Something went wrong :(";
    } else {
        echo $errorMSG;
    }
}

function sendTextMessage($toNumber, $Subject)
{
    // Require the bundled autoload file - the path may need to change
    // based on where you downloaded and unzipped the SDK
    require __DIR__ . '/../../libs/src/Twilio/autoload.php';
    require __DIR__ . '/../../libs/src/Twilio/.env.php';

    $client = new Twilio\Rest\Client($sid, $token);

    // Use the Client to make requests to the Twilio REST API
    $message = $client->messages->create(
        // The number you'd like to send the message to
        $toNumber,
        [
            // A Twilio phone number you purchased at https://console.twilio.com
            'from' => '+18339954202',
            // The body of the text message you'd like to send
            'body' => "New 2 Sisters & A Brush booking form has been emailed to you.\n\n" . $Subject,
        ]
    );

    // echo "<pre>";
    // print_r($message->sid);
    // echo "</pre>";

}
