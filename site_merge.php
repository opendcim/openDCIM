<?php
/*
	Script to merge multiple openDCIM installations into one.

	Both the child (to be imported) and parent (destination) must be running the same
	version of openDCIM, otherwise the objects won't align correctly to the database.

	The script is to be run on the parent installation, using the db values already set
	in the db.config.php file.   The child db information is to be set below and the system
	that this script runs on will need network access to that db server.

	All databases will be placed within a newly created container in the parent that matches
	the childPrefix.  After the import is completed, the container can be renamed in the UI.
	Additional objects will also have the childPrefix inserted, such as Departments, in order
	to avoid conflicts.  (ie - both sites have an 'Operations' department)

	All assets (drawings and pictures) will need to be manually copied over on the filesystem.

*/

if ( php_sapi_name() != "cli" ) {
	echo "This script may only be run from the command line.";
	header( "Refresh: 5; url=" . redirect());    
}

require_once('db.inc.php');
require_once('facilities.inc.php');

# Set these variables to the correct values
$childDBServer = "mysql.opendcim.org";
$childDBName = "dcim2";
$childDBPort = 3306;
$childDBUser = "dcim";
$childDBPassword = "dcim";
$childCountryCode = "US";
$childPrefix = "site2";

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

$targetC = new ColorCoding;
$colorMap = array();

while ( $row = $childStmt->fetch() ) {
	if ( ! $targetC->GetCodeByName($row["Name"]) ) {
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

$targetM = new MediaTypes;
$mediaMap = array();

while ( $row = $childStmt->fetch() )  {
	if ( ! $targetM->GetTypeByName($row["MediaType"])) {
		$targetM->MediaType = $row["MediaType"];
		$targetM->ColorID = $colorMap[$row["ColorID"]];
		$targetM->CreateType();
		error_log( "Created new MediaType ID of ".$targetM->MediaID." for ".$targetM->MediaType );
	}
	$mediaMap[$row["MediaID"]] = $targetM->MediaID;
}

error_log( "Merged in ".sizeof($mediaMap)." Media Types." );

# Add in the Manufacturer Names along with the mapping to the old db
$sql = "select * from fac_Manufacturer";
$childStmt = $childDBH->prepare( $sql );
$childStmt->execute();

$targetM = new Manufacturer;
$mfgMap = array();

while ( $row = $childStmt->fetch() ) {
	# Search for the exact spelling of this one in the existing db, if not, add it
	$targetM->Name = $row["Name"];
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

$targetDT = new DeviceTemplate;
$dtMap = array();

while ( $row = $childStmt->fetch() ) {
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
			$targetTP->TemplateID = $targetDT->TemplateID;
			$targetTP->PortNumber = $tpRow["PortNumber"];
			$targetTP->Label = $tpRow["Label"];
			$targetTP->PortNotes = $tpRow["PortNotes"];
			$targetTP->createPort();
		}

		error_log( "Created new DeviceTemplate ID of ".$targetDT->TemplateID." for ".$targetDT->Model );
	}
	$dtMap[$row["TemplateID"]] = $targetDT->TemplateID;
}

# Create the new top level container that everything else will fit into
$pContainer = new Container();
$pContainer->Name = $childPrefix;
$pContainer->countryCode = $childCountryCode;
$pContainer->CreateContainer();

# Now start creating all of the subsequent containers, data centers, zones, and rows
