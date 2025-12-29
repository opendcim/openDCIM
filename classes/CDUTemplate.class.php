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

class CDUTemplate {
	var $TemplateID;
	var $ManufacturerID;
	var $Model;
	var $Managed;
	var $ATS;
	var $VersionOID;
	var $OutletNameOID;
	var $OutletDescOID;
	var $OutletCountOID;
	var $OutletStatusOID;
	var $OutletStatusOn;
	var $Multiplier;
	var $OID1;
	var $OID2;
	var $OID3;
	var $ATSStatusOID;
	var $ATSDesiredResult;
	var $ProcessingProfile;
	var $Voltage;
	var $Amperage;
	var $NumOutlets;

	function MakeSafe(){
		$validMultipliers=array(0.01,0.1,1,10,100);
		$validProcessingProfiles=array('SingleOIDWatts','SingleOIDAmperes',
			'Combine3OIDWatts','Combine3OIDAmperes','Convert3PhAmperes');

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=sanitize($this->Model);
		$this->Managed=intval($this->Managed);
		$this->ATS=intval($this->ATS);
		$this->VersionOID=sanitize($this->VersionOID);
		$this->OutletNameOID=sanitize($this->OutletNameOID);
		$this->OutletDescOID=sanitize($this->OutletDescOID);
		$this->OutletCountOID=sanitize($this->OutletCountOID);
		$this->OutletStatusOID=sanitize($this->OutletStatusOID);
		$this->OutletStatusOn=sanitize($this->OutletStatusOn);
		$this->Multiplier=(in_array($this->Multiplier, $validMultipliers))?$this->Multiplier:1;
		$this->OID1=sanitize($this->OID1);
		$this->OID2=sanitize($this->OID2);
		$this->OID3=sanitize($this->OID3);
		$this->ATSStatusOID=sanitize($this->ATSStatusOID);
		$this->ATSDesiredResult=sanitize($this->ATSDesiredResult);
		$this->ProcessingProfile=(in_array($this->ProcessingProfile, $validProcessingProfiles))?$this->ProcessingProfile:'SingleOIDWatts';
		$this->Voltage=intval($this->Voltage);
		$this->Amperage=intval($this->Amperage);
		$this->NumOutlets=intval($this->NumOutlets);
	}

	function MakeDisplay(){
		$this->Model=stripslashes((string)$this->Model);
		$this->VersionOID=stripslashes((string)$this->VersionOID);
		$this->OutletNameOID=stripslashes((string)$this->OutletNameOID);
		$this->OutletDescOID=stripslashes((string)$this->OutletDescOID);
		$this->OutletCountOID=stripslashes((string)$this->OutletCountOID);
		$this->OutletStatusOID=stripslashes((string)$this->OutletStatusOID);
		$this->OutletStatusOn=stripslashes((string)$this->OutletStatusOn);
		$this->OID1=stripslashes((string)$this->OID1);
		$this->OID2=stripslashes((string)$this->OID2);
		$this->OID3=stripslashes((string)$this->OID3);
		$this->ATSStatusOID=stripslashes((string)$this->ATSStatusOID);
		$this->ATSDesiredResult=stripslashes((string)$this->ATSDesiredResult);
	}

	static function RowToObject($row){
		$template=new CDUTemplate();
		$template->TemplateID=$row["TemplateID"] ?? null;
		$template->ManufacturerID=$row["ManufacturerID"] ?? null;
		$template->Model=$row["Model"] ?? null;
		$template->Managed=$row["Managed"] ?? null;
		$template->ATS=$row["ATS"] ?? null;
		$template->VersionOID=$row["VersionOID"] ?? null;
		$template->OutletNameOID=$row["OutletNameOID"] ?? null;
		$template->OutletDescOID=$row["OutletDescOID"] ?? null;
		$template->OutletCountOID=$row["OutletCountOID"] ?? null;
		$template->OutletStatusOID=$row["OutletStatusOID"] ?? null;
		$template->OutletStatusOn=$row["OutletStatusOn"] ?? null;
		$template->Multiplier=$row["Multiplier"] ?? null;
		$template->OID1=$row["OID1"] ?? null;
		$template->OID2=$row["OID2"] ?? null;
		$template->OID3=$row["OID3"] ?? null;
		$template->ATSStatusOID=$row["ATSStatusOID"] ?? null;
		$template->ATSDesiredResult=$row["ATSDesiredResult"] ?? null;
		$template->ProcessingProfile=$row["ProcessingProfile"] ?? null;
		$template->Voltage=$row["Voltage"] ?? null;
		$template->Amperage=$row["Amperage"] ?? null;
		$template->NumOutlets=$row["NumOutlets"] ?? null;

		$template->MakeDisplay();

		return $template;
	}
	
	function GetTemplateList(){
		global $dbh;
		
		$sql="SELECT a.* FROM fac_CDUTemplate a, fac_Manufacturer b WHERE 
			a.ManufacturerID=b.ManufacturerID ORDER BY b.Name ASC,a.Model ASC;";
		
		$tmpList=array();
		if($stmt=$dbh->query($sql)){
			foreach($stmt as $row){
				$tmpList[]=CDUTemplate::RowToObject($row);
			}
		}
		
		return $tmpList;
	}
	
	function GetTemplate(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_CDUTemplate WHERE TemplateID=$this->TemplateID";

		$stmt=$dbh->query($sql);
		if($stmt && ($row=$stmt->fetch())){
			foreach(CDUTemplate::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}

			return true;
		}else{
			return false;
		}
	}
	
	function CreateTemplate($templateid) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="INSERT INTO fac_CDUTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Managed=$this->Managed, ATS=$this->ATS,
			VersionOID=\"$this->VersionOID\", 
			OutletNameOID=\"$this->OutletNameOID\",
			OutletDescOID=\"$this->OutletDescOID\",
			OutletCountOID=\"$this->OutletCountOID\",
			OutletStatusOID=\"$this->OutletStatusOID\",
			OutletStatusOn=\"$this->OutletStatusOn\",
			Multiplier=\"$this->Multiplier\", OID1=\"$this->OID1\", OID2=\"$this->OID2\", 
			OID3=\"$this->OID3\", ATSStatusOID=\"$this->ATSStatusOID\", ATSDesiredResult=\"$this->ATSDesiredResult\",
			ProcessingProfile=\"$this->ProcessingProfile\", 
			Voltage=$this->Voltage, Amperage=$this->Amperage, NumOutlets=$this->NumOutlets, TemplateID=$templateid";
		
		if(!$dbh->exec($sql)){
			// A combination of this Mfg + Model already exists most likely
			return false;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->TemplateID;
	}
	
	function UpdateTemplate() {
		global $dbh;

		$this->MakeSafe();

		$oldtemplate=new CDUTemplate();
		$oldtemplate->TemplateID=$this->TemplateID;
		$oldtemplate->GetTemplate();
		
		$sql="UPDATE fac_CDUTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Managed=$this->Managed, ATS=$this->ATS,
			VersionOID=\"$this->VersionOID\", 
			OutletNameOID=\"$this->OutletNameOID\",
			OutletDescOID=\"$this->OutletDescOID\",
			OutletCountOID=\"$this->OutletCountOID\",
			OutletStatusOID=\"$this->OutletStatusOID\",
			OutletStatusOn=\"$this->OutletStatusOn\",
			Multiplier=\"$this->Multiplier\", OID1=\"$this->OID1\", OID2=\"$this->OID2\", 
			OID3=\"$this->OID3\", ATSStatusOID=\"$this->ATSStatusOID\", ATSDesiredResult=\"$this->ATSDesiredResult\",
			ProcessingProfile=\"$this->ProcessingProfile\", 
			Voltage=$this->Voltage, Amperage=$this->Amperage, NumOutlets=$this->NumOutlets
			WHERE TemplateID=$this->TemplateID;";
		
		if(!$dbh->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$oldtemplate):'';
			return true;
		}
	}
	
	function DeleteTemplate() {
		global $dbh;

		$this->MakeSafe();
		
		// First step is to clear any power strips referencing this template
		$sql="UPDATE fac_PowerDistribution SET CDUTemplateID=0 WHERE TemplateID=$this->TemplateID;";
		$dbh->query($sql);
		
		$sql="DELETE FROM fac_CDUTemplate WHERE TemplateID=$this->TemplateID;";
		$dbh->exec($sql);
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}
}
?>
