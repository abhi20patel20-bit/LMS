<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobRoleStoreRequest;
use App\Http\Requests\JobRoleUpdateRequest;
use App\Models\JobRole;
use App\Services\Requirements\JobRoleRequirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JobRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read job roles', ['only' => ['index', 'show']]);
        $this->middleware('permission:create job roles', ['only' => ['store']]);
        $this->middleware('permission:update job roles', ['only' => ['update']]);
        $this->middleware('permission:delete job roles', ['only' => ['destroy']]);
    }

    public function index(): Response
    {
        return Inertia::render('JobRole/Index');
    }

    public function getJobRoles(): JsonResponse
    {
        $jobRoles = JobRole::visibleTo(auth()->user())
            ->with(['jobFamily:id,name', 'courses:id,title,course_category_id'])
            ->get([
                'id',
                'name',
                'description',
                'job_family_id',
                'created_at',
                'updated_at',
            ]);

        return response()->json(['jobRoles' => $jobRoles], 200);
    }

    public function store(JobRoleStoreRequest $request, JobRoleRequirementService $requirementService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $jobRole = JobRole::create([
                'name' => $request->name,
                'description' => $request->description,
                'job_family_id' => $request->job_family_id,
            ]);

            if ($request->has('course_ids')) {
                $requirementService->syncCourses($jobRole, $request->all(), $request->user());
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'Job role created successfully',
                'data'    => $jobRole
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $jobRole = JobRole::visibleTo(auth()->user())
            ->with(['jobFamily:id,name'])
            ->findOrFail($id);

        return new JsonResponse(['data' => $jobRole], 200);
    }

    public function update(JobRoleUpdateRequest $request, int $id, JobRoleRequirementService $requirementService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $jobRole = JobRole::findOrFail($id);

            $jobRole->update([
                'name' => $request->name ?? $jobRole->name,
                'description' => $request->description,
                'job_family_id' => $request->job_family_id ?? $jobRole->job_family_id,
            ]);

            if ($request->has('course_ids')) {
                $requirementService->syncCourses($jobRole, $request->all(), $request->user());
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'Job role updated successfully',
                'data'    => $jobRole
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $jobRole = JobRole::visibleTo(auth()->user())->findOrFail($id);
            $jobRole->delete();

            return new JsonResponse(['message' => 'Job role deleted successfully'], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

}
