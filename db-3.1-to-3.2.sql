--
-- Add this again because it was left out of the create.sql for the 3.1 release
--
ALTER TABLE fac_CabRow ADD COLUMN CabOrder ENUM( 'ASC', 'DESC' ) NOT NULL DEFAULT 'ASC';

--
-- Add the picture fields for front/rear views, and front/rear slots in device template 
--
ALTER TABLE fac_DeviceTemplate
	ADD COLUMN FrontPictureFile VARCHAR(45) NOT NULL AFTER Notes,
	ADD COLUMN RearPictureFile VARCHAR(45) NOT NULL AFTER FrontPictureFile,
	ADD COLUMN ChassisSlots SMALLINT(6) NOT NULL AFTER RearPictureFile,
	ADD COLUMN RearChassisSlots SMALLINT(6) NOT NULL AFTER ChassisSlots;

--
-- Slots table content the coodinates os slots in a picture of a chassis device template 
--
CREATE TABLE fac_Slots (
	TemplateID INT(11) NOT NULL,
	Position INT(11) NOT NULL,
	BackSide TINYINT(1) NOT NULL,
	X INT(11) NULL,
	Y INT(11) NULL,
	W INT(11) NULL,
	H INT(11) NULL,
	PRIMARY KEY (TemplateID, Position, BackSide)
) ENGINE = InnoDB;

--
-- Add field to indicate the intake/front edge of a cabinet
--

ALTER TABLE fac_Cabinet ADD COLUMN FrontEdge ENUM("Top","Right","Bottom","Left") NOT NULL DEFAULT "Top" AFTER MapX2;

--
-- TempaltePorts table content the ports of a device template 
--
CREATE TABLE IF NOT EXISTS `fac_TemplatePorts` (
  `TemplateID` int(11) NOT NULL,
  `PortNumber` int(11) NOT NULL,
  `Label` varchar(40) NOT NULL,
  `MediaID` int(11) NOT NULL DEFAULT '0',
  `ColorID` int(11) NOT NULL DEFAULT '0',
  `PortNotes` varchar(80) NOT NULL,
  PRIMARY KEY (`TemplateID`,`PortNumber`),
  UNIQUE KEY `LabeledPort` (`TemplateID`,`PortNumber`,`Label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Disable MediaType Enforcing, again.
--

UPDATE fac_Config SET Value='Disabled' WHERE Parameter='MediaEnforce' LIMIT 1;

--
-- Table structure for table `fac_GenericLog`
--

CREATE TABLE `fac_GenericLog` (
  UserID varchar(80) NOT NULL,
  Class varchar(40) NOT NULL,
  ObjectID int(11) NOT NULL,
  ChildID int(11) DEFAULT NULL,
  Action varchar(40) NOT NULL,
  Property varchar(40) NOT NULL,
  OldVal varchar(255) NOT NULL,
  NewVal varchar(255) NOT NULL,
  Time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Add configuration items for path weights
-- I honestly don't understand this so I didn't fill in what the UnitOfMeasure is 
--

INSERT INTO fac_Config VALUES ('path_weight_cabinet', '1', '', 'int', '1'),
	('path_weight_rear', '1', '', 'int', '1'),
	('path_weight_row', '4', '', 'int', '4');
