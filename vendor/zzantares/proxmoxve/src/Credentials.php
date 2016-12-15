<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE;

/**
 * Credentials class. It will handle all related data used to connect to a
 * promxox server.
 *
 * @author César Muñoz <zzantares@gmail.com>
 */
class Credentials
{
    /**
     * Proxmox hostname or IP (e.g. 'proxmox.mydomain.com').
     *
     * @var string
     */
    private $hostname;


    /**
     * Username used to connect to the Proxmox server.
     *
     * @var string
     */
    private $username;


    /**
     * Password used to connect to the Proxmox server.
     */
    private $password;


    /**
     * Realm used to login to the Proxmox server (e.g. 'pam' or 'pve').
     *
     * @var string
     */
    private $realm;


    /**
     * Port where Proxmox UI is listening on (e.g. '8006').
     *
     * @var string
     */
    private $port;


    /**
     * Constructor.
     *
     * @param string $hostname The proxmox hostname or IP without the "https://"
     *                         stuff.
     * @param string $username The username used to connect to the proxmox UI.
     * @param string $password The password used to connect to the proxmox UI.
     * @param string $realm    The realm in wich the username and password are
     *                         valid. If no value is passed 'pam' will be used.
     * @param string $port     The proxmox port in wich proxmox is listening on.
     *                         If no value is passed '8006' will be assumed.
     */
    public function __construct(
        $hostname,
        $username,
        $password,
        $realm = 'pam',
        $port = '8006'
    ) {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->realm = $realm;
        $this->port = $port;
    }


    /**
     * Returns the proxmox hostname associated to this AuthToken.
     *
     * @return string The proxmox hostname.
     */
    public function getHostname()
    {
        return $this->hostname;
    }


    /**
     * Returns the proxmox username associated with this AuthToken.
     *
     * @return string The proxmox username.
     */
    public function getUsername()
    {
        return $this->username;
    }


    /**
     * Returns the proxmox password associated with this AuthToken.
     *
     * @return string The proxmox password.
     */
    public function getPassword()
    {
        return $this->password;
    }


    /**
     * Returns the proxmox realm used in this AuthToken.
     *
     * @return string The proxmox realm without the @ symbol.
     */
    public function getRealm()
    {
        return $this->realm;
    }


    /**
     * Returns the port in wich proxmox is listening.
     *
     * @return string The proxmox port.
     */
    public function getPort()
    {
        return $this->port;
    }


    /**
     * Returns the base URL used to interact with the ProxmoxVE API.
     *
     * @return string The proxmox API URL.
     */
    public function getApiUrl()
    {
        return 'https://' . $this->hostname . ':' . $this->port . '/api2/json';
    }


    /**
     * Attempts to login using this credentials, if succeeded will return the
     * AuthToken used in all requests.
     *
     * @return \ProxmoxVE\AuthToken|bool If login fails will return
     *                                             false otherwise will return
     *                                             the AuthToken.
     */
    public function login()
    {
        $params = array(
            'username' => $this->username,
            'password' => $this->password,
            'realm' => $this->realm,
        );

        $params = http_build_query($params);
        $url = $this->getApiUrl() . '/access/ticket';

        $response = ProxmoxVE::request($url, 'POST', $params);

        $login = json_decode($response, true);

        if (!$login) { // Failed authentication
            return false;
        }

        return new AuthToken(
            $login['data']['CSRFPreventionToken'],
            $login['data']['ticket'],
            $login['data']['username']
        );
    }
}
