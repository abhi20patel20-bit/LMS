<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('super admin can view permissions page', function () {
    $admin = createSuperAdmin();

    $this->actingAs($admin)->get('/permission')->assertOk();
});

test('super admin can list permissions', function () {
    $admin = createSuperAdmin();
    Permission::firstOrCreate(['name' => 'listable-permission', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->get('/get-permissions');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'listable-permission']);
});

test('super admin can create a permission', function () {
    $admin = createSuperAdmin();

    $response = $this->actingAs($admin)->post('/permission', [
        'name' => 'new-permission',
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Permission Added Sucessfully']);

    $this->assertDatabaseHas('permissions', ['name' => 'new-permission']);
});

test('super admin can update a permission', function () {
    $admin = createSuperAdmin();
    $permission = Permission::firstOrCreate(['name' => 'old-permission', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->put("/permission/{$permission->id}", [
        'name' => 'updated-permission',
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Permission Update Sucessfully']);

    $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'updated-permission']);
});

test('super admin can delete a permission', function () {
    $admin = createSuperAdmin();
    $permission = Permission::firstOrCreate(['name' => 'delete-me', 'guard_name' => 'web']);

    // the controller expects a "superadmin" role to revoke the permission
    $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    $superadminRole->givePermissionTo($permission);

    $response = $this->actingAs($admin)->delete("/permission/{$permission->id}");

    $response->assertStatus(302); // controller redirects back on success

    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
});
