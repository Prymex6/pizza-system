<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffResetPasswordNotification extends Notification
{

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('tenant.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $restaurantName = \App\Models\Tenant\Setting::get('restaurant_name', config('app.name'));

        return (new MailMessage)
            ->subject('Resetowanie hasła — ' . $restaurantName)
            ->view('emails.staff-reset-password', [
                'url'            => $url,
                'user'           => $notifiable,
                'restaurantName' => $restaurantName,
                'expireMinutes'  => config('auth.passwords.tenant_users.expire', 60),
            ]);
    }
}
