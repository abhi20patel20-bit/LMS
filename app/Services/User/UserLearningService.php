<?php

namespace App\Services\User;

use App\Models\Course;
use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Models\CourseWaitlist;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Requirements\EffectiveRequirementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserLearningService
{
    public function __construct(
        private UserEnrollmentQueryService $queryService,
        private EffectiveRequirementService $requirementService
    ) {
    }

    public function getDashboard(User $user): array
    {
        $overdueCount = $this->queryService->overdue($user)->count();
        $dueSoonCount = $this->queryService->dueSoon($user)->count();
        $inProgressCount = $this->queryService->inProgress($user)->count();
        $completedCount = $this->queryService->completed($user)->count();

        $overdue = $this->mapEnrollments(
            $this->withCourse($this->queryService->overdue($user))
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
            $user
        );

        $dueSoon = $this->mapEnrollments(
            $this->withCourse($this->queryService->dueSoon($user))
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
            $user
        );

        $inProgress = $this->mapEnrollments(
            $this->withCourse($this->queryService->inProgress($user))
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
            $user
        );

        $nextUp = $this->mapEnrollments(
            $this->withCourse($this->queryService->required($user))
                ->orderByRaw('case when due_at is null then 1 else 0 end')
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
            $user
        );

        return [
            'counts' => [
                'overdue' => $overdueCount,
                'dueSoon' => $dueSoonCount,
                'inProgress' => $inProgressCount,
                'completed' => $completedCount,
            ],
            'lists' => [
                'overdue' => $overdue,
                'dueSoon' => $dueSoon,
                'inProgress' => $inProgress,
                'nextUp' => $nextUp,
            ],
        ];
    }

    public function getLearning(User $user): array
    {
        $required = $this->mapEnrollments(
            $this->withCourse($this->queryService->required($user))
                ->orderBy('due_at')
                ->get(),
            $user
        );
        $inProgress = $this->mapEnrollments(
            $this->withCourse($this->queryService->inProgress($user))
                ->orderBy('updated_at', 'desc')
                ->get(),
            $user
        );

        $completed = $this->mapEnrollments(
            $this->withCourse($this->queryService->completed($user))
                ->orderBy('completed_at', 'desc')
                ->get(),
            $user
        );

        $optional = $this->mapEnrollments(
            $this->withCourse(
                Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('enrollment_type', Enrollment::TYPE_OPTIONAL)
            )
                ->orderBy('due_at')
                ->get(),
            $user
        );

        return [
            'required' => $required,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'optional' => $optional,
        ];
    }

    public function enroll(User $user, Course $course): Enrollment
    {
        [$enrollment, $payload] = $this->prepareEnrollment($user, $course);

        if (!$enrollment->exists || !$this->isLockedStatus($enrollment->status)) {
            $payload['status'] = Enrollment::STATUS_NOT_STARTED;
        }

        $enrollment->fill($payload)->save();

        return $this->loadEnrollment($enrollment);
    }

    public function start(User $user, Course $course): Enrollment
    {
        [$enrollment, $payload] = $this->prepareEnrollment($user, $course);

        if ($enrollment->status !== Enrollment::STATUS_COMPLETED) {
            $payload['status'] = Enrollment::STATUS_IN_PROGRESS;
        }

        $enrollment->fill($payload)->save();

        return $this->loadEnrollment($enrollment);
    }

    public function complete(User $user, Course $course): Enrollment
    {
        [$enrollment, $payload] = $this->prepareEnrollment($user, $course);

        $payload['status'] = Enrollment::STATUS_COMPLETED;
        $payload['completed_at'] = now();

        $enrollment->fill($payload)->save();

        return $this->loadEnrollment($enrollment);
    }

    public function cancel(User $user, Course $course): Enrollment
    {
        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        if ($enrollment->enrollment_type !== Enrollment::TYPE_OPTIONAL) {
            throw new \RuntimeException('Only optional enrollments can be canceled.');
        }

        $enrollment->fill([
            'status' => Enrollment::STATUS_NOT_STARTED,
            'completed_at' => null,
            'cancelled_at' => now(),
        ])->save();

        return $this->loadEnrollment($enrollment);
    }

    private function prepareEnrollment(User $user, Course $course): array
    {
        $requiredCourseIds = $this->requirementService->requiredMandatoryCourseIdsForUser($user);
        $isRequired = in_array($course->id, $requiredCourseIds, true);

        $enrollment = Enrollment::firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        if ($isRequired) {
            $payload = [
                'enrollment_type' => Enrollment::TYPE_MANDATORY,
                'source' => Enrollment::SOURCE_REQUIREMENTS,
                'source_id' => null,
            ];
        } else {
            $payload = [
                'enrollment_type' => Enrollment::TYPE_OPTIONAL,
                'source' => $enrollment->source ?? Enrollment::SOURCE_MANUAL,
                'source_id' => $enrollment->source_id,
            ];
        }

        return [$enrollment, $payload];
    }

    private function isLockedStatus(?string $status): bool
    {
        return in_array($status, [
            Enrollment::STATUS_IN_PROGRESS,
            Enrollment::STATUS_COMPLETED,
            Enrollment::STATUS_EXPIRED,
        ], true);
    }

    private function withCourse(Builder $query): Builder
    {
        return $query->with([
            'course:id,title,description,course_type,duration,course_category_id,delivery_type,booking_required',
            'course.category:id,name',
            'course.providers:id,name',
        ]);
    }

    private function mapEnrollments(Collection $enrollments, User $user): array
    {
        $courseIds = $enrollments->pluck('course_id')->unique()->values()->all();
        $bookings = $this->bookingsByCourse($user->id, $courseIds);
        $waitlistEntries = $this->waitlistByCourse($user->id, $courseIds);

        return $enrollments
            ->map(function (Enrollment $enrollment) use ($bookings, $waitlistEntries) {
                return $this->formatEnrollment($enrollment, $bookings, $waitlistEntries);
            })
            ->values()
            ->all();
    }

    private function formatEnrollment(
        Enrollment $enrollment,
        Collection $bookings,
        Collection $waitlistEntries
    ): array {
        $course = $enrollment->course;
        $bookingStatus = 'none';
        $bookingSummary = null;

        if ($course && $course->delivery_type === 'scheduled') {
            $booking = $bookings->get($course->id);
            $waitlist = $waitlistEntries->get($course->id);

            if ($booking) {
                $bookingStatus = 'booked';
                $bookingSummary = $this->formatSessionSummary($booking->session);
            } elseif ($waitlist) {
                $bookingStatus = 'waitlisted';
                $bookingSummary = $this->formatSessionSummary($waitlist->session);
            }
        }

        return [
            'id' => $enrollment->id,
            'course_id' => $enrollment->course_id,
            'enrollment_type' => $enrollment->enrollment_type,
            'status' => $enrollment->status,
            'source' => $enrollment->source,
            'source_id' => $enrollment->source_id,
            'due_at' => $enrollment->due_at?->toDateString(),
            'expires_at' => $enrollment->expires_at?->toDateString(),
            'completed_at' => $enrollment->completed_at?->toDateTimeString(),
            'booking_status' => $bookingStatus,
            'booking_summary' => $bookingSummary,
            'course' => [
                'id' => $course?->id,
                'title' => $course?->title,
                'description' => $course?->description,
                'course_type' => $course?->course_type,
                'duration' => $course?->duration,
                'delivery_type' => $course?->delivery_type,
                'booking_required' => $course?->booking_required,
                'category' => $course?->category
                    ? [
                        'id' => $course->category->id,
                        'name' => $course->category->name,
                    ]
                    : null,
                'providers' => $course?->providers
                    ? $course->providers->map(function ($provider) {
                        return [
                            'id' => $provider->id,
                            'name' => $provider->name,
                        ];
                    })->values()
                    : [],
            ],
        ];
    }

    private function loadEnrollment(Enrollment $enrollment): Enrollment
    {
        return $enrollment->load([
            'course:id,title,description,course_type,duration,course_category_id,delivery_type,booking_required',
            'course.category:id,name',
            'course.providers:id,name',
        ]);
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
            ->unique('course_id')
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
            ->unique('course_id')
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
