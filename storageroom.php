<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("Storage Room Maintenance");

	if(!$person->ReadAccess){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$dev=new Device();

	if ( isset( $_POST["submit"]) && isset( $_POST["deviceid"]) ) {
		$dispID = $_POST["dispositionid"];
		$dList = Disposition::getDisposition( $dispID );
		if ( count( $dList ) == 1 ) {
			$devList = $_POST["deviceid"];

			foreach( $devList as $d ) {
				$dev->DeviceID = $d;
				$dev->GetDevice();
				$dev->Dispose( $dispID );
			}
		}
	}

	$dc=new DataCenter();

	$dList = Disposition::getDisposition();

	// Cabinet -1 is the Storage Area
	$dev->Cabinet=0;
	$dc->DataCenterID=isset($_REQUEST['dc'])?$_REQUEST['dc']:0;
	
	if ($dc->DataCenterID){
                $dev->Cabinet=$_GET['dc']*-1;
		$dc->DataCenterID=$_GET['dc'];
		$dc->GetDataCenter();
		$srname=sprintf(__("%s Storage Room"), $dc->Name." -");
	}else{
		$dev->Cabinet=0;
		$srname=__("General Storage Room");
	}
	$devList=$dev->ViewDevicesByCabinet(false,true);
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo __("Storage Room Maintenance");?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.dataTables.min.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="scripts/pdfmake.min.js"></script>
  
  <style>
      input[type="checkbox"]  { margin: 0 !important; }
      #dispositionid  { height: 24px; }
      #title { margin-bottom: 10px; }
      #export { text-align: center !important; }
      div > button { margin: 0px 3px 0px 3px; }
  </style>
  
  <script type="text/javascript">
	$(document).ready(function(){
            $('#export').dataTable({
                dom: 'B<"clear">lfrtip',
                buttons:{
                    buttons: [
                        'csv',
                        {
                            extend: 'excel',
                            title: title
                        },
                        {
                            extend: 'pdf',
                            title: title
                        }
                    ]
                }
            });
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page storage">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<?php echo '
<div class="center">
    <div>
        <form method="POST">
            <div class="table">
                <div class="title" id="title">',$srname,'</div>
                <div>
                    <table id="export">
                        <thead>
                            <tr>
                                <th>',__("Device"),'</th>
                                <th>',__("Asset Tag"),'</th>
                                <th>',__("Serial No."),'</th>
                                <th>',__("Dispose"),'</th>
                            </tr>
                        </thead>
                        <tbody>';
                        while(list($devID,$device)=each($devList)){
                            // filter the list of devices in storage rooms to only show the devices for this room
                            if(abs($device->Cabinet)==$dc->DataCenterID){
                                echo "<tr>
                                    <td><a href=\"devices.php?DeviceID=$device->DeviceID\">$device->Label</a></td>
                                    <td>$device->AssetTag</td>
                                    <td>$device->SerialNo</td>
                                    <td><input type=\"checkbox\" name=\"deviceid[]\" value=\"$device->DeviceID\"></td>
                                </tr>\n";
                            }
                        }            
echo '					</tbody>
					</table>
                </div>
                <hr>
                <div>
                    <button type="button" onclick="location.href=\'devices.php?action=new&dc='.$dc->DataCenterID.'\'">'.__("Add Device").'</button>
                    ', __("Dispose of selected devices to:"),'
                    <select name="dispositionid" id="dispositionid">';
                    foreach( $dList as $disp ) {
                        if ( $disp->Status == "Active" ) {
                            print "<option value=$disp->DispositionID>$disp->Name</option>";
                        }
                    }
echo '				</select>
                    <button type="submit" name="submit">OK</button>
                </div>
            </div><!-- END div.table -->';
?>
        </form>
    </div>
</div><!-- END div.center -->
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
