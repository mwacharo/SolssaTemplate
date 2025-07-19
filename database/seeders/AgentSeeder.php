<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 users with role = 'agent'
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($user) {
                Agent::factory()->create([
                    'user_id' => $user->id,
                    'email' => $user->email, // Optional: match user email
                    'name' => $user->name,   // Optional: match user name
                ]);
            });
    }
}
