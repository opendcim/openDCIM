<!doctype html>
<?php
/*
	Generic first time installer.  Makes assumption that the db.inc.php has been created

*/

// Pre-Flight check
	$tests=array();
	$errors=0;
	if (isset($_SERVER['REMOTE_USER'])) {
		$tests['Remote User']['state']="good";
		$tests['Remote User']['message']='';
	}
	else {
		$tests['Remote User']['state']="fail";
		$tests['Remote User']['message']='<a href="http://httpd.apache.org/docs/2.2/howto/auth.html">http://httpd.apache.org/docs/2.2/howto/auth.html</a>';
		$errors++;
	}

	if (extension_loaded('mbstring')) {
		$tests['mbstring']['state']="good";
		$tests['mbstring']['message']='';
	}
	else {
		$tests['mbstring']['state']="fail";
		$tests['mbstring']['message']='PHP is missing the <a href="http://php.net/mbstring">mbstring extension</a>';
		$errors++;
	}

	if(extension_loaded('gettext')) {
		$tests['gettext']['state']="good";
		$tests['gettext']['message']='';
	}else{
		$tests['gettext']['state']="fail";
		$tests['gettext']['message']='PHP is missing the <a href="http://php.net/manual/book.gettext.php">Gettext extension</a>. Please install it.';
	}

	$tests['pdo']['message']='';
	if (extension_loaded('PDO')) {
		$tests['pdo']['state']="good";
		if (count(PDO::getAvailableDrivers())>0) {
			$tests['pdodrivers']['message']='Available drivers: '.implode(", ",PDO::getAvailableDrivers());
			$tests['pdodrivers']['state']="good";
		}
		else {
			$tests['pdodrivers']['message']='Available drivers: none';
			$tests['pdodrivers']['state']="fail";
			$errors++;
		}
	}
	else {
		$tests['pdo']['state']="fail";
		$tests['pdo']['message']='openDCIM requires the <a href="http://php.net/manual/pdo.installation.php">PDO extention</a> and you do not appear to have it loaded';
		$tests['pdodrivers']['state']="fail";
		$tests['pdodrivers']['message']='No PDO drivers have been detected';
		$errors++;
	}

	if (function_exists('json_encode')) {
		$tests['json']['state']="good";
		$tests['json']['message']='PHP json module detected';
	}
	else {
		$tests['json']['state']="fail";
		$tests['json']['message']='PHP is missing the <a href="http://php.net/manual/book.json.php">JavaScript Object Notation (JSON) extension</a>.  Please install it.';
		$errors++;
	}

	if ($errors > 0) {
        echo '<!doctype html><html><head><title>openDCIM :: pre-flight environment sanity check</title><script type="text/javascript" src="scripts/jquery.min.js"></script><script type="text/javascript">$(document).ready(function(){$("tr").each(function(){if($(this).find("td:last-child").text()=="fail"){$(this).addClass("fail");}});});</script><style type="text/css">table{width:80%;border-collapse:collapse;border:3px solid black;}th{text-align:left;text-transform:uppercase;border-right: 1px solid black;}th,td{padding:5px;}tr:nth-child(even){background-color:#d1e1f1;}td:last-child{text-align:center;text-transform:uppercase;border:2px solid;background-color:green;}.fail td:last-child{font-weight: bold;background-color: red;}</style></head><body><h2>Pre-flight environment checks</h2><table>';
		foreach($tests as $test => $text){
			print "<tr><th>$test</th><td>{$text['message']}</td><td>{$text['state']}</td></tr>";
		}
		echo '<tr><th>javascript</th><td>Javascript is used heavily for data validation and a more polished user experience.</td><td><script>document.write("good")</script><noscript>fail</noscript></td></tr>
			</table>
		<p>If you are seeing this page then you must correct any issues shown above before the installer will continue.</p>

		</body></html>';
		exit;
	}

// Make sure that a db.inc.php has been created
	if(!file_exists("db.inc.php")){
		print "Please copy db.inc.php-dist to db.inc.php.<br>\nOpen db.inc.php with a text editor and fill in the blanks for user, pass, database, and server.";
		exit;
	}else{
		require_once("db.inc.php");
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
		$errormsg="Seems you're at 1.0 but you're missing the db updates to goto 1.1. Are you sure that db-1.0-to-1.1.sql unpacked from the archive?";
	}
	$temp=array();
	if(isset($errormsg)){
		$temp[1]=$errormsg;
	}else{
		$temp[0]=$successlog;
	}
	return $temp;
}
	$upgrade=false;

// Check to see if we are doing an upgrade or an install
	$result=$dbh->prepare("SHOW TABLES;");
	$result->execute();
	if($result->rowCount()==0){ // No tables in the DB so try to install.
		$results[]=applyupdate("create.sql");
		$upgrade=false;
	}
	// New install so create a user
	require_once("customers.inc.php");

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights();

	// Re-read the config
	$config->Config();
// Check to see if we have any users in the database.
	$sth=$dbh->prepare("SELECT * FROM fac_User WHERE SiteAdmin=1;");
	$sth->execute();
	if($sth->rowCount()<1){
		// no users in the system or no users with site admin rights, either way we're missing the class of people we need
		// put stuff here like correcting for a missing site admin
		print "There are no users in the database with sufficient privileges to perform this update";
		exit;
		$rightserror=1;
	}else{ // so we have users and at least one site admin
		require_once("customers.inc.php");

		$user=new User();
		$user->UserID=$_SERVER['REMOTE_USER'];
		$user->GetUserRights();

		if(!$user->SiteAdmin){
			// dolemite says you aren't an admin so you can't apply the update
			print "An update has been applied to the system but the system hasn't been taken out of maintenance mode. Please contact a site Administrator to correct this issue.";
			exit;
		}
		$rightserror=0;
	}

//  test for openDCIM version
	$result=$dbh->prepare("SELECT Value FROM fac_Config WHERE Parameter='Version' LIMIT 1;");
	$result->execute();
	if($result->rowCount()==0){// Empty result set means this is either 1.0 or 1.1. Surely the check above caught all 1.0 instances.
		$results[]=applyupdate("db-1.1-to-1.2.sql");
		$upgrade=true;
		$version="1.2";
	}else{
		$version=$result->fetchColumn();//sets version number
	}
	if($version==""){ // something borked re-apply all database updates and pray for forgiveness
		$version="1.2";
	}
	if($version=="1.2"){ // Do 1.2 to 1.3 Update
		$results[]=applyupdate("db-1.2-to-1.3.sql");
		$upgrade=true;
		$version="1.3";
	}
	if($version=="1.3"){ // Do 1.3 to 1.4 Update
		// Clean the configuration table of any duplicate values that might have been added.
		$config->rebuild();
		$results[]=applyupdate("db-1.3-to-1.4.sql");
		$upgrade=true;
		$version="1.4";
	}
	if($version=="1.4"){ // Do 1.4 to 1.5 Update
		// A few of the database changes require some tests to ensure that they will be able to apply.
		// Both of these need to return 0 results before we continue or the database schema update will not complete.
		$conflicts=0;
		$sql="SELECT PDUID, CONCAT(PDUID,'-',PDUPosition) AS KEY1, COUNT(PDUID) AS Count  FROM fac_PowerConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY PDUID ASC;";
		$sth=$dbh->prepare($sql);$sth->execute();
		$conflicts+=($sth->rowCount()>0)?1:0;
		$sql="SELECT DeviceID, CONCAT(DeviceID,'-',DeviceConnNumber) AS KEY2, COUNT(DeviceID) AS Count FROM fac_PowerConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY DeviceID ASC;";
		$sth=$dbh->prepare($sql);$sth->execute();
		$conflicts+=($sth->rowCount()>0)?1:0;
		$sql="SELECT SwitchDeviceID, CONCAT(SwitchDeviceID,'-',SwitchPortNumber) AS KEY1, COUNT(SwitchDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY SwitchDeviceID ASC;";
		$sth=$dbh->prepare($sql);$sth->execute();
		$conflicts+=($sth->rowCount()>0)?1:0;
		$sql="SELECT SwitchDeviceID, SwitchPortNumber, EndpointDeviceID, EndpointPort, CONCAT(EndpointDeviceID,'-',EndpointPort) AS KEY2, COUNT(EndpointDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY EndpointDeviceID ASC;";
		$sth=$dbh->prepare($sql);$sth->execute();
		$conflicts+=($sth->rowCount()>0)?1:0;

		require_once("facilities.inc.php");
		if($conflicts!=0){
			header('Location: '.redirect("conflicts.php"));
			exit;
		}

		$config->rebuild();
		$results[]=applyupdate("db-1.4-to-1.5.sql");
		$upgrade=true;
		$version="1.5";
	}
	
	if($version=="1.5"){	// Do the 1.5 to 2.0 Update
		// Get a list of all Manufacturers that are duplicated
		$sql="SELECT ManufacturerID,Name FROM fac_Manufacturer GROUP BY Name HAVING COUNT(*)>1;";
		foreach($dbh->query($sql) as $row){
			// Set all devices with that Manufacturer to the ID of just one
			$sql="UPDATE fac_DeviceTemplate SET ManufacturerID={$row["ManufacturerID"]} WHERE ManufacturerID IN (SELECT ManufacturerID FROM fac_Manufacturer WHERE Name=\"{$row["Name"]}\");";
			$dbh->query($sql);
			
			// Delete all the duplicates other than the one you set everything to
			$sql="DELETE FROM fac_Manufacturer WHERE Name=\"{$row["Name"]}\" and ManufacturerID!={$row["ManufacturerID"]};";
			$dbh->query($sql);
		}
		
		// Repeat for Templates
		$sql="SELECT TemplateID,ManufacturerID,Model FROM fac_DeviceTemplate GROUP BY ManufacturerID,Model HAVING COUNT(*)>1;";
		foreach($dbh->query($sql) as $row){
			$sql="UPDATE fac_Device SET TemplateID={$row["TemplateID"]} WHERE TemplateID IN (SELECT TemplateID FROM fac_DeviceTemplate WHERE ManufacturerID={$row["ManufacturerID"]} AND Model=\"{$row["Model"]}\");";
			$dbh->query($sql);
			
			$sql="DELETE FROM fac_DeviceTemplate WHERE ManufacturerID={$row["ManufacturerID"]} AND TemplateID!={$row["TemplateID"]};";
			$dbh->query($sql);
		}
		
		// And finally, Departments
		$sql="SELECT DeptID, Name FROM fac_Department GROUP BY Name HAVING COUNT(*)>1;";
		foreach($dbh->query($sql) as $row){
			$sql="UPDATE fac_Device SET Owner={$row["DeptID"]} WHERE Owner IN (SELECT DeptID FROM fac_Department WHERE Name=\"{$row["Name"]}\");";
			$dbh->query($sql);
			
			// Yes, I know, this may create duplicates
			$sql="UPDATE fac_DeptContacts SET DeptID={$row["DeptID"]} WHERE DeptID IN (SELECT DeptID FROM fac_Department WHERE Name=\"{$row["Name"]}\");";
			$dbh->query($sql);
			
			$sql="DELETE FROM fac_Department WHERE Name=\"{$row["Name"]}\" AND DeptID!={$row["DeptID"]};";
			$dbh->query($sql);
		}
		
		// So delete the potential duplicate contact links created in the last step
		$sql="SELECT DeptID,ContactID FROM fac_DeptContacts GROUP BY DeptID,ContactID HAVING COUNT(*)>1;";

		foreach($dbh->query($sql) as $row){	
			$sql="DELETE FROM fac_DeptContacts WHERE DeptID={$row["DeptID"]} AND ContactID={$row["ContactID"]};";
			$dbh->query($sql);
			
			$sql="INSERT INTO fac_DeptContacts VALUES ({$row["DeptID"]},{$row["ContactID"]});";
			$dbh->query($sql);
		}
		
		 /* 
		 /  Clean up multiple key issues.
		 /
		 /	1. Identify Multiple Keys
		 /	2. Remove them
		 /	3. Recreate keys based on structure in create.sql
		 /
		*/
		$array=array();
		$sql="SHOW INDEXES FROM fac_PowerConnection;";
		foreach($dbh->query($sql) as $row){
			$array[$row["Key_name"]]=1;
		}
		foreach($array as $key => $garbage){
			$sql="ALTER TABLE fac_PowerConnection DROP INDEX $key;";
			$dbh->query($sql);
		}
		$sql="ALTER TABLE fac_PowerConnection ADD UNIQUE KEY PDUID (PDUID,PDUPosition);";
		$dbh->query($sql);
		$sql="ALTER TABLE fac_PowerConnection ADD UNIQUE KEY DeviceID (DeviceID,DeviceConnNumber);";
		$dbh->query($sql);

		// Just removing keys from fac_CabinetAudit
		$array=array();
		$sql="SHOW INDEXES FROM fac_CabinetAudit;";
		foreach($dbh->query($sql) as $row){
			$array[$row["Key_name"]]=1;
		}
		foreach($array as $key => $garbage){
			$sql="ALTER TABLE fac_CabinetAudit DROP INDEX $key;";
			$dbh->query($sql);
		}

		$array=array();
		$sql="SHOW INDEXES FROM fac_Department;";
		foreach($dbh->query($sql) as $row){
			$array[$row["Key_name"]]=1;
		}
		foreach($array as $key => $garbage){
			$sql="ALTER TABLE fac_Department DROP INDEX $key;";
			$dbh->query($sql);
		}
		$sql="ALTER TABLE fac_Department ADD PRIMARY KEY (DeptID);";
		$dbh->query($sql);
		$sql="ALTER TABLE fac_Department ADD UNIQUE KEY Name (Name);";
		$dbh->query($sql);
		
		$config->rebuild();
		$results[]=applyupdate("db-1.5-to-2.0.sql");
		$upgrade=true;
		$version="2.0";
	}
	
	if($version=="2.0"){
		$sql="select InputAmperage from fac_PowerDistribution limit 1";
		// See if the field exists - some people have manually added the missing one already, so we can't add what's already there
		if(!$dbh->query($sql)){
//		if(mysql_errno($facDB)==1054){
			$sql="ALTER TABLE fac_PowerDistribution ADD COLUMN InputAmperage INT(11) NOT NULL AFTER PanelPole";
			$dbh->query($sql);
		}

		$sql='UPDATE fac_Config SET Value="2.0.1" WHERE Parameter="Version"';
		$dbh->query($sql);
		
		$upgrade=true;
		$version="2.0.1";
	}
	// Change this to 2.0.1 when we're ready for release. This will break the holy hell out of things currently
	if($version=="2.0.1"){
		// Get a list of all Manufacturers that are duplicated
		$sql="SELECT ManufacturerID,Name FROM fac_Manufacturer GROUP BY Name HAVING COUNT(*)>1;";
		
		foreach($dbh->query($sql) as $row){
			// Set all devices with that Manufacturer to the ID of just one
			$sql="UPDATE fac_DeviceTemplate SET ManufacturerID={$row["ManufacturerID"]} WHERE ManufacturerID IN (SELECT ManufacturerID FROM fac_Manufacturer WHERE Name=\"{$row["Name"]}\");";
			$dbh->query($sql);
			
			// Delete all the duplicates other than the one you set everything to
			$sql="DELETE FROM fac_Manufacturer WHERE Name=\"{$row["Name"]}\" and ManufacturerID!={$row["ManufacturerID"]};";
			$dbh->query($sql);
		}

		// Repeat for Templates
		$sql="SELECT TemplateID,ManufacturerID,Model FROM fac_DeviceTemplate GROUP BY ManufacturerID,Model HAVING COUNT(*)>1;";
		
		foreach($dbh->query($sql) as $row){
			$sql="UPDATE fac_Device SET TemplateID={$row["TemplateID"]} WHERE TemplateID IN (SELECT TemplateID FROM fac_DeviceTemplate WHERE ManufacturerID={$row["ManufacturerID"]} AND Model=\"{$row["Model"]}\");";
			$dbh->query($sql);
			
			$sql="DELETE FROM fac_DeviceTemplate WHERE ManufacturerID={$row["ManufacturerID"]} AND TemplateID!={$row["TemplateID"]};";
			$dbh->query($sql);
		}

		// Clean up multiple indexes in fac_Department
		$array=array();
		$sql="SHOW INDEXES FROM fac_Department;";
		foreach($dbh->query($sql) as $row){
			$array[$row["Key_name"]]=1;
		}
		foreach($array as $key => $garbage){
			$sql="ALTER TABLE fac_Department DROP INDEX $key;";
			$dbh->query($sql);
		}
		$sql="ALTER TABLE fac_Department ADD PRIMARY KEY (DeptID);";
		$dbh->query($sql);
		$sql="ALTER TABLE fac_Department ADD UNIQUE KEY Name (Name);";
		$dbh->query($sql);
		
		// Clean up multiple indexes in fac_DeviceTemplate
		$array=array();
		$sql="SHOW INDEXES FROM fac_DeviceTemplate;";
		foreach($dbh->query($sql) as $row){
			$array[$row["Key_name"]]=1;
		}
		foreach($array as $key => $garbage){
			$sql="ALTER TABLE fac_Department DROP INDEX $key;";
			$dbh->query($sql);
		}
		$sql="ALTER TABLE fac_DeviceTemplate ADD PRIMARY KEY (TemplateID);";
		$dbh->query($sql);
		$sql="ALTER TABLE fac_DeviceTemplate ADD UNIQUE KEY ManufacturerID (ManufacturerID,Model);";
		$dbh->query($sql);

		// Apply SQL Updates
		$results[]=applyupdate("db-2.0-to-2.1.sql");

		// Add new field for the ConnectionID
		$dbh->query('ALTER TABLE fac_SwitchConnection ADD ConnectionID INT NULL DEFAULT NULL;');

		$sql="SELECT * FROM fac_SwitchConnection;";
		foreach($dbh->query($sql) as $row){
			$insert="INSERT INTO fac_DevicePorts VALUES (NULL , '{$row['EndpointDeviceID']}', '{$row['EndpointPort']}', NULL , '{$row['Notes']}');";
			$dbh->query($insert);
			$update="UPDATE fac_SwitchConnection SET ConnectionID='".$dbh->lastInsertId()."' WHERE EndpointDeviceID='{$row['EndpointDeviceID']}' AND EndpointPort='{$row['EndpointPort']}';";
			$dbh->query($update);			
		}
		// Clear eany old primary key information
		$dbh->query('ALTER TABLE fac_SwitchConnection DROP PRIMARY KEY;');
		// Ensure new ConnectionID is unique
		$dbh->query('ALTER TABLE fac_SwitchConnection ADD UNIQUE(ConnectionID);');

		// Rebuild the config table just in case.  I dunno gremlins.
		$config->rebuild();

		$upgrade=true;
		$version="2.1";
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
if(isset($results)){
	$fh=fopen('install.err', 'a');
	fwrite($fh, date("Y-m-d g:i:s a\n"));
	foreach($results as $key => $value){
		foreach($value as $status => $message){
			if($status==1){$class="error";}else{$class="success";}
			print "<h1 class=\"$class\">$message</h1>";
			fwrite($fh, $message);
		}
	}
	fclose($fh);
	print "<p>If any red errors are showing that does not necessarily mean it failed to load.  Press F5 to reload this page until it goes to the configuration screen</p>";
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

// AJAX Requests
	if(isset($_GET['fl'])){
		echo BuildFileList();
		exit;
	}
	if(isset($_POST['fe'])){
		echo(is_file($_POST['fe']))?1:0;
		exit;
	}
// END AJAX Requests

// Configuration Form Submission
	if(isset($_REQUEST["confaction"]) && $_REQUEST["confaction"]=="Update"){
		foreach($config->ParameterArray as $key=>$value){
			if($key=="ClassList"){
				$List=explode(", ",$_REQUEST[$key]);
				$config->ParameterArray[$key]=$List;
			}else{
				$config->ParameterArray[$key]=$_REQUEST[$key];
			}
		}
		$config->UpdateConfig();

		//Disable all tooltip items and clear the SortOrder
		$dbh->query("UPDATE fac_CabinetToolTip SET SortOrder = NULL, Enabled=0;");
		if(isset($_POST["tooltip"]) && !empty($_POST["tooltip"])){
			foreach($_POST["tooltip"] as $order => $field){
				$dbh->query("UPDATE fac_CabinetToolTip SET SortOrder=".intval($order).", Enabled=1 WHERE Field='".addslashes($field)."' LIMIT 1;");
			}
		}
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
			$dc->CreateDataCenter();
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

//Installation Complete
	if($nodept=="" && $nodc=="" && $nocab==""){ // All three primary sections have had at least one item created
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
	$(document).ready( function() {
		$('#tooltip').multiselect();
		$("select#ToolTips, select#CDUToolTips, select#LabelCase").each(function(){
			$(this).val($(this).attr('data'));
		});
        function colorchange(hex,id){
			if(id==='HeaderColor'){
				$('#header').css('background-color',hex);
			}else if(id==='BodyColor'){
				$('.main').css('background-color',hex);
			}
		}
		$(".color-picker").minicolors({
			letterCase: 'uppercase',
			change: function(hex, rgb){
				colorchange($(this).val(),$(this).attr('id'));
			}
		}).change(function(){colorchange($(this).val(),$(this).attr('id'));});
		$("#configtabs").tabs();
		$('#configtabs input[defaultvalue],#configtabs select[defaultvalue]').each(function(){
			$(this).parent().after('<div><button type="button">&lt;--</button></div><div><span>'+$(this).attr('defaultvalue')+'</span></div>');
		});
		$("#configtabs input").each(function(){
			$(this).attr('id', $(this).attr('name'));
			$(this).removeAttr('defaultvalue');
		});
		$("#configtabs button").each(function(){
			var a = $(this).parent().prev().find('input,select');
			$(this).click(function(){
				a.val($(this).parent().next().children('span').text());
				if(a.hasClass('color-picker')){
					a.minicolors('value', $(this).parent().next().children('span').text()).trigger('change');
				}
				a.triggerHandler("paste");
				a.focus();
				$('input[name="OrgName"]').focus();
			});
		});
		$('input[name="LinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:link, a:hover, a:visited:hover {color: "+$(this).val()+";}</style>");
		});
		$('input[name="VisitedLinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:visited {color: "+$(this).val()+";}</style>");
		});
		$('#PDFLogoFile').click(function(){
			$.get('',{fl: '1'}).done(function(data){
				$("#imageselection").html(data);
				$("#imageselection").dialog({
					resizable: false,
					height:300,
					width: 400,
					modal: true,
					buttons: {
	<?php echo '					',__("Select"),': function() {'; ?>
							if($('#imageselection #preview').attr('image')!=""){
								$('#PDFLogoFile').val($('#imageselection #preview').attr('image'));
							}
							$(this).dialog("close");
						}
					}
				});
				$("#imageselection span").each(function(){
					var preview=$('#imageselection #preview');
					$(this).click(function(){
						preview.html('<img src="images/'+$(this).text()+'" alt="preview">').attr('image',$(this).text()).css('border-width', '5px');
						preview.children('img').load(function(){
							var topmargin=0;
							var leftmargin=0;
							if($(this).height()<$(this).width()){
								$(this).width(preview.innerHeight());
								$(this).css({'max-width': preview.innerWidth()+'px'});
								topmargin=Math.floor((preview.innerHeight()-$(this).height())/2);
							}else{
								$(this).height(preview.innerHeight());
								$(this).css({'max-height': preview.innerWidth()+'px'});
								leftmargin=Math.floor((preview.innerWidth()-$(this).width())/2);
							}
							$(this).css({'margin-top': topmargin+'px', 'margin-left': leftmargin+'px'});
						});
						$("#imageselection span").each(function(){
							$(this).removeAttr('style');
						});
						$(this).css('border','1px dotted black')
						$('#header').css('background-image', 'url("images/'+$(this).text()+'")');
					});
					if($('#PDFLogoFile').val()==$(this).text()){
						$(this).click();
					}
				});
			});
		});
		$("#tzmenu").menu();
		$("#tzmenu ul > li").click(function(e){
			e.preventDefault();
			$("#timezone").val($(this).children('a').attr('data'));
			$("#tzmenu").toggle();
		});
		$("#tzmenu").focusout(function(){
			$("#tzmenu").toggle();
		});
		$('<button type="button">').attr({
				id: 'btn_tzmenu'
		}).appendTo("#general");
		$('#btn_tzmenu').each(function(){
			var input=$("#timezone");
			var offset=input.position();
			var height=input.outerHeight();
			$(this).css({
				'height': height+'px',
				'width': height+'px',
				'position': 'absolute',
				'left': offset.left+input.width()-height-((input.outerHeight()-input.height())/2)+'px',
				'top': offset.top+'px'
			}).click(function(){
				$("#tzmenu").toggle();
				$("#tzmenu").focus().click();
			});
			offset=$(this).position();
			$("#tzmenu").css({
				'position': 'absolute',
				'left': offset.left+(($(this).outerWidth()-$(this).width())/2)+'px',
				'top': offset.top+height+'px'
			});
			$(this).addClass('text-arrow');
		});
		$('input[id^="snmp"],input[id="cut"]').each(function(){
			var a=$(this);
			var icon=$('<span>',{style: 'float:right;margin-top:5px;'}).addClass('ui-icon').addClass('ui-icon-info');
			a.parent('div').append(icon);
			$(this).keyup(function(){
				var b=a.next('span');
				$.post('',{fe: $(this).val()}).done(function(data){
					if(data==1){
						a.effect('highlight', {color: 'lightgreen'}, 1500);
						b.addClass('ui-icon-circle-check').removeClass('ui-icon-info').removeClass('ui-icon-circle-close');
					}else{
						a.effect('highlight', {color: 'salmon'}, 1500);
						b.addClass('ui-icon-circle-close').removeClass('ui-icon-info').removeClass('ui-icon-circle-check');
					}
				});
			});
			$(this).trigger('keyup');
		});
	});

  </script>
</head>
<body>
<div id="header"></div>
<?php

	if((!isset($_GET["dept"])&&!isset($_GET["cab"])&&!isset($_GET["dc"])&&!isset($_GET["complete"]))||isset($_GET["conf"])){

?>
<div class="page config installer">

<div id="sidebar">
<ul>
<a><li class="active">Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>

<?php
	// make list of department types
	$i=0;
	$classlist="";
	foreach($config->ParameterArray["ClassList"] as $item){
		$classlist .= $item;
		if($i+1 != count($config->ParameterArray["ClassList"])){
			$classlist.=", ";
		}
		$i++;
	}

	$imageselect=BuildFileList();

	function formatOffset($offset) {
			$hours = $offset / 3600;
			$remainder = $offset % 3600;
			$sign = $hours > 0 ? '+' : '-';
			$hour = (int) abs($hours);
			$minutes = (int) abs($remainder / 60);

			if ($hour == 0 AND $minutes == 0) {
				$sign = ' ';
			}
			return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) 
					.':'. str_pad($minutes,2, '0');

	}

	$regions=array();
	foreach(DateTimeZone::listIdentifiers() as $line){
		$pieces=explode("/",$line);
		if($pieces[1]){
			$regions[$pieces[0]][]=$line;
		}
	}

	$tzmenu='<ul id="tzmenu">';
	foreach($regions as $country => $cityarray){
		$tzmenu.="\t<li>$country\n\t\t<ul>";
		foreach($cityarray as $key => $city){
			$z=new DateTimeZone($city);
			$c=new DateTime(null, $z);
			$adjustedtime=$c->format('H:i a');
			$offset=formatOffset($z->getOffset($c));
			$tzmenu.="\t\t\t<li><a href=\"#\" data=\"$city\">$adjustedtime - $offset $city</a></li>\n";
		}
		$tzmenu.="\t\t</ul>\t</li>";
	}
	$tzmenu.='</ul>';

	// Figure out what the URL to this page
	$href="";
	$href.=($_SERVER['HTTPS'])?'https://':'http://';
	$href.=$_SERVER['SERVER_NAME'];
	$href.=substr($_SERVER['REQUEST_URI'], 0, -strlen(basename($_SERVER['REQUEST_URI'])));

	// Build up the list of items available for the tooltips
	$tooltip="<select id=\"tooltip\" name=\"tooltip[]\" multiple=\"multiple\">\n";
	$ttconfig=$dbh->query("SELECT * FROM fac_CabinetToolTip ORDER BY SortOrder ASC, Enabled DESC, Label ASC;");
	foreach($ttconfig as $row){
		$selected=($row["Enabled"])?" selected":"";
		$tooltip.="<option value=\"".$row['Field']."\"$selected>".__($row["Label"])."</option>\n";
	}
	$tooltip.="</select>";

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Data Center Configuration"),'</h3>
<h3>',__("Database Version"),': ',$config->ParameterArray["Version"],'</h3>
<div class="center"><div>
<form enctype="multipart/form-data" action="',$_SERVER["PHP_SELF"],'" method="POST">
   <input type="hidden" name="Version" value="',$config->ParameterArray["Version"],'">

	<div id="configtabs">
		<ul>
			<li><a href="#general">',__("General"),'</a></li>
			<li><a href="#style">',__("Style"),'</a></li>
			<li><a href="#email">',__("Email"),'</a></li>
			<li><a href="#reporting">',__("Reporting"),'</a></li>
			<li><a href="#tt">',__("Cabinet ToolTips"),'</a></li>
		</ul>
		<div id="general">
			<div class="table">
				<div>
					<div><label for="OrgName">',__("Organization Name"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["OrgName"],'" name="OrgName" value="',$config->ParameterArray["OrgName"],'"></div>
				</div>
				<div>
					<div><label for="Locale">',__("Locale"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["Locale"],'" name="Locale" value="',$config->ParameterArray["Locale"],'"></div>
				</div>
				<div>
					<div><label for="DefaultPanelVoltage">',__("Default Panel Voltage"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["DefaultPanelVoltage"],'" name="DefaultPanelVoltage" value="',$config->ParameterArray["DefaultPanelVoltage"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Time and Measurements"),'</h3>
			<div class="table" id="timeandmeasurements">
				<div>
					<div><label for="timezone">',__("Time Zone"),'</label></div>
					<div><input type="text" readonly="readonly" id="timezone" defaultvalue="',$config->defaults["timezone"],'" name="timezone" value="',$config->ParameterArray["timezone"],'"></div>
				</div>
				<div>
					<div><label for="mDate">',__("Manufacture Date"),'</label></div>
					<div><select id="mDate" name="mDate" defaultvalue="',$config->defaults["mDate"],'" data="',$config->ParameterArray["mDate"],'">
							<option value="blank"',(($config->ParameterArray["mDate"]=="blank")?' selected="selected"':''),'>',__("Blank"),'</option>
							<option value="now"',(($config->ParameterArray["mDate"]=="now")?' selected="selected"':''),'>',__("Now"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="wDate">',__("Warranty Date"),'</label></div>
					<div><select id="wDate" name="wDate" defaultvalue="',$config->defaults["wDate"],'" data="',$config->ParameterArray["wDate"],'">
							<option value="blank"',(($config->ParameterArray["wDate"]=="blank")?' selected="selected"':''),'>',__("Blank"),'</option>
							<option value="now"',(($config->ParameterArray["wDate"]=="now")?' selected="selected"':''),'>',__("Now"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="mUnits">',__("Measurement Units"),'</label></div>
					<div><select id="mUnits" name="mUnits" defaultvalue="',$config->defaults["mUnits"],'" data="',$config->ParameterArray["mUnits"],'">
							<option value="english"',(($config->ParameterArray["mUnits"]=="english")?' selected="selected"':''),'>',__("English"),'</option>
							<option value="metric"',(($config->ParameterArray["mUnits"]=="metric")?' selected="selected"':''),'>',__("Metric"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Users"),'</h3>
			<div class="table">
				<div>
					<div><label for="ClassList">',__("Department Types"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ClassList"],'" name="ClassList" value="',$classlist,'"></div>
				</div>
				<div>
					<div><label for="UserLookupURL">',__("User Lookup URL"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["UserLookupURL"],'" name="UserLookupURL" value="',$config->ParameterArray["UserLookupURL"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Rack Requests"),'</h3>
			<div class="table">
				<div>
					<div><label for="MailSubject">',__("Mail Subject"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailSubject"],'" name="MailSubject" value="',$config->ParameterArray["MailSubject"],'"></div>
				</div>
				<div>
					<div><label for="RackWarningHours">',__("Warning (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackWarningHours"],'" name="RackWarningHours" value="',$config->ParameterArray["RackWarningHours"],'"></div>
				</div>
				<div>
					<div><label for="RackOverdueHours">',__("Critical (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackOverdueHours"],'" name="RackOverdueHours" value="',$config->ParameterArray["RackOverdueHours"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Rack Usage"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><label for="SpaceRed">',__("Space Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceRed"],'" name="SpaceRed" value="',$config->ParameterArray["SpaceRed"],'"></div>
				</div>
				<div>
					<div><label for="SpaceYellow">',__("Space Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceYellow"],'" name="SpaceYellow" value="',$config->ParameterArray["SpaceYellow"],'"></div>
				</div>
				<div>
					<div><label for="WeightRed">',__("Weight Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightRed"],'" name="WeightRed" value="',$config->ParameterArray["WeightRed"],'"></div>
				</div>
				<div>
					<div><label for="WeightYellow">',__("Weight Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightYellow"],'" name="WeightYellow" value="',$config->ParameterArray["WeightYellow"],'"></div>
				</div>
				<div>
					<div><label for="PowerRed">',__("Power Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerRed"],'" name="PowerRed" value="',$config->ParameterArray["PowerRed"],'"></div>
				</div>
				<div>
					<div><label for="PowerYellow">',__("Power Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerYellow"],'" name="PowerYellow" value="',$config->ParameterArray["PowerYellow"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Virtual Machines"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><lable for="VMExpirationTime">',__("Expiration Time (Days)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["VMExpirationTime"],'" name="VMExpirationTime" value="',$config->ParameterArray["VMExpirationTime"],'"></div>
				</div>
			</div> <!-- end table -->
			',$tzmenu,'
		</div>
		<div id="style">
			<h3>',__("Racks & Maps"),'</h3>
			<div class="table">
				<div>
					<div><label for="CriticalColor">',__("Critical Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CriticalColor" value="',$config->ParameterArray["CriticalColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["CriticalColor"]),'</span></div>
				</div>
				<div>
					<div><label for="CautionColor">',__("Caution Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CautionColor" value="',$config->ParameterArray["CautionColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["CautionColor"]),'</span></div>
				</div>
				<div>
					<div><label for="GoodColor">',__("Good Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="GoodColor" value="',$config->ParameterArray["GoodColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["GoodColor"]),'</span></div>
				</div>
				<div>
					<div>&nbsp;</div>
					<div></div>
					<div></div>
					<div></div>
				</div>
				<div>
					<div><label for="ReservedColor">',__("Reserved Devices"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="ReservedColor" value="',$config->ParameterArray["ReservedColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["ReservedColor"]),'</span></div>
				</div>
				<div>
					<div><label for="FreeSpaceColor">',__("Unused Spaces"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="FreeSpaceColor" value="',$config->ParameterArray["FreeSpaceColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["FreeSpaceColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Devices"),'</h3>
			<div class="table">
				<div>
					<div><label for="LabelCase">',__("Device Labels"),'</label></div>
					<div><select id="LabelCase" name="LabelCase" defaultvalue="',$config->defaults["LabelCase"],'" data="',$config->ParameterArray["LabelCase"],'">
							<option value="upper">',transform(__("Uppercase"),'upper'),'</option>
							<option value="lower">',transform(__("Lowercase"),'lower'),'</option>
							<option value="initial">',transform(__("Initial caps"),'initial'),'</option>
							<option value="none">',__("Don't touch my labels"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Site"),'</h3>
			<div class="table">
				<div>
					<div><label for="HeaderColor">',__("Header Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="HeaderColor" value="',$config->ParameterArray["HeaderColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["HeaderColor"]),'</span></div>
				</div>
				<div>
					<div><label for="BodyColor">',__("Body Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="BodyColor" value="',$config->ParameterArray["BodyColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["BodyColor"]),'</span></div>
				</div>
				<div>
					<div><label for="LinkColor">',__("Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="LinkColor" value="',$config->ParameterArray["LinkColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["LinkColor"]),'</span></div>
				</div>
				<div>
					<div><label for="VisitedLinkColor">',__("Viewed Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="VisitedLinkColor" value="',$config->ParameterArray["VisitedLinkColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["VisitedLinkColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="email">
			<div class="table">
				<div>
					<div><label for="SMTPServer">',__("SMTP Server"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPServer"],'" name="SMTPServer" value="',$config->ParameterArray["SMTPServer"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPort">',__("SMTP Port"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPort"],'" name="SMTPPort" value="',$config->ParameterArray["SMTPPort"],'"></div>
				</div>
				<div>
					<div><label for="SMTPHelo">',__("SMTP Helo"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPHelo"],'" name="SMTPHelo" value="',$config->ParameterArray["SMTPHelo"],'"></div>
				</div>
				<div>
					<div><label for="SMTPUser">',__("SMTP Username"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPUser"],'" name="SMTPUser" value="',$config->ParameterArray["SMTPUser"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPassword">',__("SMTP Password"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPassword"],'" name="SMTPPassword" value="',$config->ParameterArray["SMTPPassword"],'"></div>
				</div>
				<div>
					<div><label for="MailToAddr">',__("Mail To"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailToAddr"],'" name="MailToAddr" value="',$config->ParameterArray["MailToAddr"],'"></div>
				</div>
				<div>
					<div><label for="MailFromAddr">',__("Mail From"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailFromAddr"],'" name="MailFromAddr" value="',$config->ParameterArray["MailFromAddr"],'"></div>
				</div>
				<div>
					<div><label for="ComputerFacMgr">',__("Facility Manager"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ComputerFacMgr"],'" name="ComputerFacMgr" value="',$config->ParameterArray["ComputerFacMgr"],'"></div>
				</div>
				<div>
					<div><label for="FacMgrMail">',__("Facility Manager Email"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["FacMgrMail"],'" name="FacMgrMail" value="',$config->ParameterArray["FacMgrMail"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="reporting">
			<div id="imageselection" title="Image file selector">
				',$imageselect,'
			</div>
			<div class="table">
				<div>
					<div><label for="annualCostPerUYear">',__("Annual Cost Per Rack Unit (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerUYear"],'" name="annualCostPerUYear" value="',$config->ParameterArray["annualCostPerUYear"],'"></div>
				</div>
				<div>
					<div><label for="annualCostPerWattYear">',__("Annual Cost Per Watt (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerWattYear"],'" name="annualCostPerWattYear" value="',$config->ParameterArray["annualCostPerWattYear"],'"></div>
				</div>
				<div>
					<div><label for="PDFLogoFile">',__("Logo file for headers"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFLogoFile"],'" name="PDFLogoFile" value="',$config->ParameterArray["PDFLogoFile"],'"></div>
				</div>
				<div>
					<div><label for="PDFfont">',__("Font"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFfont"],'" name="PDFfont" value="',$config->ParameterArray["PDFfont"],'"></div>
				</div>
				<div>
					<div><label for="NewInstallsPeriod">',__("New Installs Period"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["NewInstallsPeriod"],'" name="NewInstallsPeriod" value="',$config->ParameterArray["NewInstallsPeriod"],'"></div>
				</div>
				<div>
					<div><label for="InstallURL">',__("Base URL for install"),'</label></div>
					<div><input type="text" defaultvalue="',$href,'" name="InstallURL" value="',$config->ParameterArray["InstallURL"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Utilities"),'</h3>
			<div class="table">
				<div>
					<div><label for="snmpget">',__("snmpget"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["snmpget"],'" name="snmpget" value="',$config->ParameterArray["snmpget"],'"></div>
				</div>
				<div>
					<div><label for="snmpwalk">',__("snmpwalk"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["snmpwalk"],'" name="snmpwalk" value="',$config->ParameterArray["snmpwalk"],'"></div>
				</div>
				<div>
					<div><label for="cut">',__("cut"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["cut"],'" name="cut" value="',$config->ParameterArray["cut"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="tt">
			<div class="table">
				<div>
					<div><label for="ToolTips">',__("Cabinet ToolTips"),'</label></div>
					<div><select id="ToolTips" name="ToolTips" defaultvalue="',$config->defaults["ToolTips"],'" data="',$config->ParameterArray["ToolTips"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<br>
			',$tooltip,'
		</div>
	</div>';


?>
<div>
   <div></div>
   <div class="center"><input type="submit" name="confaction" value="Update"></div>
</div>
</div> <!-- END div.table -->
</div>
</form>
</div>
<?php

	}elseif(isset($_GET["dept"])){
		$deptList = $dept->GetDepartmentList();
?>
<script type="text/javascript">
function showgroup(obj){
	self.frames['groupadmin'].location.href='dept_groups.php?deptid='+obj;
	document.getElementById('groupadmin').style.display = "block";
	document.getElementById('deptname').readOnly = true
	document.getElementById('deptsponsor').readOnly = true
	document.getElementById('deptmgr').readOnly = true
	document.getElementById('deptclass').disabled = true
	document.getElementById('controls').id = "displaynone";
}
</script>

<div class="page installer">

<div id="sidebar">
<ul>
<a href="?conf"><li>Configuration</li></a>
<a><li class="active">Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>

<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Department Detail</h3>
<?php echo $nodept; ?>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>?dept" method="POST">
<div class="table centermargin">
<div>
   <div>Department</div>
   <div><input type="hidden" name="deptaction" value="query"><select name="deptid" onChange="form.submit()" <?php echo $nodeptdrop;?>>
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
   <div><select name="classification" id="deptclass">
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
		echo "<input type=\"button\" onClick=\"showgroup($dept->DeptID)\" value=\"Assign Contacts\">";
//		print "<input type=\"button\" onClick=\"self.frames['groupadmin'].location.href='dept_groups.php?deptid=$dept->DeptID'\" value=\"Assign Contacts\">";
//		print "<input type=\"button\" onClick=\"window.open('dept_groups.php?deptid=$dept->DeptID', 'popup')\" value=\"Assign Contacts\">";
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<iframe name="groupadmin" id="groupadmin" frameborder=0 scrolling="no"></iframe>
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
<a href="?conf"><li>Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a><li class="active">Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>

<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Detail</h3>
<?php echo $nodc; ?>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>?dc" method="POST">
<div class="table">
<div>
   <div><label for="datacenterid">Data Center ID</label></div>
   <div><select name="datacenterid" id="datacenterid" onChange="form.submit()" <?php echo $nodcdrop;?>>
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
<a href="?conf"><li>Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a><li class="active">Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Cabinet Inventory</h3>
<?php echo $nodccab; ?>
<div class='center'><div>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>?cab' method='POST'>
<div class='table'>
<div>
   <div>Cabinet</div>
   <div><select name='cabinetid' onChange='form.submit()' <?php echo $nodcdrop; ?>>
   <option value='0'>New Cabinet</option>
<?php
	foreach($cabList as $cabRow){
		echo '<option value=\''.$cabRow->CabinetID.'\'';
		if($cabRow->CabinetID == $cab->CabinetID){
			echo ' selected';
		}
		echo '>'.$cabRow->Location.'</option>\n';
	}
?>
   </select></div>
</div>
<div>
   <div>Data Center</div>
   <div><?php echo $cab->GetDCSelectList(); ?></div>
</div>
<div>
   <div>Location</div>
   <div><input type='text' name='location' size='8' value='<?php echo $cab->Location; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
  <div>Assigned To:</div>
  <div><select name='assignedto' <?php echo $nodcdrop;?>>
    <option value='0'>General Use</option>
<?php
	foreach($deptList as $deptRow){
		echo '<option value=\''.$deptRow->DeptID.'\'';
		if($deptRow->DeptID == $cab->AssignedTo){echo ' selected=\'selected\'';}
		echo '>'.$deptRow->Name.'</option>\n';
	}
?>
  </select>
  </div>
</div>
<div>
   <div>Cabinet Height (U)</div>
   <div><input type='text' name='cabinetheight' size='4' value='<?php echo $cab->CabinetHeight; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Model</div>
   <div><input type='text' name='model' size='30' value='<?php echo $cab->Model; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Maximum kW</div>
   <div><input type='text' name='maxkw' size='30' value='<?php echo $cab->MaxKW; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Maximum Weight</div>
   <div><input type='text' name='maxweight' size='30' value='<?php echo $cab->MaxWeight; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Date of Installation</div>
   <div><input type='text' name='installationdate' size='15' value='<?php echo date('m/d/Y', strtotime($cab->InstallationDate)); ?>' <?php echo $nodcfield;?>></div>
</div>
<?php
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
	}elseif(isset($_GET["complete"])){
?>
<div class='page installer'>
<div id="sidebar">
<ul>
<a href="?conf"><li>Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a><li class="active">Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Installation Complete</h3>
<?php echo $nodccab; ?>
<div class='center'><div>

<p>You have completed the basic configuration for openDCIM.  At this time please <a href="http://www.opendcim.org/wiki/" title="openDCIM Wiki">go to the wiki</a> for additional questions that you might have or <a href="http://list.opendcim.org/listinfo.cgi/discussion-opendcim.org" title="openDCIM Mailing List">join our mailing list</a>.</p>
<p>To start normal operation of openDCIM please delete install.php from the installation directory</p>


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
