<?php
#
# For OIDC you don't actually do any login on this system, you are logging in at the IdP
# so there is nothing to display, only process some redirects
#

// Set a variable so that misc.inc.php knows not to throw us into an infinite redirect loop
$loginPage = true;

require_once "db.inc.php";
require_once "facilities.inc.php";
require __DIR__ . "/vendor/autoload.php";

use Jumbojett\OpenIDConnectClient;

// This is a test lab client secret and using an OAuth installation that isn't accessible from the internet
// once we write in the new login client this file will be removed, anyway
$oidc = new OpenIDConnectClient($config->ParameterArray["OIDCEndpoint"],
                                $config->ParameterArray["OIDCClientID"],
                                $config->ParameterArray["OIDCClientSecret"]);

if ( isset($_GET['logout'])) {
	error_log( "Logging out" );
	$token = $_SESSION["ID_TOKEN"];
	// Unfortunately session_destroy() doesn't actually clear out existing variables, so let's nuke from orbit
	session_unset();
	$_SESSION=array();
	unset($_SESSION["userid"]);
	session_destroy();
	session_commit();

	$oidc->signOut($token, $config->ParameterArray["InstallURL"]);

	$content="<h3>Logout successful.</h3>";
}

$oidc->authenticate();
$check_username = $oidc->requestUserInfo($config->ParameterArray["OIDCUserID"]);

// Remove the prefix and suffix from the user name
$lenprefix = strlen($config->ParameterArray["SAMLaccountPrefix"]);
if (substr(strtoupper($check_username), 0, $lenprefix) == strtoupper($config->ParameterArray["SAMLaccountPrefix"])) {
    $check_username = substr($check_username, $lenprefix);
}

$suffixStart = strlen($check_username)-strlen($config->ParameterArray["SAMLaccountSuffix"]);
if (substr(strtoupper($check_username), $suffixStart, strlen($check_username)) == strtoupper($config->ParameterArray["SAMLaccountSuffix"])) {
    $check_username = substr($check_username, 0, $suffixStart);
}

$_SESSION["userid"] = $check_username;
$_SESSION["ID_TOKEN"] = $oidc->getIdToken();

$person = new People();
$person->UserID = $oidc->requestUserInfo($config->ParameterArray["OIDCUserID"]);
if ( ! $person->GetPersonByUserID() )
	$person->CreatePerson();

if ( $config->ParameterArray["AttrFirstName"] != "" )
	$person->FirstName = $oidc->requestUserInfo($config->ParameterArray["AttrFirstName"]);

if ( $config->ParameterArray["AttrLastName"] != "" )
	$person->LastName = $oidc->requestUserInfo($config->ParameterArray["AttrLastName"]);

if ( $config->ParameterArray["AttrEmail"] != "" )
	$person->Email = $oidc->requestUserInfo($config->ParameterArray["AttrEmail"]);

if ( $config->ParameterArray["AttrPhone1"] != "" )
	$person->Phone1 = $oidc->requestUserInfo($config->ParameterArray["AttrPhone1"]);

if ( $config->ParameterArray["AttrPhone2"] != "" )
	$person->Phone2 = $oidc->requestUserInfo($config->ParameterArray["AttrPhone2"]);

if ( $config->ParameterArray["AttrCountry"] != "" )
	$person->countryCode = $oidc->requestUserInfo($config->ParameterArray["AttrCountry"]);

// If an attribute name for 'Groups' is specified, use it to override rights.  Otherwise, leave existing accounts alone.
if ( $config->ParameterArray["SAMLGroupAttribute"] != "" ) {
	/*
	$person->revokeAll();
	$groupList = $oidc->requestUserInfo($config->ParameterArray["SAMLGroupAttribute"]);
	if ( is_array( $groupList ) && sizeof($groupList)>0 ) {
		if ( in_array( $config->ParameterArray["LDAPSiteAccess"], $groupList ) )
			$person->SiteAccess = true;
		if ( in_array( $config->ParameterArray["LDAPReadAccess"], $groupList ) )
			$person->ReadAccess = true;
		if ( in_array( $config->ParameterArray["LDAPWriteAccess"], $groupList ) )
			$person->WriteAccess = true;
		if ( in_array( $config->ParameterArray["LDAPDeleteAccess"], $groupList ) )
			$person->DeleteAccess = true;
		if ( in_array( $config->ParameterArray["LDAPAdminOwnDevices"], $groupList ) )
			$person->AdminOwnDevices = true;
		if ( in_array( $config->ParameterArray["LDAPRackRequest"], $groupList ) )
			$person->RackRequest = true;
		if ( in_array( $config->ParameterArray["LDAPRackAdmin"], $groupList ) )
			$person->RackAdmin = true;
		if ( in_array( $config->ParameterArray["LDAPContactAdmin"], $groupList ) )
			$person->ContactAdmin = true;
		if ( in_array( $config->ParameterArray["LDAPSiteAdmin"], $groupList ) )
			$person->SiteAdmin = true;
		if ( in_array( $config->ParameterArray["LDAPBulkOperations"], $groupList ) )
			$person->BulkOperations = true;
	}
	*/
}

$person->UpdatePerson();

if ( ! isset( $_POST['RelayState'])) {
	header('Location: index.php');
} else {
	header('Location: ' .$_POST['RelayState']);
}
