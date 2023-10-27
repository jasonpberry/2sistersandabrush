<?php header('Content-type: text/html; charset=utf-8');?>
<?php
/* STEP 1: LOAD RECORDS - Copy this PHP code block near the TOP of your page */

// load viewer library
$libraryPath = 'cms/lib/viewer_functions.php';
$dirsToCheck = ['', '../', '../../', '../../../', '../../../../']; // add if needed: '/Users/jasonberry/dev/php/2sistersandabrush/'
foreach ($dirsToCheck as $dir) {if (@include_once ("$dir$libraryPath")) {break;}}
if (!function_exists('getRecords')) {die("Couldn't load viewer library, check filepath in sourcecode.");}

// load records from 'weddings'
list($weddingsRecords, $weddingsMetaData) = getRecords(array(
    'tableName' => 'weddings',
    'limit' => '1',
    'loadUploads' => true,
    'allowSearch' => false,
));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title></title>
  <style>
    body          { font-family: arial; }
    .instructions { border: 3px solid #000; background-color: #EEE; padding: 10px; text-align: left; margin: 25px}
  </style>
</head>
<body>

  <!-- INSTRUCTIONS -->
    <div class="instructions">
      <b>Sample List Viewer - Instructions:</b>
      <ol>
        <?php /*><li style="color: red; font-weight: bold">Rename this file to have a .php extension!</li><x */?>
        <li><b>Remove any fields you don't want displayed.</b></li>
        <li>Rearrange remaining fields to suit your needs.</li>
        <li>Copy and paste code into previously designed page (or add design to this page).</li>
      </ol>
    </div>
  <!-- /INSTRUCTIONS -->

  <!-- STEP2: Display Records (Paste this where you want your records to be listed) -->
    <h1>Weddings - List Page Viewer</h1>
    <?php foreach ($weddingsRecords as $record): ?>
      Record Number: <?php echo htmlencode($record['num']) ?><br>

      Client Name (value): <?php echo $record['client_name'] ?><br>
      Client Name (label): <?php echo $record['client_name:label'] ?><br>
      Venue Name (value): <?php echo $record['venue_name'] ?><br>
      Venue Name (label): <?php echo $record['venue_name:label'] ?><br>
      Wedding Date: <?php echo date("D, M jS, Y g:i:s a", strtotime($record['wedding_date'])) ?><br><!-- For date formatting codes see: http://www.php.net/date -->
      Additional Details from Bride: <?php echo htmlencode($record['additional_details_from_bride']) ?><br>

      Services (values): <?php echo join(', ', $record['services:values']); ?><br>
      Services (labels): <?php echo join(', ', $record['services:labels']); ?><br>
      Attendants Hair Count (value): <?php echo $record['attendants_hair_count'] ?><br>
      Attendants Hair Count (label): <?php echo $record['attendants_hair_count:label'] ?><br>
      Attendants Makeup Count (value): <?php echo $record['attendants_makeup_count'] ?><br>
      Attendants Makeup Count (label): <?php echo $record['attendants_makeup_count:label'] ?><br>
      Flower Girl Hair  (value): <?php echo $record['flower_girl_hair'] ?><br>
      Flower Girl Hair  (label): <?php echo $record['flower_girl_hair:label'] ?><br>
      Hair Total: <?php echo htmlencode($record['hair_total']) ?><br>
      Makeup Total: <?php echo htmlencode($record['makeup_total']) ?><br>
      Travel Fee: <?php echo htmlencode($record['travel_fee']) ?><br>
      Total Service Cost: <?php echo htmlencode($record['total_service_cost']) ?><br>

      Deposit Received (value): <?php echo $record['deposit_received'] ?><br>
      Deposit Received (text):  <?php echo $record['deposit_received:text'] ?><br>
      Contract Ready (value): <?php echo $record['contract_ready'] ?><br>
      Contract Ready (text):  <?php echo $record['contract_ready:text'] ?><br>
      Trial Scheduled (value): <?php echo $record['trial_scheduled'] ?><br>
      Trial Scheduled (text):  <?php echo $record['trial_scheduled:text'] ?><br>
      Trial Scheduled Date: <?php echo date("D, M jS, Y g:i:s a", strtotime($record['trial_scheduled_date'])) ?><br><!-- For date formatting codes see: http://www.php.net/date -->
      Trial Complete (value): <?php echo $record['trial_complete'] ?><br>
      Trial Complete (text):  <?php echo $record['trial_complete:text'] ?><br>
      Paid in FULL (value): <?php echo $record['paid_in_full'] ?><br>
      Paid in FULL (text):  <?php echo $record['paid_in_full:text'] ?><br>
      Wedding Complete (value): <?php echo $record['wedding_complete'] ?><br>
      Wedding Complete (text):  <?php echo $record['wedding_complete:text'] ?><br>
      _link : <a href="<?php echo $record['_link'] ?>"><?php echo $record['_link'] ?></a><br>
      <hr>
    <?php endforeach?>

    <?php if (!$weddingsRecords): ?>
      No records were found!<br><br>
    <?php endif?>
  <!-- /STEP2: Display Records -->

</body>
</html>
