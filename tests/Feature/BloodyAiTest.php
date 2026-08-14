<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('bloody chat returns an educational answer in mock mode', function () {
    config(['donoryuks.ai.provider' => 'mock']);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Berapa kadar HB normal untuk donor?'])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.answer', fn (string $answer) => str_contains(strtolower($answer), 'hb'));
});

test('bloody chat falls back to mock when provider has no api key', function () {
    config([
        'donoryuks.ai.provider' => 'openai',
        'donoryuks.ai.openai.api_key' => null,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Jeda donor berapa lama?'])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');
});

test('bloody chat validates the message field', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => ''])
        ->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('bloody chat calls the OpenAI API with the system prompt injected', function () {
    config([
        'donoryuks.ai.provider' => 'openai',
        'donoryuks.ai.openai.api_key' => 'test-key',
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Jawaban edukatif dari OpenAI']]],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Bolehkah donor saat haid?'])
        ->assertStatus(200)
        ->assertJsonPath('data.answer', 'Jawaban edukatif dari OpenAI');

    Http::assertSent(function ($request) {
        $payload = $request->data();
        $roles = array_column($payload['messages'], 'role');

        return in_array('system', $roles, true)
            && str_contains($payload['messages'][0]['content'], 'Bloody')
            && in_array('user', $roles, true);
    });
});

test('bloody chat calls the Gemini API', function () {
    config([
        'donoryuks.ai.provider' => 'gemini',
        'donoryuks.ai.gemini.api_key' => 'test-key',
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Jawaban dari Gemini']]]]],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Nutrisi sebelum donor?'])
        ->assertStatus(200)
        ->assertJsonPath('data.answer', 'Jawaban dari Gemini');

    Http::assertSent(fn ($request) => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=test-key');
});

test('bloody chat returns fallback answer when the AI provider errors', function () {
    config([
        'donoryuks.ai.provider' => 'openai',
        'donoryuks.ai.openai.api_key' => 'test-key',
    ]);

    Http::fake(['api.openai.com/*' => Http::response([], 500)]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/bloody/chat', ['message' => 'Cara menjaga stamina pendonor?'])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');
});
