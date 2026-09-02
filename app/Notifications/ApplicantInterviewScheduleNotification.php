<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicantInterviewScheduleNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $eventType,
        private readonly string $candidateName,
        private readonly string $applicationNo,
        private readonly string $collegeName,
        private readonly string $panelName,
        private readonly string $scheduledAt,
        private readonly ?string $venue,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->eventType) {
            'CANCELLED' => 'Interview Cancelled - '.$this->applicationNo,
            'RESCHEDULED' => 'Interview Rescheduled - '.$this->applicationNo,
            default => 'Interview Scheduled - '.$this->applicationNo,
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$this->candidateName.',');

        if ($this->eventType === 'CANCELLED') {
            $message->line('Your admission interview scheduled by '.$this->collegeName.' has been cancelled.');
        } elseif ($this->eventType === 'RESCHEDULED') {
            $message->line('Your admission interview schedule has been updated by '.$this->collegeName.'.');
        } else {
            $message->line('Your admission interview has been scheduled by '.$this->collegeName.'.');
        }

        $message
            ->line('Application No: '.$this->applicationNo)
            ->line('Panel: '.$this->panelName)
            ->line('Date & Time: '.$this->scheduledAt);

        if (filled($this->venue)) {
            $message->line('Venue / Mode: '.$this->venue);
        }

        return $message->line('Please keep your application number available for the interview process.');
    }
}
