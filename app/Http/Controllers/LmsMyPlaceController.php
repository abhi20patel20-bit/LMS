<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LmsMyPlaceController extends Controller
{
    public function index()
    {
        throw new NotFoundHttpException();
    }

    public function filters()
    {
        throw new NotFoundHttpException();
    }

    public function categories()
    {
        throw new NotFoundHttpException();
    }

    public function jobRoles()
    {
        throw new NotFoundHttpException();
    }

    public function courses()
    {
        throw new NotFoundHttpException();
    }

    public function course(Request $request, Course $course): JsonResponse
    {
        $course->load([
            'category:id,name',
            'providers:id,name',
        ]);

        $enrollment = $course->enrollments()
            ->where('user_id', $request->user()->id)
            ->first(['status', 'enrollment_type']);

        return new JsonResponse([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'duration' => $course->duration,
                'updated_at' => $course->updated_at,
                'delivery_type' => $course->delivery_type,
                'booking_required' => $course->booking_required,
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
                'enrollment_status' => $enrollment?->status,
                'enrollment_type' => $enrollment?->enrollment_type,
            ],
        ]);
    }
}
