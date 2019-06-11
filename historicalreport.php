<?php
require_once("db.inc.php");
require_once("facilities.inc.php");

$subheader = __("Historical Report");

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
    $sensorReadings = $dev->GetHistoricalSensorReading($sensor[0], $startDate, $endDate);
    $readingArray[] = $sensorReadings;
  }
  $body = "";
  $body .= "<br><br><table id=\"export\" class=\"display\">\n\t<thead>\n\t\t<tr>\n
      \t<th rowspan=\"2\">" . __("TimeStamp") . "</th>";

  foreach ($readingArray as $readingRow) {
    $body .= "\t<th colspan=\"2\">" . "Label : " . $readingRow[0][4] . " DeviceID : " . $readingRow[0][0] . "</th>";
  }
  unset($readingRow);

  $body .= "</tr>\n<tr>\n";

  foreach ($readingArray as $readingRow) {
    if ($readingRow[0][1] == 0) {
      $body .= "\t<th>" . __("Humidity") . "</th>
          \t<th>" . __("Humidity Threshold") . "</th>
          ";
    } else {
      $body .= "\t<th>" . __("Temperature") . "</th>
          \t<th>" . __("Temperature Threshold") . "</th>
          ";
    }
  }

  unset($readingRow);

  $body .= "</tr>\n\t</thead>\n\t<tbody>\n";





  //confusing part, need to loop horizontally to get each row across with ALL devices. 


  for ($i = 0; $i < sizeOf($readingArray[0]); $i++) {
    $timestamp = $readingArray[0][$i][3];
    $body .= "\t\t<tr>
          \t<td>$timestamp</td>";
    for ($j = 0; $j < sizeOf($readingArray); $j++) {
      if ($readingArray[$j][$i][2] == 0) {
        $tempThreshold = $readingArray[$j][$i][5];
        if ($readingArray[$j][$i][1] >= $tempThreshold) {
          $temp = $readingArray[$j][$i][1];
          $read = "\t<td style=\"color:red;\">$temp</td>";
          $body .= "
              $read
              \t<td>$tempThreshold</td>";
        } else {
          $temp = $readingArray[$j][$i][1];
          $read = "\t<td>$temp</td>";
          $body .= "
              $read
              \t<td>$tempThreshold</td>";
        }
      } else {
        $humidThreshold = $readingArray[$j][$i][6];
        if ($readingArray[$j][$i][2] >= $humidThreshold) {
          $humid = $readingArray[$j][$i][2];
          $read = "\t<td style=\"color:red;\">$humid</td>";
          $body .= "
              $read
              \t<td>$humidThreshold</td>";
        } else {
          $humid = $readingArray[$j][$i][2];
          $read = "\t<td>$humid</td>";
          $body .= "
              $read
              \t<td>$humidThreshold</td>";
        }
      }
    }
    $body .= "
          \t\n\t\t</tr>\n";
  }





  unset($readingRow);

  $body .= "\t\t</tbody>\n\t</table>\n";

  if (isset($_REQUEST['ajax'])) {
    echo $body;
    exit;
  }
}


?>
<!doctype html>
<html>

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <title>Historical Report</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-te-1.4.0.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
  <style type="text/css"></style>
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript" src="scripts/jquery-te-1.4.0.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.textext.js"></script>
  <script type="text/javascript" src="scripts/common.js?v<?php echo filemtime('scripts/common.js'); ?>"></script>
  <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/pdfmake.min.js"></script>
  <script type="text/javascript" src="scripts/vfs_fonts.js"></script>




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
        maxDate: new Date
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
      var rows;

      function dt() {
        var title = $("option:selected", "#DataCenterID").text() + ' from ' + $("#startDate").val() + ' to ' + $("#endDate").val() + ' export'
        $('#export').dataTable({
          "iDisplayLength": 25,
          "drawCallback": function(settings) {
            redraw();
            resize();
          },
          dom: 'B<"clear">lfrtip',
          buttons: {
            buttons: [
              'copy',
              {
                extend: 'excel',
                title: title
              },
              {
                extend: 'pdf',
                title: title
              }, 'csv', 'colvis', 'print'
            ]
          }
        });
      }

      function redraw() {
        if (($('#export').outerWidth() + $('#sidebar').outerWidth() + 10) < $('.page').innerWidth()) {
          $('.main').width($('#header').innerWidth() - $('#sidebar').outerWidth() - 16);
        } else {
          $('.main').width($('#export').outerWidth() + 40);
        }
        $('.page').width($('.main').outerWidth() + $('#sidebar').outerWidth() + 10);
      }
      dt();
      $('Submit').click(function() {
        $.post('', {
          DeviceID: $('#DeviceID').val(),
          ajax: ''
        }, function(data) {
          $('#tablecontainer').html(data);
          dt();
        });
      });

    });
  </script>
</head>

<body>
  <?php
  include('header.inc.php');
  ?>
  <div class="page">
    <?php
    include('sidebar.inc.php');





















    //display the selectors in landscape 
    echo '<div class="main">
  <div  class="center" ><div id="historicalreport">
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
      </div>
      ';
    if (sizeOf($readingArray) == 0 && $_POST['action'] == 'Submit') {
      echo '<H1>No Sensors Yet Or Wrong SNMP Configurations</H1>';
    }
    ?>
    <div id="tablecontainer">
      <?php echo $body; ?>
    </div>

  </div>
  </div>
  <?php
  echo '
      </div>
    </div>';










  ?>





  <div class="clear"></div>





</body>

</html>