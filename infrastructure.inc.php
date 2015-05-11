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
		$this->UserID=sanitize($this->UserID);
		$this->AuditStamp=sanitize($this->AuditStamp);
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
		$this->Name=sanitize($this->Name);
		$this->SquareFootage=intval($this->SquareFootage);
		$this->DeliveryAddress=sanitize($this->DeliveryAddress);
		$this->Administrator=sanitize($this->Administrator);
		$this->MaxkW=intval($this->MaxkW);
		$this->DrawingFileName=sanitize($this->DrawingFileName);
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
		global $dbh;
		$this->MakeSafe();
		
		$sql="INSERT INTO fac_DataCenter SET Name=\"$this->Name\", 
			SquareFootage=$this->SquareFootage, DeliveryAddress=\"$this->DeliveryAddress\", 
			Administrator=\"$this->Administrator\", MaxkW=$this->MaxkW, 
			DrawingFileName=\"$this->DrawingFileName\", EntryLogging=0,	
			ContainerID=$this->ContainerID,	MapX=$this->MapX, MapY=$this->MapY;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error::DataCenter:CreateDataCenter {$info[2]} SQL=$sql");
			return false;
		}

		$this->DataCenterID=$dbh->lastInsertId();
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true; 
	}

	function DeleteDataCenter($junkremoval=true) {
		$this->MakeSafe();
		
		// Have to make sure that we delete EVERYTHING and not create orphans
		// Also, if we are down to the last data center, refuse to delete it
		$sql = "SELECT COUNT(*) AS Total FROM fac_DataCenter;";
		if ( ! $row = $this->query($sql)->fetch() ) {
			return false;
		}
		
		if ( $row["Total"] < 2 ) {
			return false;
		}

		// The Cabinet delete function already deletes all children within it, first, so just delete all of them
		$cab = new Cabinet();
		$cab->DataCenterID = $this->DataCenterID;
		$cabList = $cab->ListCabinetsByDC();
		
		foreach( $cabList as $c ) {
			$c->DeleteCabinet();
		}
		
		// Now delete any Zones or Rows that are attached to this data center
		$zn = new Zone();
		$zn->DataCenterID = $this->DataCenterID;
		$zoneList = $zn->GetZonesByDC();
		
		foreach ( $zoneList as $z ) {
			// This function already deletes any rows within the zone
			$z->DeleteZone();
		}
		
		// Time to deal with the crap in storage

		// Get a list of all the devices that are in this data center's storage room
		$sql="SELECT * FROM fac_Device WHERE Cabinet=-1 AND Position=$this->DataCenterID;";
		$devices=array();

		foreach($this->query($sql) as $row){
			$devices[]=Device::RowToObject($row);
		}

		// Default action is just to delete them
		if($junkremoval){
			foreach($devices as $dev){
				$dev->DeleteDevice();
			}
		}else{ // move it to the general storage room
			foreach($devices as $dev){
				$dev->Position=0;
				$dev->UpdateDevice();
			}
		}
	
		// Finally, delete the data center itself
		$sql="DELETE FROM fac_DataCenter WHERE DataCenterID=$this->DataCenterID;";
		$this->exec($sql);
		
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
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
	
		$old=new DataCenter();
		$old->DataCenterID=$this->DataCenterID;
		$old->GetDataCenter();

		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
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
		
	static function GetDCList($indexedbyid=false){
		global $dbh;

		$sql="SELECT * FROM fac_DataCenter ORDER BY Name ASC;";

		$datacenterList=array();
		foreach($dbh->query($sql) as $row){
			if($indexedbyid){
				$datacenterList[$row['DataCenterID']]=DataCenter::RowToObject($row);
			}else{
				$datacenterList[]=DataCenter::RowToObject($row);
			}
		}

		return $datacenterList;
	}

	function GetDataCenterbyID(){
		// Not sure why this was duplicated but this will do til we clear up the references
		return $this->GetDataCenter();
	}

	// Return an array of the immediate children
	function GetChildren(){
		$children=array();
		$zone=new Zone();
		$zone->DataCenterID=$this->DataCenterID;
		foreach($zone->GetZonesByDC() as $child){
			$children[]=$child;
		}
		$row=new CabRow();
		$row->DataCenterID=$this->DataCenterID;
		foreach($row->GetCabRowsByDC(true) as $child){
			$children[]=$child;
		}
		$cab=new Cabinet();
		$cab->DataCenterID=$this->DataCenterID;
		foreach($cab->ListCabinetsByDC() as $child){
			if($child->ZoneID>0 || $child->CabRowID>0 || $child->DataCenterID!=$this->DataCenterID){
			}else{
				$children[]=$child;
			}
		}

		return $children;
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

	function GetOverview(){
		$this->MakeSafe();
		$statusarray=array();	
		// check to see if map was set
		if(strlen($this->DrawingFileName)){
			$mapfile="drawings".DIRECTORY_SEPARATOR.$this->DrawingFileName;

			$overview=array();
			$space=array();
			$weight=array();
			$power=array();
			$temperature=array();
			$humidity=array();
			$realpower=array();
			$colors=array();
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
				$unknown=html2rgb('FFFFFF');

				// Copy all colors into an array to export
				$color['unk']=array('r' => $unknown[0], 'g' => $unknown[1], 'b' => $unknown[2]);
				$color['bad']=array('r' => $CriticalColor[0], 'g' => $CriticalColor[1], 'b' => $CriticalColor[2]);
				$color['med']=array('r' => $CautionColor[0], 'g' => $CautionColor[1], 'b' => $CautionColor[2]);
				$color['low']=array('r' => $GoodColor[0], 'g' => $GoodColor[1], 'b' => $GoodColor[2]);
				$colors=$color;

				// Assign color variables 
				$CriticalColor='bad';
				$CautionColor='med';
				$GoodColor='low';
				$unknownColor='unk';

				// Temperature
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

				$cdus=array();
				$sql="SELECT C.CabinetID, P.RealPower, P.BreakerSize, P.InputAmperage * PP.PanelVoltage AS VoltAmp 
					FROM ((fac_Cabinet C LEFT JOIN fac_CabinetTemps T ON C.CabinetId = T.CabinetID) LEFT JOIN
						(SELECT CabinetID, Wattage AS RealPower, BreakerSize, InputAmperage, PanelID FROM 
						fac_PowerDistribution PD LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID ) P 
						ON C.CabinetId = P.CabinetID)
					LEFT JOIN (SELECT PanelVoltage, PanelID FROM fac_PowerPanel) PP ON PP.PanelID=P.PanelID
					WHERE PanelVoltage IS NOT NULL AND RealPower IS NOT NULL AND 
					C.DataCenterID=".intval($this->DataCenterID).";";

				$rpvalues=$this->query($sql);
				foreach($rpvalues as $cduRow){
					$cabid=$cduRow['CabinetID'];
					$voltamp=$cduRow['VoltAmp'];
					$rp=$cduRow['RealPower'];
					$bs=$cduRow['BreakerSize'];

					if($bs==1){
						$maxDraw=$voltamp / 1.732;
					}elseif($bs==2){
						$maxDraw=$voltamp;
					}else{
						$maxDraw=$voltamp * 1.732;
					}

					// De-rate all breakers to 80% sustained load
					$maxDraw*=0.8;

					// Only keep the highest percentage of any single CDU in a cabinet
					$pp=intval($rp / $maxDraw * 100);
					$cdus[$cabid]=(isset($cdus[$cabid]) && $cdus[$cabid]>$pp)?$cdus[$cabid]:$pp;
				}

				$sql="SELECT C.*, T.Temp, T.Humidity, P.RealPower, T.LastRead, PLR.RPLastRead 
					FROM ((fac_Cabinet C LEFT JOIN fac_CabinetTemps T ON C.CabinetId = T.CabinetID) LEFT JOIN
						(SELECT CabinetID, SUM(Wattage) RealPower
						FROM fac_PowerDistribution PD LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID
						GROUP BY CabinetID) P ON C.CabinetId = P.CabinetID) LEFT JOIN
						(SELECT CabinetID, MAX(LastRead) RPLastRead
						FROM fac_PowerDistribution PD LEFT JOIN fac_PDUStats PS ON PD.PDUID=PS.PDUID
						GROUP BY CabinetID) PLR ON C.CabinetId = PLR.CabinetID
				    WHERE C.DataCenterID=".intval($this->DataCenterID).";";
				
				$titletemp=0;
				$titlerp=0;
				if($racks=$this->query($sql)){ 
					// read all cabinets and calculate the color to display on the cabinet
					foreach($racks as $cabRow){
						$cab->CabinetID=$cabRow["CabinetID"];

						if ($cabRow["MapX1"]==$cabRow["MapX2"] || $cabRow["MapY1"]==$cabRow["MapY2"]){
							continue;
						}
						$dev->Cabinet=$cab->CabinetID;
	    	    		$devList=$dev->ViewDevicesByCabinet();
						$currentHeight=$cabRow["CabinetHeight"];
	        			$totalWatts=$totalWeight=0;
						$currentTemperature=$cabRow["Temp"];
						$currentHumidity=$cabRow["Humidity"];
						$currentRealPower=$cabRow["RealPower"];
						
						while(list($devID,$device)=each($devList)){
							$totalWatts+=$device->GetDeviceTotalPower();
							$totalWeight+=$device->GetDeviceTotalWeight();
						}
							
	        			$used=$cab->CabinetOccupancy($cab->CabinetID);
						// check to make sure the cabinet height is set to keep errors out of the logs
						if(!isset($cabRow["CabinetHeight"])||$cabRow["CabinetHeight"]==0){$SpacePercent=100;}else{$SpacePercent=number_format($used /$cabRow["CabinetHeight"] *100,0);}
						// check to make sure there is a weight limit set to keep errors out of logs
						if(!isset($cabRow["MaxWeight"])||$cabRow["MaxWeight"]==0){$WeightPercent=0;}else{$WeightPercent=number_format($totalWeight /$cabRow["MaxWeight"] *100,0);}
						// check to make sure there is a kilowatt limit set to keep errors out of logs
	    	    		if(!isset($cabRow["MaxKW"])||$cabRow["MaxKW"]==0){$PowerPercent=0;}else{$PowerPercent=number_format(($totalWatts /1000 ) /$cabRow["MaxKW"] *100,0);}
						if(!isset($cabRow["MaxKW"])||$cabRow["MaxKW"]==0){$RealPowerPercent=0;}else{$RealPowerPercent=number_format(($currentRealPower /1000 ) /$cabRow["MaxKW"] *100,0, ",", ".");}

						// check for individual cdu's being weird
						if(isset($cdus[$cab->CabinetID])){$RealPowerPercent=($RealPowerPercent>$cdus[$cab->CabinetID])?$RealPowerPercent:$cdus[$cab->CabinetID];}
					
						//Decide which color to paint on the canvas depending on the thresholds
						if($SpacePercent>$SpaceRed){$scolor=$CriticalColor;}elseif($SpacePercent>$SpaceYellow){$scolor=$CautionColor;}else{$scolor=$GoodColor;}
						if($WeightPercent>$WeightRed){$wcolor=$CriticalColor;}elseif($WeightPercent>$WeightYellow){$wcolor=$CautionColor;}else{$wcolor=$GoodColor;}
						if($PowerPercent>$PowerRed){$pcolor=$CriticalColor;}elseif($PowerPercent>$PowerYellow){$pcolor=$CautionColor;}else{$pcolor=$GoodColor;}
						if($RealPowerPercent>$RealPowerRed){$rpcolor=$CriticalColor;}elseif($RealPowerPercent>$RealPowerYellow){$rpcolor=$CautionColor;}else{$rpcolor=$GoodColor;}
						
						if($currentTemperature==0){$tcolor=$unknownColor;}
							elseif($currentTemperature>$TemperatureRed){$tcolor=$CriticalColor;}
							elseif($currentTemperature>$TemperatureYellow){$tcolor=$CautionColor;}
							else{$tcolor=$GoodColor;}
						
						if($currentHumidity==0){$hcolor=$unknownColor;}
							elseif($currentHumidity>$HumidityMax || $currentHumidity<$HumidityMin){$hcolor=$CriticalColor;}
							elseif($currentHumidity>$HumidityMedMax || $currentHumidity<$HumidityMedMin) {$hcolor=$CautionColor;}
							else{$hcolor=$GoodColor;}
											
						foreach(array($scolor,$wcolor,$pcolor,$tcolor,$hcolor,$rpcolor) as $cc){
							if($cc=='bad'){
								$color='bad';break;
							}elseif($cc=='med'){
								$color='med';break;
							}else{
								$color='low';
							}
						}
	
						$titletemp=(!is_null($cabRow["LastRead"])&&($cabRow["LastRead"]>$titletemp))?date('%c',strtotime(($cabRow["LastRead"]))):$titletemp;
						$titlerp=(!is_null($cabRow["RPLastRead"])&&($cabRow["RPLastRead"]>$titlerp))?date('%c',strtotime(($cabRow["RPLastRead"]))):$titlerp;

						$overview[$cab->CabinetID]=$color;
						$space[$cab->CabinetID]=$scolor;
						$weight[$cab->CabinetID]=$wcolor;
						$power[$cab->CabinetID]=$pcolor;
						$temperature[$cab->CabinetID]=$tcolor;
						$humidity[$cab->CabinetID]=$hcolor;
						$realpower[$cab->CabinetID]=$rpcolor;
						$airflow[$cab->CabinetID]=$cabRow["FrontEdge"];
					}
				}
			}
			
			//Key
			$overview['title']=__("Composite View of Cabinets");
			$space['title']=__("Occupied Space");
			$weight['title']=__("Calculated Weight");
			$power['title']=__("Calculated Power Usage");
			$temperature['title']=($titletemp>0)?__("Measured on")." ".$titletemp:__("no data");
			$humidity['title']=($titletemp>0)?__("Measured on")." ".$titletemp:__("no data");
			$realpower['title']=($titlerp>0)?__("Measured on")." ".$titlerp:__("no data");
			$airflow['title']=__("Air Flow");

			$statusarray=array('overview' => $overview,
								'space' => $space,
								'weight' => $weight,
								'power' => $power,
								'humidity' => $humidity,
								'temperature' => $temperature,
								'realpower' => $realpower,
								'airflow' => $airflow,
								'colors' => $colors
							);
		}
		return $statusarray;
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

		$sql="SELECT AVG(a.Temp) as AvgTemp FROM fac_CabinetTemps a, fac_Cabinet b
			WHERE a.CabinetID=b.CabinetID AND
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["AvgTemp"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;

		$sql="SELECT AVG(a.Humidity) as AvgHumidity FROM fac_CabinetTemps a, fac_Cabinet b
			WHERE a.CabinetID=b.CabinetID AND
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["AvgHumidity"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;

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
				$tree.=str_repeat(" ",$lev+5)."<li class=\"liClosed\">".
			  		"<a class=\"CABROW\" href=\"rowview.php?row={$filaRow['CabRowID']}\">".__("Row ")."{$filaRow['Fila']}</a>\n";
				$tree.=str_repeat(" ",$lev+6)."<ul>\n";
				// DataCenterID and ZoneID are redundant if fac_cabrow is defined and is CabrowID set in fac_cabinet
				$cabsql="SELECT * FROM fac_Cabinet WHERE DataCenterID=$this->DataCenterID 
					AND ZoneID=$myzone->ZoneID AND CabRowID={$filaRow['CabRowID']} ORDER 
					BY Location REGEXP '^[A-Za-z]+$', CAST(Location as SIGNED INTEGER),
					Location;";
			  
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
		$tree.=str_repeat(" ",$lev+3)."<li id=\"sr-$this->DataCenterID\"><a href=\"storageroom.php?dc=$this->DataCenterID\">".__("Storage Room")."</a></li>\n";
		
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
	var $CustomValues;
	var $GlobalID;
	var $ShareToRepo;
	var $KeepLocal;
    
	function MakeSafe(){
		$validDeviceTypes=array('Server','Appliance','Storage Array','Switch','Chassis','Patch Panel','Physical Infrastructure','CDU','Sensor');

		$this->TemplateID=intval($this->TemplateID);
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Model=sanitize($this->Model);
		$this->Height=intval($this->Height);
		$this->Weight=intval($this->Weight);
		$this->Wattage=intval($this->Wattage);
		$this->DeviceType=(in_array($this->DeviceType, $validDeviceTypes))?$this->DeviceType:'Server';
		$this->PSCount=intval($this->PSCount);
		$this->NumPorts=intval($this->NumPorts);
        $this->Notes=sanitize($this->Notes,false);
        $this->FrontPictureFile=sanitize($this->FrontPictureFile);
	    $this->RearPictureFile=sanitize($this->RearPictureFile);
		$this->ChassisSlots=intval($this->ChassisSlots);
		$this->RearChassisSlots=intval($this->RearChassisSlots);
		$this->GlobalID = intval( $this->GlobalID );
		$this->ShareToRepo = intval( $this->ShareToRepo );
		$this->KeepLocal = intval( $this->KeepLocal );
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
		$Template->GlobalID = $row["GlobalID"];
		$Template->ShareToRepo = $row["ShareToRepo"];
		$Template->KeepLocal = $row["KeepLocal"];
        $Template->MakeDisplay();
		$Template->GetCustomValues();

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
	
	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function clearShareFlag() {
		$st = $this->prepare( "update fac_DeviceTemplate set ShareToRepo=0 where TemplateID=:TemplateID" );
		$st->execute( array( ":TemplateID"=>$this->TemplateID ) );
	}
	
	function CreateTemplate(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_DeviceTemplate SET ManufacturerID=$this->ManufacturerID, 
			Model=\"$this->Model\", Height=$this->Height, Weight=$this->Weight, 
			Wattage=$this->Wattage, DeviceType=\"$this->DeviceType\", 
			PSCount=$this->PSCount, NumPorts=$this->NumPorts, Notes=\"$this->Notes\", 
			FrontPictureFile=\"$this->FrontPictureFile\", RearPictureFile=\"$this->RearPictureFile\",
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots,
			GlobalID=$this->GlobalID, ShareToRepo=$this->ShareToRepo, KeepLocal=$this->KeepLocal;";

		if(!$dbh->exec($sql)){
			error_log( "SQL Error: " . $sql );
			return false;
		}else{
			$this->TemplateID=$dbh->lastInsertId();

			if($this->DeviceType=="CDU"){
				// If this is a cdu make the corresponding other hidden template
				$cdut=new CDUTemplate();
				$cdut->Model=$this->Model;
				$cdut->ManufacturerID=$this->ManufacturerID;
				$cdut->CreateTemplate($this->TemplateID);
			}

			if($this->DeviceType=="Sensor"){
				// If this is a sense make the corresponding other hidden template
				$st=new SensorTemplate();
				$st->Model=$this->Model;
				$st->ManufacturerID=$this->ManufacturerID;
				$st->SNMPVersion = "";
				$st->TemperatureOID = "";
				$st->HumidityOID = "";
				$st->TempMultiplier = "";
				$st->HumidityMultiplier = "";
				$st->mUnits = "";
				$st->CreateTemplate($this->TemplateID);
			}

			(class_exists('LogActions'))?LogActions::LogThis($this):'';
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
			ChassisSlots=$this->ChassisSlots, RearChassisSlots=$this->RearChassisSlots,
			GlobalID=$this->GlobalID, ShareToRepo=$this->ShareToRepo, KeepLocal=$this->KeepLocal
			WHERE TemplateID=$this->TemplateID;";

		$old=new DeviceTemplate();
		$old->TemplateID=$this->TemplateID;
		$old->GetTemplateByID();

		if($old->DeviceType=="CDU" && $this->DeviceType!=$old->DeviceType){
			// Template changed from CDU to something else, clean up the mess
			$cdut=new CDUTemplate();
			$cdut->TemplateID=$this->TemplateID;
			$cdut->DeleteTemplate();
		}elseif($this->DeviceType=="CDU" && $this->DeviceType!=$old->DeviceType){
			// Template changed to CDU from something else, make the extra stuff
			$cdut=new CDUTemplate();
			$cdut->Model=$this->Model;
			$cdut->ManufacturerID=$this->ManufacturerID;
			$cdut->CreateTemplate($this->TemplateID);
		}

		if($old->DeviceType=="Sensor" && $this->DeviceType!=$old->DeviceType){
			// Template changed from CDU to something else, clean up the mess
			$st=new SensorTemplate();
			$st->TemplateID=$this->TemplateID;
			$st->DeleteTemplate();
		}elseif($this->DeviceType=="Sensor" && $this->DeviceType!=$old->DeviceType){
			// Template changed to CDU from something else, make the extra stuff
			$st=new SensorTemplate();
			$st->Model=$this->Model;
			$st->ManufacturerID=$this->ManufacturerID;
			$st->CreateTemplate($this->TemplateID);
		}

		if(!$this->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
			$this->MakeDisplay();
			return true;
		}
	}
  
	function DeleteTemplate(){
		$this->MakeSafe();

		// If we're removing the template clean up the children
		$this->DeleteSlots();
		$this->DeletePorts();

		$sql="DELETE FROM fac_DeviceTemplate WHERE TemplateID=$this->TemplateID;";
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
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
		$this->GlobalID=0;
		$this->ShareToRepo=false;
		$this->KeepLocal=false;
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
	
	function GetTemplateListByManufacturer(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_DeviceTemplate a, fac_Manufacturer b WHERE 
			a.ManufacturerID=b.ManufacturerID AND a.ManufacturerID=$this->ManufacturerID 
			ORDER BY Name ASC, Model ASC;";

		$templateList=array();
		foreach($this->query($sql) as $row){
			$templateList[]=DeviceTemplate::RowToObject($row);
		}

		return $templateList;
	}

	function GetTemplateShareList() {
		$sql = "select * from fac_DeviceTemplate where ManufacturerID in (select ManufacturerID from fac_Manufacturer where GlobalID>0) and ShareToRepo=true order by ManufacturerID ASC";
		
		$templateList = array();
		foreach( $this->query($sql) as $row ) {
			$templateList[]=DeviceTemplate::RowToObject($row);
		}
		
		return $templateList;
	}

    /**
     * Return a list of the templates indexed by the TemplateID
     *
     * @return multitype:DeviceTemplate
     */
    public static function getTemplateListIndexedbyID() {
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
			PowerSupplyCount=$this->PSCount, ChassisSlots=$this->ChassisSlots, 
			RearChassisSlots=$this->RearChassisSlots WHERE TemplateID=$this->TemplateID;";

		return $this->query($sql);
	}
	
	function DeleteSlots(){
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_Slots WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}
	
	function DeletePorts(){
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}

	function DeletePowerPorts(){
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_TemplatePowerPorts WHERE TemplateID=$this->TemplateID";
		if(!$this->query($sql)){
			return false;
		}
		return true;
	}

	// This was a double of the DeletePorts function need to come back later
	// and see if this thing is even being used.	
	function removePorts(){
		return $this->DeletePorts();
	}
	
	function ExportTemplate(){
		$this->MakeSafe();

		//Get manufacturer name
		$manufacturer=new Manufacturer();
		$manufacturer->ManufacturerID=$this->ManufacturerID;
		$manufacturer->GetManufacturerByID();
		
		$fileContent='<?xml version="1.0" encoding="UTF-8"?>
<Template xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:noNamespaceSchemaLocation="openDCIMdevicetemplate.xsd">
	<ManufacturerName>'.$manufacturer->Name.'</ManufacturerName>
	<TemplateReg>
		<Model>'.$this->Model.'</Model> 
	  <Height>'.$this->Height.'</Height> 
	  <Weight>'.$this->Weight.'</Weight> 
	  <Wattage>'.$this->Wattage.'</Wattage> 
	  <DeviceType>'.$this->DeviceType.'</DeviceType> 
	  <PSCount>'.$this->PSCount.'</PSCount> 
	  <NumPorts>'.$this->NumPorts.'</NumPorts> 
	  <Notes>'.$this->Notes.'</Notes> 
	  <FrontPictureFile>'.$this->FrontPictureFile.'</FrontPictureFile> 
	  <RearPictureFile>'.$this->RearPictureFile.'</RearPictureFile> 
	  <ChassisSlots>'.$this->ChassisSlots.'</ChassisSlots> 
	  <RearChassisSlots>'.$this->RearChassisSlots.'</RearChassisSlots> 
	</TemplateReg>';

		//Slots
		for ($i=1; $i<=$this->ChassisSlots;$i++){
			$slot=new Slot();
			$slot->TemplateID=$this->TemplateID;
			$slot->Position=$i;
			$slot->BackSide=False;
			$slot->GetSlot();
			$fileContent.='
	<SlotReg>
		<Position>'.$slot->Position.'</Position>
		<BackSide>0</BackSide>
		<X>'.$slot->X.'</X>
		<Y>'.$slot->Y.'</Y>
		<W>'.$slot->W.'</W>
		<H>'.$slot->H.'</H>
	</SlotReg>';
		}
		for ($i=1; $i<=$this->RearChassisSlots;$i++){
			$slot=new Slot();
			$slot->TemplateID=$this->TemplateID;
			$slot->Position=$i;
			$slot->BackSide=True;
			$slot->GetSlot();
			$fileContent.='
	<SlotReg>
		<Position>'.$slot->Position.'</Position>
		<BackSide>1</BackSide>
		<X>'.$slot->X.'</X>
		<Y>'.$slot->Y.'</Y>
		<W>'.$slot->W.'</W>
		<H>'.$slot->H.'</H>
	</SlotReg>';
		}
		//Ports
		for ($i=1; $i<=$this->NumPorts;$i++){
			$tport=new TemplatePorts();
			$tport->TemplateID=$this->TemplateID;
			$tport->PortNumber=$i;
			$tport->GetPort();
			//Get media name
			$mt=new MediaTypes();
			$mt->MediaID=$tport->MediaID;
			$mt->GetType();
			//Get color name
			$cc=new ColorCoding();
			$cc->ColorID=$tport->ColorID;
			$cc->GetCode();
			$fileContent.='
	<PortReg>
		<PortNumber>'.$tport->PortNumber.'</PortNumber>
		<Label>'.$tport->Label.'</Label>
		<PortMedia>'.$mt->MediaType.'</PortMedia>
		<PortColor>'.$cc->Name.'</PortColor>
		<PortNotes>'.$tport->PortNotes.'</PortNotes>
	</PortReg>';
		}
		//Pictures
		if ($this->FrontPictureFile!="" && file_exists("pictures/".$this->FrontPictureFile)){
			$im=file_get_contents("pictures/".$this->FrontPictureFile);
			$fileContent.='
	<FrontPicture>
'.base64_encode($im).'
	</FrontPicture>';
		}
		if ($this->RearPictureFile!="" && file_exists("pictures/".$this->RearPictureFile)){
			$im=file_get_contents("pictures/".$this->RearPictureFile);
			$fileContent.='
	<RearPicture>
'.base64_encode($im).'
	</RearPicture>';
		}
		
		//End of template
		$fileContent.='
</Template>';
		
		//dounload file
		download_file_from_string($fileContent, str_replace(' ', '', $manufacturer->Name."-".$this->Model).".xml");
		
		return true;
	}

	function ImportTemplate($file){
		$result=array();
		$result["status"]="";
		$result["log"]=array();
		$ierror=0;
		
		//validate xml template file with openDCIMdevicetemplate.xsd
		libxml_use_internal_errors(true);
		$xml=new XMLReader();
		$xml->open($file);
		$resp=$xml->setSchema ("openDCIMdevicetemplate.xsd");
		while (@$xml->read()) {}; // empty loop
		$errors = libxml_get_errors();
		if (count($errors)>0){
			$result["status"]=__("No valid file");
			foreach ($errors as $error) {
				$result["log"][$ierror++]=$error->message;
			}
			return $result;
		}
    libxml_clear_errors();
		$xml->close();
		
		//read xml template file
		$xmltemplate=simplexml_load_file($file);
		
		//manufacturer
		$manufacturer=new Manufacturer();
		$manufacturer->Name=transform($xmltemplate->ManufacturerName);
		if (!$manufacturer->GetManufacturerByName()){
			//New Manufacturer
			$manufacturer->CreateManufacturer();
		}
		$template=new DeviceTemplate();
		$template->ManufacturerID=$manufacturer->ManufacturerID;
		$template->Model=transform($xmltemplate->TemplateReg->Model);
		$template->Height=$xmltemplate->TemplateReg->Height;
		$template->Weight=$xmltemplate->TemplateReg->Weight;
		$template->Wattage=$xmltemplate->TemplateReg->Wattage;
		$template->DeviceType=$xmltemplate->TemplateReg->DeviceType;
		$template->PSCount=$xmltemplate->TemplateReg->PSCount;
		$template->NumPorts=$xmltemplate->TemplateReg->NumPorts;
		$template->Notes=trim($xmltemplate->TemplateReg->Notes);
		$template->Notes=($template->Notes=="<br>")?"":$template->Notes;
		$template->FrontPictureFile=$xmltemplate->TemplateReg->FrontPictureFile;
		$template->RearPictureFile=$xmltemplate->TemplateReg->RearPictureFile;
		$template->ChassisSlots=($template->DeviceType=="Chassis")?$xmltemplate->TemplateReg->ChassisSlots:0;
		$template->RearChassisSlots=($template->DeviceType=="Chassis")?$xmltemplate->TemplateReg->RearChassisSlots:0;
		
		//Check if picture files exist
		if ($template->FrontPictureFile!="" && file_exists("pictures/".$template->FrontPictureFile)){
			$result["status"]=__("Import Error");
			$result["log"][0]= __("Front picture file already exists");
			return $result;
		}
		if ($template->RearPictureFile!="" && file_exists("pictures/".$template->RearPictureFile)){
			$result["status"]=__("Import Error");
			$result["log"][0]= __("Rear picture file already exists");
			return $result;
		}
		
		//create the template
		if (!$template->CreateTemplate()){
			$result["status"]=__("Import Error");
			$result["log"][0]=__("An error has occurred creating the template.<br>Possibly there is already a template of the same manufacturer and model");
			return $result;
		}
		
		//get template to this object
		$this->TemplateID=$template->TemplateID;
		$this->GetTemplateByID();
		
		//slots
		foreach ($xmltemplate->SlotReg as $xmlslot){
			$slot=new Slot();
			$slot->TemplateID=$this->TemplateID;
			$slot->Position=intval($xmlslot->Position);
			$slot->BackSide=intval($xmlslot->BackSide);
			$slot->X=intval($xmlslot->X);
			$slot->Y=intval($xmlslot->Y);
			$slot->W=intval($xmlslot->W);
			$slot->H=intval($xmlslot->H);
			if (($slot->Position<=$this->ChassisSlots && !$slot->BackSide) || ($slot->Position<=$this->RearChassisSlots && $slot->BackSide)){
				if(!$slot->CreateSlot()){
					$result["status"]=__("Import Warning");
					$result["log"][$ierror++]=sprintf(__("An error has occurred creating the slot %s"),$slot->Position."-".($slot->BackSide)?__("Rear"):__("Front"));
				}
			}else{
				$result["status"]=__("Import Warning");
				$result["log"][$ierror++]=sprintf(__("Ignored slot %s"),$slot->Position."-".($slot->BackSide)?__("Rear"):__("Front"));
			}
		}
		
		//ports
		foreach ($xmltemplate->PortReg as $xmlport){
			//media type
			$mt=new MediaTypes();
			$mt->MediaType=transform($xmlport->PortMedia);
			if (!$mt->GetTypeByName()){
				//New media type
				$mt->CreateType();
			}
			
			//color
			$cc=new ColorCoding();
			$cc->Name=transform($xmlport->PortColor);
			if (!$cc->GetCodeByName()){
				//New color
				$cc->CreateCode();
			}
			
			$tport=new TemplatePorts();
			$tport->TemplateID=$this->TemplateID;
			$tport->PortNumber=intval($xmlport->PortNumber);
			$tport->Label=$xmlport->Label;
			$tport->MediaID=$mt->MediaID; 
			$tport->ColorID=$cc->ColorID;
			$tport->PortNotes=$xmlport->PortNotes;
			if ($tport->PortNumber<=$this->NumPorts){
				if(!$tport->CreatePort()){
					$result["status"]=__("Import Warning");
					$result["log"][$ierror++]=sprintf(__("An error has occurred creating the port %s"),$tport->PortNumber);
				}
			}else{
				$result["status"]=__("Import Warning");
				$result["log"][$ierror++]=sprintf(__("Ignored port %s"),$tport->PortNumber);
			}
		}

		//files
		if($this->FrontPictureFile!=""){
			$im=base64_decode($xmltemplate->FrontPicture);
			file_put_contents("pictures/".$this->FrontPictureFile, $im);
		}
		if($this->RearPictureFile!="" && $this->RearPictureFile!=$this->FrontPictureFile){
			$im=base64_decode($xmltemplate->RearPicture);
			file_put_contents("pictures/".$this->RearPictureFile, $im);
		}
		return $result;
	}
	
	function GetCustomValues() {
		$this->MakeSafe();

		$tdca = array();
		$sql = "SELECT TemplateID, AttributeID, Required, Value
			FROM fac_DeviceTemplateCustomValue
			WHERE TemplateID=$this->TemplateID;";
		foreach($this->query($sql) as $tdcrow) {
			$tdca[$tdcrow["AttributeID"]]["value"]=$tdcrow["Value"];
			$tdca[$tdcrow["AttributeID"]]["required"]=$tdcrow["Required"];
		}	
		$this->CustomValues = $tdca;
	}	

	function DeleteCustomValues() {
		$this->MakeSafe();
		
		$sql="DELETE FROM fac_DeviceTemplateCustomValue WHERE TemplateID=$this->TemplateID;";
		if($this->query($sql)){
			$this->GetCustomValues();
			return true;
		}
		return false;
	}

	function InsertCustomValue($AttributeID, $Value, $Required) {
		$this->MakeSafe();
		// make the custom attirubte stuff safe
		$AttributeID=intval($AttributeID);
		$Required=intval($Required);
		$Value=sanitize(trim($Value));

		$sql="INSERT INTO fac_DeviceTemplateCustomValue
			SET TemplateID=$this->TemplateID,
			    AttributeID=$AttributeID,
			    Required=$Required,
			    Value=\"$Value\";";
		if($this->query($sql)) {
			$this->GetCustomValues();
			return true;
		}
		return false;
	}

	static function getAvailableImages(){
		$array=array();
		$path='pictures';
		if(preg_match("/api\//",getcwd())){
			$path="../../$path";
		}
		if(is_dir($path)){
			$dir=scandir($path);
			foreach($dir as $i => $f){
				if(is_file($path.DIRECTORY_SEPARATOR.$f) && ($f!='.' && $f!='..' && $f!='P_ERROR.png')){
					@$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
					if(preg_match('/^image/i', $imageinfo['mime'])){
						$array[]=$f;
					}
				}
			}
		}
		return $array;
	}
}

class Manufacturer {
	var $ManufacturerID;
	var $Name;
	var $GlobalID;
	var $SubscribeToUpdates;

	function MakeSafe(){
		$this->ManufacturerID=intval($this->ManufacturerID);
		$this->Name=sanitize($this->Name);
		$this->GlobalID = intval( $this->GlobalID );
		$this->SubscribeToUpdates = intval( $this->SubscribeToUpdates );
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$m=new Manufacturer();
		$m->ManufacturerID=$row["ManufacturerID"];
		$m->Name=$row["Name"];
		$m->GlobalID = $row["GlobalID"];
		$m->SubscribeToUpdates = $row["SubscribeToUpdates"];
		$m->MakeDisplay();

		return $m;
	}

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
	
	function getManufacturerByGlobalID() {
		$st = $this->prepare( "select * from fac_Manufacturer where GlobalID=:GlobalID" );
		$st->execute( array( ":GlobalID"=>$this->GlobalID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Manufacturer" );
		
		if ( $row = $st->fetch() ) {
			foreach( $row as $prop=>$val ) {
				$this->$prop = $val;
			}
			
			return true;
		} else {
			return false;
		}
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
	
	function GetManufacturerByName(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Manufacturer WHERE ucase(Name)=ucase('".$this->Name."');";

		if($row=$this->query($sql)->fetch()){
			foreach(Manufacturer::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}	
			return true;
		}else{
			return false;
		}
	}
	
	function GetManufacturerList($indexbyid=false){
		$sql="SELECT * FROM fac_Manufacturer ORDER BY Name ASC;";

		$ManufacturerList=array();
		foreach($this->query($sql) as $row){
			if($indexbyid){
				$ManufacturerList[$row['ManufacturerID']]=Manufacturer::RowToObject($row);
			}else{
				$ManufacturerList[]=Manufacturer::RowToObject($row);
			}
		}

		return $ManufacturerList;
	}
	
	function getSubscriptionList() {
		$st = $this->prepare( "select * from fac_Manufacturer where GlobalID>0 and SubscribeToUpdates=true order by GlobalID ASC" );
		$st->execute();
		$st->setFetchMode( PDO::FETCH_CLASS, "Manufacturer" );
		
		$mList = array();
		while ( $row = $st->fetch() ) {
			$mList[] = $row;
		}
		
		return $mList;
	}

	function CreateManufacturer(){
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_Manufacturer SET Name=\"$this->Name\", GlobalID=$this->GlobalID,
		SubscribeToUpdates=$this->SubscribeToUpdates;";

		if(!$dbh->exec($sql)){
			error_log( "SQL Error: " . $sql );
			return false;
		}else{
			$this->ManufacturerID=$dbh->lastInsertID();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			$this->MakeDisplay();
			return true;
		}
	}

	function DeleteManufacturer($TransferTo=null){
		$this->MakeSafe();

		$tmpl=new DeviceTemplate();
		$tmpl->ManufacturerID=$this->ManufacturerID;
		$templates=$tmpl->GetTemplateListByManufacturer();

		// If a TransferTo isn't supplied then just delete the templates that depend on this key
		foreach($templates as $DeviceTemplate){
			// A manufacturerid of 0 is impossible so if we get that via something fuck 'em delete
			if(!is_null($TransferTo) && intval($TransferTo)>0){
				$DeviceTemplate->ManufacturerID=$TransferTo;
				$DeviceTemplate->UpdateTemplate();
			}else{
				// This option is not being provided but us at this time, maybe through the API
				$DeviceTemplate->DeleteTemplate();
			}
		}

		$sql="DELETE FROM fac_Manufacturer WHERE ManufacturerID=$this->ManufacturerID;";

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->query($sql);
	}

	function UpdateManufacturer(){
		$this->MakeSafe();

		$sql="UPDATE fac_Manufacturer SET Name=\"$this->Name\", GlobalID=$this->GlobalID, SubscribeToUpdates=$this->SubscribeToUpdates WHERE ManufacturerID=$this->ManufacturerID;";

		$old=new Manufacturer();
		$old->ManufacturerID=$this->ManufacturerID;
		$old->GetManufacturerByID();

		$this->MakeDisplay();
		(class_exists('LogActions'))?LogActions::LogThis($this,$old):'';
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
		$this->PartNum=sanitize($this->PartNum);
		$this->PartName=sanitize($this->PartName);
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
			PartName=\"$this->PartName\", MinQty=$this->MinQty, MaxQty=$this->MaxQty;";

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
	
	static function GetSupplyCount( $SupplyID ) {
		global $dbh;
		
		$sql = "select sum(Count) as TotalQty from fac_BinContents where SupplyID=" . intval( $SupplyID );
		
		if ( $row=$dbh->query($sql)->fetch()) {
			return $row["TotalQty"];
		} else {
			return 0;
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
			PartName=\"$this->PartName\", MinQty=$this->MinQty, MaxQty=$this->MaxQty WHERE 
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
		$this->Location=sanitize($this->Location);
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
		$this->Description=sanitize($this->Description);
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
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->ZoneID;
		}
	}
	
	function UpdateZone(){
		$this->MakeSafe();

		$oldzone=new Zone();
		$oldzone->ZoneID=$this->ZoneID;
		$oldzone->GetZone();
			
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

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldzone):'';
		return true;
	}
	
	function DeleteZone(){
		global $dbh;
		
		$this->MakeSafe();
		
		//update all cabinets in this zone
		$cabinet=new Cabinet();
		$cabinet->ZoneID=$this->ZoneID;
		$cabinetList=$cabinet->GetCabinetsByZone();
		foreach($cabinetList as $cab){
			$cab->CabRowID=0;
			$cab->ZoneID=0;
			$cab->UpdateCabinet();
		}

		//delete CabRows in this zone
		$cabrow=new CabRow();
		$cabrow->ZoneID=$this->ZoneID;
		$cabrowlist=$cabrow->GetCabRowsByZones();
		foreach($cabrowlist as $cabRow){
			$cabRow->DeleteCabRow();
		}

		//delete zone
		$sql="DELETE FROM fac_Zone WHERE ZoneID=$this->ZoneID;";
		if(!$this->query($sql)){
			return false;
		}
		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	// return array of immediate children
	function GetChildren(){
		$children=array();
		$cabrow=new CabRow();
		$cabrow->ZoneID=$this->ZoneID;
		foreach($cabrow->GetCabRowsByZones() as $row){
			$children[]=$row;
		}
		// While not currently supported this will us to nest cabinets into zones directly without a cabinet row
		$cab=new Cabinet();
		$cab->ZoneID=$this->ZoneID;
		foreach($cab->GetCabinetsByZone() as $cab){
			if($cab->CabRowID==0){
				$children[]=$cab;
			}
		}

		return $children;
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
  
	function GetZonesByDC($limit=false){
		$this->MakeSafe();
		
		$hascoords=($limit)?'AND MapX1!=MapX2 AND MapY1!=MapY2':'';

		$sql="SELECT * FROM fac_Zone WHERE DataCenterID=$this->DataCenterID $hascoords 
			ORDER BY Description;";
		
		$zoneList=array();
		foreach($this->query($sql) as $row){
			$zoneList[]=Zone::RowToObject($row);
		}
		
		return $zoneList;
	}

	static function GetZoneList($indexedbyid=false){
		global $dbh;

		$sql="SELECT * FROM fac_Zone ORDER BY Description ASC;";

		$zoneList=array();
		foreach($dbh->query($sql) as $row){
			if($indexedbyid){
				$zoneList[$row['ZoneID']]=Zone::RowToObject($row);
			}else{
				$zoneList[]=Zone::RowToObject($row);
			}
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
		
		$sql="SELECT AVG(a.Temp) AS AvgTemp FROM fac_CabinetTemps a, fac_Cabinet b WHERE
			a.CabinetID=b.CabinetID AND b.ZoneID=$this->ZoneID;";
		$zoneStats["AvgTemp"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;

		$sql="SELECT AVG(a.Humidity) AS AvgHumitdity FROM fac_CabinetTemps a, fac_Cabinet b WHERE
			a.CabinetID=b.CabinetID AND b.ZoneID=$this->ZoneID;";
		$zoneStats["AvgHumidity"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;

		return $zoneStats;
	}
}

class CabRow {
	var $CabRowID;
	var $Name;
	var $DataCenterID;
	var $ZoneID;

	function MakeSafe() {
		$this->CabRowID=intval($this->CabRowID);
		$this->Name=sanitize($this->Name);
		$this->DataCenterID=intval($this->DataCenterID);
		$this->ZoneID=intval($this->ZoneID);
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
	}

	static function RowToObject($row){
		$cabrow=new CabRow();
		$cabrow->CabRowID=$row["CabRowID"];
		$cabrow->Name=$row["Name"];
		$cabrow->DataCenterID=$row["DataCenterID"];
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

		$sql="INSERT INTO fac_CabRow SET Name=\"$this->Name\", 
			DataCenterID=$this->DataCenterID, ZoneID=$this->ZoneID;";
		if($dbh->exec($sql)){
			$this->CabRowID=$dbh->lastInsertId();
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return $this->CabRowID;
		}else{
			return false;
		}
	}
	
	function UpdateCabRow(){
		$this->MakeSafe();

		$oldcabrow=new CabRow();
		$oldcabrow->CabRowID=$this->CabRowID;
		$oldcabrow->GetCabRow();

		// TODO this here can lead to untracked changes on the cabinets. fix this to use the update method
		//update all cabinets in this cabrow
		$sql="UPDATE fac_Cabinet SET ZoneID=$this->ZoneID, DataCenterID=$this->DataCenterID WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		
		$sql="UPDATE fac_CabRow SET Name=\"$this->Name\", DataCenterID=$this->DataCenterID, ZoneID=$this->ZoneID WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}
		
		(class_exists('LogActions'))?LogActions::LogThis($this,$oldcabrow):'';
		return true;
	}
	
	function DeleteCabRow(){
		global $dbh;
		
		$this->MakeSafe();

		//update cabinets in this row
		$cabinet=new Cabinet();
		$cabinet->CabRowID=$this->CabRowID;
		$cabinetList=$cabinet->GetCabinetsByRow();
		foreach($cabinetList as $cab){
			$cab->CabRowID=0;
			$cab->UpdateCabinet();
		}

		//delete cabrow
		$sql="DELETE FROM fac_CabRow WHERE CabRowID=$this->CabRowID;";
		if(!$this->query($sql)){
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
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

		$sql="SELECT * FROM fac_CabRow WHERE ZoneID=$this->ZoneID ORDER BY Name;";

		$cabrowList=array();
		foreach($this->query($sql) as $row){
			$cabrowList[]=CabRow::RowToObject($row);
		}

		return $cabrowList;
	}

	function GetCabRowsByDC($nozone=false){
		$this->MakeSafe();

		// If true return only rows that don't have a zone set, aka they're just part of the dc
		$sqladdon=($nozone)?"ZoneID=0":"ZoneID>0";

		$sql="SELECT * FROM fac_CabRow WHERE DataCenterID=$this->DataCenterID AND $sqladdon ORDER BY Name;";

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

	function GetCabRowFrontEdge($layout=""){
		//It returns the FrontEdge of most cabinets
		$this->MakeSafe();

		// If we know for sure a row is horizontal or vertical this will further limit
		// the results to valid faces only
		if($layout){
			if($layout=="Horizontal"){
				// top / bottom
				$layout=" AND (FrontEdge='Bottom' OR FrontEdge='Top')";
			}else{
				// right / left
				$layout=" AND (FrontEdge='Right' OR FrontEdge='Left')";
			}
		}

		$sql="SELECT FrontEdge, count(*) as CabCount FROM fac_Cabinet WHERE 
			CabRowID=$this->CabRowID$layout GROUP BY FrontEdge ORDER BY CabCount DESC 
			LIMIT 1;";

		if($cabinetRow=$this->query($sql)->fetch()){
			return $cabinetRow["FrontEdge"];
		}

		return "";
	}
}  //END OF CLASS CabRow

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
		$this->Name=sanitize($this->Name);
		$this->ParentID=intval($this->ParentID);
		$this->DrawingFileName=sanitize($this->DrawingFileName);
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

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return $this->ContainerID;
	}

	function UpdateContainer(){
		$this->MakeSafe();

		$oldcontainer=new Container();
		$oldcontainer->ContainerID=$this->ContainerID;
		$oldcontainer->GetContainer();

		$sql="UPDATE fac_Container SET Name=\"$this->Name\", ParentID=$this->ParentID, 
			DrawingFileName=\"$this->DrawingFileName\", MapX=$this->MapX, MapY=$this->MapY 
			WHERE ContainerID=$this->ContainerID;";
		
		if(!$this->query($sql)){
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this,$oldcontainer):'';
			return true;
		}
	}

	function DeleteContainer(){
		global $dbh;
		$this->MakeSafe();
		
		$ChildContainerList=$this->GetChildContainerList();
		foreach($ChildContainerList as $cRow){
			$cRow->ParentID=0;
			$cRow->UpdateContainer();
		}
		$ChildDCList=$this->GetChildDCList();
		foreach($ChildDCList as $dcRow){
			$dcRow->ContainerID=0;
			$dcRow->UpdateDataCenter();
		}

		// Now delete the container itself
		$sql="DELETE FROM fac_Container WHERE ContainerID=$this->ContainerID;";

		if(!$dbh->exec($sql)){
			$info=$dbh->errorInfo();

			error_log("PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return;
	}
	
	function GetContainer(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Container WHERE ContainerID=$this->ContainerID 
			ORDER BY LENGTH(Name), Name ASC;";

		if($row=$this->query($sql)->fetch()){
			foreach(Container::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}else{
			return false;
		}
	}

	function GetChildren(){
		$children=array();
		foreach($this->GetChildContainerList() as $con){
			$children[]=$con;
		}
		foreach($this->GetChildDCList() as $dc){
			$children[]=$dc;
		}

		return $children;
	}

	function GetChildContainerList(){
		$this->MakeSafe();

		$sql="SELECT * FROM fac_Container WHERE ParentID=$this->ContainerID 
			ORDER BY LENGTH(Name), Name ASC;";

		$containerList=array();
		foreach($this->query($sql) as $row){
			$containerList[]=Container::RowToObject($row);
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
		$tree.="<li id=\"dc-1\"><a href=\"storageroom.php\">".__("General Storage Room")."</a></li>\n";
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
						$mapHTML.="<a title=\"$container->Name\" href=\"container_stats.php?container=$container->ContainerID\">";
						$mapHTML.="<br><div style='background-color: #dcdcdc;'>$container->Name</div></a>";
						$mapHTML.= "</div>\n";
						}
					else {
						$mapHTML.="<div style='position:absolute; top:".($container->MapY-$tam/2)."px; left:".($container->MapX-$tam/2)."px;'>\n";
						$mapHTML.="<a title=\"$container->Name\" href=\"container_stats.php?container=$container->ContainerID\">";
						$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
						$mapHTML.="<div style='position:absolute; top:".($container->MapY+$tam/2)."px; left:".($container->MapX-$tam/2)."px; background-color: #dcdcdc;'>";
						$mapHTML.="<a title=\"$container->Name\" href=\"container_stats.php?container=$container->ContainerID\">";
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
						&& $tipo=="container" && $id==$container->ContainerID ){
							$mapHTML.="<div id='yo' hidden style='position:absolute;'>\n";
							$mapHTML.="<img src=\"images/Container.png\" width=$tam height=$tam alt=\"Container\">\n</div>\n";
							$yo_ok=true;
						}
					else {
						
						if ($tipo=="container" && $id==$container->ContainerID) {
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
	
	static function GetContainerList(){
		global $dbh;

		$sql="SELECT * FROM fac_Container ORDER BY LENGTH(Name), Name ASC;";

		$containerList=array();
		foreach($dbh->query($sql) as $row){
			$containerList[]=Container::RowToObject($row);
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
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}
	}
	
	function UpdateSlot(){
		$this->MakeSafe();

		$oldslot=new Slot();
		$oldslot->TemplateID=$this->TemplateID;
		$oldslot->Position=$this->Position;
		$oldslot->BackSide=$this->BackSide;
		$oldslot->GetSlot();
			
		$sql="UPDATE fac_Slots SET X=$this->X, Y=$this->Y, W=$this->W, H=$this->H 
			WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND 
			BackSide=$this->BackSide;";

		if(!$this->query($sql)){
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldslot):'';
		return true;
	}
	
	function DeleteSlot(){
		$this->MakeSafe();
		
		//delete slot
		$sql="DELETE FROM fac_Slots WHERE TemplateID=$this->TemplateID AND Position=$this->Position AND BackSide=$this->BackSide;";
		if(!$this->query($sql)){
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
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
	
	static function getSlots( $TemplateID ) {
		global $dbh;
		
		$st = $dbh->prepare( "select * from fac_Slots where TemplateID=:TemplateID order by BackSide ASC, Position ASC" );
		$st->execute( array( ":TemplateID"=>$TemplateID ) );
		$st->setFetchMode( PDO::FETCH_CLASS, "Slot" );
		$sList = array();
		while ( $row = $st->fetch() ) {
			$sList[] = $row;
		}
		
		return $sList;
	}

	// Return all the slots for a single template in one object
	static function GetAll($templateid){
		global $dbh;
		
		$sql="SELECT * FROM fac_Slots WHERE TemplateID=".intval($templateid)." ORDER 
			BY BackSide ASC, Position ASC;";
		$slots=array();
		foreach($dbh->query($sql) as $row){
			$slots[$row['BackSide']][$row['Position']]=Slot::RowToObject($row);
		}	
		return $slots;
	}

	function GetFirstSlot(){
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

//Class TemplatePorts (Features of ports for a device template)
class TemplatePorts {
	var $TemplateID;
	var $PortNumber;
	var $Label;
	var $MediaID;
	var $ColorID;
	var $PortNotes;
	
	function MakeSafe() {
		$this->TemplateID=intval($this->TemplateID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=sanitize($this->Label);
		$this->MediaID=intval($this->MediaID);
		$this->ColorID=intval($this->ColorID);
		$this->PortNotes=sanitize($this->PortNotes);
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim($this->Label));
		$this->PortNotes=stripslashes(trim($this->PortNotes));
	}

	static function RowToObject($dbRow){
		$tp=new TemplatePorts();
		$tp->TemplateID=$dbRow['TemplateID'];
		$tp->PortNumber=$dbRow['PortNumber'];
		$tp->Label=$dbRow['Label'];
		$tp->MediaID=$dbRow['MediaID'];
		$tp->ColorID=$dbRow['ColorID'];
		$tp->PortNotes=$dbRow['PortNotes'];

		$tp->MakeDisplay();

		return $tp;
	}

	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function flushPorts( $templateid ) {
		$st = $this->prepare( "delete from fac_TemplatePorts where TemplateID=:TemplateID" );
		return $st->execute( array( ":TemplateID"=>$templateid ) );
	}
	
	function getPort(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(TemplatePorts::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID ORDER BY PortNumber ASC;";

		$ports=array();
		foreach($dbh->query($sql) as $row){
			$ports[$row['PortNumber']]=TemplatePorts::RowToObject($row);
		}	
		return $ports;
	}
	
	function createPort() {
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_TemplatePorts SET TemplateID=$this->TemplateID, PortNumber=$this->PortNumber, 
			Label=\"$this->Label\", MediaID=$this->MediaID, ColorID=$this->ColorID, 
			PortNotes=\"$this->PortNotes\";";
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function updatePort() {
		global $dbh;

		$this->MakeSafe();

		$oldport=new TemplatePort();
		$oldport->TemplateID=$this->TemplateID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();

		// update port
		$sql="UPDATE fac_TemplatePorts SET Label=\"$this->Label\", MediaID=$this->MediaID, 
			ColorID=$this->ColorID,	PortNotes=\"$this->PortNotes\", 
			WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
		return true;
	}

	function removePort(){
		/*	Remove a single port from a template */
		global $dbh;

		if(!$this->getport()){
			return false;
		}

		$sql="DELETE FROM fac_TemplatePorts WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			//delete failed, wtf
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}		
	} //END of Class TemplatePorts
	
}

class TemplatePowerPorts {
	var $TemplateID;
	var $PortNumber;
	var $Label;
	var $PortNotes;
	
	function MakeSafe() {
		$this->TemplateID=intval($this->TemplateID);
		$this->PortNumber=intval($this->PortNumber);
		$this->Label=sanitize($this->Label);
		$this->PortNotes=sanitize($this->PortNotes);
	}

	function MakeDisplay(){
		$this->Label=stripslashes(trim($this->Label));
		$this->PortNotes=stripslashes(trim($this->PortNotes));
	}

	static function RowToObject($dbRow){
		$tp=new TemplatePorts();
		$tp->TemplateID=$dbRow['TemplateID'];
		$tp->PortNumber=$dbRow['PortNumber'];
		$tp->Label=$dbRow['Label'];
		$tp->PortNotes=$dbRow['PortNotes'];

		$tp->MakeDisplay();

		return $tp;
	}
	
	function prepare( $sql ) {
		global $dbh;
		return $dbh->prepare( $sql );
	}
	
	function flushPorts( $templateid ) {
		$st = $this->prepare( "delete from fac_TemplatePowerPorts where TemplateID=:TemplateID" );
		return $st->execute( array( ":TemplateID"=>$templateid ) );
	}

	function getPort(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_TemplatePowerPorts WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$row=$dbh->query($sql)->fetch()){
			return false;
		}else{
			foreach(TemplatePowerPorts::RowToObject($row) as $prop => $value){
				$this->$prop=$value;
			}
			return true;
		}
	}

	function getPorts(){
		global $dbh;
		$this->MakeSafe();

		$sql="SELECT * FROM fac_TemplatePowerPorts WHERE TemplateID=$this->TemplateID ORDER BY PortNumber ASC;";

		$ports=array();
		foreach($dbh->query($sql) as $row){
			$ports[$row['PortNumber']]=TemplatePowerPorts::RowToObject($row);
		}	
		return $ports;
	}
	
	function createPort() {
		global $dbh;
		
		$this->MakeSafe();

		$sql="INSERT INTO fac_TemplatePowerPorts SET TemplateID=$this->TemplateID, 
			PortNumber=$this->PortNumber, Label=\"$this->Label\", 
			PortNotes=\"$this->PortNotes\";";
			
		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("createPort::PDO Error: {$info[2]} SQL=$sql");
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this):'';
		return true;
	}

	function updatePort() {
		global $dbh;

		$this->MakeSafe();

		$oldport=new TemplatePort();
		$oldport->TemplateID=$this->TemplateID;
		$oldport->PortNumber=$this->PortNumber;
		$oldport->getPort();

		// update port
		$sql="UPDATE fac_TemplatePowerPorts SET Label=\"$this->Label\", 
			PortNotes=\"$this->PortNotes\", WHERE TemplateID=$this->TemplateID AND 
			PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			$info=$dbh->errorInfo();

			error_log("updatePort::PDO Error: {$info[2]} SQL=$sql");
			
			return false;
		}

		(class_exists('LogActions'))?LogActions::LogThis($this,$oldport):'';
		return true;
	}

	function removePort(){
		/*	Remove a single port from a template */
		global $dbh;

		if(!$this->getport()){
			return false;
		}

		$sql="DELETE FROM fac_TemplatePowerPorts WHERE TemplateID=$this->TemplateID AND PortNumber=$this->PortNumber;";

		if(!$dbh->query($sql)){
			//delete failed, wtf
			return false;
		}else{
			(class_exists('LogActions'))?LogActions::LogThis($this):'';
			return true;
		}		
	}
}
?>
