--
-- Changes to openDCIM database for migrating from 1.0 to 1.1
--

--
-- Dell changed their web page and broke the warranty information retrieval script, so the
-- use of this parameter has been deprecated.
--

delete from fac_Config where Parameter='DELL_ID';

--
-- You can't really make dynamic CSS easily, so users can stick with the openDCIM logo
-- for the web page, but reports can still have a custom logo.

delete from fac_Config where Parameter='CSSLogoFile';

--
-- Since fpdf was upgraded to a newer version, it supports png.
--

update fac_Config set DefaultVal="logo.png" where Parameter="PDFLogoFile";

--
-- Added SMTPUser and SMTPPassword so that authentication can be used for sending email
--

insert into fac_Config values ('SMTPUser','','Username','string',''),
	('SMTPPassword','','Password','string','');
	

