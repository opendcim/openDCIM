<?php

namespace ProxmoxVE\CustomClasses;

class ProtectedCredentials
{
    protected $hostname;
    protected $username;
    protected $password;

    public function __construct($host, $user, $pass)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $pass;
    }
}

