--
-- Add in new configuration items for the path
--

INSERT INTO fac_Config set Parameter='drawingpath', Value='drawings/', UnitOfMeasure='string', ValType='string', DefaultVal='drawings/';
INSERT INTO fac_Config set Parameter='picturepath', Value='pictures/', UnitOfMeasure='string', ValType='string', DefaultVal='pictures/';

--
-- Add RequestedAction field to the Rack Request From page
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("RackRequestsActions", "disabled", "Enabled/Disabled", "string", "disabled");
ALTER TABLE fac_RackRequest ADD COLUMN RequestedAction VARCHAR(10) NULL AFTER SpecialInstructions;

--
-- Add configuration options for user information to be pulled from ldap 
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPFirstName", "givenname", "string", "string", "givenname");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPLastName", "sn", "string", "string", "sn");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPEmail", "mail", "string", "string", "mail");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPPhone1", "telephonenumber", "string", "string", "telephonenumber");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPPhone2", "mobile", "string", "string", "mobile");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPPhone3", "pager", "string", "string", "pager");

--
-- More LDAP configuration options to work well with AD and simplify query
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPServerType", "OpenLDAP", "OpenLDAP/AD", "string", "OpenLDAP");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPBindDN-AD", "%userid%@opendcim.org", "DN", "string", "%userid%@opendcim.org");
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("LDAPBaseSearch-AD", "(sAMAccountName=%userid%)", "DN", "string", "(sAMAccountName=%userid%)");
DELETE FROM TABLE fac_Config WHERE Parameter="LDAPUserSearch";
UPDATE fac_Config SET Value="(|(uid=%userid%)(&(objectClass=posixGroup)(memberUid=%userid%)))", DefaultVal="(|(uid=%userid%)(&(objectClass=posixGroup)(memberUid=%userid%)))" WHERE Parameter="LDAPBaseSearch";

--
-- Reinserting this parameter that is still used
--
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ("FreeSpaceColor", "#ffffff", "HexColor", "string", "#ffffff");

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="18.02" WHERE Parameter="Version";

