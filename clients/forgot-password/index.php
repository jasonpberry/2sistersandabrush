<?php

// load viewer library
$libraryPath = 'cms/lib/viewer_functions.php';
$dirsToCheck = ['', '../', '../../', '../../../', '../../../../']; // add if needed: '/Users/jasonberry/dev/php/2sistersandabrush/'
foreach ($dirsToCheck as $dir) {if (@include_once ("$dir$libraryPath")) {break;}}
if (!function_exists('getRecords')) {die("Couldn't load viewer library, check filepath in sourcecode.");}

//
$errorsAndAlerts = alert(); // load any predefined alerts or errors
$showForm = true;
$isResetPage = isset($_REQUEST['userNum']) || isset($_REQUEST['resetCode']); // request password reset email
$isRequestPage = !$isResetPage; // reset password (with link from email)

// error checking
if (!@$GLOBALS['WEBSITE_MEMBERSHIP_PLUGIN']) {die("You must activate the Website Membership plugin before you can access this page.");}
if (!empty($CURRENT_USER)) {
    $errorsAndAlerts = "You are already logged in! <a href='{$GLOBALS['WEBSITE_LOGIN_POST_LOGIN_URL']}'>Click here to continue</a> or <a href='?action=logoff'>Logoff</a>.";
    $showForm = false;
}

// START: REQUEST FORGOT PASSWORD EMAIL - email a password reset link to user
if ($isRequestPage && isset($_POST['submitForm'])) {

    // error checking
    if (empty($_REQUEST['usernameOrEmail'])) {$errorsAndAlerts .= "Please Enter Username / Email\n";}
    if (!$errorsAndAlerts) {
        $where = mysql_escapef("? IN (`username`,`email`)", $_REQUEST['usernameOrEmail']);
        $user = mysql_get(accountsTable(), null, $where);
        if (!$user) {$errorsAndAlerts .= "Account Not Found!\n";} elseif (!isValidEmail($user['email'])) {$errorsAndAlerts .= "User doesn't have a valid email specified!\n";}
    }

    // send password reset email
    if (!$errorsAndAlerts && !empty($user)) {
        $emailHeaders = emailTemplate_loadFromDB(array(
            'template_id' => 'USER-PASSWORD-RESET',
            'placeholders' => array(
                'user.username' => $user['username'],
                'user.email' => $user['email'],
                'loginUrl' => realUrl($GLOBALS['WEBSITE_LOGIN_LOGIN_FORM_URL']),
                'resetUrl' => realUrl($GLOBALS['WEBSITE_LOGIN_REMINDER_URL'] . "?userNum={$user['num']}&resetCode=" . _generatePasswordResetCode($user['num'])),
            )));
        $mailErrors = sendMessage($emailHeaders);
        if ($mailErrors) {$errorsAndAlerts .= "Mail Error: $mailErrors";}

        //
        $errorsAndAlerts .= "Thanks, we've emailed you instructions on resetting your password.<br><br>
      If you don't receive an email within a few minutes check your spam filter for messages from {$emailHeaders['from']}\n";
        $_REQUEST = array(); // clear form fields
        $showForm = false;
    }
}
// END: REQUEST FORGOT PASSWORD EMAIL

// START: RESET PASSWORD FORM - using link from password reset email
if ($isResetPage) {

    // error checking
    if (empty($_REQUEST['userNum'])) {die("No 'userNum' value specified!");}
    if (empty($_REQUEST['resetCode'])) {die("No 'resetCode' value specified!");}
    $isValidResetCode = _isValidPasswordResetCode($_REQUEST['userNum'], $_REQUEST['resetCode']);
    if (!$isValidResetCode) {
        $errorsAndAlerts .= t("Password reset code has expired or is not valid. Try resetting your password again.");
        $showForm = false;
    }

    // load user details
    $user = mysql_get(accountsTable(), $_REQUEST['userNum']);

    // reset password
    if (isset($_POST['submitForm']) && $isValidResetCode) {
        // error checking
        $errorsAndAlerts .= getNewPasswordErrors(@$_REQUEST['password'], @$_REQUEST['password:again'], $user['username']);

        // update password
        if (!$errorsAndAlerts) {
            $newPassword = getPasswordDigest($_REQUEST['password']);
            mysql_update(accountsTable(), $user['num'], null, array('password' => $newPassword));

            // show message
            $errorsAndAlerts .= t('Password updated!');
            $_REQUEST = array(); // clear form fields
            $showForm = false;
        }
    }
}
// END: RESET PASSWORD FORM

?>

<?php include_once "../includes/header.php"?>


  <!-- PAGE TITLE -->

  <div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
          <div class="panel panel-center ">

              <?php if ($isRequestPage): ?> <h1 class="text-center mb-3">Forgot Password?</h1> <?php endif?>
              <?php if ($isResetPage): ?> <h1 class="text-center mb-3">Reset Password</h1> <?php endif?>

              <?php if (@$errorsAndAlerts): ?>
                <div class="alert alert-danger">
                  <?php echo $errorsAndAlerts; ?><br>
                </div>
              <?php endif?>

              <?php if ($isRequestPage && $showForm): ?>

                <form action="?" method="post">
                  <input type="hidden" name="submitForm" value="1">

                    <div class="form-outline mb-4">
                      <label class="form-label" for="form3Example3">Email:</label>
                      <input class="form-control" type="text" name="usernameOrEmail" value="<?php echo htmlencode(@$_REQUEST['useusernameOrEmailrname']); ?>" size="30" autocomplete="off">
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary btn-block mb-4">
                      Lookup
                    </button>
                </form>
              <?php endif;?>
              <br />
              <p>
                <a class="float-end" href="<?php echo "/clients/" ?>">Client Portal Login</a >
              </p>
          </div>
        </div>
    </div>
  </div>



  <?php include_once "../includes/footer.php";