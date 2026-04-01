<?php

namespace App\Services\Booking;

use App\Models\Course;
use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Models\CourseWaitlist;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\WaitlistJoinedNotification;
use App\Notifications\WaitlistPromotedNotification;
use App\Services\User\UserLearningService;
use Illuminate\Support\Facades\DB;

class CourseBookingService
{
    public function __construct(private UserLearningService $learningService)
    {
    }

    public function bookSession(User $user, CourseSession $session, ?string $enrollmentTypeContext = null): array
    {
        return DB::transaction(function () use ($user, $session, $enrollmentTypeContext) {
            $session = CourseSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== CourseSession::STATUS_OPEN) {
                return ['status' => 'closed'];
            }

            $existingBooking = CourseBooking::query()
                ->where('user_id', $user->id)
                ->where('course_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($existingBooking && $existingBooking->status !== CourseBooking::STATUS_CANCELLED) {
                return [
                    'status' => 'already_booked',
                    'booking' => $existingBooking,
                ];
            }

            $bookedCount = CourseBooking::query()
                ->where('course_session_id', $session->id)
                ->where('status', CourseBooking::STATUS_BOOKED)
                ->count();

            if ($bookedCount >= $session->capacity) {
                return ['status' => 'full'];
            }

            $booking = $existingBooking ?? new CourseBooking([
                'user_id' => $user->id,
                'course_id' => $session->course_id,
                'course_session_id' => $session->id,
            ]);

            $booking->fill([
                'status' => CourseBooking::STATUS_BOOKED,
                'booked_at' => now(),
                'cancelled_at' => null,
            ])->save();

            $waitlistEntry = CourseWaitlist::query()
                ->where('user_id', $user->id)
                ->where('course_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($waitlistEntry && $waitlistEntry->status === CourseWaitlist::STATUS_WAITING) {
                $waitlistEntry->update(['status' => CourseWaitlist::STATUS_CANCELLED]);
            }

            $enrollment = $this->ensureEnrollment($user, $session->course, $enrollmentTypeContext);

            $booking->load(['session.provider', 'course']);
            $user->notify(new BookingConfirmedNotification($booking));

            return [
                'status' => 'booked',
                'booking' => $booking,
                'enrollment' => $enrollment,
            ];
        });
    }

    public function updateBooking(User $user, Course $course, CourseSession $session): array
    {
        return DB::transaction(function () use ($user, $course, $session) {
            $currentBooking = CourseBooking::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', CourseBooking::STATUS_BOOKED)
                ->lockForUpdate()
                ->first();

            if (!$currentBooking) {
                return ['status' => 'not_booked'];
            }

            $session = CourseSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->course_id !== $course->id) {
                return ['status' => 'invalid_session'];
            }

            if ($session->status !== CourseSession::STATUS_OPEN) {
                return ['status' => 'closed'];
            }

            if ($currentBooking->course_session_id === $session->id) {
                return [
                    'status' => 'already_booked',
                    'booking' => $currentBooking,
                ];
            }

            $existingBooking = CourseBooking::query()
                ->where('user_id', $user->id)
                ->where('course_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($existingBooking && $existingBooking->status !== CourseBooking::STATUS_CANCELLED) {
                return [
                    'status' => 'already_booked',
                    'booking' => $existingBooking,
                ];
            }

            $bookedCount = CourseBooking::query()
                ->where('course_session_id', $session->id)
                ->where('status', CourseBooking::STATUS_BOOKED)
                ->count();

            if ($bookedCount >= $session->capacity) {
                return ['status' => 'full'];
            }

            $currentBooking->fill([
                'status' => CourseBooking::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            $this->promoteWaitlistLocked($currentBooking->course_session_id);

            $nextBooking = $existingBooking ?? new CourseBooking([
                'user_id' => $user->id,
                'course_id' => $session->course_id,
                'course_session_id' => $session->id,
            ]);

            $nextBooking->fill([
                'status' => CourseBooking::STATUS_BOOKED,
                'booked_at' => now(),
                'cancelled_at' => null,
            ])->save();

            $waitlistEntry = CourseWaitlist::query()
                ->where('user_id', $user->id)
                ->where('course_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($waitlistEntry && $waitlistEntry->status === CourseWaitlist::STATUS_WAITING) {
                $waitlistEntry->update(['status' => CourseWaitlist::STATUS_CANCELLED]);
            }

            $enrollment = $this->ensureEnrollment($user, $session->course, null);

            $nextBooking->load(['session.provider', 'course']);
            $user->notify(new BookingConfirmedNotification($nextBooking));

            return [
                'status' => 'booked',
                'booking' => $nextBooking,
                'enrollment' => $enrollment,
            ];
        });
    }

    public function cancelBooking(User $user, CourseBooking $booking): CourseBooking
    {
        return DB::transaction(function () use ($user, $booking) {
            $booking = CourseBooking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->user_id !== $user->id) {
                throw new \RuntimeException('Unauthorized booking cancellation.');
            }

            if ($booking->status === CourseBooking::STATUS_CANCELLED) {
                return $booking;
            }

            $booking->fill([
                'status' => CourseBooking::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            $this->promoteWaitlistLocked($booking->course_session_id);

            return $booking->load(['session.provider', 'course']);
        });
    }

    public function joinWaitlist(User $user, CourseSession $session): array
    {
        return DB::transaction(function () use ($user, $session) {
            $session = CourseSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== CourseSession::STATUS_OPEN) {
                return ['status' => 'closed'];
            }

            $existingBooking = CourseBooking::query()
                ->where('user_id', $user->id)
                ->where('course_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($existingBooking && $existingBooking->status !== CourseBooking::STATUS_CANCELLED) {
                return [
                    'status' => 'already_booked',
                    'booking' => $existingBooking,
                ];
            }

            $bookedCount = CourseBooking::query()
                ->where('course_session_id', $session->id)
                ->where('status', CourseBooking::STATUS_BOOKED)
                ->count();

            if ($bookedCount < $session->capacity) {
                return ['status' => 'available'];
            }

            $waitlistEntry = CourseWaitlist::query()
                ->where('user_id', $user->id)
                ->where('course_session_id', $session->id)
                ->lockForUpdate()
                ->first();

            if ($waitlistEntry) {
                if ($waitlistEntry->status !== CourseWaitlist::STATUS_WAITING) {
                    $waitlistEntry->update(['status' => CourseWaitlist::STATUS_WAITING]);
                }

                $position = $waitlistEntry->position;
                if (!$position) {
                    $position = $this->nextWaitlistPosition($session->id);
                    $waitlistEntry->update(['position' => $position]);
                }

                return [
                    'status' => 'waiting',
                    'waitlist' => $waitlistEntry,
                    'position' => $position,
                ];
            }

            $position = $this->nextWaitlistPosition($session->id);
            $waitlistEntry = CourseWaitlist::create([
                'user_id' => $user->id,
                'course_id' => $session->course_id,
                'course_session_id' => $session->id,
                'position' => $position,
                'status' => CourseWaitlist::STATUS_WAITING,
            ]);

            $user->notify(new WaitlistJoinedNotification($waitlistEntry));

            return [
                'status' => 'waiting',
                'waitlist' => $waitlistEntry,
                'position' => $position,
            ];
        });
    }

    public function promoteWaitlist(CourseSession $session): void
    {
        DB::transaction(function () use ($session) {
            $this->promoteWaitlistLocked($session->id);
        });
    }

    private function promoteWaitlistLocked(int $sessionId): void
    {
        $session = CourseSession::query()
            ->whereKey($sessionId)
            ->lockForUpdate()
            ->first();

        if (!$session || $session->status !== CourseSession::STATUS_OPEN) {
            return;
        }

        $bookedCount = CourseBooking::query()
            ->where('course_session_id', $session->id)
            ->where('status', CourseBooking::STATUS_BOOKED)
            ->count();

        if ($bookedCount >= $session->capacity) {
            return;
        }

        $waitlistEntry = CourseWaitlist::query()
            ->where('course_session_id', $session->id)
            ->where('status', CourseWaitlist::STATUS_WAITING)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (!$waitlistEntry) {
            return;
        }

        $booking = CourseBooking::query()
            ->where('user_id', $waitlistEntry->user_id)
            ->where('course_session_id', $session->id)
            ->lockForUpdate()
            ->first();

        if ($booking && $booking->status !== CourseBooking::STATUS_CANCELLED) {
            $waitlistEntry->update(['status' => CourseWaitlist::STATUS_PROMOTED]);
            return;
        }

        $booking = $booking ?? new CourseBooking([
            'user_id' => $waitlistEntry->user_id,
            'course_id' => $session->course_id,
            'course_session_id' => $session->id,
        ]);

        $booking->fill([
            'status' => CourseBooking::STATUS_BOOKED,
            'booked_at' => now(),
            'cancelled_at' => null,
        ])->save();

        $waitlistEntry->update(['status' => CourseWaitlist::STATUS_PROMOTED]);

        $user = User::query()->find($waitlistEntry->user_id);
        if (!$user) {
            return;
        }

        $this->ensureEnrollment($user, $session->course, null);

        $booking->load(['session.provider', 'course']);
        $user->notify(new WaitlistPromotedNotification($booking));
    }

    private function nextWaitlistPosition(int $sessionId): int
    {
        return CourseWaitlist::query()
            ->where('course_session_id', $sessionId)
            ->where('status', CourseWaitlist::STATUS_WAITING)
            ->lockForUpdate()
            ->count() + 1;
    }

    private function ensureEnrollment(User $user, ?Course $course, ?string $enrollmentTypeContext)
    {
        if (!$course) {
            return null;
        }

        return $this->learningService->enroll($user, $course);
    }
}
