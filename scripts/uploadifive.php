<?php
	require_once( '../db.inc.php' );
	require_once( '../facilities.inc.php' );
/*
UploadiFive
Copyright (c) 2012 Reactive Apps, Ronnie Garcia
*/

// All output from this will be json
header('Content-Type: application/json');


// Set the uplaod directory
$validDir=array('pictures','drawings');
$uploadDir=(isset($_POST['dir']) && in_array($_POST['dir'], $validDir))?$_POST['dir']:'';

$status['status']=0;
$status['msg']='';

// Check for write permissions
if($uploadDir=='' || !is_writable('..'.DIRECTORY_SEPARATOR.$uploadDir)){
	$status['status']=1;
	$status['msg']=__("Upload directory is not writable");
	echo json_encode($status);
	exit;
}
// Set the allowed file extensions
$fileTypes = array('jpg', 'jpeg', 'gif', 'png'); // Allowed file extensions

$verifyToken = md5('unique_salt' . $_POST['timestamp']);

if ((!empty($_FILES) || isset($_POST['filename']) ) && $_POST['token'] == $verifyToken && ($user->WriteAccess || $user->SiteAdmin)) {
	$uploadDir  = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
	// if a filename is set then we're looking to remove it
	if(empty($_FILES)){
		if(!preg_match('/^(\.*)?(\/|\\\)/',$_POST['filename'])){	
			$targetFile=$uploadDir.DIRECTORY_SEPARATOR.$_POST['filename'];
			unlink($targetFile);
		}
		// Verify the file was removed
		if(file_exists($targetFile)){
			$status['status']=1;
			$status['msg']=__("File was not deleted");
		}
	}else{
		$tempFile   = $_FILES['Filedata']['tmp_name'];
		$targetFile = $uploadDir.DIRECTORY_SEPARATOR.str_replace(' ','_',$_FILES['Filedata']['name']);

		// Validate the filetype
		$fileParts=pathinfo($_FILES['Filedata']['name']);
		if(in_array(strtolower($fileParts['extension']), $fileTypes)){
			// Save the file
			move_uploaded_file($tempFile, $targetFile);
			// Verify the file was written out
			if(!file_exists($targetFile)){
				$status['status']=1;
				$status['msg']=__("Couldn't complete file move");
			}
		}else{
			// The file type wasn't allowed
			$status['status']=1;
			$status['msg']=__("Invalid file type.");
		}
	}
}

echo json_encode($status);
?>
