<?php

namespace ProxmoxVE\CustomCredentials;

class PropertiesCredentials
{
    public $hostname;
    public $username;
    public $password;
    public $realm;
    public $port;


    public function __construct($host, $user, $passwd, $realm, $port)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $passwd;
        $this->realm = $realm;
        $this->port = $port;
    }

}

