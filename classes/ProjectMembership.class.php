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
	var $MemberType;
	var $MemberID;

	// 	function getProjectCabinets
	//
	//	Parameters:  ProjectID
	//
	//	Returns:	Array of Cabinet objects for all members of the given ProjectID
	//
	static function getProjectCabinets( $ProjectID, $IndexByID=false ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_ProjectMembership where ProjectID=:ProjectID and MemberType='Cabinet' order by MemberID ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "ProjectMembership" );
		
		// Since we are using PDO, it is safe to send this blindly to the query.
		$st->execute( array( ":ProjectID"=>$ProjectID ));
		$result = array();
		while ( $row = $st->fetch() ) {
			$c = new Cabinet();
			$c->CabinetID = $row->MemberID;
			$c->GetCabinet();
			if ( $IndexByID == true ) {
				$result[$c->CabinetID] = $c;
			} else {
				$result[] = $c;
			}
		}

		return $result;
	}

	// 	function getProjectMembership
	//
	//	Parameters:  ProjectID
	//
	//	Returns:	Array of Device objects for all members of the given ProjectID
	//
	static function getProjectMembership( $ProjectID, $IndexByID=false, $Inherited = true ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_ProjectMembership where ProjectID=:ProjectID and MemberType='Device' order by MemberID ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "ProjectMembership" );
		
		// Since we are using PDO, it is safe to send this blindly to the query.
		$st->execute( array( ":ProjectID"=>$ProjectID ));
		$result = array();
		while ( $row = $st->fetch() ) {
			$d = new Device();
			$d->DeviceID = $row->MemberID;
			$d->GetDevice();
			if ( $IndexByID == true ) {
				$result[$d->DeviceID] = $d;
			} else {
				$result[] = $d;
			}
		}

		//	Now get all of the devices that are Project Members, but only if we're not asking for direct membership only
		if ( $Inherited ) {
			$st = $dbh->prepare( "select DeviceID from fac_Device where Cabinet in (select MemberID from fac_ProjectMembership where MemberType='Cabinet' and ProjectID=:ProjectID)" );
			$st->setFetchMode( PDO::FETCH_NUM );

			$st->execute( array( ":ProjectID"=>$ProjectID ));
			while ( $row = $st->fetch() ) {
				$d = new Device();
				$d->DeviceID = $row[0];
				$d->GetDevice();
				if ( $IndexByID == true ) {
					$result[$d->DeviceID] = $d;
				} else {
					$result[] = $d;
				}
			}
		}

		return $result;
	}

	//	function getCabinetMembership
	//	
	//	Parameters:	CabinetID
	//	
	//	Returns:	Array of Project objects for all projects the CabinetID is a member of
	//	
	static function getCabinetMembership( $CabinetID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_ProjectMembership where MemberType='Cabinet' and MemberID=:CabinetID order by ProjectID ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "ProjectMembership" );

		$st->execute( array( ":CabinetID"=>$CabinetID ));
		$result = array();
		while ( $row = $st->fetch() ) {
			$result[] = Projects::getProject( $row->ProjectID );
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

		$st = $dbh->prepare( "select * from fac_ProjectMembership where (MemberType='Device' and MemberID=:DeviceID) or (MemberType='Cabinet' and MemberID in (select Cabinet from fac_Device where DeviceID=:DeviceID)) order by ProjectID ASC" );
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
	//		Adds the given MemberID to the membership of the given ProjectID.  In the event that the DeviceID
	//		is already a member, the function will still return success.
	//
	//	Parameters:	ProjectID, MemberID, MemberType
	//
	//	Returns:	true if success, false if not
	//
	static function addMember( $ProjectID, $MemberID, $MemberType = 'Device' ) {
		global $dbh;

		$MemberType = in_array( $MemberType, array( "Device", "Cabinet" ))?$MemberType:"Device";

		// Just like above - since we are using prepared statements, it is safe to send blind values
		$st = $dbh->prepare( "insert into fac_ProjectMembership set ProjectID=:ProjectID, MemberType=:MemberType, MemberID=:MemberID on duplicate key update MemberID=MemberID" );
		return $st->execute( array( ":ProjectID"=>$ProjectID, ":MemberType"=>$MemberType, ":MemberID"=>$MemberID ));
	}

	//	function removeMember
	//		Removes the specified member from the specified ProjectID.
	//
	//	Parameters:	ProjectID, MemberID, MemberType
	//
	//	Returns:  	true if success, false if now
	//
	static function removeMember( $MemberID, $MemberType, $ProjectID = 0 ) {
		global $dbh;

		$MemberType = in_array( $MemberType, array( "Device", "Cabinet" ))?$MemberType:"Device";

		if ( $ProjectID > 0 ) {
			// Just like above - since we are using prepared statements, it is safe to send blind values
			$st = $dbh->prepare( "delete from fac_ProjectMembership where ProjectID=:ProjectID and MemberType=:MemberType and MemberID=:MemberID" );
			return $st->execute( array( ":ProjectID"=>$ProjectID, ":MemberType"=>$MemberType, ":MemberID"=>$MemberID ));
		} else {
			$st = $dbh->prepare( "delete from fac_ProjectMembership where MemberType=:MemberType and MemberID=:MemberID" );
			return $st->execute( array( ":MemberType"=>$MemberType, ":MemberID"=>$MemberID ));
		}
	}
}
?>
