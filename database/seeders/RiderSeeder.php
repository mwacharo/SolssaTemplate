<?php

namespace Database\Seeders;

use App\Models\Rider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RiderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
       public function run(): void
    {
        // Create 10 riders
        Rider::factory()
            ->count(2)
            ->create()
            ->each(function ($rider) {
                // Set the role of the related user to 'rider'
                // $rider->user->update(['role' => 'rider']);
            });
    }

}
