--
-- Normalize DeviceTemplate -> Device db
--

ALTER TABLE fac_Ports DROP PortNotes ;
ALTER TABLE fac_TemplatePorts CHANGE PortNotes Notes VARCHAR(80) ;

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="4.5.1" WHERE Parameter="Version";
