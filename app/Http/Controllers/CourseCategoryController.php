<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseCategoryStoreRequest;
use App\Http\Requests\CourseCategoryUpdateRequest;
use App\Models\CourseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read course categories', ['only' => ['index', 'show']]);
        $this->middleware('permission:create course categories', ['only' => ['store']]);
        $this->middleware('permission:update course categories', ['only' => ['update']]);
        $this->middleware('permission:delete course categories', ['only' => ['destroy']]);
    }

    public function index(): Response
    {
        return Inertia::render('CourseCategory/Index');
    }

    public function getCourseCategories(): JsonResponse
    {
        $categories = CourseCategory::visibleTo(auth()->user())
            ->get([
                'id',
                'name',
                'description',
                'created_at',
                'updated_at',
            ]);

        return response()->json(['categories' => $categories], 200);
    }

    public function store(CourseCategoryStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = CourseCategory::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return new JsonResponse([
                'message' => 'Course category created successfully',
                'data'    => $category
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
        $category = CourseCategory::visibleTo(auth()->user())
            ->findOrFail($id);

        return new JsonResponse(['data' => $category], 200);
    }

    public function update(CourseCategoryUpdateRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $category = CourseCategory::findOrFail($id);

            $category->update([
                'name' => $request->name ?? $category->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return new JsonResponse([
                'message' => 'Course category updated successfully',
                'data'    => $category
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
            $category = CourseCategory::visibleTo(auth()->user())->findOrFail($id);
            $category->delete();

            return new JsonResponse(['message' => 'Course category deleted successfully'], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }
}
