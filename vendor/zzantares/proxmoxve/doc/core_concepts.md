Core concepts
=============

You must now that any ProxmoxVE server has a web service that can be used to manage all Proxmox resources, it uses a REST like API. The base URI to the API is like: `https://your.server:8006/api2/json/`, of course if you have Proxmox listening on another port you would replace it in the URI. Read more at the [Proxmox wiki](http://pve.proxmox.com/wiki/Proxmox_VE_API).


ProxmoxVE API Client library
----------------------------

This PHP5 library will handle all HTTP requests that the Proxmox web service needs in order to create, fetch, modify and delete all Proxmox resources.


Installation
------------

Recomended installation is using [Composer](https://getcomposer.org/), if you do not have [Composer](https://getcomposer.org/) what are you waiting?

In the root of your project execute the following:

```sh
$ composer require zzantares/proxmoxve ~1.0
```

Or add this to your `composer.json` file:

```json
{
    "require": {
        "zzantares/proxmoxve": "~1.0"
    }
}
```

Then perform the installation:
```sh
$ composer install --no-dev
```

Basic usage
-----------

```php
<?php

// Require the autoloader
require_once 'vendor/autoload.php';

// Use the library namespaces
use ProxmoxVE\Credentials;
use ProxmoxVE\Proxmox;

$server = 'your.server.tld';
$user = 'root';
$pass = 'secret';

// Create your Credentials object
$credentials = new Credentials($server, $user, $pass);

// realm and port defaults to 'pam' and '8006' but you can specify them like so
$credentials = new Credentials($server, $user, $pass, 'pve', '9009');

// Then simply pass your Credentials object when creating the API client object.
$proxmox = new Proxmox($credentials);

$allNodes = $proxmox->get('/nodes');

print_r($allNodes);
```


Sample output:

```php
Array
(
    [data] => Array
        (
            [0] => Array
                (
                    [disk] => 2539465464
                    [cpu] => 0.031314446882002
                    [maxdisk] => 30805066770
                    [maxmem] => 175168446464
                    [node] => mynode1
                    [maxcpu] => 24
                    [level] => 
                    [uptime] => 139376
                    [id] => node/mynode1
                    [type] => node
                    [mem] => 20601992182
                )

        )

)
```

For the lazy ones it is possible to create a ProxmoxVE instance passing an associative array but you need to specify all fields including *realm* and *port*:

```php
<?php
// Once again require the autoloader
require_once 'vendor/autoload.php';

// You can define your credentials using an array
$credentials = array(
    'hostname' => 'your.server.tld',
    'username' => 'root',
    'password' => 'secret',
    'realm' => 'pam',
    'port' => '8006',
);

// Create ProxmoxVE instance by passing the $credentials array
$proxmox = new ProxmoxVE\Proxmox($credentials);

// Then you can use it, for example create a new user.

// Define params
$params = array(
    'userid' => 'new_user@pve',  // Proxmox requires to specify the realm (see the docs)
    'comment' => 'Creating a new user',
    'password' => 'canyoukeepasecret?',
);

// Send request passing params
$result = $proxmox->create('/access/users', $params);

// If an error occurred the 'errors' key will exist in the response array
if (isset($result['errors'])) {
    error_log('Unable to create new proxmox user.');
    foreach ($result['errors'] as $title => $description) {
        error_log($title . ': ' . $description);
    }
} else {
    echo 'Successful user creation!';
}
```


Available functions
-------------------

On your proxmox client object you can use `get()`, `create()`, `set()` and `delete()` functions for all resources specified at [PVE2 API Documentation](http://pve.proxmox.com/pve2-api-doc/
), params are passed as the second parameter in an associative array.

- [Read more about create() function](https://github.com/ZzAntares/ProxmoxVE/blob/master/doc/create.md).
- [Read more about get() function](https://github.com/ZzAntares/ProxmoxVE/blob/master/doc/get.md).
- [Read more about set() function](https://github.com/ZzAntares/ProxmoxVE/blob/master/doc/set.md).
- [Read more about delete() function](https://github.com/ZzAntares/ProxmoxVE/blob/master/doc/delete.md).


Also any ProxmoxVE object has this functions that might be useful to you:


Set and get credentials
-----------------------

Some times your program will need to query multiple proxmox servers. You can use `setCredentials()` function to change the server that the library is talking to.
    
```php
<?php
require_once 'vendor/autoload.php';

$serverA = new ProxmoxVE\Credentials('hostA', 'userA', 'passwdA');
$proxmox = new ProxmoxVE\Proxmox($serverA);  // API object created only once

// Get nodes on server A
$proxmox->get('/nodes');

$serverB = new ProxmoxVE\Credentials('hostB', 'userB', 'passwdB');
$proxmox->setCredentials($serverB);

// After that every communication is sent to the new server

$proxmox->get('/nodes');  // Get nodes on server B

// Also you can call getCredentials for whatever reason
$credentialsB = $proxmox->getCredentials();

echo 'Hostname: ' . $credentialsB->getHostname();  // Hostname: hostB
```


Using custom credentials object
-------------------------------

You can pass your own custom credentials object when creating the API client object, for now this library internally will create a valid credentials object. The only thing you custom credentials object needs, is to have the required accesible properties:

- `hostname`
- `username`
- `password`
- `realm` (optional defaults to `pam`)
- `port` (optional defaults to `8006`)

If you feel using getters is better, the Proxmox API client object will search for the next getters if properties are not accesible:

- `getHostname()`
- `getUsername()`
- `getPassword()`
- `getRealm()` (optional defaults to `pam`)
- `getPort()` (optional defaults to `8006`)

```php
<?
require_once 'vendor/autoload.php';

// Example of custom credentials class
class CustomCredentials
{
    public function __construct($host, $user, $pass)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $pass;
    }
}

// Create an object of your custom credentials object
$customCredentials = new CustomCredentials('my.proxmox.tld', 'user', 'secret');

// Pass your custom credentials when creating the API client
$proxmox = new ProxmoxVE\Proxmox($customCredentials);

// At this point you can use the $proxmox normally
```

> **Why is this useful?** Personally when dealing with Eloquent models I already have a Credentials object, so I want to use that object to login to a proxmox server.

Set and get response type
-------------------------

You must now that the proxmox webservice API can give you responses in *json*, *extjs*, *html*, *text* and secretely *png* for server graphics. You can specify the response format using `setResponseType()` function.

```php
<?php
require_once 'vendor/autoload.php';

$serverCredentials = new ProxmoxVE\Credentials('host', 'user', 'passwd');

// You can specify format as 2nd argument when creating API client object.
$proxmox = new ProxmoxVE\Proxmox($serverCredentials, 'html');

// Ask for nodes, gives back a PHP string with HTML response
$proxmox->get('/nodes');

// Change response type to JSON
$proxmox->setResponseType('json');

// Now asking for nodes gives back JSON raw string
$proxmox->get('/nodes');

// If you want again return PHP arrays you can use the 'array' format.
$proxmox->setResponseType('array');

// Also you can call getResponseType for whatever reason have
$format = $proxmox->getResponseType();  // array
```

This library can respond in 2 extra formats, *array* and *pngb64*. If no response format is specified when creating the API client object, *array* will be used by default, which will give you back a PHP array as response.

```php
<?php
require_once 'vendor/autoload.php';

$serverCredentials = new ProxmoxVE\Credentials('host', 'user', 'passwd');

// You can specify format as 2nd argument when creating API client object.
$proxmox = new ProxmoxVE\Proxmox($serverCredentials, 'png');

// Because querying '/nodes' does not return PNG this will give you errrors.
$proxmox->get('/nodes');

// Asking for a PNG resource will give you back binary data.
$binaryPNG = $proxmox->get('/nodes/mynode/rrd', array('ds' => 'cpu', 'timeframe' => 'day'));

// It is common to fetch images and then use base64 to display the image easily
$proxmox->setResponseType('pngb64');  // format: data:image/png;base64,iVBORw0KGgoAAAA...
$base64 = $proxmox->get('/nodes/mynode/rrd', array('ds' => 'cpu', 'timeframe' => 'day'));

// 'array' it is used as default response type when unrecognized or no format is specified.
$proxmox->setResponseType();  // sets response type to 'array'
$proxmox->setResponseType('McDonalds');  // Also sets response type to 'array'
```


FAQ
---

**What resources or paths can I interact with and how?**

In your proxmox server you can use the [pvesh CLI Tool](http://pve.proxmox.com/wiki/Proxmox_VE_API#Using_.27pvesh.27_to_access_the_API) to manage all the pve resources, you can use this library in the exact same way you would use the pvesh tool. For instance you could run `pvesh` then, as the screen message should say, you can type `help [path] [--verbose]` to see how you could use a path and what params you should pass to it. Be sure to [read about the pvesh CLI Tool](http://pve.proxmox.com/wiki/Proxmox_VE_API#Using_.27pvesh.27_to_access_the_API) at the [Proxmox wiki](http://pve.proxmox.com/wiki).

**How does the Proxmox API works?**

Consult the [ProxmoxVE API](http://pve.proxmox.com/wiki/Proxmox_VE_API) article at the [Proxmox wiki](http://pve.proxmox.com/wiki).

**I need more docs!**

See the [doc](https://github.com/ZzAntares/ProxmoxVE/tree/master/doc) directory for more detailed documentation. Or use the [Proxmox forums support](http://forum.proxmox.com/).

