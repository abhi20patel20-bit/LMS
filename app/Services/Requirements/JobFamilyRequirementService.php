<?php

namespace App\Services\Requirements;

use App\Jobs\SyncEnrollmentsForJobFamilyJob;
use App\Models\JobFamily;
use App\Models\User;

class JobFamilyRequirementService
{
    public function syncCourses(JobFamily $jobFamily, array $payload, User $actor): void
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
            ];
        }

        $jobFamily->courses()->sync($syncData);

        SyncEnrollmentsForJobFamilyJob::dispatch($jobFamily->id, $actor->id)->afterCommit();
    }
}
