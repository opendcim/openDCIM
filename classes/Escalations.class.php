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
class Escalations {
	var $EscalationID;
	var $Details;

	function MakeSafe(){
		$this->EscalationID=intval($this->EscalationID);
		$this->Details=sanitize($this->Details);
	}

	function MakeDisplay(){
		$this->Details=stripslashes((string)$this->Details);
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
		
		// if($row=$this->query($sql)->fetch()){
		if($q=$this->query($sql)){
			$row=$q->fetch();
			if($row){
				$this->EscalationID=$row["EscalationID"] ?? '';
				$this->Details=$row["Details"] ?? '';
				$this->MakeDisplay();
				return true;
			}
		}else{
			return false;
		}
		return false;
	}
	
	function GetEscalationList() {
		$sql="SELECT * FROM fac_Escalations ORDER BY Details ASC;";
		
		$escList=array();
		if($stmt=$this->query($sql)){
			foreach($stmt as $row){
				$escID=$row["EscalationID"] ?? null;
				$escList[$escID]=new Escalations();
				$escList[$escID]->EscalationID=$escID;
				$escList[$escID]->Details=$row["Details"] ?? null;
				$escList[$escID]->MakeDisplay();
			}
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

?>
