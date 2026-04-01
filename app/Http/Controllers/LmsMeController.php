<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\User\UserLearningService;
use Illuminate\Http\JsonResponse;

class LmsMeController extends Controller
{
    public function dashboard(UserLearningService $service): JsonResponse
    {
        try {
            $data = $service->getDashboard(auth()->user());
            return new JsonResponse($data, 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function learning(UserLearningService $service): JsonResponse
    {
        try {
            $data = $service->getLearning(auth()->user());
            return new JsonResponse($data, 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function enroll(Course $course, UserLearningService $service): JsonResponse
    {
        try {
            if ($course->delivery_type === 'scheduled') {
                return new JsonResponse(['message' => 'This course requires session booking.'], 422);
            }

            $enrollment = $service->enroll(auth()->user(), $course);
            return new JsonResponse(['enrollment' => $enrollment], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function start(Course $course, UserLearningService $service): JsonResponse
    {
        try {
            if ($course->delivery_type === 'scheduled') {
                return new JsonResponse(['message' => 'This course requires session booking.'], 422);
            }

            $enrollment = $service->start(auth()->user(), $course);
            return new JsonResponse(['enrollment' => $enrollment], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function complete(Course $course, UserLearningService $service): JsonResponse
    {
        try {
            $enrollment = $service->complete(auth()->user(), $course);
            return new JsonResponse(['enrollment' => $enrollment], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }

    public function cancel(Course $course, UserLearningService $service): JsonResponse
    {
        try {
            $enrollment = $service->cancel(auth()->user(), $course);
            return new JsonResponse(['enrollment' => $enrollment], 200);
        } catch (\RuntimeException $th) {
            return new JsonResponse(['message' => $th->getMessage()], 422);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }
}
