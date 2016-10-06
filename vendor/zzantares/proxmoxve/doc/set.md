Update resources
================

On your Proxmox API client object you can call the `set()` function in order to change the state of a resource, depending on the resource type you will need to send an array filled with proper parameters.

Let see how can we update the *email* of the proxmox user *bob* which is using the realm *pve*:

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
$proxmox = new ProxmoxVE\Proxmox($credentials);

// We use set() function since we want to make a change on a existing resource
$result = $proxmox->set('/access/users/bob@pve', [
    'email' => 'bob.jamaica@mail.com'  // Want to change the user's email
]);

print_r($result);
```

A successful request will output:

```php
Array
(
    [data] => 
)
```

But if instead of `email` we wrote `mail`:

```php
Array
(
    [errors] => Array
        (
            [mail] => property is not defined in schema and the schema does not allow additional properties
        )

    [data] => 
)
```

As you can see the `set()` function receives the desired resource path to interact with as a first param, and as a second parameter you would pass the array filled with the info you want to update.

Search for the `errors` key in the `$result` array in order to know if your request was executed without errors.

FAQ
---

**How can I know what resource paths are available and which params needs to be passed?**

It's all in the [PVE2 API Documentation](http://pve.proxmox.com/pve2-api-doc/).

