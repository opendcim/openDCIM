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
INSERT INTO fac_Config VALUES ( 'FreeSpaceColor', '#FFFFFF', 'HexColor', 'string', '#FFFFFF');

--
-- Adding a default panel voltage for new panels being created.
--

INSERT INTO fac_Config VALUES ('DefaultPanelVoltage', '208', 'Volts', 'int', '208' );

--
-- Extend the departments table to add a new color field
--

ALTER TABLE fac_Department ADD DeptColor VARCHAR( 7 ) NOT NULL DEFAULT '#FFFFFF';

--
-- Add field in fac_Cabinet for the Key (Lock) Information
--

ALTER TABLE fac_Cabinet ADD Keylock VARCHAR( 30 ) NOT NULL AFTER Model;

--
-- Add field in fac_Device for the Warranty Expiration and Warranty Holder
--

ALTER TABLE fac_Device ADD WarrantyCo VARCHAR(80) NOT NULL AFTER InstallDate;
ALTER TABLE fac_Device ADD WarrantyExpire DATE NULL AFTER WarrantyCo;
