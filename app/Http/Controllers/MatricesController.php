<?php

namespace App\Http\Controllers;

use App\Models\JobFamily;
use App\Models\JobRole;
use App\Services\JobRole\JobRoleCourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MatricesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read metrics|read courses', ['only' => ['index', 'getJobRoles', 'getRequiredCourses']]);
    }

    public function index(): Response
    {
        return Inertia::render('Matrices/Index');
    }

    public function getJobRoles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_family_id' => ['required', 'integer', 'exists:job_families,id'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
        ]);

        $jobFamily = JobFamily::visibleTo(auth()->user())
            ->whereKey($data['job_family_id'])
            ->firstOrFail();

        $jobRolesQuery = JobRole::visibleTo(auth()->user())
            ->where('job_family_id', $jobFamily->id);

        if (!empty($data['category_id'])) {
            $categoryId = $data['category_id'];
            $familyHasCategory = $jobFamily->courses()
                ->where('course_category_id', $categoryId)
                ->exists();

            if (!$familyHasCategory) {
                $jobRolesQuery->whereHas('courses', function ($query) use ($categoryId) {
                    $query->where('course_category_id', $categoryId)
                        ->wherePivot('mandatory', true);
                });
            }
        }

        $jobRoles = $jobRolesQuery->get(['id', 'name']);

        return response()->json(['jobRoles' => $jobRoles], 200);
    }

    public function getRequiredCourses(Request $request, JobRoleCourseService $jobRoleCourseService): JsonResponse
    {
        $data = $request->validate([
            'job_family_id' => ['required', 'integer', 'exists:job_families,id'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'job_role_id' => ['required', 'integer', 'exists:job_roles,id'],
        ]);

        $jobFamily = JobFamily::visibleTo(auth()->user())
            ->whereKey($data['job_family_id'])
            ->firstOrFail();

        $jobRole = JobRole::visibleTo(auth()->user())
            ->whereKey($data['job_role_id'])
            ->where('job_family_id', $jobFamily->id)
            ->firstOrFail();

        $courses = $jobRoleCourseService
            ->getEffectiveMandatoryCourses($jobRole)
            ->when(!empty($data['category_id']), function ($collection) use ($data) {
                return $collection->where('course_category_id', $data['category_id']);
            })
            ->values()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'course_type' => $course->course_type,
                    'duration' => $course->duration,
                    'status' => $course->status,
                ];
            })
            ->values();

        return response()->json(['courses' => $courses], 200);
    }
}
