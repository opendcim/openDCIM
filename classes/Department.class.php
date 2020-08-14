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
class Department {
	/* Department:	Workgroup, division, department, or external customer.  This
					is simply a mechanism for grouping multiple assets together
					by logical container for the concept of an owner.
	*/
	
	var $DeptID;
	var $Name;
	var $ExecSponsor;
	var $SDM;
	var $Classification;
	var $DeptColor;

	public function __construct($deptid=false){
		if($deptid){
			$this->DeptID=$deptid;
		}
		return $this;
	}

	function MakeSafe(){
		$this->DeptID=intval($this->DeptID);
		$this->Name=sanitize($this->Name);
		$this->ExecSponsor=sanitize($this->ExecSponsor);
		$this->SDM=sanitize($this->SDM);
		$this->Classification=sanitize($this->Classification);
		$this->DeptColor=sanitize($this->DeptColor);
		if($this->DeptColor==""){
			$this->DeptColor="#FFFFFF"; // New color picker was allowing for an empty value
		}
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->ExecSponsor=stripslashes($this->ExecSponsor);
		$this->SDM=stripslashes($this->SDM);
		$this->Classification=stripslashes($this->Classification);
		$this->DeptColor=stripslashes($this->DeptColor);
	}

	static function RowToObject($row){
		$dept=new Department();
		$dept->DeptID=$row["DeptID"];
		$dept->Name=$row["Name"];
		$dept->ExecSponsor=$row["ExecSponsor"];
		$dept->SDM=$row["SDM"];
		$dept->Classification=$row["Classification"];
		$dept->DeptColor=$row["DeptColor"];

		$dept->MakeDisplay();

		return $dept;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function CreateDepartment(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_Department SET Name=\"$this->Name\", 
			ExecSponsor=\"$this->ExecSponsor\", SDM=\"$this->SDM\", 
			Classification=\"$this->Classification\", DeptColor=\"$this->DeptColor\";";

		if($this->exec($sql)){
			$this->DeptID=$dbh->lastInsertId();
			$this->MakeDisplay();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->DeptID;
		}else{
			return false;
		}
	}

	function UpdateDepartment() {
		$this->MakeSafe();

		$olddept=new Department();
		$olddept->DeptID=$this->DeptID;
		$olddept->GetDeptByID();

		$sql="UPDATE fac_Department SET Name=\"$this->Name\", 
			ExecSponsor=\"$this->ExecSponsor\", SDM=\"$this->SDM\", 
			Classification=\"$this->Classification\" , DeptColor=\"$this->DeptColor\" 
			WHERE DeptID=\"$this->DeptID\";";

		(class_exists('LogActions'))?LogActions::LogThis($this,$olddept):'';
		$this->query($sql); 
		$this->MakeDisplay();
	}

	function DeleteDepartment($TransferTo=null){
		// Make sure we have a real department to delete so we don't pull some bonehead move and delete everything set to 0
		if(!$this->GetDeptByID()){
			return false;
		}

		// Get people and objects that still belong to this department
		$dev=new Device();
		$cab=new Cabinet();
		$dev->Owner=$cab->AssignedTo=$this->DeptID;
		$person=new People();
		$devices=$dev->GetDevicesbyOwner();
		$cabinets=$cab->GetCabinetsByDept();
		$users=$person->GetPeopleByDepartment($this->DeptID);

		foreach($devices as $d){
			// We've designated a new owner for this equipment, zero is valid as they might be setting it to general
			if(!is_null($TransferTo)){
				$d->Owner=$TransferTo;
				$d->UpdateDevice();
			}else{
				// This option is not being provided but us at this time, maybe through the API
				$d->DeleteDevice();
			}
		}

		foreach($cabinets as $c){
			// We've designated a new owner for these cabinets, zero is valid as they might be setting it to general
			if(!is_null($TransferTo)){
				$c->AssignedTo=$TransferTo;
				$c->UpdateCabinet();
			}else{
				// This option is not being provided but us at this time, maybe through the API
				$c->DeleteCabinet();
			}
		}

		foreach($users as $p){
			// If we don't have a value over 0 then we're just removing this department and they won't be added to another group
			if(!is_null($TransferTo) && intval($TransferTo)>0){
				// Add this user into the new department
				$sql="INSERT INTO fac_DeptContacts SET DeptID=".intval($TransferTo).", ContactID=$p->PersonID;";
	 			$this->exec($sql);
			}
		}
	
		// Clear any users from this department
		$sql="DELETE FROM fac_DeptContacts WHERE DeptID=$this->DeptID;";
		$this->exec($sql);

		// By this point all devices, objects, and users should have been shoved into a new department so finish cleaning up.
		$sql="DELETE FROM fac_Department WHERE DeptID=$this->DeptID;";

		if(!$this->exec($sql)){
			global $dbh;
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function GetDeptByID() {
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Department WHERE DeptID=$this->DeptID;";

		if($row=$this->query($sql)->fetch()){
			foreach(Department::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			// Return an empty object in the case of a failed lookup, preserve the id though
			foreach($this as $prop => $value){
				$this->$prop=($prop=='DeptID')?$value:'';
			}
			return false;
		}
	}

	function GetDeptByName() {
		$this->MakeSafe();

		$sql="SELECT count(*) as Total, fac_Department.* FROM fac_Department WHERE ucase(Name)=ucase(\"$this->Name\");";
		if($row=$this->query($sql)->fetch()){
			foreach(Department::RowToObject($row) as $prop => $value){
				if ( $prop != "Total" )
					$this->$prop=$value;
			}
		}

		if ( $row["Total"] == 0 ) {
			return false;
		} else {
			return true;
		}
	}
	function GetDepartmentList() {
		$sql="SELECT * FROM fac_Department ORDER BY Name ASC;";
		$deptList=array();
		foreach($this->query($sql) as $row){
			$deptList[]=Department::RowToObject($row);
		}

		return $deptList;
	}

    /**
     * Returns the list of departments indexed by the DeptID
     * @global PDO $dbh
     * @return (Department)[]
     */
    public static function GetDepartmentListIndexedbyID() {
        global $dbh;
        $deptList = array();
        $stmt = $dbh->prepare('SELECT * FROM fac_Department ORDER BY Name ASC');
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $dept = Department::RowToObject($row);
            $deptList[$dept->DeptID] = $dept;
        }
		return $deptList;
	}

	function AssignContacts($MemberList){
		$this->MakeSafe();

		// First clear out all previous assignments
		$sql="DELETE FROM fac_DeptContacts WHERE DeptID=$this->DeptID;";
		$this->exec($sql);

		if(is_array($MemberList)){
		  foreach($MemberList as $PersonID){
				$sql="INSERT INTO fac_DeptContacts SET DeptID=$this->DeptID, ContactID=".intval($PersonID).";";
	 			$this->exec($sql); 
			}
		}
		$this->MakeDisplay();
	}
	
	function GetDepartmentByContact($UserID){
		$sql="SELECT a.* FROM fac_Department a, fac_DeptContacts b, fac_People c 
			WHERE a.DeptID=b.DeptID AND b.PersonID=c.PersonID AND 
			c.UserID=\"".sanitize($UserID)."\";";
	 
		// If someone is assigned to more than one department, just return the first hit
		if($row=$this->query($sql)->fetch()){
			$this->DeptID=$row["DeptID"];
			$this->GetDeptByID();
		}
	}

	function Search($indexedbyid=false,$loose=false){
		$o=array();
		// Store any values that have been added before we make them safe 
		foreach($this as $prop => $val){
			if(isset($val)){
				$o[$prop]=$val;
			}
		}

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($o as $prop => $val){
			extendsql($prop,$this->$prop,$sqlextend,$loose);
		}
		$sql="SELECT * FROM fac_Department $sqlextend ORDER BY Name ASC;";
		$deptList=array();
		foreach($this->query($sql) as $deptRow){
			if($indexedbyid){
				$deptList[$deptRow["DeptID"]]=Department::RowToObject($deptRow);
			}else{
				$deptList[]=Department::RowToObject($deptRow);
			}
		}

		return $deptList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}

?>
