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

class PowerPanel {
	/* PowerPanel:	PowerPanel(s) are the parents of PowerDistribution (power strips) and
					the children each other.  Panels are arranged as either Odd/Even (odd
					numbers on the left,even on the right), Busway, or Sequential (1 to N
					in a single column) numbering for the purpose of building out a panel
					schedule.  If a PowerPanel has no ParentPanelID defined	then it is
					considered to be the PowerSource.  In other words, it's a reverse linked
					list.
	*/
	
	var $PanelID;
	var $PanelLabel;
	var $NumberOfPoles;
	var $MainBreakerSize;
	var $PanelVoltage;
	var $NumberScheme;
	var $ParentPanelID;
	var $ParentBreakerName;	// For switchgear, this usually won't be numbered, so we're accepting text
	var $PanelIPAddress;
	var $TemplateID;
	var $MapDataCenterID;
	var $MapX1;
	var $MapX2;
	var $MapY1;
	var $MapY2;
	
	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function lastInsertId() {
		global $dbh;
		return $dbh->lastInsertId();
	}
	
	function errorInfo() {
		global $dbh;
		return $dbh->errorInfo();
	}
	
	function MakeSafe() {
		$this->PanelID=intval($this->PanelID);
		$this->PanelLabel=sanitize($this->PanelLabel);
		$this->NumberOfPoles=intval($this->NumberOfPoles);
		$this->MainBreakerSize=intval($this->MainBreakerSize);
		$this->PanelVoltage=intval($this->PanelVoltage);
		$this->NumberScheme=in_array($this->NumberScheme, array( "Odd/Even", "Sequential", "Busway"))?$this->NumberScheme:"Sequential";
		$this->ParentPanelID=intval($this->ParentPanelID);
		$this->ParentBreakerName=sanitize($this->ParentBreakerName);
		$this->PanelIPAddress=sanitize($this->PanelIPAddress);
		$this->TemplateID=intval($this->TemplateID);
		$this->MapDataCenterID=intval($this->MapDataCenterID);
		$this->MapX1=intval($this->MapX1);
		$this->MapX2=intval($this->MapX2);
		$this->MapY1=intval($this->MapY1);
		$this->MapY2=intval($this->MapY2);
	}

	function MakeDisplay(){
		$this->PanelLabel=stripslashes($this->PanelLabel);
		$this->ParentBreakerName=stripslashes($this->ParentBreakerName);
		$this->PanelIPAddress=stripslashes($this->PanelIPAddress);
	}

	static function RowToObject($row){
		$panel=new PowerPanel();
		$panel->PanelID=$row["PanelID"];
		$panel->PanelLabel=$row["PanelLabel"];
		$panel->NumberOfPoles=$row["NumberOfPoles"];
		$panel->MainBreakerSize=$row["MainBreakerSize"];
		$panel->PanelVoltage=$row["PanelVoltage"];
		$panel->NumberScheme=$row["NumberScheme"];
		$panel->ParentPanelID=$row["ParentPanelID"];
		$panel->ParentBreakerName=$row["ParentBreakerName"];
		$panel->TemplateID=$row["TemplateID"];
		$panel->PanelIPAddress=$row["PanelIPAddress"];
		$panel->MapDataCenterID=$row["MapDataCenterID"];
		$panel->MapX1=$row["MapX1"];
		$panel->MapX2=$row["MapX2"];
		$panel->MapY1=$row["MapY1"];
		$panel->MapY2=$row["MapY2"];

		$panel->MakeDisplay();

		return $panel;
	}


	function getPanelLoad(){
		$this->MakeSafe();

		$sql="SELECT SUM(Wattage) FROM fac_PDUStats WHERE PDUID IN (SELECT PDUID FROM 
			fac_PowerDistribution WHERE PanelID=$this->PanelID);";
		if($watts=$this->query($sql)->fetchColumn()){
			return $watts;
		}else{
			return 0;
		}
	}

	static function getInheritedLoad( $PanelID ) {
		global $dbh;

		// Get the combination of all direct branch circuit meters first, then recursively get all of the subpanels
		// This gets the direct branch circuits with metered power strips (if any)
		$watts = 0;

		$sql = "select sum(Wattage) from fac_PDUStats where PDUID in (select PDUID from fac_PowerDistribution where PanelID=" . intval($PanelID) . ")";
		// Use intval since an empty set will return a NULL for the sum
		$watts = intval($dbh->query( $sql )->fetchColumn());
		
		// Ok, now repeat for the subpanels
		$sql = "select PanelID from fac_PowerPanel where ParentPanelID=" . intval( $PanelID);
		foreach ( $dbh->query( $sql ) as $pnl) {
			$watts += PowerPanel::getInheritedLoad( $pnl["PanelID"] );
		}

		return $watts;
	}

	static function getEstimatedLoad( $PanelID ) {
		global $dbh;
		$watts = 0;

		// Same as with the InheritedLoad - get all the power strips off of the requested panel, then all of the subpanels
		$sql = "select PDUID from fac_PowerDistribution where PanelID=" . intval( $PanelID );
		foreach( $dbh->query( $sql ) as $pdu ) {
			$watts += PowerDistribution::calculateEstimatedLoad( $pdu["PDUID"] );
		}

		// Now get the subpanels
		$sql = "select PanelID from fac_PowerPanel where ParentPanelID=" . intval( $PanelID );
		foreach( $dbh->query( $sql ) as $pnl ) {
			$watts += PowerPanel::getEstimatedLoad( $pnl["PanelID"] );
		}

		return $watts;
	}

	function getPanelList() {
		// Make this a clean list because it will be confusing if it's filtered
		$clean=new PowerPanel();
		return $clean->Search();
	}
	
	function getPowerSource() {
		$sql = "select * from fac_PowerPanel where PanelID=:PanelID";
		
		$st = $this->prepare( $sql );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		
		$currParent = $this->ParentPanelID;
		while ( $currParent != 0 ) {
			$st->execute( array( ":PanelID"=>$currParent ) );
			$row = $st->fetch();
			$currParent = $row->ParentPanelID;
		}
		
		if ( ! @is_object( $row ) ) {
			// Someone called this on a PowerSource
			$row = new PowerPanel();
			foreach ( $this as $prop=>$val ) {
				$row->$prop = $val;
			}
		}
		
		return $row;
	}
	
	function getPanelListBySource( $onlyFirstGen = false ) {
		/* If you supply $this->ParentPanelID then you will get a list of all sources */
		$sql = "select * from fac_PowerPanel where ParentPanelID=:ParentPanelID order by PanelLabel ASC";
		
		$st = $this->prepare( $sql );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		$st->execute( array( ":ParentPanelID"=>$this->ParentPanelID ) );
		
		$pList = array();
		while ( $row = $st->fetch() ) {
			$pList[] = $row;
		}
		if ( ! $onlyFirstGen ) {
			foreach ( $pList as $currPan ) {
				$this->ParentPanelID=$currPan->PanelID;
				$pList = array_merge($pList, $this->getPanelListBySource());
			}
		}
		
		return $pList;
	}

	function getSubPanels( $onlyFirstGen = false ) {
		$pp=new PowerPanel();
		$pp->ParentPanelID=$this->PanelID;
		$pList = $pp->Search();

		if(!$onlyFirstGen) {
			foreach($pList as $key=>$currPan) {
				$pList[$key]->SubPanels = $currPan->getSubPanels();
			}
		}

		return $pList;

	}
	
	function getSources() {
		// The $this->Search() function doesn't work for this because it doesn't recognize that we're looking for 0 or null
		$st = $this->prepare( "select * from fac_PowerPanel where ParentPanelID=0 or ParentPanelID=null" );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		$st->execute();

		$sourceList = array();
		while ( $row = $st->fetch() ) {
			$sourceList[] = $row;
		}

		return $sourceList;
	}

	function getPanelsByDataCenter( $DataCenterID ) {
		$sql = "select * from fac_PowerPanel where PanelID in (select PanelID from fac_PowerDistribution where CabinetID in (select CabinetID from fac_Cabinet where DataCenterID=:DataCenterID)) order by PanelLabel ASC";
		$st = $this->prepare( $sql );
		$st->execute( array( ":DataCenterID"=>$DataCenterID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );
		
		$sList = array();
		while ( $row = $st->fetch() ) {
			$sList[] = $row;
		}
		
		return $sList;
	}
	
	function getSourcesByDataCenter( $DataCenterID ) {
		$pList = $this->getPanelsByDataCenter( $DataCenterID );
		
		$tmpList = array();
		
		foreach ( $pList as $p ) { 
			$tmpList[] = $p->getPowerSource()->PanelID;
		}

		$tmpList = array_unique( $tmpList );
		$psList = array();
		foreach ( $tmpList as $pID ) {
			$row = new PowerPanel();
			$row->PanelID = $pID;
			$row->getPanel();
			
			$psList[] = $row;
		}
		
		return $psList;
	}
	
	function getPanel() {
		$this->MakeSafe();

		$sql="SELECT * FROM fac_PowerPanel WHERE PanelID=$this->PanelID;";
		if($row=$this->query($sql)->fetch()){
			foreach(PowerPanel::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	function createPanel() {
		$this->MakeSafe();

		$sql="INSERT INTO fac_PowerPanel SET PanelIPAddress=\"$this->PanelIPAddress\", 
			PanelLabel=\"$this->PanelLabel\", NumberOfPoles=$this->NumberOfPoles, 
			MainBreakerSize=$this->MainBreakerSize, PanelVoltage=$this->PanelVoltage, 
			NumberScheme=\"$this->NumberScheme\", ParentPanelID=$this->ParentPanelID,
			ParentBreakerName=\"$this->ParentBreakerName\", TemplateID=$this->TemplateID,
			MapDataCenterID=$this->MapDataCenterID, MapX1=$this->MapX1,
			MapY1=$this->MapY1,MapY2=$this->MapY2;";

		if(!$this->exec($sql)){
			$info=$this->errorInfo();
			error_log("createPanel::PDO Error: {$info[2]} $sql");
			return false;
		}else{
			$this->PanelID=$this->lastInsertId();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->PanelID;
		}
	}

	function getParentTree() {
		// get a list of all parent panels above this one, as an ordered array

		$this->MakeSafe();
		$ret = array();
		if($this->ParentPanelID==0) {
			return $ret;
		} else {
			$ppanel=new PowerPanel();
			$ppanel->PanelID=$this->ParentPanelID;
			$ppanel->getPanel();
			$ppt = $ppanel->getParentTree();
			$ret = array_merge($ret, $ppt);
			$ret[] = $ppanel;
			return $ret;
		}
	}

	function getPanelSchedule() {
		$this->MakeSafe();

		$pdu = new PowerDistribution();
		$pdu->PanelID = $this->PanelID;
		$pduList = $pdu->GetPDUByPanel();
	
		$panelSchedule = array();
		$unscheduled = array();
		$errors = array();

		foreach($pduList as $currPdu) {
			if($currPdu->PanelID == $this->PanelID) {
				$poleId = $currPdu->PanelPole;
			} elseif($currPdu->PanelID2 == $this->PanelID) {
				$poleId = $currPdu->PanelPole2;
			}
			$scheduleItem = new PanelScheduleItem();
			$scheduleItem->DataType = "PDU";
			$scheduleItem->Spanned=false;
			$scheduleItem->SpanSize=0;
			$scheduleItem->NoPrint=false;
			$scheduleItem->Data=$currPdu;

			if($this->NumberScheme!="Busway" && $poleId) {
				$scheduleItem->Pole = $poleId;
				$adder=1;
				if($this->NumberScheme=="Odd/Even") {
					$adder=2;
				}
				$addError=false;	
				$endCount=$scheduleItem->Pole+($currPdu->BreakerSize*$adder);

				// check if this item would conflict with any others (so far)
				for($count=$scheduleItem->Pole; $count<$endCount; $count+=$adder) {
					if(array_key_exists($count, $panelSchedule)) {
						if($currPdu->BreakerSize > 1) {
							$addError=true;
						} else {
							foreach($panelSchedule[$count] as $psItem) {
								if($psItem->Spanned) {
									$addError=true;
								}
							}
						}
					}
				}
				if($addError) {
					$errors[]=$scheduleItem;	
				} else {
					if($currPdu->BreakerSize>1) {
						$scheduleItem->Spanned=true;
						$scheduleItem->SpanSize=$currPdu->BreakerSize;
					}
					for($count=$scheduleItem->Pole; $count<$endCount; $count+=$adder) {
						$nextItem=clone $scheduleItem;
						$nextItem->Pole=$count;
						if($count!=$scheduleItem->Pole) {
							$nextItem->NoPrint=true;
						}
						$panelSchedule[$count][]=$nextItem;
						
					}
				}
			} elseif ( $this->NumberScheme!="Busway" ) {
				$scheduleItem->Pole=0;
				$unscheduled[]=$scheduleItem;
			} else {
				$scheduleItem->Pole = $poleId;
				$scheduleItem->Spanned = true;
				$scheduleItem->SpanSize=1;
				$nextItem = clone $scheduleItem;
				$nextItem->Pole = $poleId;
				$panelSchedule[$poleId][]=$nextItem;
			}
		}
		// add first-level panels
		$subPanels = $this->getSubPanels(true);

		foreach($subPanels as $currSub) {
			$scheduleItem = new PanelScheduleItem();
			$scheduleItem->DataType = "PANEL";
			$scheduleItem->Spanned=false;
			$scheduleItem->SpanSize=0;
			$scheduleItem->NoPrint=false;
			$scheduleItem->Data=$currSub;
			if($currSub->ParentBreakerName) {
				$scheduleItem->Pole=$currSub->ParentBreakerName;
				$addError=false;
				if(array_key_exists($scheduleItem->Pole, $panelSchedule)) {
					foreach($panelSchedule[$scheduleItem->Pole] as $currItem) {
						if($currItem->Spanned) {
							$addError = true;
						}
					}
				}
				if($addError) {
					$errors[]=$scheduleItem;
				} else {
					$panelSchedule[$scheduleItem->Pole][]=$scheduleItem;
				}

			} else {
				$scheduleItem->Pole=0;
				$unscheduled[]=$scheduleItem;
			}

		}

		# poles get added out of order, so resort by the keys so they are in order
		ksort($panelSchedule);
		// return array holds: "panelSchedule", "unscheduled", "errors"
		return array("panelSchedule"=>$panelSchedule, "unscheduled"=>$unscheduled, "errors"=>$errors);

	}


	function getScheduleItemHtml($si, $currentCabinetID=0, $mpdf=false) {
		$html = "";
		if($si) {
			$data = $si->Data;
			if($si->DataType=="PDU") {
				$cab = new Cabinet();
				$cab->CabinetID = $data->CabinetID;
				if($currentCabinetID != 0 && $currentCabinetID != $cab->CabinetID) {
					// printing a new cabinet, so separate it from the others
					$html .= "<br>";
				}
				if($currentCabinetID==0 || $currentCabinetID != $cab->CabinetID) {
					$cab->GetCabinet();
					$currentCabinetID = $cab->CabinetID;
					if(!$mpdf) $html.= "<a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">";
					$html.= $cab->Location;
					if(!$mpdf) $html.="</a>";
				}
				// this is a stupid hack, but mpdf doesn't recognize any of the CSS that would allow this to happen any other way
				if($mpdf) {
					$html.= "<br>";
					$html.= "&nbsp;&nbsp;&nbsp;&nbsp;";
				}
				if(!$mpdf) $html.= "<a href=\"devices.php?DeviceID=$data->PDUID\">";
				$html.= "<span>$data->Label</span>";
				if(!$mpdf) $html.= "</a>";
			} elseif($si->DataType=="PANEL") {
				if(!$mpdf) $html.= "<a href=\"power_panel.php?PanelID=$data->PanelID\">";
				$html.=$data->PanelLabel;
				if(!$mpdf) $html.= "</a>";
			} else {
				$html.= "unknown type!";
			}
		}
		return array("html"=>$html, "currentCabinetID"=>$currentCabinetID);
	}

	function getPanelScheduleLabelHtml($ps, $count, $side="panelleft", $mpdf=false) {
		$html = "";
		if(array_key_exists($count, $ps)) {
			$currPole = $ps[$count];
			if(count($currPole) > 1) {
				//if there are more than 1 items in the current pole, we don't
				// need to worry about checking for spans because that would
				// have caused a conflict error on generation
				$html.= "<td class=\"polelabel $side\">";
				$currentCabinetID=0;
				foreach($currPole as $currScheduleItem) {
					$csiPrint = $this->getScheduleItemHtml($currScheduleItem, $currentCabinetID, $mpdf);
					$currentCabinetID = $csiPrint["currentCabinetID"];
					$html.= $csiPrint["html"];
				}
				$html.= " </td>";
			} else {
				// don't need to worry about whether cabinet was printed yet
				if(!$currPole[0]->NoPrint) {
					$spanPrint = "";
					if($currPole[0]->Spanned) {
						$spanPrint = "rowspan=\"".$currPole[0]->SpanSize."\"";
					}
					$html.= "<td class=\"polelabel $side\" $spanPrint>";
					$csiPrint = $this->getScheduleItemHtml($currPole[0], 0, $mpdf);
					$html.= $csiPrint["html"];
					$html.= "&nbsp;</td>";
				}
			}
		} else {
			$html.= "<td class=\"polelabel\">&nbsp;</td>";
		}
		return $html;
	}
	
	function deletePanel() {
		// First, set any CDUs attached to this panel to simply not have an assigned panel
		$sql="UPDATE fac_PowerDistribution SET PanelID=0 WHERE PanelID=$this->PanelID;";
		$this->query($sql);

		$sql="DELETE FROM fac_PowerPanel WHERE PanelID=$this->PanelID;";
		if(!$this->exec($sql)){
			$info=$this->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	static function getPanelsForMap( $DataCenterID ) {
		global $dbh;

		$pnlList = array();

		$st = $dbh->prepare( "select * from fac_PowerPanel where MapDataCenterID=:DataCenterID" );
		$st->setFetchMode( PDO::FETCH_CLASS, "PowerPanel" );

		$st->execute( array( ":DataCenterID"=>$DataCenterID ));
		while ( $row = $st->fetch() ) {
			$pnlList[$row->PanelID] = $row;
		}

		return $pnlList;
	}
		
	function updatePanel(){
		$this->MakeSafe();
		
		$oldpanel=new PowerPanel();
		$oldpanel->PanelID=$this->PanelID;
		$oldpanel->getPanel();

		$sql="UPDATE fac_PowerPanel SET PanelIPAddress=\"$this->PanelIPAddress\",
			PanelLabel=\"$this->PanelLabel\", NumberOfPoles=$this->NumberOfPoles, 
			MainBreakerSize=$this->MainBreakerSize, PanelVoltage=$this->PanelVoltage, 
			NumberScheme=\"$this->NumberScheme\", ParentPanelID=$this->ParentPanelID,
			ParentBreakerName=\"$this->ParentBreakerName\", TemplateID=$this->TemplateID,
			MapDataCenterID=$this->MapDataCenterID, MapX1=$this->MapX1, MapX2=$this->MapX2,
			MapY1=$this->MapY1,MapY2=$this->MapY2
			WHERE PanelID=$this->PanelID;";

		if(!$this->query($sql)){
			$info=$this->errorInfo();
			error_log("updatePanel::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldpanel):'';
		return true;
	}

	function Search($indexedbyid=false,$loose=false){
		// Store the value of devicetype before we muck with it
		$os=$this->NumberScheme;

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($this as $prop => $val){
			// We force NumberScheme to a known value so this is to check if they wanted to search for the default
			if($prop=="NumberScheme" && $val=="Sequential" && $os!="Sequential"){
				continue;
			}
			if($val){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}

		$sql="SELECT * FROM fac_PowerPanel $sqlextend ORDER BY PanelLabel ASC;";

		$panelList=array();

		foreach($this->query($sql) as $row){
			if($indexedbyid){
				$panelList[$deviceRow["DeviceID"]]=PowerPanel::RowToObject($row);
			}else{
				$panelList[]=PowerPanel::RowToObject($row);
			}
		}

		return $panelList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}
}
?>
