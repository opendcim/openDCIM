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
-- Add user right of BulkOperations for both table based and LDAP based rights
--

ALTER TABLE fac_People ADD COLUMN BulkOperations tinyint(1) NOT NULL DEFAULT 0 AFTER RackAdmin;

INSERT INTO fac_Config VALUES ( 'LDAPBulkOperations', 'cn=BulkOperations,cn=openDCIM,ou=groups,dc=opendcim,dc=org', 'DN', 'string', 'cn=BulkOperations,cn=openDCIM,ou=groups,dc=opendcim,dc=org');

--
-- More LDAP configuration options so that it will play nice with AD
--

INSERT INTO fac_Config VALUES ( 'LDAPBaseSearch', '(&(objectClass=posixGroup)(memberUid=%userid%))', 'DN', 'string', '(&(objectClass=posixGroup)(memberUid=%userid%))');
INSERT INTO fac_Config VALUES ( 'LDAPUserSearch', '(|(uid=%userid%))', 'DN', 'string', '(|(uid=%userid%))');

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="4.3" WHERE Parameter="Version";
