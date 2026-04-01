<?php

use App\Models\Course;

test('super admin can view courses page', function () {
    $admin = createSuperAdmin();

    $this->actingAs($admin)->get('/courses')->assertOk();
});

test('super admin can list courses', function () {
    $admin = createSuperAdmin();
    Course::factory()->create(['company_id' => $admin->company_id]);

    $response = $this->actingAs($admin)->get('/get-courses');

    $response->assertOk()
        ->assertJsonStructure(['courses']);
});

test('super admin can create a course', function () {
    $admin = createSuperAdmin();

    $payload = [
        'title' => 'Safety Training',
        'description' => 'Learn safety',
        'company_id' => $admin->company_id,
        'visibility' => 'company',
        'price' => 0,
        'settings' => json_encode(['level' => 'basic']),
    ];

    $response = $this->actingAs($admin)->post('/courses', $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Course created successfully']);

    $this->assertDatabaseHas('courses', ['title' => 'Safety Training']);
});

test('super admin can show a course', function () {
    $admin = createSuperAdmin();
    $course = Course::factory()->create(['company_id' => $admin->company_id]);

    $response = $this->actingAs($admin)->get("/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $course->id]);
});

test('super admin can update a course', function () {
    $admin = createSuperAdmin();
    $course = Course::factory()->create(['company_id' => $admin->company_id]);

    $payload = [
        'title' => 'Updated Title',
        'description' => 'Updated desc',
        'company_id' => $admin->company_id,
        'visibility' => 'company',
        'price' => 15,
        'settings' => json_encode(['level' => 'advanced']),
    ];

    $response = $this->actingAs($admin)->put("/courses/{$course->id}", $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Course updated successfully']);

    $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'Updated Title']);
});

test('super admin can delete a course', function () {
    $admin = createSuperAdmin();
    $course = Course::factory()->create(['company_id' => $admin->company_id]);

    $response = $this->actingAs($admin)->delete("/courses/{$course->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Course deleted successfully']);

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});
