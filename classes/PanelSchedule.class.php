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

class PanelSchedule {
	/* PanelSchedule:	Create a panel schedule based upon all of the known connections.  In
						other words - if you take down Panel A4, what cabinets will be affected?
	*/
	
	var $PanelID;
	var $PolePosition;
	var $NumPoles;
	var $Label;

	function MakeSafe(){
		$this->PanelID=intval($this->PanelID);
		$this->PolePosition=intval($this->PolePosition);
		$this->NumPoles=intval($this->NumPoles);
		$this->Label=sanitize($this->Label);
	}

	function MakeDisplay(){
		$this->Label=stripslashes($this->Label);
	}

	function MakeConnection(){
		global $dbh;

		$this->MakeSafe();

		$sql="INSERT INTO fac_PanelSchedule SET PanelID=$this->PanelID, 
			PolePosition=$this->PolePosition, NumPoles=$this->NumPoles, 
			Label=\"$this->Label\" ON DUPLICATE KEY UPDATE Label=\"$this->Label\", 
			NumPoles=$this->NumPoles;";

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $dbh->query($sql);
	}

	function DisplayPanel(){
		global $dbh;

		$html="<table border=1>\n";
		  
		$pan=new PowerPanel();
		$pan->PanelID=$this->PanelID;
		$pan->getPanel();
		 
		$sched=array_fill( 1, $pan->NumberOfPoles, "<td>&nbsp;</td>" );

		$sql="SELECT * FROM fac_PanelSchedule WHERE PanelID=$this->PanelID ORDER BY PolePosition ASC;";

		foreach($dbh->query($sql) as $row){
			$sched[$row["PolePosition"]]="<td rowspan={$row["NumPoles"]}>{$row["Label"]}</td>";
		  
			if($row["NumPoles"] >1){
				$sched[$row["PolePosition"] + 2] = "";
			}
		  
			if($row["NumPoles"] >2){
				$sched[$row["PolePosition"] + 4] = "";
			}

			for($i=1; $i< $pan->NumberOfPoles + 1; $i++){
				$html .= "<tr><td>$i</td>{$sched[$i]}<td>".($i+1)."</td>{$sched[++$i]}</tr>\n";
			}
		}

		$html .= "</table>\n";

		return $html;
	}
}
?>