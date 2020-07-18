<?php
// Set a variable so that misc.inc.php knows not to throw us into an infinite redirect loop
$loginPage = true;

require_once('../db.inc.php');
require_once('../facilities.inc.php');

define("TOOLKIT_PATH", '../vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

require_once('settings.php');

/**
 * Saml authentication
 */

$auth = new OneLogin\Saml2\Auth($saml_settings);
$auth->processResponse();
$errors = $auth->getErrors();

if (!empty($errors)) {
	exit();
}

$idpResponse = $_POST['SAMLResponse'];
$settings = new OneLogin\Saml2\Settings($saml_settings);
$samlResponse = new OneLogin\Saml2\Response($settings, $idpResponse);
if (!$auth->isAuthenticated()) {
	exit();
}

// Get and clear the SAML authRequest ID to validate the response is related
if (isset($_SESSION['saml_req_id'])) {
	$saml_reqID = $_SESSION['saml_req_id'];
	unset($_SESSION['saml_req_id']);
}

if ($samlResponse->isValid($saml_reqID)) {
	$check_username = $samlResponse->getNameId();
	$attributes = $samlResponse->getAttributes();
	if (!empty($attributes)) {
                $alias = $attributes['urn:oid:2.5.4.42'][0] . " " . $attributes['urn:oid:2.5.4.4'][0];
	}

        // Remove the prefix and suffix from the user name
        $lenprefix = strlen($config->ParameterArray["SAMLaccountPrefix"]);
        if (substr(strtoupper($check_username), 0, $lenprefix) == strtoupper($config->ParameterArray["SAMLaccountPrefix"])) {
            $check_username = substr($check_username, $lenprefix);
        }

        $suffixStart = strlen($check_username)-strlen($config->ParameterArray["SAMLaccountSuffix"]);
        if (substr(strtoupper($check_username), $suffixStart, strlen($check_username)) == strtoupper($config->ParameterArray["SAMLaccountSuffix"])) {
            $check_username = substr($check_username, 0, $suffixStart);
        }

		$_SESSION['userid'] = $check_username;
		$person = new People();
		$person->UserID = $check_username;
		if ( ! $person->GetPersonByUserID() )
			$person->CreatePerson();

		if ( $config->ParameterArray["AttrFirstName"] != "" )
			$person->FirstName = $attributes[$config->ParameterArray["AttrFirstName"]][0];

		if ( $config->ParameterArray["AttrLastName"] != "" )
			$person->LastName = $attributes[$config->ParameterArray["AttrLastName"]][0];

		if ( $config->ParameterArray["AttrEmail"] != "" )
			$person->Email = $attributes[$config->ParameterArray["AttrEmail"]][0];

		if ( $config->ParameterArray["AttrPhone1"] != "" )
			$person->Phone1 = $attributes[$config->ParameterArray["AttrPhone1"]][0];

		if ( $config->ParameterArray["AttrPhone2"] != "" )
			$person->Phone2 = $attributes[$config->ParameterArray["AttrPhone2"]][0];

		if ( $config->ParameterArray["AttrPhone3"] != "" )
			$person->Phone3 = $attributes[$config->ParameterArray["AttrPhone3"]][0];

		if ( $config->ParameterArray["SAMLGroupAttribute"] != "" ) {
			$person->revokeAll();
			$groupList = $attributes[$config->ParameterArray["SAMLGroupAttribute"]];

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

		$person->UpdatePerson();

        if ($config->ParameterArray["SAMLShowSuccessPage"] == 'disabled') {
		header('Location: ' .$_POST['RelayState']);
	}
	$success = true;
} else {
	exit();
}

	if($success)
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<?php echo ($config->ParameterArray["SAMLShowSuccessPage"]=='enabled' ? '<meta http-equiv="refresh" content="10;url='.$_POST['RelayState'].'">' : '') 
?>
<head>
<title>SAML client results</title>
</head>
<body>
<?php
		echo '<h1>', HtmlSpecialChars($check_username),
			' you have logged in successfully with SAML!</h1>';
		echo '<pre>', HtmlSpecialChars(print_r($check_username, 1));
		echo print_r($attributes, true);
?>
</body>
</html>
<?php
	}
	else
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>SAML client error</title>
</head>
<body>
<h1>SAML client error</h1>
<pre>Error: <?php echo HtmlSpecialChars($auth->getErrors()); ?></pre>
</body>
</html>
<?php
	}

?>
