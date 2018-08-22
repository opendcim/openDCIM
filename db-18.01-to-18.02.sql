--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="18.02" WHERE Parameter="Version";


--
-- Add RequestedAction field to the Rack Request From page
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("RackRequestsActions", "disabled", "Enabled/Disabled", "string", "disabled");

ALTER TABLE fac_RackRequest ADD COLUMN RequestedAction VARCHAR(10) NULL AFTER SpecialInstructions;
