<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user = new User();

	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if(!$user->ContactAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	$status="";
	$path="";
	$pathid="";

	function builddclist($id=null){
		$dc=new DataCenter();
		$dcList=$dc->GetDCList();

		$id=(!is_null($id))?" name=\"$id\" id=\"$id\"":'';

		$dcpicklist="<select$id><option value=0></option>";
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
		
		displayjson($dev->ViewDevicesByCabinet());
	}

	if(isset($_POST['dev'])){
		$dp=new DevicePorts();
		$dp->DeviceID=$_POST['dev'];
		
		displayjson($dp->getPorts());
	}

	// AJAX - End
	
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
		if (isset($_POST['notes'])){
			$status.=__("Connection implemented")." (<a href='paths.php?pathid=".$_POST["notes"]."'>".$_POST['notes'];
			$status.="</a>)";
		}
		else {
			$status.=__("Connection implemented");
			$status.=" (<a href='paths.php?deviceid=".$_POST["DeviceID"][1]."&portnumber=".$_POST["PortNumber"][1]."'>";
			$dev=new Device();
			$dev->DeviceID=$_POST["DeviceID"][1];
			$dev->GetDevice();
			$status.=$dev->Label."[".$_POST["PortNumber"][1]."]---";
			$dev->DeviceID=$_POST["DeviceID"][$_POST['elem_path']];
			$dev->GetDevice();
			$status.=$dev->Label."[".$_POST["PortNumber"][$_POST['elem_path']]."]";
			$status.="</a>)";
		}
	}
	
	if(isset($_POST['bot_crear'])){
		if((isset($_POST['label1']) && $_POST['label1']!='' || isset($_POST['devid1']) && $_POST['devid1']!=0)
			&& isset($_POST['port1']) && $_POST['port1']!=''
			&& (isset($_POST['label2']) && $_POST['label2']!='' || isset($_POST['devid2']) && $_POST['devid2']!=0)
			&& isset($_POST['port2']) && $_POST['port2']!=''){				
			
			//INITIAL DEVICE

			//Remove control characters tab, enter, etc
			$label1=preg_replace("/[[:cntrl:]]/","",$_POST['label1']);
			//Remove any extra quotes that could get passed in from some funky js or something
			$label1=str_replace(array("'",'"'),"",$label1);
			
			//Search device
			$dev=new Device();
			$dev->Label=$label1;
			$devList1=$dev->SearchDevicebyLabel();
			
			if (isset($_POST['devid1']) && $_POST['devid1']!=0 &&
				isset($_POST['label1_ant']) && $_POST['label1_ant']==$_POST['label1']){
				//by ID1
				$pp=new PlannedPath();
				$pp->devID1=intval($_POST['devid1']);
				$pp->port1=intval($_POST['port1']);
				$label1=$devList1[$pp->devID1]->Label;
			}else{ 
				//by label1
				if (count($devList1)==0){
					$status=__("Initial device not found: ");
				}
				elseif(count($devList1)>1){
					$status=__("There are several devices with this label").".<br>". __("Please, select a device from list").".";
				}else {
					$pp=new PlannedPath();
					$keys=array_keys($devList1);
					$pp->devID1=$keys[0];
					$pp->port1=intval($_POST['port1']);
				}
			}
			
			//FINAL DEVICE
			
			//Remove control characters tab, enter, etc
			$label2=preg_replace("/[[:cntrl:]]/","",$_POST['label2']);
			//Remove any extra quotes that could get passed in from some funky js or something
			$label2=str_replace(array("'",'"'),"",$label2);
				
			//Search device
			$dev=new Device();
			$dev->Label=$label2;
			$devList2=$dev->SearchDevicebyLabel();
			
			if (isset($_POST['devid2']) && $_POST['devid2']!=0 &&
				isset($_POST['label2_ant']) && $_POST['label2_ant']==$_POST['label2'] &&
				isset($pp)){
				//By ID2
				$pp->devID2=intval($_POST['devid2']);
				$pp->port2=intval($_POST['port2']);
				$label2=$devList2[$pp->devID2]->Label;
			}else{ 
				//By label2
				if (count($devList2)==0){
					$status=__("Final device not found: ");
				}
				elseif(count($devList2)>1){
					$status=__("There are several devices with this label").".<br>". __("Please, select a device from list").".";
				} elseif (isset($pp)) {
					$keys=array_keys($devList2);
					$pp->devID2=$keys[0];
					$pp->port2=intval($_POST['port2']);
				}
			}
			
			if ($status==""){		
				$pathid=$label1."[".intval($_POST['port1'])."]---".$label2."[".intval($_POST['port2'])."]";
				
				//make path
				if ($pp->MakePath()){
					//Go to initial device on Path
					if (!$pp->GotoHeadDevice()){
						$status="<blink>".__("Path not inicialiced")."</blink>";
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
			if(!(isset($_POST['label1']) && $_POST['label1']!='' || isset($_POST['devid1']) && $_POST['devid1']!=0))
				$status=__("Initial device unspecified");
			elseif(!isset($_POST['port1']) || $_POST['port1']=='')
				$status=__("Initial device port unspecified");
			elseif(!(isset($_POST['label2']) && $_POST['label2']!='' || isset($_POST['devid2']) && $_POST['devid2']!=0))
				$status=__("Final device unspecified");
			elseif(!isset($_POST['port2']) || $_POST['port2']=='')
				$status=__("Final device unspecified");
			else
				$status=_("Unknown Error");
		}

		if ($status==""){
			
			$path.="<div style='text-align: center;'>";
			$path.="<div style='font-size: 1.2em;'>".__("Conexiones")." ".$pathid."</div>\n";

			//Path Table
			$path.="<table id=parcheos>\n\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr>\n\t<tr>\n";
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
				$path.="\n\t\t\t<table class=disp align=right>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
				
				//cabinet
				$cab=new Cabinet();
				$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
				$cab->GetCabinet();
				$path.=__("Cabinet").": <a href='cabnavigator.php?cabinetid=".$cab->CabinetID."'>".$cab->Location."</a>";
				$path.="</th>\n\t\t\t\t</tr>\n\t\t\t\t<tr>\n\t\t\t\t\t<td nowrap>U:".$devList[sizeof($devList)]->Position."</td>\n";
				
				//Lineage
				$t=5;
				for ($i=sizeof($devList); $i>1; $i--){
					$path.=str_repeat("\t",$t++)."<td>\n";
					$path.=str_repeat("\t",$t++)."<table class=disp>\n";
					$path.=str_repeat("\t",$t++)."<tr>\n";
					$path.=str_repeat("\t",$t--)."<th colspan=2>";
					$path.="<a href='devices.php?deviceid=".$devList[$i]->DeviceID."'>".$devList[$i]->Label."</a>";
					$path.="</th>\n";
					$path.=str_repeat("\t",$t)."</tr>\n";
					$path.=str_repeat("\t",$t++)."<tr>\n";
					$path.=str_repeat("\t",$t)."<td nowrap>Slot:".$devList[$i-1]->Position."</td>\n";
				}
				
				//Device
				$path.=str_repeat("\t",$t--)."<td style='background-color: yellow;' nowrap>".
						"<a href='devices.php?deviceid=".$dev->DeviceID."'>".$dev->Label.
						"</a><br>Puerto: ".abs($pp->PortNumber)."</td>\n";
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
					
					$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a1".$tipo_con.".png) no-repeat center top;'></td>\n";
				}
				//next device, if exist
				if ($pp->GotoNextDevice()) {
					$dev->DeviceID=$pp->DeviceID;
					$dev->GetDevice();
					
					//In connection type
					$tipo_con=($pp->PortNumber>0)?"r":"f";
					
					//half hose
					$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a2".$tipo_con.".png) no-repeat center top;'></td>\n";
					
					//Out connection type
					$tipo_con=($pp->PortNumber>0)?"f":"r";
					
					//Can I follow?
					if ($dev->DeviceType=="Patch Panel"){
						$path.="\n\t\t<td  style='background: #FFF url(images/b1".$tipo_con.".png) no-repeat left bottom;'>";
						// I prepare row separation between patch rows
						$conex="\t\t<td style='height:30px; width: 25px; background: #FFF url(images/b3".$tipo_con.".png) no-repeat center;'>&nbsp;</td>\n";
						$conex.="\t\t<td style='height:30px; background: #FFF url(images/b2".$tipo_con.".png) no-repeat left;'>&nbsp;</td>\n\t</tr>\n";;
					}
					else{
						$conex="";
						$path.="\n\t\t<td>";
					}
				
					//I get device Lineage (for multi level chassis)
					$devList=array();
					$devList=$dev->GetDeviceLineage();
					
					//Device Table
					$path.="\n\t\t\t<table class=disp align=left>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
					
					//cabinet
					$cab=new Cabinet();
					$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
					$cab->GetCabinet();
					$path.=__("Cabinet").": <a href='cabnavigator.php?cabinetid=".$cab->CabinetID."'>".$cab->Location."</a>";
					$path.="</th>\n\t\t\t\t</tr>\n\t\t\t\t<tr>\n\t\t\t\t\t<td nowrap>U:".$devList[sizeof($devList)]->Position."</td>\n";
					
					//lineage
					$t=5;
					for ($i=sizeof($devList); $i>1; $i--){
						$path.=str_repeat("\t",$t++)."<td>\n";
						$path.=str_repeat("\t",$t++)."<table class=disp>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t--)."<th colspan=2>";
						$path.="<a href='devices.php?deviceid=".$devList[$i]->DeviceID."'>".$devList[$i]->Label."</a>";
						$path.="</th>\n";
						$path.=str_repeat("\t",$t)."</tr>\n";
						$path.=str_repeat("\t",$t++)."<tr>\n";
						$path.=str_repeat("\t",$t)."<td nowrap>Slot:".$devList[$i-1]->Position."</td>\n";
					}
					
					//device
					$path.=str_repeat("\t",$t--)."<td style='background-color: yellow;' nowrap>".
							"<a href='devices.php?deviceid=".$dev->DeviceID."'>".$dev->Label.
							"</a><br>Puerto: ".abs($pp->PortNumber)."</td>\n";
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
						$path.=str_repeat("\t",$t)."<td colspan=2 style='padding: 0px 0px 0px 0px; border: 0px solid grey; 
							height:5px; background: #FFF url(images/b0f.png) no-repeat left;'>\n";
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
					}
					$path.=str_repeat("\t",$t--)."</table>\n";
					
					//ending row
					$path.="\t\t</td>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t</tr>\n";

					if ($pp->GotoNextDevice($facDB)) {
						$tipo_con=($pp->PortNumber>0)?"r":"f";  //In connection type

						//row separation between patch rows: draw the connection between panels
						$path.="\t<tr>\n\t\t<td></td><td style='height:30px; background: #FFF url(images/b4".$tipo_con.".png) no-repeat right;'>&nbsp;</td>\n"; 
						$path.="\t\t<td style='height:30px; width: 25px; background: #FFF url(images/b3".$tipo_con.".png) no-repeat center;'>&nbsp;</td>\n";
						$path.=$conex;
						$path.="\n<tr>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td align=right>";
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
			$path.="\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr>";
			$path.="\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t\t<td style='background: #FFF url(images/leyendaf.png) no-repeat right top;'></td>\n";
			$path.="\t\t<td colspan=3 align=left>&nbsp;&nbsp;".__("Front Connection")."</td>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t</tr>\n";
			$path.="\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t\t<td style='background: #FFF url(images/leyendar.png) no-repeat right top;'></td>\n";
			$path.="\t\t<td colspan=3 align=left>&nbsp;&nbsp;".__("Rear Connection")."</td>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t</tr>\n";
			
			//End of path table
			$path.="\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr></table>";
			
			//Implement Form
			$path.= "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>\n";
			$path.= "<br>\n"; 
			$path.= "<div>\n";
			//PATH INFO
			$path.= "\t<input type='hidden' name='elem_path' value='".count($pp->Path)."'>\n";
			for ($i=1;$i<=count($pp->Path);$i++){
				$path.="\t<input type='hidden' name='DeviceID[".$i."]' value='".$pp->Path[$i]["DeviceID"]."'>\n";
				$path.="\t<input type='hidden' name='PortNumber[".$i."]' value='".$pp->Path[$i]["PortNumber"]."'>\n";
			}
				
			$path.="\t<label for='notes'>".__("Notes")."</label>\n";
			$path.="\t<input type='text' name='notes' id='notes' size='20' value=''>\n";
			$path.="\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
			$path.="\t<button type='submit' name='bot_implementar' value='implement'>".__("Implement in DataBase")."</button>\n";
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
		$('#dc-front').change(function(e){
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
						ds.append(opt.clone()).attr('name','devid1');
						$.each(data, function(i,dev){
							var o=opt.clone().val(dev.DeviceID).text(dev.Label);
							ds.append(o);
						});
						ds.change(function(e){
							$.post('',({dev: $(this).val()})).done(function(data){
								select.children().detach();
								select.append(opt.clone()).attr('name','port1');
								$.each(data, function(i,por){
									var o=opt.clone().val(por.PortNumber).text(por.PortNumber);
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
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',__("Creating an end to end conntecion"),'</h3>
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
		  	<div><label for="label">',__("Label"),'</label></div>
			<div><input type="text" name="label2" id="label2" size="20" value="',(isset($_POST['label2'])?$_POST['label2']:""),'"></div>
		</div>';
if (isset($devList2) && count($devList2)>1) {
	print "<div><div><input type='hidden' name='label2_ant' value='".(isset($_POST['label2'])?$_POST['label2']:"")."'></div></div>";
	print "		<div>
		   <div><label for='devid2'>".__("Devices")."</label></div>
		   <div><select name='devid2' id='devid2'>
		<div>";
	if (isset($_POST['devid2'])){
		print "		   <option value=0>".__("Select final device")."</option>";
		$devid2=$_POST['devid2'];
	}else{
		print "		   <option value=0 selected>".__("Select final device")."</option>";
		$devid2=0;
	}
	foreach($devList2 as $devRow ) {
		$selected=(($devid2 == $devRow->DeviceID)?" selected":"");
		$pos="[".(($devRow->ParentDevice>0)?"S":"U").((isset($devRow->BackSide) && $devRow->BackSide)?"T":"").$devRow->Position."] ";
		print "		   <option value=$devRow->DeviceID".$selected.">".$pos.$devRow->Label."</option>";
			}
	print "\t\t</select></div></div>";
}
echo'		<div>
		   <div><label for="port">',__("Port"),'</label></div>
		   <div><input type="text" name="port2" id="port2" size="20" value='.(isset($_POST['port2'])?$_POST['port2']:"").'></div>
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
</body>
</html>
