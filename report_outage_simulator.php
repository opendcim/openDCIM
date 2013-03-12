<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

class PDF extends FPDF {
  var $outlines=array();
  var $OutlineRoot;
  var $pdfconfig;
  var $pdfDB;
  
	function PDF($db){
		$this->pdfDB = $db;
		parent::FPDF();
	}
  
	function Header() {
		$this->pdfconfig = new Config($this->pdfDB);
		$this->Link( 10, 8, 100, 20, 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] );
    	$this->Image( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,'Information Technology Services',0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, 'Outage Impact Report', 0, 1, 'L' );
		$this->Cell( 50, 6, 'Date: ' . date( 'm/d/y' ), 0, 1, 'L' );
		$this->Ln(10);
	}

	function Footer() {
	    	$this->SetY(-15);
    		$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'I',8);
    		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
	
  function Bookmark($txt,$level=0,$y=0) {
    if($y==-1)
        $y=$this->GetY();
    $this->outlines[]=array('t'=>$txt,'l'=>$level,'y'=>$y,'p'=>$this->PageNo());
  }

  function _putbookmarks() {
    $nb=count($this->outlines);
    if($nb==0)
        return;
    $lru=array();
    $level=0;
    foreach($this->outlines as $i=>$o)
    {
        if($o['l']>0)
        {
            $parent=$lru[$o['l']-1];
            //Set parent and last pointers
            $this->outlines[$i]['parent']=$parent;
            $this->outlines[$parent]['last']=$i;
            if($o['l']>$level)
            {
                //Level increasing: set first pointer
                $this->outlines[$parent]['first']=$i;
            }
        }
        else
            $this->outlines[$i]['parent']=$nb;
        if($o['l']<=$level and $i>0)
        {
            //Set prev and next pointers
            $prev=$lru[$o['l']];
            $this->outlines[$prev]['next']=$i;
            $this->outlines[$i]['prev']=$prev;
        }
        $lru[$o['l']]=$i;
        $level=$o['l'];
    }
    //Outline items
    $n=$this->n+1;
    foreach($this->outlines as $i=>$o)
    {
        $this->_newobj();
        $this->_out('<</Title '.$this->_textstring($o['t']));
        $this->_out('/Parent '.($n+$o['parent']).' 0 R');
        if(isset($o['prev']))
            $this->_out('/Prev '.($n+$o['prev']).' 0 R');
        if(isset($o['next']))
            $this->_out('/Next '.($n+$o['next']).' 0 R');
        if(isset($o['first']))
            $this->_out('/First '.($n+$o['first']).' 0 R');
        if(isset($o['last']))
            $this->_out('/Last '.($n+$o['last']).' 0 R');
        $this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]',1+2*$o['p'],($this->h-$o['y'])*$this->k));
        $this->_out('/Count 0>>');
        $this->_out('endobj');
    }
    //Outline root
    $this->_newobj();
    $this->OutlineRoot=$this->n;
    $this->_out('<</Type /Outlines /First '.$n.' 0 R');
    $this->_out('/Last '.($n+$lru[0]).' 0 R>>');
    $this->_out('endobj');
  }

  function _putresources()
  {
    parent::_putresources();
    $this->_putbookmarks();
  }

  function _putcatalog()
  {
    parent::_putcatalog();
    if(count($this->outlines)>0)
    {
        $this->_out('/Outlines '.$this->OutlineRoot.' 0 R');
        $this->_out('/PageMode /UseOutlines');
    }
  }
}

if (!isset($_REQUEST['action'])){
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>

</head>
<body>
<div style="height: 66px;" id="header"></div>
<?php

	$user = new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	include( 'sidebar.inc.php' );
	
	$datacenter = new DataCenter();
	$dcList = $datacenter->GetDCList( $facDB );
	
	$pwrSource = new PowerSource();
	$pwrPanel = new PowerPanel();
	$cab = new Cabinet();
	
?>
</div>
<div class="main">
<h2>openDCIM</h2>
<h3>Outage Impact Simulation</h3>
<form action="<?php printf( "%s", $_SERVER['PHP_SELF'] ); ?>" method="post">
<table align="center" border=0>
<?php
	if ( @$_REQUEST['datacenterid'] == 0 ) {
		printf( "<tr><td>Data Center:</td><td>\n" );
		printf( "<select name=\"datacenterid\" onChange=\"form.submit()\">\n" );
		printf( "<option value=\"\">Select data center</option>\n" );
		
		foreach ( $dcList as $dc )
			printf( "<option value=\"%d\">%s</option>\n", $dc->DataCenterID, $dc->Name );
		
		printf( "</td></tr>" );
	} else {
		$datacenter->DataCenterID = $_REQUEST['datacenterid'];
		$datacenter->GetDataCenter( $facDB );
		
		$pwrSource->DataCenterID = $datacenter->DataCenterID;
		$sourceList = $pwrSource->GetSourcesByDataCenter( $facDB );
		printf( "<input type=\"hidden\" name=\"datacenterid\" value=\"%d\">\n", $datacenter->DataCenterID );
		
		printf( "<h3>Choose either power sources or panels to simulate for Data Center: %s</h3>", $datacenter->Name );
		
		printf( "<input type=submit name=\"action\" value=\"Generate\"><br>\n" );
		
		printf( "<table border=1 align=center>\n" );
		printf( "<tr><th>Power Source</th><th>Power Panel</th></tr>\n" );
		
		foreach ( $sourceList as $source ) {
			$pwrPanel->PowerSourceID = $source->PowerSourceID;
			$panelList = $pwrPanel->GetPanelListBySource( $facDB );
			
			printf( "<tr><td><input type=\"checkbox\" name=\"sourceid[]\" value=\"%d\">%s</td>\n", $source->PowerSourceID, $source->SourceName );
			
			printf( "<td><table>\n" );
			
			foreach ( $panelList as $panel )
				printf( "<tr><td><input type=\"checkbox\" name=\"panelid[]\" value=\"%d\">%s</td></tr>\n", $panel->PanelID, $panel->PanelLabel );
			
			printf( "</table></td></tr>\n" );
		}
	}
?>
</table>
</form>
<?php
} else {

	//
	//
	//	Begin Report Generation
	//
	//

	$pan = new PowerPanel();
	$pdu = new PowerDistribution();
	$source = new PowerSource();
	$dev = new Device();
	$cab = new Cabinet();
	$dept = new Department();
	$dc = new DataCenter();
	$pwrConn = new PowerConnection();
	
	// Make some quick user defined sort comparisons for this report only
	
	function compareCab( $a, $b ) {
		if ( $a->Location == $b->Location )
			return 0;
		
		return ( $a->Location > $b->Location ) ? +1 : -1;
	}
	
	$dc->DataCenterID = intval( $_REQUEST['datacenterid'] );
	$dc->GetDataCenter( $facDB );

	if(isset($_POST['sourceid'])){
		$srcArray=$_POST['sourceid'];
	}
	if(isset($_POST['panelid'])){
		$pnlArray=$_POST['panelid'];
	}
	
	if ( count( $srcArray ) > 0 ) {
		// Build an array of the Panels affected when the entire source goes down.
		// This will allow us to use one section of code to calculate effects of panels going down and use it for both cases.
		
		$pnlList = array();
		
		foreach ( $srcArray as $srcID ) {
			$pan->PowerSourceID = $srcID;
			
			$pnlList = array_merge( $pnlList, $pan->GetPanelListBySource( $facDB ) );
		}
	} else {
		// Need to build an array of Panel Objects (what we got from input was just the IDs)
		$pnlList = array();
		
		foreach ( $pnlArray as $pnlID ) {
			$pnlCount = count( $pnlList );
			$pnlList[$pnlCount] = new PowerPanel();
			$pnlList[$pnlCount]->PanelID = $pnlID;
			$pnlList[$pnlCount]->GetPanel( $facDB );
		}
	}
	
	// Now that we have a complete list of the panels, we need a list of the CDUs affected by the outage
	
	$pduList = array();
	
	// Rebuild an array of just the Panel ID values
	$pnlArray = array();
	
	foreach ( $pnlList as $pnlDown ) {
		$pdu->PanelID = $pnlDown->PanelID;
		
		$pduList = array_merge( $pduList, $pdu->GetPDUbyPanel( $facDB ) );
		
		array_push( $pnlArray, $pnlDown->PanelID );
	}
	
	// And finally, build a list of cabinets that have at least one circuit from the affected panels
	
	$cabIDList = array();
	$cabList = array();
	
	// Also need to build a unique list of all PDU ID's included in outage
	$pduArray = array();
	$fsArray = array();

	foreach ( $pduList as $outagePDU ) {
		if ( array_search( $outagePDU->CabinetID, $cabIDList ) === false ) {
			array_push( $cabIDList, $outagePDU->CabinetID );
			
			$cabCount = count( $cabList );
			
			$cabList[$cabCount] = new Cabinet();
			$cabList[$cabCount]->CabinetID = $outagePDU->CabinetID;
			$cabList[$cabCount]->GetCabinet( $facDB );
		}
			
		if ( $outagePDU->FailSafe ) {
			// Check both inputs on a FailSafe PDU
			if ( in_array( $outagePDU->PanelID, $pnlArray ) && in_array( $outagePDU->PanelID2, $pnlArray ) ) {
				array_push( $pduArray, $outagePDU->PDUID );
			} else {
				if ( in_array( $outagePDU->PanelID, $pnlArray ) || in_array( $outagePDU->PanelID2, $pnlArray ) ) {
					array_push( $fsArray, $outagePDU->PDUID );
				}
			}
		} else {
			array_push( $pduArray, $outagePDU->PDUID );
		}
	}
		
	usort( $cabList, 'compareCab' );
	
	$pdf=new PDF($facDB);
	$pdf->AliasNbPages();

	$pdf->SetFont($config->ParameterArray['PDFfont'],'',8);

	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );

	$pdf->AddPage();
	$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
	$pdf->Cell( 80, 5, 'Data Center: ' . $dc->Name );
	$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 8 );
	$pdf->Ln();

	$pdf->Cell( 0, 2, 'The following cabinets have at least one circuit affected by the simulation:', 0, 1 );
	$pdf->Ln();
	
	$column = 0;
	$pdf->SetLeftMargin( 20 );
	
	foreach ( $cabList as $outageCab ) {
		$pdf->Cell( 50, 3, $outageCab->Location );
		
		$column++;
		
		if ( $column == 4 ) {
			$pdf->Ln();
			$column = 0;
		}
	}
	
	$pdf->SetLeftMargin( 10 );
	$pdf->AddPage();
	
	$headerTags = array( 'Cabinet', 'Device Name', 'Status', 'Position', 'Owner' );
	$cellWidths = array( 25, 50, 30, 15, 65 );

	$fill = 0;
		
	foreach ( $cabList as $cabRow ) {
		$maxval = count( $headerTags );

		for ( $col = 0; $col < $maxval; $col++ )
			$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
			
		$pdf->Ln();

		$dev->Cabinet = $cabRow->CabinetID;
		
		// Check to see if all circuits to the cabinet are from the outage list - if so, the whole cabinet goes down
		$pdu->CabinetID = $cabRow->CabinetID;
		$cabPDUList = $pdu->GetPDUbyCabinet( $facDB );
		
		$diversity = false;
		foreach ( $cabPDUList as $testPDU ) {
			if ( ! in_array( $testPDU->PanelID, $pnlArray ) )
				$diversity = true;
		}
		
		$devList = $dev->ViewDevicesByCabinet( $facDB );

		if ( sizeof( $devList ) == 0 ) {
			$pdf->Cell( $cellWidths[0], 6, $cabRow->Location, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[1], 6, 'None', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, '', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, '', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[4], 6, '', 'LBRT', 1, 'L', $fill );
			
			$fill =! $fill;
		} else {
			foreach ( $devList as $devRow ) {
				// If there is not a circuit to the cabinet that is unaffected, no need to even check
				$outageStatus = 'Down';
				
				if ( ! $devRow->Reservation ) {	// No need to even process devices that aren't installed, yet
					if ( $diversity ) {
						// If a circuit was entered with no panel ID, or a device has no connections documented, mark it as unknown
						// The only way to be sure a device will stay up is if we have a connection to an unaffected circuit,
						// or to a failsafe switch (ATS) connected to at least one unaffected circuit.
						$outageStatus = 'Down';
						
						$pwrConn->DeviceID = $devRow->DeviceID;
						$connList = $pwrConn->GetConnectionsByDevice( $facDB );
						
						$devPDUList = array();
						$fsDiverse = false;
						
						if ( count( $connList ) == 0 ) {
							$outageStatus = 'Unknown';
						}

						foreach ( $connList as $connection ) {
							// If the connection is to a PDU that is NOT in the affected PDU list, and is not already in the diversity list, add it

							if ( ! in_array( $connection->PDUID, $pduArray ) ) {
								if ( ! in_array( $connection->PDUID, $devPDUList ) )
									array_push( $devPDUList, $connection->PDUID );

							}

							if ( in_array( $connection->PDUID, $fsArray ) ) {
								$fsDiverse = true;
							}
						}
						
						if ( count( $devPDUList ) > 0 ) {
							if ( count( $devPDUList ) < $devRow->PowerSupplyCount )
								$outageStatus = 'Degraded';
							elseif ( $fsDiverse )
								$outageStatus = 'Degraded/Fail-Safe';
							else
								$outageStatus = 'Normal';
						}
						
					}
					
					$pdf->Cell( $cellWidths[0], 6, $cabRow->Location, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[1], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[2], 6, $outageStatus, 'LBRT', 0, 'L', $fill ); 
					$pdf->Cell( $cellWidths[3], 6, $devRow->Position, 'LBRT', 0, 'L', $fill );

					$dept->DeptID = $devRow->Owner;
					$dept->GetDeptByID( $facDB );

					$pdf->Cell( $cellWidths[4], 6, $dept->Name, 'LBRT', 1, 'L', $fill );

					$fill =! $fill;
				}
			}
		}
	  
		$pdf->Ln();
	}    	

	$pdf->Output();
	
}
?>
