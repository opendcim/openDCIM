---
--- Schema changes for 23.04 to 25.01
---

--
-- Table structure for table `fac_Cabinet`
--
-- Add SerialNo column to fac_Cabinet after Model
ALTER TABLE fac_Cabinet ADD COLUMN SerialNo VARCHAR(30) AFTER Model;

--
-- Table structure for table `fac_MediaConnectors`
--

DROP TABLE IF EXISTS fac_MediaConnectors;
CREATE TABLE IF NOT EXISTS fac_MediaConnectors (
  ConnectorID int(11) NOT NULL AUTO_INCREMENT,
  ConnectorType varchar(40) NOT NULL,
  PRIMARY KEY (ConnectorID),
  UNIQUE KEY connectortype (ConnectorType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO fac_MediaConnectors set ConnectorType='CAT5e';
INSERT INTO fac_MediaConnectors set ConnectorType='CAT6';

--
-- Table structure for table `fac_MediaProtocols`
--

DROP TABLE IF EXISTS fac_MediaProtocols;
CREATE TABLE IF NOT EXISTS fac_MediaProtocols (
  ProtocolID int(11) NOT NULL AUTO_INCREMENT,
  ProtocolName varchar(40) NOT NULL,
  PRIMARY KEY (ProtocolID),
  UNIQUE KEY protocolname (ProtocolName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

INSERT INTO fac_MediaProtocols set ProtocolName='Ethernet';

--
-- Table structure for table `fac_MediaDataRates`
--

DROP TABLE IF EXISTS fac_MediaDataRates;
CREATE TABLE IF NOT EXISTS fac_MediaDataRates (
  RateID int(11) NOT NULL AUTO_INCREMENT,
  RateText varchar(40) NOT NULL,
  PRIMARY KEY (RateID),
  UNIQUE KEY ratetext (RateText)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

INSERT INTO fac_MediaDataRates set RateText='100M';
INSERT INTO fac_MediaDataRates set RateText='1G';

ALTER TABLE fac_Ports ADD COLUMN ConnectorID int(11) NOT NULL DEFAULT 0 AFTER Label;
ALTER TABLE fac_Ports ADD COLUMN ProtocolID int(11) NOT NULL DEFAULT 0 AFTER ConnectorID;
ALTER TABLE fac_Ports ADD COLUMN RateID int(11) NOT NULL DEFAULT 0 AFTER ProtocolID;

--
-- Table Structure for table fac_Powerconnectors
--

DROP TABLE IF EXISTS fac_PowerConnectors;
CREATE TABLE fac_PowerConnectors (
  ConnectorID int(11) NOT NULL AUTO_INCREMENT,
  ConnectorName varchar(40) NOT NULL,
  PRIMARY KEY ConnectorID (ConnectorID),
  UNIQUE KEY ConnectorName (ConnectorName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_PowerConnectors set ConnectorName='C13';
INSERT INTO fac_PowerConnectors set ConnectorName='C19';

--
-- Table Structure for table fac_PowerPhases
--

DROP TABLE IF EXISTS fac_PowerPhases;
CREATE TABLE fac_PowerPhases (
  PhaseID int(11) NOT NULL AUTO_INCREMENT,
  PhaseName varchar(40) NOT NULL,
  PRIMARY KEY PhaseID (PhaseID),
  UNIQUE KEY PhaseName (PhaseName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_PowerPhases set PhaseName='A';
INSERT INTO fac_PowerPhases set PhaseName='B';
INSERT INTO fac_PowerPhases set PhaseName='C';

--
-- Table Structure for table fac_PowerVoltages
--

DROP TABLE IF EXISTS fac_PowerVoltages;
CREATE TABLE fac_PowerVoltages (
  VoltageID int(11) NOT NULL AUTO_INCREMENT,
  VoltageName varchar(40) NOT NULL,
  PRIMARY KEY VoltageID (VoltageID),
  UNIQUE KEY VoltageName (VoltageName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO fac_PowerVoltages set VoltageName='120AC';
INSERT INTO fac_PowerVoltages set VoltageName='208AC';
INSERT INTO fac_PowerVoltages set VoltageName='240AC';
INSERT INTO fac_PowerVoltages set VoltageName='277AC';
INSERT INTO fac_PowerVoltages set VoltageName='48DC';

ALTER TABLE fac_PowerPorts ADD COLUMN ConnectorID int(11) DEFAULT NULL AFTER Label;
ALTER TABLE fac_PowerPorts ADD COLUMN PhaseID int(11) DEFAULT NULL AFTER ConnectorID;
ALTER TABLE fac_PowerPorts ADD COLUMN VoltageID int(11) DEFAULT NULL AFTER PhaseID;

UPDATE fac_Config set Value="25.01" WHERE Parameter="Version";
