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

---
--- Extend the fac_DeviceTemplate table to hold notes
---

ALTER TABLE fac_DeviceTemplate ADD COLUMN Notes text NOT NULL AFTER NumPorts;