--
-- Change the DeviceType enumeration from having 'Routing Chassis' to just plain 'Chassis'
--

ALTER TABLE fac_RackRequest MODIFY COLUMN DeviceType enum('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure','Chassis');
UPDATE fac_RackRequest SET DeviceType='Chassis' WHERE DeviceType = 'Routing Chassis';
ALTER TABLE fac_RackRequest MODIFY COLUMN DeviceType enum('Server','Appliance','Storage Array','Switch','Patch Panel','Physical Infrastructure','Chassis');

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

--
-- Expansion to allow for blade chassis to track front and back slots
--

ALTER TABLE fac_Device ADD RearChassisSlots SMALLINT(6) NOT NULL AFTER ChassisSlots;
