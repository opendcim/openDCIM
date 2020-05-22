<?php
 
/**
<<<<<<< HEAD
 *  SP Metadata Endpoint
 */

$loginPage = true;

=======
 *  SAML Metadata view
 */

>>>>>>> a500912f80079b7ec4ae7414e8f675e71950c45f
require_once('../db.inc.php');

define("TOOLKIT_PATH", '../vendor/onelogin/php-saml/');
require_once(TOOLKIT_PATH . '_toolkit_loader.php');

<<<<<<< HEAD
require_once('settings.php');

try {
    $settings = new OneLogin_Saml2_Settings($saml_settings);
    $metadata = $settings->getSPMetadata($settings);
=======
require_once 'settings.php' ;

try {
    #$auth = new OneLogin_Saml2_Auth($settingsInfo);
    #$settings = $auth->getSettings();
    // Now we only validate SP settings
    $settings = new OneLogin_Saml2_Settings($saml_settings, true);
    $metadata = $settings->getSPMetadata();
>>>>>>> a500912f80079b7ec4ae7414e8f675e71950c45f
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
