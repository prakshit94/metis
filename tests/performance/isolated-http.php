<?php

// Included only by the synthetic query regression fixture. Never run directly.
if (! isset($fixtureDatabase, $adminPdo, $sourceDatabase, $config) || ! preg_match('/^metis_query_test_[a-f0-9]{12}$/', $fixtureDatabase)) {
    throw new RuntimeException('An isolated regression database is required.');
}
if (app()->configurationIsCached()) {
    throw new RuntimeException('Clear cached configuration before isolated HTTP tests; cached connections bypass environment isolation.');
}
$root = dirname(__DIR__, 2);
$runLabel = getenv('METIS_K6_RUN_LABEL') ?: '';
if (! preg_match('/^[a-z0-9-]{0,40}$/', $runLabel)) {
    throw new RuntimeException('Invalid isolated run label.');
}
$output = $root.'/tests/performance/results/'.date('Y-m-d').'-isolated'.(getenv('K6_PROFILE') === 'stress' ? '-stress' : '').($runLabel ? '-'.$runLabel : '');
if (! is_dir($output)) {
    mkdir($output, 0775, true);
}
// Copy missing definitions only. No application data, carrier credentials, or tokens.
foreach ($adminPdo->query("SHOW TABLES FROM `{$sourceDatabase}`")->fetchAll(PDO::FETCH_COLUMN) as $table) {
    $quoted = str_replace('`', '``', $table);
    $adminPdo->exec("CREATE TABLE IF NOT EXISTS `{$fixtureDatabase}`.`{$quoted}` LIKE `{$sourceDatabase}`.`{$quoted}`");
}
$password = bin2hex(random_bytes(20));
Illuminate\Support\Facades\DB::table('users')->where('id', 1)->update([
    'password' => password_hash($password, PASSWORD_BCRYPT), 'email' => 'k6@example.invalid',
    'is_active' => true, 'suspended_until' => null,
]);
Illuminate\Support\Facades\DB::table('roles')->insert(['id' => 2, 'name' => 'Super Admin', 'guard_name' => 'web', 'team_id' => null]);
Illuminate\Support\Facades\DB::table('model_has_roles')->where('model_id', 1)->delete();
Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
    'role_id' => 2, 'model_id' => 1, 'model_type' => App\Modules\Users\Models\User::class, 'team_id' => null,
]);
Illuminate\Support\Facades\DB::table('model_has_roles')->where('model_id', 2)->delete();
Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
    'role_id' => 2, 'model_id' => 2, 'model_type' => App\Modules\Users\Models\User::class, 'team_id' => null,
]);
foreach (['complaints.view-all', 'complaints.view', 'complaints.delete'] as $permission) {
    $permissionId = Illuminate\Support\Facades\DB::table('permissions')->insertGetId(['name' => $permission, 'guard_name' => 'web']);
    Illuminate\Support\Facades\DB::table('role_has_permissions')->insert(['role_id' => 2, 'permission_id' => $permissionId]);
}
$apiToken = App\Modules\Users\Models\User::findOrFail(2)->createToken('isolated-k6')->plainTextToken;
$env = array_merge(getenv(), [
    'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:18080',
    'DB_CONNECTION' => 'mysql', 'DB_HOST' => $config['host'], 'DB_PORT' => (string) $config['port'],
    'DB_DATABASE' => $fixtureDatabase, 'DB_USERNAME' => $config['username'], 'DB_PASSWORD' => $config['password'],
    'DB_URL' => '', 'SESSION_DRIVER' => 'database', 'SESSION_CONNECTION' => 'mysql',
    'CACHE_STORE' => 'database', 'DB_CACHE_CONNECTION' => 'mysql', 'DB_QUEUE_CONNECTION' => 'mysql',
    'SANCTUM_STATEFUL_DOMAINS' => '127.0.0.1:18080',
    'QUEUE_CONNECTION' => 'database', 'MAIL_MAILER' => 'log', 'LOG_CHANNEL' => 'single', 'SESSION_SECURE_COOKIE' => 'false',
    'PHP_CLI_SERVER_WORKERS' => '4', 'K6_BASE_URL' => 'http://127.0.0.1:18080',
    'K6_EMAIL' => 'k6@example.invalid', 'K6_PASSWORD' => $password,
]);
$existingSocket = @fsockopen('127.0.0.1', 18080, $errno, $error, 0.2);
if ($existingSocket) {
    fclose($existingSocket);
    throw new RuntimeException('Port 18080 is occupied; no HTTP requests were sent to that server.');
}
$server = proc_open(['setsid', PHP_BINARY, '-d', 'opcache.enable_cli=1', '-S', '127.0.0.1:18080', '-t', $root.'/public', $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
    [0 => ['file', '/dev/null', 'r'], 1 => ['file', $output.'/server.log', 'a'], 2 => ['file', $output.'/server.log', 'a']], $pipes, $root.'/public', $env);
if (! is_resource($server)) {
    throw new RuntimeException('Cannot start isolated HTTP server.');
}
$serverPid = proc_get_status($server)['pid'];
try {
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $socket = @fsockopen('127.0.0.1', 18080, $errno, $error, 0.1);
        if ($socket) { fclose($socket); $ready = true; break; }
        usleep(100000);
    }
    if (! $ready) { throw new RuntimeException('Isolated HTTP server did not start.'); }
    $failures = [];
    foreach (explode(',', getenv('METIS_K6_PROFILES') ?: 'site,apis') as $profile) {
        if (! in_array($profile, ['site', 'apis'], true)) { throw new RuntimeException('Unknown k6 profile.'); }
        $env['K6_API_TOKEN'] = $profile === 'apis' ? $apiToken : '';
        echo "Running isolated k6 {$profile}; results: {$output}\n";
        file_put_contents($output.'/'.$profile.'.log', '');
        $process = proc_open(['k6', 'run', '--quiet', '--summary-export='.$output.'/'.$profile.'-summary.json', '--out', 'json='.$output.'/'.$profile.'-samples.ndjson', 'tests/performance/k6/'.$profile.'.js'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', $output.'/'.$profile.'.log', 'a'], 2 => ['file', $output.'/'.$profile.'.log', 'a']], $pipes, $root, $env);
        if (! is_resource($process)) { throw new RuntimeException('Cannot start k6.'); }
        $metrics = fopen($output.'/'.$profile.'-resources.csv', 'w');
        fputcsv($metrics, ['elapsed_seconds', 'php_cpu_ticks', 'php_rss_pages']);
        $started = microtime(true);
        do {
            $ticks = 0;
            $rss = 0;
            foreach (glob('/proc/[0-9]*/stat') as $statFile) {
                $stat = @file_get_contents($statFile);
                if ($stat === false) { continue; }
                $fields = explode(' ', substr($stat, strrpos($stat, ')') + 2));
                if ((int) ($fields[2] ?? 0) === $serverPid) {
                    $ticks += (int) $fields[11] + (int) $fields[12];
                    $rss += (int) $fields[21];
                }
            }
            fputcsv($metrics, [round(microtime(true) - $started, 3), $ticks, $rss]);
            $status = proc_get_status($process);
            if ($status['running']) { usleep(1000000); }
        } while ($status['running']);
        fclose($metrics);
        $code = $status['exitcode'];
        proc_close($process);
        if ($code !== 0) { $failures[] = $profile.': '.$code; }
        echo "k6 {$profile} exit code: {$code}\n";
        echo 'Fixture login outcomes: '.json_encode(Illuminate\Support\Facades\DB::table('login_histories')->select('status', 'failure_reason')->get()).PHP_EOL;
    }
    if ($failures) { throw new RuntimeException('k6 profiles failed: '.implode(', ', $failures)); }
} finally {
    if (function_exists('posix_kill')) { posix_kill(-$serverPid, SIGTERM); }
    proc_terminate($server);
    proc_close($server);
}
