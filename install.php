<?php
$codeversion="4.4.1";

require_once( "preflight.inc.php" );

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

// Check to see if we are doing an upgrade or an install
	$result=$dbh->prepare("SHOW TABLES;");
	$result->execute();
	if($result->rowCount()==0){ // No tables in the DB so try to install.
		$results[]=applyupdate("create.sql");
		$upgrade=false;
	}

	/*
	   v4.0 migrated fac_Users to fac_People we need to adjust for older 
	   installs that need upgrading.  The logic has remained nearly the 
	   same but this will support upgrades as well as new installs
	*/
	$test=$result->fetchAll();
	// First test for fac_People, second test for people using non-standard installs with all lower case table names
	$usePeople=($result->rowCount()>0 && !ArraySearchRecursive('fac_People',$test))?($result->rowCount()>0 && !ArraySearchRecursive('fac_people',$test))?false:true:true;

	// New install so create a user
	require_once("classes/People.class.php");

	if($usePeople){
		$person=new People();
	}else{
		require_once("installer.userconversion.inc.php");
		$person=new User();
	}
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

	if($users==0){
		$person->Name="Default Admin";
		foreach($person as $prop => $value){
			if(strstr($prop,"Admin") || strstr($prop,"Access")){
				$person->$prop=true;
			}
		}
		$person->Disabled=false;

		$person->CreatePerson();
	}
	// This will be reloading the rights for a new install but for upgrades
	// it will be the actual rights load
	$person->GetUserRights();
	// Re-read the config
	$config->Config();
// Check to see if we have any users in the database.
	$sth=$dbh->prepare("SELECT * FROM $table WHERE SiteAdmin=1;");
	$sth->execute();
	if($sth->rowCount()<1){
		// no users in the system or no users with site admin rights, either way we're missing the class of people we need
		// put stuff here like correcting for a missing site admin
		print "There are no users in the database with sufficient privileges to perform this update.  Current userid=" . $person->UserID;
		exit;
		$rightserror=1;
	}else{ // so we have users and at least one site admin
		require_once("classes/People.class.php");

		if(!$person->SiteAdmin){
			// dolemite says you aren't an admin so you can't apply the update
			print "An update has been applied to the system but the system hasn't been taken out of maintenance mode. Please contact a site Administrator to correct this issue.  Current userid=" . $person->UserID;
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

		$version="3.2";
	}
	if($version=="3.2"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-3.2-to-3.3.sql");

		// Rebuild the config table just in case.
		$config->rebuild();

		$version="3.3";
	}
	if($version=="3.3"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-3.3-to-4.0.sql");

		// Rebuild the config table just in case.
		$config->rebuild();

		// We added in some new config items and one of them is referenced in misc.
		// Reload the config;
		$config->Config();

		// We have several things to convert this time around.

		// Bring up the rest of the classes
		require_once("facilities.inc.php");

		// People conversion 
		$p=new People();
		$c=new Contact();
		$u=new User();

		$plist=$p->GetUserList();
		// Check if we have an empty fac_People table then merge if that's the case
		if(sizeof($plist)==0){
			$clist=$c->GetContactList();
			foreach( $clist as $tmpc ) {
				foreach($tmpc as $prop => $val){
					$p->$prop=$val;
				}
				// we're keeping the Contact ID so assign it to the PersonID
				$p->PersonID=$tmpc->ContactID;
				
				$u->UserID=$p->UserID;
				$u->GetUserRights();
				foreach($u as $prop => $val){
					$p->$prop=$val;
				}

				// This shouldn't be necessary but... 
				$p->MakeSafe();
				
				$sql="INSERT INTO fac_People SET PersonID=$p->PersonID, UserID=\"$p->UserID\", 
					AdminOwnDevices=$p->AdminOwnDevices, ReadAccess=$p->ReadAccess, 
					WriteAccess=$p->WriteAccess, DeleteAccess=$p->DeleteAccess, 
					ContactAdmin=$p->ContactAdmin, RackRequest=$p->RackRequest, 
					RackAdmin=$p->RackAdmin, SiteAdmin=$p->SiteAdmin, Disabled=$p->Disabled, 
					LastName=\"$p->LastName\", FirstName=\"$p->FirstName\", 
					Phone1=\"$p->Phone1\", Phone2=\"$p->Phone2\", Phone3=\"$p->Phone3\", 
					Email=\"$p->Email\";";

				$dbh->query($sql);
			}
			
			$ulist=$u->GetUserList();
			foreach($ulist as $tmpu){
				/* This time around we have to see if the User is already in the fac_People table */
				$p->UserID=$tmpu->UserID;
				if(!$p->GetPersonByUserID()){
					foreach($tmpu as $prop => $val){
						$p->$prop=$val;
					}
					// Names have changed formats between the user table and the people table
					$p->LastName=$tmpu->Name;
					
					$p->CreatePerson();
				}
			}
		}
		// END - People conversion 

		// CDU template conversion, to be done prior to device conversion
		/*
		   I made a poor asumption on the initial build of this that we'd always have fewer
		   CDU templates than device templates.  We're seeing an overlap conversion that is 
		   screwing the pooch.  This will find the highest template id from the two sets then
		   we'll jump the line on the device_template id's and get them lined up.
		 */
		$sql="SELECT TemplateID FROM fac_CDUTemplate UNION SELECT TemplateID FROM 
			fac_DeviceTemplate ORDER BY TemplateID DESC LIMIT 1;";
		$baseid=$dbh->query($sql)->fetchColumn();

		class PowerTemplate extends DeviceTemplate {
			function CreateTemplate($templateid=null){
				global $dbh;
				
				$this->MakeSafe();

				$sqlinsert=(is_null($templateid))?'':" TemplateID=$templateid,";

				$sql="INSERT INTO fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID, 
					Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
					Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
					SNMPVersion=\"$this->SNMPVersion\", PSCount=$this->PSCount, 
					NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
					FrontPictureFile=\"$this->FrontPictureFile\", 
					RearPictureFile=\"$this->RearPictureFile\",$sqlinsert
					ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots;";

				if(!$dbh->exec($sql)){
					error_log( "SQL Error: " . $sql );
					return false;
				}else{
					$this->TemplateID=$dbh->lastInsertId();

					(class_exists('LogActions'))?LogActions::LogThis($this):'';
					$this->MakeDisplay();
					return true;
				}
			}

			static function Convert($row){
				$ct=new stdClass();
				$ct->TemplateID=$row["TemplateID"];
				$ct->ManufacturerID=$row["ManufacturerID"];
				$ct->Model=$row["Model"];
				$ct->PSCount=$row["NumOutlets"];
				$ct->SNMPVersion=$row["SNMPVersion"];
				return $ct;
			}
		}

		$converted=array(); //index old id, value new id
		$sql="SELECT * FROM fac_CDUTemplate;";
		foreach($dbh->query($sql) as $cdutemplate){
			$ct=PowerTemplate::Convert($cdutemplate);
			$dt=new PowerTemplate();
			$dt->TemplateID=++$baseid;
			$dt->ManufacturerID=$ct->ManufacturerID;
			$dt->Model="CDU $ct->Model";
			$dt->PSCount=$ct->PSCount;
			$dt->DeviceType="CDU";
			$dt->SNMPVersion=$ct->SNMPVersion;
			$dt->CreateTemplate($dt->TemplateID);
			$converted[$ct->TemplateID]=$dt->TemplateID;
		}

		// Update all the records with their new templateid
		foreach($converted as $oldid => $newid){
			$dbh->query("UPDATE fac_CDUTemplate SET TemplateID=$newid WHERE TemplateID=$oldid;");
			$dbh->query("UPDATE fac_PowerDistribution SET TemplateID=$newid WHERE TemplateID=$oldid");
		}
		// END - CDU template conversion

		// Store a list of existing CDU ids and their converted DeviceID
		$ConvertedCDUs=array();
		// Store a list of the cdu template ids and the number of power connections they support
		$CDUTemplates=array();
		// List of ports we are going to create for every device in the system
		$PowerPorts=array();
		// These should only apply to me but the possibility exists
		$PreNamedPorts=array();

		class PowerDevice extends Device {
			/*
				to be efficient we don't want to create ports right now so we're extending 
				the class to override the create function
			*/
			function CreateDevice(){
				global $dbh;
				
				$this->MakeSafe();
				
				$this->Label=transform($this->Label);
				$this->SerialNo=transform($this->SerialNo);
				$this->AssetTag=transform($this->AssetTag);
				
				$sql="INSERT INTO fac_Device SET Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", AssetTag=\"$this->AssetTag\", 
					PrimaryIP=\"$this->PrimaryIP\", SNMPCommunity=\"$this->SNMPCommunity\", Hypervisor=$this->Hypervisor, Owner=$this->Owner, 
					EscalationTimeID=$this->EscalationTimeID, EscalationID=$this->EscalationID, PrimaryContact=$this->PrimaryContact, 
					Cabinet=$this->Cabinet, Position=$this->Position, Height=$this->Height, Ports=$this->Ports, 
					FirstPortNum=$this->FirstPortNum, TemplateID=$this->TemplateID, NominalWatts=$this->NominalWatts, 
					PowerSupplyCount=$this->PowerSupplyCount, DeviceType=\"$this->DeviceType\", ChassisSlots=$this->ChassisSlots, 
					RearChassisSlots=$this->RearChassisSlots,ParentDevice=$this->ParentDevice, 
					MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
					InstallDate=\"".date("Y-m-d", strtotime($this->InstallDate))."\", WarrantyCo=\"$this->WarrantyCo\", 
					WarrantyExpire=\"".date("Y-m-d", strtotime($this->WarrantyExpire))."\", Notes=\"$this->Notes\", 
					Reservation=$this->Reservation, HalfDepth=$this->HalfDepth, BackSide=$this->BackSide;";

				if(!$dbh->exec($sql)){
					$info=$dbh->errorInfo();

					error_log( "PDO Error: {$info[2]} SQL=$sql" );
					return false;
				}

				$this->DeviceID=$dbh->lastInsertId();

				(class_exists('LogActions'))?LogActions::LogThis($this):'';

				return $this->DeviceID;
			}
		}

		// Create new devices from existing CDUs
		$sql="SELECT * FROM fac_PowerDistribution;";
		foreach($dbh->query($sql) as $row){
			$dev=new PowerDevice();
			$dev->Label=$row['Label'];
			$dev->Cabinet=$row['CabinetID'];
			$dev->TemplateID=$row['TemplateID'];
			$dev->PrimaryIP=$row['IPAddress'];
			$dev->SNMPCommunity=$row['SNMPCommunity'];
			$dev->Position=0;
			$dev->Height=0;
			$dev->Ports=1;
			if(!isset($CDUTemplates[$dev->TemplateID])){
				$CDUTemplates[$dev->TemplateID]=$dbh->query("SELECT NumOutlets FROM fac_CDUTemplate WHERE TemplateID=$dev->TemplateID LIMIT 1;")->fetchColumn();
			}
			$dev->PowerSupplyCount=$CDUTemplates[$dev->TemplateID];
			$dev->PowerSupplyCount;
			$dev->DeviceType='CDU';
			$ConvertedCDUs[$row['PDUID']]=$dev->CreateDevice();
		}

		// Create a list of all ports that we need to create, no need to look at children or any device with no defined power supplies
		$sql="SELECT DeviceID, PowerSupplyCount FROM fac_Device WHERE ParentDevice=0 AND PowerSupplyCount>0;";
		foreach($dbh->query($sql) as $row){
			for($x=1;$x<=$row['PowerSupplyCount'];$x++){
				$PowerPorts[$row['DeviceID']][$x]['label']=$x;
			}
		}

		function workdamnit($numeric=true,&$PreNamedPorts,&$PowerPorts,&$ConvertedCDUs){
			// a PDUID of 0 is considered an error, data fragment, etc.  Fuck em, not dealing with em.
			global $dbh;
			$sql="SELECT * FROM fac_PowerConnection;";
			foreach($dbh->query($sql) as $row){
				// something is going stupid so assign everythign to variables
				$pduid=intval($row['PDUID']);
				$pdupos=$row['PDUPosition'];
				$devid=intval($row['DeviceID']);
				$devcon=$row['DeviceConnNumber'];
				$newpduid=$ConvertedCDUs[$pduid];
				
				$port='';
				if(is_numeric($pdupos) && $numeric && $pduid>0){
					$port=$pdupos;
				}elseif(!is_numeric($pdupos) && !$numeric && $pduid>0){
					$newPDUID=$newpduid;
					if(!isset($PreNamedPorts[$newPDUID][$pdupos])){
						// Move the array pointer to the end of the ports array
						end($PowerPorts[$newPDUID]);
						$max=key($PowerPorts[$newPDUID]);
						++$max;
						// Create a new port for the named port, this will likely extend past the valid amount of ports on the device.
						$PowerPorts[$newPDUID][$max]['label']=$pdupos;
						// Store a pointer between the name and new port index
						$PreNamedPorts[$newPDUID][$pdupos]=$max;
					}
					$port=$PreNamedPorts[$newPDUID][$pdupos];
				}
				if((is_numeric($pdupos) && $numeric) || (!is_numeric($pdupos) && !$numeric) && $pduid>0){
					// Create primary connections
					$PowerPorts[$devid][$devcon]['ConnectedDeviceID']=$newpduid;
					$PowerPorts[$devid][$devcon]['ConnectedPort']=$port;
					// Create reverse of primary
					$PowerPorts[$newpduid][$port]['ConnectedDeviceID']=$devid;
					$PowerPorts[$newpduid][$port]['ConnectedPort']=$devcon;
				}
			}
		}
		// We need to get a list of all existing power connections
		workdamnit(true,$PreNamedPorts,$PowerPorts,$ConvertedCDUs); // First time through setting up all numeric ports
		workdamnit(false,$PreNamedPorts,$PowerPorts,$ConvertedCDUs); // Run through again but this time only deal with named ports and append them to the end of the numeric

/* 
 * Debug Info

		print "Converted CDUs:\n<br>";
		print_r($ConvertedCDUs);

		print "Port list:\n<br>";
		print_r($PowerPorts);

		print "SQL entries:\n<br>";
 */
		$n=1; $insertsql=''; $insertlimit=100;
		foreach($PowerPorts as $DeviceID => $PowerPort){
			foreach($PowerPort as $PortNum => $PortDetails){
				$label=(isset($PortDetails['label']))?$PortDetails['label']:$PortNum;
				$cdevice=(isset($PortDetails['ConnectedDeviceID']))?$PortDetails['ConnectedDeviceID']:'NULL';
				$cport=(isset($PortDetails['ConnectedPort']))?$PortDetails['ConnectedPort']:'NULL';

				$insertsql.="($DeviceID,$PortNum,\"$label\",$cdevice,$cport,\"\")";
				if($n%$insertlimit!=0){
					$insertsql.=" ,";
				}else{
					$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
// Debug for sql
//					print "$insertsql\n\n<br><br>";
//					print_r($dbh->errorInfo());
					$insertsql='';
				}
				$n++;
			}
		}
		//do one last insert
		$insertsql=substr($insertsql, 0, -1);// shave off that last comma
		$dbh->exec('INSERT INTO fac_PowerPorts VALUES'.$insertsql);
// Debug for sql
//					print "$insertsql\n\n<br><br>";
//					print_r($dbh->errorInfo());

		// Update all the records with their new deviceid
		foreach($ConvertedCDUs as $oldid => $newid){
			$dbh->query("UPDATE fac_PowerDistribution SET PDUID = '$newid' WHERE PDUID=$oldid;");
		}
		
		// Since we moved SNMPVersion out of the subtemplates and into the main one, we need one last cleanup
		$st = $dbh->prepare( "select * from fac_DeviceTemplate where DeviceType='CDU'" );
		$st->execute();
		$up = $dbh->prepare( "update fac_Device set SNMPVersion=:SNMPVersion where TemplateID=:TemplateID" );
		
		while ( $row = $st->fetch() ) {
			$up->execute( array( ":SNMPVersion"=>$row["SNMPVersion"], ":TemplateID"=>$row["TemplateID"] ) );
		}
		
		// END - CDU template conversion, to be done prior to device conversion

		// Sensor template conversion

        // Step one - convert individual SensorTemplates into just Templates
        $s = $dbh->prepare( "select * from fac_SensorTemplate" );
        $s->execute();

        while ( $row = $s->fetch() ) {
                // Create fresh instances
                $st = new SensorTemplate();
                $dt = new DeviceTemplate();

                $dt->ManufacturerID = $row["ManufacturerID"];
                $dt->Model = $row["Model"];
                $dt->Height = 0;
                $dt->Weight = 0;
                $dt->Wattage = 0;
                $dt->DeviceType = "Sensor";
                $dt->PSCount = 0;
                $dt->NumPorts = 0;
                $dt->Notes = "Converted from version 3.3 format.";
                $dt->FrontPictureFile = '';
                $dt->RearPictureFile = '';
                $dt->ChassisSlots = 0;
                $dt->RearChassisSlots = 0;
                $dt->CreateTemplate();

                // The DeviceTemplate::CreateTemplate() method created a new SensorTemplate already
				if ( $dt->TemplateID < 1 ) {
					error_log( "DeviceTemplate creation failed." );
				} else {
					$st->TemplateID = $dt->TemplateID;
					$st->GetTemplate();
					$st->SNMPVersion = $row["SNMPVersion"];
					$st->TemperatureOID = $row["TemperatureOID"];
					$st->HumidityOID = $row["HumidityOID"];
					$st->TempMultiplier = $row["TempMultiplier"];
					$st->HumidityMultiplier = $row["HumidityMultiplier"];
					$st->mUnits = $row["mUnits"];
					$st->UpdateTemplate();

					// Even though this is just temporary, update all existing references to the new TemplateID
					$sql = "update fac_Cabinet set SensorTemplateID=:NewID where SensorTemplateID=:OldID";
					$q = $dbh->prepare( $sql );
					$q->execute( array( ":NewID"=>$dt->TemplateID, ":OldID"=>$row["TemplateID"] ) );

					// Delete the original template entry in the fac_SensorTemplate table
					$st->TemplateID = $row["TemplateID"];
					$st->DeleteTemplate();
				}
        }
		
		$ds = $dbh->prepare( "alter table fac_SensorTemplate drop column SNMPVersion" );

        // Step two - pull sensors from the Cabinets and create as new devices
        $s = $dbh->prepare( "select * from fac_Cabinet where SensorIPAddress!=''" );
        $s->execute();

        while ( $row = $s->fetch() ) {
                $dev = new Device();

                $dev->Label = $row["Location"] . " - Sensor";
                $dev->SNMPCommunity = $row["SensorCommunity"];
                $dev->PrimaryIP = $row["SensorIPAddress"];
                $dev->TemplateID = $row["SensorTemplateID"];
                $dev->DeviceType = "Sensor";
                $dev->Cabinet = $row["CabinetID"];

                $dev->CreateDevice();
        }

		// END - Sensor template conversion

		// Power panel conversion
		$dbh->beginTransaction();
		$ss=$dbh->prepare("select * from fac_PowerSource");
		$ss->execute();

		$ps=$dbh->prepare("insert into fac_PowerPanel set PanelLabel=:PanelLabel");
		$us=$dbh->prepare("update fac_PowerPanel set ParentPanelID=:PanelID where PowerSourceID=:SourceID");
		while($row=$ss->fetch()){
			$ps->execute(array(":PanelLabel"=>$row["SourceName"]));
			$us->execute(array(":PanelID"=>$dbh->LastInsertId(),":SourceID"=>$row["PowerSourceID"]));
		}
		$dbh->commit();
		// END - Power panel conversion

		// Get rid of the original PowerSource table since it is no longer in use
		$drop = $dbh->prepare( "drop table fac_PowerSource" );
		$drop->execute();
		$drop = $dbh->prepare( "alter table fac_PowerPanel drop column PowerSourceID" );
		$drop->execute();


		// Make sure all child devices have updated cabinet information
		$sql="SELECT DISTINCT ParentDevice AS DeviceID FROM fac_Device WHERE 
			ParentDevice>0 ORDER BY ParentDevice ASC;";
		foreach($dbh->query($sql) as $row){
			$d=new Device();
			$d->DeviceID=$row['DeviceID'];
			$d->GetDevice();
			$d->UpdateDevice();
		}

		$version="4.0";
	}
	if($version=="4.0"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-4.0-to-4.0.1.sql");

		// Rebuild the config table just in case.
		$config->rebuild();
		$version="4.0.1";
	}
	if($version=="4.0.1"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-4.0.1-to-4.1.sql");

		// Rebuild the config table just in case.
		$config->rebuild();
		$version="4.1";
	}
	if($version=="4.1"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-4.1-to-4.1.1.sql");

		// Rebuild the config table just in case.
		$config->rebuild();
		$version="4.1.1";
	}
	if($version=="4.1.1"){
		// First apply the schema updates needed.
		$results[]=applyupdate("db-4.1.1-to-4.2.sql");

		// Rebuild the config table just in case.
		$config->rebuild();		
		$version="4.2";
	}
	if($version=="4.2"){
		$results[]=applyupdate("db-4.2-to-4.3.sql");

		$config->rebuild();
		$version="4.3";
	}
	if($version=="4.3"){
		$results[]=applyupdate("db-4.3-to-4.3.1.sql");

		$config->rebuild();
	}
	if($version=="4.3.1"){
		$results[]=applyupdate("db-4.3.1-to-4.4.sql");

		$config->rebuild();
	}
	if($version=="4.4"){
		$results[]=applyupdate("db-4.4-to-4.5.sql");

		$st = $dbh->prepare( "select * from fac_Decommission order by SurplusDate ASC" );
		$dt = $dbh->prepare( "insert into fac_Device set Label=:Label, SerialNo=:SerialNo, AssetTag=:AssetTag, Status='Disposed'" );
		$lt = $dbh->prepare( "insert into fac_DispositionMembership values (1, :DeviceID, :DispositionDate, :DisposedBy )");

		// Fetch from the legacy surplus table
		$st->execute( array() );
		while ( $row = $st->fetch() ) {
			// Insert a new device for this one
			$dt->execute( array( ":Label"=>$row["Label"],
					":SerialNo"=>$row["SerialNo"],
					":AssetTag"=>$row["AssetTag"] ));

			// Get the new DeviceID (yes, I know, these all were originally in the fac_Device table)
			$devID = $dbh->lastInsertId();

			// Now add the logging / membership record
			$lt->execute( array( ":DeviceID"=>$devID,
				":DispositionDate"=>$row["SurplusDate"],
				":DisposedBy"=>$row["UserID"]));
		}

		// Now shit can the old table
		$dbh->query( "DROP TABLE fac_Decommission" );

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
<p>If you wish to synchronize with the online repository, you must first pull the current listing of Manufacturer Names, which requires an active connection to the internet
from the server running openDCIM.  In order to allow you to restrict connections as much as possible, the entire process runs across an SSL connection to
https://repository.opendcim.org.  This process is managed through the Template Management -> Repository Sync interface.  Once you have a manufacturer synchronized, you may download individual templates for that manufacturer.</p>
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
