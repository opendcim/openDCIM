<?php

namespace ProxmoxVE\CustomCredentials;

class BadCredentials
{
    private $hostname;
    private $username;
    private $password;


    public function __construct($host, $user, $pass)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $pass;
    }
}
