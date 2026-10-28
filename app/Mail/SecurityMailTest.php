<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

final class SecurityMailTest extends Mailable
{
    public function build(): static
    {
        return $this->subject(config('app.name').' mail configuration test')->text('mail.security-test');
    }
}
