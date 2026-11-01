<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;

final class TwoFactorCode extends Mailable
{
    // Sent synchronously: OTP plaintext is never serialized into a queue payload.
    public function __construct(public string $code, public string $purpose) {}

    public function build(): static
    {
        return $this->subject(config('app.name').' verification code')
            ->text('mail.two-factor-code', ['code' => $this->code, 'purpose' => $this->purpose]);
    }
}
