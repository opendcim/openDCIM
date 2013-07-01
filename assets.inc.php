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
	var $CabRowID;      //JMGA: Row of this cabinet
	var $CabinetHeight;
	var $Model;
	var $Keylock;
	var $MaxKW;
	var $MaxWeight;
	var $InstallationDate;
	var $SensorIPAddress;
	var $SensorCommunity;
	var $TempSensorOID;
	var $HumiditySensorOID;
	var $MapX1;
	var $MapY1;
	var $MapX2;
	var $MapY2;
	var $Notes;

	function MakeSafe() {
		$this->CabinetID=intval($this->CabinetID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Location=addslashes($this->Location);
		$this->AssignedTo=intval($this->AssignedTo);
		$this->ZoneID=intval($this->ZoneID);
		$this->CabRowID=intval($this->CabRowID);
		$this->CabinetHeight=intval($this->CabinetHeight);
		$this->Model=addslashes($this->Model);
		$this->Keylock=addslashes($this->Keylock);
		$this->MaxKW=floatval($this->MaxKW);
		$this->MaxWeight=intval($this->MaxWeight);
		$this->SensorIPAddress=addslashes($this->SensorIPAddress);
		$this->SensorCommunity=addslashes($this->SensorCommunity);
		$this->TempSensorOID=addslashes($this->TempSensorOID);
		$this->HumiditySensorOID=addslashes($this->HumiditySensorOID);
		$this->MapX1=intval($this->MapX1);
		$this->MapY1=intval($this->MapY1);
		$this->MapX2=intval($this->MapX2);
		$this->MapY2=intval($this->MapY2);
		$this->Notes=addslashes($this->Notes);
	}
	
	static function CabinetRowToObject($dbRow){
		/*
		 * Generic function that will take any row returned from the fac_Cabinet
		 * table and convert it to an object for use in array or other
		 */
		$cab=new Cabinet();
		$cab->CabinetID=$dbRow["CabinetID"];
		$cab->DataCenterID=$dbRow["DataCenterID"];
		$cab->Location=$dbRow["Location"];
		$cab->AssignedTo=$dbRow["AssignedTo"];
		$cab->ZoneID=$dbRow["ZoneID"];
		$cab->CabRowID=$dbRow["CabRowID"];
		$cab->CabinetHeight=$dbRow["CabinetHeight"];
		$cab->Model=$dbRow["Model"];
		$cab->Keylock=$dbRow["Keylock"];
		$cab->MaxKW=$dbRow["MaxKW"];
		$cab->MaxWeight=$dbRow["MaxWeight"];
		$cab->InstallationDate=$dbRow["InstallationDate"];
		$cab->SensorIPAddress=$dbRow["SensorIPAddress"];
		$cab->SensorCommunity=$dbRow["SensorCommunity"];
		$cab->TempSensorOID=$dbRow["TempSensorOID"];
		$cab->HumiditySensorOID=$dbRow["HumiditySensorOID"];
		$cab->MapX1=$dbRow["MapX1"];
		$cab->MapY1=$dbRow["MapY1"];
		$cab->MapX2=$dbRow["MapX2"];
		$cab->MapY2=$dbRow["MapY2"];
		$cab->Notes=$dbRow["Notes"];

		return $cab;
	}
	
	function CreateCabinet( $db = null ) {
		global $dbh;
		
		$this->MakeSafe();
		
		$sql = sprintf( "insert into fac_Cabinet set DataCenterID=%d, Location=\"%s\", AssignedTo=%d,
			ZoneID=%d, CabRowID=%d,
			CabinetHeight=%d, Model=\"%s\", Keylock=\"%s\", MaxKW=%f, MaxWeight=%d,
			InstallationDate=\"%s\", SensorIPAddress=\"%s\", SensorCommunity=\"%s\",
			TempSensorOID=\"%s\", HumiditySensorOID=\"%s\", MapX1=%d, MapY1=%d,
			MapX2=%d, MapY2=%d, Notes=\"%s\"",
			$this->DataCenterID, $this->Location, $this->AssignedTo, 
			$this->ZoneID, $this->CabRowID, $this->CabinetHeight,
			$this->Model, $this->Keylock, $this->MaxKW, $this->MaxWeight,
			date( "Y-m-d", strtotime( $this->InstallationDate) ), $this->SensorIPAddress,
			$this->SensorCommunity, $this->TempSensorOID, $this->HumiditySensorOID,
			$this->MapX1, $this->MapY1, $this->MapX2, $this->MapY2, $this->Notes );

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		} else {
			$this->CabinetID = $dbh->lastInsertID();
		}
		
		return $this->CabinetID;
	}

	function UpdateCabinet( $db = null ) {
		global $dbh;
		
		$this->MakeSafe();

		$sql="UPDATE fac_Cabinet SET DataCenterID=$this->DataCenterID, 
			Location=\"$this->Location\", AssignedTo=$this->AssignedTo, 
			ZoneID=$this->ZoneID, CabRowID=$this->CabRowID, 
			CabinetHeight=$this->CabinetHeight, Model=\"$this->Model\", 
			Keylock=\"$this->Keylock\", MaxKW=$this->MaxKW, MaxWeight=$this->MaxWeight, 
			InstallationDate=\"".date("Y-m-d", strtotime($this->InstallationDate))."\", 
			SensorIPAddress=\"$this->SensorIPAddress\", 
			SensorCommunity=\"$this->SensorCommunity\", 
			TempSensorOID=\"$this->TempSensorOID\", 
			HumiditySensorOID=\"$this->HumiditySensorOID\", MapX1=$this->MapX1, 
			MapY1=$this->MapY1, MapX2=$this->MapX2, MapY2=$this->MapY2, 
			Notes=\"$this->Notes\" where CabinetID=$this->CabinetID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("UpdateCabinet::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		return true;
	}

	function GetCabinet( $db = null ) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Cabinet WHERE CabinetID=$this->CabinetID;";
		
		if(!$cabinetRow=$dbh->query($sql)->fetch()){
			return false;
		}		
		
		$this->DataCenterID = $cabinetRow[ "DataCenterID" ];
		$this->Location = $cabinetRow[ "Location" ];
		$this->AssignedTo = $cabinetRow["AssignedTo"];
		$this->ZoneID = $cabinetRow["ZoneID"];
		$this->CabRowID = $cabinetRow["CabRowID"];
		$this->CabinetHeight = $cabinetRow[ "CabinetHeight" ];
		$this->Model = $cabinetRow["Model"];
		$this->Keylock = $cabinetRow["Keylock"];
		$this->MaxKW = $cabinetRow["MaxKW"];
		$this->MaxWeight = $cabinetRow["MaxWeight"];
		$this->InstallationDate = $cabinetRow[ "InstallationDate" ];
		$this->SensorIPAddress = $cabinetRow["SensorIPAddress"];
		$this->SensorCommunity = $cabinetRow["SensorCommunity"];
		$this->TempSensorOID = $cabinetRow["TempSensorOID"];
		$this->HumiditySensorOID = $cabinetRow["HumiditySensorOID"];
		$this->MapX1 = $cabinetRow["MapX1"];
		$this->MapY1 = $cabinetRow["MapY1"];
		$this->MapX2 = $cabinetRow["MapX2"];
		$this->MapY2 = $cabinetRow["MapY2"];
		$this->Notes = $cabinetRow["Notes"];

		return;
	}

	static function ListCabinets($deptid=null) {
		global $dbh;
		
		$cabinetList=array();

		$sql='';
		if(!is_null($deptid)){
			$sql=" WHERE AssignedTo=".intval($deptid);
		}

		$sql="SELECT * FROM fac_Cabinet$sql ORDER BY DataCenterID, Location;";

		foreach ( $dbh->query( $sql ) as $cabinetRow ) {
			$cabID=sizeof($cabinetList);
			$cabinetList[$cabID]=Cabinet::CabinetRowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function ListCabinetsByDC( $db = null ) {
		global $dbh;
		
		$cabinetList = array();

		$sql = "select * from fac_Cabinet where DataCenterID=\"" . intval($this->DataCenterID) . "\" order by Location";

		foreach ( $dbh->query( $sql ) as $cabinetRow ) {
			$cabID=sizeof($cabinetList);
			$cabinetList[$cabID]=Cabinet::CabinetRowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function CabinetOccupancy( $CabinetID, $db = null ) {
		global $dbh;
		
		$sql = "select sum(Height) as Occupancy from fac_Device where Cabinet=$CabinetID";

		if ( ! $row = $dbh->query( $sql )->fetch() ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}

		return $row["Occupancy"];
	}

	function GetDCSelectList( $db = null ) {
		global $dbh;
		
		$sql="SELECT * FROM fac_DataCenter ORDER BY Name";

		$selectList='<select name="datacenterid" id="datacenterid">';

		foreach($dbh->query($sql) as $selectRow){
			$selected=($selectRow["DataCenterID"]==$this->DataCenterID)?' selected':'';
			$selectList.="<option value=\"{$selectRow["DataCenterID"]}\"$selected>{$selectRow["Name"]}</option>";
		}

		$selectList.='</select>';

		return $selectList;
	}
	
	function GetDCSelectListSubmit( $db = null ) {
		global $dbh;

		$sql = "select * from fac_DataCenter order by Name";

		$selectList = "<select name=\"datacenterid\" id=\"datacenterid\" onChange=\"form.submit()\">";

		foreach ( $dbh->query( $sql ) as $selectRow ) {
			if ( $selectRow[ "DataCenterID" ] == $this->DataCenterID )
				$selected = "selected";
			else
				$selected = "";

			$selectList .= "<option value=\"" . $selectRow[ "DataCenterID" ] . "\" $selected>" . $selectRow[ "Name" ] . "</option>";
		}

		$selectList .= "</select>";

		return $selectList;
	}
	
	function GetZoneSelectList(){
		global $dbh;
		
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Zone WHERE DataCenterID=$this->DataCenterID ORDER BY Description;";

		$selectList='<select name="zoneid" id="zoneid">';
		$selectList.='<option value=0>'.__("None").'</option>';

		foreach($dbh->query($sql) as $selectRow){
			$selected=($selectRow["ZoneID"]==$this->ZoneID)?' selected':'';
			$selectList.="<option value=\"{$selectRow["ZoneID"]}\"$selected>{$selectRow["Description"]}</option>";
		}

		$selectList.='</select>';

		return $selectList;
	}

	function GetCabinetByRow(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Cabinet WHERE CabRowID=$this->CabRowID ORDER BY Location ASC;";

		$cabinetList=array();

		foreach($dbh->query($sql) as $cabinetRow){
			$cabinetList[$cabinetRow['CabinetID']]=Cabinet::CabinetRowToObject($cabinetRow);
		}

		return $cabinetList;
	}
		
	function GetCabRowSelectList(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_CabRow WHERE ZoneID=$this->ZoneID ORDER BY Name;";

		$selectList='<select name="cabrowid" id="cabrowid">';
		$selectList.='<option value=0>'.__("None").'</option>';
		
		foreach($dbh->query($sql) as $selectRow){
			$selected=($selectRow["CabRowID"]==$this->CabRowID)?' selected':'';
			$selectList.="<option value=\"{$selectRow["CabRowID"]}\"$selected>{$selectRow["Name"]}</option>";
		}

		$selectList.='</select>';

		return $selectList;
	}
	
	function GetCabinetSelectList( $db = null) {
		global $dbh;
		
		$sql = "select Name, CabinetID, Location from fac_DataCenter, fac_Cabinet where fac_DataCenter.DataCenterID=fac_Cabinet.DataCenterID order by Name ASC, Location ASC";

		$selectList = "<select name=\"cabinetid\" id=\"cabinetid\"><option value=\"-1\">Storage Room</option>";

		foreach ( $dbh->query( $sql) as $selectRow ) {
			if ( $selectRow[ "CabinetID" ] == $this->CabinetID )
				$selected = "selected";
			else
				$selected = "";

			$selectList .= "<option value=\"" . $selectRow[ "CabinetID" ] . "\" $selected>" . $selectRow[ "Name" ] . " / " . $selectRow[ "Location" ] . "</option>";
		}

		$selectList .= "</select>";

		return $selectList;
	}

	function BuildCabinetTree( $db = null ) {
		global $dbh;
		
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

				$sql = "select * from fac_Cabinet where DataCenterID=\"$dcID\" order by Location ASC";

				foreach ( $dbh->query( $sql ) as $cabRow  ) {
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

	function DeleteCabinet( $db = null ) {
		global $dbh;
		
		/* Need to delete all devices and CDUs first */
		$tmpDev = new Device();
		$tmpCDU = new PowerDistribution();
		
		$tmpDev->Cabinet = $this->CabinetID;
		$devList = $tmpDev->ViewDevicesByCabinet( $db );
		
		foreach ( $devList as &$delDev ) {
			$delDev->DeleteDevice( $db );
		}
		
		$tmpCDU->CabinetID = $this->CabinetID;
		$cduList = $tmpCDU->GetPDUbyCabinet();
		
		foreach ( $cduList as &$delCDU ) {
			$delCDU->DeletePDU();
		}
		
		$sql = sprintf( "delete from fac_Cabinet where CabinetID=\"%d\"", intval( $this->CabinetID ) );
		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}

	function SearchByCabinetName( $db = null ) {
		global $dbh;
		
		$sql="select * from fac_Cabinet where ucase(Location) like \"%" . transform($this->Location) . "%\" order by Location;";

		$cabinetList=array();

		foreach ( $dbh->query( $sql ) as $cabinetRow ){
			$cabID=sizeof($cabinetList);
			$cabinetList[$cabID]=Cabinet::CabinetRowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function SearchByOwner( $db = null ) {
		global $dbh;
		
		$sql="select * from fac_Cabinet WHERE AssignedTo=".intval($this->AssignedTo)." ORDER BY Location;";

		$cabinetList=array();

		foreach ( $dbh->query( $sql ) as $cabinetRow ) {
			$cabID=sizeof($cabinetList);
			$cabinetList[$cabID]=Cabinet::CabinetRowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function SearchByCustomTag( $tag=null ) {
		global $dbh;
		
		$sql="SELECT a.* from fac_Cabinet a, fac_CabinetTags b, fac_Tags c WHERE a.CabinetID=b.CabinetID AND b.TagID=c.TagID AND UCASE(c.Name) LIKE UCASE('%".addslashes($tag)."%');";

		$cabinetList=array();

		foreach ( $dbh->query( $sql ) as $cabinetRow ) {
			$cabID=sizeof($cabinetList);
			$cabinetList[$cabID]=Cabinet::CabinetRowToObject($cabinetRow);
		}
		return $cabinetList;
	}

	function GetTags() {
		global $dbh;
		
		$sql = "SELECT TagID FROM fac_CabinetTags WHERE CabinetID=".intval($this->CabinetID).";";

		$tags = array();

		foreach ( $dbh->query( $sql ) as $row ) {
			$tags[]=Tags::FindName($row[0]);
		}

		return $tags;
	}
	function SetTags( $tags=array() ) {
		global $dbh;
		
		if(count($tags)>0){
			//Clear existing tags
			$this->SetTags();
			foreach($tags as $tag){
				$t=Tags::FindID($tag);
				if($t==0){
					$t=Tags::CreateTag($tag);
				}
				$sql="INSERT INTO fac_CabinetTags (CabinetID, TagID) VALUES (".intval($this->CabinetID).",$t);";
				if ( ! $dbh->exec($sql) ) {
					$info = $dbh->errorInfo();

					error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
					return false;
				}			
			}
		}else{
			//If no array is passed then clear all the tags
			$delsql="DELETE FROM fac_CabinetTags WHERE CabinetID=".intval($this->CabinetID).";";
			$dbh->exec($delsql);
		}
		return 0;
	}
}

class CabinetAudit {
	/*	CabinetAudit:	A perpetual audit trail for how often a cabinet has been audited, and by what user.
	*/
	
	var $CabinetID;
	var $UserID;
	var $AuditStamp;

	function CertifyAudit( $db = null ) {
		global $dbh;
		
		$sql = "insert into fac_CabinetAudit set CabinetID=\"" . intval( $this->CabinetID ) . "\", UserID=\"" . addslashes( $this->UserID ) . "\", AuditStamp=now()";

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}

	function GetLastAudit( $db = null ) {
		global $dbh;
		
		$sql = "select * from fac_CabinetAudit where CabinetID=\"" . intval( $this->CabinetID ) . "\" order by AuditStamp DESC Limit 1";

		if($row=$dbh->query($sql)->fetch()){
			$this->CabinetID=$row["CabinetID"];
			$this->UserID=$row["UserID"];
			$this->AuditStamp=date("M d, Y H:i", strtotime($row["AuditStamp"]));

			return true;
		} else {
			// No sense in logging an error for something that's never been done
			return false;
		}
	}
	
	function GetLastAuditByUser( $db = null ) {
		global $dbh;
		
		$sql = "select * from fac_CabinetAudit where UserID=\"" . addslashes( $this->UserID ) . "\" order by AuditStamp DESC Limit 1";

		if ( $row = $dbh->query( $sql )->fetch() ) {
			$this->CabinetID = $row["CabinetID"];
			$this->UserID = $row["UserID"];
			$this->AuditStamp = date( "M d, Y H:i", strtotime( $row["AuditStamp"] ) );
		} else {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}
}

class CabinetTemps {
	/* CabinetTemps:	Temperature sensor readings from intelligent, SNMP readable temperature sensors */
	
	var $CabinetID;
	var $LastRead;
	var $Temp;
	var $Humidity;

	function GetReading() {
		global $dbh;
		
		$sql = sprintf( "select * from fac_CabinetTemps where CabinetID=%d", $this->CabinetID );
		
		if ( $row = $dbh->query( $sql )->fetch() ) {
			$this->LastRead = date( "m-d-Y H:i:s", strtotime($row["LastRead"]) );
			$Temp = $row["Temp"];
			$Humidity = $row["Humidity"];
		} else {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}
	
	function UpdateReading() {
		global $dbh;
		
		$cab = new Cabinet();
		$cab->CabinetID = $this->CabinetID;
		$cab->GetCabinet();
		
		if ( ( strlen( $cab->SensorIPAddress ) == 0 ) || ( strlen( $cab->SensorCommunity ) == 0 ) || ( strlen( $cab->TempSensorOID ) == 0 ) )
			return;

		if ( ! function_exists( "snmpget" ) ) {
			$pollCommand = sprintf( "%s -v 2c -c %s %s %s | %s -d: -f4", $config->ParameterArray["snmpget"], $cab->SensorCommunity, $cab->SensorIPAddress, $cab->TempSensorOID, $config->ParameterArray["cut"] );
			
			exec( $pollCommand, $statsOutput );
			
			$tempValue = intval( @$statsOutput[0] );
		} else {
			$result = explode( " ", snmp2_get( $cab->SensorIPAddress, $cab->SensorCommunity, $cab->TempSensorOID ));
			
			$tempValue = intval($result[1]);
		}
		
		if ( $sensorValue > 0 ) {
			$this->Temp = $sensorValue;
			// Delete any existing record and then add in a new one
			$sql = sprintf( "delete from fac_CabinetTemps where CabinetID=%d", $this->CabinetID );
			if ( ! $dbh->exec( $sql ) ) {
				$info = $dbh->errorInfo();

				error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
				return false;
			}

			$sql = sprintf( "insert into fac_CabinetTemps set CabinetID=%d, Temp=%d, Humidity=%d, LastRead=now()", $this->CabinetID, $this->Temp, $this->Humidity );
			if ( ! $dbh->exec( $sql ) ) {
				$info = $dbh->errorInfo();

				error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
				return false;
			}
		}
	}	
}

class ColorCoding {
	var $ColorID;
	var $Name;
	var $DefaultNote;
	
	function CreateCode() {
		global $dbh;
		
		$sql="INSERT INTO fac_ColorCoding SET Name=\"".addslashes($this->Name)."\", 
			DefaultNote=\"".addslashes($this->DefaultNote)."\"";
		
		if($dbh->exec($sql)){
			$this->ColorID=$dbh->lastInsertId();
		}else{
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]}");
			return false;
		}
		
		return $this->ColorID;
	}
	
	function UpdateCode() {
		global $dbh;
		
		$sql="UPDATE fac_ColorCoding SET Name=\"".addslashes($this->Name)."\", 
			DefaultNote=\"".addslashes($this->DefaultNote)."\" WHERE ColorID=".intval($this->ColorID).";";
		
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]}");
			return false;
		}else{		
			return true;
		}
	}
	
	function DeleteCode() {
		/* If you call this, the upstream application should be checking to see if it is used already - you don't want to
			create orphan connetions that reference this color code! */
		global $dbh;
		
		$sql="DELETE FROM fac_ColorCoding WHERE ColorID=".intval($this->ColorID);
		
		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]}");
			return false;
		}
		
		return true;
	}
	
	function GetCode() {
		global $dbh;
		
		$sql="SELECT * FROM fac_ColorCoding WHERE ColorID=".intval($this->ColorID);

		if($row=$dbh->query($sql)->fetch()){
			$this->Name=$row["Name"];
			$this->DefaultNote=$row["DefaultNote"];
		}else{
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]}");
			return false;
		}
			
		return true;
	}
	
	static function GetCodeList() {
		global $dbh;
		
		$sql="SELECT * FROM fac_ColorCoding ORDER BY Name ASC";
		
		$codeList=array();
		foreach($dbh->query($sql) as $row){
			$n=$row["ColorID"]; // index array by id
			$codeList[$n]=new ColorCoding();
			$codeList[$n]->ColorID=$row["ColorID"];
			$codeList[$n]->Name=$row["Name"];
			$codeList[$n]->DefaultNote=$row["DefaultNote"];
		}
		
		return $codeList;
	}

	static function ResetCode($colorid,$tocolorid=0){
	/*
	 * This probably shouldn't be a function here since it will only be used in one
	 * place. This function will remove a color code from any device ports or will
	 * set it to another via an optional second color id
	 *
	 */
		global $dbh;
		$colorid=intval($colorid);
		$tocolorid=intval($tocolorid); // it will always be 0 unless otherwise set

		$sql="UPDATE fac_DevicePorts SET ColorID='$tocolorid' WHERE ColorID='$colorid';";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]}");
			return false;
		}else{		
			return true;
		}
	}

	static function TimesUsed($colorid){
		global $dbh;
		$colorid=intval($colorid);

		// get a count of the number of times this color is in use both on ports or assigned
		// to a template.  
		$sql="SELECT COUNT(*) + (SELECT COUNT(*) FROM fac_MediaTypes WHERE ColorID=$colorid) 
			AS Result FROM fac_DevicePorts WHERE ColorID=$colorid";
		$count=$dbh->prepare($sql);
		$count->execute();
		

		return $count->fetchColumn();
	}
}

class ConnectionPath {
	
	var $DeviceID;
	var $DeviceType;
	var $PortNumber;
	var $Front;  //false for rear connetcion of panels and end of path for other devices
	
	private $PathAux; //loops control
	
	function MakeSafe(){
		$this->DeviceID=intval($this->DeviceID);
		if ( ! in_array( $this->DeviceType, array( 'Server', 'Appliance', 'Storage Array', 'Switch', 'Chassis', 'Patch Panel', 'Physical Infrastructure' ) ) )
		  $this->DeviceType = "Server";
		$this->PortNumber=intval($this->PortNumber);
		$this->Front=($this->Front)?true:false;
	}

	private function AddDeviceToPathAux () {
		$i=count($this->PathAux);
		$this->PathAux[$i]["DeviceID"]=$this->DeviceID;
		$this->PathAux[$i]["DeviceType"]=$this->DeviceType;
		$this->PathAux[$i]["PortNumber"]=$this->PortNumber;
		$this->PathAux[$i]["Front"]=$this->Front;
	}
	
	private function ClearPathAux(){
		$this->PathAux=array();
	}
	
	private function IsDeviceInPathAux () {
		$ret=false;
		for ($i=0; $i<count($this->PathAux); $i++){
			if ($this->PathAux[$i]["DeviceID"]==$this->DeviceID && $this->PathAux[$i]["PortNumber"]=$this->PortNumber) {
				$ret=true;
				break;
			}
		}
		return $ret;
	}
	
	function GotoHeadDevice () {
	//It puts the object in the first device of the path, if it is not it already
		$this->MakeSafe();
		$this->ClearPathAux();

		while ($this->DeviceType=="Patch Panel"){
			if (!$this->IsDeviceInPathAux()){
				$this->AddDeviceToPathAux();
			}else {
				//loop!!
				return false;
			}
			if (!$this->GotoNextDevice ()) {
				//It is a no connected panel in this direccion. Here it begins the path.
				//I put it pointing to contrary direction
				$this->Front=!$this->Front;
				return true;
			}
		}
		$this->Front=true;
		return true;
	}
	
	function GotoNextDevice ( $db = null ) {
	//It puts the object with the DeviceID, PortNumber and Front of the following device in the path.
	//If the current device of the object is not connected to at all, gives back "false" and the object does not change
		global $dbh;
		$this->MakeSafe();
		
		if ($this->DeviceType=="Patch Panel"){
			//it's a panel
			if ($this->Front){
				$sql = "SELECT FrontEndPointDeviceID AS DeviceID,
							FrontEndpointPort AS PortNumber,
							DeviceType 
						FROM fac_patchconnection p INNER JOIN fac_device d ON p.FrontEndPointDeviceID=d.DeviceID
						WHERE PanelDeviceID=". $this->DeviceID." AND PanelPortNumber=". $this->PortNumber;
							
			} else {
				$sql = "SELECT RearEndPointDeviceID AS DeviceID,
							RearEndpointPort AS PortNumber,
							DeviceType 
						FROM fac_patchconnection p INNER JOIN fac_device d ON p.RearEndPointDeviceID=d.DeviceID
						WHERE PanelDeviceID=". $this->DeviceID." AND PanelPortNumber=". $this->PortNumber;
				
			}
			$result = $dbh->prepare($sql);
			$result->execute();
			$Front_sig=!$this->Front;
		}elseif($this->Front){
			//It isn't a panel
			//Is it connected to rear connection of other pannel? 
			$sql = "SELECT PanelDeviceID AS DeviceID,
						PanelPortNumber AS PortNumber,
						'Patch Panel' AS DeviceType 
					FROM fac_patchconnection
					WHERE RearEndPointDeviceID=". $this->DeviceID." AND RearEndpointPort=". $this->PortNumber;
			
			$result = $dbh->prepare($sql);
			$result->execute();
			if($result->rowCount()>0){
				//I go out by front connetcion of the panel
				$Front_sig=true;
			}else{
				//In other cases, or I go out by rear connetcion, or I can not follow
				$Front_sig=false;
				
				//Is it connected to front connection of other pannel?
				$sql = "SELECT PanelDeviceID AS DeviceID,
							PanelPortNumber AS PortNumber,
							'Patch Panel' AS DeviceType 
						FROM fac_patchconnection
						WHERE FrontEndPointDeviceID=". $this->DeviceID." AND FrontEndpointPort=". $this->PortNumber;
				
				$result = $dbh->prepare($sql);
				$result->execute();	
				if($result->rowCount()==0){
					//Is it connected to switch?
					$sql = "SELECT SwitchDeviceID AS DeviceID,
								SwitchPortNumber AS PortNumber,
								'Switch' AS DeviceType 
							FROM fac_switchconnection
							WHERE EndPointDeviceID=". $this->DeviceID." AND EndpointPort=". $this->PortNumber;
					$result = $dbh->prepare($sql);
					$result->execute();
					
					if($result->rowCount()==0){
						//Is it a switch?
						$sql = "SELECT EndPointDeviceID AS DeviceID,
									EndpointPort AS PortNumber,
									DeviceType 
								FROM fac_switchconnection s INNER JOIN fac_device d ON s.EndPointDeviceID=d.DeviceID
								WHERE SwitchDeviceID=". $this->DeviceID." AND SwitchPortNumber=". $this->PortNumber;
						$result = $dbh->prepare($sql);
						$result->execute();
					
						if($result->rowCount()==0){
							//Not connected
							return false;
						}
					}
				}
			}
		}else{
			return false;
		}		
		
		$row = $result->fetch();
		if (is_null($row["DeviceID"]) || is_null($row["PortNumber"]) || is_null($row["DeviceType"])){
			return false;
		}
		$this->DeviceID=$row["DeviceID"];
		$this->PortNumber=$row["PortNumber"];
		$this->DeviceType=$row["DeviceType"];
		$this->Front=($this->DeviceType=="Patch Panel")?$Front_sig:false;
		
		return true;
	}
	
	
} //END OF CONNETCIONPATH

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
	var $FirstPortNum;
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
	
	function MakeSafe() {
		$this->DeviceID=intval($this->DeviceID);
		$this->Label=addslashes(trim($this->Label));
		$this->SerialNo=addslashes(trim($this->SerialNo));
		$this->AssetTag=addslashes(trim($this->AssetTag));
		$this->PrimaryIP=addslashes(trim($this->PrimaryIP));
		$this->SNMPCommunity=addslashes(trim($this->SNMPCommunity));
		$this->ESX=intval($this->ESX);
		$this->Owner=intval($this->Owner);
		$this->EscalationTimeID=intval($this->EscalationTimeID);
		$this->EscalationID=intval($this->EscalationID);
		$this->PrimaryContact=intval($this->PrimaryContact);
		$this->Cabinet=intval($this->Cabinet);
		$this->Position=intval($this->Position);
		$this->Height=intval($this->Height);
		$this->Ports=intval($this->Ports);
		$this->FirstPortNum=intval($this->FirstPortNum);
		$this->TemplateID=intval($this->TemplateID);
		$this->NominalWatts=intval($this->NominalWatts);
		$this->PowerSupplyCount=intval($this->PowerSupplyCount);
		$this->DeviceType=addslashes(trim($this->DeviceType));
		$this->ChassisSlots=intval($this->ChassisSlots);
		$this->RearChassisSlots=intval($this->RearChassisSlots);
		$this->ParentDevice=intval($this->ParentDevice);
		$this->MfgDate=addslashes($this->MfgDate);
		$this->InstallDate=addslashes($this->InstallDate);
		$this->WarrantyCo=addslashes(trim($this->WarrantyCo));
		$this->WarrantyExpire=addslashes($this->WarrantyExpire);
		$this->Notes=addslashes(trim($this->Notes));
		$this->Reservation=intval($this->Reservation);
	}
	
	function MakeDisplay() {
		$this->Label=stripslashes($this->Label);
		$this->SerialNo=stripslashes($this->SerialNo);
		$this->AssetTag=stripslashes($this->AssetTag);
		$this->PrimaryIP=stripslashes($this->PrimaryIP);
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
		$this->DeviceType=stripslashes($this->DeviceType);
		$this->MfgDate=stripslashes($this->MfgDate);
		$this->InstallDate=stripslashes($this->InstallDate);
		$this->WarrantyCo=stripslashes($this->WarrantyCo);
		$this->WarrantyExpire=stripslashes($this->WarrantyExpire);
		$this->Notes=stripslashes($this->Notes);
	}

	static function DeviceRowToObject($dbRow){
		/*
		 * Generic function that will take any row returned from the fac_Devices
		 * table and convert it to an object for use in array or other
		 */

		$dev=new Device();
		$dev->DeviceID=$dbRow["DeviceID"];
		$dev->Label=$dbRow["Label"];
		$dev->SerialNo=$dbRow["SerialNo"];
		$dev->AssetTag=$dbRow["AssetTag"];
		$dev->PrimaryIP=$dbRow["PrimaryIP"];
		$dev->SNMPCommunity=$dbRow["SNMPCommunity"];
		$dev->ESX=$dbRow["ESX"];
		$dev->Owner=$dbRow["Owner"];
		// Suppressing errors on the following two because they can be null and that generates an apache error
		@$dev->EscalationTimeID=$dbRow["EscalationTimeID"];
		@$dev->EscalationID=$dbRow["EscalationID"];
		$dev->PrimaryContact=$dbRow["PrimaryContact"];
		$dev->Cabinet=$dbRow["Cabinet"];
		$dev->Position=$dbRow["Position"];
		$dev->Height=$dbRow["Height"];
		$dev->Ports=$dbRow["Ports"];
		$dev->FirstPortNum=$dbRow["FirstPortNum"];
		$dev->TemplateID=$dbRow["TemplateID"];
		$dev->NominalWatts=$dbRow["NominalWatts"];
		$dev->PowerSupplyCount=$dbRow["PowerSupplyCount"];
		$dev->DeviceType=$dbRow["DeviceType"];
		$dev->ChassisSlots=$dbRow["ChassisSlots"];
		$dev->RearChassisSlots=$dbRow["RearChassisSlots"];
		$dev->ParentDevice=$dbRow["ParentDevice"];
		$dev->MfgDate=$dbRow["MfgDate"];
		$dev->InstallDate=$dbRow["InstallDate"];
		$dev->WarrantyCo=$dbRow["WarrantyCo"];
		@$dev->WarrantyExpire=$dbRow["WarrantyExpire"];
		$dev->Notes=$dbRow["Notes"];
		$dev->Reservation=$dbRow["Reservation"];

		$dev->MakeDisplay();

		return $dev;
	}


	function CreateDevice( $db = null ) {
		global $dbh;
		
		$this->MakeSafe();
		
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);
		
		//Keep weird values out of DeviceType
		if(!in_array($this->DeviceType,array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure'))){
			$this->DeviceType="Server";
		}

		$sql="INSERT INTO fac_Device SET Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", AssetTag=\"$this->AssetTag\", 
			PrimaryIP=\"$this->PrimaryIP\", SNMPCommunity=\"$this->SNMPCommunity\", ESX=$this->ESX, Owner=$this->Owner, 
			EscalationTimeID=$this->EscalationTimeID, EscalationID=$this->EscalationID, PrimaryContact=$this->PrimaryContact, 
			Cabinet=$this->Cabinet, Position=$this->Position, Height=$this->Height, Ports=$this->Ports, 
			FirstPortNum=$this->FirstPortNum, TemplateID=$this->TemplateID, NominalWatts=$this->NominalWatts, 
			PowerSupplyCount=$this->PowerSupplyCount, DeviceType=\"$this->DeviceType\", ChassisSlots=$this->ChassisSlots, 
			RearChassisSlots=$this->RearChassisSlots,ParentDevice=$this->ParentDevice, 
			MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
			InstallDate=\"".date("Y-m-d", strtotime($this->InstallDate))."\", WarrantyCo=\"$this->WarrantyCo\", 
			WarrantyExpire=\"".date("Y-m-d", strtotime($this->WarrantyExpire))."\", Notes=\"$this->Notes\", 
			Reservation=$this->Reservation;";

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		$this->DeviceID = $dbh->lastInsertId();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->DeviceID;
	}

	function CopyDevice($db,$clonedparent=null) {
		/*
		 * Need to make a copy of a device for the purpose of assigning a reservation during a move
		 *
		 * The second paremeter is optional for a copy.  if it is set and the device is a chassis
		 * this should be set to the ID of the new parent device.
		 *
		 * Also do not copy any power or network connections!
		 */
		
		// Get the device being copied
		$this->GetDevice($db);
		
		if($this->ParentDevice >0){
			/*
			 * Child devices will need to be constrained to the chassis. Check for open slots
			 * on whichever side of the chassis the blade is currently.  If a slot is available
			 * clone into the next available slot or return false and display an appropriate 
			 * errror message
			 */
			$tmpdev=new Device();
			$tmpdev->DeviceID=$this->ParentDevice;
			$tmpdev->GetDevice($db);
			$children=$tmpdev->GetDeviceChildren();
			if($tmpdev->ChassisSlots>0 || $tmpdev->RearChassisSlots>0){
				// If we're cloning every child then there is no need to attempt to find empty slots
				if(is_null($clonedparent)){
					$front=array();
					$rear=array();
					$pos=$this->Position;
					if($tmpdev->ChassisSlots>0){
						for($i=1;$i<=$tmpdev->ChassisSlots;$i++){
							$front[$i]=false;
						}
					}
					if($tmpdev->RearChassisSlots>0){
						for($i=1;$i<=$tmpdev->RearChassisSlots;$i++){
							$rear[$i]=false;
						}
					}
					foreach($children as $child){
						($child->ChassisSlots==0)?$front[$child->Position]="yes":$rear[$child->Position]="yes";
					}
					if($this->ChassisSlots==0){
						//Front slot device
						for($i=$tmpdev->ChassisSlots;$i>=1;$i--){
							if($front[$i]!="yes"){$this->Position=$i;}
						}
					}else{
						//Rear slot device
						for($i=$tmpdev->RearChassisSlots;$i>=1;$i--){
							if($rear[$i]!="yes"){$this->Position=$i;}
						}
					}
				}
				// Make sure the position updated before creating a new device
				if((isset($pos) && $pos!=$this->Position) || !is_null($clonedparent)){
					(!is_null($clonedparent))?$this->ParentDevice=$clonedparent:'';
					$this->CreateDevice($db);
				}else{
					return false;
				}
			}
		}else{
			// Now set it as being in storage
			$this->Cabinet=-1;

			// If this is a chassis device then check for children to cloned BEFORE we change the deviceid
			if($this->DeviceType=="Chassis"){
				$childList=$this->GetDeviceChildren();
			}	

			// And finally create a new device based on the exact same info
			$this->CreateDevice($db);

			// If this is a chassis device and children are present clone them
			if(isset($childList)){
				foreach($childList as $child){
					$child->CopyDevice($db,$this->DeviceID);
				}
			}

		}
		return true;
	}
	
	function Surplus() {
		global $dbh;
		
		// Make sure we're not trying to decommission a device that doesn't exist
		if(!$this->GetDevice()){
			die( "Can't find device $this->DeviceID to decommission!" );
		}

		$sql="INSERT INTO fac_Decommission VALUES ( NOW(), \"$this->Label\", 
			\"$this->SerialNo\", \"$this->AssetTag\", \"{$_SERVER['REMOTE_USER']}\" )";

		if(!$dbh->exec($sql)){
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		// Ok, we have the transaction of decommissioning, now tidy up the database.
		$this->DeleteDevice();
	}
  
	function MoveToStorage() {
		// Cabinet ID of -1 means that the device is in the storage area
		$this->Cabinet = -1;
		$this->UpdateDevice();
		
		// While the child devices will automatically get moved to storage as part of the UpdateDevice() call above, it won't sever their network connections
		if ( $this->DeviceType == "Chassis" ) {
			$childList = $this->GetDeviceChildren();
			foreach($childList as $child){
				$child->MoveToStorage();
			}
		}

		$tmpConn=new SwitchConnection();
		$tmpConn->SwitchDeviceID=$this->DeviceID;
		$tmpConn->EndpointDeviceID=$this->DeviceID;
		$tmpConn->DropSwitchConnections();
		$tmpConn->DropEndpointConnections();
		
		$tmpPan=new PatchConnection();
		if ( $this->DeviceType == "Patch Panel" ) {
			$tmpPan->PanelDeviceID = $this->DeviceID;
			$tmpPan->DropPanelConnections();
		} else {
			$tmpPan->FrontEndpointDeviceID = $this->DeviceID;
			$tmpPan->DropEndpointConnections();
		}
	}
  
	function UpdateDevice() {
		global $dbh;
		// Stupid User Tricks #417 - A user could change a device that has connections (switch or patch panel) to one that doesn't
		// Stupid User Tricks #148 - A user could change a device that has children (chassis) to one that doesn't
		//
		// As a "safety mechanism" we simply won't allow updates if you try to change a chassis IF it has children
		// For the switch and panel connections, though, we drop any defined connections
		
		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->DeviceID;
		$tmpDev->GetDevice();
	
		$this->MakeSafe();	
		if($tmpDev->DeviceType == "Chassis" && $tmpDev->DeviceType != $this->DeviceType){
			// SUT #148 - Previously defined chassis is no longer a chassis
			// If it has children, return with no update
			$childList=$this->GetDeviceChildren();
			if(sizeof($childList)>0){
				$this->GetDevice();
				return;
			}
		}
		
		if(($tmpDev->DeviceType=="Switch" || $tmpDev->DeviceType=="Patch Panel") && $tmpDev->DeviceType!=$this->DeviceType){
			// SUT #417 - Changed a Switch or Patch Panel to something else (even if you change a switch to a Patch Panel, the connections are different)
			if($tmpDev->DeviceType=="Switch"){
				$tmpSw=new SwitchConnection();
				$tmpSw->SwitchDeviceID=$tmpDev->DeviceID;
				$tmpSw->DropSwitchConnections();
				$tmpSw->DropEndpointConnections();
			}
			
			if($tmpDev->DeviceType=="Patch Panel"){
				$tmpPan=new PatchConnetion();
				$tmpPan->DropPanelConnections();
				$tmpPan->DropEndpointConnections();
			}
		}
		
		// Force all uppercase for labels
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);

		//Keep weird values out of DeviceType
		if(!in_array($this->DeviceType,array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure'))){
			$this->DeviceType="Server";
		}

		// You can't update what doesn't exist, so check for existing record first and retrieve the current location
		$sql = "SELECT * FROM fac_Device WHERE DeviceID=$this->DeviceID;";
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}		

		// If you changed cabinets then the power connections need to be removed
		if($row["Cabinet"]!=$this->Cabinet){
			$powercon=new PowerConnection();
			$powercon->DeviceID=$this->DeviceID;
			$powercon->DeleteConnections();
		}
  
		$sql="UPDATE fac_Device SET Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", AssetTag=\"$this->AssetTag\", 
			PrimaryIP=\"$this->PrimaryIP\", SNMPCommunity=\"$this->SNMPCommunity\", ESX=$this->ESX, Owner=$this->Owner, 
			EscalationTimeID=$this->EscalationTimeID, EscalationID=$this->EscalationID, PrimaryContact=$this->PrimaryContact, 
			Cabinet=$this->Cabinet, Position=$this->Position, Height=$this->Height, Ports=$this->Ports, 
			FirstPortNum=$this->FirstPortNum, TemplateID=$this->TemplateID, NominalWatts=$this->NominalWatts, 
			PowerSupplyCount=$this->PowerSupplyCount, DeviceType=\"$this->DeviceType\", ChassisSlots=$this->ChassisSlots, 
			RearChassisSlots=$this->RearChassisSlots,ParentDevice=$this->ParentDevice, 
			MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
			InstallDate=\"".date("Y-m-d", strtotime($this->InstallDate))."\", WarrantyCo=\"$this->WarrantyCo\", 
			WarrantyExpire=\"".date("Y-m-d", strtotime($this->WarrantyExpire))."\", Notes=\"$this->Notes\", 
			Reservation=$this->Reservation WHERE DeviceID=$this->DeviceID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		return true;
	}

	function GetDevice($db=null){
		global $dbh;
	
		$this->MakeSafe();
	
		if($this->DeviceID==0 || $this->DeviceID == null){
			return false;
		}
		
		$sql="SELECT * FROM fac_Device WHERE DeviceID=$this->DeviceID;";

		if($devRow=$dbh->query($sql)->fetch()){
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
			$this->FirstPortNum = $devRow["FirstPortNum"];
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

			$this->MakeDisplay();

			return true;
		}else{
			return false;
		}
	}
	
	function GetDevicesbyAge( $db, $days = 7 ) {
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE DATEDIFF(CURDATE(),InstallDate)<=".intval($days)." ORDER BY InstallDate ASC;";
		
		$deviceList = array();

		foreach ( $dbh->query( $sql ) as $deviceRow ) {
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}
		
		return $deviceList;
	}
		
	function GetDeviceChildren() {
		global $dbh;
	
		$this->MakeSafe();
	
		$sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY ChassisSlots, Position ASC;";

		$childList = array();

		foreach($dbh->query($sql) as $row){
			$childList[$row["DeviceID"]]=Device::DeviceRowToObject($row);
		}
		
		return $childList;
	}
	
	function GetParentDevices( $db = null ) {
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ChassisSlots>0 AND ParentDevice=0 ORDER BY Label ASC;";
		
		$parentList = array();

		foreach($dbh->query($sql) as $row){
			$parentList[$row["DeviceID"]]=Device::DeviceRowToObject($row);
		}
		
		return $parentList;
	}

	function ViewDevicesByCabinet( $db = null ) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet AND Cabinet!=0 ORDER BY Position DESC;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}
	
	function CreatePatchCandidateList( $db = null ) {
		// This will generate a list of all devices capable of being plugged into a switch
		// or patch panel - meaning that you set the DeviceID field to the target device and it will
		// generate a list of all candidates that are in the same Data Center.
		global $dbh;

		$this->MakeSafe();

		$dev=($this->ParentDevice>0)?$this->ParentDevice:$this->DeviceID;
		$sql = "SELECT b.DataCenterID FROM fac_Device a, fac_Cabinet b WHERE a.DeviceID=$dev AND a.Cabinet=b.CabinetID;";
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}
		
		$targetDC = $row["DataCenterID"];

		$sql="SELECT * FROM fac_Device a, fac_Cabinet b WHERE a.Cabinet=b.CabinetID AND 
			b.DataCenterID=$targetDC AND a.DeviceType!='Physical Infrastructure' AND 
			a.DeviceID!=$dev ORDER BY a.Label;";

		$deviceList=array();
	  
		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}
	
	static function GetPatchPanels(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE DeviceType='Patch Panel' ORDER BY Label ASC;";
		
		$panelList=array();

		foreach($dbh->query($sql) as $row){
			$panelList[$row["DeviceID"]]=Device::DeviceRowToObject($row);
		}
		
		return $panelList;
	}

	function DeleteDevice( $db = null ) {
		global $dbh;
	
		$this->MakeSafe();
	
		// First, see if this is a chassis that has children, if so, delete all of the children first
		if ( $this->ChassisSlots > 0 ) {
			$childList = $this->GetDeviceChildren();
			
			foreach ( $childList as $tmpDev ) {
				$tmpDev->DeleteDevice( $db );
			}
		}
		
		// Delete all network connections first
		$tmpConn = new SwitchConnection();
		$tmpConn->SwitchDeviceID = $this->DeviceID;
		$tmpConn->EndpointDeviceID = $this->DeviceID;
		$tmpConn->DropSwitchConnections();
		$tmpConn->DropEndpointConnections();
		
		$tmpPan = new PatchConnection();
		if($this->DeviceType=="Patch Panel"){
			$tmpPan->PanelDeviceID=$this->DeviceID;
			$tmpPan->DropPanelConnections();
		}else{
			$tmpPan->FrontEndpointDeviceID=$this->DeviceID;
			$tmpPan->DropEndpointConnections();
		}
		
		// Delete power connections next
		$powercon = new PowerConnection();
		$powercon->DeviceID = $this->DeviceID;
		$powercon->DeleteConnections();

		// Now delete the device itself
		$sql="DELETE FROM fac_Device WHERE DeviceID=$this->DeviceID;";

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return;
	}


	function SearchDevicebyLabel( $db = null ) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE Label LIKE \"%$this->Label%\" ORDER BY Label;";

		$deviceList = array();

		foreach ( $dbh->query( $sql ) as $deviceRow ) {
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

	function GetDevicesbyOwner( $db = null ) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT *, (SELECT b.DataCenterID FROM fac_Device a, fac_Cabinet b 
			WHERE a.Cabinet=b.CabinetID AND a.DeviceID=search.DeviceID ORDER BY 
			b.DataCenterID, a.Label) DataCenterID FROM fac_Device search WHERE 
			Owner=$this->Owner ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

  function GetESXDevices( $db = null ) {
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ESX=TRUE ORDER BY DeviceID;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){ 
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebySerialNo( $db = null ) {
		global $dbh;

		$this->MakeSafe();

		$sql = "SELECT * FROM fac_Device WHERE SerialNo LIKE \"%$this->SerialNo%\" ORDER BY Label;";

		$deviceList = array();

		foreach ( $dbh->query( $sql ) as $deviceRow ) {
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebyAssetTag( $db = null ) {
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE AssetTag LIKE \"%$this->AssetTag%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;

	}
  
	function SearchByCustomTag( $db, $tag = null ) {
		global $dbh;
		
		//
		//Build a somewhat ugly SQL expression in order to do 
		//semi-complicated tag searches.  All tags are
		//logically AND'ed togther.  Thus, if you search for tags
		//'foo' and 'bar' and '!quux', the results should be only 
		//those systems with both 'foo' and 'bar' tags while 
		//excluding those with 'quux'.
		//

		// Basic start of the query.
		$sql = "SELECT DISTINCT a.* FROM fac_Device a, fac_DeviceTags b, fac_Tags c WHERE a.DeviceID=b.DeviceID AND b.TagID=c.TagID ";

		//split the "tag" if needed, and strip whitespace
		//note that tags can contain spaces, so we have to use
		//something else in the search string (commas seem logical)
		$tags = explode(",", $tag);
		$tags = array_map("trim", $tags);

		//Two arrays, one of tags we want, and one of those we don't want.
		$want_tags = array();
		$not_want_tags = array();

		foreach ( $tags as $t ) {
			//If the tag starts with a "!" character, we want to 
			//specifically exclude it from the search.
			if (strpos($t, '!') !== false ) {
				$t=preg_replace('/^!/', '', $t,1);	//remove the leading "!" from the tag
			$not_want_tags[].= $t;
			} else {
				$want_tags[] .= $t;
			}
		}

		/*
		error_log(join(',',$want_tags));
		error_log(join(',',$not_want_tags));
		*/
		$num_want_tags = count($want_tags);
		if (count($want_tags)) {
			// This builds the part of the query that looks for all tags we want.
			// First, some basic SQL to start with
			$sql .= 'AND c.TagId in ( ';
			$sql .= 'SELECT Want.TagId from fac_Tags Want WHERE ';

			// Loop over the tags we want.
			$want_sql = sprintf("UCASE(Want.Name) LIKE UCASE('%%%s%%')", array_shift($want_tags));
			foreach ($want_tags as $t) {
				$want_sql .= sprintf(" OR UCASE(Want.Name) LIKE UCASE('%%%s%%')", $t);
			}

			$sql .= "( $want_sql ) )"; //extra parens for closing sub-select

		}

		//only include this section if we have negative tags
		if (count($not_want_tags)) {
			$sql .= 'AND a.DeviceID NOT IN ( ';
			$sql .= 'SELECT D.DeviceID FROM fac_Device D, fac_DeviceTags DT, fac_Tags T ';
			$sql .= 'WHERE D.DeviceID = DT.DeviceID ';
			$sql .= '  AND DT.TagID=T.TagID ';

			$not_want_sql = sprintf("UCASE(T.Name) LIKE UCASE('%%%s%%')", array_shift($not_want_tags));
            foreach ($not_want_tags as $t) {
                $not_want_sql .= sprintf(" OR UCASE(c.Name) LIKE UCASE('%%%s%%')", $t);
            }
			$sql .= "  AND ( $not_want_sql ) )"; //extra parens to close sub-select
		}

		// This bit of magic filters out the results that don't match enough tags.
		$sql .= "GROUP BY a.DeviceID HAVING COUNT(c.TagID) >= $num_want_tags";

		//error_log(">> $sql\n");

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}
		
		return $deviceList;
	}

	function UpdateWattageFromTemplate() {
		$tmpl=new DeviceTemplate();
		$tmpl->TemplateID=$this->TemplateID;
		$tmpl->GetTemplateByID();

		$this->NominalWatts=$tmpl->Wattage;
	}
	
	function GetTop10Tenants( $db = null ) {
		global $dbh;
		
		$sql = "select sum(height) as RackUnits,fac_Department.Name as OwnerName from fac_Device,fac_Department where Owner is not NULL and fac_Device.Owner=fac_Department.DeptID group by Owner order by RackUnits DESC limit 0,10";

		$deptList = array();
		
		foreach ( $dbh->query( $sql ) as $row )
		  $deptList[$row["OwnerName"]] = $row["RackUnits"];
		  
		return $deptList;
	}
  
  
	function GetTop10Power( $db = null ) {
		global $dbh;
		
		$sql = "select sum(NominalWatts) as TotalPower,fac_Department.Name as OwnerName from fac_Device,fac_Department where Owner is not NULL and fac_Device.Owner=fac_Department.DeptID group by Owner order by TotalPower DESC limit 0,10";
		$deptList = array();

		foreach ( $dbh->query( $sql ) as $row )
		  $deptList[$row["OwnerName"]] = $row["TotalPower"];
		  
		return $deptList;
	}
  
  
  function GetDeviceDiversity( $db = null ) {
	global $dbh;
	
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
			$powerSource = $PDU->GetSourceForPDU();

			if ( ! in_array( $powerSource, $sourceList ) )
				$sourceList[$sourceCount++] = $powerSource;
		}
	}
	
    return $sourceList;
  }

  function GetSinglePowerByCabinet( $db = null ) {
	global $dbh;
	
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
      
		if(sizeof($diversityList) <2){      
			$currSize=sizeof($devList);
			$devList[$currSize]=$devRow;
		}
    }
    
    return $devList;
  }

	function GetTags() {
		global $dbh;
		
		$sql="SELECT TagID FROM fac_DeviceTags WHERE DeviceID=".intval($this->DeviceID).";";

		$tags=array();

		foreach($dbh->query($sql) as $tagid){
			$tags[]=Tags::FindName($tagid[0]);
		}

		return $tags;
	}
	
	function SetTags($tags=array()) {
		global $dbh;

		$this->MakeSafe();		
		if(count($tags)>0){
			//Clear existing tags
			$this->SetTags();
			foreach($tags as $tag){
				$t=Tags::FindID($tag);
				if($t==0){
					$t=Tags::CreateTag($tag);
				}
				$sql="INSERT INTO fac_DeviceTags (DeviceID, TagID) VALUES ($this->DeviceID,$t);";
				if(!$dbh->exec($sql)){
					$info=$dbh->errorInfo();

					error_log("PDO Error: {$info[2]} SQL=$sql");
					return false;
				}				
			}
		}else{
			//If no array is passed then clear all the tags
			$sql="DELETE FROM fac_DeviceTags WHERE DeviceID=$this->DeviceID;";
			if(!$dbh->exec($sql)){
				return false;
			}
		}
		return;
	}
	
	//JMGA added
	function GetDeviceLineage( $db = null ) {
		$devList=array();
		$num=1;
		$devList[$num]=new Device();
		$devList[$num]->DeviceID=$this->DeviceID;
		$devList[$num]->GetDevice();
		
		while ( $devList[$num]->ParentDevice <> 0) {
			$num++;
			$devList[$num]=new Device();
			$devList[$num]->DeviceID = $devList[$num-1]->ParentDevice;
			$devList[$num]->GetDevice();
		}
		return $devList;	
	}
	//FIN JMGA
}

class DevicePorts {
	var $DeviceID;
	var $PortNumber;
	var $Label;
	var $MediaID;
	var $ColorID;
	var $PortNotes;
	var $ConnectedDeviceID;
	var $ConnectedPort;
	var $Notes;
	
	function MakeSafe() {
		$this->DeviceID=intval($this->DeviceID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=addslashes(trim($this->Label));
		$this->MediaID=intval($this->MediaID);
		$this->ColorID=intval($this->ColorID);
		$this->PortNotes=addslashes(trim($this->PortNotes));
		$this->ConnectedDeviceID=intval($this->ConnectedDeviceID);
		$this->ConnectedPort=intval($this->ConnectedPort);
		$this->Notes=addslashes(trim($this->Notes));

		if($this->ConnectedDeviceID==0 || $this->ConnectedPort==0){
			$this->ConnectedDeviceID="NULL";
			$this->ConnectedPort="NULL";
		}
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim($this->Label));
		$this->PortNotes=stripslashes(trim($this->PortNotes));
		$this->Notes=stripslashes(trim($this->Notes));
	}

	static function RowToObject($dbRow){
		$dp=new DevicePorts();
		$dp->DeviceID=$dbRow['DeviceID'];
		$dp->PortNumber=$dbRow['PortNumber'];
		$dp->Label=$dbRow['Label'];
		$dp->MediaID=$dbRow['MediaID'];
		$dp->ColorID=$dbRow['ColorID'];
		$dp->PortNotes=$dbRow['PortNotes'];
		$dp->ConnectedDeviceID=$dbRow['ConnectedDeviceID'];
		$dp->ConnectedPort=$dbRow['ConnectedPort'];
		$dp->Notes=$dbRow['Notes'];

		$dp->MakeDisplay();

		return $dp;
	}

	function getPort(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Ports WHERE DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			$this->DeviceID=$row['DeviceID'];
			$this->PortNumber=$row['PortNumber'];
			$this->Label=$row['Label'];
			$this->MediaID=$row['MediaID'];
			$this->ColorID=$row['ColorID'];
			$this->PortNotes=$row['PortNotes'];
			$this->ConnectedDeviceID=$row['ConnectedDeviceID'];
			$this->ConnectedPort=$row['ConnectedPort'];
			$this->Notes=$row['Notes'];

			$this->MakeDisplay();

			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Ports WHERE DeviceID=$this->DeviceID;";

		$ports=array();
		foreach($dbh->query($sql) as $row){
			$ports[]=DevicePorts::RowToObject($row);
		}	
		return $ports;
	}	

	function createPort() {
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_Ports SET DeviceID=$this->DeviceID, PortNumber=$this->PortNumber, 
			Label=\"$this->Label\", MediaID=$this->MediaID, ColorID=$this->ColorID, 
			PortNotes=\"$this->PortNotes\", ConnectedDeviceID=$this->ConnectedDeviceID, 
			ConnectedPort=$this->ConnectedPort, Notes=\"$this->Notes\";";
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		return true;
	}

	function updatePort() {
		global $dbh;

		$oldport=new DevicePorts(); // originating port prior to modification
		$oldport->DeviceID=$this->DeviceID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();
		$tmpport=new DevicePorts(); // connecting to here
		$tmpport->DeviceID=$this->ConnectedDeviceID;
		$tmpport->PortNumber=$this->ConnectedPort;
		$tmpport->getPort();
	
		$this->MakeSafe();

		// clear previous connection
		$oldport->removeConnection();
		$tmpport->removeConnection();

		if($this->ConnectedDeviceID==0 || $this->PortNumber==0 || $this->ConnectedPort==0){
			// when any of the above equal 0 this is a delete request
			// skip making any new connections but go ahead and update the device
		}else{
			// make new connection
			$tmpport->ConnectedDeviceID=$this->DeviceID;
			$tmpport->ConnectedPort=$this->PortNumber;
			$tmpport->Notes=$this->Notes;
			DevicePorts::makeConnection($tmpport,$this);
		}

		// update port
		$sql="UPDATE fac_Ports SET MediaID=$this->MediaID, ColorID=$this->ColorID, 
			PortNotes=\"$this->PortNotes\", ConnectedDeviceID=$this->ConnectedDeviceID, 
			ConnectedPort=$this->ConnectedPort, Notes=\"$this->Notes\" WHERE 
			DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			
			return false;
		}

		return true;
	}

	static function makeConnection($port1,$port2){
		global $dbh;

		$port1->MakeSafe();
		$port2->MakeSafe();

		$sql="UPDATE fac_Ports SET ConnectedDeviceID=$port2->DeviceID, 
			ConnectedPort=$port2->PortNumber, Notes=\"$port2->Notes\" WHERE 
			DeviceID=$port1->DeviceID AND PortNumber=$port1->PortNumber; UPDATE fac_Ports 
			SET ConnectedDeviceID=$port1->DeviceID, ConnectedPort=$port1->PortNumber, 
			Notes=\"$port1->Notes\" WHERE DeviceID=$port2->DeviceID AND 
			PortNumber=$port2->PortNumber;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		return true;
	}

	function removeConnection(){
		global $dbh;

		$this->getPort();

		$sql="UPDATE fac_Ports SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE
			(DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber) OR 
			(ConnectedDeviceID=$this->DeviceID AND ConnectedPort=$this->PortNumber);";

		/* not sure the best way to catch these errors this should modify 2 lines
		   per run. */
		try{
			$dbh->exec($sql);
		}catch(PDOException $e){
			echo $e->getMessage();
			die();
		}

		return true;
	}

	function removePort(){
		/*	Remove a single port from a device */
		global $dbh;

		if(!$this->getport()){
			return false;
		}

		$this->removeConnection();

		$sql="DELETE FROM fac_Ports WHERE DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			//delete failed, wtf
			return false;
		}else{
			return true;
		}		
	}

// these next two should probably be moved to the device object.

	static function removeConnections($DeviceID){
		/* Drop all network connections on a device */
		global $dbh;

		$dev=new Device(); // make sure we have a real device first
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		$sql="UPDATE fac_Ports SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE
			DeviceID=$dev->DeviceID OR ConnectedDeviceID=$dev->DeviceID;";

		$dbh->exec($sql); // don't need to log if this fails

		return true;
	}

	function removePorts(){
		/*	Remove all ports from a device prior to delete, etc */
		global $dbh;

		$sql="UPDATE fac_Ports SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE
            ConnectedDeviceID=$this->DeviceID;
			DELETE FROM fac_Ports WHERE DeviceID=$this->DeviceID;";

		try{
			$dbh->exec($sql);
		}catch(PDOException $e){
			echo $e->getMessage();
			die();
		}

		return true;
	}


	static function getPatchCandidates($DeviceID,$PortNum=null,$listports=null,$patchpanels=null){
		/*
		 * $DeviceID = ID of the device that you are wanting to make a connection from
		 * $PortNum(optional) = Port Number on the device you are wanting to connect,
		 *		mandatory if media enforcing is on
		 * $listports(optional) = Any value will trigger this to kick back a list of
		 * 		valid points that this port can connect to instead of the default list
		 *		of valid devices that it can connect to.
		 */
		global $dbh;
		global $config;

		$dev=new Device(); // make sure we have a real device first
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		$mediaenforce="";
		if($config->ParameterArray["MediaEnforce"]=='enabled' && !is_null($PortNum)){
			$dp=new DevicePorts();
			$dp->DeviceID=$DeviceID;
			$dp->PortNumber=$PortNum;
			$dp->getPort();
			$mt=new MediaTypes();
			$mt->MediaID=$dp->MediaID;
			$mt->GetType();

			$mediaenforce=" AND MediaID=$mt->MediaID";
		}elseif($config->ParameterArray["MediaEnforce"]=='enabled' && is_null($PortNum)){
			// Media Type Enforcing is enabled and you didn't supply a port to match type on
			return false;
		}

		$pp="";
		if(!is_null($patchpanels)){
			$pp=' AND b.DeviceType="Patch Panel"';
		}
		$candidates=array();

		if(is_null($listports)){
			$sql="SELECT DISTINCT a.DeviceID FROM fac_Ports a, fac_Device b WHERE a.DeviceID=b.DeviceID AND a.DeviceID!=$dev->DeviceID$mediaenforce$pp;";
			foreach($dbh->query($sql) as $row){
				$candidate=$row['DeviceID'];
				$tmpDev=new Device();
				$tmpDev->DeviceID=$candidate;
				$tmpDev->GetDevice();

				$candidates[$candidate]=$tmpDev;
			}
		}else{
			$sql="SELECT a.* FROM fac_Ports a, fac_Device b WHERE a.DeviceID=b.DeviceID AND a.DeviceID!=$dev->DeviceID AND ConnectedDeviceID IS NULL$mediaenforce$pp;";
			foreach($dbh->query($sql) as $row){
				$candidates[]=DevicePorts::RowToObject($row);
			}
		}

		return $candidates;
	}

	static function getPortList($DeviceID){
		global $dbh;
		
		if(intval($DeviceID) <1){
			return false;
		}
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		if(!$dev->GetDevice()){
			return false;	// This device doesn't exist
		}
		
		$sql="SELECT * FROM fac_Ports WHERE DeviceID=$dev->DeviceID;";
		
		$portList = array();
		
		foreach($dbh->query($sql) as $row){
			$portList[$row['PortNumber']]=DevicePorts::RowToObject($row);
		}
		
		if( sizeof($portList)==0 && $dev->DeviceType!="Physical Infrastructure" ){
			// Build the DevicePorts from the existing info in the following priority:
			//  - Existing switchconnection table
			//  - SNMP data (if it exists)
			//  - Placeholders
			if($dev->DeviceType=="Switch"){
				$swCon=new SwitchConnection();
				$swCon->SwitchDeviceID=$dev->DeviceID;
				
				$nameList=SwitchInfo::getPortNames($dev->DeviceID);
				$aliasList=SwitchInfo::getPortAlias($dev->DeviceID);
				
				for( $n=0; $n<$dev->Ports; $n++ ){
					$portList[$n]=new DevicePorts();
					$portList[$n]->DeviceID=$dev->DeviceID;
					$portList[$n]->PortNumber=$n+1;
					$portList[$n]->Label=@$nameList[$n];

					$swCon->SwitchPortNumber=$n+1;
					if($swCon->GetConnectionRecord()){
						$portList[$n]->Notes=$swCon->Notes;
					}else{
						$portList[$n]->Notes=$aliasList[$n];
					}

					$portList[$n]->CreatePort();
				}
			}else{
				for( $n=0; $n<$dev->Ports; $n++ ){
					$portList[$n]=new DevicePorts();
					$portList[$n]->DeviceID=$dev->DeviceID;
					$portList[$n]->PortNumber=$n+1;
					$portList[$n]->Label=@$nameList[$n];

					$portList[$n]->CreatePort();
				}
			}
		}
		
		return $portList;
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
  
	static function ESXRowToObject($dbRow){
		/*
		 * Generic function that will take any row returned from the fac_VMInventory
		 * table and convert it to an object for use in array or other
		 */

		$vm=new ESX();
		$vm->VMIndex=$dbRow["VMIndex"];
		$vm->DeviceID=$dbRow["DeviceID"];
		$vm->LastUpdated=$dbRow["LastUpdated"];
		$vm->vmID=$dbRow["vmID"];
		$vm->vmName=$dbRow["vmName"];
		$vm->vmState=$dbRow["vmState"];
		$vm->Owner=$dbRow["Owner"];

		return $vm;
	}

  function EnumerateVMs($dev,$debug=false){
    $community=$dev->SNMPCommunity;
    $serverIP=$dev->PrimaryIP;

    $vmList=array();

    $pollCommand="/usr/bin/snmpwalk -v 2c -c $community $serverIP .1.3.6.1.4.1.6876.2.1.1.2 | /bin/cut -d: -f4 | /bin/cut -d\\\" -f2";
    exec($pollCommand,$namesOutput);

    $pollCommand="/usr/bin/snmpwalk -v 2c -c $community $serverIP .1.3.6.1.4.1.6876.2.1.1.6 | /bin/cut -d: -f4 | /bin/cut -d\\\" -f2";
    exec($pollCommand,$statesOutput);

    if(count($namesOutput)==count($statesOutput)&&count($namesOutput)>0){
      $tempVMs=array_combine($namesOutput,$statesOutput);
    }else{
      $tempVMs=array();
	}

    $vmID=0;

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
  
  function UpdateInventory( $db, $debug=false ) {
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
		if($result=mysql_query($searchSQL,$db)){
			$vmRow=mysql_fetch_array($result);

			$this->DeviceID=$vmRow["DeviceID"];
			$this->LastUpdated=$vmRow["LastUpdated"];
			$this->vmID=$vmRow["vmID"];
			$this->vmName=$vmRow["vmName"];
			$this->vmState=$vmRow["vmState"];
			$this->Owner=$vmRow["Owner"];
		}

		return;
	}
  
	function UpdateVMOwner( $db ) {
		$updateSQL = "update fac_VMInventory set Owner=\"" . $this->Owner . "\" where VMIndex=\"" . $this->VMIndex . "\"";
		$result = mysql_query( $updateSQL, $db );
	} 
  
	function GetInventory( $db=null ) {
		$selectSQL = "select * from fac_VMInventory order by DeviceID, vmName";
		$result = mysql_query($selectSQL);
    
		$vmList = array();
		$vmCount = 0;
  
		while($vmRow=mysql_fetch_array($result)){
			$vmList[$vmCount]=ESX::ESXRowToObject($vmRow);
			$vmCount++;
		}
    
		return $vmList; 
	}
  
	function GetDeviceInventory( $db ) {
		$selectSQL = "select * from fac_VMInventory where DeviceID=\"" . $this->DeviceID . "\" order by vmName";
		$result = mysql_query( $selectSQL, $db );
    
		$vmList = array();
		$vmCount = 0;
  
		while($vmRow=mysql_fetch_array($result)){
			$vmList[$vmCount]=ESX::ESXRowToObject($vmRow);
			$vmCount++;
		}
   
		return $vmList; 
	}
  
	function GetVMListbyOwner( $db ) {
		$selectSQL = "select * from fac_VMInventory where Owner=\"" . $this->Owner . "\" order by DeviceID, vmName";
		$result = mysql_query( $selectSQL, $db );
    
		$vmList = array();
		$vmCount = 0;
  
		while($vmRow=mysql_fetch_array($result)){
			$vmList[$vmCount]=ESX::ESXRowToObject($vmRow);
			$vmCount++;
		}
   
		return $vmList; 
	}
  
	function SearchByVMName( $db ) {
		$selectSQL = "select * from fac_VMInventory where ucase(vmName) like \"%" . transform($this->vmName) . "%\"";
		$result = mysql_query( $selectSQL, $db );
    
		$vmList = array();
		$vmCount = 0;
  
		while($vmRow=mysql_fetch_array($result)){
			$vmList[$vmCount]=ESX::ESXRowToObject($vmRow);
			$vmCount++;
		}

		return $vmList; 
	}
  
	function GetOrphanVMList( $db ) {
		$selectSQL = "select * from fac_VMInventory where Owner is NULL"; 
		$result = mysql_query( $selectSQL, $db );
    
		$vmList = array();
		$vmCount = 0;
  
		while($vmRow=mysql_fetch_array($result)){
			$vmList[$vmCount]=ESX::ESXRowToObject($vmRow);
			$vmCount++;
		}

		return $vmList; 
	}

	function GetExpiredVMList( $numDays, $db ) {
		$selectSQL = "select * from fac_VMInventory where to_days(now())-to_days(LastUpdated)>$numDays"; 
		$result = mysql_query( $selectSQL, $db );
    
		$vmList = array();
		$vmCount = 0;
  
		while($vmRow=mysql_fetch_array($result)){
			$vmList[$vmCount]=ESX::ESXRowToObject($vmRow);
			$vmCount++;
		}

		return $vmList; 
	}
  
	function ExpireVMs( $numDays, $db ) {
		// Don't allow calls to expire EVERYTHING
		if($numDays >0){
			$selectSQL="delete from fac_VMInventory where to_days(now())-to_days(LastUpdated)>$numDays";
			$result=mysql_query($selectSQL,$db);
		}
	}

}

class MediaTypes {
	var $MediaID;
	var $MediaType;
	var $ColorID;
	
	function CreateType() {
		global $dbh;
		
		$sql="INSERT INTO fac_MediaTypes SET MediaType=\"".addslashes($this->MediaType)."\", 
			ColorID=".intval($this->ColorID);
			
		if($dbh->exec($sql)){
			$this->MediaID=$dbh->lastInsertId();
		}else{
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]}");
			return false;
		}
		
		return $this->MediaID;
	}
	
	function UpdateType() {
		global $dbh;
		
		$sql="UPDATE fac_MediaTypes SET MediaType=\"".addslashes($this->MediaType)."\", 
			ColorID=".intval($this->ColorID)." WHERE MediaID=".intval($this->MediaID);
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]}");
			return false;
		}else{		
			return true;
		}
	}
	
	function DeleteType() {
		/* It is up to the calling application to check to make sure that orphans are not being created! */
		
		global $dbh;
		
		$sql="DELETE FROM fac_MediaTypes WHERE MediaID=".intval($this->MediaID);
		
		return $dbh->exec( $sql );
	}
	
	function GetType() {
		global $dbh;
		
		$sql="SELECT * FROM fac_MediaTypes WHERE MediaID=".intval($this->MediaID);
		
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			$this->MediaType = $row["MediaType"];
			$this->ColorID = $row["ColorID"];
			
			return true;
		}
	}
	
	static function GetMediaTypeList() {
		global $dbh;
		
		$sql = "SELECT * FROM fac_MediaTypes ORDER BY MediaType ASC";
		
		$mediaList = array();
	
		foreach ( $dbh->query( $sql ) as $row ) {
			$n=$row["MediaID"];
			$mediaList[$n] = new MediaTypes();
			$mediaList[$n]->MediaID = $row["MediaID"];
			$mediaList[$n]->MediaType = $row["MediaType"];
			$mediaList[$n]->ColorID = $row["ColorID"];
		}
		
		return $mediaList;
	}

	static function ResetType($mediaid,$tomediaid=0){
	/*
	 * This probably shouldn't be a function here since it will only be used in one
	 * place. This function will remove a color code from any device ports or will
	 * set it to another via an optional second color id
	 *
	 */
		global $dbh;
		$mediaid=intval($mediaid);
		$tomediaid=intval($tomediaid); // it will always be 0 unless otherwise set

		$sql="UPDATE fac_DevicePorts SET MediaID='$tomediaid' WHERE MediaID='$mediaid';";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]}");
			return false;
		}else{		
			return true;
		}
	}

	static function TimesUsed($mediaid){
		global $dbh;

		$count=$dbh->prepare('SELECT * FROM fac_DevicePorts WHERE MediaID='.intval($mediaid));
		$count->execute();

		return $count->rowCount();
	}
}

class PatchConnection {
	/* PatchConnection:	Self explanatory - any device set as a patch will allow you to map out the port connections to
							any other device within the same data center.  For trans-data center connections, you can map the
							port back to itself, and list the external source in the Notes field.
	*/
	
	var $PanelDeviceID;
	var $PanelPortNumber;
	var $FrontEndpointDeviceID;
	var $FrontEndpointPort;
	var $RearEndpointDeviceID;
	var $RearEndpointPort;
	var $FrontNotes;
	var $RearNotes;
	
	function GetConnectionRecord($db){
		$this->MakeSafe();
		$sql="select * from fac_PatchConnection where PanelDeviceID=$this->PanelDeviceID and PanelPortNumber=$this->PanelPortNumber";
	
		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}
		
		if($row=mysql_fetch_array($result)){
			$this->FrontEndpointDeviceID=$row["FrontEndpointDeviceID"];
			$this->FrontEndpointPort=$row["FrontEndpointPort"];
			$this->RearEndpointDeviceID=$row["RearEndpointDeviceID"];
			$this->RearEndpointPort=$row["RearEndpointPort"];
			$this->FrontNotes=$row["FrontNotes"];
			$this->RearNotes=$row["RearNotes"];
		}
		
		return 1;		
	}
	
	function MakeFrontConnection($db,$recursive=true){
		$this->MakeSafe();

		$tmpDev=new Device();
		$tmpDev->DeviceID = $this->PanelDeviceID;
		$tmpDev->GetDevice( $db );
		
		// If you pass a port number lower than 1, or higher than the total number of ports defined for the patch panel, then bounce
		if ( $this->PanelPortNumber < 1 || $this->PanelPortNumber > $tmpDev->Ports )
			return -1;
			
		$sql="INSERT INTO fac_PatchConnection VALUES ($this->PanelDeviceID, $this->PanelPortNumber, $this->FrontEndpointDeviceID, $this->FrontEndpointPort, NULL, NULL, \"$this->FrontNotes\", NULL ) ON DUPLICATE KEY UPDATE FrontEndpointDeviceID=$this->FrontEndpointDeviceID,FrontEndpointPort=$this->FrontEndpointPort,FrontNotes=\"$this->FrontNotes\";";

		if ( ! $result = mysql_query( $sql, $db) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		$tmpDev->DeviceID=$this->FrontEndpointDeviceID;
		$tmpDev->GetDevice($db);
		
		if($recursive && $tmpDev->DeviceType=="Switch"){
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->FrontEndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->FrontEndpointPort;
			$tmpSw->EndpointDeviceID = $this->PanelDeviceID;
			$tmpSw->EndpointPort = $this->PanelPortNumber;
			$tmpSw->Notes = $this->FrontNotes;
			
			// Remove any existing connection from this port
			$tmpSw->RemoveConnection( $db );
			// Call yourself, but with the recursive = false so that you don't create a loop
			$tmpSw->CreateConnection( $db, false );
		}

		if ( $recursive && $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPanel = new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->FrontEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->FrontEndpointPort;
			$tmpPanel->FrontEndpointDeviceID = $this->PanelDeviceID;
			$tmpPanel->FrontEndpointPort = $this->PanelPortNumber;
			$tmpPanel->FrontNotes = $this->FrontNotes;
			$tmpPanel->MakeFrontConnection( $db, false );
		}
		
		$this->GetConnectionRecord($db); // reload the object from the DB
		return 1;
	}
	
	function MakeRearConnection($db,$recursive=true){
		$this->MakeSafe();
		
		$tmpDev=new Device();
		$tmpDev->DeviceID = $this->PanelDeviceID;
		$tmpDev->GetDevice( $db );
		
		// If you pass a port number lower than 1, or higher than the total number of ports defined for the patch panel, then bounce
		if ( $this->PanelPortNumber < 1 || $this->PanelPortNumber > $tmpDev->Ports )
			return -1;
		
		$sql="INSERT INTO fac_PatchConnection VALUES ($this->PanelDeviceID, $this->PanelPortNumber, NULL, NULL, $this->RearEndpointDeviceID, $this->RearEndpointPort, NULL, \"$this->RearNotes\" ) ON DUPLICATE KEY UPDATE RearEndpointDeviceID=$this->RearEndpointDeviceID,RearEndpointPort=$this->RearEndpointPort,RearNotes=\"$this->RearNotes\";";
		if ( ! $result = mysql_query( $sql, $db) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		$tmpDev->DeviceID = $this->RearEndpointDeviceID;
		$tmpDev->GetDevice( $db );
		
		// Patch Panel rear connections will only go to circuits or other patch panels
		// So there is no need to test for a switch like with the front side
		if ( $recursive && $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPanel = new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->RearEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->RearEndpointPort;
			$tmpPanel->RearEndpointDeviceID = $this->PanelDeviceID;
			$tmpPanel->RearEndpointPort = $this->PanelPortNumber;
			$tmpPanel->RearNotes = $this->RearNotes;
			$tmpPanel->MakeRearConnection( $db, false );
		}
		
		$this->GetConnectionRecord($db); // reload the object from the DB
		return 1;
	}
	
	function RemoveFrontConnection($db,$recursive=true){
		$this->GetConnectionRecord($db); // just pulled data from db both variables are int already, no need to sanitize again
		$sql="UPDATE fac_PatchConnection SET FrontEndpointDeviceID=NULL, FrontEndpointPort=NULL, FrontNotes=NULL WHERE PanelDeviceID=$this->PanelDeviceID AND PanelPortNumber=$this->PanelPortNumber;";

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}
		
		// Check the endpoint of the front connection in case it has a reciprocal connection
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->FrontEndpointDeviceID;
		$tmpDev->GetDevice( $db );
		
		if ( $recursive && $tmpDev->DeviceType == "Switch" ) {
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->FrontEndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->FrontEndpointPort;
			$tmpSw->RemoveConnection( $db );	
		}
		
		// Patch panel connections can go front to front, or rear to rear, but never front to rear
		// So since this is a front connection removal, you only need to remove the front connection
		// at the opposite end
		if ( $recursive && $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPanel = new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->FrontEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->FrontEndpointPort;
			$tmpPanel->RemoveFrontConnection( $db, false );
		}
		$this->GetConnectionRecord($db); // reload the object from the DB
		return 1;
	}
	
	function RemoveRearConnection($db,$recursive=true){
		$this->GetConnectionRecord($db); // just pulled data from db both variables are int already, no need to sanitize again
		$sql="UPDATE fac_PatchConnection SET RearEndpointDeviceID=NULL, RearEndpointPort=NULL, RearNotes=NULL WHERE PanelDeviceID=$this->PanelDeviceID AND PanelPortNumber=$this->PanelPortNumber;";

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}
		
		// Check the endpoint of the front connection in case it has a reciprocal connection
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->RearEndpointDeviceID;
		$tmpDev->GetDevice( $db );
		// Patch panel rear connections can only go to either
		//		(a) Another patch panel (rear)
		//		(b) A circuit ID - in which case the DeviceID is 0, but the notes has the circuit ID
		
		// Patch panel connections can go front to front, or rear to rear, but never front to rear
		// So since this is a front connection removal, you only need to remove the front connection
		// at the opposite end
		if($recursive && $tmpDev->DeviceType == "Patch Panel"){
			$tmpPanel=new PatchConnection();
			$tmpPanel->PanelDeviceID = $this->RearEndpointDeviceID;
			$tmpPanel->PanelPortNumber = $this->RearEndpointPort;
			$tmpPanel->RemoveRearConnection($db, false);
		}
		
		return 1;
	}
	
	function DropEndpointConnections() {
		global $dbh;

		// You call this when deleting an endpoint device, other than a patch panel
		$this->MakeSafe();

		$sql="UPDATE fac_PatchConnection SET FrontEndpointDeviceID=NULL, 
			FrontEndpointPort=NULL, FrontNotes=NULL WHERE 
			FrontEndpointDeviceID=$this->FrontEndpointDeviceID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DropEndpointConnections::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}else{
			return true;
		}
	}
	
	function DropPanelConnections() {
		global $dbh;

		// You only call this when you are deleting another patch panel
		$this->MakeSafe();
		$sql="UPDATE fac_PatchConnection SET RearEndpointDeviceID=NULL, 
			RearEndpointPort=NULL, RearNotes=NULL WHERE 
			FrontEndpointDeviceID=$this->FrontEndpointDeviceID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DropPanelConnections::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}
		
		// Delete any records for this panel itself
		$sql="DELETE FROM fac_PatchConnection WHERE PanelDeviceID=$this->PanelDeviceID;";
		
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("DropPanelConnections::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}else{
			return true;
		}
	}
	
	function GetPanelConnections($db){
		$this->MakeSafe();
		$sql="SELECT * FROM fac_PatchConnection WHERE PanelDeviceID=$this->PanelDeviceID ORDER BY PanelPortNumber;";
		$result=mysql_query($sql,$db);
		
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->PanelDeviceID;
		$tmpDev->GetDevice( $db );
		$connList=array();
		
		for ( $i = 1; $i <= $tmpDev->Ports; $i++ ) {
			$connList[$i] = new PatchConnection();
			$connList[$i]->PanelDeviceID = $tmpDev->DeviceID;
			$connList[$i]->PanelPortNumber = $i;
		}      
		
		while($connRow=mysql_fetch_array($result)){
			$connNum=$connRow["PanelPortNumber"];
			$connList[$connNum]->PanelDeviceID=$connRow["PanelDeviceID"];
			$connList[$connNum]->PanelPortNumber=$connRow["PanelPortNumber"];
			$connList[$connNum]->FrontEndpointDeviceID=$connRow["FrontEndpointDeviceID"];
			$connList[$connNum]->FrontEndpointPort=$connRow["FrontEndpointPort"];
			$connList[$connNum]->RearEndpointDeviceID=$connRow["RearEndpointDeviceID"];
			$connList[$connNum]->RearEndpointPort=$connRow["RearEndpointPort"];
			$connList[$connNum]->FrontNotes=$connRow["FrontNotes"];
			$connList[$connNum]->RearNotes=$connRow["RearNotes"];
		}
		
		return $connList;
	}
	
	function GetEndpointConnections($db){
		$this->MakeSafe();
		$sql="SELECT * FROM fac_PatchConnection WHERE FrontEndpointDeviceID=$this->FrontEndpointDeviceID ORDER BY PanelDeviceID ASC;";
		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}
		
		$patchList=array();
		while($row=mysql_fetch_array($result)){
			$pNum=sizeof($patchList);
			$patchList[$pNum]=new PatchConnection();
			$patchList[$pNum]->PanelDeviceID=$row["PanelDeviceID"];
			$patchList[$pNum]->PanelPortNumber=$row["PanelPortNumber"];
			$patchList[$pNum]->FrontEndpointDeviceID=$row["FrontEndpointDeviceID"];
			$patchList[$pNum]->FrontEndpointPort=$row["FrontEndpointPort"];
			$patchList[$pNum]->RearEndpointDeviceID=$row["RearEndpointDeviceID"];
			$patchList[$pNum]->RearEndpointPort=$row["RearEndpointPort"];
			$patchList[$pNum]->FrontNotes=$row["FrontNotes"];
			$patchList[$pNum]->RearNotes=$row["RearNotes"];
		}
		
		return $patchList;
	}

	function MakeSafe(){
		// mysql needed the word NULL for the fields that were null to keep the sql valid
		$this->PanelDeviceID=intval($this->PanelDeviceID);
		$this->PanelPortNumber=intval($this->PanelPortNumber);
		$this->FrontEndpointDeviceID=(is_null($this->FrontEndpointDeviceID))?'NULL':intval($this->FrontEndpointDeviceID);
		$this->FrontEndpointPort=(is_null($this->FrontEndpointPort))?'NULL':intval($this->FrontEndpointPort);
		$this->FrontNotes=(is_null($this->FrontNotes))?'NULL':mysql_real_escape_string($this->FrontNotes);
		$this->RearEndpointDeviceID=(is_null($this->RearEndpointDeviceID))?'NULL':intval($this->RearEndpointDeviceID);
		$this->RearEndpointPort=(is_null($this->RearEndpointPort))?'NULL':intval($this->RearEndpointPort);
		$this->RearNotes=(is_null($this->RearNotes))?'NULL':mysql_real_escape_string($this->RearNotes);
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
  
  function CreateRequest($db=null){
	global $dbh;
    $sql="INSERT INTO fac_RackRequest SET RequestTime=now(), RequestorID=\"".intval($this->RequestorID)."\",
		Label=\"".addslashes(transform($this->Label))."\", SerialNo=\"".addslashes(transform($this->SerialNo))."\",
		MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
		AssetTag=\"".addslashes(transform($this->AssetTag))."\", ESX=\"".intval($this->ESX)."\",
		Owner=\"".intval($this->Owner)."\", DeviceHeight=\"".intval($this->DeviceHeight)."\",
		EthernetCount=\"".intval($this->EthernetCount)."\", VLANList=\"".addslashes($this->VLANList)."\",
		SANCount=\"".intval($this->SANCount)."\", SANList=\"".addslashes($this->SANList)."\",
		DeviceClass=\"".addslashes($this->DeviceClass)."\", DeviceType=\"".addslashes($this->DeviceType)."\",
		LabelColor=\"".addslashes($this->LabelColor)."\", 
		CurrentLocation=\"".addslashes(transform($this->CurrentLocation))."\",
		SpecialInstructions=\"".addslashes($this->SpecialInstructions)."\"";
    
	if(!$dbh->exec($sql)){
		$info=$dbh->errorInfo();
		error_log("PDO Error: {$info[2]}");
		return false;
	}else{		
		$this->RequestID=$dbh->lastInsertId();
        return $this->RequestID;
	}
  }
  
  function GetOpenRequests( $db ) {
	global $dbh;
    $sql="SELECT * FROM fac_RackRequest WHERE CompleteTime='0000-00-00 00:00:00'";
    
    $requestList=array();
	foreach($dbh->query($sql) as $row){ 
		$requestNum=sizeof($requestList);

		$requestList[$requestNum]=new RackRequest();
		$requestList[$requestNum]->RequestID=$row["RequestID"];
		$requestList[$requestNum]->RequestorID=$row["RequestorID"];
		$requestList[$requestNum]->RequestTime=$row["RequestTime"];
		$requestList[$requestNum]->CompleteTime=$row["CompleteTime"];
		$requestList[$requestNum]->Label=$row["Label"];
		$requestList[$requestNum]->SerialNo=$row["SerialNo"];
		$requestList[$requestNum]->AssetTag=$row["AssetTag"];
		$requestList[$requestNum]->ESX=$row["ESX"];
		$requestList[$requestNum]->Owner=$row["Owner"];
		$requestList[$requestNum]->DeviceHeight=$row["DeviceHeight"];
		$requestList[$requestNum]->EthernetCount=$row["EthernetCount"];
		$requestList[$requestNum]->VLANList=$row["VLANList"];
		$requestList[$requestNum]->SANCount=$row["SANCount"];
		$requestList[$requestNum]->SANList=$row["SANList"];
		$requestList[$requestNum]->DeviceClass=$row["DeviceClass"];
		$requestList[$requestNum]->DeviceType=$row["DeviceType"];
		$requestList[$requestNum]->LabelColor=$row["LabelColor"];
		$requestList[$requestNum]->CurrentLocation=$row["CurrentLocation"];
		$requestList[$requestNum]->SpecialInstructions=$row["SpecialInstructions"];
    }
    
    return $requestList;
  }
  
  function GetRequest($db=null){
	global $dbh;
    $sql="SELECT * FROM fac_RackRequest WHERE RequestID=\"".intval($this->RequestID)."\";";

	if($row=$dbh->query($sql)->fetch()){
		$this->RequestorID=$row["RequestorID"];
		$this->RequestTime=$row["RequestTime"];
		$this->CompleteTime=$row["CompleteTime"];
		$this->Label=$row["Label"];
		$this->SerialNo=$row["SerialNo"];
		$this->MfgDate=$row["MfgDate"];
		$this->AssetTag=$row["AssetTag"];
		$this->ESX=$row["ESX"];
		$this->Owner=$row["Owner"];
		$this->DeviceHeight=$row["DeviceHeight"];
		$this->EthernetCount=$row["EthernetCount"];
		$this->VLANList=$row["VLANList"];
		$this->SANCount=$row["SANCount"];
		$this->SANList=$row["SANList"];
		$this->DeviceClass=$row["DeviceClass"];
		$this->DeviceType=$row["DeviceType"];
		$this->LabelColor=$row["LabelColor"];
		$this->CurrentLocation=$row["CurrentLocation"];
		$this->SpecialInstructions=$row["SpecialInstructions"];
	}else{
		//something bad happened maybe tell someone
	}
  }
  
  function CompleteRequest($db=null){
	global $dbh;
    $sql="UPDATE fac_RackRequest SET CompleteTime=now() WHERE RequestID=\"".$this->RequestID."\";";
	if($dbh->query($sql)){
		return true;
	}else{
		return false;
	}
  }
  
  function DeleteRequest($db=null){
	global $dbh;
    $sql="DELETE FROM fac_RackRequest WHERE RequestID=\"".intval($this->RequestID)."\";";
	if($dbh->query($sql)){
		return true;
	}else{
		return false;
	}
  }

  function UpdateRequest($db=null){
	global $dbh;
    $sql="UPDATE fac_RackRequest SET RequestTime=now(), RequestorID=\"".intval($this->RequestorID)."\",
		Label=\"".addslashes(transform($this->Label))."\", SerialNo=\"".addslashes(transform($this->SerialNo))."\",
		MfgDate=\"".date("Y-m-d", strtotime($this->MfgDate))."\", 
		AssetTag=\"".addslashes(transform($this->AssetTag))."\", ESX=\"".intval($this->ESX)."\",
		Owner=\"".intval($this->Owner)."\", DeviceHeight=\"".intval($this->DeviceHeight)."\",
		EthernetCount=\"".intval($this->EthernetCount)."\", VLANList=\"".addslashes($this->VLANList)."\",
		SANCount=\"".intval($this->SANCount)."\", SANList=\"".addslashes($this->SANList)."\",
		DeviceClass=\"".addslashes($this->DeviceClass)."\", DeviceType=\"".addslashes($this->DeviceType)."\",
		LabelColor=\"".addslashes($this->LabelColor)."\", 
		CurrentLocation=\"".addslashes(transform($this->CurrentLocation))."\",
		SpecialInstructions=\"".addslashes($this->SpecialInstructions)."\" 
		WHERE RequestID=\"".intval($this->RequestID)."\";";
    
	if($dbh->query($sql)){
		return true;
	}else{
		return false;
	}
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

	function MakeSafe(){
		$this->SwitchDeviceID=intval($this->SwitchDeviceID);
		$this->SwitchPortNumber=intval($this->SwitchPortNumber);
		$this->EndpointDeviceID=intval($this->EndpointDeviceID);
		$this->EndpointPort=intval($this->EndpointPort);
		$this->Notes=addslashes(trim($this->Notes));
	}

	function CreateConnection( $db, $recursive = true ) {
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INFO fac_SwitchConnection SET SwitchDeviceID=$this->SwitchDeviceID, 
			SwitchPortNumber=$this->SwitchPortNumber, 
			EndpointDeviceID=$this->EndpointDeviceID, 
			EndpointPort=$this->EndpointPort, Notes=\"$this->Notes\";"; 

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("CreateConnection::PDO Error: {$info[2]} SQL=$sql");
			return -1;
		}

		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->EndpointDeviceID;
		$tmpDev->GetDevice();
		
		if ( $recursive && $tmpDev->DeviceType == "Switch" ) {
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->EndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->EndpointPort;
			$tmpSw->EndpointDeviceID = $this->SwitchDeviceID;
			$tmpSw->EndpointPort = $this->SwitchPortNumber;
			$tmpSw->Notes = $this->Notes;
			
			// Remove any existing connection from this port
			$tmpSw->RemoveConnection( $db );
			// Call yourself, but with the recursive = false so that you don't create a loop
			$tmpSw->CreateConnection( $db, false );
		}

		if ( $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPan = new PatchConnection();
			$tmpPan->PanelDeviceID = $this->EndpointDeviceID;
			$tmpPan->PanelPortNumber = $this->EndpointPort;
			$tmpPan->FrontEndpointDeviceID = $this->SwitchDeviceID;
			$tmpPan->FrontEndpointPort = $this->SwitchPortNumber;
			$tmpPan->FrontNotes = $this->Notes;
			$tmpPan->MakeFrontConnection( $db, false );
		}
		
		return 1;
	}
  
	function UpdateConnection( $db ) {
		$sql = "update fac_SwitchConnection set EndpointDeviceID=\"" . intval( $this->EndpointDeviceID ) . "\", EndpointPort=\"" . intval( $this->EndpointPort ) . "\", Notes=\"" . addslashes( $this->Notes ) . "\" where SwitchDeviceID=\"" . intval( $this->SwitchDeviceID ) . "\" and SwitchPortNumber=\"" . intval( $this->SwitchPortNumber ) . "\"";

		mysql_query( $sql, $db );

		$tmpDev = new Device();
		$tmpDev->DeviceID = intval($this->EndpointDeviceID);
		$tmpDev->GetDevice( $db );
		
		if ( $tmpDev->DeviceType == "Switch" ) {
			$tmpSw = new SwitchConnection();
			$tmpSw->SwitchDeviceID = $this->EndpointDeviceID;
			$tmpSw->SwitchPortNumber = $this->EndpointPort;
			$tmpSw->EndpointDeviceID = $this->SwitchDeviceID;
			$tmpSw->EndpointPort = $this->SwitchPortNumber;
			$tmpSw->Notes = $this->Notes;
			
			// Remove any existing connection from this port
			$tmpSw->RemoveConnection( $db );
			// Call yourself, but with the recursive = false so that you don't create a loop
			$tmpSw->CreateConnection( $db, false );
		}

		if ( $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPan = new PatchConnection();
			$tmpPan->PanelDeviceID = $this->EndpointDeviceID;
			$tmpPan->PanelPortNumber = $this->EndpointPort;
			$tmpPan->FrontEndpointDeviceID = $this->SwitchDeviceID;
			$tmpPan->FrontEndpointPort = $this->SwitchPortNumber;
			$tmpPan->FrontNotes = $this->Notes;
			$tmpPan->MakeFrontConnection( $db, false );
		}
	}
	
	function GetConnectionRecord( $db = null ) {
		global $dbh;
		
		$sql = sprintf( "select * from fac_SwitchConnection where SwitchDeviceID=%d and SwitchPortNumber=%d", intval( $this->SwitchDeviceID), intval( $this->SwitchPortNumber ) );
			
		if ( ! $row = $dbh->query( $sql )->fetch() ) {
			return false;
		}

		$this->EndpointDeviceID = $row["EndpointDeviceID"];
		$this->EndpointPort = $row["EndpointPort"];
		$this->Notes = $row["Notes"];
		
		return;	
	}
    
	function RemoveConnection($db, $recursive=false ) {
		$this->GetConnectionRecord( $db );

		$delSQL = "delete from fac_SwitchConnection where SwitchDeviceID=\"" . $this->SwitchDeviceID . "\" and SwitchPortNumber=\"" . $this->SwitchPortNumber . "\"";

		$result = mysql_query( $delSQL, $db );

		$tmpDev = new Device();
		$tmpDev->DeviceID = intval($this->EndpointDeviceID);
		$tmpDev->GetDevice( $db );

		if ( $tmpDev->DeviceType == "Switch" && $recursive) {
			$sql = sprintf( "delete from fac_SwitchConnection where SwitchDeviceID=%d and SwitchPortNumber=%d", $this->EndpointDeviceID, $this->EndpointPort );
			$result = mysql_query( $sql, $db );
		}

		if ( $tmpDev->DeviceType == "Patch Panel" ) {
			$tmpPan = new PatchConnection();
			$tmpPan->PanelDeviceID = $this->EndpointDeviceID;
			$tmpPan->PanelPortNumber = $this->EndpointPort;
			$tmpPan->RemoveFrontConnection( $db, false );
		}

		return $result;
	}
  
	function DropEndpointConnections( $db ) {
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE FROM fac_SwitchConnection WHERE EndpointDeviceID=$this->EndpointDeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("DropEndpointConnections::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}
  
	function DropSwitchConnections() {
		global $dbh;

		$this->MakeSafe();

		$sql="DELETE FROM fac_SwitchConnection WHERE SwitchDeviceID=$this->EndpointDeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("DropSwitchConnections::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
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

class SwitchInfo {
	/* All of these functions will REQUIRE the built-in SNMP functions - the external calls are simply too slow */
	static function getNumPorts($DeviceID) {
		global $dbh;
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		
		if(!$dev->GetDevice()) {
			return false;
		}
		
		if ( $dev->PrimaryIP == "" || $dev->SNMPCommunity == "" )
			return;
			
		return @end( explode( ":", snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, 'IF-MIB::ifNumber.0' )));
	}

	static function findFirstPort( $DeviceID ) {
		global $dbh;
		
		$dev=new Device();
		$dev->DeviceID = $DeviceID;
		
		if ( !$dev->GetDevice() ) {
			return false;
		}

		if ( $dev->PrimaryIP == "" || $dev->SNMPCommunity == "" )
			return;
			
		$x = array();
		
		$portList = snmp2_real_walk( $dev->PrimaryIP, $dev->SNMPCommunity, "IF-MIB::ifDescr" );
		foreach( $portList as $index => $port ) {
			$head = @end( explode( ".", $index ) );
			$portdesc = @end( explode( ":", $port));
			if ( preg_match( "/\/1$/", $portdesc )) {
				$x[$head] = $portdesc;
			} // Find lines that end with /1
		}
		return $x;
	}

	static function getPortNames( $DeviceID, $portid = null ) {
		global $dbh;
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		
		if ( !$dev->GetDevice() ) {
			return false;
		}
		
		if ( $dev->PrimaryIP == "" || $dev->SNMPCommunity == "" )
			return;
			
		$baseOID = ".1.3.6.1.2.1.31.1.1.1.1.";
		$baseOID = "IF-MIB::ifName."; // MIB instead of OID, also full name instead of shorthand
		
		$nameList = array();
		if ( is_null( $portid )) {		
			for ( $n=0; $n < $dev->Ports; $n++ ){
				// Check to make sure that you're not timing out (snmp2_get returns FALSE), and if so, break out of the loop
				if ( ! $reply = snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID . ( $dev->FirstPortNum + $n )) )
					break;
				$query = @end( explode( ":", $reply ) );
				$nameList[$n+1] = $query;
			}
		} else {
				$query = @end( explode( ":", snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.$portid )));
				$nameList = $query;
		}
		
		return $nameList;
	}
	
	static function getPortStatus( $DeviceID, $portid = null ) {
		global $dbh;
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		
		if ( ! $dev->GetDevice() ) {
			return false;
		}
		
		if ( $dev->PrimaryIP == "" || $dev->SNMPCommunity == "" )
			return;
			
		$baseOID = ".1.3.6.1.2.1.2.2.1.8.";
		$baseOID="IF-MIB::ifOperStatus."; // arguments for not using MIB?

		$statusList = array();
		if ( is_null($portid) ) {		
			for ( $n=0; $n < $dev->Ports; $n++ ) {
				if ( ! $reply = snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.( $dev->FirstPortNum+$n )) )
					break;
				@preg_match( "/(INTEGER: )(.+)(\(.*)/", $reply, $matches);
				$statusList[$n+1]=@$matches[2];
			}
		}else{
			@preg_match( "/(INTEGER: )(.+)(\(.*)/", snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.$portid ), $matches);
			// This will change the array that was getting kicked back to a single value for an individual port lookup
			$statusList = @$matches[2];
		}
		
		return $statusList;
	}
	
	static function getPortAlias( $DeviceID, $portid = null ) {
		global $dbh;
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		
		if ( ! $dev->GetDevice() ) {
			return false;
		}
		
		if ( $dev->PrimaryIP == "" || $dev->SNMPCommunity == "" )
			return;
			
		$baseOID=".1.3.6.1.2.1.31.1.1.1.18.";
		
		$aliasList = array();

		if ( is_null( $portid )) {
			for ( $n=0; $n < $dev->Ports; $n++ ) {
				if ( ! $reply = snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.( $dev->FirstPortNum+$n )) )
					break;
				$query = @end( explode( ":", $reply ));
				$aliasList[$n] = $query;
			}
		}else{
			$query = @end( explode( ":", snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.$portid )));
			$aliasList = $query;
		}
		
		return $aliasList;	
	}
}

class Tags {
	var $TagID;
	var $TagName;

	//Add Create Tag Function
	static function CreateTag($TagName){
		if(!is_null($TagName)){
			$TagName=mysql_real_escape_string($TagName);
			$sql="INSERT INTO fac_Tags VALUES (NULL, '$TagName');";
			$results=mysql_query($sql);
			if($results){
				return mysql_insert_id();
			}
		}
		return null;
	}
	//Add Delete Tag Function

	static function FindID($TagName=null){
		if(!is_null($TagName)){
			$TagName=mysql_real_escape_string($TagName);
			$sql="SELECT TagID FROM fac_Tags WHERE Name = '$TagName';";
			$result=mysql_query($sql);
			if(mysql_num_rows($result)>0){
				return mysql_result($result,0);
			}
		}else{
			//No tagname was supplied so kick back an array of all available TagIDs and Names
			return $this->FindAll();
		}
		//everything failed give them nothing
		return 0;
	}

	static function FindName($TagID=null){
		if(!is_null($TagID)){
			$TagID=intval($TagID);
			$sql="SELECT Name FROM fac_Tags WHERE TagID = $TagID;";
			$result=mysql_query($sql);
			if(mysql_num_rows($result)>0){
				return mysql_result($result,0);
			}
		}else{
			//No tagname was supplied so kick back an array of all available TagIDs and Names
			return $this->FindAll();
		}
		//everything failed give them nothing
		return 0;
	}

	static function FindAll(){
		$sql="SELECT * FROM fac_Tags;";
		$result=mysql_query($sql);
		$tagarray=array();
		if(mysql_num_rows($result)>0){
			while($row=mysql_fetch_assoc($result)){
				$tagarray[$row['TagID']]=$row['Name'];
			}
			return $tagarray;
		}
		return 0;
	}

}


?>
