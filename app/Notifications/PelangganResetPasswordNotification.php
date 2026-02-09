<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PelangganResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('user-pelanggan.reset-password', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Reset Password Pelanggan')
            ->line('Klik tombol di bawah untuk reset password Anda.')
            ->action('Reset Password', $url)
            ->line('Jika Anda tidak merasa meminta reset password, abaikan email ini.');
    }
}
