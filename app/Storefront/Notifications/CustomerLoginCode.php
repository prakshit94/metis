<?php

namespace App\Storefront\Notifications;

use App\Services\SecurityMailSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerLoginCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return [SharedMailChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->mailer(app(SecurityMailSettings::class)->resolveMailer())->subject('Your Metis Market sign-in code')
            ->line('Your customer sign-in code is '.$this->code.'.')
            ->line('This sign-in attempt expires five minutes after you first requested a code.')
            ->line('If you did not request this, ignore this email.');
    }
}
