<?php

namespace App\Notifications;

use App\Models\CourseWaitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WaitlistJoinedNotification extends Notification
{
    use Queueable;

    public function __construct(private CourseWaitlist $waitlist)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'waitlist_joined',
            'course_id' => $this->waitlist->course_id,
            'course_session_id' => $this->waitlist->course_session_id,
            'position' => $this->waitlist->position,
        ];
    }
}
