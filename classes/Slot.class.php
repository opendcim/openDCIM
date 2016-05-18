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
class Slot {
	var $TemplateID;
	var $Position;
	var $BackSide;
	var $X;
	var $Y;
	var $W;
	var $H;

	function MakeSafe(){
		$this->TemplateID=intval($this->TemplateID);
		$this->Position=intval($this->Position);
		$this->BackSide=intval($this->BackSide);
		$this->X=abs($this->X);
		$this->Y=abs($this->Y);
		$this->W=abs($this->W);
		$this->H=abs($this->H);
	}

	static function RowToObject($row){
		$slot=New Slot();
		$slot->TemplateID=$row["TemplateID"];
		$slot->Position=$row["Position"];
		$slot->BackSide=$row["BackSide"];
		$slot->X=$row["X"];
		$slot->Y=$row["Y"];
		$slot->W=$row["W"];
		$slot->H=$row["H"];

		return $slot;
	}
 
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateSlot(){
		global $dbh;
			
		$this->MakeSafe();
			
		$sql="INSERT INTO fac_Slots SET TemplateID=$this->TemplateID, 
			Position=$this->Position,
			BackSide=$this->BackSide,
			X=$this->X,
			Y=$this->Y,
			W=$this->W,
			H=$this->H
			;";
		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
	}
	
	function UpdateSlot(){
		$this->MakeSafe();

		$oldslot=new Slot();
		$oldslot->TemplateID=$this->TemplateID;
		$oldslot->Position=$this->Position;
		$oldslot->BackSide=$this->BackSide;
		$oldslot->GetSlot();
			
		$sql="UPDATE fac_Slots SET X=$this->X, Y=$this->Y, W=$this->W, H=$this->H 
			WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND 
			BackSide=$this->BackSide;";

		if(!$this->query($sql)){
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldslot):'';
		return true;
	}
	
	function DeleteSlot(){
		$this->MakeSafe();
		
		//delete slot
		$sql="DELETE FROM fac_Slots WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND BackSide=$this->BackSide;";
		if(!$this->query($sql)){
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}
  
	function GetSlot(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Slots WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND BackSide=$this->BackSide;";
		if($row=$this->query($sql)->fetch()){
			foreach(Slot::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
	
	static function getSlots( $TemplateID ) {
		global $dbh;
		
		$st = $dbh->prepare( "select * from fac_Slots where TemplateID=:TemplateID order by BackSide ASC, Position ASC" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Slot" );
		$sList = array();
		while ( $row = $st->fetch() ) {
			$sList[] = $row;
		}
		
		return $sList;
	}

	// Return all the slots for a single template in one object
	static function GetAll($templateid){
		global $dbh;
		
		$sql="SELECT * FROM fac_Slots WHERE TemplateID=".intval($templateid)." ORDER 
			BY BackSide ASC, Position ASC;";
		$slots=array();
		foreach($dbh->query($sql) as $row){
			$slots[$row['BackSide']][$row['Position']]=Slot::RowToObject($row);
		}	
		return $slots;
	}

	function GetFirstSlot(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Slots WHERE TemplateID=$this->TemplateID ORDER BY BackSide,Position;";
		if($row=$this->query($sql)->fetch()){
			foreach(Slot::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	} 
}
?>