--
-- Add restraints to the Power Connections to prevent accidental duplicates
--

ALTER TABLE fac_PowerConnection DROP INDEX PDUID;
ALTER TABLE fac_PowerConnection ADD UNIQUE KEY (PDUID,PDUPosition);
ALTER TABLE fac_PowerConnection ADD UNIQUE KEY (DeviceID,DeviceConnNumber);

--
-- Add constraints to the Switch Connections to prevent accidental duplicates
--

ALTER TABLE fac_SwitchConnection ADD UNIQUE KEY (EndpointDeviceID,EndpointPort);
ALTER TABLE fac_SwitchConnection drop index SwitchDeviceID;
ALTER TABLE fac_SwitchConnection ADD UNIQUE KEY (SwitchDeviceID,SwitchPortNumber);
