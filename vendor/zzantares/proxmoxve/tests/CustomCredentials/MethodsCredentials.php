<?php

namespace ProxmoxVE\CustomCredentials;

class MethodsCredentials
{
    private $hostname;
    private $username;
    private $password;
    private $realm;
    private $port;


    public function __construct($host, $user, $passwd, $realm, $port)
    {
        $this->hostname = $host;
        $this->username = $user;
        $this->password = $passwd;
        $this->realm = $realm;
        $this->port = $port;
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


    public function getRealm()
    {
        return $this->realm;
    }


    public function getPort()
    {
        return $this->port;
    }

}

