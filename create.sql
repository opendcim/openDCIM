--
-- Table structure for table fac_Cabinet
--

DROP TABLE IF EXISTS fac_Cabinet;
CREATE TABLE fac_Cabinet (
  CabinetID int(11) NOT NULL AUTO_INCREMENT,
  DataCenterID int(11) NOT NULL,
  Location varchar(20) NOT NULL,
  AssignedTo int(11) NOT NULL,
  ZoneID int(11) NOT NULL,
  CabinetHeight int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Keylock varchar(30) NOT NULL,
  MaxKW float(11) NOT NULL,
  MaxWeight int(11) NOT NULL,
  InstallationDate date NOT NULL,
  SensorIPAddress varchar(20) NOT NULL,
  SensorCommunity varchar(40) NOT NULL,
  SensorOID varchar(80) NOT NULL,
  MapX1 int(11) NOT NULL,
  MapX2 int(11) NOT NULL,
  MapY1 int(11) NOT NULL,
  MapY2 int(11) NOT NULL,
  Notes text NULL,
  PRIMARY KEY (CabinetID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
-- Table structure for table `fac_CabinetTemps`
--

DROP TABLE IF EXISTS fac_CabinetTemps;
CREATE TABLE fac_CabinetTemps (
  CabinetID int(11) NOT NULL,
  LastRead datetime NOT NULL,
  Temp int(11) NOT NULL,
  PRIMARY KEY (CabinetID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `fac_CabinetAudit`
--

DROP TABLE IF EXISTS `fac_CabinetAudit`;
CREATE TABLE `fac_CabinetAudit` (
  CabinetID int(11) NOT NULL,
  UserID varchar(80) NOT NULL,
  AuditStamp datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `fac_CDUTemplate`
--

DROP TABLE IF EXISTS fac_CDUTemplate;
CREATE TABLE fac_CDUTemplate (
  TemplateID int(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Managed int(1) NOT NULL,
  VersionOID varchar(80) NOT NULL,
  Multiplier enum( '1', '10', '100' ),
  OID1 varchar(80) NOT NULL,
  OID2 varchar(80) NOT NULL,
  OID3 varchar(80) NOT NULL,
  ProcessingProfile enum('SingleOIDWatts','SingleOIDAmperes','Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes'),
  Voltage int(11) NOT NULL,
  Amperage int(11) NOT NULL,
  NumOutlets int(11) NOT NULL,
  PRIMARY KEY (TemplateID),
  KEY ManufacturerID (ManufacturerID),
  UNIQUE KEY (ManufacturerID, Model)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `fac_Contact`
--

DROP TABLE IF EXISTS fac_Contact;
CREATE TABLE fac_Contact (
  ContactID int(11) NOT NULL AUTO_INCREMENT,
  UserID varchar(80) NOT NULL,
  LastName varchar(40) NOT NULL,
  FirstName varchar(40) NOT NULL,
  Phone1 varchar(20) NOT NULL,
  Phone2 varchar(20) NOT NULL,
  Phone3 varchar(20) NOT NULL,
  Email varchar(80) NOT NULL,
  PRIMARY KEY (ContactID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
  PRIMARY KEY (DataCenterID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_DeptContacts
--

DROP TABLE IF EXISTS fac_DeptContacts;
CREATE TABLE fac_DeptContacts (
  DeptID int(11) NOT NULL,
  ContactID int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Device
--

DROP TABLE IF EXISTS fac_Device;
CREATE TABLE fac_Device (
  DeviceID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(80) NOT NULL,
  SerialNo varchar(40) NOT NULL,
  AssetTag varchar(20) NOT NULL,
  PrimaryIP varchar(20) NOT NULL,
  SNMPCommunity varchar(80) NOT NULL,
  ESX tinyint(1) NOT NULL,
  Owner int(11) NOT NULL,
  EscalationTimeID int(11) NOT NULL,
  EscalationID int(11) NOT NULL,
  PrimaryContact int(11) NOT NULL,
  Cabinet int(11) NOT NULL,
  Position int(11) NOT NULL,
  Height int(11) NOT NULL,
  Ports int(11) NOT NULL,
  TemplateID int(11) NOT NULL,
  NominalWatts int(11) NOT NULL,
  PowerSupplyCount int(11) NOT NULL,
  DeviceType enum('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') NOT NULL,
  ChassisSlots smallint(6) NOT NULL,
  RearChassisSlots smallint(6) NOT NULL,
  ParentDevice int(11) NOT NULL,
  MfgDate date NOT NULL,
  InstallDate date NOT NULL,
  WarrantyCo VARCHAR(80) NOT NULL,
  WarrantyExpire date NULL,
  Notes text NULL,
  Reservation tinyint(1) NOT NULL,
  PRIMARY KEY (DeviceID),
  KEY SerialNo (SerialNo,`AssetTag`,`PrimaryIP`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
  DeviceType enum('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') NOT NULL default 'Server',
  PSCount int(11) NOT NULL,
  NumPorts int(11) NOT NULL,
  PRIMARY KEY (TemplateID),
  KEY ManufacturerID (ManufacturerID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_EscalationTimes
--

DROP TABLE IF EXISTS fac_EscalationTimes;
CREATE TABLE fac_EscalationTimes (
	EscalationTimeID int(11) NOT NULL AUTO_INCREMENT,
	TimePeriod varchar(80) NOT NULL,
	PRIMARY KEY (EscalationTimeID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Escalations
--

DROP TABLE IF EXISTS fac_Escalations;
CREATE TABLE fac_Escalations (
	EscalationID int(11) NOT NULL AUTO_INCREMENT,
	Details varchar(80) NULL,
	PRIMARY KEY (EscalationID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Manufacturer
--

DROP TABLE IF EXISTS fac_Manufacturer;
CREATE TABLE fac_Manufacturer (
  ManufacturerID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(80) NOT NULL,
  PRIMARY KEY (ManufacturerID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PatchConnection
--

DROP TABLE IF EXISTS fac_PatchConnection;
CREATE TABLE fac_PatchConnection (
  PanelDeviceID int(11) NOT NULL,
  PanelPortNumber int(11) NOT NULL,
  FrontEndpointDeviceID int(11) DEFAULT NULL,
  FrontEndpointPort int(11) DEFAULT NULL,
  RearEndpointDeviceID int(11) DEFAULT NULL,
  RearEndpointPort int(11) DEFAULT NULL,
  FrontNotes varchar(80) DEFAULT NULL,
  RearNotes varchar(80) DEFAULT NULL,
  PRIMARY KEY (PanelDeviceID,PanelPortNumber)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table fac_PDUStats
--

DROP TABLE IF EXISTS fac_PDUStats;
create table fac_PDUStats(
  PDUID int(11) NOT NULL,
  Wattage int(11) NOT NULL,
  PRIMARY KEY (PDUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerConnection
--

DROP TABLE IF EXISTS fac_PowerConnection;
CREATE TABLE fac_PowerConnection (
  PDUID int(11) NOT NULL,
  PDUPosition int(11) NOT NULL,
  DeviceID int(11) NOT NULL,
  DeviceConnNumber int(11) NOT NULL,
  UNIQUE KEY PDUID (PDUID,PDUPosition),
  UNIQUE KEY DeviceID (DeviceID,DeviceConnNumber)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerDistribution
--

DROP TABLE IF EXISTS fac_PowerDistribution;
CREATE TABLE fac_PowerDistribution (
  PDUID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(40) NOT NULL,
  CabinetID int(11) NOT NULL,
  TemplateID int(11) NOT NULL,
  IPAddress varchar(16) NOT NULL,
  SNMPCommunity varchar(50) NOT NULL,
  FirmwareVersion varchar(40) NOT NULL,
  PanelID int(11) NOT NULL,
  BreakerSize int(11) NOT NULL,
  PanelPole int(11) NOT NULL,
  InputAmperage int(11) NOT NULL,
  FailSafe tinyint(1) NOT NULL,
  PanelID2 int(11) NOT NULL,
  PanelPole2 int(11) NOT NULL,
  PRIMARY KEY (PDUID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerPanel
--

DROP TABLE IF EXISTS fac_PowerPanel;
CREATE TABLE fac_PowerPanel (
  PanelID int(11) NOT NULL AUTO_INCREMENT,
  PowerSourceID int(11) NOT NULL,
  PanelLabel varchar(20) NOT NULL,
  NumberOfPoles int(11) NOT NULL,
  MainBreakerSize int(11) NOT NULL,
  PanelVoltage int(11) NOT NULL,
  NumberScheme enum('Odd/Even','Sequential') NOT NULL,
  PRIMARY KEY (PanelID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_PowerSource
--

DROP TABLE IF EXISTS fac_PowerSource;
CREATE TABLE fac_PowerSource (
  PowerSourceID int(11) NOT NULL AUTO_INCREMENT,
  SourceName varchar(80) NOT NULL,
  DataCenterID int(11) NOT NULL,
  IPAddress varchar(20) NOT NULL,
  Community varchar(40) NOT NULL,
  LoadOID varchar(80) NOT NULL,
  Capacity int(11) NOT NULL,
  PRIMARY KEY (PowerSourceID),
  KEY DataCenterID (DataCenterID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
  DeviceType enum('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') NOT NULL,
  LabelColor varchar(80) NOT NULL,
  CurrentLocation varchar(120) NOT NULL,
  SpecialInstructions text NOT NULL,
  PRIMARY KEY (RequestID),
  KEY RequestorID (RequestorID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_SwitchConnection
--

DROP TABLE IF EXISTS fac_SwitchConnection;
CREATE TABLE fac_SwitchConnection (
  SwitchDeviceID int(11) NOT NULL,
  SwitchPortNumber int(11) NOT NULL,
  EndpointDeviceID int(11) NOT NULL,
  EndpointPort int(11) NOT NULL,
  Notes varchar(80) NOT NULL,
  PRIMARY KEY (SwitchDeviceID,SwitchPortNumber),
  UNIQUE KEY EndpointDeviceID (EndpointDeviceID,EndpointPort),
  UNIQUE KEY SwitchDeviceID (SwitchDeviceID,SwitchPortNumber)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Tags
--

DROP TABLE IF EXISTS fac_Tags;
CREATE TABLE fac_Tags (
  TagID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(128) NOT NULL,
  PRIMARY KEY (`TagID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

--
-- Table structure for table fac_User
--

DROP TABLE IF EXISTS fac_User;
CREATE TABLE fac_User (
  UserID varchar(80) NOT NULL,
  Name varchar(80) NOT NULL,
  ReadAccess tinyint(1) NOT NULL,
  WriteAccess tinyint(1) NOT NULL,
  DeleteAccess tinyint(1) NOT NULL,
  ContactAdmin tinyint(1) NOT NULL,
  RackRequest tinyint(1) NOT NULL,
  RackAdmin tinyint(1) NOT NULL,
  SiteAdmin tinyint(1) NOT NULL,
  Disabled tinyint(1) NOT NULL,
  PRIMARY KEY (UserID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
  PRIMARY KEY (VMIndex),
  KEY ValidDevice (DeviceID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_Zone
--

DROP TABLE IF EXISTS fac_Zone;
CREATE TABLE fac_Zone (
  ZoneID int(11) NOT NULL AUTO_INCREMENT,
  DataCenterID int(11) NOT NULL,
  Description varchar(120) NOT NULL,
  PRIMARY KEY (ZoneID),
  KEY DataCenterID (DataCenterID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_SupplyBin
--
DROP TABLE IF EXISTS fac_SupplyBin;
CREATE TABLE fac_SupplyBin (
  BinID int(11) NOT NULL AUTO_INCREMENT,
  Location varchar(40) NOT NULL,
  PRIMARY KEY (BinID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_BinContents
--
DROP TABLE IF EXISTS fac_BinContents;
CREATE TABLE fac_BinContents (
  BinID int(11) NOT NULL,
  SupplyID int(11) NOT NULL,
  Count int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for fac_BinAudits
--
DROP TABLE IF EXISTS fac_BinAudits;
CREATE TABLE fac_BinAudits (
  BinID int(11) NOT NULL,
  UserID int(11) NOT NULL,
  AuditStamp datetime NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO fac_Config VALUES
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
        ('DefaultPanelVoltage','208','Volts','int','208'),
        ('annualCostPerUYear','200','Dollars','float','200'),
        ('annualCostPerWattYear','0.7884','Dollars','float','0.7884'),
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
        ('FacMgrMail','DataCenterMgr@your.domain','Email','string','DataCenterMgr@your.domain'),
		('InstallURL','','URL','string','https://dcim.your.domain'),
        ('Version','2.0','','',''),
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
		('VMExpirationTime','7','Days','int','7');

--
-- Pre-fill some of the templates
--

INSERT INTO fac_Manufacturer set Name="Generic" ON DUPLICATE KEY UPDATE Name="Generic";
INSERT INTO fac_Manufacturer set Name="APC" ON DUPLICATE KEY UPDATE Name="APC";
INSERT INTO fac_Manufacturer set Name="Geist" ON DUPLICATE KEY UPDATE Name="Geist";
INSERT INTO fac_Manufacturer set Name="ServerTech" ON DUPLICATE KEY UPDATE Name="ServerTech";

INSERT INTO fac_CDUTemplate set ManufacturerID=(select ManufacturerID from fac_Manufacturer where Name='Generic'), Model="Unmanaged CDU", Managed=FALSE, VersionOID="", Multiplier=1, OID1="", OID2="", OID3="", ProcessingProfile="SingleOIDAmperes", Voltage="", Amperage="", NumOutlets="";
INSERT INTO fac_CDUTemplate set ManufacturerID=(select ManufacturerID from fac_Manufacturer where Name='APC'), Model="Generic Single-Phase CDU", Managed=TRUE, VersionOID=".1.3.6.1.4.1.318.1.1.4.1.2.0", Multiplier=10, OID1=".1.3.6.1.4.1.318.1.1.12.2.3.1.1.2.1", OID2="", OID3="", ProcessingProfile="SingleOIDAmperes", Voltage="", Amperage="", NumOutlets="";
INSERT INTO fac_CDUTemplate set ManufacturerID=(select ManufacturerID from fac_Manufacturer where Name='Geist'), Model="Generic Delta/Single-Phase CDU", Managed=TRUE, VersionOID=".1.3.6.1.4.1.21239.2.1.2.0", Multiplier=10, OID1=".1.3.6.1.4.1.21239.2.25.1.10.1", OID2="", OID3="", ProcessingProfile="SingleOIDWatts", Voltage="", Amperage="", NumOutlets="";
INSERT INTO fac_CDUTemplate set ManufacturerID=(select ManufacturerID from fac_Manufacturer where Name='Geist'), Model="Generic Wye 3-Phase CDU", Managed=TRUE, VersionOID=".1.3.6.1.4.1.21239.2.1.2.0", Multiplier=10, OID1=".1.3.6.1.4.1.21239.2.6.1.10.1", OID2="", OID3="", ProcessingProfile="SingleOIDWatts", Voltage="", Amperage="", NumOutlets="";
INSERT INTO fac_CDUTemplate set ManufacturerID=(select ManufacturerID from fac_Manufacturer where Name='ServerTech'), Model="Generic Single-Phase CDU", Managed=TRUE, VersionOID=".1.3.6.1.4.1.1718.3.1.1.0", Multiplier=100, OID1=".1.3.6.1.4.1.1718.3.2.2.1.7.1.1", OID2="", OID3="", ProcessingProfile="SingleOIDAmperes", Voltage="", Amperage="", NumOutlets="";
INSERT INTO fac_CDUTemplate set ManufacturerID=(select ManufacturerID from fac_Manufacturer where Name='ServerTech'), Model="Generic 3-Phase CDU", Managed=TRUE, VersionOID=".1.3.6.1.4.1.1718.3.1.1.0", Multiplier=100, OID1=".1.3.6.1.4.1.1718.3.2.2.1.7.1.1", OID2=".1.3.6.1.4.1.1718.3.2.2.1.7.1.2", OID3=".1.3.6.1.4.1.1718.3.2.2.1.7.1.3", ProcessingProfile="Convert3PhAmperes", Voltage="", Amperage="", NumOutlets="";

