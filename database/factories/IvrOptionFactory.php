<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IvrOption>
 */
class IvrOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'option_number'   => $this->faker->unique()->numberBetween(1, 9999),
            'description'     => $this->faker->sentence(),
            'forward_number'  => $this->faker->optional()->numerify('###########'),
            'phone_number'    => $this->faker->optional()->phoneNumber(),
            'status'          => $this->faker->optional()->word(),
            'branch_id'       => $this->faker->optional()->numberBetween(1, 100),
            'country_id'      => $this->faker->optional()->numberBetween(1, 100),
        ];
    }
}
