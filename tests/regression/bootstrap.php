<?php

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

// All database tests use a uniquely named disposable schema. Never use the configured application DB.
require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error) {
    fwrite(STDERR, get_class($error).': '.$error->getMessage().' at '.$error->getFile().':'.$error->getLine().PHP_EOL);
    exit(1);
});
$socket = getenv('METIS_TEST_MYSQL_SOCKET') ?: '/tmp/metis-audit-mysql/mysql.sock';
$sharedDatabase = getenv('METIS_EXISTING_TEST_DB');
if ($sharedDatabase && ! preg_match('/^metis_regression_[0-9]+_[a-f0-9]{6}$/', $sharedDatabase)) {
    throw new RuntimeException('Invalid disposable schema name');
}
$testDatabase = $sharedDatabase ?: 'metis_regression_'.getmypid().'_'.bin2hex(random_bytes(3));
$pdo = new PDO('mysql:unix_socket='.$socket, getenv('METIS_TEST_MYSQL_USER') ?: 'root', getenv('METIS_TEST_MYSQL_PASSWORD') ?: '');
if (! $sharedDatabase) {
    $pdo->exec('CREATE DATABASE `'.$testDatabase.'`');
}
config(['database.default' => 'regression', 'database.connections.regression' => [
    'driver' => 'mysql', 'unix_socket' => $socket, 'database' => $testDatabase,
    'username' => getenv('METIS_TEST_MYSQL_USER') ?: 'root', 'password' => getenv('METIS_TEST_MYSQL_PASSWORD') ?: '',
    'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
], 'cache.default' => 'array', 'session.driver' => 'array', 'queue.default' => 'sync',
    'activitylog.enabled' => false, 'audit.enabled' => false, 'app.debug' => false]);
// Providers register named rate limiters during boot, before test configuration.
// Keep those registrations but move the eagerly resolved limiter onto isolated memory.
$testLimiter = app(RateLimiter::class);
(new ReflectionProperty($testLimiter, 'cache'))->setValue($testLimiter, app('cache')->store('array'));
config(['permission.cache.store' => 'array', 'permission.cache.key' => $testDatabase.'.permissions']);
app(PermissionRegistrar::class)->initializeCache();

if (! $sharedDatabase) {
    register_shutdown_function(function () use ($pdo, $testDatabase) {
        DB::disconnect('regression');
        $pdo->exec('DROP DATABASE IF EXISTS `'.$testDatabase.'`');
    });
}
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('FAIL: '.$message);
    }
    echo 'PASS: '.$message."\n";
}
function invalid(Closure $operation, string $message): void
{
    try {
        $operation();
    } catch (ValidationException $error) {
        check(true, $message);

        return;
    }
    throw new RuntimeException('FAIL (accepted invalid input): '.$message);
}
function requestFor(array $data, string $method = 'POST'): Request
{
    $request = Request::create('/', $method, $data, [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(fn () => auth()->user());

    return $request;
}
