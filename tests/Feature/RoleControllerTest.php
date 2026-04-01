<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('super admin can view roles page', function () {
    $admin = createSuperAdmin();

    $this->actingAs($admin)->get('/role')->assertOk();
});

test('super admin can list roles', function () {
    $admin = createSuperAdmin();
    Role::firstOrCreate(['name' => 'tester', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->get('/get-roles');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'tester']);
});

test('super admin can list roles dropdown', function () {
    $admin = createSuperAdmin();
    Role::firstOrCreate(['name' => 'dropdown-role', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->get('/get-roles-dropdown');

    $response->assertOk();
});

test('super admin can create a role', function () {
    $admin = createSuperAdmin();
    $permissionId = Permission::firstOrCreate(['name' => 'create widgets', 'guard_name' => 'web'])->id;

    $payload = [
        'name' => 'quality-assurance',
        'permissions' => [$permissionId],
    ];

    $response = $this->actingAs($admin)->post('/role', $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Role Added Sucessfully']);

    $this->assertDatabaseHas('roles', ['name' => 'quality-assurance']);
});

test('super admin can update a role', function () {
    $admin = createSuperAdmin();
    $permissionId = Permission::firstOrCreate(['name' => 'update widgets', 'guard_name' => 'web'])->id;
    $role = Role::firstOrCreate(['name' => 'updatable-role', 'guard_name' => 'web']);

    $payload = [
        'id' => $role->id,
        'name' => 'updated-role',
        'permissions' => [$permissionId],
    ];

    $response = $this->actingAs($admin)->put("/role/{$role->id}", $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Role updated successfully']);

    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'updated-role']);
});

test('super admin can delete a role', function () {
    $admin = createSuperAdmin();
    $operator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
    $roleToDelete = Role::firstOrCreate(['name' => 'deletable-role', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->delete("/role/{$roleToDelete->id}");

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Role deleted and users reassigned successfully']);

    $this->assertDatabaseMissing('roles', ['id' => $roleToDelete->id]);
});
