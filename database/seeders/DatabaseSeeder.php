<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobFamily;
use App\Models\JobRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Provider;
use App\Models\User;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = 'web';

        // -----------------------------------------
        // 1) CRUD PERMISSIONS
        // -----------------------------------------
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

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "$action $resource",
                    'guard_name' => $guardName,
                ]);
            }
        }

        $lmsPermissions = [
            'read user dashboard',
            'read my learning',
            'read metrics',
        ];

        foreach ($lmsPermissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guardName,
            ]);
        }

        // -----------------------------------------
        // 2) ROLES
        // -----------------------------------------
        Role::where('name', 'trainer')->delete();

        $roles = [
            'super-admin',
            'company-admin',
            'department-admin',
            'employee',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);
        }

        $allPermissions = Permission::all();

        // super admin = everything
        Role::findByName('super-admin')->syncPermissions($allPermissions);

        $companyPerms = Permission::query()
            ->whereNotIn('name', [
                'read permissions', 'create permissions', 'update permissions', 'delete permissions',
                'read roles', 'create roles', 'update roles', 'delete roles',
            ])
            ->get();

        // company admin
        Role::findByName('company-admin')->syncPermissions($companyPerms);

        // department admin
        Role::findByName('department-admin')->syncPermissions($companyPerms);

        // employee
        Role::findByName('employee')->syncPermissions($lmsPermissions);

        // -----------------------------------------
        // 3) LMS STRUCTURE
        // -----------------------------------------
        $company = Company::factory()->create();
        $departments = Department::factory()->count(10)->create();

        $superAdminAssigned = false;

        $categoryTemplates = [
            ['name' => 'Safety', 'description' => 'Health and safety training.'],
            ['name' => 'Compliance', 'description' => 'Regulatory and compliance training.'],
            ['name' => 'Leadership', 'description' => 'Leadership and management training.'],
            ['name' => 'Wellbeing', 'description' => 'Wellbeing and support training.'],
        ];

        $categories = collect();
        foreach ($categoryTemplates as $template) {
            $categories->push(CourseCategory::create([
                'name' => $template['name'],
                'description' => $template['description'],
            ]));
        }

        $providerTemplates = [
            ['name' => 'Internal Academy', 'description' => 'In-house training provider.'],
            ['name' => 'Compliance Partner', 'description' => 'External compliance training provider.'],
            ['name' => 'Company Training Team', 'description' => 'Company-specific training provider.'],
        ];

        $providers = collect();
        foreach ($providerTemplates as $template) {
            $providers->push(Provider::create([
                'name' => $template['name'],
                'description' => $template['description'],
            ]));
        }

        $courseTemplates = [
            ['title' => 'Fire Safety',          'price' => 0,  'category' => 'Safety'],
            ['title' => 'Data Protection',      'price' => 0,  'category' => 'Compliance'],
            ['title' => 'Equality & Diversity', 'price' => 0,  'category' => 'Compliance'],
            ['title' => 'Leadership',           'price' => 40, 'category' => 'Leadership'],
            ['title' => 'Mental Health',        'price' => 0,  'category' => 'Wellbeing'],
            ['title' => 'First Aid',            'price' => 25, 'category' => 'Safety'],
        ];

        $courses = collect();
        foreach ($courseTemplates as $c) {
            $category = $categories->firstWhere('name', $c['category']);
            $course = Course::create([
                'course_category_id' => $category?->id,
                'title' => $c['title'],
                'description' => $c['title'] . ' training course.',
                'price' => $c['price'],
            ]);

            $course->providers()->sync($providers->pluck('id')->all());
            $courses->push($course);
        }

        $jobFamilies = collect();
        $roleNames = ['Manager', 'Staff', 'Contractor', 'Volunteer'];
        $jobFamilyNames = ['Operations', 'Academic', 'Support'];

        foreach ($jobFamilyNames as $jobFamilyName) {
            $jobFamilies->push(
                JobFamily::create([
                    'company_id' => $company->id,
                    'name' => $jobFamilyName,
                    'description' => "$jobFamilyName job family",
                ])
            );
        }

        $familyMandatoryCourses = $courses->filter(function ($course) {
            return in_array($course->title, [
                'Fire Safety',
                'Data Protection',
                'First Aid'
            ]);
        });

        foreach ($jobFamilies as $jobFamily) {
            $jobFamily->courses()->sync($familyMandatoryCourses->pluck('id')->all());
        }

        $jobRoles = collect();
        foreach ($roleNames as $roleName) {
            $jobRoles->push(
                JobRole::create([
                    'job_family_id' => $jobFamilies->isNotEmpty() ? $jobFamilies->random()->id : null,
                    'name' => $roleName,
                    'description' => "$roleName role",
                ])
            );
        }

        $roleMandatoryTitles = [
            'Manager' => ['Leadership'],
            'Staff' => ['Equality & Diversity'],
            'Contractor' => ['Mental Health'],
            'Volunteer' => [],
        ];

        foreach ($jobRoles as $role) {
            foreach ($courses as $course) {
                $mandatory = in_array($course->title, $roleMandatoryTitles[$role->name] ?? []);

                $course->jobRoles()->attach($role->id, [
                    'mandatory' => $mandatory,
                    'visibility' => 'visible'
                ]);
            }
        }

        foreach ($departments as $department) {
            foreach ($jobRoles as $role) {
                $users = User::factory()
                    ->count(3)
                    ->create([
                        'company_id' => $company->id,
                        'department_id' => $department->id,
                        'job_role_id' => $role->id,
                        'password' => Hash::make('password'),
                    ]);

                foreach ($users as $user) {
                    if (!$superAdminAssigned) {
                        $user->assignRole('super-admin');
                        $superAdminAssigned = true;
                    } elseif ($role->name === 'Manager') {
                        $user->assignRole('department-admin');
                    } else {
                        $user->assignRole('employee');
                    }

                    $mandatoryRoleCourses = $role->courses()->wherePivot('mandatory', true)->get();
                    $mandatoryFamilyCourses = $role->jobFamily?->courses ?? collect();
                    $mandatoryCourses = $mandatoryRoleCourses
                        ->merge($mandatoryFamilyCourses)
                        ->unique('id');
                    $roleCourseIds = $mandatoryRoleCourses->pluck('id')->all();

                    foreach ($mandatoryCourses as $mandatoryCourse) {
                        $isRoleCourse = in_array($mandatoryCourse->id, $roleCourseIds, true);
                        $user->courses()->attach($mandatoryCourse->id, [
                            'enrollment_type' => 'mandatory',
                            'status' => 'not_started',
                            'source' => $isRoleCourse ? 'job_role' : 'job_family',
                            'source_id' => $isRoleCourse ? $role->id : $role->jobFamily?->id,
                        ]);
                    }
                }
            }
        }
    }
}
