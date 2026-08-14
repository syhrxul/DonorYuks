<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence;

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(4),
            'category' => fake()->randomElement(['edukasi', 'berita', 'tips']),
            'excerpt' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'image' => null,
            'author' => fake()->name(),
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
