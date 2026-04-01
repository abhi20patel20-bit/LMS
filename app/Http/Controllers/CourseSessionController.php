<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSession;
use App\Services\Booking\CourseBookingService;
use App\Services\Booking\CourseSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:update courses');
    }

    public function index(Course $course, CourseSessionService $service): JsonResponse
    {
        return new JsonResponse([
            'sessions' => $service->listAdminSessions($course),
        ]);
    }

    public function store(Request $request, Course $course, CourseSessionService $service): JsonResponse
    {
        $data = $this->validateSession($request, false);
        $session = $service->createSession($course, $data);

        return new JsonResponse([
            'message' => 'Session created successfully.',
            'session' => $session,
        ], 201);
    }

    public function update(
        Request $request,
        Course $course,
        CourseSession $session,
        CourseSessionService $service,
        CourseBookingService $bookingService
    ): JsonResponse {
        if ($session->course_id !== $course->id) {
            return new JsonResponse(['message' => 'Session not found for this course.'], 404);
        }

        $data = $this->validateSession($request, true);
        $result = $service->updateSession($session, $data);

        if ($result['status_changed_to_cancelled']) {
            $service->cancelSession($result['session']);
        }

        if ($result['capacity_increased'] || $result['status_changed_to_open']) {
            $bookingService->promoteWaitlist($result['session']);
        }

        return new JsonResponse([
            'message' => 'Session updated successfully.',
            'session' => $result['session'],
        ], 200);
    }

    public function destroy(Course $course, CourseSession $session, CourseSessionService $service): JsonResponse
    {
        if ($session->course_id !== $course->id) {
            return new JsonResponse(['message' => 'Session not found for this course.'], 404);
        }

        $service->deleteSession($session);

        return new JsonResponse([
            'message' => 'Session deleted successfully.',
        ], 200);
    }

    private function validateSession(Request $request, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'provider_id' => [$required, 'integer', 'exists:providers,id'],
            'starts_at' => [$required, 'date'],
            'ends_at' => [$required, 'date', 'after:starts_at'],
            'capacity' => [$required, 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:open,closed,cancelled'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
