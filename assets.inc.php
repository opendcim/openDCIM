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


class Cabinet {
	/* Cabinet:		The workhorse logical container for DCIM.  This can be a 2-post rack, a 4-post open rack,
					or an enclosed cabinet.  The height is variable.  Devices are attached to cabinets, and
					cabinets are attached to data centers.  PDU's are associated with cabinets, and metrics
					are reported on cabinets for power, space, and weight.
	*/
	
	var $CabinetID;
	var $DataCenterID;
	var $Location;
	var $AssignedTo;
	var $ZoneID;
	var $CabinetHeight;
	var $Model;
	var $Keylock;
	var $MaxKW;
	var $MaxWeight;
	var $InstallationDate;
	var $MapX1;
	var $MapY1;
	var $MapX2;
	var $MapY2;

	function CreateCabinet( $db ) {
		$insert_sql = "insert into fac_Cabinet set DataCenterID=\"" . intval($this->DataCenterID) . "\", Location=\"" . addslashes($this->Location) . "\", AssignedTo=\"" . intval($this->AssignedTo) . "\", ZoneID=\"" . intval($this->ZoneID) . "\", CabinetHeight=\"" . intval($this->CabinetHeight) . "\", Model=\"" . addslashes($this->Model) . "\", Keylock=\"" . addslashes( $this->Keylock ) . "\", MaxKW=\"" . floatval($this->MaxKW) . "\", MaxWeight=\"" . intval( $this->MaxWeight ). "\", InstallationDate=\"" . date( "Y-m-d", strtotime( $this->InstallationDate ) ) . "\", MapX1=\"" . intval($this->MapX1) . "\", MapY1=\"" . intval($this->MapY1) . "\", MapX2=\"" . intval($this->MapX2) . "\", MapY2=\"" . intval($this->MapY2) . "\"";

		if ( ! $result = mysql_query( $insert_sql, $db ) ) {
			// Error in inserting record
			echo mysql_errno().": ".mysql_error()."\n";
			return 0;
		}
		$this->CabinetID = mysql_insert_id( $db );

		return $this->CabinetID;
	}

	function UpdateCabinet( $db ) {
		$update_sql = "update fac_Cabinet set DataCenterID=\"" . intval($this->DataCenterID) . "\", Location=\"" . addslashes($this->Location) . "\", AssignedTo=\"" . intval($this->AssignedTo) . "\", ZoneID=\"" . intval($this->ZoneID) . "\", CabinetHeight=\"" . intval($this->CabinetHeight) . "\", Model=\"" . addslashes($this->Model) . "\", Keylock=\"" . addslashes( $this->Keylock ) . "\", MaxKW=\"" . floatval($this->MaxKW) . "\", MaxWeight=\"" . intval( $this->MaxWeight ) . "\", InstallationDate=\"" . date( "Y-m-d", strtotime( $this->InstallationDate ) ) . "\", MapX1=\"" . intval($this->MapX1) . "\", MapY1=\"" . intval($this->MapY1) . "\", MapX2=\"" . intval($this->MapX2) . "\", MapY2=\"" . intval($this->MapY2) . "\" where CabinetID=\"" . intval($this->CabinetID) . "\"";

		if ( ! $result = mysql_query( $update_sql, $db ) ) {
			return -1;
		}

		return 0;
	}

	function GetCabinet( $db ) {
		$select_sql = "select * from fac_Cabinet where CabinetID=\"" . intval($this->CabinetID) . "\"";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			// Error retrieving record
			$this->CabinetID = null;
			$this->DataCenterID = null;
			$this->Location = null;
			$this->AssignedTo = null;
			$this->ZoneID = null;
			$this->CabinetHeight = null;
			$this->Model = null;
			$this->Keylock = null;
			$this->MaxKW = null;
			$this->MaxWeight = null;
			$this->InstallationDate = null;
			$this->MapX1 = null;
			$this->MapY1 = null;
			$this->MapX2 = null;
			$this->MapY2 = null;

			return -1;
		}

		$cabinetRow = mysql_fetch_array( $result );

		$this->DataCenterID = $cabinetRow[ "DataCenterID" ];
		$this->Location = $cabinetRow[ "Location" ];
		$this->AssignedTo = $cabinetRow["AssignedTo"];
		$this->ZoneID = $cabinetRow["ZoneID"];
		$this->CabinetHeight = $cabinetRow[ "CabinetHeight" ];
		$this->Model = $cabinetRow["Model"];
		$this->Keylock = $cabinetRow["Keylock"];
		$this->MaxKW = $cabinetRow["MaxKW"];
		$this->MaxWeight = $cabinetRow["MaxWeight"];
		$this->InstallationDate = $cabinetRow[ "InstallationDate" ];
		$this->MapX1 = $cabinetRow["MapX1"];
		$this->MapY1 = $cabinetRow["MapY1"];
		$this->MapX2 = $cabinetRow["MapX2"];
		$this->MapY2 = $cabinetRow["MapY2"];

		return 0;
	}

	function ListCabinets( $db ) {
		$cabinetList = array();

		$select_sql = "select * from fac_Cabinet order by DataCenterID, Location";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		while ( $cabinetRow = mysql_fetch_array( $result ) ) {
			$cabID = $cabinetRow[ "CabinetID" ];
			$cabinetList[ $cabID ] = new Cabinet();

			$cabinetList[ $cabID ]->CabinetID = $cabinetRow[ "CabinetID" ];
			$cabinetList[ $cabID ]->DataCenterID = $cabinetRow[ "DataCenterID" ];
			$cabinetList[ $cabID ]->Location = $cabinetRow[ "Location" ];
			$cabinetList[ $cabID ]->AssignedTo = $cabinetRow[ "AssignedTo" ];
			$cabinetList[ $cabID ]->ZoneID = $cabinetRow["ZoneID"];
			$cabinetList[ $cabID ]->CabinetHeight = $cabinetRow[ "CabinetHeight" ];
			$cabinetList[ $cabID ]->Model = $cabinetRow[ "Model" ];
			$cabinetList[ $cabID ]->Keylock = $cabinetRow["Keylock"];
			$cabinetList[ $cabID ]->MaxKW = $cabinetRow[ "MaxKW" ];
			$cabinetList[ $cabID ]->MaxWeight = $cabinetRow[ "MaxWeight" ];
			$cabinetList[ $cabID ]->InstallationDate = $cabinetRow[ "InstallationDate" ];
			$cabinetList[ $cabID ]->MapX1 = $cabinetRow[ "MapX1" ];
			$cabinetList[ $cabID ]->MapY1 = $cabinetRow[ "MapY1" ];
			$cabinetList[ $cabID ]->MapX2 = $cabinetRow[ "MapX2" ];
			$cabinetList[ $cabID ]->MapY2 = $cabinetRow[ "MapY2" ];
		}

		return $cabinetList;
	}

	function ListCabinetsByDC( $db ) {
		$cabinetList = array();

		$select_sql = "select * from fac_Cabinet where DataCenterID=\"" . intval($this->DataCenterID) . "\" order by Location";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		while ( $cabinetRow = mysql_fetch_array( $result ) ) {
			$cabID = $cabinetRow[ "CabinetID" ];
			$cabinetList[ $cabID ] = new Cabinet();

			$cabinetList[ $cabID ]->CabinetID = $cabinetRow[ "CabinetID" ];
			$cabinetList[ $cabID ]->DataCenterID = $cabinetRow[ "DataCenterID" ];
			$cabinetList[ $cabID ]->Location = $cabinetRow[ "Location" ];
			$cabinetList[ $cabID ]->AssignedTo = $cabinetRow[ "AssignedTo" ];
			$cabinetList[ $cabID ]->ZoneID = $cabinetRow[ "ZoneID" ];
			$cabinetList[ $cabID ]->CabinetHeight = $cabinetRow[ "CabinetHeight" ];
			$cabinetList[ $cabID ]->Model = $cabinetRow[ "Model" ];
			$cabinetList[ $cabID ]->Keylock = $cabinetRow[ "Keylock" ];
			$cabinetList[ $cabID ]->MaxKW = $cabinetRow[ "MaxKW" ];
			$cabinetList[ $cabID ]->MaxWeight = $cabinetRow[ "MaxWeight" ];
			$cabinetList[ $cabID ]->InstallationDate = $cabinetRow[ "InstallationDate" ];
			$cabinetList[ $cabID ]->MapX1 = $cabinetRow[ "MapX1" ];
			$cabinetList[ $cabID ]->MapY1 = $cabinetRow[ "MapY1" ];
			$cabinetList[ $cabID ]->MapX2 = $cabinetRow[ "MapX2" ];
			$cabinetList[ $cabID ]->MapY2 = $cabinetRow[ "MapY2" ];
		}

		return $cabinetList;
	}

	function CabinetOccupancy( $CabinetID, $db ) {
		$select_sql = "select sum(Height) as Occupancy from fac_Device where Cabinet=$CabinetID";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		$row = mysql_fetch_array( $result );

		return $row["Occupancy"];
	}

	function GetDCSelectList( $db ) {
		$select_sql = "select * from fac_DataCenter order by Name";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return "";
		}

		$selectList = "<select name=\"datacenterid\">";

		while ( $selectRow = mysql_fetch_array( $result ) ) {
			if ( $selectRow[ "DataCenterID" ] == $this->DataCenterID )
				$selected = "selected";
			else
				$selected = "";


			$selectList .= "<option value=\"" . $selectRow[ "DataCenterID" ] . "\" $selected>" . $selectRow[ "Name" ] . "</option>";
		}

		$selectList .= "</select>";

		return $selectList;
	}

	function GetCabinetSelectList( $db ) {
		$select_sql = "select Name, CabinetID, Location from fac_DataCenter, fac_Cabinet where fac_DataCenter.DataCenterID=fac_Cabinet.DataCenterID order by Name ASC, Location ASC";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return "";
		}

		$selectList = "<select name=\"cabinetid\" id=\"cabinetid\"><option value=\"-1\">Storage Room</option>";

		while ( $selectRow = mysql_fetch_array( $result ) ) {
			if ( $selectRow[ "CabinetID" ] == $this->CabinetID )
				$selected = "selected";
			else
				$selected = "";

			$selectList .= "<option value=\"" . $selectRow[ "CabinetID" ] . "\" $selected>" . $selectRow[ "Name" ] . " / " . $selectRow[ "Location" ] . "</option>";
		}

		$selectList .= "</select>";

		return $selectList;
	}

	function BuildCabinetTree( $db ) {
		$dc = new DataCenter();
		$dept = new Department();

		$dcList = $dc->GetDCList( $db );

		if ( count( $dcList ) > 0 ) {
			$tree = "<ul class=\"mktree\" id=\"datacenters\">\n";
			
			$zoneInfo = new Zone();

			while ( list( $dcID, $datacenter ) = each( $dcList ) ) {
				if ( $dcID == $this->DataCenterID )
					$classType = "liOpen";
				else
					$classType = "liClosed";

				$tree .= "	<li class=\"$classType\" id=\"dc$dcID\"><a href=\"dc_stats.php?dc=" . $datacenter->DataCenterID . "\">" . $datacenter->Name . "</a>/\n		<ul>\n";

				$cab_sql = "select * from fac_Cabinet where DataCenterID=\"$dcID\" order by Location ASC";

				if ( ! $result = mysql_query( $cab_sql, $db ) ) {
					return -1;
				}

				while ( $cabRow = mysql_fetch_array( $result ) ) {
				  $dept->DeptID = $cabRow["AssignedTo"];
				  
				  if ( $dept->DeptID == 0 )
				    $dept->Name = "General Use";
				  else
				    $dept->GetDeptByID( $db );
				    
					$tree .= "			<li id=\"cab{$cabRow['CabinetID']}\"><a href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']} [$dept->Name]</a></li>\n";
				}

				$tree .= "		</ul>\n	</li>\n";
			}
			
			$tree .= "<li class=\"liOpen\" id=\"dc-1\"><a href=\"storageroom.php\">Storage Room</a></li>";

			$tree .= "</ul>";
		}

		return $tree;
	}

	function DeleteCabinet( $db ) {
		/* Need to delete all devices and CDUs first */
		$tmpDev = new Device();
		$tmpCDU = new PowerDistribution();
		
		$tmpDev->Cabinet = $this->CabinetID;
		$devList = $tmpDev->ViewDevicesByCabinet( $db );
		
		foreach ( $devList as &$delDev ) {
			$delDev->DeleteDevice( $db );
		}
		
		$tmpCDU->CabinetID = $this->CabinetID;
		$cduList = $tmpCDU->GetPDUbyCabinet( $db );
		
		foreach ( $cduList as &$delCDU ) {
			$delCDU->DeletePDU( $db );
		}
		
		$sql = sprintf( "delete from fac_Cabinet where CabinetID=\"%d\"", intval( $this->CabinetID ) );
		mysql_query( $sql, $db );
	}

	function SearchByCabinetName($db){
		$select_sql="select * from fac_Cabinet where ucase(Location) like \"%" . strtoupper($this->Location) . "%\" order by Location;";
		$result=mysql_query($select_sql,$db);

		$cabinetList=array();
		$cabCount=0;

		if(!$result=mysql_query($select_sql,$db)){
			return 0;
		}

		while($cabinetRow=mysql_fetch_array($result)){
			$cabID=$cabinetRow["CabinetID"];
			$cabinetList[$cabID]=new Cabinet();
			$cabinetList[$cabID]->CabinetID=$cabinetRow["CabinetID"];
			$cabinetList[$cabID]->DataCenterID=$cabinetRow["DataCenterID"];
			$cabinetList[$cabID]->Location=$cabinetRow["Location"];
			$cabinetList[$cabID]->AssignedTo=$cabinetRow["AssignedTo"];
			$cabinetList[$cabID]->ZoneID=$cabinetRow["ZoneID"];
			$cabinetList[$cabID]->CabinetHeight=$cabinetRow["CabinetHeight"];
			$cabinetList[$cabID]->Model=$cabinetRow["Model"];
			$cabinetList[$cabID]->Keylock=$cabinetRow["Keylock"];
			$cabinetList[$cabID]->MaxKW=$cabinetRow["MaxKW"];
			$cabinetList[$cabID]->MaxWeight=$cabinetRow["MaxWeight"];
			$cabinetList[$cabID]->InstallationDate=$cabinetRow["InstallationDate"];
			$cabinetList[$cabID]->MapX1=$cabinetRow["MapX1"];
			$cabinetList[$cabID]->MapY1=$cabinetRow["MapY1"];
			$cabinetList[$cabID]->MapX2=$cabinetRow["MapX2"];
			$cabinetList[$cabID]->MapY2=$cabinetRow["MapY2"];
		}

		return $cabinetList;
	}
}

class CabinetAudit {
	/*	CabinetAudit:	A perpetual audit trail for how often a cabinet has been audited, and by what user.
	*/
	
	var $CabinetID;
	var $UserID;
	var $AuditStamp;

	function CertifyAudit( $db ) {
		$sql = "insert into fac_CabinetAudit set CabinetID=\"" . intval( $this->CabinetID ) . "\", UserID=\"" . addslashes( $this->UserID ) . "\", AuditStamp=now()";

		$result = mysql_query( $sql, $db );

		return $result;
	}

	function GetLastAudit( $db ) {
		$sql = "select * from fac_CabinetAudit where CabinetID=\"" . intval( $this->CabinetID ) . "\" order by AuditStamp DESC Limit 1";

        if(!$result = mysql_query($sql,$db)){
			echo mysql_errno().": ".mysql_error()."\n";
		}

		if ( $row = mysql_fetch_array( $result ) ) {
			$this->CabinetID = $row["CabinetID"];
			$this->UserID = $row["UserID"];
			$this->AuditStamp = date( "M d, Y H:i", strtotime( $row["AuditStamp"] ) );
		}
	}
	
	function GetLastAuditByUser( $db ) {
		$sql = "select * from fac_CabinetAudit where UserID=\"" . addslashes( $this->UserID ) . "\" order by AuditStamp DESC Limit 1";

        if(!$result = mysql_query($sql,$db)){
			echo mysql_errno().": ".mysql_error()."\n";
		}

		if ( $row = mysql_fetch_array( $result ) ) {
			$this->CabinetID = $row["CabinetID"];
			$this->UserID = $row["UserID"];
			$this->AuditStamp = date( "M d, Y H:i", strtotime( $row["AuditStamp"] ) );
		}
	}
}

class Device {
	/*	Device:		Assets within the data center, at the most granular level.  There are three basic
					groupings of information kept about a device:  asset tracking, virtualization
					details, and physical infrastructure.
					If device templates are used, the default values for wattage and height can be
					used, but an override is allowed within the object.  Any value greater than zero
					for NominalWatts is used.  The Height is pulled from the template when selected,
					but any value set after that point is used.
	*/
	
	var $DeviceID;
	var $Label;
	var $SerialNo;
	var $AssetTag;
	var $PrimaryIP;
	var $SNMPCommunity;
	var $ESX;
	var $Owner;
	var $EscalationTimeID;
	var $EscalationID;
	var $PrimaryContact;
	var $Cabinet;
	var $Position;
	var $Height;
	var $Ports;
	var $TemplateID;
	var $NominalWatts;
	var $PowerSupplyCount;
	var $DeviceType;
	var $ChassisSlots;
	var $RearChassisSlots;
	var $ParentDevice;
	var $MfgDate;
	var $InstallDate;
	var $WarrantyCo;
	var $WarrantyExpire;
	var $Notes;
	var $Reservation;

	function CreateDevice( $db ) {
		// Force all uppercase for labels
		//

		$this->Label = strtoupper( $this->Label );
		$this->SerialNo = strtoupper( $this->SerialNo );
		$this->AssetTag = strtoupper( $this->AssetTag );
		
		if ( ! in_array( $this->DeviceType, array( 'Server', 'Appliance', 'Storage Array', 'Switch', 'Chassis', 'Patch Panel', 'Physical Infrastructure' ) ) )
		  $this->DeviceType = "Server";

		$insert_sql = "insert into fac_Device set Label=\"" . addslashes($this->Label) . "\", SerialNo=\"" . addslashes($this->SerialNo) . "\", AssetTag=\"" . addslashes($this->AssetTag) . 
			"\", PrimaryIP=\"" . addslashes($this->PrimaryIP) . "\", SNMPCommunity=\"" . addslashes($this->SNMPCommunity) . "\", ESX=\"" . intval($this->ESX) . "\", Owner=\"" . intval($this->Owner) . 
			"\", EscalationTimeID=\"" . intval( $this->EscalationTimeID ) . "\", EscalationID=\"" . intval( $this->EscalationID ) . "\", PrimaryContact=\"" . intval( $this->PrimaryContact ) . 
			"\", Cabinet=\"" . intval($this->Cabinet) . "\", Position=\"" . intval($this->Position) . "\", Height=\"" . intval($this->Height) . "\", Ports=\"" . intval($this->Ports) . 
			"\", TemplateID=\"" . intval($this->TemplateID) . "\", NominalWatts=\"" . intval($this->NominalWatts) . "\", PowerSupplyCount=\"" . intval($this->PowerSupplyCount) . 
			"\", DeviceType=\"" . $this->DeviceType . "\", ChassisSlots=\"" . intval($this->ChassisSlots) . "\", RearChassisSlots=\"" . intval($this->RearChassisSlots) . "\", ParentDevice=\"" . intval( $this->ParentDevice) . 
			"\", MfgDate=\"" . date("Y-m-d",strtotime($this->MfgDate)) . "\", InstallDate=\"" . date("Y-m-d",strtotime($this->InstallDate)) . 
			"\", WarrantyCo=\"" . addslashes( $this->WarrantyCo ) . "\", WarrantyExpire=\"" . date( "Y-m-d",strtotime($this->WarrantyExpire)) . 
			"\", Notes=\"" . addslashes( $this->Notes ) . "\", Reservation=\"" . intval($this->Reservation) . "\"";

		if ( ! $result = mysql_query( $insert_sql, $db ) ) {
			// Error occurred
			return 0;
		}

		$this->DeviceID = mysql_insert_id( $db );

		return $this->DeviceID;
	}

  function Surplus( $db ) {
    // Make sure we're not trying to decommission a device that doesn't exist
    if ( ! $this->GetDevice( $db ) )
      die( "Can't find device " . $this->DeviceID . " to decommission!" );
    
    $insert_sql = "insert into fac_Decommission values ( now(), \"$this->Label\", \"$this->SerialNo\", \"$this->AssetTag\", \"{$_SERVER['REMOTE_USER']}\" )";
    if ( ! $result = mysql_query( $insert_sql, $db ) )
      die( "Unable to create log of decommissioning.  $insert_sql" );
  
    // Ok, we have the transaction of decommissioning, now tidy up the database.
    $this->DeleteDevice( $db );
  }
  
  function MoveToStorage( $db ) {
    // Cabinet ID of -1 means that the device is in the storage area
    $this->Cabinet = -1;
    $this->UpdateDevice( $db );
    
    $tmpConn = new SwitchConnection();
    $tmpConn->SwitchDeviceID = $this->DeviceID;
    $tmpConn->EndpointDeviceID = $this->DeviceID;
    $tmpConn->DropSwitchConnections( $db );
    $tmpConn->DropEndpointConnections( $db );
  }
  
	function UpdateDevice( $db ) {
		// Force all uppercase for labels
		//
		$this->Label = strtoupper( $this->Label );
		$this->SerialNo = strtoupper( $this->SerialNo );
		$this->AssetTag = strtoupper( $this->AssetTag );

		if ( ! in_array( $this->DeviceType, array( 'Server', 'Appliance', 'Storage Array', 'Switch', 'Chassis', 'Patch Panel', 'Physical Infrastructure' ) ) )
		  $this->DeviceType = "Server";

		// You can't update what doesn't exist, so check for existing record first and retrieve the current location
		$select_sql = "select * from fac_Device where DeviceID=\"" . $this->DeviceID . "\"";
		$result = mysql_query( $select_sql, $db );
		if ( $row = mysql_fetch_array( $result ) ) {
		  // If you changed cabinets then the power connections need to be removed
		  if ( $row["Cabinet"] != $this->Cabinet ) {
			$powercon = new PowerConnection();
			$powercon->DeviceID = $this->DeviceID;
			$powercon->DeleteConnections( $db );
		  }
      
  		$update_sql = "update fac_Device set Label=\"" . addslashes($this->Label) . "\", SerialNo=\"" . addslashes($this->SerialNo) . "\", AssetTag=\"" . addslashes($this->AssetTag) . 
			"\", PrimaryIP=\"" . addslashes($this->PrimaryIP) . "\", SNMPCommunity=\"" . addslashes($this->SNMPCommunity) . "\", ESX=\"" . intval($this->ESX) . 
			"\", Owner=\"" . addslashes($this->Owner) . "\", EscalationTimeID=\"" . intval( $this->EscalationTimeID ) . "\", EscalationID=\"" . intval( $this->EscalationID ) . 
			"\", PrimaryContact=\"" . intval( $this->PrimaryContact ) . "\", Cabinet=\"" . intval($this->Cabinet) . "\", Position=\"" . intval($this->Position) . 
			"\", Height=\"" . intval($this->Height) . "\", Ports=\"" . intval($this->Ports) . "\", TemplateID=\"" . intval($this->TemplateID) . 
			"\", NominalWatts=\"" . intval($this->NominalWatts) . "\", PowerSupplyCount=\"" . intval($this->PowerSupplyCount) . "\", DeviceType=\"" . $this->DeviceType . 
			"\", ChassisSlots=\"" . intval($this->ChassisSlots) . "\", RearChassisSlots=\"" . intval($this->RearChassisSlots) . "\", ParentDevice=\"" . intval($this->ParentDevice) .
			"\", MfgDate=\"" . date("Y-m-d",strtotime($this->MfgDate)) . "\", InstallDate=\"" . date("Y-m-d",strtotime($this->InstallDate)) . 
			"\", WarrantyCo=\"" . addslashes( $this->WarrantyCo ) . "\", WarrantyExpire=\"" . date("Y-m-d", strtotime($this->WarrantyExpire)) . 
			"\", Notes=\"" . addslashes( $this->Notes ) . "\", Reservation=\"" . intval($this->Reservation) . "\" where DeviceID=\"" . intval($this->DeviceID) . "\"";
    }

		if ( ! $result = mysql_query( $update_sql, $db ) ) {
			// Error occurred
			return -1;
		}

		return 0;
	}

	function GetDevice( $db ) {
		$select_sql = "select * from fac_Device where DeviceID=\"" . intval($this->DeviceID) . "\"";

		$result=mysql_query($select_sql,$db);
		if(!$result || mysql_num_rows($result)==0){
			return false;
		}

		$devRow = mysql_fetch_array( $result );

		$this->DeviceID = $devRow["DeviceID"];
		$this->Label = $devRow["Label"];
		$this->SerialNo = $devRow["SerialNo"];
		$this->AssetTag = $devRow["AssetTag"];
		$this->PrimaryIP = $devRow["PrimaryIP"];
		$this->SNMPCommunity = $devRow["SNMPCommunity"];
		$this->ESX = $devRow["ESX"];
		$this->Owner = $devRow["Owner"];
		// Suppressing errors on the following two because they can be null and that generates an apache error
		@$this->EscalationTimeID = $devRow["EscalationTimeID"];
		@$this->EscalationID = $devRow["EscalationID"];
		$this->PrimaryContact = $devRow["PrimaryContact"];
		$this->Cabinet = $devRow["Cabinet"];
		$this->Position = $devRow["Position"];
		$this->Height = $devRow["Height"];
		$this->Ports = $devRow["Ports"];
		$this->TemplateID = $devRow["TemplateID"];
		$this->NominalWatts = $devRow["NominalWatts"];
		$this->PowerSupplyCount = $devRow["PowerSupplyCount"];
		$this->DeviceType = $devRow["DeviceType"];
		$this->ChassisSlots = $devRow["ChassisSlots"];
		$this->RearChassisSlots = $devRow["RearChassisSlots"];
		$this->ParentDevice = $devRow["ParentDevice"];
		$this->MfgDate = $devRow["MfgDate"];
		$this->InstallDate = $devRow["InstallDate"];
		$this->WarrantyCo = $devRow["WarrantyCo"];
		@$this->WarrantyExpire = $devRow["WarrantyExpire"];
		$this->Notes = $devRow["Notes"];
		$this->Reservation = $devRow["Reservation"];

		return true;
	}
	
	function GetDeviceChildren( $db ) {
		$sql = sprintf( "select * from fac_Device where ParentDevice='%d' order by ChassisSlots, Position ASC", intval( $this->DeviceID ) );
		$result = mysql_query( $sql, $db );
		
		$childList = array();
		while ( $row = mysql_fetch_array( $result ) ) {
			$childNum = sizeof( $childList );
			
			$childList[$childNum] = new Device();
			$childList[$childNum]->DeviceID = $row["DeviceID"];
			$childList[$childNum]->Label = $row["Label"];
			$childList[$childNum]->SerialNo = $row["SerialNo"];
			$childList[$childNum]->AssetTag = $row["AssetTag"];
			$childList[$childNum]->PrimaryIP = $row["PrimaryIP"];
			$childList[$childNum]->SNMPCommunity = $row["SNMPCommunity"];
			$childList[$childNum]->ESX = $row["ESX"];
			$childList[$childNum]->Owner = $row["Owner"];
			// Suppressing errors on the following two because they can be null and that generates an apache error
			@$childList[$childNum]->EscalationTimeID = $row["EscalationTimeID"];
			@$childList[$childNum]->EscalationID = $row["EscalationID"];
			$childList[$childNum]->PrimaryContact = $row["PrimaryContact"];
			$childList[$childNum]->Cabinet = $row["Cabinet"];
			$childList[$childNum]->Position = $row["Position"];
			$childList[$childNum]->Height = $row["Height"];
			$childList[$childNum]->Ports = $row["Ports"];
			$childList[$childNum]->TemplateID = $row["TemplateID"];
			$childList[$childNum]->NominalWatts = $row["NominalWatts"];
			$childList[$childNum]->PowerSupplyCount = $row["PowerSupplyCount"];
			$childList[$childNum]->DeviceType = $row["DeviceType"];
			$childList[$childNum]->ChassisSlots = $row["ChassisSlots"];
			$childList[$childNum]->RearChassisSlots = $row["RearChassisSlots"];
			$childList[$childNum]->ParentDevice = $row["ParentDevice"];
			$childList[$childNum]->MfgDate = $row["MfgDate"];
			$childList[$childNum]->InstallDate = $row["InstallDate"];
			$childList[$childNum]->WarrantyCo = $row["WarrantyCo"];
			@$childList[$childNum]->WarrantyExpire = $row["WarrantyExpire"];
			$childList[$childNum]->Notes = $row["Notes"];
			$childList[$childNum]->Reservation = $row["Reservation"];
		}
		
		return $childList;
	}
	
	function GetParentDevices( $db ) {
		$sql = sprintf( "select * from fac_Device where ChassisSlots>0 and ParentDevice=0 order by Label ASC" );
		$result = mysql_query( $sql, $db );
		
		$parentList = array();
		while ( $row = mysql_fetch_array( $result ) ) {
			$parentNum = sizeof( $parentList );
			
			$parentList[$parentNum] = new Device();
			$parentList[$parentNum]->DeviceID = $row["DeviceID"];
			$parentList[$parentNum]->Label = $row["Label"];
			$parentList[$parentNum]->SerialNo = $row["SerialNo"];
			$parentList[$parentNum]->AssetTag = $row["AssetTag"];
			$parentList[$parentNum]->PrimaryIP = $row["PrimaryIP"];
			$parentList[$parentNum]->SNMPCommunity = $row["SNMPCommunity"];
			$parentList[$parentNum]->ESX = $row["ESX"];
			$parentList[$parentNum]->Owner = $row["Owner"];
			// Suppressing errors on the following two because they can be null and that generates an apache error
			@$parentList[$parentNum]->EscalationTimeID = $row["EscalationTimeID"];
			@$parentList[$parentNum]->EscalationID = $row["EscalationID"];
			$parentList[$parentNum]->PrimaryContact = $row["PrimaryContact"];
			$parentList[$parentNum]->Cabinet = $row["Cabinet"];
			$parentList[$parentNum]->Position = $row["Position"];
			$parentList[$parentNum]->Height = $row["Height"];
			$parentList[$parentNum]->Ports = $row["Ports"];
			$parentList[$parentNum]->TemplateID = $row["TemplateID"];
			$parentList[$parentNum]->NominalWatts = $row["NominalWatts"];
			$parentList[$parentNum]->PowerSupplyCount = $row["PowerSupplyCount"];
			$parentList[$parentNum]->DeviceType = $row["DeviceType"];
			$parentList[$parentNum]->ChassisSlots = $row["ChassisSlots"];
			$parentList[$parentNum]->RearChassisSlots = $row["RearChassisSlots"];
			$parentList[$parentNum]->ParentDevice = $row["ParentDevice"];
			$parentList[$parentNum]->MfgDate = $row["MfgDate"];
			$parentList[$parentNum]->InstallDate = $row["InstallDate"];
			$parentList[$parentNum]->WarrantyCo = $row["WarrantyCo"];
			@$parentList[$parentNum]->WarrantyExpire = $row["WarrantyExpire"];
			$parentList[$parentNum]->Notes = $row["Notes"];
			$parentList[$parentNum]->Reservation = $row["Reservation"];
		}
		
		return $parentList;
	}

	function ViewDevicesByCabinet( $db ) {
		$select_sql = "select * from fac_Device where Cabinet=\"" . intval($this->Cabinet) . "\" order by Position DESC";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		$deviceList = array();

		while ( $deviceRow = mysql_fetch_array( $result ) ) {
			$devID = $deviceRow["DeviceID"];

			$deviceList[$devID] = new Device();

			$deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
 		  	$deviceList[$devID]->Label = $deviceRow["Label"];
			$deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
			$deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
			$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
			$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
			$deviceList[$devID]->ESX = $deviceRow["ESX"];
			$deviceList[$devID]->Owner = $deviceRow["Owner"];
			// Suppressing errors on the following two because they can be null and that generates an apache error
			@$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
			@$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
			$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
			$deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
			$deviceList[$devID]->Position = $deviceRow["Position"];
			$deviceList[$devID]->Height = $deviceRow["Height"];
			$deviceList[$devID]->Ports = $deviceRow["Ports"];
			$deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
			$deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
			$deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
			$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
			$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
			$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
			$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
			$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
			$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
			@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];
			$deviceList[$devID]->Notes = $deviceRow["Notes"];
			$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
		}

		return $deviceList;
	}
	
	function CreatePatchCandidateList( $db ) {
	  // This will generate a list of all devices capable of being plugged into a switch
	  // or patch panel - meaning that you set the DeviceID field to the target device and it will
	  // generate a list of all candidates that are in the same Data Center.
	  $dev=($this->ParentDevice>0)?intval($this->ParentDevice):intval($this->DeviceID);
	  $selectSQL = "select b.DataCenterID from fac_Device a, fac_Cabinet b where a.DeviceID=\"$dev\" and a.Cabinet=b.CabinetID";
	  $result = mysql_query( $selectSQL, $db );
	  
	  $row = mysql_fetch_array( $result );
	  $targetDC = $row["DataCenterID"];
	  
	  $selectSQL = "select * from fac_Device a, fac_Cabinet b where a.Cabinet=b.CabinetID and b.DataCenterID=\"" . intval($targetDC) . "\" and a.DeviceType in (\"Server\",\"Appliance\",\"Switch\",\"Chassis\") order by a.Label";
	  $result = mysql_query( $selectSQL, $db );
	  
	  $deviceList = array();
	  
		while ( $deviceRow = mysql_fetch_array( $result ) ) {
			$devID = $deviceRow["DeviceID"];

			$deviceList[$devID] = new Device();

			$deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
 		  	$deviceList[$devID]->Label = $deviceRow["Label"];
			$deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
			$deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
			$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
			$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
			$deviceList[$devID]->ESX = $deviceRow["ESX"];
			$deviceList[$devID]->Owner = $deviceRow["Owner"];
			// Suppressing errors on the following two because they can be null and that generates an apache error
			@$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
			@$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
			$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
			$deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
			$deviceList[$devID]->Position = $deviceRow["Position"];
			$deviceList[$devID]->Height = $deviceRow["Height"];
			$deviceList[$devID]->Ports = $deviceRow["Ports"];
			$deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
			$deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
			$deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
			$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
			$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
			$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
			$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
			$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
			$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
			@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];
			$deviceList[$devID]->Notes = $deviceRow["Notes"];
			$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
		}

		return $deviceList;
	}
	
	function DeleteDevice( $db ) {
		// First, see if this is a chassis that has children, if so, delete all of the children first
		if ( $this->ChassisSlots > 0 ) {
			$childList = $this->GetDeviceChildren( $db );
			
			foreach ( $childList as $tmpDev ) {
				$tmpDev->DeleteDevice( $db );
			}
		}
		
		// Delete all network connections first
		$tmpConn = new SwitchConnection();
		$tmpConn->SwitchDeviceID = $this->DeviceID;
		$tmpConn->EndpointDeviceID = $this->DeviceID;
		$tmpConn->DropSwitchConnections( $db );
		$tmpConn->DropEndpointConnections( $db );

		// Delete power connections next
		$powercon = new PowerConnection();
		$powercon->DeviceID = $this->DeviceID;
		$powercon->DeleteConnections( $db );

		// Now delete the device itself
		$rm_sql = "delete from fac_Device where DeviceID=\"" . intval($this->DeviceID) . "\"";

		if ( ! mysql_query( $rm_sql, $db ) ) {
			return -1;
		}

		return 0;
	}

	function SearchDevicebyLabel( $db ) {
		$searchSQL = "select * from fac_Device where Label like \"%" . addslashes(strtoupper( $this->Label )) . "%\" order by Label";

		if ( ! $result = mysql_query( $searchSQL, $db ) ) {
			return 0;
		}

		$deviceList = array();

		while ( $deviceRow = mysql_fetch_array( $result ) ) {
			$devID = $deviceRow["DeviceID"];

			$deviceList[$devID] = new Device();

			$deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
			$deviceList[$devID]->Label = $deviceRow["Label"];
			$deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
			$deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
			$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
			$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
			$deviceList[$devID]->ESX = $deviceRow["ESX"];
			$deviceList[$devID]->Owner = $deviceRow["Owner"];
			// Suppressing errors on the following two because they can be null and that generates an apache error
			@$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
			@$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
			$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
			$deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
			$deviceList[$devID]->Position = $deviceRow["Position"];
			$deviceList[$devID]->Height = $deviceRow["Height"];
			$deviceList[$devID]->Ports = $deviceRow["Ports"];
			$deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
			$deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
			$deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
			$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
			$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
			$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
			$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
			$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
			$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
			@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];
			$deviceList[$devID]->Notes = $deviceRow["Notes"];
			$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
		}

		return $deviceList;

	}

	function GetDevicesbyOwner( $db ) {
		$searchSQL = "select a.* from fac_Device a, fac_Cabinet b where a.Cabinet=b.CabinetID and a.Owner=\"" . addslashes($this->Owner) . "\" order by b.DataCenterID, a.Label";

		if ( ! $result = mysql_query( $searchSQL, $db ) ) {
			return 0;
		}

		$deviceList = array();

		while ( $deviceRow = mysql_fetch_array( $result ) ) {
			$devID = $deviceRow["DeviceID"];

			$deviceList[$devID] = new Device();

			$deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
			$deviceList[$devID]->Label = $deviceRow["Label"];
			$deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
			$deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
			$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
			$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
			$deviceList[$devID]->ESX = $deviceRow["ESX"];
			$deviceList[$devID]->Owner = $deviceRow["Owner"];
			$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
			$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
			$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
			$deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
			$deviceList[$devID]->Position = $deviceRow["Position"];
			$deviceList[$devID]->Height = $deviceRow["Height"];
			$deviceList[$devID]->Ports = $deviceRow["Ports"];
			$deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
			$deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
			$deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
			$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
			$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
			$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
			$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
			$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
			$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
			@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];
			$deviceList[$devID]->Notes = $deviceRow["Notes"];
			$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
		}

		return $deviceList;

	}

  function GetESXDevices( $db ) {
		$searchSQL = "select * from fac_Device where ESX=TRUE order by DeviceID";

		if ( ! $result = mysql_query( $searchSQL, $db ) ) {
			return 0;
		}

		$deviceList = array();

		while ( $deviceRow = mysql_fetch_array( $result ) ) {
			$devID = $deviceRow["DeviceID"];

			$deviceList[$devID] = new Device();

			$deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
			$deviceList[$devID]->Label = $deviceRow["Label"];
			$deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
			$deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
			$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
			$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
			$deviceList[$devID]->ESX = $deviceRow["ESX"];
			$deviceList[$devID]->Owner = $deviceRow["Owner"];
			$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
			$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
			$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
			$deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
			$deviceList[$devID]->Position = $deviceRow["Position"];
			$deviceList[$devID]->Height = $deviceRow["Height"];
			$deviceList[$devID]->Ports = $deviceRow["Ports"];
			$deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
			$deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
			$deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
			$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
			$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
			$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
			$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
			$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
			$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
			@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];			
			$deviceList[$devID]->Notes = $deviceRow["Notes"];
			$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
		}

		return $deviceList;

	}

  function SearchDevicebySerialNo( $db ) {
          $searchSQL = "select * from fac_Device where SerialNo like \"%" . addslashes(strtoupper( $this->SerialNo )) . "%\" order by Label";

          if ( ! $result = mysql_query( $searchSQL, $db ) ) {
                  return 0;
          }

          $deviceList = array();

          while ( $deviceRow = mysql_fetch_array( $result ) ) {
				$devID = $deviceRow["DeviceID"];

				$deviceList[$devID] = new Device();

				$deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
				$deviceList[$devID]->Label = $deviceRow["Label"];
				$deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
				$deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
				$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
				$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
				$deviceList[$devID]->ESX = $deviceRow["ESX"];
				$deviceList[$devID]->Owner = $deviceRow["Owner"];
				$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
				$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
				$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
				$deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
				$deviceList[$devID]->Position = $deviceRow["Position"];
				$deviceList[$devID]->Height = $deviceRow["Height"];
				$deviceList[$devID]->Ports = $deviceRow["Ports"];
				$deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
				$deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
				$deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
				$deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
				$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
				$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
				$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
				$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
				$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
				$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
				@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];				  
				$deviceList[$devID]->Notes = $deviceRow["Notes"];
				$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
          }

          return $deviceList;

  }

  function SearchDevicebyAssetTag( $db ) {
          $searchSQL = "select * from fac_Device where AssetTag like \"%" . addslashes(strtoupper( $this->AssetTag )) . "%\" order by Label";

          if ( ! $result = mysql_query( $searchSQL, $db ) ) {
                  return 0;
          }

          $deviceList = array();

          while ( $deviceRow = mysql_fetch_array( $result ) ) {
                $devID = $deviceRow["DeviceID"];

                $deviceList[$devID] = new Device();

                $deviceList[$devID]->DeviceID = $deviceRow["DeviceID"];
                $deviceList[$devID]->Label = $deviceRow["Label"];
                $deviceList[$devID]->SerialNo = $deviceRow["SerialNo"];
                $deviceList[$devID]->AssetTag = $deviceRow["AssetTag"];
				$deviceList[$devID]->PrimaryIP = $deviceRow["PrimaryIP"];
				$deviceList[$devID]->SNMPCommunity = $deviceRow["SNMPCommunity"];
            	$deviceList[$devID]->ESX = $deviceRow["ESX"];
            	$deviceList[$devID]->Owner = $deviceRow["Owner"];
				$deviceList[$devID]->EscalationTimeID = $deviceRow["EscalationTimeID"];
				$deviceList[$devID]->EscalationID = $deviceRow["EscalationID"];
            	$deviceList[$devID]->PrimaryContact = $deviceRow["PrimaryContact"];
                $deviceList[$devID]->Cabinet = $deviceRow["Cabinet"];
                $deviceList[$devID]->Position = $deviceRow["Position"];
                $deviceList[$devID]->Height = $deviceRow["Height"];
                $deviceList[$devID]->Ports = $deviceRow["Ports"];
                $deviceList[$devID]->TemplateID = $deviceRow["TemplateID"];
                $deviceList[$devID]->NominalWatts = $deviceRow["NominalWatts"];
                $deviceList[$devID]->PowerSupplyCount = $deviceRow["PowerSupplyCount"];
                $deviceList[$devID]->DeviceType = $deviceRow["DeviceType"];
				$deviceList[$devID]->ChassisSlots = $deviceRow["ChassisSlots"];
				$deviceList[$devID]->RearChassisSlots = $deviceRow["RearChassisSlots"];
				$deviceList[$devID]->ParentDevice = $deviceRow["ParentDevice"];
            	$deviceList[$devID]->MfgDate = $deviceRow["MfgDate"];
            	$deviceList[$devID]->InstallDate = $deviceRow["InstallDate"];
				$deviceList[$devID]->WarrantyCo = $deviceRow["WarrantyCo"];
				@$deviceList[$devID]->WarrantyExpire = $deviceRow["WarrantyExpire"];
				$deviceList[$devID]->Notes = $deviceRow["Notes"];
				$deviceList[$devID]->Reservation = $deviceRow["Reservation"];
          }

          return $deviceList;

  }

	function UpdateWattageFromTemplate( $db ) {
	   $selectSQL = "select * from fac_DeviceTemplate where TemplateID=\"" . intval($this->TemplateID) . "\"";
	   $result = mysql_query( $selectSQL, $db );
	 
  	 if ( $templateRow = mysql_fetch_array( $result ) ) {
  	   $this->NominalWatts = $templateRow["Wattage"];
  	 } else {
  	   $this->NominalWatts = 0;
  	 }
	}
	
	function GetTop10Tenants( $db ) {
    $selectSQL = "select sum(height) as RackUnits,fac_Department.Name as OwnerName from fac_Device,fac_Department where Owner is not NULL and fac_Device.Owner=fac_Department.DeptID group by Owner order by RackUnits DESC limit 0,10";
    $result = mysql_query( $selectSQL, $db );
    
    $deptList = array();
    
    while ( $row = mysql_fetch_array( $result ) )
      $deptList[$row["OwnerName"]] = $row["RackUnits"];
      
    return $deptList;
  }
  
  
  function GetTop10Power( $db ) {
    $selectSQL = "select sum(NominalWatts) as TotalPower,fac_Department.Name as OwnerName from fac_Device,fac_Department where Owner is not NULL and fac_Device.Owner=fac_Department.DeptID group by Owner order by TotalPower DESC limit 0,10";
    $result = mysql_query( $selectSQL, $db );
    
    $deptList = array();
    
    while ( $row = mysql_fetch_array( $result ) )
      $deptList[$row["OwnerName"]] = $row["TotalPower"];
      
    return $deptList;
  }
  
  
  function GetDeviceDiversity( $db ) {
    $pc = new PowerConnection();
    $PDU = new PowerDistribution();
	
	// If this is a child (card slot) device, then only the parent will have power connections defined
	if ( $this->ParentDevice > 0 ) {
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->ParentDevice;
		
		$sourceList = $tmpDev->GetDeviceDiversity( $db );
	} else {
		$pc->DeviceID = $this->DeviceID;
		$pcList = $pc->GetConnectionsByDevice( $db );
		
		$sourceList = array();
		$sourceCount = 0;
		
		foreach ( $pcList as $pcRow ) {
			$PDU->PDUID = $pcRow->PDUID;
			$powerSource = $PDU->GetSourceForPDU( $db );

			if ( ! in_array( $powerSource, $sourceList ) )
				$sourceList[$sourceCount++] = $powerSource;
		}
	}
	
    return $sourceList;
  }

  function GetSinglePowerByCabinet( $db ) {
    // Return an array of objects for devices that
    // do not have diverse (spread across 2 or more sources)
    // connections to power
    $pc = new PowerConnection();
    $PDU = new PowerDistribution();
    
    $sourceList = $this->ViewDevicesByCabinet( $db );

    $devList = array();
    
    foreach ( $sourceList as $devRow ) {    
      if ( ( $devRow->DeviceType == 'Patch Panel' || $devRow->DeviceType == 'Physical Infrastructure' || $devRow->ParentDevice > 0 ) && ( $devRow->PowerSupplyCount == 0 ) )
        continue;

      $pc->DeviceID = $devRow->DeviceID;
      
      $diversityList = $devRow->GetDeviceDiversity( $db );
      
      if ( sizeof( $diversityList ) < 2 ) {      
        $currSize = sizeof( $devList );
        
        $devList[$currSize] = new Device();

        $devList[$currSize]->DeviceID = $devRow->DeviceID;
        $devList[$currSize]->Label = $devRow->Label;
        $devList[$currSize]->SerialNo = $devRow->SerialNo;
        $devList[$currSize]->AssetTag = $devRow->AssetTag;
		$devList[$currSize]->PrimaryIP = $devRow->PrimaryIP;
		$devList[$currSize]->SNMPCommunity = $devRow->SNMPCommunity;
		$devList[$currSize]->ESX = $devRow->ESX;
		$devList[$currSize]->Owner = $devRow->Owner;
		$devList[$currSize]->EscalationTimeID = $devRow->EscalationTimeID;
		$devList[$currSize]->EscalationID = $devRow->EscalationID;
		$devList[$currSize]->PrimaryContact = $devRow->PrimaryContact;
        $devList[$currSize]->Cabinet = $devRow0>Cabinet;
        $devList[$currSize]->Position = $devRow->Position;
        $devList[$currSize]->Height = $devRow->Height;
        $devList[$currSize]->Ports = $devRow->Ports;
        $devList[$currSize]->TemplateID = $devRow->TemplateID;
        $devList[$currSize]->NominalWatts = $devRow->NominalWatts;
        $devList[$currSize]->PowerSupplyCount = $devRow->PowerSupplyCount;
        $devList[$currSize]->DeviceType = $devRow->DeviceType;
		$devList[$currSize]->MfgDate = $devRow->MfgDate;
		$devList[$currSize]->InstallDate = $devRow->InstallDate;
		$devList[$currSize]->WarrantyCo = $deviceRow["WarrantyCo"];
		@$devList[$currSize]->WarrantyExpire = $deviceRow["WarrantyExpire"];
		$devList[$currSize]->Notes = $devRow->Notes;
		$devList[$currSize]->Reservation = $devRow->Reservation;
      }
    }
    
    return $devList;
  }
}

class ESX {
	/*	ESX:	VMWare ESX has the ability to query via SNMP the virtual machines hosted
				on a device.  This allows an inventory of virtual machines to be created,
				and departments and contacts can be assigned to them, just as you can a
				physical system.
				
				Unfortunately Microsoft Hyper-V does not support SNMP queries for VM
				inventory, and the only remote access is through PowerShell, which is
				only supported on Windows systems.  Therefore, no support for Hyper-V is
				in this software.
				
				Any other virtualization technology that supports SNMP queries should be easy
				to add.
	*/
  var $VMIndex;
  var $DeviceID;
  var $LastUpdated;
  var $vmID;
  var $vmName;
  var $vmState;
  var $Owner;
  
  function EnumerateVMs( $dev, $debug ) {
    $community = $dev->SNMPCommunity;
    $serverIP = $dev->PrimaryIP;

    $vmList = array();

    $pollCommand = "/usr/bin/snmpwalk -v 2c -c $community $serverIP .1.3.6.1.4.1.6876.2.1.1.2 | /bin/cut -d: -f4 | /bin/cut -d\\\" -f2";
    @exec( $pollCommand, $namesOutput );

    $pollCommand = "/usr/bin/snmpwalk -v 2c -c $community $serverIP .1.3.6.1.4.1.6876.2.1.1.6 | /bin/cut -d: -f4 | /bin/cut -d\\\" -f2";
    @exec( $pollCommand, $statesOutput );

    if ( count( $namesOutput ) == count( $statesOutput) )
      $tempVMs = array_combine( $namesOutput, $statesOutput );
    else
      $tempVMs = array();

    $vmID = 0;

    if ( @count( $tempVMs ) > 0 ) {
      if ( $debug )
        printf( "\t%d VMs found\n", count( $tempVMs ) );
        
      foreach( $tempVMs as $key => $value ) {
                $vmList[$vmID] = new ESX();
                $vmList[$vmID]->DeviceID = $dev->DeviceID;
                $vmList[$vmID]->LastUpdated = date( 'y-m-d H:i:s' );
                $vmList[$vmID]->vmID = $vmID;
                $vmList[$vmID]->vmName = $key;
                $vmList[$vmID]->vmState = $value;

                $vmID++;
      }
    }

    return $vmList;
  }
  
  function UpdateInventory( $db, $debug ) {
    $dev = new Device();
    
    $devList = $dev->GetESXDevices( $db );
    
    foreach ( $devList as $esxDev ) {
      if ( $debug )
        printf( "Querying host %s @ %s...\n", $esxDev->Label, $esxDev->PrimaryIP );
        
      $vmList = $this->EnumerateVMs( $esxDev, $debug );
      if ( count( $vmList ) > 0 ) {
        foreach( $vmList as $vm ) {
          $searchSQL = "select * from fac_VMInventory where vmName=\"" . $vm->vmName . "\"";
          $result = mysql_query( $searchSQL, $db );
          
          if ( mysql_num_rows( $result ) > 0 ) {
            $updateSQL = "update fac_VMInventory set DeviceID=\"" . $vm->DeviceID . "\", LastUpdated=\"" . $vm->LastUpdated . "\", vmID=\"" . $vm->vmID . "\", vmState=\"" . $vm->vmState . "\" where vmName=\"" . $vm->vmName . "\"";
            $result = mysql_query( $updateSQL, $db );
          } else {
            $insertSQL = "insert into fac_VMInventory set DeviceID=\"" . $vm->DeviceID . "\", LastUpdated=\"" . $vm->LastUpdated . "\", vmID=\"" . $vm->vmID . "\", vmName=\"" . $vm->vmName . "\", vmState=\"" . $vm->vmState . "\"";
            $result = mysql_query( $insertSQL, $db );
          }
        }
      }
    }
  }
  
  function GetVMbyIndex( $db ) {
    $searchSQL = "select * from fac_VMInventory where VMIndex=\"" . $this->VMIndex . "\"";
    if ( $result = mysql_query( $searchSQL, $db ) ) {
      $vmRow = mysql_fetch_array( $result );
      
      $this->DeviceID = $vmRow["DeviceID"];
      $this->LastUpdated = $vmRow["LastUpdated"];
      $this->vmID = $vmRow["vmID"];
      $this->vmName = $vmRow["vmName"];
      $this->vmState = $vmRow["vmState"];
      $this->Owner = $vmRow["Owner"];
    }
    
    return;
  }
  
  function UpdateVMOwner( $db ) {
    $updateSQL = "update fac_VMInventory set Owner=\"" . $this->Owner . "\" where VMIndex=\"" . $this->VMIndex . "\"";
    $result = mysql_query( $updateSQL, $db );
  } 
  
  function GetInventory( $db ) {
    $selectSQL = "select * from fac_VMInventory order by DeviceID, vmName";
    $result = mysql_query( $selectSQL, $db );
    
    $vmList = array();
    $vmCount = 0;
  
    while ( $vmRow = mysql_fetch_array( $result ) ) {
      $vmList[$vmCount] = new ESX();
      $vmList[$vmCount]->VMIndex = $vmRow["VMIndex"];
      $vmList[$vmCount]->DeviceID = $vmRow["DeviceID"];
      $vmList[$vmCount]->LastUpdated = $vmRow["LastUpdated"];
      $vmList[$vmCount]->vmID = $vmRow["vmID"];
      $vmList[$vmCount]->vmName = $vmRow["vmName"];
      $vmList[$vmCount]->vmState = $vmRow["vmState"];
      $vmList[$vmCount]->Owner = $vmRow["Owner"];
      
      $vmCount++;
    }
    
    return $vmList; 
  }
  
  function GetDeviceInventory( $db ) {
    $selectSQL = "select * from fac_VMInventory where DeviceID=\"" . $this->DeviceID . "\" order by vmName";
    $result = mysql_query( $selectSQL, $db );
    
    $vmList = array();
    $vmCount = 0;
  
    while ( $vmRow = mysql_fetch_array( $result ) ) {      
      $vmList[$vmCount] = new ESX();
      $vmList[$vmCount]->VMIndex = $vmRow["VMIndex"];
      $vmList[$vmCount]->DeviceID = $vmRow["DeviceID"];
      $vmList[$vmCount]->LastUpdated = $vmRow["LastUpdated"];
      $vmList[$vmCount]->vmID = $vmRow["vmID"];
      $vmList[$vmCount]->vmName = $vmRow["vmName"];
      $vmList[$vmCount]->vmState = $vmRow["vmState"];
      $vmList[$vmCount]->Owner = $vmRow["Owner"];
      
      $vmCount++;
    }
   
    return $vmList; 
  }
  
  function GetVMListbyOwner( $db ) {
    $selectSQL = "select * from fac_VMInventory where Owner=\"" . $this->Owner . "\" order by DeviceID, vmName";
    $result = mysql_query( $selectSQL, $db );
    
    $vmList = array();
    $vmCount = 0;
  
    while ( $vmRow = mysql_fetch_array( $result ) ) {      
      $vmList[$vmCount] = new ESX();
      $vmList[$vmCount]->VMIndex = $vmRow["VMIndex"];
      $vmList[$vmCount]->DeviceID = $vmRow["DeviceID"];
      $vmList[$vmCount]->LastUpdated = $vmRow["LastUpdated"];
      $vmList[$vmCount]->vmID = $vmRow["vmID"];
      $vmList[$vmCount]->vmName = $vmRow["vmName"];
      $vmList[$vmCount]->vmState = $vmRow["vmState"];
      $vmList[$vmCount]->Owner = $vmRow["Owner"];
      
      $vmCount++;
    }
   
    return $vmList; 
  }
  
  function SearchByVMName( $db ) {
    $selectSQL = "select * from fac_VMInventory where ucase(vmName) like \"%" . strtoupper($this->vmName) . "%\"";
    $result = mysql_query( $selectSQL, $db );
    
    $vmList = array();
    $vmCount = 0;
  
    while ( $vmRow = mysql_fetch_array( $result ) ) {      
      $vmList[$vmCount] = new ESX();
      $vmList[$vmCount]->VMIndex = $vmRow["VMIndex"];
      $vmList[$vmCount]->DeviceID = $vmRow["DeviceID"];
      $vmList[$vmCount]->LastUpdated = $vmRow["LastUpdated"];
      $vmList[$vmCount]->vmID = $vmRow["vmID"];
      $vmList[$vmCount]->vmName = $vmRow["vmName"];
      $vmList[$vmCount]->vmState = $vmRow["vmState"];
      $vmList[$vmCount]->Owner = $vmRow["Owner"];
      
      $vmCount++;
    }
   
    return $vmList; 
  }
  
  function GetOrphanVMList( $db ) {
    $selectSQL = "select * from fac_VMInventory where Owner is NULL"; 
    $result = mysql_query( $selectSQL, $db );
    
    $vmList = array();
    $vmCount = 0;
  
    while ( $vmRow = mysql_fetch_array( $result ) ) {      
      $vmList[$vmCount] = new ESX();
      $vmList[$vmCount]->VMIndex = $vmRow["VMIndex"];
      $vmList[$vmCount]->DeviceID = $vmRow["DeviceID"];
      $vmList[$vmCount]->LastUpdated = $vmRow["LastUpdated"];
      $vmList[$vmCount]->vmID = $vmRow["vmID"];
      $vmList[$vmCount]->vmName = $vmRow["vmName"];
      $vmList[$vmCount]->vmState = $vmRow["vmState"];
      $vmList[$vmCount]->Owner = $vmRow["Owner"];
      
      $vmCount++;
    }
   
    return $vmList; 
  }

  function GetExpiredVMList( $numDays, $db ) {
    $selectSQL = "select * from fac_VMInventory where to_days(now())-to_days(LastUpdated)>$numDays"; 
    $result = mysql_query( $selectSQL, $db );
    
    $vmList = array();
    $vmCount = 0;
  
    while ( $vmRow = mysql_fetch_array( $result ) ) {      
      $vmList[$vmCount] = new ESX();
      $vmList[$vmCount]->VMIndex = $vmRow["VMIndex"];
      $vmList[$vmCount]->DeviceID = $vmRow["DeviceID"];
      $vmList[$vmCount]->LastUpdated = $vmRow["LastUpdated"];
      $vmList[$vmCount]->vmID = $vmRow["vmID"];
      $vmList[$vmCount]->vmName = $vmRow["vmName"];
      $vmList[$vmCount]->vmState = $vmRow["vmState"];
      $vmList[$vmCount]->Owner = $vmRow["Owner"];
      
      $vmCount++;
    }
   
    return $vmList; 
  }
  
  function ExpireVMs( $numDays, $db ) {
    // Don't allow calls to expire EVERYTHING
    if ( $numDays > 0 ) {
      $selectSQL = "delete from fac_VMInventory where to_days(now())-to_days(LastUpdated)>$numDays";
      $result = mysql_query( $selectSQL, $db );
    }
  }

}

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
  var $ESX;
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
  var $MfgDate;
  
  function CreateRequest( $db ) {
    $sql = "insert into fac_RackRequest set RequestTime=now(), 
        RequestorID=\"" . intval($this->RequestorID) . "\",
        Label=\"" . addslashes( strtoupper($this->Label )) . "\", 
        SerialNo=\"" . addslashes( strtoupper($this->SerialNo )) . "\",
		MfgDate=\"" . date("Y-m-d",strtotime($this->MfgDate)) . "\",
        AssetTag=\"" . addslashes( strtoupper($this->AssetTag )) . "\",
        ESX=\"" . intval($this->ESX) . "\",
        Owner=\"" . intval($this->Owner) . "\",
        DeviceHeight=\"" . intval($this->DeviceHeight) . "\", 
        EthernetCount=\"" . intval($this->EthernetCount) . "\", 
        VLANList=\"" . addslashes( $this->VLANList ) . "\", 
        SANCount=\"" . intval($this->SANCount) . "\", 
        SANList=\"" . addslashes( $this->SANList ) . "\",
        DeviceClass=\"" . addslashes($this->DeviceClass) . "\",
        DeviceType=\"" . addslashes($this->DeviceType) . "\", 
        LabelColor=\"" . addslashes($this->LabelColor) . "\", 
        CurrentLocation=\"" . addslashes( strtoupper($this->CurrentLocation) ) . "\", 
        SpecialInstructions=\"" . addslashes( $this->SpecialInstructions ) . "\"";
    
    $result = mysql_query( $sql, $db );
    
    $this->RequestID = mysql_insert_id( $db );
    
    return $result;
  }
  
  function GetOpenRequests( $db ) {
    $sql = "select * from fac_RackRequest where CompleteTime='0000-00-00 00:00:00'";
    
    $result = mysql_query( $sql, $db );
    
    $requestList = array();
    
    while ( $row = mysql_fetch_array( $result ) ) {
      $requestNum = sizeof( $requestList );
      
      $requestList[$requestNum]->RequestID = $row["RequestID"];
      $requestList[$requestNum]->RequestorID = $row["RequestorID"];
      $requestList[$requestNum]->RequestTime = $row["RequestTime"];
      $requestList[$requestNum]->CompleteTime = $row["CompleteTime"];
      $requestList[$requestNum]->Label = $row["Label"];
      $requestList[$requestNum]->SerialNo = $row["SerialNo"];
      $requestList[$requestNum]->AssetTag = $row["AssetTag"];
      $requestList[$requestNum]->ESX = $row["ESX"];
      $requestList[$requestNum]->Owner = $row["Owner"];
      $requestList[$requestNum]->DeviceHeight = $row["DeviceHeight"];
      $requestList[$requestNum]->EthernetCount = $row["EthernetCount"];
      $requestList[$requestNum]->VLANList = $row["VLANList"];
      $requestList[$requestNum]->SANCount = $row["SANCount"];
      $requestList[$requestNum]->SANList = $row["SANList"];
      $requestList[$requestNum]->DeviceClass = $row["DeviceClass"];
      $requestList[$requestNum]->DeviceType = $row["DeviceType"];
      $requestList[$requestNum]->LabelColor = $row["LabelColor"];
      $requestList[$requestNum]->CurrentLocation = $row["CurrentLocation"];
      $requestList[$requestNum]->SpecialInstructions = $row["SpecialInstructions"];
    }
    
    return $requestList;
  }
  
  function GetRequest( $db ) {
    $sql = "select * from fac_RackRequest where RequestID=\"" . $this->RequestID . "\"";
    $result = mysql_query( $sql, $db );
    
    $row = mysql_fetch_array( $result );
    
    $this->RequestorID = $row["RequestorID"];
    $this->RequestTime = $row["RequestTime"];
    $this->CompleteTime = $row["CompleteTime"];
    $this->Label = $row["Label"];
    $this->SerialNo = $row["SerialNo"];
    $this->MfgDate = $row["MfgDate"];
    $this->AssetTag = $row["AssetTag"];
    $this->ESX = $row["ESX"];
    $this->Owner = $row["Owner"];
    $this->DeviceHeight = $row["DeviceHeight"];
    $this->EthernetCount = $row["EthernetCount"];
    $this->VLANList = $row["VLANList"];
    $this->SANCount = $row["SANCount"];
    $this->SANList = $row["SANList"];
    $this->DeviceClass = $row["DeviceClass"];
    $this->DeviceType = $row["DeviceType"];
    $this->LabelColor = $row["LabelColor"];
    $this->CurrentLocation = $row["CurrentLocation"];
    $this->SpecialInstructions = $row["SpecialInstructions"];
  }
  
  function CompleteRequest( $db ) {
    $sql = "update fac_RackRequest set CompleteTime=now() where RequestID=\"" . $this->RequestID . "\"";
    mysql_query( $sql, $db );
  }
  
  function DeleteRequest( $db ) {
    $sql = "delete from fac_RackRequest where RequestID=\"" . intval( $this->RequestID ) . "\"";
    mysql_query( $sql, $db );
  }

  function UpdateRequest( $db ) {
    $sql = "update fac_RackRequest set 
        RequestorID=\"" . intval( $this->RequestorID ) . "\", 
        Label=\"" . addslashes( $this->Label ) . "\", 
        SerialNo=\"" . addslashes( $this->SerialNo ) . "\",
        AssetTag=\"" . addslashes( $this->AssetTag ) . "\",
        ESX=\"" . $this->ESX . "\",
        Owner=\"" . $this->Owner . "\",
        DeviceHeight=\"" . $this->DeviceHeight . "\", 
        EthernetCount=\"" . $this->EthernetCount . "\", 
        VLANList=\"" . addslashes( $this->VLANList ) . "\", 
        SANCount=\"" . $this->SANCount . "\", 
        SANList=\"" . addslashes( $this->SANList ) . "\",
        DeviceClass=\"" . $this->DeviceClass . "\",
        DeviceType=\"" . $this->DeviceType . "\", 
        LabelColor=\"" . $this->LabelColor . "\", 
        CurrentLocation=\"" . addslashes( $this->CurrentLocation ) . "\", 
        SpecialInstructions=\"" . addslashes( $this->SpecialInstructions ) . "\"
        where RequestID=\"" . intval($this->RequestID) . "\"";
    
    $result = mysql_query( $sql, $db );
  }  
}

class SwitchConnection {
	/* SwitchConnection:	Self explanatory - any device set as a switch will allow you to map out the port connections to
							any other device within the same data center.  For trans-data center connections, you can map the
							port back to itself, and list the external source in the Notes field.
	*/
	
  var $SwitchDeviceID;
  var $SwitchPortNumber;
  var $EndpointDeviceID;
  var $EndpointPort;
  var $Notes;

  function CreateConnection( $db ) {
    $insertSQL = "insert into fac_SwitchConnection set SwitchDeviceID=\"" . $this->SwitchDeviceID . "\", SwitchPortNumber=\"" . $this->SwitchPortNumber . "\", EndpointDeviceID=\"" . $this->EndpointDeviceID . "\", EndpointPort=\"" . $this->EndpointPort . "\", Notes=\"" . addslashes( $this->Notes ) . "\" on duplicate key update EndpointDeviceID=\"" . $this->EndpointDeviceID . "\", EndpointPort=\"" . $this->EndpointPort . "\", Notes=\"" . addslashes( $this->Notes ) . "\"";
    
    $result = mysql_query( $insertSQL, $db );
    
    return $result;
  }
  
  function UpdateConnection( $db ) {
    $sql = "update fac_SwitchConnection set EndpointDeviceID=\"" . intval( $this->EndpointDeviceID ) . "\", EndpointPort=\"" . intval( $this->EndpointPort ) . "\", Notes=\"" . addslashes( $this->Notes ) . "\" where SwitchDeviceID=\"" . intval( $this->SwitchDeviceID ) . "\" and SwitchPortNumber=\"" . intval( $this->SwitchPortNumber ) . "\"";
    
    $result = mysql_query( $sql, $db );
    
    return $result;
  }
    
  function RemoveConnection( $db ) {
    $delSQL = "delete from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" and SwitchPortNumber=\"" . $this->SwitchPortNumber . "\"";
  
    $result = mysql_query( $delSQL, $db );
    
    return $result;
  }
  
  function DropEndpointConnections( $db ) {
    $delSQL = "delete from fac_SwitchConnection where EndpointDeviceID=\"" . $this->EndpointDeviceID . "\"";
    
    $result = mysql_query( $delSQL, $db );
    
    return $result;
  }
  
  function DropSwitchConnections( $db ) {
    $delSQL = "delete from fac_SwitchConnections where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\"";
    
    $result = mysql_query( $delSQL, $db );
    
    return $result;
  }

  function GetSwitchConnections( $db ) {
    $selectSQL = "select * from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" order by SwitchPortNumber";
    
    $result = mysql_query( $selectSQL, $db );
    
    $tmpDev = new Device();
    $tmpDev->DeviceID = $this->SwitchDeviceID;
    $tmpDev->GetDevice( $db );
    
    for ( $i = 1; $i <= $tmpDev->Ports; $i++ ) {
      $connList[$i] = new SwitchConnection();
      $connList[$i]->SwitchDeviceID = $tmpDev->DeviceID;
      $connList[$i]->SwitchPortNumber = $i;
    }      
    
    while ( $connRow = mysql_fetch_array( $result ) ) {
      $connNum = $connRow["SwitchPortNumber"];
      $connList[$connNum]->SwitchDeviceID = $connRow["SwitchDeviceID"];
      $connList[$connNum]->SwitchPortNumber = $connRow["SwitchPortNumber"];
      $connList[$connNum]->EndpointDeviceID = $connRow["EndpointDeviceID"];
      $connList[$connNum]->EndpointPort = $connRow["EndpointPort"];
      $connList[$connNum]->Notes = $connRow["Notes"];
    }
    
    return $connList;
  }
  
  function GetSwitchPortConnector( $db ) {
    $selectSQL = "select * from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" and SwitchPortNumber=\"" . $this->SwitchPortNumber . "\"";
    
    $result = mysql_query( $selectSQL, $db );
    
    if ( $row = mysql_fetch_array( $result ) ) {
      $this->EndpointDeviceID = $row["EndpointDeviceID"];
      $this->EndpointPort = $row["EndpointPort"];
      $this->Notes = $row["Notes"];
    }
    
    return;
  }
  
  function GetEndpointConnections( $db ) {
    $selectSQL = "select * from fac_SwitchConnection where EndpointDeviceID=\"" . $this->EndpointDeviceID . "\" order by EndpointPort";
    
    $result = mysql_query( $selectSQL, $db );
    
    $connList = array();
    
    while ( $connRow = mysql_fetch_array( $result ) ) {
      $numConnects = sizeof( $connList );
      
      $connList[$numConnects] = new SwitchConnection();
      $connList[$numConnects]->SwitchDeviceID = $connRow["SwitchDeviceID"];
      $connList[$numConnects]->SwitchPortNumber = $connRow["SwitchPortNumber"];
      $connList[$numConnects]->EndpointDeviceID = $connRow["EndpointDeviceID"];
      $connList[$numConnects]->EndpointPort = $connRow["EndpointPort"];
      $connList[$numConnects]->Notes = $connRow["Notes"];
    }
    
    return $connList;
  }  
}

class SupplyBin {
	var $BinID;
	var $Location;
	
	function GetBin( $db ) {
		$sql = sprintf( "select * from fac_SupplyBin where BinID='%d'", intval( $this->BinID ) );
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->Location = $row["Location"];
		}
	}
	
	function AddBin( $db ) {
		$sql = sprintf( "insert into fac_SupplyBin set Location=\"%s\"", addslashes( $this->Location ) );
		mysql_query( $sql, $db );
		
		$this->BinID = mysql_insert_id( $db );
	}
	
	function UpdateBin( $db ) {
		$sql = sprintf( "update fac_SupplyBin set Location=\"%s\" where BinID='%d'", addslashes( $this->Location ), intval( $this->BinID ) );
		mysql_query( $sql, $db );	
	}
	
	function DeleteBin( $db ) {
		$sql = sprintf( "delete from fac_SupplyBin where BinID='%d'; delete from fac_BinContents where BinID='%d'; delete from fac_BinAudits where BinID='%d'", intval( $this->BinID ), intval( $this->BinID ), intval( $this->BinID ) );
		mysql_query( $sql, $db );
	}
	
	function GetBinList( $db ) {
		$sql = sprintf( "select * from fac_SupplyBin order by Location ASC" );
		$result = mysql_query( $sql, $db );
		
		$binList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$binNum = sizeof( $binList );
			$binList[$binNum] = new SupplyBin();
			
			$binList[$binNum]->BinID = $row["BinID"];
			$binList[$binNum]->Location = $row["Location"];
		}
		
		return $binList;
	}
}

class Supplies {
	var $SupplyID;
	var $PartNum;
	var $PartName;
	var $MinQty;
	var $MaxQty;
	
	function AddSupplies( $db ) {
		$sql = sprintf( "insert into fac_Supplies set PartNum=\"%s\", PartName=\"%s\", MinQty='%d', MaxQty='%d'", addslashes( $this->PartNum ), addslashes( $this->PartName ), intval( $this->MinQty ), intval( $this->MaxQty ) );
		mysql_query( $sql, $db );
		
		$this->SupplyID = mysql_insert_id( $db );
	}
	
	function GetSupplies( $db ) {
		$sql = sprintf( "select * from fac_Supplies where SupplyID='%d'", intval( $this->SupplyID ) );
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->SupplyID = $row["SupplyID"];
			$this->PartNum = $row["PartNum"];
			$this->PartName = $row["PartName"];
			$this->MinQty = $row["MinQty"];
			$this->MaxQty = $row["MaxQty"];
		}
	}
	
	function GetSuppliesList($db){
		$sql="select * from fac_Supplies order by PartNum ASC";
		$result=mysql_query($sql,$db);
		
		$supplyList=array();
		
		while($row=mysql_fetch_array($result)){
			$supplyList[$row["SupplyID"]]=new Supplies();
			
			$supplyList[$row["SupplyID"]]->SupplyID=$row["SupplyID"];
			$supplyList[$row["SupplyID"]]->PartNum=$row["PartNum"];
			$supplyList[$row["SupplyID"]]->PartName=$row["PartName"];
			$supplyList[$row["SupplyID"]]->MinQty=$row["MinQty"];
			$supplyList[$row["SupplyID"]]->MaxQty=$row["MaxQty"];
		}
		
		return $supplyList;
	}
	
	function UpdateSupplies($db){
		$sql=sprintf( "update fac_Supplies set PartNum=\"%s\", PartName=\"%s\", MinQty='%d', MaxQty='%d' where SupplyID='%d'", addslashes( $this->PartNum ), addslashes( $this->PartName ), intval( $this->MinQty ), intval( $this->MaxQty ), intval( $this->SupplyID ) );
		mysql_query($sql,$db);
	}
	
	function DeleteSupplies( $db ) {
		$sql = sprintf( "delete from fac_Supplies where SupplyID='%d'", intval( $this->SupplyID ) );
		mysql_query( $sql, $db );
	}
}

class BinContents {
	var $BinID;
	var $SupplyID;
	var $Count;
	
	function AddContents( $db ) {
		$sql = sprintf( "insert into fac_BinContents set BinID='%d', SupplyID='%d', Count='%d'", intval( $this->BinID ), intval( $this->SupplyID ), intval( $this->Count ) );
		mysql_query( $sql, $db );
	}
	
	function GetBinContents( $db ) {
		/* Return all of the supplies found in this bin */
		$sql = sprintf( "select * from fac_BinContents where BinID='%d'", intval( $this->BinID ) );
		$result = mysql_query( $sql, $db );
		
		$binList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$num = sizeof( $binList );
			$binList[$num] = new BinContents();
			
			$binList[$num]->BinID = $row["BinID"];
			$binList[$num]->SupplyID = $row["SupplyID"];
			$binList[$num]->Count = $row["Count"];
		}
		
		return $binList;
	}
	
	function FindSupplies( $db ) {
		/* Return all of the bins where this SupplyID is found */
		$sql = sprintf( "select a.* from fac_BinContents a, fac_SupplyBin b where a.SupplyID='%d' and a.BinID=b.BinID order by b.Location ASC", intval( $this->SupplyID ) );
		$result = mysql_query( $sql, $db );

		$binList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$num = sizeof( $binList );
			$binList[$num] = new BinContents();
			
			$binList[$num]->BinID = $row["BinID"];
			$binList[$num]->SupplyID = $row["SupplyID"];
			$binList[$num]->Count = $row["Count"];
		}
		
		return $binList;		
	}
	
	function UpdateCount( $db ) {
		$sql = sprintf( "update fac_BinContents set Count='%d' where BinID='%d' and SupplyID='%d'", intval( $this->Count ), intval( $this->BinID ), intval( $this->SupplyID ) );
		mysql_query( $sql, $db );
	}
	
	function RemoveContents( $db ) {
		$sql = sprintf( "delete from fac_BinContents where BinID='%d' and SupplyID='%d'", intval( $this->BinID ), intval( $this->SupplyID ) );
		mysql_query( $sql, $db );
	}
	
	function EmptyBin( $db ) {
		$sql = sprintf( "delete from fac_BinContents where BinID='%d'", intval( $this->BinID ) );
		mysql_query( $sql, $db );
	}
}

class BinAudits {
	var $BinID;
	var $UserID;
	var $AuditStamp;
	
	function AddAudit( $db ) {
		$sql = sprintf( "insert into fac_BinAudits set BinID='%d', UserID=\"%d\", AuditStamp=\"%s\"", intval( $this->BinID ), addslashes( $this->UserID ), date( "Y-m-d", strtotime( $this->AuditStamp ) ) );
		mysql_query( $sql, $db );
	}
}
?>
