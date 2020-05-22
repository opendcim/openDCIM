<?php
 
/**
 *  SP Metadata Endpoint
 */

$loginPage = true;

require_once('../db.inc.php');

define("TOOLKIT_PATH", '../vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

require_once('settings.php');

try {
    $settings = new OneLogin_Saml2_Settings($saml_settings);
    $metadata = $settings->getSPMetadata($settings);
    $errors = $settings->validateMetadata($metadata);
    if (empty($errors)) {
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin_Saml2_Error(
            'Invalid SP metadata: '.implode(', ', $errors),
            OneLogin_Saml2_Error::METADATA_SP_INVALID
        );
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
