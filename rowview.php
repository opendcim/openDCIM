<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Cabinet Inventory");

	// Get the list of departments that this user is a member of
	$viewList = $person->isMemberOf();

	$cab=new Cabinet();
	$head=$legend=$zeroheight=$body=$deptcolor="";
	$deptswithcolor=array();
	$dev=new Device();
	$templ=new DeviceTemplate();
	$tempDept=new Department();
	$dc=new DataCenter();
	$cabrow=new CabRow();

	$cabrow->CabRowID=$_REQUEST['row'];
	$cabrow->GetCabRow();
	$cab->CabRowID=$cabrow->CabRowID;
	$cabinets=$cab->GetCabinetsByRow();
	$frontedge=$cabrow->GetCabRowFrontEdge();
	if (isset($_GET["rear"])){
		//opposite view
		$cabinets=array_reverse($cabinets);
	}

	// Set up the classes for color coding based upon status
	$dsList=DeviceStatus::getStatusList();

	$head.="		<style type=\"text/css\">\n";
	foreach($dsList as $stat){
		if($stat->ColorCode != "#FFFFFF"){
			$stName=str_replace(' ','_',$stat->Status);
			$important=($stName == 'Reserved')?' !important':'';

			$head.="\t\t\t.$stName {background-color: {$stat->ColorCode}$important;}\n";
		}
	}

	//start loop to parse all cabinets in the row
	foreach($cabinets as $index => $cabinet){
		$currentHeight=$cabinet->CabinetHeight;

		$side=null;
		if($frontedge=="Top" || $frontedge=="Bottom"){
			$side=($cabinet->FrontEdge=="Left" || $cabinet->FrontEdge=="Right")?true:null;
		}else{ // else it's Left or Right
			$side=($cabinet->FrontEdge=="Top" || $cabinet->FrontEdge=="Bottom")?true:null;
		}

		// Here we have a decision, for now I am just making it front and rear,
		// in the future we can eval for the left and right as well to make the view 
		// more realistic
		if($side){
			$side='side';
		}else{
			if(isset($_GET["rear"])){  // if rear is specified we want the opposite of what the rack would be usually
				if($frontedge==$cabinet->FrontEdge || ($frontedge!=$cabinet->FrontEdge)){
					$side='rear';
				}else{
					$side='front';
				}
			}else{
				if($frontedge==$cabinet->FrontEdge || ($frontedge!=$cabinet->FrontEdge)){
					$side='front';
				}else{
					$side='rear';
				}
			}
		}
		$body.=BuildCabinet($cabinet->CabinetID,$side);
	}

	$dcID=$cabrow->DataCenterID;
	$dc->DataCenterID=$dcID;
	$dc->GetDataCenterbyID();

	foreach(Department::GetDepartmentListIndexedbyID() as $deptid => $d){
		// If head is empty then we don't have any custom colors defined above so add a style container for these
		$head=($head=="")?"\t\t<style type=\"text/css\">\n":$head;
		$head.="\t\t\t.dept$deptid {background-color:$d->DeptColor;}\n";
		$legend.="\t\t<div class=\"legenditem\"><span class=\"border colorbox dept$deptid\"></span> - <span>$d->Name</span></div>\n";
	}

	// If $head isn't empty then we must have added some style information so close the tag up.
	if($head!=""){
		$head.='		</style>';
	}

	$title=($cabrow->Name!='')?__("Row")." $cabrow->Name".(isset($_GET["rear"])?"(".__("Rear").")":"")." :: ".count($cabinets)." ".__("Cabinets")." :: $dc->Name":__("Facilities Cabinet Maintenance");

?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/print.css" type="text/css" media="print">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
 
<?php 
echo $head,'  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.cookie.js"></script>
  <script type="text/javascript" src="scripts/common.js?v',filemtime('scripts/common.js'),'"></script>
  <script type="text/javascript">
	$(document).ready(function() {
		$(".cabinet .error").append("*");';
if ($person->RackAdmin || $person->SiteAdmin) {
		echo '$("<button>",{type: "button"}).text("'.__("Add new cabinet").'").click(function(){
			document.location.href="cabinets.php?dcid=',$dcID,'&zoneid=',$cabrow->ZoneID,'&cabrowid=',$cabrow->CabRowID,'";
		}).prependTo($(".main"));';
}
		echo '$("<button>",{id: "reverse", type: "button"}).text("'.(isset($_GET["rear"])?__("Front View"):__("Rear View")).'").click(function(){
			document.location.href="rowview.php?row=',$cabrow->CabRowID.(isset($_GET["rear"])?"":"&rear"),'";
		}).prependTo($(".main"));';

if($config->ParameterArray["ToolTips"]=='enabled'){
?>
		$('.cabinet div[id^="servercontainer"], #infopanel').on('mouseenter', 'div > div.genericdevice, div > div a > img, #zerou div > a', function(){
			var pos=$(this).offset();
			var tooltip=$('<div />').css({
				'left':pos.left+this.getBoundingClientRect().width+15+'px',
				'top':pos.top+(this.getBoundingClientRect().height/2)-15+'px'
			}).addClass('arrow_left border cabnavigator tooltip').attr('id','tt').append('<span class="ui-icon ui-icon-refresh rotate"></span>');
			$.post('scripts/ajax_tooltip.php',{tooltip: $(this).data('deviceid'), dev: 1}, function(data){
				tooltip.html(data);
			});
			$('body').append(tooltip);
			$(this).mouseleave(function(){
				tooltip.remove();
			});
		});
<?php
}
?>

		// Damn translators not using abreviations
		// This will lock the cabinet into the correct size
		$('.cabinet #cabid').parent('tr').next('tr').find('.cabpos').css('padding','0px').wrapInner($('<div>').css({'overflow':'hidden','width':'30px'}));
	});
  </script>
</head>

<body>
<?php include( 'header.inc.php' ); ?>
<div class="page">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main rowview">
<div class="center"><div>
<div id="centeriehack">
<?php
	echo $body;
?>
</div> <!-- END div#centeriehack -->
<script type="text/javascript">
// 258 width of cabinet + 20 margin
$('#centeriehack').width($('#centeriehack div.cabinet').length * 278);
</script>
</div></div>
<?php
	print "<br>";
	if($cabrow->ZoneID){ 
		$zone=new Zone();
		$zone->ZoneID=$cabrow->ZoneID;
		$zone->GetZone();
		print " <a href=\"zone_stats.php?zone=$zone->ZoneID\">[ ".__("Return to Zone")." $zone->Description ]</a>";
	}
	if($dcID>0){
		print "<br><a href=\"dc_stats.php?dc=$dcID\">[ ".__("Return to")." $dc->Name ]</a>";
	}
?>
</div>  <!-- END div.main -->
<?php
	if ($person->SiteAdmin || $person->RackAdmin || $person->WriteAccess) {
                print '<div><input type="hidden" name="cabinetdraggable" id="cabinetdraggable" value="yes"></div>';
        }
?>

<div class="clear"></div>
</div>
<script type="text/javascript">
	$(document).ready(function() {
		// Move the cabinet labels around
		$('.cabnavigator div.picture > div.label > div').each(function(){
			var offset=this.getBoundingClientRect().height;
			var container=$(this).parents('.picture')[0].getBoundingClientRect().height;
			$(this).parent('.label').css('top', (container-offset)/2/2);
		});

		// Don't attempt to open the datacenter tree until it is loaded
		function opentree(){
			if($('#datacenters .bullet').length==0){
				setTimeout(function(){
					opentree();
					resize();
				},500);
			}else{
				expandToItem('datacenters','cr<?php echo $cabrow->CabRowID;?>');
				resize();
			}
		}
		opentree();

		// Add controls to the rack
		//cabinetimagecontrols();
	});
</script>
</body>
</html>
