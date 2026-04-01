<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseBooking;
use App\Models\CourseSession;
use App\Models\CourseWaitlist;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\JobFamily;
use App\Models\JobRole;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use App\Services\User\UserEnrollmentSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DevBaselineSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = 'web';
        $lmsPermissions = [
            'read user dashboard',
            'read my learning',
            'read metrics',
        ];

        $resourcePermissions = $this->buildResourcePermissions();
        $permissionNames = array_values(array_unique(array_merge(
            $resourcePermissions,
            $this->buildPermissionCrudAliases(),
            $lmsPermissions
        )));

        foreach ($permissionNames as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]);
        }

        Role::where('name', 'trainer')->delete();

        $roleNames = [
            'super-admin',
            'company-admin',
            'department-admin',
            'employee',
        ];

        foreach ($roleNames as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);
        }

        $allPermissions = Permission::all();
        Role::findByName('super-admin', $guardName)->syncPermissions($allPermissions);

        $excludedPermissions = [
            'read permissions', 'create permissions', 'update permissions', 'delete permissions',
            'read roles', 'create roles', 'update roles', 'delete roles',
            'read permission', 'create permission', 'update permission', 'delete permission',
        ];

        $companyPerms = Permission::query()
            ->whereNotIn('name', $excludedPermissions)
            ->get();

        Role::findByName('company-admin', $guardName)->syncPermissions($companyPerms);
        Role::findByName('department-admin', $guardName)->syncPermissions($companyPerms);
        Role::findByName('employee', $guardName)->syncPermissions($lmsPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $company = Company::create([
            'name' => 'Baseline Co',
            'email' => 'info@baseline.test',
            'phone' => '000-000-0000',
            'address' => '1 Baseline Way',
            'type' => 'standard',
        ]);

        $departmentName = 'Baseline Department';
        $departmentSlug = Str::slug($departmentName);
        $department = Department::firstOrCreate(
            ['slug' => $departmentSlug],
            [
                'name' => $departmentName,
                'subscription_type' => 'free',
            ]
        );

        $jobFamily = JobFamily::create([
            'company_id' => $company->id,
            'name' => 'Operations',
            'description' => 'Baseline job family',
        ]);
        if (Schema::hasColumn('job_families', 'department_id')) {
            $jobFamily->department_id = $department->id;
        }
        $jobFamily->save();

        $jobRolePrimary = new JobRole([
            'job_family_id' => $jobFamily->id,
            'name' => 'Operations Lead',
            'description' => 'Primary role for LMS testing',
        ]);
        if (Schema::hasColumn('job_roles', 'company_id')) {
            $jobRolePrimary->company_id = $company->id;
        }
        if (Schema::hasColumn('job_roles', 'department_id')) {
            $jobRolePrimary->department_id = $department->id;
        }
        $jobRolePrimary->save();

        $jobRoleSecondary = new JobRole([
            'job_family_id' => $jobFamily->id,
            'name' => 'Operations Associate',
            'description' => 'Secondary role for LMS testing',
        ]);
        if (Schema::hasColumn('job_roles', 'company_id')) {
            $jobRoleSecondary->company_id = $company->id;
        }
        if (Schema::hasColumn('job_roles', 'department_id')) {
            $jobRoleSecondary->department_id = $department->id;
        }
        $jobRoleSecondary->save();

        $categoryOne = new CourseCategory([
            'name' => 'Safety',
            'description' => 'Baseline safety category',
        ]);
        if (Schema::hasColumn('course_categories', 'company_id')) {
            $categoryOne->company_id = $company->id;
        }
        if (Schema::hasColumn('course_categories', 'department_id')) {
            $categoryOne->department_id = $department->id;
        }
        $categoryOne->save();

        $categoryTwo = new CourseCategory([
            'name' => 'Compliance',
            'description' => 'Baseline compliance category',
        ]);
        if (Schema::hasColumn('course_categories', 'company_id')) {
            $categoryTwo->company_id = $company->id;
        }
        if (Schema::hasColumn('course_categories', 'department_id')) {
            $categoryTwo->department_id = $department->id;
        }
        $categoryTwo->save();

        $provider = Provider::create([
            'name' => 'Baseline Provider',
            'description' => 'Default provider for baseline courses',
        ]);
        $secondaryProvider = Provider::create([
            'name' => 'Baseline Provider East',
            'description' => 'Secondary provider for scheduled sessions',
        ]);

        $courses = $this->seedCourses($company->id, $categoryOne, $categoryTwo, $provider);

        $jobFamily->courses()->sync([
            $courses['course1']->id,
            $courses['course2']->id,
        ]);

        $jobRolePrimary->courses()->sync([
            $courses['course3']->id => ['mandatory' => true, 'visibility' => 'visible'],
            $courses['course4']->id => ['mandatory' => false, 'visibility' => 'visible'],
            $courses['course5']->id => ['mandatory' => false, 'visibility' => 'visible'],
        ]);

        $jobRoleSecondary->courses()->sync([
            $courses['course6']->id => ['mandatory' => false, 'visibility' => 'visible'],
        ]);

        $users = $this->seedUsers($company->id, $department->id, $jobRolePrimary->id);
        $employee = $users['employee'];

        $scheduledCourse = $courses['course1'];
        $scheduledCourse->update([
            'delivery_type' => 'scheduled',
            'booking_required' => true,
            'default_capacity' => 2,
        ]);
        $scheduledCourse->providers()->sync([$provider->id, $secondaryProvider->id]);

        $sessionOne = CourseSession::create([
            'course_id' => $scheduledCourse->id,
            'provider_id' => $provider->id,
            'starts_at' => now()->addDays(3)->setTime(9, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'capacity' => 2,
            'status' => 'open',
            'location' => 'Training Room A',
        ]);
        $sessionTwo = CourseSession::create([
            'course_id' => $scheduledCourse->id,
            'provider_id' => $secondaryProvider->id,
            'starts_at' => now()->addDays(5)->setTime(14, 0),
            'ends_at' => now()->addDays(5)->setTime(16, 0),
            'capacity' => 2,
            'status' => 'open',
            'location' => 'Training Room B',
        ]);

        Enrollment::create([
            'user_id' => $employee->id,
            'course_id' => $courses['course3']->id,
            'enrollment_type' => Enrollment::TYPE_MANDATORY,
            'status' => Enrollment::STATUS_COMPLETED,
            'completed_at' => now(),
            'source' => Enrollment::SOURCE_JOB_FAMILY,
            'source_id' => $jobFamily->id,
        ]);

        Enrollment::create([
            'user_id' => $employee->id,
            'course_id' => $courses['course2']->id,
            'enrollment_type' => Enrollment::TYPE_MANDATORY,
            'status' => Enrollment::STATUS_IN_PROGRESS,
            'source' => Enrollment::SOURCE_JOB_FAMILY,
            'source_id' => $jobFamily->id,
        ]);

        $bookingUser = $users['company-admin'];

        CourseBooking::create([
            'user_id' => $bookingUser->id,
            'course_id' => $scheduledCourse->id,
            'course_session_id' => $sessionOne->id,
            'status' => CourseBooking::STATUS_BOOKED,
            'booked_at' => now(),
        ]);

        CourseWaitlist::create([
            'user_id' => $bookingUser->id,
            'course_id' => $scheduledCourse->id,
            'course_session_id' => $sessionTwo->id,
            'position' => 1,
            'status' => CourseWaitlist::STATUS_WAITING,
        ]);

        app(UserEnrollmentSyncService::class)->syncUser($employee);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->printSummary($users, $jobFamily->id, $jobRolePrimary->id, $jobRoleSecondary->id);
    }

    private function seedCourses(int $companyId, CourseCategory $categoryOne, CourseCategory $categoryTwo, Provider $provider): array
    {
        $courseData = [
            ['key' => 'course1', 'title' => 'Fire Safety Basics', 'category' => $categoryOne, 'duration' => 45],
            ['key' => 'course2', 'title' => 'Workplace First Aid', 'category' => $categoryOne, 'duration' => 60],
            ['key' => 'course3', 'title' => 'Operations Compliance 101', 'category' => $categoryTwo, 'duration' => 50],
            ['key' => 'course4', 'title' => 'Policy Updates', 'category' => $categoryTwo, 'duration' => 30],
            ['key' => 'course5', 'title' => 'Risk Awareness', 'category' => $categoryTwo, 'duration' => 40],
            ['key' => 'course6', 'title' => 'Safety Refresher', 'category' => $categoryOne, 'duration' => 35],
        ];

        $courses = [];

        foreach ($courseData as $data) {
            $course = new Course([
                'title' => $data['title'],
                'description' => $data['title'] . ' course.',
                'course_category_id' => $data['category']->id,
                'duration' => $data['duration'],
            ]);
            if (Schema::hasColumn('courses', 'company_id')) {
                $course->company_id = $companyId;
            }
            $course->save();
            $course->providers()->sync([$provider->id]);

            $courses[$data['key']] = $course;
        }

        return $courses;
    }

    private function seedUsers(int $companyId, int $departmentId, int $jobRoleId): array
    {
        $password = Hash::make('password');

        $users = [
            'super-admin' => $this->upsertUser([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => $password,
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'email_verified_at' => now(),
            ]),
            'company-admin' => $this->upsertUser([
                'name' => 'Company Admin',
                'email' => 'companyadmin@example.com',
                'password' => $password,
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'email_verified_at' => now(),
            ]),
            'department-admin' => $this->upsertUser([
                'name' => 'Department Admin',
                'email' => 'departmentadmin@example.com',
                'password' => $password,
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'email_verified_at' => now(),
            ]),
            'employee' => $this->upsertUser([
                'name' => 'Employee User',
                'email' => 'employee@example.com',
                'password' => $password,
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'job_role_id' => $jobRoleId,
                'email_verified_at' => now(),
            ]),
        ];

        $users['super-admin']->syncRoles(['super-admin']);
        $users['company-admin']->syncRoles(['company-admin']);
        $users['department-admin']->syncRoles(['department-admin']);
        $users['employee']->syncRoles(['employee']);

        return $users;
    }

    private function upsertUser(array $attributes): User
    {
        $user = User::withTrashed()->firstOrNew([
            'email' => $attributes['email'],
        ]);

        $user->fill($attributes);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->save();

        return $user;
    }

    private function buildResourcePermissions(): array
    {
        $resources = [
            'companies',
            'departments',
            'roles',
            'permissions',
            'job families',
            'job roles',
            'course categories',
            'providers',
            'users',
            'courses',
        ];

        $actions = ['read', 'create', 'update', 'delete'];
        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "$action $resource";
            }
        }

        return $permissions;
    }

    private function buildPermissionCrudAliases(): array
    {
        return [
            'read permission',
            'create permission',
            'update permission',
            'delete permission',
        ];
    }

    private function printSummary(array $users, int $jobFamilyId, int $primaryRoleId, int $secondaryRoleId): void
    {
        if (!$this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('Dev baseline ready.');
        $this->command->line('Users:');

        foreach ($users as $role => $user) {
            $this->command->line("- {$role}: {$user->email}");
        }

        $this->command->line("Job family ID: {$jobFamilyId}");
        $this->command->line("Job role IDs: {$primaryRoleId}, {$secondaryRoleId}");
        $this->command->line('Reminder: start the queue worker if your flow depends on queued jobs.');
    }
}
