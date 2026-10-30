<?php

// The storefront shares vendor but has its own compiled provider manifests.
// Clear them before booting Artisan, otherwise removed providers prevent boot.
require dirname(__DIR__).'/storefront/bootstrap/autoload.php';

$app = require dirname(__DIR__).'/storefront/bootstrap/app.php';

foreach ([$app->getCachedConfigPath(), $app->getCachedServicesPath(), $app->getCachedPackagesPath()] as $path) {
    if (is_file($path) && ! unlink($path)) {
        throw new RuntimeException('Unable to clear storefront bootstrap cache: '.$path);
    }
}
