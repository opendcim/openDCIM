--
-- Set correct default value for cabinet label preference to be what we had prior to v20.01 
--

UPDATE fac_Config SET DefaultVal = 'Location' WHERE fac_Config.Parameter = 'AssignCabinetLabels';


--- Add preference for resolution of device ip addresses

INSERT into fac_Config set Parameter='ResolveDeviceIp', Value='Disabled', UnitOfMeasure='string',ValType='string', DefaultVal='disabled';
--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="20.02" WHERE Parameter="Version";
