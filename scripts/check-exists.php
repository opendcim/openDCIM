<?php
require_once( "../db.inc.php" );
/*
UploadiFive
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
*/

// Define a destination
$validDir=array('pictures','drawings','images');
foreach($config->ParameterArray as $option => $value){
	if(preg_match('/path$/',$option)){
		array_push($validDir,$value);
	}
}
$targetFolder=(isset($_POST['dir']) && in_array($_POST['dir'], $validDir))?$_POST['dir']:'';
$targetFile=str_replace(' ','_',$_POST['filename']);

if (file_exists('..'.DIRECTORY_SEPARATOR.$targetFolder.DIRECTORY_SEPARATOR.$targetFile)) {
	echo 1;
} else {
	echo 0;
}
?>
