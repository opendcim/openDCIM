Creating resources
==================

On your Proxmox API client object you can call the `create()` function, depending on the resource type you will need to send an array filled with proper parameters.

Lets see how can we create a new proxmox user:

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

// Pass your Credentials object when creating the Proxmox API Client object.
$proxmox = new Proxmox($credentials);

// Prepare params to use
$newUserData = array(
    'userid' => 'bob@pve',
    'email' => 'uncle.bob@mail.com',
    'firstname' => 'Bob',
    'lastname' => 'Marley',
    'password' => 'StirItUp',
);

// Create a new proxmox user
$result = $proxmox->create('/access/users', $newUserData);

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

As you can see the `create()` function accepts two parameters, first is the desired resource path to interact with, and the second is an array filled with the proper params.

Here you can see the content of the `$result` array if we didn't pass the required param `userid`.

```php
print_r($result);
```

Outputs:

```php
Array
(
    [errors] => Array
        (
            [userid] => property is missing and it is not optional
        )

    [data] => 
)
```

Or if you pass the `userid` wihout telling the realm:

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

Finally a successful call will return:

```php
Array
(
    [data] => 
)
```

As you can see you can search for the `errors` key in the `$result` array in order to know if your request was executed without errors.

FAQ
---

**How can I know what resource paths are available and which params needs to be passed?**

It's all in the [PVE2 API Documentation](http://pve.proxmox.com/pve2-api-doc/).
