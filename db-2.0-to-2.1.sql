
--
-- Bump version number up
--

UPDATE fac_Config SET Value = '2.1' WHERE fac_Config.Parameter = 'Version';

--
-- Add table for rack tagging
--

DROP TABLE IF EXISTS fac_CabinetTags;
CREATE TABLE fac_CabinetTags (
  CabinetID int(11) NOT NULL,
  TagID int(11) NOT NULL,
  PRIMARY KEY (CabinetID,TagID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Add Notes field to cabinet table
--

ALTER TABLE fac_Cabinet ADD Notes TEXT NULL AFTER MapY2;
