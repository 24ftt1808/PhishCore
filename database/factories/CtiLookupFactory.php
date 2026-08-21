<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class CtiLookupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'source' => 'VirusTotal',
            'raw_response' => ['malicious_votes' => $this->faker->numberBetween(0, 50), 'harmless_votes' => $this->faker->numberBetween(0, 50)],
            'threat_score' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}