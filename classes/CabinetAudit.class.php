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

class CabinetAudit {
	/*	CabinetAudit:	A perpetual audit trail for how often a cabinet has been audited, and by what user.
	*/
	
	var $CabinetID;
	var $UserID;
	var $AuditStamp;
	var $Comments;

	function CertifyAudit() {
		if($this->Comments){
			$tmpAudit=new CabinetAudit();
			$tmpAudit->CabinetID=$this->CabinetID;
			(class_exists('LogActions'))?LogActions::LogThis($this,$tmpAudit):'';
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
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
	
	function GetLastAuditByUser() {
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
?>