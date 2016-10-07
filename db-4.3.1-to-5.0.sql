--
-- Change the data structure for how we determine hypervisor polling
--

ALTER TABLE fac_Device CHANGE ESX Hypervisor varchar(40);

UPDATE fac_Device set Hypervisor='ESX' where Hypervisor='1';
UPDATE fac_Device set Hypervisor='None' where Hypervisor='0';

--
-- Now add a unique index on the VMInventory table
-- constraining the combination of vmID+vmName
--

CREATE UNIQUE INDEX VMList on fac_VMInventory (vmID,vmName);

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="5.0" WHERE Parameter="Version";
