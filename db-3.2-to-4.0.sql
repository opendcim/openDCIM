--
-- Allow for alphanumeric names of power connections
--
ALTER TABLE fac_PowerConnection CHANGE PDUPosition PDUPosition VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

--
-- Add Config item for the Work Order Builder
--
INSERT INTO fac_Config set Parameter='WorkOrderBuilder', Value='disabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='disabled';

--
-- Add mUnits for the temperature sensors
--
ALTER TABLE fac_SensorTemplate ADD COLUMN mUnits ENUM( 'english', 'metric' ) NOT NULL DEFAULT 'english';

---
--- Add dot to fac_Config to handle network map reporting
---
INSERT INTO fac_Config VALUES ('dot', '/usr/bin/dot', 'path', 'string', '/usr/bin/dot');
