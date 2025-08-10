<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Courier>
 */
class CourierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            // 'vehicle_type' => $this->faker->randomElement(['bike', 'car', 'van']),
            // 'license_plate' => $this->faker->bothify('??####'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'country_id' => $this->faker->numberBetween(1, 10),
            // 'city_id' => $this->faker->numberBetween(1, 100),
            // 'zone_id' => $this->faker->numberBetween(1, 1000),
        ];
    }
}
