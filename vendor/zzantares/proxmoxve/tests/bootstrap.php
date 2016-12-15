<?php

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    echo "Please install project runing:\n\tcomposer install\n\n";
    exit("composer what?\n\thttps://getcomposer.org/download/\n\n");
}

$loader = include $autoload;
$loader->addPsr4('ProxmoxVE\\CustomCredentials\\', __DIR__ . '/CustomCredentials');
