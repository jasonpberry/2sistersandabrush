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


contract index