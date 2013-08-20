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

	function GetCabinet(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Cabinet WHERE CabinetID=$this->CabinetID;";
		
		if($cabinetRow=$dbh->query($sql)->fetch()){
			foreach(Cabinet::CabinetRowToObject($cabinetRow) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
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
		
		$sql="SELECT * FROM fac_Cabinet WHERE CabRowID=$this->CabRowID ORDER BY LENGTH(Location),Location ASC;";

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
	
	function GetCabinetSelectList(){
		global $dbh;
		
		$sql="SELECT Name, CabinetID, Location, AssignedTo FROM fac_DataCenter, fac_Cabinet WHERE 
			fac_DataCenter.DataCenterID=fac_Cabinet.DataCenterID ORDER BY Name ASC, 
			Location ASC;";

		$selectList="<select name=\"cabinetid\" id=\"cabinetid\"><option value=\"-1\">Storage Room</option>";

		foreach($dbh->query($sql) as $selectRow){
			if($selectRow["CabinetID"]==$this->CabinetID || User::Current()->canWrite($selectRow["AssignedTo"])){
				$selected=($selectRow["CabinetID"]==$this->CabinetID)?' selected':'';
				$selectList.="<option value=\"{$selectRow["CabinetID"]}\"$selected>{$selectRow["Name"]} / {$selectRow["Location"]}</option>";
			}
		}

		$selectList .= "</select>";

		return $selectList;
	}

	function BuildCabinetTree(){
		global $dbh;
		
		$dc=new DataCenter();
		$dept=new Department();

		$dcList=$dc->GetDCList();

		if(count($dcList) >0){
			$tree="<ul class=\"mktree\" id=\"datacenters\">\n";
			
			$zoneInfo=new Zone();
			while(list($dcID,$datacenter)=each($dcList)){
				if($dcID==$this->DataCenterID){
					$classType = "liOpen";
				}else{
					$classType = "liClosed";
				}

				$tree.="\t<li class=\"$classType\" id=\"dc$dcID\"><a href=\"dc_stats.php?dc=$datacenter->DataCenterID\">$datacenter->Name</a>/\n\t\t<ul>\n";

				$sql="SELECT * FROM fac_Cabinet WHERE DataCenterID=\"$dcID\" ORDER BY Location ASC;";

				foreach($dbh->query($sql) as $cabRow){
					$dept->DeptID = $cabRow["AssignedTo"];
				  
					if($dept->DeptID==0){
						$dept->Name = "General Use";
					}else{
						$dept->GetDeptByID();
					}
				    
					$tree.="\t\t\t<li id=\"cab{$cabRow['CabinetID']}\"><a href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']} [$dept->Name]</a></li>\n";
				}

				$tree.="\t\t</ul>\n	</li>\n";
			}
			
			$tree.="<li class=\"liOpen\" id=\"dc-1\"><a href=\"storageroom.php\">Storage Room</a></li>";
			$tree.="</ul>";
		}

		return $tree;
	}

	function DeleteCabinet(){
		global $dbh;
		
		/* Need to delete all devices and CDUs first */
		$tmpDev=new Device();
		$tmpCDU=new PowerDistribution();
		
		$tmpDev->Cabinet=$this->CabinetID;
		$devList=$tmpDev->ViewDevicesByCabinet();
		
		foreach($devList as &$delDev){
			$delDev->DeleteDevice();
		}
		
		$tmpCDU->CabinetID=$this->CabinetID;
		$cduList=$tmpCDU->GetPDUbyCabinet();
		
		foreach($cduList as &$delCDU){
			$delCDU->DeletePDU();
		}
		
		$sql="DELETE FROM fac_Cabinet WHERE CabinetID=$this->CabinetID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error::DeleteCabinet: {$info[2]} SQL=$sql");
			return false;
		}
		
		return true;
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

			error_log("PDO Error::CreateCode {$info[2]}");
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
	/* ConnectionPath:	Display connection path between two endpoint devices through DC infrastructure.
						Initial info are DeviceID and PortNumber. 
						Then locates one end of the connection path with "GotoHeadDevice" method.
						Walk the path to the other end with "GotoNextDevice" method.	 
	 					Contribution of Jose Miguel Gomez Apesteguia (June 2013)
	*/
	
	var $DeviceID;
	var $PortNumber; //The sign of PortNumber indicate if the path continue by front port (>0) or rear port (<0)
	
	private $PathAux; //loops control
	
	function MakeSafe(){
		$this->DeviceID=intval($this->DeviceID);
		$this->PortNumber=intval($this->PortNumber);
	}

	private function AddDeviceToPathAux () {
		$i=count($this->PathAux);
		$this->PathAux[$i]["DeviceID"]=$this->DeviceID;
		$this->PathAux[$i]["PortNumber"]=$this->PortNumber;
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
		
		$FrontPort=new DevicePorts();
		$FrontPort->DeviceID=$this->DeviceID;
		$FrontPort->PortNumber=abs($this->PortNumber);
		
		$RearPort=new DevicePorts();
		$RearPort->DeviceID=$this->DeviceID;
		$RearPort->PortNumber=-abs($this->PortNumber);
		
		if ($FrontPort->getPort() && $RearPort->getPort()){
			//It's a Panel (intermediate device)
			while ($this->GotoNextDevice ()){
				if (!$this->IsDeviceInPathAux()){
					$this->AddDeviceToPathAux();
				}else {
					//loop!!
					return false;
				}
			}
			//change orientation
			$this->PortNumber=-$this->PortNumber;
		} else {
			//It's not a panel
			$this->PortNumber=abs($this->PortNumber);
		}
		return true;
	}
	
	function GotoNextDevice () {
	//It puts the object with the DeviceID and PortNumber of the following device in the path.
	//If the current device of the object is not connected to at all, gives back "false" and the object does not change
		global $dbh;
		$this->MakeSafe();
		
		$port=new DevicePorts();
		$port->DeviceID=$this->DeviceID;
		$port->PortNumber=$this->PortNumber;
		if ($port->getPort()){
			if (is_null($port->ConnectedDeviceID) || is_null($port->ConnectedPort)){
				return false;
			} else {
				$this->DeviceID=$port->ConnectedDeviceID;
				$this->PortNumber=-$port->ConnectedPort;
				return true;
			}
		} else
			return false;
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
	var $Rights;
	var $HalfDepth ;
	var $BackSide ;
	
	function MakeSafe() {
		//Keep weird values out of DeviceType
		$validdevicetypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure');

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
		$this->DeviceType=(in_array($this->DeviceType,$validdevicetypes))?$this->DeviceType:'Server';
		$this->ChassisSlots=intval($this->ChassisSlots);
		$this->RearChassisSlots=intval($this->RearChassisSlots);
		$this->ParentDevice=intval($this->ParentDevice);
		$this->MfgDate=addslashes($this->MfgDate);
		$this->InstallDate=addslashes($this->InstallDate);
		$this->WarrantyCo=addslashes(trim($this->WarrantyCo));
		$this->WarrantyExpire=addslashes($this->WarrantyExpire);
		$this->Notes=addslashes(trim($this->Notes));
		$this->Reservation=intval($this->Reservation);
		$this->HalfDepth=intval($this->HalfDepth);
		$this->BackSide=intval($this->BackSide);
	}
	
	function MakeDisplay() {
		$this->Label=stripslashes($this->Label);
		$this->SerialNo=stripslashes($this->SerialNo);
		$this->AssetTag=stripslashes($this->AssetTag);
		$this->PrimaryIP=stripslashes($this->PrimaryIP);
		$this->SNMPCommunity=stripslashes($this->SNMPCommunity);
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
		$dev->HalfDepth=$dbRow["HalfDepth"];
		$dev->BackSide=$dbRow["BackSide"];
		
		$dev->MakeDisplay();
		$dev->FilterRights();

		return $dev;
	}

	private function FilterRights(){
		$cab=new Cabinet();
		$cab->CabinetID=$this->Cabinet;

		$this->Rights='None';
		$user=User::Current();
		if($user->canRead($this->Owner)){$this->Rights="Read";}
		if($user->canWrite($this->Owner)){$this->Rights="Write";} // write by device
		if($cab->GetCabinet()){
			if($user->canWrite($cab->AssignedTo)){$this->Rights="Write";} // write because the cabinet is assigned
		}
		if($user->SiteAdmin && $this->DeviceType=='Patch Panel'){$this->Rights="Write";} // admin override of rights for patch panels

		// Remove information that this user isn't allowed to see
		if($this->Rights=='None'){
			$publicfields=array('DeviceID','Label','Cabinet','Position','Height','Reservation','DeviceType','Rights');
			foreach($this as $prop => $value){
				if(!in_array($prop,$publicfields)){
					$this->$prop=null;
				}
			}
		}
	}
	

	function CreateDevice(){
		global $dbh;
		
		$this->MakeSafe();
		
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);
		
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
			Reservation=$this->Reservation, HalfDepth=$this->HalfDepth, BackSide=$this->BackSide;";

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		$this->DeviceID = $dbh->lastInsertId();

		DevicePorts::createPorts($this->DeviceID);

		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->DeviceID;
	}

	function CopyDevice($clonedparent=null) {
		/*
		 * Need to make a copy of a device for the purpose of assigning a reservation during a move
		 *
		 * The second paremeter is optional for a copy.  if it is set and the device is a chassis
		 * this should be set to the ID of the new parent device.
		 *
		 * Also do not copy any power or network connections!
		 */
		
		// Get the device being copied
		$this->GetDevice();
		
		if($this->ParentDevice >0){
			/*
			 * Child devices will need to be constrained to the chassis. Check for open slots
			 * on whichever side of the chassis the blade is currently.  If a slot is available
			 * clone into the next available slot or return false and display an appropriate 
			 * errror message
			 */
			$tmpdev=new Device();
			$tmpdev->DeviceID=$this->ParentDevice;
			$tmpdev->GetDevice();
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
					$this->CreateDevice();
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
			$this->CreateDevice();

			// If this is a chassis device and children are present clone them
			if(isset($childList)){
				foreach($childList as $child){
					$child->CopyDevice($this->DeviceID);
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
			$info=$dbh->errorInfo();

			error_log("Surplus::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		// Ok, we have the transaction of decommissioning, now tidy up the database.
		$this->DeleteDevice();
	}
  
	function MoveToStorage() {
		// Cabinet ID of -1 means that the device is in the storage area
		$this->Cabinet=-1;
		$this->UpdateDevice();
		
		// While the child devices will automatically get moved to storage as part of the UpdateDevice() call above, it won't sever their network connections
		if($this->DeviceType=="Chassis"){
			$childList=$this->GetDeviceChildren();
			foreach($childList as $child){
				DevicePorts::removeConnections($child->DeviceID);
			}
		}

		// Delete all network connections first
		DevicePorts::removeConnections($this->DeviceID);
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

		// Check the user's permissions to modify this device
		if($tmpDev->Rights!='Write'){return false;}
	
		$this->MakeSafe();	

		// You can't update what doesn't exist, so check for existing record first and retrieve the current location
		$sql = "SELECT * FROM fac_Device WHERE DeviceID=$this->DeviceID;";
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}		

		// If you changed cabinets then the power connections need to be removed
		if($row["Cabinet"]!=$this->Cabinet){
			$cab=new Cabinet();
			$cab->CabinetID=$this->Cabinet;
			$cab->GetCabinet();
			// Make sure the user has rights to save a device into the new cabinet
			if(!User::Current()->canWrite($cab->AssignedTo)){
				return false;
			}
			$powercon=new PowerConnection();
			$powercon->DeviceID=$this->DeviceID;
			$powercon->DeleteConnections();
		}
  
		if($tmpDev->DeviceType == "Chassis" && $tmpDev->DeviceType != $this->DeviceType){
			// SUT #148 - Previously defined chassis is no longer a chassis
			// If it has children, return with no update
			$childList=$this->GetDeviceChildren();
			if(sizeof($childList)>0){
				$this->GetDevice();
				return;
			}
		}

		// If we made it to a device update and the number of ports available don't match the device, just fix it.
		if($tmpDev->Ports!=$this->Ports){
			if($tmpDev->Ports>$this->Ports){ // old device has more ports
				for($n=$this->Ports; $n<$tmpDev->Ports; $n++){
					$p=new DevicePorts;
					$p->DeviceID=$this->DeviceID;
					$p->PortNumber=$n+1;
					$p->removePort();
					if($this->DeviceType=='Patch Panel'){
						$p->PortNumber=$p->PortNumber*-1;
						$p->removePort();
					}
				}
			}else{ // new device has more ports
				for($n=$tmpDev->Ports; $n<$this->Ports; ++$n){
					$p=new DevicePorts;
					$p->DeviceID=$this->DeviceID;
					$p->PortNumber=$n+1;
					$p->createPort();
					if($this->DeviceType=='Patch Panel'){
						$p->PortNumber=$p->PortNumber*-1;
						$p->createPort();
					}
				}

			}
		}
		
		if(($tmpDev->DeviceType=="Switch" || $tmpDev->DeviceType=="Patch Panel") && $tmpDev->DeviceType!=$this->DeviceType){
			// SUT #417 - Changed a Switch or Patch Panel to something else (even if you change a switch to a Patch Panel, the connections are different)
			if($tmpDev->DeviceType=="Switch"){
				DevicePorts::removeConnections($this->DeviceID);
			}
			if($tmpDev->DeviceType=="Patch Panel"){
				DevicePorts::removeConnections($this->DeviceID);
				$p=new DevicePorts();
				$p->DeviceID=$this->DeviceID;
				$ports=$p->getPorts();
				foreach($ports as $i => $port){
					if($port->PortNumber<0){
						$port->removePort();
					}
				}
			}
		}
		if($this->DeviceType == "Patch Panel" && $tmpDev->DeviceType != $this->DeviceType){
			// This asshole just changed a switch or something into a patch panel. Make the rear ports.
			$p=new DevicePorts();
			$p->DeviceID=$this->DeviceID;
			if($tmpDev->Ports!=$this->Ports && $tmpDev->Ports<$this->Ports){
				// since we just made the new rear ports up there only make the first few, hopefully.
				for($n=1;$n<=$tmpDev->Ports;$n++){
					$i=$n*-1;
					$p->PortNumber=$i;
					$p->createPort();
				}
			}else{
				// make a rear port to match every front port
				$ports=$p->getPorts();
				foreach($ports as $i => $port){
					$port->PortNumber=$port->PortNumber*-1;
					$port->createPort();
				}
			}
		}
		
		// Force all uppercase for labels
		$this->Label=transform($this->Label);
		$this->SerialNo=transform($this->SerialNo);
		$this->AssetTag=transform($this->AssetTag);

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
			Reservation=$this->Reservation, HalfDepth=$this->HalfDepth, BackSide=$this->BackSide WHERE DeviceID=$this->DeviceID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("UpdateDevice::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		return true;
	}

	function GetDevice(){
		global $dbh;
	
		$this->MakeSafe();
	
		if($this->DeviceID==0 || $this->DeviceID == null){
			return false;
		}
		
		$sql="SELECT * FROM fac_Device WHERE DeviceID=$this->DeviceID;";

		if($devRow=$dbh->query($sql)->fetch()){
			foreach(Device::DeviceRowToObject($devRow) as $prop => $value){
				$this->$prop=$value;
			}

			return true;
		}else{
			return false;
		}
	}

	function GetDevicesbyAge($days=7){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE DATEDIFF(CURDATE(),InstallDate)<=".intval($days)." ORDER BY InstallDate ASC;";
		
		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
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
	
	function GetParentDevices(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ChassisSlots>0 AND ParentDevice=0 ORDER BY Label ASC;";
		
		$parentList=array();

		foreach($dbh->query($sql) as $row){
			$parentList[]=Device::DeviceRowToObject($row);
		}
		
		return $parentList;
	}

	function ViewDevicesByCabinet($includechildren=false){
		global $dbh;

		$this->MakeSafe();

		if($includechildren){
			$sql="SELECT * FROM fac_Device WHERE ParentDevice IN (SELECT DeviceID FROM 
				fac_Device WHERE Cabinet=$this->Cabinet) UNION SELECT * FROM fac_Device 
				WHERE Cabinet=$this->Cabinet ORDER BY ParentDevice ASC, Position DESC;";
		}else{		
			$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet AND Cabinet!=0 
				ORDER BY Position DESC;";
		}

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::DeviceRowToObject($deviceRow);
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

	function DeleteDevice(){
		global $dbh;
	
		$this->MakeSafe();
	
		// First, see if this is a chassis that has children, if so, delete all of the children first
		if($this->ChassisSlots >0){
			$childList=$this->GetDeviceChildren();
			
			foreach($childList as $tmpDev){
				$tmpDev->DeleteDevice();
			}
		}
		
		// Delete all network connections first
		DevicePorts::removePorts($this->DeviceID);
		
		// Delete power connections next
		$powercon=new PowerConnection();
		$powercon->DeviceID=$this->DeviceID;
		$powercon->DeleteConnections();

		// Now delete the device itself
		$sql="DELETE FROM fac_Device WHERE DeviceID=$this->DeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return;
	}


	function SearchDevicebyLabel(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE Label LIKE \"%$this->Label%\" ORDER BY Label;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

	function GetDevicesbyOwner(){
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

  function GetESXDevices() {
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ESX=TRUE ORDER BY DeviceID;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){ 
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebySerialNo(){
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_Device WHERE SerialNo LIKE \"%$this->SerialNo%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebyAssetTag(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE AssetTag LIKE \"%$this->AssetTag%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::DeviceRowToObject($deviceRow);
		}

		return $deviceList;

	}
  
	function SearchByCustomTag($tag=null){
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
	
	function GetTop10Tenants(){
		global $dbh;
		
		$sql="SELECT SUM(Height) AS RackUnits,fac_Department.Name AS OwnerName FROM 
			fac_Device,fac_Department WHERE Owner IS NOT NULL AND 
			fac_Device.Owner=fac_Department.DeptID GROUP BY Owner ORDER BY RackUnits 
			DESC LIMIT 0,10";

		$deptList = array();
		
		foreach($dbh->query($sql) as $row){
			$deptList[$row["OwnerName"]]=$row["RackUnits"];
		}
		  
		return $deptList;
	}
  
  
	function GetTop10Power(){
		global $dbh;
		
		$sql="SELECT SUM(NominalWatts) AS TotalPower,fac_Department.Name AS OwnerName 
			FROM fac_Device,fac_Department WHERE Owner IS NOT NULL AND 
			fac_Device.Owner=fac_Department.DeptID GROUP BY Owner ORDER BY TotalPower 
			DESC LIMIT 0,10";

		$deptList=array();

		foreach($dbh->query($sql) as $row){
			$deptList[$row["OwnerName"]]=$row["TotalPower"];
		}
		  
		return $deptList;
	}
  
  
  function GetDeviceDiversity(){
	global $dbh;
	
    $pc=new PowerConnection();
    $PDU=new PowerDistribution();
	
	// If this is a child (card slot) device, then only the parent will have power connections defined
	if($this->ParentDevice >0){
		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->ParentDevice;
		
		$sourceList=$tmpDev->GetDeviceDiversity();
	}else{
		$pc->DeviceID=$this->DeviceID;
		$pcList=$pc->GetConnectionsByDevice();
		
		$sourceList=array();
		$sourceCount=0;
		
		foreach($pcList as $pcRow){
			$PDU->PDUID=$pcRow->PDUID;
			$powerSource=$PDU->GetSourceForPDU();

			if(!in_array($powerSource,$sourceList)){
				$sourceList[$sourceCount++]=$powerSource;
			}
		}
	}
	
    return $sourceList;
  }

  function GetSinglePowerByCabinet(){
	global $dbh;
	
    // Return an array of objects for devices that
    // do not have diverse (spread across 2 or more sources)
    // connections to power
    $pc = new PowerConnection();
    $PDU = new PowerDistribution();
    
    $sourceList = $this->ViewDevicesByCabinet();

    $devList = array();
    
    foreach ( $sourceList as $devRow ) {    
      if ( ( $devRow->DeviceType == 'Patch Panel' || $devRow->DeviceType == 'Physical Infrastructure' || $devRow->ParentDevice > 0 ) && ( $devRow->PowerSupplyCount == 0 ) )
        continue;

      $pc->DeviceID = $devRow->DeviceID;
      
      $diversityList = $devRow->GetDeviceDiversity();
      
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
	
	function GetDeviceCabinetID(){
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->DeviceID;
		$tmpDev->GetDevice();
		
		while ( $tmpDev->ParentDevice <> 0) {
			$tmpDev->DeviceID = $tmpDev->ParentDevice;
			$tmpDev->GetDevice();
		}
		return $tmpDev->Cabinet;	
	}

	function GetDeviceLineage() {
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

	function GetRootDeviceID(){
		$tmpDev = new Device();
		$tmpDev->DeviceID = $this->DeviceID;
		$tmpDev->GetDevice();
		
		while ( $tmpDev->ParentDevice <> 0) {
			$tmpDev->DeviceID = $tmpDev->ParentDevice;
			$tmpDev->GetDevice();
		}
		return $tmpDev->DeviceID;	
	}
	
	function GetDeviceTotalPower(){
	//calculate device power including child devices power
		$TotalPower=0;
		//own device power
		if($this->NominalWatts>0){
			$TotalPower=$this->NominalWatts;
		}elseif ($this->TemplateID>0){
			$templ=new DeviceTemplate();
			$templ->TemplateID=$this->TemplateID;
			$templ->GetTemplateByID();
			$TotalPower=$templ->Wattage;
		}
		
		//child device power
		if ( $this->ChassisSlots > 0 ) {
			$childList = $this->GetDeviceChildren();
			foreach ( $childList as $tmpDev ) {
				$TotalPower+=$tmpDev->GetDeviceTotalPower();
			}
		}
		return $TotalPower;	
	}

	function GetDeviceTotalWeight(){
	//calculate device weight including child devices weight
		
		$TotalWeight=0;
		
		//own device weight
		if ($this->TemplateID>0){
			$templ=new DeviceTemplate();
			$templ->TemplateID=$this->TemplateID;
			$templ->GetTemplateByID();
			$TotalWeight=$templ->Weight;
		}
		
		//child device weight
		if ( $this->ChassisSlots > 0 ) {
			$childList = $this->GetDeviceChildren();
			foreach ( $childList as $tmpDev ) {
				$TotalWeight+=$tmpDev->GetDeviceTotalWeight();
			}
		}
		return $TotalWeight;	
	}
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

		$sql="SELECT * FROM fac_Ports WHERE DeviceID=$this->DeviceID ORDER BY PortNumber ASC;";

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

	static function createPorts($DeviceID){
		$dev=New Device;
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}
		$portList=array();

		// Build the DevicePorts from the existing info in the following priority:
		//  - Existing switchconnection table
		//  - SNMP data (if it exists)
		//  - Placeholders
		if($dev->DeviceType=="Switch"){
			$nameList=SwitchInfo::getPortNames($dev->DeviceID);
			$aliasList=SwitchInfo::getPortAlias($dev->DeviceID);
			
			for( $n=0; $n<$dev->Ports; $n++ ){
				$i=$n+1;
				$portList[$i]=new DevicePorts();
				$portList[$i]->DeviceID=$dev->DeviceID;
				$portList[$i]->PortNumber=$i;
				$portList[$i]->Label=@$nameList[$n];
				$portList[$i]->Notes=@$aliasList[$n];

				$portList[$i]->createPort();
			}
		}else{
			for( $n=0; $n<$dev->Ports; $n++ ){
				$i=$n+1;
				$portList[$i]=new DevicePorts();
				$portList[$i]->DeviceID=$dev->DeviceID;
				$portList[$i]->PortNumber=$i;

				$portList[$i]->createPort();
				if($dev->DeviceType=="Patch Panel"){
					$i=$i*-1;
					$portList[$i]=new DevicePorts();
					$portList[$i]->DeviceID=$dev->DeviceID;
					$portList[$i]->PortNumber=$i;

					$portList[$i]->createPort();
				}
			}
		}
		return $portList;
	}


	function updateLabel(){
		global $dbh;

		$this->MakeSafe();

		$label=$this->Label;
		if(!$this->getPort()){return false;}

		$sql="UPDATE fac_Ports SET Label=\"$label\" WHERE 
			DeviceID=$this->DeviceID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			return false;
		}else{
			return true;
		}
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

		//check rights before we go any further
		$dev=new Device();
		$dev->DeviceID=$this->DeviceID;
		$dev->GetDevice();
		$replacingdev=new Device();
		$replacingdev->DeviceID=$oldport->ConnectedDeviceID;
		$replacingdev->GetDevice();
		$connecteddev=new Device();
		$connecteddev->DeviceID=$this->ConnectedDeviceID;
		$connecteddev->GetDevice();

		$rights=false;
		$rights=($dev->Rights=="Write")?true:$rights;
		$rights=($replacingdev->Rights=="Write")?true:$rights;
		$rights=($connecteddev->Rights=="Write")?true:$rights;

		if(!$rights){
			return false;
		}
	
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
			Label=\"$this->Label\", ConnectedPort=$this->ConnectedPort, 
			Notes=\"$this->Notes\" WHERE DeviceID=$this->DeviceID AND 
			PortNumber=$this->PortNumber;";

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

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}

		$sql="UPDATE fac_Ports SET ConnectedDeviceID=NULL, ConnectedPort=NULL WHERE
			DeviceID=$dev->DeviceID OR ConnectedDeviceID=$dev->DeviceID;";

		$dbh->exec($sql); // don't need to log if this fails

		return true;
	}

	static function removePorts($DeviceID){
		/*	Remove all ports from a device prior to delete, etc */
		global $dbh;

		$dev=new Device(); // make sure we have a real device first
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}

		DevicePorts::removeConnections($DeviceID);

		$sql="DELETE FROM fac_Ports WHERE DeviceID=$dev->DeviceID;";

		$dbh->exec($sql);

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
			// Run two queries - first, devices in the same cabinet as the device patching from
			$sql="SELECT DISTINCT a.DeviceID FROM fac_Ports a, fac_Device b WHERE b.Cabinet=$dev->Cabinet AND a.DeviceID=b.DeviceID AND a.DeviceID!=$dev->DeviceID$mediaenforce$pp ORDER BY b.Label ASC;";
			foreach($dbh->query($sql) as $row){
				$candidate=$row['DeviceID'];
				$tmpDev=new Device();
				$tmpDev->DeviceID=$candidate;
				$tmpDev->GetDevice();

				// Filter device pick list by what they have rights to modify
				($tmpDev->Rights=="Write")?$candidates[]=$tmpDev:'';
			}
			// Then run the same query, but for the rest of the devices in the database
			$sql="SELECT DISTINCT a.DeviceID FROM fac_Ports a, fac_Device b WHERE b.Cabinet>-1 AND b.Cabinet!=$dev->Cabinet AND a.DeviceID=b.DeviceID AND a.DeviceID!=$dev->DeviceID$mediaenforce$pp ORDER BY b.Label ASC;";
			foreach($dbh->query($sql) as $row){
				$candidate=$row['DeviceID'];
				$tmpDev=new Device();
				$tmpDev->DeviceID=$candidate;
				$tmpDev->GetDevice();

				// Filter device pick list by what they have rights to modify
				($tmpDev->Rights=="Write")?$candidates[]=$tmpDev:'';
			}
		}else{
			$sql="SELECT a.* FROM fac_Ports a, fac_Device b WHERE b.Cabinet>-1 AND a.DeviceID=b.DeviceID AND a.DeviceID!=$dev->DeviceID AND ConnectedDeviceID IS NULL$mediaenforce$pp;";
			foreach($dbh->query($sql) as $row){
				$candidates[]=DevicePorts::RowToObject($row);
			}
		}

		return $candidates;
	}

	static function getPortList($DeviceID){
		global $dbh;
		
		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){
			return false;	// This device doesn't exist
		}
		
		$sql="SELECT * FROM fac_Ports WHERE DeviceID=$dev->DeviceID;";
		
		$portList=array();
		foreach($dbh->query($sql) as $row){
			$portList[$row['PortNumber']]=DevicePorts::RowToObject($row);
		}
		
		if( sizeof($portList)==0 && $dev->DeviceType!="Physical Infrastructure" ){
			// somehow this device doesn't have ports so make them now
			$portList=DevicePorts::createPorts($dev->DeviceID);
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

	function search($sql){
		global $dbh;

		$vmList=array();
		$vmCount=0;

		foreach($dbh->query($sql) as $row){
			$vmList[$vmCount]=ESX::ESXRowToObject($row);
			$vmCount++;
		}

		return $vmList;
	}

	static function EnumerateVMs($dev,$debug=false){
		$community=$dev->SNMPCommunity;
		$serverIP=$dev->PrimaryIP;

		$vmList=array();

		$namesList = @snmp2_real_walk( $serverIP, $community, ".1.3.6.1.4.1.6876.2.1.1.2" );
		$statesList = @snmp2_real_walk( $serverIP, $community, ".1.3.6.1.4.1.6876.2.1.1.6" );

		if ( is_array( $namesList ) && count($namesList) > 0  && count($namesList) == count($statesList)){
			$tempList=array_combine($namesList,$statesList);
		} else {
			$tempList=array();
		}

		if ( @count( $tempList ) > 0 ) {
			if ( $debug )
				printf( "\t%d VMs found\n", count( $tempList ) );

			foreach( $tempList as $name => $state ) {
				$vmID = sizeof( $vmList );
				$vmList[$vmID] = new ESX();
				$vmList[$vmID]->DeviceID = $dev->DeviceID;
				$vmList[$vmID]->LastUpdated = date( 'Y-m-d H:i:s' );
				$vmList[$vmID]->vmID = $vmID;
				$vmList[$vmID]->vmName = trim( str_replace( '"', '', @end( explode( ":", $name ) ) ) );
				$vmList[$vmID]->vmState = trim( str_replace( '"', '', @end( explode( ":", $state ) ) ) );
			}
		}

		return $vmList;
	}
  
	function UpdateInventory($debug=false){
		$dev=new Device();

		$devList=$dev->GetESXDevices();

		foreach($devList as $esxDev){
			if($debug){
				print "Querying host $esxDev->Label @ $esxDev->PrimaryIP...\n";
			}

			$vmList = ESX::RefreshInventory( $esxDev );

			if($debug){
				print_r($vmList);
			}
		}
	}
  
	static function RefreshInventory( $DeviceID, $debug = false ) {
		global $dbh;

		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		$dev->GetDevice();
		
		$search = $dbh->prepare( "select * from fac_VMInventory where vmName=:vmName" );
		$update = $dbh->prepare( "update fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState where vmName=:vmName" );
		$insert = $dbh->prepare( "insert into fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState, vmName=:vmName" );
		
		$vmList = ESX::EnumerateVMs( $dev );
		if ( count( $vmList ) > 0 ) {
			foreach( $vmList as $vm ) {
				$search->execute( array( ":vmName"=>$vm->vmName ) );
				
				$parameters = array( ":DeviceID"=>$vm->DeviceID, ":LastUpdated"=>$vm->LastUpdated, ":vmID"=>$vm->vmID, ":vmState"=>$vm->vmState, ":vmName"=>$vm->vmName );

				if ( $search->rowCount() > 0 ) {
					$update->execute( $parameters );
					if ( $debug )
						error_log( "Updating existing VM '" . $vm->vmName . "'in inventory." );
				} else {
					$insert->execute( $parameters );
					if ( $debug ) 
						error_log( "Adding new VM '" . $vm->vmName . "'to inventory." );
				}
			}
		}
		
		return $vmList;
	}
  
	function GetVMbyIndex() {
		global $dbh;

		$sql="SELECT * FROM fac_VMInventory WHERE VMIndex=$this->VMIndex;";

		if(!$vmRow=$dbh->query($sql)){
			return false;
		}else{
			foreach(ESX::ESXRowToObject($vmRow) as $param => $value){
				$this->$param=$value;
			}
			return true;
		}
	}
  
	function UpdateVMOwner() {
		global $dbh;

		$sql="UPDATE fac_VMInventory SET Owner=$this->Owner WHERE VMIndex=$this->VMIndex;";
		$dbh->query($sql);
	} 
  
	function GetInventory() {
		$sql="SELECT * FROM fac_VMInventory ORDER BY DeviceID, vmName;";
		return $this->search($sql);
	}
  
	function GetDeviceInventory() {
		$sql="SELECT * FROM fac_VMInventory WHERE DeviceID=$this->DeviceID ORDER BY vmName;";
		return $this->search($sql);
	}
  
	function GetVMListbyOwner() {
		$sql="SELECT * FROM fac_VMInventory WHERE Owner=$this->Owner ORDER BY DeviceID, vmName;";
		return $this->search($sql);
	}
  
	function SearchByVMName() {
		$sql="SELECT * FROM fac_VMInventory WHERE vmName like \"%$this->vmName%\";";
		return $this->search($sql);
	}
  
	function GetOrphanVMList(){
		$sql="SELECT * FROM fac_VMInventory WHERE Owner IS NULL;"; 
		return $this->search($sql);
	}

	function GetExpiredVMList($numDays){
		// I don't think this is standard SQL and will need to be looked at closer
		$sql="SELECT * FROM fac_VMInventory WHERE to_days(now())-to_days(LastUpdated)>$numDays;"; 
		return $this->search($sql);
	}
  
	function ExpireVMs($numDays){
		global $dbh;

		// Don't allow calls to expire EVERYTHING
		if($numDays >0){
			$sql="DELETE FROM fac_VMInventory WHERE to_days(now())-to_days(LastUpdated)>$numDays;";
			$dbh->query($sql);
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
  
  function GetOpenRequests() {
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
		$nameList=array(); // should this fail return blank
		
		if(!$dev->GetDevice()){
			return $nameList;
		}
		
		if($dev->PrimaryIP=="" || $dev->SNMPCommunity==""){
			return $nameList;
		}
			
		$baseOID = ".1.3.6.1.2.1.31.1.1.1.1";
		$baseOID = "IF-MIB::ifName"; 

		if(is_null($portid)){		
			if($reply=snmp2_real_walk($dev->PrimaryIP,$dev->SNMPCommunity,$baseOID)){
				// Skip the returned values until we get to the first port
				$Saving = false;
				foreach($reply as $oid => $label){
					$indexValue = end(explode( ".", $oid ));
					if ( $indexValue == $dev->FirstPortNum )
						$Saving = true;
						
					if ( $Saving == true )
						$nameList[sizeof($nameList) + 1] = trim(end(explode(":",$label)));
					
					// Once we have captured enough values that match the number of ports, stop
					if ( sizeof( $nameList ) == $dev->Ports )
						break;
				}
			}
		} else {
				$query = @end( explode( ":", snmp2_get( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.'.'.$portid )));
				$nameList = $query;
		}
		
		return $nameList;
	}
	
	static function getPortStatus( $DeviceID, $portid = null ) {
		global $dbh;
		
		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		$statusList=array();
		
		if(!$dev->GetDevice()){
			return $statusList;
		}
		
		if($dev->PrimaryIP=="" || $dev->SNMPCommunity==""){
			return $statusList;
		}
			
		$baseOID = ".1.3.6.1.2.1.2.2.1.8.";
		$baseOID="IF-MIB::ifOperStatus."; // arguments for not using MIB?

		if ( is_null($portid) ) {		
			for ( $n=0; $n < $dev->Ports; $n++ ) {
				if(!$reply=@snmp2_get($dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.($dev->FirstPortNum+$n))){
					break;
				}
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
				$aliasList[$n+1] = $query;
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
		global $dbh;

		if(!is_null($TagName)){
			$TagName=addslashes($TagName);
			$sql="INSERT INTO fac_Tags VALUES (NULL, '$TagName');";
			if(!$dbh->exec($sql)){
				return null;
			}else{
				return $dbh->lastInsertId();
			}
		}
		return null;
	}

	//Add Delete Tag Function

	static function FindID($TagName=null){
		global $dbh;

		if(!is_null($TagName)){
			$TagName=addslashes($TagName);
			$sql="SELECT TagID FROM fac_Tags WHERE Name = '$TagName';";
			if($TagID=$dbh->query($sql)->fetchColumn()){
				return $TagID;
			}
		}else{
			//No tagname was supplied so kick back an array of all available TagIDs and Names
			return $this->FindAll();
		}
		//everything failed give them nothing
		return 0;
	}

	static function FindName($TagID=null){
		global $dbh;

		if(!is_null($TagID)){
			$TagID=intval($TagID);
			$sql="SELECT Name FROM fac_Tags WHERE TagID = $TagID;";
			if($TagName=$dbh->query($sql)->fetchColumn()){
				return $TagName;
			}
		}else{
			//No tagname was supplied so kick back an array of all available TagIDs and Names
			return $this->FindAll();
		}
		//everything failed give them nothing
		return 0;
	}

	static function FindAll(){
		global $dbh;

		$sql="SELECT * FROM fac_Tags;";

		$tagarray=array();
		foreach($dbh->query($sql) as $row){
			$tagarray[$row['TagID']]=$row['Name'];
		}
		return $tagarray;
	}

}

class PlannedPath {
	/* PlannedPath:		Search a minimun weight connection path between two endpoint devices.
	 					It use the panels to reach the goal. 
	 					From each device, first it try connecting patch panels or final device on actual cabinet, 
	 					and if is not posible, in the actual row of cabinets.
	 					Initial info are devID1, port1, devID2, port2.
	 					It use "MediaEnforce" configuration parameter for connections.
	 					Then, call to "MakePath" method to find the Path.
	 					If successful, go to the beginning of the connection path with "GotoHeadDevice" method.
						Walk the path to the other end with "GotoNextDevice" method.
						The "MakePath" method leaves a log file (ppath.log) on the server with the execution of the algorithm, for testing.
	 					Contribution of Jose Miguel Gomez Apesteguia (July 2013)
	*/
	
	//Device info for output
	var $DeviceID;
	var $PortNumber; //The sign of PortNumber indicate if the path continue to front port (>0) or rear port (<0)

	//initial device info input   
	var $devID1; 	
	var $port1;

	//final device info input
	var $devID2; 	
	var $port2;
	
	//aux info	
	private $cab2;		//Cabinet of final device
	private $row2;		//row of final device
	private $espejo2; 	//for ports protected by a panel in devID2 (port2 connected to rear connection of panel)

	private $nodes;		//array of nodes: [dev][port]{["prev_dev"],["prev_port"]}
	private $candidates;	//array of candidate nodes: [dev]{[port]}
							//an array smaller than $nodes, so the selection of the next node is faster
	private $used_candidates;	//array of used candidates
	
	//Path for output 
	var $Path; 			//array with created Path
	private $acti;  	//index of actual dev in $Path
	
	//error output
	var $PathError;
	
	private function escribe_log($texto){
		//remove next line if you want a log file on server
		//return;
		
	    $ddf = fopen('ppath.log','ab');
        fwrite($ddf,$texto."\r\n");
	    fclose($ddf);
	}
	
	private function MakeSafe(){
		$this->devID1=intval($this->devID1);
		$this->port1=intval($this->port1);
		$this->devID2=intval($this->devID2);
		$this->port2=intval($this->port2);
	}
	
	private function ClearPath(){
		$this->Path=array();
		$this->nodes=array();
		$this->candidates=array();
		$this->used_candidates=array();
	}
	
	private function AddNodeToList ($dev,$port,$weight,$prev_dev,$prev_port) {
		//Trato distinto las conexiones traseras y las frontales: las traseras nunca van a ser candidatos
		//Separate treatment for rear and front connections: the rear will never be candidates
		if($port<0 && $dev<>$this->devID2){
			if (isset($this->nodes[$dev][$port])) {
				if ($this->nodes[$dev][$port]["weight"]>$weight){
					$this->escribe_log("  -->Better path=>UPDATE NODE D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
					$this->nodes[$dev][$port]["weight"]=$weight;
					$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
					$this->nodes[$dev][$port]["prev_port"]=$prev_port;
				}
			} else {
				//es un nodo nuevo
				//it is a new node
				$this->escribe_log("  -->New node D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
				$this->nodes[$dev][$port]["weight"]=$weight;
				$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
				$this->nodes[$dev][$port]["prev_port"]=$prev_port;
			}
		}else {
			if (isset($this->candidates[$dev])) {
				if ($this->nodes[$dev][$this->candidates[$dev]]["weight"]>$weight){
					$this->escribe_log("  -->Better path=>UPDATE NODE D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
					unset($this->nodes[$dev]);
					$this->nodes[$dev][$port]["weight"]=$weight;
					$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
					$this->nodes[$dev][$port]["prev_port"]=$prev_port;
					$this->candidates[$dev]=$port;
				}
			} else {
				//es un nodo nuevo
				//it is a new node
				//Check is is already used
				if (!isset($this->used_candidates[$dev])){
					$this->escribe_log("  -->New node D=".$dev." P=".$port." W=".$weight." PD=".$prev_dev." PP=".$prev_port);
					$this->nodes[$dev][$port]["weight"]=$weight;
					$this->nodes[$dev][$port]["prev_dev"]=$prev_dev;
					$this->nodes[$dev][$port]["prev_port"]=$prev_port;
					$this->candidates[$dev]=$port;
				}
			}
		}
	}
	
	private function SelectNode () {
		//Busco el  nodo de la lista de candidatos el nodo con peso mínimo
		//search node in candidate list with min weight
		$minweight=99999; //big number
		$this->DeviceID=0;
		$this->escribe_log("CANDIDATES:");
		foreach($this->candidates as $dev => $port) {
			$this->escribe_log("  [D=".$dev.", P=".$port.", W=".$this->nodes[$dev][$port]["weight"]."]");
			if($this->nodes[$dev][$port]["weight"]<$minweight){
				$minweight=$this->nodes[$dev][$port]["weight"];
				$this->DeviceID=$dev;
				$this->PortNumber=$port;
			}
		}
		$this->escribe_log("");
		return ($this->DeviceID<>0);
	}
	
	private function UpdateList () {
		//find posible next devices with lower weight in list from actual node 
		//for each device found, if already it exists and it is not useded, update it if (new weight) < (old weight)
		//if it does not exist, insert in list with his actual weight and $used=false
		//Destination device is $this->devID2
		
		//weights
		$weight_cabinet=1; 	//weight for patches on actual cabinet
		$weight_rear=1;		//weight fot rear connetcion between panels
		$weight_row=4;		//weigth for patches on same row of cabinets (except actual cabinet)
		//It is possible to assign a weight proportional to the distance between the actual cabinet and each cabinet of actual row, 
		//so you can prioritize closest cabinets in the actual row. In the future...
		
		$this->escribe_log("\nSelected node: D=".$this->DeviceID.
						"; P=".$this->PortNumber.
						"; W=".$this->nodes[$this->DeviceID][$this->PortNumber]["weight"].
						"; PD=".$this->nodes[$this->DeviceID][$this->PortNumber]["prev_dev"].
						"; PP=".$this->nodes[$this->DeviceID][$this->PortNumber]["prev_port"]);;	
			
		//Compruebo si el puerto del dispositivo actual está conectado a la conexión trasera de un panel
		//I check if the port of this device is connected to a rear-panel connection
		$port=new DevicePorts();
		$port->DeviceID=$this->DeviceID;
		$port->PortNumber= $this->PortNumber;
		if (!$port->getPort()){
			$this->escribe_log("ERROR GETTING PORT");
			exit;
		}
		
		if ($port->ConnectedDeviceID<>0){
			if ($port->ConnectedPort<0){
				//It's a port of the first device connected to rear panel connection or it's a rear port of a panel.
				//Go to mirror device
				$this->escribe_log(" Rear connection to D=".$port->ConnectedDeviceID." P=".$port->ConnectedPort);
				$this->AddNodeToList($port->ConnectedDeviceID,-$port->ConnectedPort,$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_rear,$this->DeviceID, $this->PortNumber);
			} else {
				//port used in mirror panel
				//nothing to do
				$this->escribe_log(" Port used in mirror panel D=".$port->ConnectedDeviceID." P=".$port->ConnectedPort);
			}
		} else {
			//It's a free front port
			//get dev info: cabinet and row
			$device=new Device();
			$device->DeviceID=$this->DeviceID;
			$device->GetDevice();
			$cab=$device->GetDeviceCabinetID();
			$cabinet=new Cabinet();
			$cabinet->CabinetID=$cab;
			$cabinet->GetCabinet();
			$cabrow=new CabRow();
			$cabrow->CabRowID = $cabinet->CabRowID;
			$cabrow->GetCabRow();
			
			//busco el dispositivo final en el mismo armario (si no está reflejado en un panel)
			//looking for the end device in the same cabinet (if not reflected in a panel)
			if ($cab==$this->cab2 && !$this->espejo2){
				$this->escribe_log(" DEV2 found in actual cabinet (".$cab."-'".$cabinet->Location."')");
				$this->AddNodeToList($this->devID2,-$this->port2,$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet,$this->DeviceID, $this->PortNumber);
			}
			//Busco el dispositivo final en la misma fila
			//Look for the end device in the same row
			elseif ($cabrow->CabRowID<>0 && $cabrow->CabRowID==$this->row2 && !$this->espejo2){
				$this->escribe_log(" DEV2 found in actual row (".$cabrow->CabRowID."-'".$cabrow->Name."')");
				$this->AddNodeToList($this->devID2,-$this->port2,$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row,$this->DeviceID, $this->PortNumber);
			}
			
			//busco paneles con puertos libres en el armario actual
			//Look for panels with free ports on actual cabinet
			$this->escribe_log("Look for panels with free ports on actual cabinet (".$cab."-'".$cabinet->Location."')");
			global $dbh;
			global $config;

			$mediaenforce="";
			if($config->ParameterArray["MediaEnforce"]=='enabled'){
				$mediaenforce=" AND af.MediaID=".$port->MediaID;
			}
			$sql="SELECT af.DeviceID AS DeviceID1,
						af.PortNumber AS PortNumber1,
						bf.DeviceID AS DeviceID2,
						bf.PortNumber AS PortNumber2	 
				FROM fac_Ports af, fac_Ports ar, fac_Ports bf, fac_Device d 
				WHERE d.Cabinet=".$cab." AND 
					af.DeviceID=d.DeviceID AND 
					af.DeviceID!=".$this->DeviceID." AND
					af.ConnectedDeviceID IS NULL".$mediaenforce." AND 
					d.DeviceType='Patch Panel' AND
					af.PortNumber>0 AND
					ar.DeviceID=af.DeviceID AND ar.PortNumber=-af.PortNumber AND
					bf.DeviceID=ar.ConnectedDeviceID AND bf.PortNumber=-ar.ConnectedPort AND
					bf.ConnectedDeviceID IS NULL
				ORDER BY DeviceID1,PortNumber1,DeviceID2,PortNumber2;";
			foreach($dbh->query($sql) as $row){
				//Compruebo si tengo que añadir esta pareja
				//I check if I have to add this pair of nodes
				if (isset($this->candidates[$row["DeviceID2"]]) 
					&& $this->nodes[$row["DeviceID2"]][$this->candidates[$row["DeviceID2"]]]["weight"]>$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet+$weight_rear
					|| !isset($this->candidates[$row["DeviceID2"]]) && !isset($this->used_candidates[$row["DeviceID2"]])){
					$this->AddNodeToList($row["DeviceID1"],-$row["PortNumber1"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet, $this->DeviceID, $this->PortNumber);
					//Añado directamente el espejo de este puerto
					//I add directly the mirror port of this port
					$this->AddNodeToList($row["DeviceID2"],$row["PortNumber2"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet+$weight_rear, $row["DeviceID1"],-$row["PortNumber1"]);
				}
			}
			
			//busco paneles con puertos libres en la fila actual
			//Look for panels with free ports on actual row
			$this->escribe_log("Look for panels with free ports on actual row (".$cabrow->CabRowID."-'".$cabrow->Name."')");
			$sql="SELECT af.DeviceID AS DeviceID1,
						af.PortNumber AS PortNumber1,
						bf.DeviceID AS DeviceID2,
						bf.PortNumber AS PortNumber2 
				FROM fac_Ports af, fac_Ports ar, fac_Ports bf, fac_Device d, fac_Cabinet c 
				WHERE af.DeviceID=d.DeviceID AND 
					af.DeviceID!=".$this->DeviceID." AND
					d.Cabinet=c.CabinetID AND
					d.Cabinet<>".$cab." AND
					c.CabRowID=".$cabrow->CabRowID." AND 
					af.ConnectedDeviceID IS NULL".$mediaenforce." AND 
					d.DeviceType='Patch Panel' AND
					af.PortNumber>0 AND
					ar.DeviceID=af.DeviceID AND ar.PortNumber=-af.PortNumber AND
					bf.DeviceID=ar.ConnectedDeviceID AND bf.PortNumber=-ar.ConnectedPort AND
					bf.ConnectedDeviceID IS NULL
				ORDER BY DeviceID1,PortNumber1,DeviceID2,PortNumber2;";
			foreach($dbh->query($sql) as $row){
				//Compruebo si tengo que añadir esta pareja
				//I check if I have to add this pair of nodes
				if (isset($this->candidates[$row["DeviceID2"]]) 
					&& $this->nodes[$row["DeviceID2"]][$this->candidates[$row["DeviceID2"]]]["weight"]>$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row+$weight_rear
					|| !isset($this->candidates[$row["DeviceID2"]]) && !isset($this->used_candidates[$row["DeviceID2"]])){
					$this->AddNodeToList($row["DeviceID1"],-$row["PortNumber1"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row,$this->DeviceID, $this->PortNumber);
					//Añado directamente el espejo de este puerto
					//I add directly the mirror port of this port
					$this->AddNodeToList($row["DeviceID2"],$row["PortNumber2"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row+$weight_rear, $row["DeviceID1"],-$row["PortNumber1"]);
				}
			}
		}
		//quito el nodo de la lista de candidatos
		//remove the node from candidates and I include it in used_candidates
		$this->escribe_log("....Candidate DEV=".$this->DeviceID."->PORT=".$this->PortNumber." used");
		unset($this->candidates[$this->DeviceID]);
		$this->used_candidates[$this->DeviceID]=true; //any value
	}
	
	function MakePath () {
		$this->MakeSafe();
		
		//reset PathError
		$this->PathError=0;
		
		//check devices/ports
		$device=new Device();
		$device->DeviceID=$this->devID1;
		if (!$device->GetDevice()){
			$this->PathError=1;  //dev1 does not exist
			return false;
		}
		$devType1=$device->DeviceType;
		if ($device->DeviceType=="Patch Panel"){
			$this->PathError=2;  //dev1 is a Patch Pannel
			return false;
		}
		$port1=new DevicePorts();
		$port1->DeviceID=$this->devID1;
		$port1->PortNumber=$this->port1;
		if (!$port1->getPort()){
			$this->PathError=3;  //dev1,port1 is missing
			return False;
		}
		if ($port1->ConnectedDeviceID>0 && $port1->ConnectedPort>0){
			$this->PathError=4;  //dev1,port1 is connected
			return False;
		}
		$device->DeviceID=$this->devID2;
		if (!$device->GetDevice()){
			$this->PathError=5;  //dev2 does not exist
			return false;
		}
		$devType2=$device->DeviceType;
		if ($device->DeviceType=="Patch Panel"){
			$this->PathError=6;  //dev2 is a Patch Pannel
			return false;
		}
		$port2=new DevicePorts();
		$port2->DeviceID=$this->devID2;
		$port2->PortNumber=$this->port2;
		if (!$port2->getPort()){
			$this->PathError=7;  //dev2,port2 is missing
			return False;
		}
		if ($port2->ConnectedDeviceID>0 && $port2->ConnectedPort>0){
			$this->PathError=8;  //dev2,port2 is connected
			return False;
		}
		
		//get dev2 info
		$this->cab2=$device->GetDeviceCabinetID();  //cab2
		$cabinet=new Cabinet();
		$cabinet->CabinetID=$this->cab2;
		$cabinet->GetCabinet();
		$this->row2=$cabinet->CabRowID;	//row2
		
		//if dev2 is panel protected device (connected to rear connection of a panel)
		$this->espejo2=($port2->ConnectedDeviceID>0 && $port2->ConnectedPort<0);
		
		@unlink('ppath.log');
		$this->escribe_log("**** NEW PATH ****");
		$this->escribe_log("DEV1: ID=".$this->devID1."  PORT=".$this->port1);
		$this->escribe_log("DEV2: ID=".$this->devID2."  PORT=".$this->port2."  CAB_ID=".$this->cab2."  ROW_ID=".$this->row2);
		$this->escribe_log("-------------------");

		//reset Path
		$this->ClearPath();
		//initiate list with device1, port1, weitgh=0, prev_dev=0, prev_port=0
		$this->AddNodeToList($this->devID1, $this->port1, 0, 0, 0);
		
		while ($this->SelectNode()){
			if ($this->DeviceID==$this->devID2){
				$this->escribe_log("Target found. Making the PATH...");
				//make the path
				$i=1;
				while ($this->DeviceID>0) {
					$dev=$this->DeviceID;
					$port=$this->PortNumber;
					$Path[$i]["DeviceID"]=$dev;
					$Path[$i]["PortNumber"]=$port;
					$this->DeviceID=$this->nodes[$dev][$port]["prev_dev"];
					$this->PortNumber=$this->nodes[$dev][$port]["prev_port"];
					$i++;
				}
				for ($j=1;$j<$i;$j++){
					$this->Path[$j]["DeviceID"]=$Path[$i-$j]["DeviceID"];
					$this->Path[$j]["PortNumber"]=$Path[$i-$j]["PortNumber"];
				}
				$this->escribe_log("PATH created.");
				$this->escribe_log("");
				return true;
			}
			$this->UpdateList();
		}
		$this->PathError=9;  //not found
		return false;
	}
	
	function GotoHeadDevice () {
	//Pone el objeto en el primer dispositivo del Path, si no lo es ya
	//Places the object in the first device of Path, if not already
		If (isset($this->Path[1]["DeviceID"]) && $this->Path[1]["DeviceID"]==$this->devID1){
			$this->DeviceID=$this->Path[1]["DeviceID"];
			$this->PortNumber=$this->Path[1]["PortNumber"];
			$this->acti=1;
			return true;
		}else {
			return false;
		} 
	}
	
	function GotoNextDevice () {
	//Pone el objeto con el DeviceID, PortNumber y Front del dispositivo siguiente en el path.
	//Si el dispositivo actual del objeto no estï¿½ conectado a nada, devuelve "false" y el objeto no cambia
		$this->acti++;
		If (isset($this->Path[$this->acti]["DeviceID"])){
			$this->DeviceID=$this->Path[$this->acti]["DeviceID"];
			$this->PortNumber=$this->Path[$this->acti]["PortNumber"];
			return true;
		}else {
			return false;
		} 
		
	}
	
} //END OF PLANNEDPATH


?>
