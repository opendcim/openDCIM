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
	var $Notes;
	
	public function __construct($templateid=false){
		if($templateid){
			$this->TemplateID=$templateid;
		}
		return $this;
	}

	function MakeSafe() {
		$this->TemplateID=intval($this->TemplateID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=sanitize($this->Label);
		$this->MediaID=intval($this->MediaID);
		$this->ColorID=intval($this->ColorID);
		$this->Notes=sanitize($this->Notes);
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim($this->Label));
		$this->Notes=stripslashes(trim($this->Notes));
	}

	static function RowToObject($dbRow){
		$tp=new TemplatePorts();
		$tp->TemplateID=$dbRow['TemplateID'];
		$tp->PortNumber=$dbRow['PortNumber'];
		$tp->Label=$dbRow['Label'];
		$tp->MediaID=$dbRow['MediaID'];
		$tp->ColorID=$dbRow['ColorID'];
		$tp->Notes=$dbRow['Notes'];

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

		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(TemplatePorts::RowToObject($row) as $prop => $value){
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
		foreach($dbh->query($sql) as $row){
			$ports[$row['PortNumber']]=TemplatePorts::RowToObject($row);
		}	
		return $ports;
	}
	
	function createPort() {
		global $dbh;
		
		$this->MakeSafe();
		$sql="INSERT INTO fac_TemplatePorts SET TemplateID=$this->TemplateID, PortNumber=$this->PortNumber, 
			Label=\"$this->Label\", MediaID=$this->MediaID, ColorID=$this->ColorID, 
			Notes=\"$this->Notes\";";
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: {$info[2]} SQL=$sql");
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
			ColorID=$this->ColorID,	Notes=\"$this->Notes\", 
			WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			
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
