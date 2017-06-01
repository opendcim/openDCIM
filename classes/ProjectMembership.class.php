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
class ProjectMembership {
	var $ProjectID;
	var $DeviceID;

	// 	function getProjectMembership
	//
	//	Parameters:  ProjectID
	//
	//	Returns:	Array of Device objects for all members of the given ProjectID
	//
	static function getProjectMembership( $ProjectID, $IndexByID=false ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_ProjectMembership where ProjectID=:ProjectID order by DeviceID ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "ProjectMembership" );
		
		// Since we are using PDO, it is safe to send this blindly to the query.
		$st->execute( array( ":ProjectID"=>$ProjectID ));
		$result = array();
		while ( $row = $st->fetch() ) {
			$d = new Device();
			$d->DeviceID = $row->DeviceID;
			$d->GetDevice();
			if ( $IndexByID == true ) {
				$result[$row->DeviceID] = $d;
			} else {
				$result[] = $d;
			}
		}

		return $result;
	}

	//	function getDeviceMembership
	//
	//	Parameters:	DeviceID
	//
	//	Returns:	Array of Project objects for all projects the DeviceID is a member of
	//
	static function getDeviceMembership( $DeviceID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_ProjectMembership where DeviceID=:DeviceID order by ProjectID ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "ProjectMembership" );

		$st->execute( array( ":DeviceID"=>$DeviceID ));
		$result = array();
		while ( $row = $st->fetch() ) {
			$result[] = Projects::getProject( $row->ProjectID );
		}

		return $result;
	}

	// 	function clearMembership
	//		Removes all devices from the given ProjectID
	//
	//	Parameters:	ProjectID
	//
	//	Returns:	true if success, false if not.
	//
	static function clearMembership( $ProjectID ) {
		global $dbh;

		// Just like above - since we are using prepared statements, it is safe to send blind values
		$st = $dbh->prepare( "delete from fac_ProjectMembership where ProjectID=:ProjectID" );
		return $st->execute( array( ":ProjectID"=>$ProjectID ));
	}

	//	function addMember
	//		Adds the given DeviceID to the membership of the given ProjectID.  In the event that the DeviceID
	//		is already a member, the function will still return success.
	//
	//	Parameters:	ProjectID, DeviceID
	//
	//	Returns:	true if success, false if not
	//
	static function addMember( $ProjectID, $DeviceID ) {
		global $dbh;

		// Just like above - since we are using prepared statements, it is safe to send blind values
		$st = $dbh->prepare( "insert into fac_ProjectMembership set ProjectID=:ProjectID, DeviceID=:DeviceID on duplicate key update DeviceID=DeviceID" );
		return $st->execute( array( ":ProjectID"=>$ProjectID, ":DeviceID"=>$DeviceID ));
	}
}
?>
