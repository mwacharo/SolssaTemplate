<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
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
            //  GoogleSheetSeeder::class,
            // TemplateSeeder::class,
            //    ContactSeeder::class,
            // MessageSeeder::class,
            // ChannelCredentialSeeder::class

            // AgentSeeder::class,
            // RiderSeeder::class,


        ]);

        // User::factory(10)->withPersonalTeam()->create();

        // User::factory()->withPersonalTeam()->create([
        //     'name' => 'Mwacharo',
        //     'email' => '12345678',
        // ]);
    }
}
