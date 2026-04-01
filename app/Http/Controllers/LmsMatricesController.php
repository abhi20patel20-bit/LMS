<?php

namespace App\Http\Controllers;

use App\Models\JobFamily;
use App\Models\JobRole;
use App\Services\Lms\MatrixFiltersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsMatricesController extends Controller
{
    public function filters(Request $request, MatrixFiltersService $service): JsonResponse
    {
        $user = $request->user();

        $jobFamilies = $service->getJobFamiliesForUser($user)
            ->map(function ($family) {
                return [
                    'id' => $family->id,
                    'name' => $family->name,
                ];
            })
            ->values();

        $defaultJobFamilyId = $user?->jobRole?->job_family_id ?? $jobFamilies->first()['id'] ?? null;
        $defaultJobRoleId = $user?->job_role_id ?? null;

        return new JsonResponse([
            'job_families' => $jobFamilies,
            'default_job_family_id' => $defaultJobFamilyId,
            'default_job_role_id' => $defaultJobRoleId,
        ]);
    }

    public function categories(Request $request, MatrixFiltersService $service): JsonResponse
    {
        $data = $request->validate([
            'job_family_id' => ['required', 'integer', 'exists:job_families,id'],
        ]);

        $jobFamily = JobFamily::query()->find($data['job_family_id']);
        if (!$jobFamily) {
            $payload = [
                'categories' => [],
            ];
            if (app()->environment('local')) {
                $payload['debug'] = [
                    'job_family_id' => $data['job_family_id'],
                    'message' => 'Job family not found.',
                ];
            }
            return new JsonResponse($payload);
        }

        $categories = $service->getCategoriesForJobFamily((int) $data['job_family_id'])
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            })
            ->values()
            ->all();

        array_unshift($categories, [
            'id' => 'all',
            'name' => 'All Categories',
        ]);

        $payload = [
            'categories' => $categories,
        ];

        if (app()->environment('local')) {
            $categoryCount = max(count($categories) - 1, 0);
            $payload['debug'] = [
                'job_family_id' => $data['job_family_id'],
                'category_count' => $categoryCount,
                'message' => $categoryCount === 0 ? 'No categories for this job family.' : null,
            ];
        }

        return new JsonResponse($payload);
    }

    public function jobRoles(Request $request, MatrixFiltersService $service): JsonResponse
    {
        $data = $request->validate([
            'job_family_id' => ['required', 'integer', 'exists:job_families,id'],
        ]);

        $jobFamily = JobFamily::query()->find($data['job_family_id']);
        if (!$jobFamily) {
            $payload = [
                'job_roles' => [],
            ];
            if (app()->environment('local')) {
                $payload['debug'] = [
                    'job_family_id' => $data['job_family_id'],
                    'message' => 'Job family not found.',
                ];
            }
            return new JsonResponse($payload);
        }

        $roles = $service->getJobRolesForJobFamily((int) $data['job_family_id'])
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            })
            ->values();

        $payload = [
            'job_roles' => $roles,
        ];

        if (app()->environment('local')) {
            $payload['debug'] = [
                'job_family_id' => $data['job_family_id'],
                'job_role_count' => $roles->count(),
                'message' => $roles->isEmpty() ? 'No job roles for this job family.' : null,
            ];
        }

        return new JsonResponse($payload);
    }

    public function courses(Request $request, MatrixFiltersService $service): JsonResponse
    {
        $data = $request->validate([
            'job_role_id' => ['required', 'integer', 'exists:job_roles,id'],
            'category_id' => ['nullable'],
        ]);

        $rawCategory = $data['category_id'] ?? null;
        $normalizedCategory = $this->normalizeCategoryId($rawCategory);

        if ($rawCategory !== null && $normalizedCategory === null && !in_array($rawCategory, ['all', 'null', ''], true)) {
            return new JsonResponse([
                'message' => 'Invalid category_id value.',
            ], 422);
        }

        if ($normalizedCategory !== null) {
            $request->merge(['category_id' => $normalizedCategory]);
            $request->validate([
                'category_id' => ['integer', 'exists:course_categories,id'],
            ]);
        }

        $courses = $service->getCoursesForJobRole(
            (int) $data['job_role_id'],
            $normalizedCategory,
            $request->user()
        );

        if (app()->environment('local')) {
            $jobFamilyId = JobRole::query()
                ->whereKey($data['job_role_id'])
                ->value('job_family_id');

            $courses['debug'] = [
                'job_role_id' => (int) $data['job_role_id'],
                'job_family_id' => $jobFamilyId,
                'category_id' => $normalizedCategory,
                'mandatory_count' => count($courses['mandatory'] ?? []),
                'optional_count' => count($courses['optional'] ?? []),
            ];
        }

        return new JsonResponse($courses);
    }

    private function normalizeCategoryId($value): ?int
    {
        if ($value === null || $value === '' || $value === 'all' || $value === 'null') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
