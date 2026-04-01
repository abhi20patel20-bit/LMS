<?php

use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;

test('super admin can view user index page', function () {
    $admin = createSuperAdmin();

    $this->actingAs($admin)->get('/user')->assertOk();
});

test('super admin can list users', function () {
    $admin = createSuperAdmin();
    $user = User::factory()->create([
        'department_id' => $admin->department_id,
        'company_id' => $admin->company_id,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/get-users');

    $response->assertOk()
        ->assertJsonFragment(['email' => $user->email]);
});

test('super admin can create a user', function () {
    $admin = createSuperAdmin();
    $companyId = $admin->company_id;
    $departmentId = $admin->department_id;
    $roleId = SpatieRole::firstOrCreate(['name' => 'employee', 'guard_name' => 'web'])->id;

    $payload = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'company_id' => $companyId,
        'department_id' => $departmentId,
        'role' => $roleId,
    ];

    $response = $this->actingAs($admin)->post('/user', $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'User created successfully.']);

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('super admin can view a single user', function () {
    $admin = createSuperAdmin();
    $user = User::factory()->create([
        'department_id' => $admin->department_id,
        'company_id' => $admin->company_id,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get("/get-user/{$user->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $user->id]);
});

test('super admin can update a user', function () {
    $admin = createSuperAdmin();
    $user = User::factory()->create([
        'department_id' => $admin->department_id,
        'company_id' => $admin->company_id,
        'email_verified_at' => now(),
    ]);

    $roleId = SpatieRole::firstOrCreate(['name' => 'updater', 'guard_name' => 'web'])->id;

    $payload = [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
        'role' => $roleId,
    ];

    $response = $this->actingAs($admin)->put("/user/{$user->id}", $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'User updated successfully.']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated User',
        'email' => 'updated@example.com',
    ]);
});

test('super admin can delete a user', function () {
    $admin = createSuperAdmin();
    $user = User::factory()->create([
        'department_id' => $admin->department_id,
        'company_id' => $admin->company_id,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->delete("/user/{$user->id}");

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'User deleted successfully.']);

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('super admin can suspend and restore a user', function () {
    $admin = createSuperAdmin();
    $user = User::factory()->create([
        'department_id' => $admin->department_id,
        'company_id' => $admin->company_id,
        'email_verified_at' => now(),
    ]);

    $suspendResponse = $this->actingAs($admin)->post('/users/suspend', [
        'user' => ['id' => $user->id],
        'reason' => 'Testing suspension',
        'until' => now()->addDay()->toDateString(),
    ]);

    $suspendResponse->assertStatus(201)
        ->assertJsonFragment(['message' => 'User suspended successfully.']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'status' => 'suspended',
    ]);

    $restoreResponse = $this->actingAs($admin)->get("/users/restore/{$user->id}");

    $restoreResponse->assertStatus(201)
        ->assertJsonFragment(['message' => 'User activated successfully.']);
});
