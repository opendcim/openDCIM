<?php

namespace ProxmoxVE\CustomCredentials;

class MethodsOptCredentials
{
    private $hostname;
    private $username;
    private $password;


    public function __construct($host, $user, $passwd)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $passwd;
    }


    public function getHostname()
    {
        return $this->hostname;
    }
    

    public function getUsername()
    {
        return $this->username;
    }


    public function getPassword()
    {
        return $this->password;
    }

}

