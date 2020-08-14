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

INSERT into fac_Config set Parameter='SAMLGroupAttribute', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='memberOf';
INSERT into fac_Config set Parameter='AttrFirstName', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='givenName';
INSERT into fac_Config set Parameter='AttrLastName', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='sn';
INSERT into fac_Config set Parameter='AttrEmail', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='mail';
INSERT into fac_Config set Parameter='AttrPhone1', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='AttrPhone2', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='AttrPhone3', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';

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

insert into fac_Config set Parameter='RackRequestsActions', Value='Disabled', UnitOfMeasure='string', ValType='string', DefaultVal='disabled';
insert into fac_Config set Parameter='AssignCabinetLabels', Value='Location', UnitOfMeasure='string', ValType='string', DefaultVal='Location';

--
-- Changes for streamlining the Saml configuration
--

DELETE from fac_Config where Parameter='SAMLStrict';
DELETE from fac_Config where Parameter='SAMLDebug';
DELETE from fac_Config where Parameter='SAMLidpcertFingerprint';
DELETE from fac_Config where Parameter='SAMLidpcertFingerprintAlgorithm';
DELETE from fac_Config where Parameter='SAMLspacsURL';
DELETE from fac_Config where Parameter='SAMLspslsURL';
ALTER TABLE fac_Config MODIFY Value text;
INSERT into fac_Config set Parameter='SAMLidpx509cert', Value='', UnitOfMeasure='string',ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='SAMLIdPMetadataURL', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='SAMLCertCountry', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='US';
INSERT into fac_Config set Parameter='SAMLCertProvince', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='Tennessee';
INSERT into fac_Config set Parameter='SAMLCertOrganization', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='openDCIM User';

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="20.01" WHERE Parameter="Version";
