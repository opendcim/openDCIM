<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

	$dept = new Department();
	$dev = new Device();
	$cab = new Cabinet();
	$dc = new DataCenter();

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
		$this->pdfconfig = new Config();
    	$this->Image( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,'Information Technology Services',0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, 'Data Center Inventory Exception Report', 0, 1, 'L' );
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



//
//
//	Begin Report Generation
//
//

	$pdf=new PDF();
	$pdf->AliasNbPages();
	  
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',8);

	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );

  $pdf->Bookmark( 'Departments' );
	$pdf->AddPage();
  $pdf->Bookmark( 'Unknown Owner', 1, 0 );

	$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
	$pdf->Cell( 80, 5, 'Department:  Unknown' );
	$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 8 );
	$pdf->Ln();

	$dev->Owner = 0;
	$devList = $dev->GetDevicesbyOwner();

	$headerTags = array( 'Device Name', 'Serial Number', 'Template?', 'Power Cords', 'Room', 'Cabinet', 'Position', 'Rack Units' );
	$cellWidths = array( 50, 30, 20, 20, 20, 20, 20, 20 );
	$maxval = count( $headerTags );

  	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

	$pdf->Ln();

	$fill = 0;

	foreach( $devList as $devRow ) { 
	  if ( ( $devRow->TemplateID > 0 ) && ( ( $devRow->PowerSupplyCount > 0 ) && ( $devRow->Infrastructure == 0 ) ) )
	    continue;

		if ( $devRow->Cabinet != $cab->CabinetID ) {
			$cab->CabinetID = $devRow->Cabinet;
			$cab->GetCabinet();
		}

		if ( $cab->DataCenterID != $dc->DataCenterID ) {
			$dc->DataCenterID = $cab->DataCenterID;
			$dc->GetDataCenterbyID();
		}

    if ( $devRow->TemplateID > 0 )
      $template = 'Yes';
    else
      $template = 'No';
      
		$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, $devRow->SerialNo, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, $template, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, $devRow->PowerSupplyCount, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], 6, $dc->Name, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[4], 6, $cab->Location, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[5], 6, $devRow->Position, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[5], 6, $devRow->Height, 'LBRT', 1, 'L', $fill );

		$fill =! $fill;
	}

 	$deptList = $dept->GetDepartmentList();

	foreach( $deptList as $deptRow ) {
		$pdf->AddPage();
		$pdf->Bookmark( $deptRow->Name, 1, 0 );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, 'Department:' );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell( 0, 5, $deptRow->Name );
		$pdf->Ln();
		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, 'Executive Sponsor:' );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell( 0, 5, $deptRow->ExecSponsor );
		$pdf->Ln();
		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, 'Service Delivery Manager:' );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell( 0, 5, $deptRow->SDM );
		$pdf->Ln();
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 8 );

		$pdf->Ln();

		$dev->Owner = $deptRow->DeptID;
		$devList = $dev->GetDevicesbyOwner();


		$headerTags = array( 'Device Name', 'Serial Number', 'Template?', 'Power Cords', 'Room', 'Cabinet', 'Position', 'Rack Units' );
		$cellWidths = array( 50, 30, 20, 20, 20, 20, 20, 20 );
		$maxval = count( $headerTags );

		for ( $col = 0; $col < $maxval; $col++ )
			$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

		$pdf->Ln();

		$fill = 0;

		foreach( $devList as $devRow ) {
		  if ( ( $devRow->TemplateID > 0 ) && ($devRow->PowerSupplyCount > 0 ))
		    continue;
		    
			if ( $devRow->Cabinet != $cab->CabinetID ) {
				$cab->CabinetID = $devRow->Cabinet;
				$cab->GetCabinet();
			}

			if ( $cab->DataCenterID != $dc->DataCenterID ) {
				$dc->DataCenterID = $cab->DataCenterID;
				$dc->GetDataCenterbyID();
			}

      if ( $devRow->TemplateID > 0 )
        $template = 'Yes';
      else
        $template = 'No';
        
			$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[1], 6, $devRow->SerialNo, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $template, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $devRow->PowerSupplyCount, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, $dc->Name, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[4], 6, $cab->Location, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[5], 6, $devRow->Position, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[5], 6, $devRow->Height, 'LBRT', 1, 'L', $fill );

			$fill =! $fill;
		}
	}

	$pdf->Output();
?>
