<?php
require_once( 'db.inc.php' );
require_once( 'facilities.inc.php' );
	
$type = $_POST['chooseType'];
$ID = $_POST['searchID'];

if($type == 'device'){
	echo $ID;
	header('Location: '.redirect("devices.php?DeviceID=$ID"));	
}

else if($type == 'cab'){
	header('Location: '.redirect('cabnavigator.php?cabinetid='.$ID.''));	
}
?>

