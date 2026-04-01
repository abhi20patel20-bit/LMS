<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Services\Booking\CourseBookingService;
use App\Services\Booking\CourseSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsBookingController extends Controller
{
    public function metadata(Request $request, Course $course, CourseSessionService $sessionService): JsonResponse
    {
        $user = $request->user();

        $providers = CourseSession::query()
            ->where('course_id', $course->id)
            ->where('status', '!=', CourseSession::STATUS_CANCELLED)
            ->where('starts_at', '>=', now()->startOfDay())
            ->with('provider:id,name')
            ->get()
            ->pluck('provider')
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                ];
            })
            ->all();

        $currentBooking = CourseBooking::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', CourseBooking::STATUS_BOOKED)
            ->with(['session.provider'])
            ->first();

        $bookingPayload = null;
        if ($currentBooking) {
            $bookingPayload = [
                'id' => $currentBooking->id,
                'status' => $currentBooking->status,
                'session' => $currentBooking->session
                    ? [
                        'id' => $currentBooking->session->id,
                        'starts_at' => $currentBooking->session->starts_at?->toDateTimeString(),
                        'ends_at' => $currentBooking->session->ends_at?->toDateTimeString(),
                        'provider' => $currentBooking->session->provider
                            ? [
                                'id' => $currentBooking->session->provider->id,
                                'name' => $currentBooking->session->provider->name,
                            ]
                            : null,
                        'location' => $currentBooking->session->location,
                    ]
                    : null,
            ];
        }

        $dates = $sessionService->listAvailableDates($course->id);

        return new JsonResponse([
            'delivery_type' => $course->delivery_type,
            'booking_required' => $course->booking_required,
            'providers' => $providers,
            'current_booking' => $bookingPayload,
            'next_available_dates' => array_slice($dates, 0, 5),
        ]);
    }

    public function dates(Request $request, Course $course, CourseSessionService $sessionService): JsonResponse
    {
        $data = $request->validate([
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
        ]);

        $dates = $sessionService->listAvailableDates($course->id, $data['provider_id'] ?? null);

        return new JsonResponse([
            'dates' => $dates,
        ]);
    }

    public function sessions(Request $request, Course $course, CourseSessionService $sessionService): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'provider_id' => ['nullable', 'integer', 'exists:providers,id'],
        ]);

        $sessions = $sessionService->listSessions(
            $course->id,
            Carbon::createFromFormat('Y-m-d', $data['date']),
            $data['provider_id'] ?? null
        );

        return new JsonResponse([
            'sessions' => $sessions,
        ]);
    }

    public function book(Request $request, Course $course, CourseBookingService $bookingService): JsonResponse
    {
        if ($course->delivery_type !== 'scheduled') {
            return new JsonResponse(['message' => 'Booking is not required for this course.'], 422);
        }

        $data = $request->validate([
            'course_session_id' => ['required', 'integer', 'exists:course_sessions,id'],
            'enrollment_type_context' => ['nullable', 'string', 'in:mandatory,optional'],
        ]);

        $session = CourseSession::query()
            ->whereKey($data['course_session_id'])
            ->where('course_id', $course->id)
            ->firstOrFail();

        $result = $bookingService->bookSession(
            $request->user(),
            $session,
            $data['enrollment_type_context'] ?? null
        );

        if ($result['status'] === 'full') {
            return new JsonResponse(['status' => 'full', 'message' => 'Session is full.'], 409);
        }

        if ($result['status'] === 'closed') {
            return new JsonResponse(['status' => 'closed', 'message' => 'Session is not open.'], 422);
        }

        return new JsonResponse([
            'status' => $result['status'],
            'booking' => $result['booking'] ?? null,
            'enrollment' => $result['enrollment'] ?? null,
        ], 200);
    }

    public function updateBooking(Request $request, Course $course, CourseBookingService $bookingService): JsonResponse
    {
        if ($course->delivery_type !== 'scheduled') {
            return new JsonResponse(['message' => 'Booking is not required for this course.'], 422);
        }

        $data = $request->validate([
            'course_session_id' => ['required', 'integer', 'exists:course_sessions,id'],
        ]);

        $session = CourseSession::query()
            ->whereKey($data['course_session_id'])
            ->where('course_id', $course->id)
            ->firstOrFail();

        $result = $bookingService->updateBooking($request->user(), $course, $session);

        if ($result['status'] === 'full') {
            return new JsonResponse(['status' => 'full', 'message' => 'Session is full.'], 409);
        }

        if ($result['status'] === 'closed') {
            return new JsonResponse(['status' => 'closed', 'message' => 'Session is not open.'], 422);
        }

        if ($result['status'] === 'not_booked') {
            return new JsonResponse(['status' => 'not_booked', 'message' => 'No active booking found.'], 404);
        }

        if ($result['status'] === 'invalid_session') {
            return new JsonResponse(['status' => 'invalid_session', 'message' => 'Session does not match course.'], 422);
        }

        return new JsonResponse([
            'status' => $result['status'],
            'booking' => $result['booking'] ?? null,
            'enrollment' => $result['enrollment'] ?? null,
        ], 200);
    }

    public function joinWaitlist(Request $request, Course $course, CourseBookingService $bookingService): JsonResponse
    {
        if ($course->delivery_type !== 'scheduled') {
            return new JsonResponse(['message' => 'Waitlist is not required for this course.'], 422);
        }

        $data = $request->validate([
            'course_session_id' => ['required', 'integer', 'exists:course_sessions,id'],
        ]);

        $session = CourseSession::query()
            ->whereKey($data['course_session_id'])
            ->where('course_id', $course->id)
            ->firstOrFail();

        $result = $bookingService->joinWaitlist($request->user(), $session);

        if ($result['status'] === 'closed') {
            return new JsonResponse(['status' => 'closed', 'message' => 'Session is not open.'], 422);
        }

        return new JsonResponse([
            'status' => $result['status'],
            'waitlist' => $result['waitlist'] ?? null,
            'position' => $result['position'] ?? null,
        ], 200);
    }

    public function cancelBooking(Request $request, CourseBooking $booking, CourseBookingService $bookingService): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return new JsonResponse(['message' => 'Unauthorized booking cancellation.'], 403);
        }

        $updatedBooking = $bookingService->cancelBooking($request->user(), $booking);

        return new JsonResponse([
            'booking' => $updatedBooking,
        ], 200);
    }
}
