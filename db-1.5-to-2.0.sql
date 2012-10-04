
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.0' WHERE fac_Config.Parameter = 'Version';

--
-- New configuration parameters
--

--
-- This one may or may not have been added manually by people who installed 1.5.2, so use the ON DUPLICATE CLAUSE
--

INSERT INTO `fac_Config` (`Parameter`, `Value`, `UnitOfMeasure`, `ValType`, `DefaultVal`) VALUES
('FreeSpaceColor', '#FFFFFF', 'HexColor', 'string', '#FFFFFF') ON DUPLICATE KEY UPDATE ValType='string';
