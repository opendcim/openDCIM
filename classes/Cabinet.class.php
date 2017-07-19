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
	var $LocationSortable;
	var $AssignedTo;
	var $ZoneID;
	var $CabRowID;      //JMGA: Row of this cabinet
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
	var $FrontEdge;
	var $Notes;
	var $U1Position;

	function MakeSafe() {
		$this->CabinetID=intval($this->CabinetID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Location=sanitize($this->Location);
		$this->LocationSortable=str_replace(' ','',$this->Location);
		$this->AssignedTo=intval($this->AssignedTo);
		$this->ZoneID=intval($this->ZoneID);
		$this->CabRowID=intval($this->CabRowID);
		$this->CabinetHeight=intval($this->CabinetHeight);
		$this->Model=sanitize($this->Model);
		$this->Keylock=sanitize($this->Keylock);
		$this->MaxKW=float_sqlsafe(floatval($this->MaxKW));
		$this->MaxWeight=intval($this->MaxWeight);
		$this->InstallationDate=date("Y-m-d", strtotime($this->InstallationDate));
		$this->MapX1=abs($this->MapX1);
		$this->MapY1=abs($this->MapY1);
		$this->MapX2=abs($this->MapX2);
		$this->MapY2=abs($this->MapY2);
		$this->FrontEdge=in_array($this->FrontEdge, array("Top","Right","Left","Bottom"))?$this->FrontEdge:"Top";
		$this->Notes=sanitize($this->Notes,false);
		$this->U1Position=in_array($this->U1Position, array("Top","Bottom","Default"))?$this->U1Position:"Default";
	}

	public function __construct($cabinetid=false){
		if($cabinetid){
			$this->CabinetID=$cabinetid;
		}
		return $this;
	}

	static function RowToObject($dbRow,$filterrights=true){
		/*
		 * Generic function that will take any row returned from the fac_Cabinet
		 * table and convert it to an object for use in array or other
		 */
		$cab=new Cabinet();
		$cab->CabinetID=$dbRow["CabinetID"];
		$cab->DataCenterID=$dbRow["DataCenterID"];
		$cab->Location=$dbRow["Location"];
		$cab->LocationSortable=$dbRow["LocationSortable"];
		$cab->AssignedTo=$dbRow["AssignedTo"];
		$cab->ZoneID=$dbRow["ZoneID"];
		$cab->CabRowID=$dbRow["CabRowID"];
		$cab->CabinetHeight=$dbRow["CabinetHeight"];
		$cab->Model=$dbRow["Model"];
		$cab->Keylock=$dbRow["Keylock"];
		$cab->MaxKW=$dbRow["MaxKW"];
		$cab->MaxWeight=$dbRow["MaxWeight"];
		$cab->InstallationDate=$dbRow["InstallationDate"];
		$cab->MapX1=$dbRow["MapX1"];
		$cab->MapY1=$dbRow["MapY1"];
		$cab->MapX2=$dbRow["MapX2"];
		$cab->MapY2=$dbRow["MapY2"];
		$cab->FrontEdge=$dbRow["FrontEdge"];
		$cab->Notes=$dbRow["Notes"];
		$cab->U1Position=$dbRow["U1Position"];

		if($filterrights){
			$cab->FilterRights();
		} else {
			// Assume that you can read everything if there's no FilterRights call.
			$cab->Rights = "Read";
		}

		if($cab->U1Position=="Default"){
			$dc=$_SESSION['datacenters'][$cab->DataCenterID];
			if($dc->U1Position=="Default"){
				global $config;
				$cab->U1Position=$config->ParameterArray["U1Position"];
			}else{
				$cab->U1Position=$dc->U1Position;
			}
		}

		return $cab;
	}

	private function FilterRights(){
		global $person;
		$this->Rights='None';
		if($person->canRead($this->AssignedTo)){$this->Rights="Read";}
		if($person->canWrite($this->AssignedTo)){$this->Rights="Write";}

		// Remove information that they shouldn't have access to
		if($this->Rights=='None'){
			// ZoneID and CabRowID are probably both not important but meh
			$publicfields=array('CabinetID','DataCenterID','Location','LocationSortable','ZoneID','CabRowID','Rights','AssignedTo','U1Position');
			foreach($this as $prop => $value){
				if(!in_array($prop,$publicfields)){
					$this->$prop=null;
				}
			}
		}
	}

	function CreateCabinet(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_Cabinet SET DataCenterID=$this->DataCenterID, 
			Location=\"$this->Location\", LocationSortable=\"$this->LocationSortable\",
			AssignedTo=$this->AssignedTo, ZoneID=$this->ZoneID, CabRowID=$this->CabRowID, 
			CabinetHeight=$this->CabinetHeight, Model=\"$this->Model\", 
			Keylock=\"$this->Keylock\", MaxKW=$this->MaxKW, MaxWeight=$this->MaxWeight, 
			InstallationDate=\"".date("Y-m-d", strtotime($this->InstallationDate))."\", 
			MapX1=$this->MapX1, MapY1=$this->MapY1, 
			MapX2=$this->MapX2, MapY2=$this->MapY2,
			FrontEdge=\"$this->FrontEdge\", Notes=\"$this->Notes\", 
			U1Position=\"$this->U1Position\";";

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
			Location=\"$this->Location\", LocationSortable=\"$this->LocationSortable\",
			AssignedTo=$this->AssignedTo, ZoneID=$this->ZoneID, CabRowID=$this->CabRowID, 
			CabinetHeight=$this->CabinetHeight, Model=\"$this->Model\", 
			Keylock=\"$this->Keylock\", MaxKW=$this->MaxKW, MaxWeight=$this->MaxWeight, 
			InstallationDate=\"".date("Y-m-d", strtotime($this->InstallationDate))."\", 
			MapX1=$this->MapX1, MapY1=$this->MapY1, 
			MapX2=$this->MapX2, MapY2=$this->MapY2,
			FrontEdge=\"$this->FrontEdge\", Notes=\"$this->Notes\", 
			U1Position=\"$this->U1Position\" WHERE CabinetID=$this->CabinetID;";

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

	static function ListCabinets($orderbydc=false, $indexed=false){
		global $dbh;
		global $config;

		$cabinetList=array();

		// if AppendCabDC is set then we will be appending the DC to lists so sort them accordingly
		$orderbydc=(!$orderbydc || $config->ParameterArray['AppendCabDC']=='enabled')?'DataCenterID, ':'';
		$sql="SELECT * FROM fac_Cabinet ORDER BY $orderbydc LENGTH(LocationSortable), LocationSortable ASC;";

		foreach($dbh->query($sql) as $cabinetRow){
			$filter = $config->ParameterArray["FilterCabinetList"] == 'Enabled' ? true:false;
			if ( $indexed ) {
				$cabinetList[$cabinetRow["CabinetID"]]=Cabinet::RowToObject($cabinetRow, $filter);
			} else {
				$cabinetList[]=Cabinet::RowToObject($cabinetRow, $filter);
			}
		}

		return $cabinetList;
	}

	function ListCabinetsByDC($limit=false,$limitzone=false){
		global $dbh;
		global $config;
		
		$this->MakeSafe();

		$sql = "select * from fac_Cabinet where DataCenterID='" . $this->DataCenterID . "'";
		if ( $limitzone && $this->ZoneID>0 ) {
			$sql .= " and ZoneID='" . $this->ZoneID . "'";
		}
		$sql .= " ORDER BY Location ASC";

		$cabinetList = array();
		
		foreach( $dbh->query($sql) as $cabinetRow){
			$filter = $config->ParameterArray["FilterCabinetList"] == 'Enabled' ? true:false;
			$cabinetList[]=Cabinet::RowToObject($cabinetRow, $filter);		
		}
		
		foreach($cabinetList as $i => $cab){
			if($limit && ($cab->MapX1==$cab->MapX2 || $cab->MapY1==$cab->MapY2)){
				unset($cabinetList[$i]);
			}
		}

		return $cabinetList;
	}

	function GetCabinetsByDept(){
		global $dbh;

		$this->MakeSafe();

		$cabinetList = array();

		$sql = "select * from fac_Cabinet where AssignedTo='" . $this->AssignedTo . "'";
		foreach( $dbh->query($sql) as $cabinetRow){
			$filter = $config->ParameterArray["FilterCabinetList"] == 'Enabled' ? true:false;
			$cabinetList[]=Cabinet::RowToObject($cabinetRow, $filter);		
		}

		return $cabinetList;
	}

	function GetCabinetsByZone(){
		global $dbh;
		global $config;

		$this->MakeSafe();

		$cabinetList = array();
		if ( $this->ZoneID>0) {	
			$sql = "select * from fac_Cabinet where ZoneID='" . $this->ZoneID . "'";
			foreach( $dbh->query($sql) as $cabinetRow){
				$filter = $config->ParameterArray["FilterCabinetList"] == 'Enabled' ? true:false;
				$cabinetList[]=Cabinet::RowToObject($cabinetRow, $filter);		
			}
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
			LocationSortable ASC;";

		$cabinetList=array();
		foreach($dbh->query($sql) as $cabinetRow){
			$cabinetList[]=Cabinet::RowToObject($cabinetRow);
		}

		if($frontedge=="Right" || $frontedge=="Top"){
			$cabinetList=array_reverse($cabinetList);
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
		global $person;
		
		$sql="SELECT Name, CabinetID, Location, AssignedTo FROM fac_DataCenter, fac_Cabinet WHERE 
			fac_DataCenter.DataCenterID=fac_Cabinet.DataCenterID ORDER BY Name ASC, 
			Location ASC, LENGTH(Location);";

		$selectList="<select name=\"CabinetID\" id=\"CabinetID\"><option value=\"-1\">Storage Room</option>";

		foreach($dbh->query($sql) as $selectRow){
			if($selectRow["CabinetID"]==$this->CabinetID || $person->canWrite($selectRow["AssignedTo"])){
				$selected=($selectRow["CabinetID"]==$this->CabinetID)?' selected':'';
				$selectList.="<option value=\"{$selectRow["CabinetID"]}\"$selected>{$selectRow["Name"]} / {$selectRow["Location"]}</option>";
			}
		}

		$selectList .= "</select>";

		return $selectList;
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

		// Remove from any projects
		ProjectMembership::removeMember( $this->CabinetID, 'Cabinet' );
		
		$sql="DELETE FROM fac_Cabinet WHERE CabinetID=$this->CabinetID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error::DeleteCabinet: {$info[2]} SQL=$sql");
			return false;
		}
	
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function Search($indexedbyid=false,$loose=false){
		global $dbh;
		// Store the value of frontedge before we muck with it
		$ot=$this->FrontEdge;
		$op = $this->U1Position;

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($this as $prop => $val){
			// We force the following values to knowns in makesafe 
			if($prop=="FrontEdge" && $val=="Top" && $ot!="Top"){
				continue;
			}
			if($prop=="U1Position" && $val=="Default" && $op!="Default") {
				continue;
			}
			if($val && $val!=date("Y-m-d", strtotime(0))){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}

		$sql="SELECT * FROM fac_Cabinet $sqlextend ORDER BY LocationSortable ASC";

		$cabList=array();
		foreach($dbh->query($sql) as $cabRow){
			if($indexedbyid){
				$cabList[$cabRow["CabinetID"]]=Cabinet::RowToObject($cabRow);
			}else{
				$cabList[]=Cabinet::RowToObject($cabRow);
			}
		}

		return $cabList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}

	function SearchByCustomTag( $tag=null ) {
		global $dbh;
		
		$sql="SELECT a.* from fac_Cabinet a, fac_CabinetTags b, fac_Tags c WHERE 
			a.CabinetID=b.CabinetID AND b.TagID=c.TagID AND UCASE(c.Name) LIKE 
			UCASE('%".sanitize($tag)."%') ORDER BY LocationSortable;";

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

	function SetTags($tags=array()){
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

	static function getStats($CabinetID){
		global $dbh;
		$cab=new Cabinet($CabinetID);
		if(!$cab->GetCabinet()){return false;}

		$cabstats=new stdClass();
		//Weight
		$sql="SELECT SUM(NominalWatts) AS watts, SUM(Weight) AS weight FROM 
			fac_Device WHERE Cabinet=$cab->CabinetID;";

		foreach($dbh->query($sql) as $row){
			$cabstats->Weight=(!is_null($row['weight']))?$row['weight']:0;
			$cabstats->Wattage=(!is_null($row['watts']))?$row['watts']:0;
		}

		return $cabstats;
	}
}
?>
