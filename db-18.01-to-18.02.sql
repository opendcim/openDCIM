--
-- Add in new configuration items for the path
--

INSERT INTO fac_Config set Parameter='drawingpath', Value='drawings/', UnitOfMeasure='string', ValType='string', DefaultVal='drawings/';
INSERT INTO fac_Config set Parameter='picturepath', Value='pictures/', UnitOfMeasure='string', ValType='string', DefaultVal='pictures/';

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="18.02" WHERE Parameter="Version";
