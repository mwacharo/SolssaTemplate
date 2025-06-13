<?php

namespace Database\Factories;

use App\Models\GoogleSheet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoogleSheet>
 */
class GoogleSheetFactory extends Factory
{
    protected $model = GoogleSheet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sheet_name' => $this->faker->sentence(3),
            'post_spreadsheet_id' => Str::random(40),
            'active' => $this->faker->boolean(90),
            'auto_sync' => $this->faker->boolean(80),
            'sync_all' => $this->faker->boolean(20),
            'sync_interval' => $this->faker->randomElement([15, 30, 60]),
            'last_order_synced' => $this->faker->dateTimeBetween('-1 days', 'now'),
            'last_order_upload' => $this->faker->dateTimeBetween('-1 days', 'now'),
            'last_product_synced' => $this->faker->dateTimeBetween('-1 days', 'now'),
            'is_current' => $this->faker->boolean(10),
            'order_prefix' => 'ORD-' . $this->faker->year,
            'vendor_id' => 1, // Adjust if using vendor seeding or relationships
            'lastUpdatedOrderNumber' => 'ORD-' . $this->faker->year . '-' . $this->faker->numberBetween(100, 999),
            'country_id' => 1, // Adjust if using organizational unit seeding
        ];
    }
}
