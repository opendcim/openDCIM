<?php
require_once("db.inc.php");
require_once("facilities.inc.php");

$subheader = __("Historical Graph");

if (!$person->ReadAccess) {
  // No soup for you.
  header('Location: ' . redirect());
  exit;
}

date_default_timezone_set('Asia/Singapore');
$dev = new Device();
$dataCenterList = $dev->GetDataCenterList();

$selectedStartDate = "";
$selectedEndDate = "";



if ($_POST['action'] == 'Submit') {
  $sensorList = $dev->GetSensorList($_REQUEST['DataCenterID']);
  $readingArray = array();
  $startDate = $_POST['startDate'];
  $endDate = $_POST['endDate'];
  $selectedStartDate = $_POST['startDate'];
  $selectedEndDate = $_POST['endDate'];
  foreach ($sensorList as $sensor) {
    $readingArray[] = $dev->GetHistoricalSensorReading($sensor[0], $startDate, $endDate);
    
  }
}

?>
<!doctype html>
<html>

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title>Historical Graph</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/Chart.min.css" type="text/css">
  <link rel="stylesheet" href="css/dygraph.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
  <script type="text/javascript" src="scripts/moment.js"></script>
  <script type="text/javascript" src="scripts/Chart.min.js"></script>
  <script type="text/javascript" src="scripts/hammer.js"></script>
  <script type="text/javascript" src="scripts/chartjs-plugin-zoom.min.js"></script>
  <script type="text/javascript" src="scripts/chartjs-plugin-colorschemes.min.js"></script>
  <script type="text/javascript" src="scripts/dygraph.min.js"></script>




  <script type="text/javascript">
    //Jquery datepicker UI to make limit the options of dates. within history of 7 days and end date must be >= start date 
    $(document).ready(function() {
      var selectedStartDate = <?php echo json_encode($selectedStartDate); ?>;
      var selectedEndDate = <?php echo json_encode($selectedEndDate); ?>;
      $('input[name="endDate"]').datetimepicker({
        dateFormat: "yy-mm-dd",
        timeFormat: "hh:mm:ss",
        showSecond: true,
        minDate: 0 - 6,
        maxDate: new Date,
      });
      $('input[name="endDate"]').datetimepicker("setDate", selectedEndDate);
      $('input[name="startDate"]').datetimepicker({
        dateFormat: "yy-mm-dd",
        timeFormat: "hh:mm:ss",
        showSecond: true,
        minDate: 0 - 6,
        maxDate: new Date,
        onSelect: function() {
          var datearray = $('input[name="startDate"]').val().split("-");
          var year = datearray[0];
          var month = datearray[1];
          var day = datearray[2];
          var minDateNow = (year + "-" + month + "-" + day);
          $('input[name="endDate"]').val("");
          $('input[name="endDate"]').datetimepicker('option', 'minDate', minDateNow);
        }
      });
      $('input[name="startDate"]').datetimepicker("setDate", selectedStartDate);
      var charts = [];
      var resultset = <?php echo json_encode($readingArray); ?>;
      if (resultset == null) {
        resultset = 0;
      }
      var graphResultSet = new Array();
      for (var i = 0; i < resultset.length; i++) {
        var graphFormat = new Array();
        graphFormat.push(resultset[i][0][0]);
        graphFormat.push(resultset[i][0][4]);
        if (resultset[i][0][1] == 0) {
          graphFormat.push("Humidity");
        } else {
          graphFormat.push("Temperature");
        }
        var graphArray = new Array();
        if (graphFormat[2] == "Temperature") {
          for (var j = 0; j < resultset[i].length; j++) {
            graphArray.push(new Array(new Date(resultset[i][j][3].toString().split(",")[0].replace(" ", "T")), parseFloat(resultset[i][j][1]), parseFloat(resultset[i][j][5])));
          }
        } else {
          for (var j = 0; j < resultset[i].length; j++) {
            graphArray.push(new Array(new Date(resultset[i][j][3].toString().split(",")[0].replace(" ", "T")), parseFloat(resultset[i][j][2]), parseFloat(resultset[i][j][6])));
          }
        }
        graphFormat.push(graphArray);
        graphResultSet.push(graphFormat);
      }





      for (var i = 0; i < graphResultSet.length; i++) {
        g = new Dygraph(
          document.getElementById(i),

          graphResultSet[i][3], {
            labels: ["Date", graphResultSet[i][2], "Threshold"],
            title: "DeviceID : " + graphResultSet[i][0] + " Label : " + graphResultSet[i][1],


          }
        );

      }



     
    });
  </script>
</head>

<body>
  <?php include('header.inc.php'); ?>
  <div class="Historical Graph">
    <?php
    include("sidebar.inc.php");

    echo '<div class="main">
<div class="center"><div id="historicalgraph">
  <form method="POST">
    <div class="left">
      <table id = "crit_busc">
        <tr>
          <th>
            <div>', __("Start Date"), '</div>
            <div><input type="text" name="startDate" id="startDate" size=15 value="', date('Y-m-d H:m:s', time()), '"></div>
          </th>
          <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
          <th>
            <div>', __("End Date"), '</div>
            <div><input type="text" name="endDate" id="endDate" size=15 value="', date('Y-m-d H:m:s', time()), '"></div>
          </th>
          <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
          <th>
            <div>
            <div><label for="DataCenterID">', __("DataCenterID"), '</label></div>
            <div><select name="DataCenterID" id="DataCenterID">';

    foreach ($dataCenterList as $dataCenterRow) {
      $selected = ($_REQUEST["DataCenterID"] == $dataCenterRow[0]) ? ' selected' : '';
      print "<option value=$dataCenterRow[0] $selected>" .  $dataCenterRow[1] . "</option>\n";
    }

    echo '	
            </div>
          </th>
          <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
          <th>
            <br><button type="submit" name="action" value="Submit">', __("Submit"), '</button>
          </th>
          </tr>
          </table>
</div>';

    if (sizeOf($readingArray) == 0 && $_POST['action'] == 'Submit') {
      echo '<H1>No Sensors Yet Or Wrong SNMP Configurations</H1>';
    }

    for ($i = 0; $i < sizeOf($readingArray); $i++) {
      echo "<div id=\"$i\" style=\"width:1250px; height:500px;\" ></div>";
    }




    ?>

  </div>
  </div>

  </div>

  </div>


</body>

</html>