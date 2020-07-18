<?php
// Set a variable so that misc.inc.php knows not to throw us into an infinite redirect loop
$loginPage = true;

require_once('../db.inc.php');
require_once('../facilities.inc.php');

define("TOOLKIT_PATH", '../vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

require_once('settings.php');

$samlSettings = new OneLogin\Saml2\Settings($saml_settings);

$idpData = $samlSettings->getIdPData();
if (isset($idpData['singleLogoutService']) && isset($idpData['singleLogoutService']['url'])) {
    $sloUrl = $idpData['singleLogoutService']['url'];
} else {
    throw new Exception("The IdP does not support Single Log Out");
}

if (isset($_SESSION['IdPSessionIndex']) && !empty($_SESSION['IdPSessionIndex'])) {
    $logoutRequest = new OneLogin\Saml2\LogoutRequest($samlSettings, null, $_SESSION['IdPSessionIndex']);
} else {
    $logoutRequest = new OneLogin\Saml2\LogoutRequest($samlSettings);
}

$samlRequest = $logoutRequest->getRequest();

$parameters = array('SAMLRequest' => $samlRequest);

$url = OneLogin\Saml2\Utils::redirect($sloUrl, $parameters, true);

header("Location: $url");
