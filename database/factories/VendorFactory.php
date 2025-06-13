<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'company_name' => $this->faker->optional()->company,
            'email' => $this->faker->unique()->safeEmail,
            'billing_email' => $this->faker->optional()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'alt_phone' => $this->faker->optional()->phoneNumber,
            'address' => $this->faker->address,
            'city' => $this->faker->optional()->city,
            'state' => $this->faker->optional()->state,
            'zip_code' => $this->faker->optional()->postcode,
            'country' => $this->faker->optional()->country,
            'region' => $this->faker->optional()->state,
            'warehouse_location' => $this->faker->optional()->address,
            'preferred_pickup_time' => $this->faker->optional()->time('H:i'),
            'contact_person_name' => $this->faker->optional()->name,
            'business_type' => $this->faker->optional()->randomElement(['LLC', 'Corporation', 'Sole Proprietor', 'Partnership']),
            'registration_number' => $this->faker->optional()->bothify('REG-#######'),
            'tax_id' => $this->faker->optional()->bothify('TAX-########'),
            'website_url' => $this->faker->optional()->url,
            'social_media_links' => $this->faker->optional()->randomElement([
                json_encode(['facebook' => $this->faker->url, 'twitter' => $this->faker->url]),
                json_encode(['linkedin' => $this->faker->url])
            ]),
            'bank_account_info' => $this->faker->optional()->randomElement([
                json_encode(['account_number' => $this->faker->bankAccountNumber, 'bank_name' => $this->faker->company]),
                json_encode(['iban' => $this->faker->iban, 'swift' => $this->faker->swiftBicNumber])
            ]),
            'delivery_mode' => $this->faker->randomElement(['pickup', 'delivery', 'both']),
            'payment_terms' => $this->faker->optional()->randomElement(['Net 30', 'Net 60', 'Due on receipt']),
            'credit_limit' => $this->faker->randomFloat(2, 0, 100000),
            'integration_id' => $this->faker->optional()->uuid,
            'onboarding_stage' => $this->faker->randomElement(['pending', 'active', 'verified']),
            'last_active_at' => $this->faker->optional()->dateTimeThisYear,
            'rating' => $this->faker->optional()->randomFloat(1, 1, 5),
            'status' => $this->faker->boolean(90),
            'notes' => $this->faker->optional()->paragraph,
            'user_id' => \App\Models\User::factory(),
            // 'branch_id' => \App\Models\Branch::factory(), // Uncomment if using branch_id
        ];
    }
}
