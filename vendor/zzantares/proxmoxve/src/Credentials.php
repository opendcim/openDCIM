<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE;

use ProxmoxVE\Exception\MalformedCredentialsException;

/**
 * Credentials class. Handles all related data used to connect to a Proxmox
 * server.
 *
 * @author César Muñoz <zzantares@gmail.com>
 */
class Credentials
{
    /**
     * @var string The Proxmox hostname (or IP address) to connect to.
     */
    private $hostname;

    /**
     * @var string The credentials username used to authenticate with Proxmox.
     */
    private $username;

    /**
     * @var string The credentials password used to authenticate with Proxmox.
     */
    private $password;

    /**
     * @var string The authentication realm (defaults to "pam" if not provided).
     */
    private $realm;

    /**
     * @var string The Proxmox port (defaults to "8006" if not provided).
     */
    private $port;

    /**
     * @var string The Proxmox system being used (defaults to "pve" if not provided).
     */
    private $system;

    /**
     * Construct.
     *
     * @param array|object $credentials This needs to have 'hostname',
     *                                  'username' and 'password' defined.
     */
    public function __construct($credentials)
    {
        // Get credentials object in valid array form
        $credentials = $this->parseCustomCredentials($credentials);

        if (!$credentials) {
            $error = 'PVE API needs a credentials object or an array.';
            throw new MalformedCredentialsException($error);
        }

        $this->hostname = $credentials['hostname'];
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
        $this->realm = $credentials['realm'];
        $this->port = $credentials['port'];
        $this->system = $credentials['system'];
    }


    /**
     * Gives back the string representation of this credentials object.
     *
     * @return string Credentials data in a single string.
     */
    public function __toString()
    {
        return sprintf(
            '[Host: %s:%s], [Username: %s@%s].',
            $this->hostname,
            $this->port,
            $this->username,
            $this->realm
        );
    }


    /**
     * Returns the base URL used to interact with the ProxmoxVE API.
     *
     * @return string The proxmox API URL.
     */
    public function getApiUrl()
    {
        return 'https://' . $this->hostname . ':' . $this->port . '/api2';
    }


    /**
     * Gets the hostname configured in this credentials object.
     *
     * @return string The hostname in the credentials.
     */
    public function getHostname()
    {
        return $this->hostname;
    }


    /**
     * Gets the username given to this credentials object.
     *
     * @return string The username in the credentials.
     */
    public function getUsername()
    {
        return $this->username;
    }


    /**
     * Gets the password set in this credentials object.
     *
     * @return string The password in the credentials.
     */
    public function getPassword()
    {
        return $this->password;
    }


    /**
     * Gets the realm used in this credentials object.
     *
     * @return string The realm in this credentials.
     */
    public function getRealm()
    {
        return $this->realm;
    }


    /**
     * Gets the port configured in this credentials object.
     *
     * @return string The port in the credentials.
     */
    public function getPort()
    {
        return $this->port;
    }


    /**
     * Gets the system configured in this credentials object.
     *
     * @return string The port in the credentials.
     */
    public function getSystem()
    {
        return $this->system;
    }


    /**
     * Given the custom credentials object it will try to find the required
     * values to use it as the proxmox credentials, this can be an object with
     * accesible properties, getter methods or an object that uses '__get' to
     * access properties dinamically.
     *
     * @param mixed $credentials
     *
     * @return array|null If credentials are found they are returned as an
     *                    associative array, returns null if object can not be
     *                    used as a credentials provider.
     */
    public function parseCustomCredentials($credentials)
    {
        if (is_array($credentials)) {
            $requiredKeys = ['hostname', 'username', 'password'];
            $credentialsKeys = array_keys($credentials);

            $found = count(array_intersect($requiredKeys, $credentialsKeys));

            if ($found != count($requiredKeys)) {
                return null;
            }

            // Set default realm and port if are not in the array.
            if (!isset($credentials['realm'])) {
                $credentials['realm'] = 'pam';
            }

            if (!isset($credentials['port'])) {
                $credentials['port'] = '8006';
            }

            if (!isset($credentials['system'])) {
                $credentials['system'] = 'pve';
            }

            return $credentials;
        }

        if (!is_object($credentials)) {
            return null;
        }

        // Trying to find variables
        $objectProperties = array_keys(get_object_vars($credentials));
        $requiredProperties = ['hostname', 'username', 'password'];

        // Needed properties exists in the object?
        $found = count(array_intersect($requiredProperties, $objectProperties));
        if ($found == count($requiredProperties)) {
            $realm = in_array('realm', $objectProperties)
                ? $credentials->realm
                : 'pam';

            $port = in_array('port', $objectProperties)
                ? $credentials->port
                : '8006';

            $system = in_array('system', $objectProperties)
                ? $credentials->system
                : 'pve';

            return [
                'hostname' => $credentials->hostname,
                'username' => $credentials->username,
                'password' => $credentials->password,
                'realm' => $realm,
                'port' => $port,
                'system' => $system,
            ];
        }


        // Trying to find getters
        $objectMethods = get_class_methods($credentials);
        $requiredMethods = ['getHostname', 'getUsername', 'getPassword'];

        // Needed functions exists in the object?
        $found = count(array_intersect($requiredMethods, $objectMethods));
        if ($found == count($requiredMethods)) {
            $realm = method_exists($credentials, 'getRealm')
                ? $credentials->getRealm()
                : 'pam';

            $port = method_exists($credentials, 'getPort')
                ? $credentials->getPort()
                : '8006';

            $system = method_exists($credentials, 'getSystem')
                ? $credentials->getSystem()
                : 'pve';

            return [
                'hostname' => $credentials->getHostname(),
                'username' => $credentials->getUsername(),
                'password' => $credentials->getPassword(),
                'realm' => $realm,
                'port' => $port,
                'system' => $system,
            ];
        }

        // Get properties of object using magic method __get
        if (in_array('__get', $objectMethods)) {
            $hasHostname = $credentials->hostname;
            $hasUsername = $credentials->username;
            $hasPassword = $credentials->password;

            if ($hasHostname and $hasUsername and $hasPassword) {
                return [
                    'hostname' => $credentials->hostname,
                    'username' => $credentials->username,
                    'password' => $credentials->password,
                    'realm' => $credentials->realm ?: 'pam',
                    'port' => $credentials->port ?: '8006',
                    'system' => $credentials->system ?: 'pve',
                ];
            }
        }

        return null;
    }
}
