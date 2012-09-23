
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '1.5' WHERE fac_Config.Parameter = 'Version';

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

--
-- Added an attribute for users to disable accounts
--

ALTER TABLE fac_User ADD COLUMN Disabled tinyint(1) NOT NULL;

--
-- Add fields for tracking temperature in the cabinets via SNMP sensors
--

ALTER TABLE fac_Cabinet ADD COLUMN SensorIPAddress varchar(20) NOT NULL AFTER InstallationDate;
ALTER TABLE fac_Cabinet ADD COLUMN SensorCommunity varchar(40) NOT NULL AFTER SensorIPAddress;
ALTER TABLE fac_Cabinet ADD COLUMN SensorOID varchar(80) NOT NULL AFTER SensorCommunity;

--
-- Add a table for tracking the cabinet temperatures
--

DROP TABLE IF EXISTS fac_CabinetTemps;
CREATE TABLE fac_CabinetTemps (
  CabinetID int(11) NOT NULL,
  LastRead datetime NOT NULL,
  Temp int(11) NOT NULL,
  PRIMARY KEY (CabinetID)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Correction to the VMInventory tables
--
ALTER TABLE `fac_VMInventory` ADD PRIMARY KEY ( `VMIndex` );
ALTER TABLE `fac_VMInventory` ADD KEY `ValidDevice` (`DeviceID`);
ALTER TABLE `fac_VMInventory` CHANGE `VMIndex` `VMIndex` INT( 11 ) NOT NULL AUTO_INCREMENT;

--
-- Add a configuration item for how long a virtual server should be missing before it is removed
--
INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES
('VMExpirationTime', '7', 'Days', 'int', '7');

