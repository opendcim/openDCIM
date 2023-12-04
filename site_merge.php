<?php
/*
	Script to merge multiple openDCIM installations into one.

	Both the child (to be imported) and parent (destination) must be running the same
	version of openDCIM, otherwise the objects won't align correctly to the database.

	The script is to be run on the parent installation, using the db values already set
	in the db.config.php file.   The child db information is to be set below and the system
	that this script runs on will need network access to that db server.

	All objects will be placed within a newly created container in the parent that matches
	the childPrefix.  After the import is completed, the container can be renamed in the UI.
	Additional objects will also have the childPrefix inserted, such as Departments, in order
	to avoid conflicts.  (ie - both sites have an 'Operations' department)

	All assets (drawings and pictures) will need to be manually copied over on the filesystem.

	Audit logs are NOT copied over, as everything imported is basically a fresh start.  If you
	need your history from before the import, save the last db dump from the imported site.

*/

if ( php_sapi_name() != "cli" ) {
	echo "This script may only be run from the command line.";
	header( "Refresh: 5; url=" . redirect());    
}

require_once('db.inc.php');
require_once('facilities.inc.php');

global $config;
$config = new Config();

# Set these variables to the correct values
$childDBServer = "localhost";
$childDBName = "dcimchild";
$childDBPort = 3306;
$childDBUser = "dcim";
$childDBPassword = "dcim";
$childCountryCode = "CA";
$childPrefix = "Canada";

$pdo_options=array(
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
# Optionally add SSL requirements
#	PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
#	PDO::MYSQL_ATTR_SSL_CAPATH => "/etc/ssl/certs",
	PDO::ATTR_PERSISTENT => true
);

# Do not change anything below here

# Establish the child database connection

try{
	$childPDOConnect="mysql:host=$childDBServer;port=$dbport;dbname=$childDBName";
	$childDBH=@new PDO($childPDOConnect,$childDBUser,$childDBPassword,$pdo_options);
	$childDBH->exec("SET @@SESSION.sql_mode = ''");
}catch(PDOException $e){
	printf( "Error!  %s\n", $e->getMessage() );
	die();
}

# Make sure that we have all of the Colors and Media Types present in the target
$sql = "select * from fac_ColorCoding";
$childStmt = $childDBH->prepare( $sql );
$childStmt->execute();

$colorMap = array();
$colorMap[0] = 0;

while ( $row = $childStmt->fetch() ) {
	$targetC = new ColorCoding;
	$targetC->Name = $row["Name"];
	if ( ! $targetC->GetCodeByName() ) {
		$targetC->Name = $row["Name"];
		$targetC->DefaultNote = $row["DefaultNote"];
		$targetC->CreateCode();
		error_log( "Created new ColorCode ID of ".$targetC->ColorID." for ".$targetC->Name );
	}
	$colorMap[$row["ColorID"]] = $targetC->ColorID;
}

error_log( "Merged in ".sizeof($colorMap)." Color Codes." );

$sql = "select * from fac_MediaTypes";
$childStmt = $childDBH->prepare($sql);
$childStmt->execute();

$mediaMap = array();
$mediaMap[0] = 0;

while ( $row = $childStmt->fetch() )  {
	$targetM = new MediaTypes;
	$targetM->MediaType = $row["MediaType"];
	if ( ! $targetM->GetTypeByName() ) {
		$targetM->MediaType = $row["MediaType"];
		$targetM->ColorID = $colorMap[$row["ColorID"]];
		$targetM->CreateType();
		error_log( "Created new MediaType ID of ".$targetM->MediaID." for ".$targetM->MediaType );
	}
	$mediaMap[$row["MediaID"]] = $targetM->MediaID;
}

error_log( "Merged in ".sizeof($mediaMap)." Media Types." );

# Tags
$sql = "select * from fac_Tags";
$childStmt = $childDBH->prepare( $sql );
$childStmt->execute();

$tagMap = array();

while ( $row = $childStmt->fetch() ) {
	$targetTag = new Tags;
	if ( ! $ttag = $targetTag->FindID( $row["Name"] )) {
		$targetTag->Name = $row["Name"];
		$targetTag->CreateTag();
		$tagMap[$row["TagID"]] = $targetTag->TagID;
	} else {
		$tagMap[$row["TagID"]] = $ttag;
	}
}

error_log( "Merged in ".sizeof($tagMap)." Tags." );

# Add in the Manufacturer Names along with the mapping to the old db
$sql = "select * from fac_Manufacturer";
$childStmt = $childDBH->prepare( $sql );
$childStmt->execute();

$mfgMap = array();

while ( $row = $childStmt->fetch() ) {
	$targetM = new Manufacturer;
	# Search for the exact spelling of this one in the existing db, if not, add it
	$targetM->Name = $row["Name"];
	error_log( "Searching for Manufacturer Name: ".$targetM->Name );
	if ( ! $targetM->GetManufacturerByName() ) {
		$targetM->GlobalID = null;
		$targetM->SubscribeToUpdates = null;
		$targetM->CreateManufacturer();
		error_log( "Created new Manufacturer ID of ".$targetM->ManufacturerID." for ".$targetM->Name );
	}
	$mfgMap[$row["ManufacturerID"]] = $targetM->ManufacturerID;
}

error_log( "Merged in ".sizeof($mfgMap)." Manufacturers." );

# Add in the Templates now that we have our Manufacturer Mapping complete

$sql = "select * from fac_DeviceTemplate";
$childStmt = $childDBH->prepare( $sql );
$childStmt->execute();

$dtMap = array();

while ( $row = $childStmt->fetch() ) {
	$targetDT = new DeviceTemplate;
	$targetDT->ManufacturerID = $mfgMap[$row["ManufacturerID"]];
	$targetDT->Model = $row["Model"];
	if ( ! $targetDT->GetTemplateByMfgModel() ) {
		foreach( $row as $prop=>$value ) {
			if ( ! in_array( $prop, array("TemplateID", "ManufacturerID", "Model")) ) {
				$targetDT->$prop = $value;
			}
		}
		$targetDT->CreateTemplate();

		# Now also make the TemplatePorts and PowerPorts that are associated with it
		$targetTP = new TemplatePorts;
		$tpSQL = "select * from fac_TemplatePorts where TemplateID=:templateid";
		$tpStmt = $childDBH->prepare( $tpSQL );
		$tpStmt->execute( array( ":templateid"=>$row["TemplateID"]));
		while ($tpRow = $tpStmt->fetch() ) {
			$targetTP->TemplateID = $targetDT->TemplateID;
			$targetTP->PortNumber = $tpRow["PortNumber"];
			$targetTP->Label = $tpRow["Label"];
			$targetTP->MediaID = $mediaMap[$tpRow["MediaID"]];
			$targetTP->ColorID = $colorMap[$tpRow["ColorID"]];
			$targetTP->Notes = $tpRow["Notes"];
			$targetTP->createPort();
		}

		$targetPP = new TemplatePowerPorts;
		$tpSQL = "select * from fac_TemplatePowerPorts where TemplateID=:templateid";
		$tpStmt = $childDBH->prepare( $tpSQL );
		$tpStmt->execute( array( ":templateid"=>$row["TemplateID"]));
		while ($tpRow = $tpStmt->fetch() ) {
			$targetPP->TemplateID = $targetDT->TemplateID;
			$targetPP->PortNumber = $tpRow["PortNumber"];
			$targetPP->Label = $tpRow["Label"];
			$targetPP->PortNotes = $tpRow["PortNotes"];
			$targetPP->createPort();
		}

		error_log( "Created new DeviceTemplate ID of ".$targetDT->TemplateID." for ".$targetDT->Model );
	}
	$dtMap[$row["TemplateID"]] = $targetDT->TemplateID;
}

# People

$pplMap = array();
$pplMap[0] = 0;
$childPerson = new People();
$pplSQL = "select * from fac_People";
$pplStmt = $childDBH->prepare( $pplSQL );
$pplStmt->execute();

while ( $row = $pplStmt->fetch() ) {
	foreach( $row as $prop=>$value ) {
		$childPerson->$prop = $value;
	}

	if ( $childPerson->GetPersonByUserID() ) {
		error_log( "WARNING:  UserID ".$childPerson->UserID. " exists in target database already.  Skipped importing." );
	} else {
		// Set the default country for the imported person
		$childPerson->countryCode = $childCountryCode;
		$childPerson->CreatePerson();
	}

	$pplMap[$row["PersonID"]] = $childPerson->PersonID;
}

error_log( "Final total of ".sizeof($pplMap)." People records." );

# Departments and their Memberships
$depMap = array();
$depSQL = "select * from fac_Department";
$depStmt = $childDBH->prepare( $depSQL );
$depStmt->execute();

while ( $row = $depStmt->fetch() ) {
	$childDept = new Department();
	$childDept->Name = $row["Name"];

	if ( $childDept->GetDeptByName() ) {
		error_log( "WARNING:  Department name ".$childDept->Name." already exists.  Skipping import." );
	} else {
		foreach( $row as $prop=>$value ) {
			$childDept->$prop = $value;
		}
		$childDept->DeptID = 0;
		error_log( "Adding Department ".$childDept->Name." to database." );
		$childDept->CreateDepartment();
	}
	$depMap[$row["DeptID"]] = $childDept->DeptID;
}

$SQL = "select from fac_DeptContacts";
$stmt = $childDBH->prepare( $SQL );
$dmSQL = "insert into fac_DeptContacts set DeptID=:deptid, ContactID=:personid";
$dmStmt = $dbh->prepare( $dmSQL );

while ( $row = $stmt->fetch() ) {
	$dmStmt->execute( array( ":deptid"=>$depMap[$row["DeptID"]], ":personid"=>$pplMap[$row["ContactID"]] ) );
}

error_log( "Merged in ".sizeof($depMap)." Departments and their memberships." );

# Create the new top level container that everything else will fit into
$pContainer = new Container();
$pContainer->Name = $childPrefix;
$pContainer->countryCode = $childCountryCode;
$pContainer->CreateContainer();

# All top level Containers in the child will now be mapped to the newly created container under the parent
$containerMap = array();
$containerMap[0] = $pContainer->ContainerID;

# Now start creating all of the subsequent Containers

$cSQL = "select * from fac_Container";
$cStmt = $childDBH->prepare( $cSQL );
$cStmt->execute();

// In case there are no containers in the child, go ahead and initialize
$targetC = new Container;

while ( $row = $cStmt->fetch() ) {
	$targetC = new Container;
	$targetC->Name = $row["Name"];
	$targetC->DrawingFileName = $row["DrawingFileName"];
	$targetC->MapX = $row["MapX"];
	$targetC->MapY = $row["MapY"];
	$targetC->ParentID = $row["ParentID"]; # This is the OLD ID, but we will reconcile after all containers are built
	$targetC->CreateContainer();
	error_log( "Created new Container ID of ".$targetC->ContainerID." for ".$targetC->Name );

	$containerMap[$row["ContainerID"]] = $targetC->ContainerID;
}

# Reconciliation - remap the ParentID from the old value to the new one based on the mapping
foreach( $containerMap as $oldVal=>$newVal ) {
	$targetC->ContainerID = $newVal;
	$targetC->GetContainer();
	$targetC->ParentID = $containerMap[$targetC->ParentID];
	$targetC->UpdateContainer();
}

# Next up is Data Centers

$dcMap = array();
$dcSQL = "select * from fac_DataCenter";
$dcStmt = $childDBH->prepare( $dcSQL );
$dcStmt->execute();

while ( $row = $dcStmt->fetch() ) {
	$targetDC = new DataCenter;
	foreach( $row as $prop=>$value ) {
		if ( ! in_array( $prop, array("DataCenterID", "ContainerID")) ) {
			$targetDC->$prop = $value;
		}
	}
	$targetDC->ContainerID = $containerMap[$row["ContainerID"]];
	$targetDC->CreateDataCenter();
	error_log( "Created new DataCenter ID of ".$targetDC->DataCenterID." for ".$targetDC->Name );

	$dcMap[$row["DataCenterID"]] = $targetDC->DataCenterID;
}

# Now Zones

$zMap = array();
$zSQL = "select * from fac_Zone";
$zStmt = $childDBH->prepare( $zSQL );
$zStmt->execute();

while ( $row = $zStmt->fetch() ) {
	$targetZ = new Zone;
	foreach( $row as $prop=>$value ) {
		if ( ! in_array( $prop, array( "ZoneID", "DataCenterID" )) ) {
			$targetZ->$prop = $value;
		}
	}
	$targetZ->DataCenterID = $dcMap[$row["DataCenterID"]];

	$targetZ->CreateZone();
	error_log( "Created new Zone ID of ".$targetZ->ZoneID." for Zone ".$targetZ->Description );

	$zMap[$row["ZoneID"]] = $targetZ->ZoneID;
}

# CabinetRows

$cabrowMap = array();
$cSQL = "select * from fac_CabRow";
$cStmt = $childDBH->prepare( $cSQL );
$cStmt->execute();

while ( $row = $cStmt->fetch() ) {
	$targetRow = new CabRow;
	$targetRow->Name = $row["Name"];
	$targetRow->DataCenterID = $dcMap[$row["DataCenterID"]];
	$targetRow->ZoneID = $zMap[$row["ZoneID"]];
	$targetRow->CreateCabRow();
	error_log( "Created new CabinetRow ID of ".$targetRow->CabRowID." for Row ".$targetRow->Name );

	$cabrowMap[$row["CabRowID"]] = $targetRow->CabRowID;
}

# Power Panels

$ppMap = array();
$ppMap[0] = 0;
$ppSQL = "select * from fac_PowerPanel";
$ppStmt = $childDBH->prepare( $ppSQL );
$ppStmt->execute();

while ( $row = $ppStmt->fetch() ) {
	$targetPP = new PowerPanel;
	foreach( $row as $prop=>$value ) {
		if ( ! in_array( $prop, array( "PanelID", "MapDataCenterID", "PanelLabel" )) ) {
			$targetPP->$prop = $value;
		}
	}
	if ( $row["MapDataCenterID"] > 0 ) {
		$targetPP->MapDataCenterID = $dcMap[$row["MapDataCenterID"]];
	}
	# Because panel numbering is fairly standard, add the prefix onto the imported ones to keep from having name conflicts
	$targetPP->PanelLabel = $childPrefix.$row["PanelLabel"];

	$targetPP->createPanel();
	error_log( "Created new Power Panel ID of ".$targetPP->PanelID." for Panel ".$targetPP->PanelLabel );

	$ppMap[$row["PanelID"]] = $targetPP->PanelID;
}

# Just like with the Containers, since the parent panel could have been made after the child, we have to reconcile after all are pulled in
foreach( $ppMap as $oldVal=>$newVal ) {
	$targetPP->PanelID = $newVal;
	$targetPP->getPanel();
	$targetPP->ParentPanelID = $ppMap[$targetPP->ParentPanelID];
	$targetPP->updatePanel();
}

# Cabinets

$cabMap = array();
$cabSQL = "select * from fac_Cabinet";
$cabStmt = $childDBH->prepare( $cabSQL );
$cabStmt->execute();

while ( $row = $cabStmt->fetch() ) {
	$targetCab = new Cabinet;
	foreach( $row as $prop=>$value ) {
		if ( ! in_array( $prop, array( "DataCenterID", "ZoneID", "CabRowID" )) ) {
			$targetCab->$prop = $value;
		}
	}
	$targetCab->DataCenterID = $dcMap[$row["DataCenterID"]];
	$targetCab->ZoneID = $zMap[$row["ZoneID"]];
	$targetCab->CabRowID = $cabrowMap[$row["CabRowID"]];

	$targetCab->CreateCabinet();

	$cabMap[$row["CabinetID"]] = $targetCab->CabinetID;
}

error_log( "Merged in ".sizeof($cabMap)." Cabinets." );

# CabinetTags

$cabTagSQL = "select * from fac_CabinetTags";
$cabTagStmt = $childDBH->prepare( $cabTagSQL );
$cabTagStmt->execute();

$newCabTagSQL = "insert into fac_CabinetTags set CabinetID=:cabinetid and TagID=:tagid";
$newCabStmt = $dbh->prepare( $newCabTagSQL );

while ( $row = $cabStmt->fetch() ) {
	$newCabStmt->execute( array( ":cabinetid"=>$cabMap[$row["CabinetID"]], ":tagid"=>$tagMap[$row["TagID"]] ));
}
 
# Now the nasty stuff...  Devices
$devMap = array();
$devMap[0] = 0;
$devSQL = "select * from fac_Device";
$devStmt = $childDBH->prepare( $devSQL );
$devStmt->execute();

# Port Connections will have to be reconciled after ALL of the devices are created

while ( $row = $devStmt->fetch() ) {
	$targetDev = new Device;
	foreach( $row as $prop=>$value ) {
		if ( ! in_array( $prop, array( "DeviceID", "Owner", "PrimaryContact", "Cabinet", "TemplateID")) ) {
			$targetDev->$prop = $value;
		}
	}
	$targetDev->Owner = $depMap[$row["Owner"]];
	$targetDev->PrimaryContact = $pplMap[$row["PrimaryContact"]];
	$targetDev->Cabinet = $cabMap[$row["Cabinet"]];
	$targetDev->TemplateID = $dtMap[$row["TemplateID"]];
	$targetDev->CreateDevice();
	error_log( "Created new DeviceID of ".$targetDev->DeviceID." for Device ".$targetDev->Label );

	$devMap[$row["DeviceID"]] = $targetDev->DeviceID;
}

# Now reconcile
error_log( "Beginning device reconciliation process, this could take some time." );

foreach( $devMap as $oldVal=>$newVal ) {
	$targetDev->DeviceID = $newVal;
	$targetDev->GetDevice();
	if ( $targetDev->ParentDevice > 0 ) {
		$targetDev->ParentDevice = $devMap[$targetDev->ParentDevice];
	}
	$targetDev->UpdateDevice();
}

# Now map the Device Port and Power Port connections
$sql = "select * from fac_Ports";
$stmt = $childDBH->prepare( $sql );
$stmt->execute();

$devPortSQL = "insert into fac_Ports set DeviceID=:DeviceID, PortNumber=:PortNumber, Label=:Label, MediaID=:MediaID, ColorID=:ColorID, ConnectedDeviceID=:ConnectedDeviceID, ConnectedPort=:ConnectedPort, Notes=:Notes on duplicate key update DeviceID=:DeviceID, PortNumber=:PortNumber, Label=:Label, MediaID=:MediaID, ColorID=:ColorID, ConnectedDeviceID=:ConnectedDeviceID, ConnectedPort=:ConnectedPort, Notes=:Notes";
$devPortStmt = $dbh->prepare( $devPortSQL );

while ( $row = $stmt->fetch() ) {
	$params = array( ":DeviceID"=>$devMap[$row["DeviceID"]],
					":PortNumber"=>$row["PortNumber"],
					":Label"=>$row["Label"],
					":MediaID"=>$mediaMap[$row["MediaID"]],
					":ColorID"=>$colorMap[$row["ColorID"]],
					":ConnectedDeviceID"=>$devMap[intval($row["ConnectedDeviceID"])],
					":ConnectedPort"=>$row["ConnectedPort"],
					":Notes"=>$row["Notes"] );
	$devPortStmt->execute( $params );
}

$sql = "select * from fac_PowerPorts";
$stmt = $childDBH->prepare( $sql );
$stmt->execute();

$pwrPortSQL = "insert into fac_PowerPorts set DeviceID=:DeviceID, PortNumber=:PortNumber, Label=:Label, ConnectedDeviceID=:ConnectedDeviceID, ConnectedPort=:ConnectedPort, Notes=:Notes on duplicate key update DeviceID=:DeviceID, PortNumber=:PortNumber, Label=:Label, ConnectedDeviceID=:ConnectedDeviceID, ConnectedPort=:ConnectedPort, Notes=:Notes";
$pwrPortStmt = $dbh->prepare( $pwrPortSQL );

while ( $row = $stmt->fetch() ) {
	$params = array( ":DeviceID"=>$devMap[$row["DeviceID"]],
					":PortNumber"=>$row["PortNumber"],
					":Label"=>$row["Label"],
					":ConnectedDeviceID"=>$devMap[intval($row["ConnectedDeviceID"])],
					":ConnectedPort"=>$row["ConnectedPort"],
					":Notes"=>$row["Notes"] );
	$pwrPortStmt->execute( $params );
}