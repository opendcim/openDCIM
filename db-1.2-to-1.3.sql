--
-- Changes to openDCIM database for migrating from 1.2 to 1.3
--

--
-- Adding a color option to reserved devices. Default will be white like any other device.
--

INSERT INTO fac_Config VALUES ( 'ReservedColor', '#00FFFF', 'HexColor', 'string', '#FFFFFF');
