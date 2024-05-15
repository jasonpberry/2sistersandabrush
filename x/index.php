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
    'loadUploads' => true,
    'allowSearch' => true,
    'where' => 'wedding_date >= NOW()',
));

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bootstrap Tab with Sortable & Filterable Table</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <!-- jQuery -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://2sistersandabrush:8890/js/bootstrap.min.js"></script>
  <!-- DataTables CSS -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
  <!-- DataTables JS -->
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>
<body>

<style>
    .tab-pane {
        padding: 5px 0;
    }
</style>

<div class="container mt-5">
  <ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="tab1-tab" data-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">Weddings</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="tab2-tab" data-toggle="tab" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">Trials</a>
    </li>
  </ul>
  <div class="tab-content" id="myTabContent" style="padding: 10px;">
    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
        <h2>Upcoming Weddings</h2>

        <div class="table-responsive">
        <table class="table table-striped table-bordered" id="sampleTable" style="min-width: 600px;">
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>Hair</th>
              <th>Makeup</th>
              <th>Due</th>
            </tr>
          </thead>
          <tbody>
<?php

foreach ($weddingsRecords as $record):
    // Services
    $serviceHair = false;
    $serviceMakeup = false;
    $serviceFlowerGirlHair = false;
    // Trials?
    $serviceHairTrial = false;
    $serviceMakeupTrial = false;

    // Check Services

    if ($record && is_array($record['services:values'])) {
        if (in_array(4, $record['services:values'])) {$serviceHair = true;}
        if (in_array(2, $record['services:values'])) {$serviceMakeup = true;}

        // Check Trial Services
        if (in_array(1, $record['services:values'])) {$serviceHairTrial = true;}
        if (in_array(3, $record['services:values'])) {$serviceMakeupTrial = true;}

        // Flower Girl Hair Service
        if (in_array(6, $record['services:values'])) {$serviceFlowerGirlHair = true;}

    }
    ?>


						<tr>
						<td><?php echo date("Y-m-d", strtotime($record['wedding_date'])) ?></td>
						<td><?php echo $record['client_name:label'] ?></td>
						<td>
						<?php if ($serviceHair): ?>
						    Yes (<?=$record['attendants_hair_count:label'] + 1;?>)
						<?php else: ?>
            No
			    <?php endif;?>

                </td>
		        <td>


                <?php if ($serviceMakeup): ?>
		            Yes (<?=$record['attendants_makeup_count:label'] + 1;?>)
		    <?php else: ?>
No
			    <?php endif;?>

            </td>
		        <td>
                    $<?=$record['hair_total'] + $record['makeup_total'] + $record['flower_girl_total'] + $record['travel_fee'] - $record['deposit_amount']?>
                </td>
		        </tr>
		    <?php endforeach;?>

          </tbody>
        </table>
      </div>


    </div>

    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
        <h2>Weddings</h2>
      <div class="table-responsive">
        <table class="table table-striped table-bordered" id="sampleTable2">
          <thead>
            <tr>
              <th>Name</th>
              <th>Age</th>
              <th>Location</th>
              <th>Another Col</th>
              <th>Col Name</th>
            </tr>
          </thead>
          <tbody>

            <tr>
              <td>John</td>
              <td>25</td>
              <td>New York</td>
              <td>New York</td>
              <td>New York</td>
            </tr>
            <tr>
              <td>John</td>
              <td>25</td>
              <td>New York</td>
              <td>New York</td>
              <td>New York</td>
            </tr>

          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<br />
<br />
<br />
<br />
<br />
<br />

<script>
  $(document).ready(function() {

    $('#sampleTable').DataTable({
        pageLength: 25,
        columnDefs: [
        {
          targets: 0, // Date column index
          render: function(data, type, row) {
            // Parse the date to ensure proper sorting
            var date = new Date(data);

            return new Date(date).toLocaleDateString("en-us", {
                year: "numeric",
                month: "long",
                day: "numeric",
                timeZone: "UTC"
            })
          }
        }],

    });

    $('#sampleTable2').DataTable({
        pageLength: 25
    });
  });
</script>

<br />
<br />
<br />
<br />
<br />
<br />

</body>
</html>
