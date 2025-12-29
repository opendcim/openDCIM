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

class RackRequest {
	/*	RackRequest:	If enabled for users, will allow them to enter detail information about systems that
						need to be racked within a data center.  Will gather the pertinent information required
						for placement, and can then be reserved within a cabinet and a work order generated from
						that point.
						
						SMTP configuration is required for this to work properly, as an email confirmation is sent
						to the user after entering a request.
	*/
  var $RequestID;
  var $RequestorID;
  var $RequestTime;
  var $CompleteTime;
  var $Label;
  var $SerialNo;
  var $AssetTag;
  var $Hypervisor;
  var $Owner;
  var $DeviceHeight;
  var $EthernetCount;
  var $VLANList;
  var $SANCount;
  var $SANList;
  var $DeviceClass;
  var $DeviceType;
  var $LabelColor;
  var $CurrentLocation;
  var $SpecialInstructions;
  var $RequestedAction;
  var $MfgDate;

	// Create MakeSafe / MakeDisplay functions
	function MakeSafe(){
		//Keep weird values out of DeviceType
		$validdevicetypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU');
		$validrequestactions=array('new', 'change', 'move', 'retire'); // Empty string is the default value, e.g. when actions are disabled

		$this->RequestID=intval($this->RequestID);
		$this->RequestorID=intval($this->RequestorID);
		$this->RequestTime=sanitize($this->RequestTime); //datetime
		$this->CompleteTime=sanitize($this->CompleteTime); //datetime
		$this->Label=sanitize(transform($this->Label));
		$this->SerialNo=sanitize(transform($this->SerialNo));
		$this->AssetTag=sanitize($this->AssetTag);
		$this->Hypervisor=sanitize($this->Hypervisor);
		$this->Owner=intval($this->Owner);
		$this->DeviceHeight=intval($this->DeviceHeight);
		$this->EthernetCount=intval($this->EthernetCount);
		$this->VLANList=sanitize($this->VLANList);
		$this->SANCount=intval($this->SANCount);
		$this->SANList=sanitize($this->SANList);
		$this->DeviceClass=sanitize($this->DeviceClass);
		$this->DeviceType=(in_array($this->DeviceType,$validdevicetypes))?$this->DeviceType:'Server';
		$this->LabelColor=sanitize($this->LabelColor);
		$this->CurrentLocation=sanitize(transform($this->CurrentLocation));
		$this->SpecialInstructions=sanitize($this->SpecialInstructions);
		$this->RequestedAction=(in_array($this->RequestedAction,$validrequestactions))?$this->RequestedAction:'';
		$mfgDate=strtotime((string)$this->MfgDate);
		$this->MfgDate=($mfgDate !== false) ? date("Y-m-d", $mfgDate) : '';
	}

	function MakeDisplay(){
		$this->Label=stripslashes((string)$this->Label);
		$this->SerialNo=stripslashes((string)$this->SerialNo);
		$this->AssetTag=stripslashes((string)$this->AssetTag);
		$this->VLANList=stripslashes((string)$this->VLANList);
		$this->SANList=stripslashes((string)$this->SANList);
		$this->DeviceClass=stripslashes((string)$this->DeviceClass);
		$this->LabelColor=stripslashes((string)$this->LabelColor);
		$this->CurrentLocation=stripslashes((string)$this->CurrentLocation);
		$this->SpecialInstructions=stripslashes((string)$this->SpecialInstructions);
	}
 
  function CreateRequest(){
	global $dbh;

	$this->MakeSafe();

    $sql="INSERT INTO fac_RackRequest SET RequestTime=now(), RequestorID=$this->RequestorID,
		Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", MfgDate=\"$this->MfgDate\", 
		AssetTag=\"$this->AssetTag\", Hypervisor=\"$this->Hypervisor\", Owner=$this->Owner, 
		DeviceHeight=\"$this->DeviceHeight\", EthernetCount=$this->EthernetCount, 
		VLANList=\"$this->VLANList\", SANCount=$this->SANCount, SANList=\"$this->SANList\",
		DeviceClass=\"$this->DeviceClass\", DeviceType=\"$this->DeviceType\",
		LabelColor=\"$this->LabelColor\", CurrentLocation=\"$this->CurrentLocation\",
		SpecialInstructions=\"$this->SpecialInstructions\", RequestedAction=\"$this->RequestedAction\";";

	if(!$dbh->exec($sql)){
		$info=$dbh->errorInfo();
		error_log("PDO Error: " . ($info[2] ?? 'Unknown error'));
		return false;
	}else{		
		$this->RequestID=$dbh->lastInsertId();
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		$this->MakeDisplay();
        return $this->RequestID;
	}
  }
  
  function GetOpenRequests() {
	global $dbh;
    $sql="SELECT * FROM fac_RackRequest WHERE CompleteTime='0000-00-00 00:00:00'";
    
    $requestList=array();
	$stmt=$dbh->query($sql);
	if($stmt){
		foreach($stmt as $row){ 
			$requestNum=sizeof($requestList);

			$requestList[$requestNum]=new RackRequest();
			$requestList[$requestNum]->RequestID=$row["RequestID"] ?? null;
			$requestList[$requestNum]->RequestorID=$row["RequestorID"] ?? null;
			$requestList[$requestNum]->RequestTime=$row["RequestTime"] ?? null;
			$requestList[$requestNum]->CompleteTime=$row["CompleteTime"] ?? null;
			$requestList[$requestNum]->Label=$row["Label"] ?? null;
			$requestList[$requestNum]->SerialNo=$row["SerialNo"] ?? null;
			$requestList[$requestNum]->AssetTag=$row["AssetTag"] ?? null;
			$requestList[$requestNum]->Hypervisor=$row["Hypervisor"] ?? null;
			$requestList[$requestNum]->Owner=$row["Owner"] ?? null;
			$requestList[$requestNum]->DeviceHeight=$row["DeviceHeight"] ?? null;
			$requestList[$requestNum]->EthernetCount=$row["EthernetCount"] ?? null;
			$requestList[$requestNum]->VLANList=$row["VLANList"] ?? null;
			$requestList[$requestNum]->SANCount=$row["SANCount"] ?? null;
			$requestList[$requestNum]->SANList=$row["SANList"] ?? null;
			$requestList[$requestNum]->DeviceClass=$row["DeviceClass"] ?? null;
			$requestList[$requestNum]->DeviceType=$row["DeviceType"] ?? null;
			$requestList[$requestNum]->LabelColor=$row["LabelColor"] ?? null;
			$requestList[$requestNum]->CurrentLocation=$row["CurrentLocation"] ?? null;
			$requestList[$requestNum]->SpecialInstructions=$row["SpecialInstructions"] ?? null;
			$requestList[$requestNum]->RequestedAction=$row["RequestedAction"] ?? null;
			$requestList[$requestNum]->MakeDisplay();
		}
    }
    
    return $requestList;
  }
  
  function GetRequest(){
	global $dbh;
    $sql="SELECT * FROM fac_RackRequest WHERE RequestID=\"".intval($this->RequestID)."\";";

	$stmt=$dbh->query($sql);
	if($stmt && ($row=$stmt->fetch())){
		$this->RequestorID=$row["RequestorID"] ?? null;
		$this->RequestTime=$row["RequestTime"] ?? null;
		$this->CompleteTime=$row["CompleteTime"] ?? null;
		$this->Label=$row["Label"] ?? null;
		$this->SerialNo=$row["SerialNo"] ?? null;
		$this->MfgDate=$row["MfgDate"] ?? null;
		$this->AssetTag=$row["AssetTag"] ?? null;
		$this->Hypervisor=$row["Hypervisor"] ?? null;
		$this->Owner=$row["Owner"] ?? null;
		$this->DeviceHeight=$row["DeviceHeight"] ?? null;
		$this->EthernetCount=$row["EthernetCount"] ?? null;
		$this->VLANList=$row["VLANList"] ?? null;
		$this->SANCount=$row["SANCount"] ?? null;
		$this->SANList=$row["SANList"] ?? null;
		$this->DeviceClass=$row["DeviceClass"] ?? null;
		$this->DeviceType=$row["DeviceType"] ?? null;
		$this->LabelColor=$row["LabelColor"] ?? null;
		$this->CurrentLocation=$row["CurrentLocation"] ?? null;
		$this->SpecialInstructions=$row["SpecialInstructions"] ?? null;
		$this->RequestedAction=$row["RequestedAction"] ?? null;
		$this->MakeDisplay();
	}else{
		//something bad happened maybe tell someone
	}
  }
  
  function CompleteRequest(){
	global $dbh;

	$old=new RackRequest();
	$old->RequestID=$this->RequestID;
	$old->GetRequest();

    $sql="UPDATE fac_RackRequest SET CompleteTime=now() WHERE RequestID=\"".$this->RequestID."\";";
	if($dbh->query($sql)){
		$this->GetRequest();
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
		return true;
	}else{
		return false;
	}
  }
  
  function DeleteRequest(){
	global $dbh;
    $sql="DELETE FROM fac_RackRequest WHERE RequestID=\"".intval($this->RequestID)."\";";
	if($dbh->query($sql)){
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}else{
		return false;
	}
  }

  function UpdateRequest(){
	global $dbh;

	$this->MakeSafe();

	$old=new RackRequest();
	$old->RequestID=$this->RequestID;
	$old->GetRequest();

    $sql="UPDATE fac_RackRequest SET RequestTime=now(), RequestorID=$this->RequestorID,
		Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", MfgDate=\"$this->MfgDate\", 
		AssetTag=\"$this->AssetTag\", Hypervisor=\"$this->Hypervisor\", Owner=$this->Owner, 
		DeviceHeight=\"$this->DeviceHeight\", EthernetCount=$this->EthernetCount, 
		VLANList=\"$this->VLANList\", SANCount=$this->SANCount, SANList=\"$this->SANList\",
		DeviceClass=\"$this->DeviceClass\", DeviceType=\"$this->DeviceType\",
		LabelColor=\"$this->LabelColor\", CurrentLocation=\"$this->CurrentLocation\",
		SpecialInstructions=\"$this->SpecialInstructions\", RequestedAction=\"$this->RequestedAction\"
		WHERE RequestID=$this->RequestID;";
    
	if($dbh->query($sql)){
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
		$this->MakeDisplay();
		return true;
	}else{
		return false;
	}
  }  
}
?>
