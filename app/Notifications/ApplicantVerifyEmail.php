<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ApplicantVerifyEmail extends Notification
{
    use Queueable;

    public function __construct(private readonly string $slug) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'applicant.verify.confirm',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'slug' => $this->slug,
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject('Verify your applicant email address')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Please verify your email address to continue your admission application.')
            ->action('Verify Email Address', $url)
            ->line('This verification link will expire automatically. If you did not create this applicant account, no action is required.');
    }
}
