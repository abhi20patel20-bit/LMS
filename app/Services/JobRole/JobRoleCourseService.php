<?php

namespace App\Services\JobRole;

use App\Models\JobRole;
use Illuminate\Support\Collection;

class JobRoleCourseService
{
    /**
     * Get mandatory courses inherited from job family + role-specific mandatory courses.
     */
    public function getEffectiveMandatoryCourses(JobRole $jobRole): Collection
    {
        $jobRole->loadMissing([
            'jobFamily.courses',
            'courses',
        ]);

        $familyCourses = ($jobRole->jobFamily?->courses ?? collect())->filter(function ($course) {
            return (bool) ($course->pivot?->mandatory ?? true);
        });
        $roleCourses = ($jobRole->courses ?? collect())->filter(function ($course) {
            return (bool) ($course->pivot?->mandatory);
        });

        return $familyCourses
            ->merge($roleCourses)
            ->unique('id')
            ->values();
    }
}
