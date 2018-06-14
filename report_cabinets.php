<?php
    /* Cabinet Report
       Prints the contents of selected cabinets
    */

    require_once( "db.inc.php" );
    require_once( "facilities.inc.php" );
    
    if(!$person->ReadAccess){
        // No soup for you.
        header('Location: '.redirect());
        exit;
    }

    $subheader = __("Cabinet Report");
    
    if (!isset($_REQUEST['action'])){
        $datacenter = new DataCenter();
        $dcList = $datacenter->GetDCList();
        $pwrPanel = new PowerPanel();
        $cabinet = new Cabinet();
?>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title>openDCIM Inventory Reporting</title>
    <link rel="stylesheet" href="css/inventory.php" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
    <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
    <!--[if lt IE 9]>
    <link rel="stylesheet"  href="css/ie.css" type="text/css" />
    <![endif]-->
    <script type="text/javascript" src="scripts/jquery.min.js"></script>
    <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
    <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
    <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
    <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

    <script>
        $(document).ready(function () {
            $('#selectall').click(function () {
             $('.selectedId').prop('checked', this.checked);
            });

            $('.selectedId').change(function () {
                var check = ($('.selectedId').filter(":checked").length == $('.selectedId').length);
                $('#selectall').prop("checked", check);
            });
        });
    </script>



</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php include( 'sidebar.inc.php' ); ?>
<div class="main">
<div class="center"><div>
<form method="post" id="panelform">

<?php
    if(@$_REQUEST['datacenterid'] == 0) {
?>
<div class="table">
    <div>
    <div><label for="datacenterid"><?php print __("Data Center")?>:</label></div>
    <div>
        <select id="datacenterid" name="datacenterid" onchange="this.form.submit();">
            <option value="0"><?php print __("Select data center")?></option>
<?php
    foreach($dcList as $dc){
        print "             <option value=\"$dc->DataCenterID\">$dc->Name</option>\n";
    }
?>
        </select>
    </div>

<?php
    } else {
        $datacenter->DataCenterID = $_REQUEST['datacenterid'];
        $datacenter->GetDataCenter();

        $cabinet->DataCenterID = $datacenter->DataCenterID;
        $DataCenterID =  $datacenter->DataCenterID;

        $cabList=array();

        $sql="SELECT * FROM fac_Cabinet WHERE fac_Cabinet.DataCenterID=$DataCenterID order by fac_Cabinet.Location;";

        foreach($dbh->query($sql) as $row){
            $cabList[$row["CabinetID"]]=Cabinet::RowToObject($row);
        }

        print "<input type=\"hidden\" name=\"datacenterid\" value=\"$datacenter->DataCenterID\">\n";
        print "<h3>".__("Choose cabinets to print in Data Center").": $datacenter->Name<br>\n";
        print "<input type=submit name=\"action\" value=\"".__("Generate")."\"><br>\n";
?>

<div class="table">
    <div style="border-bottom: 1px solid black;">
        <div><?php print __("Cabinet ID")?></div>
    </div>
<?php
    // because mpdf is so slow, selecting all cabinets in a big data center frequently times out - just disabling fo rnow
    //print "<div><div><input type=\"checkbox\" id=\"selectall\">".__("Select all")."</input></div></div>\n";
        foreach ( $cabList as $cab ) {
            print "<div><div><input type=\"checkbox\" class=\"selectedId\" id=\"selectedId\" name=\"cabinetid[]\" value=\"$cab->CabinetID\">$cab->Location</input></div></div>\n";
        }
    }        
?>
</div>
</form>
</div></div>
</div>
</div>
</body>
</html>
<?php
    } else {
    //
    //
    //  Begin Report Generation
    //
    //

    // grabbed these 3 functions from rowview.php and modified for use here
    function get_cabinet_owner_color($cabinet, &$deptswithcolor) {
      $cab_color = '';
      if ($cabinet->AssignedTo != 0) {
        $tempDept = new Department();
        $tempDept->DeptID = $cabinet->AssignedTo;
        $deptid = $tempDept->DeptID;
        $tempDept->GetDeptByID();
        if (strtoupper($tempDept->DeptColor) != "#FFFFFF") {
           $deptswithcolor[$cabinet->AssignedTo]["color"] = $tempDept->DeptColor;
           $deptswithcolor[$cabinet->AssignedTo]["name"]= $tempDept->Name;
           $cab_color = "class=\"dept$deptid\"";
        }
      }
      return $cab_color;
    }

    function renderUnassignedTemplateOwnership($noTemplFlag, $noOwnerFlag, $device) {
        $retstr=$noTemplate=$noOwnership='';
        if ($device->TemplateID == 0) {
            $noTemplate = '(T)';
            $noTemplFlag = true;
        }
        if ($device->Owner == 0) {
            $noOwnership = '(O)';
            $noOwnerFlag = true;
        }
        if ($noTemplFlag or $noOwnerFlag) {
            $retstr = '<span class="hlight">' . $noTemplate . $noOwnership . '</span>';
        }
        return array($noTemplFlag, $noOwnerFlag, $retstr);
    }

    function MakeCabinet($rear=false,$side=null) {
        global $cab_color, $cabinet, $device, $body, $currentHeight, $heighterr,
                $devList, $templ, $tempDept, $backside, $deptswithcolor, $tempDept,
                $totalWeight, $totalWatts, $totalMoment, $zeroheight,
                $noTemplFlag, $noOwnerFlag;

        if ( $cabinet->U1Position == "Top" ) {
            $currentHeight = 1;
            array_reverse($devList);
        } else {
            $currentHeight=$cabinet->CabinetHeight;
        }

        // Determine which label to put on the rack, if any
        $rs="";
        if($rear){
            $rs=__("Rear");
        }
        if(!is_null($side)){
            $rs=__("Side");
        }
        $RearOrSide=($rs=="")?"":" ($rs)";
        $body.="<table class=\"items cabtable\">";
        $body.="<thead><tr><td id=\"cabid\" data-cabinetid=$cabinet->CabinetID colspan=2 $cab_color >".__("Cabinet")." $cabinet->Location$RearOrSide</td></tr>";
        $body.="<tr><td class=\"cabpos\">".__("Pos")."</td><td>".__("Device")."</td></tr></thead><tbody>";

        $heighterr="";
        while(list($dev_index,$device)=each($devList)){
            list($noTemplFlag, $noOwnerFlag, $highlight) =
                renderUnassignedTemplateOwnership($noTemplFlag, $noOwnerFlag, $device);
            if($device->Height<1 && !$rear){
                // empty html anchor for a line break
                $zeroheight.="\t\t\t$highlight $device->Label\n<a></a>";
            }

            if ((!$device->HalfDepth || !$device->BackSide)&&!$rear || (!$device->HalfDepth || $device->BackSide)&&$rear){
                $backside=($device->HalfDepth)?true:$backside;
                if ( $cabinet->U1Position == "Top" ) {
                    $devTop=$device->Position - $device->Height + 1;
                } else {
                    $devTop=$device->Position + $device->Height - 1;
                }

                $templ->TemplateID=$device->TemplateID;
                $templ->GetTemplateByID();

                $tempDept->DeptID=$device->Owner;
                $tempDept->GetDeptByID();

                // If a dept has been changed from white then it needs to be added to the stylesheet, legend, and device
                if(!$device->Reservation && strtoupper($tempDept->DeptColor)!="#FFFFFF"){
                    // Fill array with deptid and color so we can process the list once for the legend and style information
                    $deptswithcolor[$device->Owner]["color"]=$tempDept->DeptColor;
                    $deptswithcolor[$device->Owner]["name"]=$tempDept->Name;
                }
                $reserved="";
                if($device->Reservation==true){
                    $reserved=" reserved";
                }
                if ( $cabinet->U1Position == "Top" ) {
                    if($devTop>$currentHeight && $currentHeight>0){
                        for($i=$currentHeight;$i<$devTop;$i++){
                            $errclass=($i>$cabinet->CabinetHeight)?' error':'';
                            if($errclass!=''){$heighterr="yup";}
                            if($i==$currentHeight && $i<$cabinet->CabinetHeight){
                                $blankHeight=$devTop-$currentHeight;
                                if($devTop==-1){--$blankHeight;}
                                $body.="\t\t<tr><td class=\"cabpos freespace$errclass\">$i</td><td class=\"freespace\" rowspan=$blankHeight></td></tr>\n";
                            } else {
                                $body.="\t\t<tr><td class=\"cabpos freespace$errclass\">$i</td></tr>\n";
                                if($i==1){break;}
                            }
                        }
                    }
                    for($i=$devTop;$i<=$device->Position;$i++){
                        $errclass=($i>$cabinet->CabinetHeight)?' error':'';
                        if($errclass!=''){$heighterr="yup";}
                        if($i==$devTop){
                            // If we're looking at the side of the rack don't give any details but show the
                            // space as being occupied.
                            $sideview="";
                            if(!is_null($side)){
                                $picture=$text="";
                                $sideview=" blackout";
                            }else{
                                // Create the filler for the rack either text or a picture
                                #$picture=(!$device->BackSide && !$rear || $device->BackSide && $rear)?$device->GetDevicePicture():$device->GetDevicePicture("rear");
                                $picture="";
                                $devlabel=$device->Label.(((!$device->BackSide && $rear || $device->BackSide && !$rear) && !$device->HalfDepth)?"(".__("Rear").")":"");
                                $text="$highlight $devlabel";
                            }

                            // Put the device in the rack
                            $body.="\t\t<tr><td class=\"cabpos$reserved dept$device->Owner$errclass\">$i</td><td class=\"dept$device->Owner$reserved$sideview\" rowspan=$device->Height data-deviceid=$device->DeviceID>";
                            $body.=($picture)?$picture:$text;
                            $body.="</td></tr>\n";
                        }else{
                            $body.="\t\t<tr><td class=\"cabpos$reserved dept$device->Owner$errclass\">$i</td></tr>\n";
                        }
                    }
                } else {
                    if($devTop<$currentHeight && $currentHeight>0){
                        for($i=$currentHeight;($i>$devTop);$i--){
                            $errclass=($i>$cabinet->CabinetHeight)?' error':'';
                            if($errclass!=''){$heighterr="yup";}
                            if($i==$currentHeight && $i>1){
                                $blankHeight=$currentHeight-$devTop;
                                if($devTop==-1){--$blankHeight;}
                                $body.="\t\t<tr><td class=\"cabpos freespace$errclass\">$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
                            } else {
                                $body.="\t\t<tr><td class=\"cabpos freespace$errclass\">$i</td></tr>\n";
                                if($i==1){break;}
                            }
                        }
                    }
                    for($i=$devTop;$i>=$device->Position;$i--){
                        $errclass=($i>$cabinet->CabinetHeight)?' error':'';
                        if($errclass!=''){$heighterr="yup";}
                        if($i==$devTop){
                            // If we're looking at the side of the rack don't give any details but show the
                            // space as being occupied.
                            $sideview="";
                            if(!is_null($side)){
                                $picture=$text="";
                                $sideview=" blackout";
                            }else{
                                // Create the filler for the rack either text or a picture
                                #$picture=(!$device->BackSide && !$rear || $device->BackSide && $rear)?$device->GetDevicePicture():$device->GetDevicePicture("rear");
                                $picture="";
                                $devlabel=$device->Label.(((!$device->BackSide && $rear || $device->BackSide && !$rear) && !$device->HalfDepth)?"(".__("Rear").")":"");
                                $text="$highlight $devlabel";
                            }

                            // Put the device in the rack
                            $body.="\t\t<tr><td class=\"cabpos$reserved dept$device->Owner$errclass\">$i</td><td class=\"dept$device->Owner$reserved$sideview\" rowspan=$device->Height data-deviceid=$device->DeviceID>";
                            $body.=($picture)?$picture:$text;
                            $body.="</td></tr>\n";
                        }else{
                            $body.="\t\t<tr><td class=\"cabpos$reserved dept$device->Owner$errclass\">$i</td></tr>\n";
                        }
                    }
                }
                if ( $cabinet->U1Position == "Top" ) {
                    $currentHeight=$device->Position + 1;
                } else {
                    $currentHeight=$device->Position - 1;
                }
            }elseif(!$rear){
                $backside=true;
            }
        }

        // Fill in to the bottom
        if ( $cabinet->U1Position == "Top" ) {
            for ( $i=$currentHeight; $i<$cabinet->CabinetHeight+1; $i++ ) {
                if($i==$currentHeight){
                    $blankHeight=$cabinet->CabinetHeight - $currentHeight + 1;

                    $body.="\t\t<tr><td class=\"cabpos freespace\">$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
                }else{
                    $body.="\t\t<tr><td class=\"cabpos freespace\">$i</td></tr>\n";
                }                
            }
        } else {
            for($i=$currentHeight;$i>0;$i--){
                if($i==$currentHeight){
                    $blankHeight=$currentHeight;

                    $body.="\t\t<tr><td class=\"cabpos freespace\">$i</td><td class=\"freespace\" rowspan=$blankHeight>&nbsp;</td></tr>\n";
                }else{
                    $body.="\t\t<tr><td class=\"cabpos freespace\">$i</td></tr>\n";
                }
            }
        }

        $body.="</tbody></table>";
        reset($devList);
    }

    $cab = new Cabinet();
    $pdu = new PowerDistribution();
    $dev = new Device();
    $templ = new DeviceTemplate();
    $tempDept = new Department();
    $dc = new DataCenter();
    
    $dc->DataCenterID = intval( $_REQUEST['datacenterid'] );
    $dc->GetDataCenter();
    
    $skipNormal = false;

    if (isset( $_REQUEST["skipnormal"] ) ) {
        $skipNormal = $_REQUEST["skipnormal"];
    }
    if(isset($_POST['cabinetid'])){
        $cabArray=$_POST['cabinetid'];
    }
    if ( count( $cabArray ) > 0 ) {
        // Need to build an array of Panel Objects (what we got from input was just the IDs)
        $cabList = array();
        
        foreach ( $cabArray as $cabID ) {
            $cabCount = count( $cabList );
            $cabList[$cabCount] = new Cabinet();
            $cabList[$cabCount]->CabinetID = $cabID;
            $cabList[$cabCount]->GetCabinet();
        }
    } else {
        header('Location: '.redirect());
            exit;
    }
    // Now that we have a complete list of the cabinets, we need build the report
    // Loop through all the cabinets from the list and build a report

    $legend=$zeroheight=$body=$deptcolor="";
    $deptswithcolor=array();

    $head="\n<style type=\"text/css\">\n";

	// Set up the classes for color coding based upon status
	$dsList=DeviceStatus::getStatusList();

	foreach($dsList as $stat){
		if($stat->ColorCode != "#FFFFFF"){
			$stName=str_replace(' ','_',$stat->Status);
			$important=($stName == 'Reserved')?' !important':'';

			$head.="\t\t\t.$stName {background-color: {$stat->ColorCode}$important;}\n";
		}
	}
    foreach ( $cabList as $cabinet) {
        $dev->Cabinet=$cabinet->CabinetID;
        $devList=$dev->ViewDevicesByCabinet();
        $currentHeight=$cabinet->CabinetHeight;
        $cab_color=get_cabinet_owner_color($cabinet, $deptswithcolor);
        
        // build out the front and rear of the cabinet
        // these directly alter the $body variable
        // wrap it all in a table so both sides of a cabinet get on the same page

        $body .= "<table style=\"border:0px;\" width=\"100%\"><tr><td width=\"50%\" align=\"center\">"; 
        MakeCabinet();
        $body .= "</td><td width=\"50%\" align=\"center\">";
        MakeCabinet("rear");
        $body .= "</td></tr></table>";
    }  // Done with for each loop of cabinets

    if(!empty($deptswithcolor)) {
        foreach($deptswithcolor as $deptid=>$row) {
            $deptcolorheadstyle = ".dept$deptid {background-color: {$row['color']};}\n";
            $head.="table thead td$deptcolorheadstyle\n";
            $head.=$deptcolorheadstyle."\n";
        }
    }
    $head.="</style>\n";

    $reportHead=$head;
    $reportHTML=$body;

    // generate the report using the template
    include('template_mpdf_reports.inc.php');
}
?>

