<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => 'http://' . $this->faker->domainWord() . '-secure-login.com',
            'screenshot_path' => null,
            'sender_email' => $this->faker->safeEmail(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['active', 'investigating', 'takedown_requested', 'completed']),
        ];
    }
}