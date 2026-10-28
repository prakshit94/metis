<?php

declare(strict_types=1);

namespace App\Services;

use Aws\Ses\SesClient;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;

/** Dedicated encrypted configuration: never stored in audited general settings or returned with secrets. */
final class SecurityMailSettings
{
    private const MAILER = 'security_database';

    private const SECRETS = ['password', 'api_key', 'aws_key', 'aws_secret'];

    private function stored(): ?array
    {
        // Existing env mailers remain usable during deployment and in older installations.
        if (! Schema::hasTable('security_mail_settings')) {
            return null;
        }
        $encrypted = DB::table('security_mail_settings')->where('id', 1)->value('encrypted_payload');

        return $encrypted ? json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR) : null;
    }

    public function isDeliverable(string $name, array $seen = []): bool
    {
        if (in_array($name, $seen, true)) {
            return false;
        }
        $transport = config('mail.mailers.'.$name.'.transport');
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = config('mail.mailers.'.$name.'.mailers', []);

            return count($children) > 0 && collect($children)->every(fn ($child) => $this->isDeliverable($child, [...$seen, $name]));
        }

        return match ($transport) {
            'smtp' => ! empty(config('mail.mailers.'.$name.'.host')),
            'postmark' => class_exists(PostmarkTransportFactory::class),
            'resend' => class_exists(\Resend::class),
            'ses' => class_exists(SesClient::class),
            'sendmail' => is_executable(explode(' ', (string) config('mail.mailers.'.$name.'.path', '/usr/sbin/sendmail'))[0]),
            default => false,
        };
    }

    public function options(): array
    {
        return [
            ['value' => 'smtp', 'label' => 'SMTP', 'available' => true],
            ['value' => 'postmark', 'label' => 'Postmark API', 'available' => class_exists(PostmarkTransportFactory::class)],
            ['value' => 'resend', 'label' => 'Resend API', 'available' => class_exists(\Resend::class)],
            ['value' => 'ses', 'label' => 'Amazon SES', 'available' => class_exists(SesClient::class)],
            ['value' => 'existing', 'label' => 'Existing application mailer', 'available' => true],
        ];
    }

    private function defaults(): array
    {
        return ['enabled' => false, 'provider' => 'smtp', 'host' => '', 'port' => 587,
            'encryption' => 'starttls', 'username' => '', 'timeout' => 15, 'from_address' => '', 'from_name' => config('app.name'),
            'reply_to' => '', 'region' => 'us-east-1', 'message_stream' => '', 'existing_mailer' => 'smtp'];
    }

    private function displayDefaults(): array
    {
        // Form-only Gmail preset. Saved values still win; delivery remains disabled until explicitly saved.
        return array_replace($this->defaults(), [
            'provider' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 587,
            'encryption' => 'starttls', 'username' => '', 'from_address' => '',
            'from_name' => (string) config('app.name'), 'timeout' => 15,
            'region' => (string) (config('services.ses.region') ?: 'us-east-1'),
            'message_stream' => (string) (config('mail.mailers.postmark.message_stream_id') ?? ''),
        ]);
    }

    public function publicSettings(): array
    {
        $stored = $this->stored();
        $settings = array_replace($this->displayDefaults(), $stored ?? []);
        foreach (self::SECRETS as $key) {
            $settings[$key.'_present'] = ($settings[$key] ?? '') !== '';
            unset($settings[$key]);
        }

        return ['configuration_saved' => $stored !== null, 'settings' => $settings, 'providers' => $this->options(),
            'existing_mailers' => array_values(array_filter(array_keys(config('mail.mailers', [])), fn ($name) => $name !== self::MAILER && $this->isDeliverable($name)))];
    }

    public function save(array $data, int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            // Lock a single configuration slot before merging any retained credentials.
            DB::table('security_mail_settings')->insertOrIgnore(['id' => 1, 'encrypted_payload' => Crypt::encryptString('{}'), 'updated_at' => now()]);
            DB::table('security_mail_settings')->where('id', 1)->lockForUpdate()->first();
            $existing = $this->stored() ?? [];
            $settings = array_replace($this->defaults(), $existing, array_diff_key($data, array_flip(['current_password', 'code', 'clear_credentials'])));
            $settings['enabled'] = (bool) $settings['enabled'];
            foreach (['host', 'username', 'from_name', 'from_address', 'reply_to', 'message_stream', 'region', 'existing_mailer'] as $field) {
                $settings[$field] = $settings[$field] ?? '';
            }
            foreach (self::SECRETS as $key) {
                if (($settings[$key] ?? '') === '') {
                    $settings[$key] = $key === 'api_key' && ($existing['provider'] ?? null) !== $settings['provider'] ? '' : ($existing[$key] ?? '');
                }
                if (! empty($data['clear_credentials'])) {
                    $settings[$key] = '';
                }
            }
            if ($settings['enabled']) {
                $this->validateEnabled($settings);
            }
            DB::table('security_mail_settings')->where('id', 1)->update([
                'encrypted_payload' => Crypt::encryptString(json_encode($settings, JSON_THROW_ON_ERROR)),
                'updated_by' => $userId, 'updated_at' => now(),
            ]);
        });
        Mail::purge(self::MAILER);
    }

    private function validateEnabled(array $settings): void
    {
        $provider = collect($this->options())->firstWhere('value', $settings['provider']);
        if (! $provider || ! $provider['available']) {
            $this->invalid('provider', 'This provider requires its Laravel mail transport package. SMTP is available without additional packages.');
        }
        if (empty($settings['from_address']) || empty($settings['from_name'])) {
            $this->invalid('from_address', 'Set the sender email address and name.');
        }
        if ($settings['provider'] === 'smtp') {
            $host = $settings['host'] ?? '';
            if ($host === '' || (! filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) && ! preg_match('/^[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D', $host))) {
                $this->invalid('host', 'Enter an SMTP hostname or IP address without a URL scheme or path.');
            }
            if (! empty($settings['username']) && empty($settings['password'])) {
                $this->invalid('password', 'Enter the SMTP password for this username.');
            }
        }
        if (in_array($settings['provider'], ['postmark', 'resend'], true) && empty($settings['api_key'])) {
            $this->invalid('api_key', 'Enter the provider API key.');
        }
        if ($settings['provider'] === 'ses' && (empty($settings['aws_key']) || empty($settings['aws_secret']) || empty($settings['region']))) {
            $this->invalid('aws_key', 'Enter the SES access key, secret and region.');
        }
        if ($settings['provider'] === 'existing' && (($settings['existing_mailer'] ?? '') === self::MAILER || ! $this->isDeliverable($settings['existing_mailer'] ?? ''))) {
            $this->invalid('existing_mailer', 'Select an available delivery mailer without log/array fallbacks.');
        }
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function resolveMailer(): string
    {
        $settings = $this->stored();
        if (! $settings || ! ($settings['enabled'] ?? false)) {
            return (string) (config('two_factor.email_mailer') ?: config('mail.default'));
        }
        if ($settings['provider'] === 'existing') {
            return $settings['existing_mailer'];
        }
        $config = match ($settings['provider']) {
            'smtp' => ['transport' => 'smtp', 'host' => $settings['host'], 'port' => $settings['port'],
                'scheme' => $settings['encryption'] === 'smtps' ? 'smtps' : 'smtp', 'require_tls' => true,
                'username' => $settings['username'] ?: null, 'password' => $settings['password'] ?: null, 'timeout' => $settings['timeout']],
            'postmark' => ['transport' => 'postmark', 'key' => $settings['api_key'], 'message_stream_id' => $settings['message_stream'] ?: null],
            'resend' => ['transport' => 'resend', 'key' => $settings['api_key']],
            'ses' => ['transport' => 'ses', 'key' => $settings['aws_key'], 'secret' => $settings['aws_secret'], 'region' => $settings['region']],
        };
        config(['mail.mailers.'.self::MAILER => $config]);
        // Also refresh long-lived mail managers after a credential update.
        Mail::purge(self::MAILER);

        return self::MAILER;
    }

    public function applySender(Mailable $message): void
    {
        $settings = $this->stored();
        if (! $settings || ! ($settings['enabled'] ?? false)) {
            return;
        }
        $message->from($settings['from_address'], $settings['from_name']);
        if (! empty($settings['reply_to'])) {
            $message->replyTo($settings['reply_to']);
        }
    }
}
