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
  
	function PDF(){
		parent::FPDF();
	}
  
	function Header() {
		$this->pdfconfig = new Config();
		if ( file_exists(  $this->pdfconfig->ParameterArray['PDFLogoFile'] )) {
	    	$this->Image(  $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
		}
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,__("Information Technology Services"),0,0,'C');
    	$this->Ln(25);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, __("Data Center Asset Report"), 0, 1, 'L' );
		$this->Cell( 50, 6, __("Date").': ' . date('d F Y'), 0, 1, 'L' );
		$this->Ln(10);
	}

	function Footer() {
	    	$this->SetY(-15);
    		$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'I',8);
    		$this->Cell(0,10,__("Page").' '.$this->PageNo().'/{nb}',0,0,'C');
	}
}

    $Owner = @$_REQUEST['owner'];
	$DataCenterID = @$_REQUEST['datacenterid'];
  
	$pdf=new PDF();
	include_once("loadfonts.php");
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',6);

	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$headerTags = array( __("DC Room"), __("Rack"), __("Position"), __("Label"), __("Serial Number"), __("Asset Tag") );
	$cellWidths = array( 15, 15, 15, 70, 30, 30 );
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

	$pdf->Ln();

	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );

	$fill = 0;

	$Criteria = '';
	
	if ( $Owner > 0 )
		$Criteria .= 'c.Owner=\'' . intval( $Owner ) . '\' and ';
	if ( $DataCenterID > 0 )
		$Criteria .= 'b.DataCenterID=\'' . intval( $DataCenterID ) . '\' and ';
		
    $searchSQL = 'select a.Name,b.Location,c.Position,c.Height,c.Label,c.SerialNo,c.AssetTag,c.DeviceID,c.DeviceType from fac_DataCenter a, fac_Cabinet b, fac_Device c where ' . $Criteria . 'c.ParentDevice=0 and c.Cabinet=b.CabinetID and b.DataCenterID=a.DataCenterID order by a.Name,b.Location,c.Position';

	$lastDC = '';
	$lastCab = '';

	foreach($dbh->query($searchSQL) as $reportRow){
		$DataCenter = $reportRow['Name'];
		$Location = $reportRow['Location'];
		if ( $reportRow["Height"] > 1 )
			$Position = '[' . $reportRow['Position'] . '-' . intval($reportRow['Position']+$reportRow['Height']-1) . ']';
		else
			$Position = $reportRow['Position'];
			
		$Label = $reportRow['Label'];
		$SerialNo = $reportRow['SerialNo'];
		$AssetTag = $reportRow['AssetTag'];

		if ( $lastDC != $DataCenter )
			$pdf->Cell( $cellWidths[0], 6, $DataCenter, 'LR', 0, 'L', $fill );
		else
			$pdf->Cell( $cellWidths[0], 6, '', 'LR', 0, 'L', $fill );

		if ( $lastCab != $Location )
			$pdf->Cell( $cellWidths[1], 6, $Location, 'LR', 0, 'L', $fill );
		else
			$pdf->Cell( $cellWidths[1], 6, '', 'LR', 0, 'L', $fill );

		$pdf->Cell( $cellWidths[2], 6, $Position, 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], 6, $Label, 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[4], 6, $SerialNo, 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[5], 6, $AssetTag, 'LR', 0, 'L', $fill );
		$pdf->Ln();

		$fill =! $fill;
		
		if ( $reportRow["DeviceType"] == "Chassis" ) {
			$chDev = new Device();
			$chDev->DeviceID = $reportRow["DeviceID"];
			$chList = $chDev->GetDeviceChildren();
			
			foreach ( $chList as $chRow ) {
				$pdf->Cell( $cellWidths[0], 6, '', 'LR', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[1], 6, '', 'LR', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[2], 6, '(blade)', 'LR', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[3], 6, $chRow->Label, 'LR', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[4], 6, $chRow->SerialNo, 'LR', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[5], 6, $chRow->AssetTag, 'LR', 0, 'L', $fill );
				$pdf->Ln();

				$fill =! $fill;				
			}
		}

		$lastDC = $DataCenter;
		$lastCab = $Location;
	}

	$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );

	$pdf->Output();
?>
