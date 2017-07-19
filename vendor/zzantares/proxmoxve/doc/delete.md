Destroying resources
====================

On your Proxmox API client object you can call the `delete()` function in order to change the state of a resource, depending on the resource type you will need to send an array filled with proper parameters.

Lets see how can we destroy the proxmox user *bob* which is using the realm *pve*:

```php
<?php

// Require the autoloader
require_once 'vendor/autoload.php';

// Use the library namespaces
use ProxmoxVE\Credentials;
use ProxmoxVE\Proxmox;

$server = 'my.proxmox.tld';
$user = 'root';
$pass = 'secret';

// Create your Credentials object
$credentials = new Credentials($server, $user, $pass);

// Then simply pass your Credentials object when creating the API client object
$proxmox = new Proxmox($credentials);

// We use delete() function since we want to destroy a specified resource
$result = $proxmox->delete('/access/users/bob@pve');

print_r($result);

```

A successful request will output:

```php
Array
(
    [data] => 
)
```

But if instead we specify the user without realm we'll get an error:

```php
Array
(
    [errors] => Array
        (
            [userid] => invalid format - value 'bob' does not look like a valid user name

        )

    [data] => 
)
```

As you can see the `destroy()` function receives the desired resource path you want to destroy. In rare cases you may want to pass params to the `destroy()` function, in that cases you should pass params in an associative array as second a parameter.

Search for the `errors` key in the `$result` array in order to know if your request was executed without errors.

FAQ
---

**How can I know what resource paths are available and which params needs to be passed?**

It's all in the [PVE2 API Documentation](http://pve.proxmox.com/pve2-api-doc/).

