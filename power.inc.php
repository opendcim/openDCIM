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
	var $SNMPVersion;
	var $VersionOID;
	var $Multiplier;
	var $OID1;
	var $OID2;
	var $OID3;
	var $ProcessingProfile;
	var $Voltage;
	var $Amperage;
	var $NumOutlets;
	
	function GetTemplateList( $db = null ) {
		global $dbh;
		
		$sql = "select a.* from fac_CDUTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID order by b.Name ASC,a.Model ASC";
		
		$tmpList = array();
		
		foreach ( $dbh->query( $sql ) as $row ) {
			$n = sizeof( $tmpList );
			$tmpList[$n] = new CDUTemplate();
			$tmpList[$n]->TemplateID = $row["TemplateID"];
			$tmpList[$n]->ManufacturerID = $row["ManufacturerID"];
			$tmpList[$n]->Model = $row["Model"];
			$tmpList[$n]->Managed = $row["Managed"];
			$tmpList[$n]->SNMPVersion = $row["SNMPVersion"];
			$tmpList[$n]->VersionOID = $row["VersionOID"];
			$tmpList[$n]->Multiplier = $row["Multiplier"];
			$tmpList[$n]->OID1 = $row["OID1"];
			$tmpList[$n]->OID2 = $row["OID2"];
			$tmpList[$n]->OID3 = $row["OID3"];
			$tmpList[$n]->ProcessingProfile = $row["ProcessingProfile"];
			$tmpList[$n]->Voltage = $row["Voltage"];
			$tmpList[$n]->Amperage = $row["Amperage"];
			$tmpList[$n]->NumOutlets = $row["NumOutlets"];
		}
		
		return $tmpList;
	}
	
	function GetTemplate( $db = null ) {
		global $dbh;
		
		$sql = sprintf( "select * from fac_CDUTemplate where TemplateID=%d", $this->TemplateID );

		foreach ( $dbh->query( $sql ) as $row ) {
			$this->ManufacturerID = $row["ManufacturerID"];
			$this->Model = $row["Model"];
			$this->Managed = $row["Managed"];
			$this->SNMPVersion = $row["SNMPVersion"];
			$this->VersionOID = $row["VersionOID"];
			$this->Multiplier = $row["Multiplier"];
			$this->OID1 = $row["OID1"];
			$this->OID2 = $row["OID2"];
			$this->OID3 = $row["OID3"];
			$this->ProcessingProfile = $row["ProcessingProfile"];
			$this->Voltage = $row["Voltage"];
			$this->Amperage = $row["Amperage"];
			$this->NumOutlets = $row["NumOutlets"];
		}
		
		return;
	}
	
	function CreateTemplate() {
		global $dbh;
		
		$sql = sprintf( "select * from fac_CDUTemplate where ManufacturerID=%d and Model=\"%s\"", intval( $this->ManufacturerID ), addslashes( $this->Model ) );
		$result = mysql_query( $sql, $db );
		
		if ( mysql_num_rows( $result ) > 0 ) {
			// A combination of this Mfg + Model already exists
			return false;
		}
		
		$sql = sprintf( "INSERT fac_CDUTemplate SET ManufacturerID=%d, Model=\"%s\", Managed=%d, SNMPVersion=\"%s\", VersionOID=\"%s\", Multiplier=\"%d\", OID1=\"%s\", OID2=\"%s\", OID3=\"%s\", ProcessingProfile=\"%s\", Voltage=%d, Amperage=%d, NumOutlets=%d",
			$this->ManufacturerID, mysql_real_escape_string( $this->Model ), $this->Managed, mysql_real_escape_string( $this->SNMPVersion ),
			mysql_real_escape_string( $this->VersionOID ), $this->Multiplier, mysql_real_escape_string( $this->OID1 ), mysql_real_escape_string( $this->OID2 ),
			mysql_real_escape_string( $this->OID3 ), mysql_real_escape_string( $this->ProcessingProfile ), $this->Voltage, $this->Amperage, $this->NumOutlets );
		
		if ( ! $dbh->exec( $sql ) )
			return false;
		else
			$this->TemplateID = $dbh->lastInsertID();
		
		return $this->TemplateID;
	}
	
	function UpdateTemplate() {
		global $dbh;
		
		$sql = sprintf( "UPDATE fac_CDUTemplate SET ManufacturerID=%d, Model=\"%s\", Managed=%d, SNMPVersion=\"%s\", VersionOID=\"%s\", Multiplier=\"%d\", OID1=\"%s\", OID2=\"%s\", OID3=\"%s\", ProcessingProfile=\"%s\", Voltage=%d, Amperage=%d, NumOutlets=%d where TemplateID=%d",
			$this->ManufacturerID, mysql_real_escape_string( $this->Model ), $this->Managed, mysql_real_escape_string( $this->SNMPVersion ), mysql_real_escape_string( $this->VersionOID ),
			$this->Multiplier, mysql_real_escape_string( $this->OID1 ), mysql_real_escape_string( $this->OID2 ), mysql_real_escape_string( $this->OID3 ), 
			mysql_real_escape_string( $this->ProcessingProfile ), $this->Voltage, $this->Amperage, $this->NumOutlets, $this->TemplateID );
		
		if ( ! $dbh->exec( $sql ) )
			return false;
		else
			return true;
	}
	
	function DeleteTemplate() {
		global $dbh;
		
		// First step is to clear any power strips referencing this template
		$sql = sprintf( "update fac_PowerDistribution set CDUTemplateID=\"\" where TemplateID=%d", $this->TemplateID );
		$dbh->execute( $sql );
		
		$sql = sprintf( "delete from fac_CDUTemplate where TemplateID=%d", $this->TemplateID );
		$dbh->exec( $sql );
		
		return;
	}
}

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
	var $TemplateID;
	var $IPAddress;
	var $SNMPCommunity;
	var $FirmwareVersion;
  	var $PanelID;
	var $BreakerSize;
	var $PanelPole;
	var $InputAmperage;
	var $FailSafe;
	var $PanelID2;
	var $PanelPole2;

	function CreatePDU( $db ) {
		$insert_sql = "insert into fac_PowerDistribution set Label=\"" . addslashes($this->Label) . "\", CabinetID=\"" . intval($this->CabinetID) . "\", TemplateID=\"" . intval( $this->TemplateID ) . "\", IPAddress=\"" . addslashes($this->IPAddress) . "\", SNMPCommunity=\"" . addslashes($this->SNMPCommunity) . "\", PanelID=\"" . intval($this->PanelID) . "\", BreakerSize=\"" . intval( $this->BreakerSize ) . "\", PanelPole=\"" . intval($this->PanelPole) . "\", InputAmperage=\"" . intval( $this->InputAmperage ) . "\", FailSafe=\"" . intval($this->FailSafe) . "\", PanelID2=\"" . intval($this->PanelID2) . "\", PanelPole2=\"" . intval($this->PanelPole2) . "\"";

		if ( ! $result = mysql_query( $insert_sql, $db ) ) {
			return -1;
		}

		$this->PDUID = mysql_insert_id( $db );

		return $this->PDUID;
	}

	function UpdatePDU( $db ) {
		$update_sql = "update fac_PowerDistribution set Label=\"" . addslashes($this->Label) . "\", CabinetID=\"" . intval($this->CabinetID) . "\", TemplateID=\"" . intval( $this->TemplateID ) . "\", IPAddress=\"" . addslashes($this->IPAddress) . "\", SNMPCommunity=\"" . addslashes($this->SNMPCommunity) . "\", PanelID=\"" . intval($this->PanelID) . "\", BreakerSize=\"" . intval( $this->BreakerSize ) . "\", PanelPole=\"" . intval($this->PanelPole) . "\", InputAmperage=\"" . intval( $this->InputAmperage ) . "\", FailSafe=\"" . intval($this->FailSafe) . "\", PanelID2=\"" . intval($this->PanelID2) . "\", PanelPole2=\"" . intval($this->PanelPole2) . "\" where PDUID=\"" . intval($this->PDUID) . "\"";

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
			$this->Label = stripslashes($PDUrow["Label"]);
			$this->CabinetID = $PDUrow["CabinetID"];
			$this->TemplateID = $PDUrow["TemplateID"];
			$this->IPAddress = stripslashes($PDUrow["IPAddress"]);
			$this->SNMPCommunity = stripslashes($PDUrow["SNMPCommunity"]);
			$this->FirmwareVersion = $PDUrow["FirmwareVersion"];
			$this->PanelID = $PDUrow["PanelID"];
			$this->BreakerSize = $PDUrow["BreakerSize"];
			$this->PanelPole = $PDUrow["PanelPole"];
			$this->InputAmperage = $PDUrow["InputAmperage"];
			$this->FailSafe = $PDUrow["FailSafe"];
			$this->PanelID2 = $PDUrow["PanelID2"];
			$this->PanelPole2 = $PDUrow["PanelPole2"];
		} else {
			$this->Label = null;
			$this->CabinetID = null;
			$this->TemplateID = null;
			$this->IPAddress = null;
			$this->SNMPCommunity = null;
			$this->FirmwareVersion = null;
			$this->PanelID = null;
			$this->BreakerSize = null;
			$this->PanelPole = null;
			$this->InputAmperage = null;
			$this->FailSafe = null;
			$this->PanelID2 = null;
			$this->PanelPole2 = null;
		}

		return 0;
	}
	
	function GetPDUbyPanel($db){
		$select_sql = "select * from fac_PowerDistribution where PanelID=\"" . 
		 				intval($this->PanelID) . "\" or PanelID2=\"" . intval( $this->PanelID ) . 
		 				"\" order by PanelPole ASC, CabinetID, Label";
		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		$PDUList = array();

		while ( $PDUrow = mysql_fetch_array( $result ) ) {
			$PDUID = $PDUrow["PDUID"];

			$PDUList[$PDUID]=new PowerDistribution();
			$PDUList[$PDUID]->PDUID = $PDUrow["PDUID"];
			$PDUList[$PDUID]->Label = stripslashes($PDUrow["Label"]);
			$PDUList[$PDUID]->CabinetID = $PDUrow["CabinetID"];
			$PDUList[$PDUID]->TemplateID = $PDUrow["TemplateID"];
			$PDUList[$PDUID]->IPAddress = stripslashes($PDUrow["IPAddress"]);
			$PDUList[$PDUID]->SNMPCommunity = stripslashes($PDUrow["SNMPCommunity"]);
			$PDUList[$PDUID]->FirmwareVersion = $PDUrow["FirmwareVersion"];
			$PDUList[$PDUID]->PanelID = $PDUrow["PanelID"];
			$PDUList[$PDUID]->BreakerSize = $PDUrow["BreakerSize"];
			$PDUList[$PDUID]->PanelPole = $PDUrow["PanelPole"];
			$PDUList[$PDUID]->InputAmerage = $PDUrow["InputAmperage"];
			$PDUList[$PDUID]->FailSafe = $PDUrow["FailSafe"];
			$PDUList[$PDUID]->PanelID2 = $PDUrow["PanelID2"];
			$PDUList[$PDUID]->PanelPole2 = $PDUrow["PanelPole2"];
		}

		return $PDUList;
	}
	
	function GetPDUbyCabinet( $db ) {
		$select_sql = sprintf( "select * from fac_PowerDistribution where CabinetID=%d", intval( $this->CabinetID ) );

		if ( ! $result = mysql_query( $select_sql, $db ) ) {
			return 0;
		}

		$PDUList = array();

		while ( $PDUrow = mysql_fetch_array( $result ) ) {
			$n = sizeof( $PDUList );
			$PDUList[$n] = new PowerDistribution();

			$PDUList[$n]->PDUID = $PDUrow["PDUID"];
			$PDUList[$n]->Label = stripslashes($PDUrow["Label"]);
			$PDUList[$n]->CabinetID = $PDUrow["CabinetID"];
			$PDUList[$n]->TemplateID = $PDUrow["TemplateID"];
			$PDUList[$n]->IPAddress = stripslashes($PDUrow["IPAddress"]);
			$PDUList[$n]->SNMPCommunity = stripslashes($PDUrow["SNMPCommunity"]);
			$PDUList[$n]->FirmwareVersion = $PDUrow["FirmwareVersion"];
			$PDUList[$n]->PanelID = $PDUrow["PanelID"];
			$PDUList[$n]->BreakerSize = $PDUrow["BreakerSize"];
			$PDUList[$n]->PanelPole = $PDUrow["PanelPole"];
			$PDUList[$n]->InputAmperage = $PDUrow["InputAmperage"];
			$PDUList[$n]->FailSafe = $PDUrow["FailSafe"];
			$PDUList[$n]->PanelID2 = $PDUrow["PanelID2"];
			$PDUList[$n]->PanelPole2 = $PDUrow["PanelPole2"];
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
			$PDUList[$PDUID]->TemplateID=$PDUrow["TemplateID"];
			$PDUList[$PDUID]->IPAddress=stripslashes($PDUrow["IPAddress"]);
			$PDUList[$PDUID]->SNMPCommunity=stripslashes($PDUrow["SNMPCommunity"]);
			$PDUList[$PDUID]->FirmwareVersion=$PDUrow["FirmwareVersion"];
			$PDUList[$PDUID]->PanelID=$PDUrow["PanelID"];
			$PDUList[$PDUID]->BreakerSize=$PDUrow["BreakerSize"];
			$PDUList[$PDUID]->PanelPole=$PDUrow["PanelPole"];
			$PDUList[$PDUID]->InputAmperage=$PDUrow["InputAmperage"];
			$PDUList[$PDUID]->FailSafe=$PDUrow["FailSafe"];
			$PDUList[$PDUID]->PanelID2=$PDUrow["PanelID2"];
			$PDUList[$PDUID]->PanelPole2=$PDUrow["PanelPole2"];
		}

		return $PDUList;
	}

	function GetWattage( $db ) {
		$sql = sprintf( "select Wattage from fac_PDUStats where PDUID=%d", $this->PDUID );
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			return $row["Wattage"];
		} else {
			return 0;
		}
	}
	
	function GetWattageByDC( $dc = null ) {
		global $dbh;
		
		if ( $dc == null )
			$sql = "select count(Wattage) from fac_PDUStats";
		else
			$sql = sprintf( "select sum(Wattage) as Wattage from fac_PDUStats where PDUID in (select PDUID from fac_PowerDistribution where CabinetID in (select CabinetID from fac_Cabinet where DataCenterID=%d))", $dc );
		
		$row = $dbh->query( $sql )->fetch();
		$Wattage = $row["Wattage"];
		
		return $Wattage;
	}
	
	function GetWattageByCabinet( $CabinetID ) {
		global $dbh;
		
		if ( $CabinetID < 1 )
			return 0;
		
		$sql = sprintf( "select sum(Wattage) as Wattage from fac_PDUStats where PDUID in (select PDUID from fac_PowerDistribution where CabinetID=%d)", $CabinetID );
		
		$row = $dbh->query( $sql )->fetch();
		$Wattage = $row["Wattage"];
		
		return $Wattage;
	}

  
	function UpdateStats( $db ) {
		$config=new Config();
		
		$sql = "select PDUID, IPAddress, SNMPCommunity, SNMPVersion, Multiplier, OID1, OID2, OID3, ProcessingProfile, Voltage from fac_PowerDistribution a, fac_CDUTemplate b where a.TemplateID=b.TemplateID and b.Managed=true and IPAddress>'' and SNMPCommunity>''";
		$result = mysql_query( $sql, $db );
		
		// The result set should have no PDU's with blank IP Addresses or SNMP Community, so we can forge ahead with processing them all
		
		while ( $row = mysql_fetch_array( $result ) ) {
			// If only one OID is used, the OID2 and OID3 should be blank, so no harm in just making one string
			$OIDString = $row["OID1"] . " " . $row["OID2"] . " " . $row["OID3"];
			
			// Have to reset this every time, otherwise the exec() will append
			unset( $statsOutput );
			$amps = 0;
			$watts = 0;
			
			$pollCommand = sprintf( "%s -v %s -t 0.5 -r 2 -c %s %s %s | %s -d: -f4", $config->ParameterArray["snmpget"], $row["SNMPVersion"], $row["SNMPCommunity"], $row["IPAddress"], $OIDString, $config->ParameterArray["cut"] );
			
			exec( $pollCommand, $statsOutput );
			
			if ( count( $statsOutput ) > 0 ) {
				switch ( $row["ProcessingProfile"] ) {
					case "SingleOIDAmperes":
						$amps = intval( @$statsOutput[0] ) / intval( $row["Multiplier"] );
						$watts = $amps * intval( $row["Voltage"] );
						break;
					case "Combine3OIDAmperes":
						$amps = ( intval( @$statsOutput[0] ) + intval( @$statsOutput[1] ) + intval( @$statsOutput[2] ) ) / intval( $row["Multiplier"] );
						$watts = $amps * intval( $row["Voltage"] );
						break;
					case "Convert3PhAmperes":
						$amps = ( intval( @$statsOutput[0] ) + intval( @$statsOutput[1] ) + intval( @$statsOutput[2] ) ) / intval( $row["Multiplier"] ) / 3;
						$watts = $amps * 1.732 * intval( $row["Voltage"] );
						break;
					case "Combine3OIDWatts":
						$watts = ( intval( @$statsOutput[0] ) + intval( @$statsOutput[1] ) + intval( @$statsOutput[2] ) ) / intval( $row["Multiplier"] );
					default:
						$watts = intval( @$statsOutput[0] ) / intval( $row["Multiplier"] );
						break;
				}
			}
			
			$sql = sprintf( "insert into fac_PDUStats set PDUID=%s, Wattage=%s ON DUPLICATE KEY UPDATE Wattage=%s", $row["PDUID"], $watts, $watts );
			mysql_query( $sql );
			
			$this->PDUID = $row["PDUID"];      
			$FirmwareVersion = $this->GetSmartCDUVersion( $db );
			$updateSQL = sprintf( "update fac_PowerDistribution set FirmwareVersion=\"%s\" where PDUID=%d", $FirmwareVersion, $this->PDUID );
			mysql_query( $updateSQL, $db );
			
		}
	}
	
	function GetSmartCDUUptime( $db ) {
		$config=new Config();
		$this->GetPDU( $db );

		if (!($this->IPAddress)||!($this->SNMPCommunity)) {
			return "Not Configured";
		} else {
			$serverIP = $this->IPAddress;
			$community = $this->SNMPCommunity;
			$pollCommand ="{$config->ParameterArray["snmpget"]} -v 2c -t 0.5 -r 2 -c $community $serverIP sysUpTimeInstance";

			exec($pollCommand, $statsOutput);
			// need error checking here

			if(count($statsOutput) >0){
				$statsOutput=explode(")",$statsOutput[0]);
				$upTime=end($statsOutput);
			}else{
				$upTime = "Unknown";
			}
			return $upTime;
		}
	}
  
	function GetSmartCDUVersion( $db ) {
		$this->GetPDU( $db );
		
		$template = new CDUTemplate();
		$template->TemplateID = $this->TemplateID;
		$template->GetTemplate( $db );

		if (!($this->IPAddress)||!($this->SNMPCommunity)) {
			return "Not Configured";
		} else {
			$serverIP = $this->IPAddress;
			$community = $this->SNMPCommunity;

			$command = "/usr/bin/snmpget";
			
			$pollCommand = sprintf( "%s -v 2c -t 0.5 -r 2 -c %s %s %s", $command, $this->SNMPCommunity, $this->IPAddress, $template->VersionOID );

			exec( $pollCommand, $statsOutput );
			// need error checking here

			if ( count( $statsOutput ) > 0 )
				$version = str_replace( "\"", "", end( explode( " ", $statsOutput[0] ) ) );
			else
				$version = "Unknown";
			return $version;
		}
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

      $PanelList[$PanelID]=new PowerPanel();
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
    $sql = "insert into fac_PowerSource set SourceName=\"" . addslashes( $this->SourceName ) . "\", DataCenterID=" . intval( $this->DataCenterID ) . ", IPAddress=\"" . addslashes( $this->IPAddress ) . "\", Community=\"" . addslashes( $this->Community ) . "\", LoadOID=\"" . addslashes( $this->LoadOID ) . "\", Capacity=" . intval( $this->Capacity );

    $result = mysql_query( $sql, $db );

  }

  function UpdatePowerSource( $db ) {
	$sql = "update fac_PowerSource set SourceName=\"" . addslashes( $this->SourceName ) . "\", DataCenterID=" . intval( $this->DataCenterID ) . ", IPAddress=\"" . addslashes( $this->IPAddress ) . "\", Community=\"" . addslashes( $this->Community ) . "\", LoadOID=\"" . addslashes( $this->LoadOID ) . "\", Capacity=" . intval( $this->Capacity ) . " where PowerSourceID=\"" . intval( $this->PowerSourceID ) . "\"";

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
