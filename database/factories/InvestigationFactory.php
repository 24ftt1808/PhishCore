<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestigationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'assigned_to' => User::factory(),
            'status' => $this->faker->randomElement(['active', 'completed', 'takedown_requested', 'takedown_confirmed']),
            'notes' => $this->faker->optional()->paragraph(),
            'resolved_at' => $this->faker->optional()->dateTime(),
        ];
    }
}