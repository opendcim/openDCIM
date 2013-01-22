--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.0.1' WHERE fac_Config.Parameter = 'Version';

--
-- Left out a required column in the last version
--

ALTER TABLE fac_PowerDistribution ADD COLUMN InputAmperage int(11) NOT NULL AFTER PanelPole;