<?php


if(!function_exists("ldap_escape")){
	function ldap_escape($str = '') {
		$metaChars = array(
			chr(0x5c), // \
			chr(0x2a), // *
			chr(0x28), // (
			chr(0x29), // )
			chr(0x00) // NUL
		);

		// Build the list of the escaped versions of those characters.
		$quotedMetaChars = array ();
		foreach ($metaChars as $key => $value) {
			$quotedMetaChars[$key] = '\\' .
			str_pad(dechex(ord($value)), 2, '0', STR_PAD_LEFT);
		}

		// Make all the necessary replacements in the input string and return
		// the result.
		return str_replace($metaChars, $quotedMetaChars, $str);
	}
}
function checkAccess($ldapobject){
	$config=new Config();
	$access=false;
	if(array_key_exists("memberof",$ldapobject)){
		foreach ($ldapobject['memberof'] as $group){
			if(!strcasecmp($group, $config->ParameterArray['LDAPSiteAccess'])){
				$access=true;
			}
		}
	}else{
		if(!strcasecmp($ldapobject['dn'], $config->ParameterArray['LDAPSiteAccess'])){
			$access=true;
		}
	}
	return $access;
}
function setRights($group,&$person){
	// Originally this was a Switch/Case statement, which would seem to make more sense.
	// However, if someone wants to use the same Group identifier for more than one right,
	// the switch/case would only allow for that group membership to be used once.
	//
	// So, here we are with a ton of if/then statements.

	$config=new Config();
	if(!strcasecmp($group,$config->ParameterArray['LDAPReadAccess'])){
		$person->ReadAccess=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPWriteAccess'])){
		$person->WriteAccess=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPDeleteAccess'])){
		$person->DeleteAccess=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPAdminOwnDevices'])){
		$person->AdminOwnDevices=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPRackRequest'])){
		$person->RackRequest=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPRackAdmin'])){
		$person->RackAdmin=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPContactAdmin'])){
		$person->ContactAdmin=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPBulkOperations'])){
		$person->BulkOperations=true;
	}

	if(!strcasecmp($group,$config->ParameterArray['LDAPSiteAdmin'])){
		$person->SiteAdmin=true;
	}
}

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
      ldap_set_option( $ldapConn, LDAP_OPT_REFERRALS, 0 );

      $ldapUser = ldap_escape(htmlspecialchars($_POST['username']), null, LDAP_ESCAPE_FILTER);
      $ldapDN = str_replace( "%userid%", $ldapUser, $config->ParameterArray['LDAPBindDN']);
      $ldapPassword = ldap_escape($_POST['password'], null, LDAP_ESCAPE_FILTER);

      $ldapBind = ldap_bind( $ldapConn, $ldapDN, $ldapPassword );

      if ( ! $ldapBind ) {
        $content = "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
        error_log( __("Unable to bind to specified LDAP server with specified username/password.  Username:") . $ldapUser );
      } else {
        // User was able to authenticate, but might not have authorization to access openDCIM.  Here we check for those rights.
        /* If this install doesn't have the new parameter, use the old default */
        if ( !isset($config->ParameterArray['LDAPBaseSearch'])) {
          $config->ParameterArray['LDAPBaseSearch'] = "(&(objectClass=posixGroup)(memberUid=%userid%))";
        }
        // Now get some more info about the user
	//Get the DN so I can use the LDAP_MATCHING_RULE_IN_CHAIN function
        // Insert the default 4.2 UserSearch string in case this is an upgrade instance
        if ( ! isset($config->ParameterArray['LDAPUserSearch'])) {
          $config->ParameterArray['LDAPUserSearch'] = "(|(uid=%userid%))";
        }

        // Because we have audit logs to maintain, we need to make a local copy of the User's record
        // to keep in the openDCIM database just in case the user gets removed from LDAP.  This also
        // makes it easier to check access rights by replicating the user's rights from LDAP into the
        // local db for the session.  Revoke all rights every login and pull a fresh set from LDAP.
        $person->UserID = $ldapUser;
        $person->GetPersonByUserID();
        $person->revokeAll();
		
        $userSearch = str_replace( "%userid%", $ldapUser, html_entity_decode($config->ParameterArray['LDAPUserSearch']));
        $ldapSearch = ldap_search( $ldapConn, $config->ParameterArray['LDAPBaseDN'], $userSearch );
        $ldapResults = ldap_get_entries( $ldapConn, $ldapSearch );

        // These are standard schema items, so they aren't configurable
        // However, suppress any errors that may crop up from not finding them
        $person->FirstName = @$ldapResults[0]['givenname'][0];
        $person->LastName = @$ldapResults[0]['sn'][0];
        $person->Email = @$ldapResults[0]['mail'][0];

        $ldapSearchDN = str_replace( "%userid%", $ldapUser, html_entity_decode($config->ParameterArray['LDAPBaseSearch']));
        $ldapSearch = ldap_search( $ldapConn, $config->ParameterArray['LDAPBaseDN'], $ldapSearchDN);
        $ldapResults = ldap_get_entries( $ldapConn, $ldapSearch );

        for ( $i = 0; $i < $ldapResults['count']; $i++ ) {
          if($config->ParameterArray['LDAPSiteAccess'] == "" || checkAccess($ldapResults[$i])) {
            // No specific group membership required to access openDCIM or they have a match to the group required
            $_SESSION['userid'] = $ldapUser;
            $_SESSION['LoginTime'] = time();
            session_commit();
            error_log( __("LDAP authentication successful, granted site access based on required group membership.  Username:") . $ldapUser);
          } else {
            error_log( __("LDAP authentication successful, but access denied based on lacking group membership.  Username:") . $ldapUser);
          }
			$ldapentry=$ldapResults[$i];
			// if memberof exists then we're dealing with AD
			if (array_key_exists("memberof",$ldapentry)){
				foreach ($ldapResults[$i]['memberof'] as $group){
					setRights($group,$person);
				}
			}else{
				setRights($ldapResults[$i]['dn'],$person);
			}
        }

        if ( isset($_SESSION['userid']) ) {
          if ( $person->PersonID > 0 ) {
            $person->UpdatePerson();
          } else {
            $person->CreatePerson();
          }
          if ( isset($_COOKIE['targeturl'] )) {
            header('Location: ' . html_entity_decode($_COOKIE['targeturl']));
          } else {
            header('Location: ' . redirect('index.php'));
          }
          exit;
        } else {
          $content .= "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
          error_log( __("LDAP Authentication failed for username:") . $ldapUser);
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
<script type="text/javascript">
$(document).ready(function() {
  $("#username").focus();
});
</script>
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
    <div><input type="text" id="username" name="username"></div>
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


<div>
<?php
if ( file_exists("sitecontact.html")) {
  include("sitecontact.html");
}
?>
</div>
</div></div>
</div><!-- END div.main -->
</div><!-- END div.page -->
</body>
</html>
