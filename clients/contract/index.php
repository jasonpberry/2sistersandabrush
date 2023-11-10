<?php $GLOBALS['WEBSITE_MEMBERSHIP_PROFILE_PAGE'] = true; // prevent redirect loops for users missing fields listed in $GLOBALS['WEBSITE_LOGIN_REQUIRED_FIELDS'] ?>


<?php
# Developer Notes: To add "Agree to Terms of Service" checkbox (or similar checkbox field), just add it to the accounts menu in the CMS and uncomment agree_tos lines

// load viewer library
$libraryPath = 'cms/lib/viewer_functions.php';
$dirsToCheck = ['', '../', '../../', '../../../', '../../../../']; // add if needed: '/Users/jasonberry/dev/php/2sistersandabrush/'
foreach ($dirsToCheck as $dir) {if (@include_once ("$dir$libraryPath")) {break;}}
if (!function_exists('getRecords')) {die("Couldn't load viewer library, check filepath in sourcecode.");}
if (!@$GLOBALS['WEBSITE_MEMBERSHIP_PLUGIN']) {die("You must activate the Website Membership plugin before you can access this page.");}

// Not Logged in
if (!$CURRENT_USER) {websiteLogin_redirectToLogin();}

// prepopulate form with current user values
foreach ($CURRENT_USER as $name => $value) {
    if (array_key_exists($name, $_REQUEST)) {continue;}
    $_REQUEST[$name] = $value;
}

// Impersonate a user
if (isset($_REQUEST['id'])) {
    $CURRENT_USER['num'] = $_REQUEST['id'];
}

// load record from 'weddings'  // load record from 'weddings'
list($weddingsRecords, $weddingsMetaData) = getRecords(array(
    'tableName' => 'weddings',
    'where' => 'client_name=' . $CURRENT_USER['num'], // load first record
    'loadUploads' => true,
    'allowSearch' => false,
    'limit' => '1',
));

$weddingsRecord = @$weddingsRecords[0]; // get first record
// if (!$weddingsRecord) {dieWith404("Record not found!");} // show error message if no record found

// load record from 'event_venues'  // load record from 'weddings'
if ($weddingsRecord) {
    list($venuesRecords, $venuesMetaData) = getRecords(array(
        'tableName' => 'event_venues',
        'where' => 'num=' . $weddingsRecord['venue_name'], // load first record
        'loadUploads' => false,
        'allowSearch' => false,
        'limit' => '1',
    ));
    $venueRecord = @$venuesRecords[0]; // get first record
}

// Services
$serviceHair = false;
$serviceMakeup = false;
$serviceFlowerGirlHair = false;
// Trials?
$serviceHairTrial = false;
$serviceMakeupTrial = false;

// Check Services

if ($weddingsRecord && is_array($weddingsRecord['services:values'])) {
    if (in_array(4, $weddingsRecord['services:values'])) {$serviceHair = true;}
    if (in_array(2, $weddingsRecord['services:values'])) {$serviceMakeup = true;}

    // Check Trial Services
    if (in_array(1, $weddingsRecord['services:values'])) {$serviceHairTrial = true;}
    if (in_array(3, $weddingsRecord['services:values'])) {$serviceMakeupTrial = true;}

    // Flower Girl Hair Service
    if (in_array(6, $weddingsRecord['services:values'])) {$serviceFlowerGirlHair = true;}

}

// echo "<pre>";
// print_r($weddingsRecord);
// echo "<br>";
// print_r($venueRecord);
// print_r($CURRENT_USER);
// echo "<br>";
// print_r($weddingsRecord['venue_name:label']);
// echo "</pre>";
?>




<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Hair & Makeup Contract</title>

    <style>
        body {
            margin: 0 0;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            line-height: 28px;
            padding: 0 0 0 20px;
            /* width: 700px; */
        }

        h1 {
            font-size: 26px;
            text-decoration: underline;
        }

        h2 {
            font-size: 20px;
        }
        .signatures {
            line-height: 50px;
        }
    </style>

</head>
<body onload="printDoc();">
<!-- <body> -->
    <div class="text-center" style="text-align:center; padding: 10px;">
        <img src="https://www.2sistersandabrush.beauty/img/2sisters-logo.png" width="450" style="width: 100%; max-width: 400px;">
        <h1>Bridal Hair & Makeup Contract</h1>

    </div>


    <h2>Client Information:</h2>
    <p>
        Bride's Name: <?=$weddingsRecord['client_name:label']?><br>
        Contact Number: <?=@$CURRENT_USER['cell_number']?><br>
        Email Address: <?=@$CURRENT_USER['email']?><br>
    </p>

    <h2>Services Requested:</h2>
    <?php foreach ($weddingsRecord['services:labels'] as $key => $record): ?>
        - <?=$record?><br />
    <?php endforeach?>


    <h2>Wedding Details:</h2>
    <p>
        Wedding Date: <?=date("l - F j, Y", $weddingsRecord['wedding_date:unixtime']);?><br>
        Ready By Time: <?=date("g:i a", $weddingsRecord['wedding_date:unixtime']);?><br>
        Venue: <?=$weddingsRecord['venue_name:label']?><br>
    </p>

    <h2>Package Chosen:</h2>
    <table width="" class="table table-striped-rows">

<?php if ($serviceHairTrial || $serviceMakeupTrial): ?>
   <tr>
       <td width="250">
         Hair/Makeup Trial(s)<br />
         <em>Paid at Trial</em>
       </td>
       <td>
         $<?=$weddingsRecord['hair_makeup_trial_total']?>
       </td>
   </tr>
 <?php endif;?>

 <?php if ($serviceHair): ?>
   <tr>
       <td>
         Bridal Hair Service (<?=$weddingsRecord['attendants_hair_count'] + 1?>)
       </td>
       <td>
         $<?=$weddingsRecord['hair_total']?>
       </td>
   </tr>
 <?php endif;?>
 <?php if ($serviceMakeup): ?>
   <tr>
     <td>
       Bridal Makeup Service (<?=$weddingsRecord['attendants_makeup_count'] + 1?>)
     </td>
     <td>
     $<?=$weddingsRecord['makeup_total']?>
     </td>
   </tr>
 <?php endif;?>
 <?php if ($serviceFlowerGirlHair): ?>
 <tr>
   <td>
     Flower Girl (<?=$weddingsRecord['flower_girl_hair_count']?>)
   </td>
   <td>
     $<?=$weddingsRecord['flower_girl_total']?>
   </td>
 </tr>
 <?php endif;?>
 <tr>
   <td>
     Travel Fee
   </td>
   <td>
     $<?=$weddingsRecord['travel_fee']?>
   </td>
 </tr>
 <tr>
   <td>
     <strong>Total</strong>
   </td>
   <td>
     <strong>$<?=$weddingsRecord['total_service_cost']?></strong>
   </td>
 </tr>
</table>


    <h2>Booking Fee and Payment Schedule:</h2>
    <p>
        A non-refundable booking fee of <strong>$<?=($weddingsRecord['deposit_amount']) ? $weddingsRecord['deposit_amount'] : '200';?></strong> is required via Venmo to secure the date.<br>
        The remaining balance of <strong>$<?=$weddingsRecord['hair_total'] + $weddingsRecord['makeup_total'] + $weddingsRecord['flower_girl_total'] + $weddingsRecord['travel_fee'] - $weddingsRecord['deposit_amount']?></strong>
        is due on or before <strong><?=date("F j, Y", $weddingsRecord['wedding_date:unixtime']);?></strong>.

        Payment can be made via <strong>CASH</strong>.
    </p>

    <h2>Cancellation Policy:</h2>
    <p>
        In the event of cancellation by the client, the booking fee is non-refundable.<br>
        Cancellation within 30 days of the event will result in payment of the full remaining balance.
    </p>

    <h2>Trial Run:</h2>
    <p>
        A trial run for both Hair & Makeup will be scheduled if chosen.  Date to be decided by both parties.
        Additional charges may apply for any extra trial runs.
    </p>

    <h2>Additional Services:</h2>
    <p>
        Any additional services requested on the wedding day that were not included in the package will be charged separately.
    </p>

    <h2>Preparation:</h2>
    <p>
        The client should arrive with clean, dry hair and a makeup-free face on the wedding day.
        Any changes to the wedding day schedule must be communicated to the artist at least [Notice Period] days in advance.
    </p>

    <h2>Liability:</h2>
    <p>
        2 Sisters & A Brush is not liable for any allergic reactions, accidents, or injuries that may occur during or after the application of makeup or hairstyling.
        The client is responsible for informing the artist of any allergies or sensitivities prior to the appointment.
    </p>

    <h2>Photos and Promotion:</h2>
    <p>
        The client grants 2 Sisters & A Brush permission to use photographs of the bridal Hair & Makeup for promotional purposes, including but not limited to the company's website and social media.
    </p>

    <h2>Agreement:</h2>
    <p>
        The client agrees to the terms and conditions outlined in this contract.<br>
        Both parties acknowledge receipt of a copy of this contract.
    </p>

    <br />

    <h2>Signatures:</h2>
    <p class="signatures">

        Date: <?=date("m/d/Y");?>
        <br />
        Client's Name:<br />
         _______________________________
        <br />
        Client's Signature:
        <br />
        _______________________________
        <br />
        Date: <?=date("m/d/Y");?>
        <br />


        Hair Artist's Signature:
        <br />
        _______________________________
        <br />
        Date: <?=date("m/d/Y");?>
        <br>
        Makeup Artist's Signature:
        <br />
        _______________________________

    </p>

    <p>Please return a signed copy of this contract along with the booking fee to secure
        your wedding date. If you have any questions or need further clarification, please
        do not hesitate to contact us at <em>2sistersandabrushbeauty@gmail.com</em>.</p>

    <p>Thank you for choosing 2 Sisters and a Brush for your bridal Hair & Makeup services.
        We look forward to helping you look your best on your special day!</p>

    <script>
      function printDoc() {

        setTimeout(function() {
          window.print()
        },2000);
      }
    </script>

    </body>
</html>