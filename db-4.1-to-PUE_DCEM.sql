--
-- Table structure for fac_MeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_MeasurePoint (
  MPID INT(11) NOT NULL AUTO_INCREMENT,
  Label VARCHAR(80) NOT NULL,
  IPAddress VARCHAR(45) NOT NULL,
  Type VARCHAR(7) NOT NULL DEFAULT "elec",
  ConnectionType VARCHAR(6) NOT NULL DEFAULT "SNMP",
  PRIMARY KEY(MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_ElectricalMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_ElectricalMeasurePoint (
  MPID INT(11) NOT NULL,
  DataCenterID INT(11) NOT NULL,
  EnergyTypeID INT(11) NOT NULL,
  Category VARCHAR(16) NOT NULL DEFAULT "none",
  UPSPowered TINYINT(1) NOT NULL,
  PowerMultiplier VARCHAR(6) NULL DEFAULT NULL,
  EnergyMultiplier VARCHAR(6) NULL DEFAULT NULL,
  UNIQUE KEY MPID(MPID),
  KEY DataCenterID (DataCenterID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_SNMPElectricalMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_SNMPElectricalMeasurePoint (
  MPID INT(11) NOT NULL,
  SNMPCommunity VARCHAR(80) NOT NULL,
  SNMPVersion VARCHAR(2) NOT NULL DEFAULT "2c",
  v3SecurityLevel VARCHAR(12) NOT NULL,
  v3AuthProtocol VARCHAR(3) NOT NULL,
  v3AuthPassphrase VARCHAR(80) NOT NULL,
  v3PrivProtocol VARCHAR(3) NOT NULL,
  v3PrivPassphrase VARCHAR(80) NOT NULL,
  OID1 VARCHAR(80) NOT NULL,
  OID2 VARCHAR(80) NOT NULL,
  OID3 VARCHAR(80) NOT NULL,
  OIDEnergy VARCHAR(80) NOT NULL,
  UNIQUE KEY MPID (MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_ModbusElectricalMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_ModbusElectricalMeasurePoint (
  MPID INT(11) NOT NULL,
  UnitID INT(11) NOT NULL,
  NbWords INT(11) NOT NULL,
  Register1 INT(11) NOT NULL,
  Register2 INT(11) NOT NULL,
  Register3 INT(11) NOT NULL,
  RegisterEnergy INT(11) NOT NULL,
  UNIQUE KEY MPID (MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_ElectricalMeasure
--

CREATE TABLE IF NOT EXISTS fac_ElectricalMeasure (
  MPID INT(11) NOT NULL,
  Wattage1 INT(11) NOT NULL,
  Wattage2 INT(11) NOT NULL,
  Wattage3 INT(11) NOT NULL,
  Energy INT(11) NOT NULL,
  Date DATETIME NOT NULL,
  KEY MPID (MPID),
  UNIQUE KEY (MPID, Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_MeasurePointGroup
--

CREATE TABLE IF NOT EXISTS fac_MeasurePointGroup (
  MPGID INT(11) NOT NULL AUTO_INCREMENT, 
  Name VARCHAR(40) NOT NULL, 
  PRIMARY KEY(MPGID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_AssoMeasurePointGroup
--

CREATE TABLE IF NOT EXISTS fac_AssoMeasurePointGroup (
  MPGID INT(11) NOT NULL, 
  MPID INT(11) NOT NULL, 
  KEY MPGID (MPGID), 
  KEY MPID (MPID), 
  UNIQUE KEY (MPGID, MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_SNMPCoolingMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_SNMPCoolingMeasurePoint (
  MPID INT(11) NOT NULL,
  SNMPCommunity VARCHAR(80) NOT NULL,
  SNMPVersion VARCHAR(2) NOT NULL DEFAULT "2c",
  v3SecurityLevel VARCHAR(12) NOT NULL,
  v3AuthProtocol VARCHAR(3) NOT NULL,
  v3AuthPassphrase VARCHAR(80) NOT NULL,
  v3PrivProtocol VARCHAR(3) NOT NULL,
  v3PrivPassphrase VARCHAR(80) NOT NULL,
  FanSpeedOID VARCHAR(80) NOT NULL,
  CoolingOID VARCHAR(80) NOT NULL,
  UNIQUE KEY MPID (MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_ModbusCoolingMeasurepoINT
--

CREATE TABLE IF NOT EXISTS fac_ModbusCoolingMeasurePoint (
  MPID INT(11) NOT NULL,
  UnitID INT(11) NOT NULL,
  NbWords INT(11) NOT NULL,
  FanSpeedRegister INT(11) NOT NULL,
  CoolingRegister INT(11) NOT NULL,
  UNIQUE KEY MPID (MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_CoolingMeasure
--

CREATE TABLE IF NOT EXISTS fac_CoolingMeasure (
  MPID INT(11) NOT NULL,
  FanSpeed INT(11) NOT NULL,
  Cooling INT(11) NOT NULL,
  Date DATETIME NOT NULL,
  KEY MPID (MPID),
  UNIQUE KEY (MPID, Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_SNMPAirMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_SNMPAirMeasurePoint (
  MPID INT(11) NOT NULL,
  SNMPCommunity VARCHAR(80) NOT NULL,
  SNMPVersion VARCHAR(2) NOT NULL DEFAULT "2c",
  v3SecurityLevel VARCHAR(12) NOT NULL,
  v3AuthProtocol VARCHAR(3) NOT NULL,
  v3AuthPassphrase VARCHAR(80) NOT NULL,
  v3PrivProtocol VARCHAR(3) NOT NULL,
  v3PrivPassphrase VARCHAR(80) NOT NULL,
  TemperatureOID VARCHAR(80) NOT NULL,
  HumidityOID VARCHAR(80) NOT NULL,
  UNIQUE KEY MPID (MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_ModbusAirMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_ModbusAirMeasurePoint (
  MPID INT(11) NOT NULL,
  UnitID INT(11) NOT NULL,
  NbWords INT(11) NOT NULL,
  TemperatureRegister INT(11) NOT NULL,
  HumidityRegister INT(11) NOT NULL,
  UNIQUE KEY MPID (MPID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_AirMeasure
--

CREATE TABLE IF NOT EXISTS fac_AirMeasure (
  MPID INT(11) NOT NULL,
  Temperature DECIMAL(5,2) NOT NULL,
  Humidity DECIMAL(5,2) NOT NULL,
  Date DATETIME NOT NULL,
  KEY MPID (MPID),
  UNIQUE KEY (MPID, Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_EnergyType
--

CREATE TABLE IF NOT EXISTS fac_EnergyType (
  EnergyTypeID INT(11) NOT NULL AUTO_INCREMENT,
  Name VARCHAR(40) NOT NULL,
  GasEmissionFactor DECIMAL(5,3) NOT NULL,
  PRIMARY KEY(EnergyTypeID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for fac_MechanicalDevice
--

CREATE TABLE IF NOT EXISTS fac_MechanicalDevice (
  MechID INT(11) NOT NULL AUTO_INCREMENT,
  Label VARCHAR(80) NOT NULL,
  DataCenterID INT(11) NOT NULL,
  ZoneID INT(11) NOT NULL,
  PanelID INT(11) NOT NULL,
  BreakerSize INT(11) NOT NULL,
  PanelPole INT(11) NOT NULL,
  IPAddress VARCHAR(45) NOT NULL,
  SNMPVersion VARCHAR(2) NOT NULL DEFAULT "2c",
  SNMPCommunity VARCHAR(80) NOT NULL,
  LoadOID VARCHAR(80) NOT NULL,
  PRIMARY KEY(MechID),
  KEY DataCenterID (DataCenterID),
  KEY ZoneID (ZoneID),
  KEY PanelID (PanelID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Let"s add a first row in fac_EnergyType
--

INSERT INTO fac_EnergyType SET Name = "Electricity", GasEmissionFactor = 0.066;

--
-- Add some attributes to fac_DataCenter
--

ALTER TABLE fac_DataCenter ADD CreationDate DATETIME NOT NULL AFTER DeliveryAddress;
ALTER TABLE fac_DataCenter ADD PUEFrequency VARCHAR(1) NOT NULL DEFAULT "D" AFTER ContainerID;
ALTER TABLE fac_DataCenter ADD PUELevel VARCHAR(2) NOT NULL DEFAULT "L1" AFTER ContainerID;

--
-- Add Config item for TimeInterval
--

INSERT INTO fac_Config set Parameter="TimeInterval", Value="Last 7 Days", UnitOfMeasure="time", ValType="string", DefaultVal="Last 7 Days";

--
-- Bump up the database version
--
UPDATE fac_Config set Value="PUE_DCEM" WHERE Parameter="Version";
