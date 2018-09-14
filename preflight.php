<?php
	if(!isset($_GET['preflight-ok'])){
?>
<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">

  <style type="text/css">
	#header{
		padding:5px 0;
		height:66px;
		position: relative;
	}
	#header > span {color: white;display: block;margin-top: 5px;text-align: center;
		text-shadow: 1px 1px 0 #063, 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000;
	}
	#header1 {font-size: xx-large;}
	#header2 {font-size: x-large;}
	#header > #version {bottom: 2px;position: absolute;right: 4px;font-size:small;
		text-shadow: 1px 1px 0 #063, 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000;
	}
  </style>

</head>
<body>
<div id="white" style="display: none;"></div>
<div id="header">
	<span id="header1">openDCIM Computer Facilities</span>
	<span id="header2">Pre-installation environment check</span>
	<span id="version"></span>
</div>
<div class="page index">
	<div class="main" id="colorcheck" style="display: none"></div>
	<div id="preflight">
		<iframe src="preflight.inc.php"></iframe>
	</div>
</div><!-- END div.page -->
<!-- silly js to detect if db.inc.php loaded correctly and if not to add some flair -->
<script type="text/javascript">
	ito=setInterval(function() {
		var preflight=document.getElementsByTagName("iframe");
		if ( preflight[0].contentDocument.readyState == 'complete' ) {
			preflight[0].style.width='100%';
			preflight[0].style.height=preflight[0].contentWindow.document.body.offsetHeight + 50 + "px";
			clearTimeout(ito);
			if(window.getComputedStyle(document.getElementById("header"), null).getPropertyValue("background-color") == window.getComputedStyle(document.getElementById("white"), null).getPropertyValue("background-color")){
				bgcolor="#F0E0B2";
			}else{
				var bgcolor = window.getComputedStyle(document.getElementById("colorcheck"), null).getPropertyValue("background-color");
			}
			var style = document.createElement('style');
			style.textContent =
				'body {' +
				'  background-color: '+bgcolor+';'+
				'  text-align: center;'+
				'}'+ 
				'table {' +
				'  background-color: #ffffff;'+
				'  margin: 0 auto;'+
				'}' 
			;
			preflight[0].contentDocument.head.appendChild(style);
		}
	}, 100);
	pto=setInterval(function() {
		var preflight=document.getElementsByTagName("iframe");
		if ( document.readyState == 'complete' ) {
			if(window.getComputedStyle(document.getElementById("header"), null).getPropertyValue("background-color") == window.getComputedStyle(document.getElementById("white"), null).getPropertyValue("background-color")){
				var style = document.createElement('style');
				style.textContent =
					'#header {' +
					'  background: #006633 url(../images/logo.png) no-repeat left center;'+
					'}'
				document.head.appendChild(style); 
				clearTimeout(pto);
			}
		}
	}, 100);
</script>
</body>
</html>
<?php
}
?>
