<?php $GLOBALS['WEBSITE_MEMBERSHIP_PROFILE_PAGE'] = true; // prevent redirect loops for users missing fields listed in $GLOBALS['WEBSITE_LOGIN_REQUIRED_FIELDS'] ?>


<?php
# Developer Notes: To add "Agree to Terms of Service" checkbox (or similar checkbox field), just add it to the accounts menu in the CMS and uncomment agree_tos lines

// load viewer library
$libraryPath = 'cms/lib/viewer_functions.php';
$dirsToCheck = ['', '../', '../../', '../../../', '../../../../']; // add if needed: '/Users/jasonberry/dev/php/2sistersandabrush/'
foreach ($dirsToCheck as $dir) {if (@include_once ("$dir$libraryPath")) {break;}}
if (!function_exists('getRecords')) {die("Couldn't load viewer library, check filepath in sourcecode.");}
if (!@$GLOBALS['WEBSITE_MEMBERSHIP_PLUGIN']) {die("You must activate the Website Membership plugin before you can access this page.");}

//
$useUsernames = false; // Set this to false to disallow usernames, email will be used as username instead

// error checking
$errorsAndAlerts = "";
if (@$_REQUEST['missing_fields']) {$errorsAndAlerts = "Please fill out all of the following fields to continue.<br>\n";}
if (!$CURRENT_USER) {websiteLogin_redirectToLogin();}

### Update User Profile
if (@$_POST['save']) {

    // error checking
    $emailAlreadyInUse = mysql_count(accountsTable(), mysql_escapef("`num` != ?  AND ? IN (`username`, `email`)", $CURRENT_USER['num'], @$_REQUEST['email']));
    $usernameAlreadyInUse = mysql_count(accountsTable(), mysql_escapef("`num` != ?  AND ? IN (`username`, `email`)", $CURRENT_USER['num'], @$_REQUEST['username']));

    if (!@$_REQUEST['fullname']) {$errorsAndAlerts .= "You must enter your full name!<br>\n";}
    if (!@$_REQUEST['email']) {$errorsAndAlerts .= "You must enter your email!<br>\n";} elseif (!isValidEmail(@$_REQUEST['email'])) {$errorsAndAlerts .= "Please enter a valid email (example: user@example.com)<br>\n";} elseif ($emailAlreadyInUse) {$errorsAndAlerts .= "That email is already in use, please choose another!<br>\n";}
    if ($useUsernames) {
        if (!@$_REQUEST['username']) {$errorsAndAlerts .= "You must choose a username!<br>\n";} elseif (preg_match("/\s+/", @$_REQUEST['username'])) {$errorsAndAlerts .= "Username cannot contain spaces!<br>\n";} elseif ($usernameAlreadyInUse) {$errorsAndAlerts .= "That username is already in use, please choose another!<br>\n";}
    } elseif (!$useUsernames) {
        if (@$_REQUEST['username']) {$errorsAndAlerts .= "Usernames are not allowed!<br>\n";}
    }
    //if (!@$_REQUEST['agree_tos'])               { $errorsAndAlerts .= "You must agree to the Terms of Service!<br>\n"; }

    // update user
    if (!$errorsAndAlerts) {
        $colsToValues = array();
        //$colsToValues['agree_tos']        = $_REQUEST['agree_tos'];
        $colsToValues['fullname'] = $_REQUEST['fullname'];
        $colsToValues['username'] = $_REQUEST['email']; // email is saved as username if username code (not this line) is commented out
        $colsToValues['email'] = $_REQUEST['email'];
        // ... add more form fields here by copying the above line!
        $colsToValues['updatedByUserNum'] = $CURRENT_USER['num'];
        $colsToValues['updatedDate='] = 'NOW()';
        mysql_update(accountsTable(), $CURRENT_USER['num'], null, $colsToValues);

        // on success
        websiteLogin_setLoginTo($colsToValues['username'], $CURRENT_USER['password']); // update login session username in case use has changed it.
        $errorsAndAlerts = "Thanks, we've updated your profile!<br>\n";
    }
}

### Change Password
if (@$_POST['changePassword']) {

    // error checking
    $_REQUEST['oldPassword'] = preg_replace("/^\s+|\s+$/s", '', @$_REQUEST['oldPassword']); // v1.10 remove leading and trailing whitespace
    $oldPasswordHash = getPasswordDigest(@$_REQUEST['oldPassword']);
    if (!@$_REQUEST['oldPassword']) {$errorsAndAlerts .= "Please enter your current password<br>\n";} elseif ($oldPasswordHash != $CURRENT_USER['password']) {$errorsAndAlerts .= "Current password isn't correct!<br>\n";}
    $newPasswordErrors = getNewPasswordErrors(@$_REQUEST['newPassword1'], @$_REQUEST['newPassword2'], $CURRENT_USER['username']); // v2.52
    $errorsAndAlerts .= nl2br(htmlencode($newPasswordErrors));

    // change password
    if (!$errorsAndAlerts) {
        $passwordHash = getPasswordDigest($_REQUEST['newPassword2']);
        mysql_update(accountsTable(), $CURRENT_USER['num'], null, array('password' => $passwordHash)); // update password
        websiteLogin_setLoginTo($CURRENT_USER['username'], $_REQUEST['newPassword2']); // update current login session
        unset($_REQUEST['oldPassword'], $_REQUEST['newPassword1'], $_REQUEST['newPassword2']); // clear form password fields
        $errorsAndAlerts = "Thanks, we've updated your password!<br>\n";
    }
} ### END: Change Password

// prepopulate form with current user values
foreach ($CURRENT_USER as $name => $value) {
    if (array_key_exists($name, $_REQUEST)) {continue;}
    $_REQUEST[$name] = $value;
}

?>

<?php include_once "../includes/header.php"?>

<?php
// load record from 'weddings'  // load record from 'weddings'
list($weddingsRecords, $weddingsMetaData) = getRecords(array(
    'tableName' => 'weddings',
    'where' => 'client_name=' . $CURRENT_USER['num'], // load first record
    'loadUploads' => false,
    'allowSearch' => false,
    'limit' => '1',
));

$weddingsRecord = @$weddingsRecords[0]; // get first record
if (!$weddingsRecord) {dieWith404("Record not found!");} // show error message if no record found

// load record from 'weddings'  // load record from 'weddings'
list($venuesRecords, $venuesMetaData) = getRecords(array(
    'tableName' => 'event_venues',
    'where' => 'num=' . $weddingsRecord['venue_name'], // load first record
    'loadUploads' => false,
    'allowSearch' => false,
    'limit' => '1',
));
$venueRecord = @$venuesRecords[0]; // get first record

echo "<pre>";
// print_r($weddingsRecord);
// echo "<br>";
// print_r($venueRecord);
// print_r($CURRENT_USER);
// echo "<br>";
// print_r($weddingsRecord['venue_name:label']);
echo "</pre>";
?>


<div class="container">
  <div class="row justify-content-center">
      <div class="col-md-6 pb-3 ">
        <div class="panel panel-left mb-3 ">
            <!-- Content for the left panel -->
            <h2><span class="fas fa-home"></span> Hi <?=$weddingsRecord['client_name:label']?>!</h2>


            <table width="100%" class="table table-striped-rows">
              <tr>
                <td>
                  My Email:
                </td>
                <td>
                  <?=@$_REQUEST['email']?>
                </td>
              </tr>
              <tr>
                <td>
                  Phone Number:
                </td>
                <td>
                  <?=@$CURRENT_USER['cell_number']?>
                </td>
              </tr>
            </table>
        </div>

        <div class="panel panel-left mb-3">
            <!-- Content for the left panel -->
            <h2><span class="fas fa-dollar-sign"></span> Booking Pricing</h2>

            <table width="100%" class="table table-striped-rows">
            <tr>
                <td>
                  Bridal Hair Service
                </td>
                <td>
                  $420
                </td>
              </tr>
              <tr>
                <td>
                  Bridal Makeup Service
                </td>
                <td>
                  $420
                </td>
              </tr>
              <tr>
                <td>
                  Flower Girl
                </td>
                <td>
                  $50
                </td>
                <tr>
                <td>
                  Travel Fee
                </td>
                <td>
                  $100
                </td>
              </tr>
              <tr>
                <td>
                  <strong>Total</strong>
                </td>
                <td>
                  <strong>$990</strong>
                </td>
              </tr>
            </table>

        </div>

        <div class="panel panel-left mb-3">
            <!-- Content for the left panel -->
            <h2><span class="fas fa-check"></span> Booking Updates</h2>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
        </div>
      </div>


      <div class="col-md-6">
        <div class="panel panel-right">
            <!-- Content for the right panel -->
            <h2><span class="fas fa-calendar"></span> Booking Details</h2>

            <table class="table table-striped ">
            <tbody>
            <tr>
                    <td>Date</td>
                    <td><?=date("l - F j, Y", $weddingsRecord['wedding_date:unixtime']);?></td>
                </tr>
                <tr>
                    <td>Ready By</td>
                    <td><?=date("g:i a", $weddingsRecord['wedding_date:unixtime']);?></td>
                </tr>
                <tr>
                    <td>Venue</td>
                    <td>
                      <strong><?=$weddingsRecord['venue_name:label']?></strong>
                      <p>
                        <?=$venueRecord['venue_address'];?>
                      </p>
                  </td>
                </tr>
                <tr>
                    <td>Bridal Services</td>
                    <td>

                      <?php foreach ($weddingsRecord['services:labels'] as $key => $record): ?>
                        <p>
                          <span class="fas fa-check"></span> <?=$record?>
                        </p>
                      <?php endforeach?>
                    </td>
                </tr>
                <tr>
                    <td>Hair Count</td>
                    <td><?=$weddingsRecord['attendants_hair_count'] + 1?></td>
                </tr>
                <tr>
                    <td>Makeup Count</td>
                    <td><?=$weddingsRecord['attendants_makeup_count'] + 1?></td>
                </tr>
                <tr>
                    <td colspan="2">
                      <strong>Additional Details:</strong>

                      <p>
                        <em><?=$weddingsRecord['additional_details_from_bride']?></em>
                      </p>
                    </td>

                </tr>

                <!-- Add more rows as needed -->
            </tbody>
        </table>


        </div>
      </div>
  </div>
</div>


<!-- EDIT PROFILE FORM -->
  <?php if (@$errorsAndAlerts): ?>
    <div style="color: #C00; font-weight: bold; font-size: 13px;">
      <?php echo $errorsAndAlerts; ?><br>
    </div>
  <?php endif?>

  <form method="post" action="?">
  <input type="hidden" name="save" value="1">

  <table border="0" cellspacing="0" cellpadding="2">
   <tr>
    <td>Full Name</td>
    <td><input type="text" name="fullname" value="<?php echo htmlencode(@$_REQUEST['fullname']); ?>" size="50"></td>
   </tr>
   <tr>
    <td>Email</td>
    <td><input type="text" name="email" value="<?php echo htmlencode(@$_REQUEST['email']); ?>" size="50"></td>
   </tr>
<?php if ($useUsernames): ?>
   <tr>
    <td>Username</td>
    <td><input type="text" name="username" value="<?php echo htmlencode(@$_REQUEST['username']); ?>" size="50"></td>
   </tr>
<?php endif?>

<!--
   <tr>
    <td>Agree TOS</td>
    <td>
      <input type="hidden"   name="agree_tos" value="0">
      <label>
        <input type="checkbox" name="agree_tos" value="1" <?php checkedIf('1', @$_REQUEST['agree_tos']);?>>
        I agree to the <a href="#">terms of service</a>.
      </label>
    </td>
   </tr>
-->
   <tr>
    <td colspan="2" align="center">
      <input class="button" type="submit" name="submit" value="Update profile &gt;&gt;">
    </td>
   </tr>
  </table>

  </form><br>
<!-- /EDIT PROFILE FORM -->


<!-- CHANGE PASSWORD FORM -->
<div style="border: 1px solid #000; background-color: #EEE; padding: 10px; width: 500px">
  <b>Change Password</b><br>

  <form method="post" action="?">
  <input type="hidden" name="changePassword" value="1">

  <p><table border="0" cellspacing="0" cellpadding="1">
   <tr>
    <td>Current Password</td>
    <td><input type="password" name="oldPassword" value="<?php echo htmlencode(@$_REQUEST['oldPassword']); ?>" size="40" autocomplete="off"></td>
   </tr>
   <tr>
    <td>New Password</td>
    <td><input type="password" name="newPassword1" value="<?php echo htmlencode(@$_REQUEST['newPassword1']); ?>" size="40" autocomplete="off"></td>
   </tr>
   <tr>
    <td>New Password (again)</td>
    <td><input type="password" name="newPassword2" value="<?php echo htmlencode(@$_REQUEST['newPassword2']); ?>" size="40" autocomplete="off"></td>
   </tr>
   <tr>
    <td colspan="2" align="center">
      <input class="button" type="submit" name="submit" value="Change Password &gt;&gt;">
    </td>
   </tr>
  </table>

  </form>
</div><br>
<!-- /CHANGE PASSWORD -->

<a href="?action=logoff">Logoff</a>

<?php include_once "../includes/footer.php"?>

