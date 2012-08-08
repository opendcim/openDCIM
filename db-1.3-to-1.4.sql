--
-- Changes to openDCIM database for migrating from 1.3 to 1.4
--

--
-- Bump version number up
--

UPDATE fac_Config SET Value = '1.4' WHERE fac_Config.Parameter = 'Version';


--
-- Table structure for fac_SupplyBin
--
DROP TABLE IF EXISTS fac_SupplyBin;
CREATE TABLE fac_SupplyBin (
  BinID int(11) NOT NULL AUTO_INCREMENT,
  Location varchar(40) NOT NULL,
  PRIMARY KEY (BinID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

--
-- Table structure for fac_BinContents
--
DROP TABLE IF EXISTS fac_BinContents;
CREATE TABLE fac_BinContents (
  BinID int(11) NOT NULL,
  SupplyID int(11) NOT NULL,
  Count int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

--
-- Table structure for fac_BinAudits
--
DROP TABLE IF EXISTS fac_BinAudits;
CREATE TABLE fac_BinAudits (
  BinID int(11) NOT NULL,
  UserID int(11) NOT NULL,
  AuditStamp datetime NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

--
-- Style Customization
--
INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES
('HeaderColor', '#006633', 'HexColor', 'string', '#006633'),
('BodyColor', '#F0E0B2', 'HexColor', 'string', '#F0E0B2'),
('LinkColor', '#000000', 'HexColor', 'string', '#000000'),
('VisitedLinkColor', '#8D90B3', 'HexColor', 'string', '#8D90B3');

--
-- Moved deadlines for rack requests into configuration table
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES 
('RackWarningHours', 4, 'Hours', 'integer', '4'),
('RackOverdueHours', 1, 'Hours', 'integer', '1');

--
-- Not sure how this got overlooked for several releases
-- 
ALTER TABLE fac_RackRequest ADD MfgDate DATE NOT NULL AFTER SerialNo; 

--
-- Add database structure changes for Parent/Child relationships of Chassis / Slots
--

ALTER TABLE fac_Device add column ChassisSlots smallint(6) NOT NULL AFTER DeviceType;
ALTER TABLE fac_Device add column ParentDevice int(11) NOT NULL AFTER ChassisSlots;

--
-- Change the DeviceType enumeration from having 'Routing Chassis' to just plain 'Chassis'
--

ALTER TABLE fac_Device MODIFY COLUMN DeviceType enum('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure','Chassis');
UPDATE fac_Device SET DeviceType='Chassis' WHERE DeviceType = 'Routing Chassis';
ALTER TABLE fac_Device MODIFY COLUMN DeviceType enum('Server','Appliance','Storage Array','Switch','Patch Panel','Physical Infrastructure','Chassis');

--
-- Change the TemplateDevice enumeration from having 'Routing Chassis' to just plain 'Chassis'

ALTER TABLE fac_DeviceTemplate MODIFY COLUMN DeviceType enum('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure','Chassis');
UPDATE fac_DeviceTemplate set DeviceType='Chassis' WHERE DeviceType = 'Routing Chassis';
ALTER TABLE fac_DeviceTemplate MODIFY COLUMN DeviceType enum('Server','Appliance','Storage Array','Switch','Patch Panel','Physical Infrastructure','Chassis');
