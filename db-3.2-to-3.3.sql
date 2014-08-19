--
-- Allow for alphanumeric names of power connections
--
ALTER TABLE fac_PowerConnection CHANGE PDUPosition PDUPosition VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

--
-- Add Config item for the Work Order Builder and Rack Requests toggles
--
INSERT INTO fac_Config set Parameter='WorkOrderBuilder', Value='disabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='disabled';
INSERT INTO fac_Config set Parameter='RackRequests', Value='enabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='enabled';

--
-- Add mUnits for the temperature sensors
--
ALTER TABLE fac_SensorTemplate ADD COLUMN mUnits ENUM( 'english', 'metric' ) NOT NULL DEFAULT 'english';

--
-- Add dot to fac_Config to handle network map reporting
--
INSERT INTO fac_Config VALUES ('dot', '/usr/bin/dot', 'path', 'string', '/usr/bin/dot');

--
-- Help optimize performance by adding in some indices to existing tables
--
ALTER TABLE fac_PowerDistribution ADD INDEX CabinetID(CabinetID);
ALTER TABLE fac_PowerDistribution ADD INDEX PanelID(PanelID);
ALTER TABLE fac_Device ADD INDEX AssetTag(AssetTag);
ALTER TABLE fac_Device ADD INDEX Cabinet(Cabinet);
ALTER TABLE fac_Device ADD INDEX TemplateID(TemplateID);

--
-- Clean up - we transitioned off of this table 2 releases ago
--
DROP TABLE IF EXISTS fac_DevicePorts;
DROP TABLE IF EXISTS fac_PatchConnection;
DROP TABLE IF EXISTS fac_SwitchConnection;
--
-- Bump up the database version
--
UPDATE fac_Config set Value='3.3' WHERE Parameter='Version';

