<?php

require_once( "preflight.php" );
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
	$results[]=applyupdate("create.sql");
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
		$person->UserID=$_SESSION['userid'];
	}

	/* Check the table to see if there are any users
	   defined, yet.  If not, this is a new install, so
	   create an admin user (all rights) as the current
	   user.  */

	$table=($usePeople)?'fac_People':'fac_User';	
	$sql="SELECT COUNT(*) AS TotalUsers FROM $table;";
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

	// Check the detected version against the code version.  Update the current
	// code version at the top of this file each time we update.
	$upgrade=($codeversion!=$version)?true:false;

function upgrade(){
	global $version;
	global $config;
	global $results;
	global $dbh;
	global $errormsg;


	if($version=="18.01"){
		$results[]=applyupdate("db-18.01-to-18.02.sql");

		$config->rebuild();
	}
	if($version=="18.02"){
		$results[]=applyupdate("db-18.02-to-19.01.sql");

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
	echo '<p class="success">All is well.  Please remove install.php to return to normal functionality</p>';
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
	if($nodept=="" && $nodc=="" && $nocab==""){ // All three primary sections have had at least one item created
		if(!isset($_REQUEST['complete']) && !isset($_REQUEST['dept']) && !isset($_REQUEST['cab']) && !isset($_REQUEST['dc']) && !isset($_REQUEST['ldap'])){
			header('Location: '.redirect("install.php?complete&preflight-ok"));
		}
		//enable the finish menu option
		$complete=true;
	}

?>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Installer</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.ui.multiselect.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
  <script type="text/javascript" src="scripts/jquery.ui.multiselect.js"></script>
  <script type="text/javascript">
	if(window.location.href.indexOf("preflight-ok") == -1){
		if(window.location.href.indexOf("?") == -1){
			window.location.href=window.location.href+'?preflight-ok';
		}else{
			window.location.href=window.location.href+'&preflight-ok';
		}
	}
	$(document).ready( function() {
		$("select:not('#tooltip, #cdutooltip')").each(function(){
			$(this).val($(this).attr('data'));
		});
	});

  </script>
</head>
<body>
<div id="header"></div>
<?php

	if((!isset($_GET["cab"])&&!isset($_GET["dc"])&&!isset($_GET["ldap"])&&!isset($_GET["complete"]))||isset($_GET["dept"])){
		$deptList = $dept->GetDepartmentList();
?>
<div class="page installer">

<div id="sidebar">
<ul>
<a><li class="active">Departments</li></a>
<a href="?dc&preflight-ok"><li>Data Centers</li></a>
<a href="?cab&preflight-ok"><li>Cabinets</li></a>
<?php if ( AUTHENTICATION == "LDAP" ) echo '<a href="?ldap&preflight-ok"><li>LDAP</li></a>'; ?>
<?php if(isset($complete)){ echo '<a href="?complete&preflight-ok"><li>Complete</li></a>'; }?>
</ul>
</div>

<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Department Detail</h3>
<?php echo $nodept; ?>
<div class="center"><div>
<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>?dept&preflight-ok" method="POST">
<div class="table centermargin">
<div>
   <div>Department</div>
   <div><input type="hidden" name="deptaction" value="query"><select name="deptid" onChange="form.submit()" <?php echo $nodeptdrop;  print "data=\"$dept->DeptID\""; ?>>
   <option value=0>New Department</option>
<?php
	foreach($deptList as $deptRow){
		echo "<option value=\"$deptRow->DeptID\"";
		if($dept->DeptID == $deptRow->DeptID){
			echo ' selected';
		}
		echo ">$deptRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="deptname">Department Name</label></div>
   <div><input type="text" size="50" name="name" id="deptname" value="<?php echo $dept->Name; ?>"></div>
</div>
<div>
   <div><label for="deptsponsor">Executive Sponsor</label></div>
   <div><input type="text" size="50" name="execsponsor" id="deptsponsor" value="<?php echo $dept->ExecSponsor; ?>"></div>
</div>
<div>
   <div><label for="deptmgr">Account Manager</label></div>
   <div><input type="text" size="50" name="sdm" id="deptmgr" value="<?php echo $dept->SDM; ?>"></div>
</div>
<div>
   <div><label for="deptclass">Classification</label></div>
   <div><select name="classification" id="deptclass" data="<?php echo $dept->Classification; ?>">
<?php
  foreach($config->ParameterArray['ClassList'] as $className){
	  echo "<option value=\"$className\"";
	  if($dept->Classification==$className){echo ' selected';}
      echo ">$className</option>";
  }
?>
    </select>
   </div>
</div>
<div class="caption" id="controls">
    <input type="submit" name="deptaction" value="Create">
<?php
	if($dept->DeptID > 0){
		echo '<input type="submit" name="deptaction" value="Update">';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<br>
</div></div>
</div> <!-- END div.main -->
</div> <!-- END div.page -->

<?php
	}elseif(isset($_GET["dc"])){
?>

<div class="page installer">

<div id="sidebar">
<ul>
<a href="?dept&preflight-ok"><li>Departments</li></a>
<a><li class="active">Data Centers</li></a>
<a href="?cab&preflight-ok"><li>Cabinets</li></a>
<?php if ( AUTHENTICATION == "LDAP" ) echo '<a href="?ldap&preflight-ok"><li>LDAP</li></a>'; ?>
<?php if(isset($complete)){ echo '<a href="?complete&preflight-ok"><li>Complete</li></a>'; }?>
</ul>
</div>

<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Detail</h3>
<?php echo $nodc; ?>
<div class="center"><div>
<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>?dc&preflight-ok" method="POST">
<div class="table">
<div>
   <div><label for="datacenterid">Data Center ID</label></div>
   <div><select name="datacenterid" id="datacenterid" onChange="form.submit()" <?php echo $nodcdrop; print "data=\"$dc->DataCenterID\""; ?>>
      <option value="0">New Data Center</option>
<?php
	foreach($dcList as $dcRow){
		echo "<option value=\"$dcRow->DataCenterID\"";
		if($dcRow->DataCenterID == $dc->DataCenterID){
			echo ' selected="selected"';
		}
		echo ">$dcRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="dcname">Name</label></div>
   <div><input type="text" name="name" id="dcname" size="50" value="<?php echo $dc->Name; ?>"></div>
</div>
<div>
   <div><label for="sqfootage">
   <?php if ($config->ParameterArray["mUnits"] == "english") {
		echo __("Square Feet");
	} else {
		echo __("Square Meters");
	}?></label></div>
   <div><input type="text" name="squarefootage" id="sqfootage" size="10" value="<?php echo $dc->SquareFootage; ?>"></div>
</div>
<div>
   <div><label for="deliveryaddress">Delivery Address</label></div>
   <div><input type="text" name="deliveryaddress" id="deliveryaddress" size="60" value="<?php echo $dc->DeliveryAddress; ?>"></div>
</div>
<div>
   <div><label for="administrator">Administrator</label></div>
   <div><input type="text" name="administrator" id="administrator" size=60 value="<?php echo $dc->Administrator; ?>"></div>
</div>
<div>
   <div><label for="drawingfilename">Drawing URL</label></div>
   <div><input type="text" name="drawingfilename" id="drawingfilename" size=60 value="<?php echo $dc->DrawingFileName; ?>"></div>
</div>
<div class="caption">
<?php
	if($dc->DataCenterID >0){
		echo '   <input type="submit" name="dcaction" value="Update">';
	}else{
		echo '   <input type="submit" name="dcaction" value="Create">';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->

<?php
	}elseif(isset($_GET["cab"])){
		if($cab->CabinetID >0){
			$cab->GetCabinet();
		}else{
			$cab->CabinetID=null;
			$cab->DataCenterID=null;
			$cab->Location=null;
			$cab->CabinetHeight=null;
			$cab->Model=null;
			$cab->MaxKW=null;
			$cab->MaxWeight=null;
			$cab->InstallationDate=date('m/d/Y');
		}

		$deptList=$dept->GetDepartmentList();
		$cabList=$cab->ListCabinets();
?>

<div class='page installer'>
<div id="sidebar">
<ul>
<a href="?dept&preflight-ok"><li>Departments</li></a>
<a href="?dc&preflight-ok"><li>Data Centers</li></a>
<a><li class="active">Cabinets</li></a>
<?php if ( AUTHENTICATION == "LDAP" ) echo '<a href="?ldap&preflight-ok"><li>LDAP</li></a>'; ?>
<?php if(isset($complete)){ echo '<a href="?complete&preflight-ok"><li>Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Cabinet Inventory</h3>
<?php echo $nodccab; ?>
<div class='center'><div>
<form action='<?php echo $_SERVER['SCRIPT_NAME']; ?>?cab&preflight-ok' method='POST'>
<?php echo '
<div class="table">
<div>
   <div>Cabinet</div>
   <div><select name="cabinetid" onChange="form.submit()" '.$nodcdrop.' data='.$cab->CabinetID.'>
   <option value=0>New Cabinet</option>
';
	foreach($cabList as $cabRow){
		$selected=($cabRow->CabinetID==$cab->CabinetID)?' selected':'';
		print "\t\t\t<option value=$cabRow->CabinetID>$cabRow->Location</option>\n";
	}
echo '
   </select></div>
</div>
<div>
   <div>Data Center</div>
   <div>
		<select name="datacenterid" id="datacenterid" data='.$cab->DataCenterID.'>
';
	foreach(DataCenter::GetDCList() as $dc){
		$selected=($dc->DataCenterID==$cab->DataCenterID)?' selected':'';
		print "\t\t\t<option value=\"$dc->DataCenterID\"$selected>$dc->Name</option>\n";
	}
echo '
		</select>
	</div>
</div>
<div>
   <div>Location</div>
   <div><input type="text" name="location" size=8 value="'.$cab->Location.'" '.$nodcfield.'></div>
</div>
<div>
  <div>Assigned To:</div>
  <div><select name="assignedto" '.$nodcdrop.' data='.$cab->AssignedTo.'>
    <option value=0>General Use</option>
';
	foreach($deptList as $deptRow){
		$selected=($deptRow->DeptID==$cab->AssignedTo)?' selected':'';
		print "\t\t\t<option value=$deptRow->DeptID$selected>$deptRow->Name</option>\n";
	}
echo '
  </select>
  </div>
</div>
<div>
   <div>Cabinet Height (U)</div>
   <div><input type="text" name="cabinetheight" size=4 value='.$cab->CabinetHeight.' '.$nodcfield.'></div>
</div>
<div>
   <div>Model</div>
   <div><input type="text" name="model" size=30 value="'.$cab->Model.'" '.$nodcfield.'></div>
</div>
<div>
   <div>Maximum kW</div>
   <div><input type="text" name="maxkw" size=30 value='.$cab->MaxKW.' '.$nodcfield.'></div>
</div>
<div>
   <div>Maximum Weight</div>
   <div><input type="text" name="maxweight" size=30 value='.$cab->MaxWeight.' '.$nodcfield.'></div>
</div>
<div>
   <div>Date of Installation</div>
   <div><input type="text" name="installationdate" size=15 value="'.date('m/d/Y', strtotime($cab->InstallationDate)).'" '.$nodcfield.'></div>
</div>
';
	if($nodcdrop==""){
		echo '<div class=\'caption\'>';
		if($cab->CabinetID >0){
			echo '   <input type=\'submit\' name=\'cabaction\' value=\'Update\'>';
		}else{
			echo '   <input type=\'submit\' name=\'cabaction\' value=\'Create\'>';
		}
		echo '</div>';
	}
?>
</div> <!-- END div.table -->
</form>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
<?php
	} elseif (isset($_GET['ldap'])) {

?>
<div class='page installer'>
<div id="sidebar">
<ul>
<a href="?dept&preflight-ok"><li>Departments</li></a>
<a href="?dc&preflight-ok"><li>Data Centers</li></a>
<a href="?cab&preflight-ok"><li>Cabinets</li></a>
<a><li class="active">LDAP</li></a>
<?php if(isset($complete)){ echo '<a href="?complete&preflight-ok"><li>Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Installation Complete</h3>
<?php echo $nodccab; ?>
<div class='center'><div>
<form action="<?php echo $_SERVER['SCRIPT_NAME']; ?>?ldap&preflight-ok" method="POST">
<?php
echo '<div id="ldap">
	<h3>',__("LDAP Authentication and Authorization Configuration"),'</h3>
	<div class="table">
		<div>
			<div><label for="LDAPServer">',__("LDAP Server URI"),'</label></div>
			<div><input type="text" size="40" defaultvalue="',$config->defaults["LDAPServer"],'" name="LDAPServer" value="',$config->ParameterArray['LDAPServer'],'"></div>
		</div>
		<div>
			<div><label for="LDAPBaseDN">',__("Base DN"),'</label></div>
			<div><input type="text" size="40" defaultvalue="',$config->defaults["LDAPBaseDN"],'" name="LDAPBaseDN" value="',$config->ParameterArray["LDAPBaseDN"],'"></div>
		</div>
		<div>
			<div><label for="LDAPBindDN">',__("Bind DN"),'</label></div>
			<div><input type="text" size="40" defaultvalue="',$config->defaults["LDAPBindDN"],'" name="LDAPBindDN" value="',$config->ParameterArray["LDAPBindDN"],'"></div>
		</div>
		<div>
			<div><label for="LDAPSessionExpiration">',__("LDAP Session Expiration (Seconds)"),'</label></div>
			<div><input type="text" defaultvalue="',$config->defaults["LDAPSessionExpiration"],'" name="LDAPSessionExpiration" value="',$config->ParameterArray["LDAPSessionExpiration"],'"></div>
		</div>
	</div>
	<h3>',__("Group Distinguished Names"),'</h3>
	<div class="table">
		<div>
			<div><label for="LDAPSiteAccess">',__("Site Access"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPSiteAccess"],'" name="LDAPSiteAccess" value="',$config->ParameterArray["LDAPSiteAccess"],'"></div>
		</div>
		<div>
			<div><label for="LDAPReadAccess">',__("Global Read"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPReadAccess"],'" name="LDAPReadAccess" value="',$config->ParameterArray["LDAPReadAccess"],'"></div>
		</div>
		<div>
			<div><label for="LDAPWriteAccess">',__("Global Write"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPWriteAccess"],'" name="LDAPWriteAccess" value="',$config->ParameterArray["LDAPWriteAccess"],'"></div>
		</div>
		<div>
			<div><label for="LDAPDeleteAccess">',__("Global Delete"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPDeleteAccess"],'" name="LDAPDeleteAccess" value="',$config->ParameterArray["LDAPDeleteAccess"],'"></div>
		</div>
		<div>
			<div><label for="LDAPAdminOwnDevices">',__("Admin Owned Devices"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPAdminOwnDevices"],'" name="LDAPAdminOwnDevices" value="',$config->ParameterArray["LDAPAdminOwnDevices"],'"></div>
		</div>
		<div>
			<div><label for="LDAPRackRequest">',__("Enter Rack Request"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPRackRequest"],'" name="LDAPRackRequest" value="',$config->ParameterArray["LDAPRackRequest"],'"></div>
		</div>
		<div>
			<div><label for="LDAPRackAdmin">',__("Complete Rack Request"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPRackAdmin"],'" name="LDAPRackAdmin" value="',$config->ParameterArray["LDAPRackAdmin"],'"></div>
		</div>
		<div>
			<div><label for="LDAPContactAdmin">',__("Contact Admin"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPContactAdmin"],'" name="LDAPContactAdmin" value="',$config->ParameterArray["LDAPContactAdmin"],'"></div>
		</div>
		<div>
			<div><label for="LDAPSiteAdmin">',__("Site Admin"),'</label></div>
			<div><input type="text" size="70" defaultvalue="',$config->defaults["LDAPSiteAdmin"],'" name="LDAPSiteAdmin" value="',$config->ParameterArray["LDAPSiteAdmin"],'"></div>
		</div>
	</div>
	<div>
		<div>
			<div><input type="submit" name="ldapaction" value="Set"></div>
		</div>
	</div>
';


	}elseif(isset($_GET["complete"])){
?>
<div class='page installer'>
<div id="sidebar">
<ul>
<a href="?dept&preflight-ok"><li>Departments</li></a>
<a href="?dc&preflight-ok"><li>Data Centers</li></a>
<a href="?cab&preflight-ok"><li>Cabinets</li></a>
<?php if ( AUTHENTICATION == "LDAP" ) echo '<a href="?ldap&preflight-ok"><li>LDAP</li></a>'; ?>
<?php if(isset($complete)){ echo '<a><li class="active">Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Installation Complete</h3>
<?php echo $nodccab; ?>
<div class='center'><div>

<p>You have completed the basic configuration for openDCIM.  At this time please <a href="http://www.opendcim.org/wiki/" title="openDCIM Wiki">go to the wiki</a> for additional questions that you might have or <a href="http://list.opendcim.org/listinfo.cgi/discussion-opendcim.org" title="openDCIM Mailing List">join our mailing list</a>.</p>
<p>To start normal operation of openDCIM please delete install.php from the installation directory.</p>
<p>Be sure to visit the <a href="configuration.php">Configuration</a> page to set any new defaults that may have been introduced.</p>

<h2>Online Repository</h2>
<p>If you wish to synchronize with the online repository, you must first pull the current listing of Manufacturer Names, which requires an active connection to the internet from your browser (not the server running openDCIM).  Go to Template Management -> Repository Sync and then choose Manufacturers, first.   Once you have synchronized manufacturer names, you may choose individual templates associated with those manufacturers to download into your local installation.</p>
</form>

</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->


<?php
	}
?>
<script type="text/javascript">
if (typeof jQuery == 'undefined') {
	alert('jQuery is not loaded');
	window.location.assign("http://opendcim.org/wiki/index.php?title=Errors:Operational");
}
if (typeof jQuery.ui == 'undefined') {
	alert('jQueryUI is not loaded');
	window.location.assign("http://opendcim.org/wiki/index.php?title=Errors:Operational");
}
function resize(){
	// page width is calcuated different between ie, chrome, and ff
	$('#header').width(Math.floor($(window).outerWidth()-(16*3))); //16px = 1em per side padding
	var widesttab=0;
	// make all the tabs on the config page the same width
	$('#configtabs > ul ~ div').each(function(){
		widesttab=($(this).width()>widesttab)?$(this).width():widesttab;
	});
	$('#configtabs > ul ~ div').each(function(){
		$(this).width(widesttab);
	});
	var pnw=$('#pandn').outerWidth(),hw=$('#header').outerWidth(),maindiv=$('div.main').outerWidth(),
		sbw=$('#sidebar').outerWidth(),width,mw=$('div.left').outerWidth()+$('div.right').outerWidth(),
		main;
	widesttab+=58;
	// find widths
	maindiv=(maindiv>mw)?maindiv:mw;
	main=(maindiv>pnw)?maindiv:pnw; // find largest possible value for maindiv
	main=(maindiv>widesttab)?maindiv:widesttab; // find largest possible value for maindiv
	width=((sbw+main)>hw)?sbw+main:hw; // which is bigger sidebar + main or the header

	// The math just isn't adding up across browsers and FUCK IE
	if((maindiv+sbw)<width){ // page is larger than content expand main to fit
		$('div.main').width(width-sbw-16); 
	}else{ // page is smaller than content expand the page to fit
		$('#header').width(width+4);
		$('div.page').width(width+6);
	}
}
$(document).ready(function(){
	// add some polish to the user experience, if a select box isn't set attempt
	// to set it to whatever the value for 0 is, new cabinet, new dc, etc
	$('select').each(function(key,element){
		if($(element).find('option:selected').length==0){
			element.value=0;
		}
	})
	resize();
	// redraw the screen if the window size changes for some reason
	$(window).resize(function(){
		if(this.resizeTO){ clearTimeout(this.resizeTO);}
		this.resizeTO=setTimeout(function(){
			resize();resize();
		}, 500);
	});
});
</script>
</body>
</html>
