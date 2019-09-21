<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

	$dept = new Department();
	$device = new Device();
	$cab = new Cabinet();
	$dc = new DataCenter();
	$pdu=new PowerDistribution();
	$panel= new PowerPanel();
	$mfg=new Manufacturer();
	$templ=new DeviceTemplate();
	$pport = new PowerPorts();

	$audit=new CabinetAudit();
	$audit->CabinetID=$_REQUEST['cabinetid'];
	$audit->AuditStamp="Never";
	$audit->GetLastAudit();
	if($audit->UserID!=""){
		$tmpPerson=new People();
		$tmpPerson->UserID=$audit->UserID;
		$tmpPerson->GetUserRights();
		$AuditorName=$tmpPerson->LastName . ", " . $tmpPerson->FirstName;
	}else{
		//If no audit has been completed $AuditorName will return an error
		$AuditorName="";
	}
	$_SESSION['AuditorName']=$AuditorName;
	$_SESSION['AuditStamp']=$audit->AuditStamp;

class PDF extends FPDF {
  var $outlines=array();
  var $OutlineRoot;
  var $pdfconfig;

  
	function PDF(){
		parent::FPDF('L');
	}
  
	function Header() {
		$this->pdfconfig = new Config();
    	$this->Image(  $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(40,20,__("Information Technology Services"),0,0,'C');
    	$this->Ln(25);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, __("Cabinet Audit Report"), 0, 1, 'L' );
		$this->Cell( 50, 6, __("Date").': ' . date('d F Y'), 0, 1, 'L' );
		$this->Cell( 0, 6, __("Last Audit").": {$_SESSION['AuditStamp']} ({$_SESSION['AuditorName']})", 0, 1, 'R' );
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

class PDF_Sector extends PDF
{
    function Sector($xc, $yc, $r, $a, $b, $style='FD', $cw=true, $o=90)
    {
        if($cw){
            $d = $b;
            $b = $o - $a;
            $a = $o - $d;
        }else{
            $b += $o;
            $a += $o;
        }
        $a = ($a%360)+360;
        $b = ($b%360)+360;
        if ($a > $b)
            $b +=360;
        $b = $b/360*2*M_PI;
        $a = $a/360*2*M_PI;
        $d = $b-$a;
        if ($d == 0 )
            $d =2*M_PI;
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' or $style=='DF')
            $op='b';
        else
            $op='s';
        if (sin($d/2))
            $MyArc = 4/3*(1-cos($d/2))/sin($d/2)*$r;
        //first put the center
        $this->_out(sprintf('%.2f %.2f m',($xc)*$k,($hp-$yc)*$k));
        //put the first point
        $this->_out(sprintf('%.2f %.2f l',($xc+$r*cos($a))*$k,(($hp-($yc-$r*sin($a)))*$k)));
        //draw the arc
        if ($d < M_PI/2){
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
        }else{
            $b = $a + $d/4;
            $MyArc = 4/3*(1-cos($d/8))/sin($d/8)*$r;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
            $a = $b;
            $b = $a + $d/4;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
            $a = $b;
            $b = $a + $d/4;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
            $a = $b;
            $b = $a + $d/4;
            $this->_Arc($xc+$r*cos($a)+$MyArc*cos(M_PI/2+$a),
                        $yc-$r*sin($a)-$MyArc*sin(M_PI/2+$a),
                        $xc+$r*cos($b)+$MyArc*cos($b-M_PI/2),
                        $yc-$r*sin($b)-$MyArc*sin($b-M_PI/2),
                        $xc+$r*cos($b),
                        $yc-$r*sin($b)
                        );
        }
        //terminate drawing
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3 )
    {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
            $x1*$this->k,
            ($h-$y1)*$this->k,
            $x2*$this->k,
            ($h-$y2)*$this->k,
            $x3*$this->k,
            ($h-$y3)*$this->k));
    }
}

class PDF_Diag extends PDF_Sector {
    var $legends;
    var $wLegend;
    var $sum;
    var $NbVal;

    function PieChart($w, $h, $data, $format, $colors=null)
    {
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], '', 10);
        $this->SetLegends($data,$format);

        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;
        $hLegend = 5;
        $radius = min($w - $margin * 4 - $hLegend - $this->wLegend, $h - $margin * 2);
        $radius = floor($radius / 2);
        $XDiag = $XPage + $margin + $radius;
        $YDiag = $YPage + $margin + $radius;
        if($colors == null) {
            for($i = 0;$i < $this->NbVal; $i++) {
                $gray = $i * intval(255 / $this->NbVal);
                $colors[$i] = array($gray,$gray,$gray);
            }
        }

        //Sectors
        $this->SetLineWidth(0.2);
        $angleStart = 0;
        $angleEnd = 0;
        $i = 0;
        foreach($data as $val) {
            $angle = round(($val * 360) / doubleval($this->sum));
            if ($angle != 0) {
                $angleEnd = $angleStart + $angle;
                $this->SetFillColor($colors[$i][0],$colors[$i][1],$colors[$i][2]);
                $this->Sector($XDiag, $YDiag, $radius, $angleStart, $angleEnd);
                $angleStart += $angle;
            }
            $i++;
        }
        if ($angleEnd != 360) {
            $this->Sector($XDiag, $YDiag, $radius, $angleStart - $angle, 360);
        }

        //Legends
        $this->SetFont($this->pdfconfig->ParameterArray['PDFfont'], '', 10);
        $x1 = $XPage + 2 * $radius + 4 * $margin;
        $x2 = $x1 + $hLegend + $margin;
        $y1 = $YDiag - $radius + (2 * $radius - $this->NbVal*($hLegend + $margin)) / 2;
        for($i=0; $i<$this->NbVal; $i++) {
            $this->SetFillColor($colors[$i][0],$colors[$i][1],$colors[$i][2]);
            $this->Rect($x1, $y1, $hLegend, $hLegend, 'DF');
            $this->SetXY($x2,$y1);
            $this->Cell(0,$hLegend,$this->legends[$i]);
            $y1+=$hLegend + $margin;
        }
    }

    function BarDiagram($w, $h, $data, $format, $color=null, $maxVal=0, $nbDiv=4)
    {
        $this->SetFont('Courier', '', 10);
        $this->SetLegends($data,$format);

        $XPage = $this->GetX();
        $YPage = $this->GetY();
        $margin = 2;
        $YDiag = $YPage + $margin;
        $hDiag = floor($h - $margin * 2);
        $XDiag = $XPage + $margin * 2 + $this->wLegend;
        $lDiag = floor($w - $margin * 3 - $this->wLegend);
        if($color == null)
            $color=array(155,155,155);
        if ($maxVal == 0) {
            $maxVal = max($data);
        }
        $valIndRepere = ceil($maxVal / $nbDiv);
        $maxVal = $valIndRepere * $nbDiv;
        $lRepere = floor($lDiag / $nbDiv);
        $lDiag = $lRepere * $nbDiv;
        $unit = $lDiag / $maxVal;
        $hBar = floor($hDiag / ($this->NbVal + 1));
        $hDiag = $hBar * ($this->NbVal + 1);
        $eBaton = floor($hBar * 80 / 100);

        $this->SetLineWidth(0.2);
        $this->Rect($XDiag, $YDiag, $lDiag, $hDiag);

        $this->SetFont('Courier', '', 10);
        $this->SetFillColor($color[0],$color[1],$color[2]);
        $i=0;
        foreach($data as $val) {
            //Bar
            $xval = $XDiag;
            $lval = (int)($val * $unit);
            $yval = $YDiag + ($i + 1) * $hBar - $eBaton / 2;
            $hval = $eBaton;
            $this->Rect($xval, $yval, $lval, $hval, 'DF');
            //Legend
            $this->SetXY(0, $yval);
            $this->Cell($xval - $margin, $hval, $this->legends[$i],0,0,'R');
            $i++;
        }

        //Scales
        for ($i = 0; $i <= $nbDiv; $i++) {
            $xpos = $XDiag + $lRepere * $i;
            $this->Line($xpos, $YDiag, $xpos, $YDiag + $hDiag);
            $val = $i * $valIndRepere;
            $xpos = $XDiag + $lRepere * $i - $this->GetStringWidth($val) / 2;
            $ypos = $YDiag + $hDiag - $margin;
            $this->Text($xpos, $ypos, $val);
        }
    }

    function SetLegends($data, $format)
    {
        $this->legends=array();
        $this->wLegend=0;
        $this->sum=array_sum($data);
        $this->NbVal=count($data);
        foreach($data as $l=>$val)
        {
            $p=sprintf('%.2f',$val/$this->sum*100).'%';
            $legend=str_replace(array('%l','%v','%p'),array($l,$val,$p),$format);
            $this->legends[]=$legend;
            $this->wLegend=max($this->GetStringWidth($legend),$this->wLegend);
        }
    }
}

  
  


//
//
//	Begin Report Generation
//
//
	$pdf=new PDF();
	$pdf->SetLeftMargin(5);	
	$pdf->SetRightMargin(5);
	include_once("loadfonts.php");
	$cab->CabinetID=$_REQUEST['cabinetid'];
	$cab->GetCabinet();
	$device->Cabinet=$cab->CabinetID;
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFillColor(224,235,255);
	$fill=0;
	
	$cabmessage=__("Cabinet Location").': '.$cab->Location;
	$pdf->SetFont($config->ParameterArray['PDFfont'],'B',10);
	$pdf->Cell(0,5,$cabmessage,0,1,'C',0);
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',10);
	$deviceList = $device->ViewDevicesByCabinet();
	$headerTags = array( __("Label"), __("SerialNo"), __("AssetTag"), __("Position"), __("Rack Units"), __("#Ports"), __("#PS"), __("PowerConnection1"), __("PowerConnection2"), __("DeviceType") );
	$cellWidths = array( 45, 40, 20, 18, 20, 15, 10, 35, 35, 50 );
	$maxval = count( $headerTags );
	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
	$pdf->Ln();

	function printRow($devRow,$pport,$pdf,$templ,$fill,$cellWidths,$pdu,$mfg){
		global $fill;

		$pport->DeviceID=$devRow->DeviceID;
		$connList=$pport->getPorts();

		$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, $devRow->SerialNo, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, $devRow->AssetTag, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], 6, $devRow->Position, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[4], 6, $devRow->Height, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[5], 6, $devRow->Ports, 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[6], 6, $devRow->PowerSupplyCount, 'LBRT', 0, 'L', $fill );
		@$pdu->PDUID=$connList[1]->ConnectedDeviceID;
		$pdu->GetPDU();
		$pdf->Cell( $cellWidths[7], 6, (isset($connList[1]))?$pdu->Label.' ['.$connList[1]->ConnectedPort.']':"", 'LBRT', 0, 'L', $fill );
		@$pdu->PDUID=$connList[2]->ConnectedDeviceID;
		$pdu->GetPDU();
		$pdf->Cell( $cellWidths[8], 6, (isset($connList[2]))?$pdu->Label.' ['.$connList[2]->ConnectedPort.']':"", 'LBRT', 0, 'L', $fill );
		$templ->TemplateID=$devRow->TemplateID;
		$templ->GetTemplateByID();
		$mfg->ManufacturerID=$templ->ManufacturerID;
		$mfg->GetManufacturerByID();
		$pdf->Cell( $cellWidths[9], 6, $mfg->Name." ".$templ->Model, 'LBRT', 1, 'L', $fill );

		if(count($connList) >2){
			for($connCount=3; $connCount < count($connList); $connCount += 2 ) {
				$pdf->Cell( $cellWidths[0], 6, '', 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[1], 6, '', 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[2], 6, '', 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[3], 6, '', 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[4], 6, '', 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[5], 6, '', 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[6], 6, '', 'LBRT', 0, 'L', $fill );
				@$pdu->PDUID=$connList[$connCount]->ConnectedDeviceID;
				$pdu->GetPDU();
				$pdf->Cell( $cellWidths[7], 6, (isset($connList[$connCount]))?$pdu->Label.' ['.$connList[$connCount]->ConnectedPort.']':"", 'LBRT', 0, 'L', $fill );
				@$pdu->PDUID=$connList[$connCount+1]->ConnectedDeviceID;
				$pdu->GetPDU();
				$pdf->Cell( $cellWidths[8], 6, (isset($connList[$connCount+1]))?$pdu->Label.' ['.$connList[$connCount+1]->ConnectedPort.']':"", 'LBRT', 0, 'L', $fill );
				$pdf->Cell( $cellWidths[9], 6, '', 'LBRT', 1, 'L', $fill );
			}
		}

		$pdf->Cell( $cellWidths[0], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[4], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[5], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[6], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[7], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[8], 6, '', 'LBRT', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[9], 6, '', 'LBRT', 1, 'L', $fill );
		
		$fill=!$fill;

		if($devRow->DeviceType="Chassis1"){
			$childList=$devRow->GetDeviceChildren();
			foreach($childList as $childDev){
				printRow($childDev,$pport,$pdf,$templ,$fill,$cellWidths,$pdu,$mfg);
			}
		}
	}

	foreach($deviceList as $devRow){
		if($devRow->Cabinet!=$cab->CabinetID){
			$cab->CabinetID=$devRow->Cabinet;
			$cab->GetCabinet();
		}

		if($cab->DataCenterID!=$dc->DataCenterID){
			if($dc->DataCenterID >0){
				$pdf->Cell( 0, 5, 'Total Rack Units for ' . $dc->Name . ': ' . $DCRU, '', 1, 'L', '' );
				$pdf->Cell( 0, 5, 'Total BTU Output for ' . $dc->Name . ': ' . sprintf( '%d (%.2f Tons)', $DCBTU, $DCBTU/12000 ), '', 1, 'L', '' );
			}
		  
			$dc->DataCenterID=$cab->DataCenterID;
			$dc->GetDataCenterbyID();
			$DCRU=0;
			$DCBTU=0;
		}

		if ( $devRow->DeviceType != "CDU" ) {
			printRow($devRow,$pport,$pdf,$templ,$fill,$cellWidths,$pdu,$mfg);
		}

	}

	$pdf->AddPage();
	$fill=0;
	
	$pdu->CabinetID=$cab->CabinetID;
        $cabmessage=__("PDUs at").' '.$cabmessage;
	$pdf->SetFont($config->ParameterArray['PDFfont'],'B',10);
	$pdf->Cell(0,5,$cabmessage,0,1,'C',0);
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',10);
	$PDUList=$pdu->GetPDUbyCabinet();

	$headerTags = array( __("Label"), __("NumOutputs"),__("Model"),__("PanelLabel"), __("PanelPole") );
	$cellWidths = array(50,30,118,70,20);
	$maxval = count( $headerTags );
	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
	$pdf->Ln();
	foreach ($PDUList as $PDUrow){
			$panel->PanelID=$PDUrow->PanelID;
			$panel->getPanel();

			$pdutemp=new CDUTemplate();
			$pdutemp->TemplateID=$PDUrow->TemplateID;
			$pdutemp->GetTemplate();

			$mfg->ManufacturerID=$pdutemp->ManufacturerID;
			$mfg->GetManufacturerByID();
	
			$pdf->Cell( $cellWidths[0], 6, $PDUrow->Label, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[1], 6, $pdutemp->NumOutlets, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, "[$mfg->Name] $pdutemp->Model", 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, $panel->PanelLabel, 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[4], 6, $PDUrow->PanelPole, 'LBRT', 1, 'L', $fill );

			$pdf->Cell( $cellWidths[0], 6, '', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[1], 6, '', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, '', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, '', 'LBRT', 0, 'L', $fill );
			$pdf->Cell( $cellWidths[4], 6, '', 'LBRT', 1, 'L', $fill );
		$fill=!$fill;
		}
	$pdf->Output();
?>
