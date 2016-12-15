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
class ProxmoxVETest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSetAuthToken()
    {
        $token = new AuthToken('csrf', 'ticket', 'owner');
        $pve = new ProxmoxVE($token);
        $this->assertSame($token, $pve->getAuthToken());

        $newToken = new AuthToken('other csrf', 'other ticket', 'other owner');
        $pve->setAuthToken($newToken);
        $this->assertSame($newToken, $pve->getAuthToken());
    }


    /* Add test to get, post, put and delete functions.
     *
     * Need to create mocks but they are protected, we can do it through
     * reflection but is worth it? we should do it for sake of testing?
     */

}

