<?php

namespace Database\Factories;

use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCategory>
 */
class EventCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $materialIcons = [
            'error_med',
            'favorite',
            'stacks',
        ];
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph,
            'icon' => $this->faker->randomElement($materialIcons),
            'text_color' => $this->faker->hexColor,
            'background_color' => $this->faker->hexColor,
        ];
    }
}
