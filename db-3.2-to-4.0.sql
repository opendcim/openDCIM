--
-- Allow for alphanumeric names of power connections
--
ALTER TABLE fac_PowerConnection CHANGE PDUPosition PDUPosition VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
