
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.1' WHERE fac_Config.Parameter = 'Version';

--
-- Add table for rack tagging
--

DROP TABLE IF EXISTS fac_CabinetTags;
CREATE TABLE fac_CabinetTags (
  CabinetID int(11) NOT NULL,
  TagID int(11) NOT NULL,
  PRIMARY KEY (CabinetID,TagID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add Notes field to cabinet table
--

ALTER TABLE fac_Cabinet ADD Notes TEXT NULL AFTER MapY2;

--
-- Add configuration item to allow enabling tooltips on the rack view
--

INSERT INTO fac_Config VALUES ('ToolTips', 'Disabled', 'Enabled/Disabled', 'string', 'Disabled');

--
-- Add table for rack tooltips
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
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'ESX', 'ESX Server?', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'InstallDate', 'Install Date', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'MfgDate', 'Manufacture Date', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'NominalWatts', 'Nominal Draw (Watts)', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'Owner', 'Departmental Owner', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'Ports', 'Number of Data Ports', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'PowerSupplyCount', 'Number of Power Supplies', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'PrimaryContact', 'Primary Contact', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'PrimaryIP', 'Primary IP', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'Reservation', 'Reservation?', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'SerialNo', 'Serial Number', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'SNMPCommunity', 'SNMP Read Only Community', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'TemplateID', 'Device Class', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'WarrantyCo', 'Warranty Company', 0);
INSERT INTO fac_CabinetToolTip VALUES(NULL, 'WarrantyExpire', 'Warranty Expiration', 0);

--
-- Table structure for table `fac_MediaTypes`
--

DROP TABLE IF EXISTS fac_MediaTypes;
CREATE TABLE IF NOT EXISTS fac_MediaTypes (
  MediaID int(11) NOT NULL AUTO_INCREMENT,
  MediaType varchar(40) NOT NULL,
  PRIMARY KEY (MediaID),
  UNIQUE KEY MediaType (MediaType)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

--
-- Table structure for table fac_DevicePorts
--

DROP TABLE IF EXISTS fac_DevicePorts;
CREATE TABLE fac_DevicePorts (
  ConnectionID int(11) NOT NULL AUTO_INCREMENT,
  DeviceID int(11),
  DevicePort int(11),
  MediaID int(11),
  Notes text NULL,
  PRIMARY KEY (ConnectionID),
  KEY DeviceID (DeviceID,DevicePort)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Add configuration item to allow english or metric values
--

INSERT INTO fac_Config VALUES ('mUnits', 'english', 'English/Metric', 'string', 'english');
