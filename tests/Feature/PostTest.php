<?php

use App\Models\Post;
use App\Models\User;

test('posts can be listed and only published articles are returned', function () {
    $user = User::factory()->create();
    Post::factory()->count(3)->create(['published_at' => now()->subDay()]);
    Post::factory()->create(['published_at' => now()->addDay()]); // belum tayang

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/posts')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(3, 'data');
});

test('posts can be filtered by category', function () {
    $user = User::factory()->create();
    Post::factory()->create(['category' => 'edukasi']);
    Post::factory()->create(['category' => 'berita']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/posts?category=edukasi')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'edukasi');
});

test('posts route requires authentication', function () {
    $this->getJson('/api/posts')->assertStatus(401);
});
