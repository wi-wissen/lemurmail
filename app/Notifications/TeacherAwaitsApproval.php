<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeacherAwaitsApproval extends Notification
{
    use Queueable;

    public function __construct(private readonly User $teacher) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.approval_mail_subject'))
            ->line(__('auth.approval_mail_line', ['email' => $this->teacher->email]))
            ->action(__('auth.approval_mail_action'), route('admin.users.index'));
    }
}
