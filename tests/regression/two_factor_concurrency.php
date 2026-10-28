<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Users\Models\User;
use App\Services\Totp;
use App\Services\TwoFactorService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Only security dependencies: this test never creates or mutates business records.
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('password');
    $table->boolean('is_active')->default(true);
    $table->timestamp('password_changed_at')->nullable();
    $table->timestamp('suspended_until')->nullable();
    $table->softDeletes();
});
$migration = require dirname(__DIR__, 2).'/database/migrations/2026_10_28_100000_create_two_factor_security_tables.php';
$migration->up();
$emailMigration = require dirname(__DIR__, 2).'/database/migrations/2026_10_28_110000_add_email_two_factor_authentication.php';
$emailMigration->up();
$id = DB::table('users')->insertGetId(['name' => 'Race fixture', 'email' => 'race@example.invalid', 'password' => bcrypt('Example123!')]);
$user = User::findOrFail($id);
$secret = app(Totp::class)->secret();
$recovery = bin2hex(random_bytes(10));
DB::table('user_two_factor_settings')->insert(['user_id' => $id, 'secret' => Crypt::encryptString($secret), 'confirmed_at' => now(), 'version' => str_repeat('a', 64), 'recovery_hashes' => json_encode([hash('sha256', $recovery)])]);
$service = app(TwoFactorService::class);
function raceCodes(string $code): array
{
    global $user, $service, $testDatabase;
    $workers = [];
    foreach (['web', 'mobile'] as $channel) {
        $challenge = $service->begin($user, requestFor([]), $channel);
        $environment = getenv();
        $environment['METIS_EXISTING_TEST_DB'] = $testDatabase;
        $process = proc_open([PHP_BINARY, __DIR__.'/two_factor_worker.php'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, null, $environment);
        fwrite($pipes[0], json_encode(compact('challenge', 'code', 'channel')));
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    $results = [];
    foreach ($workers as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        check(proc_close($process) === 0, 'concurrent verifier completes without database failure: '.$error);
        $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['verified'];
    }

    return $results;
}
check(count(array_filter(raceCodes($recovery))) === 1, 'two simultaneous channels consume one recovery code exactly once');
$code = app(Totp::class)->code($secret, intdiv(now()->timestamp, 30));
check(count(array_filter(raceCodes($code))) === 1, 'two simultaneous channels consume one TOTP time step exactly once');
check($service->status($user)['recovery_codes_remaining'] === 0, 'recovery consumption persists after concurrent requests');
$emailMigration->down();
$migration->down();
check(! Schema::hasTable('two_factor_login_challenges'), 'security tables roll back independently of business migrations');
