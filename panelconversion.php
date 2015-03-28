<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$ss = $dbh->prepare( "select * from fac_PowerSource" );
	$ss->setFetchMode( PDO::FETCH_CLASS, "PowerSource" );
	$ss->execute();

	$ps = $dbh->prepare( "insert into fac_PowerPanel set PanelLabel=:PanelLabel" );
	$us = $dbh->prepare( "update fac_PowerPanel set ParentPanelID=:PanelID where PowerSourceID=:SourceID" );
	while ( $row = $ss->fetch() ) {
		$ps->execute( array( ":PanelLabel"=>$row->SourceName ));
		$us->execute( array( ":PanelID"=>$dbh->LastInsertId(), ":SourceID"=>$row->PowerSourceID ) );
	}
?>
