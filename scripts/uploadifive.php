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
$validDir=array('pictures','drawings','images');
$uploadDir=(isset($_POST['dir']) && in_array($_POST['dir'], $validDir))?$_POST['dir']:'';

$status['status']=0;
$status['msg']='';

// Check for write permissions
if($uploadDir=='' || !is_writable('..'.DIRECTORY_SEPARATOR.$uploadDir)){
	$status['status']=1;
	$status['msg']=sprintf(__("Upload directory '%s' is not writable"),$uploadDir);
	echo json_encode($status);
	exit;
}
// Set the allowed file extensions
$fileTypes = array('jpg', 'jpeg', 'gif', 'png', 'svg'); // Allowed file extensions

$verifyToken = md5('unique_salt' . $_POST['timestamp']);

if ((!empty($_FILES) || isset($_POST['filename']) ) && $_POST['token'] == $verifyToken && ($person->WriteAccess || $person->SiteAdmin)) {
	$uploadDir  = '..'.DIRECTORY_SEPARATOR.$uploadDir;
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

		// Hide some debug info in the response
		$status['debug']=$_FILES;

		// Validate the filetype
		$fileParts=pathinfo($_FILES['Filedata']['name']);
		if(in_array(strtolower($fileParts['extension']), $fileTypes)){

			if($_FILES['Filedata']['error']==0){
				// Save the file
				move_uploaded_file($tempFile, $targetFile);
				// Verify the file was written out
				if(!file_exists($targetFile)){
					$status['status']=1;
					$status['msg']=__("Couldn't complete file move");
				}
			}else{
				$status['status']=1;
				switch ($_FILES['Filedata']['error']){
					case 1:
						$status['msg']=__("The file is bigger than this PHP installation allows");
						break;
					case 2:
						$status['msg']=__("The file is bigger than this form allows");
						break;
					case 3:
						$status['msg']=__("Only part of the file was uploaded");
						break;
					case 4:
						$status['msg']=__("No file was uploaded");
						break;
					case 6:
						$status['msg']=__("Missing a temporary folder");
						break;
					case 7:
						$status['msg']=__("Failed to write file to disk");
						break;
					case 8:
						$status['msg']=__("File upload stopped by extension");
						break;
					default:
						$status['msg']=__("Unknown Error");
						break;
				}
			}
		}else{
			// The file type wasn't allowed
			$status['status']=1;
			$status['msg']=__("Invalid file type.");
		}
	}
}else{
	// Token wasn't set or user doesn't have appropriate rights
	$status['status']=1;

	if(!empty($_FILES) || isset($_POST['filename'])){}else{$status['msg']=__("No files uploaded");}
	if($person->WriteAccess || $person->SiteAdmin){}else{$status['msg']=__("You must be a site admin to add images");}
	if($_POST['token']!=$verifyToken){$status['msg']=__("Token mismatch");}
	$status['msg']=($status['msg']=='')?__("God help us something has gone horribly wrong"):$status['msg'];
}

echo json_encode($status);
?>
