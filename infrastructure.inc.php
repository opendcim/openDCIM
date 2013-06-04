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
	
	function AddAudit( $db ) {
		$sql = sprintf( "insert into fac_BinAudits set BinID='%d', UserID=\"%d\", AuditStamp=\"%s\"", intval( $this->BinID ), addslashes( $this->UserID ), date( "Y-m-d", strtotime( $this->AuditStamp ) ) );
		mysql_query( $sql, $db );
	}
}

class BinContents {
	var $BinID;
	var $SupplyID;
	var $Count;
	
	function AddContents( $db ) {
		$sql = sprintf( "insert into fac_BinContents set BinID='%d', SupplyID='%d', Count='%d'", intval( $this->BinID ), intval( $this->SupplyID ), intval( $this->Count ) );
		mysql_query( $sql, $db );
	}
	
	function GetBinContents( $db ) {
		/* Return all of the supplies found in this bin */
		$sql = sprintf( "select * from fac_BinContents where BinID='%d'", intval( $this->BinID ) );
		$result = mysql_query( $sql, $db );
		
		$binList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$num = sizeof( $binList );
			$binList[$num] = new BinContents();
			
			$binList[$num]->BinID = $row["BinID"];
			$binList[$num]->SupplyID = $row["SupplyID"];
			$binList[$num]->Count = $row["Count"];
		}
		
		return $binList;
	}
	
	function FindSupplies( $db ) {
		/* Return all of the bins where this SupplyID is found */
		$sql = sprintf( "select a.* from fac_BinContents a, fac_SupplyBin b where a.SupplyID='%d' and a.BinID=b.BinID order by b.Location ASC", intval( $this->SupplyID ) );
		$result = mysql_query( $sql, $db );

		$binList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$num = sizeof( $binList );
			$binList[$num] = new BinContents();
			
			$binList[$num]->BinID = $row["BinID"];
			$binList[$num]->SupplyID = $row["SupplyID"];
			$binList[$num]->Count = $row["Count"];
		}
		
		return $binList;		
	}
	
	function UpdateCount( $db ) {
		$sql = sprintf( "update fac_BinContents set Count='%d' where BinID='%d' and SupplyID='%d'", intval( $this->Count ), intval( $this->BinID ), intval( $this->SupplyID ) );
		mysql_query( $sql, $db );
	}
	
	function RemoveContents( $db ) {
		$sql = sprintf( "delete from fac_BinContents where BinID='%d' and SupplyID='%d'", intval( $this->BinID ), intval( $this->SupplyID ) );
		mysql_query( $sql, $db );
	}
	
	function EmptyBin( $db ) {
		$sql = sprintf( "delete from fac_BinContents where BinID='%d'", intval( $this->BinID ) );
		mysql_query( $sql, $db );
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
	
	function MakeSafe() {
		$this->DataCenterID = intval( $this->DataCenterID );
		$this->Name = mysql_real_escape_string( $this->Name );
		$this->SquareFootage = intval( $this->SquareFootage );
		$this->DeliveryAddress = mysql_real_escape_string( $this->DeliveryAddress );
		$this->Administrator = mysql_real_escape_string( $this->Administrator );
		$this->MaxkW = intval( $this->MaxkW );
		$this->DrawingFileName = mysql_real_escape_string( $this->DrawingFileName );
		$this->EntryLogging = intval( $this->EntryLogging );
		$this->ContainerID = intval( $this->ContainerID );
		$this->MapX = intval( $this->MapX );
		$this->MapY = intval( $this->MapY );
	}
		
	function CreateDataCenter( $db ) {
		$this->MakeSafe();
		
		$sql = sprintf( "insert into fac_DataCenter 
						set Name=\"%s\", 
							SquareFootage=%d, 
							DeliveryAddress=\"%s\", 
							Administrator=\"%s\", 
							MaxkW=%d, 
							DrawingFileName=\"%s\", 
							EntryLogging=0,
							ContainerID=%d,
							MapX=%d,
							MapY=%d",
							$this->Name, 
							$this->SquareFootage, 
							$this->DeliveryAddress, 
							$this->Administrator, 
							$this->MaxkW, 
							$this->DrawingFileName,
							$this->ContainerID,
							$this->MapX,
							$this->MapY );
	
		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		return;
	}

	function UpdateDataCenter( $db ) {
		$this->MakeSafe();
		$sql = sprintf( "update fac_DataCenter 
						set Name=\"%s\", 
							SquareFootage=%d, 
							DeliveryAddress=\"%s\", 
							Administrator=\"%s\", 
							MaxkW=%d, 
							DrawingFileName=\"%s\", 
							EntryLogging=0,
							ContainerID=%d,
							MapX=%d,
							MapY=%d
						 where DataCenterID=%d",
						$this->Name, 
						$this->SquareFootage, 
						$this->DeliveryAddress, 
						$this->Administrator, 
						$this->MaxkW, 
						$this->DrawingFileName,
						$this->ContainerID,
						$this->MapX,
						$this->MapY, 
						$this->DataCenterID );
			
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
			$this->ContainerID = $row["ContainerID"];
			$this->MapX = $row["MapX"];
			$this->MapY = $row["MapY"];
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
			$datacenterList[$dcID]->ContainerID = $dcRow["ContainerID"];
			$datacenterList[$dcID]->MapX = $dcRow["MapX"];
			$datacenterList[$dcID]->MapY = $dcRow["MapY"];
		}

		return $datacenterList;
	}

	function GetDataCenterbyID( $db ) {
		//JMGA: This function is identical to GetDataCenter  (???) 
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
			$this->ContainerID = $dcRow["ContainerID"];
			$this->MapX = $dcRow["MapX"];
			$this->MapY = $dcRow["MapY"];
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
		$dcStats["ComputedWatts"] = 0;
		$dcStats["MeasuredWatts"] = 0;
		
		$pdu = new PowerDistribution();

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
		$dcStats["ComputedWatts"] = intval($statsRow[0]);
		
		$selectSQL = "select sum(c.Wattage) from fac_Device a, fac_Cabinet b, fac_DeviceTemplate c where a.Cabinet=b.CabinetID and a.TemplateID=c.TemplateID and a.NominalWatts=0 and b.DataCenterID=\"" . intval($this->DataCenterID) ."\"";

		$result = mysql_query( $selectSQL, $db );

		$statsRow = mysql_fetch_array( $result );
		$dcStats["ComputedWatts"] += intval($statsRow[0]);

		$dcStats["MeasuredWatts"] = $pdu->GetWattageByDC( $this->DataCenterID );
		
		return $dcStats;
	}
	
	function AddDCToTree($db,$lev=0) {
		$dept = new Department();
		$zone = new Zone();
		
		$classType = "liClosed";
		$tree = str_repeat(" ",$lev+1)."<li class=\"$classType\" id=\"dc$this->DataCenterID\"><a class=\"DC\" href=\"dc_stats.php?dc=" 
			. $this->DataCenterID . "\">" . $this->Name . "</a>/\n";
		$tree.=str_repeat(" ",$lev+2)."<ul>\n";

		$zone->DataCenterID=$this->DataCenterID;
		$zoneList=$zone->GetZonesByDC($db);
		while ( list( $zoneNum, $myzone ) = each( $zoneList ) ) {
			$tree .= str_repeat(" ",$lev+3)."<li class=\"liClosed\" id=\"zone".$myzone->ZoneID."\">" . 
					$myzone->Description . "/\n";
			$tree.=str_repeat(" ",$lev+4)."<ul>\n";
			//Rows
			$filas_sql="SELECT CabRowID, name AS Fila
						FROM fac_cabrow
						WHERE ZoneID=\"$myzone->ZoneID\"
						ORDER BY Fila";
			
			if ( ! $result_filas= mysql_query( $filas_sql, $db ) ) {
				return -1;
			}

			while ( $filaRow = mysql_fetch_array( $result_filas ) ) {
			  $tree .= str_repeat(" ",$lev+5)."<li class=\"liClosed\" id=\"fila".$filaRow['Fila']."\">
			  		 Fila ".$filaRow['Fila']."/\n";
			  $tree.=str_repeat(" ",$lev+6)."<ul>\n";
			  // DataCenterID and ZoneID are redundant if fac_cabrow is defined and is CabrowID set in fac_cabinet
			  $cab_sql = "SELECT * 
			  			FROM fac_Cabinet 
			  			WHERE DataCenterID=\"$this->DataCenterID\" 
							AND ZoneID=\"$myzone->ZoneID\"
							AND CabRowID=\"".$filaRow['CabRowID']."\"
						ORDER BY Location ASC";
			  
			  if ( ! $result = mysql_query( $cab_sql, $db ) ) {
					return -1;
			  }

			  while ( $cabRow = mysql_fetch_array( $result ) ) {
				  $dept->DeptID = $cabRow["AssignedTo"];
				  
				  if ( $dept->DeptID == 0 )
				    $dept->Name = _("General Use");
				  else
				    $dept->GetDeptByID( $db );
				    
				  //$tree .= str_repeat(" ",$lev+7)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']} [$dept->Name]</a></li>\n";
				  $tree .= str_repeat(" ",$lev+7)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']}</a></li>\n";
			  }
			  $tree .= str_repeat(" ",$lev+6)."</ul>\n";
			  $tree .= str_repeat(" ",$lev+5)."</li>\n";
			} 
			
			//Cabinets without CabRowID
			$cab_sql = "SELECT * 
			  			FROM fac_Cabinet 
			  			WHERE DataCenterID=\"$this->DataCenterID\" 
							AND ZoneID=\"$myzone->ZoneID\"
							AND CabRowID=0
						ORDER BY Location ASC";
			
			if ( ! $result = mysql_query( $cab_sql, $db ) ) {
				return -1;
			}
	
			while ( $cabRow = mysql_fetch_array( $result ) ) {
			  $dept->DeptID = $cabRow["AssignedTo"];
			  
			  if ( $dept->DeptID == 0 )
			    $dept->Name = _("General Use");
			  else
			    $dept->GetDeptByID( $db );
			    
			  //$tree .= str_repeat(" ",$lev+1)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']} [$dept->Name]</a></li>\n";
			  $tree .= str_repeat(" ",$lev+5)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']}</a></li>\n";
			}
			
			$tree .= str_repeat(" ",$lev+4)."</ul>\n";
			$tree .= str_repeat(" ",$lev+3)."</li>\n";
		} //zone
		
		//Cabinets without ZoneID
		$cab_sql = "select * from fac_Cabinet where DataCenterID=\"$this->DataCenterID\" AND 
						ZoneID=0 order by Location ASC";

		if ( ! $result = mysql_query( $cab_sql, $db ) ) {
			return -1;
		}

		while ( $cabRow = mysql_fetch_array( $result ) ) {
		  $dept->DeptID = $cabRow["AssignedTo"];
		  
		  if ( $dept->DeptID == 0 )
		    $dept->Name = _("General Use");
		  else
		    $dept->GetDeptByID( $db );
		    
		  //$tree .= str_repeat(" ",$lev+1)."<li id=\"cab{$cabRow['CabinetID']}\"><a href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']} [$dept->Name]</a></li>\n";
		  $tree .= str_repeat(" ",$lev+3)."<li id=\"cab{$cabRow['CabinetID']}\"><a class=\"RACK\" href=\"cabnavigator.php?cabinetid={$cabRow['CabinetID']}\">{$cabRow['Location']}</a></li>\n";
		}

		$tree .= str_repeat(" ",$lev+2)."</ul>\n";
		$tree .= str_repeat(" ",$lev+1)."</li>\n";
		
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
  
  function GetTemplateByID( $db = null ) {
	global $dbh;
	
	$sql = "select * from fac_DeviceTemplate where TemplateID=\"" . intval($this->TemplateID) . "\"";

	// Reset object in case of a lookup failure
	foreach($this as $var => $value){
		$var=($var!='TemplateID')?NULL:$value;
	}
    
	if ( $tempRow = $dbh->query( $sql )->fetch() ) {
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
	} else {
		$info = $dbh->errorInfo();

		error_log( "PDO Error:  " . $info[2] );
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

class Supplies {
	var $SupplyID;
	var $PartNum;
	var $PartName;
	var $MinQty;
	var $MaxQty;
	
	function CreateSupplies( $db ) {
		$sql = sprintf( "insert into fac_Supplies set PartNum=\"%s\", PartName=\"%s\", MinQty='%d', MaxQty='%d'", addslashes( $this->PartNum ), addslashes( $this->PartName ), intval( $this->MinQty ), intval( $this->MaxQty ) );
		mysql_query( $sql, $db );
		
		$this->SupplyID = mysql_insert_id( $db );
	}
	
	function GetSupplies( $db ) {
		$sql = sprintf( "select * from fac_Supplies where SupplyID='%d'", intval( $this->SupplyID ) );
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->SupplyID = $row["SupplyID"];
			$this->PartNum = $row["PartNum"];
			$this->PartName = $row["PartName"];
			$this->MinQty = $row["MinQty"];
			$this->MaxQty = $row["MaxQty"];
		}
	}
	
	function GetSuppliesList($db){
		$sql="select * from fac_Supplies order by PartNum ASC";
		$result=mysql_query($sql,$db);
		
		$supplyList=array();
		
		while($row=mysql_fetch_array($result)){
			$supplyList[$row["SupplyID"]]=new Supplies();
			
			$supplyList[$row["SupplyID"]]->SupplyID=$row["SupplyID"];
			$supplyList[$row["SupplyID"]]->PartNum=$row["PartNum"];
			$supplyList[$row["SupplyID"]]->PartName=$row["PartName"];
			$supplyList[$row["SupplyID"]]->MinQty=$row["MinQty"];
			$supplyList[$row["SupplyID"]]->MaxQty=$row["MaxQty"];
		}
		
		return $supplyList;
	}
	
	function UpdateSupplies($db){
		$sql=sprintf( "update fac_Supplies set PartNum=\"%s\", PartName=\"%s\", MinQty='%d', MaxQty='%d' where SupplyID='%d'", addslashes( $this->PartNum ), addslashes( $this->PartName ), intval( $this->MinQty ), intval( $this->MaxQty ), intval( $this->SupplyID ) );
		mysql_query($sql,$db);
	}
	
	function DeleteSupplies( $db ) {
		$sql = sprintf( "delete from fac_Supplies where SupplyID='%d'", intval( $this->SupplyID ) );
		mysql_query( $sql, $db );
	}
}

class SupplyBin {
	var $BinID;
	var $Location;
	
	function GetBin( $db ) {
		$sql = sprintf( "select * from fac_SupplyBin where BinID='%d'", intval( $this->BinID ) );
		$result = mysql_query( $sql, $db );
		
		if ( $row = mysql_fetch_array( $result ) ) {
			$this->Location = $row["Location"];
		}
	}
	
	function CreateBin( $db ) {
		$sql = sprintf( "insert into fac_SupplyBin set Location=\"%s\"", addslashes( $this->Location ) );
		mysql_query( $sql, $db );
		
		$this->BinID = mysql_insert_id( $db );
	}
	
	function UpdateBin( $db ) {
		$sql = sprintf( "update fac_SupplyBin set Location=\"%s\" where BinID='%d'", addslashes( $this->Location ), intval( $this->BinID ) );
		mysql_query( $sql, $db );	
	}
	
	function DeleteBin( $db ) {
		$sql = sprintf( "delete from fac_SupplyBin where BinID='%d'; delete from fac_BinContents where BinID='%d'; delete from fac_BinAudits where BinID='%d'", intval( $this->BinID ), intval( $this->BinID ), intval( $this->BinID ) );
		mysql_query( $sql, $db );
	}
	
	function GetBinList( $db ) {
		$sql = sprintf( "select * from fac_SupplyBin order by Location ASC" );
		$result = mysql_query( $sql, $db );
		
		$binList = array();
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$binNum = sizeof( $binList );
			$binList[$binNum] = new SupplyBin();
			
			$binList[$binNum]->BinID = $row["BinID"];
			$binList[$binNum]->Location = $row["Location"];
		}
		
		return $binList;
	}
}

class Zone {
  var $ZoneID;
  var $DataCenterID;
  var $Description;
  
  function CreateZone ( $db ) {
		$sql = sprintf( "insert into fac_Zone set Description=\"%s\", DataCenterID='%d'", addslashes( $this->Description ), intval($this->DataCenterID));
		mysql_query( $sql, $db );
		
		$this->ZoneID = mysql_insert_id( $db );
	}
	
  function UpdateZone( $db ) {
  		//update cabinets in this zone
		$sql = sprintf( "update fac_Cabinet set DataCenterID=%d where ZoneID='%d'", intval($this->DataCenterID), intval( $this->ZoneID ) );
		mysql_query( $sql, $db );
		//update zone	
		$sql = sprintf( "update fac_Zone set Description=\"%s\", DataCenterID=%d where ZoneID='%d'", addslashes( $this->Description ), intval($this->DataCenterID), intval( $this->ZoneID ) );
		mysql_query( $sql, $db );	
  }
	
  function DeleteZone( $db ) {
		//update cabinets in this zone
  		$sql = sprintf( "update from fac_Cabinet set CabRowID=0, ZoneID=0 where ZoneID='%d'", intval( $this->ZoneID ) );
		mysql_query( $sql, $db );
		//delete CabRows in this zone
		$sql = sprintf( "delete from fac_CabRow where ZoneID='%d'", intval( $this->ZoneID ) );
		mysql_query( $sql, $db );
		//delete zone
		$sql = sprintf( "delete from fac_Zone where ZoneID='%d'", intval( $this->ZoneID ) );
		mysql_query( $sql, $db );
  }
  
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
    $sql = "select * from fac_Zone where DataCenterID=\"" . intval($this->DataCenterID) . "\" order by Description";
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
  function GetZoneList( $db ) {
	$sql = sprintf( "select * from fac_Zone order by Description ASC" );
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

class CabRow {
  var $CabRowID;
  var $Name;
  var $ZoneID;
  
  function CreateCabRow( $db ) {
		$sql = sprintf( "insert into fac_CabRow set Name=\"%s\", ZoneID='%d'", addslashes( $this->Name ), intval($this->ZoneID));
		mysql_query( $sql, $db );
		
		$this->CabRowID = mysql_insert_id( $db );
	}
	
  function UpdateCabRow( $db ) {
		//update cabinets in this cabrow
		$sql = sprintf( "update fac_Cabinet set ZoneID='%d' where CabRowID='%d'", intval($this->ZoneID), intval( $this->CabRowID ) );
		mysql_query( $sql, $db );
		//update cabrow
		$sql = sprintf( "update fac_CabRow set Name=\"%s\", ZoneID='%d' where CabRowID='%d'", addslashes( $this->Name ), intval($this->ZoneID), intval( $this->CabRowID ) );
		mysql_query( $sql, $db );	
	}
	
  function DeleteCabRow( $db ) {
  		//update cabinets in this cabrow
		$sql = sprintf( "update from fac_Cabinet set CabRowID=0 where CabRowID='%d' and ZoneID='%d'", intval( $this->CabRowID ), intval( $this->ZoneID ) );
		mysql_query( $sql, $db );
		//delete cabrow
		$sql = sprintf( "delete from fac_CabRow where CabRowID='%d'", intval( $this->CabRowID ) );
		mysql_query( $sql, $db );
  }
  
  function GetCabRow( $db ) {
    $sql = "select * from fac_CabRow where CabRowID=\"" . intval($this->CabRowID) . "\"";
    $result = mysql_query( $sql, $db );
    
    if ( $row = mysql_fetch_array( $result ) ) {
      $this->CabRowID = $row["CabRowID"];
      $this->Name = $row["Name"];
      $this->ZoneID = $row["ZoneID"];
    }
    
    return;
  }
  
  function GetCabRowsByZones( $db ) {
    $sql = "select * from fac_CabRow where ZoneID=\"" . intval($this->ZoneID) . "\" order by Name";
    $result = mysql_query( $sql, $db );
    
    $cabrowList = array();
    
    while ( $row = mysql_fetch_array( $result ) ) {
      $zoneNum = sizeof( $cabrowList );
      
      $cabrowList[$zoneNum] = new CabRow();
      $cabrowList[$zoneNum]->CabRowID = $row["CabRowID"];
      $cabrowList[$zoneNum]->Name = $row["Name"];
      $cabrowList[$zoneNum]->ZoneID = $row["ZoneID"];
    }
    
    return $cabrowList;
  }
  
  function GetCabRowList( $db ) {
	$sql = sprintf( "select * from fac_CabRow order by Name ASC" );
	$result = mysql_query( $sql, $db );
	
	$cabrowList = array();
	
	while ( $row = mysql_fetch_array( $result ) ) {
		$cabrowNum = sizeof( $cabrowList );
		$cabrowList[$cabrowNum] = new CabRow();
		
		$cabrowList[$cabrowNum]->CabRowID = $row["CabRowID"];
		$cabrowList[$cabrowNum]->ZoneID = $row["ZoneID"];
		$cabrowList[$cabrowNum]->Name = $row["Name"];
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

	function MakeSafe() {
		$this->ContainerID = intval( $this->ContainerID );
		$this->Name = mysql_real_escape_string( $this->Name );
		$this->ParentID = intval( $this->ParentID );
		$this->DrawingFileName = mysql_real_escape_string( $this->DrawingFileName );
		$this->MapX = intval( $this->MapX );
		$this->MapY = intval( $this->MapY );
	}
	
	function CreateContainer( $db ) {
		$this->MakeSafe();
		
		$sql = sprintf( "insert into fac_Container set Name=\"%s\", ParentID=%d, DrawingFileName=\"%s\", MapX=%d, MapY=%d",
			$this->Name, $this->ParentID, $this->DrawingFileName, $this->MapX, $this->MapY );

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}
		$this->ContainerID = mysql_insert_id();

		return;
	}

	function UpdateContainer( $db ) {
		$this->MakeSafe();
		$sql = sprintf( "update fac_Container set Name=\"%s\", ParentID=%d, DrawingFileName=\"%s\", MapX=%d, MapY=%d where ContainerID=%d",
			$this->Name, $this->ParentID, $this->DrawingFileName, $this->MapX, $this->MapY, $this->ContainerID );
		
		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		return;
	}

	function GetContainer( $db ) {
		$this->MakeSafe();
		$sql="SELECT * FROM fac_Container WHERE ContainerID=".intval($this->ContainerID);

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return false;
		}
		
		if (mysql_num_rows($result)==0){
			return false;
		} else{
			$row = mysql_fetch_array( $result );
			$this->Name = $row["Name"];
			$this->ParentID= $row["ParentID"];
			$this->DrawingFileName = $row["DrawingFileName"];
			$this->MapX = $row["MapX"];
			$this->MapY = $row["MapY"];
		}
		
		return true;
	}
	
	function GetChildContainerList( $db ) {
		$sql = "SELECT * 
				FROM fac_Container
				WHERE ParentID=".intval($this->ContainerID)." 
				ORDER BY Name ASC";

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return false;
		}

		$containerList = array();

		while ( $cRow = mysql_fetch_array( $result ) ) {
			$cID = $cRow[ "ContainerID" ];

			$containerList[$cID] = new Container();
			$containerList[$cID]->ContainerID = $cRow["ContainerID"];
			$containerList[$cID]->Name = $cRow["Name"];
			$containerList[$cID]->ParentID = $cRow["ParentID"];
			$containerList[$cID]->DrawingFileName = $cRow["DrawingFileName"];
			$containerList[$cID]->MapX = $cRow["MapX"];
			$containerList[$cID]->MapY = $cRow["MapY"];
		}

		return $containerList;
	}
	function GetChildDCList( $db ) {
		$sql = "SELECT * 
				FROM fac_DataCenter
				WHERE ContainerID=".$this->ContainerID." 
				ORDER BY Name ASC";

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return false;
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
			$datacenterList[$dcID]->ContainerID = $dcRow["ContainerID"];
			$datacenterList[$dcID]->MapX = $dcRow["MapX"];
			$datacenterList[$dcID]->MapY = $dcRow["MapY"];
		}

		return $datacenterList;
	}
		
	function BuildMenuTree( $db ) {
		$c=new Container();
		$c->ContainerID=0;
		//begin the tree
		$tree="\n<ul class=\"mktree\" id=\"datacenters\">\n";;
		//Add root children
		$tree.=$c->AddContainerToTree($db,0);
		$tree .= "<li class=\"liOpen\" id=\"dc-1\"><a href=\"storageroom.php\">"._("Storage Room")."</a></li>\n";
		$tree .= "</ul>\n";
		return $tree;
	}

	function AddContainerToTree($db,$lev=0) {
		
		$tree="";
		$container_opened=false;
		
		if ($this->GetContainer($db)){
			$lev++;
			$tree .= str_repeat(" ",$lev)."<li class=\"liOpen\" id=\"c".$this->ContainerID."\"><a class=\"CONTAINER\" href=\"container_stats.php?container=" 
					. $this->ContainerID . "\">" . $this->Name . "</a>\n";
			$lev++;
			$tree .= str_repeat(" ",$lev)."<ul>\n";
			$container_opened=true;
		}
		
		$cList=$this->GetChildContainerList($db);
		$lev++;
		if ( count( $cList ) > 0 ) {
			while ( list( $cID, $container ) = each( $cList ) ) {
				$tree.=$container->AddContainerToTree($db,$lev);
			}
		}
		
		$dcList = $this->GetChildDCList( $db );

		if ( count( $dcList ) > 0 ) {
			while ( list( $dcID, $datacenter ) = each( $dcList ) ) {
				$tree.=$datacenter->AddDCToTree($db,$lev);
			} //DC
		}

		if ($container_opened){
			
			$tree .= str_repeat(" ",$lev-1)."</ul>\n";
			$tree .= str_repeat(" ",$lev-2)."</li>\n";
		}
		
		return $tree;
	}
	
	function MakeContainerImage( $db ) {
		$mapHTML = "";
		$mapfile="";
		$tam=50;
	 
		if ( strlen($this->DrawingFileName) > 0 ) {
			$mapfile = "drawings/" . $this->DrawingFileName;
		}
	   
		if ( file_exists( $mapfile ) ) {
			list($width, $height, $type, $attr)=getimagesize($mapfile);
			$mapHTML.="<div style='position:relative;'>\n";
			$mapHTML.="<img src=\"".$mapfile."\" width=\"$width\" height=\"$height\" alt=\"Container Image\">\n";
			
			$cList=$this->GetChildContainerList($db);
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
			
			$dcList=$this->GetChildDCList($db);
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
	function MakeContainerMiniImage($db,$tipo="",$id=0) {
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
			
			$cList=$this->GetChildContainerList($db);
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
			
			$dcList=$this->GetChildDCList($db);
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
	
	function GetContainerStatistics( $db ) {
		$this->GetContainer( $db );

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
		
		$dcList = $this->GetChildDCList( $db );
		if ( count( $dcList ) > 0 ) {
			while ( list( $dcID, $datacenter ) = each( $dcList ) ) {
				$dcStats=$datacenter->GetDCStatistics( $db );
				$cStats["DCs"]++;
				$cStats["TotalU"] += $dcStats["TotalU"];
				$cStats["Infrastructure"] += $dcStats["Infrastructure"];
				$cStats["Occupied"] += $dcStats["Occupied"];
				$cStats["Allocated"] += $dcStats["Allocated"];
				$cStats["Available"] += $dcStats["Available"];
				$cStats["SquareFootage"] += $datacenter->SquareFootage;
				$cStats["ComputedWatts"] += $dcStats["ComputedWatts"];
				$cStats["MeasuredWatts"] += $dcStats["MeasuredWatts"];
				$cStats["MaxkW"] += $datacenter->MaxkW;
			} 
		}
		
		$cList=$this->GetChildContainerList($db);
		if ( count( $cList ) > 0 ) {
			while ( list( $cID, $container ) = each( $cList ) ) {
				$childStats=$container->GetContainerStatistics($db);
				$cStats["DCs"] += $childStats["DCs"];
				$cStats["TotalU"] += $childStats["TotalU"];
				$cStats["Infrastructure"] += $childStats["Infrastructure"];
				$cStats["Occupied"] += $childStats["Occupied"];
				$cStats["Allocated"] += $childStats["Allocated"];
				$cStats["Available"] += $childStats["Available"];
				$cStats["SquareFootage"] += $childStats["SquareFootage"];
				$cStats["ComputedWatts"] += $childStats["ComputedWatts"];
				$cStats["MeasuredWatts"] += $childStats["MeasuredWatts"];
				$cStats["MaxkW"] += $childStats["MaxkW"]; 
			}
		}
		return $cStats;
	}
	
	function GetContainerList( $db ) {
		$sql = "SELECT * FROM fac_Container ORDER BY Name ASC";

		if ( ! $result = mysql_query( $sql, $db ) ) {
			error_log( sprintf( "%s; SQL=`%s`", mysql_error( $db ), $sql ) );
			return -1;
		}

		$containerList = array();

		while ( $cRow = mysql_fetch_array( $result ) ) {
			$cID = $cRow[ "ContainerID" ];

			$containerList[$cID] = new Container();
			$containerList[$cID]->ContainerID = $cRow["ContainerID"];
			$containerList[$cID]->Name = $cRow["Name"];
			$containerList[$cID]->ParentID = $cRow["ParentID"];
			$containerList[$cID]->DrawingFileName = $cRow["DrawingFileName"];
			$containerList[$cID]->MapX = $cRow["MapX"];
			$containerList[$cID]->MapY = $cRow["MapY"];
		}

		return $containerList;
	}
	
}
//END Class Container
?>
