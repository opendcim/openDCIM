--
-- Add in new configuration item for log retention
--

INSERT INTO fac_Config set Parameter='logretention', Value='90', UnitOfMeasure='days', ValType='integer', DefaultVal='90';


--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="19.01" WHERE Parameter="Version";

