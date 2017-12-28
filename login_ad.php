<?php
  define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
  $loginPage = true;
  require_once('db.inc.php');
  require_once('facilities.inc.php');
  $header = __('openDCIM Login');
  $person = new People();
  $content = '';
  $ldapConn = NULL;
  $loginFailedMsg = '<h3>Login failed.  Incorrect username, password, or rights.</h3>';

  function doLogout() {
    global $content;
    if (!isset($_GET['logout'])) {
      return;
    }

    session_unset();
    $_SESSION = array();
    unset($_SESSION['userid']);
    session_destroy();
    session_commit();
    $content = '<h3>Logout successful.</h3>';
  }

  function connectLDAP()
  {
    global $ldapConn, $content, $config;
    if ($ldapConn) {
      return $ldapConn;
    }

    $ldapServer = $config->ParameterArray['LDAPServer'];
    $ldapConn = ldap_connect($ldapServer);
    if (! $ldapConn ) {
      $content = '<h3>Fatal error.  The LDAP server is not reachable.  Please try again later, or contact your system administrator to check the configuration.</h3>';
      error_log('Unable to connect to LDAP Server: ' . $ldapServer);
      return false;
    }
    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

    return $ldapConn;
  }

  function bindLDAP($username = NULL, $password = NULL) {
    global $ldapConn, $config;
    if (! connectLDAP()) {
      return;
    }

    if (! $username)
    {
      $username = $config->ParameterArray['LDAPBindDN'];
      $password = $config->ParameterArray['LDAPBindPassword'];
    }

    return ldap_bind($ldapConn, $username, $password);
  }

  function expandString($info, $macro)
  {
    $macro = html_entity_decode($macro);
    foreach(array_keys($info) as $key) {
      $macro = str_replace('%'. $key . '%', $info[$key], $macro);
    }
    return $macro;
  }

  function findUserDN($username) {
    global $ldapConn, $content, $config;
    $bind = bindLDAP();
    if (! $bind) {
      $content = '<h3>Fatal error.  The LDAP server is not reachable.  Please try again later, or contact your system administrator to check the configuration.</h3>';
      error_log('Unable to bind to LDAP Server using: ' . $config->ParameterArray['LDAPBindDN']);
      return false;
    }
    $searchFilter = expandString(['userid' => $username], $config->ParameterArray['LDAPUserSearch']);
    $search = ldap_search($ldapConn,
      $config->ParameterArray['LDAPBaseDN'],
      $searchFilter,
      ['givenName', 'sn', 'mail', 'sAMAccountName']);
    if (! $search) {
      ldap_get_option($ldapConn, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_info);
      error_log("LDAP DIAGNOSTIC: " . $extended_info);
      return false;
    }
    $results = ldap_get_entries($ldapConn, $search);
    return $results;
  }

  function searchGroups($userDN) {
    global $ldapConn, $config;
    $searchFilter = expandString($userDN, $config->ParameterArray['LDAPBaseSearch']);
    $search = ldap_search($ldapConn,
      $config->ParameterArray['LDAPBaseDN'],
      $searchFilter,
      ['dn']);
    return ldap_get_entries($ldapConn, $search);
  }

  function refreshRights($userDN) {
    global $person, $config;
    $person->UserID = $userDN['samaccountname'];
    $person->GetPersonByUserID();
    $person->revokeAll();

    $groups = searchGroups($userDN);
    $isAuth = false;

    for ($i = 0; $i < $groups['count']; $i++) {
      if ($config->ParameterArray['LDAPSiteAccess'] == "" || ($groups[$i]['dn'] == $config->ParameterArray['LDAPSiteAccess']) ) {
        $_SESSION['userid'] = $userDN['samaccountname'];
        $_SESSION['LoginTime'] = time();
        session_commit();
        $isAuth = true;
      }

      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPReadAccess']) {
        $person->ReadAccess = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPWriteAccess']) {
        $person->WriteAccess = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPDeleteAccess']) {
        $person->DeleteAccess = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPAdminOwnDevices']) {
        $person->AdminOwnDevices = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPRackRequest']) {
        $person->RackRequest = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPRackAdmin']) {
        $person->RackAdmin = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPContactAdmin']) {
        $person->ContactAdmin = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPBulkOperations']) {
        $person->BulkOperations = true;
      }
      if ($groups[$i]['dn'] == $config->ParameterArray['LDAPSiteAdmin']) {
        $person->SiteAdmin = true;
      }
    }
    return $isAuth;
  }

  function doLogin() {
    global $person, $content, $loginFailedMsg;
    if (!isset($_POST['username'])) {
      return;
    }
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $userDN = findUserDN($username);
    if (! $userDN) {
      return false;
    } else if ($userDN['count'] < 1) {
      error_log("User search failed\n");
      $content = $loginFailedMsg;
      return false;
    }
    $userDN = $userDN[0];
    $userInfo = array('dn' => $userDN['dn'],
                      'userdn' => $userDN['dn'],
                      'sn' => @$userDN['sn'][0],
                      'givenName' => @$userDN['givenname'][0],
                      'mail' => @$userDN['mail'][0],
                      'userid' => $userDN['samaccountname'][0],
                      'samaccountname' => $userDN['samaccountname'][0],
                    );

    $ldapBind = bindLDAP($userInfo['dn'], $password);

    if (! $ldapBind) {
      error_log("Bind as User failed");
      $content = $loginFailedMsg;
      return false;
    }

    if (refreshRights($userInfo)) {
      $person->FirstName = @$userInfo['givenName'];
      $person->LastName = @$userInfo['sn'];
      $person->Email = @$userInfo['mail'];

      if ($person->PersonID > 0) {
        $person->UpdatePerson();
      } else {
        $person->CreatePerson();
      }

      if (isset($_COOKIE['targeturl'])) {
        error_log('Cookie Target: ' . $_COOKIE['targeturl']);
        header('Location: ' . html_entity_decode($_COOKIE['targeturl']));
      } else {
        header('Location: ' . redirect('index.php'));
      }
      exit;
    } else {
      error_log("Not authorized");
      $content = $loginFailedMsg;
    }
  }
  doLogout();
  doLogin();
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

<form action="login_ad.php" method="post">
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
