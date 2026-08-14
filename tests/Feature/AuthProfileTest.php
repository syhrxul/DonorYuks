<?php

use App\Models\Donation;
use App\Models\User;

test('register creates a user and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'no_hp' => '081234567890',
        'golongan_darah' => 'O+',
        'latitude' => -6.2000000,
        'longitude' => 106.8166667,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['user', 'access_token', 'token_type'],
        ]);

    expect(User::count())->toBe(1);
});

test('register validates duplicate email', function () {
    User::factory()->create(['email' => 'budi@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('login returns a token for valid credentials', function () {
    $user = User::factory()->create(['email' => 'budi@example.com', 'password' => 'password123']);

    $response = $this->postJson('/api/login', [
        'email' => 'budi@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.user.email', 'budi@example.com');
});

test('login fails for invalid credentials', function () {
    $this->postJson('/api/login', [
        'email' => 'unknown@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('authenticated user can fetch profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/profile')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $user->id);
});

test('profile routes require authentication', function () {
    $this->getJson('/api/profile')->assertStatus(401);
});

test('user can update profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/profile', [
            'name' => 'Budi Baru',
            'no_hp' => '089999999999',
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Budi Baru');
});

test('user can update live location', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/profile/location', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.latitude', '-6.2088000');
});

test('donor card returns statistics', function () {
    $user = User::factory()->create();
    Donation::create([
        'donor_id' => $user->id,
        'type' => 'personal',
        'status' => 'completed',
        'ticket_code' => 'TCK-001',
        'completed_at' => now(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/profile/donor-card')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.total_donations', 1)
        ->assertJsonPath('data.total_completed', 1);
});

test('track record returns donation history', function () {
    $user = User::factory()->create();
    Donation::create([
        'donor_id' => $user->id,
        'type' => 'event',
        'status' => 'confirmed',
        'ticket_code' => 'TCK-002',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/profile/track-record')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data');
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/logout')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');

    expect($user->tokens()->count())->toBe(0);
});
