<?php

use App\Models\BloodRequest;
use App\Models\ChatHistory;
use App\Models\Donation;
use App\Models\DonorEvent;
use App\Models\Post;
use App\Models\User;

// ===== Fitur Baru: Riwayat Chat Bloody =====

test('bloody chat persists history and history can be retrieved', function () {
    config(['donoryuks.ai.provider' => 'mock']);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Berapa kadar HB normal?'])
        ->assertStatus(200)
        ->assertJsonPath('data.history_id', fn (int $id) => $id > 0);

    expect(ChatHistory::count())->toBe(1);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/bloody/history')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.message', 'Berapa kadar HB normal?');
});

test('chat history is isolated per user', function () {
    config(['donoryuks.ai.provider' => 'mock']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Pertanyaan user A?'])
        ->assertStatus(200);

    $this->actingAs($userB, 'sanctum')
        ->getJson('/api/bloody/history')
        ->assertJsonPath('meta.total', 0);
});

// ===== Fitur Baru: Pembatalan Donasi =====

test('donor can cancel a confirmed donation and request reopens', function () {
    $requester = User::factory()->create(['golongan_darah' => 'O+']);
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $requester->id,
        'blood_type' => 'O+',
        'status' => 'matched',
    ]);
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'blood_request_id' => $bloodRequest->id,
        'type' => 'personal',
        'status' => 'confirmed',
        'ticket_code' => 'DNY-TESTC1',
    ]);

    $this->actingAs($donor, 'sanctum')
        ->postJson('/api/donations/'.$donation->id.'/cancel')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'cancelled');

    expect($donation->fresh()->status)->toBe('cancelled');
    expect($bloodRequest->fresh()->status)->toBe('open');
});

test('donor cannot cancel a completed donation', function () {
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'type' => 'personal',
        'status' => 'completed',
        'ticket_code' => 'DNY-TESTC2',
        'completed_at' => now(),
    ]);

    $this->actingAs($donor, 'sanctum')
        ->postJson('/api/donations/'.$donation->id.'/cancel')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('another user cannot cancel a donation', function () {
    $donor = User::factory()->create(['golongan_darah' => 'O-']);
    $other = User::factory()->create(['golongan_darah' => 'A+']);
    $donation = Donation::create([
        'donor_id' => $donor->id,
        'type' => 'personal',
        'status' => 'confirmed',
        'ticket_code' => 'DNY-TESTC3',
    ]);

    $this->actingAs($other, 'sanctum')
        ->postJson('/api/donations/'.$donation->id.'/cancel')
        ->assertStatus(403)
        ->assertJsonPath('status', 'error');
});

// ===== Fitur Baru: Pembatalan Permohonan =====

test('requester can cancel their open blood request', function () {
    $user = User::factory()->create(['golongan_darah' => 'A+']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $user->id,
        'status' => 'open',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/blood-requests/'.$bloodRequest->id.'/cancel')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.status', 'cancelled');
});

test('non-owner cannot cancel a blood request', function () {
    $owner = User::factory()->create(['golongan_darah' => 'A+']);
    $other = User::factory()->create(['golongan_darah' => 'O-']);
    $bloodRequest = BloodRequest::factory()->create([
        'user_id' => $owner->id,
        'status' => 'open',
    ]);

    $this->actingAs($other, 'sanctum')
        ->postJson('/api/blood-requests/'.$bloodRequest->id.'/cancel')
        ->assertStatus(403)
        ->assertJsonPath('status', 'error');
});

// ===== Pagination & Meta =====

test('list endpoints return pagination meta', function () {
    $user = User::factory()->create();
    Post::factory()->count(20)->create(['published_at' => now()->subDay()]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/posts')
        ->assertStatus(200)
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonPath('meta.total', 20)
        ->assertJsonPath('meta.last_page', 2);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/posts?per_page=5&page=2')
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2);
});

test('events list respects pagination meta', function () {
    $user = User::factory()->create();
    DonorEvent::factory()->count(5)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/events?per_page=2')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5);
});

test('posts can be searched by keyword', function () {
    $user = User::factory()->create();
    Post::factory()->create(['title' => 'Cara Menjaga Kesehatan', 'published_at' => now()]);
    Post::factory()->create(['title' => 'Tips Liburan', 'published_at' => now()]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/posts?q=Kesehatan')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Cara Menjaga Kesehatan');
});

// ===== Rate Limiting =====

test('login is throttled after too many attempts', function () {
    User::factory()->create(['email' => 'ratelimit@example.com', 'password' => 'password123']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'wrong-password',
        ]);
    }

    $this->postJson('/api/login', [
        'email' => 'ratelimit@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});
