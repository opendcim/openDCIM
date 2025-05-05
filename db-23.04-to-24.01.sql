---
--- Schema changes for 23.04 to 24.01
---

UPDATE fac_Config set Value="24.01" WHERE Parameter="Version";
INSERT INTO fac_Config (Parameter, Value) VALUES ('feature_hdd', 'disabled')
ON DUPLICATE KEY UPDATE Value = Value;
ALTER TABLE fac_People ADD COLUMN ManageHDD TINYINT(1) DEFAULT 0;
CREATE TABLE fac_DeviceTemplateHdd (
    TemplateID INT NOT NULL,
    EnableHDDFeature TINYINT(1) DEFAULT 0,
    HDDCount INT DEFAULT 0,
    PRIMARY KEY (TemplateID)
);
CREATE TABLE fac_HDD (
    HDDID INT NOT NULL AUTO_INCREMENT,
    DeviceID INT NOT NULL,
    Label VARCHAR(100),
    SerialNo VARCHAR(100) UNIQUE,
    Status ENUM('on','off','replace','pending_destruction','destroyed_h2') DEFAULT 'on',
    Size INT, -- en Go
    TypeMedia ENUM('SATA', 'SCSI', 'SD'),
    DateAdd DATETIME DEFAULT CURRENT_TIMESTAMP,
    DateWithdrawn DATETIME,
    DateDestruction DATETIME,
    StatusDestruction ENUM('none', 'pending', 'destroyed'),
    Note TEXT,
    PRIMARY KEY (HDDID)
);