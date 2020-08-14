<?php
	// Allow the installer to link to the config page
	$devMode=true;
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$subheader=__("Data Center Configuration");
	$timestamp=time();
	$salt=md5('unique_salt' . $timestamp);

	if(!$person->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	define("TOOLKIT_PATH", './vendor/onelogin/php-saml/');
	require_once(TOOLKIT_PATH . '_toolkit_loader.php');
	require_once( "./saml/settings.php" );

	/*
	 * Automatic Key/Cert Generation Function for Saml
	 */
	if ( isset($_POST["NewSPCert"])) {
		error_log( "Generating new Key and Certificate for SAML ServiceProvider" );

		$keyConfig = array(
		    "digest_alg" => "sha512",
		    "private_key_bits" => 4096,
		    "private_key_type" => OPENSSL_KEYTYPE_RSA,
		);
		   
		// Create the private and public key
		$res = openssl_pkey_new($keyConfig);

		// Extract the private key from $res to $privKey
		openssl_pkey_export($res, $newKey );
		$retVal["SAMLspprivateKey"] = $newKey;

		// Fill in the DN
		$dn = array(
		    "countryName" => $_POST["SAMLCertCountry"] != "" ? $_POST["SAMLCertCountry"]:$config->defaults["SAMLCertCountry"],
		    "stateOrProvinceName" => $_POST["SAMLCertProvince"] != "" ? $_POST["SAMLCertProvince"]:$config->defaults["SAMLCertProvince"],
		    "organizationName" => $_POST["SAMLCertOrganization"] != "" ? $_POST["SAMLCertOrganization"]:$config->defaults["SAMLCertOrganization"],
		    "organizationalUnitName" => "openDCIM",
		    "commonName" => "openDCIM SP Cert"
		);

		$req_csr = openssl_csr_new($dn, $req_key);
		$req_cert = openssl_csr_sign($req_csr, NULL, $req_key, 365);

		openssl_x509_export( $req_cert, $newCert );
		$retVal["SAMLspx509cert"] = $newCert;

		$data = openssl_x509_parse(str_replace(array("&#13;", "&#10;"), array(chr(13), chr(10)), $newCert));
		$validTo = date('Y-m-d H:i:s', $data['validTo_time_t']);

		$retVal["SAMLspCertExpiration"] = $validTo;

		echo json_encode($retVal);
		exit;
	}

	// Automagically pull down information from the Identity Provider via the metadata if requested
	if ( isset( $_POST["RefreshMetadata"]) ) {
		$parser = new OneLogin\Saml2\IdPMetadataParser;
		error_log( "Downloading new IdP Metadata from " . $_POST["SAMLIdPMetadataURL"]);
		$IdPSettings = $parser->parseRemoteXML($_POST["SAMLIdPMetadataURL"]);

		$retVal["SAMLidpentityId"] = $IdPSettings['idp']['entityId'];
		$retVal["SAMLidpx509cert"] = $IdPSettings['idp']['x509cert'];
		$retVal["SAMLidpslsURL"] = $IdPSettings['idp']['singleLogoutService']['url'];
		$retVal["SAMLidpssoURL"] = $IdPSettings['idp']['singleSignOnService']['url'];
		
		echo json_encode($retVal);
		exit;
	}


	function BuildDirectoryList($returnjson=false,$path="."){
		$path=trim($path);
		# Make sure we don't have any path shenanigans going on
		$path=str_replace(array("..","./"),"",$path);
		# we don't need trailing slashes, and leading slashes are going to be invalid paths
		$path=trim($path,"/");
		# if path is empty revert to the current directory
		$path=($path)?$path:'.';
		$here=@end(explode(DIRECTORY_SEPARATOR,getcwd()));
		$breadcrumb="<a href=\"?dl=\">$here</a>/";
		$breadcrumbpath="";
		if($path!='.'){
			foreach(explode("/",$path) as $i => $d) {
				$breadcrumb.="<a href=\"?dl=$breadcrumbpath$d\">$d</a>/";
				$breadcrumbpath.="$d/";
			}
		}
		$imageselect=__("Current selection").': '.$breadcrumb.'<br><input type="hidden" id="directoryselectionvalue" value="'.$breadcrumbpath.'"><div id="filelist">';

		$directoriesonly=array();
		$dir=scandir($path);
		foreach($dir as $i => $f){
			if(is_dir($path.DIRECTORY_SEPARATOR.$f) && $f!="." && $f!=".." && !preg_match('/^\./', $f)){
				$imageselect.="<a href=\"?dl=$path/$f\"><span data=\"$breadcrumbpath$f\">$f</span></a><br>\n";
				$filesonly[]=$f;
			}
		}
		$imageselect.="</div>";
		if($returnjson){
			header('Content-Type: application/json');
			echo json_encode($filesonly);
		}else{
			return $imageselect;
		}
	}

	function BuildFileList($returnjson=false){
		$imageselect='<div id="preview"></div><div id="filelist">';

		$filesonly=array();
		$path='./images';
		$dir=scandir($path);
		foreach($dir as $i => $f){
			if(is_file($path.DIRECTORY_SEPARATOR.$f) && round(filesize($path.DIRECTORY_SEPARATOR.$f) / 1024, 2)>=4 && $f!="serverrack.png" && $f!="gradient.png"){
				$mimeType=mime_content_type($path.DIRECTORY_SEPARATOR.$f);
				if(preg_match('/^image/i', $mimeType)){
					$imageselect.="<span>$f</span>\n";
					$filesonly[]=$f;
				}
			}
		}
		$imageselect.="</div>";
		if($returnjson){
			header('Content-Type: application/json');
			echo json_encode($filesonly);
		}else{
			return $imageselect;
		}
	}

	// AJAX Requests
	if(isset($_GET['dl'])){
		echo BuildDirectoryList(isset($_GET['json']),$_GET['dl']);
		exit;
	}
	if(isset($_GET['fl'])){
		echo BuildFileList(isset($_GET['json']));
		exit;
	}
	if(isset($_POST['fe'])){ // checking that a file exists
		echo(is_file($_POST['fe']))?1:0;
		exit;
	}
	if(isset($_POST['mt'])){ // Media Types
		$mt=new MediaTypes();
		$mt->MediaType=trim($_POST['mt']);
		$mt->ColorID=$_POST['mtcc'];
		if(isset($_POST['mtid'])){ // If set we're updating an existing entry
			$mt->MediaID=$_POST['mtid'];
			if(isset($_POST['original'])){
				$mt->GetType();
			    header('Content-Type: application/json');
				echo json_encode($mt);
				exit;
			}
			if(isset($_POST['clear']) || isset($_POST['change'])){
				if(isset($_POST['clear'])){
					MediaTypes::ResetType($mt->MediaID);
				}else{
					$newmediaid=$_POST['change'];
					MediaTypes::ResetType($mt->MediaID,$newmediaid);
				}
				if($mt->DeleteType()){
					echo 'u';
				}else{
					echo 'f';
				}
				exit;
			}
			if($mt->UpdateType()){
				echo 'u';
			}else{
				echo 'f';
			}
		}else{
			if($mt->CreateType()){
				echo $mt->MediaID;
			}else{
				echo 'f';
			}
			
		}
		exit;
	}
	if(isset($_POST['mtused'])){
		$count=MediaTypes::TimesUsed($_POST['mtused']);
		if($count==0){
			$mt=new MediaTypes();
			$mt->MediaID=$_POST['mtused'];
			$mt->DeleteType();
		}
		echo $count;
		exit;
	}
	if(isset($_POST['mtlist'])){
		$codeList=MediaTypes::GetMediaTypeList();
		$output='<option value=""></option>';
		foreach($codeList as $mt){
			$output.="<option value=\"$mt->MediaID\">$mt->MediaType</option>";
		}
		echo $output;
		exit;		
	}

	if(isset($_POST['dcal'])){
		$dca = new DeviceCustomAttribute();
		$dca->Label=trim($_POST['dcal']);
		$dca->AttributeType=trim($_POST['dcat']);
		if(isset($_POST['dcar']) && trim($_POST['dcar'])=="true"){
			$dca->Required=1;
		}else{
			$dca->Required=0;
		}
		if(isset($_POST['dcaa']) && trim($_POST['dcaa'])=="true"){
			$dca->AllDevices=1;
		}else{
			$dca->AllDevices=0;
		}
		if($dca->AttributeType == "checkbox") {
			if(trim($_POST['dcav'])=="true") {
				$dca->DefaultValue="1";
			} else {
				$dca->DefaultValue="0";
			}
		} else {
			$dca->DefaultValue=trim($_POST['dcav']);
		}
		if(isset($_POST['dcaid'])){
			$dca->AttributeID=$_POST['dcaid'];
			if(isset($_POST['original'])){
				$dca->GetDeviceCustomAttribute();
				header('Content-Type: application/json');
				echo json_encode($dca);
				exit;
			}
			if(isset($_POST['clear'])){
				if($dca->RemoveDeviceCustomAttribute()){
					echo 'u';
				}else{
					echo 'f';
				}
				exit;
			}
			if(isset($_POST['removeuses'])){
				if($dca->RemoveFromTemplatesAndDevices()){
					echo 'u';
				} else{
					echo 'f';
				}
				exit;
			} 
			if($dca->UpdateDeviceCustomAttribute()){
				echo 'u';
			}else{
				echo 'f';
			}
			exit;
		}else{
			if($dca->CreateDeviceCustomAttribute()){
				echo $dca->AttributeID;
			}else{
				echo 'f';
			}
			exit;
		}

		exit;
	}
	if(isset($_POST['dcaused'])){
		$count=DeviceCustomAttribute::TimesUsed($_POST['dcaused']);
		if($count==0 && isset($_POST['remove'])){
			$dca=new DeviceCustomAttribute();
			$dca->AttributeID=$_POST['dcaused'];
			if($dca->RemoveDeviceCustomAttribute()){
				echo $count;
				exit;
			}else{
				echo "fail";
				exit;
			}
		}
		echo $count;
		exit;
	}
	// END AJAX Requests

	if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="Update"){
		foreach($config->ParameterArray as $key=>$value){
			if($key=="ClassList"){
				$List=explode(", ",$_REQUEST[$key]);
				$config->ParameterArray[$key]=$List;
			}else{
				// Not all values are inputs on the screen
				@$config->ParameterArray[$key]=$_REQUEST[$key];
			}
		}

		$config->UpdateConfig();

		//Disable all tooltip items and clear the SortOrder
		$dbh->exec("UPDATE fac_CabinetToolTip SET SortOrder = NULL, Enabled=0;");
		if(isset($_POST["tooltip"]) && !empty($_POST["tooltip"])){
			$p=$dbh->prepare("UPDATE fac_CabinetToolTip SET SortOrder=:sortorder, Enabled=1 WHERE Field=:field LIMIT 1;");
			foreach($_POST["tooltip"] as $order => $field){
				$p->bindParam(":sortorder",$order);
				$p->bindParam(":field",$field);
				$p->execute();
			}
		}

		//Disable all cdu tooltip items and clear the SortOrder
		$dbh->exec("UPDATE fac_CDUToolTip SET SortOrder = NULL, Enabled=0;");
		if(isset($_POST["cdutooltip"]) && !empty($_POST["cdutooltip"])){
			$p=$dbh->prepare("UPDATE fac_CDUToolTip SET SortOrder=:sortorder, Enabled=1 WHERE Field=:field LIMIT 1;");
			foreach($_POST["cdutooltip"] as $order => $field){
				$p->bindParam(":sortorder",$order);
				$p->bindParam(":field",$field);
				$p->execute();
			}
		}
		exit;
	}

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

	$directoryselect=BuildDirectoryList();
	$imageselect=BuildFileList();

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

	$regions=array();
	foreach(DateTimeZone::listIdentifiers() as $line){
		$pieces=explode("/",$line);
		if(isset($pieces[1])){
			$regions[$pieces[0]][]=$line;
		}
	}

	$tzmenu='<ul id="tzmenu">';
	foreach($regions as $country => $cityarray){
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

	// Build list of cable color codes
	$cablecolors="";
	$colorselector='<select name="mediacolorcode[]"><option value="0"></option>';

	$codeList=ColorCoding::GetCodeList();
	if(count($codeList)>0){
		foreach($codeList as $cc){
			$colorselector.='<option value="'.$cc->ColorID.'">'.$cc->Name.'</option>';
			$cablecolors.='<div>
					<div><img src="images/del.gif"></div>
					<div><input type="text" name="colorcode[]" data='.$cc->ColorID.' value="'.$cc->Name.'"></div>
					<div><input type="text" name="ccdefaulttext[]" value="'.$cc->DefaultNote.'"></div>
				</div>';
		}
	}
	$colorselector.='</select>';

	// Build list of media types
	$mediatypes="";
	$mediaList=MediaTypes::GetMediaTypeList();

	if(count($mediaList)>0){
		foreach($mediaList as $mt){
			$mediatypes.='<div>
					<div><img src="images/del.gif"></div>
					<div><input type="text" name="mediatype[]" data='.$mt->MediaID.' value="'.$mt->MediaType.'"></div>
					<div><select name="mediacolorcode[]"><option value=""></option>';
			foreach($codeList as $cc){
				$selected=($mt->ColorID==$cc->ColorID)?' selected':'';
				$mediatypes.="<option value=\"$cc->ColorID\"$selected>$cc->Name</option>";
			}
			$mediatypes.='</select></div>
				</div>';
		}
	}

	// build list of existing device custom attributes
	$customattrs="";
	$dcaTypeList=DeviceCustomAttribute::GetDeviceCustomAttributeTypeList();
	$dcaList=DeviceCustomAttribute::GetDeviceCustomAttributeList();
	if(count($dcaList)>0) {
		foreach($dcaList as $dca) {
			$customattrs.='<div>
					<div><img src="images/del.gif"></div>
					<div><input type="text" name="dcalabel[]" data='.$dca->AttributeID.' value="'.$dca->Label.'" class="validate[required,custom[onlyLetterNumberConfigurationPage]]"></div>
					<div><select name="dcatype[]" id="dcatype">';
			foreach($dcaTypeList as $dcatype){
				$selected=($dca->AttributeType==$dcatype)?' selected':'';
				$customattrs.="<option value=\"$dcatype\"$selected>$dcatype</option>";
			}
			$customattrs.='</select></div>
					<div><input type="checkbox" name="dcarequired[]"';
			if($dca->Required) { $customattrs.=' checked'; }
			$customattrs.='></div>
					<div><input type="checkbox" name="dcaalldevices[]"';
			if($dca->AllDevices) { $customattrs.=' checked'; }
			$currinputtype="text";
			$currchecked="";
			if($dca->AttributeType=="checkbox") { 
				$currinputtype="checkbox"; 
				if($dca->DefaultValue) {
					$currchecked=" checked";
				}
			}
			$customattrs.='></div>
					<div><input type="'.$currinputtype.'" name="dcavalue[]" value="'.$dca->DefaultValue.'" '.$currchecked.'></div>
					</div>';
		}
	}

	$dcaTypeSelector='<select name="dcatype[]" id="dcatype">';
	if(count($dcaTypeList)>0){
		foreach($dcaTypeList as $dcatype){
			$selected=($dcatype=='string')?' selected':'';
			$dcaTypeSelector.="<option value=\"$dcatype\"$selected>$dcatype</option>";
		}
	}
	$dcaTypeSelector.="</select>";

	// Make our list of device statuses
	$devstatusList='';
	foreach(DeviceStatus::getStatusList(true) as $status){
		$disabled=($status->Status == 'Reserved' || $status->Status == 'Disposed')?' readonly="readonly"':'';
		$adddel=($disabled)?'../css/blank.gif':'del.gif';
		$reserved=($disabled)?' reserved':'';
		$devstatusList.='
				<div data-StatusID='.$status->StatusID.'>
					<div class="addrem'.$reserved.'"><img src="images/'.$adddel.'" height=20 width=20></div>
					<div><input type="text" class="validate[required,custom[onlyLetterNumberSpacesConfigurationPage]]" value="'.$status->Status.'"'.$disabled.'></div>
					<div><div class="cp"><input type="text" class="color-picker" name="StatusColor" value="'.$status->ColorCode.'"></div></div>
				</div>
		';
	}

	// Figure out what the URL to this page
	$href="";
	$href.=(array_key_exists('HTTPS', $_SERVER)) ? 'https://' : 'http://';
	$href.=$_SERVER['SERVER_NAME'];
	$href.=substr($_SERVER['REQUEST_URI'], 0, -strlen(basename($_SERVER['REQUEST_URI'])));

	// Build up the list of items available for the tooltips
	$tooltip="<select id=\"tooltip\" name=\"tooltip[]\" multiple=\"multiple\">\n";
	$sql="SELECT * FROM fac_CabinetToolTip ORDER BY SortOrder ASC, Enabled DESC, Label ASC;";
	foreach($dbh->query($sql) as $row){
		$selected=($row["Enabled"])?" selected":"";
		$tooltip.="<option value=\"".$row['Field']."\"$selected>".__($row["Label"])."</option>\n";
	}
	$tooltip.="</select>";

	// Build up the list of items available for the tooltips
	$cdutooltip="<select id=\"cdutooltip\" name=\"cdutooltip[]\" multiple=\"multiple\">\n";
	$sql="SELECT * FROM fac_CDUToolTip ORDER BY SortOrder ASC, Enabled DESC, Label ASC;";
	foreach($dbh->query($sql) as $row){
		$selected=($row["Enabled"])?" selected":"";
		$cdutooltip.="<option value=\"".$row['Field']."\"$selected>".__($row["Label"])."</option>\n";
	}
	$cdutooltip.="</select>";
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <link rel="stylesheet" href="css/jquery.ui.multiselect.css" type="text/css">
  <link rel="stylesheet" href="css/uploadifive.css" type="text/css">
  <link rel="stylesheet" href="css/validationEngine.jquery.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.uploadifive.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
  <script type="text/javascript" src="scripts/jquery.ui.multiselect.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine-en.js"></script>
  <script type="text/javascript" src="scripts/jquery.validationEngine.js"></script>
  <script type="text/javascript">
	function binddirectoryselection() {
		$("#directoryselection a").each(function(){
			$(this).click(function(e){
				e.preventDefault();
				$.get(this.href).done(function(data){
					$("#directoryselection").html(data);
					binddirectoryselection();
				});
			});
		});
	}

	// fix this to trigger after the drawing path has been updated
	function rebuildcache(){
		$('<div>').append($('<iframe>').attr('src','build_image_cache.php').css({'max-width':'600px','max-height':'400px','min-height':'300px','align':'middle'})).attr('title','Rebuild device image cache').dialog({
			width: 'auto',
			modal: true,
			open: function(e){
				thismodal=this;
				timer = setInterval( function() {
					$.ajax({
						type: 'GET',
						url: 'scripts/ajax_progress.php',
						dataType: 'json',
						success: function(data) {
							if ( data.Percentage >= 100 ) {
								clearInterval(timer);
								// wait 5 seconds after the rebuild completes and autoclose this dialog
								timer = setInterval( function() {
									$(thismodal).dialog('destroy');
									clearInterval(timer);
								}, 5000 );
								// Reload with Stage 3 to send the file to the user
							}
						}
					})
				}, 1500 );
			}
		});
	};
	
	$(document).ready(function(){
		// ToolTips
		$('#tooltip, #cdutooltip').multiselect();
		$("select:not('#tooltip, #cdutooltip')").each(function(){
			if($(this).attr('data')){
				$(this).val($(this).attr('data'));
			}
		});

		// Generate SP Cert

		$('#btn_spcert').click(function(e) {
			e.preventDefault();

			var formdata=$('#spcertinfo').serializeArray();
			formdata.push({name: "NewSPCert", value: "1"});
			$.post( 'configuration.php', formdata, function(data) {
				var obj=JSON.parse(data);
				$('#SAMLspprivateKey').val(obj.SAMLspprivateKey);
				$('#SAMLspx509cert').val(obj.SAMLspx509cert);
				$('#SPCertExpiration').val(obj.SAMLspCertExpiration);
			} )
		});

		// Get IdP Metadata

		$('#btn_refreshidpmetadata').click(function(e) {
			e.preventDefault();

			var formdata=$('#idpinfo').serializeArray();
			formdata.push({name: "RefreshMetadata", value: "1"});
			$.post( 'configuration.php', formdata, function(data) {
				var obj=JSON.parse(data);
				$('#SAMLidpssoURL').val(obj.SAMLidpssoURL);
				$('#SAMLidpslsURL').val(obj.SAMLidpslsURL);
				$('#SAMLidpentityId').val(obj.SAMLidpentityId);
				$('#SAMLidpx509cert').val(obj.SAMLidpx509cert);
			} )
		});


		// Email test
		$('#btn_smtptest').click(function(e) {
			e.preventDefault();

			var formdata=$('#smtpblock').serializeArray();
			$.post( 'scripts/testemail.php', formdata, function(data) {
				$('#smtptest > div').html(data);
				$('#smtptest').dialog({minWidth: 850, position: { my: "center", at: "top", of: window },closeOnEscape: true });
			});
		});

		// Applies to everything

		$("#configtabs").tabs({
			activate: function( event, ui ) {
				if(ui.newPanel.selector=="#preflight"){
					var preflight=document.getElementsByTagName("iframe");
					preflight[0].style.width='100%';
					preflight[0].style.height=preflight[0].contentWindow.document.body.offsetHeight + 50 + "px";
				}
			}
		});
		$('#configtabs input[defaultvalue],#configtabs select[defaultvalue]').each(function(){
			$(this).parent().after('<div><button type="button">&lt;--</button></div><div><span>'+$(this).attr('defaultvalue')+'</span></div>');
		});
		$("#configtabs input").each(function(){
			$(this).attr('id', $(this).attr('name'));
			$(this).removeAttr('defaultvalue');
		});
		$("#configtabs button").each(function(){
			var a = $(this).parent().prev().find('input,select');
			$(this).click(function(){
				
				var value_to_set = $(this).parent().next().children('span').text();

				// Only for selects, try to assign an existing option, so to avoid to default to an empty value that cannot be saved
				if (a.is("select")){
					a.find('option').each(function (){
						if ($(this).val().toLowerCase() === value_to_set.toLowerCase()){
							value_to_set = $(this).val();
						}
					});
				}
				a.val(value_to_set).trigger('change');

				if(a.hasClass('color-picker')){
					a.minicolors('value', $(this).parent().next().children('span').text()).trigger('change');
				}
				a.triggerHandler("paste");
				a.focus();
				$('input[name="OrgName"]').focus();
			});
		});

		// Style - Site

		function colorchange(hex,id){
			if(id==='HeaderColor'){
				$('#header').css('background-color',hex);
			}else if(id==='BodyColor'){
				$('.main').css('background-color',hex);
			}
		}
		$(".color-picker").minicolors({
			letterCase: 'uppercase',
			change: function(hex, rgb){
					colorchange($(this).val(),$(this).attr('id'));
			}
		}).change(function(){colorchange($(this).val(),$(this).attr('id'));});
		$('input[name="LinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:link, a:hover, a:visited:hover {color: "+$(this).val()+";}</style>");
		});
		$('input[name="VisitedLinkColor"]').blur(function(){
			$("head").append("<style type=\"text/css\">a:visited {color: "+$(this).val()+";}</style>");
		});

		// Reporting

		$('#PDFLogoFile').click(function(){
			var input=this;
			var originalvalue=this.value;
			$.get('',{fl: '1'}).done(function(data){
				$("#imageselection").html(data);
				var upload=$('<input>').prop({type: 'file', name: 'dev_file_upload', id: 'dev_file_upload'}).data('dir','images');
				$("#imageselection").dialog({
					resizable: false,
					height:500,
					width: 670,
					modal: true,
					buttons: {
	<?php echo '					',__("Select"),': function() {'; ?>
							if($('#imageselection #preview').attr('image')!=""){
								$('#PDFLogoFile').val($('#imageselection #preview').attr('image'));
							}
							$(this).dialog("destroy");
						}
					},
					close: function(){
							// they clicked the x, set the value back if something was uploaded
							input.value=originalvalue;
							$('#header').css('background-image', 'url("images/'+input.value+'")');
							$(this).dialog("destroy");
						}
				}).data('input',input);;
				$("#imageselection").next('div').prepend(upload);
				uploadifive();
				$("#imageselection span").each(function(){
					var preview=$('#imageselection #preview');
					$(this).click(function(){
						preview.html('<img src="images/'+$(this).text()+'" alt="preview">').attr('image',$(this).text()).css('border-width', '5px');
						preview.children('img').load(function(){
							var topmargin=0;
							var leftmargin=0;
							if($(this).height()<$(this).width()){
								$(this).width(preview.innerHeight());
								$(this).css({'max-width': preview.innerWidth()+'px'});
								topmargin=Math.floor((preview.innerHeight()-$(this).height())/2);
							}else{
								$(this).height(preview.innerHeight());
								$(this).css({'max-height': preview.innerWidth()+'px'});
								leftmargin=Math.floor((preview.innerWidth()-$(this).width())/2);
							}
							$(this).css({'margin-top': topmargin+'px', 'margin-left': leftmargin+'px'});
						});
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
			});
		});

		// Make SNMP community visible
		$('#SNMPCommunity,#v3AuthPassphrase,#v3PrivPassphrase')
			.focus(function(){$(this).attr('type','text');})
			.blur(function(){$(this).attr('type','password');});

		// General - Site Specific Paths

		$('#drawingpath, #picturepath, #reportspath').click(function(){
			var input=this;
			var originalvalue=this.value;
			$.get('',{dl: this.value}).done(function(data){
				$("#directoryselection").html(data);
				$("#directoryselection").dialog({
					resizable: false,
					height:500,
					width: 670,
					modal: true,
					buttons: {
	<?php echo '					',__("Select"),': function() {'; ?>
							input.value=$('#directoryselectionvalue').val();
							$(input).trigger('change');
							$(this).dialog("destroy");
						}
					},
					close: function(){
							// they clicked the x, set the value back if something was uploaded
							input.value=originalvalue;
							$(this).dialog("destroy");
						}
				}).data('input',input);
				binddirectoryselection();
			});
		}).on('change',function(e){
			if(e.currentTarget.id=='picturepath'){
				window.picturepathupdated=true;
			}
			$(".main form").validationEngine('validate');
		});

		// General - Time and Measurements

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

		// Cabling - Media Types
		function removemedia(row){
			$.post('',{mtused: row.find('div:nth-child(2) input').attr('data')}).done(function(data){
				if(data.trim()==0){
					row.effect('explode', {}, 500, function(){
						$(this).remove();
					});
				}else{
					var defaultbutton={
						"<?php echo __("Clear all"); ?>": function(){
							$.post('',{mtid: row.find('div:nth-child(2) input').attr('data'),mt: '', mtcc: '', clear: ''}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html("AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>");
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var replacebutton={
						"<?php echo __("Replace"); ?>": function(){
							// send command to replace all connections with x
							$.post('',{mtid: row.find('div:nth-child(2) input').attr('data'),mt: '', mtcc: '', change: $('#modal select').val()}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html("AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>");
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var cancelbutton={
						"<?php echo __("Cancel"); ?>": function(){
							$(this).dialog("destroy");
						}
					}
<?php echo "					var modal=$('<div />', {id: 'modal', title: '".__("Media Type Delete Override")."'}).html('<div id=\"modaltext\">".__("This media type is in use somewhere. Select an alternate type to assign to all the records to or choose clear all.")."<select id=\"replaceme\"></select></div>').dialog({"; ?>
						dialogClass: 'no-close',
						appendTo: 'body',
						modal: true,
						buttons: $.extend({}, defaultbutton, cancelbutton)
					});
					$.post('',{mtlist: ''}).done(function(data){
						var choices=$('<select />');
						choices.html(data);
						choices.find('option').each(function(){
							if($(this).val()==row.find('div:nth-child(2) input').attr('data')){$(this).remove();}
						});
						choices.change(function(){
							if($(this).val()==''){ // clear all
								modal.dialog('option','buttons',$.extend({}, defaultbutton, cancelbutton));
							}else{ // replace
								modal.dialog('option','buttons',$.extend({}, replacebutton, cancelbutton));
							}
						});
						modal.find($('#replaceme')).replaceWith(choices);
						
					});
				}
			});

			$('.main form').validationEngine();
		}

		var blankmediarow=$('<div />').html('<div><img src="images/del.gif"></div><div><input id="mediatype[]" name="mediatype[]" type="text"></div><div><select name="mediacolorcode[]"></select></div>');
		function bindmediarow(row){
			var addrem=row.find('div:first-child');
			var mt=row.find('div:nth-child(2) input');
			var mtcc=row.find('div:nth-child(3) select');
			if(mt.val().trim()!='' && addrem.attr('id')!='newline'){
				addrem.click(function(){
					removemedia(row);
				});
			}
			mt.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					mt.change();
				}
			});
			function update(inputobj){
				if(mt.val().trim()==''){
					// reset value to previous
					$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val(),original:''}).done(function(jsondata){
						mt.val(jsondata.MediaType);
						mtcc.val(jsondata.ColorID);
					});
					mt.effect('highlight', {color: 'salmon'}, 1500);
					mtcc.effect('highlight', {color: 'salmon'}, 1500);
				}else{
					// attempt to update
					$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val()}).done(function(data){
						if(data.trim()=='f'){ // fail
							$.post('',{mt: mt.val(), mtid: mt.attr('data'), mtcc: mtcc.val(),original:''}).done(function(jsondata){
								mt.val(jsondata.MediaType);
								mtcc.val(jsondata.ColorID);
							});
							mt.effect('highlight', {color: 'salmon'}, 1500);
							mtcc.effect('highlight', {color: 'salmon'}, 1500);
						}else if(data.trim()=='u'){ // updated
							mt.effect('highlight', {color: 'lightgreen'}, 2500);
							mtcc.effect('highlight', {color: 'lightgreen'}, 2500);
						}else{ // created
							var newitem=blankmediarow.clone();
							newitem.find('div:nth-child(2) input').val(mt.val()).attr('data',data.trim());
							newitem.find('div:nth-child(3) select').replaceWith(mtcc.clone());
							bindmediarow(newitem);
							row.before(newitem);
							newitem.find('div:nth-child(3) select').val(mtcc.val()).focus();
							if(addrem.attr('id')=='newline'){
								mt.val('');
								mtcc.val('');
							}else{
								row.remove();
							}
						}
					});
				}
			}
			mt.change(function(){
				update($(this));
			});
			mtcc.change(function(){
				var row=$(this).parent('div').parent('div');
				if(row.find('div:first-child').attr('id')!='newline'){
					update($(this));
				}else if(row.find('div:nth-child(2) input').val().trim()!=''){
					update($(this));
				}
			});
		}

		// Add a new blank row
		$('#mediatypes > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankmediarow.clone();
					// Clone the current dropdown list
					newitem.find('select[name="mediacolorcode[]"]').replaceWith((row.find('select[name="mediacolorcode[]"]').clone()));
					newitem.find('div:first-child').click(function(){
						removecolor($(this).parent('div'),false);
					});
					bindmediarow(newitem);
					row.before(newitem);
				});
			}
			bindmediarow($(this).parent('div'));
		});

		// Update color drop lists
		function updatechoices(){
			$.get('api/v1/colorcode').done(function(data){
				if(!data.error){
					$('#mediatypes > div ~ div').each(function(){
						var list=$(this).find('select[name="mediacolorcode[]"]');
						var dc=list.val();
						list.html($('<option>').val('0'));
						for(var i in data.colorcode){
							list.append($('<option>').val(data.colorcode[i].ColorID).text(data.colorcode[i].Name));
						}
						list.val(dc);
					});
				}
			});
		}

		



		// Cabling - Cable Colors

		function removecolor(rowobject,lookup){
			if(!lookup){
				rowobject.remove();
			}else{
				$.get('api/v1/colorcode/'+rowobject.find('div:nth-child(2) input').attr('data')+'/timesused').done(function(data){
					if(data.colorcode==0){
						$.ajax('api/v1/colorcode/'+rowobject.find('div:nth-child(2) input').attr('data'),{type: 'delete'}).done(function(data){
							if(!data.error){
								updatechoices();
								rowobject.effect('explode', {}, 500, function(){
									$(this).remove();
								});
							}
						});
					}else{
						var defaultbutton={
							"<?php echo __("Clear all"); ?>": function(){
								$.post('api/v1/colorcode/'+rowobject.find('div:nth-child(2) input').attr('data')+'/replacewith/'+$('#modal select').val()).done(function(data){
									if(!data.error){ // success
										$.ajax('api/v1/colorcode/'+rowobject.find('div:nth-child(2) input').attr('data'),{type: 'delete'});
										$('#modal').dialog("destroy");
										updatechoices();
										rowobject.effect('explode', {}, 500, function(){
											$(this).remove();
										});
									}else{ // failed to delete
										$('#modaltext').html("AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>");
										$('#modal').dialog('option','buttons',cancelbutton);
									}
								});
							}
						}
						var replacebutton={
							"<?php echo __("Replace"); ?>": function(){
								// send command to replace all connections with x
								$.post('api/v1/colorcode/'+rowobject.find('div:nth-child(2) input').attr('data')+'/replacewith/'+$('#modal select').val()).done(function(data){
									if(!data.error){ // success
										$.ajax('api/v1/colorcode/'+rowobject.find('div:nth-child(2) input').attr('data'),{type: 'delete'});
										$('#modal').dialog("destroy");
										updatechoices();
										rowobject.effect('explode', {}, 500, function(){
											$(this).remove();
										});
										// Need to trigger a reload of any of the media types that had this 
										// color so they will display the new color
										$('#mediatypes > div ~ div:not(:last-child) input').val('').change();
									}else{ // failed to delete
										$('#modaltext').html("AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>");
										$('#modal').dialog('option','buttons',cancelbutton);
									}
								});
							}
						}
						var cancelbutton={
							"<?php echo __("Cancel"); ?>": function(){
								$(this).dialog("destroy");
							}
						}
<?php echo "						var modal=$('<div />', {id: 'modal', title: '".__("Code Delete Override")."'}).html('<div id=\"modaltext\">".__("This code is in use somewhere. You can either choose to clear all instances of this color being used or choose to have them replaced with another color.")." <select id=\"replaceme\"></select></div>').dialog({"; ?>
							dialogClass: 'no-close',
							appendTo: 'body',
							modal: true,
							buttons: $.extend({}, defaultbutton, cancelbutton)
						});
						var choices=$('div#mediatypes.table div:last-child div select').clone();
						choices.find('option').each(function(){
							if($(this).val()==rowobject.find('div:nth-child(2) input').attr('data')){$(this).remove();}
						});
						choices.change(function(){
							if($(this).val()==''){ // clear all
								modal.dialog('option','buttons',$.extend({}, defaultbutton, cancelbutton));
							}else{ // replace
								modal.dialog('option','buttons',$.extend({}, replacebutton, cancelbutton));
							}
						});
						modal.find($('#replaceme')).replaceWith(choices);
					}
				});
			}
		}
		var blankrow=$('<div />').html('<div><img src="images/del.gif"></div><div><input type="text" name="colorcode[]"></div><div><input type="text" name="ccdefaulttext[]"></div>');
		function bindrow(row){
			var addrem=row.find('div:first-child');
			var cc=row.find('div:nth-child(2) input');
			var ccdn=row.find('div:nth-child(3) input');
			if(cc.val().trim()!='' && addrem.attr('id')!='newline'){
				addrem.click(function(){
					removecolor(row,true);
				});
			}
			cc.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					cc.change();
				}
			});
			ccdn.keypress(function(event){
				if(event.keyCode==10 || event.keyCode==13){
					event.preventDefault();
					ccdn.change();
				}
			});
			function FlashGreen(){
				cc.effect('highlight', {color: 'lightgreen'}, 2500);
				ccdn.effect('highlight', {color: 'lightgreen'}, 2500);
			}
			function FlashRed(){
				cc.effect('highlight', {color: 'salmon'}, 1500);
				ccdn.effect('highlight', {color: 'salmon'}, 1500);
			}
			row.find('div > input').each(function(){
				// If a value changes then check it for conflicts, if no conflict update
				$(this).change(function(){
					if(cc.val().trim()!=''){
						// if this is defined we're doing an update operation
						if(cc.attr('data')){
							$.post('api/v1/colorcode/'+cc.attr('data'),{ColorID: cc.attr('data'),Name: cc.val(),DefaultNote: ccdn.val()}).done(function(data){
								if(data.error){
									$.get('api/v1/colorcode/'+cc.attr('data')).done(function(data){
										for(var i in data.colorcode){
											var colorcode=data.colorcode[i];
											cc.val(colorcode.Name);
											ccdn.val(colorcode.DefaultNote);
										}
									});
									FlashRed();
								}else{ // updated
									FlashGreen();
									// update media type color pick lists
									updatechoices();
								}
							});
						}else{ // Color code not defined we must be creating a new one
							$.ajax('api/v1/colorcode/'+cc.val(),{type: 'put',data:{Name: cc.val(),DefaultNote: ccdn.val()}}).done(function(data){
								if(data.error){
									FlashRed();
								}else{
									var newitem=blankrow.clone();
									for(var i in data.colorcode){
										newitem.find('div:nth-child(2) input').val(cc.val()).attr('data',data.colorcode[i].ColorID);
									}
									bindrow(newitem);
									row.before(newitem);
									newitem.find('div:nth-child(3) input').val(ccdn.val()).focus();
									if(addrem.attr('id')=='newline'){
										cc.val('');
										ccdn.val('');
									}else{
										row.remove();
									}
									// update media type color pick lists
									updatechoices();
								}
							});
						}
					}else if(cc.val().trim()=='' && ccdn.val().trim()=='' && addrem.attr('id')!='newline'){
						// If both blanks are emptied of values and they were an existing data pair
						$.get('api/v1/colorcode/'+cc.attr('data')).done(function(data){
							for(var i in data.colorcode){
								var colorcode=data.colorcode[i];
								cc.val(colorcode.Name);
								ccdn.val(colorcode.DefaultNote);
							}
						});
						FlashRed();
					}
				});
			});
		}
		$('#cablecolor > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankrow.clone();
					newitem.find('div:first-child').click(function(){
						removecolor($(this).parent('div'),false);
					});
					bindrow(newitem);
					row.before(newitem);
				});
			}
			bindrow($(this).parent('div'));
		});

		// device custom attribute rows
		var blankdcarow=$('<div />').html('<div><img src="images/del.gif"></div><div><input type="text" name="dcalabel[]" class="validate[required,custom[onlyLetterNumberConfigurationPage]]"></div><div><select name="dcatype[]" id="dcatype"></select></div></div><div><input type="checkbox" name="dcarequired[]"></div><div><input type="checkbox" name=dcaalldevices[]"></div><div><input type="text" name="dcavalue[]"></div>');

		// row is expected to be the row object and data to be a valid object
		function updatecarow(row,data){
			for(var x in data){
				if(x=='AttributeID'){continue;}
				if(x=='AllDevices' || x=='Required'){
					eval("row."+x+".prop('checked',"+data[x]+")");
				}else{
					eval("row."+x+".val(\""+data[x]+"\")");
				}
			}
		}

		function revertdefault(row,error){
			$.post('',{dcal:'',dcaid:row.Label.attr('data'),original:''}).done(function(data){
				updatecarow(row,data);
			});
			if(error){
				row.effect('highlight', {color: 'salmon'}, 1500);
			}else{
				row.effect('highlight', {color: 'lightgreen'}, 1500);
			}
		}

		function binddcarow(row) {
			var addrem=row.find('div:first-child');
			var dcal=row.find('div:nth-child(2) input');
			var dcat=row.find('div:nth-child(3) select');
			var dcar=row.find('div:nth-child(4) input');
			var dcaa=row.find('div:nth-child(5) input');
			var dcav=row.find('div:nth-child(6) input');
			row.addrem=row.find('div:first-child');
			row.Label=row.find('div:nth-child(2) input');
			row.AttributeType=row.find('div:nth-child(3) select');
			row.Required=row.find('div:nth-child(4) input');
			row.AllDevices=row.find('div:nth-child(5) input');
			row.DefaultValue=row.find('div:nth-child(6) input');

			// Create click target for add / remove row
			if(addrem.attr('id')!='newline' && row.Label.val()!=''){
				addrem.click(function(){
					removedca(row,true);
				});
			}
			// This is to keep an enter from submitting the form
			row.find(':input').change(update).keypress(function(e){
				if(e.keyCode==10 || e.keyCode==13){
					e.preventDefault();
					$(this).change();
				}
			});

			function update(e){
				if(e.currentTarget.tagName=="SELECT"){
					function processChange() { 
						if(e.currentTarget.value == "checkbox") {
							row.DefaultValue.attr('type', 'checkbox');
							row.DefaultValue.prop('checked', false);
							row.DefaultValue.val('');
						} else {
							row.DefaultValue.attr('type', 'text');
							row.DefaultValue.val('');
						}
						if(row.addrem.attr('id')!='newline'){
							row.DefaultValue.change();
						} else if(row.Label.val().trim()!=''){
							row.DefaultValue.change();
						}
					}
					
					if(row.addrem.attr('id')=='newline') { 
						processChange();
					} else {
						$.post('',{dcaused: row.Label.attr('data')}).done(function(data){
							if(data.trim()==0){
								// if not in use, just let the type change
								processChange();
							} else if(data.trim()=="fail") {
								var cancelbutton={
									"<?php echo __("Cancel"); ?>": function(){
										revertdefault(row,true);
										$(this).dialog("destroy");
									}
								}
								<?php echo "				var modal=$('<div />', {id: 'modal', title: \"".__("Custom Device Attribute Type Change Error")."\"}).html(\"<div id=\\\"modaltext\\\">AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br>".__("Something just went horribly wrong.")."</div>\").dialog({"; ?>
								dialogClass: 'no-close',
								appendTo: 'body',
								modal: true,
								buttons: $.extend({}, cancelbutton)
								});
							} else {
								var defaultbutton={
									"<?php echo __("Change Type and Clear all uses"); ?>": function(){
										$.post('',{dcaid: row.Label.attr('data'),dcal: '', dcar: '', dcaa: '', dcav: '', dcat: '', removeuses: ''}).done(function(data){
											if(data.trim()=='u'){ // success
												$('#modal').dialog("destroy");
												processChange();
											}else{ // failed to delete
												$('#modaltext').html("AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>");
												$('#modal').dialog('option','buttons',cancelbutton);
												revertdefault(row,true);
											}
										});
									}
								}
								var cancelbutton={
									"<?php echo __("Cancel"); ?>": function(){
										revertdefault(row,true);
										$(this).dialog("destroy");
									}
								}
								<?php echo "				var modal=$('<div />', {id: 'modal', title: \"".__("Custom Device Attribute Type Change Override")."\"}).html(\"<div id=\\\"modaltext\\\">".__("This custom device attribute is in use somewhere. If you choose to change the attribute type, it will be cleared from all devices and device templates.")."</div>\").dialog({"; ?>
								dialogClass: 'no-close',
								appendTo: 'body',
								modal: true,
								buttons: $.extend({}, defaultbutton, cancelbutton)
								});
							}
						});
					}
				}else{
					var dcavtosend=row.DefaultValue.val();
					if(row.AttributeType.val()=='checkbox'){
						dcavtosend=row.DefaultValue.prop('checked');
					}	
					if(row.Label.val().trim()=='' && row.addrem.prop('id')!='newline'){
						//reset to previous value
						revertdefault(row,true);
					} else {
						// attempt to update
						if(((row.addrem.prop('id')=='newline' && row.Label.val()!='') || row.addrem.prop('id')!='newline' ) && $(".main form").validationEngine('validate')){
							$.post('',{dcal: dcal.val(), dcaid: dcal.attr('data'), dcat: dcat.val(), dcar: dcar.prop('checked'), dcaa: dcaa.prop('checked'), dcav: dcavtosend}).done(function(data){
								if(data.trim()=='f'){ //fail
									revertdefault(row,true);
								} else if(data.trim()=='u') { // updated
									row.effect('highlight', {color: 'lightgreen'}, 2500);
								} else { // created
									var newitem=blankdcarow.clone();
									binddcarow(newitem);
									newitem.AttributeType.replaceWith(row.AttributeType.clone());
									newitem.Label.attr('data',data);
									row.before(newitem);
									revertdefault(newitem,false);
									// The new row didn't have data when the bind ran
									// this will allow it to be removed immediately
									newitem.addrem.click(function(){
										removedca(newitem,true);
									});
									newitem.DefaultValue.val(dcav.val()).focus();
									if(addrem.attr('id')=='newline'){
										dcal.val('');
										dcat.val('string');
										dcar.prop('checked',false);
										dcaa.prop('checked',false);
										dcav.attr('type', 'text');
										dcav.val('');
									} else {
										row.remove();
									}	
								}
							});
						}
					}
				}
			}
		}
		$('#customattrs > div ~ div > div:first-child').each(function(){
			if($(this).attr('id')=='newline'){
				var row=$(this).parent('div');
				$(this).click(function(){
					var newitem=blankdcarow.clone();
					newitem.find('select[name="dcatype[]"]').replaceWith((row.find('select[name="dcatype[]"]').clone()));
					newitem.find('div:first-child').click(function(){
						removedca($(this).parent('div'),false);
					});
					binddcarow(newitem);
					row.before(newitem);
				});
			}
			binddcarow($(this).parent('div'));
		});

        function removedca(row,lookup){
		  if(!lookup) {
			row.remove();
		  } else {
			$.post('',{dcaused: row.Label.attr('data'), remove: ''}).done(function(data){
				if(data.trim()==0){
					row.effect('explode', {}, 500, function(){
						$(this).remove();
					});
				}else if(data.trim()=="fail") {
					var cancelbutton={
						"<?php echo __("Cancel"); ?>": function(){
							$(this).dialog("destroy");
						}
					}
<?php echo "				var modal=$('<div />', {id: 'modal', title: \"".__("Custom Device Attribute Delete Error")."\"}).html(\"<div id=\\\"modaltext\\\">AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br>".__("Something just went horribly wrong.")."</div>\").dialog({"; ?>
					dialogClass: 'no-close',
					appendTo: 'body',
					modal: true,
					buttons: $.extend({}, cancelbutton)
					});

				}else{
					var defaultbutton={
						"<?php echo __("Delete from All Devices/Templates"); ?>": function(){
							$.post('',{dcaid: row.find('div:nth-child(2) input').attr('data'),dcal: '', dcar: '', dcaa: '', dcav: '', clear: ''}).done(function(data){
								if(data.trim()=='u'){ // success
									$('#modal').dialog("destroy");
									row.effect('explode', {}, 500, function(){
										$(this).remove();
									});
								}else{ // failed to delete
									$('#modaltext').html("AAAAAAAAAAHHHHHHHHHH!!!  *crash* *fire* *chaos*<br><br><?php echo __("Something just went horribly wrong."); ?>");
									$('#modal').dialog('option','buttons',cancelbutton);
								}
							});
						}
					}
					var cancelbutton={
						"<?php echo __("Cancel"); ?>": function(){
							$(this).dialog("destroy");
						}
					}
<?php echo "				var modal=$('<div />', {id: 'modal', title: \"".__("Custom Device Attribute Delete Override")."\"}).html(\"<div id=\\\"modaltext\\\">".__("This custom device attribute is in use somewhere. If you choose to delete the attribute, it will be removed from all devices and device templates.")."</div>\").dialog({"; ?>
					dialogClass: 'no-close',
					appendTo: 'body',
					modal: true,
					buttons: $.extend({}, defaultbutton, cancelbutton)
                                        });
                                }
                        });
		  }
                }

		function bindstatusrow(div) {
			var row=$(div);
			var addrem=row.find('div:first-child:not(.cp)');
			var dsl=row.find('div:nth-child(2) input');
			var dsc=row.find('div:nth-child(3) input');
			row.addrem=addrem;
			row.Label=dsl;
			row.Color=dsc;
			row.ID=div.dataset['statusid'];
			// save the row object back to the div for quick access later
			div.row=row;

			// Create click target for add / remove row
			if(!addrem.hasClass('newstatus') && !addrem.hasClass('reserved')){
				addrem.click(function(e){
					removestatus(e);
				});
			}else if(addrem.hasClass('newstatus')){
				addrem.click(function(e){
					addstatus(e);
				});
			}

			// Bind update event to the color change selection
			dsc.blur(updatestatus);

			// This is to keep an enter from submitting the form
			row.find(':input:not(.newstatus >)').change(updatestatus).keypress(function(e){
				if(e.keyCode==10 || e.keyCode==13){
					e.preventDefault();
					updatestatus(e);
				}
			});
			row.find('.newstatus > :input').keypress(function(e){
				if(e.keyCode==10 || e.keyCode==13){
					e.preventDefault();
					addrem.trigger('click');
				}
			});
		}

		function createstatusrow(statusobject){
			var newrow=$('<div>').attr('data-StatusID',statusobject.StatusID);
			newrow.append($('<div>').addClass('addrem').append($('<img>').attr({'src':'images/del.gif','height':20,'width':20})));
			newrow.append($('<div>').append($('<input>').addClass('validate[required,custom[onlyLetterNumberSpacesConfigurationPage]]').val(statusobject.Status)));
			newrow.append($('<div>').append($('<div>').addClass('cp').append($('<input>').attr({'type':'text','name':'StatusColor'}).val(statusobject.ColorCode).addClass('color-picker'))));

			return newrow;
		}

		$('#devstatus > div ~ div > div:first-child').each(function(){
			bindstatusrow(this.parentElement);
		});

		function StatusFlashGreen(row){
			row.effect('highlight', {color: 'lightgreen'}, 2500);
			row.Label.effect('highlight', {color: 'lightgreen'}, 2500);
		}
		function StatusFlashRed(row){
			row.effect('highlight', {color: 'salmon'}, 1500);
			row.Label.effect('highlight', {color: 'salmon'}, 1500);
		}

		function addstatus(e){
			var row=e.currentTarget.parentElement.row;
			if(row.Label.val()!='' && $(".main form").validationEngine('validate')){
				$.ajax({
					type: 'PUT',
					url: 'api/v1/devicestatus/'+row.Label.val(),
					async: false,
					dataType: "JSON",
					data: null,
					success: function(data){
						if(!data.error){
							for(var x in data.devicestatus){
								row.Label.val('');
								var newrow=createstatusrow(data.devicestatus[x]);
								bindstatusrow(newrow[0]);
								newrow.insertBefore(row);
								newrow.find(".color-picker").minicolors({
									letterCase: 'uppercase',
									change: function(hex, rgb){
										colorchange($(this).val(),$(this).attr('id'));
									}
								});
								// Had to reference the row inside the row because I don't know
								StatusFlashGreen(newrow[0].row);
							}
						}else{
							StatusFlashRed(row);
						}
					}
				});
			}else{
				console.log('clicked add, label is blank, do nothing');
			}
		}

		function removestatus(e){
			var row=e.currentTarget.parentElement.row;
			$.ajax({
				type: 'DELETE',
				url: 'api/v1/devicestatus/'+row.ID,
				async: false,
				dataType: "JSON",
				data: null,
				success: function(data){
					if(!data.error){
						// remove row from dom
						row.effect('explode', {}, 500, function(){
							row.remove();
						});
					}else{
						StatusFlashRed(row);
					}
				},
				error: function(data){
					if(!data.error){
						StatusFlashRed(row);
					}else{
						StatusFlashRed(row);
					}
				}
			});
		}

		function updatestatus(e){
			if(e.currentTarget.classList.contains('color-picker')){
				var row=e.currentTarget.parentElement.parentElement.parentElement.parentElement.row;
			}else{
				var row=e.currentTarget.parentElement.parentElement.row;
			}
			if(row.Label.val()!='' && $(".main form").validationEngine('validate')){
				$.ajax({
					type: 'POST',
					url: 'api/v1/devicestatus/'+row.ID,
					async: false,
					dataType: "JSON",
					data: {'StatusID':row.ID,'Status':row.Label.val(),'ColorCode':row.Color.val()},
					success: function(data){
						if(!data.error){
							StatusFlashGreen(row);
						}else{
							StatusFlashRed(row);
						}
					},
					error: function(data){
						if(!data.error){
							StatusFlashRed(row);
						}else{
							StatusFlashRed(row);
						}
					}
				});
			}else{
				console.log('tried to change label to be blank, do nothing');
			}
		}

		// Reporting - Utilities

		$('input[id^="snmp"],input[id="cut"],input[id="dot"]').each(function(){
			var a=$(this);
			var icon=$('<span>',{style: 'float:right;margin-top:5px;'}).addClass('ui-icon').addClass('ui-icon-info');
			a.parent('div').append(icon);
			$(this).keyup(function(){
				var b=a.next('span');
				$.post('',{fe: $(this).val()}).done(function(data){
					if(data==1){
						a.effect('highlight', {color: 'lightgreen'}, 1500);
						b.addClass('ui-icon-circle-check').removeClass('ui-icon-info').removeClass('ui-icon-circle-close');
					}else{
						a.effect('highlight', {color: 'salmon'}, 1500);
						b.addClass('ui-icon-circle-close').removeClass('ui-icon-info').removeClass('ui-icon-circle-check');
					}
				});
			});
			$(this).trigger('keyup');
		});

		// Convert this bitch over to an ajax form submit
		$('button[name="action"]').click(function(e){
			if($(".main form").validationEngine('validate')){
				// Clear the messages blank
				$('#messages').text('');
				// Don't let this button do a real form submit
				e.preventDefault();
				// Collect the config data
				var formdata=$(".main form").serializeArray();
				// Set the action of the form to Update
				formdata.push({name:'action',value:"Update"});
				// Post the config data then update the status message
				$.post('',formdata).done(function(){
						$('#messages').text('Updated');
						if(typeof(window.picturepathupdated)==="boolean"){
							if(window.picturepathupdated){
								$('#messages').text("<?php echo __("Verify directory rights");?>");
								$('a[href=#preflight]').trigger('click');
								window.scrollTo(0,0);
								rebuildcache();
								window.picturepathupdated=false;
							}
						}
					}).error(function(){
						$('#messages').text('Something is broken');
					});
			}
		});

		$('.main form').submit(function(e){
			e.preventDefault();
		});

		// Make all the selects 100% width
		sheet.insertRule(".config .main select { width: 100%; }", 0);
	});

	// Making it to where I can add a rule to make the config page look nicer
	var sheet=(function() {
		var style = document.createElement("style");
		style.appendChild(document.createTextNode(""));
		document.head.appendChild(style);
		return style.sheet;
	})();

	// File upload
	function reload() {
		$.get('configuration.php?fl&json').done(function(data){
			var filelist=$('#filelist');
			filelist.html('');
			for(var f in data){
				filelist.append($('<span>').text(data[f]));
			}
			bindevents();
		});
	}
	function bindevents() {
		$("#imageselection span").each(function(){
			var preview=$('#imageselection #preview');
			$(this).click(function(){
				preview.css({'border-width': '5px', 'width': '380px', 'height': '380px'});
				preview.html('<img src="images/'+$(this).text()+'" alt="preview">').attr('image',$(this).text());
				preview.children('img').load(function(){
					var topmargin=0;
					var leftmargin=0;
					if($(this).height()<$(this).width()){
						$(this).width(preview.innerHeight());
						$(this).css({'max-width': preview.innerWidth()+'px'});
						topmargin=Math.floor((preview.innerHeight()-$(this).height())/2);
					}else{
						$(this).height(preview.innerHeight());
						$(this).css({'max-height': preview.innerWidth()+'px'});
						leftmargin=Math.floor((preview.innerWidth()-$(this).width())/2);
					}
					$(this).css({'margin-top': topmargin+'px', 'margin-left': leftmargin+'px'});
				});
				$("#imageselection span").each(function(){
					$(this).removeAttr('style');
				});
				$(this).css({'border':'1px dotted black','background-color':'#eeeeee'});
				$('#header').css('background-image', 'url("images/'+$(this).text()+'")');
			});
			if($($("#imageselection").data('input')).val()==$(this).text()){
				$(this).click();
				this.parentNode.scrollTop=(this.offsetTop - (this.parentNode.clientHeight / 2) + (this.scrollHeight / 2) );
			}
		});
	}
	function uploadifive() {
		$('#dev_file_upload').uploadifive({
			'formData' : {
					'timestamp' : '<?php echo $timestamp;?>',
					'token'     : '<?php echo $salt;?>',
					'dir'		: 'images'
				},
			'buttonText'		: 'Upload new image',
			'width'				: '150',
			'removeCompleted' 	: true,
			'checkScript'		: 'scripts/check-exists.php',
			'uploadScript'		: 'scripts/uploadifive.php',
			'onUploadComplete'	: function(file, data) {
				data=$.parseJSON(data);
				if(data.status=='1'){
					// something broke, deal with it
					var toast=$('<div>').addClass('uploadifive-queue-item complete');
					var close=$('<a>').addClass('close').text('X').click(function(){$(this).parent('div').remove();});
					var span=$('<span>');
					var error=$('<div>').addClass('border').css({'margin-top': '2px', 'padding': '3px'}).text(data.msg);
					toast.append(close);
					toast.append($('<div>').append(span.clone().addClass('filename').text(file.name)).append(span.clone().addClass('fileinfo').text(' - Error')));
					toast.append(error);
					$('#uploadifive-'+this[0].id+'-queue').append(toast);
				}else{
					$($("#imageselection").data('input')).val(file.name.replace(/\s/g,'_'));
					// fuck yeah, reload the file list
					reload($(this).data('dir'));
				}
			}
		});
	}

  </script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page config">
<?php
	include( "sidebar.inc.php" );

echo '<div class="main">
<div class="center"><div>
<h3></h3><h3 id="messages"></h3>
<form enctype="multipart/form-data" method="POST">
   <input type="hidden" name="Version" value="',$config->ParameterArray["Version"],'">

	<div id="configtabs">
		<ul>
			<li><a href="#general">',__("General"),'</a></li>
			<li><a href="#workflow">',__("Workflow"),'</a></li>
			<li><a href="#style">',__("Style"),'</a></li>
			<li><a href="#email">',__("Email"),'</a></li>
			<li><a href="#reporting">',__("Reporting"),'</a></li>
			<li><a href="#tt">',__("ToolTips"),'</a></li>
			<li><a href="#cc">',__("Cabling"),'</a></li>
			<li><a href="#dca">',__("Custom Device Attributes"),'</a></li>
			<li><a href="#mappers">',__("Attribute Mapping"),'</a></li>
			<li><a href="#ldap">',__("LDAP"),'</a></li>
			<li><a href="#saml">',__("SAML"),'</a></li>
			<li><a href="#preflight">',__("Pre-Flight Check"),'</a></li>
		</ul>
		<div id="general">
			<div class="table">
				<div>
					<div><label for="OrgName">',__("Organization Name"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["OrgName"],'" name="OrgName" value="',$config->ParameterArray["OrgName"],'"></div>
				</div>
				<div>
					<div><label for="Locale">',__("Locale"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["Locale"],'" name="Locale" value="',$config->ParameterArray["Locale"],'"></div>
				</div>
				<div>
					<div><label for="DefaultPanelVoltage">',__("Default Panel Voltage"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["DefaultPanelVoltage"],'" name="DefaultPanelVoltage" value="',$config->ParameterArray["DefaultPanelVoltage"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Site Specific Paths"),'</h3>
			<div id="directoryselection" title="Image file directory selector">
				',$directoryselect,'
			</div>
			<div class="table" id="sitepaths">
				<div>
					<div><label for="drawingpath">',__("Relative path for Drawings"),'</label></div>
					<div><input type="text" id="drawingpath" defaultvalue="',$config->defaults["drawingpath"],'" name="drawingpath" value="',$config->ParameterArray["drawingpath"],'" class="validate[required,custom[endWithSlashConfigurationPage]]"></div>
				</div>
				<div>
					<div><label for="picturepath">',__("Relative path for Pictures"),'</label></div>
					<div><input type="text" id="picturepath" defaultvalue="',$config->defaults["picturepath"],'" name="picturepath" value="',$config->ParameterArray["picturepath"],'" class="validate[required,custom[endWithSlashConfigurationPage]]">
					</div>
				</div>
				<div>
					<div><label for="reportspath">',__("Relative path for Local/Custom Reports"),'</label></div>
					<div><input type="text" id="reportspath" defaultvalue="',$config->defaults["reportspath"],'" name="reportspath" value="',$config->ParameterArray["reportspath"],'" class="validate[required,custom[endWithSlashConfigurationPage]]">
					</div>
				</div>
			</div> <!-- end table -->			
			<h3>',__("Time and Measurements"),'</h3>
			<div class="table" id="timeandmeasurements">
				<div>
					<div><label for="timezone">',__("Time Zone"),'</label></div>
					<div><input type="text" readonly="readonly" id="timezone" defaultvalue="',$config->defaults["timezone"],'" name="timezone" value="',$config->ParameterArray["timezone"],'"></div>
				</div>
				<div>
					<div><label for="logretention">',__("Log Retention (Days)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["logretention"],'" name="logretention" value="',$config->ParameterArray["logretention"],'"></div>
				</div>
				<div>
					<div><label for="mDate">',__("Manufacture Date"),'</label></div>
					<div><select id="mDate" name="mDate" defaultvalue="',$config->defaults["mDate"],'" data="',$config->ParameterArray["mDate"],'">
							<option value="blank"',(($config->ParameterArray["mDate"]=="blank")?' selected="selected"':''),'>',__("Blank"),'</option>
							<option value="now"',(($config->ParameterArray["mDate"]=="now")?' selected="selected"':''),'>',__("Now"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="wDate">',__("Warranty Date"),'</label></div>
					<div><select id="wDate" name="wDate" defaultvalue="',$config->defaults["wDate"],'" data="',$config->ParameterArray["wDate"],'">
							<option value="blank"',(($config->ParameterArray["wDate"]=="blank")?' selected="selected"':''),'>',__("Blank"),'</option>
							<option value="now"',(($config->ParameterArray["wDate"]=="now")?' selected="selected"':''),'>',__("Now"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="mUnits">',__("Measurement Units"),'</label></div>
					<div><select id="mUnits" name="mUnits" defaultvalue="',$config->defaults["mUnits"],'" data="',$config->ParameterArray["mUnits"],'">
							<option value="english"',(($config->ParameterArray["mUnits"]=="english")?' selected="selected"':''),'>',__("English"),'</option>
							<option value="metric"',(($config->ParameterArray["mUnits"]=="metric")?' selected="selected"':''),'>',__("Metric"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="PageSize">',__("Page Size"),'</label></div>
					<div><select id="PageSize" name="PageSize" defaultvalue="',$config->defaults["PageSize"],'" data="',$config->ParameterArray["PageSize"],'">
							<option value="A4"',(($config->ParameterArray["PageSize"]=="A4")?' selected="selected"':''),'>',__("A4"),'</option>
							<option value="A3"',(($config->ParameterArray["PageSize"]=="A3")?' selected="selected"':''),'>',__("A3"),'</option>
							<option value="Letter"',(($config->ParameterArray["PageSize"]=="Letter")?' selected="selected"':''),'>',__("Letter"),'</option>
							<option value="Legal"',(($config->ParameterArray["PageSize"]=="Legal")?' selected="selected"':''),'>',__("Legal"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Users"),'</h3>
			<div class="table">
				<div>
					<div><label for="ClassList">',__("Department Types"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ClassList"],'" name="ClassList" value="',$classlist,'"></div>
				</div>
				<div>
					<div><label for="UserLookupURL">',__("User Lookup URL"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["UserLookupURL"],'" name="UserLookupURL" value="',$config->ParameterArray["UserLookupURL"],'"></div>
				</div>
				<div>
					<div><label for="RequireDefinedUser">',__("Block Undefined Users"),'</label></div>
					<div><select id="RequireDefinedUser" name="RequireDefinedUser" defaultvalue="',$config->defaults["RequireDefinedUser"],'" data="',$config->ParameterArray["RequireDefinedUser"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Rack Usage"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><label for="SpaceRed">',__("Space Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceRed"],'" name="SpaceRed" value="',$config->ParameterArray["SpaceRed"],'"></div>
					<div></div>
					<div><label for="TemperatureRed">',__("Temperature Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["TemperatureRed"],'" name="TemperatureRed" value="',$config->ParameterArray["TemperatureRed"],'"></div>
				</div>
				<div>
					<div><label for="SpaceYellow">',__("Space Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SpaceYellow"],'" name="SpaceYellow" value="',$config->ParameterArray["SpaceYellow"],'"></div>
					<div></div>
					<div><label for="TemperatureYellow">',__("Temperature Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["TemperatureYellow"],'" name="TemperatureYellow" value="',$config->ParameterArray["TemperatureYellow"],'"></div>
				</div>
				<div>
					<div><label for="WeightRed">',__("Weight Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightRed"],'" name="WeightRed" value="',$config->ParameterArray["WeightRed"],'"></div>
					<div></div>
					<div><label for="HumidityRedHigh">',__("High Humidity Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityRedHigh"],'" name="HumidityRedHigh" value="',$config->ParameterArray["HumidityRedHigh"],'"></div>
				</div>
				<div>
					<div><label for="WeightYellow">',__("Weight Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["WeightYellow"],'" name="WeightYellow" value="',$config->ParameterArray["WeightYellow"],'"></div>
					<div></div>
					<div><label for="HumidityRedLow">',__("Low Humidity Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityRedLow"],'" name="HumidityRedLow" value="',$config->ParameterArray["HumidityRedLow"],'"></div>
				</div>
				<div>
					<div><label for="PowerRed">',__("Power Critical"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerRed"],'" name="PowerRed" value="',$config->ParameterArray["PowerRed"],'"></div>
					<div></div>
					<div><label for="HumidityYellowHigh">',__("High Humidity Caution"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityYellowHigh"],'" name="HumidityYellowHigh" value="',$config->ParameterArray["HumidityYellowHigh"],'"></div>
				</div>
				<div>
					<div><label for="PowerYellow">',__("Power Warning"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PowerYellow"],'" name="PowerYellow" value="',$config->ParameterArray["PowerYellow"],'"></div>
					<div></div>
					<div><label for="HumidityYellowLow">',__("Low Humidity Caution"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["HumidityYellowLow"],'" name="HumidityYellowLow" value="',$config->ParameterArray["HumidityYellowLow"],'"></div>
				</div>
				<div>
					<div><label for="RCIHigh">',__("RCI (Rack Cooling Index) High"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RCIHigh"],'" name="RCIHigh" value="',$config->ParameterArray["RCIHigh"],'"></div>
					<div></div>
					<div><label for="RCILow">',__("RCI (Rack Cooling Index) Low"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RCILow"],'" name="RCILow" value="',$config->ParameterArray["RCILow"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Expirations"),'</h3>
			<div class="table" id="rackusage">
				<div>
					<div><label for="VMExpirationTime">',__("Unseen Virtual Machines (Days)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["VMExpirationTime"],'" name="VMExpirationTime" value="',$config->ParameterArray["VMExpirationTime"],'"></div>
					<div></div>
					<div><label for="ReservationExpiration">',__("Uninstalled Reservations (Days)"),'</label></div>
					<div><input type="text" defaultValue="',$config->defaults["ReservationExpiration"],'" name="ReservationExpiration" value="',$config->ParameterArray["ReservationExpiration"],'"></div>
				</div>
			</div> <!-- end table -->
			',$tzmenu,'
		</div>
		<div id="workflow">
			<div class="table">
				<div>
					<div><label for="WorkOrderBuilder">',__("Work Order Builder"),'</label></div>
					<div><select id="WorkOrderBuilder" name="WorkOrderBuilder" defaultvalue="',$config->defaults["WorkOrderBuilder"],'" data="',$config->ParameterArray["WorkOrderBuilder"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Site Level Security Options"),'</h3>
			<div class="table">
				<div>
					<div><label for="FilterCabinetList">',__("Filter Cabinet List"),'</label></div>
					<div><select id="FilterCabinetList" name="FilterCabinetList" defaultvalue="',$config->defaults["FilterCabinetList"],'" data="',$config->ParameterArray["FilterCabinetList"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->			
			<h3>',__("Rack Requests"),'</h3>
			<div class="table">
				<div>
					<div><label for="RackRequests">',__("Rack Requests"),'</label></div>
					<div><select id="RackRequests" name="RackRequests" defaultvalue="',$config->defaults["RackRequests"],'" data="',$config->ParameterArray["RackRequests"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="RackRequestsActions">',__("Rack Requests Actions"),'</label></div>
					<div><select id="RackRequestsActions" name="RackRequestsActions" defaultvalue="',$config->defaults["RackRequestsActions"],'" data="',$config->ParameterArray["RackRequestsActions"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="MailSubject">',__("Mail Subject"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailSubject"],'" name="MailSubject" value="',$config->ParameterArray["MailSubject"],'"></div>
				</div>
				<div>
					<div><label for="RackWarningHours">',__("Warning (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackWarningHours"],'" name="RackWarningHours" value="',$config->ParameterArray["RackWarningHours"],'"></div>
				</div>
				<div>
					<div><label for="RackOverdueHours">',__("Critical (Hours)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["RackOverdueHours"],'" name="RackOverdueHours" value="',$config->ParameterArray["RackOverdueHours"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Online Repository"),'</h3>
			<h5>',__("UserID and Key are not needed to pull from the repository, only to send."),'</h5>
			<div class="table" id="repository">
				<div>
					<div><label for="APIUserID">',__("API UserID"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["APIUserID"],'" name="APIUserID" value="',$config->ParameterArray["APIUserID"],'"></div>
				</div>
				<div>
					<div><label for="APIKey">',__("API Key"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["APIKey"],'" name="APIKey" value="',$config->ParameterArray["APIKey"],'"></div>
				</div>
			</div>
		</div>
		<div id="style">
			<h3>',__("Racks & Maps"),'</h3>
			<div class="table">
				<div>
					<div><label for="CriticalColor">',__("Critical Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CriticalColor" value="',$config->ParameterArray["CriticalColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["CriticalColor"]),'</span></div>
				</div>
				<div>
					<div><label for="CautionColor">',__("Caution Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="CautionColor" value="',$config->ParameterArray["CautionColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["CautionColor"]),'</span></div>
				</div>
				<div>
					<div><label for="GoodColor">',__("Good Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="GoodColor" value="',$config->ParameterArray["GoodColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["GoodColor"]),'</span></div>
				</div>
				<div>
					<div>&nbsp;</div>
					<div></div>
					<div></div>
					<div></div>
				</div>
				<div>
					<div><label for="FreeSpaceColor">',__("Unused Spaces"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="FreeSpaceColor" value="',$config->ParameterArray["FreeSpaceColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["FreeSpaceColor"]),'</span></div>
				</div>
				<div>
				   <div>',__("Default U1 Position"),'</div>
				   <div><select id="U1Position" name="U1Position" defaultvalue="',$config->defaults["U1Position"],'" data="',$config->ParameterArray["U1Position"],'">
							<option value="Bottom">',__("Bottom"),'</option>
							<option value="Top">',__("Top"),'</option>
				   </select></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Devices"),'</h3>
			<div class="table">
				<div>
					<div><label for="LabelCase">',__("Device Labels"),'</label></div>
					<div><select id="LabelCase" name="LabelCase" defaultvalue="',$config->defaults["LabelCase"],'" data="',$config->ParameterArray["LabelCase"],'">
							<option value="upper">',transform(__("Uppercase"),'upper'),'</option>
							<option value="lower">',transform(__("Lowercase"),'lower'),'</option>
							<option value="initial">',transform(__("Initial caps"),'initial'),'</option>
							<option value="none">',__("Don't touch my labels"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="AppendCabDC">',__("Device Lists"),'</label></div>
					<div><select id="AppendCabDC" name="AppendCabDC" defaultvalue="',$config->defaults["AppendCabDC"],'" data="',$config->ParameterArray["AppendCabDC"],'">
							<option value="disabled">',__("Just Devices"),'</option>
							<option value="enabled">',__("Show Datacenter and Cabinet"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Cabinets"),'</h3>
			<div class="table">
				<div>
					<div><label for="OutlineCabinets">',__("Draw Cabinet Outlines"),'</label></div>
					<div><select id="OutlineCabinets" name="OutlineCabinets" defaultvalue="',$config->defaults["OutlineCabinets"],'" data="',$config->ParameterArray["OutlineCabinets"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="LabelCabinets">',__("Add Cabinet Labels"),'</label></div>
					<div><select id="LabelCabinets" name="LabelCabinets" defaultvalue="',$config->defaults["LabelCabinets"],'" data="',$config->ParameterArray["LabelCabinets"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
                                 <div>
					<div><label for="AssignCabinetLabels">',__("Which Cabinet Label?"),'</label></div>
					<div><select id="AssignCabinetLabels" name="AssignCabinetLabels" defaultvalue="',$config->defaults["AssignCabinetLabels"],'" data="',$config->ParameterArray["AssignCabinetLabels"],'">

							<option value="Location">',__("Location"),'</option>
							<option value="OwnerName">',__("Owner Name"),'</option>
							<option value="KeyLockInformation">',__("Key Lock Information"),'</option>
							<option value="ModelNo">',__("Model No"),'</option> 
						</select>
					</div>
				</div>
				
			</div> <!-- end table -->
			<h3>',__("Site"),'</h3>
			<div class="table">
				<div>
					<div><label for="HeaderColor">',__("Header Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="HeaderColor" value="',$config->ParameterArray["HeaderColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["HeaderColor"]),'</span></div>
				</div>
				<div>
					<div><label for="BodyColor">',__("Body Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="BodyColor" value="',$config->ParameterArray["BodyColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["BodyColor"]),'</span></div>
				</div>
				<div>
					<div><label for="LinkColor">',__("Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="LinkColor" value="',$config->ParameterArray["LinkColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["LinkColor"]),'</span></div>
				</div>
				<div>
					<div><label for="VisitedLinkColor">',__("Viewed Link Color"),'</label></div>
					<div><div class="cp"><input type="text" class="color-picker" name="VisitedLinkColor" value="',$config->ParameterArray["VisitedLinkColor"],'"></div></div>
					<div><button type="button">&lt;--</button></div>
					<div><span>',strtoupper($config->defaults["VisitedLinkColor"]),'</span></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="email">
		    <fieldset id="smtpblock">
			<div class="table">
				<div>
					<div><label for="SMTPServer">',__("SMTP Server"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPServer"],'" name="SMTPServer" value="',$config->ParameterArray["SMTPServer"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPort">',__("SMTP Port"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPort"],'" name="SMTPPort" value="',$config->ParameterArray["SMTPPort"],'"></div>
				</div>
				<div>
					<div><label for="SMTPHelo">',__("SMTP Helo"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPHelo"],'" name="SMTPHelo" value="',$config->ParameterArray["SMTPHelo"],'"></div>
				</div>
				<div>
					<div><label for="SMTPUser">',__("SMTP Username"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPUser"],'" name="SMTPUser" value="',$config->ParameterArray["SMTPUser"],'"></div>
				</div>
				<div>
					<div><label for="SMTPPassword">',__("SMTP Password"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["SMTPPassword"],'" name="SMTPPassword" value="',$config->ParameterArray["SMTPPassword"],'"></div>
				</div>
				<div>
					<div><label for="MailToAddr">',__("Mail To"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailToAddr"],'" name="MailToAddr" value="',$config->ParameterArray["MailToAddr"],'"></div>
				</div>
				<div>
					<div><label for="MailFromAddr">',__("Mail From"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["MailFromAddr"],'" name="MailFromAddr" value="',$config->ParameterArray["MailFromAddr"],'"></div>
				</div>
				<div>
					<div><label for="ComputerFacMgr">',__("Facility Manager"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["ComputerFacMgr"],'" name="ComputerFacMgr" value="',$config->ParameterArray["ComputerFacMgr"],'"></div>
				</div>
				<div>
					<div><label for="FacMgrMail">',__("Facility Manager Email"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["FacMgrMail"],'" name="FacMgrMail" value="',$config->ParameterArray["FacMgrMail"],'"></div>
				</div>
				<div>
					<div></div>
					<div><button type="button" id="btn_smtptest" style="display; inline-block">',__("Test Settings"),'</button></div>
				</div>
				<div id="smtptest" title="Testing SMTP Communications"><div></div></div>
			</div> <!-- end table -->
			</fieldset>
			<div class="table">
				<div>
					<div><label for="PowerAlertsEmail">',__("Email Alerts on Power Poll"),'</label></div>
					<div><select id="PowerAlertsEmail" name="PowerAlertsEmail" defaultvalue="',$config->defaults["PowerAlertsEmail"],'" data="',$config->ParameterArray["PowerAlertsEmail"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="SensorAlertsEmail">',__("Email Alerts on Sensor Poll"),'</label></div>
					<div><select id="SensorAlertsEmail" name="SensorAlertsEmail" defaultvalue="',$config->defaults["SensorAlertsEmail"],'" data="',$config->ParameterArray["SensorAlertsEmail"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div id="reporting">
			<div id="imageselection" title="Image file selector">
				',$imageselect,'
			</div>
			<div class="table">
				<div>
					<div><label for="annualCostPerUYear">',__("Annual Cost Per Rack Unit (Year)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["annualCostPerUYear"],'" name="annualCostPerUYear" value="',$config->ParameterArray["annualCostPerUYear"],'"></div>
				</div>
				<div>
					<div><label for="CostPerKwHr">',__("Cost Per KwHr"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["CostPerKwHr"],'" name="CostPerKwHr" value="',$config->ParameterArray["CostPerKwHr"],'"></div>
				</div>
				<div>
					<div><label for="PDFLogoFile">',__("Logo file for headers"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFLogoFile"],'" name="PDFLogoFile" value="',$config->ParameterArray["PDFLogoFile"],'"></div>
				</div>
				<div>
					<div><label for="PDFfont">',__("Font"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["PDFfont"],'" name="PDFfont" value="',$config->ParameterArray["PDFfont"],'" title="examples: courier, DejaVuSans, helvetica, OpenSans-Bold, OpenSans-Cond, times"></div>
				</div>
				<div>
					<div><label for="NewInstallsPeriod">',__("New Installs Period"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["NewInstallsPeriod"],'" name="NewInstallsPeriod" value="',$config->ParameterArray["NewInstallsPeriod"],'"></div>
				</div>
				<div>
					<div><label for="InstallURL">',__("Base URL for install"),'</label></div>
					<div><input type="text" defaultvalue="',$href,'" name="InstallURL" value="',$config->ParameterArray["InstallURL"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("SNMP Options"),'</h3>
			<div class="table">
				<div>
					<div><label for="SNMPCommunity">',__("Default SNMP Community"),'</label></div>
					<div><input type="password" defaultvalue="',$config->defaults["SNMPCommunity"],'" name="SNMPCommunity" value="',$config->ParameterArray["SNMPCommunity"],'"></div>
				</div>
				<div>
				  <div><label for="SNMPVersion">'.__("SNMP Version").'</label></div>
				  <div>
						<select id="SNMPVersion" defaultvalue="',$config->defaults["SNMPVersion"],'" name="SNMPVersion" data="',$config->ParameterArray["SNMPVersion"],'">
							<option value="1">1</option>
							<option value="2c">2c</option>
							<option value="3">3</option>
						</select>
					</div>
				</div>
				<div>
				  <div><label for="v3SecurityLevel">'.__("SNMPv3 Security Level").'</label></div>
				  <div>
					<select id="v3SecurityLevel" defaultvalue="',$config->defaults["v3SecurityLevel"],'" name="v3SecurityLevel" data="',$config->ParameterArray["v3SecurityLevel"],'">
						<option value="noAuthNoPriv">noAuthNoPriv</option>
						<option value="authNoPriv">authNoPriv</option>
						<option value="authPriv">authPriv</option>
					</select>
				  </div>
				</div>
				<div>
				  <div><label for="v3AuthProtocol">'.__("SNMPv3 AuthProtocol").'</label></div>
					<div>
						<select id="v3AuthProtocol" defaultvalue="',$config->defaults["v3AuthProtocol"],'" name="v3AuthProtocol" data="',$config->ParameterArray["v3AuthProtocol"],'">
							<option value="MD5">MD5</option>
							<option value="SHA">SHA</option>
						</select>
					</div>
				</div>
				<div>
				  <div><label for="v3AuthPassphrase">'.__("SNMPv3 Passphrase").'</label></div>
				  <div><input type="password" defaultvalue="',$config->defaults["v3AuthPassphrase"],'" name="v3AuthPassphrase" id="v3AuthPassphrase" value="',$config->ParameterArray["v3AuthPassphrase"],'"></div>
				</div>
				<div>
				  <div><label for="v3PrivProtocol">'.__("SNMPv3 PrivProtocol").'</label></div>
				  <div>
					<select id="v3PrivProtocol" defaultvalue="',$config->defaults["v3PrivProtocol"],'" name="v3PrivProtocol" data="',$config->ParameterArray["v3PrivProtocol"],'">
						<option value="DES">DES</option>
						<option value="AES">AES</option>
					</select>
				  </div>
				</div>
				<div>
				  <div><label for="v3PrivPassphrase">'.__("SNMPv3 PrivPassphrase").'</label></div>
				  <div><input type="password" defaultvalue="',$config->defaults["v3PrivPassphrase"],'" name="v3PrivPassphrase" id="v3PrivPassphrase" value="',$config->ParameterArray["v3PrivPassphrase"],'"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Capacity Reporting"),'</h3>
			<div class="table">
				<div>
					<div><label for="NetworkCapacityReportOptIn">',__("Switches"),'</label></div>
					<div>
						<select id="NetworkCapacityReportOptIn" defaultvalue="',$config->defaults["NetworkCapacityReportOptIn"],'" name="NetworkCapacityReportOptIn" data="',$config->ParameterArray["NetworkCapacityReportOptIn"],'">
							<option value="OptIn">',__("OptIn"),'</option>
							<option value="OptOut">',__("OptOut"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="NetworkThreshold">',__("Switch Capacity Threshold"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["NetworkThreshold"],'" name="NetworkThreshold" value="',$config->ParameterArray["NetworkThreshold"],'"></div>
				</div>
			</div>
			<h3>',__("Utilities"),'</h3>
			<div class="table">
				<div>
					<div><label for="snmpget">',__("snmpget"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["snmpget"],'" name="snmpget" value="',$config->ParameterArray["snmpget"],'"></div>
				</div>
				<div>
					<div><label for="snmpwalk">',__("snmpwalk"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["snmpwalk"],'" name="snmpwalk" value="',$config->ParameterArray["snmpwalk"],'"></div>
				</div>
				<div>
					<div><label for="cut">',__("cut"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["cut"],'" name="cut" value="',$config->ParameterArray["cut"],'"></div>
				</div>
				<div>
					<div><label for="dot">',__("dot"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["dot"],'" name="dot" value="',$config->ParameterArray["dot"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="tt">
			<div class="table">
				<div>
					<div><label for="ToolTips">',__("Cabinet ToolTips"),'</label></div>
					<div><select id="ToolTips" name="ToolTips" defaultvalue="',$config->defaults["ToolTips"],'" data="',$config->ParameterArray["ToolTips"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<br>
			',$tooltip,'
			<br>
			<div class="table">
				<div>
					<div><label for="CDUToolTips">',__("CDU ToolTips"),'</label></div>
					<div><select id="CDUToolTips" name="CDUToolTips" defaultvalue="',$config->defaults["CDUToolTips"],'" data="',$config->ParameterArray["CDUToolTips"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div> <!-- end table -->
			<br>
			',$cdutooltip,'
		</div>
		<div id="cc">
			<h3>',__("Media Types"),'</h3>
			<div class="table">
				<div>
					<!-- <div><label for="MediaEnforce">',__("Media Type Matching"),'</label></div>
					<div><select id="MediaEnforce" name="MediaEnforce" defaultvalue="',$config->defaults["MediaEnforce"],'" data="',$config->ParameterArray["MediaEnforce"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enforce"),'</option>
						</select>
					</div> -->
					<input type="hidden" name="MediaEnforce" value="disabled">
				</div>
			</div> <!-- end table -->
			<br>
			<div class="table" id="mediatypes">
				<div>
					<div></div>
					<div>',__("Media Type"),'</div>
					<div>',__("Default Color"),'</div>
				</div>
				',$mediatypes,'
				<div>
					<div id="newline"><img title="',__("Add new row"),'" src="images/add.gif"></div>
					<div><input type="text" name="mediatype[]"></div>
					<div>',$colorselector,'</div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Cable Colors"),'</h3>
			<div class="table" id="cablecolor">
				<div>
					<div></div>
					<div>',__("Color"),'</div>
					<div>',__("Default Note"),'</div>
				</div>
				',$cablecolors,'
				<div>
					<div id="newline"><img title="',__("Add new row"),'" src="images/add.gif"></div>
					<div><input type="text" name="colorcode[]"></div>
					<div><input type="text" name="ccdefaulttext[]"></div>
				</div>
			</div> <!-- end table -->
			<h3>',__("Connection Filtering"),'</h3>
			<div class="table" id="connectionfiltering">
				<div>
					<div><label for="PatchPanelsOnly">',__("Patch panel rear connection filtering"),'</label></div>
					<div><select id="PatchPanelsOnly" name="PatchPanelsOnly" defaultvalue="',$config->defaults["PatchPanelsOnly"],'" data="',$config->ParameterArray["PatchPanelsOnly"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enforce"),'</option>
						</select>
					</div>
				</div>
			</div>
			<h3>',__("Connection Pathing"),'</h3>
			<div class="table" id="pathweights">
				<div>
					<div><label for="path_weight_cabinet">',__("Cabinet Weight"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["path_weight_cabinet"],'" name="path_weight_cabinet" value="',$config->ParameterArray["path_weight_cabinet"],'"></div>
				</div>
				<div>
					<div><label for="path_weight_rear">',__("Weight for rear connections between panels"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["path_weight_rear"],'" name="path_weight_rear" value="',$config->ParameterArray["path_weight_rear"],'"></div>
				</div>
				<div>
					<div><label for="path_weight_row">',__("Weight for patches in the same row"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["path_weight_row"],'" name="path_weight_row" value="',$config->ParameterArray["path_weight_row"],'"></div>
				</div>
			</div> <!-- end table -->
		</div>
		<div id="dca">
			<h3>',__("Custom Device Attributes"),'</h3>
			<div class="table" id="customattrs">
				<div>
					<div></div>
					<div class="customattrsheader">',__("Label"),'</div>
					<div class="customattrsheader">',__("Type"),'</div>
					<div class="customattrsheader">',__("Required"),'</div>
					<div class="customattrsheader">',__("Apply to<br>All Devices"),'</div>
					<div class="customattrsheader">',__("Default Value"),'</div>
				</div>
				',$customattrs,'
				<div>
					<div id="newline"><img title="',__("Add new row"),'" src="images/add.gif"></div>
					<div><input type="text" name="dcalabel[]" class="validate[optional,custom[onlyLetterNumberConfigurationPage]]"></div>
					<div>',$dcaTypeSelector,'</div>
					<div><input type="checkbox" name="dcarequired[]"></div>
					<div><input type="checkbox" name="dcaalldevices[]"></div>
					<div><input type="text" name="dcavalue[]"></div>
				</div>
			</div>
			<h3>',__("Device Status"),'</h3>
			<div class="table" id="devstatus">
				<div>
					<div></div>
					<div>Status</div>
					<div>Color</div>
				</div>
				',$devstatusList,'
				<div>
					<div class="newstatus"><img title="',__("Add new row"),'" src="images/add.gif"></div>
					<div class="newstatus"><input type="text" name="devstatus[]" class="validate[optional,custom[onlyLetterNumberSpacesConfigurationPage]]"></div>
				</div>
			</div>
		</div>
		<div id="mappers">
			<h3>',__("LDAP or SAML Attribute Mapping"),'</h3>
			<div class="table">
				<div>
					<div><label for="AttrFirstName">',__("FirstName"),'</label></div>
					<div><input type="text" size="40" defaultvalue="',$config->defaults["AttrFirstName"],'" name="AttrFirstName" value="',$config->ParameterArray["AttrFirstName"],'"></div>
				</div>
				<div>
					<div><label for="AttrLastName">',__("Last Name"),'</label></div>
					<div><input type="text" size="40" defaultvalue="',$config->defaults["AttrLastName"],'" name="AttrLastName" value="',$config->ParameterArray["AttrLastName"],'"></div>
				</div>
				<div>
					<div><label for="AttrEmail">',__("Email"),'</label></div>
					<div><input type="text" size="40" defaultvalue="',$config->defaults["AttrEmail"],'" name="AttrEmail" value="',$config->ParameterArray["AttrEmail"],'"></div>
				</div>
				<div>
					<div><label for="AttrPhone1">',__("Phone1"),'</label></div>
					<div><input type="text" size="40" defaultvalue="',$config->defaults["AttrPhone1"],'" name="AttrPhone1" value="',$config->ParameterArray["AttrPhone1"],'"></div>
				</div>
				<div>
					<div><label for="AttrPhone2">',__("Phone2"),'</label></div>
					<div><input type="text" size="40" defaultvalue="',$config->defaults["AttrPhone2"],'" name="AttrPhone2" value="',$config->ParameterArray["AttrPhone2"],'"></div>
				</div>
				<div>
					<div><label for="AttrPhone3">',__("Phone3"),'</label></div>
					<div><input type="text" size="40" defaultvalue="',$config->defaults["AttrPhone3"],'" name="AttrPhone3" value="',$config->ParameterArray["AttrPhone3"],'"></div>
				</div>
			</div>
			<h3>',__("Group Mapping"),'</h3>
			<div class="table">
				<div>
					<div><label for="SAMLGroupAttribute">',__("SAML Attribute containing Groups"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLGroupAttribute"],'" name="SAMLGroupAttribute" value="',$config->ParameterArray["SAMLGroupAttribute"],'"></div>
				</div>
				<div>
					<div><label for="LDAPSiteAccess">',__("Site Access"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPSiteAccess"],'" name="LDAPSiteAccess" value="',$config->ParameterArray["LDAPSiteAccess"],'"></div>
				</div>
				<div>
					<div><label for="LDAPReadAccess">',__("Global Read"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPReadAccess"],'" name="LDAPReadAccess" value="',$config->ParameterArray["LDAPReadAccess"],'"></div>
				</div>
				<div>
					<div><label for="LDAPWriteAccess">',__("Global Write"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPWriteAccess"],'" name="LDAPWriteAccess" value="',$config->ParameterArray["LDAPWriteAccess"],'"></div>
				</div>
				<div>
					<div><label for="LDAPDeleteAccess">',__("Global Delete"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPDeleteAccess"],'" name="LDAPDeleteAccess" value="',$config->ParameterArray["LDAPDeleteAccess"],'"></div>
				</div>
				<div>
					<div><label for="LDAPAdminOwnDevices">',__("Admin Owned Devices"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPAdminOwnDevices"],'" name="LDAPAdminOwnDevices" value="',$config->ParameterArray["LDAPAdminOwnDevices"],'"></div>
				</div>
				<div>
					<div><label for="LDAPRackRequest">',__("Enter Rack Request"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPRackRequest"],'" name="LDAPRackRequest" value="',$config->ParameterArray["LDAPRackRequest"],'"></div>
				</div>
				<div>
					<div><label for="LDAPRackAdmin">',__("Complete Rack Request"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPRackAdmin"],'" name="LDAPRackAdmin" value="',$config->ParameterArray["LDAPRackAdmin"],'"></div>
				</div>
				<div>
					<div><label for="LDAPContactAdmin">',__("Contact Admin"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPContactAdmin"],'" name="LDAPContactAdmin" value="',$config->ParameterArray["LDAPContactAdmin"],'"></div>
				</div>
				<div>
					<div><label for="LDAPBulkOperations">',__("Bulk Operations"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPBulkOperations"],'" name="LDAPBulkOperations" value="',$config->ParameterArray["LDAPBulkOperations"],'"></div>
				</div>
				<div>
					<div><label for="LDAPSiteAdmin">',__("Site Admin"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["LDAPSiteAdmin"],'" name="LDAPSiteAdmin" value="',$config->ParameterArray["LDAPSiteAdmin"],'"></div>
				</div>
			</div>
		</div>
		<div id="ldap">
			<h3>',__("LDAP Authentication and Authorization Configuration"),'</h3>
			<div class="table">
				<div>
					<div><label for="LDAPServer">',__("LDAP Server URI"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["LDAPServer"],'" name="LDAPServer" value="',$config->ParameterArray["LDAPServer"],'"></div>
				</div>
				<div>
					<div><label for="LDAPBaseDN">',__("Base DN"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["LDAPBaseDN"],'" name="LDAPBaseDN" value="',$config->ParameterArray["LDAPBaseDN"],'"></div>
				</div>
				<div>
					<div><label for="LDAPBaseSearch">',__("Base Search"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["LDAPBaseSearch"],'" name="LDAPBaseSearch" value="',$config->ParameterArray["LDAPBaseSearch"],'" title="',__("Leave blank for Active Directory"),'"></div>
				</div>
				<div>
					<div><label for="LDAPBindDN">',__("Bind DN"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["LDAPBindDN"],'" name="LDAPBindDN" value="',$config->ParameterArray["LDAPBindDN"],'" title="%userid%@opendcim.org for Active Directory"></div>
				</div>
				<div>
					<div><label for="LDAPUserSearch">',__("User Search"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["LDAPUserSearch"],'" name="LDAPUserSearch" value="',$config->ParameterArray["LDAPUserSearch"],'"></div>
				</div>
				<div>
					<div><label for="LDAPSessionExpiration">',__("LDAP Session Expiration (Seconds)"),'</label></div>
					<div><input type="text" defaultvalue="',$config->defaults["LDAPSessionExpiration"],'" name="LDAPSessionExpiration" value="',$config->ParameterArray["LDAPSessionExpiration"],'"></div>
				</div>
				<div>
					<div><label for="LDAPDebug">',__("LDAP Debugging"),'</label></div>
					<div><select id="LDAPDebug" name="LDAPDebug" defaultValue="',$config->defaults["LDAPDebug"],'" data="', $config->ParameterArray["LDAPDebug"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
				<div>
					<div><label for="LDAP_Debug_Password">',__("Emergency Bypass Password"),'</label></div>
					<div><input type="password" defaultvalue="',$config->defaults["LDAP_Debug_Password"],'" name="LDAP_Debug_Password" value="',$config->ParameterArray["LDAP_Debug_Password"],'"></div>
				</div>
			</div>
		</div>
			<div id="saml">
			<h3>',__("SAML Authentication Configuration"),'</h3>
			<div class="table">
				<div>
					<div><label for="SAMLBaseURL">',__("Base URL"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLBaseURL"],'" name="SAMLBaseURL" value="',$config->ParameterArray["SAMLBaseURL"],'"></div>
				</div>
				<div>
					<div><label for="SAMLShowSuccessPage">',__("Show Success Page"),'</label></div>
					<div><select id="SAMLShowSuccessPage" name="SAMLShowSuccessPage" defaultValue="',$config->defaults["SAMLShowSuccessPage"],'" data="', $config->ParameterArray["SAMLShowSuccessPage"],'">
							<option value="disabled">',__("Disabled"),'</option>
							<option value="enabled">',__("Enabled"),'</option>
						</select>
					</div>
				</div>
			</div>
			<h3>',__("SAML SP Information and Certificate Generation"),'</h3>
			<fieldset id="spcertinfo">
			<div class="table">
				<div>
					<div><label for="SAMLspentityId">',__("Entity ID"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLspentityId"],'" name="SAMLspentityId" value="',$config->ParameterArray["SAMLspentityId"],'"></div>
				</div>
				<div>
					<div><label for="SAMLCertCountry">',__("Country (2 Character)"),'</label></div>
					<div><input type="text" size="3" defaultvalue="',$config->defaults["SAMLCertCountry"],'" name="SAMLCertCountry" value="',$config->ParameterArray["SAMLCertCountry"],'"></div>
				</div>
				<div>
					<div><label for="SAMLCertProvince">',__("State or Province"),'</label></div>
					<div><input type="text" size="3" defaultvalue="',$config->defaults["SAMLCertProvince"],'" name="SAMLCertProvince" value="',$config->ParameterArray["SAMLCertProvince"],'"></div>
				</div>
				<div>
					<div><label for="SAMLCertOrganization">',__("Organization"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLCertOrganization"],'" name="SAMLCertOrganization" value="',$config->ParameterArray["SAMLCertOrganization"],'"></div>
				</div>';
 			if ( $config->ParameterArray["SAMLspx509cert"] != "" ) {
 				$data = openssl_x509_parse(str_replace(array("&#13;", "&#10;"), array(chr(13), chr(10)), $config->ParameterArray["SAMLspx509cert"]));

				$validFrom = date('Y-m-d H:i:s', $data['validFrom_time_t']);
				$validTo = date('Y-m-d H:i:s', $data['validTo_time_t']);
			} else {
				$validTo = "No Certificate";
			}	

			echo '<div>
					<div>',__("Certificate Expiration"),'</div>
					<div><input type="text" size="20" id="SPCertExpiration" name="SPCertExpiration" readonly value="',$validTo,'"></div>
				</div>
				<div>
					<div>',__("SP Private Key"),'</div>
					<div><textarea cols="60" rows="10" id="SAMLspprivateKey" name="SAMLspprivateKey">', $config->ParameterArray["SAMLspprivateKey"], '</textarea></div>
				</div>
				<div>
					<div>',__("SP x509 Certificate"),'</div>
					<div><textarea cols="60" rows="10" id="SAMLspx509cert" name="SAMLspx509cert">', $config->ParameterArray["SAMLspx509cert"], '</textarea></div>
				</div>
 				<div>
 					<div><label for="SAMLGenNewCert"></label></div>
					<div><div><button type="button" id="btn_spcert" style="display; inline-block">',__("Generate/Renew Certificate"),'</button></div>
					</div>
				</div>
			</div>
			</fieldset>
			<h3>',__("SAML Identity Provider Configuration"),'</h3>
			<fieldset id="idpinfo">
			<div class="table">
				<div>
					<div><label for="SAMLidpentityId">',__("IdP entityId"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLidpentityId"],'" name="SAMLidpentityId" value="',$config->ParameterArray["SAMLidpentityId"],'"></div>
				</div>
				<div>
					<div><label for="SAMLIdPMetadataURL">',__("IdP Metadata URL"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLIdPMetadataURL"],'" name="SAMLIdPMetadataURL" value="',$config->ParameterArray["SAMLIdPMetadataURL"],'"></div>
				</div>
				<div>
					<div><label for="SAMLidpssoURL">',__("SSO URL"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLidpssoURL"],'" name="SAMLidpssoURL" value="',$config->ParameterArray["SAMLidpssoURL"],'"></div>
				</div>
				<div>
					<div><label for="SAMLidpslsURL">',__("SLS URL"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLidpslsURL"],'" name="SAMLidpslsURL" value="',$config->ParameterArray["SAMLidpslsURL"],'"></div>
				</div>
				<div>
					<div>',__("IdP x509 Certificate"),'</div>
					<div><textarea cols="60" rows="10" id="SAMLidpx509cert" name="SAMLidpx509cert">', $config->ParameterArray["SAMLidpx509cert"], '</textarea></div>
				</div>
				<div>
					<div><label for="SAMLRefreshIdPMetadata"></label></div>
					<div><div><button type="button" id="btn_refreshidpmetadata" style="display; inline-block">',__("Refresh IdP Metadata"),'</button></div>
					</div>
				</div>
			</div>
			</fieldset>
			<h3>',__("SAML Account Configuration"),'</h3>
			<div class="table">
				<div>
					<div><label for="SAMLaccountPrefix">',__("Remove Account Prefix"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLaccountPrefix"],'" name="SAMLaccountPrefix" value="',$config->ParameterArray["SAMLaccountPrefix"],'"></div>
				</div>
				<div>
					<div><label for="SAMLaccountSuffix">',__("Remove Account Suffix"),'</label></div>
					<div><input type="text" size="60" defaultvalue="',$config->defaults["SAMLaccountSuffix"],'" name="SAMLaccountSuffix" value="',$config->ParameterArray["SAMLaccountSuffix"],'"></div>
				</div>
			</div>
		</div>
		<div id="preflight">
			<iframe src="preflight.inc.php"></iframe>
		</div><!-- end preflight tab -->
	</div>';

?>

<div class="table centermargin">
<div>
	<div>&nbsp;</div>
</div>
<div>
   <?php echo '<button type="submit" name="action" value="Update">',__("Update"),'</button></div>'; ?>
</div>
</div> <!-- END div.table -->
</form>
</div>
   <?php echo '<a href="index.php">[ ',__("Return to Main Menu"),' ]</a><span class="hide"><!-- hiding these two phrases here to make sure they get translated for use in the asset tracking status fields -->',__("Reserved"),'',__("Disposed"),'</span>'; ?>
</div>
  </div>
  </div>
</body>
</html>
