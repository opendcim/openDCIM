--
-- Backwards compatibility for change to allow logo.png to be anywhere
--

UPDATE fac_Config set Value=concat("images/",Value) WHERE Parameter="PDFLogoFile";

--
-- New configuration parameter for whether or not to enable LDAP debugging
--

INSERT into fac_Config set Parameter='LDAP_Debug_Password', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='disabled';

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="19.02" WHERE Parameter="Version";