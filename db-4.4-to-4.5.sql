--
-- Add in configuration items for LDAP authentication and authorization
--

INSERT INTO fac_Config set Parameter="SAMLStrict", Value="enabled", UnitOfMeasure="string", ValType="Enabled/Disabled", DefaultVal="enabled";
INSERT INTO fac_Config set Parameter="SAMLDebug", Value="disabled", UnitOfMeasure="string", ValType="Enabled/Disabled", DefaultVal="disabled";
INSERT INTO fac_Config set Parameter="SAMLBaseURL", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="https://opendcim.local";
INSERT INTO fac_Config set Parameter="SAMLShowSuccessPage", Value="enabled", UnitOfMeasure="string", ValType="Enabled/Disabled", DefaultVal="enabled";
INSERT INTO fac_Config set Parameter="SAMLspentityId", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="https://opendcim.local";
INSERT INTO fac_Config set Parameter="SAMLspacsURL", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="https://opendcim.local/saml/acs.php";
INSERT INTO fac_Config set Parameter="SAMLspslsURL", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="https://opendcim.local";
INSERT INTO fac_Config set Parameter="SAMLspx509cert", Value="", UnitOfMeasure="string", ValType="string", DefaultVal="";
INSERT INTO fac_Config set Parameter="SAMLspprivateKey", Value="", UnitOfMeasure="string", ValType="string", DefaultVal="";
INSERT INTO fac_Config set Parameter="SAMLidpentityId", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="https://accounts.google.com/o/saml2?idpid=XXXXXXXXX";
INSERT INTO fac_Config set Parameter="SAMLidpssoURL", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="https://accounts.google.com/o/saml2/idp?idpid=XXXXXXXXX";
INSERT INTO fac_Config set Parameter="SAMLidpslsURL", Value="", UnitOfMeasure="URL", ValType="string", DefaultVal="";
INSERT INTO fac_Config set Parameter="SAMLidpcertFingerprint", Value="", UnitOfMeasure="string", ValType="string", DefaultVal="FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF:FF";
INSERT INTO fac_Config set Parameter="SAMLidpcertFingerprintAlgorithm", Value="", UnitOfMeasure="string", ValType="string", DefaultVal="sha1";
INSERT INTO fac_Config set Parameter="SAMLaccountPrefix", Value="", UnitOfMeasure="string", ValType="string", DefaultVal="DOMAIN\\";
INSERT INTO fac_Config set Parameter="SAMLaccountSuffix", Value="", UnitOfMeasure="string", ValType="string", DefaultVal="@example.org";

--
-- Change the Reservation field to Status
--

ALTER TABLE fac_Device ADD COLUMN Status varchar(20) NOT NULL DEFAULT 'Production' AFTER Reservation;
UPDATE fac_Device set Status='Reserved' WHERE Reservation=true;
ALTER TABLE fac_Device DROP COLUMN Reservation;

--
-- Change the Project Membership table
--

ALTER TABLE fac_ProjectMembership ADD COLUMN MemberType varchar(7) NOT NULL DEFAULT 'Device' AFTER ProjectID;
ALTER TABLE fac_ProjectMembership CHANGE COLUMN DeviceID MemberID int(11) NOT NULL;
ALTER TABLE fac_ProjectMembership DROP PRIMARY KEY, ADD PRIMARY KEY (`ProjectID`, `MemberType`, `MemberID`);

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="4.5" WHERE Parameter="Version";
