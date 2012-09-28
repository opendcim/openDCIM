
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.0' WHERE fac_Config.Parameter = 'Version';

--
-- New configuration parameters
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES
('FreeSpaceColor', '#FFFFFF', 'HexColor', 'string', '#FFFFFF');