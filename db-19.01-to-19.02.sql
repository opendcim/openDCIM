--
-- Backwards compatibility for change to allow logo.png to be anywhere
--

UPDATE fac_Config set Value=concat("images/",Value) WHERE Parameter="PDFLogoFile";

--
-- New configuration parameter for whether or not to enable LDAP debugging
--

INSERT into fac_Config set Parameter='LDAP_Debug_Password', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='disabled';

--
-- New parameters for mapping SAML/LDAP entries to fields in the database
--

INSERT into fac_Config set Parameter='LDAPFirstName', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='LDAPLastName', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='LDAPEmail', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='LDAPPhone1', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='LDAPPhone2', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='LDAPPhone3', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';

--
-- New fields tracked in the CDUDeviceTemplate
--

alter table fac_CDUTemplate add column OutletNameOID varchar(80) NOT NULL after VersionOID;
alter table fac_CDUTemplate add column OutletDescOID varchar(80) NOT NULL after OutletNameOID;
alter table fac_CDUTemplate add column OutletCountOID varchar(80) NOT NULL after OutletDescOID;
alter table fac_CDUTemplate add column OutletStatusOID varchar(80) NOT NULL after OutletCountOID;
alter table fac_CDUTemplate add column OutletStatusOn varchar(80) NOT NULL after OutletStatusOID;

--
-- New parameter for Changing cabinet labels from cabinet name to user preference based label
--

insert into fac_Config set Parameter='AssignCabinetLabels', Value='OwnerName', UnitOfMeasure='string', ValType='string', DefaultVal='OwnerName';

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="19.02" WHERE Parameter="Version";
