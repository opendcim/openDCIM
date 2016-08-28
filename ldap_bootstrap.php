<?php
  // Very important since we are using session variables to pass into install.php so that it knows we at least have some level of auth set up
  session_start();

  //	Uncomment these if you need/want to set a title in the header
	$header="openDCIM LDAP Setup";
  $content = "";

  if ( isset($_REQUEST['ldapserver'])) {
    $ldapConn = ldap_connect( $_REQUEST['ldapserver'] );
    if ( ! $ldapConn ) {
      $content = "<h3>Fatal error.  The LDAP server is not reachable.  Please try again later, or contact your system administrator to check the configuration.</h3>";
      error_log( "Unable to connect to LDAP Server: " . $_REQUEST['ldapserver']);
    } else {
      ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );

      $ldapDN = $_REQUEST['binddn'];
      $ldapPassword = $_REQUEST['password'];

      $ldapBind = ldap_bind( $ldapConn, $ldapDN, $ldapPassword );

      if ( ! $ldapBind ) {
        $content = "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
      } else {
        $_SESSION['userid'] = $_REQUEST['userid'];
        $_SESSION['ldapserver'] = $_REQUEST['ldapserver'];
        $_SESSION['binddn'] = $_REQUEST['binddn'];

        session_commit();
        header('Location: install.php');
        exit;
      }

      $content .= "<h3>Login failed.  Incorrect configuration.</h3>";

      }
  }

  $ldapserver = $binddn = $userid = "";
  if ( isset( $_SESSION['ldapserver'] )) {
    $ldapserver = $_SESSION['ldapserver'];
  }

  if ( isset( $_SESSION['binddn'])) {
    $binddn = $_SESSION['binddn'];
  }

  if ( isset( $_SESSION['userid'] ) ) {
    $userid = $_SESSION['userid'];
  }
?>

<!doctype html>
<html>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  
  <title>openDCIM Data Center Inventory</title>
  <link rel="stylesheet" href="css/inventory.php" type="text/css">
  <link rel="stylesheet" href="css/jquery-ui.css" type="text/css">
  <!--[if lt IE 9]>
  <link rel="stylesheet"  href="css/ie.css" type="text/css" />
  <![endif]-->
  
  <script type="text/javascript" src="scripts/jquery.min.js"></script>
  <script type="text/javascript" src="scripts/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#username").focus();
});
</script>
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<div id="sidebar">
<script type="text/javascript">

function resize(){
	// Reset widths to make shrinking screens work better
	$('#header,div.main,div.page').css('width','auto');
	// This function will run each 500ms for 2.5s to account for slow loading content
	var count=0;
	subresize();
	var longload=setInterval(function(){
		subresize();
		if(count>4){
			clearInterval(longload);
			window.resized=true;
		}
		++count;
	},500);

	function subresize(){
		// page width is calcuated different between ie, chrome, and ff
		$('#header').width(Math.floor($(window).outerWidth()-(16*3))); //16px = 1em per side padding
		var widesttab=0;
		// make all the tabs on the config page the same width
		$('#configtabs > ul ~ div').each(function(){
			widesttab=($(this).width()>widesttab)?$(this).width():widesttab;
		});
		$('#configtabs > ul ~ div').each(function(){
			$(this).width(widesttab);
		});

		if(typeof getCookie=='function' && getCookie("layout")=="Landscape"){
			// edge case where a ridiculously long device type can expand the field selector out too far
			var rdivwidth=$('div.right').outerWidth();
			$('div.right fieldset').each(function(){
				rdivwidth=($(this).outerWidth()>rdivwidth)?$(this).outerWidth():rdivwidth;
			});
			// offset for being centered
			rdivwidth=(rdivwidth>495)?(rdivwidth-495)+rdivwidth:rdivwidth;
		}else{
			rdivwidth=0;
		}

		var pnw=$('#pandn').outerWidth(),hw=$('#header').outerWidth(),maindiv=$('div.main').outerWidth(),
			sbw=$('#sidebar').outerWidth(),width,mw=$('div.left').outerWidth()+rdivwidth+20,
			main,cw=$('.main > .center').outerWidth();
		widesttab+=58;

		// find widths
		width=(cw>mw)?cw:mw;
		main=(pnw>width)?pnw:width; // Find the largest width of possible content in maindiv
		main+=12; // add in padding and borders
		width=((main+sbw)>hw)?main+sbw:hw; // find the widest point of the page

		// The math just isn't adding up across browsers and FUCK IE
		if((main+sbw)<width){ // page is larger than content expand main to fit
			$('#header').outerWidth(width);
			$('div.main').outerWidth(width-sbw-4); 
			$('div.page').outerWidth(width);
		}else{ // page is smaller than content expand the page to fit
			$('div.main').width(width-sbw-12); 
			$('#header').width(width+4);
			$('div.page').width(width+6);
		}

		// If the function MoveButtons is defined run it
		if(typeof movebuttons=='function'){
			movebuttons();
		}
	}
}
$(document).ready(function(){
	resize();
	// redraw the screen if the window size changes for some reason
	$(window).resize(function(){
		if(this.resizeTO){ clearTimeout(this.resizeTO);}
		this.resizeTO=setTimeout(function(){
			resize();
		}, 500);
	});
});
</script>
</div>
<div class="main">
<div class="center"><div>

<?php echo $content; ?>

<form action="ldap_bootstrap.php" method="post">
<div class="table">
  <div>
    <div><label for="ldapserver">LDAP Server:</label></div>
    <div><input type="text" id="ldapserver" name="ldapserver" value=<?php echo "\"$ldapserver\""; ?>></div>
  </div>
  <div>
    <div><label for="binddn">LDAP Bind DN:</label></div>
    <div><input type="text" id="binddn" name="binddn" value=<?php echo "\"$binddn\""; ?>></div>
  </div>
  <div>
    <div><label for="userid">UserID:</label></div>
    <div><input type="text" name="userid" value=<?php echo "\"$userid\""; ?>></div>
  </div>
  <div>
    <div><label for="password">LDAP Bind Password:</label></div>
    <div><input type="password" name="password"></div>
  </div>
  <div>
    <div></div>
    <div><input type="submit" name="submit" value="Submit"></div>
  </div>
</div>
</form>

</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
