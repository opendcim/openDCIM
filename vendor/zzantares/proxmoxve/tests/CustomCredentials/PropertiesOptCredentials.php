<?php

namespace ProxmoxVE\CustomCredentials;

class PropertiesOptCredentials
{
    public $hostname;
    public $username;
    public $password;


    public function __construct($host, $user, $passwd)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $passwd;
    }

}


