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


class DataCenterLog {
	/* DataCenterLog:	Class for logging entry in and out of the data center.
	*/
	var $EntryID;
	var $DataCenterID;
	var $UserID;
	var $EscortRequired;
	var $RequestTime;
	var $Reason;
	var $AuthorizedBy;
	var $TimeIn;
	var $TimeOut;
	var $GuestList;

	function AddRequest( $db ) {
	  $sql = "insert into fac_DataCenterLog set DataCenterID=\"" . $this->DataCenterID . "\",
	    UserID=\"" . $this->UserID . "\", EscortRequired=\"" . $this->EscortRequired . "\", RequestTime=now(),
      Reason=\"" . addslashes( $this->Reason ) . "\", GuestList=\"" . addslashes( $this->GuestList ) . "\"";

		$result = mysql_query( $sql, $db );
		
		$this->EntryID = mysql_insert_id( $db );
		
    if ( $this->EntryID < 1 ) {
		   printf( "<h3>Error adding request:  %s</h3>\n", $sql );
		   exit;
    }
		
		return;
	}

	function GetRequest( $db ) {
	  $sql = "select * from fac_DataCenterLog where EntryID=\"" . $this->EntryID . "\"";
	  $result = mysql_query( $sql, $db );
	  
	  if ( $row = mysql_fetch_array( $result ) ) {
	    $this->EntryID = $row["EntryID"];
	    $this->DataCenterID = $row["DataCenterID"];
      $this->UserID = $row["UserID"];
      $this->EscortRequired = $row["EscortRequired"];
      $this->RequestTime = $row["RequestTime"];
      $this->Reason = $row["Reason"];
 	    $this->GuestList = $row["GuestList"];
		}
		
		return;
	}

	function GetOpenRequests( $db ) {
	  $sql = "select * from fac_DataCenterLog where TimeIn='0000-00-00 00:00:00' and TimeOut='0000-00-00 00:00:00' order by RequestTime ASC";
	  $result = mysql_query( $sql, $db );
	  
	  $reqList = array();
	  while ( $row = mysql_fetch_array( $result ) ) {
	    $reqNum = sizeof( $reqList );
	    
	    $reqList[$reqNum] = new DataCenterLog();
	    $reqList[$reqNum]->EntryID = $row["EntryID"];
	    $reqList[$reqNum]->DataCenterID = $row["DataCenterID"];
      $reqList[$reqNum]->UserID = $row["UserID"];
      $reqList[$reqNum]->EscortRequired = $row["EscortRequired"];
      $reqList[$reqNum]->RequestTime = $row["RequestTime"];
      $reqList[$reqNum]->Reason = $row["Reason"];
 	    $reqList[$reqNum]->GuestList = $row["GuestList"];
		}
		
		return $reqList;
	}
	
	function GetOpenRequestsByDC( $db ) {
	  $sql = "select * from fac_DataCenterLog where DataCenterID=\"" . $this->DataCenterID . "\"
			and TimeIn='0000-00-00 00:00:00' and TimeOut='0000-00-00 00:00:00' order by RequestTime ASC";
	  $result = mysql_query( $sql, $db );

	  $reqList = array();
	  while ( $row = mysql_fetch_array( $result ) ) {
	    $reqNum = sizeof( $reqList );

	    $reqList[$reqNum] = new DataCenterLog();
      $reqList[$reqNum]->EntryID = $row["EntryID"];
			$reqList[$reqNum]->DataCenterID = $row["DataCenterID"];
      $reqList[$reqNum]->UserID = $row["UserID"];
      $reqList[$reqNum]->EscortRequired = $row["EscortRequired"];
      $reqList[$reqNum]->RequestTime = $row["RequestTime"];
      $reqList[$reqNum]->Reason = $row["Reason"];
 	    $reqList[$reqNum]->GuestList = $row["GuestList"];
		}

		return $reqList;
	}
	
	function RemoveRequest( $db ) {
    $sql = "update fac_DataCenterLog set TimeIn=now(), TimeOut=now(), AuthorizedBy=\"DENIED\" where EntryID=\"" . $this->EntryID . "\"";
    
    mysql_query( $sql, $db );
  }

	function ApproveEntry( $db, $grantingAuthority ) {
		$sql = "update fac_DataCenterLog set AuthorizedBy=\"" . $grantingAuthority . "\", TimeIn=now()
		  where EntryID=\"" . $this->EntryID . "\"";
		$result = mysql_query( $sql, $db );
		
		return;
	}
	
	function GetDCOccupants( $db ) {
	  $sql = "select * from fac_DataCenterLog where TimeIn>'0000-00-00 00:00:00' and
			TimeOut='0000-00-00 00:00:00'";
		$result = mysql_query( $sql, $db );
		
		$reqList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
	    $reqNum = sizeof( $reqList );

	    $reqList[$reqNum] = new DataCenterLog();
      $reqList[$reqNum]->EntryID = $row["EntryID"];
			$reqList[$reqNum]->DataCenterID = $row["DataCenterID"];
      $reqList[$reqNum]->UserID = $row["UserID"];
      $reqList[$reqNum]->EscortRequired = $row["EscortRequired"];
      $reqList[$reqNum]->RequestTime = $row["RequestTime"];
      $reqList[$reqNum]->Reason = $row["Reason"];
 	    $reqList[$reqNum]->GuestList = $row["GuestList"];
 	    $reqList[$reqNum]->TimeIn = $row["TimeIn"];
 	    $reqList[$reqNum]->AuthorizedBy = $row["AuthorizedBy"];
		}

		return $reqList;
	}

	function CloseEntry( $db ) {
	  $sql = "update fac_DataCenterLog set TimeOut=now() where EntryID=\"" . $this->EntryID . "\"";
	    
	 	$result = mysql_query( $sql, $db );

	 	return;
	}
}

class Resource {
	var $ResourceID;
	var $CategoryID;
	var $Description;
	var $UniqueID;
	var $Active;
	var $Status;
	
	function GetResource( $db ) {
		$sql = "select * from fac_Resource where ResourceID=\"" . $this->ResourceID . "\"";
		$result = mysql_query( $sql, $db );
		
		// printf( "<h3>GetResource->%s</h3>", $sql );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->CategoryID = $row["CategoryID"];
			$this->Description = $row["Description"];
			$this->UniqueID = $row["UniqueID"];
			$this->Active = $row["Active"];
			$this->Status = $row["Status"];
		}
		
		return;
	}
	
	function GetResources( $db ) {
		$sql = "select * from fac_Resource order by CategoryID ASC, Description ASC";
		$result = mysql_query( $sql, $db );

		$resourceList = array();

		while ( $row = mysql_fetch_array( $result ) ) {
			$resNum = sizeof( $resourceList );
			$resourceList[$resNum] = new Resource();

			$resourceList[$resNum]->ResourceID = $row["ResourceID"];
			$resourceList[$resNum]->CategoryID = $row["CategoryID"];
			$resourceList[$resNum]->Description = $row["Description"];
			$resourceList[$resNum]->UniqueID = $row["UniqueID"];
			$resourceList[$resNum]->Active = $row["Active"];
			$resourceList[$resNum]->Status = $row["Status"];
		}

		return $resourceList;
	}

	function GetActiveResources( $db ) {
		$sql = "select * from fac_Resource where Active=true order by CategoryID ASC, Description ASC";
		$result = mysql_query( $sql, $db );
		
		$resourceList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$resNum = sizeof( $resourceList );
			$resourceList[$resNum] = new Resource();
			
			$resourceList[$resNum]->ResourceID = $row["ResourceID"];
			$resourceList[$resNum]->CategoryID = $row["CategoryID"];
			$resourceList[$resNum]->Description = $row["Description"];
			$resourceList[$resNum]->UniqueID = $row["UniqueID"];
			$resourceList[$resNum]->Active = $row["Active"];
			$resourceList[$resNum]->Status = $row["Status"];
		}
		
		return $resourceList;
	}
	
	function GetActiveResourcesByCategory( $db ) {
		$sql = "select * from fac_Resource where Active=true and CategoryID=\"" . $this->CategoryID . "\" order by Description ASC";
		$result = mysql_query( $sql, $db );
		
		$resourceList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$resNum = sizeof( $resourceList );
			$resourceList[$resNum] = new Resource();
			
			$resourceList[$resNum]->ResourceID = $row["ResourceID"];
			$resourceList[$resNum]->CategoryID = $row["CategoryID"];
			$resourceList[$resNum]->Description = $row["Description"];
			$resourceList[$resNum]->UniqueID = $row["UniqueID"];
			$resourceList[$resNum]->Active = $row["Active"];
			$resourceList[$resNum]->Status = $row["Status"];
		}
		
		return $resourceList;
	}
	
  function CreateResource( $db ) {
		if ( $this->CategoryID < 1 )
		  return;

		if ( sizeof( $this->Description ) == 0 )
		  return;

		$sql = "insert into fac_Resource set CategoryID=\"" . $this->CategoryID . "\",
			Description=\"" . $this->Description . "\", UniqueID=\"" . $this->UniqueID . "\",
			Active=\"" . $this->Active . "\", Status=\"Available\"";
		$result = mysql_query( $sql, $db );
		
		$this->ResourceID = mysql_insert_id( $db );
		
		return;
  }
  
  function UpdateResource( $db ) {
		$sql = "update fac_Resource set CategoryID=\"" . $this->CategoryID . "\", Description=\"" . $this->Description . "\",
			UniqueID=\"" . $this->UniqueID . "\", Active=\"" . $this->Active . "\", Status=\"" . $this->Status . "\" where
			ResourceID=\"" . $this->ResourceID . "\"";
		$result = mysql_query( $sql, $db );
		
		// printf( "<h3>UpdateResource->%s</h3>", $sql );
		
		return $result;
	}				 
}

class ResourceCategory {
	var $CategoryID;
	var $Description;
	
	function GetCategory( $db ) {
		$sql = "select * from fac_ResourceCategory where CategoryID=\"" . $this->CategoryID . "\"";
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->Description = $row["Description"];
		}
		
		return;
	}
	
	function GetCategoryList( $db ) {
		$sql = "select * from fac_ResourceCategory order by Description ASC";
		$result = mysql_query( $sql, $db );
		
		$catList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$catNum = sizeof( $catList );
			$catList[$catNum] = new ResourceCategory();
			
			$catList[$catNum]->CategoryID = $row["CategoryID"];
			$catList[$catNum]->Description = $row["Description"];
		}
		
		return $catList;
	}
}

class ResourceLog {
	var $ResourceID;
	var $UserID;
	var $Note;
	var $RequestedTime;
	var $TimeOut;
	var $EstimatedReturn;
	var $ActualReturn;
	
	function GetCurrentStatus( $db ) {
    $sql = "select * from fac_ResourceLog where ResourceID=\"" . $this->ResourceID . "\" order by RequestedTime DESC limit 1";
    $result = mysql_query( $sql, $db );

    if ( $row = mysql_fetch_array( $result ) ) {
      $this->UserID = $row["UserID"];
      $this->Note = $row["Note"];
      $this->RequestedTime = $row["RequestedTime"];
      $this->TimeOut = $row["TimeOut"];
      $this->EstimatedReturn = $row["EstimatedReturn"];
      $this->ActualReturn = $row["ActualReturn"];
    }

    return;
  }
 
	function RequestResource( $db ) {
		// First check to see if the resource is active, or still checked out
		$res = new Resource();
		$res->ResourceID = $this->ResourceID;
		$res->GetResource( $db );
		
		// print_r( $res );
				
		if ( $res->Status == "Out" ) {
			$this->CheckinResource( $db );
		}

		$res->Status = "Reserved";
		$res->UpdateResource( $db );
		
		$sql = "insert into fac_ResourceLog set ResourceID=\"" . $this->ResourceID . "\", 
			UserID=\"" . $this->UserID . "\", Note=\"" . addslashes( $this->Note ) . "\", RequestedTime=now(), EstimatedReturn=\"" . $this->EstimatedReturn . "\"";
		$result = mysql_query( $sql, $db );
		
		// printf( "<h3>RequestResource->%s</h3>", $sql );
		return;		 
	}
	
	function CheckoutResource( $db ) {
		$res = new Resource();
		$res->ResourceID = $this->ResourceID;
		$res->GetResource( $db );

		// print_r( $res );

		$res->Status = "Out";
		$res->UpdateResource( $db );

		$sql = "update fac_ResourceLog set TimeOut=now() where ResourceID=\"" . $this->ResourceID . "\" and TimeOut='0000-00-00 00:00:00'";
		$result = mysql_query( $sql, $db );

		// printf( "<h3>RequestResource->%s</h3>", $sql );
		return;
	}

	function CheckinResource( $db ) {		
		// First check to see if the resource is active
		$res = new Resource();
		$res->ResourceID = $this->ResourceID;
		$res->GetResource( $db );
		
		if ( $res->Active == false ) {
			return;
		}
		
		$res->Status = "Available";
		$res->UpdateResource( $db );

		$sql = "update fac_ResourceLog set ActualReturn=now() where ResourceID=\"" . $this->ResourceID . "\" and ActualReturn=\"0000-00-00 00:00:00\"";
		$result = mysql_query( $sql, $db );
		
		// printf( "<h3>CheckinResource->%s</h3>", $sql );
	}
}

?>