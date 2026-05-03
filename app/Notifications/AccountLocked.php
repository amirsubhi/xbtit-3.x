<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLocked extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account has been locked')
            ->line('Your account has been locked after too many failed login attempts.')
            ->line('To regain access, please reset your password.')
            ->action('Reset Password', url(route('password.request')));
    }
}
