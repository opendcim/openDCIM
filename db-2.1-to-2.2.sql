
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.2' WHERE fac_Config.Parameter = 'Version';

--
-- Extend power multiplier enum
-- An add/copy/drop has to be done to make sure that the enum 
-- values are migrated "by value" instead of "by order"
--

ALTER TABLE fac_CDUTemplate ADD COLUMN new_mult enum('0.1','1','10','100');
UPDATE fac_CDUTemplate SET new_mult=Multiplier;
ALTER TABLE fac_CDUTemplate DROP Multiplier;
ALTER TABLE fac_CDUTemplate CHANGE new_mult Multiplier enum('0.1','1','10','100');
