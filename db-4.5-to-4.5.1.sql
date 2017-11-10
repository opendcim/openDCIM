--
-- Normalize DeviceTemplate -> Device db
--

ALTER TABLE fac_Ports DROP PortNotes ;
ALTER TABLE fac_TemplatePorts CHANGE PortNotes Notes VARCHAR(80) ;

--
-- Update Rackrequest form to use new hypervisor columr to match device
-- 

ALTER TABLE fac_RackRequest CHANGE ESX Hypervisor varchar(40);
UPDATE fac_RackRequest set Hypervisor='ESX' where Hypervisor='1';
UPDATE fac_RackRequest set Hypervisor='None' where Hypervisor='0';
--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="4.5.1" WHERE Parameter="Version";
