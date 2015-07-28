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

class People {
	/*  People:		A merged construct that was previous split into both users (of the system)
					and contacts (users of the data center).  In early versions the security
					model did not allow for easy segregation of data, but at this point with
					the ability to hide data for which a user has no rights, this simplifies
					things greatly.
	*/

	var $PersonID;
	var $UserID;
	var $LastName;
	var $FirstName;
	var $Phone1;
	var $Phone2;
	var $Phone3;
	var $Email;
	var $AdminOwnDevices;
	var $ReadAccess;
	var $WriteAccess;
	var $DeleteAccess;
	var $ContactAdmin;
	var $RackRequest;
	var $RackAdmin;
	var $SiteAdmin;
	var $Disabled;
	
	function MakeSafe(){
		$this->PersonID=intval($this->PersonID);
		$this->UserID=sanitize($this->UserID);
		$this->LastName=sanitize($this->LastName);
		$this->FirstName=sanitize($this->FirstName);
		$this->Phone1=sanitize($this->Phone1);
		$this->Phone2=sanitize($this->Phone2);
		$this->Phone3=sanitize($this->Phone3);
		$this->Email=sanitize($this->Email);
		$this->AdminOwnDevices=intval($this->AdminOwnDevices);
		$this->ReadAccess=intval($this->ReadAccess);
		$this->WriteAccess=intval($this->WriteAccess);
		$this->DeleteAccess=intval($this->DeleteAccess);
		$this->ContactAdmin=intval($this->ContactAdmin);
		$this->RackRequest=intval($this->RackRequest);
		$this->RackAdmin=intval($this->RackAdmin);
		$this->SiteAdmin=intval($this->SiteAdmin);
		$this->Disabled=intval($this->Disabled);
	}
	
	function MakeDisplay(){
		$this->PersonID=intval($this->PersonID);
		$this->UserID=sanitize($this->UserID);
		$this->LastName=stripslashes($this->LastName);
		$this->FirstName=stripslashes($this->FirstName);
		$this->Phone1=stripslashes($this->Phone1);
		$this->Phone2=stripslashes($this->Phone2);
		$this->Phone3=stripslashes($this->Phone3);
		$this->Email=stripslashes($this->Email);
		$this->AdminOwnDevices=intval($this->AdminOwnDevices);
		$this->ReadAccess=intval($this->ReadAccess);
		$this->WriteAccess=intval($this->WriteAccess);
		$this->DeleteAccess=intval($this->DeleteAccess);
		$this->ContactAdmin=intval($this->ContactAdmin);
		$this->RackRequest=intval($this->RackRequest);
		$this->RackAdmin=intval($this->RackAdmin);
		$this->SiteAdmin=intval($this->SiteAdmin);
		$this->Disabled=intval($this->Disabled);
	}

	static function RowToObject($row){
		$person=new People();
		$person->PersonID=$row["PersonID"];
		$person->UserID=$row["UserID"];
		$person->LastName=$row["LastName"];
		$person->FirstName=$row["FirstName"];
		$person->Phone1=$row["Phone1"];
		$person->Phone2=$row["Phone2"];
		$person->Phone3=$row["Phone3"];
		$person->Email=$row["Email"];
		$person->AdminOwnDevices=$row["AdminOwnDevices"];
		$person->ReadAccess=$row["ReadAccess"];
		$person->WriteAccess=$row["WriteAccess"];
		$person->DeleteAccess=$row["DeleteAccess"];
		$person->ContactAdmin=$row["ContactAdmin"];
		$person->RackRequest=$row["RackRequest"];
		$person->RackAdmin=$row["RackAdmin"];
		$person->SiteAdmin=$row["SiteAdmin"];
		$person->Disabled=$row["Disabled"];

		$person->MakeDisplay();

		return $person;
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function lastID($sql) {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	function AssignDepartments( $DeptList ) {
		$this->MakeSafe();
		
		$sql = "delete from fac_DeptContacts where ContactID=" . $this->PersonID;
		$this->exec( $sql );
		
		foreach ( $DeptList as $DeptID ) {
			$sql = "insert into fac_DeptContacts set ContactID=" . $this->PersonID . ", DeptID=$DeptID";
			$this->exec( $sql );
		}
	}

	function canRead( $Owner ) {
		// If the user has Global rights, don't waste compute cycles on more granular checks
		if ( $this->ReadAccess ) {
			return true;
		}
		
		if ( in_array( $Owner, $this->isMemberOf() ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	function canWrite( $Owner ) {
		// If the user has Global rights, don't wast compute cycles on more granular checks
		if ( $this->WriteAccess ) {
			return true;
		}
		
		if ( in_array( $Owner, $this->isMemberOf() ) && $this->AdminOwnDevices ) {
			return true;
		} else {
			return false;
		}
	}

	function CreatePerson() {
		global $dbh;
		
		$this->MakeSafe();
		
		$sql="INSERT INTO fac_People SET UserID=\"$this->UserID\", LastName=\"$this->LastName\", 
			FirstName=\"$this->FirstName\", Phone1=\"$this->Phone1\", Phone2=\"$this->Phone2\", 
			Phone3=\"$this->Phone3\", Email=\"$this->Email\", 
			AdminOwnDevices=$this->AdminOwnDevices, ReadAccess=$this->ReadAccess, 
			WriteAccess=$this->WriteAccess, DeleteAccess=$this->DeleteAccess, 
			ContactAdmin=$this->ContactAdmin, RackRequest=$this->RackRequest, 
			RackAdmin=$this->RackAdmin, SiteAdmin=$this->SiteAdmin, Disabled=$this->Disabled;";

		if(!$this->query($sql)){
			$info=$dbh->errorInfo();
			error_log("CreatePerson::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			$this->PersonID = $dbh->lastInsertId();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->PersonID;
		}
	}
	
	static function Current(){
		$cperson=new People();

		if(php_sapi_name()=="cli"){
			// If the script is being called from the command line, just give God priveleges and be done with it
			$cperson->UserID="cli_admin";
			$cperson->ReadAccess=true;
			$cperson->WriteAccess=true;
			$cperson->SiteAdmin=true;
			$cperson->Disabled=false;
		}elseif(AUTHENTICATION=="Apache"){
			if(!isset($_SERVER["REMOTE_USER"])){
				return false;
			}
			$cperson->UserID=$_SERVER['REMOTE_USER'];
			$cperson->GetUserRights();
		}elseif(AUTHENTICATION=="Oauth"){
			if(!isset($_SESSION['userid'])){
				return false;
			}
			$cperson->UserID=$_SESSION['userid'];
			$cperson->GetUserRights();
		}
		
		return $cperson;
	}
	
	function GetDeptsByPerson() {
		$sql="SELECT DeptID FROM fac_DeptContacts WHERE ContactID=" . intval( $this->PersonID );

		$deptList=array();

		if($query=$this->query($sql)){
			foreach($query as $row){
				$n = sizeof( $deptList );
				$deptList[$n]=new Department();
				$deptList[$n]->DeptID = $row["DeptID"];
				$deptList[$n]->GetDeptByID();
			}
		}

		return $deptList;
	}
	
	function GetPerson() {
		$this->MakeSafe();
		
		$sql = "select * from fac_People where PersonID=\"". $this->PersonID . "\"";
		
		if ( $row = $this->query( $sql )->fetch() ) {
			foreach( People::RowToObject( $row ) as $prop=>$value ) {
				$this->$prop=$value;
			}
		} else {
			// Kick back a blank record if the PersonID was not found
			foreach ( $this as $prop => $value ) {
				if ( $prop!='PersonID' ) {
					$this->$prop = '';
				}
			}
		}
	}
	
	function GetPeopleByDepartment( $DeptID ) {
		$sql = "select * from fac_People where PersonID in (select ContactID from fac_DeptContacts where DeptID=$DeptID) order by LastName ASC, FirstName ASC";
		
		$personList=array();
		foreach($this->query($sql) as $row){
			$personList[]=People::RowToObject($row);
		}

		return $personList;
	}		
	
	function GetPersonByUserID() {
		$this->MakeSafe();
		
		$sql = "select * from fac_People where ucase(UserID)=ucase(\"" . $this->UserID . "\")";
		
		if ( $row = $this->query( $sql )->fetch() ) {
			foreach( People::RowToObject( $row ) as $prop=>$value ) {
				$this->$prop=$value;
			}
			
			return true;
		} else {
			// Kick back a blank record if the UserID was not found
			foreach ( $this as $prop => $value ) {
				if ( $prop!='UserID' ) {
					$this->$prop = '';
				}
			}
			
			return false;
		}
	}
	
	function GetUserList($indexed=false){
		$sql="SELECT * FROM fac_People ORDER BY LastName ASC, FirstName ASC";
		
		$userList=array();
		foreach($this->query($sql) as $row){
			if($indexed){
				$userList[$row['PersonID']]=People::RowToObject($row);
			}else{
				$userList[]=People::RowToObject($row);
			}
		}

		return $userList;
	}
	
	function GetUserRights() {
		$this->MakeSafe();
		
		/* Set all rights to false just in case the object being called is reused */
		foreach($this as $prop => $value){
			if($prop!='LastName' && $prop!='UserID'){
				$this->$prop=false;
			}
		}
		
		// The default for no user in DB
		$this->Disabled = true;

		$sql="SELECT * FROM fac_People WHERE UserID=\"$this->UserID\";";
		
		if($row=$this->query($sql)->fetch()){
			foreach(People::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}

		/* Just in case someone disabled a user, but didn't remove all of their individual rights */
		if($this->Disabled){
			foreach($this as $prop => $value){
				if($prop!='Name' && $prop!='UserID'){
					$this->$prop=false;
				}
			}
		}
		
		return;
	}
	
	function isMemberOf(){
		$this->GetUserRights();
		
		$sql="SELECT DeptID FROM fac_DeptContacts WHERE ContactID IN 
			(SELECT PersonID FROM fac_People WHERE UserID=\"$this->UserID\");";

		$deptList=array();
		$deptList[]=0; // This is allowing anyone to use an unassigned rack / device
		if($query=$this->query($sql)){
			foreach($query as $row){
				$deptList[]=$row["DeptID"];
			}
		}

		return $deptList;
	}
	
	function UpdatePerson() {
		$this->MakeSafe();
		
		$sql="UPDATE fac_People SET UserID=\"$this->UserID\", LastName=\"$this->LastName\", 
			FirstName=\"$this->FirstName\", Phone1=\"$this->Phone1\", Phone2=\"$this->Phone2\", 
			Phone3=\"$this->Phone3\", Email=\"$this->Email\", 
			AdminOwnDevices=$this->AdminOwnDevices, ReadAccess=$this->ReadAccess, 
			WriteAccess=$this->WriteAccess, DeleteAccess=$this->DeleteAccess, 
			ContactAdmin=$this->ContactAdmin, RackRequest=$this->RackRequest, 
			RackAdmin=$this->RackAdmin, SiteAdmin=$this->SiteAdmin, Disabled=$this->Disabled
			WHERE PersonID=$this->PersonID;";
			
		if ( $this->query( $sql ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		} else {
			error_log( "Unable to modify record in fac_People with SQL: " . $sql );
			return false;
		}
	}

	function Search($indexedbyid=false,$loose=false){
		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($this as $prop => $val){
			if($val){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}
		$sql="SELECT * FROM fac_People $sqlextend ORDER BY LastName ASC, FirstName ASC;";

		$peopleList=array();
		foreach($this->query($sql) as $peopleRow){
			if($indexedbyid){
				$peopleList[$peopleRow["PersonID"]]=People::RowToObject($peopleRow);
			}else{
				$peopleList[]=People::RowToObject($peopleRow);
			}
		}

		return $peopleList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}

class Contact {
	/*	Contact:	A responsible party associated with one or more assets.
					Not to be confused with a user, who is an actual
					user of the DCIM software, and is typically limited
					to data center personnel.
	*/

	var $ContactID;
	var $UserID;
	var $LastName;
	var $FirstName;
	var $Phone1;
	var $Phone2;
	var $Phone3;
	var $Email;

	function MakeSafe(){
		$this->ContactID=intval($this->ContactID);
		$this->UserID=sanitize($this->UserID);
		$this->LastName=sanitize($this->LastName);
		$this->FirstName=sanitize($this->FirstName);
		$this->Phone1=sanitize($this->Phone1);
		$this->Phone2=sanitize($this->Phone2);
		$this->Phone3=sanitize($this->Phone3);
		$this->Email=sanitize($this->Email);
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
		$this->LastName=stripslashes($this->LastName);
		$this->FirstName=stripslashes($this->FirstName);
		$this->Phone1=stripslashes($this->Phone1);
		$this->Phone2=stripslashes($this->Phone2);
		$this->Phone3=stripslashes($this->Phone3);
		$this->Email=stripslashes($this->Email);
	}

	static function RowToObject($row){
		$contact=new Contact();
		$contact->ContactID=$row["ContactID"];
		$contact->UserID=$row["UserID"];
		$contact->LastName=$row["LastName"];
		$contact->FirstName=$row["FirstName"];
		$contact->Phone1=$row["Phone1"];
		$contact->Phone2=$row["Phone2"];
		$contact->Phone3=$row["Phone3"];
		$contact->Email=$row["Email"];

		$contact->MakeDisplay();

		return $contact;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function GetContactByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Contact WHERE ContactID=$this->ContactID;";

		if($row=$this->query($sql)->fetch()){
			foreach(Contact::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			// if a lookup fails just kick back a blank record
			foreach($this as $prop => $value){
				if($prop!='ContactID'){
					$this->$prop='';
				}
			}
		}
		return true;
	}
	
	function GetContactByUserID(){
		$sql="SELECT * FROM fac_Contact WHERE UserID=\"$this->UserID\";";

		if($row=$this->query($sql)->fetch()){
			foreach(Contact::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}else{
			// if a lookup fails just kick back a blank record
			foreach($this as $prop => $value){
				if($prop!='UserID'){
					$this->$prop='';
				}
			}
		}
		return true;
	}

	function CreateContact() {
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_Contact SET UserID=\"$this->UserID\", 
			LastName=\"$this->LastName\", FirstName=\"$this->FirstName\", 
			Phone1=\"$this->Phone1\", Phone2=\"$this->Phone2\", Phone3=\"$this->Phone3\", 
			Email=\"$this->Email\";";

		if($this->exec($sql)){
			$this->ContactID=$dbh->lastInsertId();
			$this->MakeDisplay();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->ContactID;
		}else{
			return false;
		}
	}

	function UpdateContact(){
		$this->MakeSafe();

		$oldcontact=new Contact();
		$oldcontact->ContactID=$this->ContactID;
		$oldcontact->GetContactById();

		$sql="UPDATE fac_Contact SET UserID=\"$this->UserID\", 
			LastName=\"$this->LastName\", FirstName=\"$this->FirstName\", 
			Phone1=\"$this->Phone1\", Phone2=\"$this->Phone2\",	Phone3=\"$this->Phone3\", 
			Email=\"$this->Email\" WHERE ContactID=$this->ContactID;";
       
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldcontact):'';
		$this->query($sql); 
		$this->MakeDisplay();
	}
	function DeleteContact(){
		$this->MakeSafe();

		// Clear up any records that might have still had this contact set as the primary contact.
		$this->query("UPDATE fac_Device SET PrimaryContact=0 WHERE PrimaryContact=$this->ContactID;");
		if($this->exec("DELETE FROM fac_Contact WHERE ContactID=$this->ContactID;")){
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}else{
			return false;
		}
	}

	static function GetContactList(){
		global $dbh;

		$sql="SELECT * FROM fac_Contact ORDER BY LastName ASC;";

		$contactList=array();
		foreach($dbh->query($sql) as $row){
			$contactList[]=Contact::RowToObject($row);
		}

		return $contactList;
	}

    /**
     * Return the list of all contacts indexed by ContactID
     * 
     * @global PDO $dbh
     * @return (Contact)[]
     */
    public static function GetContactListIndexedbyID() {
        global $dbh;
        $deptList = array();
        $stmt = $dbh->prepare('SELECT * FROM fac_Contact');
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $contact = Contact::RowToObject($row);
            $deptList[$contact->ContactID] = $contact;
        }
		return $deptList;
    }

	function GetContactsForDepartment($DeptID){
		$sql="SELECT a.* FROM fac_Contact a, fac_DeptContacts b WHERE 
			a.ContactID=b.ContactID AND b.DeptID=".intval($DeptID)." ORDER BY a.LastName ASC;";

		$contactList=array();
		foreach($this->query($sql) as $row){
			$contactList[$row["ContactID"]]=Contact::RowToObject($row);
		}

		return $contactList;
	}
}

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

		$sql="SELECT * FROM fac_Department WHERE Name LIKE \"%$this->Name%\";";
		if($row=$this->query($sql)->fetch()){
			foreach(Department::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
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
}

class Escalations {
	var $EscalationID;
	var $Details;

	function MakeSafe(){
		$this->EscalationID=intval($this->EscalationID);
		$this->Details=sanitize($this->Details);
	}

	function MakeDisplay(){
		$this->Details=stripslashes($this->Details);
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function CreateEscalation(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_Escalations SET Details=\"$this->Details\";";

		if($this->exec($sql)){
			$this->EscalationID=$dbh->lastInsertId();
			$this->MakeDisplay();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->EscalationID;
		}else{
			return false;
		}
	}
	
	function DeleteEscalation(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_Escalations WHERE EscalationID=$this->EscalationID;";

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->exec($sql);
	}
	
	function GetEscalation(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Escalations WHERE EscalationID=$this->EscalationID;";
		
		if($row=$this->query($sql)->fetch()){
			$this->EscalationID=$row["EscalationID"];
			$this->Details=$row["Details"];
			$this->MakeDisplay();
			return true;
		}else{
			return false;
		}
	}
	
	function GetEscalationList() {
		$sql="SELECT * FROM fac_Escalations ORDER BY Details ASC;";
		
		$escList=array();
		foreach($this->query($sql) as $row){
			$escList[$row["EscalationID"]]=new Escalations();
			$escList[$row["EscalationID"]]->EscalationID=$row["EscalationID"];
			$escList[$row["EscalationID"]]->Details=$row["Details"];
			$escList[$row["EscalationID"]]->MakeDisplay();
		}
		
		return $escList;
	}
	
	function UpdateEscalation(){
		$this->MakeSafe();

		$oldesc=new Escalations();
		$oldesc->EscalationID=$this->EscalationID;
		$oldesc->GetEscalation();

		$sql="UPDATE fac_Escalations SET Details=\"$this->Details\" WHERE 
			EscalationID=$this->EscalationID;";

		$this->MakeDisplay();
			
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldesc):'';
		return $this->query($sql);
	}
}

class EscalationTimes {
	var $EscalationTimeID;
	var $TimePeriod;

	function MakeSafe(){
		$this->EscalationTimeID=intval($this->EscalationTimeID);
		$this->TimePeriod=sanitize($this->TimePeriod);
	}

	function MakeDisplay(){
		$this->TimePeriod=stripslashes($this->TimePeriod);
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function CreatePeriod(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_EscalationTimes SET TimePeriod=\"$this->TimePeriod\";";
		
		if($this->exec($sql)){
			$this->EscalationTimeID=$dbh->lastInsertId();
			$this->MakeDisplay();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->EscalationTimeID;
		}else{
			return false;
		}
	}
	
	function DeletePeriod(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_EscalationTimes WHERE EscalationTimeID=$this->EscalationTimeID;";
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->exec($sql);
	}
	
	function GetEscalationTime(){
		$sql="SELECT * FROM fac_EscalationTimes WHERE EscalationTimeID=$this->EscalationTimeID;";
		
		if($row=$this->query($sql)->fetch()){
			$this->EscalationTimeID=$row["EscalationTimeID"];
			$this->TimePeriod=$row["TimePeriod"];
			$this->MakeDisplay();
			return true;
		}else{
			return false;
		}
	}
	
	function GetEscalationTimeList(){
		$sql="SELECT * FROM fac_EscalationTimes ORDER BY TimePeriod ASC;";
		
		$escList=array();
		foreach($this->query($sql) as $row){
			$escList[$row["EscalationTimeID"]]=new EscalationTimes();
			$escList[$row["EscalationTimeID"]]->EscalationTimeID = $row["EscalationTimeID"];
			$escList[$row["EscalationTimeID"]]->TimePeriod = $row["TimePeriod"];
			$escList[$row["EscalationTimeID"]]->MakeDisplay();
		}
		
		return $escList;
	}
	
	function UpdatePeriod(){
		$this->MakeSafe();

		$oldperiod=new EscalationTimes();
		$oldperiod->EscalationTimeID=$this->EscalationTimeID;
		$oldperiod->GetEscalationTime();

		$sql="UPDATE fac_EscalationTimes SET TimePeriod=\"$this->TimePeriod\" WHERE 
			EscalationTimeID=$this->EscalationTimeID;";
		
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldperiod):'';
		return $this->query($sql);
	}
}

class User {
	/* User:	A user of the DCIM software package.  This is not the same
				as a contact.  A person may be both, and is often the case
				with systems administrators, as they will often need
				access to the DCIM software, and are primary contacts for
				assets located within the data center(s).
	*/
	
	var $UserID;
	var $Name;
	var $AdminOwnDevices;
	var $ReadAccess;
	var $WriteAccess;
	var $DeleteAccess;
	var $ContactAdmin;
	var $RackRequest;
	var $RackAdmin;
	var $SiteAdmin;
	var $Disabled;

	function MakeSafe(){
		$this->UserID=sanitize($this->UserID);
		$this->Name=sanitize($this->Name);
		$this->AdminOwnDevices=intval($this->AdminOwnDevices);
		$this->ReadAccess=intval($this->ReadAccess);
		$this->WriteAccess=intval($this->WriteAccess);
		$this->DeleteAccess=intval($this->DeleteAccess);
		$this->ContactAdmin=intval($this->ContactAdmin);
		$this->RackRequest=intval($this->RackRequest);
		$this->RackAdmin=intval($this->RackAdmin);
		$this->SiteAdmin=intval($this->SiteAdmin);
		$this->Disabled=intval($this->Disabled);
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$user=new User();
		$user->UserID=$row["UserID"];
		$user->Name=$row["Name"];
		$user->AdminOwnDevices=$row["AdminOwnDevices"];
		$user->ReadAccess=$row["ReadAccess"];
		$user->WriteAccess=$row["WriteAccess"];
		$user->DeleteAccess=$row["DeleteAccess"];
		$user->ContactAdmin=$row["ContactAdmin"];
		$user->RackRequest=$row["RackRequest"];
		$user->RackAdmin=$row["RackAdmin"];
		$user->SiteAdmin=$row["SiteAdmin"];
		$user->Disabled=$row["Disabled"];
		$user->MakeDisplay();

		return $user;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}

	function canRead( $Owner ) {
		// If the user has Global rights, don't waste compute cycles on more granular checks
		if ( $this->ReadAccess ) {
			return true;
		}
		
		if ( in_array( $Owner, $this->isMemberOf() ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	function canWrite( $Owner ) {
		// If the user has Global rights, don't wast compute cycles on more granular checks
		if ( $this->WriteAccess ) {
			return true;
		}
		
		if ( in_array( $Owner, $this->isMemberOf() ) && $this->AdminOwnDevices ) {
			return true;
		} else {
			return false;
		}
	}

	function GetUserRights(){
		$this->MakeSafe();
		
		/* Clear out all rights in case the object calling this has been called before */
		foreach($this as $prop => $value){
			if($prop!='Name' && $prop!='UserID'){
				$this->$prop=false;
			}
		}


		$sql="SELECT * FROM fac_User WHERE UserID=\"$this->UserID\";";

		if($row=$this->query($sql)->fetch()){
			foreach(User::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
		}

		/* Just in case someone disabled a user, but didn't remove all of their individual rights */
		if($this->Disabled){
			foreach($this as $prop => $value){
				if($prop!='Name' && $prop!='UserID'){
					$this->$prop=false;
				}
			}
		}
		
		return;
	}

	function GetUserList(){
		/* Return an array of objects relating to all defined users. */
		global $dbh;
		
		$sql="SELECT * FROM fac_User ORDER BY Name ASC;";
		
		$userList=array();
		foreach($dbh->query($sql) as $row){
			$userList[]=User::RowToObject($row);
		}

		return $userList;
	}
	
	function isMemberOf(){
		$this->GetUserRights();
		
		$sql="SELECT DeptID FROM fac_DeptContacts WHERE ContactID IN 
			(SELECT ContactID FROM fac_Contact WHERE UserID=\"$this->UserID\");";

		$deptList=array();
		$deptList[]=0; // This is allowing anyone to use an unassigned rack / device
		if($query=$this->query($sql)){
			foreach($query as $row){
				$deptList[]=$row["DeptID"];
			}
		}

		return $deptList;
	}

	function CreateUser(){
		global $dbh;

		$this->MakeSafe();
		
		/* Create a user record based upon the current object attribute values. */
		$sql="INSERT INTO fac_User VALUES (\"$this->UserID\", \"$this->Name\", 
			$this->AdminOwnDevices, $this->ReadAccess, $this->WriteAccess, 
			$this->DeleteAccess, $this->ContactAdmin, $this->RackRequest, $this->RackAdmin, 
			$this->SiteAdmin, $this->Disabled);";

		$this->MakeDisplay();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $dbh->exec($sql);
	}

	function UpdateUser(){
		global $dbh;
		
		$this->MakeSafe();

		$olduser=new User();
		$olduser->UserID=$this->UserID;
		$olduser->GetUserRights();

		/* Update a user record based upon the current object attribute values, with UserID as key. */
		$sql="UPDATE fac_User SET Name=\"$this->Name\", ReadAccess=$this->ReadAccess, 
			AdminOwnDevices=$this->AdminOwnDevices, WriteAccess=$this->WriteAccess, 
			DeleteAccess=$this->DeleteAccess, ContactAdmin=$this->ContactAdmin, 
			RackRequest=$this->RackRequest, RackAdmin=$this->RackAdmin, 
			SiteAdmin=$this->SiteAdmin, Disabled=$this->Disabled 
			WHERE UserID=\"$this->UserID\";";

		$this->MakeDisplay();

		(class_exists('LogActions'))?LogActions::LogThis($this,$olduser):'';
		return $dbh->exec($sql);
	}

	static function Current(){
		$cuser=new User();

		if ( php_sapi_name() == "cli" ) {
			// If the script is being called from the command line, just give God priveleges and be done with it
			$cuser->ReadAccess = true;
			$cuser->WriteAccess = true;
			$cuser->SiteAdmin = true;
		} else {
			$cuser->UserID=@$_SERVER['REMOTE_USER'];
			$cuser->GetUserRights();
		}
		return $cuser;
	}
}



?>
