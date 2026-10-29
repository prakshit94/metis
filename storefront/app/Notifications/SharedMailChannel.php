<?php

namespace Storefront\Notifications;

use App\Services\SecurityMailSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SharedMailChannel
{
    public function send(object $notifiable, object $notification): void
    {
        $settings = app(SecurityMailSettings::class);
        $mailer = $settings->resolveMailer();
        if (! $settings->isDeliverable($mailer)) {
            throw new \RuntimeException('Shared email delivery must be configured by the master administrator.');
        }
        $message = $notification->toMail($notifiable);
        $mail = (new Mailable)->subject($message->subject)->markdown($message->markdown, $message->data());
        $settings->applySender($mail);
        Mail::mailer($mailer)->to($notifiable->email)->send($mail);
    }
}
