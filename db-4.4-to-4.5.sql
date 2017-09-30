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
-- New tables for the disposition process to replace the old salvage stuff
--

CREATE TABLE fac_Disposition (
DispositionID INT(11) NOT NULL AUTO_INCREMENT,
Name VARCHAR(80) NOT NULL,
Description VARCHAR(255) NOT NULL,
ReferenceNumber VARCHAR(80) NOT NULL,
Status VARCHAR(10) NOT NULL DEFAULT 'Active',
PRIMARY KEY (DispositionID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE fac_DispositionMembership (
DispositionID INT(11) NOT NULL,
DeviceID INT(11) NOT NULL,
DispositionDate DATE NOT NULL,
DisposedBy VARCHAR(80) NOT NULL,
PRIMARY KEY (DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Seed the db with two default dispositions
--

INSERT INTO fac_Disposition VALUES ( 1, 'Legacy Salvage', 'Items marked as disposed in openDCIM prior to the version 4.5 upgrade.', '', 'Active');
INSERT INTO fac_Disposition VALUES ( 2, 'Returned to Customer', 'Item has been removed from the data center and returned to the customer.', '', 'Active');

--
-- Add some new fields to the Power Panel table
--

ALTER TABLE fac_PowerPanel ADD COLUMN MapDataCenterID INT(11) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN MapX1 INT(11) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN MapX2 INT(11) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN MapY1 INT(11) NOT NULL;
ALTER TABLE fac_PowerPanel ADD COLUMN MapY2 INT(11) NOT NULL;

--
-- Change the PowerDistribution table to allow for alphanumeric breaker identifiers
--

ALTER TABLE fac_PowerDistribution CHANGE COLUMN PanelPole PanelPole varchar(20) NOT NULL;
ALTER TABLE fac_PowerDistribution CHANGE COLUMN PanelPole2 PanelPole2 varchar(20) NOT NULL;

--
-- Add a table of Status Field values to allow
--

CREATE TABLE fac_DeviceStatus (
	StatusID INT(11) NOT NULL AUTO_INCREMENT,
	Status varchar(40) NOT NULL,
	ColorCode VARCHAR(7) NOT NULL,
	PRIMARY KEY(StatusID)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Reserved', '#00FFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Test', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Development', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('QA', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Production', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Spare', '#FFFFFF');
INSERT INTO fac_DeviceStatus (Status, ColorCode) VALUES ('Disposed', '#FFFFFF');
DELETE FROM fac_Config where Parameter='ReservedColor';

INSERT INTO fac_CabinetToolTip (SortOrder, Field, Label, Enabled) VALUES (NULL, 'Status', 'Device Status', 0);
DELETE from fac_CabinetToolTip WHERE Field='Reservation';

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="4.5" WHERE Parameter="Version";
