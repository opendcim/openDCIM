--
-- Normalize DeviceTemplate -> Device db
--

ALTER TABLE fac_Ports DROP PortNotes ;
ALTER TABLE fac_TemplatePorts CHANGE PortNotes Notes VARCHAR(80) ;

--
-- Update Rackrequest form to use new hypervisor column to match device
-- 

ALTER TABLE fac_RackRequest CHANGE ESX Hypervisor varchar(40);
UPDATE fac_RackRequest set Hypervisor='ESX' where Hypervisor='1';
UPDATE fac_RackRequest set Hypervisor='None' where Hypervisor='0';

--
-- Remove old references to the scripted version of the repository sync
--

ALTER TABLE fac_DeviceTemplate DROP ShareToRepo;
ALTER TABLE fac_DeviceTemplate DROP KeepLocal;
DELETE FROM fac_Config WHERE Parameter='ShareToRepo';
DELETE FROM fac_Config WHERE Parameter='KeepLocal';

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="18.01" WHERE Parameter="Version";
