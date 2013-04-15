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
				
			if (isset($_GET['deviceid'])) {
				$deviceid=intval( $_GET['deviceid'] );
			}else{ 
				$deviceid=intval( $_POST['pathid'] );
			}

			$pathid=_("Device")." '".$_POST['label']."'-"._("Port")." ".intval($_POST['port']);
			
			//Search device by label
			$sql = "SELECT *
					FROM fac_device
					WHERE Label LIKE '".$_POST['label']."'";
			$result = mysql_query( $sql, $facDB );
			
			if (mysql_num_rows($result)==0){
				$status=_("Not found");
			}
			elseif (mysql_num_rows($result)>1){
				$status=_("There are several devices labeled ").$_POST['label'];
			} else {
				$row = mysql_fetch_array( $result );
				$cp=new ConnectionPath();
				$cp->DeviceID=$row["DeviceID"];
				$cp->PortNumber=$_POST['port'];
				$cp->DeviceType=$row["DeviceType"];
				$cp->Front=true;
				
				if (!$cp->GotoHeadDevice($facDB)){
					$status="<blink>"._("There is a loop in the port")."</blink>";
				} 
			}
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
					WHERE FrontNotes LIKE '%PATH(".$pathid.")%'
					UNION
					SELECT SwitchDeviceID AS DeviceID,
						SwitchPortNumber AS port,
						'Switch' AS DeviceType 
					FROM fac_switchconnection
					WHERE Notes LIKE '%PATH(".$pathid.")%'";
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
			$primero=true;
				
			while (!$end) {
				$dev->DeviceID=$cp->DeviceID;
				$dev->GetDevice( $facDB );
				
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
					
					if ($primero || $dev->DeviceType=="Patch Panel"){
						//half hose
						//Out connection type
						$tipo_con=($cp->Front)?"f":"r";
						
						$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a1".$tipo_con.".png) no-repeat center top;'></td>\n";
					}
					//next device, if exist
					if ($cp->GotoNextDevice($facDB)) {
						$dev->DeviceID=$cp->DeviceID;
						$dev->GetDevice( $facDB );
						
						//In connection type
						$tipo_con=($cp->Front)?"r":"f";
						
						//half hose
						$path.="\n\t\t<td style='width:25px; background: #FFF url(images/a2".$tipo_con.".png) no-repeat center top;'></td>\n";
						
						//Out connection type
						$tipo_con=($cp->Front)?"f":"r";
						
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
				$primero=false;
			}

			//End of path table
			$path.="\t<tr>\n\t\t<td>&nbsp;</td>\n\t</tr></table></div>";
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
