<?php

require __DIR__ . "/vendor/autoload.php";

use Jumbojett\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://keycloak.cadmuslabs.net/auth/realms/openDCIM/',
                                'dcim-live',
                                'c0e0cd6f-65cc-45b9-9c90-7d991024f8b7');
$oidc->authenticate();
$name = $oidc->requestUserInfo('given_name');
$email = $oidc->requestUserInfo('email');
print "Welcome, $name - $email";

$roles = $oidc->requestUserInfo('roles');
print_r( $roles );
