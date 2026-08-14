<?php

use App\Models\BloodRequest;
use App\Models\Donation;
use App\Models\User;

test('authenticated user can create a blood request', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/blood-requests', [
            'patient_name' => 'Sari Dewi',
            'blood_type' => 'A+',
            'bags_needed' => 2,
            'hospital_name' => 'RS Harapan',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'urgency_level' => 'urgent',
        ])
        ->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.blood_type', 'A+')
        ->assertJsonPath('data.status', 'open');

    expect(BloodRequest::count())->toBe(1);
});

test('blood request creation validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/blood-requests', [])
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('nearby returns only blood requests within radius sorted by distance', function () {
    $user = User::factory()->create();

    // Titik acuan: Monas Jakarta (-6.1751, 106.8272)
    $lat = -6.1751;
    $lng = 106.8272;

    BloodRequest::factory()->create(['blood_type' => 'O+', 'status' => 'open', 'latitude' => -6.1800, 'longitude' => 106.8300]); // ~0.6 km
    BloodRequest::factory()->create(['blood_type' => 'O+', 'status' => 'open', 'latitude' => -6.3000, 'longitude' => 106.9000]); // ~14 km
    BloodRequest::factory()->create(['blood_type' => 'B-', 'status' => 'open', 'latitude' => -6.1800, 'longitude' => 106.8300]); // ~0.6 km tapi beda golongan

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/blood-requests/nearby?latitude='.$lat.'&longitude='.$lng.'&radius_km=5&blood_type='.urlencode('O+'))
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');

    $data = $response->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['blood_type'])->toBe('O+');
    expect($data[0]['distance_km'])->toBeLessThan(5);
});

test('nearby requires latitude and longitude', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/blood-requests/nearby')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('donor can confirm a blood request and receives a ticket', function () {
    $requester = User::factory()->create(['golongan_darah' => 'O+']);
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $requester->id,
        'blood_type' => 'O+',
        'status' => 'open',
    ]);

    $this->actingAs($donor, 'sanctum')
        ->postJson('/api/blood-requests/'.$bloodRequest->id.'/confirm')
        ->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.type', 'personal')
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('donations', [
        'donor_id' => $donor->id,
        'blood_request_id' => $bloodRequest->id,
    ]);

    expect($bloodRequest->fresh()->status)->toBe('matched');
});

test('donor cannot confirm their own blood request', function () {
    $user = User::factory()->create(['golongan_darah' => 'O+']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $user->id,
        'blood_type' => 'O+',
        'status' => 'open',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/blood-requests/'.$bloodRequest->id.'/confirm')
        ->assertStatus(403)
        ->assertJsonPath('status', 'error');
});

test('donor cannot confirm with incompatible blood type', function () {
    $requester = User::factory()->create(['golongan_darah' => 'B+']);
    $donor = User::factory()->create(['golongan_darah' => 'A+']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $requester->id,
        'blood_type' => 'B+',
        'status' => 'open',
    ]);

    $this->actingAs($donor, 'sanctum')
        ->postJson('/api/blood-requests/'.$bloodRequest->id.'/confirm')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('donor cannot confirm an already completed request', function () {
    $requester = User::factory()->create(['golongan_darah' => 'A+']);
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $requester->id,
        'blood_type' => 'A+',
        'status' => 'completed',
    ]);

    $this->actingAs($donor, 'sanctum')
        ->postJson('/api/blood-requests/'.$bloodRequest->id.'/confirm')
        ->assertStatus(403)
        ->assertJsonPath('status', 'error');
});

test('completing a donation awards points and increments bags fulfilled', function () {
    $requester = User::factory()->create(['golongan_darah' => 'O+']);
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $requester->id,
        'blood_type' => 'O+',
        'bags_needed' => 1,
        'status' => 'matched',
    ]);
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'blood_request_id' => $bloodRequest->id,
        'type' => 'personal',
        'status' => 'confirmed',
        'ticket_code' => 'DNY-TEST001',
    ]);

    $this->actingAs($donor, 'sanctum')
        ->postJson('/api/donations/'.$donation->id.'/complete')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.points_earned', 50)
        ->assertJsonPath('data.donation.status', 'completed');

    expect($donor->fresh()->points)->toBe(50);
    expect($bloodRequest->fresh()->bags_fulfilled)->toBe(1);
    expect($bloodRequest->fresh()->status)->toBe('completed');
});

test('a donation can only be completed by its donor', function () {
    $requester = User::factory()->create(['golongan_darah' => 'A+']);
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $otherUser = User::factory()->create(['golongan_darah' => 'O+']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $requester->id,
        'blood_type' => 'A+',
        'status' => 'matched',
    ]);
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'blood_request_id' => $bloodRequest->id,
        'type' => 'personal',
        'status' => 'confirmed',
        'ticket_code' => 'DNY-TEST002',
    ]);

    $this->actingAs($otherUser, 'sanctum')
        ->postJson('/api/donations/'.$donation->id.'/complete')
        ->assertStatus(403)
        ->assertJsonPath('status', 'error');
});
