<?php
/**
 * This file is part of the ProxmoxVE PHP API wrapper library (unofficial).
 *
 * @copyright 2014 César Muñoz <zzantares@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License.
 */

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    echo "Please install project running:\n\tcomposer install\n\n";
    exit("composer what?\n\thttps://getcomposer.org/download/\n\n");
}

$loader = include $autoload;
$loader->addPsr4('ProxmoxVE\\', __DIR__);
$loader->addPsr4('ProxmoxVE\\CustomClasses\\', __DIR__ . '/CustomClasses');

