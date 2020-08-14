--
-- Set correct default value for cabinet label preference to be what we had prior to v20.01 
--

UPDATE fac_Config SET DefaultVal = 'Location' WHERE fac_Config.Parameter = 'AssignCabinetLabels';

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="20.02" WHERE Parameter="Version";
