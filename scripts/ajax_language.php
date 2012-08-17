<?php
if(isset($_POST['sl'])){
	//set cookie to contents of $_POST['sl']
	setcookie("lang", $_POST['sl'], time()+31536000, '/'); // set cookie expiration for one year
}
?>
