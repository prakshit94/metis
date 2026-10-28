<?php

use App\Services\SecurityMailSettings;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function defaultCheck(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS: '.$message.PHP_EOL;
}
// Read-only test doubles: no database access or application setting writes.
Schema::swap(new class
{
    public bool $exists = false;

    public function hasTable($name)
    {
        return $this->exists;
    }
});
config(['two_factor.email_mailer' => null, 'mail.default' => 'primary',
    'mail.mailers.primary' => ['transport' => 'smtp', 'host' => 'smtp.fixture.invalid', 'port' => 465, 'scheme' => 'smtps', 'username' => 'fixture-user', 'password' => 'private-fixture-password', 'timeout' => 8],
    'mail.from' => ['address' => 'sender@example.invalid', 'name' => 'Fixture Sender'],
    'mail.reply_to' => ['address' => 'reply@example.invalid'], 'services.ses.region' => 'eu-west-1']);
$service = app(SecurityMailSettings::class);
$response = $service->publicSettings();
$defaults = $response['settings'];
defaultCheck($defaults['provider'] === 'smtp' && $defaults['host'] === 'smtp.gmail.com', 'new configuration defaults to Gmail SMTP');
defaultCheck($defaults['port'] === 587 && $defaults['encryption'] === 'starttls' && $defaults['timeout'] === 15, 'Gmail default uses port 587, STARTTLS and bounded timeout');
defaultCheck($defaults['username'] === '' && $defaults['from_address'] === '', 'Gmail account-specific username and sender remain for the user to enter');
defaultCheck(! $defaults['enabled'] && ! isset($defaults['password']) && ! $defaults['password_present'] && ! str_contains(json_encode($response), 'private-fixture-password'), 'Gmail prefill neither enables delivery nor exposes credentials');
defaultCheck(config('mail.default') === 'primary' && config('mail.mailers.primary.host') === 'smtp.fixture.invalid', 'Gmail form preset does not change the business mailer');
config(['mail.default' => 'log', 'mail.mailers.smtp' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 2525, 'timeout' => null]]);
$defaults = $service->publicSettings()['settings'];
defaultCheck($defaults['host'] === 'smtp.gmail.com' && $defaults['port'] === 587, 'unconfigured local SMTP no longer overrides the Gmail preset');
Schema::getFacadeRoot()->exists = true;
$payload = Crypt::encryptString(json_encode(['host' => 'saved.fixture.invalid', 'port' => 587, 'from_name' => 'Saved Sender', 'username' => '', 'password' => 'saved-private-password', 'enabled' => true]));
DB::swap(new class($payload)
{
    public function __construct(private string $payload) {}

    public function table($name)
    {
        return $this;
    }

    public function where(...$arguments)
    {
        return $this;
    }

    public function value($column)
    {
        return $this->payload;
    }
});
$response = $service->publicSettings();
defaultCheck($response['settings']['host'] === 'saved.fixture.invalid' && $response['settings']['from_name'] === 'Saved Sender' && $response['settings']['username'] === '', 'saved values, including intentionally blank fields, take precedence over defaults');
defaultCheck($response['settings']['password_present'] && ! str_contains(json_encode($response), 'saved-private-password'), 'saved passwords remain redacted while showing their presence');
