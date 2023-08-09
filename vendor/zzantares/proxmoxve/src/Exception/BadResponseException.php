<?php

/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

namespace ProxmoxVE\Exception;

/**
 * BadResponseException class. Is the exception thrown when proxmox
 * return status_code >= 400, thus the ProxmoxVE API client can not be used.
 *
 * @author César Muñoz <zzantares@gmail.com>
 */
class BadResponseException extends \RuntimeException
{
}
