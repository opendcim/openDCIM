<?php
/*		Supply Status Report
		Will print out all supply types, list the Min/Max/Current quantities along with current locations
*/

	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );
	
	if(!$person->ReadAccess){
	    // No soup for you.
	    header('Location: '.redirect());
	    exit;
	}

	/* Version 1.0 of this report has no selectable parameters - you just get a complete dump */
	
	$mpdf=new mPDF('win-1252','A4','','',20,15,48,25,10,10); 
	$mpdf->useOnlyCoreFonts = true;    // false is default
	//$mpdf->SetProtection(array('print'));
	$mpdf->SetTitle($config->ParameterArray["OrgName"] . " " . __("Supply Status Report"));
	$mpdf->SetAuthor($config->ParameterArray["OrgName"]);
	$mpdf->SetDisplayMode('fullpage');
    $mpdf->useActiveForms = true;

	$sup = new Supplies();
	$bin = new SupplyBin();
	$bc = new BinContents();
	
	$SupplyList = $sup->GetSuppliesList();
	
	$html = '
<html>
<head>
<style>
body {font-family: sans-serif;
    font-size: 10pt;
}
p {    margin: 0pt;
}
td { vertical-align: top; }
.items td {
    border-left: 0.1mm solid #000000;
    border-right: 0.1mm solid #000000;
}
table thead td { background-color: #EEEEEE;
    text-align: center;
    border: 0.1mm solid #000000;
}
.items td.bottom {
    background-color: #FFFFFF;
    border: 0mm none #000000;
    border-top: 0.1mm solid #000000;
}
.items td.totals {
    text-align: right;
    border: 0.1mm solid #000000;
}
</style>
</head>
<body>

<!--mpdf
<htmlpageheader name="myheader">
<table width="100%"><tr>
<td width="50%" style="color:#0000BB;"><span style="font-weight: bold; font-size: 14pt;">'.$config->ParameterArray["OrgName"].'</span></td>
<td width="50%" style="text-align: right;">'.__("Date").':<span style="font-weight: bold; font-size: 12pt;">'.strftime("%x").'</span></td>
</tr></table>
</htmlpageheader>

<htmlpagefooter name="myfooter">
<div style="border-top: 1px solid #000000; font-size: 9pt; text-align: center; padding-top: 3mm; ">
Page {PAGENO} of {nb}
</div>
</htmlpagefooter>

<sethtmlpageheader name="myheader" value="on" show-this-page="1" />
<sethtmlpagefooter name="myfooter" value="on" />
mpdf-->

<h2>'.__("Supplies Status Report").'
<table class="items" width="100%" style="font-size: 9pt; border-collapse: collapse;" cellpadding="8">
<thead>
<tr>
<td width="20%">'.__("Part Number").'</td>
<td width="50%">'.__("Part Name").'</td>
<td width="10%">'.__("Min Qty").'</td>
<td width="10%">'.__("Max Qty").'</td>
<td width="10%">'.__("On Hand").'</td>
</tr>
</thead>
<tbody>
<!-- ITEMS HERE -->';

	foreach ( $SupplyList as $Supply ) {
		$html .= sprintf('<tr><td align="center">%s</td><td>%s</td><td align="right">%d</td><td align="right">%d</td><td align="right">%d</td></tr>\n', 
			$Supply->PartNum, $Supply->PartName, $Supply->MinQty, $Supply->MaxQty, Supplies::GetSupplyCount($Supply->SupplyID) );
			
		$bc->SupplyID = $Supply->SupplyID;
		$binList = $bc->FindSupplies();
		
		foreach ( $binList as $sb ) {
			$bin->BinID = $sb->BinID;
			$bin->GetBin();
			
			$html .= sprintf( '<tr><td>&nbsp;</td><td>%s: %s</td><td>&nbsp;</td><td>&nbsp;</td><td align="right">%d</td></tr>\n', __("Location"), $bin->Location, $sb->Count );
		}
	}
	
	$html .= '<!-- END ITEMS HERE -->
<tr>
<td class="bottom" colspan=5>&nbsp;</td>
</tbody>
</table>
</body>
</html>
';

$mpdf->WriteHTML($html);

$mpdf->Output(); exit;

exit;

?>
