<?php

require_once( "version.php" );

// Make sure that a db.inc.php has been created
	if(!file_exists("db.inc.php")){
		print "Please copy db.inc.php-dist to db.inc.php.<br>\nOpen db.inc.php with a text editor and fill in the blanks for user, pass, database, and server.";
		exit;
	}else{
		require_once("db.inc.php");
	}


// See if the database is empty - if so, simply create a new one, otherwise, run the upgrade
$upgrade=false;

$result=$dbh->prepare("SHOW TABLES;");
$result->execute();
if($result->rowCount()==0){ // No tables in the DB so try to install.
	error_log("Detected fresh installation.   Creating database tables.");
	require_once( "classes/People.class.php" );
	$results[]=applyupdate("create.sql");
	$person = new People();
	$person->UserID=$initialAdminUser;
	$person->SiteAdmin=true;
	$person->WriteAccess=true;
	$person->ReadAccess=true;
	$person->ContactAdmin=true;
	$person->LastName='Administrator';
	$person->FirstName='Emergency';
	$person->CreatePerson();

	header('Location: index.php');
}

// Functions for upgrade / installing db objects
$successlog="";
	
function applyupdate ($updatefile){
	global $dbh;

	//Make sure the upgrade file exists.
	if(file_exists($updatefile)){
		$file=fopen($updatefile, 'r');
		$sql=array();
		while(feof($file)===false){
			$sql[]=fgets($file);
		}
		$sqlstring="";
		foreach($sql as $key => $value){
			// I really need a better way to filter out comments but this works.
			if(substr($value,0,1)=='-'){
			}else{
				$sqlstring.=trim($value);
			}
		}
		fclose($file);
		$sql=explode(";",$sqlstring);
		unset($sql[count($sql)-1]);
		$result=0;
		$errormsg = "";
		foreach($sql as $key => $value){
// uncomment to debug sql injection
//			echo $value."<br>\n";
			if(!$dbh->query($value)){
				$info=$dbh->errorInfo();
				//something broke log it
				$errormsg.="$value<br>\n";
				$errormsg.=$info[2];
				$errormsg.="<br>\n";
				$result=1;
			}
		}
		if($result){
			if(!isset($errormsg)){
				$errormsg="An error has occured while applying $updatefile. Please consult the server logs for more details.<br>\n";
			}
		}else{
			$successlog="$updatefile: Database updates applied.<br>\n";
		}
	}else{
		$errormsg="An update has been unpacked to the openDCIM installation but the database update &quot;$updatefile&quot; is missing.<br><br>\nPlease unpack the archive and try again.";
	}
	$temp=array();
	if(isset($errormsg)){
		$temp[1]=$errormsg;
	}else{
		$temp[0]=$successlog;
	}

	$message=(isset($errormsg))?$errormsg:$successlog;
	$class=(isset($errormsg))?'error':'success';
	print "<h1 class=\"$class\">$message</h1>";

	return $temp;
}

/* Generic html sanitization routine */

function sanitize($string,$stripall=true){
	// Trim any leading or trailing whitespace
	$clean=trim($string);

	// Convert any special characters to their normal parts
	$clean=html_entity_decode($clean,ENT_COMPAT,"UTF-8");

	// By default strip all html
	$allowedtags=($stripall)?'':'<a><b><i><img><u><br>';

	// Strip out the shit we don't allow
	$clean=strip_tags($clean, $allowedtags);
	// If we decide to strip double quotes instead of encoding them uncomment the 
	//	next line
//	$clean=($stripall)?str_replace('"','',$clean):$clean;
	// What is this gonna do ?
	$clean=filter_var($clean, FILTER_SANITIZE_SPECIAL_CHARS);

	// There shoudln't be anything left to escape but wtf do it anyway
	$clean=addslashes($clean);

	return $clean;
}

function ArraySearchRecursive($Needle,$Haystack,$NeedleKey="",$Strict=false,$Path=array()) {
	if(!is_array($Haystack))
		return false;
	foreach($Haystack as $Key => $Val) {
		if(is_array($Val)&&$SubPath=ArraySearchRecursive($Needle,$Val,$NeedleKey,$Strict,$Path)) {
			$Path=array_merge($Path,Array($Key),$SubPath);
			return $Path;
		}elseif((!$Strict&&$Val==$Needle&&$Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))||($Strict&&$Val===$Needle&&$Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))) {
			$Path[]=$Key;
			return $Path;
		}
	}
	return false;
}

	// New install so create a user
	require_once("classes/People.class.php");

	$person=new People();

	if(AUTHENTICATION=="Apache"){
		$person->UserID=$_SERVER['REMOTE_USER'];
	}elseif(AUTHENTICATION=="Oauth" || AUTHENTICATION=="LDAP" || AUTHENTICATION=="Saml"){
		if ( isset( $_SESSION['userid'] ) ) {
			$person->UserID=$_SESSION['userid'];
		}
	}

	/* Check the table to see if there are any users
	   defined, yet.  If not, this is a new install, so
	   create an admin user (all rights) as the current
	   user.  */

	$sql="SELECT COUNT(*) AS TotalUsers FROM fac_People;";
	$users=$dbh->query($sql)->fetchColumn();

//  test for openDCIM version
	$result=$dbh->prepare("SELECT Value FROM fac_Config WHERE Parameter='Version' LIMIT 1;");
	$result->execute();
	if($result->rowCount()==0){
		// We won't upgrade ancient versions with the container, so no point in trying to figure out if this was 1.0, 1.1, or 1.2
		$version="";
	}else{
		$version=$result->fetchColumn();//sets version number
	}

	if ( $version < "18.01" ) {
		echo '<html>
		<h1>Fatal error.</h1>
		<p>You are attempting to upgrade a prior installation that is not supported in the containerized version of
		openDCIM.   You must first upgrade your non-containerized installation to at least version 18.01, which is the minimum
		version supported for upgrading in the containerized distribution.</p>';
		exit;
	}

	// Check the detected version against the code version.  Update the current
	// code version at the top of this file each time we update.
	$upgrade=(VERSION!=$version)?true:false;

function upgrade(){
	global $version;
	global $config;
	global $results;
	global $dbh;
	global $errormsg;


	if($version=="18.01"){
		error_log("Applying database update from 18.01 to 18.02");
		$results[]=applyupdate("db-18.01-to-18.02.sql");

		$config->rebuild();
	}
	if($version=="18.02"){
		error_log("Applying database update from 18.02 to 19.01");
		$results[]=applyupdate("db-18.02-to-19.01.sql");

		$config->rebuild();
	}
	if($version=="19.01"){
		error_log("Applying database update from 19.01 to 20.01");
		$results[]=applyupdate("db-19.01-to-20.01.sql");

		$config->rebuild();
	}
}

	if($upgrade==true){ //If we're doing an upgrade don't call the rest of the installer.
?>
<!doctype html>
<html>
<head>
<title>Upgrade</title>
<style type="text/css">
.error { color: red;}
.success { color: green;}
</style>
</head>
<body>
<?php

upgrade();
 
if(isset($results)){
	date_default_timezone_set($config->ParameterArray['timezone']);

	$fh=fopen('install.err', 'a');
	fwrite($fh, date("Y-m-d g:i:s a\n"));
	foreach($results as $key => $value){
		foreach($value as $status => $message){
			if($status==1){$class="error";}else{$class="success";}
			fwrite($fh, $message);
		}
	}
	fclose($fh);
	print "<p>Anything shown here is just a notice.  It is not necessarily an error.  We will occasionally have to repeat database modifications that will fail and will show here. <b>This is behavior is to be expected</b>. Take note of any errors displayed in red then press F5 to reload this page until it goes to the configuration screen.</p>";
}else{
	echo '<meta http-equiv="refresh" content="0; url=index.php">';
}
?>
</body>
</html>


<?php
	exit;
	}
	require_once( "facilities.inc.php" );

	$dept=new Department();
	$dc=new DataCenter();
	$cab=new Cabinet();

	function BuildFileList(){
		$imageselect='<div id="preview"></div><div id="filelist">';
		$path='./images';
		$dir=scandir($path);
		foreach($dir as $i => $f){
			if(is_file($path.DIRECTORY_SEPARATOR.$f) && round(filesize($path.DIRECTORY_SEPARATOR.$f) / 1024, 2)>=4 && $f!="serverrack.png" && $f!="gradient.png"){
				$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
				if(preg_match('/^image/i', $imageinfo['mime'])){
					$imageselect.="<span>$f</span>\n";
				}
			}
		}
		$imageselect.="</div>";
		return $imageselect;
	}

// Departments Form Submission
	if(isset($_REQUEST['deptid'])&&($_REQUEST['deptid']>0)){
		$dept->DeptID = $_REQUEST['deptid'];
		$dept->GetDeptByID();
	}

	if(isset($_REQUEST['deptaction'])&& (($_REQUEST['deptaction']=='Create') || ($_REQUEST['deptaction']=='Update'))){
		$dept->DeptID = $_REQUEST['deptid'];
		$dept->Name = $_REQUEST['name'];
		$dept->ExecSponsor = $_REQUEST['execsponsor'];
		$dept->SDM = $_REQUEST['sdm'];
		$dept->Classification = $_REQUEST['classification'];

		if($_REQUEST['deptaction']=='Create'){
		  if($dept->Name != '' && $dept->Name != null)
			 $dept->CreateDepartment();
		}else{
			$dept->UpdateDepartment();
		}
	}
	$result=$dbh->prepare("SELECT * FROM fac_Department LIMIT 1;");
	$result->execute();
	if($result->rowCount()==0){ // No departments defined
		$nodept="<h3>Create a department</h3>";
		$nodeptdrop="readonly";
	}else{
		$nodept=$nodeptdrop="";
	}

// Data Centers Form Submission
	if(isset($_REQUEST['dcaction']) && (($_REQUEST['dcaction']=='Create')||($_REQUEST['dcaction']=='Update'))){
		$dc->DataCenterID = $_REQUEST['datacenterid'];
		$dc->Name = $_REQUEST['name'];
		$dc->SquareFootage = $_REQUEST['squarefootage'];
		$dc->DeliveryAddress = $_REQUEST['deliveryaddress'];
		$dc->Administrator = $_REQUEST['administrator'];
		$dc->DrawingFileName = $_REQUEST['drawingfilename'];
		
		if($_REQUEST['dcaction']=='Create'){
			if(!($dc->CreateDataCenter())) {
				$errormsg = "<h3>Datacenter not created, check the apache error log</h3>";
			}
		}else{
			$dc->UpdateDataCenter();
		}
	}

	if(isset($_REQUEST['datacenterid']) && $_REQUEST['datacenterid'] >0){
		$dc->DataCenterID=$_REQUEST['datacenterid'];
		$dc->GetDataCenter();
	}
	$dcList=$dc->GetDCList();
	$result=$dbh->prepare("SELECT * FROM fac_DataCenter LIMIT 1;");
	$result->execute();
	if($result->rowCount()==0){ // No data centers configured disable cabinets and complete options
		$nodc="<h3>Define a data center</h3>";
		$nodc.=(isset($errormsg))?$errormsg:"";
		$nodccab="<h3>You must create a Data Center before you can create cabinets in it.</h3>";
		$nodcfield="disabled";
		$nodcdrop="readonly";
	}else{
		$nodc=$nodccab=$nodcfield=$nodcdrop="";
	}

//Cabinet Form Submission
	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=$_REQUEST['cabinetid'];
		$cab->GetCabinet();
	}

	if(isset($_REQUEST['cabaction'])){
		if(($cab->CabinetID >0)&&($_REQUEST['cabaction']=='Update')){
			$cab->DataCenterID=$_REQUEST['datacenterid'];
			$cab->Location=$_REQUEST['location'];
			$cab->AssignedTo=$_REQUEST['assignedto'];
			$cab->CabinetHeight=$_REQUEST['cabinetheight'];
			$cab->Model=$_REQUEST['model'];
			$cab->MaxKW=$_REQUEST['maxkw'];
			$cab->MaxWeight=$_REQUEST['maxweight'];
			$cab->InstallationDate=$_REQUEST['installationdate'];
			$cab->UpdateCabinet();
		}elseif($_REQUEST['cabaction']=='Create'){
			$cab->DataCenterID=$_REQUEST['datacenterid'];
			$cab->Location=$_REQUEST['location'];
			$cab->AssignedTo=$_REQUEST['assignedto'];
			$cab->CabinetHeight=$_REQUEST['cabinetheight'];
			$cab->Model=$_REQUEST['model'];
			$cab->MaxKW=$_REQUEST['maxkw'];
			$cab->MaxWeight=$_REQUEST['maxweight'];
			$cab->InstallationDate=$_REQUEST['installationdate'];
			$cab->CreateCabinet();
		}
	}
	if($nodccab==""){ // only attempt to check for racks in the db if a data center has already been created
		$result=$dbh->prepare("SELECT * FROM fac_Cabinet LIMIT 1;");
		$result->execute();
		if($result->rowCount()==0){ // No racks defined disable complete option
			$nodccab="<h3>Create a rack for equipment to be housed in</h3>";
			$nocabdrop="readonly";
			$nocab="error";
		}else{
			$nocab=$nocabdrop=$nodccab="";
		}
	}

	if ( isset($_REQUEST['ldapaction']) && $_REQUEST['ldapaction'] == "Set" ) {
		Config::UpdateParameter( 'LDAPServer', $_REQUEST['LDAPServer']);
		Config::UpdateParameter( 'LDAPBaseDN', $_REQUEST['LDAPBaseDN']);
		Config::UpdateParameter( 'LDAPBindDN', $_REQUEST['LDAPBindDN']);
		Config::UpdateParameter( 'LDAPSessionExpiration', $_REQUEST['LDAPSessionExpiration'] );
		Config::UpdateParameter( 'LDAPSiteAccess', $_REQUEST['LDAPSiteAccess']);
		Config::UpdateParameter( 'LDAPReadAccess', $_REQUEST['LDAPReadAccess']);
		Config::UpdateParameter( 'LDAPWriteAccess', $_REQUEST['LDAPWriteAccess']);
		Config::UpdateParameter( 'LDAPDeleteAccess', $_REQUEST['LDAPDeleteAccess']);
		Config::UpdateParameter( 'LDAPAdminOwnDevices', $_REQUEST['LDAPAdminOwnDevices']);
		Config::UpdateParameter( 'LDAPRackRequest', $_REQUEST['LDAPRackRequest']);
		Config::UpdateParameter( 'LDAPRackAdmin', $_REQUEST['LDAPRackAdmin']);
		Config::UpdateParameter( 'LDAPContactAdmin', $_REQUEST['LDAPContactAdmin']);
		Config::UpdateParameter( 'LDAPSiteAdmin', $_REQUEST['LDAPSiteAdmin']);
	}

	
//Installation Complete
	error_log( "Installation complete.   Redirecting to main entrypoint.");
	header('Location: '.redirect("index.php"));
?>
