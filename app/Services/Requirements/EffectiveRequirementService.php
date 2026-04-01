<?php

namespace App\Services\Requirements;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class EffectiveRequirementService
{
    /**
     * Return the unique union of mandatory course IDs for a user.
     */
    public function requiredMandatoryCourseIdsForUser(User $user): array
    {
        $requirements = $this->requirementCourseIdsForUser($user);

        return $requirements['mandatory'];
    }

    /**
     * Return the optional course IDs for a user (excluding mandatory).
     */
    public function optionalCourseIdsForUser(User $user): array
    {
        $requirements = $this->requirementCourseIdsForUser($user);

        return $requirements['optional'];
    }

    /**
     * Return mandatory and optional course ID sets for a user.
     *
     * @return array{mandatory: array<int>, optional: array<int>}
     */
    public function requirementCourseIdsForUser(User $user): array
    {
        $jobRoleId = $user->job_role_id;
        $jobFamilyId = null;

        if ($jobRoleId) {
            $jobFamilyId = $user->jobRole?->job_family_id;

            if (!$jobFamilyId) {
                $jobFamilyId = DB::table('job_roles')
                    ->where('id', $jobRoleId)
                    ->value('job_family_id');
            }
        }

        $familyMandatoryIds = [];
        $familyOptionalIds = [];
        if ($jobFamilyId) {
            $familyMandatoryIds = DB::table('course_job_family')
                ->where('job_family_id', $jobFamilyId)
                ->where('mandatory', true)
                ->pluck('course_id')
                ->all();

            $familyOptionalIds = DB::table('course_job_family')
                ->where('job_family_id', $jobFamilyId)
                ->where('mandatory', false)
                ->pluck('course_id')
                ->all();
        }

        $roleMandatoryIds = [];
        $roleOptionalIds = [];
        if ($jobRoleId) {
            $roleMandatoryIds = DB::table('course_job_role')
                ->where('job_role_id', $jobRoleId)
                ->where('mandatory', true)
                ->pluck('course_id')
                ->all();

            $roleOptionalIds = DB::table('course_job_role')
                ->where('job_role_id', $jobRoleId)
                ->where('mandatory', false)
                ->pluck('course_id')
                ->all();
        }

        $mandatoryIds = array_values(array_unique(array_merge($familyMandatoryIds, $roleMandatoryIds)));
        $optionalIds = array_values(array_diff(
            array_unique(array_merge($familyOptionalIds, $roleOptionalIds)),
            $mandatoryIds
        ));

        return [
            'mandatory' => $mandatoryIds,
            'optional' => $optionalIds,
        ];
    }
}
