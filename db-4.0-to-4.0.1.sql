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
-- fac_Config parameters
-- 
INSERT INTO fac_Config set Parameter='SNMPVersion', Value='2c', UnitOfMeasure='Version', ValType='string', DefaultVal='2c';

--
-- Bump up the database version
--
UPDATE fac_Config set Value='4.0.1' WHERE Parameter='Version';
