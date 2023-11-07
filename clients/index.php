<?php

// load viewer library
$libraryPath = 'cms/lib/viewer_functions.php';
$dirsToCheck = ['', '../', '../../', '../../../', '../../../../']; // add if needed: '/Users/jasonberry/dev/php/2sistersandabrush/'
foreach ($dirsToCheck as $dir) {if (@include_once ("$dir$libraryPath")) {break;}}
if (!function_exists('getRecords')) {die("Couldn't load viewer library, check filepath in sourcecode.");}
if (!@$GLOBALS['WEBSITE_MEMBERSHIP_PLUGIN']) {die("You must activate the Website Membership plugin before you can access this page.");}

// error checking
$errorsAndAlerts = alert();
if (@$CURRENT_USER) {
    $redirectUser = true;
    $errorsAndAlerts .= "You are already logged in! <a href='{$GLOBALS['WEBSITE_LOGIN_POST_LOGIN_URL']}'>Click here to continue</a> or <a href='?action=logoff'>Logoff</a>.<br>\n";
}
if (!$CURRENT_USER && @$_REQUEST['loginRequired']) {$errorsAndAlerts .= "Please login to continue.\n";}

// save url of referring page so we can redirect user there after login
// if (!getPrefixedCookie('lastUrl')) { setPrefixedCookie('lastUrl', @$_SERVER['HTTP_REFERER'] ); }

if (isset($redirectUser)) {
    echo '<meta http-equiv = "refresh" content = "0; url = /clients/profile/" />';
}
?>

<?php include_once "includes/header.php"?>

<!-- USER LOGIN FORM IF NOT LOGGED IN-->

<?php if (!@$CURRENT_USER): ?>
  <div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
          <div class="panel panel-center ">

              <h1 class="text-center">Client Login</h1>

              <?php if (@$errorsAndAlerts): ?>
                <div class="alert alert-danger">
                  <?php echo $errorsAndAlerts; ?><br>
                </div>
              <?php endif?>

              <form action="?" method="post">
                <input type="hidden" name="action" value="login">

                  <div class="form-outline mb-4">
                    <label class="form-label" for="form3Example3">Email:</label>
                    <input class="form-control" type="email" name="username" value="<?php echo htmlencode(@$_REQUEST['username']); ?>" size="30" autocomplete="off">
                  </div>

                  <div class="form-outline mb-4">
                    <label class="form-label" for="form3Example4">Password:</label>
                    <input class="form-control" type="password" name="password" value="<?php echo htmlencode(@$_REQUEST['password']); ?>" size="30" autocomplete="off">
                  </div>

                  <button type="submit" name="submit" class="btn btn-primary btn-block mb-4">
                    Login
                  </button>
                </form>

                <p>
                  <!-- <a class="float-end" href="<?php echo $GLOBALS['WEBSITE_LOGIN_REMINDER_URL'] ?>">Forgot your password?</a > -->
                </p>
          </div>
        </div>
    </div>
  </div>

<?php endif?>

<?php include_once "includes/footer.php";