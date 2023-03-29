--
-- Bump up the database version (uncomment below once released)
--

INSERT into fac_Config set Parameter='OIDCEndpoint', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='OIDCClientID', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';
INSERT into fac_Config set Parameter='OIDCClientSecret', Value='', UnitOfMeasure='string', ValType='string', DefaultVal='';

UPDATE fac_Config set Value="23.01" WHERE Parameter="Version";
