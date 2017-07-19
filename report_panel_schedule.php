<?php
    /* Power Panel Schedule report
       Prints out a power panel schedule for all selected Power Panels
    */

    require_once( "db.inc.php" );
    require_once( "facilities.inc.php" );
    
    if(!$person->SiteAdmin){
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
<form method="post" id="panelform">

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
    $cab = new Cabinet();
    $dc = new DataCenter();

    $dc->DataCenterID = intval( $_REQUEST['datacenterid'] );
    $dc->GetDataCenter();

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
		// we were passed in a list of power source ids, so generate panel array baseed off of that
		$pnlList = array();
		foreach ( $srcArray as $srcID ) {
			$pan->ParentPanelID = $srcID;
			$pnlList = array_merge( $pnlList, $pan->getPanelListBySource() );
		}
	} else {
		// we were passed in a list of panel ids, so create the panel array from that
		$pnlList = array();
		foreach ( $pnlArray as $pnlID ) {
			$pnlCount = count( $pnlList );
			$pnlList[$pnlCount] = new PowerPanel();
			$pnlList[$pnlCount]->PanelID = $pnlID;
			$pnlList[$pnlCount]->GetPanel();
		}
	}

	if ( sizeof( $pnlList ) == 0 ) {
		echo '<meta http-equiv="refresh" content="0">';
		exit;
	}
    
    //
    // Now that we have a complete list of the panels, we need get the panel schedules for them
    //
    // Loop through all the panels from the list and build a schedule
    $reportHTML="";
    foreach ( $pnlList as $panel) {

		$panelSchedule=$panel->getPanelSchedule();

		$powerSource = "";
		$parentTree = $panel->getParentTree();
		$lastTreeElement = key(end($parentTree));
		foreach($parentTree as $key=>$currParent) {
			if($key!=$lastTreeElement) {
				$powerSource.=" / ";
			}
			$powerSource.=$currParent->PanelLabel;
		}

		$totalCols=2;
		if($panel->NumberScheme=="Odd/Even") {
			$totalCols=4;
		}
        
		$reportHTML.= '<table class="items" width="100%">';
		$reportHTML.= '<thead>';
		$reportHTML.= '<tr><td colspan="'.$totalCols.'" width="100%"><h4>'.__("Panel Schedule for").':<br>';
		$reportHTML.= __("Data Center").': '.$dc->Name.'<br>';
		$reportHTML.= __("Power Source").': '.$powerSource.'<br>';
		$reportHTML.= __("Power Panel").': '.$panel->PanelLabel.'</h4></td></tr>'; 

		if($panel->NumberScheme=="Odd/Even") {
			$reportHTML.= '<tr><td width="5%">'.__("Pole").'</td>';
			$reportHTML.= '<td width="45%">'.__("Circuit").'</td>';
			$reportHTML.= '<td width="5%">'.__("Pole").'</td>';
			$reportHTML.= '<td width="45%">'.__("Circuit").'</td></tr></thead><tbody>';

			for($count=1; $count<=$panel->NumberOfPoles; $count++) {
				if($count % 2 == 0) {
					$reportHTML .= '<td class="polenumber panelright">'.$count.'</td>';
					$reportHTML .= $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $count, "panelright", true);
					$reportHTML .= '</tr>';
				} else {
					$reportHTML .= '<tr><td class="polenumber panelleft">'.$count.'</td>';
					$reportHTML .= $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $count, "panelleft", true);
				}
			}

		} else {
			$reportHTML.= '<tr><td width="5%">'.__("Pole").'</td>';
			$reportHTML.= '<td width="95%">'.__("Circuit").'</td></tr></thead><tbody>';
			for($count=1; $count<=$panel->NumberOfPoles; $count++) {
				$reportHTML .= '<tr id="itemRow"><td align="center">'.$count.'</td>';
				$reportHTML .= $panel->getPanelScheduleLabelHtml($panelSchedule["panelSchedule"], $count, "panelleft", true);
				$reportHTML .- '</tr>';
			}
		}

		$reportHTML .= '</tbody></table>';


		// put a pagebreak for each table in mpdf, but don't do it after the
		// last table
		if($panel !== end($pnlList)) {
			$reportHTML .= '<!--mpdf <pagebreak /> mpdf-->';
		}
	} //Done with panel loop


	// go back and zebra stripe the report
	require_once("simple_html_dom.php");

	$dom = str_get_html($reportHTML);

	// find all item tables, which is what we will zebra stripe
	foreach($dom->find('table.items') as $currTable) {
		$numTD=0;
		// get the first row that has a td with rowspan attribute
		$firstRow= $currTable->find('tr td[rowspan]', 0);
		if(!$firstRow) {
			// no row found, instead get the first row that has a td without colspan attribute
			$firstRow = $currTable->find('tr td[!colspan]', 0);
		}
		if($firstRow) {
			// calculate the number of tds inside the found tr
			$numTD = count($firstRow->parent()->children());
		}

		if($numTD==4) {
			// this is odd/even
			$filterTrLeft=array();
			$filterTrRight=array();
			foreach($currTable->find('tr') as $currTr) {
				// simplehtmldoms handling of tbody in find() is broken, so filter it here
				$currTrChildren = $currTr->children();
				$currTrChildrenCount = count($currTrChildren);
				if( $currTr->parent()->tag!="thead") {
					if($currTrChildrenCount==$numTD) {
						$filterTrLeft[]=array($currTrChildren[0], $currTrChildren[1]);
						$filterTrRight[]=array($currTrChildren[2], $currTrChildren[3]);
					} elseif($currTrChildrenCount == 3) {
						// if child count is 3, then we need to figure out if the left half or the 
						// right half is rowspanned. easiest way is to look at the second item
						// and see if it is just an integer (the pole number) and if so, the
						// left side is rowspanned, otherwise the right side is
						if(is_numeric($currTrChildren[1]->innertext)) {
							$filterTrRight[]=array($currTrChildren[1], $currTrChildren[2]);
						} else {
							$filterTrLeft[]=array($currTrChildren[0], $currTrChildren[1]);
						}
					} 
				}
			}

			// this colors the main part of the filtered rows, but not the rowspanned parts
			for($count=0; $count<count($filterTrLeft); $count++) {
				if($count%2!=0) {
					$filterTrLeft[$count][0]->class.=" altcolor";
					$filterTrLeft[$count][1]->class.=" altcolor";
				}
			}
			for($count=0; $count<count($filterTrRight); $count++) {
				if($count%2==0) {
					$filterTrRight[$count][0]->class.=" altcolor";
					$filterTrRight[$count][1]->class.=" altcolor";
				}
			}

		} else {
			// this is sequential

			// filter the list of trs so we only get ones that are in tbody, and have a count
			// equal to the full number of tds for the table - these are the rows we are
			// actually going to zebra stripe (basically, excludes rowspanned rows)
			$filterTr=array();
			foreach($currTable->find('tr') as $currTr) {
				// simplehtmldoms handling of tbody in find() is broken, so filter it here
				if( $currTr->parent()->tag!="thead" && count($currTr->children())==$numTD) {
					$filterTr[]=$currTr;
				}	
			}
			// now, go through the list and add the altcolor class to every other filtered row
			for($count=0; $count<count($filterTr); $count++) {
				if($count%2!=0) {
					$filterTr[$count]->class.=" altcolor";
				}
			}
		}

	}

	// now, we need to also color any rowspanned rows, and at this point we know which those are

	// this is for odd/even (which are done at the td level)
	foreach($dom->find('table.items tr td.altcolor') as $currTd) {
		if(strpos($currTd->class, "altcolor") && $currTd->rowspan) {
			$nextTr=$currTd->parent()->next_sibling();
			for($count=0; $count<$currTd->rowspan-1; $count++) {
				if(strpos($currTd->class, "panelleft")) {
					foreach($nextTr->children() as $nextTd) {
						if(strpos($nextTd->class, "panelleft") && !strpos($nextTd->class, "altcolor")) {
							$nextTd->class.=" altcolor";
						}
					}
				} elseif(strpos($currTd->class, "panelright")) {
					foreach($nextTr->children() as $nextTd) {
						if(strpos($nextTd->class, "panelright") && !strpos($nextTd->class, "altcolor")) {
							$nextTd->class.=" altcolor";
						}
					}
				}
				$nextTr=$nextTr->next_sibling();
			}
		}
	}



	// this is for sequential (which are done at the tr level)
	foreach($dom->find('table.items tr.altcolor td[rowspan]') as $currTd) {
		//for some reason, the find above doesn't always filter out trs that don't have 
		// altcolor set, so make doubly sure
		if(strpos($currTd->parent()->class, "altcolor")) {
			// at this point, the currTd parent should be a row that is altcolored, and has a rowspan
			// loop through all of the follow-up trs until we color all of the ones in the rowspan
			$nextTr = $currTd->parent()->next_sibling();
			for($count=0; $count<$currTd->rowspan-1; $count++) {
				$nextTr->class.=" altcolor";
				$nextTr = $nextTr->next_sibling();
			}
		}
	}


	$reportHTML = $dom->save();

	// generate the report using the template
	include('template_mpdf_reports.inc.php');

	print $reportHTML;

}
?>
