<?php

use App\Models\Department;

test('super admin can view the department index page', function () {
    $user = createSuperAdmin();

    $response = $this->actingAs($user)->get('/department');

    $response->assertOk();
});

test('super admin can list departments', function () {
    $user = createSuperAdmin();
    Department::factory()->count(2)->create();

    $response = $this->actingAs($user)->get('/get-departments');

    $response->assertOk()
        ->assertJsonCount(3, 'departments');
});

test('super admin can create a department', function () {
    $user = createSuperAdmin();

    $payload = [
        'name' => 'New Department',
        'slug' => 'new-department',
        'custom_domain' => 'example.test',
        'subscription_type' => 'free',
        'settings' => ['theme' => 'light'],
    ];

    $response = $this->actingAs($user)->post('/department', $payload);

    $response->assertCreated()
        ->assertJsonFragment(['message' => 'Department created successfully']);

    $this->assertDatabaseHas('departments', [
        'name' => 'New Department',
        'slug' => 'new-department',
    ]);
});

test('super admin can show a department', function () {
    $user = createSuperAdmin();
    $department = Department::factory()->create();

    $response = $this->actingAs($user)->get("/department/{$department->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $department->id]);
});

test('super admin can update a department', function () {
    $user = createSuperAdmin();
    $department = Department::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-slug',
    ]);

    $payload = [
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
        'custom_domain' => 'updated.test',
        'subscription_type' => 'paid',
        'settings' => ['theme' => 'dark'],
    ];

    $response = $this->actingAs($user)->put("/department/{$department->id}", $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Department updated successfully']);

    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'name' => 'Updated Name',
        'slug' => 'updated-slug',
    ]);
});

test('super admin can delete a department', function () {
    $user = createSuperAdmin();
    $department = Department::factory()->create();

    $response = $this->actingAs($user)->delete("/department/{$department->id}");

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Department deleted successfully']);

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});
