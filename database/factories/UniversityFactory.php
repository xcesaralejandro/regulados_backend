<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<University>
 */
class UniversityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->university(),
            'short_name' => $this->faker->lexify('???'),
            'canvas_domain_url' => $this->faker->optional()->url(),
            'canvas_client_id' => $this->faker->optional()->numerify('#####'),
            'canvas_client_secret' => $this->faker->optional()->password(40),
        ];
    }
}
