<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'status_category' => $this->faker->optional()->word,
            'description' => $this->faker->optional()->sentence,
            'color' => $this->faker->optional()->safeColorName,
            'country_id' => \App\Models\Country::factory(),
        ];
    }
}
