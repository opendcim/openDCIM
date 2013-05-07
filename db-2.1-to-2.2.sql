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

