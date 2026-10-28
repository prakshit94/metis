<?php

// Structural permission audit only: no requests, application data or business workflows are executed.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Illuminate\Contracts\Http\Kernel::class);
$app['router']->getRoutes()->refreshNameLookups();
$catalog = Database\Seeders\RolesAndPermissionsSeeder::permissions();
$known = array_fill_keys($catalog, true);
$errors = [];
$gates = 0;
foreach ($app['router']->getRoutes() as $route) {
    foreach ($route->gatherMiddleware() as $middleware) {
        if (!is_string($middleware) || !str_contains($middleware, 'permission:')) continue;
        $permissions = explode(',', explode('permission:', $middleware, 2)[1])[0];
        foreach (explode('|', $permissions) as $permission) {
            $gates++;
            if (!isset($known[$permission])) $errors[] = $route->uri().' references missing '.$permission;
        }
    }
}
foreach (['app', 'resources/views', 'routes'] as $directory) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/'.$directory));
    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php' || str_contains($file->getPathname(), '.orig/')) continue;
        preg_match_all('/(?:->can|->cannot|@can|@cannot)\([\'\"]([a-zA-Z0-9_.-]+)[\'\"]/', file_get_contents($file->getPathname()), $matches);
        foreach ($matches[1] as $permission) {
            if (str_ends_with($permission, '-') || str_ends_with($permission, '.')) continue; // Existing dynamic prefixes.
            if (!isset($known[$permission])) $errors[] = $file->getPathname().' references missing '.$permission;
        }
    }
}
foreach (App\Services\FeaturePermissions::legacy() as $action => $sources) {
    if (!isset($known[$action])) $errors[] = 'Unknown new action '.$action;
    foreach ($sources as $source) if (!isset($known[$source])) $errors[] = 'Unknown legacy source '.$source;
}
if (count($catalog) !== count(array_unique($catalog))) $errors[] = 'Duplicate catalog entries';
if ($errors) {fwrite(STDERR,implode("\n",array_unique($errors))."\n");exit(1);}
echo 'PASS: '.count($catalog).' unique permissions cover '.$gates." route gates and all literal application/template permission checks.\n";
echo "PASS: all compatibility sources and action permissions exist in the seeder catalog.\n";
