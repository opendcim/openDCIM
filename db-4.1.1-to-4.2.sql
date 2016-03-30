--
-- Add in configuration items for LDAP authentication and authorization
--

INSERT INTO fac_Config set Parameter="LDAPServer", Value="localhost", UnitOfMeasure="URI", ValType="string", DefaultVal="localhost";
INSERT INTO fac_Config set Parameter="LDAPBaseDN", Value="dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPBindDN", Value="cn=%userid%,ou=users,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=%userid%,ou=users,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPSessionExpiration", Value="0", UnitOfMeasure="Seconds", ValType="int", DefaultVal="0";

INSERT INTO fac_Config set Parameter="LDAPSiteAccess", Value="cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPReadAccess", Value="cn=ReadAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=ReadAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPWriteAccess", Value="cn=WriteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=WriteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPDeleteAccess", Value="cn=DeleteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=DeleteAccess,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPAdminOwnDevices", Value="cn=AdminOwnDevices,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=AdminOwnDevices,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPRackRequest", Value="cn=RackRequest,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=RackRequest,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPRackAdmin", Value="cn=RackAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=RackAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPContactAdmin", Value="cn=ContactAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=ContactAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org";
INSERT INTO fac_Config set Parameter="LDAPSiteAdmin", Value="cn=SiteAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org", UnitOfMeasure="DN", ValType="string", DefaultVal="cn=SiteAdmin,cn=openDCIM,ou=groups,dc=opendcim,dc=org";

--
-- Alter the fac_People table to allow for API Key accounts
--

ALTER TABLE fac_People ADD COLUMN APIKey VARCHAR(80) NOT NULL DEFAULT '';

--
-- Bump up the database version (uncomment below once released)
--

UPDATE fac_Config set Value="4.2" WHERE Parameter="Version";
