<?php

namespace App\Services\User;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRequiredCourseService
{
    /**
     * Resolve required courses for a user with job role overrides.
     *
     * @return Collection<int, array{source: string, source_id: int|null}>
     */
    public function getEffectiveRequiredCourses(User $user): Collection
    {
        $jobRole = $user->jobRole;

        if (!$jobRole) {
            return collect();
        }

        $jobRole->loadMissing([
            'jobFamily.courses',
            'courses',
        ]);

        $required = collect();

        $jobFamily = $jobRole->jobFamily;
        $familyCourses = ($jobFamily?->courses ?? collect())->filter(function ($course) {
            return (bool) ($course->pivot?->mandatory ?? true);
        });
        foreach ($familyCourses as $course) {
            $required->put($course->id, [
                'source' => Enrollment::SOURCE_JOB_FAMILY,
                'source_id' => $jobFamily?->id,
            ]);
        }

        $roleCourses = ($jobRole->courses ?? collect())->filter(function ($course) {
            return (bool) ($course->pivot?->mandatory);
        });

        foreach ($roleCourses as $course) {
            $required->put($course->id, [
                'source' => Enrollment::SOURCE_JOB_ROLE,
                'source_id' => $jobRole->id,
            ]);
        }

        return $required;
    }
}
