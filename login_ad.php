<?php

  /* Gets the members of the given group within the base DN.
     Returns an array containing the members. */
  function getLDAPGroupMembers(&$ldapConn, $baseDN, $groupDN) {
	$ldapSearch = ldap_search($ldapConn, $baseDN, "(|(distinguishedName=$groupDN))", array("member"));
	$ldapResults = ldap_get_entries($ldapConn, $ldapSearch);

	return @$ldapResults[0]['member'];
  }

  /* Checks if a user's group is also a member of the group's group.
     Returns true or false. */
  function inLDAPGroup($groupGroups, $userGroups) {
	// Remove the first element of the array.
	// The first element will be the element count, as returned by ldap_get_entries().
	@array_shift($groupGroups);
	@array_shift($userGroups);

	// If either the group groups or user groups are empty, return false.
	if (empty($groupGroups) || empty($userGroups)) {
		return false;
	}

	// Set all elements to lowercase.
	$groupGroups = array_map('strtolower', $groupGroups);
	$userGroups = array_map('strtolower', $userGroups);

	// Flip the group groups array so the value becomes the key.
	$groupGroups = array_flip($groupGroups);

	// Use isset() because it's faster than in_array() and others.
	foreach($userGroups as $userGroup) {
		if (isset($groupGroups[$userGroup])) {
			return true;
		}
	}

	return false;
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
      ldap_set_option( $ldapConn, LDAP_OPT_REFERRALS, 0 );
      ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );

      $ldapUser = htmlspecialchars($_POST['username']);
      $ldapDN = str_replace( "%userid%", $ldapUser, $config->ParameterArray['LDAPBindDN']);
      $ldapPassword = $_POST['password'];

      $ldapBind = ldap_bind( $ldapConn, $ldapDN, $ldapPassword );

      if ( ! $ldapBind ) {
        $content = "<h3>Login failed.  Incorrect username, password, or rights.</h3>";
	error_log("Login failed for $ldapUser. Incorrect username or password");
      } else {
        // User was able to authenticate, but might not have authorization to access openDCIM.  Here we check for those rights.
        /* If this install doesn't have the new parameter, use the old default */
        if ( !isset($config->ParameterArray['LDAPBaseSearch'])) {
          $config->ParameterArray['LDAPBaseSearch'] = "(&(objectClass=posixGroup)(memberUid=%userid%))";
        }

        // Because we have audit logs to maintain, we need to make a local copy of the User's record
        // to keep in the openDCIM database just in case the user gets removed from LDAP.  This also
        // makes it easier to check access rights by replicating the user's rights from LDAP into the
        // local db for the session.  Revoke all rights every login and pull a fresh set from LDAP.
        $person->UserID = $ldapUser;
        $person->GetPersonByUserID();
        $person->revokeAll();

        // Now get some more info about the user
        // Insert the default 4.2 UserSearch string in case this is an upgrade instance
        if ( ! isset($config->ParameterArray['LDAPUserSearch'])) {
          $config->ParameterArray['LDAPUserSearch'] = "(|(uid=%userid%))";
        }
        $userSearch = str_replace( "%userid%", $ldapUser, html_entity_decode($config->ParameterArray['LDAPUserSearch']));
        $ldapSearch = ldap_search( $ldapConn, $config->ParameterArray['LDAPBaseDN'], $userSearch );
        $ldapResults = ldap_get_entries( $ldapConn, $ldapSearch );

        // These are standard schema items, so they aren't configurable
        // However, suppress any errors that may crop up from not finding them
        $person->FirstName = @$ldapResults[0]['givenname'][0];
        $person->LastName = @$ldapResults[0]['sn'][0];
        $person->Email = @$ldapResults[0]['mail'][0];

	// Get the user's DN.
	$ldapUserDN = @$ldapResults[0]['dn'];
	// Get the groups that the user is a member of.
	$ldapUserGroups = @$ldapResults[0]['memberof'];
	// Add the user's DN to the groups as well because they may have been added as a member of the group explicitly.
	array_push($ldapUserGroups, $ldapUserDN);

	// Check the different OpenDCIM priviledges and try to match.
	// Lots of if statements here because a user could be a member of more than one group.
	if ($config->ParameterArray['LDAPSiteAccess'] == "" || inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPSiteAccess']), $ldapUserGroups)) {
		// No specific group membership required to access openDCIM or they have a match to the group required
		$_SESSION['userid'] = $ldapUser;
		$_SESSION['LoginTime'] = time();
		session_commit();
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPReadAccess']), $ldapUserGroups)) {
		$person->ReadAccess = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPWriteAccess']), $ldapUserGroups)) {
		$person->WriteAccess = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPDeleteAccess']), $ldapUserGroups)) {
		$person->DeleteAccess = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPAdminOwnDevices']), $ldapUserGroups)) {
		$person->AdminOwnDevices = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPRackRequest']), $ldapUserGroups)) {
		$person->RackRequest = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPRackAdmin']), $ldapUserGroups)) {
		$person->RackAdmin = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPContactAdmin']), $ldapUserGroups)) {
		$person->ContactAdmin = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPBulkOperations']), $ldapUserGroups)) {
		$person->BulkOperations = true;
	}

	if (inLDAPGroup(getLDAPGroupMembers($ldapConn, $config->ParameterArray['LDAPBaseDN'], $config->ParameterArray['LDAPSiteAdmin']), $ldapUserGroups)) {
		$person->SiteAdmin = true;
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
