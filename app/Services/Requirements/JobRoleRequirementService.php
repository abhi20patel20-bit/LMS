<?php

namespace App\Services\Requirements;

use App\Jobs\SyncEnrollmentsForJobRoleJob;
use App\Models\JobRole;
use App\Models\User;

class JobRoleRequirementService
{
    public function syncCourses(JobRole $jobRole, array $payload, User $actor): void
    {
        $mandatoryIds = $payload['mandatory_course_ids'] ?? [];
        $optionalIds = $payload['optional_course_ids'] ?? [];
        $courseIds = array_values(array_unique(array_merge(
            $payload['course_ids'] ?? [],
            $mandatoryIds,
            $optionalIds
        )));
        $mandatoryLookup = array_flip($mandatoryIds);
        $syncData = [];

        foreach ($courseIds as $courseId) {
            $syncData[$courseId] = [
                'mandatory' => array_key_exists($courseId, $mandatoryLookup),
                'visibility' => 'visible',
            ];
        }

        $jobRole->courses()->sync($syncData);

        SyncEnrollmentsForJobRoleJob::dispatch($jobRole->id, $actor->id)->afterCommit();
    }
}
