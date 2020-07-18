<?php
 
/**
 *  SP Metadata Endpoint
 */

$loginPage = true;

require_once('../db.inc.php');

define("TOOLKIT_PATH", '../vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

require_once 'settings.php';
require_once '../vendor/autoload.php';

try {
    $settings = new OneLogin\Saml2\Settings($saml_settings);
    $metadata = $settings->getSPMetadata($settings);

    // Now we only validate SP settings
    $settings = new OneLogin\Saml2\Settings($saml_settings, true);
    $metadata = $settings->getSPMetadata();
    $errors = $settings->validateMetadata($metadata);
    if (empty($errors)) {
        header('Content-Type: text/xml');
        echo $metadata;
    } else {
        throw new OneLogin\Saml2\Error(
            'Invalid SP metadata: '.implode(', ', $errors),
            OneLogin\Saml2\Error::METADATA_SP_INVALID
        );
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
