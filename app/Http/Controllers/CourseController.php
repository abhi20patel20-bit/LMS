<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CourseUpdateRequest;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read courses', ['only' => ['index', 'show']]);
        $this->middleware('permission:create courses', ['only' => ['store']]);
        $this->middleware('permission:update courses', ['only' => ['update']]);
        $this->middleware('permission:delete courses', ['only' => ['destroy']]);
    }

    /**
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Course/Index');

    }

    public function getCourses()
    {
        $courses = Course::with(['category:id,name', 'providers:id,name'])
            ->get([
                'id',
                'title',
                'description',
                'course_category_id',
                'price',
                'settings',
                'status',
                'course_type',
                'duration',
                'delivery_type',
                'default_capacity',
                'booking_required',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'courses' => $courses
        ], 200);
    }

    /**
     * Create a new course
     */
    public function store(CourseStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $deliveryType = $request->input('delivery_type', 'self_paced');
            $bookingRequired = $request->has('booking_required')
                ? $request->boolean('booking_required')
                : $deliveryType === 'scheduled';

            $course = Course::create([
                'title'          => $request->title,
                'description'   => $request->description,
                'course_category_id' => $request->course_category_id,
                'price'         => $request->price,
                'status'        => $request->status,
                'course_type'   => $request->course_type,
                'duration'      => $request->duration,
                'settings'   => json_encode($request->settings),
                'delivery_type' => $deliveryType,
                'default_capacity' => $request->default_capacity,
                'booking_required' => $bookingRequired,
            ]);

            if ($request->filled('provider_ids')) {
                $course->providers()->sync($request->provider_ids);
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'Course created successfully',
                'data'    => $course
            ], 201);

        } catch (\Throwable $th) {

            DB::rollBack();
            report($th);

            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Show single course
     */
    public function show(int $id): JsonResponse
    {
        $course = Course::visibleTo(auth()->user())
            ->with(['category:id,name', 'providers:id,name'])
            ->findOrFail($id);

        return new JsonResponse([
            'data' => $course
        ], 200);
    }

    /**
     * Update existing course
     */
    public function update(CourseUpdateRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $course = Course::findOrFail($id);

            $deliveryType = $request->input('delivery_type', $course->delivery_type);
            $bookingRequired = $request->has('booking_required')
                ? $request->boolean('booking_required')
                : $deliveryType === 'scheduled';

            $course->update([
                'title'        => $request->title,
                'description' => $request->description,
                'course_category_id' => $request->course_category_id ?? $course->course_category_id,
                'price'   => $request->price,
                'status'      => $request->status ?? $course->status,
                'course_type' => $request->course_type ?? $course->course_type,
                'duration'    => $request->duration,
                'settings'   => json_encode($request->settings),
                'delivery_type' => $deliveryType,
                'default_capacity' => $request->default_capacity ?? $course->default_capacity,
                'booking_required' => $bookingRequired,
            ]);

            if ($request->has('provider_ids')) {
                $course->providers()->sync($request->provider_ids ?? []);
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'Course updated successfully',
                'data'    => $course
            ], 201);

        } catch (\Throwable $th) {

            DB::rollBack();
            report($th);
            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete course
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $course = Course::visibleTo(auth()->user())->findOrFail($id);
            $course->delete();

            return new JsonResponse([
                'message' => 'Course deleted successfully'
            ], 200);

        } catch (\Throwable $th) {

            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
