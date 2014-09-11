--
-- Move entries from fac_CabinetAudit to fac_GenericLog
--

INSERT INTO fac_GenericLog (UserID, Class, ObjectID, Action, Time) SELECT fac_CabinetAudit.UserID as UserID, "CabinetAudit" as Class, fac_CabinetAudit.CabinetID as ObjectID, "CertifyAudit" as Action, fac_CabinetAudit.AuditStamp as Time FROM fac_CabinetAudit;

--
-- Not sure if you want to do this yet
-- The answer is NO.  Wait until next point release after 4.0
--

-- DROP TABLE IF EXISTS fac_CabinetAudit;

--
-- Time to merge Contacts and Users - create a new fac_People table and delete the two old ones in the next release
--

DROP TABLE IF EXISTS fac_People;
CREATE TABLE fac_People (
  PersonID int(11) NOT NULL AUTO_INCREMENT,
  UserID varchar(255) NOT NULL,
  LastName varchar(40) NOT NULL,
  FirstName varchar(40) NOT NULL,
  Phone1 varchar(20) NOT NULL,
  Phone2 varchar(20) NOT NULL,
  Phone3 varchar(20) NOT NULL,
  Email varchar(80) NOT NULL,
  AdminOwnDevices tinyint(1) NOT NULL,
  ReadAccess tinyint(1) NOT NULL,
  WriteAccess tinyint(1) NOT NULL,
  DeleteAccess tinyint(1) NOT NULL,
  ContactAdmin tinyint(1) NOT NULL,
  RackRequest tinyint(1) NOT NULL,
  RackAdmin tinyint(1) NOT NULL,
  SiteAdmin tinyint(1) NOT NULL,
  Disabled tinyint(1) NOT NULL,
  PRIMARY KEY(PersonID),
  KEY(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;