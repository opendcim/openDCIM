<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE\Exception;

/**
 * AuthenticationException class. Is the exception thrown when credentials
 * passed are not correct, thus the ProxmoxVE API client can not be used.
 *
 * @author César Muñoz <zzantares@gmail.com>
 */
class AuthenticationException extends \RuntimeException
{
}
