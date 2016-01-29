--
-- Add the new table for monitoring jobs
--

CREATE TABLE fac_Jobs (
	SessionID varchar(80) NOT NULL,
	Percentage int(11) NOT NULL DEFAULT "0",
	Status varchar(255) NOT NULL,
	PRIMARY KEY(SessionID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Bump up the database version (uncomment below once released)
--
-- UPDATE fac_Config set Value="4.2" WHERE Parameter="Version";
