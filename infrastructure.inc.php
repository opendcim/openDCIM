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

	function MakeSafe() {
		$this->DataCenterID = intval( $this->DataCenterID );
		$this->Name = mysql_real_escape_string( $this->Name );
		$this->SquareFootage = intval( $this->SquareFootage );
		$this->DeliveryAddress = mysql_real_escape_string( $this->DeliveryAddress );
		$this->Administrator = mysql_real_escape_string( $this->Administrator );
		$this->MaxkW = intval( $this->MaxkW );
		$this->DrawingFileName = mysql_real_escape_string( $this->DrawingFileName );
		$this->EntryLogging = intval( $this->EntryLogging );
	}
	
	function CreateDataCenter( $db ) {
		$this->MakeSafe();
		
		$sql = sprintf( "insert into fac_DataCenter set Name=\"%s\", SquareFootage=%d, DeliveryAddress=\"%s\", Administrator=\"%s\", MaxkW=%d, DrawingFileName=\"%s\", EntryLogging=0",
			$this->Name, $this->SquareFootage, $this->DeliveryAddress, $this->Administrator, $this->MaxkW, $this->DrawingFileName );

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		return;
	}

	function UpdateDataCenter( $db ) {
		$this->MakeSafe();
		$sql = sprintf( "update fac_DataCenter set Name=\"%s\", SquareFootage=%d, DeliveryAddress=\"%s\", Administrator=\"%s\", MaxkW=%d, DrawingFileName=\"%s\", EntryLogging=0 where DataCenterID=%d",
			$this->Name, $this->SquareFootage, $this->DeliveryAddress, $this->Administrator, $this->MaxkW, $this->DrawingFileName, $this->DataCenterID );
		
		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		return;
	}

	function GetDataCenter( $db ) {
		$this->MakeSafe();
		$sql = sprintf( "select * from fac_DataCenter where DataCenterID=%d", $this->DataCenterID );

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$this->Name = $row["Name"];
			$this->SquareFootage = $row["SquareFootage"];
			$this->DeliveryAddress = $row["DeliveryAddress"];
			$this->Administrator = $row["Administrator"];
			$this->MaxkW = $row["MaxkW"];
			$this->DrawingFileName = $row["DrawingFileName"];
			$this->EntryLogging = $row["EntryLogging"];
		}
		
		return;
	}
		
	function GetDCList( $db ) {
		$sql = "select * from fac_DataCenter order by Name ASC";

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		$datacenterList = array();

		while ( $dcRow = mysql_fetch_array( $result ) ) {
			$dcID = $dcRow[ "DataCenterID" ];

			$datacenterList[$dcID] = new DataCenter();
			$datacenterList[$dcID]->DataCenterID = $dcRow["DataCenterID"];
			$datacenterList[$dcID]->Name = $dcRow["Name"];
			$datacenterList[$dcID]->SquareFootage = $dcRow["SquareFootage"];
			$datacenterList[$dcID]->DeliveryAddress = $dcRow["DeliveryAddress"];
			$datacenterList[$dcID]->Administrator = $dcRow["Administrator"];
			$datacenterList[$dcID]->MaxkW = $dcRow["MaxkW"];
			$datacenterList[$dcID]->DrawingFileName = $dcRow["DrawingFileName"];
			$datacenterList[$dcID]->EntryLogging = $dcRow["EntryLogging"];
		}

		return $datacenterList;
	}

	function GetDataCenterbyID( $db ) {
		$this->MakeSafe();
		$sql = sprintf( "select * from fac_DataCenter where DataCenterID=%d", $this->DataCenterID );
		
		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		if ( $dcRow = mysql_fetch_array( $result ) ) {
			$this->Name = $dcRow["Name"];
			$this->SquareFootage = $dcRow["SquareFootage"];
			$this->DeliveryAddress = $dcRow["DeliveryAddress"];
			$this->Administrator = $dcRow["Administrator"];
			$this->MaxkW = $dcRow["MaxkW"];
			$this->DrawingFileName = $dcRow["DrawingFileName"];
			$this->EntryLogging = $dcRow["EntryLogging"];
		}

		return;
	}
	
	function MakeImageMap( $db ) {
	 $mapHTML = "";
	 
	 if ( strlen($this->DrawingFileName) > 0 ) {
	   $mapfile = "drawings/" . $this->DrawingFileName;
	   
	   if ( file_exists( $mapfile ) ) {
	     list($width, $height, $type, $attr)=getimagesize($mapfile);
	     $mapHTML.="<div class=\"canvas\">\n";
		 $mapHTML.="<img src=\"css/blank.gif\" usemap=\"#datacenter\" width=\"$width\" height=\"$height\" alt=\"clearmap over canvas\">\n";
	     $mapHTML.="<map name=\"datacenter\">\n";
	     
	     $selectSQL="select * from fac_Cabinet where DataCenterID=\"" . intval($this->DataCenterID) . "\"";
		 $result = mysql_query( $selectSQL, $db );
	     
	     while ( $cabRow = mysql_fetch_array( $result ) ) {
	       $mapHTML.="<area href=\"cabnavigator.php?cabinetid=" . $cabRow["CabinetID"] . "\" shape=\"rect\" coords=\"" . $cabRow["MapX1"] . ", " . $cabRow["MapY1"] . ", " . $cabRow["MapX2"] . ", " . $cabRow["MapY2"] . "\" alt=\"".$cabRow["Location"]."\" title=\"".$cabRow["Location"]."\">\n";
	     }
	     
	     $mapHTML.="</map>\n";
	     $mapHTML.="<canvas id=\"mapCanvas\" width=\"$width\" height=\"$height\"></canvas>\n";
             
	     
	     $mapHTML .= "</div>\n";
	    }
	 }
	 return $mapHTML;
	}

	function MakeImageMapNoLinks( $db ) {
	 $mapHTML = "";
	 
	 if ( strlen($this->DrawingFileName) > 0 ) {
	   $mapfile = "drawings/" . $this->DrawingFileName;
	   
	   if ( file_exists( $mapfile ) ) {
	     list($width, $height, $type, $attr)=getimagesize($mapfile);
	     $mapHTML.="<div class=\"canvas\">\n";
		 $mapHTML.="<img src=\"css/blank.gif\" usemap=\"#datacenter\" width=\"$width\" height=\"$height\" alt=\"clearmap over canvas\">\n";
	     $mapHTML.="<map name=\"datacenter\">\n";
	     
	     $selectSQL="select * from fac_Cabinet where DataCenterID=\"" . intval($this->DataCenterID) . "\"";
		 $result = mysql_query( $selectSQL, $db );
	     
	     while ( $cabRow = mysql_fetch_array( $result ) ) {
	       $mapHTML.="<area href=\"#\" shape=\"rect\" coords=\"" . $cabRow["MapX1"] . ", " . $cabRow["MapY1"] . ", " . $cabRow["MapX2"] . ", " . $cabRow["MapY2"] . "\" alt=\"".$cabRow["Location"]."\" title=\"".$cabRow["Location"]."\">\n";
	     }
	     
	     $mapHTML.="</map>\n";
	     $mapHTML.="<canvas id=\"mapCanvas\" width=\"$width\" height=\"$height\"></canvas>\n";
             
	     
	     $mapHTML .= "</div>\n";
	    }
	 }
	 return $mapHTML;
	}

	function DrawCanvas($db){
		$script="";	
		// check to see if map was set
		if(strlen($this->DrawingFileName)){
			$mapfile = "drawings/" . $this->DrawingFileName;

			// map was set in config check to ensure a file exists before we attempt to use it
			if(file_exists($mapfile)){
				$this->dcconfig=new Config($db);
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
				$selectSQL="select * from fac_Cabinet where DataCenterID=\"".intval($this->DataCenterID)."\"";
				$result=mysql_query($selectSQL,$db);
				
				// read all cabinets and draw image map
				while($cabRow=mysql_fetch_array($result)){
					$cab->CabinetID=$cabRow["CabinetID"];
					$cab->GetCabinet($db);
					$dev->Cabinet=$cab->CabinetID;
					$dev->Location=$cab->Location;
    	    		$devList=$dev->ViewDevicesByCabinet( $db );
					$currentHeight = $cab->CabinetHeight;
        			$totalWatts = $totalWeight = $totalMoment =0;
        							
					while(list($devID,$device)=each($devList)){
        	        	$templ->TemplateID=$device->TemplateID;
            	    	$templ->GetTemplateByID($db);

						if($device->NominalWatts >0){
							$totalWatts += $device->NominalWatts;
						}elseif($device->TemplateID!=0 && $templ->Wattage>0){
							$totalWatts += $templ->Wattage;
						}
						if($device->DeviceType=="Chassis"){
							$childList=$device->GetDeviceChildren($db);
							$childTempl=new DeviceTemplate();
							foreach($childList as $childDev){
								$childTempl->TemplateID=$childDev->TemplateID;
								$childTempl->GetTemplateByID($db);
								
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

        			$used=$cab->CabinetOccupancy($cab->CabinetID,$db);
        			$SpacePercent=number_format($used /$cab->CabinetHeight *100,0);
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



	function GetDCStatistics( $db ) {
		$this->GetDataCenterbyID( $db );

		$dcStats["TotalU"] = 0;
		$dcStats["Infrastructure"] = 0;
		$dcStats["Occupied"] = 0;
		$dcStats["Allocated"] = 0;
		$dcStats["Available"] = 0;
		$dcStats["TotalWatts"] = 0;

		$selectSQL = "select sum(CabinetHeight) from fac_Cabinet where DataCenterID=\"" . intval($this->DataCenterID) . "\"";

		$result = mysql_query( $selectSQL, $db );

		$statsRow = mysql_fetch_array( $result );
		$dcStats["TotalU"] = $statsRow[0];

		$selectSQL = "select sum(a.Height) from fac_Device a,fac_Cabinet b where a.Cabinet=b.CabinetID and b.DataCenterID=\"" . intval($this->DataCenterID) . "\" and a.DeviceType not in ('Server','Storage Array')";

        $result = mysql_query( $selectSQL, $db );

        $statsRow = mysql_fetch_array( $result );
        $dcStats["Infrastructure"] = $statsRow[0];
 
		$selectSQL = "select sum(a.Height) from fac_Device a,fac_Cabinet b where a.Cabinet=b.CabinetID and b.DataCenterID=\"" . intval($this->DataCenterID) . "\" and a.Reservation=false and a.DeviceType in ('Server', 'Storage Array')";
 
		$result = mysql_query( $selectSQL, $db );

		$statsRow = mysql_fetch_array( $result );
		$dcStats["Occupied"] = $statsRow[0];

        $selectSQL = "select sum(a.Height) from fac_Device a,fac_Cabinet b where a.Cabinet=b.CabinetID and b.DataCenterID=\"" . intval($this->DataCenterID) . "\" and a.Reservation=true";

        $result = mysql_query( $selectSQL, $db );

        $statsRow = mysql_fetch_array( $result );
        $dcStats["Allocated"] = $statsRow[0];

        $dcStats["Available"] = $dcStats["TotalU"] - $dcStats["Occupied"] - $dcStats["Infrastructure"] - $dcStats["Allocated"];


		// Perform two queries - one is for the wattage overrides (where NominalWatts > 0) and one for the template (default) values
		$selectSQL = "select sum(NominalWatts) from fac_Device a,fac_Cabinet b where a.Cabinet=b.CabinetID and a.NominalWatts>0 and b.DataCenterID=\"" . intval($this->DataCenterID) . "\"";

		$result = mysql_query( $selectSQL, $db );

		$statsRow = mysql_fetch_array( $result );
		$dcStats["TotalWatts"] = intval($statsRow[0]);
		
		$selectSQL = "select sum(c.Wattage) from fac_Device a, fac_Cabinet b, fac_DeviceTemplate c where a.Cabinet=b.CabinetID and a.TemplateID=c.TemplateID and a.NominalWatts=0 and b.DataCenterID=\"" . intval($this->DataCenterID) ."\"";

		$result = mysql_query( $selectSQL, $db );

		$statsRow = mysql_fetch_array( $result );
		$dcStats["TotalWatts"] += intval($statsRow[0]);

		return $dcStats;
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
  
  function CreateTemplate( $db ) {
    $insertSQL="insert into fac_DeviceTemplate set ManufacturerID=\"" . intval($this->ManufacturerID) . "\", Model=\"" . addslashes($this->Model) . "\", Height=\"" . intval($this->Height) . "\", Weight=\"" . intval($this->Weight) . "\", Wattage=\"" . intval($this->Wattage) . "\", DeviceType=\"" . addslashes( $this->DeviceType ) . "\", PSCount=\"" . intval( $this->PSCount ) . "\", NumPorts=\"" . intval( $this->NumPorts ) . "\"";
    $result=mysql_query($insertSQL,$db);

	if(!mysql_insert_id($db)){
		return -1;
	}else{    
	    $this->TemplateID=mysql_insert_id($db);     
	}
  }
  
  function UpdateTemplate( $db ) {
    $updateSQL = "update fac_DeviceTemplate set ManufacturerID=\"" . intval($this->ManufacturerID) . "\", Model=\"" . addslashes($this->Model) . "\", Height=\"" . intval($this->Height) . "\", Weight=\"" . intval($this->Weight) . "\", Wattage=\"" . intval($this->Wattage) . "\", DeviceType=\"" . addslashes( $this->DeviceType ) . "\", PSCount=\"" . intval( $this->PSCount ) . "\", NumPorts=\"" . intval( $this->NumPorts ) . "\" where TemplateID=\"" . intval($this->TemplateID) . "\"";
    $result = mysql_query( $updateSQL, $db );
	if(mysql_error($db)){
		return mysql_errno($db).": ".mysql_error($db)."\n";
	}
  }
  
  function DeleteTemplate( $db ) {
    $delSQL = "delete from fac_DeviceTemplate where TemplateID=\"" . intval($this->TemplateID) . "\"";
    $result = mysql_query( $delSQL, $db );
  }
  
  function GetTemplateByID( $db ) {
	$selectSQL = "select * from fac_DeviceTemplate where TemplateID=\"" . intval($this->TemplateID) . "\"";
	$result = mysql_query( $selectSQL, $db );

	// Reset object in case of a lookup failure
	foreach($this as $var => $value){
		$var=($var!='TemplateID')?NULL:$value;
	}
    
	if($tempRow=mysql_fetch_array($result)){
		$this->TemplateID=$tempRow["TemplateID"];
		$this->ManufacturerID=$tempRow["ManufacturerID"];
		$this->Model=$tempRow["Model"];
		$this->Height=$tempRow["Height"];
		$this->Weight=$tempRow["Weight"];
		$this->Wattage=$tempRow["Wattage"];
		$this->DeviceType=$tempRow["DeviceType"];
		$this->PSCount=$tempRow["PSCount"];
		$this->NumPorts=$tempRow["NumPorts"];
      
		return true;
	}else{
		return false;
	}
  }
  
  function GetTemplateList( $db ) {
    $selectSQL = "select * from fac_DeviceTemplate a, fac_Manufacturer b where a.ManufacturerID=b.ManufacturerID order by Name ASC, Model ASC";
    
    $result = mysql_query( $selectSQL, $db );
    
    $templateList = array();
    
    while ( $tempRow = mysql_fetch_array( $result ) ) {
      $templateNum = sizeof( $templateList );
      $templateList[$templateNum] = new DeviceTemplate();
      
      $templateList[$templateNum]->TemplateID = $tempRow["TemplateID"];
      $templateList[$templateNum]->ManufacturerID = $tempRow["ManufacturerID"];
      $templateList[$templateNum]->Model = $tempRow["Model"];
      $templateList[$templateNum]->Height = $tempRow["Height"];
      $templateList[$templateNum]->Weight = $tempRow["Weight"];
      $templateList[$templateNum]->Wattage = $tempRow["Wattage"];
	  $templateList[$templateNum]->DeviceType = $tempRow["DeviceType"];
	  $templateList[$templateNum]->PSCount = $tempRow["PSCount"];
	  $templateList[$templateNum]->NumPorts = $tempRow["NumPorts"];
    }
    
    return $templateList;
  }

  function GetMissingMfgDates( $db ) {
	$sql = "select a.* from fac_Device a, fac_DeviceTemplate b where
		a.TemplateID=b.TemplateID and b.ManufacturerID=" . 
		intval( $this->ManufacturerID ) . " and a.MfgDate<'1970-01-01'";
	$res = mysql_query( $sql, $db );

	$devList = array();

	while ( $devRow = mysql_fetch_array( $res ) ) {
		$devNum = count( $devList );
		$devList[$devNum] = new Device();

		$devList[$devNum]->DeviceID = $devRow["DeviceID"];
		$devList[$devNum]->GetDevice( $db );
	}

	return $devList;
  }
}

class Manufacturer {
  var $ManufacturerID;
  var $Name;
  
  function GetManufacturerByID( $db ) {
    $selectSQL = "select * from fac_Manufacturer where ManufacturerID=\"" . intval($this->ManufacturerID) . "\"";
    $result = mysql_query( $selectSQL, $db );
    
    if ( $row = mysql_fetch_array( $result ) ) {
      $this->ManufacturerID = $row["ManufacturerID"];
      $this->Name = $row["Name"];
      
      return true;
    } else {
      return false;
    }
  }
  
  function GetManufacturerList( $db ) {
    $selectSQL = "select * from fac_Manufacturer order by Name ASC";
    $result = mysql_query( $selectSQL, $db );
    
    $ManufacturerList = array();
    
    while ( $row = mysql_fetch_array( $result ) ) {
      $MfgNum = sizeof( $ManufacturerList );
      
      $ManufacturerList[$MfgNum] = new Manufacturer();
      
      $ManufacturerList[$MfgNum]->ManufacturerID = $row["ManufacturerID"];
      $ManufacturerList[$MfgNum]->Name = $row["Name"];
    }
    
    return $ManufacturerList;
  }

  function AddManufacturer( $db ) {
	$sql = "insert into fac_Manufacturer set Name=\"" . addslashes( $this->Name ) . "\"";

	$result = mysql_query( $sql, $db );

	return $result;
  }

  function UpdateManufacturer( $db ) {
	$sql = "update fac_Manufacturer set Name=\"" . addslashes( $this->Name ) . "\" where ManufacturerID=\"" . intval( $this->ManufacturerID ) . "\"";

	$result = mysql_query( $sql, $db );
	return "yup";
  }
}

class Zone {
  var $ZoneID;
  var $DataCenterID;
  var $Description;
  
  function GetZone( $db ) {
    $sql = "select * from fac_Zone where ZoneID=\"" . intval($this->ZoneID) . "\"";
    $result = mysql_query( $sql, $db );
    
    if ( $row = mysql_fetch_array( $result ) ) {
      $this->ZoneID = $row["ZoneID"];
      $this->DataCenterID = $row["DataCenterID"];
      $this->Description = $row["Description"];
    }
    
    return;
  }
  
  function GetZonesByDC( $db ) {
    $sql = "select * from fac_Zone where DataCenterID=\"" . intval($this->DataCenterID) . "\"";
    $result = mysql_query( $sql, $db );
    
    $zoneList = array();
    
    while ( $row = mysql_fetch_array( $result ) ) {
      $zoneNum = sizeof( $zoneList );
      
      $zoneList[$zoneNum] = new Zone();
      $zoneList[$zoneNum]->ZoneID = $row["ZoneID"];
      $zoneList[$zoneNum]->DataCenterID = $row["DataCenterID"];
      $zoneList[$zoneNum]->Description = $row["Description"];
    }
    
    return $zoneList;
  }
}


?>
