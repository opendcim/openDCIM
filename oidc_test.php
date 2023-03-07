<?php

require __DIR__ . "/vendor/autoload.php";

use Jumbojett\OpenIDConnectClient;

// This is a test lab client secret and using an OAuth installation that isn't accessible from the internet
// once we write in the new login client this file will be removed, anyway
$oidc = new OpenIDConnectClient('https://keycloak.cadmuslabs.net/auth/realms/openDCIM/',
                                'dcim-live',
                                'c0e0cd6f-65cc-45b9-9c90-7d991024f8b7');
$oidc->authenticate();
$name = $oidc->requestUserInfo('given_name');
$email = $oidc->requestUserInfo('email');
print "Welcome, $name - $email";

$roles = $oidc->requestUserInfo('roles');
print_r( $roles );
