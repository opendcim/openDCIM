--
-- Add new fields for Cabinet Order support
--

ALTER TABLE fac_Cabinet ADD COLUMN U1Position VARCHAR(7) NOT NULL DEFAULT "Default" AFTER Notes;
ALTER TABLE fac_DataCenter ADD COLUMN U1Position VARCHAR(7) NOT NULL DEFAULT "Default" AFTER MapY;
INSERT INTO fac_Config SET Parameter="U1Position", Value="Bottom", UnitOfMeasure="Top/Bottom", ValType="string", DefaultVal="Bottom";

--
-- Add new configuration values for Rack Cooling Index metric (RCI).
--

INSERT INTO fac_Config SET Parameter='RCIHigh', Value='80', UnitOfMeasure='degrees', ValType='float', DefaultVal='80';
INSERT INTO fac_Config SET Parameter='RCILow', Value='65', UnitOfMeasure='degrees', ValType='float', DefaultVal='65';

--
-- Make UserID unique in the fac_People table
--

ALTER TABLE fac_People DROP INDEX UserID;
ALTER TABLE fac_People ADD UNIQUE (UserID);

--
-- Bump up the database version
--
-- UPDATE fac_Config set Value='4.1' WHERE Parameter='Version';
