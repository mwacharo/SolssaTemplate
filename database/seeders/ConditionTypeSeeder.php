<?php

namespace Database\Seeders;

use App\Models\ConditionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConditionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $conditions = [



            // 📦 Parcel Characteristics
            [
                'name' => 'Weight',
                'code' => 'weight',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'kg',
                'meta' => ['min' => 0, 'step' => 0.1],
            ],
            [
                'name' => 'Parcel Volume',
                'code' => 'volume',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'cbm',
            ],
            [
                'name' => 'Parcel Type',
                'code' => 'parcel_type',
                'input_type' => 'select',
                'supports_range' => false,
                'meta' => [
                    'options' => ['standard', 'fragile', 'liquid', 'oversized'],
                ],
            ],

            // 📍 Location & Distance
            [
                'name' => 'Distance',
                'code' => 'distance',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'km',
            ],
            [
                'name' => 'Zone',
                'code' => 'zone',
                'input_type' => 'select',
                'supports_range' => false,
                'meta' => [
                    'options' => ['zone_a', 'zone_b', 'zone_c', 'zone_d'],
                ],
            ],
            [
                'name' => 'Region',
                'code' => 'region',
                'input_type' => 'select',
                'supports_range' => false,
                'meta' => [
                    'options' => ['nairobi', 'central', 'rift_valley', 'coast', 'upcountry'],
                ],
            ],

            // 💰 Value & Payments
            [
                'name' => 'Order Value',
                'code' => 'order_value',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'KES',
            ],
            [
                'name' => 'Cash on Delivery',
                'code' => 'cod',
                'input_type' => 'boolean',
                'supports_range' => false,
            ],

            // 🏢 Fulfillment & Storage
            [
                'name' => 'Pick & Pack Items',
                'code' => 'pick_pack_items',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'items',
            ],
            [
                'name' => 'Storage Duration',
                'code' => 'storage_days',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'days',
            ],

            // ⏱ SLA & Priority
            [
                'name' => 'Delivery Speed',
                'code' => 'delivery_speed',
                'input_type' => 'select',
                'supports_range' => false,
                'meta' => [
                    'options' => ['same_day', 'next_day', 'standard'],
                ],
            ],
            [
                'name' => 'Weekend Delivery',
                'code' => 'weekend',
                'input_type' => 'boolean',
                'supports_range' => false,
            ],

            // 📊 Merchant Volume
            [
                'name' => 'Monthly Order Volume',
                'code' => 'monthly_orders',
                'input_type' => 'numeric',
                'supports_range' => true,
                'unit' => 'orders',
            ],

        ];

        foreach ($conditions as $condition) {
            ConditionType::updateOrCreate(
                ['code' => $condition['code']],
                array_merge($condition, ['is_active' => true])
            );
        }
    }
}
