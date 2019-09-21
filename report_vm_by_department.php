<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

if(!$person->ReadAccess){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

	$dept = new Department();
	$con = new People();
	$dev = new Device();
	$cab = new Cabinet();
	$dc = new DataCenter();

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
		$this->Cell( 50, 6, __("Virtual Machines by Department"), 0, 1, 'L' );
		$this->Cell( 50, 6, __("Date").': ' . date('d F Y'), 0, 1, 'L' );
		$this->Ln(10);
	}

	function Footer() {
	    	$this->SetY(-15);
    		$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'I',8);
    		$this->Cell(0,10,__("Page").' '.$this->PageNo().'/{nb}',0,0,'C');
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
	include_once("loadfonts.php");
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',8);
	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );
	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );

  $pdf->Bookmark( 'Departments' );
	$deptList = $dept->GetDepartmentList();
	
	$VM = new VM();
	$vmList = $VM->GetInventory();
	
	$vmCount = count( $vmList );
	
	$pdf->Cell( 80, 5, __("Total Number of VMs") );
	$pdf->Cell( 0, 5, $vmCount );
	$pdf->Ln();
	
	$vmList = $VM->GetOrphanVMList();
	$vmCount = 0;
	
	$pdf->Cell( 80, 12, __("Virtual Machines Unassigned to a Department") );
	$pdf->Ln();
	
	$headerTags = array( __("Index"), __("Virtual Machine Image Name"), __("Current Host Server") );
	$cellWidths = array( 10, 80, 80 );
	$maxval = count( $headerTags );

  	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

	$pdf->Ln();

	$fill = 0;
        $lastDevice = 0;
  
	foreach( $vmList as $vmRow ) {
		if ( $vmRow->DeviceID != $lastDevice ) {
			$dev->DeviceID = $vmRow->DeviceID;
			$dev->GetDevice();
		}
		
		$pdf->Cell( $cellWidths[0], 6, ++$vmCount, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, $vmRow->vmName, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, $dev->Label, 'LBRT', 1, 'L', $fill );
  
                $fill != $fill;
        }

	foreach( $deptList as $deptRow ) {
	 $VM->Owner = $deptRow->DeptID; 
	 $vmList = $VM->GetVMListByOwner();
	 $vmCount = 0;
	 
	 if ( count( $vmList ) > 0 ) {
		$pdf->AddPage();
		$pdf->Bookmark( $deptRow->Name, 1, 0 );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, __("Department").':' );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell( 0, 5, $deptRow->Name );
		$pdf->Ln();
		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, __("Executive Sponsor").':' );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell( 0, 5, $deptRow->ExecSponsor );
		$pdf->Ln();
		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, __("Service Delivery Manager").':' );
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell( 0, 5, $deptRow->SDM );
		$pdf->Ln();


		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 8 );

		$headerTags = array( __("UserName"), __("UserID"), __("Phone1"), __("Phone2"), __("Phone3"), __("Email") );
		$cellWidths = array( 50, 20, 25, 25, 25, 50 );
		$maxval = count( $headerTags );

		for ( $col = 0; $col < $maxval; $col++ )
			$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

		$pdf->Ln();

		$contactList=$con->GetPeopleByDepartment($deptRow->DeptID);

		$fill = 0;

		foreach( $contactList as $contact ) {
			$pdf->Cell( $cellWidths[0], 6, $contact->LastName . ', ' . $contact->FirstName, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[1], 6, $contact->UserID, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $contact->Phone1, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, $contact->Phone2, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[4], 6, $contact->Phone3, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[5], 6, $contact->Email, 'LBRT', 1, 'L', $fill );

			$fill =! $fill;
		}

		$pdf->Ln();
    
		$headerTags = array( __("Index"), __("Virtual Machine Image Name"), __("Current Host Server") );
		$cellWidths = array( 10, 80, 80 );

		$maxval = count( $headerTags );

		for ( $col = 0; $col < $maxval; $col++ )
			$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );

		$pdf->Ln();

		$fill = 0;
    $lastDevice = 0;
    
		foreach( $vmList as $vmRow ) {
			if ( $vmRow->DeviceID != $lastDevice ) {
				$dev->DeviceID = $vmRow->DeviceID;
				$dev->GetDevice();
			}
			
			$pdf->Cell( $cellWidths[0], 6, ++$vmCount, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[1], 6, $vmRow->vmName, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $dev->Label, 'LBRT', 1, 'L', $fill );
    
      $fill != $fill;
    }
   }
  }

	$pdf->Output();
?>
