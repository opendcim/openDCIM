--
-- Table structure for fac_MeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_MeasurePoint (
	MPID int(11) NOT NULL AUTO_INCREMENT,
	Label varchar(40) NOT NULL,
	IPAddress varchar(254) NOT NULL,
	Type ENUM('elec', 'cooling', 'air') NOT NULL,
	ConnectionType ENUM('SNMP','Modbus') NOT NULL,
	PRIMARY KEY(MPID)
	)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_ElectricalMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_ElectricalMeasurePoint (
	MPID int(11) NOT NULL,
	DataCenterID int(11) NOT NULL,
	EnergyTypeID int(11) NOT NULL,
	Category ENUM('none', 'IT', 'Cooling', 'Other Mechanical', 'UPS Input', 'UPS Output', 'Energy Reuse') NOT NULL,
	UPSPowered TINYINT(1) NOT NULL,
	PowerMultiplier ENUM('0.1','1','10','100') NOT NULL,
	EnergyMultiplier ENUM('0.1','1','10','100') NOT NULL,
	UNIQUE KEY MPID(MPID),
	KEY DataCenterID (DataCenterID)
	)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_SNMPElectricalMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_SNMPElectricalMeasurePoint (
	MPID int(11) NOT NULL,
	SNMPCommunity varchar(40) NOT NULL,
	SNMPVersion ENUM('1','2c') NOT NULL,
	v3SecurityLevel varchar(12) NOT NULL DEFAULT '',
	v3AuthProtocol varchar(3) NOT NULL DEFAULT '',
	v3AuthPassphrase varchar(80) NOT NULL DEFAULT '',
	v3PrivProtocol varchar(3) NOT NULL DEFAULT '',
	v3PrivPassphrase varchar(80) NOT NULL DEFAULT '',
	OID1 varchar(80) NOT NULL,
	OID2 varchar(80) NOT NULL,
	OID3 varchar(80) NOT NULL,
	OIDEnergy varchar(80) NOT NULL,
	UNIQUE KEY MPID (MPID)
	)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_ModbusElectricalMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_ModbusElectricalMeasurePoint (
	MPID int(11) NOT NULL,
	UnitID int(11) NOT NULL,
	NbWords int(11) NOT NULL,
	Register1 int(11) NOT NULL,
	Register2 int(11) NOT NULL,
	Register3 int(11) NOT NULL,
	RegisterEnergy int(11) NOT NULL,
	UNIQUE KEY MPID (MPID)
	)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_MeasurePointGroup
--

CREATE TABLE IF NOT EXISTS fac_MeasurePointGroup (
	MPGID int(11) NOT NULL AUTO_INCREMENT, 
	Name varchar(40) NOT NULL, 
	PRIMARY KEY(MPGID)
	)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_AssoMeasurePointGroup
--

CREATE TABLE IF NOT EXISTS fac_AssoMeasurePointGroup (
	MPGID int(11) NOT NULL, 
	MPID int(11) NOT NULL, 
	KEY MPGID (MPGID), 
	KEY MPID (MPID), 
	UNIQUE KEY (MPGID, MPID)
	)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_SNMPCoolingMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_SNMPCoolingMeasurePoint (
        MPID int(11) NOT NULL,
        SNMPCommunity varchar(40) NOT NULL,
        SNMPVersion ENUM('1','2c') NOT NULL,
        FanSpeedOID varchar(80) NOT NULL,
        CoolingOID varchar(80) NOT NULL,
        UNIQUE KEY MPID (MPID)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_ModbusCoolingMeasurepoint
--

CREATE TABLE IF NOT EXISTS fac_ModbusCoolingMeasurePoint (
        MPID int(11) NOT NULL,
        UnitID int(11) NOT NULL,
        NbWords int(11) NOT NULL,
        FanSpeedRegister int(11) NOT NULL,
        CoolingRegister int(11) NOT NULL,
        UNIQUE KEY MPID (MPID)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_CoolingMeasure
--

CREATE TABLE IF NOT EXISTS fac_CoolingMeasure (
        MPID int(11) NOT NULL,
        FanSpeed int(11) NOT NULL,
        Cooling int(11) NOT NULL,
        Date DATETIME NOT NULL,
        KEY MPID (MPID),
        UNIQUE KEY (MPID, Date)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_SNMPAirMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_SNMPAirMeasurePoint (
        MPID int(11) NOT NULL,
        SNMPCommunity varchar(40) NOT NULL,
        SNMPVersion ENUM('1','2c') NOT NULL,
        TemperatureOID varchar(80) NOT NULL,
        HumidityOID varchar(80) NOT NULL,
        UNIQUE KEY MPID (MPID)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_ModbusAirMeasurePoint
--

CREATE TABLE IF NOT EXISTS fac_ModbusAirMeasurePoint (
        MPID int(11) NOT NULL,
        UnitID int(11) NOT NULL,
        NbWords int(11) NOT NULL,
        TemperatureRegister int(11) NOT NULL,
        HumidityRegister int(11) NOT NULL,
        UNIQUE KEY MPID (MPID)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_AirMeasure
--

CREATE TABLE IF NOT EXISTS fac_AirMeasure (
        MPID int(11) NOT NULL,
        Temperature DECIMAL(5,2) NOT NULL,
        Humidity DECIMAL(5,2) NOT NULL,
        Date DATETIME NOT NULL,
        KEY MPID (MPID),
        UNIQUE KEY (MPID, Date)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_EnergyType
--

CREATE TABLE IF NOT EXISTS fac_EnergyType (
        EnergyTypeID int(11) NOT NULL AUTO_INCREMENT,
        Name varchar(40) NOT NULL,
        GasEmissionFactor DECIMAL(5,3) NOT NULL,
        PRIMARY KEY(EnergyTypeID)
        )ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for fac_MechanicalDevice
--

CREATE TABLE IF NOT EXISTS fac_MechanicalDevice (
	MechID int(11) NOT NULL AUTO_INCREMENT,
	Label varchar(40) NOT NULL,
	DataCenterID int(11) NOT NULL,
	ZoneID int(11) NOT NULL,
	PanelID int(11) NOT nULL,
	BreakerSize int(11) NOT NULL,
	PanelPole int(11) NOT NULL,
	IPAddress varchar(254) NOT NULL,
	SNMPVersion ENUM('1','2c') NOT NULL,
	SNMPCommunity varchar(50) NOT NULL,
	LoadOID varchar(80) NOT NULL,
	PRIMARY KEY(MechID),
	KEY DataCenterID (DataCenterID),
	KEY ZoneID (ZoneID),
	KEY PanelID (PanelID)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Let's add a first row in fac_EnergyType
--

INSERT INTO fac_EnergyType SET Name = "Electricity", GasEmissionFactor = 0.066;

--
-- Add some attributes to fac_DataCenter
--

ALTER TABLE fac_DataCenter ADD CreationDate datetime NOT NULL after DeliveryAddress;
ALTER TABLE fac_DataCenter ADD PUEFrequency enum('-','C','D','W','M','Y') NOT NULL after ContainerID;
ALTER TABLE fac_DataCenter ADD PUELevel enum('L1', 'L2', 'L3') NOT NULL after ContainerID;

--
-- Bump up the database version
--
UPDATE fac_Config set Value='PUE_DCEM' WHERE Parameter='Version';
