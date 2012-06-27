--
-- Changes to openDCIM database for migrating from 1.2 to 1.3
--

--
-- Bump version number up
--

UPDATE fac_Config SET Value = '1.3' WHERE fac_Config.Parameter = 'Version';

--
-- Adding a color option to reserved devices. Default will be white like any other device.
--

INSERT INTO fac_Config VALUES ( 'ReservedColor', '#00FFFF', 'HexColor', 'string', '#FFFFFF');

--
-- Extend the departments table to add a new color field
--

ALTER TABLE fac_Department ADD DeptColor VARCHAR( 7 ) NOT NULL DEFAULT '#FFFFFF';
