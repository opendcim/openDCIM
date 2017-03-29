--
-- Change the data structure for how we determine hypervisor polling
--

ALTER TABLE fac_Device CHANGE ESX Hypervisor varchar(40);

UPDATE fac_Device set Hypervisor='ESX' where Hypervisor='1';
UPDATE fac_Device set Hypervisor='None' where Hypervisor='0';

ALTER TABLE fac_Device ADD COLUMN APIUsername varchar(80) AFTER Hypervisor;
ALTER TABLE fac_Device ADD COLUMN APIPassword varchar(80) AFTER APIUsername;
ALTER TABLE fac_Device ADD COLUMN APIPort smallint(4) UNSIGNED AFTER APIPassword;
ALTER TABLE fac_Device ADD COLUMN ProxMoxRealm varchar(80) AFTER APIPort;

--
-- Now add a unique index on the VMInventory table
-- constraining the combination of vmID+vmName
--

CREATE UNIQUE INDEX VMList on fac_VMInventory (vmID,vmName);

--
-- Normalize the custom attribute labels
--

UPDATE fac_DeviceCustomAttribute SET Label = REPLACE (Label,' ','_');
--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="5.0" WHERE Parameter="Version";

--
-- Allow for longer template image names
--

ALTER TABLE fac_DeviceTemplate MODIFY COLUMN FrontPictureFile varchar(255) NOT NULL;
ALTER TABLE fac_DeviceTemplate MODIFY COLUMN  RearPictureFile varchar(255) NOT NULL;

