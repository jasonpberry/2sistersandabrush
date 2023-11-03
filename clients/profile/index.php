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

<?php if ($weddingsRecord): ?>

  <div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 pb-3 ">
          <div class="panel panel-left mb-3 ">
              <!-- Content for the left panel -->
              <a class="float-end" href="?action=logoff">Logout</a>
              <h2><span class="fas fa-home"></span> Hello, <?=$weddingsRecord['client_name:label']?>!</h2>

              <table width="100%" class="table table-striped-rows">
                <tr>
                  <td>
                    Email:
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

               <?php if ($serviceHairTrial || $serviceMakeupTrial): ?>
                  <tr>
                      <td>
                        Hair/Makeup Trial(s)
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

          </div>

          <div class="panel panel-left mb-3">
              <!-- Content for the left panel -->
              <h2><span class="fas fa-check"></span> Booking Updates</h2>

              <p class="alert alert-secondary">
                As you move from the booking stage to your wedding day, this section will
                continuously monitor your progress.
              </p>

              <style>
                .progress-check-complete {
                  color: green !important;
                  font-weight: bold;
                  font-size: 20px;
                }
                .progress-check-incomplete {
                  color: #ddd !important;
                  font-weight: bold;
                  font-size: 20px;
                }
              </style>

              <!-- Deposit -->
              <p>
                <?php if ($weddingsRecord['deposit_received']): ?>
                  <span class="fas fa-check progress-check-complete"></span> &nbsp; Deposit Received ($<?=$weddingsRecord['deposit_amount']?> on <?=date("F j, Y", $weddingsRecord['deposit_date:unixtime']);?>)
                <?php else: ?>
                  <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Deposit Received
                <?php endif?>
              </p>

              <!-- Contract Ready? -->
              <p>
                <?php if ($weddingsRecord['contract_ready']): ?>
                  <span class="fas fa-check progress-check-complete"></span> &nbsp; Contract Ready To Sign <?php if (!$weddingsRecord['contract_received']): ?>( <a target="_2saab_contract" href="/clients/contract/">View, Print & Sign & Return</a> )<?php endif;?>
                <?php else: ?>
                  <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Sign Contract
                <?php endif?>
              </p>

              <!-- Contract Received? -->
              <p>
                <?php if ($weddingsRecord['contract_received']): ?>
                  <span class="fas fa-check progress-check-complete"></span> &nbsp; Contract Received

                  <?php if (is_array($weddingsRecord['signed_contract']) && array_key_exists(0, $weddingsRecord['signed_contract'])): ?>

                    - <a href="<?=$weddingsRecord['signed_contract'][0]['urlPath'];?>">Download Signed Contract</a>

                  <?php endif;?>


                <?php else: ?>
                  <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Contract Received
                <?php endif?>
              </p>

              <?php if ($serviceHairTrial || $serviceMakeupTrial): ?>
                <!-- Trial Scheduled? -->
                <p>
                  <?php if ($weddingsRecord['trial_scheduled']): ?>
                    <span class="fas fa-check progress-check-complete"></span> &nbsp; Trial Scheduled For <?=date("l F j, Y - g:i a", $weddingsRecord['trial_scheduled_date:unixtime']);?>)
                  <?php else: ?>
                    <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Schedule Trial
                  <?php endif?>
                </p>

                <!-- Trial Complete? -->
                <p>
                  <?php if ($weddingsRecord['trial_complete']): ?>
                    <span class="fas fa-check progress-check-complete"></span> &nbsp; Trial Complete
                  <?php else: ?>
                    <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Your Trial
                  <?php endif?>
                </p>
              <?php endif;?>

              <!-- Wedding Complete? -->
              <p>
                <?php if ($weddingsRecord['wedding_complete']): ?>
                  <span class="fas fa-check progress-check-complete"></span> &nbsp; Wedding Complete
                <?php else: ?>
                  <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Your Wedding!
                <?php endif?>
              </p>

              <!-- Paid In FULL? -->
              <p>
                <?php if ($weddingsRecord['paid_in_full']): ?>
                  <span class="fas fa-check progress-check-complete"></span> &nbsp; Paid In FULL (Thank You!)
                <?php else: ?>
                  <span class="fas fa-check progress-check-incomplete"></span> &nbsp; Final Payment
                <?php endif?>
              </p>



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
                  <?php if ($serviceHair): ?>
                    <tr>
                        <td>Hair Count</td>
                        <td><?=$weddingsRecord['attendants_hair_count'] + 1?> - (Includes Bride)</td>
                    </tr>
                  <?php endif;?>
                  <?php if ($serviceMakeup): ?>
                    <tr>
                        <td>Makeup Count</td>
                        <td><?=$weddingsRecord['attendants_makeup_count'] + 1?> - (Includes Bride)</td>
                    </tr>
                  <?php endif;?>
                  <?php if ($serviceFlowerGirlHair): ?>
                    <tr>
                        <td>Flower Girl Hair Count</td>
                        <td><?=$weddingsRecord['flower_girl_hair_count']?></td>
                    </tr>
                  <?php endif;?>
                  <tr>
                      <td colspan="2">
                        <strong>Additional Details:</strong>

                        <p>
                          <em><?=$weddingsRecord['additional_details_from_bride']?></em>
                        </p>
                      </td>

                  </tr>
              </tbody>
          </table>

          <div class="alert alert-primary">

          While utilizing the client portal, kindly notify us of any encountered issues or problems by sending a
          message to <em><a href="mailto:2sistersandabrushbeauty@gmail.com">2sistersandabrushbeauty@gmail.com</a></em>.

          </div>


          </div>
        </div>
    </div>
  </div>
<?php else: ?>

  <div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 pb-3 ">
          <div class="alert alert-secondary">

              <h3>Hi <?=$CURRENT_USER['fullname']?>!</h3>

              <p>
                Thank you for accessing the 2 Sisters & A Brush client portal. We are currently in the
                process of inputting your booking information into our system.
              </p>

              <p>

              </p>
          </div>
        </div>
    </div>
  </div>

<?php endif;?>

<!-- EDIT PROFILE FORM -->
<!--
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


   <tr>
    <td colspan="2" align="center">
      <input class="button" type="submit" name="submit" value="Update profile &gt;&gt;">
    </td>
   </tr>
  </table>

  </form><br>


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
</div><br> -->


<?php include_once "../includes/footer.php"?>

