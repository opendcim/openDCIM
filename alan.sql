alter table fac_PowerPanel add column(managed Boolean, IPAddress varchar(20), PanelOID varchar(100), SNMPCommunity varchar(50)) ;
alter table fac_PDUStats add column (Amps float(3,2) ) ;
alter table fac_PanelStats add primary key(PanelID,PanelPole);
alter table fac_user add column (ReadBilling tinyint(1) default 0);

create table fac_DeletedDevice like fac_Device ;
alter table fac_DeletedDevice MODIFY DeviceID int not null ;
alter table fac_DeletedDevice drop primary key ;
alter table fac_DeletedDevice add column DeletedDate date ;



