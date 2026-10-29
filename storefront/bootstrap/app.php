<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'password_confirmation', 'code']);
    })
    ->create();

$app->useDatabasePath(dirname(__DIR__, 2).'/database');

// Package discovery must use the same installed dependencies as the autoloader.
$app->extend(\Illuminate\Foundation\PackageManifest::class, function ($manifest) {
    $manifest->vendorPath = dirname(__DIR__, 2).'/vendor';
    return $manifest;
});

return $app;
