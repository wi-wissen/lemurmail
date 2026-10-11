<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\TeacherAwaitsApproval;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Notification;

/** Tells the admins once a teacher with an unknown domain has confirmed the address. */
class NotifyAdminOfPendingTeacher
{
    public function handle(Verified $event): void
    {
        $teacher = $event->user;

        if ($teacher->status !== User::STATUS_PENDING) {
            return;
        }

        $notification = new TeacherAwaitsApproval($teacher);

        if ($adminEmail = config('lemurmail.admin_email')) {
            Notification::route('mail', $adminEmail)->notify($notification);
        }

        Notification::send(
            User::where('role', User::ROLE_SUPER_ADMIN)->get(),
            $notification,
        );
    }
}
