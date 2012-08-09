<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php
	require_once( "db.inc.php" );
	require_once( "facilities.inc.php" );

	$user = new User();

	$user->UserID = $_SERVER["REMOTE_USER"];
	$user->GetUserRights( $facDB );

	if(!$user->SiteAdmin){
		// No soup for you.
		header('Location: '.redirect());
		exit;
	}

	if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="Update"){
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
	if(isset($_REQUEST["Revert"]) && $_REQUEST["Revert"]=="yes"){
		$config->RevertToDefault($facDB,$_REQUEST["Single"]);
		$config=new Config($facDB);
	}
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery.miniColors.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css">
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery.miniColors.js"></script>
  <script type="text/javascript">
	function verifyRevert(formname, para) {
		if (confirm( "Do you really want to revert configuration to factory default?" )){
		document.getElementById('Revert').value='yes';
		document.getElementById('Single').value=para;
		formname.submit();
		}else{
		document.getElementById('Revert').value='no';
		formname.submit();
		}
	}
	$(document).ready( function() {
		$(".color-picker").miniColors({
			letterCase: 'uppercase',
			change: function(hex, rgb) {
				logData(hex, rgb);
			}
		});
	});

  </script>
</head>
<body>
<div id="header"></div>
<div class="page config">
<?php
	include( "sidebar.inc.php" );
?>
<div class="main">
<h2><?php print $config->ParameterArray["OrgName"]; ?></h2>
<h3>Data Center Configuration</h3>
<h3>Database Version:  <?php print $config->ParameterArray["Version"]; ?></h3>
<div class="center"><div>
<form action="<?php print $_SERVER["PHP_SELF"]; ?>" method="POST">
   <input type="hidden" name="Revert" id="Revert" value="no">
   <input type="hidden" name="Single" id="Single" value="none">
   <input type="hidden" name="Version" value="<?php print $config->ParameterArray["Version"]; ?>">
<div class="table rights">
<div><div><h3>Parameter</h3></div><div><h3>Value</h3></div><div></div><div><h3>Default Value</h3></div></div>
<?php
	foreach ($config->ParameterArray as $key=>$value){
	
		if ( strpos( $key, "Color" ) ) {
			$class='class="color-picker"';
			$cssfix1='<div class="cp">';
			$cssfix2='</div>';
		} else { 
			$cssfix1=$cssfix2=$class='';
		}
		
		if ($key =="ClassList"){
			$numItems=count($config->ParameterArray[$key]);
			$i=0;
			$valueStr="";
			foreach($config->ParameterArray[$key] as $item){
				$valueStr .= $item;
				if($i+1 != $numItems){
					$valueStr.=", ";
				}
				$i++;
			}
			print "<div>\n";
			print "<div>$key:</div>\n";
			print "<div><input type=\"text\" maxlength=\"200\" name=\"$key\" value=\"$valueStr\"></div>\n";
			print "<div><input type=\"button\" value=\"Revert To Default\" onclick=\"javascript:verifyRevert(this.form,'$key')\"></div>\n";
			print "<div>{$config->defaults[$key]}</div>\n";
			print "</div>\n";
		} elseif ( $key != "Version" ) {
			print "<div>\n";
			print "<div>$key:</div>\n";
			print "<div>$cssfix1<input type=\"text\" $class maxlength=\"200\" name=\"$key\" value=\"{$config->ParameterArray[$key]}\">$cssfix2</div>\n";
			print "<div><input type=\"button\" value=\"Revert To Default\" onclick=\"javascript:verifyRevert(this.form,'$key')\"></div>\n";
			print "<div>{$config->defaults[$key]}</div>\n";
			print "</div>\n";
		}
	}
?>
<div>
   <div></div>
   <div><input type="submit" name="action" value="Update"></div>
   <div><input type="button" value="Revert All To Default" onclick="javascript:verifyRevert(this.form, 'none')"></div>
   <div></div>
</div>
<div class="caption">
   <a href="index.php">Return to Main Menu</a>
</div>
</div> <!-- END div.table -->
</div>
</form>
</div>
</body>
</html>
