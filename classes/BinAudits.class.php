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
class BinAudits {
	var $BinID;
	var $UserID;
	var $AuditStamp;

	function MakeSafe(){
		$this->BinID=intval($this->BinID);
		$this->UserID=sanitize($this->UserID);
		$this->AuditStamp=sanitize($this->AuditStamp);
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
		$this->AuditStamp=stripslashes($this->AuditStamp);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function AddAudit(){
		$this->AuditStamp=date("Y-m-d",strtotime($this->AuditStamp));
		$this->MakeSafe();

		$sql="INSERT INTO fac_BinAudits SET BinID=$this->BinID, UserID=\"$this->UserID\", AuditStamp=\"$this->AuditStamp\";";
		$this->exec($sql);
	}
}
?>
