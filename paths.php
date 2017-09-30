<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("End to end connection path");

	$status="";
	$path="";
	$pathid="";
	$dp=new DevicePorts();

	function builddclist($id=null){
		$dc=new DataCenter();
		$dcList=$dc->GetDCList();
		$idnum='';

		if(!is_null($id)){
			if($id=="dc-front"){
				$idnum=1;
			}elseif($id=="dc-rear"){
				$idnum=2;
			}
			$id=" name=\"$id\" id=\"$id\"";
		}

		$dcpicklist="<select$id><option value=0>&nbsp;</option>";
		foreach($dcList as $d){
			$dcpicklist.="<option value=$d->DataCenterID>$d->Name</option>";
		}
		$dcpicklist.='</select>';

		return $dcpicklist;
	}

	if(isset($_POST['action']) && $_POST['action']=='delete'){
		$port=new DevicePorts();
		$ports=array();// list of ports we want to remove
		$rights="None";
		for ($i=1;$i<count($_POST["PortNumber"]);$i++){
			if ($_POST["PortNumber"][$i]>0){
				$port->DeviceID=$_POST["DeviceID"][$i];
				$port->PortNumber=$_POST["PortNumber"][$i];
				$port->getPort();
				$dev=new Device();
				$dev->DeviceID=$port->DeviceID;
				$dev->GetDevice();
				$rights=($dev->Rights=="Write")?"Write":$rights;
				//only remove connections between front ports
				if ($port->ConnectedPort>0){
					// Add to the list
					$ports[]=$port;
				}
			}
		}
		if($rights=="Write"){
			foreach($ports as $p){
				$p->removeConnection();
			}
			$status.=__("Front connections deleted");
		}else{
			$status.=__("Come back when you have device rights");
		}
	}
	
	if(isset($_POST['action']) || isset($_REQUEST['pathid']) || (isset($_REQUEST['deviceid']) && isset($_REQUEST['portnumber']))){
		//Search by deviceid/port
		if(isset($_REQUEST['deviceid']) && $_REQUEST['deviceid']!=''
			&& isset($_REQUEST['portnumber']) && $_REQUEST['portnumber']!=''){
			
			$cp=new ConnectionPath();
			$dev=new Device();
			
			$dev->DeviceID=intval($_REQUEST['deviceid']);
			$dev->GetDevice();
			
			$pathid=$dev->Label ." - ".__("Port")." ".intval($_REQUEST['portnumber']);
			
			$cp->DeviceID=intval($_REQUEST['deviceid']);
			$cp->PortNumber=intval($_REQUEST['portnumber']);
			$cp->DeviceType=$dev->DeviceType;
			$cp->Front=true;
				
			if (!$cp->GotoHeadDevice()){
				$status="<blink>".__("There is a loop in this port")."</blink>";
			} 
		//Search by label/port
		}elseif(isset($_POST['label']) && $_POST['label']!=''
			&& isset($_POST['port']) && $_POST['port']!=''
			&& $_POST['action']=="DevicePortSearch"){

			//Remove control characters tab, enter, etc
			$label=preg_replace("/[[:cntrl:]]/","",$_POST['label']);
			//Remove any extra quotes that could get passed in from some funky js or something
			$label=str_replace(array("'",'"'),"",$label);
			
			//Get list of devices
			$dev=new Device();
			$dev->Label=$label;
			$devList=$dev->SearchDevicebyLabel();
			
			if (isset($_POST['devid']) && $_POST['devid']!=0 &&
				isset($_POST['label_ant']) && $_POST['label_ant']==$_POST['label']){
				//by ID1
				$cp=new ConnectionPath();
				$cp->DeviceID=intval($_POST['devid']);
				$cp->PortNumber=intval($_POST['port']);
				//label of devid
				$label=$devList[$cp->DeviceID]->Label;
				//search the begining of the path
				if (!$cp->GotoHeadDevice()){
					$status="<blink>".__("There is a loop in this port")."</blink>";
				} 
			}else{ //no devid1 or changed label
				//by label
				if (count($devList)==0){
					$status=__("Device not found")." '$label'";
				}
				elseif(count($devList)>1){
					//several dev1
					$status=__("There are several devices with this label").".<br>". __("Please, select a device from list").".";
					//I use $devList to fill a combobox later
				}else {
					$cp=new ConnectionPath();
					$keys=array_keys($devList);
					$cp->DeviceID=$keys[0];
					$cp->PortNumber=intval($_POST['port']);
					//label of devid
					$label=$devList[$cp->DeviceID]->Label;
					
					//intento irme al principio del path
					if (!$cp->GotoHeadDevice()){
						$status="<blink>".__("There is a loop in this port")."</blink>";
					} 
				}
			}
				
			$pathid=$label."[".__("Port").": ".intval($_POST['port'])."]";
		}
		
		//Search by path identifier (in "notes" field)
		elseif(isset($_POST['pathid']) && $_POST['pathid']!='' && $_POST['action']=="PathIdSearch" 
			|| isset($_GET['pathid']) && $_GET['pathid']!=''){
			$status="";
			if (isset($_GET['pathid'])) {
				$pathid=$_GET['pathid'];
			}else{ 
				$pathid=$_POST['pathid'];
			}

			// No SQL injection for joo
			$pathid=sanitize($pathid);
			
			$sql="SELECT DeviceID, PortNumber FROM fac_Ports WHERE Notes=\"$pathid\"";

			$result = $dbh->prepare($sql);
			$result->execute();
			
			if($result->rowCount()==0){
				$status=__("Not found");
			} else {
				$row = $result->fetch();
				
				$cp=new ConnectionPath();
				$cp->DeviceID=$row["DeviceID"];
				$cp->PortNumber=$row["PortNumber"];
				
				if (!$cp->GotoHeadDevice()){
					$status="<blink>".__("There is a loop in this port")."</blink>";
				} 
			}
		}
		else{
			$status="<blink>".__("Error")."</blink>";
		}
		
		if ($status==""){
			
			$path.="<div style=\"text-align: center;\">";
			$path.="<div style=\"font-size: 1.5em;\">".__("Path of")." $pathid</div>\n";

			//Path Table
			$path.="<table id=\"parcheos\">\n\t<tr>\n\t\t<td colspan=6>&nbsp;</td>\n\t</tr>\n\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td class=\"right\">";
			
			$dev=new Device();
			$end=false;
			$elem_path=0;
			$form_eliminar="";
				
			while (!$end) {
				//first device
				//get the device
				$dev->DeviceID=$cp->DeviceID;
				$dev->GetDevice();
				$elem_path++;
				$form_eliminar.="<input type=\"hidden\" name=\"DeviceID[$elem_path]\" value=\"$cp->DeviceID\">\n";
				$form_eliminar.="<input type=\"hidden\" name=\"PortNumber[$elem_path]\" value=\"$cp->PortNumber\">\n";
				
				//If this device is the first and is a panel, I put it to the right position freeing the left
				if ($elem_path==1 && $dev->DeviceType=="Patch Panel"){
					$path.="</td>\n\t\t<td></td>";
					
					//In connection type
					$tipo_con=($cp->PortNumber>0)?"r":"f";
					
					//half hose
					$path.="\n\t\t<td class=\"$tipo_con-right\"></td>\n";
					
					//Out connection type
					$tipo_con=($cp->PortNumber>0)?"f":"r";
					
					//Can the path continue?
					if ($dev->DeviceType=="Patch Panel"){
						$path.="\n\t\t<td class=\"connection-$tipo_con-1\">";
					}else{
						$path.="\n\t\t<td>";
					}
				
					//I get device Lineage (for multi level chassis)
					$devList=array();
					$devList=$dev->GetDeviceLineage();
					
					//Device table
					$path.="\n\t\t\t<table>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
					
					//Cabinet
					$cab=new Cabinet();
					$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
					$cab->GetCabinet();
					$path.=__("Cabinet").": <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">$cab->Location</a>";
					$path.="</th>\n\t\t\t\t</tr>\n\t\t\t\t<tr>\n\t\t\t\t\t<td>U:{$devList[sizeof($devList)]->Position}</td>\n";
					
					
					//Lineage
					$t=5;
					for ($i=sizeof($devList); $i>1; $i--){
						$path.=str_repeat("\t",$t++)."<td>\n";
						$path.=str_repeat("\t",$t++)."<table>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t--)."<th colspan=2>";
						$path.="<a href=\"devices.php?DeviceID={$devList[$i]->DeviceID}\">{$devList[$i]->Label}</a>";
						$path.="</th>\n";
						$path.=str_repeat("\t",$t)."</tr>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t)."<td>Slot:{$devList[$i-1]->Position}</td>\n";
					}
					
					//device
					$dp->DeviceID=$dev->DeviceID;
					$dp->PortNumber=abs($cp->PortNumber);
					$dp->getPort();
					$label=($dp->Label!='')?$dp->Label:abs($cp->PortNumber);
					$path.=str_repeat("\t",$t--)."<td>".
							"<a href=\"devices.php?DeviceID=$dev->DeviceID\">$dev->Label</a>
							<br>".__("Port").": $label</td>\n";
					$path.=str_repeat("\t",$t--)."</tr>\n";
					
					//Ending device table
					for ($i=sizeof($devList); $i>1; $i--){
						$path.=str_repeat("\t",$t--)."</table>\n";
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
					}
					if ($cp->PortNumber>0){
						$t++;
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t)."<td colspan=2 class=\"base-f\">\n";
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
					}
					$path.=str_repeat("\t",$t--)."</table>\n";
					
					//ending row
					$path.="\t\t</td>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t</tr>\n";

					//connection for next row
					$conex="\t\t<td class=\"connection-$tipo_con-3\">&nbsp;</td>\n";
					$conex.="\t\t<td class=\"connection-$tipo_con-2\">&nbsp;</td>\n\t<td></td></tr>\n";
					if ($cp->GotoNextDevice()) {
						$tipo_con=($cp->PortNumber>0)?"r":"f";  //In connection type

						//row separation between patch rows: draw the connection between panels
						$path.="\t<tr>\n\t\t<td></td><td class=\"connection-$tipo_con-4\">&nbsp;</td>\n"; 
						$path.="\t\t<td class=\"connection-$tipo_con-3\">&nbsp;</td>\n";
						$path.=$conex;
						$path.="\n<tr>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td>";
					} else {
						//End of path
						$end=true;
					}
					
				} else {
				//A row with two devices
					//I get device Lineage (for multi level chassis)
					$devList=array();
					$devList=$dev->GetDeviceLineage();
					
					//Device table
					$path.="\n\t\t\t<table>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
					
					//cabinet
					$cab=new Cabinet();
					$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
					$cab->GetCabinet();
					$path.=__("Cabinet").": <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">$cab->Location</a>";
					$path.="</th>\n\t\t\t\t</tr>\n\t\t\t\t<tr>\n\t\t\t\t\t<td>U:{$devList[sizeof($devList)]->Position}</td>\n";
					
					//Lineage
					$t=5;
					for ($i=sizeof($devList); $i>1; $i--){
						$path.=str_repeat("\t",$t++)."<td>\n";
						$path.=str_repeat("\t",$t++)."<table>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t--)."<th colspan=2>";
						$path.="<a href=\"devices.php?DeviceID={$devList[$i]->DeviceID}\">{$devList[$i]->Label}</a>";
						$path.="</th>\n";
						$path.=str_repeat("\t",$t)."</tr>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t)."<td>Slot:{$devList[$i-1]->Position}</td>\n";
					}
					
					//Device
					$dp->DeviceID=$dev->DeviceID;
					$dp->PortNumber=abs($cp->PortNumber);
					$dp->getPort();
					$label=($dp->Label!='')?$dp->Label:abs($cp->PortNumber);
					$path.=str_repeat("\t",$t--)."<td>".
							"<a href=\"devices.php?DeviceID=$dev->DeviceID\">$dev->Label".
							"</a><br>".__("Port").": $label</td>\n";
					$path.=str_repeat("\t",$t--)."</tr>\n";
					$path.=str_repeat("\t",$t--)."</table>\n";
					
					//ending device table
					for ($i=sizeof($devList); $i>1; $i--){
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
						$path.=str_repeat("\t",$t--)."</table>\n";
					}
					
					$path.="\t\t</td>";
					
					if ($elem_path==1 || $dev->DeviceType=="Patch Panel"){
						//half hose
						//Out connection type
						$tipo_con=($cp->PortNumber>0)?"f":"r";
						
						$path.="\n\t\t<td class=\"$tipo_con-left\"></td>\n";
					}
					//next device, if exist
					if ($cp->GotoNextDevice()) {
						$elem_path++;
						$form_eliminar.="<input type=\"hidden\" name=\"DeviceID[$elem_path]\" value=\"$cp->DeviceID\">\n";
						$form_eliminar.="<input type=\"hidden\" name=\"PortNumber[$elem_path]\" value=\"$cp->PortNumber\">\n";
						
						$dev->DeviceID=$cp->DeviceID;
						$dev->GetDevice();
						
						//In connection type
						$tipo_con=($cp->PortNumber>0)?"r":"f";
						
						//half hose
						$path.="\n\t\t<td class=\"$tipo_con-right\"></td>\n";
						
						//Out connection type
						$tipo_con=($cp->PortNumber>0)?"f":"r";
						
						//Can I follow?
						if ($dev->DeviceType=="Patch Panel"){
							$path.="\n\t\t<td class=\"connection-$tipo_con-1\">";
							// I prepare row separation between patch rows
							$conex="\t\t<td class=\"connection-$tipo_con-3\">&nbsp;</td>\n";
							$conex.="\t\t<td class=\"connection-$tipo_con-2\">&nbsp;</td>\n\t<td></td></tr>\n";
						}
						else{
							$conex="\t\t<td></td>\t\t<td></td>\n\t<td></td></tr>";
							$path.="\n\t\t<td>";
						}
					
						//I get device Lineage (for multi level chassis)
						$devList=array();
						$devList=$dev->GetDeviceLineage();
						
						//Device Table
						$path.="\n\t\t\t<table>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
						
						//cabinet
						$cab=new Cabinet();
						$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
						$cab->GetCabinet();
						$path.=__("Cabinet").": <a href=\"cabnavigator.php?cabinetid=$cab->CabinetID\">$cab->Location</a>";
						$path.="</th>\n\t\t\t\t</tr>\n\t\t\t\t<tr>\n\t\t\t\t\t<td>U:{$devList[sizeof($devList)]->Position}</td>\n";
						
						//lineage
						$t=5;
						for ($i=sizeof($devList); $i>1; $i--){
							$path.=str_repeat("\t",$t++)."<td>\n";
							$path.=str_repeat("\t",$t++)."<table>\n";
							$path.=str_repeat("\t",$t++)."<tr>\n";
							$path.=str_repeat("\t",$t--)."<th colspan=2>";
							$path.="<a href=\"devices.php?DeviceID={$devList[$i]->DeviceID}\">".$devList[$i]->Label."</a>";
							$path.="</th>\n";
							$path.=str_repeat("\t",$t)."</tr>\n";
							$path.=str_repeat("\t",$t++)."<tr>\n";
							$path.=str_repeat("\t",$t)."<td>Slot:{$devList[$i-1]->Position}</td>\n";
						}
						
						//device
						$dp->DeviceID=$dev->DeviceID;
						$dp->PortNumber=abs($cp->PortNumber);
						$dp->getPort();
						$label=($dp->Label!='')?$dp->Label:abs($cp->PortNumber);
						$path.=str_repeat("\t",$t--)."<td>".
								"<a href=\"devices.php?DeviceID=$dev->DeviceID\">$dev->Label".
								"</a><br>".__("Port").": $label</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
						
						//ending device table
						for ($i=sizeof($devList); $i>1; $i--){
							$path.=str_repeat("\t",$t--)."</table>\n";
							$path.=str_repeat("\t",$t--)."</td>\n";
							$path.=str_repeat("\t",$t--)."</tr>\n";
						}
						
						if ($cp->PortNumber>0){
							$t++;
							$path.=str_repeat("\t",$t++)."<tr>\n";
							$path.=str_repeat("\t",$t)."<td colspan=2 class=\"base-f\">\n";
							$path.=str_repeat("\t",$t--)."</td>\n";
							$path.=str_repeat("\t",$t--)."</tr>\n";
						}
						$path.=str_repeat("\t",$t--)."</table>\n";
						
						//ending row
						$path.="\t\t</td>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t</tr>\n";
	
						if ($cp->GotoNextDevice()) {
							$tipo_con=($cp->PortNumber>0)?"r":"f";  //In connection type
	
							//row separation between patch rows: draw the connection between panels
							$path.="\t<tr>\n\t\t<td></td><td class=\"connection-$tipo_con-4\">&nbsp;</td>\n"; 
							$path.="\t\t<td class=\"connection-$tipo_con-3\">&nbsp;</td>\n";
							$path.=$conex;
							$path.="\n<tr>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td>";
						} else {
							//End of path
							$path.="\t<tr>\n\t\t<td></td><td>&nbsp;</td>\n";
							$path.="\t\t<td>&nbsp;</td>\n";
							$path.=$conex;
							$end=true;
						}
					}else {
						//End of path
						$path.="\n\t\t<td></td>\n\t\t<td></td>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t</tr>\n";
						$end=true;
					}
				}
			}
			//key
			$path.="\t<tr>\n\t\t<td colspan=6>&nbsp;</td>\n\t</tr>";
			$path.="\t<tr>\n";
			$path.="\t\t<td class=\"right\" colspan=2><img src=\"images/leyendaf.png\" alt=\"\"></td>\n";
			$path.="\t\t<td class=\"left\" colspan=4>&nbsp;&nbsp;".__("Front Connection")."</td>\n";
			$path.="\t</tr>\n";
			$path.="\t<tr>\n";
			$path.="\t\t<td class=\"right\" colspan=2><img src=\"images/leyendar.png\" alt=\"\"></td>\n";
			$path.="\t\t<td class=\"left\" colspan=4>&nbsp;&nbsp;".__("Rear Connection")."</td>\n";
			$path.="\t</tr>\n";
			
			//End of path table
			$path.="\t<tr>\n\t\t<td colspan=6>&nbsp;</td>\n\t</tr></table></div>";
		
			// need to add an additional check for permission here if they can write
			if(!isset($_GET['pathonly'])){
				//Delete Form
				$path.= "<form method=\"POST\">\n";
				$path.= "<br>\n"; 
				$path.= "<div>\n";
				//PATH INFO
				$path.=$form_eliminar;	
				$path.= "	<button type=\"submit\" name=\"action\" value=\"delete\">".__("Delete front connections in DataBase")."</button>\n";
				$path.= "</div>\n";
				$path.= "</form>\n";
			}
			$path.= "</div>\n";
		}	
	}

// Slight style adjustment that css can't handle on its own
$path.="<script type=\"text/javascript\">
	$('table#parcheos table tr + tr > td + td:has(table)').css('background-color','transparent');
</script>";

if(isset($_GET['pathonly'])){
	if(isset($_GET['print'])){
		echo '<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
</head>
<body>';
	}
	echo $path;
	echo $status;
	exit;
}
		
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript">
	$(document).ready(function(){
		var cabl=$('<div>');
		var cabs=$('<div>');
		var cabr=$('<div>').append(cabl).append(cabs);
		var devl=cabl.clone();
		var devs=cabs.clone();
		var devr=$('<div>').append(devl).append(devs);
		var porl=cabl.clone();
		var pors=cabs.clone();
		var porr=$('<div>').append(porl).append(pors);
		var select=$('<select>');
		var opt=$('<option>');
		$('.main fieldset select').change(function(e){
			$.post('pathmaker.php',({dc: $(this).val()})).done(function(data){
				var s=select.clone();
				s.children().detach();
				s.append(opt.clone());
				$.each(data, function(i,cab){
					var o=opt.clone().val(cab.CabinetID).text(cab.Location);
					s.append(o);
				});
				s.change(function(e){
					$.post('pathmaker.php',({cab: $(this).val()})).done(function(data){
						var ds=select.clone();
						ds.children().detach();
						ds.append(opt.clone()).attr('name','deviceid');
						$.each(data, function(i,dev){
							var o=opt.clone().val(dev.DeviceID).text(dev.Label);
							ds.append(o);
						});
						ds.change(function(e){
							$.post('pathmaker.php',({dev: $(this).val()})).done(function(data){
								select.children().detach();
								select.append(opt.clone()).attr('name','portnumber');
								$.each(data, function(i,por){
									por.Label=(por.Label=='')?Math.abs(por.PortNumber):por.Label;
									var o=opt.clone().val(por.PortNumber).text(por.Label);
									select.append(o);
								});
								porl.text('Port');
								pors.html(select.change());
								porr.insertAfter($(e.target).parent('div').parent('div'));
							});
						});
						devl.text('Device');
						devs.html(ds.change());
						devr.insertAfter($(e.target).parent('div').parent('div'));
					});
				});
				cabl.text('Cabinet');
				cabs.html(s.change());
				cabr.insertAfter($(e.target).parent('div').parent('div'));
			});
		});
	});
  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h3>',$status,'</h3>
<div class="center"><div><div>
<table id="crit_busc">
<tr><td>
<fieldset class="crit_busc">
		<legend>'.__("Search by path identifier").'</legend>
<form method="POST">
<div class="table">
<br>
<div>
   <div><label for="pathid">',__("Identifier"),'</label></div>
   <div><input type="text" name="pathid" id="pathid" size="20" value="',(isset($_POST['pathid'])?$_POST['pathid']:""),'"></div>
</div>
<br>
<div class="caption"><button type="submit" name="action" value="PathIdSearch">',__("Search"),'</button></div>
</div> <!-- END div.table -->
</form></fieldset></td>

<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>

<td><fieldset class=crit_busc>
		<legend>'.__("Search by label/port").'</legend>
		<form method="POST">
<div class="table">
	<div>
		<div><label for="dc-rear">',__("Data Center"),'</label></div>
		<div>'.builddclist('dc-rear').'</div>
	</div>
<br>
<div class="caption">';
echo '	 <button type="submit" name="action" value="DevicePortSearch">',__("Search"),'</button></div>';
echo '</div> <!-- END div.table -->';
echo '</form></fieldset></td></tr></table>';

?>
</div></div>
<?php echo "<br><br>",$path,"<br>"; 
echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
