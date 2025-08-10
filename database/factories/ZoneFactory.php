<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Zone>
 */
class ZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
    */
    public function definition(): array
    {
       return [
          'name' => $this->faker->city,
          'country_id' => \App\Models\Country::factory(),
          // 'state_id' => \App\Models\State::factory(),
          'city_id' => \App\Models\City::factory(),
          'latitude' => $this->faker->latitude,
          'longitude' => $this->faker->longitude,
          'population' => $this->faker->numberBetween(1000, 1000000),
       ];
    }
}
