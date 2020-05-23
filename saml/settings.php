<?php

/*
 * Start off with the bare minimum parameters
 */
$saml_settings = array(
	'strict' => true,
	'debug' => true,
	'baseurl' => $config->ParameterArray["SAMLBaseURL"].'/saml/',
	'sp' => array(
		'entityId' => $config->ParameterArray["SAMLspentityId"],
		'assertionConsumerService' => array(
			'url' => $config->ParameterArray["SAMLBaseURL"] . "/saml/acs.php",
			'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
		),
		'singleLogoutService' => array(
            'url' => $config->ParameterArray["SAMLBaseURL"] . "/saml/sls.php",
			'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
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
		'x509cert' => $config->ParameterArray["SAMLidpx509cert"],
	),
	'security' => array(
		'authNRequestsSigned' => true,
		'logoutRequestSigned' => true,
		'logoutResponseSigned' => true,
		'signMetadata' => false,
		'wantAssertionsSigned' => true
	)
);

// IdP Initiated Logout
if ( $config->ParameterArray["SAMLidpslsURL"] != "" ) {
	$saml_settings['idp']['singleLogoutService'] = array(
                        'url' => $config->ParameterArray["SAMLidpslsURL"],
                        'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
		);
}

?>
