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

class MediaTypes {
	var $MediaID;
	var $MediaType;
	var $ColorID;
	
	function CreateType() {
		global $dbh;
		
		$sql="INSERT INTO fac_MediaTypes SET MediaType=\"".sanitize($this->MediaType)."\", 
			ColorID=".intval($this->ColorID);
			
		if($dbh->exec($sql)){
			$this->MediaID=$dbh->lastInsertId();
		}else{
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]}");
			return false;
		}
		
		return $this->MediaID;
	}
	
	function UpdateType() {
		global $dbh;
		
		$sql="UPDATE fac_MediaTypes SET MediaType=\"".sanitize($this->MediaType)."\", 
			ColorID=".intval($this->ColorID)." WHERE MediaID=".intval($this->MediaID);
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]}");
			return false;
		}else{		
			return true;
		}
	}
	
	function DeleteType() {
		/* It is up to the calling application to check to make sure that orphans are not being created! */
		
		global $dbh;
		
		$sql="DELETE FROM fac_MediaTypes WHERE MediaID=".intval($this->MediaID);
		
		return $dbh->exec( $sql );
	}
	
	function GetType() {
		global $dbh;
		
		$sql="SELECT * FROM fac_MediaTypes WHERE MediaID=".intval($this->MediaID);
		
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			$this->MediaType = $row["MediaType"];
			$this->ColorID = $row["ColorID"];
			
			return true;
		}
	}
	
	function GetTypeByName() {
		global $dbh;
		
		$sql="SELECT * FROM fac_MediaTypes WHERE MediaType='".sanitize($this->MediaType)."';";
		
		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			$this->MediaID = $row["MediaID"];
			$this->ColorID = $row["ColorID"];
			
			return true;
		}
	}
	
	static function GetMediaTypeList($indexedby="MediaID") {
		global $dbh;
		
		$sql = "SELECT * FROM fac_MediaTypes ORDER BY MediaType ASC";
		
		$mediaList = array();
	
		foreach ( $dbh->query( $sql ) as $row ) {
			$n=$row[$indexedby];
			$mediaList[$n] = new MediaTypes();
			$mediaList[$n]->MediaID = $row["MediaID"];
			$mediaList[$n]->MediaType = $row["MediaType"];
			$mediaList[$n]->ColorID = $row["ColorID"];
		}
		
		return $mediaList;
	}

	static function ResetType($mediaid,$tomediaid=0){
	/*
	 * This probably shouldn't be a function here since it will only be used in one
	 * place. This function will remove a color code from any device ports or will
	 * set it to another via an optional second color id
	 *
	 */
		global $dbh;
		$mediaid=intval($mediaid);
		$tomediaid=intval($tomediaid); // it will always be 0 unless otherwise set

		$sql="UPDATE fac_DevicePorts SET MediaID='$tomediaid' WHERE MediaID='$mediaid';";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]}");
			return false;
		}else{		
			return true;
		}
	}

	static function TimesUsed($mediaid){
		global $dbh;

		$count=$dbh->prepare('SELECT * FROM fac_DevicePorts WHERE MediaID='.intval($mediaid));
		$count->execute();

		return $count->rowCount();
	}
}
?>
