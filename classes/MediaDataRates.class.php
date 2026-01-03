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
class MediaDataRates {
	public $RateID;
	public $RateText;

	function prepare($sql){
		global $dbh;
		return $dbh->prepare($sql);
	}
	
	function lastID() {
		global $dbh;
		return $dbh->lastInsertID();
	}
	
	static function getRate( $RateID ) {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_MediaDataRates where RateID=:RateID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "MediaDataRates" );
		$st->execute( array( ":RateID"=>$RateID ));
		if ( $row = $st->fetch() ) {
			return $row;
		} else {
			return false;
		}
	}

	static function getRateList() {
		global $dbh;

		$st = $dbh->prepare( "select * from fac_MediaDataRates order by RateText ASC" );
		$st->setFetchMode( PDO::FETCH_CLASS, "MediaDataRates" );
		$st->execute();

		$result = array();
		while ( $row = $st->fetch() ) {
			$result[$row->RateID] = $row;
		}

		return $result;
	}

	static function deleteRate( $RateID, $NewRateID = 0 ) {
		global $dbh;
		
		$oldRate = MediaDataRates::getRate( $RateID );

		// Set any connections using this RateID to have new one (Default 0) instead
		$st = $dbh->prepare( "update fac_Ports set RateID=:NewRateID where RateID=:RateID" );
		$st->execute( array( ":RateID"=>$RateID, ":NewRateID"=>$NewRateID ));

		$st = $dbh->prepare( "delete from fac_MediaDataRates where RateID=:RateID" );
		if ( $st->execute( array( ":RateID"=>$RateID ))) {
			(class_exists('LogActions'))?LogActions::LogThis($oldRate):'';
			return true;
		} else {
			return false;
		}
	}

	function createRate( $RateText ) {
		$st = $this->prepare( "insert into fac_MediaDataRates set RateText=:RateText" );
		$st->execute( array( 	":RateText"=>$RateText ) );
		$this->RateID = $this->lastID();

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->RateID;
	}

	function updateRate() {
		$oldRate = MediaDataRates::getRate( $this->RateID );

		$st = $this->prepare( "update fac_MediaDataRates set RateText=:RateText where RateID=:RateID" );

		if( $st->execute( array( ":RateID"=>$this->RateID, ":RateText"=>$this->RateText ) ) ) {
			(class_exists('LogActions'))?LogActions::LogThis($this, $oldRate):'';
			return true;
		} else {
			return false;
		}
	}

	static function TimesUsed($id){
		global $dbh;

		$count=$dbh->prepare('SELECT * FROM fac_Ports WHERE RateID='.intval($id));
		$count->execute();

		return $count->rowCount();
	}
}
?>
