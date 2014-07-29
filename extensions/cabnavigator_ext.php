<?php

if(  !($user->ReadBilling)  ){
	exit();
}

include("adb.inc.php");
	
print "<HR>\n" ;
$cab=new Cabinet();
$cab->CabinetID=$_REQUEST["cabinetid"];
$cab->GetCabinet();

print "<h2 style=\"text-align:left\">ADB says : </h2>\n";

$found_rack_charge=0;

$sql="select tblColocation.id, rackid, dateordered, dateinstalled,  tblColocation.Notes, PeriodCharge, BillingProfile, tblOrganisation.ID as companyid, tblOrganisation.Name  as company from tblColocation,tblOrganisation  where rackid='" . $cab->Location . "'" .  " and State='OPEN' and tblOrganisation.ID=tblColocation.CompanyID"  ;

foreach($adbh->query($sql) as $row){
	$found_rack_charge=1 ;
	print "Allocated to: " . "<a href=\"$adbbase" . "customer_services.php?ID=" . $row["companyid"] . "\">" . "<B>" . $row["company"] . "</B></A><P>" ;
	print " Ordered: <B>" . $row["dateordered"] . "</B>,  Installed: <B>" . $row["dateinstalled"] .  "</B><P>\n";
	print "<BR>";
	print "<h3 style=\"text-align:left; font-weight:bold;\">Colocation Charging</h3>\n";

	print "Footprint charged on a <B>" . $row["BillingProfile"] . "</B> basis at " . "<B>£" . $row["PeriodCharge"] . "</B><P>\n";
	print "<BR>\n";


	$sql2="select * from tblMiscItems where CompanyID=" .$row["companyid"] . " and LineDescription like '%Utility%" . $cab->Location ."%'" ;

	print "<h3 style=\"text-align:left; font-weight:bold;\">Electricity Charging\n</h3>";
	$found_elec_charge=0;
	foreach($adbh->query($sql2) as $row){
		$found_elec_charge=1;
		print "Electricity charged on a <B>" . $row["BillingProfile"] . "</B> basis at " . "<B>£" . $row["PeriodCharge"] . "</B><BR>\n";
		print "Billing line match: " . $row["LineDescription"] . "<BR>\n";
	}

	if (! $found_elec_charge) {
		print "<font color=\"red\">No Electricity Charges found for this rack under this company!</FONT><BR>\n";
	}

}

if (! $found_rack_charge) {
	print "<font color=\"red\">No Charges found for this rack !</FONT><BR>\n";
}

$sql="select tblColocation.id, rackid, dateclosed, tblOrganisation.ID as companyid, tblOrganisation.Name as company, state from tblColocation,tblOrganisation  where rackid='" . $cab->Location . "'" .  " and State <> 'OPEN' and tblOrganisation.ID=tblColocation.CompanyID"  ;

print "<P><BR>\n";
print "<h4 style=\"text-align:left; font-weight:bold;\">Rack History\n</h4>";
foreach($adbh->query($sql) as $row){
	print "<a href=\"$adbbase" . "customer_services.php?ID=" . $row["companyid"] . "\">" . "<B>" . $row["company"] . "</B></A>," ;
	print " " . $row["state"] . ": <B>" . $row["dateclosed"] . "</B><P>\n";
}

?>
