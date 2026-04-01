<?php

namespace App\Notifications;

use App\Models\CourseBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(private CourseBooking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $session = $this->booking->session;

        return [
            'event' => 'booking_confirmed',
            'course_id' => $this->booking->course_id,
            'course_title' => $this->booking->course?->title,
            'course_session_id' => $this->booking->course_session_id,
            'starts_at' => $session?->starts_at?->toDateTimeString(),
            'ends_at' => $session?->ends_at?->toDateTimeString(),
            'provider_name' => $session?->provider?->name,
            'location' => $session?->location,
        ];
    }
}
