--
-- Add new fields for Cabinet Order support
--

-- ALTER TABLE fac_Cabinet ADD COLUMN U1Position VARCHAR(7) NOT NULL DEFAULT "Default" AFTER Notes;
-- ALTER TABLE fac_DataCenter ADD COLUMN U1Position VARCHAR(7) NOT NULL DEFAULT "Default" AFTER MapY;
-- INSERT INTO fac_Config SET Parameter="U1Position", Value="Bottom", UnitOfMeasure="Top/Bottom", ValType="string", DefaultVal="Bottom";

--
-- Compatability updates below
--

ALTER TABLE fac_SensorTemplate CHANGE mUnits mUnits VARCHAR( 7 ) NOT NULL DEFAULT "english";
ALTER TABLE fac_SensorTemplate DROP COLUMN SNMPVersion;

--
-- Not sure how we missed this
--
ALTER TABLE fac_CDUTemplate ADD COLUMN SNMPVersion varchar(2) NOT NULL DEFAULT "2c" AFTER ATS;

--
-- Moving entries from the rack audit to the generic log was off by a column
--
UPDATE fac_GenericLog SET Action="CertifyAudit", Property="" WHERE Property="CertifyAudit";

--
-- Bump up the database version
--
-- UPDATE fac_Config set Value='4.1' WHERE Parameter='Version';
