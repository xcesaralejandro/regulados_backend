<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\EventCategory;

/**
 * @extends Factory<Event>
 */
class EventActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();
        return [
            'event_id' => Event::factory(),
            'user_id' => $user,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'order' => $this->faker->numberBetween(1, 100),
            'completed_by' => $user,
            'completed_at' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'source' => $this->faker->randomElement(["ai", "user"]),
        ];
    }
}
