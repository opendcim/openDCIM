<?php
	require_once( 'db.inc.php' );
	require_once( 'facilities.inc.php' );

if(!$person->ReadAccess){
    // No soup for you.
    header('Location: '.redirect());
    exit;
}

	$subheader=__("Cabinet Audit Reporting");

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
		if ( $_REQUEST["startdate"] > "" )
			$startDate = date( "Y-m-d", strtotime( $_REQUEST["startdate"] ));
		else
			$startDate = date( "Y-m-d", strtotime( "1/1/2010"));
			
		if ( $_REQUEST["enddate"] > "" )
			$endDate = date( "Y-m-d", strtotime( $_REQUEST["enddate"] ));
		else
			$endDate = date( "Y-m-d" );
		
		$this->pdfconfig = new Config();
		$this->Link( 10, 8, 100, 20, 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] );
		if ( file_exists( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'] )) {
	    	$this->Image( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
		}
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,__("Information Technology Services"),0,0,'C');
    	$this->Ln(25);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, __("Cabinet Audits Report"), 0, 1, 'L' );
		$this->Cell( 50, 6, __("Dates").': ' . $startDate . ' - ' . $endDate, 0, 1, 'L' );
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
	<div>
		<div><label for="startdate">Start Date:</label></div>
		<div><input type="text" id="startdate" name="startdate"></div>
	</div>
	<div>
		<div><label for="enddate">End Date:</label></div>
		<div><input type="text" id="enddate" name="enddate"></div>
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
	
	if ( $_REQUEST["startdate"] > "" )
		$startDate = date( "Y-m-d", strtotime( $_REQUEST["startdate"] ));
	else
		$startDate = "2010-01-01";
	
	if ( $_REQUEST["enddate"] > "" )
		$endDate = date( "Y-m-d", strtotime( $_REQUEST["enddate"] ));
	else
		$endDate = date( "Y-m-d" );
		
		
	// If no data center was selected, then show all data centers, otherwise, add in a SQL clause
		
	if ( @intval($_REQUEST["datacenterid"]) > 0 ) {
		$dcLimit = sprintf( "ObjectID in (select CabinetID from fac_Cabinet where DataCenterID=%d) and", intval( $_REQUEST["datacenterid"] ));
		$dc->DataCenterID = $_REQUEST["datacenterid"];
		$dc->GetDataCenter();
		
		$cab->DataCenterID = $dc->DataCenterID;
		$cabList = $cab->ListCabinetsByDC();
	} else {
		$dcLimit = "";
		$dc->Name = "All Data Centers";
		
		$cabList = $cab->ListCabinets();
	}
	
	// First query - Summary of all auditors, including the total within the selected period and the date of the last audit
	
	$sql = sprintf( "select count(*) as TotalCabinets, a.*, CONCAT(b.LastName,', ',b.FirstName) as Name from fac_GenericLog a, fac_People b where a.Action=\"CertifyAudit\" and a.UserID=b.UserID and %s date(Time)>='%s' and date(Time)<='%s' group by UserID order by count(*) ASC", $dcLimit, $startDate, $endDate );
	$summaryResult=$dbh->query($sql);
	
	// Second query - List of all cabinets audits in the time period, sorted and grouped by date
	
	$sql = sprintf( "select count(*) as TotalCabinets, date(a.Time) as AuditDate from fac_GenericLog a where a.Action=\"CertifyAudit\" and %s date(Time)>='%s' and date(Time)<='%s' group by date(a.Time) order by a.Time ASC", $dcLimit, $startDate, $endDate );
	$dateSumResult=$dbh->query($sql);
	
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
	$pdf->Bookmark( "Auditor Summary" );
	
	$pdf->Cell( 80, 5, __("Auditor Summary") );
	$pdf->Ln();
	
	$headerTags = array( __("UserID"), __("UserName"), __("Count"), __("Last Audit") );
	$cellWidths = array( 20, 50, 20, 40 );

	$fill = 0;
		
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();

	foreach($summaryResult as $row){
		$sql = "select date(Time) as LastAudit from fac_GenericLog where Action='CertifyAudit' and UserID='" . $row["UserID"] . "' and $dcLimit date(Time)>='$startDate' and date(Time)<='$endDate' order by Time desc limit 1";
		$res = $dbh->query( $sql );
		$lastRow = $res->fetch();
		
		$pdf->Cell( $cellWidths[0], 6, $row["UserID"], 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, $row["Name"], 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, $row["TotalCabinets"], 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], 6, $lastRow["LastAudit"], 'LR', 0, 'L', $fill );
		
		$pdf->Ln();
		
		$fill =! $fill;
	}	

	$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
	
	$pdf->AddPage();
	$pdf->Bookmark( "Activity by Date" );
	
	$pdf->Cell( 80, 5, __("Activity by Date") );
	$pdf->Ln();
	
	$headerTags = array( __("Date"), __("Cabinet Location"), __("UserName"), __("Comments") );
	$cellWidths = array( 30, 30, 40, 70 );
	
	$fill = 0;
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	//Check for locale
	if(sprintf('%.1f',1.0)!='1.0') {
		$dowCount = array( __("Mon")=>0, __("Tue")=>0, __("Wed")=>0, __("Thu")=>0, __("Fri")=>0, __("Sat")=>0, __("Sun")=>0 );
	} else {
		$dowCount = array( __("Sun")=>0, __("Mon")=>0, __("Tue")=>0, __("Wed")=>0, __("Thu")=>0, __("Fri")=>0, __("Sat")=>0 );
	}
	
	foreach($dateSumResult as $row){
		$auditDate = date( "Y-m-d", strtotime( $row["AuditDate"] ) );
		$dow = date( "D", strtotime( $row["AuditDate"] ) );
		$showDate = true;
		$pdf->Bookmark( $auditDate, 1, 0 );
		
		$sql = sprintf( "select b.Location as 'Cabinet Location', CONCAT(c.LastName,', ',c.FirstName) as Auditor, a.NewVal as Comments from fac_GenericLog a, fac_Cabinet b, fac_People c where a.Action=\"CertifyAudit\" and a.UserID=c.UserID and a.ObjectID=b.CabinetID and date(a.Time)=\"%s\"", $row["AuditDate"] );

		foreach($dbh->query($sql) as $resRow){		
			if ( $showDate ) {
				$pdf->Ln(4);
				$pdf->Cell( $cellWidths[0], 6, $auditDate, 'TLR', 0, 'L', $fill );
				$borders = "TLR";
			} else {
				$pdf->Cell( $cellWidths[0], 6, "", 'LR', 0, 'L', $fill );
				$borders = "LR";
			}
			
			$dowCount[$dow]++;
			
			// Only show the date on the first row of consecutive audits
			$showDate = false;
			
			$pdf->Cell( $cellWidths[1], 6, $resRow["Cabinet Location"], $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $resRow["Auditor"], $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, $resRow["Comments"], $borders, 0, 'L', $fill );
		
			$pdf->Ln();
			
			$fill =! $fill;
		}

		$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
		$pdf->Ln();

	}
	
	$totalAudits = array_sum( $dowCount );
	
	// Just to avoid any division by zero
	if ( $totalAudits < 1 )
		$totalAudits = 1;
	
	$pdf->AddPage();
	$pdf->Cell( 80, 5, __("Day of Week Frequency") );
	$pdf->Ln();
	$pdf->Bookmark( "Day of Week Frequency" );
	
	$headerTags = array( __("Day of Week"), __("Audits"), __("Percentage") );
	$cellWidths = array( 50, 30, 30 );
	
	$fill = 0;
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	foreach ( $dowCount as $dayName => $value ) {
		$pdf->Cell( $cellWidths[0], 7, $dayName, 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 7, $value, 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 7, sprintf( "%d%%", intval( $value / $totalAudits * 100 ) ), 'LR', 0, 'L', $fill );
		$pdf->Ln();
		
		$fill =! $fill;
	}
	
	$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );

	$pdf->AddPage();
	$pdf->Bookmark( "Activity by Location" );
	
	$pdf->Cell( 80, 5, __("Activity by Location") );
	$pdf->Ln();
	
	$headerTags = array( __("Cabinet Location"), __("Date"), __("UserName"), __("Comments") );
	$cellWidths = array( 30, 30, 40, 70 );
	
	$fill = 0;
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	foreach ( $cabList as $tmpCab ) {
		$sql = sprintf( "select a.Time as AuditDate, CONCAT(b.LastName,', ',b.FirstName) as Auditor, a.NewVal as Comments from fac_GenericLog a, fac_People b where a.Action=\"CertifyAudit\" and a.UserID=b.UserID and ObjectID='%d' and date(Time)>='%s' and date(Time)<='%s' order by Time DESC", $tmpCab->CabinetID, $startDate, $endDate );

		$showCab = true;

		foreach($dbh->query($sql) as $resRow){		
			if ( $showCab ) {
				$pdf->Bookmark( $tmpCab->Location, 1, 0 );
				$pdf->Ln(4);
				$pdf->Cell( $cellWidths[0], 6, $tmpCab->Location, 'TLR', 0, 'L', $fill );
				$borders = "TLR";
			} else {
				$pdf->Cell( $cellWidths[0], 6, "", 'LR', 0, 'L', $fill );
				$borders = "LR";
			}
			
			// Only show the location on the first row of consecutive audits
			$showCab = false;
			
			$pdf->Cell( $cellWidths[1], 6, $resRow["AuditDate"], $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $resRow["Auditor"], $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[3], 6, $resRow["Comments"], $borders, 0, 'L', $fill );
		
			$pdf->Ln();
			
			$fill =! $fill;
		}
				
		$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
		$pdf->Ln();

	}
	
	$pdf->Output();
	
}
?>

