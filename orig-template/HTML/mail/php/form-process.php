<?php

$errorMSG = "";

$fname = $_POST["fname"];
$lname = $_POST["lname"];
$email = $_POST["email"];
$message = $_POST["message"];
$EmailTo = "test@gmail.com";
$Subject = "Website Contact Form";

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
$Body .= "Message: ";
$Body .= $message;
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