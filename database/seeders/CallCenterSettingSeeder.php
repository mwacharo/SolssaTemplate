<?php

namespace Database\Seeders;

use App\Models\CallCenterSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CallCenterSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
                CallCenterSetting::factory()->count(1)->create();

    }
}
