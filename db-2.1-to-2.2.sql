--
-- Add configuration item for setting the paths to snmpwalk and snmpget
--

INSERT INTO fac_Config VALUES ('snmpwalk', '/usr/bin/snmpwalk', 'path', 'string', '/usr/bin/snmpwalk');
INSERT INTO fac_Config VALUES ('snmpget', '/usr/bin/snmpget', 'path', 'string', '/usr/bin/snmpget');
INSERT INTO fac_Config VALUES ('cut', '/bin/cut', 'path', 'string', '/bin/cut');

--
-- Add two more fields to the DevicePorts table
--

ALTER TABLE fac_DevicePorts ADD COLUMN PortDescriptor varchar(30) AFTER MediaID;
ALTER TABLE fac_DevicePorts ADD COLUMN CableColor int(11) AFTER PortDescriptor;

--
-- Add the SNMPVersion field to CDUTemplates
--

ALTER TABLE fac_CDUTemplate ADD COLUMN SNMPVersion enum( '1', '2c' ) AFTER Managed;

--
-- Add the new UserRights field for allowing users to admin their own devices
--

ALTER TABLE fac_User ADD COLUMN AdminOwnDevices tinyint(1) NOT NULL AFTER Name;

--
-- Add configuration item to allow enabling tooltips on the rack view
--

INSERT INTO fac_Config VALUES ('CDUToolTips', 'Disabled', 'Enabled/Disabled', 'string', 'Disabled');

--
-- Add table for cdu tooltips
--

DROP TABLE IF EXISTS fac_CDUToolTip;
CREATE TABLE fac_CDUToolTip (
  SortOrder smallint(6) DEFAULT NULL,
  Field varchar(20) NOT NULL,
  Label varchar(30) NOT NULL,
  Enabled tinyint(1) DEFAULT '1',
  UNIQUE KEY Field (Field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add base ToolTip configuration options
--

INSERT INTO fac_CDUToolTip VALUES(NULL, 'PanelID', 'Source Panel', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'PanelVoltage', 'Voltage', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'BreakerSize', 'Breaker Size', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'PanelPole', 'Panel Pole Number', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'InputAmperage', 'Input Amperage', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'Model', 'Model', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'IPAddress', 'IP Address', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'Uptime', 'Uptime', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'FirmwareVersion', 'Firmware Version', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'SNMPCommunity', 'SNMP Community', 0);
INSERT INTO fac_CDUToolTip VALUES(NULL, 'NumOutlets', 'Used/Total Connections', 0);

--
-- Updating MediaTypes table
--

ALTER TABLE fac_MediaTypes ADD COLUMN ColorID INT(11) DEFAULT NULL;

--
-- Add ColorCoding Table
--

DROP TABLE IF EXISTS fac_ColorCoding;
CREATE TABLE fac_ColorCoding (
  ColorID INT(11) NOT NULL AUTO_INCREMENT,
  Name VARCHAR(20) NOT NULL,
  DefaultNote VARCHAR(40),
  PRIMARY KEY(ColorID),
  UNIQUE KEY Name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Modify fac_DevicePorts for consistency
--

ALTER TABLE fac_DevicePorts CHANGE CableColor ColorID INT(11);

--
-- Adding a field to track where numbering starts for a port - mainly for switch devices
--

ALTER TABLE fac_Device ADD COLUMN FirstPortNum INT(11) NOT NULL AFTER Ports;

--
-- Expanding cabinet sensory information
--

ALTER TABLE fac_Cabinet CHANGE SensorOID TempSensorOID VARCHAR(80) NOT NULL;
ALTER TABLE fac_Cabinet ADD COLUMN HumiditySensorOID VARCHAR(80) NOT NULL AFTER TempSensorOID;

ALTER TABLE fac_CabinetTemps ADD COLUMN Humidity INT(11) NOT NULL AFTER Temp;

--
-- Changes to improve the cabinet tree
-- Modify fac_datacenter to link to containers 
-- Modify fac_cabinet to link to Cabinet Rows
-- Adding Containers (fac_Container) and Cabinet Rows (fac_CabRow). Using Zones (fac_Zone)
--
ALTER TABLE fac_DataCenter ADD COLUMN ContainerID INT(11) NOT NULL DEFAULT '0' AFTER EntryLogging;
ALTER TABLE fac_DataCenter ADD COLUMN MapX INT(11) NOT NULL AFTER ContainerID;
ALTER TABLE fac_DataCenter ADD COLUMN MapY INT(11) NOT NULL AFTER MapX;

ALTER TABLE fac_Cabinet ADD COLUMN CabRowID INT(11) NOT DEFAULT '0' NULL AFTER ZoneID;

CREATE TABLE IF NOT EXISTS fac_CabRow (
  CabRowID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(120) NOT NULL,
  ZoneID int(11) NOT NULL,
  PRIMARY KEY (CabRowID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS fac_Container (
  ContainerID int(11) NOT NULL AUTO_INCREMENT,
  Name varchar(120) NOT NULL,
  ParentID int(11) NOT NULL DEFAULT '0',
  DrawingFileName varchar(255) DEFAULT NULL,
  MapX int(11) NOT NULL,
  MapY int(11) NOT NULL,
  PRIMARY KEY (ContainerID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add configuration item to set enforcing of media type matching on ports
--

INSERT INTO fac_Config VALUES ('MediaEnforce', 'Disabled', 'Enabled/Disabled', 'string', 'Disabled');

--
-- Table structure for table `fac_Ports`
--

CREATE TABLE fac_Ports (
  DeviceID int(11) NOT NULL,
  PortNumber int(11) NOT NULL,
  Label varchar(40) NOT NULL,
  MediaID int(11) NOT NULL DEFAULT '0',
  ColorID int(11) NOT NULL DEFAULT '0',
  PortNotes varchar(80) NOT NULL,
  ConnectedDeviceID int(11) DEFAULT NULL,
  ConnectedPort int(11) DEFAULT NULL,
  Notes varchar(80) NOT NULL,
  PRIMARY KEY (DeviceID,PortNumber),
  UNIQUE KEY LabeledPort (DeviceID,PortNumber,Label),
  UNIQUE KEY ConnectedDevice (ConnectedDeviceID,ConnectedPort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
