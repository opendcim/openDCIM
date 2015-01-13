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
	var $SensorTemplateID;
	var $MapX1;
	var $MapY1;
	var $MapX2;
	var $MapY2;
	var $FrontEdge;
	var $Notes;

	function MakeSafe() {
		$this->CabinetID=intval($this->CabinetID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Location=sanitize($this->Location);
		$this->AssignedTo=intval($this->AssignedTo);
		$this->ZoneID=intval($this->ZoneID);
		$this->CabRowID=intval($this->CabRowID);
		$this->CabinetHeight=intval($this->CabinetHeight);
		$this->Model=sanitize($this->Model);
		$this->Keylock=sanitize($this->Keylock);
		$this->MaxKW=floatval($this->MaxKW);
		$this->MaxWeight=intval($this->MaxWeight);
		$this->InstallationDate=date("Y-m-d", strtotime($this->InstallationDate));
		$this->SensorIPAddress=sanitize($this->SensorIPAddress);
		$this->SensorCommunity=sanitize($this->SensorCommunity);
		$this->SensorTemplateID=intval($this->SensorTemplateID);
		$this->MapX1=abs($this->MapX1);
		$this->MapY1=abs($this->MapY1);
		$this->MapX2=abs($this->MapX2);
		$this->MapY2=abs($this->MapY2);
		$this->FrontEdge=in_array($this->FrontEdge, array("Top","Right","Left","Bottom"))?$this->FrontEdge:"Top";
		$this->Notes=sanitize($this->Notes,false);
	}
	
	static function RowToObject($dbRow){
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
		$cab->SensorTemplateID=$dbRow["SensorTemplateID"];
		$cab->MapX1=$dbRow["MapX1"];
		$cab->MapY1=$dbRow["MapY1"];
		$cab->MapX2=$dbRow["MapX2"];
		$cab->MapY2=$dbRow["MapY2"];
		$cab->FrontEdge=$dbRow["FrontEdge"];
		$cab->Notes=$dbRow["Notes"];

		return $cab;
	}
	
	function CreateCabinet(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_Cabinet SET DataCenterID=$this->DataCenterID, 
			Location=\"$this->Location\", AssignedTo=$this->AssignedTo, 
			ZoneID=$this->ZoneID, CabRowID=$this->CabRowID, 
			CabinetHeight=$this->CabinetHeight, Model=\"$this->Model\", 
			Keylock=\"$this->Keylock\", MaxKW=\"$this->MaxKW\", MaxWeight=$this->MaxWeight, 
			InstallationDate=\"$this->InstallationDate\", 
			SensorIPAddress=\"$this->SensorIPAddress\", 
			SensorCommunity=\"$this->SensorCommunity\", 
			SensorTemplateID=$this->SensorTemplateID, MapX1=$this->MapX1, 
			MapY1=$this->MapY1, MapX2=$this->MapX2, MapY2=$this->MapY2, FrontEdge=\"$this->FrontEdge\",
			Notes=\"$this->Notes\";";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("CreateCabinet::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			$this->CabinetID=$dbh->lastInsertID();
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->CabinetID;
	}

	function UpdateCabinet(){
		global $dbh;
		
		$this->MakeSafe();

		$old=new Cabinet();
		$old->CabinetID=$this->CabinetID;
		$old->GetCabinet();

		$sql="UPDATE fac_Cabinet SET DataCenterID=$this->DataCenterID, 
			Location=\"$this->Location\", AssignedTo=$this->AssignedTo, 
			ZoneID=$this->ZoneID, CabRowID=$this->CabRowID, 
			CabinetHeight=$this->CabinetHeight, Model=\"$this->Model\", 
			Keylock=\"$this->Keylock\", MaxKW=$this->MaxKW, MaxWeight=$this->MaxWeight, 
			InstallationDate=\"".date("Y-m-d", strtotime($this->InstallationDate))."\", 
			SensorIPAddress=\"$this->SensorIPAddress\", 
			SensorCommunity=\"$this->SensorCommunity\", 
			SensorTemplateID=$this->SensorTemplateID, 
			MapX1=$this->MapX1, MapY1=$this->MapY1, MapX2=$this->MapX2, MapY2=$this->MapY2, FrontEdge=\"$this->FrontEdge\",
			Notes=\"$this->Notes\" WHERE CabinetID=$this->CabinetID;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("UpdateCabinet::PDO Error: {$info[2]} SQL=$sql" );
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
		return true;
	}

	function GetCabinet(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Cabinet WHERE CabinetID=$this->CabinetID;";
		
		if($cabinetRow=$dbh->query($sql)->fetch()){
			foreach(Cabinet::RowToObject($cabinetRow) as $prop => $value){
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

		$dept=(!is_null($deptid))?" WHERE AssignedTo=".intval($deptid):'';
		$sql="SELECT * FROM fac_Cabinet$dept ORDER BY DataCenterID, Location;";

		foreach($dbh->query($sql) as $cabinetRow){
			$cabinetList[]=Cabinet::RowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function ListCabinetsByDC($limit=false,$limitzone=false){
		global $dbh;
		
		$this->MakeSafe();
		
		$hascoords=($limit)?'AND MapX1!=MapX2 AND MapY1!=MapY2':'';
		$limitzone=($limitzone && $this->ZoneID>0)?" AND ZoneID=$this->ZoneID":'';

		$sql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID $hascoords$limitzone ORDER BY Location;";

		$cabinetList=array();
		foreach($dbh->query($sql) as $cabinetRow){
			$cabinetList[]=Cabinet::RowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function CabinetOccupancy($CabinetID){
		global $dbh;

		$CabinetID=intval($CabinetID);
		
		//$sql="SELECT SUM(Height) AS Occupancy FROM fac_Device WHERE Cabinet=$CabinetID;";
		//JMGA halfdepth height calculation
		$sql = "select sum(if(HalfDepth,Height/2,Height)) as Occupancy from fac_Device where ParentDevice=0 AND Cabinet=$CabinetID";

		if(!$row=$dbh->query($sql)->fetch()){
			$info=$dbh->errorInfo();

			error_log("CabinetOccupancy::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		return $row["Occupancy"];
	}

	static function GetOccupants($CabinetID){
		global $dbh;

		$sql="SELECT Owner FROM fac_Device WHERE Cabinet=".intval($CabinetID)." Group By Owner;";

		$occupants=array();
		foreach($dbh->query($sql) as $row){
			$occupants[]=$row[0];
		}

		return $occupants;
	}

	function GetDCSelectList(){
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
	
	function GetDCSelectListSubmit(){
		global $dbh;

		$sql="SELECT * FROM fac_DataCenter ORDER BY Name;";

		$selectList='<select name="datacenterid" id="datacenterid" onChange="form.submit()">';

		foreach($dbh->query($sql) as $selectRow){
			$selected=($selectRow[ "DataCenterID"]==$this->DataCenterID)?' selected':'';
			$selectList.="<option value={$selectRow["DataCenterID"]}$selected>{$selectRow["Name"]}</option>";
		}

		$selectList.='</select>';

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

	function GetCabinetsByRow($rear=false){
		global $dbh;

		$this->MakeSafe();

		$cabrow=new CabRow();
		$cabrow->CabRowID=$this->CabRowID;

		$sql="SELECT MIN(MapX1) AS MapX1, MAX(MapX2) AS MapX2, MIN(MapY1) AS MapY1, 
			MAX(MapY2) AS MapY2, AVG(MapX1) AS AvgX1, AVG(MapX2) AS AvgX2, COUNT(*) AS 
			CabCount FROM fac_Cabinet WHERE CabRowID=$cabrow->CabRowID AND MapX1>0 
			AND MapX2>0 AND MapY1>0 and MapY2>0;";
		$shape=$dbh->query($sql)->fetch();

		// size of average cabinet
		$sX=$shape["AvgX2"]-$shape["AvgX1"];
		// change in x and y to give overall shape of row
		$cX=$shape["MapX2"]-$shape["MapX1"];
		$cY=$shape["MapY2"]-$shape["MapY1"];

		/*
		 * In rows with more than one cabinet we can determine the layout based on
		 * their size.  The side of a row will be close to the change in x or y while
		 * the front/rear of a row will be equal to the average of the sides 
		 * multiplied by the number of objects in the set
		 *
		 * change = size * number of cabinets
		 */
		$layout=($cX==$sX*$shape["CabCount"] || $cX>$cY)?"Horizontal":"Vertical";
		$order=($layout=="Horizontal")?"MapX1,":"MapY1,";
		$frontedge=$cabrow->GetCabRowFrontEdge($layout);

		// Order first by row layout then by natural sort
		$sql="SELECT * FROM fac_Cabinet WHERE CabRowID=$cabrow->CabRowID ORDER BY $order 
			length(Location), Location ASC;";

		$cabinetList=array();
		foreach($dbh->query($sql) as $cabinetRow){
			$cabinetList[]=Cabinet::RowToObject($cabinetRow);
		}

		if($frontedge=="Right" || $frontedge=="Top"){
			$cabinetList=array_reverse($cabinetList);
		}

		return $cabinetList;
	}

	function GetCabinetsByZone(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Cabinet WHERE ZoneID=$this->ZoneID;";
		
		$cabinetList=array();
		foreach($dbh->query($sql) as $cabinetRow){
			$cabinetList[]=Cabinet::RowToObject($cabinetRow);
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
			if($selectRow["CabinetID"]==$this->CabinetID || People::Current()->canWrite($selectRow["AssignedTo"])){
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
	
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function SearchByCabinetName( $db = null ) {
		global $dbh;
		
		$sql="select * from fac_Cabinet where ucase(Location) like \"%" . transform($this->Location) . "%\" order by Location;";

		$cabinetList=array();

		foreach ( $dbh->query( $sql ) as $cabinetRow ){
			$cabID=$cabinetRow["CabinetID"];
			$cabinetList[$cabID]=Cabinet::RowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function SearchByOwner( $db = null ) {
		global $dbh;
		
		$sql="select * from fac_Cabinet WHERE AssignedTo=".intval($this->AssignedTo)." ORDER BY Location;";

		$cabinetList=array();

		foreach ( $dbh->query( $sql ) as $cabinetRow ) {
			$cabID=$cabinetRow["CabinetID"];
			$cabinetList[$cabID]=Cabinet::RowToObject($cabinetRow);
		}

		return $cabinetList;
	}

	function SearchByCustomTag( $tag=null ) {
		global $dbh;
		
		$sql="SELECT a.* from fac_Cabinet a, fac_CabinetTags b, fac_Tags c WHERE a.CabinetID=b.CabinetID AND b.TagID=c.TagID AND UCASE(c.Name) LIKE UCASE('%".sanitize($tag)."%');";

		$cabinetList=array();

		foreach ( $dbh->query( $sql ) as $cabinetRow ) {
			$cabID=$cabinetRow["CabinetID"];
			$cabinetList[$cabID]=Cabinet::RowToObject($cabinetRow);
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
	
	static function UpdateSensors( $CabinetID = null ) {
		global $dbh;
		global $config;
		
		if ( ! function_exists( "snmpget" ) ) {
			return;
		}
		
		if ( $CabinetID != null ) {
			$sql = sprintf( "select a.CabinetID, a.SensorIPAddress, a.SensorCommunity, b.* from fac_Cabinet a, fac_SensorTemplate b where a.SensorTemplateID=b.TemplateID and a.CabinetID=%d and a.SensorIPAddress>'' and a.SensorTemplateID>0", $CabinetID );
		} else {
			$sql = "select a.CabinetID, a.SensorIPAddress, a.SensorCommunity, b.* from fac_Cabinet a, fac_SensorTemplate b where a.SensorTemplateID=b.TemplateID and a.SensorIPAddress>'' and a.SensorTemplateID>0";
		}
		
		$sensors = $dbh->prepare( "insert into fac_CabinetTemps values (:cabinetid, now(), :temp, :humidity ) on duplicate key update LastRead=now(), Temp=:temp, Humidity=:humidity" );
		
		foreach ( $dbh->query( $sql ) as $row ) {
			if ( $row["SensorCommunity"] == "" ) {
				$Community = $config->ParameterArray["SNMPCommunity"];
			} else {
				$Community = $row["SensorCommunity"];
			}
			
			if ( $row["SNMPVersion"] == "2c" ) {
				@list( $trash, $temp ) = explode( ":", @snmp2_get( $row["SensorIPAddress"], $Community, $row["TemperatureOID"] ) );
				@list( $trash, $humid ) = explode( ":", @snmp2_get( $row["SensorIPAddress"], $Community, $row["HumidityOID"] ) );
			} else {
				@list( $trash, $temp ) = explode( ":", @snmpget( $row["SensorIPAddress"], $Community, $row["TemperatureOID"] ) );
				@list( $trash, $humid ) = explode( ":", @snmpget( $row["SensorIPAddress"], $Community, $row["HumidityOID"] ) );
			}
			
			$temp = preg_replace( "/[^0-9.,+]/", "", $temp );
			$humid = preg_replace( "/[^0-9.'+]/", "", $humid );
			
			if (($row["mUnits"] == "english") && ($config->ParameterArray["mUnits"] == "metric")) {
				$temp = (($temp-32)*5/9);
			} elseif (($row["mUnits"] == "metric") && ($config->ParameterArray["mUnits"] == "english")) {
				$temp = (($temp*9/5)+32);
			}

			if ( $row["TempMultiplier"] != 0 ) {
				$temp *= $row["TempMultiplier"];
			}
			
			if ( $row["HumidityMultiplier"] != 0 ) {
				$humid *= $row["HumidityMultiplier"];
			}
			
			$sensors->execute( array( "cabinetid"=>$row["CabinetID"], "temp"=>$temp, "humidity"=>$humid ) );
		}
	}
}

class CabinetAudit {
	/*	CabinetAudit:	A perpetual audit trail for how often a cabinet has been audited, and by what user.
	*/
	
	var $CabinetID;
	var $UserID;
	var $AuditStamp;
	var $Comments;

	function CertifyAudit( $db = null ) {
		global $dbh;
		
		if($this->Comments){
			$tmpAudit=new CabinetAudit();
			$tmpAudit->CabinetID=$this->CabinetID;
			(class_exists('LogActions'))?LogActions::LogThis($this,$tmpAudit):'';
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
		}

		if ( ! $dbh->exec( $sql ) ) {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}

	function GetLastAudit( $db = null ) {
		global $dbh;
		
		$sql = "select * from fac_GenericLog where ObjectID=\"" . intval( $this->CabinetID ) . "\" and Class=\"CabinetAudit\" order by Time DESC Limit 1";

		if($row=$dbh->query($sql)->fetch()){
			$this->CabinetID=$row["ObjectID"];
			$this->UserID=$row["UserID"];
			$this->AuditStamp=date("M d, Y H:i", strtotime($row["Time"]));

			return true;
		} else {
			// No sense in logging an error for something that's never been done
			return false;
		}
	}
	
	function GetLastAuditByUser( $db = null ) {
		global $dbh;
		
		$sql = "select * from fac_GenericLog where UserID=\"" . addslashes( $this->UserID ) . "\" and Class=\"CabinetAudit\" order by Time DESC Limit 1";

		if ( $row = $dbh->query( $sql )->fetch() ) {
			$this->CabinetID = $row["ObjectID"];
			$this->UserID = $row["UserID"];
			$this->AuditStamp = date( "M d, Y H:i", strtotime( $row["Time"] ) );
		} else {
			$info = $dbh->errorInfo();

			error_log( "PDO Error: " . $info[2] . " SQL=" . $sql );
			return false;
		}
		
		return;
	}
}

class SensorTemplate {
	/* Sensor Template - Information about how to get temperature/humidity from various types of devices */
	
	var $TemplateID;
	var $ManufacturerID;
	var $Name;
	var $SNMPVersion;
	var $TemperatureOID;
	var $HumidityOID;
	var $TempMultiplier;
	var $HumidityMultiplier;
	var $mUnits;
		
	static function getTemplate( $templateID = null ) {
		global $dbh;
		
		if ( $templateID != null ) {
			$sql = sprintf( "select * from fac_SensorTemplate where TemplateID=%d", $templateID );
		} else {
			$sql = "select * from fac_SensorTemplate order by Name ASC";
		}
		
		$tempList = array();
		
		foreach ( $dbh->query( $sql ) as $row ) {
			$n = sizeof ( $tempList );
			$tempList[$n] = new SensorTemplate();
			$tempList[$n]->TemplateID = $row["TemplateID"];
			$tempList[$n]->ManufacturerID = $row["ManufacturerID"];
			$tempList[$n]->Name = $row["Name"];
			$tempList[$n]->SNMPVersion = $row["SNMPVersion"];
			$tempList[$n]->TemperatureOID = $row["TemperatureOID"];
			$tempList[$n]->HumidityOID = $row["HumidityOID"];
			$tempList[$n]->TempMultiplier = $row["TempMultiplier"];
			$tempList[$n]->HumidityMultiplier = $row["HumidityMultiplier"];
			$tempList[$n]->mUnits = $row["mUnits"];
		}
		
		if ( $templateID != null ) {
			return array_pop($tempList);
		} else {
			return $tempList;
		}
	}
	
	function CreateTemplate() {
		global $dbh;
		
		$sql = $dbh->prepare( "insert into fac_SensorTemplate values ( 0, :ManufacturerID, :Name, :SNMPVersion, :TemperatureOID, :HumidityOID, :TempMultiplier, :HumidityMultiplier, :mUnits )" );
		
		$args = array( 	"ManufacturerID" => $this->ManufacturerID,
						"Name" => $this->Name,
						"SNMPVersion" => $this->SNMPVersion,
						"TemperatureOID" => $this->TemperatureOID,
						"HumidityOID" => $this->HumidityOID,
						"TempMultiplier" => $this->TempMultiplier,
						"HumidityMultiplier" => $this->HumidityMultiplier,
						"mUnits" => $this->mUnits );
		
		$sql->execute( $args );
		
		$this->TemplateID = $dbh->lastInsertId();
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
	}
	
	function UpdateTemplate() {
		global $dbh;
		
		$old=SensorTemplate::getTemplate($this->TemplateID);

		$sql = $dbh->prepare( "update fac_SensorTemplate set ManufacturerID=:ManufacturerID, Name=:Name, SNMPVersion=:SNMPVersion, TemperatureOID=:TemperatureOID, HumidityOID=:HumidityOID, TempMultiplier=:TempMultiplier, HumidityMultiplier=:HumidityMultiplier, mUnits=:mUnits where TemplateID=:TemplateID" );
		
		$args = array( 	"ManufacturerID" => $this->ManufacturerID,
						"Name" => $this->Name,
						"SNMPVersion" => $this->SNMPVersion,
						"TemperatureOID" => $this->TemperatureOID,
						"HumidityOID" => $this->HumidityOID,
						"TempMultiplier" => $this->TempMultiplier,
						"HumidityMultiplier" => $this->HumidityMultiplier,
						"mUnits" => $this->mUnits,
						"TemplateID" => $this->TemplateID );
		
		$sql->execute( $args );
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
	}
	
	function DeleteTemplate() {
		global $dbh;
		
		// Set any sensors using this template back to the default "no template" value
		$sql = "update fac_Cabinet set SensorTemplateID=0 where SensorTemplateID=" . intval( $this->TemplateID );
		$dbh->exec( $sql );
		
		// Now it is "safe" to delete the record as it will leave no orphans
		$sql = "delete from fac_SensorTemplate where TemplateID=" . intval( $this->TemplateID );
		$dbh->exec( $sql );

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
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
}

class ColorCoding {
	var $ColorID;
	var $Name;
	var $DefaultNote;
	
	function CreateCode() {
		global $dbh;
		
		$sql="INSERT INTO fac_ColorCoding SET Name=\"".sanitize($this->Name)."\", 
			DefaultNote=\"".sanitize($this->DefaultNote)."\"";
		
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
		
		$sql="UPDATE fac_ColorCoding SET Name=\"".sanitize($this->Name)."\", 
			DefaultNote=\"".sanitize($this->DefaultNote)."\" WHERE ColorID=".intval($this->ColorID).";";
		
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
	
	function GetCodeByName() {
		global $dbh;
		
		$sql="SELECT * FROM fac_ColorCoding WHERE Name='".transform($this->Name)."';";

		if($row=$dbh->query($sql)->fetch()){
			$this->ColorID=$row["ColorID"];
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
	var $HalfDepth;
	var $BackSide;
	var $AuditStamp;
	var $CustomValues;

	function MakeSafe() {
		if ( ! is_object( $this ) ) {
			// If called from a static procedure, $this is not a valid object and the routine will throw an error
			return;
		}
		
		//Keep weird values out of DeviceType
		$validdevicetypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU');

		$this->DeviceID=intval($this->DeviceID);
		$this->Label=sanitize($this->Label);
		$this->SerialNo=sanitize($this->SerialNo);
		$this->AssetTag=sanitize($this->AssetTag);
		$this->PrimaryIP=sanitize($this->PrimaryIP);
		$this->SNMPCommunity=sanitize($this->SNMPCommunity);
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
		$this->MfgDate=sanitize($this->MfgDate);
		$this->InstallDate=sanitize($this->InstallDate);
		$this->WarrantyCo=sanitize($this->WarrantyCo);
		$this->WarrantyExpire=sanitize($this->WarrantyExpire);
		$this->Notes=sanitize($this->Notes,false);
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

	static function RowToObject($dbRow,$filterrights=true){
		/*
		 * Generic function that will take any row returned from the fac_Devices
		 * table and convert it to an object for use in array or other
		 *
		 * Pass false to filterrights when you don't need to check for rights for 
		 * whatever reason.
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
		$dev->AuditStamp=$dbRow["AuditStamp"];
		$dev->GetCustomValues();
		
		$dev->MakeDisplay();
		if($filterrights){
			$dev->FilterRights();
		}

		return $dev;
	}

	private function FilterRights(){
		$cab=new Cabinet();
		$cab->CabinetID=$this->Cabinet;

		$this->Rights='None';
		$person=People::Current();
		if($person->canRead($this->Owner)){$this->Rights="Read";}
		if($person->canWrite($this->Owner)){$this->Rights="Write";} // write by device
		if($this->ParentDevice>0){ // this is a child device of a chassis
			$par=new Device();
			$par->DeviceID=$this->ParentDevice;
			$par->GetDevice();
			$this->Rights=($par->Rights=="Write")?"Write":$this->Rights;
		}elseif($cab->GetCabinet()){
			if($cab->AssignedTo!=0 && $person->canWrite($cab->AssignedTo)){$this->Rights="Write";} // write because the cabinet is assigned
		}
		if($person->SiteAdmin && $this->DeviceType=='Patch Panel'){$this->Rights="Write";} // admin override of rights for patch panels

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
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
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
		PowerPorts::createPorts($this->DeviceID);

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
					$olddev=new Device();
					$olddev->DeviceID=$this->DeviceID;
					$olddev->GetDevice();
					$this->CreateDevice();
					$olddev->CopyDeviceCustomValues($this);
				}else{
					return false;
				}
			}
		}else{
			// Set the position in the current cabinet above the usable space. This will
			// make the user change the position before they can update it.
			$cab=new Cabinet();
			$cab->CabinetID=$this->Cabinet;
			$cab->GetCabinet();
			$this->Position=$cab->CabinetHeight+1;

			// If this is a chassis device then check for children to cloned BEFORE we change the deviceid
			if($this->DeviceType=="Chassis"){
				$childList=$this->GetDeviceChildren();
			}	

			$olddev=new Device();
			$olddev->DeviceID=$this->DeviceID;
			$olddev->GetDevice();

			// And finally create a new device based on the exact same info
			$this->CreateDevice();
			$olddev->CopyDeviceCustomValues($this);

			// If this is a chassis device and children are present clone them
			if(isset($childList)){
				foreach($childList as $child){
					$child->CopyDevice($this->DeviceID);
				}
			}

		}
		return true;
	}

	function CopyDeviceCustomValues($new) {
		// in this context, "$this" is the old device we are copying from, "$new" is where we are copying to
		global $dbh;
		if($this->GetDevice() && $new->GetDevice()) {
			$sql="INSERT INTO fac_DeviceCustomValue(DeviceID, AttributeID, Value) 
				SELECT $new->DeviceID, dcv.AttributeID, dcv.Value FROM fac_DeviceCustomValue dcv WHERE dcv.DeviceID=$this->DeviceID;";

			if(!$dbh->query($sql)){
				$info=$dbh->errorInfo();
				error_log("CopyDeviceCustomValues::PDO Error: {$info[2]} SQL=$sql");
				return false;
			}
			return true;
		} else { return false; }
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
		$this->Position=$this->GetDeviceDCID();
		$this->UpdateDevice();
		
		// While the child devices will automatically get moved to storage as part of the UpdateDevice() call above, it won't sever their network connections
		// Multilevel chassis
		if ($this->ChassisSlots>0 || $this->RearChassisSlots>0){
			$descList=$this->GetDeviceDescendants();
			foreach($descList as $child){
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
			if(!People::Current()->canWrite($cab->AssignedTo)){
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

		// Device has been changed to be a CDU from something else so we need to create the extra records
		if($this->DeviceType=="CDU" && $tmpDev->DeviceType!=$this->DeviceType){
			$pdu=new PowerDistribution();
			$pdu->CreatePDU($dev->DeviceID);
		// Device was changed from CDU to something else, clean up the extra shit
		}elseif($tmpDev->DeviceType=="CDU" && $tmpDev->DeviceType!=$this->DeviceType){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$this->DeviceID;
			$pdu->DeletePDU();
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
					$p->Label=__("Port").($n+1);
					$p->PortNumber=$n+1;
					$p->createPort();
					if($this->DeviceType=='Patch Panel'){
						$p->PortNumber=$p->PortNumber*-1;
						$p->createPort();
					}
				}

			}
		}

		// If we made it to a device update and the number of power ports available don't match the device, just fix it.
		if($tmpDev->PowerSupplyCount!=$this->PowerSupplyCount){
			if($tmpDev->PowerSupplyCount>$this->PowerSupplyCount){ // old device has more ports
				for($n=$this->PowerSupplyCount; $n<$tmpDev->PowerSupplyCount; $n++){
					$p=new PowerPorts();
					$p->DeviceID=$this->DeviceID;
					$p->PortNumber=$n+1;
					$p->removePort();
				}
			}else{ // new devices has more ports
				for($n=$tmpDev->PowerSupplyCount; $n<$this->PowerSupplyCount; ++$n){
					$p=new PowerPorts;
					$p->DeviceID=$this->DeviceID;
					$p->Label=__("Power Connection")." ".($n+1);
					$p->PortNumber=$n+1;
					$p->createPort();
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
		
		//Update children, if necesary
		if ($this->ChassisSlots>0 || $this->RearChassisSlots>0){
				$this->SetChildDevicesCabinet();
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this,$tmpDev):'';
		return true;
	}

	function Audit() {
		global $dbh;

		// Make sure we're not trying to decommission a device that doesn't exist
		if(!$this->GetDevice()){
			return false;
		}

		$tmpDev=new Device();
		$tmpDev->DeviceID=$this->DeviceID;
		$tmpDev->GetDevice();

		$sql="UPDATE fac_Device SET AuditStamp=NOW() WHERE DeviceID=$this->DeviceID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("Device:Audit::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		
		$this->GetDevice();

		(class_exists('LogActions'))?LogActions::LogThis($this,$tmpDev):'';
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
			foreach(Device::RowToObject($devRow) as $prop => $value){
				$this->$prop=$value;
			}

			return true;
		}else{
			return false;
		}
	}
	
	function GetDeviceList( $datacenterid=null ) {
		if ( $datacenterid == null ) {
			$dcLimit = "";
		} else {
			$dcLimit = "and b.DataCenterID=" . $datacenterid;
		}
		
		$sql = "select a.* from fac_Device a, fac_Cabinet b where a.Cabinet=b.CabinetID $dcLimit order by b.DataCenterID ASC, Label ASC";
		
		$deviceList = array();
		foreach ( $this->query( $sql ) as $deviceRow ) {
			$deviceList[]=Device::RowToObject( $deviceRow );
		}
		
		return $deviceList;
	}	

	static function GetDeviceByID($DeviceID){
		$dev=New Device();
		$dev->DeviceID=$DeviceID;
		$dev->GetDevice();
		return $dev;
	}

	static function GetDevicesByTemplate($templateID) {
		global $dbh;
		
		$sql = "select * from fac_Device where TemplateID='" . intval( $templateID ) . "' order by Label ASC";
		
		$deviceList = array();
		foreach ( $dbh->query( $sql ) as $deviceRow ) {
			$deviceList[]=Device::RowToObject( $deviceRow );
		}
		
		return $deviceList;
	}
	
	static function GetSwitchesToReport() {
		global $dbh;
		global $config;
		
		// No, Wilbur, these are not identical SQL statement except for the tag.  Please don't combine them, again.
		if ( $config->ParameterArray["NetworkCapacityReportOptIn"] == "OptIn") {
			$sql="SELECT * FROM fac_Device a, fac_Cabinet b WHERE a.Cabinet=b.CabinetID 
				AND DeviceType=\"Switch\" AND DeviceID IN (SELECT DeviceID FROM 
				fac_DeviceTags WHERE TagID IN (SELECT TagID FROM fac_Tags WHERE 
				Name=\"Report\")) ORDER BY b.DataCenterID ASC, b.Location ASC, Label ASC;";
		} else {
			$sql="SELECT * FROM fac_Device a, fac_Cabinet b WHERE a.Cabinet=b.CabinetID 
				AND DeviceType=\"Switch\" AND DeviceID NOT IN (SELECT DeviceID FROM 
				fac_DeviceTags WHERE TagID IN (SELECT TagID FROM fac_Tags WHERE 
				Name=\"NoReport\")) ORDER BY b.DataCenterID ASC, b.Location ASC, Label ASC;";
		}

		$deviceList=array();
		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}
	
	function GetDevicesbyAge($days=7){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE DATEDIFF(CURDATE(),InstallDate)<=".
			intval($days)." ORDER BY InstallDate ASC;";
		
		$deviceList=array();
		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}
		
	function GetDeviceChildren() {
		global $dbh;
	
		$this->MakeSafe();
	

		// $sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY ChassisSlots, Position ASC;";
		// JMGA
		$sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY BackSide, Position ASC;";

		$childList = array();

		foreach($dbh->query($sql) as $row){
			$childList[]=Device::RowToObject($row);
		}
		
		return $childList;
	}
	
  function GetDeviceDescendants() {
		global $dbh;
		
		$dev=New Device();
	
		$this->MakeSafe();
	

		$sql="SELECT * FROM fac_Device WHERE ParentDevice=$this->DeviceID ORDER BY BackSide, Position ASC;";

		$descList = array();
		$descList2 = array();

		foreach($dbh->query($sql) as $row){
			$dev=Device::RowToObject($row);
			$descList[]=$dev;
			if ($dev->ChassisSlots>0 || $dev->RearChassisSlots>0){
				$descList2=$dev->GetDeviceDescendants();
				$descList=array_merge($descList,$descList2);
			}
		}
		
		return $descList;
	}
	
	function GetParentDevices(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ChassisSlots>0 OR RearChassisSlots>0 ORDER BY Label ASC;";

		$parentList=array();
		foreach($dbh->query($sql) as $row){
			// Assigning here will trigger the FilterRights method and check the cabinet rights
			$temp=Device::RowToObject($row);
			if($temp->DeviceID==$this->ParentDevice || $temp->Rights=="Write"){
				$parentList[]=$temp;
			}
		}
		
		return $parentList;
	}
	
	static function GetReservationsByDate( $Days = null ) {
		global $dbh;

		// Since we are only concerned with physical space being occupied in terms of capacity, don't worry about child devices
		if ( $Days == null ) {
			$sql = "select * from fac_Device where Reservation=true order by InstallDate ASC";
		} else {
			$sql = sprintf( "select * from fac_Device where Reservation=true and InstallDate<=(CURDATE()+%d) ORDER BY InstallDate ASC", $Days );
		}
		
		$devList = array();

		foreach($dbh->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		return $devList;
	}
	
	static function GetReservationsByDC( $dc ) {
		global $dbh;

		// Since we are only concerned with physical space being occupied in terms of capacity, don't worry about child devices
		$sql = sprintf( "select a.* from fac_Device a, fac_Cabinet b where a.Cabinet=b.CabinetID and b.DataCenterID=%d and Reservation=true order by a.InstallDate ASC, a.Cabinet ASC", $dc );
		
		$devList = array();

		foreach($dbh->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		return $devList;
	}
	
	static function GetReservationsByOwner( $Owner ) {
		global $dbh;

		// Since we are only concerned with physical space being occupied in terms of capacity, don't worry about child devices
		$sql = sprintf( "select * from fac_Device where Owner=%d and Reservation=true order by InstallDate ASC, Cabinet ASC", $Owner );
		
		$devList = array();

		foreach($dbh->query($sql) as $row){
			$devList[]=Device::RowToObject($row);
		}

		return $devList;
	}
	
	
	function WhosYourDaddy() {
		global $dbh;
	
		$dev = new Device();
		
		if ( $this->ParentDevice == 0 ) {
			return $dev;
		} else {
			$dev->DeviceID = $this->ParentDevice;
			$dev->GetDevice();
			return $dev;
		}
	}

	function ViewDevicesByCabinet($includechildren=false){
		global $dbh;

		$this->MakeSafe();

		if($includechildren){
			$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet 
				ORDER BY Position DESC;";
		}elseif ($this->Cabinet<0){
			//StorageRoom
			if ($this->Position>0)
				$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet AND Position=$this->Position 
					ORDER BY Position DESC;";
			else
				$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet 
					ORDER BY Position DESC;";
		}else{
			$sql="SELECT * FROM fac_Device WHERE Cabinet=$this->Cabinet AND ParentDevice=0 
				ORDER BY Position DESC;";
		}
		
		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}
	
	static function GetPatchPanels(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE DeviceType='Patch Panel' ORDER BY Label ASC;";
		
		$panelList=array();

		foreach($dbh->query($sql) as $row){
			$panelList[$row["DeviceID"]]=Device::RowToObject($row);
		}
		
		return $panelList;
	}

	function DeleteDevice(){
		global $dbh;

		// Can't delete something that doesn't exist
		if(!$this->GetDevice()){
			return false;
		}
	
		// First, see if this is a chassis that has children, if so, delete all of the children first
		if($this->ChassisSlots >0){
			$childList=$this->GetDeviceChildren();
			
			foreach($childList as $tmpDev){
				$tmpDev->DeleteDevice();
			}
		}

		// If this is a CDU then remove it from the other table
		if($this->DeviceType=="CDU"){
			$pdu=new PowerDistribution();
			$pdu->PDUID=$this->DeviceID;
			$pdu->DeletePDU();
		}
	
		// Delete all network connections first
		DevicePorts::removePorts($this->DeviceID);
		
		// Delete power connections next
		PowerPorts::removePorts($this->DeviceID);

		// Remove custom values
		$this->DeleteCustomValues();

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
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebyIP(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE PrimaryIP LIKE \"%$this->PrimaryIP%\" ORDER BY Label;";

		$deviceList = array();
		foreach($this->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
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
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

  function GetESXDevices() {
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ESX=TRUE ORDER BY DeviceID;";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){ 
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebySerialNo(){
		global $dbh;

		$this->MakeSafe();

		$sql="SELECT * FROM fac_Device WHERE SerialNo LIKE \"%$this->SerialNo%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}

		return $deviceList;
	}

	function SearchDevicebyAssetTag(){
		global $dbh;

		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Device WHERE AssetTag LIKE \"%$this->AssetTag%\" ORDER BY Label;";

		$deviceList=array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
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
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
		}
		
		return $deviceList;
	}

	function SearchByCustomAttribute($searchTerm=null){
		global $dbh;
		
		//
		//Build a somewhat ugly SQL expression in order to do 
		//semi-complicated attribute searches.  All attributes are
		//logically AND'ed togther.  Thus, if you search for attributes 
		//'foo' and 'bar' and '!quux', the results should be only 
		//those systems with both 'foo' and 'bar' attributes while 
		//excluding those with 'quux'.
		//

		// Basic start of the query.
		$sql = "SELECT DISTINCT a.* FROM fac_Device a, fac_DeviceCustomValue b WHERE a.DeviceID=b.DeviceID ";

		//split the searchTerm if needed, and strip whitespace
		//note that search terms can contain spaces, so we have to use
		//something else in the search string (commas seem logical)
		$terms = explode(",", $searchTerm);
		$terms = array_map("trim", $terms);

		//Two arrays, one of terms we want, and one of those we don't want.
		$want_terms = array();
		$not_want_terms = array();

		foreach ( $terms as $t ) {
			//If the term starts with a "!" character, we want to 
			//specifically exclude it from the search.
			if (strpos($t, '!') !== false ) {
				$t=preg_replace('/^!/', '', $t,1);	//remove the leading "!" from the term
			$not_want_terms[].= $t;
			} else {
				$want_terms[] .= $t;
			}
		}
		/*
		error_log(join(',',$want_terms));
		error_log(join(',',$not_want_terms));
		*/
		$num_want_terms = count($want_terms);
		if (count($want_terms)) {
			// This builds the part of the query that looks for all terms we want.

			$sql .= " AND a.DeviceID IN ( SELECT DeviceID from fac_DeviceCustomValue WHERE ";
			// Loop over the terms  we want.
			$want_sql = sprintf("UCASE(Value) LIKE UCASE('%%%s%%')", array_shift($want_terms));
			foreach ($want_terms as $t) {
				$want_sql .= sprintf(" OR UCASE(Value) LIKE UCASE('%%%s%%')", $t);
			}

			$sql .= " $want_sql ) "; //extra parens for closing sub-select

		}

		//only include this section if we have negative terms
		if (count($not_want_terms)) {

			$sql .= " AND a.DeviceID NOT IN (SELECT DeviceID from fac_DeviceCustomValue WHERE ";

			$not_want_sql = sprintf("UCASE(Value) LIKE UCASE('%%%s%%')", array_shift($not_want_terms));
			foreach ($not_want_terms as $t) {
				$not_want_sql .= sprintf(" OR UCASE(Value) LIKE UCASE('%%%s%%')", $t);
			}
			$sql .= "  $not_want_sql ) "; //extra parens to close sub-select
		}

		// This bit of magic filters out the results that don't match enough terms.
		$sql .= "GROUP BY a.DeviceID HAVING COUNT(b.AttributeID) >= $num_want_terms";

		$deviceList = array();

		foreach($dbh->query($sql) as $deviceRow){
			$deviceList[$deviceRow["DeviceID"]]=Device::RowToObject($deviceRow);
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
		$tmpDev->DeviceID = $this->GetRootDeviceID();
		$tmpDev->GetDevice();
		return $tmpDev->Cabinet;	
	}
	
	function GetDeviceDCID(){
		$rootDev = new Device();
		$rootDev->DeviceID = $this->GetRootDeviceID();
		$rootDev->GetDevice();
		if ($rootDev->Cabinet>0){
			$cab = new Cabinet();
			$cab->CabinetID = $rootDev->Cabinet;
			$cab->GetCabinet();
			return $cab->DataCenterID;
		}else{
			//root device is in StorageRomm. DataCenterID is in his Position field.
			return $rootDev->Position;
		}
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
		// Make sure we read the device from the db and didn't just get the device ID
		if(!isset($this->Rights)){
			if(!$this->GetDevice()){
				return 0;
			}
		}

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
		if($this->ChassisSlots >0 || $this->RearChassisSlots >0){
			$childList=$this->GetDeviceChildren();
			foreach($childList as $tmpDev){
				$TotalPower+=$tmpDev->GetDeviceTotalPower();
			}
		}
		return $TotalPower;	
	}

	function GetDeviceTotalWeight(){
		// Make sure we read the device from the db and didn't just get the device ID
		if(!isset($this->Rights)){
			if(!$this->GetDevice()){
				return 0;
			}
		}
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
		if($this->ChassisSlots >0 || $this->RearChassisSlots >0){
			$childList = $this->GetDeviceChildren();
			foreach ( $childList as $tmpDev ) {
				$TotalWeight+=$tmpDev->GetDeviceTotalWeight();
			}
		}
		return $TotalWeight;	
	}


	function GetChildDevicePicture($parentDetails, $rear=false){
		/*
		 * The following section will make a few assumptions
		 * - All dimensions will be given back as a percentage of the whole for scalability
		 * -- Labels will be the exception to that, we're just going to assign them values
		 * - Child devices will only have one face, front
		 * -- This makes the pictures on the templates easier to manage
		 * --- Children of an HTRAY or VTRAY will be treated as any other device with a front
		 *		and a rear image.  This makes this just stupidly complicated but has to be done
		 * -- Child devices defined with rear slots will have the rear slots ignored
		 * --- This logic needs to be applied to the functions that figure power usage and weight
		 *		so we don't end up with phantom sources
		 * - Child devices shouldn't need to conform to the 1.75:19 ratio we use for devices 
		 *		directly in a cabinet they will target the slot that they are inside
		 */
		$resp="";
		
		$templ=new DeviceTemplate();
		$templ->TemplateID=$this->TemplateID;
		$templ->GetTemplateByID();
		
		$parentDev=$parentDetails->parentDev;
		$parentTempl=$parentDetails->parentTempl;

		// We'll only consider checking a rear image on a child if it is sitting on a shelf
		if(($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY') && $rear){
			$picturefile="pictures/$templ->RearPictureFile";
		}else{
			$picturefile="pictures/$templ->FrontPictureFile";
		}
		if (!file_exists($picturefile)){
			$picturefile="pictures/P_ERROR.png";
		}
		@list($width, $height)=getimagesize($picturefile);
		// In the event of read error this will rotate a horizontal text label
		$hor_blade=($width=="" || $height=="")?true:($width>$height);

		// We only need these numbers in the event that we have a nested device
		// and need to scale the coordinates based off the original image size
		$kidsHavingKids=new stdClass();
		$kidsHavingKids->Height=$height;
		$kidsHavingKids->Width=$width;

		$slot=new Slot();
		$slotOK=false;

		//get slot from DB
		$slot->TemplateID=$parentDev->TemplateID;
		$slot->Position=$this->Position;
		$slot->BackSide=$this->BackSide;
		if(($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY') || $slot->GetSlot()){
			// If we're dealing with a shelf mimic what GetSlot() would have done for our fake slot
			if($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY'){
				$imageratio=($hor_blade || (!$hor_blade && $parentTempl->Model=='HTRAY'))?($width/$height):($height/$width);
				$slot->W=($parentTempl->Model=='HTRAY')?$parentDetails->targetWidth/$parentDev->ChassisSlots:$parentDetails->targetWidth;
				$slot->H=($parentTempl->Model=='HTRAY')?$parentDetails->targetHeight:$parentDetails->targetHeight/$parentDev->ChassisSlots;
				$slot->X=($parentTempl->Model=='HTRAY')?($rear)?($parentDev->ChassisSlots-$this->Position-$this->Height+1)*$slot->W:($slot->Position-1)*$slot->W:0;
				$slot->Y=($parentTempl->Model=='HTRAY')?0:$parentDetails->targetHeight-$parentDetails->targetHeight/$parentDev->ChassisSlots*($this->Position+$this->Height-1);

				// Enlarge the slot if needed
				$slot->H=($parentTempl->Model=='HTRAY')?$parentDetails->targetHeight:$parentDetails->targetHeight/$parentDev->ChassisSlots*$this->Height;
				$slot->W=($parentTempl->Model=='HTRAY')?$parentDetails->targetWidth/$parentDev->ChassisSlots*$this->Height:$slot->H*$imageratio;

				// To center the devices in the slot we first needed to know the width figured just above
				$slot->X=($parentTempl->Model=='VTRAY')?($parentDetails->targetWidth-$slot->W)/2:$slot->X;

				// This covers the event that an image scaled properly will be too wide for the slot.
				// Recalculate all the things!  Shelves are stupid.
				if($parentTempl->Model=='VTRAY' && $slot->W>$parentDetails->targetWidth){
					$originalH=$slot->H;
					$slot->W=$parentDetails->targetWidth;
					$slot->H=$slot->W/$imageratio;
					$slot->X=0;
					$slot->Y=$originalH-$slot->H;
				}
				if($parentTempl->Model=='HTRAY' && $slot->W>$slot->H*$imageratio){
					$originalW=$slot->W;
					$originalX=$slot->X;
					$slot->W=$slot->H*$imageratio;
					$slot->X=($rear)?$originalX+($originalW-$slot->W):$slot->X;
				}elseif($parentTempl->Model=='HTRAY' && $slot->H>$slot->W*$this->Height/$imageratio){
					$originalH=$slot->H;
					$slot->H=($hor_blade)?$slot->W*$imageratio:$slot->W/$imageratio;
					$slot->Y=$originalH-$slot->H;
				}
				// Reset the zoome on the parent to 1 just for trays
				$parentDetails->zoomX=1;
				$parentDetails->zoomY=1;
			}

			// Check for slot orientation before we possibly modify it via height
			$hor_slot=($slot->W>$slot->H);

			// We dealt with the slot sizing above for trays this will bypass the next bit
			if($parentTempl->Model=='HTRAY' || $parentTempl->Model=='VTRAY'){$slotOK=true;$this->Height=0;}

			// This will prevent the freak occurance of a child device with a 0 height
			if($this->Height>=1){
				// If height==1 then just accept the defined slot as is
				if($this->Height>1){
					//get last slot
					$lslot=new Slot();
					$lslot->TemplateID=$slot->TemplateID;
					$lslot->Position=$slot->Position+$this->Height-1;
					// If the height extends past the defined slots then just get the last slot
					if($lslot->Position>(($slot->BackSide)?$parentDev->RearChassisSlots:$parentDev->ChassisSlots)){
						$lslot->Position=($slot->BackSide)?$parentDev->RearChassisSlots:$parentDev->ChassisSlots;
					}
					$lslot->BackSide=$slot->BackSide;
					if($lslot->GetSlot()){
						//calculate total size
						$xmin=min($slot->X, $lslot->X);
						$ymin=min($slot->Y, $lslot->Y);
						$xmax=max($slot->X+$slot->W, $lslot->X+$lslot->W);
						$ymax=max($slot->Y+$slot->H, $lslot->Y+$lslot->H);

						//put new size in $slot
						$slot->X=$xmin;
						$slot->Y=$ymin;
						$slot->W=$xmax-$xmin;
						$slot->H=$ymax-$ymin;
					}else{
						// Last slot isn't defined so just error out
						break;
					}
				}
				$slotOK=true;
			}
		}

		if ($slotOK){
			// Determine if the element needs to be rotated or not
			// This only evaluates if we have a horizontal image in a vertical slot
			$rotar=(!$hor_slot && $hor_blade)?"rotar_d":"";

			// Scale the slot to fit the forced aspect ratio
			$zoomX=$parentDetails->zoomX;
			$zoomY=$parentDetails->zoomY;
			$slot->X=$slot->X*$zoomX;
			$slot->Y=$slot->Y*$zoomY;
			$slot->W=$slot->W*$zoomX;
			$slot->H=$slot->H*$zoomY;
			
			if($rotar){
				$left=$slot->X-abs($slot->W-$slot->H)/2;
				$top=$slot->Y+abs($slot->W-$slot->H)/2;
				$height=$slot->W;
				$width=$slot->H;
			}else{
				$left=$slot->X;
				$top=$slot->Y;
				$height=$slot->H;
				$width=$slot->W;
			}
			$left=intval(round($left));$top=intval(round($top));
			$height=intval(round($height));$width=intval(round($width));

			// If they have rights to the device then make the picture clickable
			$clickable=($this->Rights!="None")?"\t\t\t<a href=\"devices.php?deviceid=$this->DeviceID\">\n":"";
			$clickableend=($this->Rights!="None")?"\t\t\t</a>\n":"";
			
			// Add in flags for missing ownership
			// Device pictures are set on the template so always assume template has been set
			$flags=($this->Owner==0)?'(O)&nbsp;':'';
			$flags=($this->TemplateID==0)?$flags.'(T)&nbsp;':$flags;
			$flags=($flags!='')?'<span class="hlight">'.$flags.'</span>':'';

			$label="";
			$resp.="\t\t<div class=\"dept$this->Owner $rotar\" style=\"left: ".round($left/$parentDetails->targetWidth*100,2)."%; top: ".round($top/$parentDetails->targetHeight*100,2)."%; width: ".round($width/$parentDetails->targetWidth*100,2)."%; height:".round($height/$parentDetails->targetHeight*100,2)."%;\">\n$clickable";
//			if(($templ->FrontPictureFile!="" && !$rear) || ($templ->RearPictureFile!="" && $rear)){
			if($picturefile!='pictures/'){
				// IMAGE
				// this rotate should only happen for a horizontal slot with a vertical image
				$rotateimage=($hor_slot && !$hor_blade)?" class=\"rotar_d rlt\"  style=\"height: ".round($width/$height*100,2)."%; left: 100%; width: ".round($height/$width*100,2)."%; top: 0; position: absolute;\"":"";
				$resp.="\t\t\t\t<img data-deviceid=$this->DeviceID src=\"$picturefile\"$rotateimage alt=\"$this->Label\">\n";
				
				// LABEL FOR IMAGE
				if($hor_slot || $rotar && !$hor_slot){
					$label="\t\t\t<div class=\"label\" style=\"line-height:".$height."px; height:".$height."px;".(($height*0.8<13)?" font-size: ".intval($height*0.8)."px;":"")."\">";
				}else{
					// This is a vertical slot with a vertical picture so we have to rotate the label
					$label="\t\t\t<div class=\"rotar_d rlt label\" style=\"top: calc(".$height."px * 0.05); left: ".$width."px; width: calc(".$height."px * 0.9); line-height:".$width."px; height:".$width."px;".(($width*0.8<13)?" font-size: ".intval($width*0.8)."px; ":"")."\">";
				}
				$label.="<div>$flags$this->Label".(($rear)?" (".__("Rear").")":"")."</div></div>\n";
			}else{
				//LABEL for child device without image - Always show
				$resp.="\t\t\t\t<div class=\"label\" data-deviceid=$this->DeviceID style='height: ".$height."px; line-height:".$height."px; ".(($height*0.8<13)?" font-size: ".intval($height*0.8)."px;":"")."'>";
				$resp.="<div>$flags$this->Label".(($rear)?" (".__("Rear").")":"")."</div></div>\n";
			}
			$resp.=$clickableend.$label;

// If the label on a nested chassis device proves to be a pita remove the label
// above and uncomment the following if
// if($this->ChassisSlots<4){$resp.=$label;}

			if($this->ChassisSlots >0){
				$kidsHavingKids->targetWidth=$width;
				$kidsHavingKids->targetHeight=$height;
				$kidsHavingKids->zoomX=$width/$kidsHavingKids->Width;
				$kidsHavingKids->zoomY=$height/$kidsHavingKids->Height;
				$kidsHavingKids->parentDev=$this;
				$kidsHavingKids->parentTempl=$templ;
				//multichassis
				$childList=$this->GetDeviceChildren();
				foreach($childList as $tmpDev){
					if ((!$tmpDev->BackSide && !$rear) || ($tmpDev->BackSide && $rear)){
						$resp.=$tmpDev->GetChildDevicePicture($kidsHavingKids,$rear);
					}
				}
			}
			$resp.="\t\t</div>\n";
		}
		return $resp;
	}
	function GetDevicePicture($rear=false,$targetWidth=220,$nolinks=false){
		// Just in case
		$targetWidth=($targetWidth==0)?220:$targetWidth;
		$rear=($rear==true || $rear==false)?$rear:true;
		$nolinks=($nolinks==true || $nolinks==false)?$nolinks:false;

		$templ=new DeviceTemplate();
		$templ->TemplateID=$this->TemplateID;
		$templ->GetTemplateByID();
		$resp="";

		if(($templ->FrontPictureFile!="" && !$rear) || ($templ->RearPictureFile!="" && $rear)){
			$picturefile="pictures/";
			$picturefile.=($rear)?$templ->RearPictureFile:$templ->FrontPictureFile;
			if (!file_exists($picturefile)){
				$picturefile="pictures/P_ERROR.png";
			}

			// Get the true size of the template image
			list($pictW, $pictH)=getimagesize($picturefile);

			// adjusted height = targetWidth * height:width ratio for 1u * height of device in U
			$targetHeight=$targetWidth*1.75/19*$this->Height;

			// We need integers for the height and width because browsers act funny with decimals
			$targetHeight=intval($targetHeight);
			$targetWidth=intval($targetWidth);
			
			// URLEncode the image file name just to be compliant.
			$picturefile=str_replace(' ',"%20",$picturefile);

			// If they have rights to the device then make the picture clickable
			$clickable=($this->Rights!="None")?"\t\t<a href=\"devices.php?deviceid=$this->DeviceID\">\n\t":"";
			$clickableend=($this->Rights!="None")?"\n\t\t</a>\n":"";

			// Add in flags for missing ownership
			// Device pictures are set on the template so always assume template has been set
			$flags=($this->Owner==0)?'(O)':'';
			$flags=($flags!='')?'<span class="hlight">'.$flags.'</span>':'';

			// This is for times when you want to use the image on a report but don't want links
			$nolinks=($nolinks)?' disabled':'';

			$resp.="\n\t<div class=\"picture$nolinks\" style=\"width: ".$targetWidth."px; height: ".$targetHeight."px;\">\n";
			$resp.="$clickable\t\t<img data-deviceid=$this->DeviceID src=\"$picturefile\" alt=\"$this->Label\">$clickableend\n";

			/*
			 * Labels on chassis devices were getting silly with smaller devices.  For aesthetic 
			 * reasons we are going to hide the label for the chassis devices that are less than 3U
			 * in height and have slots defined.  If it is just a chassis with nothing defined then 
			 * go ahead and show the chassis label.
			 */
			if(($this->Height<3 && $this->DeviceType=='Chassis' && (($rear && $this->RearChassisSlots > 0) || (!$rear && $this->ChassisSlots > 0))) || ($templ->Model=='HTRAY' || $templ->Model=='VTRAY') ){

			}else{
				$resp.="\t\t<div class=\"label\"><div>$flags$this->Label".
					(((!$this->BackSide && $rear || $this->BackSide && !$rear) && !$this->HalfDepth)?" (".__("Rear").")":"");
				$resp.="</div></div>\n";
			}

			$parent=new stdClass();
			$parent->zoomX=$targetWidth/$pictW;
			$parent->zoomY=$targetHeight/$pictH;
			$parent->targetWidth=$targetWidth;
			$parent->targetHeight=$targetHeight;
			$parent->Height=$pictH;
			$parent->Width=$pictW;
			$parent->parentDev=$this;
			$parent->parentTempl=$templ;

			//Children
			$childList=$this->GetDeviceChildren();
			if (count($childList)>0){
				if(($this->ChassisSlots >0 && !$rear) || ($this->RearChassisSlots >0 && $rear) || ($templ->Model=='HTRAY' || $templ->Model=='VTRAY')){
					//children in front face
					foreach($childList as $tmpDev){
						if (($templ->Model=='HTRAY' || $templ->Model=='VTRAY') || ((!$tmpDev->BackSide && !$rear) || ($tmpDev->BackSide && $rear))){
							$resp.=$tmpDev->GetChildDevicePicture($parent,$rear);
						}
					}
				}
			}
			$resp.="\t</div>\n";
		}
		return $resp;
	}

	function GetCustomValues() {
		global $dbh;

		$this->MakeSafe();
		$dcv = array();
		$sql = "SELECT DeviceID, AttributeID, Value
			FROM fac_DeviceCustomValue
			WHERE DeviceID = $this->DeviceID;";
		foreach($dbh->query($sql) as $dcvrow){
			$dcv[$dcvrow["AttributeID"]]=$dcvrow["Value"];
		}
		$this->CustomValues=$dcv;
	}	

	function DeleteCustomValues() {
		global $dbh;

		$this->MakeSafe();
		$sql="DELETE FROM fac_DeviceCustomValue WHERE DeviceID = $this->DeviceID;";
		if($dbh->query($sql)) {
			$this->GetCustomValues();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}

	function InsertCustomValue($AttributeID, $Value) {
		global $dbh;
	
		$this->MakeSafe();
		// make the custom attribute stuff safe
		$AttributeID = intval($AttributeID);
		$Value=sanitize(trim($Value));

		$sql = "INSERT INTO fac_DeviceCustomValue 
			SET DeviceID = $this->DeviceID,
			AttributeID = $AttributeID,
			Value = \"$Value\";";
		if($dbh->query($sql)) {
			$this->GetCustomValues();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
		return false;
	}
	function SetChildDevicesCabinet(){
		global $dbh;
		
		$sql="SELECT * FROM fac_Device WHERE ParentDevice=".$this->DeviceID;

		foreach($dbh->query($sql) as $row){
			$dev=Device::RowToObject($row);
			$dev->Cabinet=$dev->GetDeviceCabinetID();
			$dev->UpdateDevice();
			if ($dev->ChassisSlots>0 || $dev->RearChassisSlots>0){
				$dev->SetChildDevicesCabinet();
			}
		}
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
		$this->Label=sanitize($this->Label);
		$this->MediaID=intval($this->MediaID);
		$this->ColorID=intval($this->ColorID);
		$this->PortNotes=sanitize($this->PortNotes);
		$this->ConnectedDeviceID=intval($this->ConnectedDeviceID);
		$this->ConnectedPort=intval($this->ConnectedPort);
		$this->Notes=sanitize($this->Notes);

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
			foreach(DevicePorts::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Ports WHERE DeviceID=$this->DeviceID ORDER BY PortNumber ASC;";

		$ports=array();
		foreach($dbh->query($sql) as $row){
			$ports[$row['PortNumber']]=DevicePorts::RowToObject($row);
		}	
		return $ports;
	}
	
	function getActivePortCount() {
		global $dbh;
		$this->MakeSafe();
			
		$sql = "select count(*) as ActivePorts from fac_Ports where DeviceID=$this->DeviceID and (ConnectedDeviceID>0 or Notes > '')";
		
		$row = $dbh->query($sql)->fetch();

		return $row["ActivePorts"];
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

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	static function createPorts($DeviceID){
		$dev=New Device;
		$dev->DeviceID=$DeviceID;
		if(!$dev->GetDevice()){return false;}

		// Check the user's permissions to modify this device
		if($dev->Rights!='Write'){return false;}
		$portList=array();
		
		if($dev->DeviceType=="Switch"){
			$nameList=SwitchInfo::getPortNames($dev->DeviceID);
			$aliasList=SwitchInfo::getPortAlias($dev->DeviceID);
		}

		// Build the DevicePorts from the existing info in the following priority:
		//  - Template ports table
		//  - SNMP data (if it exists)
		//  - Placeholders
		
		//Search template ports
		$tports=array();
		if($dev->TemplateID>0){
			$tport=new TemplatePorts();
			$tport->TemplateID=$dev->TemplateID;
			$tports=$tport->getPorts();
		}
		
		if($dev->DeviceType=="Switch"){
			for($n=0; $n<$dev->Ports; $n++){
				$i=$n+1;
				$portList[$i]=new DevicePorts();
				$portList[$i]->DeviceID=$dev->DeviceID;
				$portList[$i]->PortNumber=$i;
				if(isset($tports[$i])){
					// Get any attributes from the device template
					foreach($tports[$i] as $key => $value){
						if(array_key_exists($key,$portList[$i])){
							$portList[$i]->$key=$value;
						}
					}
				}
				// pull port name first from snmp then from template then just call it port x
				$portList[$i]->Label=(isset($nameList[$n]))?$nameList[$n]:(isset($tports[$i]) && $tports[$i]->Label)?$tports[$i]->Label:__("Port").$i;
				$portList[$i]->Notes=(isset($aliasList[$n]))?$aliasList[$n]:'';
				$portList[$i]->createPort();
			}
		}else{
			for($n=0; $n<$dev->Ports; $n++){
				$i=$n+1;
				$portList[$i]=new DevicePorts();
				$portList[$i]->DeviceID=$dev->DeviceID;
				$portList[$i]->PortNumber=$i;
				if(isset($tports[$i])){
					// Get any attributes from the device template
					foreach($tports[$i] as $key => $value){
						if(array_key_exists($key,$portList[$i])){
							$portList[$i]->$key=$value;
						}
					}
				}
				$portList[$i]->Label=($portList[$i]->Label=="")?__("Port").$i:$portList[$i]->Label;
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

		$sql="UPDATE fac_Ports SET Label=\"$this->Label\" WHERE 
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
		$oldtmpport=new DevicePorts(); // used for logging
		$oldtmpport->DeviceID=$oldport->ConnectedDeviceID;
		$oldtmpport->PortNumber=$oldport->ConnectedPort;
		$oldtmpport->getPort();

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
			// reload tmpport with data from the other device
			$tmpport->DeviceID=$oldport->ConnectedDeviceID;
			$tmpport->PortNumber=$oldport->ConnectedPort;
			$tmpport->getPort();
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

		// If this is a patch panel and a front port then set the label on the rear 
		// to match only after a successful update, done above.
		if($dev->DeviceType=="Patch Panel" && $this->PortNumber>0 && $this->Label!=$oldport->Label){
			$pport=new DevicePorts();
			$pport->DeviceID=$this->DeviceID;
			$pport->PortNumber=-$this->PortNumber;
			$pport->getPort();
			$pport->Label=$this->Label;
			$pport->updateLabel();
		}

		// two logs, because we probably modified two devices
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
		(class_exists('LogActions'))?LogActions::LogThis($tmpport,$oldtmpport):'';
		return true;
	}

	static function followPathToEndPoint( $DeviceID, $PortNumber ) {
		$path = array();
		$n = sizeof( $path );
		
		$dev = new Device();
		$dev->DeviceID=$DeviceID;
		$dev->getDevice();
		
		$path[$n] = new DevicePorts();
		$path[$n]->DeviceID = $DeviceID;
		$path[$n]->PortNumber = ($dev->DeviceType=="Patch Panel")?-$PortNumber:$PortNumber;
		$path[$n]->getPort();
		
		// Follow the trail until you get no more connections
		while ( $path[$n]->ConnectedDeviceID > 0 ) {
			$path[++$n] = new DevicePorts();
			$path[$n]->DeviceID = $path[$n-1]->ConnectedDeviceID;
			// Patch panels have +/- port numbers to designate front/rear, so as you
			// traverse the path, you have to flip
			$path[$n]->PortNumber = -($path[$n-1]->ConnectedPort);
			$path[$n]->getPort();
		}
		
		// If the connected device id is null and the label is empty then the port failed to lookup
		// invert the sign and try to get the port again cause this might be a device and not a 
		// patch panel
		if($path[$n]->ConnectedDeviceID=="NULL" && $path[$n]->Label==""){
			$path[$n]->PortNumber=$path[$n]->PortNumber*-1;
			$path[$n]->getPort();
		}

		return $path;		
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
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
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
			$pp=' AND DeviceType="Patch Panel"';
		}
		$candidates=array();

		if(is_null($listports)){
			$currentperson=People::Current();
			if(!$currentperson->WriteAccess){
				$groups=$currentperson->isMemberOf();  // list of groups the current user is member of
				$rights=null;
				foreach($groups as $index => $DeptID){
					if(is_null($rights)){
						$rights="Owner=$DeptID";
					}else{
						$rights.=" OR Owner=$DeptID";
					}
				}
				$rights=(is_null($rights))?null:" AND ($rights)";
			}else{
				$rights=null;
			}

			// Gets a little complicated if you are on a blade device and looking for other patch candidates
			// But putting this logic into the SQL is extremely processor intensive, so do the conditional on the outside
			// and only take the processing hit when there's a child device as the source
			
			//JMGA #511
			//$cabinetID=($dev->ParentDevice==0)?$dev->Cabinet:$dev->WhosYourDaddy()->Cabinet;
			$cabinetID=$dev->GetDeviceCabinetID();
			
			$sqlSameCabDevice="SELECT * FROM fac_Device WHERE Ports>0 AND 
				Cabinet=$cabinetID $rights$pp GROUP BY DeviceID ORDER BY Position 
				DESC, Label ASC;";
			/*JMGA #511
			$sqlSameCabChildDevice="SELECT * FROM fac_Device WHERE Ports>0 AND 
				Cabinet=0 AND ParentDevice IN (SELECT DeviceID FROM fac_Device WHERE 
				Cabinet=$cabinetID AND (ChassisSlots>0 OR RearChassisSlots>0)) $rights$pp 
				GROUP BY DeviceID ORDER BY Position DESC, Label ASC;";
			*/
			$sqlDiffCabDevice="SELECT * FROM fac_Device WHERE Ports>0 AND 
				Cabinet!=$cabinetID $rights$pp GROUP BY DeviceID ORDER BY Label ASC;";
			
			/*JMGA #511
			$sqlDiffCabChildDevice="SELECT * FROM fac_Device WHERE Ports>0 AND Cabinet=0 
				AND ParentDevice IN (SELECT DeviceID FROM fac_Device WHERE 
				Cabinet!=$cabinetID AND (ChassisSlots>0 OR RearChassisSlots>0)) $rights$pp 
				GROUP BY DeviceID ORDER BY Label ASC;";
			*/

			// Running these four simple queries is supposed to be faster than the previous complicated ones
			//JMGA #511
			//foreach(array($sqlSameCabDevice, $sqlSameCabChildDevice, $sqlDiffCabDevice, $sqlDiffCabChildDevice) as $sql){
			foreach(array($sqlSameCabDevice, $sqlDiffCabDevice) as $sql){
				foreach($dbh->query($sql) as $row){
					// false to skip rights check we filtered using sql above
					$tmpDev=Device::RowToObject($row,false);
					// Child devices the cabinet will be 0 this will fix that
					$tmpDev->Cabinet=($tmpDev->ParentDevice!=0)?$tmpDev->WhosYourDaddy()->Cabinet:$tmpDev->Cabinet;
					$candidates[]=array("DeviceID" => $tmpDev->DeviceID, "Label" => $tmpDev->Label, "CabinetID" => $tmpDev->Cabinet);
				}
			}
		}else{
			$sql="SELECT a.*, b.Cabinet as CabinetID FROM fac_Ports a, fac_Device b WHERE 
				Ports>0 AND Cabinet>-1 AND a.DeviceID=b.DeviceID AND 
				a.DeviceID!=$dev->DeviceID AND ConnectedDeviceID IS NULL$mediaenforce$pp;";
			foreach($dbh->query($sql) as $row){
				$candidates[]=array("DeviceID"=>$row["DeviceID"], "Label"=>$row["Label"], "CabinetID"=>$row["CabinetID"]);
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
  
	static function RowToObject($dbRow){
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
			$vmList[$vmCount]=ESX::RowToObject($row);
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

			$vmList = ESX::RefreshInventory( $esxDev, $debug );

			if($debug){
				print_r($vmList);
			}
		}
	}
  
	static function RefreshInventory( $ESXDevice, $debug = false ) {
		global $dbh;

		$dev = new Device();
		if ( is_object( $ESXDevice ) ) {
			$dev->DeviceID = $ESXDevice->DeviceID;
		} else {
			$dev->DeviceID = $ESXDevice;
		}
		$dev->GetDevice();
		
		$search = $dbh->prepare( "select * from fac_VMInventory where vmName=:vmName" );
		$update = $dbh->prepare( "update fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState where vmName=:vmName" );
		$insert = $dbh->prepare( "insert into fac_VMInventory set DeviceID=:DeviceID, LastUpdated=:LastUpdated, vmID=:vmID, vmState=:vmState, vmName=:vmName" );
		
		$vmList = ESX::EnumerateVMs( $dev, $debug );
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

		if(!$vmRow=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(ESX::RowToObject($vmRow) as $param => $value){
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
		
		$sql="INSERT INTO fac_MediaTypes SET MediaType=\"".sanitize($this->MediaType)."\", 
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
		
		$sql="UPDATE fac_MediaTypes SET MediaType=\"".sanitize($this->MediaType)."\", 
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
	
	function GetTypeByName() {
		global $dbh;
		
		$sql="SELECT * FROM fac_MediaTypes WHERE MediaType='".sanitize($this->MediaType)."';";
		
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			$this->MediaID = $row["MediaID"];
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

	// Create MakeSafe / MakeDisplay functions
	function MakeSafe(){
		//Keep weird values out of DeviceType
		$validdevicetypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU');

		$this->RequestID=intval($this->RequestID);
		$this->RequestorID=intval($this->RequestorID);
		$this->RequestTime=sanitize($this->RequestTime); //datetime
		$this->CompleteTime=sanitize($this->CompleteTime); //datetime
		$this->Label=sanitize(transform($this->Label));
		$this->SerialNo=sanitize(transform($this->SerialNo));
		$this->AssetTag=sanitize($this->AssetTag);
		$this->ESX=intval($this->ESX);
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
		$this->MfgDate=date("Y-m-d", strtotime($this->MfgDate)); //date
	}

	function MakeDisplay(){
		$this->Label=stripslashes($this->Label);
		$this->SerialNo=stripslashes($this->SerialNo);
		$this->AssetTag=stripslashes($this->AssetTag);
		$this->VLANList=stripslashes($this->VLANList);
		$this->SANList=stripslashes($this->SANList);
		$this->DeviceClass=stripslashes($this->DeviceClass);
		$this->LabelColor=stripslashes($this->LabelColor);
		$this->CurrentLocation=stripslashes($this->CurrentLocation);
		$this->SpecialInstructions=stripslashes($this->SpecialInstructions);
	}
 
  function CreateRequest(){
	global $dbh;

	$this->MakeSafe();

    $sql="INSERT INTO fac_RackRequest SET RequestTime=now(), RequestorID=$this->RequestorID,
		Label=\"$this->Label\", SerialNo=\"$this->SerialNo\", MfgDate=\"$this->MfgDate\", 
		AssetTag=\"$this->AssetTag\", ESX=$this->ESX, Owner=$this->Owner, 
		DeviceHeight=\"$this->DeviceHeight\", EthernetCount=$this->EthernetCount, 
		VLANList=\"$this->VLANList\", SANCount=$this->SANCount, SANList=\"$this->SANList\",
		DeviceClass=\"$this->DeviceClass\", DeviceType=\"$this->DeviceType\",
		LabelColor=\"$this->LabelColor\", CurrentLocation=\"$this->CurrentLocation\",
		SpecialInstructions=\"$this->SpecialInstructions\";";

	if(!$dbh->exec($sql)){
		$info=$dbh->errorInfo();
		error_log("PDO Error: {$info[2]}");
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
		$requestList[$requestNum]->MakeDisplay();
    }
    
    return $requestList;
  }
  
  function GetRequest(){
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
		AssetTag=\"$this->AssetTag\", ESX=$this->ESX, Owner=$this->Owner, 
		DeviceHeight=\"$this->DeviceHeight\", EthernetCount=$this->EthernetCount, 
		VLANList=\"$this->VLANList\", SANCount=$this->SANCount, SANList=\"$this->SANList\",
		DeviceClass=\"$this->DeviceClass\", DeviceType=\"$this->DeviceType\",
		LabelColor=\"$this->LabelColor\", CurrentLocation=\"$this->CurrentLocation\",
		SpecialInstructions=\"$this->SpecialInstructions\"
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

class SwitchInfo {
	/* All of these functions will REQUIRE the built-in SNMP functions - the external calls are simply too slow */
	static function getNumPorts($DeviceID) {
		global $dbh;
		global $config;
		
		if ( ! function_exists( "snmpget" ) ) {
			return;
		}
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		
		if(!$dev->GetDevice()) {
			return false;
		}
		
		if ( $dev->PrimaryIP == "" )
			return;
		
		if ( $dev->SNMPCommunity == "" ) {
			$Community = $config->ParameterArray["SNMPCommunity"];
		} else {
			$Community = $dev->SNMPCommunity;
		}
		
		return @end( explode( ":", snmpget( $dev->PrimaryIP, $Community, 'IF-MIB::ifNumber.0' )));
	}

	static function findFirstPort( $DeviceID ) {
		global $dbh;
		global $config;
		
		if ( ! function_exists( "snmpget" ) ) {
			return;
		}
		
		$dev=new Device();
		$dev->DeviceID = $DeviceID;
		
		if ( !$dev->GetDevice() ) {
			return false;
		}

		if ( $dev->PrimaryIP == "" )
			return;
		
		if ( $dev->SNMPCommunity == "" ) {
			$Community = $config->ParameterArray["SNMPCommunity"];
		} else {
			$Community = $dev->SNMPCommunity;
		}
		
		$x = array();
		
		$portList = snmprealwalk( $dev->PrimaryIP, $Community, "IF-MIB::ifDescr" );
		foreach( $portList as $index => $port ) {
			$head = @end( explode( ".", $index ) );
			$portdesc = @end( explode( ":", $port));
			if ( preg_match( "/(bond|\"[A-Z]|swp|eth|Ethernet|Port-Channel|\/)[01]$/", $portdesc )) {
				$x[$head] = $portdesc;
			} // Find lines that end with /1
		}
		return $x;
	}

	static function getPortNames( $DeviceID, $portid = null ) {
		global $dbh;
		global $config;
		
		if ( ! function_exists( "snmpget" ) ) {
			return;
		}
		
		$dev = new Device();
		$dev->DeviceID = $DeviceID;
		$nameList=array(); // should this fail return blank
		
		if(!$dev->GetDevice()){
			return $nameList;
		}
		
		if( $dev->PrimaryIP=="" ){
			return $nameList;
		}
		
		if ( $dev->SNMPCommunity == "" ) {
			$Community = $config->ParameterArray["SNMPCommunity"];
		} else {
			$Community = $dev->SNMPCommunity;
		}
			
		$baseOID = ".1.3.6.1.2.1.31.1.1.1.1";
		$baseOID = "IF-MIB::ifName"; 

		if(is_null($portid)){		
			if($reply=@snmprealwalk($dev->PrimaryIP,$Community,$baseOID)){
				// Skip the returned values until we get to the first port
				$Saving = false;
				foreach($reply as $oid => $label){
					$indexValue = @end(explode( ".", $oid ));
					if ( $indexValue == $dev->FirstPortNum )
						$Saving = true;
						
					if ( $Saving == true )
						$nameList[sizeof($nameList) + 1] = trim(@end(explode(":",$label)));
					
					// Once we have captured enough values that match the number of ports, stop
					if ( sizeof( $nameList ) == $dev->Ports )
						break;
				}
			}
		} else {
				$query = @end( explode( ":", snmp2_get( $dev->PrimaryIP, $Community, $baseOID.'.'.$portid )));
				$nameList = $query;
		}
		
		return $nameList;
	}
	
	static function getPortStatus( $DeviceID, $portid = null ) {
		global $dbh;
		global $config;
		
		if ( ! function_exists( "snmpget" ) ) {
			return;
		}
		
		$dev=new Device();
		$dev->DeviceID=$DeviceID;
		$statusList=array();
		
		if(!$dev->GetDevice()){
			return $statusList;
		}
		
		if( $dev->PrimaryIP=="" ){
			return $statusList;
		}
		
		if ( $dev->SNMPCommunity == "" ) {
			$Community = $config->ParameterArray["SNMPCommunity"];
		} else {
			$Community = $dev->SNMPCommunity;
		}
			
		// $baseOID = ".1.3.6.1.2.1.2.2.1.8.";
		$baseOID="IF-MIB::ifOperStatus"; // arguments for not using MIB?

		if ( is_null($portid) ) {		
			if($reply=@snmprealwalk($dev->PrimaryIP, $Community, $baseOID)){	
				// Skip the returned values until we get to the first port
				$Saving = false;
				foreach($reply as $oid => $status){
					$indexValue = @end(explode( ".", $oid ));
					if ( $indexValue == $dev->FirstPortNum ) {
						$Saving = true;
					}
					
					if ( $Saving == true ) {
						@preg_match( "/(INTEGER: )(.+)(\(.*)/", $status, $matches);
						$statusList[sizeof( $statusList) + 1]=@$matches[2];
					}
					
					// Once we have captured enough values that match the number of ports, stop
					if ( sizeof( $statusList ) == $dev->Ports ) {
						break;
					}
				}
			}
		}else{
			@preg_match( "/(INTEGER: )(.+)(\(.*)/", snmpget( $dev->PrimaryIP, $dev->SNMPCommunity, $baseOID.$portid ), $matches);
			// This will change the array that was getting kicked back to a single value for an individual port lookup
			$statusList = @$matches[2];
		}
		
		return $statusList;
	}
	
	static function getPortAlias( $DeviceID, $portid = null ) {
		global $config;

		if(!function_exists("snmpget")){
			return false;
		}

		$dev=new Device();
		$dev->DeviceID=$DeviceID;

		$aliasList=array();

		if(!$dev->GetDevice()){
			return $aliasList;
		}

		if($dev->PrimaryIP==""){
			return $aliasList;
		}

		// Get SNMP community from the device, fall back to default if one isn't set on the device
		$Community=($dev->SNMPCommunity=="")?$config->ParameterArray["SNMPCommunity"]:$dev->SNMPCommunity;
		if($Community==""){
			return $aliasList;
		}

		$baseOID=".1.3.6.1.2.1.31.1.1.1.18.";
		$baseOID="IF-MIB::ifAlias";

		if(is_null($portid)){
			if($reply=snmprealwalk($dev->PrimaryIP,$Community,$baseOID)){
				$n=1; // Start our index at 1
				foreach($reply as $oid => $string){
					if(@end(explode( ".", $oid ))>=$dev->FirstPortNum){
						@preg_match( "/(STRING: )(.*)/", $string, $matches);
						$aliasList[$n++]=$matches[2];
					}
					// Once we have captured enough values that match the number of ports, stop
					if(sizeof($aliasList)==$dev->Ports){
						break;
					}
				}
			}
		}else{
			$query = @end( explode( ":", snmpget( $dev->PrimaryIP, $Community, $baseOID.'.'.$portid )));
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
			$TagName=sanitize($TagName);
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
			$TagName=sanitize($TagName);
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

		$sql="SELECT * FROM fac_Tags order by Name ASC";

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
		//Busco el  nodo de la lista de candidatos el nodo con peso minimo
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
		global $config;
		//find posible next devices with lower weight in list from actual node 
		//for each device found, if already it exists and it is not useded, update it if (new weight) < (old weight)
		//if it does not exist, insert in list with his actual weight and $used=false
		//Destination device is $this->devID2
		
		//weights
		$weight_cabinet=$config->ParameterArray["path_weight_cabinet"]; 	//weight for patches on actual cabinet
		$weight_rear=$config->ParameterArray["path_weight_rear"];		//weight fot rear connetcion between panels
		$weight_row=$config->ParameterArray["path_weight_row"];		//weigth for patches on same row of cabinets (except actual cabinet)
		//It is possible to assign a weight proportional to the distance between the actual cabinet and each cabinet of actual row, 
		//so you can prioritize closest cabinets in the actual row. In the future...
		
		$this->escribe_log("\nSelected node: D=".$this->DeviceID.
						"; P=".$this->PortNumber.
						"; W=".$this->nodes[$this->DeviceID][$this->PortNumber]["weight"].
						"; PD=".$this->nodes[$this->DeviceID][$this->PortNumber]["prev_dev"].
						"; PP=".$this->nodes[$this->DeviceID][$this->PortNumber]["prev_port"]);;	
			
		//Compruebo si el puerto del dispositivo actual esta conectado a la conexion trasera de un panel
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
			
			//busco el dispositivo final en el mismo armario (si no esta reflejado en un panel)
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
				//Compruebo si tengo que anadir esta pareja
				//I check if I have to add this pair of nodes
				if (isset($this->candidates[$row["DeviceID2"]]) 
					&& $this->nodes[$row["DeviceID2"]][$this->candidates[$row["DeviceID2"]]]["weight"]>$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet+$weight_rear
					|| !isset($this->candidates[$row["DeviceID2"]]) && !isset($this->used_candidates[$row["DeviceID2"]])){
					$this->AddNodeToList($row["DeviceID1"],-$row["PortNumber1"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_cabinet, $this->DeviceID, $this->PortNumber);
					//Anado directamente el espejo de este puerto
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
				//Compruebo si tengo que anadir esta pareja
				//I check if I have to add this pair of nodes
				if (isset($this->candidates[$row["DeviceID2"]]) 
					&& $this->nodes[$row["DeviceID2"]][$this->candidates[$row["DeviceID2"]]]["weight"]>$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row+$weight_rear
					|| !isset($this->candidates[$row["DeviceID2"]]) && !isset($this->used_candidates[$row["DeviceID2"]])){
					$this->AddNodeToList($row["DeviceID1"],-$row["PortNumber1"],$this->nodes[$this->DeviceID][$this->PortNumber]["weight"]+$weight_row,$this->DeviceID, $this->PortNumber);
					//Anado directamente el espejo de este puerto
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
	//Si el dispositivo actual del objeto no esta conectado a nada, devuelve "false" y el objeto no cambia
	// Places the object with the DeviceID, PortNumber and Front of the next device in the path.
    // If the object's current device is not connected returns "false" and the object doesn't change.
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

class DeviceCustomAttribute {
	var $AttributeID;
	var $Label;
	var $AttributeType='string';
	var $Required=0;
	var $AllDevices=0;
	var $DefaultValue;

	function MakeSafe() {
		$this->AttributeID=intval($this->AttributeID);
		$this->Label=sanitize($this->Label);
		$this->AttributeType=sanitize($this->AttributeType);
		$this->Required=intval($this->Required);
		$this->Required=($this->Required>=1)?1:0;
		$this->AllDevices=intval($this->AllDevices);
		$this->AllDevices=($this->AllDevices>=1)?1:0;
		$this->DefaultValue=sanitize($this->DefaultValue);
	}
	
	function CheckInput() {
		$this->MakeSafe();
		
		if(!in_array($this->AttributeType, DeviceCustomAttribute::GetDeviceCustomAttributeTypeList())){
			return false;
		}
		if(trim($this->DefaultValue) != "") {
			switch($this->AttributeType){
				case "number":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_FLOAT)) { return false; }
					break;
				case "integer":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_INT)) { return false; }
					break;
				case "date":
					$dateparts = preg_split("/\/|-/", $this->DefaultValue);
					if(count($dateparts)!=3 || !checkdate($dateparts[0], $dateparts[1], $dateparts[2])) { return false; }
					break;
				case "phone":
					// stole this regex out of the jquery.validationEngine-en.js source
					if(!preg_match("/^([\+][0-9]{1,3}[\ \.\-])?([\(]{1}[0-9]{2,6}[\)])?([0-9\ \.\-\/]{3,20})((x|ext|extension)[\ ]?[0-9]{1,4})?$/", $this->DefaultValue)) { return false; }
					break;
				case "email":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_EMAIL)) { return false; }
					break;
				case "ipv4":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_IP)) { return false; }
					break;
				case "url":
					if(!filter_var($this->DefaultValue, FILTER_VALIDATE_URL)) { return false; }
					break;
				case "checkbox":
					$acceptable = array("0", "1", "true", "false", "on", "off");
					if(!in_array($this->DefaultValue, $acceptable)) { return false; }		
					break;
			}
		}
		return true;
	}

	static function RowToObject($dbRow) {
		$dca = new DeviceCustomAttribute();
		$dca->AttributeID=$dbRow["AttributeID"];
		$dca->Label=$dbRow["Label"];
		$dca->AttributeType=$dbRow["AttributeType"];
		$dca->Required=$dbRow["Required"];
		$dca->AllDevices=$dbRow["AllDevices"];
		$dca->DefaultValue=$dbRow["DefaultValue"];
		return $dca;
	}

	function CreateDeviceCustomAttribute() {
		global $dbh;
		$this->MakeSafe();
		if(!$this->CheckInput()) { return false; }
		$sql="INSERT INTO fac_DeviceCustomAttribute SET Label=\"$this->Label\",
			AttributeType=\"$this->AttributeType\", Required=$this->Required,
			AllDevices=$this->AllDevices,DefaultValue=\"$this->DefaultValue\";";

		if(!$dbh->exec($sql)) {
			$info=$dbh->errorInfo();
			error_log("CreateDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql");
			return false;
		} else {
			$this->AttributeID=$dbh->LastInsertId();
		}

		// If something is marked "AllDevices", we don't actually add it to all devices
		// in the database, we just check when displaying devices/templates and 
		// display any that are AllDevices to help reduce db size/complexity

		(class_exists('LogActions'))?LogActions::LogThis($this):'';

		return $this->AttributeID;

	}

	function UpdateDeviceCustomAttribute() {
		global $dbh;
		$this->MakeSafe();
		if(!$this->CheckInput()) { return false; }

		$old = new DeviceCustomAttribute();
		$old->AttributeID = $this->AttributeID;
		$old->GetDeviceCustomAttribute();

		$sql="UPDATE fac_DeviceCustomAttribute SET Label=\"$this->Label\",
			AttributeType=\"$this->AttributeType\", Required=$this->Required,
			AllDevices=$this->AllDevices,DefaultValue=\"$this->DefaultValue\"
			WHERE AttributeID=$this->AttributeID;";

		if(!$dbh->query($sql)) {
			$info=$dbh->errorInfo();
			error_log("UpdateDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';

		return true;
	}

	function GetDeviceCustomAttribute() {
		global $dbh;
		$this->MakeSafe();
		$sql="SELECT AttributeID, Label, AttributeType, Required, AllDevices, DefaultValue 
			FROM fac_DeviceCustomAttribute
			WHERE AttributeID=$this->AttributeID;";

		if($dcaRow=$dbh->query($sql)->fetch()) {
			foreach(DeviceCustomAttribute::RowToObject($dcaRow) as $prop => $value) {
				$this->$prop=$value;
			}
			return true;
		} else {
			return false;
		}
	}
	
	function RemoveDeviceCustomAttribute() {
		global $dbh;
		$this->AttributeID=intval($this->AttributeID);
	
		$sql="DELETE FROM fac_DeviceTemplateCustomValue WHERE AttributeID=$this->AttributeID;";
                if(!$dbh->query($sql)){
                        $info=$dbh->errorInfo();
                        error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
                        return false;
                }
		$sql="DELETE FROM fac_DeviceCustomValue WHERE AttributeID=$this->AttributeID;";
                if(!$dbh->query($sql)){
                        $info=$dbh->errorInfo();
                        error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
                        return false;
                }

		$sql="DELETE FROM fac_DeviceCustomAttribute WHERE AttributeID=$this->AttributeID;";
                if(!$dbh->query($sql)){
                        $info=$dbh->errorInfo();
                        error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
                        return false;
                }

                (class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;

	}

	function RemoveFromTemplatesAndDevices() {
		global $dbh;
		$this->AttributeID=intval($this->AttributeID);
	
		$sql="DELETE FROM fac_DeviceTemplateCustomValue WHERE AttributeID=$this->AttributeID;";
                if(!$dbh->query($sql)){
                        $info=$dbh->errorInfo();
                        error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
                        return false;
                }
		$sql="DELETE FROM fac_DeviceCustomValue WHERE AttributeID=$this->AttributeID;";
                if(!$dbh->query($sql)){
                        $info=$dbh->errorInfo();
                        error_log("RemoveDeviceCustomAttribute::PDO Error: {$info[2]} SQL=$sql" );
                        return false;
                }

                (class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;

	}

	static function GetDeviceCustomAttributeList() {
		global $dbh;
		$dcaList=array();
		
		$sql="SELECT AttributeID, Label, AttributeType, Required, AllDevices, DefaultValue
			FROM fac_DeviceCustomAttribute
			ORDER BY Label, AttributeID;";

		foreach($dbh->query($sql) as $dcaRow) {
			$dcaList[$dcaRow["AttributeID"]]=DeviceCustomAttribute::RowToObject($dcaRow);
		}

		return $dcaList;
	}

	static function GetDeviceCustomAttributeTypeList() {
		global $dbh;
		$typeList = array();
		$sql="SHOW COLUMNS FROM fac_DeviceCustomAttribute LIKE 'AttributeType'";
		$row = $dbh->query($sql)->fetch();
		preg_match('#^enum\((.*?)\)$#ism', $row['Type'], $matches);
		//use of str_getcsv requires php5.3.0+
		$typeList = str_getcsv($matches[1], ",", "'");
		return $typeList;
	}	

	static function TimesUsed($AttributeID) {
		global $dbh;
		$AttributeID=intval($AttributeID);

                // get a count of the number of times this attribute is in templates or devices
                $sql="SELECT COUNT(*) + (SELECT COUNT(*) FROM fac_DeviceCustomValue WHERE AttributeID=$AttributeID)
                        AS Result FROM fac_DeviceTemplateCustomValue WHERE AttributeID=$AttributeID";
                $count=$dbh->prepare($sql);
                $count->execute();


                return $count->fetchColumn();

	}
}
?>
