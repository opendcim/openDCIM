<?php
	require_once( '../db.inc.php' );
	require_once( '../facilities.inc.php' );
	// Composer autoloader (adjust if vendor/ lives somewhere else)
	$uf_autoload = dirname(__FILE__).'/../vendor/autoload.php';
	if(!file_exists($uf_autoload)){
		header('Content-Type: application/json');
		echo json_encode(array('status' => 1, 'msg' => 'vendor/autoload.php not found - run composer install'));
		exit;
	}
	require_once( $uf_autoload );

use enshrined\svgSanitize\Sanitizer;

/*
UploadiFive
Copyright (c) 2012 Reactive Apps, Ronnie Garcia

Hardened: content-based image validation, GD re-encoding of raster images,
and allowlist-based SVG sanitizing via enshrined/svg-sanitize.
*/

// All output from this will be json
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Allowed extensions => expected MIME type (as detected from file contents)
$allowedTypes = array(
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'gif'  => 'image/gif',
	'png'  => 'image/png',
	'svg'  => 'image/svg+xml',
);

// Max SVG size we are willing to parse (bytes)
const MAX_SVG_BYTES = 1048576;

$status = array('status' => 0, 'msg' => '');

function uf_respond($status){
	echo json_encode($status);
	exit;
}

function uf_fail($msg){
	uf_respond(array('status' => 1, 'msg' => $msg));
}

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */

// Build a safe filename: basename only, whitelisted stem characters, single extension
function uf_safe_filename($origName, $allowedTypes){
	$base = basename(str_replace("\0", '', $origName));
	$ext  = strtolower(pathinfo($base, PATHINFO_EXTENSION));
	if(!isset($allowedTypes[$ext])){
		return false;
	}
	$stem = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($base, PATHINFO_FILENAME));
	$stem = trim($stem, '_');
	if($stem === ''){
		$stem = 'image';
	}
	return array($stem.'.'.$ext, $ext);
}

// Animated GIFs have more than one Graphic Control Extension block
function uf_is_animated_gif($path){
	$data = file_get_contents($path);
	return $data !== false && preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $data) > 1;
}

// Sanitize SVG with enshrined/svg-sanitize. Returns clean markup or false.
function uf_clean_svg($tmpPath){
	if(filesize($tmpPath) > MAX_SVG_BYTES){
		return false;
	}
	$dirty = file_get_contents($tmpPath);
	if($dirty === false || $dirty === ''){
		return false;
	}
	// Entity declarations are never needed in a logo/icon and enable entity-expansion attacks
	if(preg_match('/<!ENTITY/i', $dirty)){
		return false;
	}

	$sanitizer = new Sanitizer();
	$sanitizer->removeRemoteReferences(true);  // no external hrefs / url() fetches
	$sanitizer->removeXMLTag(true);            // drop the XML prolog
	$sanitizer->minify(true);

	$clean = $sanitizer->sanitize($dirty);
	if($clean === false || trim($clean) === ''){
		return false;
	}
	// Must still look like an SVG after sanitizing
	if(stripos($clean, '<svg') === false){
		return false;
	}
	return $clean;
}

// Validate a raster image by content, then write it to $target (re-encoded when possible).
// Returns true on success, or an error string.
function uf_save_raster($tmpPath, $ext, $expectedMime, $target){
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime  = $finfo->file($tmpPath);
	if($mime !== $expectedMime){
		return __("File content does not match its extension.");
	}
	$info = @getimagesize($tmpPath);
	if($info === false || $info['mime'] !== $mime){
		return __("File is not a valid image.");
	}

	// Re-encode with GD so anything that isn't pixel data (appended PHP, metadata payloads) is discarded
	$gd = function_exists('imagecreatefromjpeg');
	if($gd){
		switch($mime){
			case 'image/jpeg':
				$img = @imagecreatefromjpeg($tmpPath);
				$ok  = $img && imagejpeg($img, $target, 90);
				break;
			case 'image/png':
				$img = @imagecreatefrompng($tmpPath);
				if($img){
					imagealphablending($img, false);
					imagesavealpha($img, true);
				}
				$ok = $img && imagepng($img, $target);
				break;
			case 'image/gif':
				if(uf_is_animated_gif($tmpPath)){
					// Re-encoding would destroy the animation; fall through to a plain move
					return move_uploaded_file($tmpPath, $target) ? true : __("Couldn't complete file move");
				}
				$img = @imagecreatefromgif($tmpPath);
				$ok  = $img && imagegif($img, $target);
				break;
			default:
				return __("Invalid file type.");
		}
		if(isset($img) && $img){
			imagedestroy($img);
		}
		return $ok ? true : __("Couldn't complete file move");
	}

	// GD unavailable: fall back to moving the (validated) file
	return move_uploaded_file($tmpPath, $target) ? true : __("Couldn't complete file move");
}

/* ------------------------------------------------------------------ */
/* Authorization and token                                             */
/* ------------------------------------------------------------------ */

if(!($person->WriteAccess || $person->SiteAdmin)){
	uf_fail(__("You must be a site admin to add images"));
}

$timestamp   = isset($_POST['timestamp']) ? $_POST['timestamp'] : '';
$token       = isset($_POST['token']) ? $_POST['token'] : '';
$verifyToken = md5('unique_salt' . $timestamp);
if(!hash_equals($verifyToken, (string)$token)){
	uf_fail(__("Token mismatch"));
}

$isDelete = empty($_FILES) && isset($_POST['filename']);
if(empty($_FILES) && !$isDelete){
	uf_fail(__("No files uploaded"));
}

/* ------------------------------------------------------------------ */
/* Resolve the upload directory                                        */
/* ------------------------------------------------------------------ */

$validDir = array('pictures', 'drawings', 'images');
foreach($config->ParameterArray as $option => $value){
	if(preg_match('/path$/', $option)){
		array_push($validDir, $value);
	}
}

$postDir   = isset($_POST['dir']) ? $_POST['dir'] : '';
$uploadDir = in_array($postDir, $validDir, true) ? $postDir : '';

// Keywords are resolved from the config so a stale cached path in the page's
// javascript can't cause weird errors after the configuration changes.
if($uploadDir == 'drawings'){
	$uploadDir = $config->ParameterArray['drawingpath'];
}elseif($uploadDir == 'pictures'){
	$uploadDir = $config->ParameterArray['picturepath'];
}

// Allow any path under assets/, but only if it really resolves inside assets/
if($uploadDir == '' && $postDir !== '' && strpos($postDir, "\0") === false && strpos($postDir, 'assets/') === 0){
	$assetsRoot = realpath('..'.DIRECTORY_SEPARATOR.'assets');
	$candidate  = realpath('..'.DIRECTORY_SEPARATOR.$postDir);
	if($assetsRoot !== false && $candidate !== false
		&& strpos($candidate, $assetsRoot.DIRECTORY_SEPARATOR) === 0){
		$uploadDir = $postDir;
	}
}

if($uploadDir == '' || !is_writable('..'.DIRECTORY_SEPARATOR.$uploadDir)){
	uf_fail(sprintf(__("Upload directory '%s' is not writable"), $uploadDir));
}
$uploadDir = '..'.DIRECTORY_SEPARATOR.$uploadDir;

/* ------------------------------------------------------------------ */
/* Delete                                                              */
/* ------------------------------------------------------------------ */

if($isDelete){
	$name = basename(str_replace("\0", '', $_POST['filename']));
	$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	if($name === '' || !isset($allowedTypes[$ext])){
		uf_fail(__("Invalid file type."));
	}
	$targetFile = $uploadDir.DIRECTORY_SEPARATOR.$name;
	if(is_file($targetFile)){
		unlink($targetFile);
	}
	if(file_exists($targetFile)){
		uf_fail(__("File was not deleted"));
	}
	uf_respond($status);
}

/* ------------------------------------------------------------------ */
/* Upload                                                              */
/* ------------------------------------------------------------------ */

if(!isset($_FILES['Filedata'])){
	uf_fail(__("No file was uploaded"));
}

$file = $_FILES['Filedata'];

if($file['error'] != 0){
	switch($file['error']){
		case 1:  $msg = __("The file is bigger than this PHP installation allows"); break;
		case 2:  $msg = __("The file is bigger than this form allows"); break;
		case 3:  $msg = __("Only part of the file was uploaded"); break;
		case 4:  $msg = __("No file was uploaded"); break;
		case 6:  $msg = __("Missing a temporary folder"); break;
		case 7:  $msg = __("Failed to write file to disk"); break;
		case 8:  $msg = __("File upload stopped by extension"); break;
		default: $msg = __("Unknown Error"); break;
	}
	uf_fail($msg);
}

$tempFile = $file['tmp_name'];
if(!is_uploaded_file($tempFile)){
	uf_fail(__("Invalid upload"));
}

$parts = uf_safe_filename($file['name'], $allowedTypes);
if($parts === false){
	uf_fail(__("Invalid file type."));
}
list($safeName, $ext) = $parts;
$targetFile = $uploadDir.DIRECTORY_SEPARATOR.$safeName;

if($ext === 'svg'){
	$clean = uf_clean_svg($tempFile);
	if($clean === false){
		uf_fail(__("SVG could not be validated"));
	}
	if(file_put_contents($targetFile, $clean) === false){
		uf_fail(__("Couldn't complete file move"));
	}
}else{
	$result = uf_save_raster($tempFile, $ext, $allowedTypes[$ext], $targetFile);
	if($result !== true){
		uf_fail($result);
	}
}

// Verify the file was written out
if(!file_exists($targetFile)){
	uf_fail(__("Couldn't complete file move"));
}

uf_respond($status);
?>
