--
-- Fix for an ancient bug that was just discovered
--

CREATE TABLE IF NOT EXISTS fac_Zone (
  ZoneID int(11) NOT NULL AUTO_INCREMENT,
  DataCenterID int(11) NOT NULL,
  Description varchar(120) NOT NULL,
  MapX1 int(11) NOT NULL,
  MapY1 int(11) NOT NULL,
  MapX2 int(11) NOT NULL,
  MapY2 int(11) NOT NULL,
  MapZoom int(11) DEFAULT '100' NOT NULL,
  PRIMARY KEY (ZoneID),
  KEY DataCenterID (DataCenterID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Move entries from fac_CabinetAudit to fac_GenericLog
--

INSERT INTO fac_GenericLog (SELECT UserID, "CabinetAudit", CabinetID, NULL, " ", "CertifyAudit", " ", " ", AuditStamp FROM fac_CabinetAudit);

--
-- Time to merge Contacts and Users - create a new fac_People table and delete the two old ones in the next release
--

DROP TABLE IF EXISTS fac_People;
CREATE TABLE fac_People (
  PersonID int(11) NOT NULL AUTO_INCREMENT,
  UserID varchar(255) NOT NULL,
  LastName varchar(40) NOT NULL,
  FirstName varchar(40) NOT NULL,
  Phone1 varchar(20) NOT NULL,
  Phone2 varchar(20) NOT NULL,
  Phone3 varchar(20) NOT NULL,
  Email varchar(80) NOT NULL,
  AdminOwnDevices tinyint(1) NOT NULL,
  ReadAccess tinyint(1) NOT NULL,
  WriteAccess tinyint(1) NOT NULL,
  DeleteAccess tinyint(1) NOT NULL,
  ContactAdmin tinyint(1) NOT NULL,
  RackRequest tinyint(1) NOT NULL,
  RackAdmin tinyint(1) NOT NULL,
  SiteAdmin tinyint(1) NOT NULL,
  APIToken varchar(80) NOT NULL,
  Disabled tinyint(1) NOT NULL,
  PRIMARY KEY(PersonID),
  KEY(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_DeviceCustomAttribute
--

DROP TABLE IF EXISTS fac_DeviceCustomAttribute;
CREATE TABLE fac_DeviceCustomAttribute(
  AttributeID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(80) NOT NULL,
  AttributeType varchar(8) NOT NULL DEFAULT 'string',
  Required tinyint(1) NOT NULL DEFAULT 0,
  AllDevices tinyint(1) NOT NULL DEFAULT 0,
  DefaultValue varchar(65000),
  PRIMARY KEY (AttributeID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_DeviceTemplateCustomValue
--

DROP TABLE IF EXISTS fac_DeviceTemplateCustomValue;
CREATE TABLE fac_DeviceTemplateCustomValue (
  TemplateID int(11) NOT NULL,
  AttributeID int(11) NOT NULL,
  Required tinyint(1) NOT NULL DEFAULT 0,
  Value varchar(65000),
  PRIMARY KEY (TemplateID, AttributeID)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_DeviceCustomValue
--

DROP TABLE IF EXISTS fac_DeviceCustomValue;
CREATE TABLE fac_DeviceCustomValue (
  DeviceID int(11) NOT NULL,
  AttributeID int(11) NOT NULL,
  Value varchar(65000),
  PRIMARY KEY (DeviceID, AttributeID)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Create new table for power ports
--

DROP TABLE IF EXISTS fac_PowerPorts;
CREATE TABLE fac_PowerPorts (
	DeviceID int(11) NOT NULL,
	PortNumber int(11) NOT NULL,
	Label varchar(40) NOT NULL,
	ConnectedDeviceID int(11) DEFAULT NULL,
	ConnectedPort int(11) DEFAULT NULL,
	Notes varchar(80) NOT NULL,
	PRIMARY KEY (DeviceID,PortNumber),
	UNIQUE KEY LabeledPort (DeviceID,PortNumber,Label),
	UNIQUE KEY ConnectedDevice (ConnectedDeviceID,ConnectedPort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- TemplatePowerPorts table content the power connections of a device template 
--

DROP TABLE IF EXISTS fac_TemplatePowerPorts;
CREATE TABLE fac_TemplatePowerPorts (
  TemplateID int(11) NOT NULL,
  PortNumber int(11) NOT NULL,
  Label varchar(40) NOT NULL,
  PortNotes varchar(80) NOT NULL,
  PRIMARY KEY (TemplateID,PortNumber),
  UNIQUE KEY LabeledPort (TemplateID,PortNumber,Label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Add Config item for appending the datacenter / cabinet to device lists 
--

INSERT INTO fac_Config set Parameter='AppendCabDC', Value='disabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='disabled';

--
-- Extend fac_Cabinet table for better sorting
--

ALTER TABLE fac_Cabinet ADD LocationSortable VARCHAR( 20 ) NOT NULL AFTER Location;
UPDATE fac_Cabinet SET LocationSortable = REPLACE(Location, ' ', '');

--
-- Add a failure counter to all devices to keep track of whether or not they've gone silent
--

ALTER TABLE fac_Device ADD SNMPFailureCount TINYINT(1) NOT NULL AFTER SNMPCommunity;

--
-- Extend fac_CabRow table to allow for rows directly in a datacenter not just a zone
--

ALTER TABLE fac_CabRow ADD DataCenterID INT( 11 ) NOT NULL AFTER Name;
UPDATE fac_CabRow SET DataCenterID=(SELECT DataCenterID FROM fac_Zone WHERE fac_CabRow.ZoneID=fac_Zone.ZoneID);

--
-- This will account for a data validation error in the cabrow create script
--

UPDATE fac_CabRow SET DataCenterID=(SELECT DataCenterID FROM `fac_DataCenter` ORDER BY DataCenterID LIMIT 1) WHERE ZoneID=0;

--
-- Add some fields needed to keep the local database in sync (if enabled) with the global repository
--

ALTER TABLE fac_DeviceTemplate ADD GlobalID int(11) NOT NULL DEFAULT 0;
ALTER TABLE fac_DeviceTemplate ADD ShareToRepo tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE fac_DeviceTemplate ADD KeepLocal tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE fac_Manufacturer ADD GlobalID int(11) NOT NULL DEFAULT 0;
ALTER TABLE fac_Manufacturer ADD SubscribeToUpdates tinyint(1) NOT NULL DEFAULT 0;

INSERT INTO fac_Config set Parameter="ShareToRepo", Value="disabled", UnitOfMeasure="Enabled/Disabled", ValType="string", DefaultVal="disabled";
INSERT INTO fac_Config set Parameter="KeepLocal", Value="enabled", UnitOfMeasure="Enabled/Disabled", ValType="string", DefaultVal="enabled";

--
-- Compatability updates below
--

ALTER TABLE fac_Cabinet CHANGE FrontEdge FrontEdge VARCHAR( 7 ) NOT NULL DEFAULT "Top";
ALTER TABLE fac_CabRow DROP CabOrder;
ALTER TABLE fac_CDUTemplate CHANGE Multiplier Multiplier VARCHAR( 6 ) NULL DEFAULT NULL;
ALTER TABLE fac_CDUTemplate CHANGE ProcessingProfile ProcessingProfile VARCHAR( 20 ) NOT NULL DEFAULT "SingleOIDWatts";
ALTER TABLE fac_PowerPanel CHANGE NumberScheme NumberScheme VARCHAR( 10 ) NOT NULL DEFAULT "Sequential";
ALTER TABLE fac_Device CHANGE DeviceType DeviceType VARCHAR( 23 ) NOT NULL DEFAULT "Server";
ALTER TABLE fac_RackRequest CHANGE DeviceType DeviceType VARCHAR( 23 ) NOT NULL DEFAULT "Server";
ALTER TABLE fac_DeviceTemplate CHANGE DeviceType DeviceType VARCHAR( 23 ) NOT NULL DEFAULT "Server";
ALTER TABLE fac_DeviceCustomAttribute CHANGE AttributeType AttributeType VARCHAR( 8 ) NOT NULL DEFAULT "string";

--
-- We added in GlobalIDs make sure they are all set to 0
-- 
UPDATE fac_Manufacturer SET GlobalID=0;

--
-- Increase size of PanelLabel field
--

ALTER TABLE fac_PowerPanel MODIFY PanelLabel varchar(80);

--
-- Add new fields for the subpanel support
--

ALTER TABLE fac_PowerPanel ADD COLUMN ParentPanelID int(11) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN ParentBreakerName varchar(80) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN PanelIPAddress varchar(30) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN TemplateID int(11) NOT NULL;

--
-- Repo API Key Configuration Fields
--

INSERT INTO fac_Config set Parameter="APIUserID", Value="", UnitOfMeasure="Email", ValType="string", DefaultVal="";
INSERT INTO fac_Config set Parameter="APIKey", Value="", UnitOfMeasure="Key", ValType="string", DefaultVal="";

--
-- Configuration item for RequireDefinedUser to see anything at all (Default is Disabled so that behavior doesn't change from prior versions)
--

INSERT INTO fac_Config set Parameter="RequireDefinedUser", Value="Disabled", UnitOfMeasure="Enabled/Disabled", ValType="string", DefaultVal="Disabled";

--
-- This damn thing keeps coming up.  Add it in again. Add in column to track an audit date on a device
--
ALTER TABLE fac_Device ADD AuditStamp DATETIME NOT NULL;

--
-- Normalize the names to be consistent
--

ALTER TABLE fac_SensorTemplate CHANGE Name Model varchar(80);

--
-- Add SNMP information to the main device template
--

ALTER TABLE fac_DeviceTemplate ADD COLUMN SNMPVersion varchar(2) NOT NULL DEFAULT '2c' AFTER RearChassisSlots;

--
-- Add in fields for SNMPv3 support
--

ALTER TABLE fac_Device ADD COLUMN SNMPVersion varchar(2) NOT NULL DEFAULT '' AFTER PrimaryIP;
ALTER TABLE fac_Device ADD COLUMN v3SecurityLevel varchar(12) NOT NULL DEFAULT '' AFTER SNMPVersion;
ALTER TABLE fac_Device ADD COLUMN v3AuthProtocol varchar(3) NOT NULL DEFAULT '' AFTER v3SecurityLevel;
ALTER TABLE fac_Device ADD COLUMN v3AuthPassphrase varchar(80) NOT NULL DEFAULT '' AFTER v3AuthProtocol;
ALTER TABLE fac_Device ADD COLUMN v3PrivProtocol varchar(3) NOT NULL DEFAULT '' AFTER v3AuthPassphrase;
ALTER TABLE fac_Device ADD COLUMN v3PrivPassphrase varchar(80) NOT NULL DEFAULT '' AFTER v3PrivProtocol;

INSERT INTO fac_Config set Parameter='v3SecurityLevel', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT INTO fac_Config set Parameter='v3AuthProtocol', Value='', UnitOfMeasure='Hash', ValType='string', DefaultVal='';
INSERT INTO fac_Config set Parameter='v3AuthPassphrase', Value='', UnitOfMeasure='Password', ValType='string', DefaultVal='';
INSERT INTO fac_Config set Parameter='v3PrivProtocol', Value='', UnitOfMeasure='Hash', ValType='string', DefaultVal='';
INSERT INTO fac_Config set Parameter='v3PrivPassphrase', Value='', UnitOfMeasure='Password', ValType='string', DefaultVal='';

--
-- Drop the old sensor readings table and make the new one
--

DROP TABLE IF EXISTS fac_CabinetTemps;
DROP TABLE IF EXISTS fac_SensorReadings;
CREATE TABLE fac_SensorReadings (
  DeviceID int(11) NOT NULL,
  Temperature float NOT NULL,
  Humidity float NOT NULL,
  LastRead datetime NOT NULL,
  PRIMARY KEY (DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Fix instances where we have ports that have missing labels somehow
--

UPDATE fac_Ports SET Label=CONCAT("Port",ABS(PortNumber)) WHERE Label="";

--
-- Bump up the database version
--
UPDATE fac_Config set Value='4.0' WHERE Parameter='Version';
