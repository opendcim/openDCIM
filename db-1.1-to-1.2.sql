--
-- Changes to openDCIM database for migrating from 1.1 to 1.2
--

--
-- First and foremost, we are now setting a version number in the database
--

insert into fac_Config values( 'Version','1.2', '', '', '' );


--
-- New fields tracked in the DeviceTemplate
--

alter table fac_DeviceTemplate add column DeviceType enum('Server','Appliance','Storage Array','Switch','Routing Chassis','Patch Panel','Physical Infrastructure') not null default 'Server';

alter table fac_DeviceTemplate add column PSCount int(11) not null;

alter table fac_DeviceTemplate add column NumPorts int(11) not null;


--
-- Major change - automate the transition from InputVoltage to BreakerSize in the fac_PowerDistribution table
--

alter table fac_PowerDistribution add column BreakerSize int(11) not null after PanelID;

update fac_PowerDistribution set BreakerSize=1 where InputVoltage='110VAC';

update fac_PowerDistribution set BreakerSize=2 where InputVoltage='208VAC 2-Pole';

update fac_PowerDistribution set BreakerSize=3 where InputVoltage='208VAC 3-Pole';

alter table fac_PowerDistribution drop column InputVoltage;

alter table fac_PowerPanel add column PanelVoltage int(11) not null after MainBreakerSize;


