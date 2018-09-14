--
-- Add in new configuration items for the path
--

INSERT INTO fac_Config set Parameter='drawingpath', Value='drawings/', UnitOfMeasure='string', ValType='string', DefaultVal='drawings/';
INSERT INTO fac_Config set Parameter='picturepath', Value='pictures/', UnitOfMeasure='string', ValType='string', DefaultVal='pictures/';

--
-- Add RequestedAction field to the Rack Request From page
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("RackRequestsActions", "disabled", "Enabled/Disabled", "string", "disabled");

ALTER TABLE fac_RackRequest ADD COLUMN RequestedAction VARCHAR(10) NULL AFTER SpecialInstructions;

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="18.02" WHERE Parameter="Version";

