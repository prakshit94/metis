<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
Artisan::call('route:list', ['--json' => true]);
$routes = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
foreach ($routes as $route) {
    if (! str_starts_with($route['action'], 'App\\')) {
        continue;
    }
    [$class, $method] = array_pad(explode('@', $route['action']), 2, '__invoke');
    if (! method_exists($class, $method)) {
        $errors[] = $route['uri'].' targets a missing handler';

        continue;
    }
    preg_match_all('/\{([^}?]+)\??\}/', $route['uri'], $matches);
    foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin() || ! is_subclass_of($type->getName(), Model::class)) {
            continue;
        }
        if (! in_array($parameter->getName(), $matches[1], true) && ! in_array(Str::snake($parameter->getName()), $matches[1], true)) {
            $errors[] = $route['uri'].' does not bind $'.$parameter->getName();
        }
    }
}
if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}
echo 'PASS: '.count($routes).' routes have existing handlers and compatible model parameter names'.PHP_EOL;
