<?php
/*
UploadiFive
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
*/

// Define a destination
$validDir=array('pictures','drawings');
$targetFolder=(isset($_POST['dir']) && in_array($_POST['dir'], $validDir))?$_POST['dir']:'';

if (file_exists($_SERVER['DOCUMENT_ROOT'].((in_array(substr($_SERVER['DOCUMENT_ROOT'], -1), array('/',"\\")))?'':DIRECTORY_SEPARATOR).$targetFolder.DIRECTORY_SEPARATOR.$_POST['filename'])) {
	echo 1;
} else {
	echo 0;
}
?>
