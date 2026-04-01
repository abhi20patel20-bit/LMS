<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\JobRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

beforeEach(function () {
    // Use sqlite in-memory for isolated, fast tests
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createSuperAdmin(): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permissions = [
        'read departments', 'create departments', 'update departments', 'delete departments',
        'read users', 'create users', 'update users', 'delete users',
        'read roles', 'create roles', 'update roles', 'delete roles',
        'read permissions', 'create permissions', 'update permissions', 'delete permissions',
        'read companies', 'create companies', 'update companies', 'delete companies',
        'read courses', 'create courses', 'update courses', 'delete courses',
    ];

    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::all());

    $company = Company::factory()->create();
    $department = Department::factory()->create();
    $jobRole = JobRole::factory()->create([
        'department_id' => $department->id,
        'company_id' => $company->id,
    ]);

    $user = User::factory()->create([
        'department_id' => $department->id,
        'company_id' => $company->id,
        'job_role_id' => $jobRole->id,
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}
