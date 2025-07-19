<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rider>
 */
class RiderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
       public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Assumes you have a UserFactory
            'country_id' => Country::factory(), // Assumes you have a CountryFactory
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'vehicle_number' => $this->faker->bothify('K?? ###X'),
            'license_number' => $this->faker->bothify('DL######'),
            'phone' => $this->faker->phoneNumber,
            'status' => true,
        ];
    }
}
