-- MySQL dump 10.13  Distrib 5.5.31, for debian-linux-gnu (armv5tel)
--
-- Host: localhost    Database: dcim
-- ------------------------------------------------------
-- Server version	5.5.31-0+wheezy1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `fac_BinAudits`
--

DROP TABLE IF EXISTS `fac_BinAudits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_BinAudits` (
  `BinID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `AuditStamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_BinAudits`
--

LOCK TABLES `fac_BinAudits` WRITE;
/*!40000 ALTER TABLE `fac_BinAudits` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_BinAudits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_BinContents`
--

DROP TABLE IF EXISTS `fac_BinContents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_BinContents` (
  `BinID` int(11) NOT NULL,
  `SupplyID` int(11) NOT NULL,
  `Count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_BinContents`
--

LOCK TABLES `fac_BinContents` WRITE;
/*!40000 ALTER TABLE `fac_BinContents` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_BinContents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CDUTemplate`
--

DROP TABLE IF EXISTS `fac_CDUTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CDUTemplate` (
  `TemplateID` int(11) NOT NULL AUTO_INCREMENT,
  `ManufacturerID` int(11) NOT NULL,
  `Model` varchar(80) NOT NULL,
  `Managed` int(1) NOT NULL,
  `ATS` int(1) NOT NULL,
  `SNMPVersion` enum('1','2c') DEFAULT NULL,
  `VersionOID` varchar(80) NOT NULL,
  `Multiplier` enum('0.1','1','10','100') DEFAULT NULL,
  `OID1` varchar(80) NOT NULL,
  `OID2` varchar(80) NOT NULL,
  `OID3` varchar(80) NOT NULL,
  `ATSStatusOID` varchar(80) NOT NULL,
  `ATSDesiredResult` varchar(80) NOT NULL,
  `ProcessingProfile` enum('SingleOIDWatts','SingleOIDAmperes','Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes') DEFAULT NULL,
  `Voltage` int(11) NOT NULL,
  `Amperage` int(11) NOT NULL,
  `NumOutlets` int(11) NOT NULL,
  PRIMARY KEY (`TemplateID`),
  UNIQUE KEY `ManufacturerID_2` (`ManufacturerID`,`Model`),
  KEY `ManufacturerID` (`ManufacturerID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CDUTemplate`
--

LOCK TABLES `fac_CDUTemplate` WRITE;
/*!40000 ALTER TABLE `fac_CDUTemplate` DISABLE KEYS */;
INSERT INTO `fac_CDUTemplate` VALUES (1,1,'Unmanaged CDU',0,0,NULL,'','0.1','','','','','','SingleOIDAmperes',0,0,8),(2,2,'Generic Single-Phase CDU',1,0,NULL,'.1.3.6.1.4.1.318.1.1.4.1.2.0','','.1.3.6.1.4.1.318.1.1.12.2.3.1.1.2.1','','','','','SingleOIDAmperes',0,0,24),(3,2,'AP7721 ATS',1,0,NULL,'1.3.6.1.4.1.318.1.1.8.1.2','0.1','.1.3.6.1.4.1.318.1.1.8.5.4.3.1.7.1.1.1','','','.1.3.6.1.4.1.318.1.1.8.5.1.3.0','2','SingleOIDAmperes',208,20,8),(4,2,'AP7723 ATS',1,0,NULL,'.1.3.6.1.4.1.318.1.1.8.1.2','0.1','.1.3.6.1.4.1.318.1.1.8.5.4.3.1.7.1.1.1','','','.1.3.6.1.4.1.318.1.1.8.5.1.3.0','2','SingleOIDAmperes',208,12,8),(5,3,'RCXB308-103IN6TL30',1,0,NULL,'.1.3.6.1.4.1.21239.2.1.2.0','0.1','.1.3.6.1.4.1.21239.2.6.1.10.1','','','','','SingleOIDWatts',0,0,30),(6,3,'RCXB308-103IN6TL21',1,0,NULL,'.1.3.6.1.4.1.21239.2.1.2.0','0.1','.1.3.6.1.4.1.21239.2.25.1.30.1','','','','','SingleOIDWatts',0,0,30),(7,3,'RCMB248-105PH6CS15-OD',1,0,NULL,'.1.3.6.1.4.1.21239.2.1.2.0','0.1','.1.3.6.1.4.1.21239.2.6.1.10.1','','','','','SingleOIDWatts',0,0,30),(8,4,'Generic Single-Phase CDU',1,0,NULL,'.1.3.6.1.4.1.1718.3.1.1.0','','.1.3.6.1.4.1.1718.3.2.2.1.7.1.1','','','','','SingleOIDAmperes',0,0,24),(9,4,'Generic 3-Phase CDU',1,0,NULL,'.1.3.6.1.4.1.1718.3.1.1.0','','.1.3.6.1.4.1.1718.3.2.2.1.7.1.1','.1.3.6.1.4.1.1718.3.2.2.1.7.1.2','.1.3.6.1.4.1.1718.3.2.2.1.7.1.3','','','Convert3PhAmperes',0,0,24);
/*!40000 ALTER TABLE `fac_CDUTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CDUToolTip`
--

DROP TABLE IF EXISTS `fac_CDUToolTip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CDUToolTip` (
  `SortOrder` smallint(6) DEFAULT NULL,
  `Field` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Label` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `Enabled` tinyint(1) DEFAULT '1',
  UNIQUE KEY `Field` (`Field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CDUToolTip`
--

LOCK TABLES `fac_CDUToolTip` WRITE;
/*!40000 ALTER TABLE `fac_CDUToolTip` DISABLE KEYS */;
INSERT INTO `fac_CDUToolTip` VALUES (NULL,'BreakerSize','Breaker Size',0),(NULL,'FirmwareVersion','Firmware Version',0),(NULL,'InputAmperage','Input Amperage',0),(NULL,'IPAddress','IP Address',0),(NULL,'Model','Model',0),(NULL,'NumOutlets','Used/Total Connections',0),(NULL,'PanelID','Source Panel',0),(NULL,'PanelPole','Panel Pole Number',0),(NULL,'PanelVoltage','Voltage',0),(NULL,'SNMPCommunity','SNMP Community',0),(NULL,'Uptime','Uptime',0);
/*!40000 ALTER TABLE `fac_CDUToolTip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CabRow`
--

DROP TABLE IF EXISTS `fac_CabRow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CabRow` (
  `CabRowID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `ZoneID` int(11) NOT NULL,
  `CabOrder` enum('ASC','DESC') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ASC',
  PRIMARY KEY (`CabRowID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CabRow`
--

LOCK TABLES `fac_CabRow` WRITE;
/*!40000 ALTER TABLE `fac_CabRow` DISABLE KEYS */;
INSERT INTO `fac_CabRow` VALUES (1,'ROW TEST',1,'ASC'),(2,'C1Y',2,'ASC');
/*!40000 ALTER TABLE `fac_CabRow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Cabinet`
--

DROP TABLE IF EXISTS `fac_Cabinet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Cabinet` (
  `CabinetID` int(11) NOT NULL AUTO_INCREMENT,
  `DataCenterID` int(11) NOT NULL,
  `Location` varchar(20) NOT NULL,
  `AssignedTo` int(11) NOT NULL,
  `ZoneID` int(11) NOT NULL,
  `CabRowID` int(11) NOT NULL,
  `CabinetHeight` int(11) NOT NULL,
  `Model` varchar(80) NOT NULL,
  `Keylock` varchar(30) NOT NULL,
  `MaxKW` float NOT NULL,
  `MaxWeight` int(11) NOT NULL,
  `InstallationDate` date NOT NULL,
  `SensorIPAddress` varchar(254) NOT NULL,
  `SensorCommunity` varchar(40) NOT NULL,
  `SensorTemplateID` int(11) NOT NULL,
  `MapX1` int(11) NOT NULL,
  `MapX2` int(11) NOT NULL,
  `FrontEdge` enum('Top','Right','Bottom','Left') NOT NULL DEFAULT 'Top',
  `MapY1` int(11) NOT NULL,
  `MapY2` int(11) NOT NULL,
  `Notes` text,
  PRIMARY KEY (`CabinetID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Cabinet`
--

LOCK TABLES `fac_Cabinet` WRITE;
/*!40000 ALTER TABLE `fac_Cabinet` DISABLE KEYS */;
INSERT INTO `fac_Cabinet` VALUES (1,1,'DC2B6A',0,1,1,42,'TEST CAB','needs one',0,0,'2014-07-26','','',0,0,0,'Top',0,0,''),(2,2,'C1Y3',0,2,0,42,'','',0,0,'2014-07-27','','',0,0,0,'Top',0,0,''),(3,1,'A1',0,1,1,12,'jdj','',0,0,'2014-07-27','','',0,0,0,'Top',0,0,'');
/*!40000 ALTER TABLE `fac_Cabinet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CabinetAudit`
--

DROP TABLE IF EXISTS `fac_CabinetAudit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CabinetAudit` (
  `CabinetID` int(11) NOT NULL,
  `UserID` varchar(80) NOT NULL,
  `AuditStamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CabinetAudit`
--

LOCK TABLES `fac_CabinetAudit` WRITE;
/*!40000 ALTER TABLE `fac_CabinetAudit` DISABLE KEYS */;
INSERT INTO `fac_CabinetAudit` VALUES (1,'apearson','2014-07-27 04:13:19'),(1,'apearson','2014-07-27 04:14:30');
/*!40000 ALTER TABLE `fac_CabinetAudit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CabinetTags`
--

DROP TABLE IF EXISTS `fac_CabinetTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CabinetTags` (
  `CabinetID` int(11) NOT NULL,
  `TagID` int(11) NOT NULL,
  PRIMARY KEY (`CabinetID`,`TagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CabinetTags`
--

LOCK TABLES `fac_CabinetTags` WRITE;
/*!40000 ALTER TABLE `fac_CabinetTags` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_CabinetTags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CabinetTemps`
--

DROP TABLE IF EXISTS `fac_CabinetTemps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CabinetTemps` (
  `CabinetID` int(11) NOT NULL,
  `LastRead` datetime NOT NULL,
  `Temp` float NOT NULL,
  `Humidity` float NOT NULL,
  PRIMARY KEY (`CabinetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CabinetTemps`
--

LOCK TABLES `fac_CabinetTemps` WRITE;
/*!40000 ALTER TABLE `fac_CabinetTemps` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_CabinetTemps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_CabinetToolTip`
--

DROP TABLE IF EXISTS `fac_CabinetToolTip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_CabinetToolTip` (
  `SortOrder` smallint(6) DEFAULT NULL,
  `Field` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `Label` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `Enabled` tinyint(1) DEFAULT '1',
  UNIQUE KEY `Field` (`Field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_CabinetToolTip`
--

LOCK TABLES `fac_CabinetToolTip` WRITE;
/*!40000 ALTER TABLE `fac_CabinetToolTip` DISABLE KEYS */;
INSERT INTO `fac_CabinetToolTip` VALUES (NULL,'AssetTag','Asset Tag',0),(NULL,'ChassisSlots','Number of Slots in Chassis:',0),(NULL,'DeviceID','Device ID',0),(NULL,'DeviceType','Device Type',0),(NULL,'EscalationID','Details',0),(NULL,'EscalationTimeID','Time Period',0),(NULL,'ESX','ESX Server?',0),(NULL,'InstallDate','Install Date',0),(NULL,'MfgDate','Manufacture Date',0),(NULL,'NominalWatts','Nominal Draw (Watts)',0),(NULL,'Owner','Departmental Owner',0),(NULL,'Ports','Number of Data Ports',0),(NULL,'PowerSupplyCount','Number of Power Supplies',0),(NULL,'PrimaryContact','Primary Contact',0),(NULL,'PrimaryIP','Primary IP',0),(NULL,'Reservation','Reservation?',0),(NULL,'SerialNo','Serial Number',0),(NULL,'SNMPCommunity','SNMP Read Only Community',0),(NULL,'TemplateID','Device Class',0),(NULL,'WarrantyCo','Warranty Company',0),(NULL,'WarrantyExpire','Warranty Expiration',0);
/*!40000 ALTER TABLE `fac_CabinetToolTip` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_ColorCoding`
--

DROP TABLE IF EXISTS `fac_ColorCoding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_ColorCoding` (
  `ColorID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `DefaultNote` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ColorID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_ColorCoding`
--

LOCK TABLES `fac_ColorCoding` WRITE;
/*!40000 ALTER TABLE `fac_ColorCoding` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_ColorCoding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Config`
--

DROP TABLE IF EXISTS `fac_Config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Config` (
  `Parameter` varchar(40) NOT NULL,
  `Value` varchar(200) NOT NULL,
  `UnitOfMeasure` varchar(40) NOT NULL,
  `ValType` varchar(40) NOT NULL,
  `DefaultVal` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Config`
--

LOCK TABLES `fac_Config` WRITE;
/*!40000 ALTER TABLE `fac_Config` DISABLE KEYS */;
INSERT INTO `fac_Config` VALUES ('OrgName','openDCIM Computer Facilities','Name','string','openDCIM Computer Facilities'),('ClassList','ITS, Internal, Customer','List','string','ITS, Internal, Customer'),('SpaceRed','80','percentage','float','80'),('SpaceYellow','60','percentage','float','60'),('WeightRed','80','percentage','float','80'),('WeightYellow','60','percentage','float','60'),('PowerRed','80','percentage','float','80'),('PowerYellow','60','percentage','float','60'),('RackWarningHours','4','Hours','integer','4'),('RackOverdueHours','1','Hours','integer','1'),('CriticalColor','#CC0000','HexColor','string','#cc0000'),('CautionColor','#CCCC00','HexColor','string','#cccc00'),('GoodColor','#00AA00','HexColor','string','#0a0'),('MediaEnforce','disabled','Enabled/Disabled','string','Disabled'),('DefaultPanelVoltage','208','Volts','int','208'),('annualCostPerUYear','200','Dollars','float','200'),('annualCostPerWattYear','0.7884','Dollars','float','0.7884'),('Locale','en_US.utf8','TextLocale','string','en_US.utf8'),('timezone','Europe/London','string','string','America/Chicago'),('PDFLogoFile','logo.png','Filename','string','logo.png'),('PDFfont','Arial','Font','string','Arial'),('SMTPServer','smtp.your.domain','Server','string','smtp.your.domain'),('SMTPPort','25','Port','int','25'),('SMTPHelo','your.domain','Helo','string','your.domain'),('SMTPUser','','Username','string',''),('SMTPPassword','','Password','string',''),('MailFromAddr','DataCenterTeamAddr@your.domain','Email','string','DataCenterTeamAddr@your.domain'),('MailSubject','ITS Facilities Rack Request','EmailSub','string','ITS Facilities Rack Request'),('MailToAddr','DataCenterTeamAddr@your.domain','Email','string','DataCenterTeamAddr@your.domain'),('ComputerFacMgr','DataCenterMgr Name','Name','string','DataCenterMgr Name'),('NetworkCapacityReportOptIn','OptIn','OptIn/OptOut','string','OptIn'),('NetworkThreshold','75','Percentage','integer','75'),('FacMgrMail','DataCenterMgr@your.domain','Email','string','DataCenterMgr@your.domain'),('InstallURL','','URL','string','https://dcim.your.domain'),('Version','3.2','','',''),('UserLookupURL','https://','URL','string','https://'),('ReservedColor','#00FFFF','HexColor','string','#FFFFFF'),('FreeSpaceColor','#FFFFFF','HexColor','string','#FFFFFF'),('HeaderColor','#006633','HexColor','string','#006633'),('BodyColor','#F0E0B2','HexColor','string','#F0E0B2'),('LinkColor','#000000','HexColor','string','#000000'),('VisitedLinkColor','#8D90B3','HexColor','string','#8D90B3'),('LabelCase','upper','string','string','upper'),('mDate','blank','string','string','blank'),('wDate','blank','string','string','blank'),('NewInstallsPeriod','7','Days','int','7'),('VMExpirationTime','7','Days','int','7'),('mUnits','metric','English/Metric','string','english'),('snmpwalk','/usr/bin/snmpwalk','path','string','/usr/bin/snmpwalk'),('snmpget','/usr/bin/snmpget','path','string','/usr/bin/snmpget'),('SNMPCommunity','public','string','string','public'),('cut','/bin/cut','path','string','/bin/cut'),('ToolTips','','Enabled/Disabled','string','Disabled'),('CDUToolTips','','Enabled/Disabled','string','Disabled'),('PageSize','A4','string','string','Letter'),('path_weight_cabinet','1','','int','1'),('path_weight_rear','','','int','1'),('path_weight_row','4','','int','4'),('TemperatureRed','30','degrees','float','30'),('TemperatureYellow','25','degrees','float','25'),('HumidityRedHigh','75','percentage','float','75'),('HumidityRedLow','35','percentage','float','35'),('HumidityYellowHigh','55','percentage','float','55'),('HumidityYellowLow','45','percentage','float','45');
/*!40000 ALTER TABLE `fac_Config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Contact`
--

DROP TABLE IF EXISTS `fac_Contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Contact` (
  `ContactID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` varchar(80) NOT NULL,
  `LastName` varchar(40) NOT NULL,
  `FirstName` varchar(40) NOT NULL,
  `Phone1` varchar(20) NOT NULL,
  `Phone2` varchar(20) NOT NULL,
  `Phone3` varchar(20) NOT NULL,
  `Email` varchar(80) NOT NULL,
  PRIMARY KEY (`ContactID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Contact`
--

LOCK TABLES `fac_Contact` WRITE;
/*!40000 ALTER TABLE `fac_Contact` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Container`
--

DROP TABLE IF EXISTS `fac_Container`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Container` (
  `ContainerID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `ParentID` int(11) NOT NULL DEFAULT '0',
  `DrawingFileName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MapX` int(11) NOT NULL,
  `MapY` int(11) NOT NULL,
  PRIMARY KEY (`ContainerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Container`
--

LOCK TABLES `fac_Container` WRITE;
/*!40000 ALTER TABLE `fac_Container` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Container` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_DataCenter`
--

DROP TABLE IF EXISTS `fac_DataCenter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_DataCenter` (
  `DataCenterID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `SquareFootage` int(11) NOT NULL,
  `DeliveryAddress` varchar(255) NOT NULL,
  `Administrator` varchar(80) NOT NULL,
  `MaxkW` int(11) NOT NULL,
  `DrawingFileName` varchar(255) NOT NULL,
  `EntryLogging` tinyint(1) NOT NULL,
  `ContainerID` int(11) NOT NULL,
  `MapX` int(11) NOT NULL,
  `MapY` int(11) NOT NULL,
  PRIMARY KEY (`DataCenterID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_DataCenter`
--

LOCK TABLES `fac_DataCenter` WRITE;
/*!40000 ALTER TABLE `fac_DataCenter` DISABLE KEYS */;
INSERT INTO `fac_DataCenter` VALUES (1,'testDC',10000,'ksdk','kask',1000,'',0,0,0,0),(2,'UKDC2 test',1000,'oo','',0,'',0,0,0,0);
/*!40000 ALTER TABLE `fac_DataCenter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Decommission`
--

DROP TABLE IF EXISTS `fac_Decommission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Decommission` (
  `SurplusDate` date NOT NULL,
  `Label` varchar(80) NOT NULL,
  `SerialNo` varchar(40) NOT NULL,
  `AssetTag` varchar(20) NOT NULL,
  `UserID` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Decommission`
--

LOCK TABLES `fac_Decommission` WRITE;
/*!40000 ALTER TABLE `fac_Decommission` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Decommission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Department`
--

DROP TABLE IF EXISTS `fac_Department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Department` (
  `DeptID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `ExecSponsor` varchar(80) NOT NULL,
  `SDM` varchar(80) NOT NULL,
  `Classification` varchar(80) NOT NULL,
  `DeptColor` varchar(7) NOT NULL DEFAULT '#FFFFFF',
  PRIMARY KEY (`DeptID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Department`
--

LOCK TABLES `fac_Department` WRITE;
/*!40000 ALTER TABLE `fac_Department` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_DeptContacts`
--

DROP TABLE IF EXISTS `fac_DeptContacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_DeptContacts` (
  `DeptID` int(11) NOT NULL,
  `ContactID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_DeptContacts`
--

LOCK TABLES `fac_DeptContacts` WRITE;
/*!40000 ALTER TABLE `fac_DeptContacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_DeptContacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Device`
--

DROP TABLE IF EXISTS `fac_Device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Device` (
  `DeviceID` int(11) NOT NULL AUTO_INCREMENT,
  `Label` varchar(80) NOT NULL,
  `SerialNo` varchar(40) NOT NULL,
  `AssetTag` varchar(20) NOT NULL,
  `PrimaryIP` varchar(254) NOT NULL,
  `SNMPCommunity` varchar(80) NOT NULL,
  `ESX` tinyint(1) NOT NULL,
  `Owner` int(11) NOT NULL,
  `EscalationTimeID` int(11) NOT NULL,
  `EscalationID` int(11) NOT NULL,
  `PrimaryContact` int(11) NOT NULL,
  `Cabinet` int(11) NOT NULL,
  `Position` int(11) NOT NULL,
  `Height` int(11) NOT NULL,
  `Ports` int(11) NOT NULL,
  `FirstPortNum` int(11) NOT NULL,
  `TemplateID` int(11) NOT NULL,
  `NominalWatts` int(11) NOT NULL,
  `PowerSupplyCount` int(11) NOT NULL,
  `DeviceType` enum('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') NOT NULL,
  `ChassisSlots` smallint(6) NOT NULL,
  `RearChassisSlots` smallint(6) NOT NULL,
  `ParentDevice` int(11) NOT NULL,
  `MfgDate` date NOT NULL,
  `InstallDate` date NOT NULL,
  `WarrantyCo` varchar(80) NOT NULL,
  `WarrantyExpire` date DEFAULT NULL,
  `Notes` text,
  `Reservation` tinyint(1) NOT NULL,
  `HalfDepth` tinyint(1) NOT NULL DEFAULT '0',
  `BackSide` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`DeviceID`),
  KEY `SerialNo` (`SerialNo`,`AssetTag`,`PrimaryIP`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Device`
--

LOCK TABLES `fac_Device` WRITE;
/*!40000 ALTER TABLE `fac_Device` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Device` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_DevicePorts`
--

DROP TABLE IF EXISTS `fac_DevicePorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_DevicePorts` (
  `ConnectionID` int(11) NOT NULL AUTO_INCREMENT,
  `DeviceID` int(11) DEFAULT NULL,
  `DevicePort` int(11) DEFAULT NULL,
  `MediaID` int(11) DEFAULT NULL,
  `PortDescriptor` varchar(30) DEFAULT NULL,
  `ColorID` int(11) DEFAULT NULL,
  `Notes` text,
  PRIMARY KEY (`ConnectionID`),
  KEY `DeviceID` (`DeviceID`,`DevicePort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_DevicePorts`
--

LOCK TABLES `fac_DevicePorts` WRITE;
/*!40000 ALTER TABLE `fac_DevicePorts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_DevicePorts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_DeviceTags`
--

DROP TABLE IF EXISTS `fac_DeviceTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_DeviceTags` (
  `DeviceID` int(11) NOT NULL,
  `TagID` int(11) NOT NULL,
  PRIMARY KEY (`DeviceID`,`TagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_DeviceTags`
--

LOCK TABLES `fac_DeviceTags` WRITE;
/*!40000 ALTER TABLE `fac_DeviceTags` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_DeviceTags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_DeviceTemplate`
--

DROP TABLE IF EXISTS `fac_DeviceTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_DeviceTemplate` (
  `TemplateID` int(11) NOT NULL AUTO_INCREMENT,
  `ManufacturerID` int(11) NOT NULL,
  `Model` varchar(80) NOT NULL,
  `Height` int(11) NOT NULL,
  `Weight` int(11) NOT NULL,
  `Wattage` int(11) NOT NULL,
  `DeviceType` enum('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') NOT NULL DEFAULT 'Server',
  `PSCount` int(11) NOT NULL,
  `NumPorts` int(11) NOT NULL,
  `Notes` text NOT NULL,
  `FrontPictureFile` varchar(45) NOT NULL,
  `RearPictureFile` varchar(45) NOT NULL,
  `ChassisSlots` smallint(6) NOT NULL,
  `RearChassisSlots` smallint(6) NOT NULL,
  PRIMARY KEY (`TemplateID`),
  UNIQUE KEY `ManufacturerID` (`ManufacturerID`,`Model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_DeviceTemplate`
--

LOCK TABLES `fac_DeviceTemplate` WRITE;
/*!40000 ALTER TABLE `fac_DeviceTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_DeviceTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_EscalationTimes`
--

DROP TABLE IF EXISTS `fac_EscalationTimes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_EscalationTimes` (
  `EscalationTimeID` int(11) NOT NULL AUTO_INCREMENT,
  `TimePeriod` varchar(80) NOT NULL,
  PRIMARY KEY (`EscalationTimeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_EscalationTimes`
--

LOCK TABLES `fac_EscalationTimes` WRITE;
/*!40000 ALTER TABLE `fac_EscalationTimes` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_EscalationTimes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Escalations`
--

DROP TABLE IF EXISTS `fac_Escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Escalations` (
  `EscalationID` int(11) NOT NULL AUTO_INCREMENT,
  `Details` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`EscalationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Escalations`
--

LOCK TABLES `fac_Escalations` WRITE;
/*!40000 ALTER TABLE `fac_Escalations` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Escalations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_GenericLog`
--

DROP TABLE IF EXISTS `fac_GenericLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_GenericLog` (
  `UserID` varchar(80) NOT NULL,
  `Class` varchar(40) NOT NULL,
  `ObjectID` varchar(80) NOT NULL,
  `ChildID` int(11) DEFAULT NULL,
  `Action` varchar(40) NOT NULL,
  `Property` varchar(40) NOT NULL,
  `OldVal` varchar(255) NOT NULL,
  `NewVal` varchar(255) NOT NULL,
  `Time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_GenericLog`
--

LOCK TABLES `fac_GenericLog` WRITE;
/*!40000 ALTER TABLE `fac_GenericLog` DISABLE KEYS */;
INSERT INTO `fac_GenericLog` VALUES ('apearson','DataCenter','1',NULL,'1','DataCenterID','','1','2014-07-25 23:14:21'),('apearson','DataCenter','1',NULL,'1','Name','','testDC','2014-07-25 23:14:21'),('apearson','DataCenter','1',NULL,'1','SquareFootage','','10000','2014-07-25 23:14:21'),('apearson','DataCenter','1',NULL,'1','DeliveryAddress','','ksdk','2014-07-25 23:14:21'),('apearson','DataCenter','1',NULL,'1','Administrator','','kask','2014-07-25 23:14:21'),('apearson','DataCenter','1',NULL,'1','MaxkW','','1000','2014-07-25 23:14:21'),('apearson','PowerSource','1',NULL,'1','PowerSourceID','','1','2014-07-25 23:15:26'),('apearson','PowerSource','1',NULL,'1','SourceName','','ups1','2014-07-25 23:15:26'),('apearson','PowerSource','1',NULL,'1','DataCenterID','','1','2014-07-25 23:15:26'),('apearson','PowerSource','1',NULL,'1','Capacity','','800','2014-07-25 23:15:26'),('apearson','PowerPanel','1',NULL,'1','PanelID','','1','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','PowerSourceID','','1','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','PanelLabel','','ssdsd','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','NumberOfPoles','','10','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','MainBreakerSize','','7','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','PanelVoltage','','216','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','NumberScheme','','Odd/Even','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','IPAddress','','asdasd','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','panelOID','','132332','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'1','PanelIPAddress','','asdasd','2014-07-26 00:03:16'),('apearson','PowerPanel','1',NULL,'3','IPAddress','this->IPAddress','12','2014-07-26 00:03:45'),('apearson','PowerPanel','1',NULL,'3','PanelOID','this->PanelOID','','2014-07-26 00:03:46'),('apearson','PowerPanel','1',NULL,'3','panelOID','','123','2014-07-26 00:03:46'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12','2014-07-26 00:03:46'),('apearson','PowerPanel','1',NULL,'3','IPAddress','this->IPAddress','123','2014-07-26 00:05:07'),('apearson','PowerPanel','1',NULL,'3','PanelOID','this->PanelOID','','2014-07-26 00:05:07'),('apearson','PowerPanel','1',NULL,'3','panelOID','','1223232','2014-07-26 00:05:07'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','123','2014-07-26 00:05:07'),('apearson','PowerPanel','1',NULL,'3','panelOID','','122332','2014-07-26 00:05:11'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','123','2014-07-26 00:05:11'),('apearson','PowerPanel','1',NULL,'3','panelOID','','122332','2014-07-26 00:09:31'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','123','2014-07-26 00:09:31'),('apearson','PowerPanel','1',NULL,'3','IPAddress','123','12223','2014-07-26 00:09:35'),('apearson','PowerPanel','1',NULL,'3','panelOID','','','2014-07-26 00:09:35'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:09:35'),('apearson','PowerPanel','1',NULL,'3','panelOID','','asddsaadsdsds','2014-07-26 00:09:38'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:09:38'),('apearson','PowerPanel','1',NULL,'3','PanelOID','','asddsaadsdsds','2014-07-26 00:14:39'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:14:39'),('apearson','PowerPanel','1',NULL,'3','PanelOID','asddsaadsdsds','242','2014-07-26 00:14:48'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:14:48'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:15:54'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:15:58'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:16:02'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:16:32'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:16:44'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:24:49'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:24:53'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:25:05'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:26:28'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:26:31'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:26:48'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:26:52'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:27:01'),('apearson','PowerPanel','1',NULL,'3','Managed','','1','2014-07-26 00:35:10'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:35:10'),('apearson','PowerPanel','1',NULL,'3','Managed','1','','2014-07-26 00:35:41'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:35:41'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:42:09'),('apearson','PowerPanel','1',NULL,'3','Managed','','1','2014-07-26 00:42:15'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:42:15'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:44:02'),('apearson','PowerPanel','1',NULL,'3','Managed','1','','2014-07-26 00:44:10'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:44:10'),('apearson','PowerPanel','1',NULL,'3','Managed','','1','2014-07-26 00:44:12'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 00:44:15'),('apearson','PowerPanel','2',NULL,'1','PanelID','','2','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','PowerSourceID','','1','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','PanelLabel','','as','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','NumberOfPoles','','21','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','MainBreakerSize','','1111','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','PanelVoltage','','208','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','NumberScheme','','Odd/Even','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','Managed','','1','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','IPAddress','','12','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','SNMPCommunity','','123','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','PanelOID','','1232','2014-07-26 01:12:19'),('apearson','PowerPanel','2',NULL,'1','PanelIPAddress','','12','2014-07-26 01:12:19'),('apearson','PowerPanel','1',NULL,'3','SNMPCommunity','','public','2014-07-26 01:12:29'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:12:29'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','2c','2014-07-26 01:46:30'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:46:31'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:46:31'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:46:31'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','2c','2014-07-26 01:48:00'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:48:00'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:48:00'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:48:00'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','2c','2014-07-26 01:48:29'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:48:30'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:48:30'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:48:30'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','2c','2014-07-26 01:49:34'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:49:34'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:49:34'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:49:34'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:49:49'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:49:49'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:49:49'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:49:49'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:49:55'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:49:55'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 01:49:55'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:49:55'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','2c','2014-07-26 01:50:02'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:50:02'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:50:02'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:50:02'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:50:10'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1000','2014-07-26 01:50:10'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:50:10'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:50:10'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:51:32'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1000','2014-07-26 01:51:32'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:51:32'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:51:32'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:51:38'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:51:38'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 01:51:38'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:51:38'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:51:44'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1000','2014-07-26 01:51:44'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 01:51:44'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:51:44'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:52:28'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1000','2014-07-26 01:52:28'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 01:52:28'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:52:28'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:52:32'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:52:32'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:52:32'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:52:32'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:52:41'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1','2014-07-26 01:52:41'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 01:52:41'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:52:41'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 01:52:49'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1000','2014-07-26 01:52:49'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:52:49'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 01:52:49'),('apearson','PowerPanel','2',NULL,'3','SNMPVersion','','1','2014-07-26 01:57:35'),('apearson','PowerPanel','2',NULL,'3','Multiplier','','100','2014-07-26 01:57:36'),('apearson','PowerPanel','2',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:57:36'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 01:57:36'),('apearson','PowerPanel','2',NULL,'3','SNMPVersion','','1','2014-07-26 01:57:42'),('apearson','PowerPanel','2',NULL,'3','PanelOID','1232','12345','2014-07-26 01:57:42'),('apearson','PowerPanel','2',NULL,'3','Multiplier','','1','2014-07-26 01:57:42'),('apearson','PowerPanel','2',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:57:42'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 01:57:42'),('apearson','PowerPanel','2',NULL,'3','SNMPVersion','','1','2014-07-26 01:57:49'),('apearson','PowerPanel','2',NULL,'3','PanelOID','1232','123455','2014-07-26 01:57:49'),('apearson','PowerPanel','2',NULL,'3','Multiplier','','1','2014-07-26 01:57:49'),('apearson','PowerPanel','2',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:57:49'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 01:57:49'),('apearson','PowerPanel','2',NULL,'3','PanelVoltage','208','205','2014-07-26 01:57:55'),('apearson','PowerPanel','2',NULL,'3','SNMPVersion','','1','2014-07-26 01:57:55'),('apearson','PowerPanel','2',NULL,'3','Multiplier','','1','2014-07-26 01:57:55'),('apearson','PowerPanel','2',NULL,'3','ProcessingProfile','','SingleOIDWatts','2014-07-26 01:57:55'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 01:57:55'),('apearson','PowerPanel','3',NULL,'1','PanelID','','3','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','PowerSourceID','','1','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','PanelLabel','','asadss','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','NumberOfPoles','','23','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','MainBreakerSize','','1111','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','PanelVoltage','','122','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','NumberScheme','','Odd/Even','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','Managed','','1','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','IPAddress','','21','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','SNMPCommunity','','ddsdfdsdff','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','SNMPVersion','','2c','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','PanelOID','','sdfd','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','Multiplier','','10','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','ProcessingProfile','','SingleOIDAmperes','2014-07-26 02:00:46'),('apearson','PowerPanel','3',NULL,'1','PanelIPAddress','','21','2014-07-26 02:00:46'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','','1','2014-07-26 02:01:01'),('apearson','PowerPanel','1',NULL,'3','Multiplier','','1000','2014-07-26 02:01:01'),('apearson','PowerPanel','1',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 02:01:01'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 02:01:01'),('apearson','PowerPanel','1',NULL,'3','SNMPVersion','1','2c','2014-07-26 02:01:16'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 02:01:16'),('apearson','PowerPanel','1',NULL,'3','NumberOfPoles','10','11','2014-07-26 02:01:36'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 02:01:36'),('apearson','PowerPanel','1',NULL,'3','NumberOfPoles','11','24','2014-07-26 02:01:46'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-26 02:01:46'),('apearson','CabRow','1',NULL,'1','CabRowID','','1','2014-07-26 02:12:08'),('apearson','CabRow','1',NULL,'1','Name','','ROW TEST','2014-07-26 02:12:08'),('apearson','Zone','1',NULL,'1','ZoneID','','1','2014-07-26 02:12:20'),('apearson','Zone','1',NULL,'1','DataCenterID','','1','2014-07-26 02:12:20'),('apearson','Zone','1',NULL,'1','Description','','ZONE TEST','2014-07-26 02:12:20'),('apearson','Zone','1',NULL,'1','MapZoom','','100','2014-07-26 02:12:20'),('apearson','CabRow','1',NULL,'3','ZoneID','','1','2014-07-26 02:12:33'),('apearson','Cabinet','1',NULL,'1','CabinetID','','1','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','DataCenterID','','1','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','Location','','TEST1','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','ZoneID','','1','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','CabRowID','','1','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','CabinetHeight','','42','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','Model','','TEST CAB','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','Keylock','','needs one','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','InstallationDate','','2014-07-26','2014-07-26 02:13:00'),('apearson','Cabinet','1',NULL,'1','FrontEdge','','Top','2014-07-26 02:13:00'),('apearson','PowerDistribution','1',NULL,'1','PDUID','','1','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','Label','','UPS1/DB1/1','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','CabinetID','','1','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','TemplateID','','2','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','PanelID','','3','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','BreakerSize','','1','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','PanelPole','','2','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'1','InputAmperage','','32','2014-07-26 02:14:16'),('apearson','PowerDistribution','1',NULL,'3','PanelID','3','2','2014-07-26 02:16:30'),('apearson','PowerPanel','2',NULL,'3','SNMPVersion','','1','2014-07-26 02:19:23'),('apearson','PowerPanel','2',NULL,'3','Multiplier','','1','2014-07-26 02:19:23'),('apearson','PowerPanel','2',NULL,'3','ProcessingProfile','','SingleOIDAmperes','2014-07-26 02:19:23'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:19:23'),('apearson','PowerPanel','2',NULL,'3','Multiplier','1','1000','2014-07-26 02:20:11'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:20:11'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:28:03'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:29:07'),('apearson','PowerPanel','2',NULL,'3','NumberScheme','Odd/Even','Sequential','2014-07-26 02:29:34'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:29:34'),('apearson','PowerPanel','2',NULL,'3','NumberScheme','Sequential','Odd/Even','2014-07-26 02:29:43'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:29:44'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:30:14'),('apearson','PowerPanel','2',NULL,'3','NumberScheme','Odd/Even','Sequential','2014-07-26 02:30:31'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:30:31'),('apearson','PowerPanel','2',NULL,'3','NumberScheme','Sequential','Odd/Even','2014-07-26 02:31:17'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:31:17'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:32:13'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:32:37'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:34:59'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:36:28'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:36:43'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:37:53'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:38:42'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:39:15'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:39:47'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:43:43'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:44:35'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:44:54'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:45:56'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:47:14'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:49:29'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-26 02:49:44'),('apearson','PowerDistribution','2',NULL,'1','PDUID','','2','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','Label','','adadadad','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','CabinetID','','1','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','TemplateID','','3','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','PanelID','','1','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','BreakerSize','','1','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','PanelPole','','1','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'1','InputAmperage','','12','2014-07-26 23:42:10'),('apearson','PowerDistribution','2',NULL,'3','PanelPole','1','2','2014-07-26 23:42:25'),('apearson','PowerDistribution','2',NULL,'3','PanelPole','2','3','2014-07-26 23:42:31'),('apearson','PowerDistribution','2',NULL,'3','PanelPole','3','1','2014-07-26 23:42:38'),('apearson','PowerDistribution','2',NULL,'3','PanelPole','3','1','2014-07-26 23:42:38'),('apearson','PowerDistribution','2',NULL,'3','PanelID','1','3','2014-07-26 23:42:45'),('apearson','PowerDistribution','2',NULL,'3','PanelID','3','2','2014-07-26 23:42:51'),('apearson','PowerPanel','1',NULL,'3','PanelOID','242','freakyP3','2014-07-27 00:32:57'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-27 00:32:57'),('apearson','PowerPanel','3',NULL,'3','PanelOID','sdfd','panel2','2014-07-27 00:33:07'),('apearson','PowerPanel','3',NULL,'3','PanelIPAddress','','21','2014-07-27 00:33:07'),('apearson','PowerPanel','2',NULL,'3','PanelVoltage','208','240','2014-07-27 01:17:41'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 01:17:42'),('apearson','PowerPanel','2',NULL,'3','Multiplier','1000','10','2014-07-27 01:17:49'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 01:17:49'),('apearson','PowerPanel','3',NULL,'3','PanelVoltage','122','250','2014-07-27 01:17:58'),('apearson','PowerPanel','3',NULL,'3','PanelIPAddress','','21','2014-07-27 01:17:58'),('apearson','PowerPanel','1',NULL,'3','PanelVoltage','216','120','2014-07-27 01:18:11'),('apearson','PowerPanel','1',NULL,'3','Multiplier','1000','10','2014-07-27 01:18:11'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-27 01:18:11'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 02:21:45'),('apearson','PowerPanel','2',NULL,'3','Managed','1','','2014-07-27 02:21:46'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 02:21:46'),('apearson','PowerDistribution','2',NULL,'3','PanelID','2','3','2014-07-27 02:22:20'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 02:22:43'),('apearson','PowerPanel','2',NULL,'3','Managed','','1','2014-07-27 02:22:43'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 02:22:43'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 02:27:43'),('apearson','PowerPanel','2',NULL,'3','Multiplier','10','100','2014-07-27 02:27:43'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 02:27:43'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 02:28:28'),('apearson','PowerPanel','2',NULL,'3','Multiplier','100','1','2014-07-27 02:28:28'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 02:28:28'),('apearson','PowerDistribution','2',NULL,'3','InputAmperage','12','32','2014-07-27 02:53:47'),('apearson','PowerDistribution','2',NULL,'3','InputAmperage','32','1','2014-07-27 02:54:08'),('apearson','PowerDistribution','1',NULL,'3','PanelPole','2','5','2014-07-27 02:56:40'),('apearson','PowerDistribution','1',NULL,'3','PanelPole','5','2','2014-07-27 02:57:14'),('apearson','PowerDistribution','1',NULL,'3','InputAmperage','32','2','2014-07-27 02:58:33'),('apearson','PowerDistribution','1',NULL,'3','InputAmperage','2','6','2014-07-27 03:11:19'),('apearson','PowerDistribution','1',NULL,'3','InputAmperage','6','8','2014-07-27 03:11:45'),('apearson','PowerDistribution','1',NULL,'3','InputAmperage','8','10','2014-07-27 03:12:01'),('apearson','PowerDistribution','1',NULL,'3','InputAmperage','10','16','2014-07-27 03:12:48'),('apearson','PowerDistribution','1',NULL,'LogManualWattage','Wattage','','100','2014-07-27 03:13:01'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:16:11'),('apearson','PowerPanel','2',NULL,'3','NumberScheme','Odd/Even','Sequential','2014-07-27 03:16:11'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 03:16:11'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:16:20'),('apearson','PowerPanel','2',NULL,'3','NumberScheme','Sequential','Odd/Even','2014-07-27 03:16:20'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 03:16:20'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:16:33'),('apearson','PowerPanel','2',NULL,'3','PanelOID','1232','1232.131.31.31','2014-07-27 03:16:33'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 03:16:33'),('apearson','PowerPanel','3',NULL,'3','PanelPole','2','','2014-07-27 03:16:56'),('apearson','PowerPanel','3',NULL,'3','PanelOID','panel2','12.3.3.12.43','2014-07-27 03:16:56'),('apearson','PowerPanel','3',NULL,'3','PanelIPAddress','','21','2014-07-27 03:16:56'),('apearson','PowerPanel','1',NULL,'3','PanelLabel','ssdsd','DB3','2014-07-27 03:17:23'),('apearson','PowerPanel','1',NULL,'3','PanelPole','2','','2014-07-27 03:17:23'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-27 03:17:23'),('apearson','PowerPanel','3',NULL,'3','PanelLabel','asadss','DB2','2014-07-27 03:17:33'),('apearson','PowerPanel','3',NULL,'3','PanelPole','2','','2014-07-27 03:17:33'),('apearson','PowerPanel','3',NULL,'3','PanelIPAddress','','21','2014-07-27 03:17:33'),('apearson','PowerPanel','2',NULL,'3','PanelLabel','as','DB1','2014-07-27 03:17:44'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:17:44'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 03:17:44'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:18:14'),('apearson','PowerPanel','2',NULL,'3','SNMPCommunity','123','joepublic','2014-07-27 03:18:14'),('apearson','PowerPanel','2',NULL,'3','SNMPVersion','1','2c','2014-07-27 03:18:14'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 03:18:14'),('apearson','PowerPanel','1',NULL,'3','PanelVoltage','120','240','2014-07-27 03:19:05'),('apearson','PowerPanel','1',NULL,'3','PanelPole','2','','2014-07-27 03:19:05'),('apearson','PowerPanel','1',NULL,'3','PanelIPAddress','','12223','2014-07-27 03:19:05'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:19:14'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12','2014-07-27 03:19:14'),('apearson','PowerDistribution','1',NULL,'3','Label','UPS1/DB1/1','UPS1/DB1/2','2014-07-27 03:20:37'),('apearson','PowerDistribution','2',NULL,'3','Label','adadadad','UPS1/DB2/1','2014-07-27 03:21:09'),('apearson','PowerPanel','2',NULL,'3','PanelPole','2','','2014-07-27 03:21:53'),('apearson','PowerPanel','2',NULL,'3','IPAddress','12','12.12.12.1','2014-07-27 03:21:53'),('apearson','PowerPanel','2',NULL,'3','PanelIPAddress','','12.12.12.1','2014-07-27 03:21:53'),('apearson','Cabinet','1',NULL,'3','Location','TEST1','A5','2014-07-27 14:14:02'),('apearson','Cabinet','1',NULL,'3','Location','A5','DC2B2','2014-07-27 14:40:19'),('apearson','Cabinet','1',NULL,'3','Location','DC2B2','DC2B6A','2014-07-27 16:54:58'),('apearson','DataCenter','2',NULL,'1','DataCenterID','','2','2014-07-27 17:25:02'),('apearson','DataCenter','2',NULL,'1','Name','','UKDC2 test','2014-07-27 17:25:02'),('apearson','DataCenter','2',NULL,'1','SquareFootage','','1000','2014-07-27 17:25:02'),('apearson','DataCenter','2',NULL,'1','DeliveryAddress','','oo','2014-07-27 17:25:02'),('apearson','Zone','2',NULL,'1','ZoneID','','2','2014-07-27 17:25:53'),('apearson','Zone','2',NULL,'1','DataCenterID','','2','2014-07-27 17:25:53'),('apearson','Zone','2',NULL,'1','Description','','Gnd Floor','2014-07-27 17:25:53'),('apearson','Zone','2',NULL,'1','MapZoom','','100','2014-07-27 17:25:53'),('apearson','CabRow','2',NULL,'1','CabRowID','','2','2014-07-27 17:26:15'),('apearson','CabRow','2',NULL,'1','Name','','C1Y','2014-07-27 17:26:15'),('apearson','CabRow','2',NULL,'1','ZoneID','','2','2014-07-27 17:26:15'),('apearson','Cabinet','2',NULL,'1','CabinetID','','2','2014-07-27 17:26:53'),('apearson','Cabinet','2',NULL,'1','DataCenterID','','2','2014-07-27 17:26:53'),('apearson','Cabinet','2',NULL,'1','Location','','C1Y3','2014-07-27 17:26:53'),('apearson','Cabinet','2',NULL,'1','ZoneID','','2','2014-07-27 17:26:53'),('apearson','Cabinet','2',NULL,'1','CabinetHeight','','42','2014-07-27 17:26:53'),('apearson','Cabinet','2',NULL,'1','InstallationDate','','2014-07-27','2014-07-27 17:26:53'),('apearson','Cabinet','2',NULL,'1','FrontEdge','','Top','2014-07-27 17:26:53'),('apearson','Cabinet','3',NULL,'1','CabinetID','','3','2014-07-27 17:54:01'),('apearson','Cabinet','3',NULL,'1','DataCenterID','','1','2014-07-27 17:54:02'),('apearson','Cabinet','3',NULL,'1','Location','','A1','2014-07-27 17:54:02'),('apearson','Cabinet','3',NULL,'1','ZoneID','','1','2014-07-27 17:54:02'),('apearson','Cabinet','3',NULL,'1','CabRowID','','1','2014-07-27 17:54:03'),('apearson','Cabinet','3',NULL,'1','CabinetHeight','','12','2014-07-27 17:54:04'),('apearson','Cabinet','3',NULL,'1','Model','','jdj','2014-07-27 17:54:04'),('apearson','Cabinet','3',NULL,'1','InstallationDate','','2014-07-27','2014-07-27 17:54:04'),('apearson','Cabinet','3',NULL,'1','FrontEdge','','Top','2014-07-27 17:54:04');
/*!40000 ALTER TABLE `fac_GenericLog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Manufacturer`
--

DROP TABLE IF EXISTS `fac_Manufacturer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Manufacturer` (
  `ManufacturerID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) NOT NULL,
  PRIMARY KEY (`ManufacturerID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Manufacturer`
--

LOCK TABLES `fac_Manufacturer` WRITE;
/*!40000 ALTER TABLE `fac_Manufacturer` DISABLE KEYS */;
INSERT INTO `fac_Manufacturer` VALUES (2,'APC'),(3,'Geist'),(1,'Generic'),(4,'ServerTech');
/*!40000 ALTER TABLE `fac_Manufacturer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_MediaTypes`
--

DROP TABLE IF EXISTS `fac_MediaTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_MediaTypes` (
  `MediaID` int(11) NOT NULL AUTO_INCREMENT,
  `MediaType` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `ColorID` int(11) NOT NULL,
  PRIMARY KEY (`MediaID`),
  UNIQUE KEY `mediatype` (`MediaType`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_MediaTypes`
--

LOCK TABLES `fac_MediaTypes` WRITE;
/*!40000 ALTER TABLE `fac_MediaTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_MediaTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PDUStats`
--

DROP TABLE IF EXISTS `fac_PDUStats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PDUStats` (
  `PDUID` int(11) NOT NULL,
  `Wattage` int(11) NOT NULL,
  `LastRead` datetime DEFAULT NULL,
  `Amps` float(3,2) DEFAULT NULL,
  PRIMARY KEY (`PDUID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PDUStats`
--

LOCK TABLES `fac_PDUStats` WRITE;
/*!40000 ALTER TABLE `fac_PDUStats` DISABLE KEYS */;
INSERT INTO `fac_PDUStats` VALUES (1,100,'2014-07-27 04:13:02',NULL);
/*!40000 ALTER TABLE `fac_PDUStats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PanelSchedule`
--

DROP TABLE IF EXISTS `fac_PanelSchedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PanelSchedule` (
  `PanelID` int(11) NOT NULL AUTO_INCREMENT,
  `PolePosition` int(11) NOT NULL,
  `NumPoles` int(11) NOT NULL,
  `Label` varchar(80) NOT NULL,
  PRIMARY KEY (`PanelID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PanelSchedule`
--

LOCK TABLES `fac_PanelSchedule` WRITE;
/*!40000 ALTER TABLE `fac_PanelSchedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_PanelSchedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PanelStats`
--

DROP TABLE IF EXISTS `fac_PanelStats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PanelStats` (
  `PanelID` int(11) NOT NULL DEFAULT '0',
  `PanelPole` int(11) NOT NULL DEFAULT '0',
  `Wattage` int(11) DEFAULT NULL,
  `Amps` float(3,2) DEFAULT NULL,
  `LastRead` datetime DEFAULT NULL,
  PRIMARY KEY (`PanelID`,`PanelPole`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PanelStats`
--

LOCK TABLES `fac_PanelStats` WRITE;
/*!40000 ALTER TABLE `fac_PanelStats` DISABLE KEYS */;
INSERT INTO `fac_PanelStats` VALUES (1,1,12,0.10,'2014-07-27 04:14:02'),(1,2,48,0.40,'2014-07-27 04:14:02'),(1,3,24,0.20,'2014-07-27 04:14:02'),(2,1,240,1.00,'2014-07-27 04:14:02'),(2,2,960,4.00,'2014-07-27 04:14:02'),(2,3,480,2.00,'2014-07-27 04:14:02'),(3,1,25,0.10,'2014-07-27 04:14:02'),(3,2,100,0.40,'2014-07-27 04:14:02'),(3,3,50,0.20,'2014-07-27 04:14:02');
/*!40000 ALTER TABLE `fac_PanelStats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PatchConnection`
--

DROP TABLE IF EXISTS `fac_PatchConnection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PatchConnection` (
  `PanelDeviceID` int(11) NOT NULL,
  `PanelPortNumber` int(11) NOT NULL,
  `FrontEndpointDeviceID` int(11) DEFAULT NULL,
  `FrontEndpointPort` int(11) DEFAULT NULL,
  `RearEndpointDeviceID` int(11) DEFAULT NULL,
  `RearEndpointPort` int(11) DEFAULT NULL,
  `FrontNotes` varchar(80) DEFAULT NULL,
  `RearNotes` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`PanelDeviceID`,`PanelPortNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PatchConnection`
--

LOCK TABLES `fac_PatchConnection` WRITE;
/*!40000 ALTER TABLE `fac_PatchConnection` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_PatchConnection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Ports`
--

DROP TABLE IF EXISTS `fac_Ports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Ports` (
  `DeviceID` int(11) NOT NULL,
  `PortNumber` int(11) NOT NULL,
  `Label` varchar(40) NOT NULL,
  `MediaID` int(11) NOT NULL DEFAULT '0',
  `ColorID` int(11) NOT NULL DEFAULT '0',
  `PortNotes` varchar(80) NOT NULL,
  `ConnectedDeviceID` int(11) DEFAULT NULL,
  `ConnectedPort` int(11) DEFAULT NULL,
  `Notes` varchar(80) NOT NULL,
  PRIMARY KEY (`DeviceID`,`PortNumber`),
  UNIQUE KEY `LabeledPort` (`DeviceID`,`PortNumber`,`Label`),
  UNIQUE KEY `ConnectedDevice` (`ConnectedDeviceID`,`ConnectedPort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Ports`
--

LOCK TABLES `fac_Ports` WRITE;
/*!40000 ALTER TABLE `fac_Ports` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Ports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PowerConnection`
--

DROP TABLE IF EXISTS `fac_PowerConnection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PowerConnection` (
  `PDUID` int(11) NOT NULL,
  `PDUPosition` int(11) NOT NULL,
  `DeviceID` int(11) NOT NULL,
  `DeviceConnNumber` int(11) NOT NULL,
  UNIQUE KEY `PDUID` (`PDUID`,`PDUPosition`),
  UNIQUE KEY `DeviceID` (`DeviceID`,`DeviceConnNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PowerConnection`
--

LOCK TABLES `fac_PowerConnection` WRITE;
/*!40000 ALTER TABLE `fac_PowerConnection` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_PowerConnection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PowerDistribution`
--

DROP TABLE IF EXISTS `fac_PowerDistribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PowerDistribution` (
  `PDUID` int(11) NOT NULL AUTO_INCREMENT,
  `Label` varchar(40) NOT NULL,
  `CabinetID` int(11) NOT NULL,
  `TemplateID` int(11) NOT NULL,
  `IPAddress` varchar(254) NOT NULL,
  `SNMPCommunity` varchar(50) NOT NULL,
  `FirmwareVersion` varchar(40) NOT NULL,
  `PanelID` int(11) NOT NULL,
  `BreakerSize` int(11) NOT NULL,
  `PanelPole` int(11) NOT NULL,
  `InputAmperage` int(11) NOT NULL,
  `FailSafe` tinyint(1) NOT NULL,
  `PanelID2` int(11) NOT NULL,
  `PanelPole2` int(11) NOT NULL,
  PRIMARY KEY (`PDUID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PowerDistribution`
--

LOCK TABLES `fac_PowerDistribution` WRITE;
/*!40000 ALTER TABLE `fac_PowerDistribution` DISABLE KEYS */;
INSERT INTO `fac_PowerDistribution` VALUES (1,'UPS1/DB1/2',1,2,'','','',2,1,2,16,0,0,0),(2,'UPS1/DB2/1',1,3,'','','',3,1,1,1,0,0,0);
/*!40000 ALTER TABLE `fac_PowerDistribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PowerPanel`
--

DROP TABLE IF EXISTS `fac_PowerPanel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PowerPanel` (
  `PanelID` int(11) NOT NULL AUTO_INCREMENT,
  `PowerSourceID` int(11) NOT NULL,
  `PanelLabel` varchar(20) NOT NULL,
  `NumberOfPoles` int(11) NOT NULL,
  `MainBreakerSize` int(11) NOT NULL,
  `PanelVoltage` int(11) NOT NULL,
  `NumberScheme` enum('Odd/Even','Sequential') NOT NULL,
  `IPAddress` varchar(20) DEFAULT NULL,
  `PanelOID` varchar(100) DEFAULT NULL,
  `SNMPCommunity` varchar(50) DEFAULT NULL,
  `Managed` int(1) DEFAULT NULL,
  `ProcessingProfile` enum('SingleOIDWatts','SingleOIDAmperes','Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes') DEFAULT NULL,
  `SNMPVersion` enum('1','2c') DEFAULT NULL,
  `Multiplier` enum('0.1','1','10','100','1000') DEFAULT NULL,
  PRIMARY KEY (`PanelID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PowerPanel`
--

LOCK TABLES `fac_PowerPanel` WRITE;
/*!40000 ALTER TABLE `fac_PowerPanel` DISABLE KEYS */;
INSERT INTO `fac_PowerPanel` VALUES (1,1,'DB3',24,7,240,'Odd/Even','12223','freakyP3','public',1,'SingleOIDAmperes','2c','10'),(2,1,'DB1',21,1111,240,'Odd/Even','12.12.12.1','1232.131.31.31','joepublic',1,'SingleOIDAmperes','2c','1'),(3,1,'DB2',23,1111,250,'Odd/Even','21','12.3.3.12.43','ddsdfdsdff',1,'SingleOIDAmperes','2c','10');
/*!40000 ALTER TABLE `fac_PowerPanel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_PowerSource`
--

DROP TABLE IF EXISTS `fac_PowerSource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_PowerSource` (
  `PowerSourceID` int(11) NOT NULL AUTO_INCREMENT,
  `SourceName` varchar(80) NOT NULL,
  `DataCenterID` int(11) NOT NULL,
  `IPAddress` varchar(254) NOT NULL,
  `Community` varchar(40) NOT NULL,
  `LoadOID` varchar(80) NOT NULL,
  `Capacity` int(11) NOT NULL,
  PRIMARY KEY (`PowerSourceID`),
  KEY `DataCenterID` (`DataCenterID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_PowerSource`
--

LOCK TABLES `fac_PowerSource` WRITE;
/*!40000 ALTER TABLE `fac_PowerSource` DISABLE KEYS */;
INSERT INTO `fac_PowerSource` VALUES (1,'ups1',1,'','','',800);
/*!40000 ALTER TABLE `fac_PowerSource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_RackRequest`
--

DROP TABLE IF EXISTS `fac_RackRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_RackRequest` (
  `RequestID` int(11) NOT NULL AUTO_INCREMENT,
  `RequestorID` int(11) NOT NULL,
  `RequestTime` datetime NOT NULL,
  `CompleteTime` datetime NOT NULL,
  `Label` varchar(40) NOT NULL,
  `SerialNo` varchar(40) NOT NULL,
  `MfgDate` date NOT NULL,
  `AssetTag` varchar(40) NOT NULL,
  `ESX` tinyint(1) NOT NULL,
  `Owner` int(11) NOT NULL,
  `DeviceHeight` int(11) NOT NULL,
  `EthernetCount` int(11) NOT NULL,
  `VLANList` varchar(80) NOT NULL,
  `SANCount` int(11) NOT NULL,
  `SANList` varchar(80) NOT NULL,
  `DeviceClass` varchar(80) NOT NULL,
  `DeviceType` enum('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure') NOT NULL,
  `LabelColor` varchar(80) NOT NULL,
  `CurrentLocation` varchar(120) NOT NULL,
  `SpecialInstructions` text NOT NULL,
  PRIMARY KEY (`RequestID`),
  KEY `RequestorID` (`RequestorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_RackRequest`
--

LOCK TABLES `fac_RackRequest` WRITE;
/*!40000 ALTER TABLE `fac_RackRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_RackRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_SensorTemplate`
--

DROP TABLE IF EXISTS `fac_SensorTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_SensorTemplate` (
  `TemplateID` int(11) NOT NULL AUTO_INCREMENT,
  `ManufacturerID` int(11) NOT NULL,
  `Name` varchar(80) NOT NULL,
  `SNMPVersion` enum('1','2c') NOT NULL DEFAULT '2c',
  `TemperatureOID` varchar(256) NOT NULL,
  `HumidityOID` varchar(256) NOT NULL,
  `TempMultiplier` float NOT NULL DEFAULT '1',
  `HumidityMultiplier` float NOT NULL DEFAULT '1',
  PRIMARY KEY (`TemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_SensorTemplate`
--

LOCK TABLES `fac_SensorTemplate` WRITE;
/*!40000 ALTER TABLE `fac_SensorTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_SensorTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Slots`
--

DROP TABLE IF EXISTS `fac_Slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Slots` (
  `TemplateID` int(11) NOT NULL,
  `Position` int(11) NOT NULL,
  `BackSide` tinyint(1) NOT NULL,
  `X` int(11) DEFAULT NULL,
  `Y` int(11) DEFAULT NULL,
  `W` int(11) DEFAULT NULL,
  `H` int(11) DEFAULT NULL,
  PRIMARY KEY (`TemplateID`,`Position`,`BackSide`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Slots`
--

LOCK TABLES `fac_Slots` WRITE;
/*!40000 ALTER TABLE `fac_Slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Supplies`
--

DROP TABLE IF EXISTS `fac_Supplies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Supplies` (
  `SupplyID` int(11) NOT NULL AUTO_INCREMENT,
  `PartNum` varchar(40) NOT NULL,
  `PartName` varchar(80) NOT NULL,
  `MinQty` int(11) NOT NULL,
  `MaxQty` int(11) NOT NULL,
  PRIMARY KEY (`SupplyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Supplies`
--

LOCK TABLES `fac_Supplies` WRITE;
/*!40000 ALTER TABLE `fac_Supplies` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_Supplies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_SupplyBin`
--

DROP TABLE IF EXISTS `fac_SupplyBin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_SupplyBin` (
  `BinID` int(11) NOT NULL AUTO_INCREMENT,
  `Location` varchar(40) NOT NULL,
  PRIMARY KEY (`BinID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_SupplyBin`
--

LOCK TABLES `fac_SupplyBin` WRITE;
/*!40000 ALTER TABLE `fac_SupplyBin` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_SupplyBin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_SwitchConnection`
--

DROP TABLE IF EXISTS `fac_SwitchConnection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_SwitchConnection` (
  `SwitchDeviceID` int(11) NOT NULL,
  `SwitchPortNumber` int(11) NOT NULL,
  `EndpointDeviceID` int(11) NOT NULL,
  `EndpointPort` int(11) NOT NULL,
  `Notes` varchar(80) NOT NULL,
  PRIMARY KEY (`SwitchDeviceID`,`SwitchPortNumber`),
  UNIQUE KEY `EndpointDeviceID` (`EndpointDeviceID`,`EndpointPort`),
  UNIQUE KEY `SwitchDeviceID` (`SwitchDeviceID`,`SwitchPortNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_SwitchConnection`
--

LOCK TABLES `fac_SwitchConnection` WRITE;
/*!40000 ALTER TABLE `fac_SwitchConnection` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_SwitchConnection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Tags`
--

DROP TABLE IF EXISTS `fac_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Tags` (
  `TagID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(128) NOT NULL,
  PRIMARY KEY (`TagID`),
  UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Tags`
--

LOCK TABLES `fac_Tags` WRITE;
/*!40000 ALTER TABLE `fac_Tags` DISABLE KEYS */;
INSERT INTO `fac_Tags` VALUES (2,'NoReport'),(1,'Report');
/*!40000 ALTER TABLE `fac_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_TemplatePorts`
--

DROP TABLE IF EXISTS `fac_TemplatePorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_TemplatePorts` (
  `TemplateID` int(11) NOT NULL,
  `PortNumber` int(11) NOT NULL,
  `Label` varchar(40) NOT NULL,
  `MediaID` int(11) NOT NULL DEFAULT '0',
  `ColorID` int(11) NOT NULL DEFAULT '0',
  `PortNotes` varchar(80) NOT NULL,
  PRIMARY KEY (`TemplateID`,`PortNumber`),
  UNIQUE KEY `LabeledPort` (`TemplateID`,`PortNumber`,`Label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_TemplatePorts`
--

LOCK TABLES `fac_TemplatePorts` WRITE;
/*!40000 ALTER TABLE `fac_TemplatePorts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_TemplatePorts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_User`
--

DROP TABLE IF EXISTS `fac_User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_User` (
  `UserID` varchar(80) NOT NULL,
  `Name` varchar(80) NOT NULL,
  `AdminOwnDevices` tinyint(1) NOT NULL,
  `ReadAccess` tinyint(1) NOT NULL,
  `WriteAccess` tinyint(1) NOT NULL,
  `DeleteAccess` tinyint(1) NOT NULL,
  `ContactAdmin` tinyint(1) NOT NULL,
  `RackRequest` tinyint(1) NOT NULL,
  `RackAdmin` tinyint(1) NOT NULL,
  `SiteAdmin` tinyint(1) NOT NULL,
  `Disabled` tinyint(1) NOT NULL,
  `ReadBilling` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_User`
--

LOCK TABLES `fac_User` WRITE;
/*!40000 ALTER TABLE `fac_User` DISABLE KEYS */;
INSERT INTO `fac_User` VALUES ('apearson','Default Admin',1,1,1,1,1,1,1,1,0,1);
/*!40000 ALTER TABLE `fac_User` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_VMInventory`
--

DROP TABLE IF EXISTS `fac_VMInventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_VMInventory` (
  `VMIndex` int(11) NOT NULL AUTO_INCREMENT,
  `DeviceID` int(11) NOT NULL,
  `LastUpdated` datetime NOT NULL,
  `vmID` int(11) NOT NULL,
  `vmName` varchar(80) NOT NULL,
  `vmState` varchar(80) NOT NULL,
  `Owner` int(11) NOT NULL,
  PRIMARY KEY (`VMIndex`),
  KEY `ValidDevice` (`DeviceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_VMInventory`
--

LOCK TABLES `fac_VMInventory` WRITE;
/*!40000 ALTER TABLE `fac_VMInventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `fac_VMInventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fac_Zone`
--

DROP TABLE IF EXISTS `fac_Zone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fac_Zone` (
  `ZoneID` int(11) NOT NULL AUTO_INCREMENT,
  `DataCenterID` int(11) NOT NULL,
  `Description` varchar(120) NOT NULL,
  `MapX1` int(11) NOT NULL,
  `MapY1` int(11) NOT NULL,
  `MapX2` int(11) NOT NULL,
  `MapY2` int(11) NOT NULL,
  `MapZoom` int(11) NOT NULL DEFAULT '100',
  PRIMARY KEY (`ZoneID`),
  KEY `DataCenterID` (`DataCenterID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fac_Zone`
--

LOCK TABLES `fac_Zone` WRITE;
/*!40000 ALTER TABLE `fac_Zone` DISABLE KEYS */;
INSERT INTO `fac_Zone` VALUES (1,1,'ZONE TEST',0,0,0,0,100),(2,2,'Gnd Floor',0,0,0,0,100);
/*!40000 ALTER TABLE `fac_Zone` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-07-29  1:18:45
