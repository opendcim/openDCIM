--
-- Add configuration options for ldap debug 
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPDebug", "enabled", "Enabled/Disabled", "string", "disabled");
UPDATE fac_Config SET DefaultVal="(|(uid=%userid%)(sAMAccountName=%userid%))" WHERE Parameter="LDAPUserSearch";

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="18.03" WHERE Parameter="Version";

