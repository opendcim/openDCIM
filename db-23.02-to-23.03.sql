---
--- Schema changes for 23.02 to 23.03
---

INSERT into fac_Config set Parameter='GDPRCountryIsolation', Value='disabled', UnitOfMeasure='Enabled/Disabled', ValType='string', DefaultVal='disabled';
INSERT into fac_Config set Parameter='DefaultCountry', Value='US', UnitOfMeasure='Country', ValType='string', DefaultVal='US';

CREATE TABLE fac_Country (
  countryCode CHAR(2) NOT NULL,
  countryName VARCHAR(80) NOT NULL,
  PRIMARY KEY (countryCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE fac_People drop column Phone3;
ALTER TABLE fac_People add column countryCode char(2) NOT NULL after Phone2;
ALTER TABLE fac_People add column LastActivity DATETIME NOT NULL after Disabled;
ALTER TABLE fac_People add column ExpirationDate DATE NOT NULL after LastActivity;
ALTER TABLE fac_DataCenter add column countryCode char(2) NOT NULL after DeliveryAddress;
ALTER TABLE fac_Container add column countryCode char(2) NOT NULL after Name;

delete from fac_Config where Parameter='AttrPhone3';
insert into fac_Config set Parameter='AttrCountry', Value='', UnitOfMeasure='Country', ValType='string', DefaultVal='';

UPDATE fac_Config set Value="23.03" WHERE Parameter="Version";
