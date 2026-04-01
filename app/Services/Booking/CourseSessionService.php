<?php

namespace App\Services\Booking;

use App\Models\Course;
use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Models\CourseWaitlist;
use App\Notifications\SessionCancelledNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseSessionService
{
    public function listAvailableDates(int $courseId, ?int $providerId = null): array
    {
        $query = CourseSession::query()
            ->where('course_id', $courseId)
            ->where('status', CourseSession::STATUS_OPEN)
            ->where('starts_at', '>=', now()->startOfDay())
            ->withCount([
                'bookings as booked_count' => function ($builder) {
                    $builder->where('status', CourseBooking::STATUS_BOOKED);
                },
            ])
            ->orderBy('starts_at');

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        $sessions = $query->get(['id', 'starts_at', 'capacity', 'status']);

        return $sessions
            ->groupBy(function (CourseSession $session) {
                return $session->starts_at->toDateString();
            })
            ->map(function (Collection $group, string $date) {
                $availableSessions = $group->filter(function (CourseSession $session) {
                    $available = $session->capacity - $session->booked_count;
                    return $available > 0 && $session->status === CourseSession::STATUS_OPEN;
                })->count();

                return [
                    'date' => $date,
                    'has_availability' => $availableSessions > 0,
                    'total_sessions' => $group->count(),
                    'available_sessions' => $availableSessions,
                ];
            })
            ->values()
            ->all();
    }

    public function listSessions(int $courseId, Carbon $date, ?int $providerId = null): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $query = CourseSession::query()
            ->where('course_id', $courseId)
            ->whereBetween('starts_at', [$start, $end])
            ->where('status', '!=', CourseSession::STATUS_CANCELLED)
            ->with(['provider:id,name'])
            ->withCount([
                'bookings as seats_taken' => function ($builder) {
                    $builder->where('status', CourseBooking::STATUS_BOOKED);
                },
            ])
            ->orderBy('starts_at');

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        return $query
            ->get(['id', 'provider_id', 'starts_at', 'ends_at', 'capacity', 'status', 'location'])
            ->map(function (CourseSession $session) {
                $seatsAvailable = $session->status === CourseSession::STATUS_OPEN
                    ? max(0, $session->capacity - $session->seats_taken)
                    : 0;

                return [
                    'id' => $session->id,
                    'starts_at' => $session->starts_at?->toDateTimeString(),
                    'ends_at' => $session->ends_at?->toDateTimeString(),
                    'provider' => $session->provider
                        ? [
                            'id' => $session->provider->id,
                            'name' => $session->provider->name,
                        ]
                        : null,
                    'location' => $session->location,
                    'capacity' => $session->capacity,
                    'seats_taken' => $session->seats_taken,
                    'seats_available' => $seatsAvailable,
                    'status' => $session->status,
                ];
            })
            ->values()
            ->all();
    }

    public function listAdminSessions(Course $course): array
    {
        return CourseSession::query()
            ->where('course_id', $course->id)
            ->with(['provider:id,name'])
            ->withCount([
                'bookings as booked_count' => function ($builder) {
                    $builder->where('status', CourseBooking::STATUS_BOOKED);
                },
                'waitlistEntries as waitlist_count' => function ($builder) {
                    $builder->where('status', CourseWaitlist::STATUS_WAITING);
                },
            ])
            ->orderBy('starts_at')
            ->get(['id', 'course_id', 'provider_id', 'starts_at', 'ends_at', 'capacity', 'status', 'location', 'notes'])
            ->map(function (CourseSession $session) {
                return [
                    'id' => $session->id,
                    'starts_at' => $session->starts_at?->toDateTimeString(),
                    'ends_at' => $session->ends_at?->toDateTimeString(),
                    'provider' => $session->provider
                        ? [
                            'id' => $session->provider->id,
                            'name' => $session->provider->name,
                        ]
                        : null,
                    'capacity' => $session->capacity,
                    'status' => $session->status,
                    'location' => $session->location,
                    'notes' => $session->notes,
                    'booked_count' => $session->booked_count,
                    'waitlist_count' => $session->waitlist_count,
                ];
            })
            ->values()
            ->all();
    }

    public function createSession(Course $course, array $payload): CourseSession
    {
        return $course->sessions()->create($payload);
    }

    public function updateSession(CourseSession $session, array $payload): array
    {
        $previousCapacity = $session->capacity;
        $previousStatus = $session->status;

        $session->fill($payload)->save();

        return [
            'session' => $session,
            'capacity_increased' => $session->capacity > $previousCapacity,
            'status_changed_to_open' => $previousStatus !== CourseSession::STATUS_OPEN
                && $session->status === CourseSession::STATUS_OPEN,
            'status_changed_to_cancelled' => $previousStatus !== CourseSession::STATUS_CANCELLED
                && $session->status === CourseSession::STATUS_CANCELLED,
        ];
    }

    public function deleteSession(CourseSession $session): void
    {
        $session->delete();
    }

    public function cancelSession(CourseSession $session): void
    {
        DB::transaction(function () use ($session) {
            $session->loadMissing(['course:id,title', 'provider:id,name']);

            $bookings = CourseBooking::query()
                ->where('course_session_id', $session->id)
                ->where('status', CourseBooking::STATUS_BOOKED)
                ->with('user')
                ->get();

            foreach ($bookings as $booking) {
                $booking->fill([
                    'status' => CourseBooking::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ])->save();

                $booking->user?->notify(new SessionCancelledNotification($session));
            }

            $waitlistEntries = CourseWaitlist::query()
                ->where('course_session_id', $session->id)
                ->where('status', CourseWaitlist::STATUS_WAITING)
                ->with('user')
                ->get();

            foreach ($waitlistEntries as $entry) {
                $entry->update(['status' => CourseWaitlist::STATUS_CANCELLED]);
                $entry->user?->notify(new SessionCancelledNotification($session));
            }
        });
    }
}
