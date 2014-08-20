--
-- Move entries from fac_CabinetAudit to fac_GenericLog
--

INSERT INTO fac_GenericLog (UserID, Class, ObjectID, Action, Time) SELECT fac_CabinetAudit.UserID as UserID, "CabinetAudit" as Class, fac_CabinetAudit.CabinetID as ObjectID, "CertifyAudit" as Action, fac_CabinetAudit.AuditStamp as Time FROM fac_CabinetAudit;

--
-- Not sure if you want to do this yet
--

-- DROP TABLE IF EXISTS fac_CabinetAudit;
