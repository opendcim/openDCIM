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

	static function RowToObject($row){
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
			$binList[]=BinContents::RowToObject($row);
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
			$binList[]=BinContents::RowToObject($row);
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
		$this->MapX=abs($this->MapX);
		$this->MapY=abs($this->MapY);
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->DeliveryAddress=stripslashes($this->DeliveryAddress);
		$this->Administrator=stripslashes($this->Administrator);
		$this->DrawingFileName=stripslashes($this->DrawingFileName);
	}

	static function RowToObject($row){
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
			foreach(DataCenter::RowToObject($row) as $prop => $value){
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
			$datacenterList[]=DataCenter::RowToObject($row);
		}

		return $datacenterList;
	}

	function GetDataCenterbyID(){
		// Not sure why this was duplicated but this will do til we clear up the references
		return $this->GetDataCenter();
	}

    /**
     * Returns an array with all the hierarchy of containers the data center
     *  belongs to.
     * @param type $containerList
     * @return type
     */
    public function getContainerList($containerID = 0)
    {
        $container = new Container();
        if ($containerID == 0) {
            $container->ContainerID = $this->ContainerID;
        } else {
            $container->ContainerID = $containerID;
        }
        $container->GetContainer();
        $containerList[] = $container->Name;
        if ($container->ParentID > 0) {
            $childContainerList = $this->getContainerList($container->ParentID);
            $containerList = array_merge($childContainerList, $containerList);
        }
        return $containerList;
    }
	
	function MakeImageMap($nolinks=null) {
		$this->MakeSafe();
		$mapHTML="";
	 
		if(strlen($this->DrawingFileName)>0){
			$mapfile="drawings".DIRECTORY_SEPARATOR.$this->DrawingFileName;
		   
			if(file_exists($mapfile)){
				list($width, $height, $type, $attr)=getimagesize($mapfile);
				$mapHTML.="<div class=\"canvas\" style=\"background-image: url('drawings/$this->DrawingFileName')\">\n";
				$mapHTML.="<img src=\"css/blank.gif\" usemap=\"#datacenter\" width=\"$width\" height=\"$height\" alt=\"clearmap over canvas\">\n";
				$mapHTML.="<map name=\"datacenter\">\n";
				 
				if(is_null($nolinks)){
					$sql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID;";
					if($racks=$this->query($sql)){ 
						foreach($racks as $row){
							$mapHTML.="<area name=\"cab\" href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" shape=\"rect\"";
							$mapHTML.=" coords=\"{$row["MapX1"]},{$row["MapY1"]},{$row["MapX2"]},{$row["MapY2"]}\"";
							$mapHTML.=" alt=\"{$row["Location"]}\" data-zone=\"zone{$row["ZoneID"]}\">\n";
						}
					}

					$sql="SELECT * FROM fac_Zone WHERE DataCenterID=$this->DataCenterID;";
					if($zones=$this->query($sql)){ 
						foreach($zones as $row){
							$mapHTML.="<area name=\"zone{$row["ZoneID"]}\" href=\"zone_stats.php?zone={$row["ZoneID"]}\" shape=\"rect\"";
							$mapHTML.=" coords=\"{$row["MapX1"]},{$row["MapY1"]},{$row["MapX2"]},{$row["MapY2"]}\"";
							$mapHTML.=" alt=\"{$row["Description"]}\" title=\"{$row["Description"]}\">\n";
						}
					}

					// What is this for?
					$mapHTML.="<area name=\"dc\" shape=\"rect\"";
					$mapHTML.=" coords=\"0,0,{$width},{$height}\"";
					$mapHTML.=" alt=\"{$this->Name}\" title=\"{$this->Name}\">\n";
				}
				 
				$mapHTML.="</map>\n";
				$mapHTML.="<canvas id=\"mapCanvas\" width=\"$width\" height=\"$height\"></canvas>\n";
					 
				$mapHTML .= "<br><br><br><br><br><br><br><br></div>\n";
			}
		}
		return $mapHTML;
	}

	function MakeZoneJS(){
		$this->MakeSafe();
		$js='';
		
		if(strlen($this->DrawingFileName)>0){
			$sql="SELECT * FROM fac_Zone WHERE DataCenterID=$this->DataCenterID;";
			if($zones=$this->query($sql)){ 
				foreach($zones as $row){
					$zone=Zone::RowToObject($row);
					if($zone->MapX1==0 && $zone->MapX2==0 && $zone->MapY1==0 && $zone->MapY2==0){
						// zone exists but has no shape, ignore it.
					}else{
						if(strlen($js)>0){
							//Already have an initial if so add an else if
							$else="\t\t\t}else ";
						}else{
							$else="";
						}
						$js.=$else."if((e.pageX>(cpos.left+$zone->MapX1) && e.pageX<(cpos.left+$zone->MapX2)) && (e.pageY>(cpos.top+$zone->MapY1) && e.pageY<(cpos.top+$zone->MapY2))){
				$('#maptitle .nav select').trigger('change');
				HilightZone('zone$zone->ZoneID');
				redraw=true;\n";
					}
				}
				if(strlen($js)>0){
					// add the first and last bits needs to make the loops function
					$hilight="\n
		function HilightZone(area){
			context.globalCompositeOperation='source-over';
			//there has to be a better way to do this.  stupid js
			area=$('area[name='+area+']').prop('coords').split(',');
			context.lineWidth='4';
			context.strokeStyle='red';
			context.strokeRect(area[0],area[1],(area[2]-area[0]),(area[3]-area[1]));
		}\n";
					$js="$hilight
		var redraw=false;
		var cpos=$('#mapCanvas').offset();
		$('.canvas').mousemove(function(e){
			$js\t\t\t}else if(redraw){
				$('#maptitle .nav select').trigger('change');
				redraw=false;
			}
		});\n";
				}
			}
		}

		return $js;
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
				
				// Temperature 
				$unknounColor=html2rgb('FFFFFF');
				//$TemperatureGreen=20;
				$TemperatureYellow=intval($this->dcconfig->ParameterArray["TemperatureYellow"]);
				$TemperatureRed=intval($this->dcconfig->ParameterArray["TemperatureRed"]);
				
				// Humidity
				$HumidityMin=intval($this->dcconfig->ParameterArray["HumidityRedLow"]);
				$HumidityMedMin=intval($this->dcconfig->ParameterArray["HumidityYellowLow"]);			
				$HumidityMedMax=intval($this->dcconfig->ParameterArray["HumidityYellowHigh"]);				
				$HumidityMax=intval($this->dcconfig->ParameterArray["HumidityRedHigh"]);
				
				//Real Power
				$RealPowerRed=intval($this->dcconfig->ParameterArray["PowerRed"]);
				$RealPowerYellow=intval($this->dcconfig->ParameterArray["PowerYellow"]);
				
				// get image file attributes and type
				list($width, $height, $type, $attr)=getimagesize($mapfile);

				$script.="\n\t\tvar maptitle=$('#maptitle span');
		var mycanvas=document.getElementById(\"mapCanvas\");
		var context=mycanvas.getContext('2d');
		context.globalCompositeOperation='destination-over';
		context.save();

		function clearcanvas(){
			// erase anything on the canvas
			context.clearRect(0,0, mycanvas.width, mycanvas.height);
			// create a new image for the canvas
			var img=new Image();
			// draw after the image has loaded
			img.onload=function(){
				// changed to eliminate the flickering of reloading the background image on a redraw
				//context.drawImage(img,0,0);
				airflow();
			}
			// give it an image to load
			img.src=\"$mapfile\";
		}

		function loadCanvas(){\n\t\t\tclearcanvas();\n";

				$space="\t\tfunction space(){\n\t\t\tclearcanvas();\n";
				$weight="\t\tfunction weight(){\n\t\t\tclearcanvas();\n";
				$power="\t\tfunction power(){\n\t\t\tclearcanvas();\n";
				$temperature="\t\tfunction temperatura(){\n\t\t\tclearcanvas();\n";
				$humidity="\t\tfunction humedad(){\n\t\t\tclearcanvas();\n";				
				$realpower="\t\tfunction realpower(){\n\t\t\tclearcanvas();\n";
				$airflow="\t\tfunction airflow(){\n\t\t\t\n";
				/*
				$sql="SELECT C.*, Temps.Temp, Temps.Humidity, Stats.Wattage AS RealPower, 
					Temps.LastRead, Temps.LastRead AS RPLastRead FROM fac_Cabinet AS C
					LEFT JOIN fac_CabinetTemps AS Temps ON C.CabinetID=Temps.CabinetID
					LEFT JOIN fac_PowerDistribution AS P ON C.CabinetID=P.CabinetID
					LEFT JOIN fac_PDUStats AS Stats ON P.PDUID=Stats.PDUID 
					WHERE C.DataCenterID=$this->DataCenterID GROUP BY CabinetID;";
				*/
				$sql="SELECT C.*, T.Temp, T.Humidity, P.RealPower, T.LastRead, PLR.RPLastRead 
					FROM ((fac_Cabinet C LEFT JOIN fac_CabinetTemps T ON C.CabinetId = T.CabinetID) LEFT JOIN
						(SELECT CabinetID, SUM(Wattage) RealPower
						FROM fac_PowerDistribution PD LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID
						GROUP BY CabinetID) P ON C.CabinetId = P.CabinetID) LEFT JOIN
						(SELECT CabinetID, MAX(LastRead) RPLastRead
						FROM fac_PowerDistribution PD LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID
						GROUP BY CabinetID) PLR ON C.CabinetId = PLR.CabinetID
				    WHERE C.DataCenterID=".intval($this->DataCenterID).";";
				
				$fechaLecturaTemps=0;
				$fechaLecturaRP=0;
				if($racks=$this->query($sql)){ 
					// read all cabinets and draw image map
					foreach($racks as $cabRow){
						$cab->CabinetID=$cabRow["CabinetID"];
						if (!$cab->GetCabinet()){
							continue;
						}
						if ($cab->MapX1==$cab->MapX2 || $cab->MapY1==$cab->MapY2){
							continue;
						}
						$dev->Cabinet=$cab->CabinetID;
						$dev->Location=$cab->Location;  //$dev->Location ???
	    	    		$devList=$dev->ViewDevicesByCabinet();
						$currentHeight = $cab->CabinetHeight;
	        			$totalWatts = $totalWeight = $totalMoment =0;
						$currentTemperature=$cabRow["Temp"];
						$currentHumidity=$cabRow["Humidity"];
						$currentRealPower=$cabRow["RealPower"];
						
						while(list($devID,$device)=each($devList)){
							$totalWatts+=$device->GetDeviceTotalPower();
							$DeviceTotalWeight=$device->GetDeviceTotalWeight();
							$totalWeight+=$DeviceTotalWeight;
							$totalMoment+=($DeviceTotalWeight*($device->Position+($device->Height/2)));
						}
							
	        			$used=$cab->CabinetOccupancy($cab->CabinetID);
						// check to make sure the cabinet height is set to keep errors out of the logs
						if(!isset($cab->CabinetHeight)||$cab->CabinetHeight==0){$SpacePercent=100;}else{$SpacePercent=number_format($used /$cab->CabinetHeight *100,0);}
						// check to make sure there is a weight limit set to keep errors out of logs
						if(!isset($cab->MaxWeight)||$cab->MaxWeight==0){$WeightPercent=0;}else{$WeightPercent=number_format($totalWeight /$cab->MaxWeight *100,0);}
						// check to make sure there is a kilowatt limit set to keep errors out of logs
	    	    		if(!isset($cab->MaxKW)||$cab->MaxKW==0){$PowerPercent=0;}else{$PowerPercent=number_format(($totalWatts /1000 ) /$cab->MaxKW *100,0);}
						if(!isset($cab->MaxKW)||$cab->MaxKW==0){$RealPowerPercent=0;}else{$RealPowerPercent=number_format(($currentRealPower /1000 ) /$cab->MaxKW *100,0, ",", ".");}
					
						//Decide which color to paint on the canvas depending on the thresholds
						if($SpacePercent>$SpaceRed){$scolor=$CriticalColor;}elseif($SpacePercent>$SpaceYellow){$scolor=$CautionColor;}else{$scolor=$GoodColor;}
						if($WeightPercent>$WeightRed){$wcolor=$CriticalColor;}elseif($WeightPercent>$WeightYellow){$wcolor=$CautionColor;}else{$wcolor=$GoodColor;}
						if($PowerPercent>$PowerRed){$pcolor=$CriticalColor;}elseif($PowerPercent>$PowerYellow){$pcolor=$CautionColor;}else{$pcolor=$GoodColor;}
						if($RealPowerPercent>$RealPowerRed){$rpcolor=$CriticalColor;}elseif($RealPowerPercent>$RealPowerYellow){$rpcolor=$CautionColor;}else{$rpcolor=$GoodColor;}
						
						/* Example for continuous color range for temperature
						if($currentTemperature==0){$tcolor=$unknounColor;}
							elseif($currentTemperature>$TemperatureRed){$tcolor=$CriticalColor;}
							elseif($currentTemperature<$TemperatureGreen){$tcolor=$GoodColor;}
							elseif($currentTemperature<$TemperatureYellow){
								$tcolor[0]=intval(($CautionColor[0]-$GoodColor[0])/($TemperatureYellow-$TemperatureGreen)*($currentTemperature-$TemperatureGreen)+$GoodColor[0]);
								$tcolor[1]=intval(($CautionColor[1]-$GoodColor[1])/($TemperatureYellow-$TemperatureGreen)*($currentTemperature-$TemperatureGreen)+$GoodColor[1]);
								$tcolor[2]=intval(($CautionColor[2]-$GoodColor[2])/($TemperatureYellow-$TemperatureGreen)*($currentTemperature-$TemperatureGreen)+$GoodColor[2]);}
							else{
								$tcolor[0]=intval(($CriticalColor[0]-$CautionColor[0])/($TemperatureRed-$TemperatureYellow)*($currentTemperature-$TemperatureYellow)+$CautionColor[0]);
								$tcolor[1]=intval(($CriticalColor[1]-$CautionColor[1])/($TemperatureRed-$TemperatureYellow)*($currentTemperature-$TemperatureYellow)+$CautionColor[1]);
								$tcolor[2]=intval(($CriticalColor[2]-$CautionColor[2])/($TemperatureRed-$TemperatureYellow)*($currentTemperature-$TemperatureYellow)+$CautionColor[2]);}
						*/
						if($currentTemperature==0){$tcolor=$unknounColor;}
							elseif($currentTemperature>$TemperatureRed){$tcolor=$CriticalColor;}
							elseif($currentTemperature>$TemperatureYellow){$tcolor=$CautionColor;}
							else{$tcolor=$GoodColor;}
						
						if($currentHumidity==0){$hcolor=$unknounColor;}
							elseif($currentHumidity>$HumidityMax || $currentHumidity<$HumidityMin){$hcolor=$CriticalColor;}
							elseif($currentHumidity>$HumidityMedMax || $currentHumidity<$HumidityMedMin) {$hcolor=$CautionColor;}
							else{$hcolor=$GoodColor;}
												
						if($SpacePercent>$SpaceRed || $WeightPercent>$WeightRed || $PowerPercent>$PowerRed || 
							$currentTemperature>$TemperatureRed || $currentHumidity>$HumidityMax || 
							$currentHumidity<$HumidityMin && $currentHumidity!=0 || 
							$RealPowerPercent>$RealPowerRed){$color=$CriticalColor;}
	        			elseif($SpacePercent>$SpaceYellow || $WeightPercent>$WeightYellow || $PowerPercent>$PowerYellow || 
	        				$currentTemperature>$TemperatureYellow || $currentHumidity>$HumidityMedMax || 
	        				$currentHumidity<$HumidityMedMin && $currentHumidity!=0  || 
	        				$RealPowerPercent>$RealPowerYellow){$color=$CautionColor;}
	        			else{$color=$GoodColor;}
	        			
						$width=$cab->MapX2-$cab->MapX1;
						$height=$cab->MapY2-$cab->MapY1;
						$textstrlen=strlen($dev->Location);
						$textXcoord=$cab->MapX1+3;
						$textYcoord=$cab->MapY1+floor($height*2/3);
	
						$border="\n\t\t\tcontext.strokeStyle='#000000';\n\t\t\tcontext.lineWidth=1;\n\t\t\tcontext.strokeRect($cab->MapX1,$cab->MapY1,$width,$height);";
						$statuscolor="\n\t\t\tcontext.fillRect($cab->MapX1,$cab->MapY1,$width,$height);";
						$airflow.="\n\t\t\tdrawArrow(context,$cab->MapX1,$cab->MapY1,$width,$height,'$cab->FrontEdge');";
						$label="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('$dev->Location',$textXcoord,$textYcoord);\n";
						$labelsp="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='bold 12px arial';\n\t\t\tcontext.fillText('".number_format($used,0, ",", ".")."',$textXcoord,$textYcoord);\n";
						$labelwe="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='bold 12px arial';\n\t\t\tcontext.fillText('".number_format($totalWeight,0, ",", ".")."',$textXcoord,$textYcoord);\n";
						$labelpo="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".number_format($totalWatts/1000,2, ",", ".")."',$textXcoord,$textYcoord);\n";
						$labelte="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".(($currentTemperature>0)?number_format($currentTemperature,0, ",", "."):"")."',$textXcoord,$textYcoord);\n";
						$labelhu="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".(($currentHumidity>0)?number_format($currentHumidity,0, ",", ".")."%":"")."',$textXcoord,$textYcoord);\n";
						$labelrp="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".(($currentRealPower>0)?number_format($currentRealPower/1000,2, ",", "."):"")."',$textXcoord,$textYcoord);\n";
	
						// Comment this to add borders and rack labels to the canvas drawing of the data center.
						// Discuss moving this into a configuration item for the future.
						$border=$label=$labelsp=$labelwe=$labelpo=$labelte=$labelhu=$labelrp="";
	
						$script.="\t\t\tcontext.fillStyle=\"rgba({$color[0]}, {$color[1]}, {$color[2]}, 0.35)\";$border$statuscolor$label\n";
						$space.="\t\t\tcontext.fillStyle=\"rgba({$scolor[0]}, {$scolor[1]}, {$scolor[2]}, .35)\";$border$statuscolor$labelsp\n";
						$weight.="\t\t\tcontext.fillStyle=\"rgba({$wcolor[0]}, {$wcolor[1]}, {$wcolor[2]}, 0.35)\";$border$statuscolor$labelwe\n";
						$power.="\t\t\tcontext.fillStyle=\"rgba({$pcolor[0]}, {$pcolor[1]}, {$pcolor[2]}, 0.35)\";$border$statuscolor$labelpo\n";
						$temperature.="\t\t\tcontext.fillStyle=\"rgba({$tcolor[0]}, {$tcolor[1]}, {$tcolor[2]}, 0.35)\";$border$statuscolor$labelte\n";
						$humidity.="\t\t\tcontext.fillStyle=\"rgba({$hcolor[0]}, {$hcolor[1]}, {$hcolor[2]}, 0.35)\";$border$statuscolor$labelhu\n";
						$realpower.="\t\t\tcontext.fillStyle=\"rgba({$rpcolor[0]}, {$rpcolor[1]}, {$rpcolor[2]}, 0.35)\";$border$statuscolor$labelrp\n";
						
						$fechaLecturaTemps=(!is_null($cabRow["LastRead"])&&($cabRow["LastRead"]>$fechaLecturaTemps))?date('d-m-Y',strtotime(($cabRow["LastRead"]))):$fechaLecturaTemps;
						$fechaLecturaRP=(!is_null($cabRow["RPLastRead"])&&($cabRow["RPLastRead"]>$fechaLecturaRP))?date('d-m-Y',strtotime(($cabRow["RPLastRead"]))):$fechaLecturaRP;
					}
				}
			}
			
			//Key
			$leyenda="\t\t\tmaptitle.html('".__("Worst state of cabinets")."');";
			$leyendasp="\t\t\tmaptitle.html('".__("Occupied space")."');";
			$leyendawe="\t\t\tmaptitle.html('".__("Calculated weight")."');";
			$leyendapo="\t\t\tmaptitle.html('".__("Calculated power usage")."');";
			$leyendate="\t\t\tmaptitle.html('".($fechaLecturaTemps>0?__("Measured on")." ".$fechaLecturaTemps:__("no data"))."');";
			$leyendahu="\t\t\tmaptitle.html('".($fechaLecturaTemps>0?__("Measured on")." ".$fechaLecturaTemps:__("no data"))."');";
			$leyendarp="\t\t\tmaptitle.html('".($fechaLecturaRP>0?__("Measured on")." ".$fechaLecturaRP:__("no data"))."');";
						/*
			$leyenda="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("OVERVIEW: worse state of cabinets")."',5,20);";
			$leyendasp="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("SPACE: occupation of cabinets")."',5,20);";
			$leyendawe="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("WEIGHT: Supported weight by cabinets")."',5,20);";
			$leyendapo="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("POWER: Computed from devices power supplies")."',5,20);";
			$leyendate="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("TEMPERATURE: Measured on")." ".$fechaLecturaTemps."',5,20);";
			$leyendahu="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("HUMIDITY: % Measured on")." ".$fechaLecturaTemps."',5,20);";
			$leyendarp="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("REAL POWER: Measured on")." ".$fechaLecturaRP."',5,20);";
			*/
			$space.=$leyendasp."\n\t\t}\n";
			$weight.=$leyendawe."\n\t\t}\n";
			$power.=$leyendapo."\n\t\t}\n";
			$temperature.=$leyendate."\n\t\t}\n";
			$humidity.=$leyendahu."\n\t\t}\n";
			$realpower.=$leyendarp."\n\t\t}\n";
			$airflow.="\n\t\t}\n";
			
			$script.=$leyenda."\n\t\t}\n";
			$script.=$space.$weight.$power.$temperature.$humidity.$realpower.$airflow;
		}
		return $script;
	}



	function GetDCStatistics(){
		$this->GetDataCenter();

		$sql="SELECT SUM(CabinetHeight) as TotalU FROM fac_Cabinet WHERE 
			DataCenterID=$this->DataCenterID;";
		$dcStats["TotalU"]=($test=$this->query($sql)->fetchColumn())?$test:0;

		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND 
			a.DeviceType NOT IN ('Server','Storage Array');";
		$dcStats["Infrastructure"]=($test=$this->query($sql)->fetchColumn())?$test:0;
 
		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND 
			a.Reservation=false AND a.DeviceType IN ('Server', 'Storage Array');";
		$dcStats["Occupied"]=($test=$this->query($sql)->fetchColumn())?$test:0;

        $sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND a.Reservation=true AND b.DataCenterID=$this->DataCenterID;";
		$dcStats["Allocated"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
        $dcStats["Available"]=$dcStats["TotalU"] - $dcStats["Occupied"] - $dcStats["Infrastructure"] - $dcStats["Allocated"];

		// Perform two queries - one is for the wattage overrides (where NominalWatts > 0) and one for the template (default) values
		$sql="SELECT SUM(NominalWatts) as TotalWatts FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND a.NominalWatts>0 AND 
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["ComputedWatts"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT SUM(c.Wattage) as TotalWatts FROM fac_Device a, fac_Cabinet b, 
			fac_DeviceTemplate c WHERE a.Cabinet=b.CabinetID AND 
			a.TemplateID=c.TemplateID AND a.NominalWatts=0 AND 
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["ComputedWatts"]+=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$pdu=new PowerDistribution();
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
			$tree.=str_repeat(" ",$lev+3)."<li class=\"liClosed\" id=\"zone$myzone->ZoneID\"><a class=\"ZONE\" href=\"zone_stats.php?zone="
				."$myzone->ZoneID\">$myzone->Description</a>\n";
			$tree.=str_repeat(" ",$lev+4)."<ul>\n";
			//Rows
			$sql="SELECT CabRowID, Name AS Fila FROM fac_CabRow WHERE 
				ZoneID=$myzone->ZoneID ORDER BY Fila;";
			
			foreach($this->query($sql) as $filaRow){
				$tree.=str_repeat(" ",$lev+5)."<li class=\"liClosed\" id=\"fila{$filaRow['Fila']}\">".
			  		"<a class=\"CABROW\" href=\"rowview.php?row={$filaRow['CabRowID']}\">".__("Row ")."{$filaRow['Fila']}</a>\n";
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
		
		//StorageRoom for this DC
		$tree.="<li class=\"liOpen\" id=\"dc-1\"><a href=\"storageroom.php?dc=$this->DataCenterID\">".__("Storage Room")."</a></li>\n";
		
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
    var $Notes;
    var $FrontPictureFile;
    var $RearPictureFile;
	var $ChassisSlots;
	var $RearChassisSlots;
    
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
        $this->Notes=addslashes(trim($this->Notes));
        $this->FrontPictureFile=addslashes(trim($this->FrontPictureFile));
	    $this->RearPictureFile=addslashes(trim($this->RearPictureFile));
		$this->ChassisSlots=intval($this->ChassisSlots);
		$this->RearChassisSlots=intval($this->RearChassisSlots);
	}

	function MakeDisplay(){
		$this->Model=stripslashes($this->Model);
        $this->Notes=stripslashes($this->Notes);
        $this->FrontPictureFile=stripslashes($this->FrontPictureFile);
	    $this->RearPictureFile=stripslashes($this->RearPictureFile);
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
        $Template->Notes=$row["Notes"];
        $Template->FrontPictureFile=$row["FrontPictureFile"];
        $Template->RearPictureFile=$row["RearPictureFile"];
		$Template->ChassisSlots=$row["ChassisSlots"];
		$Template->RearChassisSlots=$row["RearChassisSlots"];
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
			PSCount=$this->PSCount, NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
			FrontPictureFile=\"$this->FrontPictureFile\", RearPictureFile=\"$this->RearPictureFile\",
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots;";

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
		$this->MakeSafe();
        $sql="UPDATE fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID,
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			PSCount=$this->PSCount, NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
			FrontPictureFile=\"$this->FrontPictureFile\", RearPictureFile=\"$this->RearPictureFile\",
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots
			WHERE TemplateID=$this->TemplateID;";

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

		//JMGA Reset object in case of a lookup failure
		$this->ManufacturerID=0;
		$this->Model="";
		$this->Height=0;
		$this->Weight=0;
		$this->Wattage=0;
		$this->DeviceType='Server';
		$this->PSCount=0;
		$this->NumPorts=0;
        $this->Notes="";
        $this->FrontPictureFile="";
	    $this->RearPictureFile="";
		$this->ChassisSlots=0;
		$this->RearChassisSlots=0;
		// Reset object in case of a lookup failure
		//foreach($this as $prop => $value){
		//	$value=($prop!='TemplateID')?null:$value;
		//}
		
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

    /**
     * Return a list of the templates indexed by the TemplateID
     *
     * @param DbLink $db
     * @return multitype:DeviceTemplate
     */
    function getTemplateListIndexedbyID ()
    {
        global $dbh;
        $templateList = array();
        $stmt = $dbh->prepare('select * from fac_DeviceTemplate');
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $devTempl = DeviceTemplate::RowToObject($row);
            $templateList[$devTempl->TemplateID] = $devTempl;
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
			$devList[]=Device::RowToObject($row);
		}

		$this->MakeDisplay();
		return $devList;
	}

	function UpdateDevices(){
		/* This will cause every device with a TemplateID matching this one to display
		   the updated values.  We are not touching DeviceType or NumPorts at this time
		   because those have alternate side effects that i'm not sure we really need
		   to address here
		*/
		$this->MakeSafe();

		$sql="UPDATE fac_Device SET Height=$this->Height, NominalWatts=$this->Wattage, 
			PowerSupplyCount=$this->PSCount WHERE TemplateID=$this->TemplateID;";

		return $this->query($sql);
	}
	
	function DeleteSlots(){
		$this->MakeSafe();
		
		//delete zone
		$sql="DELETE FROM fac_Slots WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
		
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
	var $MapX1;
	var $MapY1;
	var $MapX2;
	var $MapY2;
	var $MapZoom;  // % of Zoom (100=>no zoom)
	function MakeSafe(){
		$this->ZoneID=intval($this->ZoneID);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->Description=addslashes(trim($this->Description));
		// ensure all coordinates are positive values
		$this->MapX1=abs($this->MapX1);
		$this->MapY1=abs($this->MapY1);
		$this->MapX2=abs($this->MapX2);
		$this->MapY2=abs($this->MapY2);
		$this->MapZoom=abs($this->MapZoom);
	}

	function MakeDisplay(){
		$this->Description=stripslashes($this->Description);
	}

	static function RowToObject($row){
		$zone=New Zone();
		$zone->ZoneID=$row["ZoneID"];
		$zone->DataCenterID=$row["DataCenterID"];
		$zone->Description=$row["Description"];
		$zone->MapX1=$row["MapX1"];
		$zone->MapY1=$row["MapY1"];
		$zone->MapX2=$row["MapX2"];
		$zone->MapY2=$row["MapY2"];
		$zone->MapZoom=$row["MapZoom"];
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
			DataCenterID=$this->DataCenterID,
			MapX1=$this->MapX1,
			MapY1=$this->MapY1,
			MapX2=$this->MapX2,
			MapY2=$this->MapY2,
			MapZoom=$this->MapZoom
			;";
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
			
		//update all cabinets in this zone
		$sql="UPDATE fac_Cabinet SET DataCenterID=$this->DataCenterID WHERE 
			ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
	
		//update zone	
		$sql="UPDATE fac_Zone SET Description=\"$this->Description\", 
			DataCenterID=$this->DataCenterID,
			MapX1=$this->MapX1,
			MapY1=$this->MapY1,
			MapX2=$this->MapX2,
			MapY2=$this->MapY2, 
			MapZoom=$this->MapZoom 
			WHERE ZoneID=$this->ZoneID;";
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
	
	function GetZoneStatistics(){
		$this->GetZone();

		$sql="SELECT SUM(CabinetHeight) as TotalU FROM fac_Cabinet WHERE
			ZoneID=$this->ZoneID;";
		$zoneStats["TotalU"]=($test=$this->query($sql)->fetchColumn())?$test:0;

		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND b.ZoneID=$this->ZoneID AND
			a.DeviceType NOT IN ('Server','Storage Array');";
		$zoneStats["Infrastructure"]=($test=$this->query($sql)->fetchColumn())?$test:0;

		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND b.ZoneID=$this->ZoneID AND
			a.Reservation=false AND a.DeviceType IN ('Server', 'Storage Array');";
		$zoneStats["Occupied"]=($test=$this->query($sql)->fetchColumn())?$test:0;

        $sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE
			a.Cabinet=b.CabinetID AND a.Reservation=true AND b.ZoneID=$this->ZoneID;";
		$zoneStats["Allocated"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
        $zoneStats["Available"]=$zoneStats["TotalU"] - $zoneStats["Occupied"] - $zoneStats["Infrastructure"] - $zoneStats["Allocated"];

		// Perform two queries - one is for the wattage overrides (where NominalWatts > 0) and one for the template (default) values
		$sql="SELECT SUM(NominalWatts) as TotalWatts FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND a.NominalWatts>0 AND 
			b.ZoneID=$this->ZoneID;";
		$zoneStats["ComputedWatts"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT SUM(c.Wattage) as TotalWatts FROM fac_Device a, fac_Cabinet b, 
			fac_DeviceTemplate c WHERE a.Cabinet=b.CabinetID AND 
			a.TemplateID=c.TemplateID AND a.NominalWatts=0 AND 
			b.ZoneID=$this->ZoneID;";
		$zoneStats["ComputedWatts"]+=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT SUM(Wattage) AS Wattage FROM fac_PDUStats WHERE PDUID IN 
			(SELECT PDUID FROM fac_PowerDistribution WHERE CabinetID IN 
			(SELECT CabinetID FROM fac_Cabinet WHERE ZoneID=$this->ZoneID))";
		$zoneStats["MeasuredWatts"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		return $zoneStats;
	}
	
	function MakeImageMap($nolinks=null) {
		$this->MakeSafe();
		$zoom=$this->MapZoom/100;
		$mapHTML="";
		$dc=new DataCenter();
		$dc->DataCenterID=$this->DataCenterID;
		$dc->GetDataCenterbyID();

		if(strlen($dc->DrawingFileName)>0){
			//$mapfile="drawings".DIRECTORY_SEPARATOR.$this->DrawingFileName;
			$mapfile="drawings/".$dc->DrawingFileName;
			if(file_exists($mapfile)){
				list($width, $height, $type, $attr)=getimagesize($mapfile);
				$width=($this->MapX2-$this->MapX1)*$zoom;
				$height=($this->MapY2-$this->MapY1)*$zoom;
				$mapHTML.="<div class=\"canvas\">\n";
				$mapHTML.="<img src=\"css/blank.gif\" usemap=\"#datacenter\" width=\"$width\" height=\"$height\" alt=\"clearmap over canvas\">\n";
				$mapHTML.="<map name=\"datacenter\">\n";
				if(is_null($nolinks)){
					$sql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID;";

					if($racks=$this->query($sql)){
						foreach($racks as $row){
							$mapHTML.="<area href=\"cabnavigator.php?cabinetid={$row["CabinetID"]}\" shape=\"rect\"";
							$mapHTML.=" coords=\"".(($row["MapX1"]-$this->MapX1)*$zoom).",".(($row["MapY1"]-$this->MapY1)*$zoom).",".(($row["MapX2"]-$this->MapX1)*$zoom).",".(($row["MapY2"]-$this->MapY1)*$zoom)."\"";
							//$mapHTML.=" alt=\"{$row["Location"]}\" title=\"{$row["Location"]}\">\n";
							$mapHTML.=" alt=\"{$row["Location"]}\">\n";
						}
					}
				}
				$mapHTML.="</map>\n";
				$mapHTML.="<canvas id=\"mapCanvas\" width=\"$width\" height=\"$height\"></canvas>\n";
					 
				$mapHTML .= "<br><br><br><br><br><br><br><br></div>\n";
			}
		}
		return $mapHTML;
	}
	function DrawCanvas(){
		$this->MakeSafe();
		$zoom=$this->MapZoom/100;
		$dc=new DataCenter();
		$dc->DataCenterID=$this->DataCenterID;
		$dc->GetDataCenterbyID();
		$script="";
		// check to see if map was set
		if(strlen($dc->DrawingFileName)){
			//$mapfile="drawings".DIRECTORY_SEPARATOR.$this->DrawingFileName;
			$mapfile="drawings/".$dc->DrawingFileName;
			// map was set in config check to ensure a file exists before we attempt to use it
			if(file_exists($mapfile)){
				$dc->dcconfig=new Config();
				$dev=new Device();
				$templ=new DeviceTemplate();
				$cab=new Cabinet();
				// get all color codes and limits for use with loop below
				$CriticalColor=html2rgb($dc->dcconfig->ParameterArray["CriticalColor"]);
				$CautionColor=html2rgb($dc->dcconfig->ParameterArray["CautionColor"]);
				$GoodColor=html2rgb($dc->dcconfig->ParameterArray["GoodColor"]);
				$SpaceRed=intval($dc->dcconfig->ParameterArray["SpaceRed"]);
				$SpaceYellow=intval($dc->dcconfig->ParameterArray["SpaceYellow"]);
				$WeightRed=intval($dc->dcconfig->ParameterArray["WeightRed"]);
				$WeightYellow=intval($dc->dcconfig->ParameterArray["WeightYellow"]);
				$PowerRed=intval($dc->dcconfig->ParameterArray["PowerRed"]);
				$PowerYellow=intval($dc->dcconfig->ParameterArray["PowerYellow"]);
				
				// Temperature 
				$unknounColor=html2rgb('FFFFFF');
				$TemperatureYellow=intval($dc->dcconfig->ParameterArray["TemperatureYellow"]);
				$TemperatureRed=intval($dc->dcconfig->ParameterArray["TemperatureRed"]);
				
				// Humidity
				$HumidityMin=intval($dc->dcconfig->ParameterArray["HumidityRedLow"]);
				$HumidityMedMin=intval($dc->dcconfig->ParameterArray["HumidityYellowLow"]);			
				$HumidityMedMax=intval($dc->dcconfig->ParameterArray["HumidityYellowHigh"]);				
				$HumidityMax=intval($dc->dcconfig->ParameterArray["HumidityRedHigh"]);
								
				//Real Power
				$RealPowerRed=intval($dc->dcconfig->ParameterArray["PowerRed"]);
				$RealPowerYellow=intval($dc->dcconfig->ParameterArray["PowerYellow"]);
				
				// get image file attributes and type
				list($width, $height, $type, $attr)=getimagesize($mapfile);

				$script.="\n\t\tvar maptitle=$('#maptitle span');
		var mycanvas=document.getElementById(\"mapCanvas\");
		var context=mycanvas.getContext('2d');
		context.globalCompositeOperation='destination-over';


		function clearcanvas(){
			// erase anything on the canvas
			context.clearRect(0,0, mycanvas.width, mycanvas.height);
			// create a new image for the canvas
			var img=new Image();
			// draw after the image has loaded
			img.onload=function(){
				// changed to eliminate the flickering of reloading the background image on a redraw
				context.drawImage(img,-$this->MapX1*$zoom,-$this->MapY1*$zoom,$width*$zoom,$height*$zoom);
				airflow();
			}
			// give it an image to load
			img.src=\"$mapfile\";
		}

		function loadCanvas(){\n\t\t\tclearcanvas();\n";

				$space="\t\tfunction space(){\n\t\t\tclearcanvas();\n";
				$weight="\t\tfunction weight(){\n\t\t\tclearcanvas();\n";
				$power="\t\tfunction power(){\n\t\t\tclearcanvas();\n";
				$temperature="\t\tfunction temperatura(){\n\t\t\tclearcanvas();\n";
				$humidity="\t\tfunction humedad(){\n\t\t\tclearcanvas();\n";				
				$realpower="\t\tfunction realpower(){\n\t\t\tclearcanvas();\n";				
				$airflow="\t\tfunction airflow(){\n";				
								
				$sql="SELECT C.*, Temps.Temp, Temps.Humidity, Stats.Wattage AS RealPower, 
					Temps.LastRead, Temps.LastRead AS RPLastRead FROM fac_Cabinet AS C
					LEFT JOIN fac_CabinetTemps AS Temps ON C.CabinetID=Temps.CabinetID
					LEFT JOIN fac_PowerDistribution AS P ON C.CabinetID=P.CabinetID
					LEFT JOIN fac_PDUStats AS Stats ON P.PDUID=Stats.PDUID 
					WHERE C.ZoneID=$this->ZoneID GROUP BY CabinetID;";

				$fechaLecturaTemps=0;
				$fechaLecturaRP=0;
				if($racks=$this->query($sql)){ 
					// read all cabinets and draw image map
					foreach($racks as $cabRow){
						$cab->CabinetID=$cabRow["CabinetID"];
						if (!$cab->GetCabinet()){
							continue;
						}
						if ($cab->MapX1==$cab->MapX2 || $cab->MapY1==$cab->MapY2){
							continue;
						}
						$dev->Cabinet=$cab->CabinetID;
						$dev->Location=$cab->Location;  //$dev->Location ???
	    	    		$devList=$dev->ViewDevicesByCabinet();
						$currentHeight = $cab->CabinetHeight;
	        			$totalWatts = $totalWeight =0;
						$currentTemperature=$cabRow["Temp"];
						$currentHumidity=$cabRow["Humidity"];
						$currentRealPower=$cabRow["RealPower"];
						
						while(list($devID,$device)=each($devList)){
							$totalWatts+=$device->GetDeviceTotalPower();
							$DeviceTotalWeight=$device->GetDeviceTotalWeight();
							$totalWeight+=$DeviceTotalWeight;
						}
	        			$used=$cab->CabinetOccupancy($cab->CabinetID);
						// check to make sure the cabinet height is set to keep errors out of the logs
						if(!isset($cab->CabinetHeight)||$cab->CabinetHeight==0){$SpacePercent=100;}else{$SpacePercent=number_format($used /$cab->CabinetHeight *100,0);}
						// check to make sure there is a weight limit set to keep errors out of logs
						if(!isset($cab->MaxWeight)||$cab->MaxWeight==0){$WeightPercent=0;}else{$WeightPercent=number_format($totalWeight /$cab->MaxWeight *100,0);}
						// check to make sure there is a kilowatt limit set to keep errors out of logs
	    	    		if(!isset($cab->MaxKW)||$cab->MaxKW==0){$PowerPercent=0;}else{$PowerPercent=number_format(($totalWatts /1000 ) /$cab->MaxKW *100,0);}
						if(!isset($cab->MaxKW)||$cab->MaxKW==0){$RealPowerPercent=0;}else{$RealPowerPercent=number_format(($currentRealPower /1000 ) /$cab->MaxKW *100,0, ",", ".");}
					
						//Decide which color to paint on the canvas depending on the thresholds
						if($SpacePercent>$SpaceRed){$scolor=$CriticalColor;}elseif($SpacePercent>$SpaceYellow){$scolor=$CautionColor;}else{$scolor=$GoodColor;}
						if($WeightPercent>$WeightRed){$wcolor=$CriticalColor;}elseif($WeightPercent>$WeightYellow){$wcolor=$CautionColor;}else{$wcolor=$GoodColor;}
						if($PowerPercent>$PowerRed){$pcolor=$CriticalColor;}elseif($PowerPercent>$PowerYellow){$pcolor=$CautionColor;}else{$pcolor=$GoodColor;}
						if($RealPowerPercent>$RealPowerRed){$rpcolor=$CriticalColor;}elseif($RealPowerPercent>$RealPowerYellow){$rpcolor=$CautionColor;}else{$rpcolor=$GoodColor;}
						
						if($currentTemperature==0){$tcolor=$unknounColor;}
							elseif($currentTemperature>$TemperatureRed){$tcolor=$CriticalColor;}
							elseif($currentTemperature>$TemperatureYellow){$tcolor=$CautionColor;}
							else{$tcolor=$GoodColor;}
						
						if($currentHumidity==0){$hcolor=$unknounColor;}
							elseif($currentHumidity>$HumidityMax || $currentHumidity<$HumidityMin){$hcolor=$CriticalColor;}
							elseif($currentHumidity>$HumidityMedMax || $currentHumidity<$HumidityMedMin) {$hcolor=$CautionColor;}
							else{$hcolor=$GoodColor;}
												
						if($SpacePercent>$SpaceRed || $WeightPercent>$WeightRed || $PowerPercent>$PowerRed || 
							$currentTemperature>$TemperatureRed || $currentHumidity>$HumidityMax || 
							$currentHumidity<$HumidityMin && $currentHumidity!=0 || 
							$RealPowerPercent>$RealPowerRed){$color=$CriticalColor;}
	        			elseif($SpacePercent>$SpaceYellow || $WeightPercent>$WeightYellow || $PowerPercent>$PowerYellow || 
	        				$currentTemperature>$TemperatureYellow || $currentHumidity>$HumidityMedMax || 
	        				$currentHumidity<$HumidityMedMin && $currentHumidity!=0  || 
	        				$RealPowerPercent>$RealPowerYellow){$color=$CautionColor;}
	        			else{$color=$GoodColor;}
	        			
						$width=($cab->MapX2-$cab->MapX1)*$zoom;
						$height=($cab->MapY2-$cab->MapY1)*$zoom;
						$textstrlen=strlen($dev->Location);
						$textXcoord=($cab->MapX1-$this->MapX1)*$zoom+3;
						$textYcoord=($cab->MapY1-$this->MapY1)*$zoom+floor($height*2/3);
	        				
						$border="\n\t\t\tcontext.strokeStyle='#000000';\n\t\t\tcontext.lineWidth=1;\n\t\t\tcontext.strokeRect(($cab->MapX1-$this->MapX1)*$zoom,($cab->MapY1-$this->MapY1)*$zoom,$width,$height);";
						$statuscolor="\n\t\t\tcontext.fillRect(($cab->MapX1-$this->MapX1)*$zoom,($cab->MapY1-$this->MapY1)*$zoom,$width,$height);";
						$airflow.="\n\t\t\tdrawArrow(context,($cab->MapX1-$this->MapX1)*$zoom,($cab->MapY1-$this->MapY1)*$zoom,$width,$height,'$cab->FrontEdge');";
						$label="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('$dev->Location',$textXcoord,$textYcoord);\n";
						$labelsp="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='bold 12px arial';\n\t\t\tcontext.fillText('".number_format($used,0, ",", ".")."',$textXcoord,$textYcoord);\n";
						$labelwe="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='bold 12px arial';\n\t\t\tcontext.fillText('".number_format($totalWeight,0, ",", ".")."',$textXcoord,$textYcoord);\n";
						$labelpo="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".number_format($totalWatts/1000,2, ",", ".")."',$textXcoord,$textYcoord);\n";
						$labelte="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".(($currentTemperature>0)?number_format($currentTemperature,0, ",", "."):"")."',$textXcoord,$textYcoord);\n";
						$labelhu="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".(($currentHumidity>0)?number_format($currentHumidity,0, ",", ".")."%":"")."',$textXcoord,$textYcoord);\n";
						$labelrp="\n\t\t\tcontext.fillStyle='#000000';\n\t\t\tcontext.font='10px arial';\n\t\t\tcontext.fillText('".(($currentRealPower>0)?number_format($currentRealPower/1000,2, ",", "."):"")."',$textXcoord,$textYcoord);\n";
	
						// Comment this to add borders and rack labels to the canvas drawing of the data center.
						// Discuss moving this into a configuration item for the future.
						$border=$label=$labelsp=$labelwe=$labelpo=$labelte=$labelhu=$labelrp="";
	
						$script.="\t\t\tcontext.fillStyle=\"rgba({$color[0]}, {$color[1]}, {$color[2]}, 0.35)\";$border$statuscolor$label\n";
						$space.="\t\t\tcontext.fillStyle=\"rgba({$scolor[0]}, {$scolor[1]}, {$scolor[2]}, .35)\";$border$statuscolor$labelsp\n";
						$weight.="\t\t\tcontext.fillStyle=\"rgba({$wcolor[0]}, {$wcolor[1]}, {$wcolor[2]}, 0.35)\";$border$statuscolor$labelwe\n";
						$power.="\t\t\tcontext.fillStyle=\"rgba({$pcolor[0]}, {$pcolor[1]}, {$pcolor[2]}, 0.35)\";$border$statuscolor$labelpo\n";
						$temperature.="\t\t\tcontext.fillStyle=\"rgba({$tcolor[0]}, {$tcolor[1]}, {$tcolor[2]}, 0.35)\";$border$statuscolor$labelte\n";
						$humidity.="\t\t\tcontext.fillStyle=\"rgba({$hcolor[0]}, {$hcolor[1]}, {$hcolor[2]}, 0.35)\";$border$statuscolor$labelhu\n";
						$realpower.="\t\t\tcontext.fillStyle=\"rgba({$rpcolor[0]}, {$rpcolor[1]}, {$rpcolor[2]}, 0.35)\";$border$statuscolor$labelrp\n";
						
						$fechaLecturaTemps=(!is_null($cabRow["LastRead"])&&($cabRow["LastRead"]>$fechaLecturaTemps))?date('d-m-Y',strtotime(($cabRow["LastRead"]))):$fechaLecturaTemps;
						$fechaLecturaRP=(!is_null($cabRow["RPLastRead"])&&($cabRow["RPLastRead"]>$fechaLecturaRP))?date('d-m-Y',strtotime(($cabRow["RPLastRead"]))):$fechaLecturaRP;
					}
				}
			}
			//Key
			$leyenda="\t\t\tmaptitle.html('".__("Worst state of cabinets")."');";
			$leyendasp="\t\t\tmaptitle.html('".__("Occupied space")."');";
			$leyendawe="\t\t\tmaptitle.html('".__("Calculated weight")."');";
			$leyendapo="\t\t\tmaptitle.html('".__("Calculated power usage")."');";
			$leyendate="\t\t\tmaptitle.html('".($fechaLecturaTemps>0?__("Measured on")." ".$fechaLecturaTemps:__("no data"))."');";
			$leyendahu="\t\t\tmaptitle.html('".($fechaLecturaTemps>0?__("Measured on")." ".$fechaLecturaTemps:__("no data"))."');";
			$leyendarp="\t\t\tmaptitle.html('".($fechaLecturaRP>0?__("Measured on")." ".$fechaLecturaRP:__("no data"))."');";
			/*
			$leyenda="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("OVERVIEW: worse state of cabinets")."',5,20);";
			$leyendasp="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("SPACE: occupation of cabinets")."',5,20);";
			$leyendawe="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("WEIGHT: Supported weight by cabinets")."',5,20);";
			$leyendapo="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("POWER: Computed from devices power supplies")."',5,20);";
			$leyendate="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("TEMPERATURE: Measured on")." ".$fechaLecturaTemps."',5,20);";
			$leyendahu="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("HUMIDITY: % Measured on")." ".$fechaLecturaTemps."',5,20);";
			$leyendarp="\n\t\tcontext.fillStyle='#000000';\n\t\tcontext.font='15px arial';
				\n\t\tcontext.fillText('".__("REAL POWER: Measured on")." ".$fechaLecturaRP."',5,20);";
			*/
			
			$space.=$leyendasp."\n\t\t}\n";
			$weight.=$leyendawe."\n\t\t}\n";
			$power.=$leyendapo."\n\t\t}\n";
			$temperature.=$leyendate."\n\t\t}\n";
			$humidity.=$leyendahu."\n\t\t}\n";
			$realpower.=$leyendarp."\n\t\t}\n";
			$airflow.="\n\t\t}\n";
			
			$script.=$leyenda."\n\t\t}\n";
			$script.=$space.$weight.$power.$temperature.$humidity.$realpower.$airflow;
		}
		return $script;
	}

	function MakeDCZoneMiniImage() {
		$mapHTML = "";
		$mapfile="";
		$red=.5;
		
		$dc=new DataCenter();
		$dc->DataCenterID=$this->DataCenterID;
		$dc->GetDataCenterbyID();

		if ( strlen($dc->DrawingFileName) > 0 ) {
			$mapfile = "drawings/" . $dc->DrawingFileName;
		}

		if ( file_exists( $mapfile ) ) {
			list($width, $height, $type, $attr)=getimagesize($mapfile);
			$mapHTML.="<div style='position:relative;'>\n";
			$mapHTML.="<img id='dczoneimg' src=\"".$mapfile."\" width=\"".($width*$red)."\" height=\"".($height*$red)."\"
					 onclick='coords(event)' alt=\"DC Zone Image\">\n";
			$mapHTML .= "<canvas id=\"mapCanvas\" width=\"".($width*$red)."\" height=\"".($height*$red)."\"></canvas>\n";
			$mapHTML .= "</div>\n";
	    }
	    return $mapHTML;
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
		$this->CabOrder=($this->CabOrder=="ASC")?"ASC":"DESC";
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$cabrow=new CabRow();
		$cabrow->CabRowID=$row["CabRowID"];
		$cabrow->Name=$row["Name"];
		$cabrow->ZoneID=$row["ZoneID"];
		$cabrow->CabOrder=$row["CabOrder"];
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

		//update all cabinets in this cabrow
		$sql="UPDATE fac_Cabinet SET ZoneID=$this->ZoneID, DataCenterID=(SELECT DataCenterID FROM fac_Zone WHERE ZoneID=$this->ZoneID) WHERE CabRowID=$this->CabRowID;";
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

	function SetDirection(){
		$this->MakeSafe();

		$sql="UPDATE fac_CabRow SET CabOrder=\"$this->CabOrder\" WHERE CabRowID=$this->CabRowID;";

		return $this->query($sql);
	}
}

//JMGA: container objects may contain DCs or other containers
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
		$this->MapX=abs($this->MapX);
		$this->MapY=abs($this->MapY);
	}
	
	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->DrawingFileName=stripslashes($this->DrawingFileName);
	}

	static function RowToObject($row){
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
			$datacenterList[$row["DataCenterID"]]=DataCenter::RowToObject($row);
		}

		return $datacenterList;
	}
		
	function BuildMenuTree() {
		$c=new Container();
		//begin the tree
		$tree="\n<ul class=\"mktree\" id=\"datacenters\">\n";;
		//Add root children
		$tree.=$c->AddContainerToTree();
		$tree.="<li class=\"liOpen\" id=\"dc-1\"><a href=\"storageroom.php\">".__("General Storage Room")."</a></li>\n";
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

    /**
     * Returns the maximum level of containers.
     * 
     * @param int $level
     * @return int
     */
    public function computeMaxLevel($level = 0)
    {
        $this->GetContainer();
        $maxLevel = $level;
        $containerChildList = $this->GetChildContainerList();
        if (count($containerChildList) > 0) {
            $level++;
            foreach ($containerChildList as $cName => $childContainer) {
                $retval = $childContainer->computeMaxLevel($level);
                $maxLevel = max(array($retval, $maxLevel));
            }
        }
        return $maxLevel;
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

//Class Slots (coordinates of front/rear slots in device front/rear picture)
class Slot {
	var $TemplateID;
	var $Position;
	var $BackSide;
	var $X;
	var $Y;
	var $W;
	var $H;

	function MakeSafe(){
		$this->TemplateID=intval($this->TemplateID);
		$this->Position=intval($this->Position);
		$this->BackSide=intval($this->BackSide);
		$this->X=abs($this->X);
		$this->Y=abs($this->Y);
		$this->W=abs($this->W);
		$this->H=abs($this->H);
	}

	static function RowToObject($row){
		$slot=New Slot();
		$slot->TemplateID=$row["TemplateID"];
		$slot->Position=$row["Position"];
		$slot->BackSide=$row["BackSide"];
		$slot->X=$row["X"];
		$slot->Y=$row["Y"];
		$slot->W=$row["W"];
		$slot->H=$row["H"];

		return $slot;
	}
 
	function query($sql){
		global $dbh;
		return $dbh->query($sql);
	}
	
	function exec($sql){
		global $dbh;
		return $dbh->exec($sql);
	}
	
	function CreateSlot(){
		global $dbh;
			
		$this->MakeSafe();
			
		$sql="INSERT INTO fac_Slots SET TemplateID=$this->TemplateID, 
			Position=$this->Position,
			BackSide=$this->BackSide,
			X=$this->X,
			Y=$this->Y,
			W=$this->W,
			H=$this->H
			;";
		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();
			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}else{
			return true;
		}
	}
	
	function UpdateSlot(){
		$this->MakeSafe();
			
		$sql="UPDATE fac_Slots SET  
			X=$this->X,
			Y=$this->Y,
			W=$this->W, 
			H=$this->H 
			WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND BackSide=$this->BackSide;";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}
	
	function DeleteSlot(){
		$this->MakeSafe();
		
		//delete slot
		$sql="DELETE FROM fac_Slots WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND BackSide=$this->BackSide;";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}
  
	function GetSlot(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Slots WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND BackSide=$this->BackSide;";
		if($row=$this->query($sql)->fetch()){
			foreach(Slot::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}
	function GetFistSlot(){
		$this->MakeSafe();
		
		$sql="SELECT * FROM fac_Slots WHERE TemplateID=$this->TemplateID ORDER BY BackSide,Position;";
		if($row=$this->query($sql)->fetch()){
			foreach(Slot::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	} 
} //END of Class Slot

?>
