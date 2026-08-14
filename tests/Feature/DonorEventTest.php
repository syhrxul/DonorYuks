<?php

use App\Models\Donation;
use App\Models\DonorEvent;
use App\Models\User;

test('events can be listed', function () {
    $user = User::factory()->create();
    DonorEvent::factory()->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/events')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(3, 'data');
});

test('events can be filtered by date', function () {
    $user = User::factory()->create();
    DonorEvent::factory()->create(['event_date' => now()->addDays(5)]);
    DonorEvent::factory()->create(['event_date' => now()->addDays(20)]);

    $date = now()->addDays(5)->toDateString();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/events?date='.$date)
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('events can be filtered by location radius', function () {
    $user = User::factory()->create();
    DonorEvent::factory()->create(['latitude' => -6.1800, 'longitude' => 106.8300]); // ~0.6 km dari Monas
    DonorEvent::factory()->create(['latitude' => -6.3000, 'longitude' => 106.9000]); // ~14 km

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/events?latitude=-6.1751&longitude=106.8272&radius_km=5')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('user can book an event and receive a ticket code', function () {
    $user = User::factory()->create();
    $event = DonorEvent::factory()->create(['quota' => 10]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events/'.$event->id.'/book')
        ->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.type', 'event')
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('donations', [
        'donor_id' => $user->id,
        'donor_event_id' => $event->id,
    ]);
});

test('booking fails when quota is full', function () {
    $user = User::factory()->create();
    $event = DonorEvent::factory()->create(['quota' => 1]);
    Donation::create([
        'donor_id' => User::factory()->create()->id,
        'donor_event_id' => $event->id,
        'type' => 'event',
        'status' => 'confirmed',
        'ticket_code' => 'EVT-FULL001',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events/'.$event->id.'/book')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('booking fails for past events', function () {
    $user = User::factory()->create();
    $event = DonorEvent::factory()->create(['event_date' => now()->subDay()]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/events/'.$event->id.'/book')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});
