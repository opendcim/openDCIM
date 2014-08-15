--
-- Add Comments field to cabinetaudit table
--

ALTER TABLE fac_CabinetAudit ADD Comments TEXT NULL AFTER AuditStamp;
