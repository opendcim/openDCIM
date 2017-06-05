<?php
// Set a variable so that misc.inc.php knows not to throw us into an infinite redirect loop
$loginPage = true;

require_once('../db.inc.php');

define("TOOLKIT_PATH", '/var/www/opendcim/vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

require_once('settings.php');

/**
 * Saml authentication
 */

$auth = new OneLogin_Saml2_Auth($saml_settings);
$auth->processResponse();
$errors = $auth->getErrors();

if (!empty($errors)) {
	exit();
}

$idpResponse = $_POST['SAMLResponse'];
$settings = new OneLogin_Saml2_Settings($saml_settings);
$samlResponse = new OneLogin_Saml2_Response($settings, $idpResponse);
if (!$auth->isAuthenticated()) {
	exit();
}

if ($samlResponse->isValid()) {
	$check_username = $samlResponse->getNameId();
	$attributes = $samlResponse->getAttributes();
	if (!empty($attributes)) {
                $alias = $attributes['fn'][0] . " " . $attributes['ln'][0];
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
	$userid = $check_username;

        if ($config->ParameterArray["SAMLShowSuccessPage"] == 'disabled') {
		header('Location: ..');
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
<?php echo ($config->ParameterArray["SAMLShowSuccessPage"]=='enabled' ? '<meta http-equiv="refresh" content="2;url=..">' : '') 
?>
<head>
<title>SAML client results</title>
</head>
<body>
<?php
		echo '<h1>', HtmlSpecialChars($check_username),
			' you have logged in successfully with SAML!</h1>';
		echo '<pre>', HtmlSpecialChars(print_r($check_username, 1)), '</pre>';
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
