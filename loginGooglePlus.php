<?php
session_start();

require_once 'Google/Client.php';

/************************************************
  Instructions
  
  Remove any .htaccess file in the main DCIM directory
  Make a symbolic link for this file:
  
    $ ln -s loginGoogle.php login.php
	
  Create your API Access Key at the Google API Developer Console
  and be sure to select the Google+ API.  Note that you are
  limited to 10,000 athentications per month on the free use.
  
  Be sure to also follow the instruction in db.inc.php-dist to
  enable this authentication.
  
  ATTENTION: Fill in these values! Make sure
  the redirect URI is to this page.
 ************************************************/
 $client_id = '[Place your API Client ID Here]';
 $client_secret = '[Place your API Secret Key Here]';
 $redirect_uri = 'https://yourdomain/login.php';

/************************************************
  Change nothing else below here.
 ************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/plus.login");
$client->addScope("https://www.googleapis.com/auth/plus.profile.emails.read");

if (isset($_REQUEST['logout'])) {
  unset($_SESSION['access_token']);
}

if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $client->setAccessToken($_SESSION['access_token']);
  $plus = new Google_Service_Plus( $client );
} else {
  $authUrl = $client->createAuthUrl();
}

/************************************************
  If we're signed in then we set the session
  access token and move along to the index.
 ************************************************/
if ($client->getAccessToken() ) {
        $me = $plus->people->get('me');
        $_SESSION['access_token'] = $client->getAccessToken();
//      print "<pre>";
//      print_r( $me );
//      print "</pre>";
        print "Email: {$me['emails'][0]['value']}<br>";
}

?>
<div class="box">
  <div class="request">
    <?php if (isset($authUrl)): ?>
        This demo of openDCIM is testing Google OAuth2.0 authorization.
      <a class='login' href='<?php echo $authUrl; ?>'>Connect</a>
    <?php else: ?>
      <meta http-equiv="refresh" content="0; url=index.php">
    <?php endif ?>
  </div>
</div>
<?php
