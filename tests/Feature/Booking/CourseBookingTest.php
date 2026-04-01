<?php

namespace Tests\Feature\Booking;

use App\Models\Course;
use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Models\CourseWaitlist;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use App\Services\Booking\CourseBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeScheduledSession(int $capacity = 1): CourseSession
    {
        $course = Course::factory()->create([
            'delivery_type' => 'scheduled',
            'booking_required' => true,
        ]);

        $provider = Provider::create([
            'name' => 'Test Provider',
            'description' => 'Provider for booking tests',
        ]);

        return CourseSession::create([
            'course_id' => $course->id,
            'provider_id' => $provider->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 0),
            'capacity' => $capacity,
            'status' => 'open',
        ]);
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_booking_respects_capacity_and_waitlist(): void
    {
        $session = $this->makeScheduledSession(1);
        $userOne = $this->makeUser();
        $userTwo = $this->makeUser();

        $service = app(CourseBookingService::class);

        $first = $service->bookSession($userOne, $session);
        $this->assertSame('booked', $first['status']);

        $second = $service->bookSession($userTwo, $session);
        $this->assertSame('full', $second['status']);

        $waitlist = $service->joinWaitlist($userTwo, $session);
        $this->assertSame('waiting', $waitlist['status']);

        $this->assertDatabaseCount('course_bookings', 1);
        $this->assertDatabaseHas('course_waitlist', [
            'user_id' => $userTwo->id,
            'course_session_id' => $session->id,
            'status' => CourseWaitlist::STATUS_WAITING,
        ]);
    }

    public function test_cancel_booking_promotes_waitlist(): void
    {
        $session = $this->makeScheduledSession(1);
        $userOne = $this->makeUser();
        $userTwo = $this->makeUser();

        $service = app(CourseBookingService::class);

        $service->bookSession($userOne, $session);
        $service->joinWaitlist($userTwo, $session);

        $booking = CourseBooking::query()
            ->where('user_id', $userOne->id)
            ->where('course_session_id', $session->id)
            ->firstOrFail();

        $service->cancelBooking($userOne, $booking);

        $this->assertDatabaseHas('course_bookings', [
            'user_id' => $userTwo->id,
            'course_session_id' => $session->id,
            'status' => CourseBooking::STATUS_BOOKED,
        ]);

        $this->assertDatabaseHas('course_waitlist', [
            'user_id' => $userTwo->id,
            'course_session_id' => $session->id,
            'status' => CourseWaitlist::STATUS_PROMOTED,
        ]);
    }

    public function test_user_cannot_double_book_same_session(): void
    {
        $session = $this->makeScheduledSession(2);
        $user = $this->makeUser();

        $service = app(CourseBookingService::class);

        $first = $service->bookSession($user, $session);
        $this->assertSame('booked', $first['status']);

        $second = $service->bookSession($user, $session);
        $this->assertSame('already_booked', $second['status']);

        $this->assertDatabaseCount('course_bookings', 1);
    }

    public function test_employee_permissions_allow_booking_but_block_admin_sessions(): void
    {
        $session = $this->makeScheduledSession(1);
        $course = Course::query()->findOrFail($session->course_id);

        $permission = Permission::create([
            'name' => 'read my learning',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = $this->makeUser();
        $user->assignRole($role);

        $this->actingAs($user);

        $this->getJson("/lms/me/courses/{$course->id}/booking/metadata")
            ->assertOk();

        $provider = Provider::query()->firstOrFail();

        $this->postJson("/courses/{$course->id}/sessions", [
            'provider_id' => $provider->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
            'ends_at' => now()->addDays(2)->setTime(12, 0)->toDateTimeString(),
            'capacity' => 2,
            'status' => 'open',
        ])->assertForbidden();
    }
}
