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
	
	if(isset($_POST['bot_eliminar'])){
		for ($i=1;$i<$_POST['elem_path'];$i++){
			if ($_POST["DeviceType"][$i]!="Patch Panel" 
				&& $_POST["DeviceType"][$i+1]=="Patch Panel"
				&& !$_POST["Front"][$i+1]){
				$pc=new PatchConnection();
				$pc->PanelDeviceID=$_POST["DeviceID"][$i+1];
				$pc->PanelPortNumber=$_POST["PortNumber"][$i+1];
				$pc->RemoveFrontConnection($facDB);
				
			} elseif ($_POST["DeviceType"][$i]!="Patch Panel"
				&& $_POST["DeviceType"][$i+1]!="Patch Panel"){
				
				$sc=new SwitchConnection();
				$sc->SwitchDeviceID=$_POST["DeviceID"][$i+1];
				$sc->SwitchPortNumber=$_POST["PortNumber"][$i+1];
				$sc->RemoveConnection($facDB);
				
			} elseif ($_POST["DeviceType"][$i]=="Patch Panel"
				&& $_POST["Front"][$i]) {
				
				$pc=new PatchConnection();
				$pc->PanelDeviceID=$_POST["DeviceID"][$i];
				$pc->PanelPortNumber=$_POST["PortNumber"][$i];
				$pc->RemoveFrontConnection($facDB);
			}
		}
		$status.="Eliminada";
	}
	
	if(isset($_POST['action']) || isset($_GET['pathid']) || (isset($_GET['deviceid']) && isset($_GET['portnumber']))){
		//Search by deviceid/port
		if(isset($_GET['deviceid']) && $_GET['deviceid']!=''
			&& isset($_GET['portnumber']) && $_GET['portnumber']!=''){
				
			$pathid=_("Device")." ".intval($_GET['deviceid'])."-"._("Port")." ".intval($_GET['portnumber']);
			
			$cp=new ConnectionPath();
			$dev=new Device();
			
			$dev->DeviceID=intval($_GET['deviceid']);
			$dev->GetDevice($facDB);
			
			$cp->DeviceID=intval($_GET['deviceid']);
			$cp->PortNumber=intval($_GET['portnumber']);
			$cp->DeviceType=$dev->DeviceType;
			$cp->Front=true;
				
			if (!$cp->GotoHeadDevice($facDB)){
				$status="<blink>"._("There is a loop in the port")."</blink>";
			} 
		}
		
		//Search by label/port
		elseif(isset($_POST['label']) && $_POST['label']!=''
			&& isset($_POST['port']) && $_POST['port']!=''
			&& $_POST['action']=="DevicePortSearch"){

			//Remove control characters tab, enter, etc
			$label=preg_replace("/[[:cntrl:]]/","",$_POST['label']);
			//Remove any extra quotes that could get passed in from some funky js or something
			$label=str_replace(array("'",'"'),"",$label);
			
			//Construyo la lista para el dispositivo
			$dev=new Device();
			$dev->Label=$label;
			$devList=$dev->SearchDevicebyLabel($facDB);

			if (isset($_POST['devid']) && $_POST['devid']!=0 &&
				isset($_POST['label_ant']) && $_POST['label_ant']==$_POST['label']){
				//by ID1
				$cp=new ConnectionPath();
				$cp->DeviceID=intval($_POST['devid']);
				$cp->PortNumber=intval($_POST['port']);
				$cp->DeviceType=$devList[$cp->DeviceID]->DeviceType;
				$cp->Front=true;
				//label of devid
				$label=$devList[$cp->DeviceID]->Label;
				//intento irme al principio del path
				if (!$cp->GotoHeadDevice($facDB)){
					//$status="<blink>"._("There is a loop in this port")."</blink>";
					$status="<blink>Hay un bucle en ese puerto</blink>";
				} 
			}else{ //no devid1 or changed label
				//por el label
				if (count($devList)==0){
					//$status=_("Not found the device")." '".$label1."'";
					$status="No encontrado el dispositivo '".$label1."'";
				}
				elseif(count($devList)>1){
					//Varios dev1
					//$status=_("There are several devices with this label").".<br>". _("Please, select a device from list").".";
					$status="Hay varios dispositivos que contienen ese nemónico.<br>Seleccione uno de la lista correspondiente.";
					//creo una lista con las posibilidades un combo
				}else {
					$cp=new ConnectionPath();
					$keys=array_keys($devList);
					$cp->DeviceID=$keys[0];
					$cp->PortNumber=intval($_POST['port']);
					$cp->DeviceType=$devList[$cp->DeviceID]->DeviceType;
					$cp->Front=true;
					//label of devid
					$label=$devList[$cp->DeviceID]->Label;
					
					//intento irme al principio del path
					if (!$cp->GotoHeadDevice($facDB)){
						//$status="<blink>"._("There is a loop in this port")."</blink>";
						$status="<blink>Hay un bucle en ese puerto</blink>";
					} 
				}
			}
				
			//$pathid=$label."["._("Port").": ".intval($_POST['port'])."]";
			$pathid=$label."[Puerto: ".intval($_POST['port'])."]";
		}
		
		//Search by path identifier (in "notes" field)
		elseif(isset($_POST['pathid']) && $_POST['pathid']!='' && $_POST['action']=="PathIdSearch" 
			|| isset($_GET['pathid']) && $_GET['pathid']!=''){
			$status="";
			if (isset($_GET['pathid'])) {
				$pathid=intval( $_GET['pathid'] );
			}else{ 
				$pathid=intval( $_POST['pathid'] );
			}
			
			
			$sql = "SELECT PanelDeviceID AS DeviceID,
							PanelPortNumber AS port,
							'Patch Panel' AS DeviceType 
					FROM fac_patchconnection
					WHERE FrontNotes ='".$pathid."'
					UNION
					SELECT SwitchDeviceID AS DeviceID,
						SwitchPortNumber AS port,
						'Switch' AS DeviceType 
					FROM fac_switchconnection
					WHERE Notes ='".$pathid."'";
			$result = mysql_query( $sql, $facDB );
			
			if (mysql_num_rows($result)==0){
				$status="No encontrado";
			} else {
				$row = mysql_fetch_array( $result );
				
				$cp=new ConnectionPath();
				$cp->DeviceID=$row["DeviceID"];
				$cp->PortNumber=$row["port"];
				$cp->DeviceType=$row["DeviceType"];
				$cp->Front=true;
				
				if (!$cp->GotoHeadDevice($facDB)){
					$status="<blink>"._("There is a loop in the port")."</blink>";
				} 
			}
		}
		else{
			$status="<blink>"._("Error")."</blink>";
		}
		
		if ($status==""){
			
			$path.="<div style='text-align: center;'>";
			$path.="<div style='font-size: 1.5em;'>"._("Path of ").$pathid."</div>\n";

			//Path Table
			$path.="<table id=parcheos>\n\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr>\n\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td>";
			
			$dev=new Device();
			$end=false;
			$elem_path=0;
			$form_eliminar="";
				
			while (!$end) {
				//first device
				//get the device
				$dev->DeviceID=$cp->DeviceID;
				$dev->GetDevice( $facDB );
				$elem_path++;
				$form_eliminar.="<input type='hidden' name='DeviceID[".$elem_path."]' value='".$cp->DeviceID."'>\n";
				$form_eliminar.="<input type='hidden' name='PortNumber[".$elem_path."]' value='".$cp->PortNumber."'>\n";
				$form_eliminar.="<input type='hidden' name='DeviceType[".$elem_path."]' value='".$cp->DeviceType."'>\n";
				$form_eliminar.="<input type='hidden' name='Front[".$elem_path."]' value='".$cp->Front."'>\n";
				
				//If this device is the first and is a panel, I put it to the right position freeing the left
				if ($primero && $dev->DeviceType=="Patch Panel"){
					$path.="</td>\n\t\t<td></td>";
					
					//In connection type
					$tipo_con=($cp->Front)?"r":"f";
					
					//half hose
					$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a2".$tipo_con.".png) no-repeat center top;'></td>\n";
					
					//Out connection type
					$tipo_con=($cp->Front)?"f":"r";
					
					//Can the path continue?
					if ($dev->DeviceType=="Patch Panel"){
						$path.="\n\t\t<td  style='background: #FFF url(images/b1".$tipo_con.".png) no-repeat left bottom;'>";
					}
					else{
						$path.="\n\t\t<td>";
					}
				
					//I get device Lineage (for multi level chassis)
					$devList=array();
					$devList=$dev->GetDeviceLineage($facDB);
					
					//Device table
					$path.="\n\t\t\t<table class=disp align=left>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
					
					//Cabinet
					$cab=new Cabinet();
					$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
					$cab->GetCabinet($facDB);
					$path.=_("Cabinet").": <a href='cabnavigator.php?cabinetid=".$cab->CabinetID."'>".$cab->Location."</a>";
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
						$path.=str_repeat("\t",$t)."<td nowrap>Slot:".$devList[$i]->Position."</td>\n";
					}
					
					//device
					$path.=str_repeat("\t",$t--)."<td style='background-color: yellow;' nowrap>".
							"<a href='devices.php?deviceid=".$dev->DeviceID."'>".$dev->Label."</a>
							<br>"._("Port").": ".$cp->PortNumber."</td>\n";
					$path.=str_repeat("\t",$t--)."</tr>\n";
					
					//Ending device table
					for ($i=sizeof($devList); $i>2; $i--){
						$path.=str_repeat("\t",$t--)."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
						$path.=str_repeat("\t",$t--)."</table>\n";
					}
					$path.=str_repeat("\t",$t--)."</td>\n";
					$path.=str_repeat("\t",$t--)."</tr>\n";
					if ($cp->Front){
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

					//connection for next row
					$conex="\t\t<td style='height:30px; width: 25px; background: #FFF url(images/b3".$tipo_con.".png) no-repeat center;'>&nbsp;</td>\n";
					$conex.="\t\t<td style='height:30px; background: #FFF url(images/b2".$tipo_con.".png) no-repeat left;'>&nbsp;</td>\n\t</tr>\n";;
					if ($cp->GotoNextDevice($facDB)) {
						$tipo_con=($cp->Front)?"r":"f";  //In connection type

						//row separation between patch rows: draw the connection between panels
						$path.="\t<tr>\n\t\t<td></td><td style='height:30px; background: #FFF url(images/b4".$tipo_con.".png) no-repeat right;'>&nbsp;</td>\n"; 
						$path.="\t\t<td style='height:30px; width: 25px; background: #FFF url(images/b3".$tipo_con.".png) no-repeat center;'>&nbsp;</td>\n";
						$path.=$conex;
						$path.="\n<tr>\n\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n\t\t<td align=right>";
					} else {
						//End of path
						$end=true;
					}
					
				} else {
				//A row with two devices
					//I get device Lineage (for multi level chassis)
					$devList=array();
					$devList=$dev->GetDeviceLineage($facDB);
					
					//Device table
					$path.="\n\t\t\t<table class=disp align=right>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
					
					//cabinet
					$cab=new Cabinet();
					$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
					$cab->GetCabinet($facDB);
					$path.=_("Cabinet").": <a href='cabnavigator.php?cabinetid=".$cab->CabinetID."'>".$cab->Location."</a>";
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
						$path.=str_repeat("\t",$t)."<td nowrap>Slot:".$devList[$i]->Position."</td>\n";
					}
					
					//Device
					$path.=str_repeat("\t",$t--)."<td style='background-color: yellow;' nowrap>".
							"<a href='devices.php?deviceid=".$dev->DeviceID."'>".$dev->Label.
							"</a><br>"._("Port").": ".$cp->PortNumber."</td>\n";
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
						$tipo_con=($cp->Front)?"f":"r";
						
						$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a1".$tipo_con.".png) no-repeat center top;'></td>\n";
					}
					//next device, if exist
					if ($cp->GotoNextDevice($facDB)) {
						$elem_path++;
						$form_eliminar.="<input type='hidden' name='DeviceID[".$elem_path."]' value='".$cp->DeviceID."'>\n";
						$form_eliminar.="<input type='hidden' name='PortNumber[".$elem_path."]' value='".$cp->PortNumber."'>\n";
						$form_eliminar.="<input type='hidden' name='DeviceType[".$elem_path."]' value='".$cp->DeviceType."'>\n";
						$form_eliminar.="<input type='hidden' name='Front[".$elem_path."]' value='".$cp->Front."'>\n";
						
						$dev->DeviceID=$cp->DeviceID;
						$dev->GetDevice( $facDB );
						
						//In connection type
						$tipo_con=($cp->Front)?"r":"f";
						
						//half hose
						$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a2".$tipo_con.".png) no-repeat center top;'></td>\n";
						
						//Out connection type
						$tipo_con=($cp->Front)?"f":"r";
						
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
						$devList=$dev->GetDeviceLineage($facDB);
						
						//Device Table
						$path.="\n\t\t\t<table class=disp align=left>\n\t\t\t\t<tr>\n\t\t\t\t\t<th colspan=2>";
						
						//cabinet
						$cab=new Cabinet();
						$cab->CabinetID=$devList[sizeof($devList)]->Cabinet;
						$cab->GetCabinet($facDB);
						$path.=_("Cabinet").": <a href='cabnavigator.php?cabinetid=".$cab->CabinetID."'>".$cab->Location."</a>";
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
							$path.=str_repeat("\t",$t)."<td nowrap>Slot:".$devList[$i]->Position."</td>\n";
						}
						
						//device
						$path.=str_repeat("\t",$t--)."<td style='background-color: yellow;' nowrap>".
								"<a href='devices.php?deviceid=".$dev->DeviceID."'>".$dev->Label.
								"</a><br>"._("Port").": ".$cp->PortNumber."</td>\n";
						$path.=str_repeat("\t",$t--)."</tr>\n";
						
						//ending device table
						for ($i=sizeof($devList); $i>1; $i--){
							$path.=str_repeat("\t",$t--)."</table>\n";
							$path.=str_repeat("\t",$t--)."</td>\n";
							$path.=str_repeat("\t",$t--)."</tr>\n";
						}
						
						if ($cp->Front){
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
	
						if ($cp->GotoNextDevice($facDB)) {
							$tipo_con=($cp->Front)?"r":"f";  //In connection type
	
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
				}
			}
			//key
			$path.="\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr>";
			$path.="\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t\t<td style='background: #FFF url(images/leyendaf.png) no-repeat right top;'></td>\n";
			$path.="\t\t<td colspan=3 align=left>&nbsp;&nbsp;"._("Conexión frontal")."</td>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t</tr>\n";
			$path.="\t<tr>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t\t<td style='background: #FFF url(images/leyendar.png) no-repeat right top;'></td>\n";
			$path.="\t\t<td colspan=3 align=left>&nbsp;&nbsp;"._("Conexión trasera")."</td>\n";
			$path.="\t\t<td>&nbsp;&nbsp;&nbsp;</td>\n";
			$path.="\t</tr>\n";
			
			//End of path table
			$path.="\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr></table></div>";
			
			//Delete Form
			$path.= "<form action='".$_SERVER["PHP_SELF"]."' method='POST'>\n";
			$path.= "<br>\n"; 
			$path.= "<div>\n";
			//PATH INFO
			$path.= "<input type='hidden' name='elem_path' value='".$elem_path."'>\n";
			$path.=$form_eliminar;	
			$path.= "	<button type='submit' name='bot_eliminar' value='delete'>"._("Delete connection in DataBase")."</button>\n";
			$path.= "</div>\n";
			$path.= "</form>\n";
			$path.= "</div>\n";
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
</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("End to end connection path"),'</h3>
<h3>',$status,'</h3>
<div class="center"><div><div>
<table id=crit_busc border=10>
<tr><td>
<fieldset class=crit_busc>
		<legend>'._("Search by path identifier").'</legend>
<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<br>
<div>
   <div><label for="pathid">',_("Identifier"),'</label></div>
   <div><input type="text" name="pathid" id="pathid" size="20" value=',(isset($_POST['pathid'])?$_POST['pathid']:""),'></div>
</div>
<br>
<div class="caption">';
echo '	 <button type="submit" name="action" value="PathIdSearch">',_("Search"),'</button></div>';
echo '</div> <!-- END div.table -->';
echo '</form></fieldset></td>';

echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';

echo '<td><fieldset class=crit_busc>
		<legend>'._("Search by label/port").'</legend>
		<form action="',$_SERVER["PHP_SELF"],'" method="POST">
<div class="table">
<div>
   <div><label for="label">',_("Label"),'</label></div>
   <div><input type="text" name="label" id="label" size="20" value=',(isset($_POST['label'])?$_POST['label']:""),'></div>
</div>
<div>
   <div><label for="port">',_("Port"),'</label></div>
   <div><input type="text" name="port" id="port" size="20" value='.(isset($_POST['port'])?$_POST['port']:"").'></div>
</div>
<br>
<div class="caption">';
echo '	 <button type="submit" name="action" value="DevicePortSearch">',_("Search"),'</button></div>';
echo '</div> <!-- END div.table -->';
echo '</form></fieldset></td></tr></table>';

?>
</div></div>
<?php echo "<br><br>",$path,"</div><br>"; 
echo '<a href="index.php">[ ',_("Return to Main Menu"),' ]</a>'; ?>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
