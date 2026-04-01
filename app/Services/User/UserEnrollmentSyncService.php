<?php

namespace App\Services\User;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\Requirements\EffectiveRequirementService;

class UserEnrollmentSyncService
{
    public function __construct(private EffectiveRequirementService $requirementService)
    {
    }

    /**
     * Upsert mandatory enrollments for required courses.
     * Completed enrollments are preserved.
     */
    public function syncUser(User $user): void
    {
        $requirements = $this->requirementService->requirementCourseIdsForUser($user);
        $requiredCourseIds = $requirements['mandatory'] ?? [];
        $optionalCourseIds = $requirements['optional'] ?? [];

        $this->syncRequiredEnrollments($user, $requiredCourseIds);
        $this->syncOptionalEnrollments($user, $optionalCourseIds);
    }

    private function syncRequiredEnrollments(User $user, array $requiredCourseIds): void
    {
        $requiredCourseIds = array_values(array_unique($requiredCourseIds));

        $this->downgradeNoLongerRequired($user, $requiredCourseIds);

        if (empty($requiredCourseIds)) {
            return;
        }

        $existingEnrollments = Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $requiredCourseIds)
            ->get()
            ->keyBy('course_id');

        foreach ($requiredCourseIds as $courseId) {
            $existing = $existingEnrollments->get($courseId);

            $payload = [
                'enrollment_type' => Enrollment::TYPE_MANDATORY,
            ];

            if ($existing) {
                if (!$existing->source) {
                    $payload['source'] = Enrollment::SOURCE_REQUIREMENTS;
                }

                $existing->fill($payload)->save();
                continue;
            }

            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'enrollment_type' => Enrollment::TYPE_MANDATORY,
                'status' => Enrollment::STATUS_NOT_STARTED,
                'source' => Enrollment::SOURCE_REQUIREMENTS,
            ]);
        }
    }

    private function syncOptionalEnrollments(User $user, array $optionalCourseIds): void
    {
        $optionalCourseIds = array_values(array_unique($optionalCourseIds));

        if (empty($optionalCourseIds)) {
            return;
        }

        $existingEnrollments = Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $optionalCourseIds)
            ->get()
            ->keyBy('course_id');

        foreach ($optionalCourseIds as $courseId) {
            $existing = $existingEnrollments->get($courseId);

            if ($existing) {
                if ($existing->enrollment_type !== Enrollment::TYPE_OPTIONAL) {
                    $existing->update([
                        'enrollment_type' => Enrollment::TYPE_OPTIONAL,
                    ]);
                }
                continue;
            }

            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $courseId,
                'enrollment_type' => Enrollment::TYPE_OPTIONAL,
                'status' => Enrollment::STATUS_NOT_STARTED,
                'source' => Enrollment::SOURCE_REQUIREMENTS,
            ]);
        }
    }

    private function downgradeNoLongerRequired(User $user, array $requiredCourseIds): void
    {
        $query = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('enrollment_type', Enrollment::TYPE_MANDATORY);

        if (!empty($requiredCourseIds)) {
            $query->whereNotIn('course_id', $requiredCourseIds);
        }

        $query->update([
            'enrollment_type' => Enrollment::TYPE_OPTIONAL,
        ]);
    }

}
