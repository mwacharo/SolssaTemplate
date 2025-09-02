<?php

namespace Database\Seeders;

use App\Models\CallCenterSetting;
use App\Models\Contact;
use App\Models\User;
use App\Models\Country;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the roles and permissions seeder
        $this->call([
            // RolesAndPermissionsSeeder::class,
            // CountrySeeder::class,
            // VendorSeeder::class,
            // GoogleSheetSeeder::class,
            // TemplateSeeder::class,
            // ContactSeeder::class,
            // MessageSeeder::class,
            // ChannelCredentialSeeder::class,
            //  RiderSeeder::class,
            // AgentSeeder::class,
            // WaybillSettingSeeder::class, // Fixed typo: was "aybillSettingSeeder"
            

            CallCenterSettingSeeder::class,
            IvrOptionSeeder::class,

            StatusSeeder::class,
            CitySeeder::class,
            ZoneSeeder::class,
            CourierSeeder::class,

            EmailTemplateSeeder::class,
            EmailSeeder::class,
        ]);

        // User::factory(1)->withPersonalTeam()->create();

        // User::factory()->withPersonalTeam()->create([
        //     'name' => 'Mwacharo',
        //     'email' => '12345678',
        // ]);

        // Create a user with a personal team
        // First, ensure we have a country to reference
        $country = Country::first();
        
        User::factory()->withPersonalTeam()->create([
            'name' => 'Mwacharo',
            'email' => 'john.boxleo@gmail.com',
            'password' => bcrypt('0741821113'),
            'is_active' => true,
            'two_factor_enabled' => false,
            'country_id' => $country ? $country->id : null, // Use first available country or null
        ]);
    }
}