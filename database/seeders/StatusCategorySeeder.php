<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatusCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Get the PENDING status ID
        $pendingStatusId = DB::table('statuses')
            ->where('name', 'Pending')
            ->value('id');

        // Safety check
        if (!$pendingStatusId) {
            $this->command->error('PENDING status not found. Seed statuses table first.');
            return;
        }

        $categories = [
            'No Contact',
            'Follow Up',
            'Interested',
            'Not Financially Ready',
            'Declined',
            'Invalid Lead',
            'Unavailable – Location',
            'Delivery Issue',
        ];

        foreach ($categories as $category) {
            DB::table('status_categories')->updateOrInsert(
                ['name' => $category], // because name is unique
                [
                    'status_id' => $pendingStatusId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
