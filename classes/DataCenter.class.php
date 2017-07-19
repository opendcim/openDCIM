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
	var $U1Position;

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
		$this->U1Position=in_array($this->U1Position, array("Top","Bottom","Default"))?$this->U1Position:"Default";
	}

	function MakeDisplay(){
		$this->Name=stripslashes($this->Name);
		$this->DeliveryAddress=stripslashes($this->DeliveryAddress);
		$this->Administrator=stripslashes($this->Administrator);
		$this->DrawingFileName=stripslashes($this->DrawingFileName);
	}

	public function __construct($dcid=false){
		if($dcid){
			$this->DataCenterID=intval($dcid);
		}
		return $this;
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
		$dc->U1Position=$row["U1Position"];
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

	function Search($indexedbyid=false,$loose=false){
		$o=new stdClass();
		// Store any values that have been added before we make them safe 
		foreach($this as $prop => $val){
			if(isset($val)){
				$o->$prop=$val;
			}
		}

		// Make everything safe for us to search with
		$this->MakeSafe();

		// This will store all our extended sql
		$sqlextend="";
		foreach($this as $prop => $val){
			if($val){
				extendsql($prop,$val,$sqlextend,$loose);
			}
		}

		$sql="SELECT * FROM fac_DataCenter $sqlextend ORDER BY Name ASC;";

		$dcList=array();

		foreach($this->query($sql) as $row){
			if($indexedbyid){
				$dcList[$row["DataCenterID"]]=DataCenter::RowToObject($row);
			}else{
				$dcList[]=DataCenter::RowToObject($row);
			}
		}

		return $dcList;
	}

	// Make a simple reference to a loose search
	function LooseSearch($indexedbyid=false){
		return $this->Search($indexedbyid,true);
	}

	function CreateDataCenter(){
		global $dbh;
		$this->MakeSafe();
		
		$sql="INSERT INTO fac_DataCenter SET Name=\"$this->Name\", 
			SquareFootage=$this->SquareFootage, DeliveryAddress=\"$this->DeliveryAddress\", 
			Administrator=\"$this->Administrator\", MaxkW=$this->MaxkW, 
			DrawingFileName=\"$this->DrawingFileName\", EntryLogging=0,	
			ContainerID=$this->ContainerID,	MapX=$this->MapX, MapY=$this->MapY, 
			U1Position=\"$this->U1Position\";";

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
			ContainerID=$this->ContainerID,	MapX=$this->MapX, MapY=$this->MapY, 
			U1Position=\"$this->U1Position\" WHERE DataCenterID=$this->DataCenterID;";

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
				if(mime_content_type($mapfile)=='image/svg+xml'){
					$svgfile = simplexml_load_file($mapfile);
					$width = substr($svgfile['width'],0,4);
					$height = substr($svgfile['height'],0,4);
				}else{
					list($width, $height, $type, $attr)=getimagesize($mapfile);
				}
				$cdus=array();
					
				$sql = "select c.CabinetID, P.RealPower, P.BreakerSize, P.InputAmperage*PP.PanelVoltage as VoltAmp from 
					(fac_Cabinet c left join (select CabinetID, Wattage as RealPower, BreakerSize, InputAmperage, PanelID from fac_PowerDistribution PD 
					left join fac_PDUStats PS on PD.PDUID=PS.PDUID) P on c.CabinetID=P.CabinetID) 
					left join (select PanelVoltage, PanelID from fac_PowerPanel) PP on PP.PanelID=P.PanelID 
					where PanelVoltage is not null and RealPower is not null and c.DataCenterID=".intval($this->DataCenterID);

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
					if ( $maxDraw > 0 ) {
						$pp=intval($rp / $maxDraw * 100);
					} else {
						$pp = 0;
					}
					$cdus[$cabid]=(isset($cdus[$cabid]) && $cdus[$cabid]>$pp)?$cdus[$cabid]:$pp;
				}
				$cab->DataCenterID = $this->DataCenterID;
				$cabList = $cab->ListCabinetsByDC();
				
				$titletemp=0;
				$titlerp=0;
				// read all cabinets and calculate the color to display on the cabinet
				foreach($cabList as $cabRow){
					if ($cabRow->MapX1==$cabRow->MapX2 || $cabRow->MapY1==$cabRow->MapY2){
						continue;
					}
					$currentHeight=$cabRow->CabinetHeight;
					
					$metrics = CabinetMetrics::getMetrics( $cabRow->CabinetID );
					
					$currentTemperature=$metrics->IntakeTemperature;
					$currentHumidity=$metrics->IntakeHumidity;
					$currentRealPower=$metrics->MeasuredPower;

					$used=$metrics->SpaceUsed;
					// check to make sure the cabinet height is set to keep errors out of the logs
					if(!isset($cabRow->CabinetHeight)||$cabRow->CabinetHeight==0){$SpacePercent=100;}else{$SpacePercent=number_format($metrics->SpaceUsed /$cabRow->CabinetHeight *100,0);}
					// check to make sure there is a weight limit set to keep errors out of logs
					if(!isset($cabRow->MaxWeight)||$cabRow->MaxWeight==0){$WeightPercent=0;}else{$WeightPercent=number_format($metrics->CalculatedWeight /$cabRow->MaxWeight *100,0);}
					// check to make sure there is a kilowatt limit set to keep errors out of logs
					if(!isset($cabRow->MaxKW)||$cabRow->MaxKW==0){$PowerPercent=0;}else{$PowerPercent=number_format(($metrics->CalculatedPower /1000 ) /$cabRow->MaxKW *100,0);}
					if(!isset($cabRow->MaxKW)||$cabRow->MaxKW==0){$RealPowerPercent=0;}else{$RealPowerPercent=number_format(($metrics->MeasuredPower /1000 ) /$cabRow->MaxKW *100,0, ",", ".");}

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
					
					$overview[$cabRow->CabinetID]=$color;
					$space[$cabRow->CabinetID]=$scolor;
					$weight[$cabRow->CabinetID]=$wcolor;
					$power[$cabRow->CabinetID]=$pcolor;
					$temperature[$cabRow->CabinetID]=$tcolor;
					$humidity[$cabRow->CabinetID]=$hcolor;
					$realpower[$cabRow->CabinetID]=$rpcolor;
					$airflow[$cabRow->CabinetID]=$cabRow->FrontEdge;
				}
			}
			
			$tempSQL = "select max(LastRead) as ReadingTime from fac_SensorReadings where DeviceID in (select DeviceID from fac_Device where DeviceType='Sensor' and Cabinet in (select CabinetID from fac_Cabinet where DataCenterID=" . $this->DataCenterID . "))";
			$tempRes = $this->query( $tempSQL );
			$tempRow = $tempRes->fetch();
			
			$pwrSQL = "select max(LastRead) as ReadingTime from fac_PDUStats where PDUID in (select DeviceID from fac_Device where DeviceType='CDU' and Cabinet in (select CabinetID from fac_Cabinet where DataCenterID=" . $this->DataCenterID . "))";
			$pwrRes = $this->query( $pwrSQL );
			$pwrRow = $pwrRes->fetch();
			
			//Key
			$overview['title']=__("Composite View of Cabinets");
			$space['title']=__("Occupied Space");
			$weight['title']=__("Calculated Weight");
			$power['title']=__("Calculated Power Usage");
			$temperature['title']=($tempRow["ReadingTime"]>0)?__("Measured on")." ".date( 'c', strtotime( $tempRow["ReadingTime"])):__("no data");
			$humidity['title']=($tempRow["ReadingTime"]>0)?__("Measured on")." ".date( 'c', strtotime( $tempRow["ReadingTime"])):__("no data");
			$realpower['title']=($pwrRow["ReadingTime"]>0)?__("Measured on")." ".date( 'c', strtotime( $pwrRow["ReadingTime"])):__("no data");
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

		// Count the U used up by devices that are (a) not chassis cards; (b) in a cabinet in the data center; (c) not a server or array; and (d) not in the Storage Room (Cabinet=-1)
		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND ParentDevice=0 AND
			a.DeviceType NOT IN ('Server','Storage Array') and a.Cabinet>0;";
		$dcStats["Infrastructure"]=($test=$this->query($sql)->fetchColumn())?$test:0;
 
		$sql="SELECT SUM(a.Height) as TotalU FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND b.DataCenterID=$this->DataCenterID AND ParentDevice=0 AND
			a.Status not in ('Reserved', 'Salvage') AND a.DeviceType IN ('Server', 'Storage Array') and a.Cabinet>0;";
		$dcStats["Occupied"]=($test=$this->query($sql)->fetchColumn())?$test:0;

		// There should never be a case where a device marked as reserved ends up in the Storage Room, but S.U.T. #44
		$sql="SELECT SUM(a.Height) FROM fac_Device a,fac_Cabinet b WHERE ParentDevice=0 AND
			a.Cabinet=b.CabinetID AND a.Status='Reserved' AND b.DataCenterID=$this->DataCenterID and a.Cabinet>0;";
		$dcStats["Allocated"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
        $dcStats["Available"]=$dcStats["TotalU"] - $dcStats["Occupied"] - $dcStats["Infrastructure"] - $dcStats["Allocated"];

		// Perform two queries - one is for the wattage overrides (where NominalWatts > 0) and one for the template (default) values
		$sql="SELECT SUM(NominalWatts) as TotalWatts FROM fac_Device a,fac_Cabinet b WHERE 
			a.Cabinet=b.CabinetID AND a.NominalWatts>0 AND a.Cabinet>0 AND
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["ComputedWatts"]=($test=$this->query($sql)->fetchColumn())?$test:0;
		
		$sql="SELECT SUM(c.Wattage) as TotalWatts FROM fac_Device a, fac_Cabinet b, 
			fac_DeviceTemplate c WHERE a.Cabinet=b.CabinetID AND a.Cabinet>0 AND
			a.TemplateID=c.TemplateID AND a.NominalWatts=0 AND 
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["ComputedWatts"]+=($test=$this->query($sql)->fetchColumn())?$test:0;

		$sql="SELECT AVG(NULLIF(a.Temperature, 0)) as AvgTemp FROM fac_SensorReadings a, fac_Cabinet b, fac_Device c
			WHERE a.DeviceID=c.DeviceID and c.Cabinet=b.CabinetID AND c.BackSide=0 AND
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["AvgTemp"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;
		
		$sql="SELECT AVG(NULLIF(a.Humidity, 0)) as AvgHumidity FROM fac_SensorReadings a, fac_Cabinet b, fac_Device c
			WHERE a.DeviceID=c.DeviceID and c.BackSide=0 and c.Cabinet=b.CabinetID AND
			b.DataCenterID=$this->DataCenterID;";
		$dcStats["AvgHumidity"]=($test=round($this->query($sql)->fetchColumn()))?$test:0;
		
		$pdu=new PowerDistribution();
		$dcStats["MeasuredWatts"]=$pdu->GetWattageByDC($this->DataCenterID);

		$sql = "select count(*) from fac_Cabinet where DataCenterID=" . intval($this->DataCenterID);
		$dcStats["TotalCabinets"] = ($test=$this->query($sql)->fetchColumn())?$test:0;
		
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
?>
