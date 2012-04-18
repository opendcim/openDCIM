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
    	$this->Image($this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,'Information Technology Services',0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, 'Data Center Asset Report', 0, 1, 'L' );
		$this->Cell( 50, 6, 'Date: ' . date( 'm/d/y' ), 0, 1, 'L' );
		$this->Ln(10);
	}

	function Footer() {
	    	$this->SetY(-15);
    		$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'I',8);
    		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
}

    $Owner = @$_REQUEST['owner'];
	$DataCenterID = @$_REQUEST['datacenterid'];
  
	$pdf=new PDF($facDB);
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',8);

	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$headerTags = array( 'Room', 'Rack', 'Position', 'Label', 'Serial Number', 'Asset Tag' );
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
		
    $searchSQL = 'select a.Name,b.Location,c.Position,c.Label,c.SerialNo,c.AssetTag from fac_DataCenter a, fac_Cabinet b, fac_Device c where ' . $Criteria . 'c.Cabinet=b.CabinetID and b.DataCenterID=a.DataCenterID and c.Reservation=false order by a.Name,b.Location,c.Position';

	$result = mysql_query( $searchSQL, $facDB );

	$lastDC = '';
	$lastCab = '';

	while ( $reportRow = mysql_fetch_array( $result ) ) {
		$DataCenter = $reportRow['Name'];
		$Location = $reportRow['Location'];
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

		$lastDC = $DataCenter;
		$lastCab = $Location;
	}

	$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );

	$pdf->Output();
?>
