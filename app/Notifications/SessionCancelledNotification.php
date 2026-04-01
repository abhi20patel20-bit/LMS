<?php

namespace App\Notifications;

use App\Models\CourseSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(private CourseSession $session)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'session_cancelled',
            'course_id' => $this->session->course_id,
            'course_title' => $this->session->course?->title,
            'course_session_id' => $this->session->id,
            'starts_at' => $this->session->starts_at?->toDateTimeString(),
            'ends_at' => $this->session->ends_at?->toDateTimeString(),
            'provider_name' => $this->session->provider?->name,
            'location' => $this->session->location,
        ];
    }
}
