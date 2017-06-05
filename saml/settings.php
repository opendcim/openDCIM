<?php

$saml_settings = array(
	'strict' => ($config->ParameterArray["SAMLStrict"]=='enabled' ? true : false),
	'debug' => ($config->ParameterArray["SAMLDebug"]=='enabled' ? true : false),
	'baseurl' => $config->ParameterArray["SAMLBaseURL"],
	'sp' => array(
		'entityId' => $config->ParameterArray["SAMLspentityId"],
		'assertionConsumerService' => array(
			'url' => $config->ParameterArray["SAMLspacsURL"],
			'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
		),
		'singleLogoutService' => array(
			'url' => $config->ParameterArray["SAMLspslsURL"],
			'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
		),
		'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
		'x509cert' => $config->ParameterArray["SAMLspx509cert"],
		'privateKey' => $config->ParameterArray["SAMLspprivateKey"],
	),
	'idp' => array(
		'entityId' => $config->ParameterArray["SAMLidpentityId"],
		'singleSignOnService' => array(
			'url' => $config->ParameterArray["SAMLidpssoURL"],
			'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
		),
		'singleLogoutService' => array(
			'url' => $config->ParameterArray["SAMLidpslsURL"],
			'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
		),
		'certFingerprint' => $config->ParameterArray["SAMLidpcertFingerprint"],
		'certFingerprintAlgorithm' => $config->ParameterArray["SAMLidpcertFingerprintAlgorithm"],
	),
);
?>
