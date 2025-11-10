---
--- Schema changes for 23.04 to 24.01
---

UPDATE fac_Config set Value="24.01" WHERE Parameter="Version";
--- feature hdd
---  -fac_config
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ('feature_hdd', 'disabled','Enabled/Disabled','string','Disabled')
ON DUPLICATE KEY UPDATE Value = Value;
INSERT INTO fac_Config (Parameter, Value, UnitOfMeasure, ValType, DefaultVal) VALUES ('Log_for_user_hdd', 'disabled','Enabled/Disabled','string','Disabled')
ON DUPLICATE KEY UPDATE Value = Value;
---  -fac_people
ALTER TABLE fac_People ADD COLUMN ManageHDD TINYINT(1) DEFAULT 0;
---  -fac_devicetemplatehdd
CREATE TABLE fac_DeviceTemplateHdd (
    TemplateID INT NOT NULL,
    EnableHDDFeature TINYINT(1) DEFAULT 0,
    HDDCount INT DEFAULT 0,
    PRIMARY KEY (TemplateID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
---  -fac_hdd
CREATE TABLE fac_HDD (
  HDDID           INT NOT NULL AUTO_INCREMENT,
  DeviceID        INT NOT NULL,
  SerialNo        VARCHAR(100),
  Status          ENUM('On','Off','Pending_destruction','Destroyed','Spare')
                  DEFAULT 'On',
  Size            INT(11) DEFAULT NULL,          -- en Go
  TypeMedia       ENUM('HDD','SSD','MVME') DEFAULT NULL,
  DateAdd         DATETIME      DEFAULT CURRENT_TIMESTAMP,
  DateWithdrawn   DATETIME DEFAULT NULL,
  DateDestroyed DATETIME DEFAULT NULL,
  PRIMARY KEY (HDDID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

