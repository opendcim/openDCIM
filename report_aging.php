<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights();

	if(!$user->ReadAccess){
		header( "Location: ".redirect());
		exit;
	}

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

	$dept = new Department();
	$con = new Contact();

class DeviceAge extends Device{
 function GetAge( $db ) {
	$selectSQL = "select count(Label) as NumDevices,'<=1' as NumYears from fac_Device where (DATEDIFF(NOW(),IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)<=1 and (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)>0";
	$result = mysql_query( $selectSQL, $db );

	while ( $row = mysql_fetch_array( $result ) )
		$deptList[$row['NumYears']] = $row['NumDevices'];

	$selectSQL = "select count(*) as NumDevices,'<=2' as NumYears from fac_Device where (DATEDIFF(NOW(),IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)<=2 and (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)>1";
	$result = mysql_query( $selectSQL, $db );

	while ( $row = mysql_fetch_array( $result ) )
		$deptList[$row['NumYears']] = $row['NumDevices'];
	
	$selectSQL = "select count(*) as NumDevices,'<=3' as NumYears from fac_Device where (DATEDIFF(NOW(),IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)<=3 and (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)>2";
	$result = mysql_query( $selectSQL, $db );

	while ( $row = mysql_fetch_array( $result ) )
		$deptList[$row['NumYears']] = $row['NumDevices'];
	
	$selectSQL = "select count(*) as NumDevices,'<=4' as NumYears from fac_Device where (DATEDIFF(NOW(),IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)<=4 and (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)>3";
	$result = mysql_query( $selectSQL, $db );

	while ( $row = mysql_fetch_array( $result ) )
		$deptList[$row['NumYears']] = $row['NumDevices'];
	
	$selectSQL = "select count(*) as NumDevices,'>4' as NumYears from fac_Device where (DATEDIFF(NOW(),fac_Device.MfgDate)/365)>4 and IF(MfgDate>'1970-01-01',MfgDate,InstallDate)>'1970-01-01'";
	$result = mysql_query( $selectSQL, $db );

	while ( $row = mysql_fetch_array( $result ) )
		$deptList[$row['NumYears']] = $row['NumDevices'];
	
	$selectSQL = "select count(*) as NumDevices,'Unknown' as NumYears from fac_Device where IF(MfgDate>'1970-01-01',MfgDate,InstallDate)<'1970-01-01'";
	$result = mysql_query( $selectSQL, $db );

	while ( $row = mysql_fetch_array( $result ) )
		$deptList[$row['NumYears']] = $row['NumDevices'];
	
	return $deptList;
}

function GetDeviceByAge($db,$years){
	$deviceList=array();
	if($years<=3){
		$yearsplus=$years+1;
		$selectSQL = sprintf( "select * from fac_Device where (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)<%d and (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)>=%d", $yearsplus, $years );
		$result = mysql_query($selectSQL,$db);
		while($deviceRow=mysql_fetch_array($result)){
			$devID = $deviceRow['DeviceID'];

			$deviceList[$devID] = new DeviceAge();
			$deviceList[$devID]->DeviceID = $deviceRow['DeviceID'];
			$deviceList[$devID]->Label = $deviceRow['Label'];
			$deviceList[$devID]->SerialNo = $deviceRow['SerialNo'];
			$deviceList[$devID]->AssetTag = $deviceRow['AssetTag'];
			$deviceList[$devID]->PrimaryIP = $deviceRow['PrimaryIP'];
			$deviceList[$devID]->SNMPCommunity = $deviceRow['SNMPCommunity'];
			$deviceList[$devID]->ESX = $deviceRow['ESX'];
			$deviceList[$devID]->Owner = $deviceRow['Owner'];
			$deviceList[$devID]->PrimaryContact = $deviceRow['PrimaryContact'];
			$deviceList[$devID]->Cabinet = $deviceRow['Cabinet'];
			$deviceList[$devID]->Position = $deviceRow['Position'];
			$deviceList[$devID]->Height = $deviceRow['Height'];
			$deviceList[$devID]->Ports = $deviceRow['Ports'];
			$deviceList[$devID]->TemplateID = $deviceRow['TemplateID'];
			$deviceList[$devID]->NominalWatts = $deviceRow['NominalWatts'];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow['PowerSupplyCount'];
			$deviceList[$devID]->DeviceType = $deviceRow['DeviceType'];
			$deviceList[$devID]->MfgDate = $deviceRow['MfgDate'];
			$deviceList[$devID]->InstallDate = $deviceRow['InstallDate'];
			$deviceList[$devID]->Notes = $deviceRow['Notes'];
			$deviceList[$devID]->Reservation = $deviceRow['Reservation'];
		}
	}else{
		$selectSQL="select * from fac_Device where (DATEDIFF(NOW(), IF(MfgDate>'1970-01-01',MfgDate,InstallDate))/365)>4 and IF(MfgDate>'1970-01-01',MfgDate,InstallDate)>'1970-01-01'";
    	$result=mysql_query($selectSQL,$db);

		while($deviceRow=mysql_fetch_array($result)){
			$devID=$deviceRow['DeviceID'];

			$deviceList[$devID]=new DeviceAge();
			$deviceList[$devID]->DeviceID = $deviceRow['DeviceID'];
			$deviceList[$devID]->Label = $deviceRow['Label'];
			$deviceList[$devID]->SerialNo = $deviceRow['SerialNo'];
			$deviceList[$devID]->AssetTag = $deviceRow['AssetTag'];
			$deviceList[$devID]->PrimaryIP = $deviceRow['PrimaryIP'];
			$deviceList[$devID]->SNMPCommunity = $deviceRow['SNMPCommunity'];
			$deviceList[$devID]->ESX = $deviceRow['ESX'];
			$deviceList[$devID]->Owner = $deviceRow['Owner'];
			$deviceList[$devID]->PrimaryContact = $deviceRow['PrimaryContact'];
			$deviceList[$devID]->Cabinet = $deviceRow['Cabinet'];
			$deviceList[$devID]->Position = $deviceRow['Position'];
			$deviceList[$devID]->Height = $deviceRow['Height'];
			$deviceList[$devID]->Ports = $deviceRow['Ports'];
			$deviceList[$devID]->TemplateID = $deviceRow['TemplateID'];
			$deviceList[$devID]->NominalWatts = $deviceRow['NominalWatts'];
			$deviceList[$devID]->PowerSupplyCount = $deviceRow['PowerSupplyCount'];
			$deviceList[$devID]->DeviceType = $deviceRow['DeviceType'];
			$deviceList[$devID]->MfgDate = $deviceRow['MfgDate'];
			$deviceList[$devID]->InstallDate = $deviceRow['InstallDate'];
			$deviceList[$devID]->Notes = $deviceRow['Notes'];
			$deviceList[$devID]->Reservation = $deviceRow['Reservation'];
		}
	}
    return $deviceList;
}

}
	$dev = new DeviceAge();
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
    	$this->Cell(30,20,__('Information Technology Services'),0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, __('Data Center Asset Aging Report'), 0, 1, 'L' );
		$this->Cell( 50, 6, __('Date').': ' . date( 'm/d/y' ), 0, 1, 'L' );
		$this->Ln(10);
	}

	function Footer() {
	    	$this->SetY(-15);
    		$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'I',8);
    		$this->Cell(0,10,__('Page').' '.$this->PageNo().'/{nb}',0,0,'C');
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
	$agingList = $dev->GetAge( $facDB );
	$yearoldlist=$dev->GetDeviceByAge($facDB, 0);
	$year2oldlist=$dev->GetDeviceByAge($facDB,1);
	$year3oldlist=$dev->GetDeviceByAge($facDB,2);
	$year4oldlist=$dev->GetDeviceByAge($facDB,3);
	$oldestlist=$dev->GetDeviceByAge($facDB,4);
  
  


//
//
//	Begin Report Generation
//
//

	$pdf=new PDF_Diag($facDB);
	$pdf->AliasNbPages();
	$pdf->AddPage();
	
	$colors[0]=array(100,100,255);
	$colors[1]=array(255,100,100);
	$colors[2]=array(255,255,100);
	$colors[3]=array(170,170,255);
	$colors[4]=array(0,255,255);
	$colors[5]=array(255,0,0);
	$colors[6]=array(0,255,0);
	$colors[7]=array(0,0,255);
	$colors[8]=array(100,175,255);
	$colors[9]=array(255,175,100);
  

	$pdf->SetFont( $config->ParameterArray['PDFfont'],'B', 16 );

	$pdf->Cell( 0, 18, __('Device Ages by Count'), '', 1, 'C', 0 );
	$pdf->SetXY( 10, 70 );
	$pdf->PieChart(200, 80, $agingList, '%l years %v machines (%p)', $colors);

	$pdf->AddPage();
	$pdf->SetFillColor(224,235,255);
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'B', 12 );
	$pdf->Cell( 0, 18, __('Devices from 0-1 Years Old'), '', 1, 'C', 0 );
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'', 7 );
	$headerTags = array( __('Label'),__('Age'),__('Owner'),__('Primary Contact') );
	$cellWidths = array( 45, 30, 50, 45 );
	$maxval = count( $headerTags );
	for ( $col = 0; $col < $maxval ; $col++ )
			$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0 );
	$pdf->Ln();
	$fill=1;
	foreach( $yearoldlist as $devRow){
		$dept->DeptID=$devRow->Owner;
		$dept->GetDeptByID();
		$con->ContactID=$devRow->PrimaryContact;
		$con->GetContactByID();
		if ( $devRow->MfgDate > "1970-01-01" )
			$date1=new DateTime($devRow->MfgDate);
		else
			$date1 = new DateTime($devRow->InstallDate);
			
		$date2=new DateTime('now');
		$interval=$date1->diff($date2);
		$years=$interval->format('%y years %d days');
		
		$cellHeight = 6;
		
		$pdf->Cell( $cellWidths[0], $cellHeight, $devRow->Label, 1, 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], $cellHeight, $years, 1, 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], $cellHeight, $dept->Name, 1, 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], $cellHeight, $con->FirstName.' '.$con->LastName, 1, 1, 'L', $fill );

		$fill=!$fill;
	}


	$pdf->AddPage();
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'B', 16 );
	$pdf->Cell( 0, 18, __('Devices from 1-2 Years Old'), '', 1, 'C', 0 );
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'', 10 );
	$headerTags = array( __('Label'),__('Age'),__('Owner'),__('Primary Contact') );
        $cellWidths = array( 45, 30, 50, 45 );
		$maxval = count( $headerTags );
        for ( $col = 0; $col < $maxval; $col++ )
                $pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0 );
        $pdf->Ln();
	$fill=1;
	if(count($year2oldlist)>0){
		foreach($year2oldlist as $devRow){
			$dept->DeptID=$devRow->Owner;
			$dept->GetDeptByID();
			$con->ContactID=$devRow->PrimaryContact;
			$con->GetContactByID();
			$date1=new DateTime($devRow->MfgDate);
			$date2=new DateTime('now');
			$interval=$date1->diff($date2);
			$years=$interval->format('%y years %d days');

			$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[1], 6, $years, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[2], 6, $dept->Name, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[3], 6, $con->FirstName.' '.$con->LastName, 'LBRT', 1, 'L', $fill );

			$fill=!$fill;
		}
	}


	$pdf->AddPage();
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'B', 16 );
	$pdf->Cell( 0, 18, __('Devices from 2-3 Years Old'), '', 1, 'C', 0 );
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'', 10 );
	$headerTags = array( __('Label'),__('Age'),__('Owner'),__('Primary Contact') );
        $cellWidths = array( 45, 30, 50, 45 );
		$maxval = count( $headerTags );
        for ( $col = 0; $col < $maxval; $col++ )
                $pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0 );
        $pdf->Ln();
	$fill=1;
	if(count($year3oldlist)>0){
		foreach($year3oldlist as $devRow){
			$dept->DeptID=$devRow->Owner;
			$dept->GetDeptByID();
			$con->ContactID=$devRow->PrimaryContact;
			$con->GetContactByID();
			$date1=new DateTime($devRow->MfgDate);
			$date2=new DateTime('now');
			$interval=$date1->diff($date2);
			$years=$interval->format('%y years %d days');

			$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[1], 6, $years, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[2], 6, $dept->Name, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[3], 6, $con->FirstName.' '.$con->LastName, 'LBRT', 1, 'L', $fill );

			$fill=!$fill;
		}
	}


	$pdf->AddPage();
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'B', 16 );
	$pdf->Cell( 0, 18, __('Devices from 3-4 Years Old'), '', 1, 'C', 0 );
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'', 10 );
	$headerTags = array( __('Label'),__('Age'),__('Owner'),__('Primary Contact') );
        $cellWidths = array( 45, 30, 50, 45 );
		$maxval = count( $headerTags );
        for ( $col = 0; $col < $maxval; $col++ )
                $pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0 );
        $pdf->Ln();
	$fill=1;
	if(count($year4oldlist)>0){
		foreach($year4oldlist as $devRow){
			$dept->DeptID=$devRow->Owner;
			$dept->GetDeptByID();
			$con->ContactID=$devRow->PrimaryContact;
			$con->GetContactByID();
			$date1=new DateTime($devRow->MfgDate);
			$date2=new DateTime('now');
			$interval=$date1->diff($date2);
			$years=$interval->format('%y years %d days');

			$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[1], 6, $years, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[2], 6, $dept->Name, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[3], 6, $con->FirstName.' '.$con->LastName, 'LBRT', 1, 'L', $fill );

			$fill=!$fill;
		}
	}


	$pdf->AddPage();
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'B', 16 );
	$pdf->Cell( 0, 18, __('Devices Greater Than 4 Years Old'), '', 1, 'C', 0 );
	$pdf->SetFont( $config->ParameterArray['PDFfont'],'', 10 );
	$headerTags = array( __('Label'),__('Age'),__('Owner'),__('Primary Contact') );
        $cellWidths = array( 45, 30, 50, 45 );
		$maxval = count( $headerTags );
        for ( $col = 0; $col < $maxval; $col++ )
                $pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 0 );
        $pdf->Ln();
	$fill=1;
	if(count($oldestlist)>0){
		foreach($oldestlist as $devRow){
			$dept->DeptID=$devRow->Owner;
			$dept->GetDeptByID();
			$con->ContactID=$devRow->PrimaryContact;
			$con->GetContactByID();
			$date1=new DateTime($devRow->MfgDate);
			$date2=new DateTime('now');
			$interval=$date1->diff($date2);
			$years=$interval->format('%y years %d days');

			$pdf->Cell( $cellWidths[0], 6, $devRow->Label, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[1], 6, $years, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[2], 6, $dept->Name, 'LBRT', 0, 'L', $fill );
					$pdf->Cell( $cellWidths[3], 6, $con->FirstName.' '.$con->LastName, 'LBRT', 1, 'L', $fill );

			$fill=!$fill;
		}
	}

	$pdf->Output();
?>
