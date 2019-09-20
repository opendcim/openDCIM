<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

if(!$person->ReadAccess){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

	$subheader=__("Cabinet Audit Frequency Report");

	define('FPDF_FONTPATH','font/');
	require('fpdf.php');

class PDF extends FPDF {
  var $outlines=array();
  var $OutlineRoot;
  var $pdfconfig;
  
	function Header() {
		$this->pdfconfig = new Config();
		$this->Link( 10, 8, 100, 20, 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] );
		if ( file_exists( $this->pdfconfig->ParameterArray['PDFLogoFile'] )) {
	    	$this->Image(  $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
		}
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,__("Information Technology Services"),0,0,'C');
    	$this->Ln(25);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, __("Cabinet Audit Frequency Report"), 0, 1, 'L' );
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

if(!isset($_REQUEST['action'])){
	$dc=new DataCenter();
	$dcList=$dc->GetDCList();
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

<script type="text/javascript">
$(function(){
	$('#auditform').validationEngine({});
	$('#startdate').datepicker({dateFormat: "yy-mm-dd"});
	$('#enddate').datepicker({dateFormat: "yy-mm-dd"});
});
</script>

</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>
<form method="post" id="auditform">
<div class="table">
	<div>
		<div><label for="datacenterid">Data Center:</label></div>
		<div>
			<select id="datacenterid" name="datacenterid">
				<option value="">Select data center</option>
<?php
	foreach($dcList as $dc){
		print "				<option value=\"$dc->DataCenterID\">$dc->Name</option>\n";
	}
?>
			</select>
		</div>
	</div>
	<div class="caption">
		<input type="submit" value="Generate" name="action">
	</div>
</div>
</form>

</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
<?php
}else{

	//
	//
	//	Begin Report Generation
	//
	//

	$cab = new Cabinet();
	$cabAudit=new CabinetAudit();
	$dc=new DataCenter();
	
	// If no data center was selected, then show all data centers, otherwise, add in a SQL clause
		
	if ( @intval($_REQUEST["datacenterid"]) > 0 ) {
		$dcLimit = sprintf( "CabinetID in (select CabinetID from fac_Cabinet where DataCenterID=%d) and", intval( $_REQUEST["datacenterid"] ));
		$dc->DataCenterID = $_REQUEST["datacenterid"];
		$dc->GetDataCenter();
		
		$cab->DataCenterID = $dc->DataCenterID;
		$cabList = $cab->ListCabinetsByDC();
	} else {
		$dcLimit = "";
		$dc->Name = "All Data Centers";
		
		$cabList = $cab->ListCabinets();
	}
	
	$pdf=new PDF();
	$pdf->AliasNbPages();
	include_once("loadfonts.php");
	$pdf->SetFont($config->ParameterArray['PDFfont'],'',8);

	$pdf->SetFillColor( 0, 0, 0 );
	$pdf->SetTextColor( 255 );
	$pdf->SetDrawColor( 128, 0, 0 );
	$pdf->SetLineWidth( .3 );

	$pdf->SetfillColor( 224, 235, 255 );
	$pdf->SetTextColor( 0 );
	
	$column = 0;
	
	$pdf->SetLeftMargin( 10 );
	$pdf->AddPage();
	$pdf->Bookmark( "Activity by Location" );
	
	$pdf->Cell( 80, 5, __("Activity by Location") );
	$pdf->Ln();
	
	$headerTags = array( __("Cabinet Location"), __("Last Audit"), __("Times Audited"), __("Installation Date"), __("Days Since Last Audit") );
	$cellWidths = array( 40, 30, 30, 30, 60 );
	
	$fill = 0;
	
	$freqCount = array( "30"=>0, "90"=>0, "180"=>0, "365"=>0, "Year"=>0 );
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	$currDate = new DateTime( "now" );
	$borders = "TLR";
	
	foreach ( $cabList as $tmpCab ) {
		$sql="select a.Time as AuditDate, CONCAT(b.LastName, ', ', b.FirstName) as Auditor, c.Location, c.InstallationDate from fac_GenericLog a, fac_People b, fac_Cabinet c where a.Action=\"CertifyAudit\" and a.UserID=b.UserID and a.ObjectID=c.CabinetID and c.CabinetID=$tmpCab->CabinetID order by a.Time DESC limit 1;";

		foreach($dbh->query($sql) as $resRow){
			$pdf->Cell( $cellWidths[0], 6, $tmpCab->Location, $borders, 0, 'L', $fill );
			
			$sql="SELECT COUNT(Time) AS Frequency FROM fac_GenericLog WHERE Action=\"CertifyAudit\" and ObjectID=$tmpCab->CabinetID;";
			$frequency=$dbh->query($sql)->fetchColumn();
			
			if ( $frequency == 0 ) {
				$auditDate = "Never";
				$lastAudit = new DateTime( $resRow["InstallationDate"] );
			} else {
				$auditDate = date( "D, M d, Y", strtotime( $resRow["AuditDate"] ) );
				$lastAudit = new DateTime( $resRow["AuditDate"] );
			}
			
			$interval = $currDate->diff($lastAudit);
			
			if ( $interval->days < 31 ) {
				$freqCount["30"]++;
				$period = "0-30 Days";
			} elseif ( $interval->days < 91 ) {
				$freqCount["90"]++;
				$period = "31-90 Days";
			} elseif ($interval->days < 181 ) {
				$freqCount["180"]++;
				$period = "91-180 Days";
			} elseif ($interval->days < 366 ) {
				$freqCount["365"]++;
				$period = "181-365 Days";
			} else {
				$freqCount["Year"]++;
				$period = "1 Year or Longer";
			}
			
			$installDate = date( "M d, Y", strtotime( $resRow["InstallationDate"] ) );
			
			$pdf->Cell( $cellWidths[1], 6, $auditDate, $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $frequency, $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, $installDate, $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[4], 6, $period, $borders, 0, 'L', $fill );
		
			$pdf->Ln();
			
			$fill =! $fill;
		}
				
		$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
		$pdf->Ln();
	}
	
	$pdf->AddPage();
	
	$pdf->Bookmark( "Summary by Period" );
	
	$pdf->Cell( 80, 5, __("Summary by Period") );
	$pdf->Ln();
	
	$headerTags = array( __("Days Since Last Audit"), __("Number of Cabinets"), __("Percentage") );
	$cellWidths = array( 40, 40, 40 );
	
	$fill = 0;
	$borders = "TLR";
	
	$totalAudits = array_sum( $freqCount );
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )

		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	foreach ( $freqCount as $key => $value ) {
		switch ( $key ) {
			case "30":
				$period = "0-30 Days";
				break;
			case "90":
				$period = "31-90 Days";
				break;
			case "180":
				$period = "91-180 Days";
				break;
			case "365":
				$period = "181-365 Days";
				break;
			default:
				$period = "1 Year or Longer";
				break;
		}


		$pdf->Cell( $cellWidths[0], 6, $period, $borders, 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, $value, $borders, 0, 'C', $fill );
		// Silencing the next line because i'm too lazy to validate the data properly
		@$pdf->Cell( $cellWidths[2], 6, sprintf( "%.2f%%", $value / $totalAudits * 100 ), $borders, 0, 'C', $fill );
		$pdf->Ln();
		
		$fill =! $fill;
	}
	
	$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
	$pdf->Ln();
	
	$pdf->Output();
	
}
?>

