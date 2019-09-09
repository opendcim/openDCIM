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

class CDUInfo {
	/* All of these functions will REQUIRE the built-in SNMP functions
	*  - the external calls are simply too slow */
	static private function BasicTests($DeviceID){
		global $config;

		// First check if the SNMP library is present
		if(!class_exists('OSS_SNMP\SNMP')){
			return false;
		}

		$dev=New Device();
		$dev->DeviceID=$DeviceID;

		// Make sure this is a real device and has an IP set
		if(!$dev->GetDevice()){return false;}
		if($dev->PrimaryIP==""){return false;}

		// If the device doesn't have an SNMP community set,
		// check and see if we have a global one
		$dev->SNMPCommunity=($dev->SNMPCommunity=="")?$config->ParameterArray["SNMPCommunity"]:$dev->SNMPCommunity;

		// Make this false faster
		$dev->SNMPCommunity=trim($dev->SNMPCommunity);
		if($dev->SNMPCommunity==""){return false;}

		// We've passed all the repeatable tests, return the device object for digging
		return $dev;
	}

	// Making an attempt at reducing the lines that I was constantly 
	// repeating at a cost of making this a little more convoluted.
	static private function OSS_SNMP_Lookup($dev,$snmplookup,$portid=null,$baseOID=null){
		// This is find out the name of the function that called this to
		// make the error logging more descriptive
		$caller=debug_backtrace();
		$caller=$caller[1]['function'];

		// Since we don't really let the user specify the version right now here's a stop gap
		// Try the default version of 2c first
		$snmpHost=new OSS_SNMP\SNMP($dev->PrimaryIP,$dev->SNMPCommunity,$dev->SNMPVersion,$dev->v3SecurityLevel,$dev->v3AuthProtocol,$dev->v3AuthPassphrase,$dev->v3PrivProtocol,$dev->v3PrivPassphrase);
		try {
			$snmpHost->useSystem()->name();
		}catch (Exception $e){
			// That shit the bed so drop down to 1
			$snmpHost=new OSS_SNMP\SNMP($dev->PrimaryIP,$dev->SNMPCommunity,1);
		}

		$snmpresult=false;
		try {
			$snmpresult=($snmpHost->get($baseOID.($portid!=Null?".$portid":"$portid")));
		}catch (Exception $e){
			error_log("CDUInfo::$caller($dev->DeviceID) ".$e->getMessage());
		}

		return $snmpresult;
	}

	// Returns the number of outlets in the CDU based on the SNMP response
	// of number of outlets returned by the device.  If not if fails to the
	// number of outlets the user configured for the device
	static function getNumPorts($DeviceID) {
		if(!$dev=CDUInfo::BasicTests($DeviceID)){
			return false;
		}

		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$dev->TemplateID;
		if(!$tmpl->GetTemplate()){
			return false;
		}

		$baseOID = $tmpl->OutletCountOID;

		if ( ($numPorts=self::OSS_SNMP_Lookup($dev,Null,Null,$baseOID)) === false ){
			$numPorts = $dev->PowerSupplyCount;
		}

		return $numPorts;
	}

	// The device this was written for did not really need this but it is 
	// kept incase the next device has some method to have virtual ports
	// or if the user wants to change the first (bank number ouside down?)
	static function findFirstPort( $DeviceID ) {
		if(!$dev=CDUInfo::BasicTests($DeviceID)){
			return false;
		}
		
		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$dev->TemplateID;
		if(!$tmpl->GetTemplate()){
			return false;
		}

		$baseOID = (isset($tmpl->OutletNameOID)?$tmpl->OutletNameOID:$tmpl->OutletDescOID);

		$numPorts=CDUInfo::getNumPorts($DeviceID);

		$portlist =[];

		for ($i=1; $i<=$numPorts; $i++) {
			$portlist += array($i => self::OSS_SNMP_Lookup($dev, Null, $i , $baseOID));
			if ( $portlist[$i] === false ) {break;}
		}

		return $portlist;
	}

	static function getPortNames($DeviceID,$portid=null){
		if(!$dev=CDUInfo::BasicTests($DeviceID)){
			return false;
		}

		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$dev->TemplateID;
		if(!$tmpl->GetTemplate()){
			return false;
		}

		$baseOID = $tmpl->OutletNameOID;

		$numPorts=CDUInfo::getNumPorts($DeviceID);

		$nameList = [];

		for ($i=1; $i<=$numPorts; $i++) {
			$nameList += array($i => self::OSS_SNMP_Lookup($dev, Null, $i , $baseOID));
			if ( $nameList[$i] === false ) {break;}
		}	

		if(is_array($nameList)){
			$saving=false;
			$newList=array();
			foreach($nameList as $i => $desc){
				if($i==$dev->FirstPortNum){$saving=true;}
				if($saving){$newList[sizeof($newList)+1]=$desc;}
				if(sizeof($newList)==$numPorts){break;}
			}
			$nameList=$newList;
		}

		return $nameList;
	}

	static function getPortStatus($DeviceID,$portid=null){
		if(!$dev=CDUInfo::BasicTests($DeviceID)){
			return false;
		}

		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$dev->TemplateID;
		if(!$tmpl->GetTemplate()){
			return false;
		}

		$baseOID = $tmpl->OutletStatusOID;

		$numPorts=CDUInfo::getNumPorts($DeviceID);

		$statusList = [];

		for ($i=1; $i<=$numPorts; $i++) {
			$statusList += array($i => self::OSS_SNMP_Lookup($dev, Null, $i , $baseOID));
			if ( $statusList[$i] === false ) {break;}
		}

		if(is_array($statusList)){
			$saving=false;
			$newList=array();
			foreach($statusList as $i => $status){
				if($i==$dev->FirstPortNum){$saving=true;}
				if($saving){$newList[sizeof($newList)+1]=($status==$tmpl->OutletStatusOn?"up":"down");}
				if(sizeof($newList)==$numPorts){break;}
			}
			$statusList=$newList;
		}

		return $statusList;
	}
	
	static function getPortAlias($DeviceID,$portid=null){
		if(!$dev=CDUInfo::BasicTests($DeviceID)){
			return false;
		}

		$tmpl=new CDUTemplate();
		$tmpl->TemplateID=$dev->TemplateID;
		if(!$tmpl->GetTemplate()){
			return false;
		}

		$baseOID = $tmpl->OutletDescOID;

		$numPorts=CDUInfo::getNumPorts($DeviceID);

		$aliasList = [];

		for ($i=1; $i<=$numPorts; $i++) {
			$aliasList += array($i => self::OSS_SNMP_Lookup($dev, Null, $i , $baseOID));
			if ( $aliasList[$i] === false ) {break;}
		}

		if(is_array($aliasList)){
			$saving=false;
			$newList=array();
			foreach($aliasList as $i => $alias){
				if($i==$dev->FirstPortNum){$saving=true;}
				if($saving){$newList[sizeof($newList)+1]=$alias;}
				if(sizeof($newList)==$numPorts){break;}
			}
			$aliasList=$newList;
		}

		return $aliasList;
	}
}
?>
