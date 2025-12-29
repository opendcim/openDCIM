<?php
	require_once "db.inc.php";
	require_once "facilities.inc.php";

	define("TOOLKIT_PATH", './vendor/onelogin/php-saml/');
	require_once(TOOLKIT_PATH . '_toolkit_loader.php');
	require_once( "./saml/settings.php" );

	$parser = new OneLogin\Saml2\IdPMetadataParser;
	error_log( "Downloading new IdP Metadata from " . $config->ParameterArray["SAMLIdPMetadataURL"]);
	$IdPSettings = $parser->parseRemoteXML($config->ParameterArray["SAMLIdPMetadataURL"]);

	// Overcommunicate so that we can actually keep track of any changes in the logs
	if ( $config->ParameterArray["SAMLidpentityId"] != $IdPSettings['idp']['entityId'] ) {
		$config->ParameterArray["SAMLidpentityId"] = $IdPSettings['idp']['entityId'];
		error_log( "SAMLidpentityId updated to " . $IdPSettings['idp']['entityId']);
	}

	if ( $config->ParameterArray["SAMLidpx509cert"] != $IdPSettings['idp']['x509cert'] ) {
		$config->ParameterArray["SAMLidpx509cert"] = $IdPSettings['idp']['x509cert'];
		error_log( "SAMLidpx509cert updated to " . $IdPSettings['idp']['x509cert']);
	}
	// Only set the SLS URL if it exists in the metadata
	if ( array_key_exists( "singleLogoutService", $IdPSettings["idp"]) && $config->ParameterArray["SAMLidpslsURL"] != $IdPSettings['idp']['singleLogoutService']['url'] ) {
		$config->ParameterArray["SAMLidpslsURL"] = $IdPSettings['idp']['singleLogoutService']['url'];
		error_log( "SAMLidpslsURL updated to " . $IdPSettings['idp']['singleLogoutService']['url']);
	}
	if ( $config->ParameterArray["SAMLidpssoURL"] != $IdPSettings['idp']['singleSignOnService']['url'] ) {
		$config->ParameterArray["SAMLidpssoURL"] = $IdPSettings['idp']['singleSignOnService']['url'];
		error_log( "SAMLidpssoURL updated to " . $IdPSettings['idp']['singleSignOnService']['url']);
	}

	$config->UpdateConfig();
	
	error_log( "Metadata refresh complete." );
?>