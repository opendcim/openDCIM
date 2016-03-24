<?php
  // Very important since we are using session variables to pass into install.php so that it knows we at least have some level of auth set up
  session_start();

  //	Uncomment these if you need/want to set a title in the header
	$header="openDCIM LDAP Setup";
  $content = "";

  if ( isset($_POST['ldapserver'])) {
    $ldapConn = ldap_connect( $config->ParameterArray['LDAPServer'] );
    if ( ! $ldapConn ) {
      $content = "<h3>Fatal error.  The LDAP server is not reachable.  Please try again later, or contact your system administrator to check the configuration.</h3>";
      error_log( "Unable to connect to LDAP Server: " . $_POST['ldapserver']);
    } else {
      ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );

      $ldapDN = $_POST['binddn'];
      $ldapPassword = $_POST['password'];

      $ldapBind = ldap_bind( $ldapConn, $ldapDN, $ldapPassword );

      if ( ! $ldapBind ) {
        $content = "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
      } else {
        $_SESSION['userid'] = $_POST['userid'];
        $_SESSION['ldapserver'] = $_POST['ldapserver'];
        $_SESSION['binddn'] = $_POST['binddn'];

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
<div class="page index">
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
    <div><input type="text" id="binddn" name="binddn" value=<?php echo "\"$binddn\""; ?>"></div>
  </div>
  <div>
    <div><label for="userid">UserID:</label></div>
    <div><input type="text" name="userid" value=<?php echo "\"$userid\""; ?>"></div>
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
