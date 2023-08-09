<?php

namespace ProxmoxVE\CustomClasses;

class MagicCredentials
{
    public function __get($property)
    {
        switch($property) {
            case 'hostname':
                return 'the.proxmox.tld';
                break;

            case 'username':
                return 'root';
                break;

            case 'password':
                return 'Magic World';
                break;

            default:
                return null;
        }
    }
}

