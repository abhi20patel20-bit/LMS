<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\DepartmentStoreRequest;
use App\Http\Requests\DepartmentUpdateRequest;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read departments', ['only' => ['index', 'show']]);
        $this->middleware('permission:create departments', ['only' => ['store']]);
        $this->middleware('permission:update departments', ['only' => ['update']]);
        $this->middleware('permission:delete departments', ['only' => ['destroy']]);
    }

    /**
     * Display the department listing page.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Department/Index');
    }

    /**
     * Fetch all departments for the datatable.
     *
     * @return JsonResponse
     */
    public function getDepartments(): JsonResponse
    {
        $departments = Department::visibleTo(auth()->user())
            ->select([
                'id',
                'name',
                'slug',
                'custom_domain',
                'subscription_type',
                'settings',
                'created_at',
                'updated_at',
            ])
            ->latest('id')
            ->get();

        return new JsonResponse([
            'departments' => $departments
        ], 200);
    }

    /**
     * Create a new department
     *
     * @param DepartmentStoreRequest $request
     * @return JsonResponse
     */
    public function store(DepartmentStoreRequest $request): JsonResponse
    {
        try {
            $department = Department::create([
                'name'              => $request->name,
                'slug'              => $request->slug,
                'custom_domain'     => $request->custom_domain,
                'subscription_type' => $request->subscription_type,
                'settings'          => $request->settings,
            ]);

            return new JsonResponse([
                'message' => 'Department created successfully',
                'data'    => $department
            ], 201);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show single department
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $department = Department::visibleTo(auth()->user())->findOrFail($id);

        return new JsonResponse([
            'data' => $department
        ], 200);
    }

    /**
     * Update existing department
     *
     * @param DepartmentUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(DepartmentUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $department = Department::visibleTo(auth()->user())->findOrFail($id);

            $department->update([
                'name'              => $request->name,
                'slug'              => $request->slug,
                'custom_domain'     => $request->custom_domain,
                'subscription_type' => $request->subscription_type,
                'settings'          => $request->settings,
            ]);

            return new JsonResponse([
                'message' => 'Department updated successfully',
                'data'    => $department
            ], 201);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Delete department
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $department = Department::visibleTo(auth()->user())->findOrFail($id);
            $department->delete();

            return new JsonResponse([
                'message' => 'Department deleted successfully'
            ], 201);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
