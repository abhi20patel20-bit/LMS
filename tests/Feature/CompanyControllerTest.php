<?php

use App\Models\Company;

test('super admin can view companies page', function () {
    $admin = createSuperAdmin();

    $this->actingAs($admin)->get('/company')->assertOk();
});

test('super admin can list companies', function () {
    $admin = createSuperAdmin();
    Company::factory()->create();

    $response = $this->actingAs($admin)->get('/get-companies');

    $response->assertOk();
});

test('super admin can list companies dropdown', function () {
    $admin = createSuperAdmin();
    Company::factory()->create();

    $response = $this->actingAs($admin)->get('/get-companies-dropdown');

    $response->assertOk();
});

test('super admin can update a company', function () {
    $admin = createSuperAdmin();
    $company = Company::factory()->create();

    $payload = [
        'id' => $company->id,
        'name' => 'Updated Company',
        'type' => 'customer',
        'settings' => '{}',
        'address' => '123 Test Street',
        'phone' => '1234567',
        'email' => 'company@example.com',
    ];

    $response = $this->actingAs($admin)->post("/company-update/{$company->id}", $payload);

    $response->assertStatus(201)
        ->assertJsonFragment(['message' => 'Company updated successfully']);

    $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Updated Company']);
});

test('super admin can delete a company and children', function () {
    $admin = createSuperAdmin();
    $company = Company::factory()->create();

    $response = $this->actingAs($admin)->delete("/company/{$company->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Company and related users deleted successfully']);

    $this->assertSoftDeleted('companies', ['id' => $company->id]);
});
