<?php

// Domain events must invalidate the same caches as the admin application.
$config = require dirname(__DIR__, 2).'/config/cache.php';
$config['stores']['file']['path'] = dirname(__DIR__, 2).'/storage/framework/cache/data';
$config['stores']['file']['lock_path'] = dirname(__DIR__, 2).'/storage/framework/cache/data';
$config['stores']['storefront_locks'] = ['driver' => 'file', 'path' => storage_path('framework/cache/data'), 'lock_path' => storage_path('framework/cache/data')];

return $config;
