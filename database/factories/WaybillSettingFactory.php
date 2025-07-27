<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WaybillSetting>
 */
class WaybillSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id'    => $this->faker->numberBetween(1,2),
            'template_name' => $this->faker->word,
            'options'       => json_encode(['color' => $this->faker->safeColorName, 'size' => $this->faker->randomElement(['A4', 'A5'])]),
            'name'          => $this->faker->company,
            'phone'         => $this->faker->phoneNumber,
            'email'         => $this->faker->unique()->safeEmail,
            'address'       => $this->faker->address,
            'terms'         => $this->faker->sentence,
            'footer'        => $this->faker->sentence,
        ];
    }
}
