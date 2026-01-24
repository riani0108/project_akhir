<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailCustom extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // ✅ PASTIKAN MENGGUNAKAN temporarySignedRoute
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60), // 60 menit expiry
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Verifikasi Email Anda')
            ->greeting('Halo ' . $notifiable->nama_pelanggan . '!')
            ->line('Silakan klik tombol di bawah untuk verifikasi email Anda.')
            ->action('Verifikasi Email', $verificationUrl)
            ->line('Link verifikasi akan kadaluarsa dalam 60 menit.')
            ->line('Jika Anda tidak membuat permintaan ini, abaikan email ini.');
    }
}


