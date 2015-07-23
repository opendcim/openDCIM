<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

class DCEM_PDF extends FPDF {
  var $outlines=array();
  var $OutlineRoot;
  var $pdfconfig;
  var $pdfDB;
  
	function DCEM_PDF(){
		parent::FPDF();
	}
  
	function Header() {
		$this->pdfconfig = new Config();
    	$this->Image( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	//$this->Cell(30,20,'Information Technology Services',0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, 'Data Center KPI Report', 0, 1, 'L' );
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


	$pageWidth = 210;
	$pageHeight = 297;

	$pdf=new DCEM_PDF();
	include_once("loadfonts.php");
	$pdf->AliasNbPages();

	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );

	//we start with the KPIs

	$pdf->AddPage();

	$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
	$pdf->Cell( 80, 5, 'DCEM' );
	$pdf->Ln();
	$pdf->Ln();

	$columnWidth = 85;
	$textRatio = 4/7;

	$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());

	$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
	$pdf->Cell($columnWidth * 2, 10, __("DCEM calculation"), 1, 2, 'C', true);

	$nextColumnX = $pageWidth / 2;
	$nextColumnY = $pdf->GetY();

	$subColumnX = $pdf->GetX() + $columnWidth * $textRatio;
	$subColumnY = $pdf->GetY();

	$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 10 );

	$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());
	$pdf->MultiCell($columnWidth * $textRatio, 5, __("Start date")."\n".__("End date"),'LTB','L',true);

	$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());
	$pdf->MultiCell($columnWidth * $textRatio, 5, "KPI EC\nKPI TE\nKPI REUSE\nKPI REN",'LTB','L',true);
	
	$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());
	$pdf->MultiCell($columnWidth * $textRatio, 5, __("Re-used energy weight")."\n".__("Renewable energy weight"),'LTB','L',true);
	
	$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());
	$pdf->MultiCell($columnWidth * $textRatio, 5, 'KPI EM','LTB','L',true);

	$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());
	$pdf->MultiCell($columnWidth * $textRatio, 5, 'KPI DCEM','LTB','L',true);


	$pdf->SetXY($subColumnX, $subColumnY);
	$pdf->MultiCell($columnWidth * (1 - $textRatio), 5, $_POST["startdate"]."\n".$_POST["enddate"], 'TRB', 'R', true);
	
	$pdf->SetXY($subColumnX, $pdf->GetY());
	$pdf->MultiCell($columnWidth * (1 - $textRatio), 5, $_POST["KPI_EC"]."\n".$_POST["KPI_TE"]."\n".$_POST["KPI_REUSE"]."\n".$_POST["KPI_REN"],'TRB','R',true);

	$pdf->SetXY($subColumnX, $pdf->GetY());
	$pdf->MultiCell($columnWidth * (1 - $textRatio), 5, $_POST["WREUSE"]."\n".$_POST["WREN"],'TRB','R',true);

	$pdf->SetXY($subColumnX, $pdf->GetY());
	$pdf->MultiCell($columnWidth * (1 - $textRatio), 5, $_POST["KPI_EM"],'TRB','R',true);

	$pdf->SetXY($subColumnX, $pdf->GetY());
	$pdf->MultiCell($columnWidth * (1 - $textRatio), 5, $_POST["KPI_DCEM"],'TRB','R',true);



	$pdf->SetXY($nextColumnX, $nextColumnY);

	//$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
	//$pdf->Cell($columnWidth, 10, 'Data center performance', 1, 2, 'C', true);

	$subColumnX = $pdf->GetX() + $columnWidth * $textRatio;
	$subColumnY = $pdf->GetY();

	$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 10 );

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->MultiCell($columnWidth * $textRatio, 5, 'DC P','LTB','L',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(0, 170, 0);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'A','LTR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(0, 255, 0);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'B','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(187, 255, 85);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'C','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(255, 255, 0);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'D','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(255, 194, 0);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'E','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(238, 153, 0);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'F','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(255, 0, 0);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'G','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(136, 136, 136);
	$pdf->MultiCell($columnWidth * 9/10, 5, 'H','LR','C',true);

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->SetFillColor(0, 0, 0);
	$pdf->SetTextColor( 255, 255, 255 );
	$pdf->MultiCell($columnWidth * 9/10, 5, 'I','LBR','C',true);
	
	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );


	$pdf->SetXY($nextColumnX + $columnWidth * $textRatio, $subColumnY);
	$pdf->MultiCell($columnWidth * (1 - $textRatio), 5, $_POST["DCP"].' ', 'TRB', 'R', true);

	$pdf->SetXY($nextColumnX + $columnWidth * 9/10, $pdf->GetY());
	$pdf->Cell($columnWidth * 1/10, 5*9, '', 1, 2, 'R', true);

	$pdf->Image("images/arrow.png",$nextColumnX + $columnWidth * 9/10 + 1, $subColumnY + 5 * (intval($_POST['DCPclass']) + 1) + 1, 0, 0, "PNG");	///

	$pdf->SetXY($nextColumnX, $pdf->GetY());
	$pdf->Cell($columnWidth * $textRatio, 5, 'DC G','LTB',0,'L',true);
	$pdf->Cell($columnWidth * (1 - $textRatio), 5, $_POST["DCG"].' ', 'TRB', 2, 'R', true);

	$dcidList = explode(',', $_POST["DCID"]);
	foreach($dcidList as $dcid) {
		printDC($pdf, $dcid);
	}

	$pdf->Output();



	function printDC($pdf, $dcid) {
		global $config;
		global $pageWidth;
		
		$pdf->AddPage();
		
		$dc = new DataCenter();
		$dc->DataCenterID = $dcid;
		$dc->GetDataCenter();

		$pdf->SetFont( $config->ParameterArray['PDFfont'], 'B', 12 );
		$pdf->Cell( 80, 5, 'Data center : '.$dc->Name );
		$pdf->Ln();
		$pdf->Ln();

		$columnWidth = 95;
		$textRatio = 1/2;
		$subColumnRatio = 4/5;

		$nextColumnX = $pageWidth / 2;
		$nextColumnY = $pdf->GetY();

		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->SetXY($pageWidth / 2 - $columnWidth, $pdf->GetY());
		$pdf->Cell($columnWidth * $subColumnRatio, 10, __("Energy sources"), 1, 2, 'C', true);
		$pdf->SetXY($pageWidth / 2 - $columnWidth * (1 - $subColumnRatio), $nextColumnY);
		$pdf->Cell($columnWidth * (1 - $subColumnRatio), 10, __("Penalty"), 1, 2, 'C', true);
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 10 );

		$currentColumnX = $pageWidth / 2 - $columnWidth;
		$currentColumnY = $pdf->GetY();

		$mpidTab = explode(';', $_POST["MPID_".$dcid]);
		foreach($mpidTab as &$tab)
			$tab = explode(',', $tab);

		$psText="";
		$psVals="";
		$psWeights="";
		foreach($mpidTab[0] as $mpid) {
			$psText .= $_POST['UPSname_'.$dcid.'_'.$mpid]."\n";
			$psVals .= $_POST['UPS_'.$dcid.'_'.$mpid]." kW.h\n";
			$psWeights .= $_POST['PUPS_'.$dcid.'_'.$mpid]."\n";
		}

		foreach($mpidTab[1] as $mpid) {
			$psText .= $_POST['noUPSname_'.$dcid.'_'.$mpid]."\n";
			$psVals .= $_POST['noUPS_'.$dcid.'_'.$mpid]." kW.h\n";
			$psWeights .= $_POST['PnoUPS_'.$dcid.'_'.$mpid]."\n";
		}
		
		if($mpidTab[0][0] != "" || $mpidTab[1][0]) {
			$pdf->SetXY($currentColumnX, $currentColumnY);
			$pdf->MultiCell($columnWidth * $subColumnRatio * $textRatio, 5, $psText, 'TLB', 'L', true);

			$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio * $textRatio, $currentColumnY);
			$pdf->MultiCell($columnWidth * $subColumnRatio * (1-$textRatio), 5, $psVals, 'TRB', 'R', true);

			$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio, $currentColumnY);
			$pdf->MultiCell($columnWidth * (1 - $subColumnRatio), 5, $psWeights, 1, 'R', true);
		}

		$currentColumnY = $pdf->GetY();
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->MultiCell($columnWidth * $subColumnRatio * $textRatio, 5, __("Total Energy Consumption"), 'TLB', 'L', true);

		$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio * $textRatio, $currentColumnY);
		$pdf->MultiCell($columnWidth * $subColumnRatio * (1-$textRatio), 5, intval($_POST['UPS_'.$dcid.'_tot']).' kW.h', 'TRB', 'R', true);

		$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio, $currentColumnY);
		$pdf->MultiCell($columnWidth * (1 - $subColumnRatio), 5, '', 1, 'R', true);

		$currentColumnY = $pdf->GetY();
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell($columnWidth, 10, __("Energy Reuse"), 1, 2, 'C', true);
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 10 );

		$reuseText="";
                $reuseVals="";
                foreach($mpidTab[3] as $mpid) {
                        $reuseText .= $_POST['Reusename_'.$dcid.'_'.$mpid]."\n";
                        $reuseVals .= $_POST['Reuse_'.$dcid.'_'.$mpid]." kW.h\n";
                }

                if($mpidTab[3][0] != "") {
                        $currentColumnY = $pdf->GetY();
                        $pdf->SetXY($currentColumnX, $currentColumnY);
                        $pdf->MultiCell($columnWidth * $textRatio, 5, $reuseText, 'TLB', 'L', true);

                        $pdf->SetXY($currentColumnX + $columnWidth * $textRatio, $currentColumnY);
                        $pdf->MultiCell($columnWidth * (1-$textRatio), 5, $reuseVals, 'TRB', 'R', true);
                }

		$currentColumnY = $pdf->GetY();
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->Cell($columnWidth * $textRatio, 5, __("Ratio used for IT"), 'TLB', 0, 'L', true);
		$pdf->Cell($columnWidth * (1 - $textRatio), 5, $_POST["Reuse_".$dcid."_Ratio"], 'TRB', 2, 'R', true);

		$currentColumnY = $pdf->GetY();
                $pdf->SetXY($currentColumnX, $currentColumnY);
                $pdf->Cell($columnWidth * $textRatio, 5, __("Total Energy Reused"), 'LTB', 0, 'L', true);
                $pdf->Cell($columnWidth * (1 - $textRatio), 5, $_POST["Reuse_".$dcid."_tot"]." kW.h", 'RTB', 2, 'R', true);

		$currentColumnY = $pdf->GetY();
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->Cell($columnWidth, 10, __("Renewable Energy"), 1, 2, 'C', true);
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 10 );

		$renText="";
                $renVals="";
                foreach($mpidTab[4] as $mpid) {
                        $renText .= $_POST['Renewablename_'.$dcid.'_'.$mpid]."\n";
                        $renVals .= $_POST['Renewable_'.$dcid.'_'.$mpid]." kW.h\n";
                }

		if($mpidTab[4][0] != "") {
			$currentColumnY = $pdf->GetY();
			$pdf->SetXY($currentColumnX, $currentColumnY);
			$pdf->MultiCell($columnWidth * $textRatio, 5, $renText, 'TLB', 'L', true);

			$pdf->SetXY($currentColumnX + $columnWidth * $textRatio, $currentColumnY);
			$pdf->MultiCell($columnWidth * (1-$textRatio), 5, $renVals, 'TRB', 'R', true);
		}

		$currentColumnY = $pdf->GetY();
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->Cell($columnWidth * $textRatio, 5, __("Total Renewable Energy"), 'TLB', 0, 'L', true);
		$pdf->Cell($columnWidth * (1 - $textRatio), 5, $_POST["Renewable_".$dcid."_tot"]." kW.h", 'TRB', 2, 'R', true);

		$currentColumnX = $nextColumnX;
		$currentColumnY = $nextColumnY;
		$textRatio = 4/7;

		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 12 );
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->Cell($columnWidth * $subColumnRatio, 10, __("IT Energy"), 1, 2, 'C', true);
		$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio, $currentColumnY);
		$pdf->Cell($columnWidth * (1 - $subColumnRatio), 10, 'Penalty', 1, 2, 'C', true);
		$pdf->SetFont( $config->ParameterArray['PDFfont'], '', 10 );

		$pduText="";
		$pduVals="";
		$pduWeights="";
		foreach($mpidTab[2] as $mpid) {
			$pduText .= $_POST['ITname_'.$dcid.'_'.$mpid]."\n";
			$pduVals .= $_POST['IT_'.$dcid.'_'.$mpid]." kW.h\n";
			$pduWeights .= $_POST['PIT_'.$dcid.'_'.$mpid]."\n";
		}

		if($mpidTab[2][0] != "") {
			$currentColumnY = $pdf->GetY();
			$pdf->SetXY($currentColumnX, $currentColumnY);
			$pdf->MultiCell($columnWidth * $subColumnRatio * $textRatio, 5, $pduText, 'TLB', 'L', true);

			$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio * $textRatio, $currentColumnY);
			$pdf->MultiCell($columnWidth * $subColumnRatio * (1-$textRatio), 5, $pduVals, 'TRB', 'R', true);

			$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio, $currentColumnY);
			$pdf->MultiCell($columnWidth * (1 - $subColumnRatio), 5, $pduWeights, 1, 'R', true);
		}

		$currentColumnY = $pdf->GetY();
		$pdf->SetXY($currentColumnX, $currentColumnY);
		$pdf->MultiCell($columnWidth * $subColumnRatio * $textRatio, 5, __("IT Energy Consumption"), 'TLB', 'L', true);

		$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio * $textRatio, $currentColumnY);
		$pdf->MultiCell($columnWidth * $subColumnRatio * (1-$textRatio), 5, intval($_POST['IT_'.$dcid.'_tot']).' kW.h', 'TRB', 'R', true);

		$pdf->SetXY($currentColumnX + $columnWidth * $subColumnRatio, $currentColumnY);
		$pdf->MultiCell($columnWidth * (1 - $subColumnRatio), 5, '', 1, 'R', true);
	}
?>
