<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_rates', function (Blueprint $table) {
            // Add rate_type column for fixed / percentage
            if (!Schema::hasColumn('service_rates', 'rate_type')) {
                $table->enum('rate_type', ['fixed', 'percentage'])
                    ->nullable()
                    ->after('custom_rate')
                    ->comment('Overrides rate type from service_conditions; null = inherit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_rates', function (Blueprint $table) {
            if (Schema::hasColumn('service_rates', 'rate_type')) {
                $table->dropColumn('rate_type');
            }
        });
    }
};
