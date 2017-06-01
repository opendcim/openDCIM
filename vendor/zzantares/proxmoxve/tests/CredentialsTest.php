<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE;

/**
 * @author César Muñoz <zzantares@gmail.com>
 */
class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->hostname = 'mypve.tld';
        $this->username = 'root';
        $this->password = 'secret123';
        $this->realm = 'pam';
        $this->port = '8006';

        $this->credentials = new Credentials(
            $this->hostname,
            $this->username,
            $this->password,
            $this->realm,
            $this->port
        );
    }


    public function testGetters()
    {
        $this->assertSame($this->hostname, $this->credentials->getHostname());
        $this->assertSame($this->username, $this->credentials->getUsername());
        $this->assertSame($this->password, $this->credentials->getPassword());
        $this->assertSame($this->realm, $this->credentials->getRealm());
        $this->assertSame($this->port, $this->credentials->getPort());
    }


    public function testApiUrlIsFormedCorrectly()
    {
        $apiUrl = $this->credentials->getApiUrl();
        $url = 'https://' . $this->hostname . ':' . $this->port . '/api2/json';

        $this->assertEquals($apiUrl, $url);
    }


    /*
     * Need to mock up $token->login() function.
     */
}
