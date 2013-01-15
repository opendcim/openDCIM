
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
-- Remove the primary key (unique) constraint from the CabinetAudit table
-- Handled this in the installer.  Left for people that might just try to 
-- apply the update manually.
--

-- ALTER TABLE fac_CabinetAudit DROP PRIMARY KEY;

--
-- Correction for typo in create.sql for v1.5, won't have an effect on 
-- existing installations will just recreate the same index after it removes it
--

ALTER TABLE fac_PowerConnection DROP INDEX PDUID;
ALTER TABLE fac_PowerConnection ADD UNIQUE KEY (PDUID,PDUPosition);

--
-- Add field to Data Centers for tracking Maximum Design Capacity in kW
--

ALTER TABLE fac_DataCenter ADD COLUMN MaxkW INT(11) NOT NULL AFTER Administrator;

--
-- New table for smart power distribution device templates
--

DROP TABLE IF EXISTS fac_CDUTemplate;
CREATE TABLE fac_CDUTemplate (
  TemplateID int(11) NOT NULL AUTO_INCREMENT,
  ManufacturerID int(11) NOT NULL,
  Model varchar(80) NOT NULL,
  Managed int(1) NOT NULL,
  VersionOID varchar(80) NOT NULL,
  Multiplier enum( '1', '10', '100' ) NOT NULL,
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
-- Recreate the PDU_Stats table - all existing stats will be LOST, but since it only holds the last value, it's a minor inconvenience
--

DROP TABLE IF EXISTS fac_PDUStats;
CREATE TABLE fac_PDUStats(
  PDUID int(11) NOT NULL,
  Wattage int(11) NOT NULL,
  PRIMARY KEY (PDUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Update the existing fac_PowerDistribution table to reference the new templates
--

ALTER TABLE fac_PowerDistribution ADD COLUMN TemplateID int(11) NOT NULL AFTER CabinetID;

--
-- Attempt to insert manufacturers of APC, Geist, and ServerTech.  If they already exist, fine.
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

--
-- Set the TemplateID for the three types of management as a means of preserving as much data as possible
--

UPDATE fac_PowerDistribution SET TemplateID=(select TemplateID from fac_CDUTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID and b.Name="APC" and a.Model="Generic Single-Phase CDU") where ManagementType="APC";
UPDATE fac_PowerDistribution SET TemplateID=(select TemplateID from fac_CDUTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID and b.Name="Geist" and a.Model="Generic Delta/Single-Phase CDU") where ManagementType="Geist" and BreakerSize<3;
UPDATE fac_PowerDistribution SET TemplateID=(select TemplateID from fac_CDUTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID and b.Name="Geist" and a.Model="Generic Wye 3-Phase CDU") where ManagementType="Geist" and BreakerSize=3;
UPDATE fac_PowerDistribution SET TemplateID=(select TemplateID from fac_CDUTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID and b.Name="ServerTech" and a.Model="Generic Single-Phase CDU") where ManagementType="ServerTech" and BreakerSize<3;
UPDATE fac_PowerDistribution SET TemplateID=(select TemplateID from fac_CDUTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID and b.Name="ServerTech" and a.Model="Generic 3-Phase CDU") where ManagementType="ServerTech" and BreakerSize=3;

-- 
-- Delete the columns no longer needed in the PowerDistribution table
--

ALTER TABLE fac_PowerDistribution DROP COLUMN ManagementType;
ALTER TABLE fac_PowerDistribution DROP COLUMN Model;
ALTER TABLE fac_PowerDistribution DROP COLUMN NumOutputs;

--
-- Add a new configurable timezone parameter 
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES
('timezone', 'America/Chicago', 'string', 'string', 'America/Chicago');

--
-- Add new table for Patch Panel Connections
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
-- Add new tables for device tagging
--

DROP TABLE IF EXISTS fac_DeviceTags;
CREATE TABLE fac_DeviceTags (
  DeviceID int(11) NOT NULL,
  TagID int(11) NOT NULL,
  PRIMARY KEY (DeviceID,TagID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS fac_Tags;
CREATE TABLE fac_Tags (
  TagID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(128) NOT NULL,
  PRIMARY KEY (`TagID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

--
-- Add a new configurable device label case
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES 
('LabelCase','upper','string','string','upper');


--
-- Add a configuration item for defaulting empty date values on the device screen to NOW() or the epoch.
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES 
('mDate','blank','string','string','blank');
INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES 
('wDate','blank','string','string','blank');

--
-- Add configuration items for use in reports
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES 
('NewInstallsPeriod','7','Days','int','7');
INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES 
('InstallURL','','URL','string','');

