<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	if(!$user->ReadAccess){
		header( "Location: ".redirect());
		exit;
	}

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
    	$this->Image( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,'Information Technology Services',0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, 'Devices Without Diverse (Tier III) Power', 0, 1, 'L' );
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

  $pan = new PowerPanel();
  $pdu = new PowerDistribution();
  $source = new PowerSource();
	$dev = new Device();
	$cab = new Cabinet();
	$dept = new Department();
	$dc = new DataCenter();

	$pdf=new PDF($facDB);
	$pdf->AliasNbPages();
	  
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',8);

	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );

  $pdf->Bookmark( 'Data Centers' );
	
  $dcList = $dc->GetDCList( $facDB );
  
  foreach ( $dcList as $dcRow ) {
    $pdf->AddPage();
    $pdf->BookMark( $dcRow->Name, 1 );
    $pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
    $pdf->Cell( 80, 5, 'Data Center: ' . $dcRow->Name );
    $pdf->SetFont( $config->ParameterArray['PDFfont'], '', 8 );
    $pdf->Ln();

  	$headerTags = array( 'Cabinet', 'Device Name', 'Dependency', 'Position', 'Owner' );
  	$cellWidths = array( 15, 50, 30, 15, 60 );

  	$maxval = count( $headerTags );

  	for ( $col = 0; $col < $maxval; $col++ )
  		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

  	$pdf->Ln();

  	$fill = 0;
  	
    $cab->DataCenterID = $dcRow->DataCenterID;
    $cabList = $cab->ListCabinetsByDC( $facDB );
    
    foreach ( $cabList as $cabRow ) {
      $pdf->BookMark( $cabRow->Location, 2 );
      
      $dev->Cabinet = $cabRow->CabinetID;
      $devList = $dev->GetSinglePowerByCabinet( $facDB );
      
      if ( sizeof( $devList ) == 0 ) {
        $pdf->Cell( $cellWidths[0], 6, $cabRow->Location, 'LBRT', 0, 'L', $fill );
        $pdf->Cell( $cellWidths[1], 6, 'None', 'LBRT', 0, 'L', $fill );
    		$pdf->Cell( $cellWidths[2], 6, '', 'LBRT', 0, 'L', $fill );
    		$pdf->Cell( $cellWidths[3], 6, '', 'LBRT', 0, 'L', $fill );
    		$pdf->Cell( $cellWidths[4], 6, '', 'LBRT', 1, 'L', $fill );
        
        $fill =! $fill;
      } else {
        foreach ( $devList as $devRow ) {
          $sourceList = $devRow->GetDeviceDiversity( $facDB );
          $source->PowerSourceID = $sourceList[0];
          if ( $source->PowerSourceID > 0 ) {
            $source->GetSource( $facDB );
          } else {
            $source->SourceName = 'Unknown';
          }
          
          foreach ( $sourceList as $sourceID ) {
            $source->PowerSourceID = $sourceID;
            if ( $sourceID < 1 ) {
              $sources .= 'Unknown ';
            } else {
              $source->GetSource( $facDB );
              $sources .= $source->SourceName . ' ';
            }
          }
            
          $pdf->Cell( $cellWidths[0], 6, $cabRow->Location, 'LBRT', 0, 'L', $fill );
          $pdf->Cell( $cellWidths[1], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
          $pdf->Cell( $cellWidths[2], 6, $source->SourceName, 'LBRT', 0, 'L', $fill ); 
      		$pdf->Cell( $cellWidths[3], 6, $devRow->Position, 'LBRT', 0, 'L', $fill );
      		
      		$dept->DeptID = $devRow->Owner;
      		$dept->GetDeptByID( $facDB );
      		
      		$pdf->Cell( $cellWidths[4], 6, $dept->Name, 'LBRT', 1, 'L', $fill );
          
          $fill =! $fill;         	
        }
      }
      
      $pdf->Ln();
    }    	
  }

	$pdf->Output();
?>
