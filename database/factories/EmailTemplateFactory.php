<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\EmailTemplate;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => $this->faker->unique()->words(3, true), // e.g. "Order Shipped Notification"
            'subject'      => $this->faker->sentence(6),              // e.g. "Your package is on its way!"
            'body'         => "<p>Hello {{name}},</p><p>Your order #{{order_number}} has been shipped.</p>",
            'placeholders' => ['name', 'order_number'],
        ];
    }
}
