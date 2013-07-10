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
	protected $DB;

	function __construct(){
		global $dbh;
		$this->DB=$dbh;
	}

	function MakeSafe(){
		$this->ContactID=intval($this->ContactID);
		$this->UserID=addslashes(trim($this->UserID));
		$this->LastName=addslashes(trim($this->LastName));
		$this->FirstName=addslashes(trim($this->FirstName));
		$this->Phone1=addslashes(trim($this->Phone1));
		$this->Phone2=addslashes(trim($this->Phone2));
		$this->Phone3=addslashes(trim($this->Phone3));
		$this->Email=addslashes(trim($this->Email));
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

	static function ContactRowToObject($row){
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

	function GetContactByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Contact WHERE ContactID=$this->ContactID;";

		if($row=$this->DB->query($sql)->fetch()){
			foreach(Contact::ContactRowToObject($row) as $prop => $value){
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

		if($row=$this->DB->query($sql)->fetch()){
			foreach(Contact::ContactRowToObject($row) as $prop => $value){
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
		$this->MakeSafe();

		$sql="INSERT INTO fac_Contact SET UserID=\"$this->UserID\", 
			LastName=\"$this->LastName\", FirstName=\"$this->FirstName\", 
			Phone1=\"$this->Phone1\", Phone2=\"$this->Phone2\", Phone3=\"$this->Phone3\", 
			Email=\"$this->Email\";";

		if($this->DB->exec($sql)){
			$this->ContactID=$dbh->lastInsertId();
			$this->MakeDisplay();
			return $this->ContactID;
		}else{
			return false;
		}
	}

	function UpdateContact(){
		$this->MakeSafe();

		$sql="UPDATE fac_Contact SET UserID=\"$this->UserID\", 
			LastName=\"$this->LastName\", FirstName=\"$this->FirstName\", 
			Phone1=\"$this->Phone1\", Phone2=\"$this->Phone2\",	Phone3=\"$this->Phone3\", 
			Email=\"$this->Email\" WHERE ContactID=$this->ContactID;";
       
		$this->DB->query($sql); 
		$this->MakeDisplay();
	}
	function DeleteContact(){
		// Clear up any records that might have still had this contact set as the primary contact.
		mysql_query("UPDATE fac_Device SET PrimaryContact=0 WHERE PrimaryContact=".intval($this->ContactID).";");
		mysql_query("DELETE FROM fac_Contact WHERE ContactID=".intval($this->ContactID).";");
		return mysql_affected_rows();
	}

	static function GetContactList( $db ) {
		$selectSQL = "select * from fac_Contact order by LastName ASC";
		$result = mysql_query( $selectSQL, $db );

		$contactList = array();

		while ( $contactRow = mysql_fetch_array( $result ) ) {
			$contactID = $contactRow["ContactID"];

			$contactList[$contactID] = new Contact();
			$contactList[$contactID]->ContactID = $contactRow["ContactID"];
			$contactList[$contactID]->UserID = $contactRow["UserID"];
			$contactList[$contactID]->LastName = $contactRow["LastName"];
			$contactList[$contactID]->FirstName = $contactRow["FirstName"];
			$contactList[$contactID]->Phone1 = $contactRow["Phone1"];
			$contactList[$contactID]->Phone2 = $contactRow["Phone2"];
			$contactList[$contactID]->Phone3 = $contactRow["Phone3"];
			$contactList[$contactID]->Email = $contactRow["Email"];
		}

		return $contactList;
	}

	function GetContactsForDepartment( $DeptID, $db ) {
		$selectSQL = "select a.* from fac_Contact a, fac_DeptContacts b where a.ContactID=b.ContactID and b.DeptID=\"" . $DeptID . "\" order by a.LastName ASC";
		$result = mysql_query( $selectSQL, $db );

		$contactList = array();

		while ( $contactRow = mysql_fetch_array( $result ) ) {
			$contactID = $contactRow["ContactID"];

			$contactList[$contactID] = new Contact();
			$contactList[$contactID]->ContactID = $contactRow["ContactID"];
			$contactList[$contactID]->UserID = $contactRow["UserID"];
			$contactList[$contactID]->LastName = $contactRow["LastName"];
			$contactList[$contactID]->FirstName = $contactRow["FirstName"];
			$contactList[$contactID]->Phone1 = $contactRow["Phone1"];
			$contactList[$contactID]->Phone2 = $contactRow["Phone2"];
			$contactList[$contactID]->Phone3 = $contactRow["Phone3"];
			$contactList[$contactID]->Email = $contactRow["Email"];
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

	function CreateDepartment( $db ) {
		$insertSQL = "insert into fac_Department set Name=\"" . addslashes($this->Name) . "\", ExecSponsor=\"" . addslashes($this->ExecSponsor) . "\", SDM=\"" . addslashes($this->SDM) . "\", Classification=\"" . addslashes($this->Classification) . "\", DeptColor=\"" . addslashes($this->DeptColor) . "\"";
		$result = mysql_query( $insertSQL, $db );

		$this->DeptID = mysql_insert_id( $db );

		return $this->DeptID;
	}

	function UpdateDepartment() {
		if($this->DeptColor==""){$this->DeptColor="#FFFFFF";} // New color picker was allowing for an empty value
		$updateSQL = "update fac_Department set Name=\"" . addslashes($this->Name) . "\", ExecSponsor=\"" . addslashes($this->ExecSponsor) . "\", SDM=\"" . addslashes($this->SDM) . "\", Classification=\"" . addslashes($this->Classification) . "\" , DeptColor=\"" . addslashes($this->DeptColor) . "\"where DeptID=\"" . intval($this->DeptID) . "\"";

		$result = mysql_query( $updateSQL );
	}

	function GetDeptByID() {
		$selectSQL = "select * from fac_Department where DeptID=\"" . intval($this->DeptID) . "\"";
		$result = mysql_query( $selectSQL );

		$deptRow = mysql_fetch_array( $result );

		$this->Name = $deptRow["Name"];
		$this->ExecSponsor = $deptRow["ExecSponsor"];
		$this->SDM = $deptRow["SDM"];
		$this->Classification = $deptRow["Classification"];
		$this->DeptColor = $deptRow["DeptColor"];
	}

	function GetDeptByName() {
		$selectSQL="SELECT * FROM fac_Department WHERE Name LIKE \"%".addslashes($this->Name)."%\"";
		$result = mysql_query( $selectSQL);

		$deptRow = mysql_fetch_array( $result );

		$this->DeptID=$deptRow["DeptID"];
		$this->Name = $deptRow["Name"];
		$this->ExecSponsor = $deptRow["ExecSponsor"];
		$this->SDM = $deptRow["SDM"];
		$this->Classification = $deptRow["Classification"];
		$this->DeptColor = $deptRow["DeptColor"];
	}
	function GetDepartmentList() {
		$deptList = array();

		$selectSQL = "select * from fac_Department order by Name ASC";
		$result = mysql_query( $selectSQL);

		while ( $deptRow = mysql_fetch_array( $result ) ) {
			$deptID = $deptRow["DeptID"];

			$deptList[$deptID] = new Department();
			$deptList[$deptID]->DeptID = $deptRow["DeptID"];
			$deptList[$deptID]->Name = $deptRow["Name"];
			$deptList[$deptID]->ExecSponsor = $deptRow["ExecSponsor"];
			$deptList[$deptID]->SDM = $deptRow["SDM"];
			$deptList[$deptID]->Classification = $deptRow["Classification"];
			$deptList[$deptID]->DeptColor = $deptRow["DeptColor"];
		}

		return $deptList;
	}

	function AssignContacts( $MemberList, $db ) {
		// First clear out all previous assignments
		$clearSQL = "delete from fac_DeptContacts where DeptID=\"" . intval($this->DeptID) . "\"";
		mysql_query( $clearSQL, $db );

    if ( is_array( $MemberList ) ) {
      foreach( $MemberList as $ContactID ) {
  			$insertSQL = "insert into fac_DeptContacts set DeptID=\"" . intval($this->DeptID) . "\", ContactID=\"" . intval($ContactID) . "\"";
  
  			mysql_query( $insertSQL, $db );
  		}
  	}
	}
	
	function GetDepartmentByContact($UserID,$db){
		$searchSQL="select a.* from fac_Department a, fac_DeptContacts b, fac_Contact c where a.DeptID=b.DeptID and b.ContactID=c.ContactID and c.UserID=\"".addslashes($UserID)."\"";
	 
		// If someone is assigned to more than one department, just return the first hit
		if($result=mysql_query($searchSQL,$db)){
			$deptRow=mysql_fetch_array($result);
				   
			$this->DeptID=$deptRow["DeptID"];
			$this->GetDeptByID($db);
		}
	}
}

class Escalations {
	var $EscalationID;
	var $Details;

	function CreateEscalation( $db ) {
		$sql = "insert into fac_Escalations set Details=\"" . addslashes( $this->Details ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		$this->EscalationID = mysql_insert_id( $db );
		
		return $this->EscalationID;
	}
	
	function DeleteEscalation( $db ) {
		$sql = "delete from fac_Escalations where EscalationID=\"" . intval( $this->EscalationID ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		return $result;
	}
	
	function GetEscalation( $db ) {
		$sql = "select * from fac_Escalations where EscalationID=\"" . intval( $this->EscalationID ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->EscalationID = $row["EscalationID"];
			$this->Details = $row["Details"];
		}
		
		return;
	}
	
	function GetEscalationList( $db ) {
		$sql = "select * from fac_Escalations order by Details ASC";
		
		$result = mysql_query( $sql, $db );
		
		$escList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$currEsc = sizeof( $escList );
			$escList[$currEsc] = new Escalations();
			
			$escList[$currEsc]->EscalationID = $row["EscalationID"];
			$escList[$currEsc]->Details = $row["Details"];
		}
		
		return $escList;
	}
	
	function UpdateEscalation( $db ) {
		$tmpEsc = new Escalations();
		$sql = "update fac_Escalations set Details=\"" . addslashes( $this->Details ) .
			"\" where EscalationID=\"" . intval( $this->EscalationID ) . "\"";
			
		$result = mysql_query( $sql, $db );
		
		return $result;
	}
}

class EscalationTimes {
	var $EscalationTimeID;
	var $TimePeriod;
	
	function CreatePeriod( $db ) {
		$sql = "insert into fac_EscalationTimes set TimePeriod=\"" . addslashes( $this->TimePeriod ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		$this->EscalationTimeID = mysql_insert_id( $db );
		
		return $this->EscalationTimeID;
	}
	
	function DeletePeriod( $db ) {
		$sql = "delete from fac_EscalationTimes where EscalationTimeID=\"" . intval( $this->EscalationTimeID ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		return $result;
	}
	
	function GetEscalationTime( $db ) {
		$sql = "select * from fac_EscalationTimes where EscalationTimeID=\"" . intval( $this->EscalationTimeID ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->EscalationTimeID = $row["EscalationTimeID"];
			$this->TimePeriod = $row["TimePeriod"];
		}
		
		return;
	}
	
	function GetEscalationTimeList( $db ) {
		$sql = "select * from fac_EscalationTimes order by TimePeriod ASC";
		
		$result = mysql_query( $sql, $db );
		
		$escList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$currEsc = sizeof( $escList );
			$escList[$currEsc] = new EscalationTimes();
			
			$escList[$currEsc]->EscalationTimeID = $row["EscalationTimeID"];
			$escList[$currEsc]->TimePeriod = $row["TimePeriod"];
		}
		
		return $escList;
	}
	
	function UpdatePeriod( $db ) {
		$sql = "update fac_EscalationTimes set TimePeriod=\"" . addslashes( $this->TimePeriod ) . "\" where EscalationTimeID=\"" . intval( $this->EscalationTimeID ) . "\"";
		
		$result = mysql_query( $sql, $db );
		
		return $result;
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

	function GetUserRights( $db = null ) {
		/* Check the table to see if there are any users
		   defined, yet.  If not, this is a new install, so
		   create an admin user (all rights) as the current
		   user.  */
		  
		global $dbh;
		
		$sql = "select count(*) as TotalUsers from fac_User";
		$row = $dbh->query($sql)->fetch();

		if ( $row["TotalUsers"] == 0 ) {
			$this->Name = "Default Admin";
			$this->AdminOwnDevices = false;
			$this->ReadAccess = true;
			$this->WriteAccess = true;
			$this->DeleteAccess = true;
			$this->ContactAdmin = true;
			$this->RackRequest = true;
			$this->RackAdmin = true;
			$this->SiteAdmin = true;
			$this->Disabled = false;

			$this->CreateUser( $db );
		}

		$selectSQL = "select * from fac_User where UserID=\"" . addslashes($this->UserID) . "\"";

		$userRow = $dbh->query( $selectSQL )->fetch();

		$this->Name = $userRow["Name"];
		$this->AdminOwnDevices = $userRow["AdminOwnDevices"];
		$this->ReadAccess = $userRow["ReadAccess"];
		$this->WriteAccess = $userRow["WriteAccess"];
		$this->DeleteAccess = $userRow["DeleteAccess"];
		$this->ContactAdmin = $userRow["ContactAdmin"];
		$this->RackRequest = $userRow["RackRequest"];
		$this->RackAdmin = $userRow["RackAdmin"];
		$this->SiteAdmin = $userRow["SiteAdmin"];
		$this->Disabled = $userRow["Disabled"];

		/* Just in case someone disabled a user, but didn't remove all of their individual rights */
		if ( $this->Disabled ) {
			$this->ReadAccess = false;
			$this->WriteAccess = false;
			$this->DeleteAccess = false;
			$this->ContactAdmin = false;
			$this->RackRequest = false;
			$this->RackAdmin = false;
			$this->SiteAdmin = false;
		}
		
		return;
	}

	function GetUserList( $db = null ) {
		/* Return an array of objects relating to all defined users. */
		global $dbh;
		
		$sql = "select * from fac_User order by Name ASC";
		
		$userList = array();

		foreach ( $dbh->query( $sql ) as $userRow ) {
			$userNum = sizeof( $userList );
			$userList[$userNum] = new User();

			$userList[$userNum]->UserID = $userRow["UserID"];
			$userList[$userNum]->Name = $userRow["Name"];
			$userList[$userNum]->AdminOwnDevices = $userRow["AdminOwnDevices"];
			$userList[$userNum]->ReadAccess = $userRow["ReadAccess"];
			$userList[$userNum]->WriteAccess = $userRow["WriteAccess"];
			$userList[$userNum]->DeleteAccess = $userRow["DeleteAccess"];
			$userList[$userNum]->ContactAdmin = $userRow["ContactAdmin"];
			$userList[$userNum]->RackRequest = $userRow["RackRequest"];
			$userList[$userNum]->RackAdmin = $userRow["RackAdmin"];
			$userList[$userNum]->SiteAdmin = $userRow["SiteAdmin"];
			$userList[$userNum]->Disabled = $userRow["Disabled"];
		}

		return $userList;
	}
	
	function isMemberOf() {
		global $dbh;
		
		$sql = sprintf( "select DeptID from fac_DeptContacts where ContactID in (select ContactID from fac_Contact where UserID='%s')", $this->UserID );
		
		$deptList = array();
		foreach( $dbh->query( $sql ) as $row ) {
			$n = sizeof( $deptList );
			
			$deptList[$n] = $row["DeptID"];
		}
		
		return $deptList;
	}

	function CreateUser( $db = null ) {
		global $dbh;
		
		/* Create a user record based upon the current object attribute values. */
		$sql = sprintf( "insert into fac_User values (\"%s\", \"%s\", %d, %d, %d, %d, %d, %d, %d, %d, %d )", addslashes( $this->UserID ), addslashes( $this->Name ), $this->AdminOwnDevices, $this->ReadAccess, $this->WriteAccess, $this->DeleteAccess, $this->ContactAdmin, $this->RackRequest, $this->RackAdmin, $this->SiteAdmin, $this->Disabled );
		$dbh->exec( $sql );
		
		return;
	}

	function UpdateUser( $db ) {
		global $dbh;
		
		/* Update a user record based upon the current object attribute values, with UserID as key. */
		$sql = sprintf( "update fac_User set Name=\"%s\", AdminOwnDevices=%d, ReadAccess=%d, WriteAccess=%d, DeleteAccess=%d, ContactAdmin=%d, RackRequest=%d, RackAdmin=%d, SiteAdmin=%d, Disabled=%d where UserID=\"%s\"", addslashes( $this->Name ), $this->AdminOwnDevices, $this->ReadAccess, $this->WriteAccess, $this->DeleteAccess, $this->ContactAdmin, $this->RackRequest, $this->RackAdmin, $this->SiteAdmin, $this->Disabled, addslashes( $this->UserID ) );

		$dbh->exec( $sql );

		return;
	}
}



?>
