<?php
/**
 *  SP Single Logout Service Endpoint
 */

$loginPage = true;

require_once('../db.inc.php');
require_once('../facilities.inc.php');

define("TOOLKIT_PATH", '../vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

require_once('settings.php');

$auth = new OneLogin\Saml2\Auth($saml_settings);

$auth->processSLO();

$errors = $auth->getErrors();

if (empty($errors)) {
	error_log( "Logged out, redirecting back to home page.");
    header("Location: " . $config->ParameterArray["SAMLBaseURL"]);
} else {
    echo implode(', ', $errors);
}