<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConditionType>
 */
class ConditionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return aarray<string, mixed>
     */
    public function definition(): array
    {
        return [
            //


            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->slug(2),
            'input_type' => 'numeric',
            'supports_range' => true,
            'unit' => null,
            'meta' => null,
            'is_active' => true,
        ];
    }
}
