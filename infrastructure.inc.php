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


	Classes Defined Here:

		DataCenter:		A logical/physical container for assets.  This may be a room, or
						even just a portion of a room.  It does need to be a contiguous
						space for mapping purposes.  Large data centers may want to break
						up the space into quadrants for easier management, but it can
						easily be handled as a whole (in terms of the software and database).
						Any mappings for larger than approximately 2500 SF become difficult
						to see on a laptop screen, because each cabinet takes up such a small
						portion of the overall map.

		DeviceTemplate:	A template with default values for height, wattage, and weight.
						Height and wattage values can be overridden at the device level.
						Templates are completely optional, but are a very good way to
						manage the power capacity of the data center.

		Manufacturer:	Used only in DeviceTemplate, so if you choose not to utilize
						templates, there is no need to enter Manufacturers.

		Zone:			A logical grouping of DataCenter components, so that if a large data
						center has been broken down into smaller components, it can be
						reported on as a single entity.  For example, Building A may have
						data centers in Room 100, Room 109, and Room 205.  All three can
						be placed in a single zone for reporting on Building A data center
						metrics.
						
						NOT YET IMPLEMENTED


*/


class BinAudits {
	var $BinID;
	var $UserID;
	var $AuditStamp;

	function MakeSafe(){
		$this->BinID=intval($this->BinID);
		$this->UserID=addslashes(trim($this->UserID));
		$this->AuditStamp=addslashes(trim($this->AuditStamp));
	}

	function MakeDisplay(){
		$this->UserID=stripslashes($this->UserID);
		$this->AuditStamp=stripslashes($this->AuditStamp);
	}

	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function AddAudit(){
		$this->AuditStamp=date("Y-m-d",strtotime($this->AuditStamp));
		$this->MakeSafe();

		$sql="INSERT INTO fac_BinAudits SET BinID=$this->BinID, UserID=\"$this->UserID\", AuditStamp=\"$this->AuditStamp\";";
		$this->exec($sql);
	}
}

class BinContents {
	var $BinID;
	var $SupplyID;
	var $Count;

	function MakeSafe(){
		$this->BinID=intval($this->BinID);
		$this->SupplyID=intval($this->SupplyID);
		$this->Count=intval($this->Count);
	}

	function BinRowToObject($row){
		$bin=new BinContents();
		$bin->BinID=$row["BinID"];
		$bin->SupplyID=$row["SupplyID"];
		$bin->Count=$row["Count"];

		return $bin;
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function AddContents(){
		$sql="INSERT INTO fac_BinContents SET BinID=$this->BinID, SupplyID=$this->SupplyID, Count=$this->Count;";
		return $this->exec($sql);
	}
	
	function GetBinContents(){
		$this->MakeSafe();

		/* Return all of the supplies found in this bin */
		$sql="SELECT * FROM fac_BinContents WHERE BinID=$this->BinID;";
		
		$binList=array();
		foreach($this->query($sql) as $row){
			$binList[]=BinContents::BinRowToObject($row);
		}
		
		return $binList;
	}
	
	function FindSupplies(){
		$this->MakeSafe();

		/* Return all of the bins where this SupplyID is found */
		$sql="SELECT a.* FROM fac_BinContents a, fac_SupplyBin b WHERE 
			a.SupplyID=$this->SupplyID AND a.BinID=b.BinID ORDER BY b.Location ASC;";

		$binList=array();
		foreach($this->query($sql) as $row){
			$binList[]=BinContents::BinRowToObject($row);
		}
		
		return $binList;		
	}
	
	function UpdateCount(){
		$this->MakeSafe();

		$sql="UPDATE fac_BinContents SET Count=$this->Count WHERE BinID=$this->BinID 
			AND SupplyID=$this->SupplyID;";

		return $this->query($sql);
	}
	
	function RemoveContents(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_BinContents WHERE BinID=$this->BinID AND 
			SupplyID=$this->SupplyID;";

		return $this->exec($sql);
	}
	
	function EmptyBin(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_BinContents WHERE BinID=$this->BinID;";

		return $this->exec($sql);
	}
}

class DataCenter {
	var $DataCenterID;
	var $Name;
	var $SquareFootage;
	var $DeliveryAddress;
	var $Administrator;
	var $MaxkW;
	var $DrawingFileName;
	var $EntryLogging;
	var $dcconfig;
	var $ContainerID;
	var $MapX;
	var $MapY;
	
	function MakeSafe(){
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Name=addslashes(trim($this->Name));
		$this->SquareFootage=intval($this->SquareFootage);
		$this->DeliveryAddress=addslashes(trim($this->DeliveryAddress));
		$this->Administrator=addslashes(trim($this->Administrator));
		$this->MaxkW=intval($this->MaxkW);
		$this->DrawingFileName=addslashes(trim($this->DrawingFileName));
		$this->EntryLogging=intval($this->EntryLogging);
		$this->ContainerID=intval($this->ContainerID);
		$this->MapX=intval($this->MapX);
		$this->MapY=intval($this->MapY);
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->DeliveryAddress=stripslashes($this->DeliveryAddress);
		$this->Administrator=stripslashes($this->Administrator);
		$this->DrawingFileName=stripslashes($this->DrawingFileName);
	}

	static function DataCenterRowToObject($row){
		$dc=New DataCenter();
		$dc->DataCenterID=$row["DataCenterID"];
		$dc->Name=$row["Name"];
		$dc->SquareFootage=$row["SquareFootage"];
		$dc->DeliveryAddress=$row["DeliveryAddress"];
		$dc->Administrator=$row["Administrator"];
		$dc->MaxkW=$row["MaxkW"];
		$dc->DrawingFileName=$row["DrawingFileName"];
		$dc->EntryLogging=$row["EntryLogging"];
		$dc->ContainerID=$row["ContainerID"];
		$dc->MapX=$row["MapX"];
		$dc->MapY=$row["MapY"];
		$dc->MakeDisplay();

		return $dc;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateDataCenter(){
		$this->MakeSafe();
		
		$sql="INSERT INTO fac_DataCenter SET Name=\"$this->Name\", 
			SquareFootage=$this->SquareFootage, DeliveryAddress=\"$this->DeliveryAddress\", 
			Administrator=\"$this->Administrator\", MaxkW=$this->MaxkW, 
			DrawingFileName=\"$this->DrawingFileName\", EntryLogging=0,	
			ContainerID=$this->ContainerID,	MapX=$this->MapX, MapY=$this->MapY;";

		return $this->exec($sql);
	}

	function UpdateDataCenter(){
		$this->MakeSafe();

		$sql="UPDATE fac_DataCenter SET Name=\"$this->Name\", 
			SquareFootage=$this->SquareFootage, DeliveryAddress=\"$this->DeliveryAddress\", 
			Administrator=\"$this->Administrator\", MaxkW=$this->MaxkW, 
			DrawingFileName=\"$this->DrawingFileName\", EntryLogging=0,	
			ContainerID=$this->ContainerID,	MapX=$this->MapX, MapY=$this->MapY WHERE
			DataCenterID=$this->DataCenterID;";

		$this->MakeDisplay();
	
		return $this->query($sql);		
	}

	function GetDataCenter(){
		$this->MakeSafe();
		$sql="SELECT * FROM fac_DataCenter WHERE DataCenterID=$this->DataCenterID;";

		if($row=$this->query($sql)->fetch()){
			foreach(DataCenter::DataCenterRowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
		
	function GetDCList(){
		$sql="SELECT * FROM fac_DataCenter ORDER BY Name ASC;";

		$datacenterList=array();
		foreach($this->query($sql) as $row){
			$datacenterList[]=DataCenter::DataCenterRowToObject($row);
		}

		return $datacenterList;
	}

	function GetDataCenterbyID(){
		// Not sure why this was duplicated but this will do til we clear up the references
		return $this->GetDataCenter();
	}
	
	function MakeImageMap($nolinks=null) {
		$this->MakeSafe();
		$mapHTML="";
	 
		if(strlen($this->DrawingFileName)>0){
			$mapfile="drawings".DIRECTORY_SEPARATOR.$this->DrawingFileName;
		   
			if(file_exists($mapfile)){
				list($width, $height, $type, $attr)=getimagesize($mapfile);
				$mapHTML.="<div class=\"canvas\">\n";
				$mapHTML.="<img src=\"css/blank.gif\" usemap=\"#datacenter\" width=\"$width\" height=\"$height\" alt=\"clearmap over canvas\">\n";
				$mapHTML.="<map name=\"datacenter\">\n";
				 
				if(is_null($nolinks)){
					$sql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID;";

					if($racks=$this->query($sql)){ 
						foreach($racks as $row){
							$mapHTML.="<area href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" shape=\"rect\"";
							$mapHTML.=" coords=\"{$row["MapX1"]},{$row["MapY1"]},{$row["MapX2"]},{$row["MapY2"]}\"";
							$mapHTML.=" alt=\"{$row["Location"]}\" title=\"{$row["Location"]}\">\n";
						}
					}
				}
				 
				$mapHTML.="</map>\n";
				$mapHTML.="<canvas id=\"mapCanvas\" width=\"$width\" height=\"$height\"></canvas>\n";
					 
				$mapHTML .= "</div>\n";
			}
		}
		return $mapHTML;
	}

	function DrawCanvas(){
		$this->MakeSafe();
		$script="";	
		// check to see if map was set
		if(strlen($this->DrawingFileName)){
			$mapfile="drawings".DIRECTORY_SEPARATOR.$this->DrawingFileName;

			// map was set in config check to ensure a file exists before we attempt to use it
			if(file_exists($mapfile)){
				$this->dcconfig=new Config();
				$dev=new Device();
				$templ=new DeviceTemplate();
				$cab=new Cabinet();
				
				// get all color codes and limits for use with loop below
				$CriticalColor=html2rgb($this->dcconfig->ParameterArray["CriticalColor"]);
				$CautionColor=html2rgb($this->dcconfig->ParameterArray["CautionColor"]);
				$GoodColor=html2rgb($this->dcconfig->ParameterArray["GoodColor"]);
				$SpaceRed=intval($this->dcconfig->ParameterArray["SpaceRed"]);
				$SpaceYellow=intval($this->dcconfig->ParameterArray["SpaceYellow"]);
				$WeightRed=intval($this->dcconfig->ParameterArray["WeightRed"]);
				$WeightYellow=intval($this->dcconfig->ParameterArray["WeightYellow"]);
				$PowerRed=intval($this->dcconfig->ParameterArray["PowerRed"]);
				$PowerYellow=intval($this->dcconfig->ParameterArray["PowerYellow"]);
				
				$script.="  <script type=\"text/javascript\">\n	function loadCanvas(){\n";
				$space="	function space(){\n";
				$weight="	function weight(){\n";
				$power="	function power(){\n";
				$space.="	var mycanvas=document.getElementById(\"mapCanvas\");\n		var width = mycanvas.width;\n		mycanvas.width = width + 1;\n		width = mycanvas.width;\n		mycanvas.width = width - 1;\n		var context=mycanvas.getContext('2d');\n";
				$weight.="	var mycanvas=document.getElementById(\"mapCanvas\");\n		var width = mycanvas.width;\n		mycanvas.width = width + 1;\n		width = mycanvas.width;\n		mycanvas.width = width - 1;\n		var context=mycanvas.getContext('2d');\n";
				$power.="	var mycanvas=document.getElementById(\"mapCanvas\");\n		var width = mycanvas.width;\n		mycanvas.width = width + 1;\n		width = mycanvas.width;\n		mycanvas.width = width - 1;\n		var context=mycanvas.getContext('2d');\n";
				$script.="	var mycanvas=document.getElementById(\"mapCanvas\");\n		var width = mycanvas.width;\n		mycanvas.width = width + 1;\n		width = mycanvas.width;\n		mycanvas.width = width - 1;\n		var context=mycanvas.getContext('2d');\n";
				
				// get image file attributes and type
				list($width, $height, $type, $attr)=getimagesize($mapfile);
				$script.="		context.globalCompositeOperation = 'destination-over';\n		var img=new Image();\n		img.onload=function(){\n			context.drawImage(img,0,0);\n		}\n		img.src=\"$mapfile\";\n";
				$space.="		context.globalCompositeOperation = 'destination-over';\n		var img=new Image();\n		img.onload=function(){\n			context.drawImage(img,0,0);\n		}\n		img.src=\"$mapfile\";\n";
				$weight.="		context.globalCompositeOperation = 'destination-over';\n		var img=new Image();\n		img.onload=function(){\n			context.drawImage(img,0,0);\n		}\n		img.src=\"$mapfile\";\n";
				$power.="		context.globalCompositeOperation = 'destination-over';\n		var img=new Image();\n		img.onload=function(){\n			context.drawImage(img,0,0);\n		}\n		img.src=\"$mapfile\";\n";
				$sql="SELECT * FROM fac_Cabinet WHERE DataCenterID=\"$this->DataCenterID\"";
				
				// read all cabinets and draw image map
				foreach($this->query($sql) as $cabRow){
					$cab->CabinetID=$cabRow["CabinetID"];
					$cab->GetCabinet();
					$dev->Cabinet=$cab->CabinetID;
					$dev->Location=$cab->Location;
    	    		$devList=$dev->ViewDevicesByCabinet();
					$currentHeight = $cab->CabinetHeight;
        			$totalWatts = $totalWeight = $totalMoment =0;
					while(list($devID,$device)=each($devList)){
        	        	$templ->TemplateID=$device->TemplateID;
            	    	$templ->GetTemplateByID();

						if($device->NominalWatts >0){
							$totalWatts += $device->NominalWatts;
						}elseif($device->TemplateID!=0 && $templ->Wattage>0){
							$totalWatts += $templ->Wattage;
						}
						if($device->DeviceType=="Chassis"){
							$childList=$device->GetDeviceChildren();
							$childTempl=new DeviceTemplate();
							foreach($childList as $childDev){
								$childTempl->TemplateID=$childDev->TemplateID;
								$childTempl->GetTemplateByID();
								
								if($childDev->NominalWatts>0){
									$totalWatts+=$childDev->NominalWatts;
								}elseif($childDev->TemplateID!=0&&$childTempl->Wattage>0){
									$totalWatts+=$childTempl->Wattage;
								}
								if($childDev->TemplateID!=0){
									$totalWeight+=$childTempl->Weight;
									//Child device's position is parent's position
									$totalMoment+=($childTempl->Weight*($device->Position+($device->Height/2)));
								}
							}
						}
						if($device->TemplateID!=0) {
							$totalWeight+=$templ->Weight;
							$totalMoment+=($templ->Weight*($device->Position+($device->Height/2)));
						}
					}
						
					$CenterofGravity=@round($totalMoment /$totalWeight);

        			$used=$cab->CabinetOccupancy($cab->CabinetID);
					// check to make sure the cabinet height is set to keep errors out of the logs
					if(!isset($cab->CabinetHeight)||$cab->CabinetHeight==0){$SpacePercent=100;}else{$SpacePercent=number_format($used /$cab->CabinetHeight *100,0);}
					// check to make sure there is a weight limit set to keep errors out of logs
					if(!isset($cab->MaxWeight)||$cab->MaxWeight==0){$WeightPercent=0;}else{$WeightPercent=number_format($totalWeight /$cab->MaxWeight *100,0);}
					// check to make sure there is a kilowatt limit set to keep errors out of logs
    	    		if(!isset($cab->MaxKW)||$cab->MaxKW==0){$PowerPercent=0;}else{$PowerPercent=number_format(($totalWatts /1000 ) /$cab->MaxKW *100,0);}
					
					//Decide which color to paint on the canvas depending on the thresholds
					if($SpacePercent>$SpaceRed){$scolor=$CriticalColor;}elseif($SpacePercent>$SpaceYellow){$scolor=$CautionColor;}else{$scolor=$GoodColor;}
					if($WeightPercent>$WeightRed){$wcolor=$CriticalColor;}elseif($WeightPercent>$WeightYellow){$wcolor=$CautionColor;}else{$wcolor=$GoodColor;}
					if($PowerPercent>$PowerRed){$pcolor=$CriticalColor;}elseif($PowerPercent>$PowerYellow){$pcolor=$CautionColor;}else{$pcolor=$GoodColor;}
					if($SpacePercent>$SpaceRed || $WeightPercent>$WeightRed || $PowerPercent>$PowerRed){$color=$CriticalColor;}elseif($SpacePercent>$SpaceYellow || $WeightPercent>$WeightYellow || $PowerPercent>$PowerYellow){$color=$CautionColor;}else{$color=$GoodColor;}

					$width=$cab->MapX2-$cab->MapX1;
					$height=$cab->MapY2-$cab->MapY1;
					$textstrlen=strlen($dev->Location);
					$textXcoord=$cab->MapX1+3;
					$textYcoord=$cab->MapY1+floor($height/2);

					$border="\n\t\tcontext.strokeStyle='#000000';\n\t\tcontext.lineWidth=1;\n\t\tcontext.strokeRect($cab->MapX1,$cab->MapY1,$width,$height);";
					$statuscolor="\n\t\tcontext.fillRect($cab->MapX1,$cab->MapY1,$width,$height);";
					$label="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='bold 7px sans-serif';\n\t\tcontext.fillText('$dev->Location',$textXcoord,$textYcoord);";

					// Uncomment this to add borders and rack labels to the canvas drawing of the data center.
					// Discuss moving this into a configuration item for the future.
					$border=$label="";

					$script.="\t\tcontext.fillStyle=\"rgba({$color[0]}, {$color[1]}, {$color[2]}, 0.35)\";$border$statuscolor$label\n";
					$space.="\t\tcontext.fillStyle=\"rgba({$scolor[0]}, {$scolor[1]}, {$scolor[2]}, 0.35)\";$border$statuscolor$label\n";
					$weight.="\t\tcontext.fillStyle=\"rgba({$wcolor[0]}, {$wcolor[1]}, {$wcolor[2]}, 0.35)\";$border$statuscolor$label\n";
					$power.="\t\tcontext.fillStyle=\"rgba({$pcolor[0]}, {$pcolor[1]}, {$pcolor[2]}, 0.35)\";$border$statuscolor$label\n";
				}
			}
			$space.="	}\n";
			$weight.="	}\n";
			$power.="	}\n";
			$script.="	}\n";
			$script.=$space.$weight.$power;
			$script.="	</script>\n";
		}
		return $script;
	}



	function GetDCStatistics(){
		$this->GetDataCenter();

		$dcStats["TotalU"] = 0;
		$dcStats["Infrastructure"] = 0;
		$dcStats["Occupied"] = 0;
		$dcStats["Allocated"] = 0;
		$dcStats["Available"] = 0;
		$dcStats["ComputedWatts"] = 0;
		$dcStats["MeasuredWatts"] = 0;
		
		$pdu=new PowerDistribution();

		$sql="SELECT SUM(CabinetHeight) FROM fac_Cabinet WHERE 
			DataCenterID=$this->DataCenterID;";

		if(!$dcStats["TotalU"]=$this->query($sql)->fetchColumn()){
			return false;
		}		

		$sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND a.DeviceType 
			NOT IN ('Server','Storage Array');";

		if(!$dcStats["Infrastructure"]=$this->query($sql)->fetchColumn()){
			return false;
		}		
 
		$sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND 
			a.Reservation=false AND a.DeviceType IN ('Server', 'Storage Array');";

		if(!$dcStats["Occupied"]=$this->query($sql)->fetchColumn()){
			return false;
		}		

        $sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND 
			a.Reservation=true;";

		if(!$dcStats["Allocated"]=$this->query($sql)->fetchColumn()){
			return false;
		}		

        $dcStats["Available"]=$dcStats["TotalU"] - $dcStats["Occupied"] - $dcStats["Infrastructure"] - $dcStats["Allocated"];


		// Perform two queries - one is for the wattage overrides (where NominalWatts > 0) and one for the template (default) values
		$sql="SELECT SUM(NominalWatts) FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND a.NominalWatts>0 AND 
			b.DataCenterID=$this->DataCenterID;";

		if(!$dcStats["ComputedWatts"]=$this->query($sql)->fetchColumn()){
			return false;
		}		
		
		$sql="SELECT SUM(c.Wattage) FROM fac_Device a, fac_Cabinet b, 
			fac_DeviceTemplate c WHERE a.Cabinet=b.CabinetID AND 
			a.TemplateID=c.TemplateID AND a.NominalWatts=0 AND 
			b.DataCenterID=$this->DataCenterID;";

		if(!$dcStats["ComputedWatts"]+=$this->query($sql)->fetchColumn()){
			return false;
		}		

		$dcStats["MeasuredWatts"]=$pdu->GetWattageByDC($this->DataCenterID);
		
		return $dcStats;
	}
	
	function AddDCToTree($lev=0) {
		$dept=new Department();
		$zone=new Zone();
		
		$classType = "liClosed";
		$tree=str_repeat(" ",$lev+1)."<li class=\"$classType\" id=\"dc$this->DataCenterID\"><a class=\"DC\" href=\"dc_stats.php?dc=" 
			."$this->DataCenterID\">$this->Name</a>\n";
		$tree.=str_repeat(" ",$lev+2)."<ul>\n";

		$zone->DataCenterID=$this->DataCenterID;
		$zoneList=$zone->GetZonesByDC(); 
		while(list($zoneNum,$myzone)=each($zoneList)){
			$tree.=str_repeat(" ",$lev+3)."<li class=\"liClosed\" id=\"zone$myzone->ZoneID\"><span class=\"ZONE\">". 
				"$myzone->Description</span>\n";
			$tree.=str_repeat(" ",$lev+4)."<ul>\n";
			//Rows
			$sql="SELECT CabRowID, Name AS Fila FROM fac_CabRow WHERE 
				ZoneID=$myzone->ZoneID ORDER BY Fila;";
			
			foreach($this->query($sql) as $filaRow){
				$tree.=str_repeat(" ",$lev+5)."<li class=\"liClosed\" id=\"fila{$filaRow['Fila']}\"><span class=\"CABROW\">".
			  		"<a href=\"rowview.php?row={$filaRow['CabRowID']}\">".__("Row ")."{$filaRow['Fila']}</a></span>\n";
				$tree.=str_repeat(" ",$lev+6)."<ul>\n";
				// DataCenterID and ZoneID are redundant if fac_cabrow is defined and is CabrowID set in fac_cabinet
				$cabsql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID 
					AND ZoneID=$myzone->ZoneID AND CabRowID={$filaRow['CabRowID']} ORDER 
					BY LENGTH(Location),Location ASC;";
			  
				foreach($this->query($cabsql) as $cabRow){
					$tree.=str_repeat(" ",$lev+7)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']}</a></li>\n";
				}
				$tree.=str_repeat(" ",$lev+6)."</ul>\n";
				$tree.=str_repeat(" ",$lev+5)."</li>\n";
			}

			//Cabinets without CabRowID
			$cabsql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID AND 
				ZoneID=$myzone->ZoneID AND CabRowID=0 ORDER BY Location ASC;";
			
			foreach($this->query($cabsql) as $cabRow){
				$tree.=str_repeat(" ",$lev+5)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']}</a></li>\n";
			}
			
			$tree.=str_repeat(" ",$lev+4)."</ul>\n";
			$tree.=str_repeat(" ",$lev+3)."</li>\n";
		} //zone
		
		//Cabinets without ZoneID
		$cabsql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID AND 
			ZoneID=0 ORDER BY Location ASC;";

		foreach($this->query($cabsql) as $cabRow){
			$tree.=str_repeat(" ",$lev+3)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']}</a></li>\n";
		}

		$tree.=str_repeat(" ",$lev+2)."</ul>\n";
		$tree.=str_repeat(" ",$lev+1)."</li>\n";
		
		return $tree;
	}
	
}

class DeviceTemplate {
	var $TemplateID;
	var $ManufacturerID;
	var $Model;
	var $Height;
	var $Weight;
	var $Wattage;
	var $DeviceType;
	var $PSCount;
	var $NumPorts;

	function MakeSafe(){
		$validDeviceTypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure');

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=addslashes(trim($this->Model));
		$this->Height=intval($this->Height);
		$this->Weight=intval($this->Weight);
		$this->Wattage=intval($this->Wattage);
		$this->DeviceType=(in_array($this->DeviceType, $validDeviceTypes))?$this->DeviceType:'Server';
		$this->PSCount=intval($this->PSCount);
		$this->NumPorts=intval($this->NumPorts);
	}

	function MakeDisplay(){
		$this->Model=stripslashes($this->Model);
	}

	static function RowToObject($row){
		$Template=new DeviceTemplate();
		$Template->TemplateID=$row["TemplateID"];
		$Template->ManufacturerID=$row["ManufacturerID"];
		$Template->Model=$row["Model"];
		$Template->Height=$row["Height"];
		$Template->Weight=$row["Weight"];
		$Template->Wattage=$row["Wattage"];
		$Template->DeviceType=$row["DeviceType"];
		$Template->PSCount=$row["PSCount"];
		$Template->NumPorts=$row["NumPorts"];
		$Template->MakeDisplay();

		return $Template;
	}
  
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateTemplate(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			PSCount=$this->PSCount, NumPorts=$this->NumPorts;";

		if(!$dbh->exec($sql)){
			error_log( "SQL Error: " . $sql );
			return false;
		}else{
			$this->TemplateID=$dbh->lastInsertID();
			$this->MakeDisplay();
			return true;
		}
	}
  
	function UpdateTemplate(){
		$sql="UPDATE fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			PSCount=$this->PSCount, NumPorts=$this->NumPorts WHERE 
			TemplateID=$this->TemplateID;";

		if(!$this->query($sql)){
			return false;
		}else{
			$this->MakeDisplay();
			return true;
		}
	}
  
	function DeleteTemplate(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_DeviceTemplate WHERE TemplateID=$this->TemplateID;";
		return $this->exec($sql);
	}
  
	function GetTemplateByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DeviceTemplate WHERE TemplateID=$this->TemplateID;";

		// Reset object in case of a lookup failure
		foreach($this as $prop => $value){
			$var=($prop!='TemplateID')?null:$value;
		}
		
		if($row=$this->query($sql)->fetch()){
			foreach(DeviceTemplate::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
  
	function GetTemplateList(){
		$sql="SELECT * FROM fac_DeviceTemplate a, fac_Manufacturer b WHERE 
			a.ManufacturerID=b.ManufacturerID ORDER BY Name ASC, Model ASC;";

		$templateList=array();
		foreach($this->query($sql) as $row){
			$templateList[]=DeviceTemplate::RowToObject($row);
		}

		return $templateList;
	}

	function GetMissingMfgDates(){
		$this->MakeSafe();

		$sql="SELECT a.* FROM fac_Device a, fac_DeviceTemplate b WHERE
			a.TemplateID=b.TemplateID AND b.ManufacturerID=$this->ManufacturerID AND 
			a.MfgDate<'1970-01-01'";

		$devList=array();
		foreach($this->query($sql) as $row){
			$devList[]=Device::DeviceRowToObject($row);
		}

		$this->MakeDisplay();
		return $devList;
	}
}

class Manufacturer {
	var $ManufacturerID;
	var $Name;

	function MakeSafe(){
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Name=addslashes(trim($this->Name));
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$m=new Manufacturer();
		$m->ManufacturerID=$row["ManufacturerID"];
		$m->Name=$row["Name"];
		$m->MakeDisplay();

		return $m;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetManufacturerByID(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Manufacturer WHERE ManufacturerID=$this->ManufacturerID;";

		if($row=$this->query($sql)->fetch()){
			foreach(Manufacturer::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}	
			return true;
		}else{
			return false;
		}
	}

	function GetManufacturerList(){
		$sql="SELECT * FROM fac_Manufacturer ORDER BY Name ASC;";

		$ManufacturerList=array();
		foreach($this->query($sql) as $row){
			$ManufacturerList[]=Manufacturer::RowToObject($row);
		}

		return $ManufacturerList;
	}

	function AddManufacturer(){
		$this->MakeSafe();

		$sql="INSERT INTO fac_Manufacturer SET Name=\"$this->Name\";";

		$this->MakeDisplay();
		return $this->exec($sql);
	}

	function UpdateManufacturer(){
		$this->MakeSafe();

		$sql="UPDATE fac_Manufacturer SET Name=\"$this->Name\" WHERE ManufacturerID=$this->ManufacturerID;";

		$this->MakeDisplay();
		return $this->query($sql);
	}
}

class Supplies {
	var $SupplyID;
	var $PartNum;
	var $PartName;
	var $MinQty;
	var $MaxQty;

	function MakeSafe(){
		$this->SupplyID=intval($this->SupplyID);
		$this->PartNum=addslashes(trim($this->PartNum));
		$this->PartName=addslashes(trim($this->PartName));
		$this->MinQty=intval($this->MinQty);
		$this->MaxQty=intval($this->MaxQty);
	}

	function MakeDisplay(){
		$this->PartNum=stripslashes($this->PartNum);
		$this->PartName=stripslashes($this->PartName);
	}

	static function RowToObject($row){
		$supply=new Supplies();
		$supply->SupplyID=$row['SupplyID'];
		$supply->PartNum=$row['PartNum'];
		$supply->PartName=$row['PartName'];
		$supply->MinQty=$row['MinQty'];
		$supply->MaxQty=$row['MaxQty'];
		$supply->MakeDisplay();

		return $supply;
	}
	
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateSupplies(){
		global $dbh;

		$sql="INSERT INTO fac_Supplies SET PartNum=\"$this->PartNum\", 
			PartName=\"$this->PartNum\", MinQty=$this->MinQty, MaxQty=$this->MaxQty;";

		if(!$this->exec($sql)){
			return false;
		}else{
			$this->SupplyID=$dbh->lastInsertID();
			$this->MakeDisplay();
			return true;
		}
	}
	
	function GetSupplies(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Supplies WHERE SupplyID=$this->SupplyID;";
		if($row=$this->query($sql)->fetch()){
			foreach(Supplies::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
	
	function GetSuppliesList($indexbyid=false){
		$sql="SELECT * FROM fac_Supplies ORDER BY PartNum ASC;";
		
		$supplyList=array();
		foreach($this->query($sql) as $row){
			$index=($indexbyid)?$row['SupplyID']:$row['PartNum'];
			$supplyList[$index]=Supplies::RowToObject($row);
		}
		
		return $supplyList;
	}
	
	function UpdateSupplies(){
		$this->MakeSafe();

		$sql="UPDATE fac_Supplies SET PartNum=\"$this->PartNum\", 
			PartName=\"$this->PartNum\", MinQty=$this->MinQty, MaxQty=$this->MaxQty WHERE 
			SupplyID=$this->SupplyID;";

		return $this->query($sql);
	}
	
	function DeleteSupplies(){
		$this->MakeSafe();

		$sql="DELETE FROM fac_Supplies WHERE SupplyID=$this->SupplyID;";

		return $this->exec($sql);
	}
}

class SupplyBin {
	var $BinID;
	var $Location;
	
	function MakeSafe(){
		$this->BinID=intval($this->BinID);
		$this->Location=addslashes(trim($this->Location));
	}

	function MakeDisplay(){
		$this->Location=stripslashes($this->Location);
	}

	static function RowToObject($row){
		$bin=New SupplyBin();
		$bin->BinID=$row['BinID'];
		$bin->Location=$row['Location'];
		$bin->MakeDisplay();

		return $bin;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function GetBin(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_SupplyBin WHERE BinID=$this->BinID;";

		if($row=$this->query($sql)->fetch()){
			foreach(SupplyBin::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
	
	function CreateBin(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_SupplyBin SET Location=\"$this->Location\";";
		
		if(!$this->exec($sql)){
			return false;
		}else{
			$this->BinID=$dbh->lastInsertID();
			$this->MakeDisplay();
			return true;
		}
	}
	
	function UpdateBin(){
		$this->MakeSafe();

		$sql="UPDATE fac_SupplyBin SET Location=\"$this->Location\" WHERE 
			BinID=$this->BinID;";

		return $this->query($sql);
	}
	
	function DeleteBin(){
		// needs testing, not currently implemented
		$this->MakeSafe();

		$sql="DELETE FROM fac_SupplyBin WHERE BinID=$this->BinID; 
			DELETE FROM fac_BinContents WHERE BinID=$this->BinID; 
			DELETE FROM fac_BinAudits WHERE BinID=$this->BinID;";

		return $this->exec($sql);
	}
	
	function GetBinList(){
		$sql="SELECT * FROM fac_SupplyBin ORDER BY Location ASC;";
		
		$binList=array();
		foreach($this->query($sql) as $row){
			$binList[]=SupplyBin::RowToObject($row);
		}
		
		return $binList;
	}
}

class Zone {
	var $ZoneID;
	var $DataCenterID;
	var $Description;
  
	function MakeSafe(){
		$this->ZoneID=intval($this->ZoneID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Description=addslashes(trim($this->Description));
	}

	function MakeDisplay(){
		$this->Description=stripslashes($this->Description);
	}

	static function RowToObject($row){
		$zone=New Zone();
		$zone->ZoneID=$row["ZoneID"];
		$zone->DataCenterID=$row["DataCenterID"];
		$zone->Description=$row["Description"];
		$zone->MakeDisplay();

		return $zone;
	}
 
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateZone(){
		global $dbh;
			
		$this->MakeSafe();
			
		$sql="INSERT INTO fac_Zone SET Description=\"$this->Description\", 
			DataCenterID=$this->DataCenterID;";
		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			$this->ZoneID=$dbh->lastInsertID();
			return $this->ZoneID;
		}
	}
	
	function UpdateZone(){
		$this->MakeSafe();
			
		//update cabinets in this zone
		$sql="UPDATE fac_Cabinet SET DataCenterID=$this->DataCenterID WHERE 
			ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
	
		//update zone	
		$sql="UPDATE fac_Zone SET Description=\"$this->Description\", 
			DataCenterID=$this->DataCenterID WHERE ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}
	
	function DeleteZone(){
		$this->MakeSafe();
		
		//update cabinets in this zone
		$sql="UPDATE FROM fac_Cabinet SET CabRowID=0, ZoneID=0 WHERE 
			ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}

		//delete CabRows in this zone
		$sql="DELETE FROM fac_CabRow WHERE ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}

		//delete zone
		$sql="DELETE FROM fac_Zone WHERE ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}
  
	function GetZone(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Zone WHERE ZoneID=$this->ZoneID;";
		if($row=$this->query($sql)->fetch()){
			foreach(Zone::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
  
	function GetZonesByDC(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Zone WHERE DataCenterID=$this->DataCenterID ORDER BY 
			Description;";
		
		$zoneList=array();
		foreach($this->query($sql) as $row){
			$zoneList[]=Zone::RowToObject($row);
		}
		
		return $zoneList;
	}

	function GetZoneList(){
		$sql="SELECT * FROM fac_Zone ORDER BY Description ASC;";

		$zoneList=array();
		foreach($this->query($sql) as $row){
			$zoneList[]=Zone::RowToObject($row);
		}
		
		return $zoneList;
	}
  
}

class CabRow {
	var $CabRowID;
	var $Name;
	var $ZoneID;

	function MakeSafe() {
		$this->CabRowID=intval($this->CabRowID);
		$this->Name=addslashes(trim($this->Name));
		$this->ZoneID=intval($this->ZoneID);
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	function RowToObject($row){
		$cabrow=new CabRow();
		$cabrow->CabRowID=$row["CabRowID"];
		$cabrow->Name=$row["Name"];
		$cabrow->ZoneID=$row["ZoneID"];
		$cabrow->MakeDisplay();

		return $cabrow;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateCabRow(){
		global $dbh;
		$this->MakeSafe();

		$sql="INSERT INTO fac_CabRow SET Name=\"$this->Name\", ZoneID=$this->ZoneID;";
		if($dbh->exec($sql)){
			$this->CabRowID=$dbh->lastInsertID();
			return $this->CabRowID;
		}else{
			return false;
		}
	}
	
	function UpdateCabRow(){
		$this->MakeSafe();

		$sql="UPDATE fac_Cabinet SET ZoneID=$this->ZoneID WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		
		$sql="UPDATE fac_CabRow SET Name=\"$this->Name\", ZoneID=$this->ZoneID WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		
		return true;
	}
	
	function DeleteCabRow(){
		$this->MakeSafe();

		//update cabinets in this cabrow
		$sql="UPDATE fac_Cabinet SET CabRowID=0 WHERE CabRowID=$this->CabRowID AND ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}

		//delete cabrow
		$sql="DELETE FROM fac_CabRow WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}

	function GetCabRow(){
		$sql="SELECT * FROM fac_CabRow WHERE CabRowID=$this->CabRowID;";

		if($row=$this->query($sql)->fetch()){
			foreach(CabRow::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	function GetCabRowsByZones(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_CabRow WHERE ZoneID=$this->ZoneID ORDER BY Name";

		$cabrowList=array();
		foreach($this->query($sql) as $row){
			$cabrowList[]=CabRow::RowToObject($row);
		}

		return $cabrowList;
	}

	function GetCabRowList(){
		$sql="SELECT * FROM fac_CabRow ORDER BY Name ASC;";
		
		$cabrowList=array();
		foreach($this->query($sql) as $row){
			$cabrowList[]=CabRow::RowToObject($row);
		}
		
		return $cabrowList;
	}

}

//JMGA: containerobjects may contain DCs or other containers
class Container {
	var $ContainerID;
	var $Name;
	var $ParentID;
	var $DrawingFileName;
	var $MapX;
	var $MapY;

	function MakeSafe(){
		$this->ContainerID=intval($this->ContainerID);
		$this->Name=addslashes(trim($this->Name));
		$this->ParentID=intval($this->ParentID);
		$this->DrawingFileName=addslashes(trim($this->DrawingFileName));
		$this->MapX=intval($this->MapX);
		$this->MapY=intval($this->MapY);
	}
	
	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->DrawingFileName=stripslashes($this->DrawingFileName);
	}

	function RowToObject($row){
		$container=new Container();
		$container->ContainerID=$row["ContainerID"];
		$container->Name=$row["Name"];
		$container->ParentID=$row["ParentID"];
		$container->DrawingFileName=$row["DrawingFileName"];
		$container->MapX=$row["MapX"];
		$container->MapY=$row["MapY"];
		$container->MakeDisplay();

		return $container;
	}

	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateContainer() {
		global $dbh;
		
		$this->MakeSafe();
		$sql="INSERT INTO fac_Container set Name=\"$this->Name\", ParentID=$this->ParentID, 
				DrawingFileName=\"$this->DrawingFileName\", MapX=$this->MapX, MapY=$this->MapY;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		} else {
			$this->ContainerID = $dbh->lastInsertID();
		}
		return $this->ContainerID;
	}

	function UpdateContainer(){
		$this->MakeSafe();

		$sql="UPDATE fac_Container SET Name=\"$this->Name\", ParentID=$this->ParentID, 
			DrawingFileName=\"$this->DrawingFileName\", MapX=$this->MapX, MapY=$this->MapY 
			WHERE ContainerID=$this->ContainerID;";
		
		if(!$this->query($sql)){
			return false;
		}else{
			return true;
		}
	}

	function GetContainer(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Container WHERE ContainerID=$this->ContainerID;";

		if($row=$this->query($sql)->fetch()){
			foreach(Container::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
	
	function GetChildContainerList(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Container WHERE ParentID=$this->ContainerID 
			ORDER BY Name ASC;";

		$containerList=array();
		foreach($this->query($sql) as $row){
			$containerList[$row["ContainerID"]]=Container::RowToObject($row);
		}

		return $containerList;
	}

	function GetChildDCList(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DataCenter WHERE ContainerID=$this->ContainerID 
			ORDER BY Name ASC;";

		$datacenterList=array();
		foreach($this->query($sql) as $row){
			$datacenterList[$row["DataCenterID"]]=DataCenter::DataCenterRowToObject($row);
		}

		return $datacenterList;
	}
		
	function BuildMenuTree() {
		$c=new Container();
		//begin the tree
		$tree="\n<ul class=\"mktree\" id=\"datacenters\">\n";;
		//Add root children
		$tree.=$c->AddContainerToTree();
		$tree.="<li class=\"liOpen\" id=\"dc-1\"><a href=\"storageroom.php\">"._("Storage Room")."</a></li>\n";
		$tree.="</ul>\n";

		return $tree;
	}

	private function AddContainerToTree($lev=0) {
		$tree="";
		$container_opened=false;
		
		if($this->GetContainer()){
			$lev++;
			$tree.=str_repeat(" ",$lev)."<li class=\"liOpen\" id=\"c$this->ContainerID\">"
				."<a class=\"CONTAINER\" href=\"container_stats.php?container=$this->ContainerID\">"
				."$this->Name</a>\n";
			$lev++;
			$tree.=str_repeat(" ",$lev)."<ul>\n";
			$container_opened=true;
		}
		
		$cList=$this->GetChildContainerList();
		$lev++;
		if(count($cList) >0){
			while(list($cID,$container)=each($cList)){
				$tree.=$container->AddContainerToTree($lev);
			}
		}
		
		$dcList=$this->GetChildDCList();

		if(count($dcList) >0){
			while(list($dcID,$datacenter)=each($dcList)){
				$tree.=$datacenter->AddDCToTree($lev);
			} //DC
		}

		if ($container_opened){
			
			$tree.=str_repeat(" ",$lev-1)."</ul>\n";
			$tree.=str_repeat(" ",$lev-2)."</li>\n";
		}
		
		return $tree;
	}
	
	function MakeContainerImage(){
		$mapHTML="";
		$mapfile="";
		$tam=50;
	 
		if ( strlen($this->DrawingFileName) > 0 ) {
			$mapfile = "drawings/" . $this->DrawingFileName;
		}
	   
		if ( file_exists( $mapfile ) ) {
			list($width, $height, $type, $attr)=getimagesize($mapfile);
			$mapHTML.="<div style='position:relative;'>\n";
			$mapHTML.="<img src=\"$mapfile\" width=\"$width\" height=\"$height\" alt=\"Container Image\">\n";
			
			$cList=$this->GetChildContainerList();
			if ( count( $cList ) > 0 ) {
				while ( list( $cID, $container ) = each( $cList ) ) {
					if (is_null($container->MapX) || $container->MapX==0 
						|| is_null($container->MapY) || $container->MapY==0 ){
						$mapHTML.="<div>\n";
						$mapHTML.="<a title=\"".$container->Name."\" href=\"container_stats.php?container=".$cID."\">";
						$mapHTML.="<br><div style='background-color: #dcdcdc;'>".$container->Name."</div></a>";
						$mapHTML.= "</div>\n";
						}
					else {
						$mapHTML.="<div style='position:absolute; top:".($container->MapY-$tam/2)."px; left:".($container->MapX-$tam/2)."px;'>\n";
						$mapHTML.="<a title=\"".$container->Name."\" href=\"container_stats.php?container=".$cID."\">";
						$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
						$mapHTML.="<div style='position:absolute; top:".($container->MapY+$tam/2)."px; left:".($container->MapX-$tam/2)."px; background-color: #dcdcdc;'>";
						$mapHTML.="<a title=\"".$container->Name."\" href=\"container_stats.php?container=".$cID."\">";
						$mapHTML.= $container->Name."</a></div>";
					}
				}
			}
			
			$dcList=$this->GetChildDCList();
			if ( count( $dcList ) > 0 ) {
				while ( list( $dcID, $dc ) = each( $dcList ) ) {
					if (is_null($dc->MapX) || $dc->MapX==0 
						|| is_null($dc->MapY) || $dc->MapY==0 ){
						$mapHTML.="<div>\n";
						$mapHTML.="<a title=\"".$dc->Name."\" href=\"dc_stats.php?dc=".$dcID."\">";
						$mapHTML.=$dc->Name."</a>";
						$mapHTML.= "</div>\n";
					}
					else{
						$mapHTML.="<div style='position:absolute; top:".($dc->MapY-$tam/2)."px; left:".($dc->MapX-$tam/2)."px;'>\n";
						$mapHTML.="<a title=\"".$dc->Name."\" href=\"dc_stats.php?dc=".$dcID."\">";
						$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\"></a>\n</div>\n";
						$mapHTML.="<div style='position:absolute; top:".($dc->MapY+$tam/2)."px; left:".($dc->MapX-$tam/2)."px; background-color: #dcdcdc;'>";
						$mapHTML.="<a title=\"".$dc->Name."\" href=\"dc_stats.php?dc=".$dcID."\">";
						$mapHTML.=$dc->Name."</a></div>";
					}
				}
			}
			
			$mapHTML .= "</div>\n";
	    }
	    return $mapHTML;
	}

	function MakeContainerMiniImage($tipo="",$id=0) {
		$mapHTML = "";
		$mapfile="";
		$tam=50;
		$red=.5;
		$tam*=$red;
		$yo_ok=false;
	 
		if ( strlen($this->DrawingFileName) > 0 ) {
			$mapfile = "drawings/" . $this->DrawingFileName;
		}
	   
		if ( file_exists( $mapfile ) ) {
			list($width, $height, $type, $attr)=getimagesize($mapfile);
			$mapHTML.="<div style='position:relative;'>\n";
			$mapHTML.="<img id='containerimg' src=\"".$mapfile."\" width=\"".($width*$red)."\" height=\"".($height*$red)."\" 
					 onclick='coords(event)' alt=\"Container Image\">\n";
			
			$cList=$this->GetChildContainerList();
			if ( count( $cList ) > 0 ) {
				while ( list( $cID, $container ) = each( $cList ) ) {
					if ((is_null($container->MapX) || $container->MapX<0 || $container->MapX>$width 
						|| is_null($container->MapY) || $container->MapY<0 || $container->MapY>$height)
						&& $tipo=="container" && $id==$cID ){
							$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
							$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
							$yo_ok=true;
						}
					else {
						
						if ($tipo=="container" && $id==$cID) {
							$mapHTML.="<div id='yo' style='position:absolute; top:".($container->MapY*$red-$tam/2)."px; left:".($container->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
							$yo_ok=true;
						}
						else {
							$mapHTML.="<div style='position:absolute; top:".($container->MapY*$red-$tam/2)."px; left:".($container->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/ContainerGris.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
						}
					}
				}
			}
			
			$dcList=$this->GetChildDCList();
			if ( count( $dcList ) > 0 ) {
				while ( list( $dcID, $dc ) = each( $dcList ) ) {
					if ((is_null($dc->MapX) || $dc->MapX<0 || $dc->MapX>$width
						|| is_null($dc->MapY) || $dc->MapY<0 || $dc->MapY>$height)
						&& $tipo=="dc" && $id==$dcID){
							$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
							$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
							$yo_ok=true;
						}
					else{
						if ($tipo=="dc" && $id==$dcID){
							$mapHTML.="<div id='yo' style='position:absolute; top:".($dc->MapY*$red-$tam/2)."px; left:".($dc->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
							$yo_ok=true;
						}
						else {
							$mapHTML.="<div style='position:absolute; top:".($dc->MapY*$red-$tam/2)."px; left:".($dc->MapX*$red-$tam/2)."px;'>\n";
							$mapHTML.="<img src=\"images/DCGris.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
						}
					}
				}
			}
			if (!$yo_ok){
				$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
				if ($tipo=="container")
					$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
				else
					$mapHTML.="<img src=\"images/DC.png\" width=$tam height=$tam alt=\"Datacenter\">\n</div>\n";
			}
			
			$mapHTML .= "</div>\n";
	    }
	    return $mapHTML;
	}
	
	function GetContainerStatistics(){
		$this->GetContainer();

		$cStats["DCs"] = 0;
		$cStats["TotalU"] = 0;
		$cStats["Infrastructure"] = 0;
		$cStats["Occupied"] = 0;
		$cStats["Allocated"] = 0;
		$cStats["Available"] = 0;
		$cStats["SquareFootage"] = 0;
		$cStats["MaxkW"] = 0;
		$cStats["ComputedWatts"] = 0;
		$cStats["MeasuredWatts"] = 0;
		
		$dcList=$this->GetChildDCList();
		if(count($dcList) >0){
			while(list($dcID,$datacenter)=each($dcList)){
				$dcStats=$datacenter->GetDCStatistics();
				$cStats["DCs"]++;
				$cStats["TotalU"]+=$dcStats["TotalU"];
				$cStats["Infrastructure"]+=$dcStats["Infrastructure"];
				$cStats["Occupied"]+=$dcStats["Occupied"];
				$cStats["Allocated"]+=$dcStats["Allocated"];
				$cStats["Available"]+=$dcStats["Available"];
				$cStats["SquareFootage"]+=$datacenter->SquareFootage;
				$cStats["ComputedWatts"]+=$dcStats["ComputedWatts"];
				$cStats["MeasuredWatts"]+=$dcStats["MeasuredWatts"];
				$cStats["MaxkW"]+=$datacenter->MaxkW;
			} 
		}
		
		$cList=$this->GetChildContainerList();
		if(count($cList) >0){
			while(list($cID,$container)=each($cList)){
				$childStats=$container->GetContainerStatistics();
				$cStats["DCs"]+=$childStats["DCs"];
				$cStats["TotalU"]+=$childStats["TotalU"];
				$cStats["Infrastructure"]+=$childStats["Infrastructure"];
				$cStats["Occupied"]+=$childStats["Occupied"];
				$cStats["Allocated"]+=$childStats["Allocated"];
				$cStats["Available"]+=$childStats["Available"];
				$cStats["SquareFootage"]+=$childStats["SquareFootage"];
				$cStats["ComputedWatts"]+=$childStats["ComputedWatts"];
				$cStats["MeasuredWatts"]+=$childStats["MeasuredWatts"];
				$cStats["MaxkW"]+=$childStats["MaxkW"];
			}
		}
		return $cStats;
	}
	
	function GetContainerList(){
		$sql="SELECT * FROM fac_Container ORDER BY Name ASC;";

		$containerList=array();
		foreach($this->query($sql) as $row){
			$containerList[$row["ContainerID"]]=Container::RowToObject($row);
		}

		return $containerList;
	}
	
}
//END Class Container
?>
