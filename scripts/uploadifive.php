<?php
/*
UploadiFive
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
*/

// Set the uplaod directory
$validDir=array('pictures','drawings');
$uploadDir=(isset($_POST['dir']) && in_array($_POST['dir'], $validDir))?$_POST['dir']:'';

if($uploadDir=='' || !is_writable('..'.DIRECTORY_SEPARATOR.$uploadDir)){exit;}
// Set the allowed file extensions
$fileTypes = array('jpg', 'jpeg', 'gif', 'png'); // Allowed file extensions

$verifyToken = md5('unique_salt' . $_POST['timestamp']);

if (!empty($_FILES) && $_POST['token'] == $verifyToken && ($user->WriteAccess || $user->SiteAdmin)) {
	$tempFile   = $_FILES['Filedata']['tmp_name'];
	$uploadDir  = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
	$targetFile = $uploadDir.DIRECTORY_SEPARATOR.str_replace(' ','_',$_FILES['Filedata']['name']);

	// Validate the filetype
	$fileParts = pathinfo($_FILES['Filedata']['name']);
	if (in_array(strtolower($fileParts['extension']), $fileTypes)) {

		// Save the file
		move_uploaded_file($tempFile, $targetFile);
		// Verify the file was written out
		echo (file_exists($targetFile))?'1':'0';

	} else {

		// The file type wasn't allowed
		echo 'Invalid file type.';

	}
}else{
	echo '0';
}
?>
