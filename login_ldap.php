<?php

  // Set a variable so that misc.inc.php knows not to throw us into an infinite redirect loop
  $loginPage = true;

	require_once('db.inc.php');
	require_once('facilities.inc.php');

//	Uncomment these if you need/want to set a title in the header
	$header=__("openDCIM Login");
  $content = "";
  $person = new People();

  if ( isset($_GET['logout'])) {
    // Unfortunately session_destroy() doesn't actually clear out existing variables, so let's nuke from orbit
    session_unset();
    $_SESSION = array();
    unset($_SESSION["userid"]);
    session_destroy();
    session_commit();
    $content = "<h3>Logout successful.</h3>";
  }

  if ( isset($_POST['username'])) {
    $ldapConn = ldap_connect( $config->ParameterArray['LDAPServer'] );
    if ( ! $ldapConn ) {
      $content = "<h3>Fatal error.  The LDAP server is not reachable.  Please try again later, or contact your system administrator to check the configuration.</h3>";
      error_log( "Unable to connect to LDAP Server: " . $config->ParameterArray['LDAPServer']);
    } else {
      ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );

      $ldapUser = htmlspecialchars($_POST['username']);
      $ldapDN = str_replace( "%userid%", $ldapUser, $config->ParameterArray['LDAPBindDN']);
      $ldapPassword = $_POST['password'];

      $ldapBind = ldap_bind( $ldapConn, $ldapDN, $ldapPassword );

      if ( ! $ldapBind ) {
        $content = "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
        $content .= "ldapDN: $ldapDN<br>ldapPassword: $ldapPassword<br>";
      } else {
        // User was able to authenticate, but might not have authorization to access openDCIM.  Here we check for those rights.
        $ldapSearch = ldap_search( $ldapConn, $config->ParameterArray['LDAPBaseDN'], "(&(objectClass=posixGroup)(memberUid=$ldapUser))" );
        $ldapResults = ldap_get_entries( $ldapConn, $ldapSearch );

        // Because we have audit logs to maintain, we need to make a local copy of the User's record
        // to keep in the openDCIM database just in case the user gets removed from LDAP.  This also
        // makes it easier to check access rights by replicating the user's rights from LDAP into the
        // local db for the session.  Revoke all rights every login and pull a fresh set from LDAP.
        $person->UserID = $ldapUser;
        $person->GetPersonByUserID();
        $person->revokeAll();

        for ( $i = 0; $i < $ldapResults['count']; $i++ ) {
          $content .= "Group: " . $ldapResults[$i]['dn'] . "<br>\n";
          if ( $config->ParameterArray['LDAPSiteAccess'] == "" || $ldapResults[$i]['dn'] == $config->ParameterArray['LDAPSiteAccess'] ) {
            // No specific group membership required to access openDCIM or they have a match to the group required
            $_SESSION['userid'] = $ldapUser;
            session_commit();
          }


          switch( $ldapResults[$i]['dn'] ) {
            case $config->ParameterArray['LDAPReadAccess']:
              $person->ReadAccess = true;
              break;
            case $config->ParameterArray['LDAPWriteAccess']:
              $person->WriteAccess = true;
              break;
            case $config->ParameterArray['LDAPDeleteAccess']:
              $person->DeleteAccess = true;
              break;
            case $config->ParameterArray['LDAPAdminOwnDevices']:
              $person->AdminOwnDevices = true;
              break;
            case $config->ParameterArray['LDAPRackRequest']:
              $person->RackRequest = true;
              break;
            case $config->ParameterArray['LDAPRackAdmin']:
              $person->RackAdmin = true;
              break;
            case $config->ParameterArray['LDAPContactAdmin']:
              $person->ContactAdmin = true;
              break;
            case $config->ParameterArray['LDAPSiteAdmin']:
              $person->SiteAdmin = true;
              break;
            default:
              // Ignore any non-openDCIM related group memberships
          }
        }

        if ( isset($_SESSION['userid']) ) {
          if ( $person->PersonID > 0 ) {
            $person->UpdatePerson();
          } else {
            $person->CreatePerson();
          }
          echo "<meta http-equiv='refresh' content='0; index.php'>";
          exit;
        } else {
          $content .= "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
        }

      }
    }

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
</head>
<body>
<?php include( 'header.inc.php' ); ?>
<div class="page index">
<?php
  include( 'sidebar.inc.php' );
?>
<div class="main">
<div class="center"><div>

<?php echo $content; ?>

<form action="login_ldap.php" method="post">
<div class="table">
  <div>
    <div><label for="username"><?php echo __("Username:"); ?></label></div>
    <div><input type="text" name="username"></div>
  </div>
  <div>
    <div><label for="password"><?php echo __("Password:"); ?></label></div>
    <div><input type="password" name="password"></div>
  </div>
  <div>
    <div></div>
    <div><input type="submit" name="submit" value="<?php echo __("Submit"); ?>"></div>
  </div>
</div>
</form>




</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
