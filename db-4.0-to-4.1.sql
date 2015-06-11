--
-- Add new fields for Cabinet Order support
--

ALTER TABLE fac_Cabinet ADD COLUMN U1Position VARCHAR(7) NOT NULL DEFAULT 'Default' AFTER Notes;

ALTER TABLE fac_DataCenter ADD COLUMN U1Position VARCHAR(7) NOT NULL DEFAULT 'Default' AFTER MapY;

INSERT INTO fac_Config set Parameter='U1Position', Value='Bottom', UnitOfMeasure='Top/Bottom', ValType='string', DefaultVal='Bottom';

--
-- Bump up the database version
--
-- UPDATE fac_Config set Value='4.1' WHERE Parameter='Version';
