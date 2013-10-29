--
-- Extend power multiplier enum
-- An add/copy/drop has to be done to make sure that the enum 
-- values are migrated "by value" instead of "by order"
--

ALTER TABLE fac_CDUTemplate ADD COLUMN new_mult enum('0.1','1','10','100');
UPDATE fac_CDUTemplate SET new_mult=Multiplier;
ALTER TABLE fac_CDUTemplate DROP Multiplier;
ALTER TABLE fac_CDUTemplate CHANGE new_mult Multiplier enum('0.1','1','10','100');

--
-- Add fields to CDU Templates for ATS monitoring
--

ALTER TABLE fac_CDUTemplate ADD COLUMN ATS INT(1) NOT NULL AFTER Managed;
ALTER TABLE fac_CDUTemplate ADD COLUMN ATSStatusOID varchar(80) NOT NULL AFTER OID3;
ALTER TABLE fac_CDUTemplate ADD COLUMN ATSDesiredResult varchar(80) NOT NULL AFTER ATSStatusOID;

--
-- Add coordinates and zoom defining zone in DC image
--

ALTER TABLE fac_Zone ADD COLUMN MapX1 int(11) NOT NULL AFTER Description;
ALTER TABLE fac_Zone ADD COLUMN MapY1 int(11) NOT NULL AFTER MapX1;
ALTER TABLE fac_Zone ADD COLUMN MapX2 int(11) NOT NULL AFTER MapY1;
ALTER TABLE fac_Zone ADD COLUMN MapY2 int(11) NOT NULL AFTER MapX2;
ALTER TABLE fac_Zone ADD COLUMN MapZoom int(11) DEFAULT '100' NOT NULL AFTER MapY2;

--
-- Extend the fac_CabRow table to hold an order attribute
--
ALTER TABLE fac_CabRow ADD COLUMN CabOrder ENUM( 'ASC', 'DESC' ) NOT NULL DEFAULT 'ASC';

--
-- Bump up the database version
--

UPDATE fac_Config set Value='3.1' WHERE Parameter='Version';

--
-- Add configuration item for page size of the worksheets of generated Excel files
--

INSERT INTO fac_Config VALUES ('PageSize', 'Letter', 'string', 'string', 'Letter');

--
-- Add configuration items for temperature and humidity ranges for DC and Zone drawing
--

INSERT INTO fac_Config VALUES ('TemperatureRed', '30', 'degrees', 'float', '30'),
	('TemperatureYellow', '25', 'degrees', 'float', '25'),
	('HumidityRedHigh', '75', 'percentage', 'float', '75'),
	('HumidityRedLow', '35', 'percentage', 'float', '35'),
	('HumidityYellowHigh', '55', 'percentage', 'float', '55'),
	('HumidityYellowLow', '45', 'percentage', 'float', '45');	
	
--
-- Add LastRead field in PDUStats
--

ALTER TABLE fac_PDUStats ADD COLUMN LastRead datetime DEFAULT NULL AFTER Wattage;

--
-- Extend the fac_DeviceTemplate table to hold notes
--

ALTER TABLE fac_DeviceTemplate ADD COLUMN Notes text NOT NULL AFTER NumPorts;

--
-- Add a new table for sensor probe templates
--

DROP TABLE IF EXISTS fac_SensorTemplate;
CREATE TABLE fac_SensorTemplate (
	TemplateID INT(11) NOT NULL AUTO_INCREMENT,
	ManufacturerID INT(11) NOT NULL,
	Name VARCHAR(80) NOT NULL,
	SNMPVersion ENUM('1','2c') NOT NULL DEFAULT '2c',
	TemperatureOID VARCHAR(256) NOT NULL,
	HumidityOID VARCHAR(256) NOT NULL,
	TempMultiplier FLOAT(8) NOT NULL DEFAULT 1,
	HumidityMultiplier FLOAT(8) NOT NULL DEFAULT 1,
	PRIMARY KEY(TemplateID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Alter the fac_Cabinet table to reference templates, rather than OIDs
--

ALTER TABLE fac_Cabinet DROP COLUMN TempSensorOID;
ALTER TABLE fac_Cabinet DROP COLUMN HumiditySensorOID;
ALTER TABLE fac_Cabinet ADD COLUMN SensorTemplateID INT(11) NOT NULL AFTER SensorCommunity;

--
-- Temperature and Humidity should be floats, not integers
--

ALTER TABLE fac_CabinetTemps MODIFY COLUMN Temp FLOAT(8) NOT NULL;
ALTER TABLE fac_CabinetTemps MODIFY COLUMN Humidity FLOAT(8) NOT NULL;

--
-- New configuration item
--

INSERT INTO fac_Config VALUES (
'SNMPCommunity', 'public', 'string', 'string', 'public' );

