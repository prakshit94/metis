<?php

// Keep the domain implementation shared with the admin app: no duplicated order engine.
$loader = require dirname(__DIR__, 2).'/vendor/autoload.php';
$loader->addPsr4('Storefront\\', dirname(__DIR__).'/app/');
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

return $loader;
