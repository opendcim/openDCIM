--
-- Add configuration options for ldap debug 
--

INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPDebug", "enabled", "Enabled/Disabled", "string", "disabled");
UPDATE fac_Config SET DefaultVal="(|(uid=%userid%)(sAMAccountName=%userid%))" WHERE Parameter="LDAPUserSearch";

--
-- Add in new configuration item for log retention
--

INSERT INTO fac_Config set Parameter='logretention', Value='0', UnitOfMeasure='days', ValType='integer', DefaultVal='0';

--
-- Add in new configuration item for local reports
--

INSERT INTO fac_Config set Parameter='reportspath', Value='assets/reports/', UnitOfMeasure='string', ValType='string', DefaultVal='assets/reports/';

--
-- Add in new configuration item for Reservation Expiration
--

INSERT INTO fac_Config set Parameter='ReservationExpiration', Value='0', UnitOfMeasure='days', ValType='integer', DefaultVal='0';

--
-- Add an index to the fac_GenericLog to speed it up
--

CREATE INDEX ObjectID on fac_GenericLog (ObjectID);
CREATE INDEX ObjectTime on fac_GenericLog (ObjectID, Time);

--
-- Add in new configuration items for Alert Emails
--

INSERT INTO fac_Config set Parameter='PowerAlertsEmail', Value='disabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='disabled';
INSERT INTO fac_Config set Parameter='SensorAlertsEmail', Value='disabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='disabled';

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="19.01" WHERE Parameter="Version";

