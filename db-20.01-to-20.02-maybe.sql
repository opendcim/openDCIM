--
-- New parameter for Changing cabinet labels from cabinet name to user preference based label
--

insert into fac_Config set Parameter='AssignCabinetLabels', Value='Location', UnitOfMeasure='string', ValType='string', DefaultVal='Location';

--
-- Bump up the database version (uncomment below once released)
--

-- UPDATE fac_Config set Value="20.02" WHERE Parameter="Version";
