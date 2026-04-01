<?php

namespace App\Services\User;

use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Models\CourseWaitlist;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;

class UserProfileCourseService
{
    public function getCourses(User $user): array
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $user->id)
            ->with(['course:id,title,course_type,duration,delivery_type'])
            ->get();

        if ($enrollments->isEmpty()) {
            return [
                'mandatory' => [],
                'optional' => [],
            ];
        }

        $courseIds = $enrollments->pluck('course_id')->unique()->values()->all();
        $bookings = $this->bookingsByCourse($user->id, $courseIds);
        $waitlistEntries = $this->waitlistByCourse($user->id, $courseIds);

        $mapCourse = function (Enrollment $enrollment) use ($bookings, $waitlistEntries) {
            $course = $enrollment->course;
            if (!$course) {
                return null;
            }

            $booking = $bookings->get($course->id);
            $waitlist = $waitlistEntries->get($course->id);

            $bookingStatus = 'none';
            $bookingSummary = null;

            if ($course->delivery_type === 'scheduled') {
                if ($booking) {
                    $bookingStatus = 'booked';
                    $bookingSummary = $this->formatSessionSummary($booking->session);
                } elseif ($waitlist) {
                    $bookingStatus = 'waitlisted';
                    $bookingSummary = $this->formatSessionSummary($waitlist->session);
                }
            }

            return [
                'id' => $course->id,
                'title' => $course->title,
                'courseType' => $course->course_type,
                'duration' => $course->duration,
                'delivery_type' => $course->delivery_type,
                'is_required' => $enrollment->enrollment_type === Enrollment::TYPE_MANDATORY,
                'enrollment_type' => $enrollment->enrollment_type,
                'enrollment_status' => $enrollment->status,
                'completed_at' => $enrollment->completed_at?->toDateTimeString(),
                'booking_status' => $bookingStatus,
                'booking_summary' => $bookingSummary,
            ];
        };

        $mandatory = $enrollments
            ->where('enrollment_type', Enrollment::TYPE_MANDATORY)
            ->map($mapCourse)
            ->filter()
            ->sortBy('title')
            ->values()
            ->all();

        $optional = $enrollments
            ->where('enrollment_type', Enrollment::TYPE_OPTIONAL)
            ->map($mapCourse)
            ->filter()
            ->sortBy('title')
            ->values()
            ->all();

        return [
            'mandatory' => $mandatory,
            'optional' => $optional,
        ];
    }

    private function bookingsByCourse(int $userId, array $courseIds): Collection
    {
        if (empty($courseIds)) {
            return collect();
        }

        return CourseBooking::query()
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->where('status', CourseBooking::STATUS_BOOKED)
            ->with(['session.provider'])
            ->orderByDesc('booked_at')
            ->get()
            ->keyBy('course_id');
    }

    private function waitlistByCourse(int $userId, array $courseIds): Collection
    {
        if (empty($courseIds)) {
            return collect();
        }

        return CourseWaitlist::query()
            ->where('user_id', $userId)
            ->whereIn('course_id', $courseIds)
            ->where('status', CourseWaitlist::STATUS_WAITING)
            ->with(['session.provider'])
            ->orderByDesc('created_at')
            ->get()
            ->keyBy('course_id');
    }

    private function formatSessionSummary(?CourseSession $session): ?array
    {
        if (!$session) {
            return null;
        }

        return [
            'session_id' => $session->id,
            'starts_at' => $session->starts_at?->toDateTimeString(),
            'ends_at' => $session->ends_at?->toDateTimeString(),
            'provider_name' => $session->provider?->name,
            'location' => $session->location,
        ];
    }
}
