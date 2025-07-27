<?php

namespace Database\Seeders;

use App\Models\GoogleSheet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GoogleSheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
                GoogleSheet::factory()->count(2)->create(); // Create 10 fake countries

    }
}
