<?php

namespace Database\Seeders;

use App\Models\WaybillSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WaybillSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
                WaybillSetting::factory()->count(2)->create();

    }
}
