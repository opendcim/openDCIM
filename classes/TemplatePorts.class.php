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
class TemplatePorts {
	var $TemplateID;
	var $PortNumber;
	var $Label;
	var $MediaID;
	var $ColorID;
	var $ConnectorID;
	var $ProtocolID;
	var $RateID;
	var $Notes;
	
	public function __construct($templateid=false){
		if($templateid){
			$this->TemplateID=$templateid;
		}
	}

	function MakeSafe() {
		$this->TemplateID=intval($this->TemplateID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=sanitize($this->Label);
		$this->MediaID=intval($this->MediaID);
		$this->ColorID=intval($this->ColorID);
		$this->ConnectorID=intval($this->ConnectorID);
		$this->ProtocolID=intval($this->ProtocolID);
		$this->RateID=intval($this->RateID);
		$this->Notes=sanitize($this->Notes);
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim((string)$this->Label));
		$this->Notes=stripslashes(trim((string)$this->Notes));
	}

	static function RowToObject($dbRow){
		$tp=new TemplatePorts();
		$tp->TemplateID=$dbRow['TemplateID'] ?? null;
		$tp->PortNumber=$dbRow['PortNumber'] ?? null;
		$tp->Label=$dbRow['Label'] ?? null;
		$tp->MediaID=$dbRow['MediaID'] ?? null;
		$tp->ColorID=$dbRow['ColorID'] ?? null;
		$tp->ConnectorID=$dbRow['ConnectorID'] ?? null;
		$tp->ProtocolID=$dbRow['ProtocolID'] ?? null;
		$tp->RateID=$dbRow['RateID'] ?? null;
		$tp->Notes=$dbRow['Notes'] ?? null;

		$tp->MakeDisplay();

		return $tp;
	}

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function flushPorts( $templateid ) {
		$st = $this->prepare( "delete from fac_TemplatePorts where TemplateID=:TemplateID" );
		return $st->execute( array( ":TemplateID"=>$templateid ) );
	}
	
	function getPort(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		$stmt=$dbh->query($sql);
		if(!$stmt || !($row=$stmt->fetch())){
			return false;
		}else{
			foreach(get_object_vars(TemplatePorts::RowToObject($row)) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID ORDER BY PortNumber ASC;";

		$ports=array();
		$stmt=$dbh->query($sql);
		if($stmt){
			foreach($stmt as $row){
				$ports[$row['PortNumber'] ?? null]=TemplatePorts::RowToObject($row);
			}
		}	
		return $ports;
	}
	
	function createPort() {
		global $dbh;
		
		$this->MakeSafe();
		$sql="INSERT INTO fac_TemplatePorts SET TemplateID=$this->TemplateID, PortNumber=$this->PortNumber, 
			Label=\"$this->Label\", MediaID=$this->MediaID, ColorID=$this->ColorID, ConnectorID=$this->ConnectorID, 
			ProtocolID=$this->ProtocolID, RateID=$this->RateID,
			Notes=\"$this->Notes\";";
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: " . ($info[2] ?? 'Unknown error') . " SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function updatePort() {
		global $dbh;

		$this->MakeSafe();

		$oldport=new TemplatePort();
		$oldport->TemplateID=$this->TemplateID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();

		// update port
		$sql="UPDATE fac_TemplatePorts SET Label=\"$this->Label\", MediaID=$this->MediaID, 
			ColorID=$this->ColorID,	Notes=\"$this->Notes\", ConnectorID=$this->ConnectorID, 
			ProtocolID=$this->ProtocolID, RateID=$this->RateID
			WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: " . ($info[2] ?? 'Unknown error') . " SQL=$sql");
			
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
		return true;
	}

	function removePort(){
		/*	Remove a single port from a template */
		global $dbh;

		if(!$this->getport()){
			return false;
		}

		$sql="DELETE FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			//delete failed, wtf
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}		
	} //END of Class TemplatePorts
	
}
?>
