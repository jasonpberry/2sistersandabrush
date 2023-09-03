<?php

$errorMSG = "";

$fname = $_POST["fname"];
$lname = $_POST["lname"];
$emailFromForm = $_POST["email"];
$email = 'noreply@2sistersandabrush.beauty';
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
$headers .= "From: (2 Sisters & A Brush) " . $email . "\r\n";
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
    echo "success";
} else {
    if ($errorMSG == "") {
        echo "Something went wrong :(";
    } else {
        echo $errorMSG;
    }
}
