<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Program;

/**
 * @extends Factory<User>
 */
class ContactRequestFactory extends Factory
{

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'deleted_by' => function (array $attributes) {
                return fake()->randomElement([
                    $attributes['sender_id'],
                    $attributes['receiver_id'],
                ]);
            },
            'workflow_state' => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
        ];
    }
}
