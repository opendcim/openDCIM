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
	var $BulkOperations;
	var $SiteAdmin;
	var $APIKey;
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
		$this->BulkOperations=intval($this->BulkOperations);
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
		$this->BulkOperations=intval($this->BulkOperations);
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
		$person->BulkOperations=$row["BulkOperations"];
		$person->SiteAdmin=$row["SiteAdmin"];
		$person->APIKey=$row["APIKey"];
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

	function revokeAll() {
		$this->ReadAccess = false;
		$this->WriteAccess = false;
		$this->DeleteAccess = false;
		$this->AdminOwnDevices = false;
		$this->RackRequest = false;
		$this->RackAdmin = false;
		$this->ContactAdmin = false;
		$this->BulkOperations = false;
		$this->SiteAdmin = false;
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
			RackAdmin=$this->RackAdmin, BulkOperations=$this->BulkOperations, SiteAdmin=$this->SiteAdmin,
			APIKey=\"$this->APIKey\", Disabled=$this->Disabled;";

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
			$cperson->GetUserRights( true );
		} elseif(AUTHENTICATION=="Oauth" || AUTHENTICATION=="LDAP" || AUTHENTICATION=="Saml"){
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

			return true;
		} else {
			// Kick back a blank record if the PersonID was not found
			foreach ( $this as $prop => $value ) {
				if ( $prop!='PersonID' ) {
					$this->$prop = '';
				}
			}

			return false;
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
	
	function GetUserRights( $templateNewUsers = false ) {
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
		} elseif ( $templateNewUsers ) {
			// For Apache Auth, set up a user based upon a template profile if they don't exist in the db already.  If no template exists, just ignore.
			$newPerson = new People;

			$newPerson->UserID = "_DEFAULT_";
			if ( $newPerson->GetPersonByUserID() ) {
				$newPerson->UserID = $this->UserID;
				$newPerson->FirstName="";
				$newPerson->LastName="";
				$newPerson->Email="";
				$newPerson->CreatePerson();
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
			RackAdmin=$this->RackAdmin, BulkOperations=$this->BulkOperations, SiteAdmin=$this->SiteAdmin,
			APIKey=\"$this->APIKey\", Disabled=$this->Disabled
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
?>
