--
-- Changes to openDCIM database for migrating from 1.1 to 1.2
--

--
-- First and foremost, we are now setting a version number in the database
--

insert into fac_Config values( 'Version','1.2' );

