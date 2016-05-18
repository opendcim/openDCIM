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
class Projects {
	//
	//	Projects
	//
	//	Projects can also be thought of as services in terms of a service catalog.  The premise is to create a record with metadata regarding
	//	who the ProjectSponsor (or Product Manager/Service Manager) is, along with the lifetime information regarding that project.  Devices
	//	may then be added to the project.  Devices can be members of multiple projects, and projects can have multiple devices in them.
	//
	//	The inclusion of this information will allow reports to be tailored such that they can report on the projects/services affected rather
	//	than simply spitting out hundreds of devices, which can be especially helpful when trying to run a power outage simulation report.
	//
	public $ProjectID;
	public $ProjectName;
	public $ProjectSponsor;
	public $ProjectStartDate;
	public $ProjectExpirationDate;
	public $ProjectActualEndDate;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	static function getProject( $ProjectID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_Projects where ProjectID=:ProjectID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "Projects" );
		$st->execute( array( ":ProjectID"=>$ProjectID ));
		if ( $row = $st->fetch() ) {
			return $row;
		} else {
			return false;
		}
	}

	static function getProjectList( $orderBy = "ProjectName" ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_Projects order by " . $orderBy . " ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "Projects" );
		$st->execute();

		$result = array();
		while ( $row = $st->fetch() ) {
			$result[] = $row;
		}

		return $result;
	}

	static function deleteProject( $ProjectID ) {
		global $dbh;

		$oldProject = Projects::getProject( $ProjectID );
		if ( ProjectMembership::clearMembership( $ProjectID ) ) {
			$st = $dbh->prepare( "delete from fac_Projects where ProjectID=:ProjectID" );
			if ( $st->execute( array( ":ProjectID"=>$ProjectID ) ) ) {
				(class_exists('LogActions'))?LogActions::LogThis($oldProject):'';
				return true;
			}
		} else {
			return false;
		}
	}

	function createProject() {
		$st = $this->prepare( "insert into fac_Projects set ProjectName=:ProjectName, ProjectSponsor=:ProjectSponsor, ProjectStartDate=:ProjectStartDate,
			ProjectExpirationDate=:ProjectExpirationDate, ProjectActualEndDate=:ProjectActualEndDate" );
		$st->execute( array( 	":ProjectName"=>$this->ProjectName,
								":ProjectSponsor"=>$this->ProjectSponsor,
								":ProjectStartDate"=>$this->ProjectStartDate,
								":ProjectExpirationDate"=>$this->ProjectExpirationDate,
								":ProjectActualEndDate"=>$this->ProjectActualEndDate ) );
		$this->ProjectID = $this->lastID();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->ProjectID;
	}

	function updateProject() {
		$oldProject = Projects::getProject( $this->ProjectID );

		$st = $this->prepare( "update fac_Projects set ProjectName=:ProjectName, ProjectSponsor=:ProjectSponsor, ProjectStartDate=:ProjectStartDate,
			ProjectExpirationDate=:ProjectExpirationDate, ProjectActualEndDate=:ProjectActualEndDate where ProjectID=:ProjectID" );
		if( $st->execute( array( 	":ProjectName"=>$this->ProjectName,
								":ProjectSponsor"=>$this->ProjectSponsor,
								":ProjectStartDate"=>$this->ProjectStartDate,
								":ProjectExpirationDate"=>$this->ProjectExpirationDate,
								":ProjectActualEndDate"=>$this->ProjectActualEndDate,
								":ProjectID"=>$this->ProjectID ) ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this, $oldProject):'';
			return true;
		} else {
			return false;
		}
	}

	function Search($indexedbyid=false,$loose=false){
		// This will store all our extended sql
		$sqlextend="";
		$args = array();
		foreach($this as $prop => $val){
			if ( isset( $val ) ) {
				$method=($loose)?" LIKE \":" . $prop . "%\"":"=:" . $prop;
				if ($sqlextend) {
					$sqlextend .= " AND $prop$method";
				} else {
					$sqlextend .= " WHERE $prop$method";
				}
				$args[":" . $prop] = $val;
			}
		}
		$st = $this->prepare( "select * from fac_Projects " . $sqlextend . " ORDER BY ProjectName ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "Projects" );
		$st->execute( $args );
		$projectList=array();
		while( $row = $st->fetch() ) {
			if($indexedbyid){
				$projectList[$row["ProjectID"]]=$row;
			}else{
				$projectList[]=$row;
			}
		}

		return $projectList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}
?>
