<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobFamilyStoreRequest;
use App\Http\Requests\JobFamilyUpdateRequest;
use App\Models\JobFamily;
use App\Services\Requirements\JobFamilyRequirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JobFamilyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read job families', ['only' => ['index', 'show']]);
        $this->middleware('permission:create job families', ['only' => ['store']]);
        $this->middleware('permission:update job families', ['only' => ['update']]);
        $this->middleware('permission:delete job families', ['only' => ['destroy']]);
    }

    public function index(): Response
    {
        return Inertia::render('JobFamily/Index');
    }

    public function getJobFamilies(): JsonResponse
    {
        $jobFamilies = JobFamily::visibleTo(auth()->user())
            ->with(['company:id,name', 'courses:id,title,course_category_id'])
            ->get([
                'id',
                'name',
                'description',
                'company_id',
                'created_at',
                'updated_at',
            ]);

        return response()->json(['jobFamilies' => $jobFamilies], 200);
    }

    public function store(JobFamilyStoreRequest $request, JobFamilyRequirementService $requirementService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $jobFamily = JobFamily::create([
                'name' => $request->name,
                'description' => $request->description,
                'company_id' => $request->company_id,
            ]);

            if ($request->has('course_ids')) {
                $requirementService->syncCourses($jobFamily, $request->all(), $request->user());
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'Job family created successfully',
                'data'    => $jobFamily
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
        $jobFamily = JobFamily::visibleTo(auth()->user())
            ->with(['company:id,name', 'courses:id,title,course_category_id'])
            ->findOrFail($id);

        return new JsonResponse(['data' => $jobFamily], 200);
    }

    public function update(JobFamilyUpdateRequest $request, int $id, JobFamilyRequirementService $requirementService): JsonResponse
    {
        DB::beginTransaction();

        try {
            $jobFamily = JobFamily::visibleTo(auth()->user())->findOrFail($id);

            $jobFamily->update([
                'name' => $request->name ?? $jobFamily->name,
                'description' => $request->description,
                'company_id' => $request->company_id ?? $jobFamily->company_id,
            ]);

            if ($request->has('course_ids')) {
                $requirementService->syncCourses($jobFamily, $request->all(), $request->user());
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'Job family updated successfully',
                'data'    => $jobFamily
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
            $jobFamily = JobFamily::visibleTo(auth()->user())->findOrFail($id);
            $jobFamily->delete();

            return new JsonResponse(['message' => 'Job family deleted successfully'], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

}
