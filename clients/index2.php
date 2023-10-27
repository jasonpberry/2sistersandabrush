<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>2 Sisters & A Brush - Client Login</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css?family=Poppins|Dancing Script|Sacramento:300,400,400i,500,600,700,800,900" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

  <style>
    body {
      background-color: #eee;
      padding-bottom: 20px;
    }
    .text-logo {
      font-family: "Sacramento";
      font-size: 38px;
      padding: 20px 0;
    }
  </style>

</head>
<body>

<style>
    /* Custom CSS for panel styling */
  .panel-left, .panel-right {
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f8f9fa;
  }
  .panel-center {
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background-color: #f8f9fa;

  }
  label {
    font-weight: bold;;
  }
  span.fas {
    color: #6F8FAF;
  }
</style>

<div class="container">

  <div class="text-logo text-center">2 Sisters & A Brush</div>

  <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="panel panel-center ">

            <h1 class="text-center">Client Login</h1>

            <form>
                <div class="form-outline mb-4">
                  <label class="form-label" for="form3Example3">Email:</label>
                  <input type="email" id="form3Example3" class="form-control" />
                </div>

                <div class="form-outline mb-4">
                  <label class="form-label" for="form3Example4">Password:</label>
                  <input type="password" id="form3Example4" class="form-control" />
                </div>

                <button type="submit" class="btn btn-primary btn-block mb-4">
                  Login
                </button>

              </form>

        </div>
      </div>
  </div>
</div>

<br />
<br />


<div class="container">
  <div class="row">
      <div class="col-md-6 pb-3">
      <div class="panel panel-left mb-3">
            <!-- Content for the left panel -->
            <h2><span class="fas fa-home"></span> Hi Jason Berry!</h2>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
            <p>This is the left panel content.</p>
        </div>

        <div class="panel panel-left">
            <!-- Content for the left panel -->
            <h2><span class="fas fa-check"></span> Booking Updates</h2>
            <p>This is the left panel content.</p>
        </div>
      </div>


      <div class="col-md-6">
        <div class="panel panel-right">
            <!-- Content for the right panel -->
            <h2><span class="fas fa-calendar"></span> Event Details</h2>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
            <p>This is the right panel content.</p>
        </div>
      </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>
