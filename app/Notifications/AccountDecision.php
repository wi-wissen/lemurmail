<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDecision extends Notification
{
    use Queueable;

    public function __construct(public bool $approved) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting(__('admin.mail.greeting'));

        if (! $this->approved) {
            return $mail->subject(__('admin.mail.rejected_subject'))
                ->line(__('admin.mail.rejected_line'));
        }

        return $mail->subject(__('admin.mail.approved_subject'))
            ->line(__('admin.mail.approved_line'))
            ->action(__('admin.mail.approved_action'), route('login'));
    }
}
