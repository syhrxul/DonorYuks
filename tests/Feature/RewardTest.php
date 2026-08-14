<?php

use App\Models\Reward;
use App\Models\User;
use App\Models\UserReward;

test('rewards can be listed', function () {
    $user = User::factory()->create();
    Reward::factory()->count(3)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/rewards')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(3, 'data');
});

test('out-of-stock rewards are excluded from listing', function () {
    $user = User::factory()->create();
    Reward::factory()->create(['stock' => 5]);
    Reward::factory()->create(['stock' => 0]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/rewards')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('user can redeem a reward with enough points', function () {
    $user = User::factory()->create(['points' => 200]);
    $reward = Reward::factory()->create(['points_required' => 100, 'stock' => 5]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/rewards/'.$reward->id.'/redeem')
        ->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.remaining_points', 100);

    expect($user->fresh()->points)->toBe(100);
    expect($reward->fresh()->stock)->toBe(4);
    expect(UserReward::count())->toBe(1);
});

test('redeem fails when user has insufficient points', function () {
    $user = User::factory()->create(['points' => 50]);
    $reward = Reward::factory()->create(['points_required' => 100, 'stock' => 5]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/rewards/'.$reward->id.'/redeem')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('redeem fails when reward stock is empty', function () {
    $user = User::factory()->create(['points' => 500]);
    $reward = Reward::factory()->create(['points_required' => 100, 'stock' => 0]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/rewards/'.$reward->id.'/redeem')
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});
