<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Program;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'semester' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->firstName(),
            'surname' => $this->faker->lastName(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'custom_gender' => $this->faker->optional()->word(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->randomNumber(9, true),
            'birthdate' => $this->faker->date(),
            'avatar' => $this->faker->optional()->imageUrl(),
            'canvas_user_id' => $this->faker->optional()->numberBetween(1, 1000000),
            'access_code' => $this->faker->optional()->lexify('??????'),
            'instagram' => $this->faker->optional()->userName(),
            'discord' => $this->faker->optional()->userName(),
            'access_code_expires_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
