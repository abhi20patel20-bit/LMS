<?php

namespace App\Services\Lms;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\JobFamily;
use App\Models\JobRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatrixFiltersService
{
    public function getJobFamiliesForUser(User $user): Collection
    {
        $query = JobFamily::query()->orderBy('name');

        if ($user->hasAnyRole(['super-admin', 'company-admin', 'department-admin'])) {
            $query = JobFamily::visibleTo($user)->orderBy('name');
        } elseif ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query->get(['id', 'name', 'company_id']);
    }

    public function getCategoriesForJobFamily(int $jobFamilyId): Collection
    {
        $roleIds = JobRole::query()
            ->where('job_family_id', $jobFamilyId)
            ->pluck('id')
            ->all();

        $familyCourseIds = DB::table('course_job_family')
            ->where('job_family_id', $jobFamilyId)
            ->pluck('course_id')
            ->all();

        $roleCourseIds = [];
        if (!empty($roleIds)) {
            $roleCourseIds = DB::table('course_job_role')
                ->whereIn('job_role_id', $roleIds)
                ->where('visibility', 'visible')
                ->pluck('course_id')
                ->all();
        }

        $courseIds = array_values(array_unique(array_merge($familyCourseIds, $roleCourseIds)));
        if (empty($courseIds)) {
            return collect();
        }

        $categoryIds = Course::query()
            ->whereIn('id', $courseIds)
            ->whereNotNull('course_category_id')
            ->pluck('course_category_id')
            ->unique()
            ->values()
            ->all();

        if (empty($categoryIds)) {
            return collect();
        }

        return CourseCategory::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getJobRolesForJobFamily(int $jobFamilyId): Collection
    {
        return JobRole::query()
            ->where('job_family_id', $jobFamilyId)
            ->orderBy('name')
            ->get(['id', 'name', 'job_family_id']);
    }

    public function getCoursesForJobRole(int $jobRoleId, ?int $categoryId, User $user): array
    {
        $jobFamilyId = JobRole::query()
            ->where('id', $jobRoleId)
            ->value('job_family_id');

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

        $mandatoryRoleIds = DB::table('course_job_role')
            ->where('job_role_id', $jobRoleId)
            ->where('mandatory', true)
            ->where('visibility', 'visible')
            ->pluck('course_id')
            ->all();

        $mandatoryIds = array_values(array_unique(array_merge($familyMandatoryIds, $mandatoryRoleIds)));

        $optionalRoleIds = DB::table('course_job_role')
            ->where('job_role_id', $jobRoleId)
            ->where('mandatory', false)
            ->where('visibility', 'visible')
            ->pluck('course_id')
            ->all();

        $optionalIds = array_values(array_diff(
            array_unique(array_merge($familyOptionalIds, $optionalRoleIds)),
            $mandatoryIds
        ));

        $mandatoryCourses = $this->loadCourses($mandatoryIds, $categoryId);
        $optionalCourses = $this->loadCourses($optionalIds, $categoryId);

        $courseIds = array_merge(
            $mandatoryCourses->pluck('id')->all(),
            $optionalCourses->pluck('id')->all()
        );
        $enrollments = $this->loadEnrollments($user, $courseIds);

        return [
            'mandatory' => $mandatoryCourses
                ->map(function (Course $course) use ($enrollments) {
                    return $this->formatCourse($course, 'mandatory', $enrollments[$course->id] ?? null);
                })
                ->values()
                ->all(),
            'optional' => $optionalCourses
                ->map(function (Course $course) use ($enrollments) {
                    return $this->formatCourse($course, 'optional', $enrollments[$course->id] ?? null);
                })
                ->values()
                ->all(),
        ];
    }

    private function loadCourses(array $courseIds, ?int $categoryId): Collection
    {
        if (empty($courseIds)) {
            return collect();
        }

        $query = Course::query()
            ->whereIn('id', $courseIds)
            ->with([
                'category:id,name',
                'providers:id,name',
            ])
            ->orderBy('title');

        if ($categoryId) {
            $query->where('course_category_id', $categoryId);
        }

        return $query->get([
            'id',
            'title',
            'description',
            'duration',
            'course_category_id',
            'delivery_type',
            'booking_required',
            'updated_at',
        ]);
    }

    private function loadEnrollments(User $user, array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)
            ->get(['id', 'course_id', 'status', 'enrollment_type'])
            ->keyBy('course_id')
            ->all();
    }

    private function formatCourse(Course $course, string $type, ?Enrollment $enrollment): array
    {
        $providerNames = $course->providers
            ? $course->providers->map(function ($provider) {
                return $provider->name;
            })->filter()->values()
            : collect();

        return [
            'id' => $course->id,
            'title' => $course->title,
            'duration' => $course->duration,
            'delivery_type' => $course->delivery_type,
            'booking_required' => $course->booking_required,
            'category_name' => $course->category?->name,
            'provider_name' => $providerNames->implode(', '),
            'effective_type' => $type,
            'type' => $type,
            'enrollment_status' => $enrollment?->status,
            'enrollment_type' => $enrollment?->enrollment_type,
            'category' => $course->category
                ? [
                    'id' => $course->category->id,
                    'name' => $course->category->name,
                ]
                : null,
            'providers' => $course->providers
                ? $course->providers->map(function ($provider) {
                    return [
                        'id' => $provider->id,
                        'name' => $provider->name,
                    ];
                })->values()
                : [],
        ];
    }
}
