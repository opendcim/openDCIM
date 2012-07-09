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
		if ( $_REQUEST["startdate"] > "" )
			$startDate = date( "M d, Y", strtotime( $_REQUEST["startdate"] ));
		else
			$startDate = date( "M d, Y", strtotime( "1/1/2010"));
			
		if ( $_REQUEST["enddate"] > "" )
			$endDate = date( "M d, Y", strtotime( $_REQUEST["enddate"] ));
		else
			$endDate = date( "M d, Y" );
		
		$this->pdfconfig = new Config($this->pdfDB);
		$this->Link( 10, 8, 100, 20, 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] );
    	$this->Image( 'images/' . $this->pdfconfig->ParameterArray['PDFLogoFile'],10,8,100);
    	$this->SetFont($this->pdfconfig->ParameterArray['PDFfont'],'B',12);
    	$this->Cell(120);
    	$this->Cell(30,20,'Information Technology Services',0,0,'C');
    	$this->Ln(20);
		$this->SetFont( $this->pdfconfig->ParameterArray['PDFfont'],'',10 );
		$this->Cell( 50, 6, 'Cabinet Audits Report', 0, 1, 'L' );
		$this->Cell( 50, 6, 'Dates: ' . $startDate . ' - ' . $endDate, 0, 1, 'L' );
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

if ( @$_REQUEST['action'] != 'Generate' ) {

	$user = new User();
	$user->UserID = $_SERVER['REMOTE_USER'];
	$user->GetUserRights( $facDB );

	if ( ! $user->ReadAccess ) {
		header( "Location: ".redirect());
		exit;
	}

	$dc = new DataCenter();
	$dcList = $dc->GetDCList( $facDB );
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Inventory Reporting</title>
  <link rel="stylesheet" href="css/inventory.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui-1.8.18.custom.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui-1.8.18.custom.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.timepicker.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>

<script type="text/javascript">
$(function(){
	$('#auditform').validationEngine({});
	$('#startdate').datepicker({});
	$('#enddate').datepicker({});
});
</script>

</head>
<body>
<div id="header"></div>
<div class="page">
<?php
	include( 'sidebar.inc.php' );
?>
<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Cabinet Audit Reporting</h3>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="auditform">
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
		$dcLimit = sprintf( "CabinetID in (select CabinetID from fac_Cabinet where DataCenterID=%d) and", intval( $_REQUEST["datacenterid"] ));
		$dc->DataCenterID = $_REQUEST["datacenterid"];
		$dc->GetDataCenter( $facDB );
		
		$cab->DataCenterID = $dc->DataCenterID;
		$cabList = $cab->ListCabinetsByDC( $facDB );
	} else {
		$dcLimit = "";
		$dc->Name = "All Data Centers";
		
		$cabList = $cab->ListCabinets( $facDB );
	}
	
	// First query - Summary of all auditors, including the total within the selected period and the date of the last audit
	
	$sql = sprintf( "select count(*) as TotalCabinets, a.*, b.Name from fac_CabinetAudit a, fac_User b where a.UserID=b.UserID and %s AuditStamp>='%s' and AuditStamp<='%s' group by UserID order by count(*) ASC", $dcLimit, $startDate, $endDate );
	$summaryResult = mysql_query( $sql, $facDB );
	
	// Second query - List of all cabinets audits in the time period, sorted and grouped by date
	
	$sql = sprintf( "select count(*) as TotalCabinets, date(a.AuditStamp) as AuditDate from fac_CabinetAudit a where %s AuditStamp>='%s' and AuditStamp<='%s' group by date(a.AuditStamp) order by a.AuditStamp ASC", $dcLimit, $startDate, $endDate );
	$dateSumResult = mysql_query( $sql, $facDB );
	
	$pdf=new PDF($facDB);
	$pdf->AliasNbPages();

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
	
	$pdf->Cell( 80, 5, "Auditor Summary" );
	$pdf->Ln();
	
	$headerTags = array( 'User ID', 'Name', 'Count', 'Last Audit' );
	$cellWidths = array( 20, 50, 20, 40 );

	$fill = 0;
		
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();

	while ( $row = mysql_fetch_array( $summaryResult ) ) {
		$cabAudit->UserID = $row["UserID"];
		$cabAudit->GetLastAuditByUser( $facDB );
		
		$pdf->Cell( $cellWidths[0], 6, $row["UserID"], 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[1], 6, $row["Name"], 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[2], 6, $row["TotalCabinets"], 'LR', 0, 'L', $fill );
		$pdf->Cell( $cellWidths[3], 6, $cabAudit->AuditStamp, 'LR', 0, 'L', $fill );
		
		$pdf->Ln();
		
		$fill =! $fill;
	}	

	$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
	
	$pdf->AddPage();
	$pdf->Bookmark( "Activity by Date" );
	
	$pdf->Cell( 80, 5, "Activity by Date" );
	$pdf->Ln();
	
	$headerTags = array( "Date", "Location", "Auditor" );
	$cellWidths = array( 40, 40, 50 );
	
	$fill = 0;
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	$dowCount = array( "Sun"=>0, "Mon"=>0, "Tue"=>0, "Wed"=>0, "Thu"=>0, "Fri"=>0, "Sat"=>0 );

	while ( $row = mysql_fetch_array( $dateSumResult ) ) {
		$auditDate = date( "D, M d, Y", strtotime( $row["AuditDate"] ) );
		$dow = date( "D", strtotime( $row["AuditDate"] ) );
		$showDate = true;
		$pdf->Bookmark( $auditDate, 1, 0 );
		
		$sql = sprintf( "select b.Location, c.Name as Auditor from fac_CabinetAudit a, fac_Cabinet b, fac_User c where a.UserID=c.UserID and a.CabinetID=b.CabinetID and date(a.AuditStamp)=\"%s\"", $row["AuditDate"] );
		$res = mysql_query( $sql, $facDB );
		
		while ( $resRow = mysql_fetch_array( $res ) ) {
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
			
			$pdf->Cell( $cellWidths[1], 6, $resRow["Location"], $borders, 0, 'L', $fill );
			$pdf->Cell( $cellWidths[2], 6, $resRow["Auditor"], $borders, 0, 'L', $fill );
		
			$pdf->Ln();
			
			$fill =! $fill;
		}

		$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
		$pdf->Ln();

	}
	
	$totalAudits = array_sum( $dowCount );
	
	$pdf->AddPage();
	$pdf->Cell( 80, 5, "Day of Week Frequency" );
	$pdf->Ln();
	$pdf->Bookmark( "Day of Week Frequency" );
	
	$headerTags = array( "Day of Week", "Audits", "Percentage" );
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
	
	$pdf->Cell( 80, 5, "Activity by Location" );
	$pdf->Ln();
	
	$headerTags = array( "Location", "Date", "Auditor" );
	$cellWidths = array( 40, 40, 50 );
	
	$fill = 0;
	
	$maxval = count( $headerTags );

	for ( $col = 0; $col < $maxval; $col++ )
		$pdf->Cell( $cellWidths[$col], 7, $headerTags[$col], 1, 0, 'C', 1 );
		
	$pdf->Ln();
	
	foreach ( $cabList as $tmpCab ) {
		$sql = sprintf( "select a.AuditStamp as AuditDate, b.Name as Auditor from fac_CabinetAudit a, fac_User b where a.UserID=b.UserID and CabinetID='%d' and AuditStamp>='%s' and AuditStamp<='%s' order by AuditStamp DESC", $tmpCab->CabinetID, $startDate, $endDate );
		$res = mysql_query( $sql, $facDB );

		$showCab = true;
		
		while ( $resRow = mysql_fetch_array( $res ) ) {
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
		
			$pdf->Ln();
			
			$fill =! $fill;
		}
				
		$pdf->Cell( array_sum( $cellWidths ), 0, '', 'T' );
		$pdf->Ln();

	}
	
	$pdf->Output();
	
}
?>

