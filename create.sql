--
-- Table structure for table fac_Cabinet
--

DROP TABLE IF EXISTS fac_Cabinet;
CREATE TABLE fac_Cabinet (
  CabinetID int(11) NOT NULL AUTO_INCREMENT,
  DataCenterID int(11) NOT NULL,
  Location varchar(20) NOT NULL,
  LocationSortable varchar(20) NOT NULL,
  AssignedTo int(11) NOT NULL,
  ZoneID int(11) NOT NULL,
  CabRowID int(11) NOT NULL,
  CabinetHeight int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Keylock varchar(30) NOT NULL,
  MaxKW float(11) NOT NULL,
  MaxWeight int(11) NOT NULL,
  InstallationDate date NOT NULL,
  MapX1 int(11) NOT NULL,
  MapX2 int(11) NOT NULL,
  FrontEdge varchar(7) NOT NULL DEFAULT "Top",
  MapY1 int(11) NOT NULL,
  MapY2 int(11) NOT NULL,
  Notes text NULL,
  U1Position varchar(7) NOT NULL DEFAULT "Default",
  PRIMARY KEY (CabinetID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_CabRow
--

DROP TABLE IF EXISTS fac_CabRow;
CREATE TABLE fac_CabRow (
  CabRowID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(120) NOT NULL,
  DataCenterID int(11) NOT NULL,
  ZoneID int(11) NOT NULL,
  PRIMARY KEY (CabRowID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_CabContainer
--

DROP TABLE IF EXISTS fac_Container;
CREATE TABLE fac_Container (
  ContainerID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(120) NOT NULL,
  ParentID int(11) NOT NULL DEFAULT '0',
  DrawingFileName varchar(255) DEFAULT NULL,
  MapX int(11) NOT NULL,
  MapY int(11) NOT NULL,
  PRIMARY KEY (ContainerID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `fac_CabinetTags`
--

DROP TABLE IF EXISTS fac_CabinetTags;
CREATE TABLE fac_CabinetTags (
  CabinetID int(11) NOT NULL,
  TagID int(11) NOT NULL,
  PRIMARY KEY (CabinetID,TagID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `fac_SensorReadings`
--

DROP TABLE IF EXISTS fac_SensorReadings;
CREATE TABLE fac_SensorReadings (
  DeviceID int(11) NOT NULL,
  Temperature float NOT NULL,
  Humidity float NOT NULL,
  LastRead datetime NOT NULL,
  PRIMARY KEY (DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Add a new table for sensor probe templates
--

DROP TABLE IF EXISTS fac_SensorTemplate;
CREATE TABLE fac_SensorTemplate (
	TemplateID INT(11) NOT NULL AUTO_INCREMENT,
	ManufacturerID INT(11) NOT NULL,
	Model VARCHAR(80) NOT NULL,
	TemperatureOID VARCHAR(256) NOT NULL,
	HumidityOID VARCHAR(256) NOT NULL,
	TempMultiplier FLOAT(8) NOT NULL DEFAULT 1,
	HumidityMultiplier FLOAT(8) NOT NULL DEFAULT 1,
	mUnits VARCHAR(7) NOT NULL DEFAULT "english",
	PRIMARY KEY(TemplateID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_Slots
--
DROP TABLE IF EXISTS fac_Slots;
CREATE TABLE fac_Slots (
	TemplateID INT(11) NOT NULL,
	Position INT(11) NOT NULL,
	BackSide TINYINT(1) NOT NULL,
	X INT(11) NULL,
	Y INT(11) NULL,
	W INT(11) NULL,
	H INT(11) NULL,
	PRIMARY KEY (TemplateID, Position, BackSide)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_TemplatePorts
--
DROP TABLE IF EXISTS fac_TemplatePorts;
CREATE TABLE IF NOT EXISTS fac_TemplatePorts (
  TemplateID int(11) NOT NULL,
  PortNumber int(11) NOT NULL,
  Label varchar(40) NOT NULL,
  MediaID int(11) NOT NULL DEFAULT '0',
  ColorID int(11) NOT NULL DEFAULT '0',
  Notes varchar(80) NOT NULL,
  PRIMARY KEY (TemplateID,PortNumber),
  UNIQUE KEY LabeledPort (TemplateID,PortNumber,Label)
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
-- Table structure for table `fac_CDUTemplate`
--

DROP TABLE IF EXISTS fac_CDUTemplate;
CREATE TABLE fac_CDUTemplate (
  TemplateID int(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Managed int(1) NOT NULL,
  ATS int(1) NOT NULL,
  SNMPVersion varchar(2) NOT NULL DEFAULT '2c',
  VersionOID varchar(80) NOT NULL,
  Multiplier varchar(6) NULL DEFAULT NULL,
  OID1 varchar(80) NOT NULL,
  OID2 varchar(80) NOT NULL,
  OID3 varchar(80) NOT NULL,
  ATSStatusOID varchar(80) NOT NULL,
  ATSDesiredResult varchar(80) NOT NULL,
  ProcessingProfile varchar(20) NOT NULL DEFAULT "SingleOIDWatts", 
  Voltage int(11) NOT NULL,
  Amperage int(11) NOT NULL,
  NumOutlets int(11) NOT NULL,
  PRIMARY KEY (TemplateID),
  KEY ManufacturerID (ManufacturerID),
  UNIQUE KEY (ManufacturerID, Model)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Add ColorCoding Table
--

DROP TABLE IF EXISTS fac_ColorCoding;
CREATE TABLE fac_ColorCoding (
  ColorID INT(11) NOT NULL AUTO_INCREMENT,
  Name VARCHAR(20) NOT NULL,
  DefaultNote VARCHAR(40),
  PRIMARY KEY(ColorID),
  UNIQUE KEY Name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table fac_DataCenter
--

DROP TABLE IF EXISTS fac_DataCenter;
CREATE TABLE fac_DataCenter (
  DataCenterID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(255) NOT NULL,
  SquareFootage int(11) NOT NULL,
  DeliveryAddress varchar(255) NOT NULL,
  Administrator varchar(80) NOT NULL,
  MaxkW int(11) NOT NULL,
  DrawingFileName varchar(255) NOT NULL,
  EntryLogging tinyint(1) NOT NULL,
  ContainerID INT(11) NOT NULL,
  MapX int(11) NOT NULL,
  MapY int(11) NOT NULL,
  U1Position varchar(7) NOT NULL DEFAULT "Default",
  PRIMARY KEY (DataCenterID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Department
--

DROP TABLE IF EXISTS fac_Department;
CREATE TABLE fac_Department (
  DeptID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(255) NOT NULL,
  ExecSponsor varchar(80) NOT NULL,
  SDM varchar(80) NOT NULL,
  Classification varchar(80) NOT NULL,
  DeptColor VARCHAR( 7 ) NOT NULL DEFAULT '#FFFFFF',
  PRIMARY KEY (DeptID),
  UNIQUE KEY Name (Name)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_DeptContacts
--

DROP TABLE IF EXISTS fac_DeptContacts;
CREATE TABLE fac_DeptContacts (
  DeptID int(11) NOT NULL,
  ContactID int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Decommission
--

DROP TABLE IF EXISTS fac_Decommission;
CREATE TABLE fac_Decommission (
  SurplusDate DATE NOT NULL,
  Label varchar(80) NOT NULL,
  SerialNo varchar(40) NOT NULL,
  AssetTag varchar(20) NOT NULL,
  UserID varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Device
--

DROP TABLE IF EXISTS fac_Device;
CREATE TABLE fac_Device (
  DeviceID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(80) NOT NULL,
  SerialNo varchar(40) NOT NULL,
  AssetTag varchar(20) NOT NULL,
  PrimaryIP varchar(254) NOT NULL,
  SNMPVersion varchar(2) NOT NULL,
  v3SecurityLevel varchar(12) NOT NULL,
  v3AuthProtocol varchar(3) NOT NULL,
  v3AuthPassphrase varchar(80) NOT NULL,
  v3PrivProtocol varchar(3) NOT NULL,
  v3PrivPassphrase varchar(80) NOT NULL,
  SNMPCommunity varchar(80) NOT NULL,
  SNMPFailureCount TINYINT(1) NOT NULL,
  Hypervisor varchar(40) NOT NULL,
  APIUsername varchar(80) NOT NULL,
  APIPassword varchar(80) NOT NULL,
  APIPort smallint(4) NOT NULL,
  ProxMoxRealm varchar(80) NOT NULL,
  Owner int(11) NOT NULL,
  EscalationTimeID int(11) NOT NULL,
  EscalationID int(11) NOT NULL,
  PrimaryContact int(11) NOT NULL,
  Cabinet int(11) NOT NULL,
  Position int(11) NOT NULL,
  Height int(11) NOT NULL,
  Ports int(11) NOT NULL,
  FirstPortNum int(11) NOT NULL,
  TemplateID int(11) NOT NULL,
  NominalWatts int(11) NOT NULL,
  PowerSupplyCount int(11) NOT NULL,
  DeviceType varchar(23) NOT NULL DEFAULT "Server",
  ChassisSlots smallint(6) NOT NULL,
  RearChassisSlots smallint(6) NOT NULL,
  ParentDevice int(11) NOT NULL,
  MfgDate date NOT NULL,
  InstallDate date NOT NULL,
  WarrantyCo VARCHAR(80) NOT NULL,
  WarrantyExpire date NULL,
  Notes text NULL,
  Status varchar(20) NOT NULL DEFAULT 'Production',
  HalfDepth tinyint(1) NOT NULL DEFAULT '0',
  BackSide tinyint(1) NOT NULL DEFAULT '0',
  AuditStamp DATETIME NOT NULL,
  Weight int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DeviceID),
  KEY SerialNo (SerialNo,`AssetTag`,`PrimaryIP`),
  KEY AssetTag (AssetTag),
  KEY Cabinet (Cabinet),
  KEY TemplateID (TemplateID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_DeviceTags
--

DROP TABLE IF EXISTS fac_DeviceTags;
CREATE TABLE fac_DeviceTags (
  DeviceID int(11) NOT NULL,
  TagID int(11) NOT NULL,
  PRIMARY KEY (DeviceID,TagID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table fac_DeviceTemplate
--

DROP TABLE IF EXISTS fac_DeviceTemplate;
CREATE TABLE fac_DeviceTemplate (
  TemplateID int(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Height int(11) NOT NULL,
  Weight int(11) NOT NULL,
  Wattage int(11) NOT NULL,
  DeviceType varchar(23) NOT NULL DEFAULT "Server",
  PSCount int(11) NOT NULL,
  NumPorts int(11) NOT NULL,
  Notes text NOT NULL,
  FrontPictureFile VARCHAR(255) NOT NULL,
  RearPictureFile VARCHAR(255) NOT NULL,
  ChassisSlots SMALLINT(6) NOT NULL,
  RearChassisSlots SMALLINT(6) NOT NULL,
  SNMPVersion VARCHAR(2) NOT NULL DEFAULT '2c',
  GlobalID int(11) NOT NULL DEFAULT 0,
  ShareToRepo tinyint(1) NOT NULL DEFAULT 0,
  KeepLocal tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (TemplateID),
  UNIQUE KEY ManufacturerID (ManufacturerID,Model)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_EscalationTimes
--

DROP TABLE IF EXISTS fac_EscalationTimes;
CREATE TABLE fac_EscalationTimes (
	EscalationTimeID int(11) NOT NULL AUTO_INCREMENT,
	TimePeriod varchar(80) NOT NULL,
	PRIMARY KEY (EscalationTimeID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Escalations
--

DROP TABLE IF EXISTS fac_Escalations;
CREATE TABLE fac_Escalations (
	EscalationID int(11) NOT NULL AUTO_INCREMENT,
	Details varchar(80) NULL,
	PRIMARY KEY (EscalationID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `fac_GenericLog`
--

DROP TABLE IF EXISTS fac_GenericLog;
CREATE TABLE `fac_GenericLog` (
  UserID varchar(80) NOT NULL,
  Class varchar(40) NOT NULL,
  ObjectID varchar(80) NOT NULL,
  ChildID int(11) DEFAULT NULL,
  Action varchar(40) NOT NULL,
  Property varchar(40) NOT NULL,
  OldVal varchar(255) NOT NULL,
  NewVal varchar(255) NOT NULL,
  Time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Manufacturer
--

DROP TABLE IF EXISTS fac_Manufacturer;
CREATE TABLE fac_Manufacturer (
  ManufacturerID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(80) NOT NULL,
  GlobalID int(11) NOT NULL DEFAULT 0,
  SubscribeToUpdates int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (ManufacturerID),
  UNIQUE KEY Name (Name)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `fac_Ports`
--

DROP TABLE IF EXISTS fac_Ports;
CREATE TABLE fac_Ports (
  DeviceID int(11) NOT NULL,
  PortNumber int(11) NOT NULL,
  Label varchar(40) NOT NULL,
  MediaID int(11) NOT NULL DEFAULT '0',
  ColorID int(11) NOT NULL DEFAULT '0',
  Notes varchar(80) NOT NULL,
  ConnectedDeviceID int(11) DEFAULT NULL,
  ConnectedPort int(11) DEFAULT NULL,
  Notes varchar(80) NOT NULL,
  PRIMARY KEY (DeviceID,PortNumber),
  UNIQUE KEY LabeledPort (DeviceID,PortNumber,Label),
  UNIQUE KEY ConnectedDevice (ConnectedDeviceID,ConnectedPort),
  KEY Notes (Notes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `fac_MediaTypes`
--

DROP TABLE IF EXISTS fac_MediaTypes;
CREATE TABLE IF NOT EXISTS fac_MediaTypes (
  MediaID int(11) NOT NULL AUTO_INCREMENT,
  MediaType varchar(40) NOT NULL,
  ColorID INT(11) NOT NULL,
  PRIMARY KEY (mediaid),
  UNIQUE KEY mediatype (mediatype)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

--
-- Table structure for table fac_PanelSchedule
--

DROP TABLE IF EXISTS fac_PanelSchedule;
CREATE TABLE fac_PanelSchedule (
  PanelID int(11) NOT NULL AUTO_INCREMENT,
  PolePosition int(11) NOT NULL,
  NumPoles int(11) NOT NULL,
  Label varchar(80) NOT NULL,
  PRIMARY KEY (PanelID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PDUStats
--

DROP TABLE IF EXISTS fac_PDUStats;
create table fac_PDUStats(
  PDUID int(11) NOT NULL,
  Wattage int(11) NOT NULL,
  LastRead datetime DEFAULT NULL,
  PRIMARY KEY (PDUID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_People
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
  APIKey varchar(80) NOT NULL,
  AdminOwnDevices tinyint(1) NOT NULL,
  ReadAccess tinyint(1) NOT NULL,
  WriteAccess tinyint(1) NOT NULL,
  DeleteAccess tinyint(1) NOT NULL,
  ContactAdmin tinyint(1) NOT NULL,
  RackRequest tinyint(1) NOT NULL,
  RackAdmin tinyint(1) NOT NULL,
  BulkOperations tinyint(1) NOT NULL,
  SiteAdmin tinyint(1) NOT NULL,
  APIToken varchar(80) NOT NULL,
  Disabled tinyint(1) NOT NULL,
  PRIMARY KEY(PersonID),
  UNIQUE KEY UserID (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerConnection
--

DROP TABLE IF EXISTS fac_PowerConnection;
CREATE TABLE fac_PowerConnection (
  PDUID int(11) NOT NULL,
  PDUPosition VARCHAR(11) NOT NULL,
  DeviceID int(11) NOT NULL,
  DeviceConnNumber int(11) NOT NULL,
  UNIQUE KEY PDUID (PDUID,PDUPosition),
  UNIQUE KEY DeviceID (DeviceID,DeviceConnNumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerDistribution
--

DROP TABLE IF EXISTS fac_PowerDistribution;
CREATE TABLE fac_PowerDistribution (
  PDUID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(40) NOT NULL,
  CabinetID int(11) NOT NULL,
  TemplateID int(11) NOT NULL,
  IPAddress varchar(254) NOT NULL,
  SNMPCommunity varchar(50) NOT NULL,
  FirmwareVersion varchar(40) NOT NULL,
  PanelID int(11) NOT NULL,
  BreakerSize int(11) NOT NULL,
  PanelPole varchar(20) NOT NULL,
  InputAmperage int(11) NOT NULL,
  FailSafe tinyint(1) NOT NULL,
  PanelID2 int(11) NOT NULL,
  PanelPole2 varchar(20) NOT NULL,
  PRIMARY KEY (PDUID),
  KEY CabinetID (CabinetID),
  KEY PanelID (PanelID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerPanel
--

DROP TABLE IF EXISTS fac_PowerPanel;
CREATE TABLE fac_PowerPanel (
  PanelID int(11) NOT NULL AUTO_INCREMENT,
  PanelLabel varchar(80) NOT NULL,
  NumberOfPoles int(11) NOT NULL,
  MainBreakerSize int(11) NOT NULL,
  PanelVoltage int(11) NOT NULL,
  NumberScheme varchar(10) NOT NULL DEFAULT "Sequential",
  ParentPanelID int(11) NOT NULL,
  ParentBreakerName varchar(80) NOT NULL,
  PanelIPAddress varchar(30) NOT NULL,
  TemplateID int(11) NOT NULL,
  MapDataCenterID INT(11) NOT NULL,
  MapX1 INT(11) NOT NULL,
  MapX2 INT(11) NOT NULL,
  MapY1 INT(11) NOT NULL,
  MapY2 INT(11) NOT NULL,
  PRIMARY KEY (PanelID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
  UNIQUE KEY ConnectedDevice (ConnectedDeviceID,ConnectedPort),
  KEY Notes (Notes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_RackRequest
--

DROP TABLE IF EXISTS fac_RackRequest;
CREATE TABLE fac_RackRequest (
  RequestID int(11) NOT NULL AUTO_INCREMENT,
  RequestorID int(11) NOT NULL,
  RequestTime datetime NOT NULL,
  CompleteTime datetime NOT NULL,
  Label varchar(40) NOT NULL,
  SerialNo varchar(40) NOT NULL,
  MfgDate date NOT NULL,
  AssetTag varchar(40) NOT NULL,
  ESX tinyint(1) NOT NULL,
  Owner int(11) NOT NULL,
  DeviceHeight int(11) NOT NULL,
  EthernetCount int(11) NOT NULL,
  VLANList varchar(80) NOT NULL,
  SANCount int(11) NOT NULL,
  SANList varchar(80) NOT NULL,
  DeviceClass varchar(80) NOT NULL,
  DeviceType varchar(23) NOT NULL DEFAULT "Server",
  LabelColor varchar(80) NOT NULL,
  CurrentLocation varchar(120) NOT NULL,
  SpecialInstructions text NOT NULL,
  PRIMARY KEY (RequestID),
  KEY RequestorID (RequestorID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Tags
--

DROP TABLE IF EXISTS fac_Tags;
CREATE TABLE fac_Tags (
  TagID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(128) NOT NULL,
  PRIMARY KEY (`TagID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO fac_Tags VALUES (NULL, 'Report');
INSERT INTO fac_Tags VALUES (NULL , 'NoReport');

--
-- Table structure for table fac_VMInventory
--

DROP TABLE IF EXISTS fac_VMInventory;
CREATE TABLE fac_VMInventory (
  VMIndex int(11) NOT NULL AUTO_INCREMENT,
  DeviceID int(11) NOT NULL,
  LastUpdated datetime NOT NULL,
  vmID int(11) NOT NULL,
  vmName varchar(80) NOT NULL,
  vmState varchar(80) NOT NULL,
  Owner int(11) NOT NULL,
  PrimaryContact int(11) NOT NULL,
  PRIMARY KEY (VMIndex),
  UNIQUE KEY `VMList` (`vmID`, `vmName`),
  KEY ValidDevice (DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Zone
--

DROP TABLE IF EXISTS fac_Zone;
CREATE TABLE fac_Zone (
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
-- Table structure for fac_SupplyBin
--
DROP TABLE IF EXISTS fac_SupplyBin;
CREATE TABLE fac_SupplyBin (
  BinID int(11) NOT NULL AUTO_INCREMENT,
  Location varchar(40) NOT NULL,
  PRIMARY KEY (BinID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_Supplies
--
DROP TABLE IF EXISTS fac_Supplies;
CREATE TABLE fac_Supplies (
  SupplyID int(11) NOT NULL AUTO_INCREMENT,
  PartNum varchar(40) NOT NULL,
  PartName varchar(80) NOT NULL,
  MinQty int(11) NOT NULL,
  MaxQty int(11) NOT NULL,
  PRIMARY KEY (SupplyID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_BinContents
--
DROP TABLE IF EXISTS fac_BinContents;
CREATE TABLE fac_BinContents (
  BinID int(11) NOT NULL,
  SupplyID int(11) NOT NULL,
  Count int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_BinAudits
--
DROP TABLE IF EXISTS fac_BinAudits;
CREATE TABLE fac_BinAudits (
  BinID int(11) NOT NULL,
  UserID int(11) NOT NULL,
  AuditStamp datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_CabinetToolTip
--
DROP TABLE IF EXISTS fac_CabinetToolTip;
CREATE TABLE fac_CabinetToolTip (
  SortOrder smallint(6) DEFAULT NULL,
  Field varchar(20) NOT NULL,
  Label varchar(30) NOT NULL,
  Enabled tinyint(1) DEFAULT '1',
  UNIQUE KEY Field (Field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add base ToolTip configuration options
--

INSERT INTO fac_CabinetToolTip VALUES(NULL, 'AssetTag', 'Asset Tag', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'ChassisSlots', 'Number of Slots in Chassis:', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'DeviceID', 'Device ID', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'DeviceType', 'Device Type', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'EscalationID', 'Details', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'EscalationTimeID', 'Time Period', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'VM Hypervisor', 'VM Hypervisor', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'InstallDate', 'Install Date', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'MfgDate', 'Manufacture Date', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'NominalWatts', 'Nominal Draw (Watts)', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'Owner', 'Departmental Owner', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'Ports', 'Number of Data Ports', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'PowerSupplyCount', 'Number of Power Supplies', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'PrimaryContact', 'Primary Contact', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'PrimaryIP', 'Primary IP', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'Status', 'Device Status', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'SerialNo', 'Serial Number', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'SNMPCommunity', 'SNMP Read Only Community', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'TemplateID', 'Device Class', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'WarrantyCo', 'Warranty Company', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'WarrantyExpire', 'Warranty Expiration', 0);

--
-- Add table for cdu tooltips
--

DROP TABLE IF EXISTS fac_CDUToolTip;
CREATE TABLE fac_CDUToolTip (
  SortOrder smallint(6) DEFAULT NULL,
  Field varchar(20) NOT NULL,
  Label varchar(30) NOT NULL,
  Enabled tinyint(1) DEFAULT '1',
  UNIQUE KEY Field (Field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add base ToolTip configuration options
--

INSERT INTO fac_CDUToolTip VALUES(NULL, 'PanelID', 'Source Panel', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'PanelVoltage', 'Voltage', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'BreakerSize', 'Breaker Size', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'PanelPole', 'Panel Pole Number', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'InputAmperage', 'Input Amperage', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'Model', 'Model', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'IPAddress', 'IP Address', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'Uptime', 'Uptime', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'FirmwareVersion', 'Firmware Version', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'SNMPCommunity', 'SNMP Community', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'NumOutlets', 'Used/Total Connections', 0);

--
-- Table structure and insert script for table fac_Config
--

DROP TABLE IF EXISTS fac_Config;
CREATE TABLE fac_Config (
 Parameter varchar(40) NOT NULL,
 Value varchar(200) NOT NULL,
 UnitOfMeasure varchar(40) NOT NULL,
 ValType varchar(40) NOT NULL,
 DefaultVal varchar(200) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO fac_Config VALUES
  ('Version','4.5','','',''),
	('OrgName','openDCIM Computer Facilities','Name','string','openDCIM Computer Facilities'),
	('ClassList','ITS, Internal, Customer','List','string','ITS, Internal, Customer'),
	('SpaceRed','80','percentage','float','80'),
	('SpaceYellow','60','percentage','float','60'),
	('WeightRed','80','percentage','float','80'),
	('WeightYellow','60','percentage','float','60'),
	('PowerRed','80','percentage','float','80'),
	('PowerYellow','60','percentage','float','60'),
	('RackWarningHours', 4, 'Hours', 'integer', '4'),
	('RackOverdueHours', 1, 'Hours', 'integer', '1'),
	('CriticalColor','#cc0000','HexColor','string','#cc0000'),
	('CautionColor','#cccc00','HexColor','string','#cccc00'),
	('GoodColor','#0a0','HexColor','string','#0a0'),
	('MediaEnforce', 'disabled', 'Enabled/Disabled', 'string', 'disabled'),
  ('OutlineCabinets', 'disabled', 'Enabled/Disabled', 'string', 'disabled'),
  ('LabelCabinets', 'disabled', 'Enabled/Disabled', 'string', 'disabled'),
	('DefaultPanelVoltage','208','Volts','int','208'),
	('annualCostPerUYear','200','Dollars','float','200'),
	('Locale','en_US.utf8','TextLocale','string','en_US.utf8'),
	('timezone', 'America/Chicago', 'string', 'string', 'America/Chicago'),
	('PDFLogoFile','logo.png','Filename','string','logo.png'),
	('PDFfont','Arial','Font','string','Arial'),
	('SMTPServer','smtp.your.domain','Server','string','smtp.your.domain'),
	('SMTPPort','25','Port','int','25'),
	('SMTPHelo','your.domain','Helo','string','your.domain'),
	('SMTPUser','','Username','string',''),
	('SMTPPassword','','Password','string',''),
	('MailFromAddr','DataCenterTeamAddr@your.domain','Email','string','DataCenterTeamAddr@your.domain'),
	('MailSubject','ITS Facilities Rack Request','EmailSub','string','ITS Facilities Rack Request'),
	('MailToAddr','DataCenterTeamAddr@your.domain','Email','string','DataCenterTeamAddr@your.domain'),
	('ComputerFacMgr','DataCenterMgr Name','Name','string','DataCenterMgr Name'),
	('NetworkCapacityReportOptIn', 'OptIn', 'OptIn/OptOut', 'string', 'OptIn' ),
	('NetworkThreshold', '75', 'Percentage', 'integer', '75' ),
	('FacMgrMail','DataCenterMgr@your.domain','Email','string','DataCenterMgr@your.domain'),
	('InstallURL','','URL','string','https://dcim.your.domain'),
	('UserLookupURL','https://','URL','string','https://'),
	('ReservedColor','#00FFFF','HexColor','string','#FFFFFF'),
	('FreeSpaceColor','#FFFFFF','HexColor','string','#FFFFFF'),
	('HeaderColor', '#006633', 'HexColor', 'string', '#006633'),
	('BodyColor', '#F0E0B2', 'HexColor', 'string', '#F0E0B2'),
	('LinkColor', '#000000', 'HexColor', 'string', '#000000'),
	('VisitedLinkColor', '#8D90B3', 'HexColor', 'string', '#8D90B3'),
	('LabelCase','upper','string','string','upper'),
	('mDate','blank','string','string','blank'),
	('wDate','blank','string','string','blank'),
	('NewInstallsPeriod', '7', 'Days', 'int', '7' ),
 	('VMExpirationTime','7','Days','int','7'),
 	('mUnits', 'english', 'English/Metric', 'string', 'english'),
	('snmpwalk', '/usr/bin/snmpwalk', 'path', 'string', '/usr/bin/snmpwalk'),
	('snmpget', '/usr/bin/snmpget', 'path', 'string', '/usr/bin/snmpget'),
	('SNMPCommunity','public', 'string', 'string', 'public' ),
	('cut', '/bin/cut', 'path', 'string', '/bin/cut'),
 	('ToolTips', 'Disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('CDUToolTips', 'Disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('PageSize', 'Letter', 'string', 'string', 'Letter'),
	('path_weight_cabinet', '1', '', 'int', '1'),
	('path_weight_rear', '1', '', 'int', '1'),
	('path_weight_row', '4', '', 'int', '4'),
	('TemperatureRed', '30', 'degrees', 'float', '30'),
	('TemperatureYellow', '25', 'degrees', 'float', '25'),
	('HumidityRedHigh', '75', 'percentage', 'float', '75'),
	('HumidityRedLow', '35', 'percentage', 'float', '35'),
	('HumidityYellowHigh', '55', 'percentage', 'float', '55'),
	('HumidityYellowLow', '45', 'percentage', 'float', '45'),
	('WorkOrderBuilder', 'disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('RackRequests', 'enabled', 'Enabled/Disabled', 'string', 'Enabled'),
	('dot', '/usr/bin/dot', 'path', 'string', '/usr/bin/dot'),
	('AppendCabDC', 'disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('ShareToRepo', 'disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('APIUserID', '', 'Email', 'string', ''),
	('APIKey', '', 'Key', 'string', ''),
	('RequireDefinedUser', 'disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('KeepLocal', 'enabled', 'Enabled/Disabled', 'string', 'Enabled'),
	('SNMPVersion', '2c', 'Version', 'string', '2c'),
	('U1Position', 'Bottom', 'Top/Bottom', 'string', 'Bottom'),
	('RCIHigh', '80', 'degrees', 'float', '80'),
	('RCILow', '65', 'degress', 'float', '65'),
	('FilterCabinetList', 'disabled', 'Enabled/Disabled', 'string', 'Disabled'),
	('CostPerKwHr', '.25', 'Currency', 'float', '.25'),
	('v3SecurityLevel', '', 'noAuthNoPriv/authNoPriv/authPriv', 'string', 'noAuthNoPriv'),
	('v3AuthProtocol', '', 'SHA/MD5', 'string', 'SHA'),
	('v3AuthPassphrase', '', 'Password', 'string', ''),
	('v3PrivProtocol', '', 'SHA/MD5', 'string', 'SHA'),
	('v3PrivPassphrase', '', 'Password', 'string', ''),
  ('PatchPanelsOnly','enabled', 'Enabled/Disabled', 'string', 'enabled'),
  ('LDAPServer', 'localhost', 'URI', 'string', 'localhost'),
  ('LDAPBaseDN', 'dc=opendcim,dc=org', 'DN', 'string', 'dc=opendcim,dc=org'),
  ('LDAPBindDN', 'cn=%userid%,ou=users,dc=opendcim,dc=org', 'DN', 'string', 'cn=%userid%,ou=users,dc=opendcim,dc=org'),
  ('LDAPBaseSearch', '(&(objectClass=posixGroup)(memberUid=%userid%))', 'DN', 'string', '(&(objectClass=posixGroup)(memberUid=%userid%))'),
  ('LDAPUserSearch', '(|(uid=%userid%))', 'DN', 'string', '(|(uid=%userid%))'),
  ('LDAPSessionExpiration', '0', 'Seconds', 'int', '0'),
  ('LDAPSiteAccess', 'cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPReadAccess', 'cn=ReadAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=ReadAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPWriteAccess', 'cn=WriteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=WriteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPDeleteAccess', 'cn=DeleteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=DeleteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPAdminOwnDevices', 'cn=AdminOwnDevices,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=AdminOwnDevices,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPRackRequest', 'cn=RackRequest,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=RackRequest,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPRackAdmin', 'cn=RackAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=RackAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPBulkOperations', 'cn=BulkOperations,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=BulkOperations,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPContactAdmin', 'cn=ContactAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=ContactAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('LDAPSiteAdmin', 'cn=SiteAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=SiteAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org'),
  ('SAMLStrict', 'enabled', 'string', 'Enabled/Disabled', 'enabled'),
  ('SAMLDebug', 'disabled', 'string', 'Enabled/Disabled', 'disabled'),
  ('SAMLBaseURL', '', 'URL', 'string', 'https://opendcim.local'),
  ('SAMLShowSuccessPage', 'enabled', 'string', 'Enabled/Disabled', 'enabled'),
  ('SAMLspentityId', '', 'URL', 'string', 'https://opendcim.local'),
  ('SAMLspacsURL', '', 'URL', 'string', 'https://opendcim.local/saml/acs.php'),
  ('SAMLspslsURL', '', 'URL', 'string', 'https://opendcim.local'),
  ('SAMLspx509cert', '', 'string', 'string', ''),
  ('SAMLspprivateKey', '', 'string', 'string', ''),
  ('SAMLidpentityId', '', 'URL', 'string', 'https://accounts.google.com/o/saml2?idpid=XXXXXXXXX'),
  ('SAMLidpssoURL', '', 'URL', 'string', 'https://accounts.google.com/o/saml2/idp?idpid=XXXXXXXXX'),
  ('SAMLidpslsURL', '', 'URL', 'string', ''),
  ('SAMLidpcertFingerprint', '', 'string', 'string', 'FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF'),
  ('SAMLidpcertFingerprintAlgorithm', '', 'string', 'string', 'sha1'),
  ('SAMLaccountPrefix', '', 'string', 'string', 'DOMAIN\\'),
  ('SAMLaccountSuffix', '', 'string', 'string', '@example.org')
;

--
-- Table structure for fac_DeviceCustomAttribute
--
DROP TABLE IF EXISTS fac_DeviceCustomAttribute;
CREATE TABLE fac_DeviceCustomAttribute(
  AttributeID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(80) NOT NULL,
  AttributeType varchar(8) NOT NULL DEFAULT "string",
  Required tinyint(1) NOT NULL DEFAULT 0,
  AllDevices tinyint(1) NOT NULL DEFAULT 0,
  DefaultValue varchar(65000),
  PRIMARY KEY (AttributeID),
  UNIQUE (Label)
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
-- Table for monitoring long running jobs
--

CREATE TABLE IF NOT EXISTS fac_Jobs (
  SessionID varchar(80) NOT NULL,
  Percentage int(11) NOT NULL DEFAULT "0",
  Status varchar(255) NOT NULL,
  PRIMARY KEY(SessionID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Tables for tracking Projects/ Services
--

DROP TABLE IF EXISTS fac_Projects;
CREATE TABLE fac_Projects (
  ProjectID int(11) NOT NULL AUTO_INCREMENT,
  ProjectName varchar(80) NOT NULL,
  ProjectSponsor varchar(80) NOT NULL,
  ProjectStartDate date NOT NULL,
  ProjectExpirationDate date NOT NULL,
  ProjectActualEndDate date NOT NULL,
  PRIMARY KEY (ProjectID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS fac_ProjectMembership;
CREATE TABLE fac_ProjectMembership (
  ProjectID int(11) NOT NULL,
  MemberType varchar(7) NOT NULL DEFAULT 'Device',
  MemberID int(11) NOT NULL,
  PRIMARY KEY (`ProjectID`, `MemberType`, `MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Tables for tracking how things leave
--

CREATE TABLE fac_Disposition (
DispositionID INT(11) NOT NULL AUTO_INCREMENT,
Name VARCHAR(80) NOT NULL,
Description VARCHAR(255) NOT NULL,
ReferenceNumber VARCHAR(80) NOT NULL,
Status VARCHAR(10) NOT NULL DEFAULT 'Active',
PRIMARY KEY (DispositionID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE fac_DispositionMembership (
DispositionID INT(11) NOT NULL,
DeviceID INT(11) NOT NULL,
DispositionDate DATE NOT NULL,
DisposedBy VARCHAR(80) NOT NULL,
PRIMARY KEY (DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_Disposition VALUES ( 1, 'Salvage', 'Items sent to a qualified e-waste disposal provider.', '', 'Active');
INSERT INTO fac_Disposition VALUES ( 2, 'Returned to Customer', 'Item has been removed from the data center and returned to the customer.', '', 'Active');

--
-- Add a table of Status Field values to allow
--

CREATE TABLE fac_DeviceStatus (
  StatusID INT(11) NOT NULL AUTO_INCREMENT,
  Status varchar(40) NOT NULL,
  ColorCode VARCHAR(7) NOT NULL,
  PRIMARY KEY(StatusID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=UTF8;

INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Reserved', '#00FFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Test', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Development', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('QA', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Production', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Spare', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Disposed', '#FFFFFF');
