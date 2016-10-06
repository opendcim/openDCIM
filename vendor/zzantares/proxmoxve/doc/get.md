Fetching resources
==================

On your Proxmox API client object you can call the `get()` function, depending on the resource type you will need to send an array filled with proper parameters.

Let see how can we get the info of the proxmox user *bob* which is using the realm *pve*:

```php
<?php

// Require the autoloader
require_once 'vendor/autoload.php';

// Create your credentials array
$credentials = [
    'hostname' => 'my.proxmox.tld',
    'username' => 'root',
    'password' => 'secret',
];

// Then simply pass your credentials when creating the API client object
$proxmox = new \ProxmoxVE\Proxmox($credentials);

// We use get() function since we want to fetch data for a specified resource
$result = $proxmox->get('/access/users/bob@pve');

print_r($result);
```

Sample output:

```php
Array
(
    [data] => Array
        (
            [email] => bob.jamaica@mail.com
            [firstname] => Bob
            [enable] => 1
            [groups] => Array
                (
                )

            [lastname] => Marley
            [expire] => 0
        )

)
```

If we ask for a user without specifiying the realm we'll get an error:

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

As you can see the `get()` function receives as parameter the desired resource path to interact with, some times depending on the resource you will need to pass params, for example if you want to get only active users you will pass the `enabled` param in an array.

```php
$result = $proxmox->get('/access/users', ['enabled' => true]);
```

Search for the `errors` key in the `$result` array in order to know if your request was executed without errors.

FAQ
---

**How can I know what resource paths are available and which params needs to be passed?**

It's all in the [PVE2 API Documentation](http://pve.proxmox.com/pve2-api-doc/).


