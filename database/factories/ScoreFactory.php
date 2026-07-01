<?php

namespace Database\Factories;

use App\Models\Score;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'points' => $this->faker->numberBetween(0, 100),
            'context' => $this->faker->word(),
            'action' => $this->faker->randomElement(['create', 'update', 'delete', 'share']),
            'reference_id' => $this->faker->optional()->randomNumber(),
            'reference_table' => $this->faker->optional()->randomElement(['posts', 'comments', 'events']),
        ];
    }
}
