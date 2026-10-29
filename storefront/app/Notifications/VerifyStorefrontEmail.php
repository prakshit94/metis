<?php

namespace Storefront\Notifications;

use App\Services\SecurityMailSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyStorefrontEmail extends Notification
{
    public function via(object $notifiable): array
    {
        return [SharedMailChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('shop.verify', now()->addHour(), ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)]);

        return (new MailMessage)->mailer(app(SecurityMailSettings::class)->resolveMailer())->subject('Verify your Metis Market account')->greeting('Welcome to Metis Market')
            ->line('Verify your email address to activate your customer shopping account.')
            ->action('Verify email address', $url)->line('This link expires in one hour. If you did not create this account, ignore this email.');
    }
}
