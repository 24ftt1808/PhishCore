<?php

namespace Database\Factories;

use App\Models\ScamNews;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScamNews>
 */
class ScamNewsFactory extends Factory
{
    public function definition(): array
    {
        // url_hash is left out on purpose: the model fills it from "url", also when a test sets its own url.
        return [
            'title' => fake()->sentence(8),
            'url' => 'https://news.example/'.fake()->unique()->slug(),
            'source' => fake()->randomElement(['Example News', 'Sample Daily', 'Test Gazette']),
            'image_url' => null,
            'published_at' => now()->subHours(fake()->numberBetween(1, 72)),
            'is_visible' => true,
            'is_manual' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }

    public function manual(): static
    {
        return $this->state(fn () => ['is_manual' => true]);
    }
}
