<?php
$codeversion="3.2";

// Pre-Flight check
	$tests=array();
	$errors=0;
	if (isset($_SERVER['REMOTE_USER'])) {
		$tests['Remote User']['state']="good";
		$tests['Remote User']['message']='';
	}else{
		$tests['Remote User']['state']="fail";
		$tests['Remote User']['message']='<a href="http://httpd.apache.org/docs/2.2/howto/auth.html">http://httpd.apache.org/docs/2.2/howto/auth.html</a>';
		$errors++;
	}

	if (extension_loaded('mbstring')) {
		$tests['mbstring']['state']="good";
		$tests['mbstring']['message']='';
	}else{
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

	if(extension_loaded('snmp')) {
		$tests['snmp']['state']="good";
		$tests['snmp']['message']='';
	}else{
		$tests['snmp']['state']="fail";
		$tests['snmp']['message']='PHP is missing the <a href="http://php.net/manual/book.snmp.php">snmp extension</a>. Please install it.';
	}

	$tests['pdo']['message']='';
	if (extension_loaded('PDO')) {
		$tests['pdo']['state']="good";
		if (count(PDO::getAvailableDrivers())>0) {
			$tests['pdodrivers']['message']='Available drivers: '.implode(", ",PDO::getAvailableDrivers());
			$tests['pdodrivers']['state']="good";
			// pdo is loaded check for the db.inc
			if(file_exists("db.inc.php")){
				$tests['db.inc']['state']="good";
				$tests['db.inc']['message']="db.inc.php has been detected and in the proper place";
				require_once("db.inc.php");
				// check for strict_trans_tables
				if(strpos(@end($dbh->query("select @@global.sql_mode;")->fetch()),'STRICT_TRANS_TABLES') === false){
					$tests['strictdb']['state']="good";
					$tests['strictdb']['message']='';
				}else{
					$tests['strictdb']['state']="fail";
					$tests['strictdb']['message']='openDCIM does not support STRICT_TRANS_TABLES. The following SQL statement might clear the error for this session.  More information can be found <a href="https://github.com/samilliken/openDCIM/issues/457">here</a>.<br><br><i>SET GLOBAL sql_mode = "";</i>';
					$errors++;
				}
			}else{
				$tests['db.inc']['state']="fail";
				$tests['db.inc']['message']="Please copy db.inc.php-dist to db.inc.php and edit appropriately";
				$errors++;
			}

		}else{
			$tests['pdodrivers']['message']='Available drivers: none';
			$tests['pdodrivers']['state']="fail";
			$errors++;
		}
	}else{
		$tests['pdo']['state']="fail";
		$tests['pdo']['message']='openDCIM requires the <a href="http://php.net/manual/pdo.installation.php">PDO extention</a> and you do not appear to have it loaded';
		$tests['pdodrivers']['state']="fail";
		$tests['pdodrivers']['message']='No PDO drivers have been detected';
		$errors++;
	}

	if (function_exists('json_encode')) {
		$tests['json']['state']="good";
		$tests['json']['message']='PHP json module detected';
	}else{
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
	$upgrade=false;

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

	/* Check the table to see if there are any users
	   defined, yet.  If not, this is a new install, so
	   create an admin user (all rights) as the current
	   user.  */
	
	$sql="SELECT COUNT(*) AS TotalUsers FROM fac_User;";
	$users=$dbh->query($sql)->fetchColumn();

	if($users==0){
		$user->Name="Default Admin";
		foreach($user as $prop => $value){
			if($prop!='Name' && $prop!='UserID'){
				$user->$prop=true;
			}
		}
		$user->Disabled=false;

		$user->CreateUser();
	}

	// This will be reloading the rights for a new install but for upgrades
	// it will be the actual rights load
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
		$version="1.2";
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

	if($version==""){ // something borked re-apply all database updates and pray for forgiveness
		$version="1.2";
	}
	if($version=="1.2"){ // Do 1.2 to 1.3 Update
		$results[]=applyupdate("db-1.2-to-1.3.sql");
		$version="1.3";
	}
	if($version=="1.3"){ // Do 1.3 to 1.4 Update
		// Clean the configuration table of any duplicate values that might have been added.
		$config->rebuild();
		$results[]=applyupdate("db-1.3-to-1.4.sql");
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
		
		$version="2.0.1";
	}

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

		$version="2.1";
	}

	if($version=="2.1"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-2.1-to-3.0.sql");

		print "Update blade devices for new front/rear tracking method<br>\n";
		$sql="UPDATE fac_Device SET BackSide=1, ChassisSlots=0 WHERE ChassisSlots=1 AND ParentDevice>0;";
		$dbh->query($sql);

		// Port conversion
		$numports=$dbh->query('SELECT SUM(Ports) + (SELECT SUM(Ports) FROM fac_Device WHERE DeviceType="Patch Panel") as TotalPorts, COUNT(DeviceID) as Devices FROM fac_Device')->fetch();
		print "Creating {$numports['TotalPorts']} ports for {$numports['Devices']} devices. <b>THIS MAY TAKE A WHILE</b><br>\n";

		// Retrieve a list of all devices and make ports for them.
		$sql='SELECT DeviceID,Ports,DeviceType from fac_Device WHERE 
			DeviceType!="Physical Infrastructure" AND Ports>0;';

		$errors=array();
		$ports=array();
		foreach($dbh->query($sql) as $row){
			for($x=1;$x<=$row['Ports'];$x++){
				// Create a port for every device
				$ports[$row['DeviceID']][$x]['Label']=$x;
				if($row['DeviceType']=='Patch Panel'){
					// Patch panels needs rear ports as well
					$ports[$row['DeviceID']][-$x]['Label']="Rear $x";
				}
			}
		}

		$findswitch=$dbh->prepare('SELECT * FROM fac_SwitchConnection WHERE EndpointDeviceID=:deviceid ORDER BY EndpointPort ASC;');
		foreach($ports as $deviceid => $port){
			$findswitch->execute(array(':deviceid' => $deviceid));
			$defined=$findswitch->fetchAll();
			foreach($defined as $row){
				// Weed out any port numbers that have been defined outside the range of 
				// valid ports for the device
				if(isset($ports[$deviceid][$row['EndpointPort']])){
					// Device Ports
					$ports[$deviceid][$row['EndpointPort']]['Notes']=$row['Notes'];
					$ports[$deviceid][$row['EndpointPort']]['Connected Device']=$row['SwitchDeviceID'];
					$ports[$deviceid][$row['EndpointPort']]['Connected Port']=$row['SwitchPortNumber'];

					// Switch Ports
					$ports[$row['SwitchDeviceID']][$row['SwitchPortNumber']]['Notes']=$row['Notes'];
					$ports[$row['SwitchDeviceID']][$row['SwitchPortNumber']]['Connected Device']=$row['EndpointDeviceID'];
					$ports[$row['SwitchDeviceID']][$row['SwitchPortNumber']]['Connected Port']=$row['EndpointPort'];

				}else{
					// Either display this as a log item later or possibly backfill empty 
					// ports with this data
					$errors[$deviceid][$row['EndpointPort']]['Notes']=$row['Notes'];
					$errors[$deviceid][$row['EndpointPort']]['Connected Device']=$row['SwitchDeviceID'];
					$errors[$deviceid][$row['EndpointPort']]['Connected Port']=$row['SwitchPortNumber'];
				}
			}
		}

		$findpatch=$dbh->prepare('SELECT * FROM fac_PatchConnection WHERE FrontEndpointDeviceID=:deviceid ORDER BY FrontEndpointPort ASC;');
		foreach($ports as $deviceid => $port){
			$findpatch->execute(array(':deviceid' => $deviceid));
			$defined=$findpatch->fetchAll();
			foreach($defined as $row){
				// Weed out any port numbers that have been defined outside the range of 
				// valid ports for the device
				if(isset($ports[$deviceid][$row['FrontEndpointPort']])){
					// Connect the device to the panel
					$ports[$deviceid][$row['FrontEndpointPort']]['Notes']=$row['FrontNotes'];
					$ports[$deviceid][$row['FrontEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
					$ports[$deviceid][$row['FrontEndpointPort']]['Connected Port']=$row['PanelPortNumber'];
					// Connect the panel to the device
					$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Connected Device']=$row['FrontEndpointDeviceID'];
					$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Connected Port']=$row['FrontEndpointPort'];
					$ports[$row['PanelDeviceID']][$row['PanelPortNumber']]['Notes']=$row['FrontNotes'];
				}else{
					// Either display this as a log item later or possibly backfill empty 
					// ports with this data
					$errors[$deviceid][$row['FrontEndpointPort']]['Notes']=$row['FrontNotes'];
					$errors[$deviceid][$row['FrontEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
					$errors[$deviceid][$row['FrontEndpointPort']]['Connected Port']=$row['PanelPortNumber'];
				}
			}
		}

		foreach($dbh->query('SELECT * FROM fac_PatchConnection;') as $row){
			// Read all the patch connections again to get the rear connection info 
			$ports[$row['RearEndpointDeviceID']][-$row['RearEndpointPort']]['Connected Device']=$row['PanelDeviceID'];
			$ports[$row['RearEndpointDeviceID']][-$row['RearEndpointPort']]['Connected Port']=-$row['PanelPortNumber'];
			$ports[$row['RearEndpointDeviceID']][-$row['RearEndpointPort']]['Notes']=$row['RearNotes'];
			$ports[$row['PanelDeviceID']][-$row['PanelPortNumber']]['Connected Device']=$row['RearEndpointDeviceID'];
			$ports[$row['PanelDeviceID']][-$row['PanelPortNumber']]['Connected Port']=-$row['RearEndpointPort'];
			$ports[$row['PanelDeviceID']][-$row['PanelPortNumber']]['Notes']=$row['RearNotes'];
		}

		// Backfill the extra data
		foreach($errors as $deviceid => $row){
			$numPorts=count($ports[$deviceid])+1;
			foreach($row as $portnum => $port){
				for($n=1;$n<$numPorts;$n++){
					if(!isset($ports[$deviceid][$n]['Notes'])){
						$ports[$deviceid][$n]=$port;
						// connect up the other side as well
						$ports[$port['Connected Device']][$port['Connected Port']]['Connected Device']=$deviceid;
						$ports[$port['Connected Device']][$port['Connected Port']]['Connected Port']=$n;
						$ports[$port['Connected Device']][$port['Connected Port']]['Notes']=$port['Notes'];
						unset($errors[$deviceid][$portnum]); // Remove it from the backfill data
						break;
					}
				}
			}
		}

		$incdataports=$dbh->prepare('UPDATE fac_Device SET Ports=:n WHERE DeviceID=:deviceid;');

		// Anything left in $errors now is an extra port that exists outside the number of designated deviceports.
		foreach($errors as $deviceid => $row){
			foreach($row as $portnum => $port){
				$n=count($ports[$deviceid])+1;
				$ports[$deviceid][]=$port;
				// connect up the other side as well, $n will give us the new port number 
				// since it is outside the defined range for the device
				$ports[$port['Connected Device']][$port['Connected Port']]['Connected Device']=$deviceid;
				$ports[$port['Connected Device']][$port['Connected Port']]['Connected Port']=$n;
				$ports[$port['Connected Device']][$port['Connected Port']]['Notes']=$port['Notes'];
				// Update the number of ports on this device to match the corrected value
				$incdataports->execute(array(":n"=>$n,":deviceid"=>$deviceid));
			unset($errors[$deviceid][$portnum]); // Remove it from the backfill data
			}
		}

		$n=1; $insertsql=''; $insertlimit=100;
		$insertprogress=100/($numports['TotalPorts']/$insertlimit);
		$insertprogress=(intval($insertprogress)>0)?intval($insertprogress):1; // if this is gonna do more than 100 inserts we might have issues
		echo "<br>\nConversion process: <br>\n<table style=\"border: 1px solid black; border-collapse: collapse; width: 100%;\"><tr>";flush();
		// All the ports should be in the array now, use the prepared statement to load them all
		foreach($ports as $deviceid => $row){
			foreach($row as $portnum => $port){
				$null=null;$blank="";
				$cdevice=(isset($port['Connected Device']))?$port['Connected Device']:'NULL';
				$cport=(isset($port['Connected Port']))?$port['Connected Port']:'NULL';
				$notes=(isset($port['Notes']))?$port['Notes']:'';

				$insertsql.="($deviceid,$portnum,\"\",0,0,\"\",$cdevice,$cport,\"$notes\")";
				if($n%$insertlimit!=0){
					$insertsql.=" ,";
				}else{
					$dbh->exec('INSERT INTO fac_Ports VALUES'.$insertsql);
					$insertsql='';
					print "<td width=\"$insertprogress%\" style=\"background-color: green\">&nbsp;</td>";
					flush(); // attempt to update the progress as this goes on.
				}
				++$n;
			}
		}
		//do one last insert
		$insertsql=substr($insertsql, 0, -1);// shave off that last comma
		$dbh->exec('INSERT INTO fac_Ports VALUES'.$insertsql);
		echo "</tr></table>";

		print "<br>\nPort conversion complete.<br>\n";

		// Rebuild the config table just in case.
		$config->rebuild();

		$version="3.0";
	}
	if($version=="3.0"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-3.0-to-3.1.sql");

		// Rebuild the config table just in case.
		$config->rebuild();

		$version="3.1";
	}
	if($version=="3.1"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-3.1-to-3.2.sql");

		// Rebuild the config table just in case.
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
	print "<p>Take note of any errors displayed in red then press F5 to reload this page until it goes to the configuration screen.</p>";
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
	if(isset($_POST['cc'])){  // Cable color codes
		$col=new ColorCoding();
		$col->Name=trim($_POST['cc']);
		$col->DefaultNote=trim($_POST['ccdn']);
		if(isset($_POST['cid'])){ // If set we're updating an existing entry
			$col->ColorID=$_POST['cid'];
			if(isset($_POST['original'])){
				$col->GetCode();
			    header('Content-Type: application/json');
				echo json_encode($col);
				exit;
			}
			if(isset($_POST['clear']) || isset($_POST['change'])){
				$newcolorid=0;
				if(isset($_POST['clear'])){
					ColorCoding::ResetCode($col->ColorID);
				}else{
					$newcolorid=$_POST['change'];
					ColorCoding::ResetCode($col->ColorID,$newcolorid);
				}
				$mediatypes=MediaTypes::GetMediaTypeList();
				foreach($mediatypes as $mt){
					if($mt->ColorID==$col->ColorID){
						$mt->ColorID=$newcolorid;
						$mt->UpdateType();
					}
				}
				if($col->DeleteCode()){
					echo 'u';
				}else{
					echo 'f';
				}
				exit;
			}
			if($col->UpdateCode()){
				echo 'u';
			}else{
				echo 'f';
			}
		}else{
			if($col->CreateCode()){
				echo $col->ColorID;
			}else{
				echo 'f';
			}
		}
		exit;
	}
	if(isset($_POST['ccused'])){
		$count=ColorCoding::TimesUsed($_POST['ccused']);
		if($count==0){
			$col=new ColorCoding();
			$col->ColorID=$_POST['ccused'];
			$col->DeleteCode();
		}
		echo $count;
		exit;
	}
	if(isset($_POST['mt'])){ // Media Types
		$mt=new MediaTypes();
		$mt->MediaType=trim($_POST['mt']);
		$mt->ColorID=$_POST['mtcc'];
		if(isset($_POST['mtid'])){ // If set we're updating an existing entry
			$mt->MediaID=$_POST['mtid'];
			if(isset($_POST['original'])){
				$mt->GetType();
			    header('Content-Type: application/json');
				echo json_encode($mt);
				exit;
			}
			if(isset($_POST['clear']) || isset($_POST['change'])){
				if(isset($_POST['clear'])){
					MediaTypes::ResetType($mt->MediaID);
				}else{
					$newmediaid=$_POST['change'];
					MediaTypes::ResetType($mt->MediaID,$newmediaid);
				}
				if($mt->DeleteType()){
					echo 'u';
				}else{
					echo 'f';
				}
				exit;
			}
			if($mt->UpdateType()){
				echo 'u';
			}else{
				echo 'f';
			}
		}else{
			if($mt->CreateType()){
				echo $mt->MediaID;
			}else{
				echo 'f';
			}
			
		}
		exit;
	}
	if(isset($_POST['mtused'])){
		$count=MediaTypes::TimesUsed($_POST['mtused']);
		if($count==0){
			$mt=new MediaTypes();
			$mt->MediaID=$_POST['mtused'];
			$mt->DeleteType();
		}
		echo $count;
		exit;
	}
	if(isset($_POST['mtlist'])){
		$codeList=MediaTypes::GetMediaTypeList();
		$output='<option value=""></option>';
		foreach($codeList as $mt){
			$output.="<option value=\"$mt->MediaID\">$mt->MediaType</option>";
		}
		echo $output;
		exit;		
	}
	if(isset($_POST['cclist'])){
		$codeList=ColorCoding::GetCodeList();
		$output='<option value=""></option>';
		foreach($codeList as $cc){
			$output.="<option value=\"$cc->ColorID\">$cc->Name</option>";
		}
		echo $output;
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

		//Disable all cdu tooltip items and clear the SortOrder
		$dbh->exec("UPDATE fac_CDUToolTip SET SortOrder = NULL, Enabled=0;");
		if(isset($_POST["cdutooltip"]) && !empty($_POST["cdutooltip"])){
			$p=$dbh->prepare("UPDATE fac_CDUToolTip SET SortOrder=:sortorder, Enabled=1 WHERE Field=:field LIMIT 1;");
			foreach($_POST["cdutooltip"] as $order => $field){
				$p->bindParam(":sortorder",$order);
				$p->bindParam(":field",$field);
				$p->execute();
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
		$('#tooltip, #cdutooltip').multiselect();
		$("select:not('#tooltip, #cdutooltip')").each(function(){
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


		// Cabling - Media Types
		function removemedia(row){
			$.post('',{mtused: row.find('div:nth-child(2) input').attr('data')}).done(function(data){
				if(data.trim()==0){
					row.effect('explode', {}, 500, function(){
						$(this).remove();
					});
				}else{
					var defaultbutton={
						"<?php echo __("Clear all"); ?>": function(){
							$.post('',{mtid: row.find('div:nth-child(2) input').attr('data'),mt: '', mtcc: '', clear: ''}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var replacebutton={
						"<?php echo __("Replace"); ?>": function(){
							// send command to replace all connections with x
							$.post('',{mtid: row.find('div:nth-child(2) input').attr('data'),mt: '', mtcc: '', change: $('#modal select').val()}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var cancelbutton={
						"<?php echo __("Cancel"); ?>": function(){
							$(this).dialog("destroy");
						}
					}
<?php echo "					var modal=$('<div />', {id: 'modal', title: '".__("Media Type Delete Override")."'}).html('<div id=\"modaltext\">".__("This media type is in use somewhere. Select an alternate type to assign to all the records to or choose clear all.")."<select id=\"replaceme\"></select></div>').dialog({"; ?>
						dialogClass: 'no-close',
						appendTo: 'body',
						modal: true,
						buttons: $.extend({}, defaultbutton, cancelbutton)
					});
					$.post('',{mtlist: ''}).done(function(data){
						var choices=$('<select />');
						choices.html(data);
						choices.find('option').each(function(){
							if($(this).val()==row.find('div:nth-child(2) input').attr('data')){$(this).remove();}
						});
						choices.change(function(){
							if($(this).val()==''){ // clear all
								modal.dialog('option','buttons',$.extend({}, defaultbutton, cancelbutton));
							}else{ // replace
								modal.dialog('option','buttons',$.extend({}, replacebutton, cancelbutton));
							}
						});
						modal.find($('#replaceme')).replaceWith(choices);
						
					});
				}
			});

		}

		var blankmediarow=$('<div />').html('<div><img src="images/del.gif"></div><div><input id="mediatype[]" name="mediatype[]" type="text"></div><div><select name="mediacolorcode[]"></select></div>');
		function bindmediarow(row){
			var addrem=row.find('div:first-child');
			var mt=row.find('div:nth-child(2) input');
			var mtcc=row.find('div:nth-child(3) select');
			if(mt.val().trim()!='' && addrem.attr('id')!='newline'){
				addrem.click(function(){
					removemedia(row);
				});
			}
			mt.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					mt.change();
				}
			});
			function update(inputobj){
				if(mt.val().trim()==''){
					// reset value to previous
					$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val(),original:''}).done(function(jsondata){
						mt.val(jsondata.MediaType);
						mtcc.val(jsondata.ColorID);
					});
					mt.effect('highlight', {color: 'salmon'}, 1500);
					mtcc.effect('highlight', {color: 'salmon'}, 1500);
				}else{
					// attempt to update
					$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val()}).done(function(data){
						if(data.trim()=='f'){ // fail
							$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val(),original:''}).done(function(jsondata){
								mt.val(jsondata.MediaType);
								mtcc.val(jsondata.ColorID);
							});
							mt.effect('highlight', {color: 'salmon'}, 1500);
							mtcc.effect('highlight', {color: 'salmon'}, 1500);
						}else if(data.trim()=='u'){ // updated
							mt.effect('highlight', {color: 'lightgreen'}, 2500);
							mtcc.effect('highlight', {color: 'lightgreen'}, 2500);
						}else{ // created
							var newitem=blankmediarow.clone();
							newitem.find('div:nth-child(2) input').val(mt.val()).attr('data',data.trim());
							newitem.find('div:nth-child(3) select').replaceWith(mtcc.clone());
							bindmediarow(newitem);
							row.before(newitem);
							newitem.find('div:nth-child(3) select').val(mtcc.val()).focus();
							if(addrem.attr('id')=='newline'){
								mt.val('');
								mtcc.val('');
							}else{
								row.remove();
							}
						}
					});
				}
			}
			mt.change(function(){
				update($(this));
			});
			mtcc.change(function(){
				var row=$(this).parent('div').parent('div');
				if(row.find('div:first-child').attr('id')!='newline'){
					update($(this));
				}else if(row.find('div:nth-child(2) input').val().trim()!=''){
					update($(this));
				}
			});
		}

		// Add a new blank row
		$('#mediatypes > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankmediarow.clone();
					// Clone the current dropdown list
					newitem.find('select[name="mediacolorcode[]"]').replaceWith((row.find('select[name="mediacolorcode[]"]').clone()));
					newitem.find('div:first-child').click(function(){
						removecolor($(this).parent('div'),false);
					});
					bindmediarow(newitem);
					row.before(newitem);
				});
			}
			bindmediarow($(this).parent('div'));
		});

		// Update color drop lists
		function updatechoices(){
			$.post('',{cclist: ''}).done(function(data){
				$('#mediatypes > div ~ div').each(function(){
					var list=$(this).find('select[name="mediacolorcode[]"]');
					var dc=list.val();
					list.html(data);
					$(this).find('select[name="mediacolorcode[]"]').val(dc);
				});
			});
		}

		// Cabling - Cable Colors

		function removecolor(rowobject,lookup){
			if(!lookup){
				rowobject.remove();
			}else{
				$.post('',{ccused: rowobject.find('div:nth-child(2) input').attr('data')}).done(function(data){
					if(data.trim()==0){
						updatechoices();
						rowobject.effect('explode', {}, 500, function(){
							$(this).remove();
						});
					}else{
						var defaultbutton={
							"<?php echo __("Clear all"); ?>": function(){
								$.post('',{cid: rowobject.find('div:nth-child(2) input').attr('data'),cc: '', ccdn: '', clear: ''}).done(function(data){
									if(data.trim()=='u'){ // success
										$('#modal').dialog("destroy");
										updatechoices();
										rowobject.effect('explode', {}, 500, function(){
											$(this).remove();
										});
									}else{ // failed to delete
										$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
										$('#modal').dialog('option','buttons',cancelbutton);
									}
								});
							}
						}
						var replacebutton={
							"<?php echo __("Replace"); ?>": function(){
								// send command to replace all connections with x
								$.post('',{cid: rowobject.find('div:nth-child(2) input').attr('data'),cc: '', ccdn: '', change: $('#modal select').val()}).done(function(data){
									if(data.trim()=='u'){ // success
										$('#modal').dialog("destroy");
										updatechoices();
										rowobject.effect('explode', {}, 500, function(){
											$(this).remove();
										});
										// Need to trigger a reload of any of the media types that had this 
										// color so they will display the new color
										$('#mediatypes > div ~ div:not(:last-child) input').val('').change();
									}else{ // failed to delete
										$('#modaltext').html('AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>');
										$('#modal').dialog('option','buttons',cancelbutton);
									}
								});
							}
						}
						var cancelbutton={
							"<?php echo __("Cancel"); ?>": function(){
								$(this).dialog("destroy");
							}
						}
<?php echo "						var modal=$('<div />', {id: 'modal', title: '".__("Code Delete Override")."'}).html('<div id=\"modaltext\">".__("This code is in use somewhere. You can either choose to clear all instances of this color being used or choose to have them replaced with another color.")." <select id=\"replaceme\"></select></div>').dialog({"; ?>
							dialogClass: 'no-close',
							appendTo: 'body',
							modal: true,
							buttons: $.extend({}, defaultbutton, cancelbutton)
						});
						var choices=$('div#mediatypes.table div:last-child div select').clone();
						choices.find('option').each(function(){
							if($(this).val()==rowobject.find('div:nth-child(2) input').attr('data')){$(this).remove();}
						});
						choices.change(function(){
							if($(this).val()==''){ // clear all
								modal.dialog('option','buttons',$.extend({}, defaultbutton, cancelbutton));
							}else{ // replace
								modal.dialog('option','buttons',$.extend({}, replacebutton, cancelbutton));
							}
						});
						modal.find($('#replaceme')).replaceWith(choices);
					}
				});
			}
		}
		var blankrow=$('<div />').html('<div><img src="images/del.gif"></div><div><input type="text" name="colorcode[]"></div><div><input type="text" name="ccdefaulttext[]"></div>');
		function bindrow(row){
			var addrem=row.find('div:first-child');
			var cc=row.find('div:nth-child(2) input');
			var ccdn=row.find('div:nth-child(3) input');
			if(cc.val().trim()!='' && addrem.attr('id')!='newline'){
				addrem.click(function(){
					removecolor(row,true);
				});
			}
			cc.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					cc.change();
				}
			});
			ccdn.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					ccdn.change();
				}
			});
			row.find('div > input').each(function(){
				// If a value changes then check it for conflicts, if no conflict update
				$(this).change(function(){
					if(cc.val().trim()!=''){
						$.post('',{cid: cc.attr('data'),cc: cc.val(), ccdn: ccdn.val()}).done(function(data){
							if(data.trim()=='f'){ // fail
								$.post('',{cid: cc.attr('data'),cc: cc.val(), ccdn: ccdn.val(),original:data.trim()}).done(function(jsondata){
									cc.val(jsondata.Name);
									ccdn.val(jsondata.DefaultNote);
								});
								cc.effect('highlight', {color: 'salmon'}, 1500);
								ccdn.effect('highlight', {color: 'salmon'}, 1500);
							}else if(data.trim()=='u'){ // updated
								cc.effect('highlight', {color: 'lightgreen'}, 2500);
								ccdn.effect('highlight', {color: 'lightgreen'}, 2500);
								// update media type color pick lists
								updatechoices();
							}else{ // created
								var newitem=blankrow.clone();
								newitem.find('div:nth-child(2) input').val(cc.val()).attr('data',data.trim());
								bindrow(newitem);
								row.before(newitem);
								newitem.find('div:nth-child(3) input').val(ccdn.val()).focus();
								if(addrem.attr('id')=='newline'){
									cc.val('');
									ccdn.val('');
								}else{
									row.remove();
								}
								// update media type color pick lists
								updatechoices();
							}
						});
					}else if(cc.val().trim()=='' && ccdn.val().trim()=='' && addrem.attr('id')!='newline'){
						// If both blanks are emptied of values and they were an existing data pair
						$.post('',{cid: cc.attr('data'),cc: cc.val(), ccdn: ccdn.val(),original:''}).done(function(jsondata){
							cc.val(jsondata.Name);
							ccdn.val(jsondata.DefaultNote);
						});
						cc.effect('highlight', {color: 'salmon'}, 1500);
						ccdn.effect('highlight', {color: 'salmon'}, 1500);
					}
				});
			});
		}
		$('#cablecolor > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankrow.clone();
					newitem.find('div:first-child').click(function(){
						removecolor($(this).parent('div'),false);
					});
					bindrow(newitem);
					row.before(newitem);
				});
			}
			bindrow($(this).parent('div'));
		});





	});

  </script>
</head>
<body>
<div id="header"></div>
<?php

	if((!isset($_GET["cab"])&&!isset($_GET["dc"])&&!isset($_GET["complete"]))||isset($_GET["dept"])){
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
   <div><select name='cabinetid' onChange='form.submit()' <?php echo $nodcdrop;  print "data=\"$cab->CabinetID\"";?>>
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
  <div><select name='assignedto' <?php echo $nodcdrop; print "data=\"$cab->AssignedTo\"";?>>
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
<p>To start normal operation of openDCIM please delete install.php from the installation directory.</p>
<p>Be sure to visit the <a href="configuration.php">Configuration</a> page to set any new defaults that may have been introduced.</p>


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
