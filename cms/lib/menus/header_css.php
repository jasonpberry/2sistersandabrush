
  <!-- fonts -->
  <link href='<?php echo CMS_ASSETS_URL ?>/3rdParty/google-fonts/google-fonts.css' rel='stylesheet'>

  <!-- CSS -->
  <?php /* required for all templates */ ?>
  <link rel="stylesheet" href="<?php echo CMS_ASSETS_URL ?>/3rdParty/clipone/plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo CMS_ASSETS_URL ?>/3rdParty/clipone/fonts/style.css">
  <link rel="stylesheet" href="<?php echo CMS_ASSETS_URL ?>/3rdParty/clipone/css/print.css" media="print">
  <link rel="stylesheet" href="<?php echo noCacheUrlForCmsFile("3rdParty/clipone/css/main.css"); ?>">
  <link rel="stylesheet" href="<?php echo noCacheUrlForCmsFile("3rdParty/clipone/css/".$GLOBALS['SETTINGS']['cssTheme']); ?>" id="skin_color">
  <link rel="stylesheet" href="<?php echo CMS_ASSETS_URL ?>/3rdParty/clipone/css/main-responsive.css">
<?php if (file_exists(SCRIPT_DIR . '/3rdParty/clipone/css/main-small.css')): // you can delete or rename file to disable small screen CSS formatting ?>
  <link rel="stylesheet" href="<?php echo CMS_ASSETS_URL ?>/3rdParty/clipone/css/main-small.css">
<?php endif ?>

  <!-- end: MAIN CSS -->

  <link rel="stylesheet" href="<?php echo CMS_ASSETS_URL ?>/3rdParty/jqueryPlugins/uploadifive/uploadifive.css" media="screen">

  <!-- load favicon, etc -->
  <?php
    if (is_file("{$GLOBALS['PROGRAM_DIR']}/favicon.ico"))          { print "<link rel='shortcut icon' href='favicon.ico'>\n";  }
    if (is_file("{$GLOBALS['PROGRAM_DIR']}/apple-touch-icon.png")) { print "<link rel='apple-touch-icon' href='apple-touch-icon.png'>\n";  }
  ?>

  <!-- load custom.css if it exists -->
  <?php if (is_file("{$GLOBALS['PROGRAM_DIR']}/custom.css")): ?>
    <link rel="stylesheet" href="<?php echo noCacheUrlForCmsFile('custom.css')?>" media="screen">
  <?php endif ?>