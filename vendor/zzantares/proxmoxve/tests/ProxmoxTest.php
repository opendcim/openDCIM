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
class ProxmoxTest extends TestCase
{
    /**
     * @expectedException \ProxmoxVE\Exception\MalformedCredentialsException
     */
    public function testExceptionIsThrownIfBadParamsPassed()
    {
        new Proxmox('bad param');
    }


    /**
     * @expectedException \ProxmoxVE\Exception\MalformedCredentialsException
     */
    public function testExceptionIsThrownWhenNonAssociativeArrayIsGivenAsCredentials()
    {
        new Proxmox([
            'root', 'So Bruce Wayne is alive? or did he died in the explosion?',
        ]);
    }


    /**
     * @expectedException \ProxmoxVE\Exception\MalformedCredentialsException
     */
    public function testExceptionIsThrownWhenIncompleteCredentialsArrayIsPassed()
    {
        new Proxmox([
            'username' => 'root',
            'password' => 'The NSA is watching us! D=',
        ]);
    }


    /**
     * @expectedException \ProxmoxVE\Exception\MalformedCredentialsException
     */
    public function testExceptionIsThrownWhenWrongCredentialsObjectIsPassed()
    {
        $credentials = new CustomClasses\Person('Harry Potter', 13);
        new Proxmox($credentials);
    }


    /**
     * @expectedException \ProxmoxVE\Exception\MalformedCredentialsException
     */
    public function testExceptionIsThrownWhenIncompleteCredentialsObjectIsPassed()
    {
        $credentials = new CustomClasses\IncompleteCredentials("user", "and that's it");
        new Proxmox($credentials);
    }


    /**
     * @expectedException \ProxmoxVE\Exception\MalformedCredentialsException
     */
    public function testExceptionIsThrownWhenProtectedCredentialsObjectIsPassed()
    {
        $credentials = new CustomClasses\ProtectedCredentials('host', 'user', 'pass');
        new Proxmox($credentials);
    }


    /**
     * @expectedException \GuzzleHttp\Exception\RequestException
     */
    public function testProxmoxExceptionIsNotThrownWhenUsingMagicCredentialsObject()
    {
        $credentials = new CustomClasses\MagicCredentials();
        new Proxmox($credentials);
    }


    public function testGetCredentialsWithAllValues()
    {
        $ids = [
            'hostname' => 'some.proxmox.tld',
            'username' => 'root',
            'password' => 'I was here',
        ];

        $fakeAuthToken = new AuthToken('csrf', 'ticket', 'username');
        $proxmox = $this->getMockProxmox('login', $fakeAuthToken);
        $proxmox->setCredentials($ids);

        $credentials = $proxmox->getCredentials();

        $this->assertEquals($credentials->getHostname(), $ids['hostname']);
        $this->assertEquals($credentials->getUsername(), $ids['username']);
        $this->assertEquals($credentials->getPassword(), $ids['password']);
        $this->assertEquals($credentials->getRealm(), 'pam');
        $this->assertEquals($credentials->getPort(), '8006');
        $this->assertEquals($credentials->getSystem(), 'pve');
    }


    public function testCredentialsWithMailGatewaySystem()
    {
        $ids = [
            'hostname' => 'some.proxmox.tld',
            'username' => 'root',
            'password' => 'I was here',
            'system' => 'pmg',
        ];

        $fakeAuthToken = new AuthToken('csrf', 'ticket', 'username');
        $proxmox = $this->getMockProxmox('login', $fakeAuthToken);
        $proxmox->setCredentials($ids);

        $credentials = $proxmox->getCredentials();

        $this->assertEquals($credentials->getSystem(), 'pmg');
    }


    /**
     * @expectedException \Exception
     */
    public function testUnresolvedHostnameThrowsException()
    {
        $credentials = [
            'hostname' => 'proxmox.example.tld',
            'username' => 'user',
            'password' => 'pass',
        ];

        new Proxmox($credentials);
    }


    /**
     * @expectedException \ProxmoxVE\Exception\AuthenticationException
     */
    public function testLoginErrorThrowsException()
    {
        $credentials = [
            'hostname' => 'proxmox.server.tld',
            'username' => 'are not',
            'password' => 'valid folks!',
        ];

        $httpClient = $this->getMockHttpClient(false); // Simulate failed login

        new Proxmox($credentials, null, $httpClient);
    }


    public function testGetAndSetResponseType()
    {
        $proxmox = $this->getProxmox(null);
        $this->assertEquals($proxmox->getResponseType(), 'array');

        $proxmox->setResponseType('json');
        $this->assertEquals($proxmox->getResponseType(), 'json');

        $proxmox->setResponseType('html');
        $this->assertEquals($proxmox->getResponseType(), 'html');

        $proxmox->setResponseType('extjs');
        $this->assertEquals($proxmox->getResponseType(), 'extjs');

        $proxmox->setResponseType('text');
        $this->assertEquals($proxmox->getResponseType(), 'text');

        $proxmox->setResponseType('png');
        $this->assertEquals($proxmox->getResponseType(), 'png');

        $proxmox->setResponseType('pngb64');
        $this->assertEquals($proxmox->getResponseType(), 'pngb64');

        $proxmox->setResponseType('object');
        $this->assertEquals($proxmox->getResponseType(), 'object');

        $proxmox->setResponseType('other');
        $this->assertEquals($proxmox->getResponseType(), 'array');
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetResourceWithBadParamsThrowsException()
    {
        $proxmox = $this->getProxmox(null);
        $proxmox->get('/someResource', 'wrong params here');
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateResourceWithBadParamsThrowsException()
    {
        $proxmox = $this->getProxmox(null);
        $proxmox->create('/someResource', 'wrong params here');
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetResourceWithBadParamsThrowsException()
    {
        $proxmox = $this->getProxmox(null);
        $proxmox->set('/someResource', 'wrong params here');
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDeleteResourceWithBadParamsThrowsException()
    {
        $proxmox = $this->getProxmox(null);
        $proxmox->delete('/someResource', 'wrong params here');
    }


    public function testGetResource()
    {
        $fakeResponse = <<<'EOD'
{"data":[{"disk":940244992,"cpu":0.000998615325210486,"maxdisk":5284429824,"maxmem":1038385152,"node":"office","maxcpu":1,"level":"","uptime":3296027,"id":"node/office","type":"node","mem":311635968}]}
EOD;
        $proxmox = $this->getProxmox($fakeResponse);

        $this->assertEquals($proxmox->get('/nodes'), json_decode($fakeResponse, true));
    }
}
