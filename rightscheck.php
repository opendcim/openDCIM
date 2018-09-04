<!doctype html>
<html>
<head>
<title>openDCIM rights check</title>
<style type="text/css">
.wanted { background-color: forestgreen; }
.warning { background-color: orange; }
table { background-color: azure; border-collapse: collapse; border: 1px solid black; display: inline-flex; }
td, th { padding: 0 8px; }
</style>
</head>
<body>

<?php

$userid=exec('id -u');
$grpid=exec('id -g');

// The directories we want writable for uploads
$wantedpaths=array('drawings', 'pictures','vendor'.DIRECTORY_SEPARATOR.'mpdf'.DIRECTORY_SEPARATOR.'mpdf'.DIRECTORY_SEPARATOR.'ttfontdata');

print "<table>
	<tr>
		<th>Directory</th>
		<th>Writable</th>
		<th colspan=2>Rights</th>
		<th>Owner:Group</th>
	</tr>";
function matches(&$check,$const){
	if($check==$const){
		$check="<font color=\"LimeGreen\">$check</font>";
	}
}

function printrow($file,&$wantedpaths,$userid,$grpid){
	$uploadDir=$file;
	$not=(is_writable('.'.DIRECTORY_SEPARATOR.$uploadDir))?'<font color="LimeGreen">Yes</font>':'<font color="red">No</font>';
	$perms = fileperms('.'.DIRECTORY_SEPARATOR.$uploadDir);

	if (($perms & 0xC000) == 0xC000) {
		// Socket
		$info = 's';
	} elseif (($perms & 0xA000) == 0xA000) {
		// Symbolic Link
		$info = 'l';
	} elseif (($perms & 0x8000) == 0x8000) {
		// Regular
		$info = '-';
	} elseif (($perms & 0x6000) == 0x6000) {
		// Block special
		$info = 'b';
	} elseif (($perms & 0x4000) == 0x4000) {
		// Directory
		$info = 'd';
	} elseif (($perms & 0x2000) == 0x2000) {
		// Character special
		$info = 'c';
	} elseif (($perms & 0x1000) == 0x1000) {
		// FIFO pipe
		$info = 'p';
	} else {
		// Unknown
		$info = 'u';
	}

	// Owner
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ?
			 (($perms & 0x0800) ? 's' : 'x' ) :
			 (($perms & 0x0800) ? 'S' : '-'));

	// Group
	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ?
			 (($perms & 0x0400) ? 's' : 'x' ) :
			 (($perms & 0x0400) ? 'S' : '-'));

	// World
	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ?
			 (($perms & 0x0200) ? 't' : 'x' ) :
			 (($perms & 0x0200) ? 'T' : '-'));

	$owner=fileowner($uploadDir);
	$group=filegroup($uploadDir);
	$perms=substr(sprintf('%o', $perms), -4);
	matches($owner,$userid);
	matches($group,$grpid);

	$class=(in_array($uploadDir,$wantedpaths))?' class="wanted"':'';
	$class=(preg_match('/LimeGreen/',$not) && !in_array($uploadDir,$wantedpaths))?' class="warning"':$class;

	print "\n\t<tr$class>\n\t\t<td>$uploadDir</td><td>$not</td><td>$info</td><td>$perms</td><td>$owner:$group</td></tr>";
}

$directory=".";
$scanned_directory = array_diff(scandir($directory), array('..', '.'));
foreach($scanned_directory as $i => $file){
	if(!is_dir($file)){
		continue;
	}

	printrow($file,$wantedpaths,$userid,$grpid);
}

# Add in extra paths here that aren't part of the root loop.
printrow('vendor'.DIRECTORY_SEPARATOR.'mpdf'.DIRECTORY_SEPARATOR.'mpdf'.DIRECTORY_SEPARATOR.'ttfontdata',$wantedpaths,$userid,$grpid);

# Handle paths that may or may not be set in the configuration screen for docker
# clowns.
if(file_exists("db.inc.php")){
	require_once("db.inc.php");
	foreach($config->ParameterArray as $option => $value){
		if(preg_match('/path$/',$option)){
			array_push($wantedpaths,$value);
			printrow($value,$wantedpaths,$userid,$grpid);
		}
	}
}

print "\n</table>
<table>
	<tr>
		<th>Legend</th>
	</tr>
	<tr class=\"wanted\"><td>Directory that should be writable</td></tr>
	<tr class=\"warning\"><td>Directory is writable and unexpected</td></tr>
	<tr><td>Normal and expected rights</td></tr>
</table>
<p>Script is being executed as owner: $userid group: $grpid</p>";

