<?php

$config = require dirname(__DIR__, 2).'/config/session.php';
$config['driver'] = 'file';
$config['block_store'] = 'storefront_locks';
$config['cookie'] = 'metis_storefront_session';
$config['files'] = storage_path('framework/sessions');

return $config;
