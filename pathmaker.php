<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$subheader=__("Creating an end to end connection");

	$status="";
	$path="";
	$pathid="";

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

		$dcpicklist="<select$id data-port=$idnum><option value=0></option>";
		foreach($dcList as $d){
			$dcpicklist.="<option value=$d->DataCenterID>$d->Name</option>";
		}
		$dcpicklist.='</select>';

		return $dcpicklist;
	}

	// AJAX - Start

	function displayjson($array){
		header('Content-Type: application/json');
		echo json_encode($array);
		exit;
	}

	if(isset($_POST['dc'])){
		$cab=new Cabinet();
		$cab->DataCenterID=$_POST['dc'];

		displayjson($cab->ListCabinetsByDC());
	}

	if(isset($_POST['cab'])){
		$dev=new Device();
		$dev->Cabinet=$_POST['cab'];

		// Filter devices by rights
		$devs=array();
		foreach($dev->ViewDevicesByCabinet(true) as $d){
			if($d->Rights=="Write"){
				$devs[]=$d;
			}
		}
		displayjson($devs);
	}

	if(isset($_POST['dev'])){
		$dp=new DevicePorts();
		$dp->DeviceID=$_POST['dev'];
		$dev=new Device();
		$dev->DeviceID=$dp->DeviceID;
		$dev->GetDevice();
		$ports=($dev->Rights=="Write")?$dp->getPorts():array();
		displayjson($ports);
	}

	// AJAX - End
	
	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	if(isset($_POST['bot_implementar'])){
		for ($i=1;$i<$_POST['elem_path'];$i++){
			if ($_POST["PortNumber"][$i]>0 && $_POST["PortNumber"][$i+1]<0) {
				$port1=new DevicePorts();
				$port1->DeviceID=$_POST["DeviceID"][$i];
				$port1->PortNumber=$_POST["PortNumber"][$i];
				$port1->Notes=(isset($_POST['notes'])?$_POST['notes']:"");
				$port2=new DevicePorts();
				$port2->DeviceID=$_POST["DeviceID"][$i+1];
				$port2->PortNumber=-$_POST["PortNumber"][$i+1];
				$port2->Notes=(isset($_POST['notes'])?$_POST['notes']:"");
				DevicePorts::makeConnection($port1,$port2);
			}
		}
		if(isset($_POST['notes'])){
			$status.=__("Connection implemented")." (<a href=\"paths.php?pathid={$_POST["notes"]}\">{$_POST['notes']}</a>)";
		}else{
			$status.=__("Connection implemented");
			$status.=" (<a href=\"paths.php?deviceid={$_POST["DeviceID"][1]}&portnumber={$_POST["PortNumber"][1]}\">";
			$dev=new Device();
			$dev->DeviceID=$_POST["DeviceID"][1];
			$dev->GetDevice();
			$status.="$dev->Label[{$_POST["PortNumber"][1]}]---";
			$dev->DeviceID=$_POST["DeviceID"][$_POST['elem_path']];
			$dev->GetDevice();
			$status.="$dev->Label[{$_POST["PortNumber"][$_POST["elem_path"]]}]</a>)";
		}
	}
	
	if(isset($_POST['bot_crear'])){
		if((isset($_POST['devid1']) && $_POST['devid1']!=0)
			&& isset($_POST['port1']) && $_POST['port1']!=''
			&& (isset($_POST['devid2']) && $_POST['devid2']!=0)
			&& isset($_POST['port2']) && $_POST['port2']!=''){				
			
			//INITIAL DEVICE

			//Search device
			$dev=new Device();
			$dev->DeviceID=intval($_POST['devid1']);
			
			if ($dev->GetDevice()){
				$pp=new PlannedPath();
				$pp->devID1=$dev->DeviceID;
				$pp->port1=intval($_POST['port1']);
				$label1=$dev->Label;
			}
			
			//FINAL DEVICE
				
			//Search device
			$dev=new Device();
			$dev->DeviceID=intval($_POST['devid2']);
			
			if ($dev->GetDevice() && isset($pp)){
				$pp->devID2=$dev->DeviceID;
				$pp->port2=intval($_POST['port2']);
				$label2=$dev->Label;
			}
			
			if ($status==""){		
				$pathid=$label1."[".$pp->port1."]---".$label2."[".$pp->port2."]";
				
				//make path
				if ($pp->MakePath()){
					//Go to initial device on Path
					if (!$pp->GotoHeadDevice()){
						$status="<blink>".__("Path not initialized")."</blink>";
					}
				} else {
					switch ($pp->PathError){
						case 1:
							$status="<blink>".__("Error getting initial device data")."</blink>";
							break;
						case 2:
							$status="<blink>".__("Error: initial device is a panel")."</blink>";
							break;
						case 3:
							$status="<blink>".__("Error getting initial device port data")."</blink>";
							break;
						case 4:
							$status="<blink>".__("Error: initial device port is already connected")."</blink>";
							break;
						case 5:
							$status="<blink>".__("Error getting final device data")."</blink>";
							break;
						case 6:
							$status="<blink>".__("Error: final device is a panel")."</blink>";
							break;
						case 7:
							$status="<blink>".__("Error getting final device port data")."</blink>";
							break;
						case 8:
							$status="<blink>".__("Error: final device port is already connected")."</blink>";
							break;
						case 9:
							$status="<blink>".__("Path not found")."</blink>";
							break;
					}		
				}
			}
		} else {
			if(!(isset($_POST['devid1']) && $_POST['devid1']!=0)){
				$status=__("Initial device unspecified");
			}elseif(!isset($_POST['port1']) || $_POST['port1']==''){
				$status=__("Initial device port unspecified");
			}elseif(!(isset($_POST['devid2']) && $_POST['devid2']!=0)){
				$status=__("Final device unspecified");
			}elseif(!isset($_POST['port2']) || $_POST['port2']==''){
				$status=__("Final device unspecified");
			}else{
				$status=__("Unknown Error");
			}
		}

		if ($status==""){
			
			$path.="<div style='text-align: center;'>";
			$path.="<div style='font-size: 1.2em;'>".__("Connections")." $pathid</div>\n";

			//Path Table
			$path.="<table id=parcheos>\n\t<tr>\n\t\t<td colspan=6>&nbsp;</td>\n\t</tr>\n\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td>";
			
			$dev=new Device();
			$end=false;
			$elem_path=0;
				
			while (!$end) {
				//first device
				//get the device
				$dev->DeviceID=$pp->DeviceID;
				$dev->GetDevice();
				$elem_path++;
				
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
				$path.=str_repeat("\t",$t--)."<td>".
						"<a href=\"devices.php?DeviceID=$dev->DeviceID\">$dev->Label".
						"</a><br>".__("Port").": ".abs($pp->PortNumber)."</td>\n";
				$path.=str_repeat("\t",$t--)."</tr>\n";
				$path.=str_repeat("\t",$t--)."</table>\n";
				
				//ending device table
				for ($i=sizeof($devList); $i>1; $i--){
					$path.=str_repeat("\t",$t--)."</td>\n";
					$path.=str_repeat("\t",$t--)."</tr>\n";
					$path.=str_repeat("\t",$t--)."</table>\n";
				}
				
				$path.="\t\t</td>";
				
				if ($elem_path==1  || $dev->DeviceType=="Patch Panel"){
					//half hose
					//Out connection type
					$tipo_con=($pp->PortNumber>0)?"f":"r";
					
					$path.="\n\t\t<td class=\"$tipo_con-left\"></td>\n";
				}
				//next device, if exist
				if ($pp->GotoNextDevice()) {
					$dev->DeviceID=$pp->DeviceID;
					$dev->GetDevice();
					
					//In connection type
					$tipo_con=($pp->PortNumber>0)?"r":"f";
					
					//half hose
					$path.="\n\t\t<td class=\"$tipo_con-right\"></td>\n";
					
					//Out connection type
					$tipo_con=($pp->PortNumber>0)?"f":"r";
					
					//Can I follow?
					if ($dev->DeviceType=="Patch Panel"){
						$path.="\n\t\t<td class=\"connection-$tipo_con-1\">";
						// I prepare row separation between patch rows
						$conex="\t\t<td class=\"connection-$tipo_con-3\">&nbsp;</td>\n";
						$conex.="\t\t<td class=\"connection-$tipo_con-2\">&nbsp;</td>\n\t</tr>\n";
					}
					else{
						$conex="";
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
						$path.="<a href=\"devices.php?DeviceID={$devList[$i]->DeviceID}\">{$devList[$i]->Label}</a>";
						$path.="</th>\n";
						$path.=str_repeat("\t",$t)."</tr>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t)."<td>Slot:{$devList[$i-1]->Position}</td>\n";
					}
					
					//device
					$path.=str_repeat("\t",$t--)."<td>".
							"<a href=\"devices.php?DeviceID=$dev->DeviceID\">$dev->Label".
							"</a><br>".__("Port").": ".abs($pp->PortNumber)."</td>\n";
					$path.=str_repeat("\t",$t--)."</tr>\n";
					
					//ending device table
					for ($i=sizeof($devList); $i>1; $i--){
						$path.=str_repeat("\t",$t--)."</table>\n";
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
					}
					
					if ($pp->PortNumber>0){
						$t++;
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t)."<td colspan=2 class=\"base-f\">\n";
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
					}
					$path.=str_repeat("\t",$t--)."</table>\n";
					
					//ending row
					$path.="\t\t</td>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t</tr>\n";

					if ($pp->GotoNextDevice()) {
						$tipo_con=($pp->PortNumber>0)?"r":"f";  //In connection type

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
				$primero=false;
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
			$path.="\t<tr>\n\t\t<td colspan=6>&nbsp;</td>\n\t</tr></table>";
			
			//Implement Form
			$path.= "<form action=\"{$_SERVER["PHP_SELF"]}\" method=\"POST\">\n";
			$path.= "<br>\n"; 
			$path.= "<div>\n";
			//PATH INFO
			$path.= "\t<input type=\"hidden\" name=\"elem_path\" value=".count($pp->Path).">\n";
			for ($i=1;$i<=count($pp->Path);$i++){
				$path.="\t<input type=\"hidden\" name=\"DeviceID[$i]\" value=\"{$pp->Path[$i]["DeviceID"]}\">\n";
				$path.="\t<input type=\"hidden\" name=\"PortNumber[$i]\" value=\"{$pp->Path[$i]["PortNumber"]}\">\n";
			}
				
			$path.="\t<label for=\"notes\">".__("Notes")."</label>\n";
			$path.="\t<input type=\"text\" name=\"notes\" id=\"notes\" size=20 value=\"\">\n";
			$path.="\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
			$path.="\t<button type=\"submit\" name=\"bot_implementar\" value=\"implement\">".__("Implement in DataBase")."</button>\n";
			$path.="</div>\n";
			$path.="</form>\n";
			$path.="</div>\n";
		}	
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
		$('.main fieldset select').each(function(){
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
			$(this).change(function(e){
				var port=$(this).data('port');
				$.post('',({dc: $(this).val()})).done(function(data){
					var s=select.clone();
					s.children().detach();
					s.append(opt.clone());
					$.each(data, function(i,cab){
						var o=opt.clone().val(cab.CabinetID).text(cab.Location);
						s.append(o);
					});
					s.change(function(e){
						$.post('',({cab: $(this).val()})).done(function(data){
							var ds=select.clone();
							ds.children().detach();
							ds.append(opt.clone()).attr('name','devid'+port);
							$.each(data, function(i,dev){
								var o=opt.clone().val(dev.DeviceID).text(dev.Label);
								ds.append(o);
							});
							ds.change(function(e){
								$.post('',({dev: $(this).val()})).done(function(data){
									select.children().detach();
									select.append(opt.clone()).attr('name','port'+port);
									$.each(data, function(i,por){
										var o=opt.clone().val(por.PortNumber).text(por.Label);
										select.append(o);
									});
									porl.text('<?php echo __("Port");?>');
									pors.html(select.change());
									porr.insertAfter($(e.target).parent('div').parent('div'));
								});
							});
							devl.text('<?php echo __("Device");?>');
							devs.html(ds.change());
							devr.insertAfter($(e.target).parent('div').parent('div'));
						});
					});
					cabl.text('<?php echo __("Cabinet");?>');
					cabs.html(s.change());
					cabr.insertAfter($(e.target).parent('div').parent('div'));
				});
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
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<table id=crit_busc>
<tr><td>
<fieldset class=crit_busc>
	<legend>'.__("Initial device").'</legend>
	<div class="table">
		<div>
		  	<div><label for="dc-front">',__("Data Center"),'</label></div>
			<div>'.builddclist('dc-front').'</div>
		</div>
	</div>
</fieldset>
</td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td>
<fieldset class=crit_busc>
	<legend>'.__("Final device").'</legend>
	<div class="table">
		<div>
		  	<div><label for="dc-rear">',__("Data Center"),'</label></div>
			<div>'.builddclist('dc-rear').'</div>
		</div>
	</div>
</fieldset>
</td></tr></table>';
echo '<div style="text-align: center;">';
echo '<button type="submit" name="bot_crear" value="makepath">',__("Make Path"),'</button></div>';

echo '</form>';
?>
</div></div>
<?php echo "<br><br>",$path,"</div><br>";
//print_r($_POST);
//print "<br>"; 
echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
<script type="text/javascript">
	$('table#parcheos table tr + tr > td + td:has(table)').css('background-color','transparent');
</script>
</body>
</html>
