--
-- Changes to openDCIM database for migrating from 1.0 to 1.1
--

--
-- Dell changed their web page and broke the warranty information retrieval script, so the
-- use of this parameter has been deprecated.
--

delete from fac_Config where Parameter='DELL_ID';

--
-- Added SMTPUser and SMTPPassword so that authentication can be used for sending email
--

insert into fac_Config values ('SMTPUser','','Username','string',''),
	('SMTPPassword','','Password','string','');
	

