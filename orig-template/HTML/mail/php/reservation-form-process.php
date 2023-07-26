<?php

$errorMSG = "";

$fname = $_POST["fname"];
$lname = $_POST["lname"];
$email = $_POST["email"];
$phone = $_POST["phone"];
$address = $_POST["address"];
$zipcode = $_POST["zipcode"];
$city = $_POST["city"];
$select_service = $_POST["service"];
$date = $_POST["date"];
$time = $_POST["time"];
$EmailTo = "test@gmail.com";
$Subject = "Make an Appointment";

// prepare email body text
$Body = "";
$Body .= "First Name: ";
$Body .= $fname;
$Body .= "\n";
$Body .= "Last Name: ";
$Body .= $lname;
$Body .= "\n";
$Body .= "Email: ";
$Body .= $email;
$Body .= "\n";
$Body .= "Phone Number: ";
$Body .= $phone;
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
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// send email
$success = mail($EmailTo, $Subject, $Body, "From:".$email);

// redirect to success page
if ($success && $errorMSG == ""){
   echo "success";
}else{
    if($errorMSG == ""){
        echo "Something went wrong :(";
    } else {
        echo $errorMSG;
    }
}

?>