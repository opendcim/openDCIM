<?php
require_once("db.inc.php");
require_once("facilities.inc.php");

$subheader = __("Realtime Graph");

if (!$person->ReadAccess) {
  // No soup for you.
  header('Location: ' . redirect());
  exit;
}

date_default_timezone_set('Asia/Singapore');
$dev = new Device();
$dataCenterList = $dev->GetDataCenterList();



if ($_POST['action'] == 'Submit') {
  $sensorList = $dev->GetSensorListWithLabelThreshold($_REQUEST['DataCenterID']);
}

?>
<!doctype html>
<html>

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title>Realtime Graph</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/Chart.min.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
  <script type="text/javascript" src="scripts/moment.js"></script>
  <script type="text/javascript" src="scripts/Chart.min.js"></script>
  <script type="text/javascript" src="scripts/chartjs-plugin-streaming.js"></script>
  <script type="text/javascript" src="scripts/hammer.js"></script>
  <script type="text/javascript" src="scripts/chartjs-plugin-zoom.min.js"></script>
  <script type="text/javascript" src="scripts/chartjs-plugin-colorschemes.min.js"></script>




  <script type="text/javascript">
    //Jquery datepicker UI to make limit the options of dates. within history of 7 days and end date must be >= start date 
    $(document).ready(function() {

      var charts = [];
      var resultset = <?php echo json_encode($sensorList); ?>;
      if (resultset == null) {
        resultset = 0;
      }







      for (var i = 0; i < resultset.length; i++) {
        var ctx = document.getElementById(i);
        charts[i] = new Chart(ctx, {
          type: 'line', // 'line', 'bar', 'bubble' and 'scatter' types are supported
          data: {
            datasets: [{
              label: 'Actual Readings',
              data: [], // empty at the beginning
              fill: false,

            }, {
              label: 'Threshold',
              data: [],
              fill: false,
            }]
          },
          options: {
            responsive: false,
            title: {
              display: true,
              text: "DeviceID : " + resultset[i][0] + " Label : " + resultset[i][1] + " Threshold : " + resultset[i][2],
              deviceID: resultset[i][0],
              threshold: resultset[i][2],
            },
            scales: {
              xAxes: [{
                type: 'realtime', // x axis will auto-scroll from right to left
                realtime: { // per-axis options
                  duration: 20000, // data in the past 20000 ms will be displayed
                  refresh: 1000, // onRefresh callback will be called every 1000 ms
                  delay: 2000, // delay of 1000 ms, so upcoming values are known before plotting a line
                  pause: false, // chart is not paused
                  ttl: undefined,
                  // a callback to update datasets
                  onRefresh: function(chart) {

                    //var graphID = chart.options.title.text.split(":")[1].trim().split(" ")[0];
                    var graphID = chart.options.title.deviceID;
                    $.ajax({
                      url: "scripts/ajax_realtime.php", //the page containing php script
                      type: "POST", //request type,
                      dataType: "json",
                      data: {
                        'graphID': graphID
                      },
                      success: function(result) {
                        chart.options.scales.yAxes[0].ticks.suggestedMin = result[0] - 5;
                        chart.options.scales.yAxes[0].ticks.suggestedMax = result[0] + 5;
                        chart.data.datasets[0].data.push({
                          x: Date.now(),
                          y: result[0]
                        });
                        chart.data.datasets[1].data.push({
                          x: Date.now(),
                          //y: parseFloat(chart.options.title.text.split(":")[3].trim())
                          y: chart.options.title.threshold,
                        });


                        chart.update({
                          preservation: true
                        });

                      },

                    });

                  }
                }
              }],
              yAxes: [{
                ticks: {
                  suggestedMin: 10,
                  suggestedMax: 100
                }
              }]


            },
            plugins: {
              colorschemes: {
                scheme: 'brewer.Greys3',
                reverse: true,
              }
            }


          }
        });
      }



    });
  </script>
</head>

<body>
  <?php include('header.inc.php'); ?>
  <div class="Realtime Graph">
    <?php
    include("sidebar.inc.php");

    echo '<div class="main">
<div class="center"><div id="historicalgraph">
  <form method="POST">
    <div class="left">
      <table id = "crit_busc">
        <tr>
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
    if (sizeOf($sensorList) == 0 && $_POST['action'] == 'Submit') {
      echo '<H1>No Sensors Yet Or Wrong SNMP Configurations</H1>';
    }

    for ($i = 0; $i < sizeOf($sensorList); $i++) {
      echo "<canvas id=\"$i\" width=\"1250\"height=\"500\" ></canvas>";
    }


    ?>



  </div>
  </div>

  </div>

  </div>


</body>

</html>