--
-- Changes to openDCIM database for migrating from 1.1 to 1.2
--

--
-- First and foremost, we are now setting a version number in the database
--

insert into fac_Config values( 'Version','1.2' );


--
-- New fields tracked in the DeviceTemplate
--

alter table fac_DeviceTemplate add column DeviceType enum('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure') not null default 'Server';

alter table fac_DeviceTemplate add column PSCount int(11) not null;

alter table fac_DeviceTemplate add column NumPorts int(11) not null;


