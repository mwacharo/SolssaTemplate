<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Email;
use App\Models\EmailTemplate;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Email>
 */
class EmailFactory extends Factory
{
    protected $model = Email::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'to'            => $this->faker->safeEmail(),
            'user_id'      => \App\Models\User::factory(), // links to a user
            'from'          => $this->faker->companyEmail(),
            'subject'       => $this->faker->sentence(6),
            'body'          => $this->faker->paragraphs(3, true),
            'status'        => $this->faker->randomElement(['draft', 'scheduled', 'queued', 'sent', 'failed']),
            'sent_at'       => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            // 'template_id'   => EmailTemplate::factory(), // links to a template
        ];
    }
}
