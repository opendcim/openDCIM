<?php
/*
	openDCIM

	This is the main class library for the openDCIM application, which
	is a PHP/Web based data center infrastructure management system.

	This application was originally written by Scott A. Milliken while
	employed at Vanderbilt University in Nashville, TN, as the
	Data Center Manager, and released under the GNU GPL.

	Copyright (C) 2011 Scott A. Milliken

	This program is free software:  you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published
	by the Free Software Foundation, version 3.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	For further details on the license, see http://www.gnu.org/licenses
*/
class DeviceTemplate {
	var $TemplateID;
	var $ManufacturerID;
	var $Model;
	var $Height;
	var $Weight;
	var $Wattage;
	var $DeviceType;
	var $PSCount;
	var $NumPorts;
	var $Notes;
	var $FrontPictureFile;
	var $RearPictureFile;
	var $ChassisSlots;
	var $RearChassisSlots;
	var $SNMPVersion;
	var $CustomValues;
	var $GlobalID;
	var $ShareToRepo;
	var $KeepLocal;
    
	public function __construct($dtid=false){
		if($dtid){
			$this->TemplateID=intval($dtid);
		}
		return $this;
	}

	function MakeSafe(){
		$validDeviceTypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU','Sensor');
		$validSNMPVersions=array(1,'2c',3);

		// Instead of defaulting to v2c for snmp we'll default to whatever the system default is
		global $config;

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=sanitize($this->Model);
		$this->Height=intval($this->Height);
		$this->Weight=intval($this->Weight);
		$this->Wattage=intval($this->Wattage);
		$this->DeviceType=(in_array($this->DeviceType, $validDeviceTypes))?$this->DeviceType:'Server';
		$this->PSCount=intval($this->PSCount);
		$this->NumPorts=intval($this->NumPorts);
        $this->Notes=sanitize($this->Notes,false);
        $this->FrontPictureFile=sanitize($this->FrontPictureFile);
	    $this->RearPictureFile=sanitize($this->RearPictureFile);
		$this->ChassisSlots=intval($this->ChassisSlots);
		$this->RearChassisSlots=intval($this->RearChassisSlots);
		$this->SNMPVersion=(in_array($this->SNMPVersion, $validSNMPVersions))?$this->SNMPVersion:$config->ParameterArray["SNMPVersion"];
		$this->GlobalID=intval($this->GlobalID);
		$this->ShareToRepo=intval($this->ShareToRepo);
		$this->KeepLocal=intval($this->KeepLocal);
	}

	function MakeDisplay(){
		$this->Model=stripslashes($this->Model);
        $this->Notes=stripslashes($this->Notes);
        $this->FrontPictureFile=stripslashes($this->FrontPictureFile);
	    $this->RearPictureFile=stripslashes($this->RearPictureFile);
	}

	static function RowToObject($row,$extendmodel=true){
		$Template=new DeviceTemplate();
		$Template->TemplateID=$row["TemplateID"];
		$Template->ManufacturerID=$row["ManufacturerID"];
		$Template->Model=$row["Model"];
		$Template->Height=$row["Height"];
		$Template->Weight=$row["Weight"];
		$Template->Wattage=$row["Wattage"];
		$Template->DeviceType=$row["DeviceType"];
		$Template->PSCount=$row["PSCount"];
		$Template->NumPorts=$row["NumPorts"];
        $Template->Notes=$row["Notes"];
        $Template->FrontPictureFile=$row["FrontPictureFile"];
        $Template->RearPictureFile=$row["RearPictureFile"];
		$Template->ChassisSlots=$row["ChassisSlots"];
		$Template->RearChassisSlots=$row["RearChassisSlots"];
		$Template->SNMPVersion=$row["SNMPVersion"];
		$Template->GlobalID = $row["GlobalID"];
		$Template->ShareToRepo = $row["ShareToRepo"];
		$Template->KeepLocal = $row["KeepLocal"];
        $Template->MakeDisplay();
		$Template->GetCustomValues();

		if($extendmodel){
			// Extend our device model
			if($Template->DeviceType=="CDU"){
				$cdut=new CDUTemplate();
				$cdut->TemplateID=$Template->TemplateID;
				$cdut->GetTemplate();
				foreach($cdut as $prop => $val){
					$Template->$prop=$val;
				}
			}
			if($Template->DeviceType=="Sensor"){
				$st=new SensorTemplate();
				$st->TemplateID=$Template->TemplateID;
				$st->GetTemplate();
				foreach($st as $prop => $val){
					$Template->$prop=$val;
				}
			}
		}
		return $Template;
	}
  
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function clearShareFlag() {
		$st = $this->prepare( "update fac_DeviceTemplate set ShareToRepo=0 where TemplateID=:TemplateID" );
		$st->execute( array( ":TemplateID"=>$this->TemplateID ) );
	}
	
	function CreateTemplate(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			PSCount=$this->PSCount, NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
			FrontPictureFile=\"$this->FrontPictureFile\", RearPictureFile=\"$this->RearPictureFile\",
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots, SNMPVersion=\"$this->SNMPVersion\",
			GlobalID=$this->GlobalID, ShareToRepo=$this->ShareToRepo, KeepLocal=$this->KeepLocal;";

		if(!$dbh->exec($sql)){
			error_log( "SQL Error: " . $sql );
			return false;
		}else{
			$this->TemplateID=$dbh->lastInsertId();
			if($this->DeviceType=="CDU"){
				// If this is a cdu make the corresponding other hidden template
				$cdut=new CDUTemplate();
				foreach($cdut as $prop => $val){
					if(isset($this->$prop)){
						$cdut->$prop=$this->$prop;
					}
				}
				$cdut->CreateTemplate($this->TemplateID);
			}

			if($this->DeviceType=="Sensor"){
				// If this is a sensor make the corresponding other hidden template
				$st=new SensorTemplate();
				foreach($st as $prop => $val){
					if(isset($this->$prop)){
						$st->$prop=$this->$prop;
					}
				}
				$st->CreateTemplate($this->TemplateID);
			}

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			$this->MakeDisplay();
			return true;
		}
	}
  
	function UpdateTemplate(){
		$this->MakeSafe();
        $sql="UPDATE fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID,
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			PSCount=$this->PSCount, NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
			FrontPictureFile=\"$this->FrontPictureFile\", RearPictureFile=\"$this->RearPictureFile\",
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots, SNMPVersion=\"$this->SNMPVersion\",
			GlobalID=$this->GlobalID, ShareToRepo=$this->ShareToRepo, KeepLocal=$this->KeepLocal
			WHERE TemplateID=$this->TemplateID;";

		$old=new DeviceTemplate();
		$old->TemplateID=$this->TemplateID;
		$old->GetTemplateByID();

		if($old->DeviceType=="CDU" && $this->DeviceType!=$old->DeviceType){
			// Template changed from CDU to something else, clean up the mess
			$cdut=new CDUTemplate();
			$cdut->TemplateID=$this->TemplateID;
			$cdut->DeleteTemplate();
		}elseif($this->DeviceType=="CDU" && $this->DeviceType!=$old->DeviceType){
			// Template changed to CDU from something else, make the extra stuff
			$cdut=new CDUTemplate();
			$cdut->Model=$this->Model;
			$cdut->ManufacturerID=$this->ManufacturerID;
			$cdut->CreateTemplate($this->TemplateID);
		}

		if($old->DeviceType=="Sensor" && $this->DeviceType!=$old->DeviceType){
			// Template changed from Sensor to something else, clean up the mess
			$st=new SensorTemplate();
			$st->TemplateID=$this->TemplateID;
			$st->DeleteTemplate();
		}elseif($this->DeviceType=="Sensor" && $this->DeviceType!=$old->DeviceType){
			// Template changed to Sensor from something else, make the extra stuff
			$st=new SensorTemplate();
			$st->Model=$this->Model;
			$st->ManufacturerID=$this->ManufacturerID;
			$st->CreateTemplate($this->TemplateID);
		}

		if(!$this->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
			$this->MakeDisplay();
			return true;
		}
	}
  
	function DeleteTemplate(){
		$this->MakeSafe();

		// If we're removing the template clean up the children
		$this->DeleteSlots();
		$this->DeletePorts();

		$sql="DELETE FROM fac_DeviceTemplate WHERE TemplateID=$this->TemplateID;";
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->exec($sql);
	}

	function Search($indexedbyid=false,$loose=false){
		$o=new stdClass();
		// Store any values that have been added before we make them safe 
		foreach($this as $prop => $val){
			if(isset($val)){
				$o->$prop=$val;
			}
		}

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="WHERE a.ManufacturerID=b.ManufacturerID";

		foreach($o as $prop => $val){
			extendsql('a.'.$prop,$this->$prop,$sqlextend,$loose);
		}

		// The join is purely to sort the templates by the manufacturer's name
		$sql="SELECT a.* FROM fac_DeviceTemplate a, fac_Manufacturer b 
			$sqlextend ORDER BY Name ASC, Model ASC;";

		$templateList=array();

		foreach($this->query($sql) as $row){
			if($indexedbyid){
				$templateList[$row["TemplateID"]]=DeviceTemplate::RowToObject($row);
			}else{
				$templateList[]=DeviceTemplate::RowToObject($row);
			}
		}

		return $templateList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}

	function GetTemplateByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DeviceTemplate WHERE TemplateID=$this->TemplateID;";

		//JMGA Reset object in case of a lookup failure
		$this->ManufacturerID=0;
		$this->Model="";
		$this->Height=0;
		$this->Weight=0;
		$this->Wattage=0;
		$this->DeviceType='Server';
		$this->PSCount=0;
		$this->NumPorts=0;
        $this->Notes="";
        $this->FrontPictureFile="";
	    $this->RearPictureFile="";
		$this->ChassisSlots=0;
		$this->RearChassisSlots=0;
		$this->SNMPVersion="";
		$this->GlobalID=0;
		$this->ShareToRepo=false;
		$this->KeepLocal=false;
		// Reset object in case of a lookup failure
		//foreach($this as $prop => $value){
		//	$value=($prop!='TemplateID')?null:$value;
		//}
		
		if($row=$this->query($sql)->fetch()){
			foreach(DeviceTemplate::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
  
	static function GetTemplateList( $indexed=false ){
		global $dbh;

		$sql="SELECT * FROM fac_DeviceTemplate a, fac_Manufacturer b WHERE 
			a.ManufacturerID=b.ManufacturerID ORDER BY Name ASC, Model ASC;";

		$templateList=array();
		foreach($dbh->query($sql) as $row){
			if ( $indexed ) {
				$templateList[$row["TemplateID"]]=DeviceTemplate::RowToObject($row);
			} else {
				$templateList[]=DeviceTemplate::RowToObject($row);
			}
		}

		return $templateList;
	}
	
	function GetTemplateListByManufacturer(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DeviceTemplate a, fac_Manufacturer b WHERE 
			a.ManufacturerID=b.ManufacturerID AND a.ManufacturerID=$this->ManufacturerID 
			ORDER BY Name ASC, Model ASC;";

		$templateList=array();
		foreach($this->query($sql) as $row){
			$templateList[]=DeviceTemplate::RowToObject($row);
		}

		return $templateList;
	}

	function GetTemplateShareList() {
		$sql = "select * from fac_DeviceTemplate where ManufacturerID in (select ManufacturerID from fac_Manufacturer where GlobalID>0) and ShareToRepo=true order by ManufacturerID ASC";
		
		$templateList = array();
		foreach( $this->query($sql) as $row ) {
			$templateList[]=DeviceTemplate::RowToObject($row);
		}
		
		return $templateList;
	}

    /**
     * Return a list of the templates indexed by the TemplateID
     *
     * @return multitype:DeviceTemplate
     */
    public static function getTemplateListIndexedbyID() {
        global $dbh;
        $templateList = array();
        $stmt = $dbh->prepare('select * from fac_DeviceTemplate');
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $devTempl = DeviceTemplate::RowToObject($row);
            $templateList[$devTempl->TemplateID] = $devTempl;
        }
        return $templateList;
    }

	function GetMissingMfgDates(){
		$this->MakeSafe();

		$sql="SELECT a.* FROM fac_Device a, fac_DeviceTemplate b WHERE
			a.TemplateID=b.TemplateID AND b.ManufacturerID=$this->ManufacturerID AND 
			a.MfgDate<'1970-01-01'";

		$devList=array();
		foreach($this->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		$this->MakeDisplay();
		return $devList;
	}

	function UpdateDevices(){
		/* This will cause every device with a TemplateID matching this one to display
		   the updated values.  We are not touching DeviceType or NumPorts at this time
		   because those have alternate side effects that i'm not sure we really need
		   to address here
		*/
		$this->MakeSafe();

		$sql="UPDATE fac_Device SET Height=$this->Height, NominalWatts=$this->Wattage, 
			PowerSupplyCount=$this->PSCount, ChassisSlots=$this->ChassisSlots, Weight=$this->Weight,
			RearChassisSlots=$this->RearChassisSlots, SNMPVersion=\"$this->SNMPVersion\" WHERE TemplateID=$this->TemplateID;";

		return $this->query($sql);
	}
	
	function DeleteSlots(){
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_Slots WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}
	
	function DeletePorts(){
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}

	function DeletePowerPorts(){
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_TemplatePowerPorts WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}

	// This was a double of the DeletePorts function need to come back later
	// and see if this thing is even being used.	
	function removePorts(){
		return $this->DeletePorts();
	}
	
	function ExportTemplate(){
		$this->MakeSafe();

		//Get manufacturer name
		$manufacturer=new Manufacturer();
		$manufacturer->ManufacturerID=$this->ManufacturerID;
		$manufacturer->GetManufacturerByID();
		
		$fileContent='<?xml version="1.0" encoding="UTF-8"?>
<Template xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:noNamespaceSchemaLocation="openDCIMdevicetemplate.xsd">
	<ManufacturerName>'.$manufacturer->Name.'</ManufacturerName>
	<TemplateReg>
		<Model>'.$this->Model.'</Model> 
	  <Height>'.$this->Height.'</Height> 
	  <Weight>'.$this->Weight.'</Weight> 
	  <Wattage>'.$this->Wattage.'</Wattage> 
	  <DeviceType>'.$this->DeviceType.'</DeviceType> 
	  <PSCount>'.$this->PSCount.'</PSCount> 
	  <NumPorts>'.$this->NumPorts.'</NumPorts> 
	  <Notes>'.$this->Notes.'</Notes> 
	  <FrontPictureFile>'.$this->FrontPictureFile.'</FrontPictureFile> 
	  <RearPictureFile>'.$this->RearPictureFile.'</RearPictureFile> 
	  <SNMPVersion>'.$this->SNMPVersion.'</SNMPVersion>
	  <ChassisSlots>'.$this->ChassisSlots.'</ChassisSlots> 
	  <RearChassisSlots>'.$this->RearChassisSlots.'</RearChassisSlots> 
	</TemplateReg>';

		//Slots
		for ($i=1; $i<=$this->ChassisSlots;$i++){
			$slot=new Slot();
			$slot->TemplateID=$this->TemplateID;
			$slot->Position=$i;
			$slot->BackSide=False;
			$slot->GetSlot();
			$fileContent.='
	<SlotReg>
		<Position>'.$slot->Position.'</Position>
		<BackSide>0</BackSide>
		<X>'.$slot->X.'</X>
		<Y>'.$slot->Y.'</Y>
		<W>'.$slot->W.'</W>
		<H>'.$slot->H.'</H>
	</SlotReg>';
		}
		for ($i=1; $i<=$this->RearChassisSlots;$i++){
			$slot=new Slot();
			$slot->TemplateID=$this->TemplateID;
			$slot->Position=$i;
			$slot->BackSide=True;
			$slot->GetSlot();
			$fileContent.='
	<SlotReg>
		<Position>'.$slot->Position.'</Position>
		<BackSide>1</BackSide>
		<X>'.$slot->X.'</X>
		<Y>'.$slot->Y.'</Y>
		<W>'.$slot->W.'</W>
		<H>'.$slot->H.'</H>
	</SlotReg>';
		}
		//Ports
		for ($i=1; $i<=$this->NumPorts;$i++){
			$tport=new TemplatePorts();
			$tport->TemplateID=$this->TemplateID;
			$tport->PortNumber=$i;
			$tport->GetPort();
			//Get media name
			$mt=new MediaTypes();
			$mt->MediaID=$tport->MediaID;
			$mt->GetType();
			//Get color name
			$cc=new ColorCoding();
			$cc->ColorID=$tport->ColorID;
			$cc->GetCode();
			$fileContent.='
	<PortReg>
		<PortNumber>'.$tport->PortNumber.'</PortNumber>
		<Label>'.$tport->Label.'</Label>
		<PortMedia>'.$mt->MediaType.'</PortMedia>
		<PortColor>'.$cc->Name.'</PortColor>
		<Notes>'.$tport->Notes.'</Notes>
	</PortReg>';
		}
		//Pictures
		if ($this->FrontPictureFile!="" && file_exists("pictures/".$this->FrontPictureFile)){
			$im=file_get_contents("pictures/".$this->FrontPictureFile);
			$fileContent.='
	<FrontPicture>
'.base64_encode($im).'
	</FrontPicture>';
		}
		if ($this->RearPictureFile!="" && file_exists("pictures/".$this->RearPictureFile)){
			$im=file_get_contents("pictures/".$this->RearPictureFile);
			$fileContent.='
	<RearPicture>
'.base64_encode($im).'
	</RearPicture>';
		}
		
		//End of template
		$fileContent.='
</Template>';
		
		//download file
		download_file_from_string($fileContent, str_replace(' ', '', $manufacturer->Name."-".$this->Model).".xml");
		
		return true;
	}

	function ImportTemplate($file){
		$result=array();
		$result["status"]="";
		$result["log"]=array();
		$ierror=0;
		
		//validate xml template file with openDCIMdevicetemplate.xsd
		libxml_use_internal_errors(true);
		$xml=new XMLReader();
		$xml->open($file);
		$resp=$xml->setSchema ("openDCIMdevicetemplate.xsd");
		while (@$xml->read()) {}; // empty loop
		$errors = libxml_get_errors();
		if (count($errors)>0){
			$result["status"]=__("No valid file");
			foreach ($errors as $error) {
				$result["log"][$ierror++]=$error->message;
			}
			return $result;
		}
    libxml_clear_errors();
		$xml->close();
		
		//read xml template file
		$xmltemplate=simplexml_load_file($file);
		
		//manufacturer
		$manufacturer=new Manufacturer();
		$manufacturer->Name=transform($xmltemplate->ManufacturerName);
		if (!$manufacturer->GetManufacturerByName()){
			//New Manufacturer
			$manufacturer->CreateManufacturer();
		}
		$template=new DeviceTemplate();
		$template->ManufacturerID=$manufacturer->ManufacturerID;
		$template->Model=transform($xmltemplate->TemplateReg->Model);
		$template->Height=$xmltemplate->TemplateReg->Height;
		$template->Weight=$xmltemplate->TemplateReg->Weight;
		$template->Wattage=$xmltemplate->TemplateReg->Wattage;
		$template->DeviceType=$xmltemplate->TemplateReg->DeviceType;
		$template->PSCount=$xmltemplate->TemplateReg->PSCount;
		$template->NumPorts=$xmltemplate->TemplateReg->NumPorts;
		$template->Notes=trim($xmltemplate->TemplateReg->Notes);
		$template->Notes=($template->Notes=="<br>")?"":$template->Notes;
		$template->FrontPictureFile=$xmltemplate->TemplateReg->FrontPictureFile;
		$template->RearPictureFile=$xmltemplate->TemplateReg->RearPictureFile;
		$template->SNMPVersion=$xmltemplate->TemplateReg->SNMPVersion;
		$template->ChassisSlots=($template->DeviceType=="Chassis")?$xmltemplate->TemplateReg->ChassisSlots:0;
		$template->RearChassisSlots=($template->DeviceType=="Chassis")?$xmltemplate->TemplateReg->RearChassisSlots:0;
		
		//Check if picture files exist
		if ($template->FrontPictureFile!="" && file_exists("pictures/".$template->FrontPictureFile)){
			$result["status"]=__("Import Error");
			$result["log"][0]= __("Front picture file already exists");
			return $result;
		}
		if ($template->RearPictureFile!="" && file_exists("pictures/".$template->RearPictureFile)){
			$result["status"]=__("Import Error");
			$result["log"][0]= __("Rear picture file already exists");
			return $result;
		}
		
		//create the template
		if (!$template->CreateTemplate()){
			$result["status"]=__("Import Error");
			$result["log"][0]=__("An error has occurred creating the template.<br>Possibly there is already a template of the same manufacturer and model");
			return $result;
		}
		
		//get template to this object
		$this->TemplateID=$template->TemplateID;
		$this->GetTemplateByID();
		
		//slots
		foreach ($xmltemplate->SlotReg as $xmlslot){
			$slot=new Slot();
			$slot->TemplateID=$this->TemplateID;
			$slot->Position=intval($xmlslot->Position);
			$slot->BackSide=intval($xmlslot->BackSide);
			$slot->X=intval($xmlslot->X);
			$slot->Y=intval($xmlslot->Y);
			$slot->W=intval($xmlslot->W);
			$slot->H=intval($xmlslot->H);
			if (($slot->Position<=$this->ChassisSlots && !$slot->BackSide) || ($slot->Position<=$this->RearChassisSlots && $slot->BackSide)){
				if(!$slot->CreateSlot()){
					$result["status"]=__("Import Warning");
					$result["log"][$ierror++]=sprintf(__("An error has occurred creating the slot %s"),$slot->Position."-".($slot->BackSide)?__("Rear"):__("Front"));
				}
			}else{
				$result["status"]=__("Import Warning");
				$result["log"][$ierror++]=sprintf(__("Ignored slot %s"),$slot->Position."-".($slot->BackSide)?__("Rear"):__("Front"));
			}
		}
		
		//ports
		foreach ($xmltemplate->PortReg as $xmlport){
			//media type
			$mt=new MediaTypes();
			$mt->MediaType=transform($xmlport->PortMedia);
			if (!$mt->GetTypeByName()){
				//New media type
				$mt->CreateType();
			}
			
			//color
			$cc=new ColorCoding();
			$cc->Name=transform($xmlport->PortColor);
			if (!$cc->GetCodeByName()){
				//New color
				$cc->CreateCode();
			}
			
			$tport=new TemplatePorts();
			$tport->TemplateID=$this->TemplateID;
			$tport->PortNumber=intval($xmlport->PortNumber);
			$tport->Label=$xmlport->Label;
			$tport->MediaID=$mt->MediaID; 
			$tport->ColorID=$cc->ColorID;
			$tport->Notes=$xmlport->Notes;
			if ($tport->PortNumber<=$this->NumPorts){
				if(!$tport->CreatePort()){
					$result["status"]=__("Import Warning");
					$result["log"][$ierror++]=sprintf(__("An error has occurred creating the port %s"),$tport->PortNumber);
				}
			}else{
				$result["status"]=__("Import Warning");
				$result["log"][$ierror++]=sprintf(__("Ignored port %s"),$tport->PortNumber);
			}
		}

		//files
		if($this->FrontPictureFile!=""){
			$im=base64_decode($xmltemplate->FrontPicture);
			file_put_contents("pictures/".$this->FrontPictureFile, $im);
		}
		if($this->RearPictureFile!="" && $this->RearPictureFile!=$this->FrontPictureFile){
			$im=base64_decode($xmltemplate->RearPicture);
			file_put_contents("pictures/".$this->RearPictureFile, $im);
		}
		return $result;
	}
	
	function GetCustomValues() {
		$this->MakeSafe();

		$tdca = array();
		//Table join to make it where we can half ass sort the custom attributes based on the label data
		$sql="SELECT a.Label, v.TemplateID, v.AttributeID, v.Required, v.Value FROM 
			fac_DeviceTemplateCustomValue v, fac_DeviceCustomAttribute a WHERE 
			a.AttributeID=v.AttributeID AND TemplateID=$this->TemplateID ORDER BY Label, 
			AttributeID;";
		foreach($this->query($sql) as $tdcrow) {
			$tdca[$tdcrow["AttributeID"]]["value"]=$tdcrow["Value"];
			$tdca[$tdcrow["AttributeID"]]["required"]=$tdcrow["Required"];
		}	
		$this->CustomValues = $tdca;
	}	

	function DeleteCustomValues() {
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_DeviceTemplateCustomValue WHERE TemplateID=$this->TemplateID;";
		if($this->query($sql)){
			$this->GetCustomValues();
			return true;
		}
		return false;
	}

	function InsertCustomValue($AttributeID, $Value, $Required) {
		$this->MakeSafe();
		// make the custom attirubte stuff safe
		$AttributeID=intval($AttributeID);
		$Required=intval($Required);
		$Value=sanitize(trim($Value));

		$sql="INSERT INTO fac_DeviceTemplateCustomValue
			SET TemplateID=$this->TemplateID,
			    AttributeID=$AttributeID,
			    Required=$Required,
			    Value=\"$Value\";";
		if($this->query($sql)) {
			$this->GetCustomValues();
			return true;
		}
		return false;
	}

	static function getAvailableImages(){
		$array=array();
		$path='pictures';
		if(preg_match('/api\//',str_replace(DIRECTORY_SEPARATOR, '/',getcwd()))){
			$path="../../$path";
		}
		if(is_dir($path)){
			$dir=scandir($path);
			foreach($dir as $i => $f){
				if(is_file($path.DIRECTORY_SEPARATOR.$f) && ($f!='.' && $f!='..' && $f!='P_ERROR.png')){
					$mimeType=mime_content_type($path.DIRECTORY_SEPARATOR.$f);
					if(preg_match('/^image/i', $mimeType)){
						$array[]=$f;
					}
				}
			}
		}
		return $array;
	}
}

?>
