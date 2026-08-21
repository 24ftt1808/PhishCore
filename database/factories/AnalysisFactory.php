<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'whois_data' => ['registrar' => $this->faker->company(), 'created' => $this->faker->date()],
            'domain_age_days' => $this->faker->numberBetween(1, 3650),
            'url_syntax_score' => $this->faker->randomFloat(2, 0, 1),
            'ip_address' => $this->faker->ipv4(),
            'ip_reputation' => $this->faker->randomElement(['clean', 'suspicious', 'malicious']),
            'redirect_chain' => [$this->faker->url(), $this->faker->url()],
            'verdict' => $this->faker->randomElement(['phishing', 'suspicious', 'clean']),
        ];
    }
}