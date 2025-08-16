<?php

namespace Database\Seeders;

use App\Models\Email;
use App\Models\EmailTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
      public function run(): void
    {
        // First, create some templates
        $templates = Email::factory()
            ->count(5)
            ->create();

        // Then, create emails using those templates
        $templates->each(function ($template) {
            Email::factory()
                ->count(10) // 10 emails per template


                // ->create([
                //     'template_id' => $template->id,
                // ])
                
                ;
        });
    }
}
