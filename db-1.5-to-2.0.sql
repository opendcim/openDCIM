
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.0' WHERE fac_Config.Parameter = 'Version';

--
-- New configuration parameters
--

--
-- This one may or may not have been added manually by people who installed 1.5.2, so use the $config->rebuild in the installer
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES
('FreeSpaceColor', '#FFFFFF', 'HexColor', 'string', '#FFFFFF');

--
-- Force department names to be unique
--

ALTER TABLE fac_Department ADD UNIQUE (Name);

--
-- Force Manufacturer names to be unique
--

ALTER TABLE fac_Manufacturer ADD UNIQUE (Name);

--
-- Force device templates to be unique
--

ALTER TABLE fac_DeviceTemplate ADD UNIQUE KEY (ManufacturerID,Model);

--
-- Correction for typo in create.sql for v1.5, won't have an effect on 
-- existing installations will just recreate the same index after it removes it
--

ALTER TABLE fac_PowerConnection DROP INDEX PDUID;
ALTER TABLE fac_PowerConnection ADD UNIQUE KEY (PDUID,PDUPosition);

--
-- New table for smart power distribution device templates
--

DROP TABLE IF EXISTS fac_CDUTemplate;
CREATE TABLE fac_CDUTemplate (
  TemplateID int(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Managed int(1) NOT NULL,
  UnitOfMeasure enum( 'Watts', 'deciAmperes', 'milliAmperes' ) NOT NULL,
  OID1 varchar(80) NOT NULL,
  OID2 varchar(80) NOT NULL,
  OID3 varchar(80) NOT NULL,
  ProcessingProfile enum('SingleOIDWatts','SingleOIDAmperes','Combine3OIDAmperes','Convert3PhAmperes'),
  Voltage int(11) NOT NULL,
  Amperage int(11) NOT NULL,
  NumOutlets int(11) NOT NULL,
  PRIMARY KEY (TemplateID),
  KEY ManufacturerID (ManufacturerID),
  UNIQUE KEY (ManufacturerID, Model)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Create templates equivalent to the three management types (really 6, since we had single v. three phase) that we had before
--


--
-- Update the existing fac_PowerDistribution table to reference the new templates
--

ALTER TABLE fac_PowerDistribution ADD COLUMN TemplateID int(11) NOT NULL AFTER CabinetID;

--
-- Set the TemplateID for the three types of management as a means of preserving as much data as possible
--

-- 
-- Delete the columns no longer needed in the PowerDistribution table
--

ALTER TABLE fac_PowerDistribution DELETE COLUMN InputAmperage;
ALTER TABLE fac_PowerDistribution DELETE COLUMN ManagementType;
ALTER TABLE fac_PowerDistribution DELETE COLUMN Model;
ALTER TABLE fac_PowerDistribution DELETE COLUMN NumInputs;
