--
-- Add the new table for monitoring jobs
--

CREATE TABLE IF NOT EXISTS fac_Jobs (
	SessionID varchar(80) NOT NULL,
	Percentage int(11) NOT NULL DEFAULT "0",
	Status varchar(255) NOT NULL,
	PRIMARY KEY(SessionID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Make custom attributes unique, need to add a test for this
-- 
ALTER TABLE fac_DeviceCustomAttribute ADD UNIQUE (Label);

--
-- Add in new parameter to control whether or not to filter the cabinet listing (Disable for Performance Boost on large installs)
--

INSERT INTO fac_Config SET Parameter="PatchPanelsOnly", Value="enabled", UnitOfMeasure="Enabled/Disabled", ValType="string", DefaultVal="enabled";

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="4.1.1" WHERE Parameter="Version";
