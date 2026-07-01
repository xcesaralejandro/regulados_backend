<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\EventCategory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
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
            'event_category_id' => EventCategory::factory(),
            'repeat_code' => $this->faker->optional()->uuid(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->optional()->paragraph(),
            'location' => $this->faker->optional()->address(),
            'notes' => $this->faker->optional()->text(),
            'visibility' => $this->faker->randomElement(['public', 'contacts', 'private']),
            'start_at' => $this->faker->dateTime(),
            'end_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
