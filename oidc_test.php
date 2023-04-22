<?php
require 'vendor/autoload.php';

$issuer = 'https://login.microsoftonline.com/57ee79b4-0672-40a5-b9bb-65b1306b8986/v2.0/';
$cid = 'd83a1b4d-3b04-4fc5-9ed4-d44c53508721';
$secret = 'bma8Q~D2fJtSuUreG0iT4lbtVLj9HX~whtaw.cGd';
$oidc = new Jumbojett\OpenIDConnectClient($issuer, $cid, $secret);

$oidc->addScope(array('openid', 'profile', 'email'));
$oidc->setAllowImplicitFlow(true);
$oidc->authenticate();

$attrList = [ "sub", "preferred_username", "name", "email", "family_name", "given_name", "upn" ];
foreach ( $attrList as $attr ) {
    $attrs[$attr] = $oidc->requestUserInfo($attr);
}

print_r( $attrs );

$session = array();
foreach($oidc as $key=> $value) {
    if(is_array($value)){
            $v = implode(', ', $value);
    }else{
            $v = $value;
    }
    $session[$key] = $v;
}


print_r(  $session );

?>