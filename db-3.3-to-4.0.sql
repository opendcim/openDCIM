--
-- Move entries from fac_CabinetAudit to fac_GenericLog
--

INSERT INTO fac_GenericLog (UserID, Class, ObjectID, Action, Time) SELECT fac_CabinetAudit.UserID as UserID, "CabinetAudit" as Class, fac_CabinetAudit.CabinetID as ObjectID, "CertifyAudit" as Action, fac_CabinetAudit.AuditStamp as Time FROM fac_CabinetAudit;

--
-- Not sure if you want to do this yet
-- The answer is NO.  Wait until next point release after 4.0
--

-- DROP TABLE IF EXISTS fac_CabinetAudit;

--
-- Time to merge Contacts and Users - create a new fac_People table and delete the two old ones in the next release
--

CREATE TABLE fac_People (
  UserID varchar(80) NOT NULL,
  LastName varchar(40) NOT NULL,
  FirstName varchar(40) NOT NULL,
  Phone1 varchar(20) NOT NULL,
  Phone2 varchar(20) NOT NULL,
  Phone3 varchar(20) NOT NULL,
  Email varchar(80) NOT NULL,
  AdminOwnDevices tinyint(1) NOT NULL,
  ReadAccess tinyint(1) NOT NULL,
  WriteAccess tinyint(1) NOT NULL,
  DeleteAccess tinyint(1) NOT NULL,
  ContactAdmin tinyint(1) NOT NULL,
  RackRequest tinyint(1) NOT NULL,
  RackAdmin tinyint(1) NOT NULL,
  SiteAdmin tinyint(1) NOT NULL,
  Disabled tinyint(1) NOT NULL,
  PRIMARY KEY(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

---
--- Table structure for fac_DeviceCustomAttribute
---
DROP TABLE IF EXISTS fac_DeviceCustomAttribute;
CREATE TABLE fac_DeviceCustomAttribute(
  AttributeID int(11) NOT NULL AUTO_INCREMENT,
  Label varchar(80) NOT NULL,
  AttributeType enum('string', 'number', 'integer', 'date', 'phone', 'email', 'ipv4', 'url', 'checkbox') NOT NULL DEFAULT 'string',
  Required tinyint(1) NOT NULL DEFAULT 0,
  AllDevices tinyint(1) NOT NULL DEFAULT 0,
  DefaultValue varchar(65000),
  PRIMARY KEY (AttributeID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

---
--- Table structure for fac_DeviceTemplateCustomValue
---
DROP TABLE IF EXISTS fac_DeviceTemplateCustomValue;
CREATE TABLE fac_DeviceTemplateCustomValue (
  TemplateID int(11) NOT NULL,
  AttributeID int(11) NOT NULL,
  Required tinyint(1) NOT NULL DEFAULT 0,
  Value varchar(65000),
  PRIMARY KEY (TemplateID, AttributeID)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;

---
--- Table structure for fac_DeviceCustomValue
---
DROP TABLE IF EXISTS fac_DeviceCustomValue;
CREATE TABLE fac_DeviceCustomValue (
  DeviceID int(11) NOT NULL,
  AttributeID int(11) NOT NULL,
  Value varchar(65000),
  PRIMARY KEY (DeviceID, AttributeID)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add new device type for CDUs
--
ALTER TABLE fac_Device CHANGE DeviceType DeviceType ENUM( 'Server', 'Appliance', 'Storage Array', 'Switch', 'Patch Panel', 'Physical Infrastructure', 'Chassis', 'CDU' ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

--
-- Create new table for power ports
--
CREATE TABLE fac_PowerPorts (
	DeviceID int(11) NOT NULL,
	PortNumber int(11) NOT NULL,
	Label varchar(40) NOT NULL,
	ConnectedDeviceID int(11) DEFAULT NULL,
	ConnectedPort int(11) DEFAULT NULL,
	Notes varchar(80) NOT NULL,
	PRIMARY KEY (DeviceID,PortNumber),
	UNIQUE KEY LabeledPort (DeviceID,PortNumber,Label),
	UNIQUE KEY ConnectedDevice (ConnectedDeviceID,ConnectedPort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
