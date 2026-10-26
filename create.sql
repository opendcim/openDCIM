  PDUPosition VARCHAR(11) NOT NULL,
  DeviceID int(11) NOT NULL,
  DeviceConnNumber int(11) NOT NULL,
  UNIQUE KEY PDUID (PDUID,PDUPosition),
  UNIQUE KEY DeviceID (DeviceID,DeviceConnNumber)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table Structure for table fac_Powerconnector
--

DROP TABLE IF EXISTS fac_PowerConnectors;
CREATE TABLE fac_PowerConnectors (
  ConnectorID int(11) NOT NULL AUTO_INCREMENT,
  ConnectorName varchar(40) NOT NULL,
  PRIMARY KEY ConnectorID (ConnectorID),
  UNIQUE KEY ConnectorName (ConnectorName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_PowerConnectors set ConnectorName='C13';
INSERT INTO fac_PowerConnectors set ConnectorName='C19';

--
-- Table Structure for table fac_PowerPhases
--

DROP TABLE IF EXISTS fac_PowerPhases;
CREATE TABLE fac_PowerPhases (
  PhaseID int(11) NOT NULL AUTO_INCREMENT,
  PhaseName varchar(40) NOT NULL,
  PRIMARY KEY PhaseID (PhaseID),
  UNIQUE KEY PhaseName (PhaseName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_PowerPhases set PhaseName='A';
INSERT INTO fac_PowerPhases set PhaseName='B';
INSERT INTO fac_PowerPhases set PhaseName='C';

--
-- Table Structure for table fac_PowerVoltages
--

DROP TABLE IF EXISTS fac_PowerVoltages;
CREATE TABLE fac_PowerVoltages (
  VoltageID int(11) NOT NULL AUTO_INCREMENT,
  VoltageName varchar(40) NOT NULL,
  PRIMARY KEY VoltageID (VoltageID),
  UNIQUE KEY VoltageName (VoltageName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_PowerVoltages set VoltageName='120AC';
INSERT INTO fac_PowerVoltages set VoltageName='208AC';
INSERT INTO fac_PowerVoltages set VoltageName='240AC';
INSERT INTO fac_PowerVoltages set VoltageName='277AC';
INSERT INTO fac_PowerVoltages set VoltageName='48DC';

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
  ConnectorID int(11) DEFAULT NULL,
  PhaseID int(11) DEFAULT NULL,
  VoltageID int(11) DEFAULT NULL,
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
  Hypervisor varchar(40) NOT NULL,
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
  RequestedAction varchar(10) NOT NULL,
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
