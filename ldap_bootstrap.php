<?php
  // Very important since we are using session variables to pass into install.php so that it knows we at least have some level of auth set up
  session_start();

  require_once("db.inc.php");

  //	Uncomment these if you need/want to set a title in the header
	$header="openDCIM LDAP/AD Authentication Bootstrap";
  $content = "";

  if ( isset($_REQUEST['ldapserver'])) {
    $ldapConn = ldap_connect( $_REQUEST['ldapserver'] );
    if ( AUTHENTICATION == "AD" && ( empty($_REQUEST['password']) || empty($_REQUEST['userid']) ) ) {
      // Username and password should not be empty when using AD authentication, otherwise
      // we bind anonymously which kind of defeats the purpose of authenticating...
      $content .= "<h3>A username and password are required.</h3>";
    } else {
      ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );

      if ( AUTHENTICATION == "AD" ) {
	      $ldapDN = $_REQUEST['userid'];
      } else {
	      $ldapDN = $_REQUEST['binddn'];
      }
      $ldapPassword = $_REQUEST['password'];

      $ldapBind = ldap_bind( $ldapConn, $ldapDN, $ldapPassword );

      if ( ! $ldapBind ) {
        $content .= "<h3>Login failed.  " . ldap_error($ldapConn) . ".</h3>";
	error_log( "Login failed for $ldapDN: " . ldap_error($ldapConn));
      } else {
        $_SESSION['userid'] = $_REQUEST['userid'];
	$_SESSION['ldapserver'] = $_REQUEST['ldapserver'];

	if ( AUTHENTICATION == "LDAP" ) {
		$_SESSION['ldapbinddn'] = $_REQUEST['binddn'];
	}

	// Try to be helpful during install phase and take a guess at the base DN value
	// based on the LDAP server name. The user can always change this if we get it wrong.
	if ( preg_match('/\w+\.([\w.]+)/', $_REQUEST['ldapserver'], $matches) ) {
		// Take LDAP server, match everything after the first period (and excluding any ports).
		// Replace any periods with "dc=". e.g. if LDAP server = server.corp.opendcim.org,
		// the base DN would be dc=corp,dc=opendcim,dc=org

		$_SESSION['ldapbasedn'] = "dc=" . str_replace('.', ',dc=', $matches[1]);
	}

        session_commit();
        header('Location: install.php');
        exit;
      }

    }

    $content .= "<h3>Incorrect configuration.</h3>";
  }

  $ldapserver = $binddn = $userid = "";
  if ( isset( $_REQUEST['ldapserver'] )) {
    $ldapserver = $_REQUEST['ldapserver'];
  }

  if ( isset( $_REQUEST['binddn'])) {
    $binddn = $_REQUEST['binddn'];
  }

  if ( isset( $_REQUEST['userid'] ) ) {
    $userid = $_REQUEST['userid'];
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
<?php
if ( AUTHENTICATION == "LDAP" ) {
?>
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
<?php
} elseif ( AUTHENTICATION == "AD" ) {
?>
  <div>
    <div><label for="ldapserver">AD Global Catalog Server:</label></div>
    <div><input type="text" id="ldapserver" name="ldapserver" value=<?php echo "\"$ldapserver\""; ?>></div>
  </div>
  <div>
    <div><label for="userid">Username (UPN):</label></div>
    <div><input type="text" name="userid" value=<?php echo "\"$userid\""; ?>></div>
  </div>
  <div>
    <div><label for="password">Password:</label></div>
    <div><input type="password" name="password"></div>
  </div>
<?php
}
?>
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
