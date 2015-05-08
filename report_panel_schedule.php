<?php
    /* Power Panel Schedule report
       Prints out a power panel schedule for all selected Power Panels
    */

    require_once( "db.inc.php" );
    require_once( "facilities.inc.php" );
    
    if(!$person->ReadAccess){
        // No soup for you.
        header('Location: '.redirect());
        exit;
    }
    
    $subheader = __("Power Panel Schedule Report");
    
    if (!isset($_REQUEST['action'])){
        $datacenter = new DataCenter();
        $dcList = $datacenter->GetDCList();
        $pwrPanel = new PowerPanel();
        $cab = new Cabinet();
?>
<!doctype html>
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
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php include( 'sidebar.inc.php' ); ?>
<div class="main">
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="panelform">

<?php
	if ( @$_REQUEST['datacenterid'] == 0 ) {
		printf( "<tr><td>%s:</td><td>\n", __("Data Center") );
		printf( "<select name=\"datacenterid\" onChange=\"form.submit()\">\n" );
		printf( "<option value=\"\">%s</option>\n", __("Select data center") );
		
		foreach ( $dcList as $dc )
			printf( "<option value=\"%d\">%s</option>\n", $dc->DataCenterID, $dc->Name );
		
		printf( "</td></tr>" );
	} else {
		$datacenter->DataCenterID = $_REQUEST['datacenterid'];
		$datacenter->GetDataCenter();
		
		$sourceList = $pwrPanel->getSourcesByDataCenter( $datacenter->DataCenterID );
		printf( "<input type=\"hidden\" name=\"datacenterid\" value=\"%d\">\n", $datacenter->DataCenterID );
		
		printf( "<h3>%s: %s</h3>", __("Choose either power sources or panels to simulate for Data Center"), $datacenter->Name );
		
		printf( "<input type=submit name=\"action\" value=\"%s\"><br>\n", __("Generate") );
		
		printf( "<table border=1 align=center>\n" );
		printf( "<tr><th>%s</th><th>%s</th></tr>\n", __("Power Source"), __("Power Panel") );
		
		foreach ( $sourceList as $source ) {
			$pwrPanel->ParentPanelID = $source->PanelID;
			$panelList = $pwrPanel->getPanelListBySource();
			
			printf( "<tr><td><input type=\"checkbox\" name=\"sourceid[]\" value=\"%d\">%s</td>\n", $source->PanelID, $source->PanelLabel );
			
			printf( "<td><table>\n" );
			
			foreach ( $panelList as $panel )
				printf( "<tr><td><input type=\"checkbox\" name=\"panelid[]\" value=\"%d\">%s</td></tr>\n", $panel->PanelID, $panel->PanelLabel );
			
			printf( "</table></td></tr>\n" );
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
    //  Begin Report Generation
    //
    $pan = new PowerPanel();
    $pdu = new PowerDistribution();
    $dev = new Device();
    $cab = new Cabinet();
    $dept = new Department();
    $dc = new DataCenter();
    $pwrConn = new PowerConnection();
    //
    // Make some quick user defined sort comparisons for this report only
    //
    function compareCab( $a, $b ) {
        if ( $a->Location == $b->Location )
            return 0;
        return ( $a->Location > $b->Location ) ? +1 : -1;
    }

    $dc->DataCenterID = intval( $_REQUEST['datacenterid'] );
    $dc->GetDataCenter();

    $skipNormal = false;

    if (isset( $_REQUEST["skipnormal"] ) ) {
        $skipNormal = $_REQUEST["skipnormal"];
    }

    $pnlArray=array();

	if(isset($_POST['sourceid'])){
		$srcArray=$_POST['sourceid'];
	}
    if(isset($_POST['panelid'])){
        $pnlArray=$_POST['panelid'];
    }

	// Need to build an array of Panel Objects (what we got from input was just the IDs)
	$pnlList = array();

	if ( count( $srcArray ) > 0 ) {
		// Build an array of the Panels affected when the entire source goes down.
		// This will allow us to use one section of code to calculate effects of panels going down and use it for both cases.
		
		$pnlList = array();
		
		foreach ( $srcArray as $srcID ) {
			$pan->ParentPanelID = $srcID;
			
			$pnlList = array_merge( $pnlList, $pan->getPanelListBySource() );
		}
	} else {
		// Need to build an array of Panel Objects (what we got from input was just the IDs)
		$pnlList = array();
		
		foreach ( $pnlArray as $pnlID ) {
			$pnlCount = count( $pnlList );
			$pnlList[$pnlCount] = new PowerPanel();
			$pnlList[$pnlCount]->PanelID = $pnlID;
			$pnlList[$pnlCount]->GetPanel();
		}
	}
    
    //
    // Now that we have a complete list of the panels, we need get the panel schedules for them
    //
    // Loop through all the panels from the list and build a schedule
    $reportHTML="";
    foreach ( $pnlList as $panel) {
        $nextPole=1;
        $odd=$even=0;
        $pdu=new PowerDistribution();

        $pdu->PanelID = $panel->PanelID;
        $pduList=$pdu->GetPDUbyPanel();

		$ps = $panel->getPowerSource();
		
        $pduarray=array();
        foreach($pduList as $pnlPDU){
            if($pnlPDU->PanelID == $panel->PanelID){
                $pduarray[$pnlPDU->PanelPole][]=$pnlPDU;
            }elseif($pnlPDU->PanelID2 == $panel->PanelID){
                $pduarray[$pnlPDU->PanelPole2][]=$pnlPDU;
            }
        }
        $reportHTML.= '<table class="items" width="100%">';

        if($panel->NumberScheme=="Sequential"){
            $fill=0;
            $fillcolor="";
            $reportHTML.= '<thead>';
            $reportHTML.= '<tr><td colspan="2" width="100%"><h4>'.__("Panel Schedule for").':<br>';
            $reportHTML.= __("Data Center").': '.$dc->Name.'<br>';
            $reportHTML.= __("Power Source").': '.$ps->PanelLabel.'<br>';
            $reportHTML.= __("Power Panel").': '.$panel->PanelLabel.'</h4></td></tr>'; 
            $reportHTML.= '<tr><td width="5%">'.__("Pole").'</td>';
            $reportHTML.= '<td width="95%">'.__("Circuit").'</td></tr></thead><tbody>';
            while($nextPole <= $panel->NumberOfPoles){
                $reportHTML .= '<tr'.$fillcolor.'><td align="center">'.$nextPole.'</td>';
                // Someone input a pole number wrong and this one would have been skipped
                // store the value and deal with it later.
                if(isset($pduarray[$nextPole])&&$odd!=0){
                    foreach($pduarray[$nextPole] as $pduvar){
                    $errors[]="$pduvar->Label";
                    }
                }
                // Get info for pdu on this pole if it is populated.
                $lastCabinet=0;
                if($odd==0){
                    if(isset($pduarray[$nextPole])){
                        $pn="";
                        foreach($pduarray[$nextPole] as $pduvar) {
                            $cab->CabinetID=$pduvar->CabinetID;
                            $cab->GetCabinet(  );

                            if ($lastCabinet<>$pduvar->CabinetID)
                                $pn.="$cab->Location<br>";
                            // mpdf doesn't support text-indent inside of tables
                            $pn.="&nbsp;&nbsp;&nbsp;$pduvar->Label";
                            $lastCabinet=$pduvar->CabinetID;

                            switch($pduvar->BreakerSize){
                                case '3': $odd=3; break;
                                case '2': $odd=2; break;
                                default: $odd=0;
                            }
                        }
                    }else{
                        $pn="Available";
                    }
                    if($odd==0){
                        $reportHTML .= '<td>'.$pn.'</td></tr>';
                        $fill=!$fill;
                        $fillcolor=$fill?' class="altcolor" ':'';
                    }else{
                        $reportHTML .= '<td rowspan="'.$odd.'">'.$pn.'</td></tr>';
                        --$odd;
                    }
                }else{ // we've already started to display a circuit.  no new circuits will be drawn til this count hits zero.
                    $reportHTML .= '</tr>';
                    --$odd;
                    if($odd==0) {
                        $fill=!$fill;
                        $fillcolor=$fill?' class="altcolor" ':'';
                    }
                }
                ++$nextPole;
            } 
            $reportHTML .= '</tbody></table>';
        } // Done with table for Sequential


        if ($panel->NumberScheme=="Odd/Even"){
            $ofill=0;
            $ofillcolor="";
            $efill=1;
            $efillcolor=' class="altcolor" ';
            $reportHTML.= '<thead>';
            $reportHTML.= '<tr><td colspan="4" width="100%"><h4>'.__("Panel Schedule for").':<br>';
            $reportHTML.= __("Data Center").': '.$dc->Name.'<br>';
            $reportHTML.= __("Power Source").': '.$currSource->SourceName.'<br>';
            $reportHTML.= __("Power Panel").': '.$panel->PanelLabel.'</h4></td></tr>'; 
            $reportHTML.= '<tr><td width="5%">'.__("Pole").'</td>';
            $reportHTML.= '<td width="45%">'.__("Circuit").'</td>';
            $reportHTML.= '<td width="5%">'.__("Pole").'</td>';
            $reportHTML.= '<td width="45%">'.__("Circuit").'</td></tr></thead><tbody>';
            // Build single table with four columns to represent an odd/even panel layout
            // $odd and $even will be travel counters to ensure the table is built in a sane manner
            while($nextPole <= $panel->NumberOfPoles){
                $reportHTML .= '<tr><td align="center"'.$ofillcolor.'>'.$nextPole.'</td>';
                // Someone input a pole number wrong and this one would have been skipped
                // store the value and deal with it later.
                if(isset($pduarray[$nextPole])&&$odd!=0){
                    foreach($pduarray[$nextPole] as $pduvar){
                    $errors[]="$pduvar->Label";
                    }
                }
                // Get info for pdu on this pole if it is populated.
                $lastCabinet=0;
                if($odd==0){
                    if(isset($pduarray[$nextPole])){
                        $pn="";
                        foreach($pduarray[$nextPole] as $pduvar) {
                            $cab->CabinetID=$pduvar->CabinetID;
                            $cab->GetCabinet(  );

                            if ($lastCabinet<>$pduvar->CabinetID)
                                $pn.="$cab->Location<br>";
                            $pn.="&nbsp;&nbsp;&nbsp;$pduvar->Label";
                            $lastCabinet=$pduvar->CabinetID;

                            switch($pduvar->BreakerSize){
                                case '3': $odd=3; break;
                                case '2': $odd=2; break;
                                default: $odd=0;
                            }
                        }
                    }else{
                        $pn="Available";
                    }
                    if($odd==0){
                        $reportHTML .= '<td'.$ofillcolor.'>'.$pn.'</td>';
                        $ofill=!$ofill;
                        $ofillcolor=$ofill?' class="altcolor" ':'';
                    }else{
                        $reportHTML .= '<td rowspan="'.$odd.'"'.$ofillcolor.'>'.$pn.'</td>';
                        --$odd;
                    }
                }else{ // we've already started to display a circuit.  no new circuits will be drawn til this count hits zero.
                    --$odd;
                    if($odd==0) {
                        $ofill=!$ofill;
                        $ofillcolor=$ofill?' class="altcolor" ':'';
                    }
                }
                //Odd side done. Print even side circuit id then check for connected device.
                ++$nextPole;
                $reportHTML .= '<td align="center"'.$efillcolor.'>'.$nextPole.'</td>';
                // Someone input a pole number wrong and this one would have been skipped
                // store the value and deal with it later.
                if(isset($pduarray[$nextPole])&&$even!=0){ 
                    foreach($pduarray[$nextPole] as $pduvar){
                    $errors[]="$pduvar->Label";
                    }
                }
                if($even==0){
                    if(isset($pduarray[$nextPole])){
                        $pn="";
                        foreach($pduarray[$nextPole] as $pduvar) {
                            $cab->CabinetID=$pduvar->CabinetID;
                            $cab->GetCabinet(  );

                            if ($lastCabinet<>$pduvar->CabinetID)
                                $pn.="$cab->Location<br>";
                            $pn.="&nbsp;&nbsp;&nbsp;$pduvar->Label";
                            $lastCabinet=$pduvar->CabinetID;

                            switch($pduvar->BreakerSize){
                                case '3': $even=3; break;
                                case '2': $even=2; break;
                                default: $even=0;
                            }
                        }
                    }else{
                        $pn="Available";
                    }
                    if($even==0){
                        $reportHTML .= '<td'.$efillcolor.'>'.$pn.'</td></tr>';
                        $efill=!$efill;
                        $efillcolor=$efill?' class="altcolor" ':'';
                    }else{
                        $reportHTML .= '<td rowspan="'.$even.'"'.$efillcolor.'>'.$pn.'</td></tr>';
                        --$even;
                    }
                }else{ // we've already started to display a circuit.  no new circuits will be drawn til this count hits zero.
                    $reportHTML .= '</tr>';
                    --$even;
                    if($even==0) {
                        $efill=!$efill;
                        $efillcolor=$efill?' class="altcolor" ':'';
                    }
                }
                //Even side done. Increment counter and restart loop for next row.
                ++$nextPole;
            } 
            $reportHTML .= '</tbody></table>';
        } //Done with table for Odd/Even

        // put a pagebreak for each table in mpdf, but don't do it after the
        // last table
        if($panel !== end($pnlList)) {
            $reportHTML .= '<!--mpdf <pagebreak /> mpdf-->';
        }
    } //Done with panel loop

    // generate the report using the template
    include('template_mpdf_reports.inc.php');

}
?>
