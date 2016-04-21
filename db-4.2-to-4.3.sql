--
-- Add in table for Project Tracking
--

DROP TABLE IF EXISTS fac_Projects;
CREATE TABLE fac_Projects (
  ProjectID int(11) NOT NULL AUTO_INCREMENT,
  ProjectName varchar(80) NOT NULL,
  ProjectSponsor varchar(80) NOT NULL,
  ProjectStartDate date NULL,
  ProjectExpirationDate date NULL,
  ProjectActualEndDate date NULL,
  PRIMARY KEY (ProjectID)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS fac_ProjectMembership;
CREATE TABLE fac_ProjectMembership (
	ProjectID int(11) NOT NULL,
	DeviceID int(11) NOT NULL,
	PRIMARY KEY (ProjectID,DeviceID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="4.3" WHERE Parameter="Version";
