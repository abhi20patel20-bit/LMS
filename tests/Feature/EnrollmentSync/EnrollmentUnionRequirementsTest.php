<?php

namespace Tests\Feature\EnrollmentSync;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\JobFamily;
use App\Models\JobRole;
use App\Models\User;
use App\Services\User\UserEnrollmentSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentUnionRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(): array
    {
        $jobFamily = JobFamily::create([
            'name' => 'Operations',
            'description' => 'Ops family',
            'company_id' => null,
        ]);

        $jobRole = JobRole::factory()->create([
            'job_family_id' => $jobFamily->id,
        ]);

        $user = User::factory()->create([
            'job_role_id' => $jobRole->id,
        ]);

        return [$user, $jobFamily, $jobRole];
    }

    private function sync(User $user): void
    {
        app(UserEnrollmentSyncService::class)->syncUser($user);
    }

    public function test_family_only_creates_mandatory_enrollment(): void
    {
        [$user, $jobFamily] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobFamily->courses()->attach($course->id);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_MANDATORY,
        ]);
    }

    public function test_role_only_creates_mandatory_enrollment(): void
    {
        [$user, $jobFamily, $jobRole] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobRole->courses()->attach($course->id, [
            'mandatory' => true,
            'visibility' => 'visible',
        ]);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_MANDATORY,
        ]);
    }

    public function test_union_creates_single_mandatory_enrollment(): void
    {
        [$user, $jobFamily, $jobRole] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobFamily->courses()->attach($course->id);
        $jobRole->courses()->attach($course->id, [
            'mandatory' => true,
            'visibility' => 'visible',
        ]);

        $this->sync($user);

        $this->assertEquals(
            1,
            Enrollment::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->count()
        );
    }

    public function test_role_removed_but_family_required_keeps_mandatory(): void
    {
        [$user, $jobFamily, $jobRole] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobFamily->courses()->attach($course->id);
        $jobRole->courses()->attach($course->id, [
            'mandatory' => true,
            'visibility' => 'visible',
        ]);

        $this->sync($user);

        $jobRole->courses()->sync([
            $course->id => [
                'mandatory' => false,
                'visibility' => 'visible',
            ],
        ]);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_MANDATORY,
        ]);
    }

    public function test_remove_from_both_downgrades_to_optional(): void
    {
        [$user, $jobFamily, $jobRole] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobFamily->courses()->attach($course->id);
        $jobRole->courses()->attach($course->id, [
            'mandatory' => true,
            'visibility' => 'visible',
        ]);

        $this->sync($user);

        $jobFamily->courses()->detach($course->id);
        $jobRole->courses()->detach($course->id);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_OPTIONAL,
        ]);
    }

    public function test_family_optional_creates_optional_enrollment(): void
    {
        [$user, $jobFamily] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobFamily->courses()->attach($course->id, [
            'mandatory' => false,
        ]);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_OPTIONAL,
        ]);
    }

    public function test_role_optional_creates_optional_enrollment(): void
    {
        [$user, $jobFamily, $jobRole] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobRole->courses()->attach($course->id, [
            'mandatory' => false,
            'visibility' => 'visible',
        ]);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_OPTIONAL,
        ]);
    }

    public function test_mandatory_overrides_optional(): void
    {
        [$user, $jobFamily, $jobRole] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        $jobFamily->courses()->attach($course->id, [
            'mandatory' => false,
        ]);
        $jobRole->courses()->attach($course->id, [
            'mandatory' => true,
            'visibility' => 'visible',
        ]);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_MANDATORY,
        ]);
    }

    public function test_completed_status_is_never_overwritten(): void
    {
        [$user, $jobFamily] = $this->makeUserWithRole();
        $course = Course::factory()->create();

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_type' => Enrollment::TYPE_OPTIONAL,
            'status' => Enrollment::STATUS_COMPLETED,
        ]);

        $jobFamily->courses()->attach($course->id);

        $this->sync($user);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_COMPLETED,
        ]);
    }
}
