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


class PowerConnection {
	/* PowerConnection:		A mapping of power strip (PDU) ports to the devices connected to them.
							Devices are limited to those within the same cabinet as the power strip,
							as connecting power across cabinets is not just a BAD PRACTICE, it's
							outright idiotic, except in temporary situations.
	*/
	
	var $PDUID;
	var $PDUPosition;
	var $DeviceID;
	var $DeviceConnNumber;

	function CreateConnection($db){
		// Clear out any existing connections first
		$sql="delete from fac_PowerConnection where PDUID=\"".intval($this->PDUID)."\" and PDUPosition=\"".intval($this->PDUPosition)."\"";
		mysql_query($sql,$db);

		$insert_sql="insert into fac_PowerConnection set DeviceID=\"".intval($this->DeviceID)."\", DeviceConnNumber=\"".intval($this->DeviceConnNumber)."\", PDUID=\"".intval($this->PDUID)."\", PDUPosition=\"".intval($this->PDUPosition)."\"";
		if(!$result=mysql_query($insert_sql,$db)){
			return -1;
		}

		return 0;
	}
	
	function DeleteConnections( $db ) {
	/* This function is called when deleting a device, and will remove ALL connections for the specified device. */
	 $rm_sql = "delete from fac_PowerConnection where DeviceID=\"" . intval($this->DeviceID) . "\"";
	 if ( ! $result = mysql_query( $rm_sql, $db ) )
	   die( "Unable to remove power connections from database table fac_PowerConnection." );
	}
	
	function RemoveConnection( $db ) {
		/* This function is called when removing a single connection, specified by the unique combination of PDU ID and PDU Position. */
		$sql = "delete from fac_PowerConnection where PDUID=\"" . intval( $this->PDUID ) . "\" and PDUPosition=\"" . intval( $this->PDUPosition ) . "\"";
		mysql_query( $sql, $db );
		
		return;
	}

  function GetPDUConnectionByPosition( $db ) {
    $select_sql = "select * from fac_PowerConnection where PDUID=\"" . intval($this->PDUID) . "\" and PDUPosition=\"" . intval($this->PDUPosition) . "\"";
    
    $result = mysql_query( $select_sql, $db );
    
    if ( $connRow = mysql_fetch_array( $result ) ) {
        $this->PDUID = $connRow["PDUID"];
        $this->PDUPosition = $connRow["PDUPosition"];
        $this->DeviceID = $connRow["DeviceID"];
        $this->DeviceConnNumber = $connRow["DeviceConnNumber"];
    }
  }
  
  function GetConnectionsByPDU( $db ) {
    $select_sql="select * from fac_PowerConnection where PDUID=\"" . intval($this->PDUID) . "\" order by PDUPosition";
    $result=mysql_query($select_sql,$db);

    $connList=array();
    
	while($connRow=mysql_fetch_array($result)){
		$connNum=$connRow["PDUPosition"];
		$connList[$connNum]=new PowerConnection;
		$connList[$connNum]->PDUID=$connRow["PDUID"];
		$connList[$connNum]->PDUPosition=$connRow["PDUPosition"];
		$connList[$connNum]->DeviceID=$connRow["DeviceID"];
		$connList[$connNum]->DeviceConnNumber=$connRow["DeviceConnNumber"];
	}
    return $connList;
  }
  
  function GetConnectionsByDevice( $db ) {
    $select_sql = "select * from fac_PowerConnection where DeviceID=\"" . intval($this->DeviceID) . "\" order by PDUID, PDUPosition";

    $result = mysql_query( $select_sql, $db );

    $connList = array();
    $connNum = 0;
    
    while ( $connRow = mysql_fetch_array( $result ) ) {
      $connList[$connNum] = new PowerConnection;
      
      $connList[$connNum]->PDUID = $connRow["PDUID"];
      $connList[$connNum]->PDUPosition = $connRow["PDUPosition"];
      $connList[$connNum]->DeviceID = $connRow["DeviceID"];
      $connList[$connNum]->DeviceConnNumber = $connRow["DeviceConnNumber"];
      
      $connNum++;
    }
    
    return $connList;
  }    
}

class PowerDistribution {
	/* PowerDistribution:	A power strip, essentially.  Intelligent power strips from APC, Geist Manufacturing,
							and Server Technologies are supported for polling of amperage.  Future implementation
							will include temperature/humidity probe data for inclusion on the data center mapping.
							Non-monitored power strips are also supported, but simply won't have data regarding
							current load.
							
							Power strips are mapped to the panel / circuit, and panels are mapped to the power source,
							which is then wrapped up at the data center level.
	*/
	
	var $PDUID;
	var $Label;
	var $CabinetID;
	var $InputAmperage;
	var $ManagementType;
	var $Model;
	var $NumOutputs;
	var $IPAddress;
	var $SNMPCommunity;
	var $FirmwareVersion;
  	var $PanelID;
	var $BreakerSize;
	var $PanelPole;
	var $FailSafe;
	var $PanelID2;
	var $PanelPole2;

	function CreatePDU( $db ) {
		$insert_sql = "insert into fac_PowerDistribution set Label=\"" . addslashes($this->Label) . "\", CabinetID=\"" . intval($this->CabinetID) . "\",  InputAmperage=\"" . intval( $this->InputAmperage ) . "\", ManagementType=\"" . $this->ManagementType . "\", Model=\"" . addslashes($this->Model) . "\", NumOutputs=\"" . intval($this->NumOutputs) . "\", IPAddress=\"" . addslashes($this->IPAddress) . "\", SNMPCommunity=\"" . addslashes($this->SNMPCommunity) . "\", PanelID=\"" . intval($this->PanelID) . "\", BreakerSize=\"" . intval( $this->BreakerSize ) . "\", PanelPole=\"" . intval($this->PanelPole) . "\", FailSafe=\"" . intval($this->FailSafe) . "\", PanelID2=\"" . intval($this->PanelID2) . "\", PanelPole2=\"" . intval($this->PanelPole2) . "\"";

		if ( ! $result = mysql_query( $insert_sql, $db ) ) {
			return -1;
		}

		$this->PDUID = mysql_insert_id( $db );

		return $this->PDUID;
	}

	function UpdatePDU( $db ) {
		$update_sql = "update fac_PowerDistribution set Label=\"" . addslashes($this->Label) . "\", CabinetID=\"" . intval($this->CabinetID) . "\", InputAmperage=\"" . intval( $this->InputAmperage ) . "\", ManagementType=\"" . $this->ManagementType . "\", Model=\"" . addslashes($this->Model) . "\", NumOutputs=\"" . intval($this->NumOutputs) . "\", IPAddress=\"" . addslashes($this->IPAddress) . "\", SNMPCommunity=\"" . addslashes($this->SNMPCommunity) . "\", PanelID=\"" . intval($this->PanelID) . "\", BreakerSize=\"" . intval( $this->BreakerSize ) . "\", PanelPole=\"" . intval($this->PanelPole) . "\", FailSafe=\"" . intval($this->FailSafe) . "\", PanelID2=\"" . intval($this->PanelID2) . "\", PanelPole2=\"" . intval($this->PanelPole2) . "\" where PDUID=\"" . intval($this->PDUID) . "\"";

		return mysql_query( $update_sql, $db );
	}

	function GetSourceForPDU( $db ) {
		$this->GetPDU( $db );

		$panel = new PowerPanel();

		$panel->PanelID = $this->PanelID;
		$panel->GetPanel( $db );

		return $panel->PowerSourceID;
	}
  
	function GetPDU( $db ) {
		$select_sql = "select * from fac_PowerDistribution where PDUID=\"" . intval($this->PDUID) . "\"";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return -1;
		}

		if ( $PDUrow = mysql_fetch_array( $result ) ) {
			$this->CabinetID = $PDUrow["CabinetID"];
			$this->Label = stripslashes($PDUrow["Label"]);
			$this->InputAmperage = $PDUrow["InputAmperage"];
			$this->ManagementType = $PDUrow["ManagementType"];
			$this->Model = stripslashes($PDUrow["Model"]);
			$this->NumOutputs = $PDUrow["NumOutputs"];
			$this->IPAddress = stripslashes($PDUrow["IPAddress"]);
			$this->SNMPCommunity = stripslashes($PDUrow["SNMPCommunity"]);
			$this->FirmwareVersion = $PDUrow["FirmwareVersion"];
			$this->PanelID = $PDUrow["PanelID"];
			$this->BreakerSize = $PDUrow["BreakerSize"];
			$this->PanelPole = $PDUrow["PanelPole"];
			$this->FailSafe = $PDUrow["FailSafe"];
			$this->PanelID2 = $PDUrow["PanelID2"];
			$this->PanelPole2 = $PDUrow["PanelPole2"];
		} else {
			$this->CabinetID = null;
			$this->Label = null;
			$this->InputAmperage = null;
			$this->ManagementType = null;
			$this->Model = null;
			$this->NumOutputs = null;
			$this->IPAddress = null;
			$this->SNMPCommunity = null;
			$this->FirmwareVersion = null;
			$this->PanelID = null;
			$this->BreakerSize = null;
			$this->PanelPole = null;
			$this->FailSafe = null;
			$this->PanelID2 = null;
			$this->PanelPole2 = null;
		}

		return 0;
	}

	function GetPDUbyCabinet( $db ) {
		$select_sql = sprintf( "select * from fac_PowerDistribution where CabinetID=\"%d\"", intval( $this->CabinetID ) );

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		$PDUList = array();

		while ( $PDUrow = mysql_fetch_array( $result ) ) {
			$PDUID = sizeof( $PDUList );
			$PDUList[$PDUID] = new PowerDistribution();

			$PDUList[$PDUID]->PDUID = $PDUrow["PDUID"];
			$PDUList[$PDUID]->Label = stripslashes($PDUrow["Label"]);
			$PDUList[$PDUID]->CabinetID = $PDUrow["CabinetID"];
			$PDUList[$PDUID]->InputAmperage = $PDUrow["InputAmperage"];
			$PDUList[$PDUID]->ManagementType=$PDUrow["ManagementType"];
			$PDUList[$PDUID]->Model = stripslashes($PDUrow["Model"]);
			$PDUList[$PDUID]->NumOutputs = $PDUrow["NumOutputs"];
			$PDUList[$PDUID]->IPAddress = stripslashes($PDUrow["IPAddress"]);
			$PDUList[$PDUID]->SNMPCommunity = stripslashes($PDUrow["SNMPCommunity"]);
			$PDUList[$PDUID]->FirmwareVersion = $PDUrow["FirmwareVersion"];
			$PDUList[$PDUID]->PanelID = $PDUrow["PanelID"];
			$PDUList[$PDUID]->BreakerSize = $PDUrow["BreakerSize"];
			$PDUList[$PDUID]->PanelPole = $PDUrow["PanelPole"];
			$PDUList[$PDUID]->FailSafe = $PDUrow["FailSafe"];
			$PDUList[$PDUID]->PanelID2 = $PDUrow["PanelID2"];
			$PDUList[$PDUID]->PanelPole2 = $PDUrow["PanelPole2"];
		}

		return $PDUList;
	}
	
	function SearchByPDUName($db){
		$select_sql="select * from fac_PowerDistribution where ucase(Label) like \"%".strtoupper($this->Label)."%\";";

		if(!$result=mysql_query($select_sql,$db)){
			return 0;
		}

		$PDUList = array();

		while($PDUrow=mysql_fetch_array($result)){
			$PDUID=sizeof($PDUList);
			$PDUList[$PDUID]=new PowerDistribution();

			$PDUList[$PDUID]->PDUID=$PDUrow["PDUID"];
			$PDUList[$PDUID]->Label=stripslashes($PDUrow["Label"]);
			$PDUList[$PDUID]->CabinetID=$PDUrow["CabinetID"];
			$PDUList[$PDUID]->InputAmperage=$PDUrow["InputAmperage"];
			$PDUList[$PDUID]->ManagementType=$PDUrow["ManagementType"];
			$PDUList[$PDUID]->Model=stripslashes($PDUrow["Model"]);
			$PDUList[$PDUID]->NumOutputs=$PDUrow["NumOutputs"];
			$PDUList[$PDUID]->IPAddress=stripslashes($PDUrow["IPAddress"]);
			$PDUList[$PDUID]->SNMPCommunity=stripslashes($PDUrow["SNMPCommunity"]);
			$PDUList[$PDUID]->FirmwareVersion=$PDUrow["FirmwareVersion"];
			$PDUList[$PDUID]->PanelID=$PDUrow["PanelID"];
			$PDUList[$PDUID]->BreakerSize=$PDUrow["BreakerSize"];
			$PDUList[$PDUID]->PanelPole=$PDUrow["PanelPole"];
			$PDUList[$PDUID]->FailSafe=$PDUrow["FailSafe"];
			$PDUList[$PDUID]->PanelID2=$PDUrow["PanelID2"];
			$PDUList[$PDUID]->PanelPole2=$PDUrow["PanelPole2"];
		}

		return $PDUList;
	}

	function GetPDUbyPanel( $db ) {
		$select_sql = "select * from fac_PowerDistribution where PanelID=\"" . intval($this->PanelID) . "\" or PanelID2=\"" . intval( $this->PanelID ) . "\" order by PanelPole ASC";

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		$PDUList = array();

		while ( $PDUrow = mysql_fetch_array( $result ) ) {
			$PDUID = $PDUrow["PDUID"];
			$PDUlist[$PDUID] = new PowerDistribution();

			$PDUList[$PDUID]->PDUID = $PDUID;
			$PDUList[$PDUID]->Label = stripslashes($PDUrow["Label"]);
			$PDUList[$PDUID]->CabinetID = $PDUrow["CabinetID"];
			$PDUList[$PDUID]->InputAmperage = $PDUrow["InputAmperage"];
			$PDUList[$PDUID]->ManagementType=$PDUrow["ManagementType"];
			$PDUList[$PDUID]->Model = stripslashes($PDUrow["Model"]);
			$PDUList[$PDUID]->NumOutputs = $PDUrow["NumOutputs"];
			$PDUList[$PDUID]->IPAddress = stripslashes($PDUrow["IPAddress"]);
			$PDUList[$PDUID]->SNMPCommunity = stripslashes($PDUrow["SNMPCommunity"]);
			$PDUList[$PDUID]->FirmwareVersion = $PDUrow["FirmwareVersion"];
			$PDUList[$PDUID]->PanelID = $PDUrow["PanelID"];
			$PDUList[$PDUID]->BreakerSize = $PDUrow["BreakerSize"];
			$PDUList[$PDUID]->PanelPole = $PDUrow["PanelPole"];
			$PDUList[$PDUID]->FailSafe = $PDUrow["FailSafe"];
			$PDUList[$PDUID]->PanelID2 = $PDUrow["PanelID2"];
			$PDUList[$PDUID]->PanelPole2 = $PDUrow["PanelPole2"];
		}

		return $PDUList;
	}

	function GetAmperage( $db ) {
		$selectSQL = "select * from fac_PDUStats where PDUID=\"" . intval($this->PDUID) . "\"";
		if ( $result = mysql_query( $selectSQL, $db ) ) {
		  $pduRow = mysql_fetch_array( $result );
		  if ($pduRow["TotalAmps"]!='')
			return $pduRow["TotalAmps"];
		  else
			return 0;
		}
	}
  
	function UpdateStats( $db ) {
		// Automatically pull the current amperage per phase from a Server Technologies SmartCDU
		$selectSQL = "select * from fac_PowerDistribution where IPAddress<>'' and SNMPCommunity<>''";
		$result = mysql_query( $selectSQL, $db );

		while ( $pduRow = mysql_fetch_array( $result ) ) {
			$statsOutput = "";
			$PDUID = $pduRow["PDUID"];
			$serverIP = $pduRow["IPAddress"];
			$community = $pduRow["SNMPCommunity"];
		  
			if ( $pduRow["BreakerSize"] == "208VAC 2-Pole" )
			  $threePhase = false;
			else
			  $threePhase = true;

			if (strtoupper(substr($pduRow["ManagementType"],0,5))==='GEIST') {
				$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP .1.3.6.1.4.1.21239.2.25.1.8.1 .1.3.6.1.4.1.21239.2.25.1.16.1 .1.3.6.1.4.1.21239.2.25.1.24.1 | /bin/cut -d: -f4";
				$conversionRatio=10;
			} elseif (strtoupper(substr($pduRow["ManagementType"],0,6))==='SERVER') {
				$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP .1.3.6.1.4.1.1718.3.2.2.1.7.1.1 .1.3.6.1.4.1.1718.3.2.2.1.7.1.2 .1.3.6.1.4.1.1718.3.2.2.1.7.1.3 | /bin/cut -d: -f4";
				$conversionRatio=100;
			} elseif (strtoupper(substr($pduRow["ManagementType"],0,3))==='APC') {
				$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP .1.3.6.1.4.1.318.1.1.12.2.3.1.1.2.1 | /bin/cut -d: -f4";
				$conversionRatio = 10;
			} else
				continue;

		  
			exec( $pollCommand, $statsOutput );
		  
			if ( count( $statsOutput ) > 0 ) {
				$phaseA = $statsOutput[0] / $conversionRatio;
				$phaseB=$phaseC=0;

				if ( $threePhase && (strtoupper(substr($pduRow["ManagementType"],0,3))!='APC')) {
					$phaseB = $statsOutput[1] / $conversionRatio;
					$phaseC = $statsOutput[2] / $conversionRatio;
					$TotalAmps = (( $phaseA + $phaseB + $phaseC ) / 3) * 1.732;
				} else
					$TotalAmps = $phaseA + $phaseB + $phaseC;
		  
				$clearSQL = "delete from fac_PDUStats where PDUID=$PDUID";
				$updateSQL = "insert into fac_PDUStats set PDUID=$PDUID, PhaseA=$phaseA, PhaseB=$phaseB, PhaseC=$phaseC, TotalAmps=$TotalAmps";
				
				mysql_query( $clearSQL, $db );
				mysql_query( $updateSQL, $db );
			}

			$this->PDUID = $PDUID;      
			$FirmwareVersion = $this->GetSmartCDUVersion( $db );
			$updateSQL = "update fac_PowerDistribution set FirmwareVersion=\"$FirmwareVersion\" where PDUID=\"$PDUID\"";
			mysql_query( $updateSQL, $db );
		}
	}
  
	function GetSmartCDUUptime( $db ) {
		$this->GetPDU( $db );

		if (!($this->IPAddress)||!($this->SNMPCommunity)) {
			return "Not Configured";
		} else {
			$serverIP = $this->IPAddress;
			$community = $this->SNMPCommunity;
			$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP sysUpTimeInstance";

			exec($pollCommand, $statsOutput);
			// need error checking here

			if ( count( $statsOutput ) > 0 )
				$upTime=end(explode(")",$statsOutput[0]));
			else
				$upTime = "Unknown";
	
			return $upTime;
		}
	}
  
	function GetSmartCDUVersion( $db ) {
		$this->GetPDU( $db );

		if (!($this->IPAddress)||!($this->SNMPCommunity)) {
			return "Not Configured";
		} else {
			$serverIP = $this->IPAddress;
			$community = $this->SNMPCommunity;
			if(strtoupper(substr($this->ManagementType,0,5))==='GEIST'){
				$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP .1.3.6.1.4.1.21239.2.1.2.0";
			} elseif (strtoupper(substr($this->ManagementType,0,6))==='SERVER') {
				$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP .1.3.6.1.4.1.1718.3.1.1.0";
			} else {
				$pollCommand = "/usr/bin/snmpget -v 2c -c $community $serverIP .1.3.6.1.4.1.318.1.1.4.1.2.0";
			}

			exec( $pollCommand, $statsOutput );
			// need error checking here

			if ( count( $statsOutput ) > 0 )
				$version = str_replace( "\"", "", end( explode( " ", $statsOutput[0] ) ) );
			else
				$version = "Unknown";
			return $version;
		}
	}
    
	function GetManagementTypeSelectList( $db ) {
		$MgmtList = array( "Unmanaged", "Geist", "ServerTech", "APC");

		$selectList = "<select name=\"managementtype\" id=\"managementtype\"><option value=\"Unmanaged\">Unmanaged</option>";

		foreach ( $MgmtList as $MgmtType ) {
			if ( $MgmtType == $this->ManagementType )
				$selected = "selected";
			else
				$selected = "";

			$selectList .= "<option value=\"" . $MgmtType . "\" $selected>". $MgmtType ."</option>";
		}

		$selectList .= "</select>";

		return $selectList;
	}

	function DeletePDU( $db ) {
		// First, remove any connections to the PDU
		$tmpConn = new PowerConnection();
		$tmpConn->PDUID = $this->PDUID;
		$connList = $tmpConn->GetConnectionsByPDU( $db );
		
		foreach ( $connList as $delConn ) {
			$delConn->RemoveConnections( $db );
		}
		
		$sql = sprintf( "delete from fac_PowerDistribution where PDUID=\"%d\"", intval( $this->PDUID ) );
		mysql_query( $sql, $db );
	}
}

class PowerPanel {
	/* PowerPanel:	PowerPanel(s) are the parents of PowerDistribution (power strips) and the children
					PowerSource(s).  Panels are arranged as either Odd/Even (odd numbers on the left,
					even on the right) or Sequential (1 to N in a single column) numbering for the
					purpose of building out a panel schedule.
	*/
	
  var $PanelID;
  var $PowerSourceID;
  var $PanelLabel;
  var $NumberOfPoles;
  var $MainBreakerSize;
  var $PanelVoltage;
  var $NumberScheme;
  
  function GetPanelsByDataCenter( $DataCenterID, $db ) {
    $select_sql = "select * from fac_PowerPanel a, fac_PowerSource b where a.PowerSourceID=b.PowerSourceID and b.DataCenterID=\"" . intval($DataCenterID) . "\" order by PanelLabel";
    $result = mysql_query( $select_sql, $db );
    
    $PanelList = array();
    
    while ( $row = mysql_fetch_array( $result ) ) {
      $PanelID = $row["PanelID"];
      
      $PanelList[$PanelID]->PanelID = $row["PanelID"];
      $PanelList[$PanelID]->PowerSourceID = $row["PowerSourceID"];
      $PanelList[$PanelID]->PanelLabel = stripslashes($row["PanelLabel"]);
      $PanelList[$PanelID]->NumberOfPoles = $row["NumberOfPoles"];
      $PanelList[$PanelID]->MainBreakerSize = $row["MainBreakerSize"];
	  $PanelList[$PanelID]->PanelVoltage = $row["PanelVoltage"];
      $PanelList[$PanelID]->NumberScheme = $row["NumberScheme"];
    }
    
    return $PanelList;
  }

  function GetPanelList( $db ) {
    $sql = "select * from fac_PowerPanel order by PanelLabel";
    $result = mysql_query( $sql, $db );

    $PanelList = array();

    while ( $row = mysql_fetch_array( $result ) ) {
      $PanelID = $row["PanelID"];

      $PanelList[$PanelID]->PanelID = $row["PanelID"];
      $PanelList[$PanelID]->PowerSourceID = $row["PowerSourceID"];
      $PanelList[$PanelID]->PanelLabel = stripslashes($row["PanelLabel"]);
      $PanelList[$PanelID]->NumberOfPoles = $row["NumberOfPoles"];
      $PanelList[$PanelID]->MainBreakerSize = $row["MainBreakerSize"];
	  $PanelList[$PanelID]->PanelVoltage = $row["PanelVoltage"];
      $PanelList[$PanelID]->NumberScheme = $row["NumberScheme"];
    }

    return $PanelList;
  }
  
  function GetPanelListBySource( $db ) {
    $sql = "select * from fac_PowerPanel where PowerSourceID=\"".intval($this->PowerSourceID)."\" order by PanelLabel";
    $result = mysql_query( $sql, $db );

    $PanelList = array();

    while ( $row = mysql_fetch_array( $result ) ) {
      $PanelID = $row["PanelID"];

      $PanelList[$PanelID]->PanelID = $row["PanelID"];
      $PanelList[$PanelID]->PowerSourceID = $row["PowerSourceID"];
      $PanelList[$PanelID]->PanelLabel = stripslashes($row["PanelLabel"]);
      $PanelList[$PanelID]->NumberOfPoles = $row["NumberOfPoles"];
      $PanelList[$PanelID]->MainBreakerSize = $row["MainBreakerSize"];
	  $PanelList[$PanelID]->PanelVoltage = $row["PanelVoltage"];
      $PanelList[$PanelID]->NumberScheme = $row["NumberScheme"];
    }

    return $PanelList;
  }
  
  function GetPanel( $db ) {
	$sql = "select * from fac_PowerPanel where PanelID=\"" . intval( $this->PanelID ) . "\"";
	$result = mysql_query( $sql, $db );

	if ( $row = mysql_fetch_array( $result ) ) {
		$this->PanelID = $row["PanelID"];
		$this->PowerSourceID = $row["PowerSourceID"];
		$this->PanelLabel = $row["PanelLabel"];
		$this->NumberOfPoles = $row["NumberOfPoles"];
		$this->MainBreakerSize = $row["MainBreakerSize"];
		$this->PanelVoltage = $row["PanelVoltage"];
		$this->NumberScheme = $row["NumberScheme"];
	}
  }

  
  function CreatePanel( $db ) {
	/* Only 2 types of number schemes */
	if ( ! $this->NumberScheme == "Sequential" )
		$this->NumberScheme = "Odd/Even";

	$sql = "insert into fac_PowerPanel set PowerSourceID=\"" . intval( $this->PowerSourceID ) . "\", PanelLabel=\"" . addslashes( $this->PanelLabel ) . "\", NumberOfPoles=\"" . intval( $this->NumberOfPoles ) . "\", MainBreakerSize=\"" . intval( $this->MainBreakerSize ) . "\", PanelVoltage=\"" . intval( $this->PanelVoltage ) . "\", NumberScheme=\"" . $this->NumberScheme . "\"";

	$result = mysql_query( $sql, $db );

	$this->PanelID = mysql_insert_id();

	return $this->PanelID;
  }

  function UpdatePanel( $db ) {
        /* Only 2 types of number schemes */
        if ( ! $this->NumberScheme == "Sequential" )
                $this->NumberScheme = "Odd/Even";

        $sql = "update fac_PowerPanel set PowerSourceID=\"" . intval( $this->PowerSourceID ) . "\", PanelLabel=\"" . addslashes( $this->PanelLabel ) . "\", NumberOfPoles=\"" . intval( $this->NumberOfPoles ) . "\", MainBreakerSize=\"" . intval( $this->MainBreakerSize ) . "\", PanelVoltage=\"" . intval( $this->PanelVoltage ) . "\", NumberScheme=\"" . $this->NumberScheme . "\" where PanelID=\"" . intval( $this->PanelID ) . "\"";

        $result = mysql_query( $sql, $db );

	return $result;
  }
}

class PanelSchedule {
	/* PanelSchedule:	Create a panel schedule based upon all of the known connections.  In
						other words - if you take down Panel A4, what cabinets will be affected?
	*/
	
  var $PanelID;
  var $PolePosition;
  var $NumPoles;
  var $Label;
  
  function MakeConnection( $db ) {
    $insert_sql = "insert into fac_PanelSchedule values( \"" . intval($this->PanelID) . "\", \"" . intval($this->PolePosition) . "\", \"" . intval($this->NumPoles) . "\", \"" . addslashes($this->Label) . "\" on duplicate key update fac_PanelSchedule set NumPoles=\"" . intval($this->NumPoles) . "\", Label=\"" . addslashes($this->Label) . "\" where PanelID=\"" . intval($this->PanelID) . "\" and PolePosition=\"" . intval($this->PolePosition) . "\"";
    return mysql_query( $insert_sql, $db );
  }
  
  function DisplayPanel( $db ) {
    $html = "<table border=1>\n";
      
    $pan = new PowerPanel();
    $pan->PanelID = $this->PanelID;
    $pan->GetPanel( $db );
   
    $sched = array_fill( 1, $pan->NumberOfPoles, "<td>&nbsp;</td>" );
    
    $select_sql = "select * from fac_PanelSchedule where PanelID=\"" . intval($this->PanelID) . "\" order by PolePosition ASC";
    $result = mysql_query( $select_sql, $db );
  
    while ( $row = mysql_fetch_assoc( $result ) ) {
      $sched[$row["PolePosition"]] = "<td rowspan=" . $row["NumPoles"] . ">" . $row["Label"] . "</td>";
      
      if ( $row["NumPoles"] > 1 )
        $sched[$row["PolePosition"] + 2] = "";
      
      if ( $row["NumPoles"] > 2 )
        $sched[$row["PolePosition"] + 4] = "";
    }
    
    for ( $i = 1; $i < $pan->NumberOfPoles + 1; $i++ ) {
      $html .= "<tr><td>" . $i . "</td>" . $sched[$i] . "<td>" . ($i + 1) . "</td>" . $sched[++$i] . "</tr>\n";
    }
    
    $html .= "</table>\n";
    
    return $html;
  }
}

class PowerSource {
	/* PowerSource:		This is the most upstream power source that is managed in DCIM.
						You will need to have at least one power source per data center, 
						even if they are physically the same (such as 1 UPS for the
						entire site, or utility power for multiple sites).  Small data
						centers will most likely have just one power source per data centers,
						but large ones may even equate utility power down to which feeder
						or transfer switch that is in use.
						
						At this time there are no parent/child relationships between
						power sources, but it may be implemented in a future release.
	*/
	
  var $PowerSourceID;
  var $SourceName;
  var $DataCenterID;
  var $IPAddress;
  var $Community;
  var $LoadOID;
  var $Capacity;
  
  function CreatePowerSource( $db ) {
    $sql = "insert into fac_PowerSource set SourceName=\"" . addslashes( $this->SourceName ) . "\", DataCenterID=" . intval( $this->DataCenterID ) . ", IPAddress=\"" . addslashes( $this->IPAddress ) . "\", Community=\"" . addslashes( $Community ) . "\", LoadOID=\"" . addslashes( $this->LoadOID ) . "\", Capacity=" . intval( $this->Capacity );

    $result = mysql_query( $sql, $db );

  }

  function UpdatePowerSource( $db ) {
	$sql = "update fac_PowerSource set SourceName=\"" . addslashes( $this->SourceName ) . "\", DataCenterID=" . intval( $this->DataCenterID ) . ", IPAddress=\"" . addslashes( $this->IPAddress ) . "\", Community=\"" . addslashes( $Community ) . "\", LoadOID=\"" . addslashes( $this->LoadOID ) . "\", Capacity=" . intval( $this->Capacity ) . " where PowerSourceID=\"" . intval( $this->PowerSourceID ) . "\"";

	$result = mysql_query( $sql, $db );

	return $result;
  }

  function GetSourcesByDataCenter( $db ) {
    $select_sql = "select * from fac_PowerSource where DataCenterID=\"" . intval($this->DataCenterID) . "\"";
    $result = mysql_query( $select_sql, $db );
    
    $SourceList = array();
    
    while ( $row = mysql_fetch_array( $result ) ) {
      $SourceID = count($SourceList);
      
      $SourceList[$SourceID] = new PowerSource;
      $SourceList[$SourceID]->PowerSourceID = $row["PowerSourceID"];
      $SourceList[$SourceID]->SourceName = $row["SourceName"];
      $SourceList[$SourceID]->DataCenterID = $row["DataCenterID"];
      $SourceList[$SourceID]->IPAddress = $row["IPAddress"];
      $SourceList[$SourceID]->Community = $row["Community"];
      $SourceList[$SourceID]->LoadOID = $row["LoadOID"];
      $SourceList[$SourceID]->Capacity = $row["Capacity"];
    }
    
    return $SourceList;
  }
  
  function GetPSList( $db ) {
    $sql = "select * from fac_PowerSource order by SourceName ASC";
    $result = mysql_query( $sql, $db );

    $SourceList = array();

    while ( $row = mysql_fetch_array( $result ) ) {
      $SourceID = count($SourceList);

      $SourceList[$SourceID] = new PowerSource;
      $SourceList[$SourceID]->PowerSourceID = $row["PowerSourceID"];
      $SourceList[$SourceID]->SourceName = $row["SourceName"];
      $SourceList[$SourceID]->DataCenterID = $row["DataCenterID"];
      $SourceList[$SourceID]->IPAddress = $row["IPAddress"];
      $SourceList[$SourceID]->Community = $row["Community"];
      $SourceList[$SourceID]->LoadOID = $row["LoadOID"];
      $SourceList[$SourceID]->Capacity = $row["Capacity"];
    }

    return $SourceList;
  } 
  
  function GetSource( $db ) {
    $selectSQL = "select * from fac_PowerSource where PowerSourceID=\"" . intval($this->PowerSourceID) . "\"";
    $result = mysql_query( $selectSQL, $db );
    
    if ( $row = mysql_fetch_array( $result ) ) {
      $this->PowerSourceID = $row["PowerSourceID"];
      $this->SourceName = $row["SourceName"];
      $this->DataCenterID = $row["DataCenterID"];
      $this->IPAddress = $row["IPAddress"];
      $this->Community = $row["Community"];
      $this->LoadOID = $row["LoadOID"];
      $this->Capacity = $row["Capacity"];
    }
  }
  
  function GetCurrentLoad( $db ) {
  	$totalLoad = 0;
  	
	// Liebert UPS Query
  	// Query OID .1.3.6.1.4.1.476.1.1.1.1.1.2.0 to get the model number
  	// If model type is blank (NFinity), OID = 1.3.6.1.4.1.476.1.42.3.5.2.2.1.8.3
  	// If model type is Series 300 / 600, OID = .1.3.6.1.4.1.476.1.1.1.1.4.2.0
    $pollCommand = "/usr/bin/snmpget -v 1 -c " . $this->Community . " " . $this->IPAddress . " .1.3.6.1.4.1.476.1.1.1.1.1.2.0 | /bin/cut -d: -f4";
    exec( $pollCommand, $snmpOutput );
    
    if ( @$snmpOutput[0] != "" ) {
	    $pollCommand = "/usr/bin/snmpget -v 1 -c " . $this->Community . " " . $this->IPAddress . " .1.3.6.1.4.1.476.1.1.1.1.4.2.0 | /bin/cut -d: -f4";
	    exec( $pollCommand, $loadOutput );
	    
	    $totalLoad = ( $loadOutput[0] * $this->Capacity ) / 100;
  	} else {
	    $pollCommand = "/usr/bin/snmpget -v 1 -c " . $this->Community . " " . $this->IPAddress . " .1.3.6.1.4.1.476.1.42.3.5.2.2.1.8.3 | /bin/cut -d: -f4";
	    exec( $pollCommand, $loadOutput );
	    
	    $totalLoad = $loadOutput[0];
	  }
    
    return $totalLoad;
  }

}

?>
