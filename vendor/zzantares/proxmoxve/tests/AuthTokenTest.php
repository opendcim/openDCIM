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
class AuthTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $csrf = '4EEC61E2:lwk7od06fa1+DcPUwBTXCcndyAY';
        $ticket = 'PVE:root@pam:4EEC61E2::rsKoApxDTLYPn6H3NNT6iP2mv...';
        $username = 'root@pam';

        $token = new AuthToken($csrf, $ticket, $username);

        $this->assertSame($csrf, $token->getCsrf());
        $this->assertSame($ticket, $token->getTicket());
        $this->assertSame($username, $token->getUsername());

        $this->assertGreaterThanOrEqual($token->getTimestamp(), time());
        $this->assertTrue($token->isValid());
    }

}
