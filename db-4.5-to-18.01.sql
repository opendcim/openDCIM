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
-- Create a table for things that we want to cache, such as the Navigation Menu
--

CREATE TABLE fac_DataCache (
	ItemType varchar(80) not null,
	Value mediumtext not null, primary key (ItemType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Create a table specifically for device image caching, optimize later 
--

DROP TABLE IF EXISTS fac_DeviceCache;
CREATE TABLE fac_DeviceCache (
  DeviceID int(11) NOT NULL,
  Front mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  Rear mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY DeviceID (DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="18.01" WHERE Parameter="Version";
