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

if (!isset($_SESSION['samlUserdata'])) {
	$settings = new OneLogin_Saml2_Settings($saml_settings);
	$authRequest = new OneLogin_Saml2_AuthnRequest($settings);
	$samlRequest = $authRequest->getRequest();
	
	$parameters = array('SAMLRequest' => $samlRequest);
	$parameters['RelayState'] = OneLogin_Saml2_Utils::getSelfURLNoQuery();

    	$idpData = $settings->getIdPData();
	$ssoUrl = $idpData['singleSignOnService']['url'];
	$url = OneLogin_Saml2_Utils::redirect($ssoUrl, $parameters, true);

	header("Location: $url");
    }
?>
