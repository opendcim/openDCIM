<?php
	// Set a variable so that misc.inc.php knows not to throw us into an infinite redirect loop
	$loginPage=true;

	require_once('db.inc.php');
	require_once('facilities.inc.php');

	// Uncomment these if you need/want to set a title in the header
	$header=__("openDCIM Login");
	$content="";
	$person=new People();

	// Helper functions
	if(!defined('LDAP_ESCAPE_FILTER')){
		define('LDAP_ESCAPE_FILTER', 'Error Suppression');
	}
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

	if ( isset($_GET['logout'])) {
		// Unfortunately session_destroy() doesn't actually clear out existing variables, so let's nuke from orbit
		session_unset();
		$_SESSION=array();
		unset($_SESSION["userid"]);
		session_destroy();
		session_commit();
		$content="<h3>Logout successful.</h3>";
	}

	if(isset($_POST['username'])){
		$ldapConn=ldap_connect($config->ParameterArray['LDAPServer']);
		if(!$ldapConn){
			$content="<h3>Fatal error.  The LDAP server is not reachable.  Please try again later, or contact your system administrator to check the configuration.</h3>";
			error_log("Unable to connect to LDAP Server: {$config->ParameterArray['LDAPServer']}");
		}else{
			if(!strcasecmp($config->ParameterArray['LDAPServerType'],"OpenLDAP")){
				$groupAttr="dn";
				$ldapBaseSearch=$config->ParameterArray['LDAPBaseSearch'];
				$ldapBindDN=$config->ParameterArray['LDAPBindDN'];
			}else{
				$groupAttr="memberof";
				$ldapBaseSearch=$config->ParameterArray['LDAPBaseSearch-AD'];
				$ldapBindDN=$config->ParameterArray['LDAPBindDN-AD'];
			}
				
			ldap_set_option($ldapConn,LDAP_OPT_PROTOCOL_VERSION,3);
			ldap_set_option($ldapConn,LDAP_OPT_REFERRALS,0);

			$ldapUser=ldap_escape(htmlspecialchars($_POST['username']),null,LDAP_ESCAPE_FILTER);
			$ldapDN=str_replace("%userid%",$ldapUser,$ldapBindDN);
			$ldapPassword=ldap_escape($_POST['password'],null,LDAP_ESCAPE_FILTER);

			$ldapBind=ldap_bind($ldapConn,$ldapDN,$ldapPassword);

			if(!$ldapBind){
				$content="<h3>Login failed.  Incorrect username, password, or rights.</h3>";
				error_log( __("Unable to bind to specified LDAP server with specified username/password.  Username:") . $ldapUser );
			}else{
				$justThese = array(
					$config->ParameterArray['LDAPFirstName'],
					$config->ParameterArray['LDAPLastName'],
					$config->ParameterArray['LDAPEmail'],
					$config->ParameterArray['LDAPPhone1'],
					$config->ParameterArray['LDAPPhone2'],
					$config->ParameterArray['LDAPPhone3'],
					$groupAttr
				);
                                
				$ldapSearchDN=str_replace("%userid%",$ldapUser,html_entity_decode($ldapBaseSearch));
				$ldapSearch=ldap_search($ldapConn,$config->ParameterArray['LDAPBaseDN'],$ldapSearchDN,$justThese);
				// Sort the LDAP query result to make it easier to separate user and group data (only OpenLDAP);
				ldap_sort($ldapConn,$ldapSearch,$config->ParameterArray['LDAPFirstName']);
				$ldapResults=ldap_get_entries($ldapConn,$ldapSearch);

				// Because we have audit logs to maintain, we need to make a local copy of the User's record
				// to keep in the openDCIM database just in case the user gets removed from LDAP.  This also
				// makes it easier to check access rights by replicating the user's rights from LDAP into the
				// local db for the session.  Revoke all rights every login and pull a fresh set from LDAP.
				$person->UserID=$ldapUser;
				$person->GetPersonByUserID();
				$person->revokeAll();
                                
				if(!strcasecmp($config->ParameterArray['LDAPServerType'],"OpenLDAP")){
					$ldapUserInfo=array_pop($ldapResults);
					$ldapUserGroups=array_map('strtolower',array_column($ldapResults, 'dn'));
				}else{
					$ldapUserInfo=$ldapResults[0];
					$ldapUserGroups=array_map('strtolower',$ldapUserInfo['memberof']);
					unset($ldapUserInfo['memberof']);
				}

				// GetPersonByUserID just populated our person object, update it with the 
				// info we just pulled from ldap, if they are a valid user we'll update the
				// db version of their name below, suppress any errors for missing attributes
				@$person->FirstName=$ldapUserInfo[$config->ParameterArray['LDAPFirstName']][0];
				@$person->LastName =$ldapUserInfo[$config->ParameterArray['LDAPLastName']][0];
				@$person->Email    =$ldapUserInfo[$config->ParameterArray['LDAPEmail']][0];
				@$person->Phone1   =$ldapUserInfo[$config->ParameterArray['LDAPPhone1']][0];
				@$person->Phone2   =$ldapUserInfo[$config->ParameterArray['LDAPPhone2']][0];
				@$person->Phone3   =$ldapUserInfo[$config->ParameterArray['LDAPPhone3']][0];

				if($config->ParameterArray['LDAPSiteAccess']=="" || in_array(strtolower($config->ParameterArray['LDAPSiteAccess']),$ldapUserGroups)){
					// No specific group membership required to access openDCIM or they have a match to the group required
					$_SESSION['userid']=$ldapUser;
					$_SESSION['LoginTime']=time();
					session_commit();
					
					if(in_array(strtolower($config->ParameterArray['LDAPReadAccess']),$ldapUserGroups)){
						$person->ReadAccess=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPWriteAccess']),$ldapUserGroups)){
						$person->WriteAccess=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPDeleteAccess']),$ldapUserGroups)){
						$person->DeleteAccess=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPAdminOwnDevices']),$ldapUserGroups)){
						$person->AdminOwnDevices=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPRackRequest']),$ldapUserGroups)){
						$person->RackRequest=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPRackAdmin']),$ldapUserGroups)){
							$person->RackAdmin=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPContactAdmin']),$ldapUserGroups)){
						$person->ContactAdmin=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPBulkOperations']),$ldapUserGroups)){
						$person->BulkOperations=true;
					}

					if(in_array(strtolower($config->ParameterArray['LDAPSiteAdmin']),$ldapUserGroups)){
						$person->SiteAdmin=true;
					}
				}else{
					error_log(__("LDAP authentication successful, but access denied based on lacking group membership.  Username: $ldapUser"));
				}	
				
				if(isset($_SESSION['userid'])){
					if($person->PersonID>0){
						$person->UpdatePerson();
					}else{
						$person->CreatePerson();
					}
					if(isset($_COOKIE['targeturl'])){
						header('Location: ' . html_entity_decode($_COOKIE['targeturl']));
					}else{
						header('Location: ' . redirect('index.php'));
					}
					exit;
				}else{
					$content.="<h3>Login failed.  Incorrect username, password, or rights.</h3>";
					error_log(__("LDAP Authentication failed for username: $ldapUser"));
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