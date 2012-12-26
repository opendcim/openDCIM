<!doctype html>
<?php
/*
	Generic first time installer.  Makes assumption that the db.inc.php has been created

*/

// Pre-Flight check
	if(!isset($_SERVER['REMOTE_USER']) || !extension_loaded('gettext') || !function_exists("mysql_query") || !function_exists("json_encode")){
		$tests=array();
		$tests['Remote User']['errtxt']='<a href="http://httpd.apache.org/docs/2.2/howto/auth.html">http://httpd.apache.org/docs/2.2/howto/auth.html</a>';
		$tests['Remote User']['goodtxt']='';
		$tests['Remote User']['state']=(isset($_SERVER['REMOTE_USER']))?"good":"fail";
		$tests['mbstring']['errtxt']='PHP is missing the <a href="http://php.net/mbstring">mbstring extension</a>';
		$tests['mbstring']['goodtxt']='';
		$tests['mbstring']['state']=(extension_loaded('mbstring'))?"good":"fail";
		$tests['gettext']['errtxt']='PHP is missing the <a href="http://php.net/manual/book.gettext.php">Gettext extension</a>. Please install it.';
		$tests['gettext']['goodtxt']='';
		$tests['gettext']['state']=(extension_loaded('gettext'))?"good":"fail";
		$tests['mysql']['errtxt']='openDCIM requires a MySQL database, but PHP doesn\'t have the <a href="http://php.net/mysql">MySQL</a> extension.';
		$tests['mysql']['goodtxt']='';
		$tests['mysql']['state']=(function_exists("mysql_query"))?"good":"fail";
		$tests['json']['errtxt']='PHP is missing the <a href="http://php.net/manual/book.json.php">JavaScript Object Notation (JSON) extension</a>.  Please install it.';
		$tests['json']['goodtxt']='';
		$tests['json']['state']=(function_exists("json_encode"))?"good":"fail";

        echo '<!doctype html><html><head><title>openDCIM :: pre-flight environment sanity check</title><script type="text/javascript" src="scripts/jquery.min.js"></script><script type="text/javascript">$(document).ready(function(){$("tr").each(function(){if($(this).find("td:last-child").text()=="fail"){$(this).addClass("fail");}});});</script><style type="text/css">table{width:80%;border-collapse:collapse;border:3px solid black;}th{text-align:left;text-transform:uppercase;border-right: 1px solid black;}th,td{padding:5px;}tr:nth-child(even){background-color:#d1e1f1;}td:last-child{text-align:center;text-transform:uppercase;border:2px solid;background-color:green;}.fail td:last-child{font-weight: bold;background-color: red;}</style></head><body><h2>Pre-flight environment checks</h2><table>';
		foreach($tests as $test => $text){
			$desc=($text['state']=='good')?$text['goodtxt']:$text['errtxt'];
			print "<tr><th>$test</th><td>$desc</td><td>{$text['state']}</td></tr>";
		}
		echo '<tr><th>javascript</th><td>Javascript is used heavily for data validation and a more polished user experience.</td><td><script>document.write("good")</script><noscript>fail</noscript></td></tr>
			</table>
		<p>If you are seeing this page then you must correct any issues shown above before the installer will continue.</p>

		</body></html>';
		exit;
	}

// Make sure that a db.inc.php has been created
	if(!file_exists("db.inc.php")){
		print "Please copy db.inc.php-dist to db.inc.php.<br>\nOpen db.inc.php with a text editor and fill in the blanks for user, pass, database, and server.";
		exit;
	}else{
		require_once("db.inc.php");
	}

// Functions for upgrade / installing db objects
	$successlog="";
	
function applyupdate ($updatefile){
	//Make sure the upgrade file exists.
	if(file_exists($updatefile)){
		$file=fopen($updatefile, 'r');
		$sql=array();
		while(feof($file)===false){
			$sql[]=fgets($file);
		}
		$sqlstring="";
		foreach($sql as $key => $value){
			// I really need a better way to filter out comments but this works.
			if(substr($value,0,1)=='-'){
			}else{
				$sqlstring.=trim($value);
			}
		}
		fclose($file);
		$sql=explode(";",$sqlstring);
		unset($sql[count($sql)-1]);
		$result=0;
		foreach($sql as $key => $value){
// uncomment to debug sql injection
//			echo $value."<br>\n";
			if(!mysql_query($value)){
				//something broke log it
				$errormsg.=mysql_error();
				$errormsg.="<br>\n";
				$result=1;
			}
		}
		if($result){
			if(!isset($errormsg)){
				$errormsg="An error has occured while applying $updatefile. Please consult the server logs for more details.<br>\n";
			}
		}else{
			$successlog="$updatefile: Database updates applied.<br>\n";
		}
	}else{
		$errormsg="Seems you're at 1.0 but you're missing the db updates to goto 1.1. Are you sure that db-1.0-to-1.1.sql unpacked from the archive?";
	}
	$temp=array();
	if(isset($errormsg)){
		$temp[1]=$errormsg;
	}else{
		$temp[0]=$successlog;
	}
	return $temp;
}
	$upgrade=false;

// Check to see if we are doing an upgrade or an install
	$result=mysql_query("SHOW TABLES;");
	if(mysql_num_rows($result)==0){ // No tables in the DB so try to install.
		$results[]=applyupdate("create.sql");
		$upgrade=false;
	}
	// New install so create a user
	require_once("customers.inc.php");

	$user=new User();
	$user->UserID=$_SERVER['REMOTE_USER'];
	$user->GetUserRights($facDB);

	// Re-read the config
	$config->Config($facDB);
// Check to see if we have any users in the database.
	if(mysql_num_rows(mysql_query("SELECT * FROM fac_User WHERE SiteAdmin=1;"))<1){
		// no users in the system or no users with site admin rights, either way we're missing the class of people we need
		// put stuff here like correcting for a missing site admin
		print "There are no users in the database with sufficient privileges to perform this update";
		exit;
		$rightserror=1;
	}else{ // so we have users and at least one site admin
		require_once("customers.inc.php");

		$user=new User();
		$user->UserID=$_SERVER['REMOTE_USER'];
		$user->GetUserRights($facDB);

		if(!$user->SiteAdmin){
			// dolemite says you aren't an admin so you can't apply the update
			print "An update has been applied to the system but the system hasn't been taken out of maintenance mode. Please contact a site Administrator to correct this issue.";
			exit;
		}
		$rightserror=0;
	}

//  test for openDCIM version
	$result=mysql_query("SELECT Value FROM fac_Config WHERE Parameter='Version' LIMIT 1;");
	if(mysql_num_rows($result)==0){// Empty result set means this is either 1.0 or 1.1. Surely the check above caught all 1.0 instances.
		$results[]=applyupdate("db-1.1-to-1.2.sql");
		$upgrade=true;
		$version="1.2";
	}else{
		$version=mysql_result($result,0);//sets version number
	}
	if($version=="1.2"){ // Do 1.2 to 1.3 Update
		$results[]=applyupdate("db-1.2-to-1.3.sql");
		$upgrade=true;
		$version="1.3";
	}
	if($version=="1.3"){ // Do 1.3 to 1.4 Update
		// Clean the configuration table of any duplicate values that might have been added.
		$config->rebuild($facDB);
		$results[]=applyupdate("db-1.3-to-1.4.sql");
		$upgrade=true;
		$version="1.4";
	}
	if($version=="1.4"){ // Do 1.4 to 1.5 Update
		// A few of the database changes require some tests to ensure that they will be able to apply.
		// Both of these need to return 0 results before we continue or the database schema update will not complete.
		$conflicts=0;
		$sql="SELECT PDUID, CONCAT(PDUID,'-',PDUPosition) AS KEY1, COUNT(PDUID) AS Count  FROM fac_PowerConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY PDUID ASC;";
		$conflicts+=(mysql_num_rows(mysql_query($sql, $facDB))>0)?1:0;
		$sql="SELECT DeviceID, CONCAT(DeviceID,'-',DeviceConnNumber) AS KEY2, COUNT(DeviceID) AS Count FROM fac_PowerConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY DeviceID ASC;";
		$conflicts+=(mysql_num_rows(mysql_query($sql, $facDB))>0)?1:0;
		$sql="SELECT SwitchDeviceID, CONCAT(SwitchDeviceID,'-',SwitchPortNumber) AS KEY1, COUNT(SwitchDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY1 HAVING (COUNT(KEY1)>1) ORDER BY SwitchDeviceID ASC;";
		$conflicts+=(mysql_num_rows(mysql_query($sql, $facDB))>0)?1:0;
		$sql="SELECT SwitchDeviceID, SwitchPortNumber, EndpointDeviceID, EndpointPort, CONCAT(EndpointDeviceID,'-',EndpointPort) AS KEY2, COUNT(EndpointDeviceID) AS Count FROM fac_SwitchConnection GROUP BY KEY2 HAVING (COUNT(KEY2)>1) ORDER BY EndpointDeviceID ASC;";
		$conflicts+=(mysql_num_rows(mysql_query($sql, $facDB))>0)?1:0;

		require_once("facilities.inc.php");
		if($conflicts!=0){
			header('Location: '.redirect("conflicts.php"));
			exit;
		}

		$config->rebuild($facDB);
		$results[]=applyupdate("db-1.4-to-1.5.sql");
		$upgrade=true;
		$version="1.5";
	}
	
	if ( $version == "1.5" ) {	// Do the 1.5 to 2.0 Update
		// Get a list of all Manufacturers that are duplicated
		$sql = "select ManufacturerID,Name from fac_Manufacturer group by Name having count(*)>1";
		$result = mysql_query( $sql, $facDB );
		
		while ( $row = mysql_fetch_array( $result ) ) {
			// Set all devices with that Manufacturer to the ID of just one
			$sql = sprintf( "update fac_DeviceTemplate set ManufacturerID=%d where ManufacturerID in (select ManufacturerID from fac_Manufacturer where Name=\"%s\")", $row["ManufacturerID"], $row["Name"] );
			mysql_query( $sql, $facDB );
			
			// Delete all the duplicates other than the one you set everything to
			$sql = sprintf( "delete from fac_Manufacturer where Name=\"%s\" and ManufacturerID!=%d", $row["Name"], $row["ManufacturerID"] );
			mysql_query( $sql, $facDB );
		}
		
		// Repeat for Templates
		$sql = "select TemplateID,ManufacturerID,Model from fac_DeviceTemplate group by ManufacturerID,Model having count(*)>1";
		$result = mysql_query( $sql, $facDB );
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$sql = sprintf( "update fac_Device set TemplateID=%d where TemplateID in (select TemplateID from fac_DeviceTemplate where ManufacturerID=%d and Model=\"%s\")", $row["TemplateID"], $row["ManufacturerID"], $row["Model"] );
			mysql_query( $sql, $facDB );
			
			$sql = sprintf( "delete from fac_DeviceTemplate where ManufacturerID=%d and TemplateID!=%d", $row["ManufacturerID"], $row["TemplateID"] );
			mysql_query( $sql, $facDB );
		}
		
		// And finally, Departments
		$sql = "select DeptID, Name from fac_Department group by Name having count(*)>1";
		$result = mysql_query( $sql, $facDB );
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$sql = sprintf( "update fac_Device set Owner=%d where Owner in (select DeptID from fac_Department where Name=\"%s\")", $row["DeptID"], $row["Name"] );
			mysql_query( $sql, $facDB );
			
			// Yes, I know, this may create duplicates
			$sql = sprintf( "update fac_DeptContacts set DeptID=%d where DeptID in (select DeptID from fac_Department where Name=\"%s\")", $row["DeptID"], $row["Name"] );
			mysql_query( $sql, $facDB );
			
			$sql = sprintf( "delete from fac_Department where Name=\"%s\" and DeptID!=%d", $row["Name"], $row["DeptID"] );
			mysql_query( $sql, $facDB );
		}
		
		// So delete the potential duplicate contact links created in the last step
		$sql = "select DeptID,ContactID from fac_DeptContacts group by DeptID,ContactID having count(*)>1";
		$result = mysql_query( $sql, $facDB );
		
		while ( $row = mysql_fetch_array( $result ) ) {
			$sql = sprintf( "delete from fac_DeptContacts where DeptID=%d and ContactID=%d", $row["DeptID"], $row["ContactID"] );
			mysql_query( $sql, $facDB );
			
			$sql = sprintf( "insert into fac_DeptContacts values ( %d, %d )", $row["DeptID"], $row["ContactID"] );
			mysql_query( $sql, $facDB );
		}
		
		$config->rebuild( $facDB );
		$results[]=applyupdate( "db-1.5-to-2.0.sql" );
		$upgrade = true;
		$version = "2.0";
	}
		
	if($upgrade==true){ //If we're doing an upgrade don't call the rest of the installer.
?>
<!doctype html>
<html>
<head>
<title>Upgrade</title>
<style type="text/css">
.error { color: red;}
.success { color: green;}
</style>
</head>
<body>
<?php 
if(isset($results)){
	foreach($results as $key => $value){
		foreach($value as $status => $message){
			if($status==1){$class="error";}else{$class="success";}
			print "<h1 class=\"$class\">$message</h1>";
		}
	}
	print "<p class=\"$class\">If all updates have completed.  Please remove install.php to return to normal functionality.</p><p>Reload the page to try loading sql updates again or to go on to the installer</p>";
}else{
	echo '<p class="success">All is well.  Please remove install.php to return to normal functionality</p>';
}
?>
</body>
</html>


<?php
	exit;
	}
	require_once( "facilities.inc.php" );

	$dept=new Department();
	$dc=new DataCenter();
	$cab=new Cabinet();

// Configuration Form Submission
	if(isset($_REQUEST["confaction"]) && $_REQUEST["confaction"]=="Update"){
		foreach($config->ParameterArray as $key=>$value){
			if($key=="ClassList"){
				$List=explode(", ",$_REQUEST[$key]);
				$config->ParameterArray[$key]=$List;
			}else{
				$config->ParameterArray[$key]=$_REQUEST[$key];
			}
		}
		$config->UpdateConfig($facDB);
	}

// Departments Form Submission
	if(isset($_REQUEST['deptid'])&&($_REQUEST['deptid']>0)){
		$dept->DeptID = $_REQUEST['deptid'];
		$dept->GetDeptByID( $facDB );
	}

	if(isset($_REQUEST['deptaction'])&& (($_REQUEST['deptaction']=='Create') || ($_REQUEST['deptaction']=='Update'))){
		$dept->DeptID = $_REQUEST['deptid'];
		$dept->Name = $_REQUEST['name'];
		$dept->ExecSponsor = $_REQUEST['execsponsor'];
		$dept->SDM = $_REQUEST['sdm'];
		$dept->Classification = $_REQUEST['classification'];

		if($_REQUEST['deptaction']=='Create'){
		  if($dept->Name != '' && $dept->Name != null)
			 $dept->CreateDepartment($facDB);
		}else{
			$dept->UpdateDepartment($facDB);
		}
	}
	$result=mysql_query("SELECT * FROM fac_Department LIMIT 1;");
	if(mysql_num_rows($result)==0){ // No departments defined
		$nodept="<h3>Create a department</h3>";
		$nodeptdrop="readonly";
	}else{
		$nodept=$nodeptdrop="";
	}

// Data Centers Form Submission
	if(isset($_REQUEST['dcaction']) && (($_REQUEST['dcaction']=='Create')||($_REQUEST['dcaction']=='Update'))){
		$dc->DataCenterID = $_REQUEST['datacenterid'];
		$dc->Name = $_REQUEST['name'];
		$dc->SquareFootage = $_REQUEST['squarefootage'];
		$dc->DeliveryAddress = $_REQUEST['deliveryaddress'];
		$dc->Administrator = $_REQUEST['administrator'];
		$dc->DrawingFileName = $_REQUEST['drawingfilename'];
		
		if($_REQUEST['dcaction']=='Create'){
			$dc->CreateDataCenter($facDB);
		}else{
			$dc->UpdateDataCenter($facDB);
		}
	}

	if(isset($_REQUEST['datacenterid']) && $_REQUEST['datacenterid'] >0){
		$dc->DataCenterID=$_REQUEST['datacenterid'];
		$dc->GetDataCenter($facDB);
	}
	$dcList=$dc->GetDCList($facDB);
	$result=mysql_query("SELECT * FROM fac_DataCenter LIMIT 1;");
	if(mysql_num_rows($result)==0){ // No data centers configured disable cabinets and complete options
		$nodc="<h3>Define a data center</h3>";
		$nodccab="<h3>You must create a Data Center before you can create cabinets in it.</h3>";
		$nodcfield="disabled";
		$nodcdrop="readonly";
	}else{
		$nodc=$nodccab=$nodcfield=$nodcdrop="";
	}

//Cabinet Form Submission
	if(isset($_REQUEST['cabinetid'])){
		$cab->CabinetID=$_REQUEST['cabinetid'];
		$cab->GetCabinet($facDB);
	}

	if(isset($_REQUEST['cabaction'])){
		if(($cab->CabinetID >0)&&($_REQUEST['cabaction']=='Update')){
			$cab->DataCenterID=$_REQUEST['datacenterid'];
			$cab->Location=$_REQUEST['location'];
			$cab->AssignedTo=$_REQUEST['assignedto'];
			$cab->CabinetHeight=$_REQUEST['cabinetheight'];
			$cab->Model=$_REQUEST['model'];
			$cab->MaxKW=$_REQUEST['maxkw'];
			$cab->MaxWeight=$_REQUEST['maxweight'];
			$cab->InstallationDate=$_REQUEST['installationdate'];
			$cab->UpdateCabinet($facDB);
		}elseif($_REQUEST['cabaction']=='Create'){
			$cab->DataCenterID=$_REQUEST['datacenterid'];
			$cab->Location=$_REQUEST['location'];
			$cab->AssignedTo=$_REQUEST['assignedto'];
			$cab->CabinetHeight=$_REQUEST['cabinetheight'];
			$cab->Model=$_REQUEST['model'];
			$cab->MaxKW=$_REQUEST['maxkw'];
			$cab->MaxWeight=$_REQUEST['maxweight'];
			$cab->InstallationDate=$_REQUEST['installationdate'];
			$cab->CreateCabinet($facDB);
		}
	}
	if($nodccab==""){ // only attempt to check for racks in the db if a data center has already been created
		$result=mysql_query("SELECT * FROM fac_Cabinet LIMIT 1;");
		if(mysql_num_rows($result)==0){ // No racks defined disable complete option
			$nodccab="<h3>Create a rack for equipment to be housed in</h3>";
			$nocabdrop="readonly";
			$nocab="error";
		}else{
			$nocab=$nocabdrop=$nodccab="";
		}
	}

//Installation Complete
	if($nodept=="" && $nodc=="" && $nocab==""){ // All three primary sections have had at least one item created
		//enable the finish menu option
		$complete=true;
	}

?>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Installer</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
  <script type="text/javascript">
	$(document).ready( function() {
		$(".color-picker").miniColors({
			letterCase: 'uppercase',
			change: function(hex, rgb){
				if($(this).attr('id')==='HeaderColor'){
					$('#header').css('background-color',$(this).val());
				}else if($(this).attr('id')==='BodyColor'){
					$('.main').css('background-color',$(this).val());
				}
			}
		});
		$("#configtabs").tabs();
		$("#configtabs input[defaultvalue]").each(function(){
			$(this).parent().after('<div><button type="button">&lt;--</button></div><div><span>'+$(this).attr('defaultvalue')+'</span></div>');
		});
		$("#configtabs input").each(function(){
			$(this).attr('id', $(this).attr('name'));
			$(this).removeAttr('defaultvalue');
		});
		$("#configtabs button").each(function(){
			var a = $(this).parent().prev().find('input');
			$(this).click(function(){
				a.val($(this).parent().next().children('span').text());
				a.triggerHandler("paste");
				a.focus();
				$('input[name="OrgName"]').focus();
			});
		});
		$('input[name="LinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:link, a:hover, a:visited:hover {color: "+$(this).val()+";}</style>");
		});
		$('input[name="VisitedLinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:visited {color: "+$(this).val()+";}</style>");
		});
		$("#imageselection span").each(function(){
			var preview=$('#imageselection #preview');
			$(this).click(function(){
				preview.html('<img src="images/'+$(this).text()+'" alt="preview" width="'+preview.innerHeight()+'px">').attr('image',$(this).text()).css('border-width', '5px').children('img').css('margin-top', preview.innerHeight()/2-preview.children('img').height()/2+'px');
				$("#imageselection span").each(function(){
					$(this).removeAttr('style');
				});
				$(this).css('border','1px dotted black')
				$('#header').css('background-image', 'url("images/'+$(this).text()+'")');
			});
			if($('#PDFLogoFile').val()==$(this).text()){
				$(this).click();
			}
		});
		$('#PDFLogoFile').click(function(){
			$("#imageselection").dialog({
				resizable: false,
				height:300,
				width: 400,
				modal: true,
				buttons: {
<?php echo '					',_("Select"),': function() {'; ?>
						if($('#imageselection #preview').attr('image')!=""){
							$('#PDFLogoFile').val($('#imageselection #preview').attr('image'));
						}
						$(this).dialog("close");
					}
				}
			});
		});
		$("#tzmenu").menu();
		$("#tzmenu ul > li").click(function(e){
			e.preventDefault();
			$("#timezone").val($(this).children('a').attr('data'));
			$("#tzmenu").toggle();
		});
		$("#tzmenu").focusout(function(){
			$("#tzmenu").toggle();
		});
		$('<button type="button">').attr({
				id: 'btn_tzmenu'
		}).appendTo("#general");
		$('#btn_tzmenu').each(function(){
			var input=$("#timezone");
			var offset=input.position();
			var height=input.outerHeight();
			$(this).css({
				'height': height+'px',
				'width': height+'px',
				'position': 'absolute',
				'left': offset.left+input.width()-height-((input.outerHeight()-input.height())/2)+'px',
				'top': offset.top+'px'
			}).click(function(){
				$("#tzmenu").toggle();
				$("#tzmenu").focus().click();
			});
			offset=$(this).position();
			$("#tzmenu").css({
				'position': 'absolute',
				'left': offset.left+(($(this).outerWidth()-$(this).width())/2)+'px',
				'top': offset.top+height+'px'
			});
			$(this).addClass('text-arrow');
		});
	});

  </script>
</head>
<body>
<div id="header"></div>
<?php

	if((!isset($_GET["dept"])&&!isset($_GET["cab"])&&!isset($_GET["dc"])&&!isset($_GET["complete"]))||isset($_GET["conf"])){

?>
<div class="page config installer">

<div id="sidebar">
<ul>
<a><li class="active">Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>

<?php
	// make list of department types
	$i=0;
	$classlist="";
	foreach($config->ParameterArray["ClassList"] as $item){
		$classlist .= $item;
		if($i+1 != count($config->ParameterArray["ClassList"])){
			$classlist.=", ";
		}
		$i++;
	}

	$imageselect='<div id="preview"></div><div id="filelist">';

	$path='./images';
	$dir=scandir($path);
	foreach($dir as $i => $f){
		if(is_file($path.DIRECTORY_SEPARATOR.$f) && round(filesize($path.DIRECTORY_SEPARATOR.$f) / 1024, 2)>=4 && $f!="serverrack.png" && $f!="gradient.png"){
			$imageinfo=getimagesize($path.DIRECTORY_SEPARATOR.$f);
			if(preg_match('/^image/i', $imageinfo['mime'])){
				$imageselect.="<span>$f</span>\n";
			}
		}
	}
	$imageselect.="</div>";

	function formatOffset($offset) {
			$hours = $offset / 3600;
			$remainder = $offset % 3600;
			$sign = $hours > 0 ? '+' : '-';
			$hour = (int) abs($hours);
			$minutes = (int) abs($remainder / 60);

			if ($hour == 0 AND $minutes == 0) {
				$sign = ' ';
			}
			return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) 
					.':'. str_pad($minutes,2, '0');

	}

	static $regions = array(
		'Africa' => DateTimeZone::AFRICA,
		'America' => DateTimeZone::AMERICA,
		'Antarctica' => DateTimeZone::ANTARCTICA,
		'Asia' => DateTimeZone::ASIA,
		'Atlantic' => DateTimeZone::ATLANTIC,
		'Europe' => DateTimeZone::EUROPE,
		'Indian' => DateTimeZone::INDIAN,
		'Pacific' => DateTimeZone::PACIFIC
	);

	foreach($regions as $name => $mask){
		$tzlist[$name]=DateTimeZone::listIdentifiers($mask);
	}

	$tzmenu='<ul id="tzmenu">';
	foreach($tzlist as $country => $cityarray){
		$tzmenu.="\t<li>$country\n\t\t<ul>";
		foreach($cityarray as $key => $city){
			$z=new DateTimeZone($city);
			$c=new DateTime(null, $z);
			$adjustedtime=$c->format('H:i a');
			$offset=formatOffset($z->getOffset($c));
			$tzmenu.="\t\t\t<li><a href=\"#\" data=\"$city\">$adjustedtime - $offset $city</a></li>\n";
		}
		$tzmenu.="\t\t</ul>\t</li>";
	}
	$tzmenu.='</ul>';

echo '<div class="main">
<h2>',$config->ParameterArray["OrgName"],'</h2>
<h3>',_("Data Center Configuration"),'</h3>
<h3>',_("Database Version"),': ',$config->ParameterArray["Version"],'</h3>
<div class="center"><div>
<form action="',$_SERVER["PHP_SELF"],'?conf" method="POST">
   <input type="hidden" name="Version" value="',$config->ParameterArray["Version"],'">

	<div id="configtabs">
		<ul>
			<li><a href="#general">',_("General"),'</a></li>
			<li><a href="#style">',_("Style"),'</a></li>
			<li><a href="#email">',_("Email"),'</a></li>
			<li><a href="#reporting">',_("Reporting"),'</a></li>
		</ul>
		<div id="general">
			<div class="table">
				<div>
					<div><label for="OrgName">',_("Organization Name"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["OrgName"],'" name="OrgName" value="',$config->ParameterArray["OrgName"],'"></div>
				</div>
				<div>
					<div><label for="Locale">',_("Locale"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["Locale"],'" name="Locale" value="',$config->ParameterArray["Locale"],'"></div>
				</div>
				<div>
					<div><label for="DefaultPanelVoltage">',_("Default Panel Voltage"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["DefaultPanelVoltage"],'" name="DefaultPanelVoltage" value="',$config->ParameterArray["DefaultPanelVoltage"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',_("Time and Measurements"),'</h3>
			<div class="table" id="timeandmeasurements">
				<div>
					<div><label for="timezone">',_("Time Zone"),'</label></div>
					<div><input type="text" readonly="readonly" id="timezone" defaultvalue="',$config->defaults["timezone"],'" name="timezone" value="',$config->ParameterArray["timezone"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',_("Users"),'</h3>
			<div class="table">
				<div>
					<div><label for="ClassList">',_("Department Types"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ClassList"],'" name="ClassList" value="',$classlist,'"></div>
				</div>
				<div>
					<div><label for="UserLookupURL">',_("User Lookup URL"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["UserLookupURL"],'" name="UserLookupURL" value="',$config->ParameterArray["UserLookupURL"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',_("Rack Requests"),'</h3>
			<div class="table">
				<div>
					<div><label for="MailSubject">',_("Mail Subject"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailSubject"],'" name="MailSubject" value="',$config->ParameterArray["MailSubject"],'"></div>
				</div>
				<div>
					<div><label for="RackWarningHours">',_("Warning (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackWarningHours"],'" name="RackWarningHours" value="',$config->ParameterArray["RackWarningHours"],'"></div>
				</div>
				<div>
					<div><label for="RackOverdueHours">',_("Critical (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackOverdueHours"],'" name="RackOverdueHours" value="',$config->ParameterArray["RackOverdueHours"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',_("Rack Usage"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><label for="SpaceRed">',_("Space Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceRed"],'" name="SpaceRed" value="',$config->ParameterArray["SpaceRed"],'"></div>
				</div>
				<div>
					<div><label for="SpaceYellow">',_("Space Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceYellow"],'" name="SpaceYellow" value="',$config->ParameterArray["SpaceYellow"],'"></div>
				</div>
				<div>
					<div><label for="WeightRed">',_("Weight Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightRed"],'" name="WeightRed" value="',$config->ParameterArray["WeightRed"],'"></div>
				</div>
				<div>
					<div><label for="WeightYellow">',_("Weight Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightYellow"],'" name="WeightYellow" value="',$config->ParameterArray["WeightYellow"],'"></div>
				</div>
				<div>
					<div><label for="PowerRed">',_("Power Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerRed"],'" name="PowerRed" value="',$config->ParameterArray["PowerRed"],'"></div>
				</div>
				<div>
					<div><label for="PowerYellow">',_("Power Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerYellow"],'" name="PowerYellow" value="',$config->ParameterArray["PowerYellow"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',_("Virtual Machines"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><lable for="VMExpirationTime">',_("Expiration Time (Days)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["VMExpirationTime"],'" name="VMExpirationTime" value="',$config->ParameterArray["VMExpirationTime"],'"></div>
				</div>
			</div> <!-- end table -->
			',$tzmenu,'
		</div>
		<div id="style">
			<h3>',_("Racks & Maps"),'</h3>
			<div class="table">
				<div>
					<div><label for="CriticalColor">',_("Critical Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CriticalColor" value="',$config->ParameterArray["CriticalColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["CriticalColor"]),'</span></div>
				</div>
				<div>
					<div><label for="CautionColor">',_("Caution Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CautionColor" value="',$config->ParameterArray["CautionColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["CautionColor"]),'</span></div>
				</div>
				<div>
					<div><label for="GoodColor">',_("Good Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="GoodColor" value="',$config->ParameterArray["GoodColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["GoodColor"]),'</span></div>
				</div>
				<div>
					<div>&nbsp;</div>
					<div></div>
					<div></div>
					<div></div>
				</div>
				<div>
					<div><label for="ReservedColor">',_("Reserved Devices"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="ReservedColor" value="',$config->ParameterArray["ReservedColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["ReservedColor"]),'</span></div>
				</div>
				<div>
					<div><label for="FreeSpaceColor">',_("Unused Spaces"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="FreeSpaceColor" value="',$config->ParameterArray["FreeSpaceColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["FreeSpaceColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
			<h3>',_("Site"),'</h3>
			<div class="table">
				<div>
					<div><label for="HeaderColor">',_("Header Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="HeaderColor" value="',$config->ParameterArray["HeaderColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["HeaderColor"]),'</span></div>
				</div>
				<div>
					<div><label for="BodyColor">',_("Body Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="BodyColor" value="',$config->ParameterArray["BodyColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["BodyColor"]),'</span></div>
				</div>
				<div>
					<div><label for="LinkColor">',_("Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="LinkColor" value="',$config->ParameterArray["LinkColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["LinkColor"]),'</span></div>
				</div>
				<div>
					<div><label for="VisitedLinkColor">',_("Viewed Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="VisitedLinkColor" value="',$config->ParameterArray["VisitedLinkColor"],'"></div></div>
					<div><button type="button"><--</button></div>
					<div><span>',strtoupper($config->defaults["VisitedLinkColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="email">
			<div class="table">
				<div>
					<div><label for="SMTPServer">',_("SMTP Server"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPServer"],'" name="SMTPServer" value="',$config->ParameterArray["SMTPServer"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPort">',_("SMTP Port"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPort"],'" name="SMTPPort" value="',$config->ParameterArray["SMTPPort"],'"></div>
				</div>
				<div>
					<div><label for="SMTPHelo">',_("SMTP Helo"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPHelo"],'" name="SMTPHelo" value="',$config->ParameterArray["SMTPHelo"],'"></div>
				</div>
				<div>
					<div><label for="SMTPUser">',_("SMTP Username"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPUser"],'" name="SMTPUser" value="',$config->ParameterArray["SMTPUser"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPassword">',_("SMTP Password"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPassword"],'" name="SMTPPassword" value="',$config->ParameterArray["SMTPPassword"],'"></div>
				</div>
				<div>
					<div><label for="MailToAddr">',_("Mail To"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailToAddr"],'" name="MailToAddr" value="',$config->ParameterArray["MailToAddr"],'"></div>
				</div>
				<div>
					<div><label for="MailFromAddr">',_("Mail From"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailFromAddr"],'" name="MailFromAddr" value="',$config->ParameterArray["MailFromAddr"],'"></div>
				</div>
				<div>
					<div><label for="ComputerFacMgr">',_("Facility Manager"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ComputerFacMgr"],'" name="ComputerFacMgr" value="',$config->ParameterArray["ComputerFacMgr"],'"></div>
				</div>
				<div>
					<div><label for="FacMgrMail">',_("Facility Manager Email"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["FacMgrMail"],'" name="FacMgrMail" value="',$config->ParameterArray["FacMgrMail"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="reporting">
			<div id="imageselection" title="Image file selector">
				',$imageselect,'
			</div>
			<div class="table">
				<div>
					<div><label for="annualCostPerUYear">',_("Annual Cost Per Rack Unit (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerUYear"],'" name="annualCostPerUYear" value="',$config->ParameterArray["annualCostPerUYear"],'"></div>
				</div>
				<div>
					<div><label for="annualCostPerWattYear">',_("Annual Cost Per Watt (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerWattYear"],'" name="annualCostPerWattYear" value="',$config->ParameterArray["annualCostPerWattYear"],'"></div>
				</div>
				<div>
					<div><label for="PDFLogoFile">',_("Logo file for headers"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFLogoFile"],'" name="PDFLogoFile" value="',$config->ParameterArray["PDFLogoFile"],'"></div>
				</div>
				<div>
					<div><label for="PDFfont">',_("Font"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFfont"],'" name="PDFfont" value="',$config->ParameterArray["PDFfont"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
	</div>';
?>
<div>
   <div></div>
   <div class="center"><input type="submit" name="confaction" value="Update"></div>
</div>
</div> <!-- END div.table -->
</div>
</form>
</div>
<?php

	}elseif(isset($_GET["dept"])){
		$deptList = $dept->GetDepartmentList( $facDB );
?>
<script type="text/javascript">
function showgroup(obj){
	self.frames['groupadmin'].location.href='dept_groups.php?deptid='+obj;
	document.getElementById('groupadmin').style.display = "block";
	document.getElementById('deptname').readOnly = true
	document.getElementById('deptsponsor').readOnly = true
	document.getElementById('deptmgr').readOnly = true
	document.getElementById('deptclass').disabled = true
	document.getElementById('controls').id = "displaynone";
}
</script>

<div class="page installer">

<div id="sidebar">
<ul>
<a href="?conf"><li>Configuration</li></a>
<a><li class="active">Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>

<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Department Detail</h3>
<?php echo $nodept; ?>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>?dept" method="POST">
<div class="table centermargin">
<div>
   <div>Department</div>
   <div><input type="hidden" name="deptaction" value="query"><select name="deptid" onChange="form.submit()" <?php echo $nodeptdrop;?>>
   <option value=0>New Department</option>
<?php
	foreach($deptList as $deptRow){
		echo "<option value=\"$deptRow->DeptID\"";
		if($dept->DeptID == $deptRow->DeptID){
			echo ' selected';
		}
		echo ">$deptRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="deptname">Department Name</label></div>
   <div><input type="text" size="50" name="name" id="deptname" value="<?php echo $dept->Name; ?>"></div>
</div>
<div>
   <div><label for="deptsponsor">Executive Sponsor</label></div>
   <div><input type="text" size="50" name="execsponsor" id="deptsponsor" value="<?php echo $dept->ExecSponsor; ?>"></div>
</div>
<div>
   <div><label for="deptmgr">Account Manager</label></div>
   <div><input type="text" size="50" name="sdm" id="deptmgr" value="<?php echo $dept->SDM; ?>"></div>
</div>
<div>
   <div><label for="deptclass">Classification</label></div>
   <div><select name="classification" id="deptclass">
<?php
  foreach($config->ParameterArray['ClassList'] as $className){
	  echo "<option value=\"$className\"";
	  if($dept->Classification==$className){echo ' selected';}
      echo ">$className</option>";
  }
?>
    </select>
   </div>
</div>
<div class="caption" id="controls">
    <input type="submit" name="deptaction" value="Create">
<?php
	if($dept->DeptID > 0){
		echo '<input type="submit" name="deptaction" value="Update">';
		echo "<input type=\"button\" onClick=\"showgroup($dept->DeptID)\" value=\"Assign Contacts\">";
//		print "<input type=\"button\" onClick=\"self.frames['groupadmin'].location.href='dept_groups.php?deptid=$dept->DeptID'\" value=\"Assign Contacts\">";
//		print "<input type=\"button\" onClick=\"window.open('dept_groups.php?deptid=$dept->DeptID', 'popup')\" value=\"Assign Contacts\">";
	}
?>
</div>
</div> <!-- END div.table -->
</form>
<iframe name="groupadmin" id="groupadmin" frameborder=0 scrolling="no"></iframe>
<br>
</div></div>
</div> <!-- END div.main -->
</div> <!-- END div.page -->

<?php
	}elseif(isset($_GET["dc"])){
?>

<div class="page installer">

<div id="sidebar">
<ul>
<a href="?conf"><li>Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a><li class="active">Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>

<div class="main">
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Detail</h3>
<?php echo $nodc; ?>
<div class="center"><div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>?dc" method="POST">
<div class="table">
<div>
   <div><label for="datacenterid">Data Center ID</label></div>
   <div><select name="datacenterid" id="datacenterid" onChange="form.submit()" <?php echo $nodcdrop;?>>
      <option value="0">New Data Center</option>
<?php
	foreach($dcList as $dcRow){
		echo "<option value=\"$dcRow->DataCenterID\"";
		if($dcRow->DataCenterID == $dc->DataCenterID){
			echo ' selected="selected"';
		}
		echo ">$dcRow->Name</option>\n";
	}
?>
	</select></div>
</div>
<div>
   <div><label for="dcname">Name</label></div>
   <div><input type="text" name="name" id="dcname" size="50" value="<?php echo $dc->Name; ?>"></div>
</div>
<div>
   <div><label for="sqfootage">Square Footage</label></div>
   <div><input type="text" name="squarefootage" id="sqfootage" size="10" value="<?php echo $dc->SquareFootage; ?>"></div>
</div>
<div>
   <div><label for="deliveryaddress">Delivery Address</label></div>
   <div><input type="text" name="deliveryaddress" id="deliveryaddress" size="60" value="<?php echo $dc->DeliveryAddress; ?>"></div>
</div>
<div>
   <div><label for="administrator">Administrator</label></div>
   <div><input type="text" name="administrator" id="administrator" size=60 value="<?php echo $dc->Administrator; ?>"></div>
</div>
<div>
   <div><label for="drawingfilename">Drawing URL</label></div>
   <div><input type="text" name="drawingfilename" id="drawingfilename" size=60 value="<?php echo $dc->DrawingFileName; ?>"></div>
</div>
<div class="caption">
<?php
	if($dc->DataCenterID >0){
		echo '   <input type="submit" name="dcaction" value="Update">';
	}else{
		echo '   <input type="submit" name="dcaction" value="Create">';
	}
?>
</div>
</div> <!-- END div.table -->
</form>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->

<?php
	}elseif(isset($_GET["cab"])){
		if($cab->CabinetID >0){
			$cab->GetCabinet($facDB);
		}else{
			$cab->CabinetID=null;
			$cab->DataCenterID=null;
			$cab->Location=null;
			$cab->CabinetHeight=null;
			$cab->Model=null;
			$cab->MaxKW=null;
			$cab->MaxWeight=null;
			$cab->InstallationDate=date('m/d/Y');
		}

		$deptList=$dept->GetDepartmentList($facDB);
		$cabList=$cab->ListCabinets($facDB);
?>

<div class='page installer'>
<div id="sidebar">
<ul>
<a href="?conf"><li>Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a><li class="active">Cabinets</li></a>
<?php if(isset($complete)){ echo '<a href="?complete"><li>Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Data Center Cabinet Inventory</h3>
<?php echo $nodccab; ?>
<div class='center'><div>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>?cab' method='POST'>
<div class='table'>
<div>
   <div>Cabinet</div>
   <div><select name='cabinetid' onChange='form.submit()' <?php echo $nodcdrop; ?>>
   <option value='0'>New Cabinet</option>
<?php
	foreach($cabList as $cabRow){
		echo '<option value=\''.$cabRow->CabinetID.'\'';
		if($cabRow->CabinetID == $cab->CabinetID){
			echo ' selected';
		}
		echo '>'.$cabRow->Location.'</option>\n';
	}
?>
   </select></div>
</div>
<div>
   <div>Data Center</div>
   <div><?php echo $cab->GetDCSelectList($facDB); ?></div>
</div>
<div>
   <div>Location</div>
   <div><input type='text' name='location' size='8' value='<?php echo $cab->Location; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
  <div>Assigned To:</div>
  <div><select name='assignedto' <?php echo $nodcdrop;?>>
    <option value='0'>General Use</option>
<?php
	foreach($deptList as $deptRow){
		echo '<option value=\''.$deptRow->DeptID.'\'';
		if($deptRow->DeptID == $cab->AssignedTo){echo ' selected=\'selected\'';}
		echo '>'.$deptRow->Name.'</option>\n';
	}
?>
  </select>
  </div>
</div>
<div>
   <div>Cabinet Height (U)</div>
   <div><input type='text' name='cabinetheight' size='4' value='<?php echo $cab->CabinetHeight; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Model</div>
   <div><input type='text' name='model' size='30' value='<?php echo $cab->Model; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Maximum kW</div>
   <div><input type='text' name='maxkw' size='30' value='<?php echo $cab->MaxKW; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Maximum Weight</div>
   <div><input type='text' name='maxweight' size='30' value='<?php echo $cab->MaxWeight; ?>' <?php echo $nodcfield;?>></div>
</div>
<div>
   <div>Date of Installation</div>
   <div><input type='text' name='installationdate' size='15' value='<?php echo date('m/d/Y', strtotime($cab->InstallationDate)); ?>' <?php echo $nodcfield;?>></div>
</div>
<?php
	if($nodcdrop==""){
		echo '<div class=\'caption\'>';
		if($cab->CabinetID >0){
			echo '   <input type=\'submit\' name=\'cabaction\' value=\'Update\'>';
		}else{
			echo '   <input type=\'submit\' name=\'cabaction\' value=\'Create\'>';
		}
		echo '</div>';
	}
?>
</div> <!-- END div.table -->
</form>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
<?php
	}elseif(isset($_GET["complete"])){
?>
<div class='page installer'>
<div id="sidebar">
<ul>
<a href="?conf"><li>Configuration</li></a>
<a href="?dept"><li>Departments</li></a>
<a href="?dc"><li>Data Centers</li></a>
<a href="?cab"><li>Cabinets</li></a>
<?php if(isset($complete)){ echo '<a><li class="active">Complete</li></a>'; }?>
</ul>
</div>
<div class='main'>
<h2><?php echo $config->ParameterArray['OrgName']; ?></h2>
<h3>Installation Complete</h3>
<?php echo $nodccab; ?>
<div class='center'><div>

<p>You have completed the basic configuration for openDCIM.  At this time please goto the wiki for additional questions that you might have or join our mailing list at [insert link here].</p>
<p>To start normal operation of openDCIM please delete install.php from the installation directory</p>


</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->


<?php
	}
?>

</body>
</html>
