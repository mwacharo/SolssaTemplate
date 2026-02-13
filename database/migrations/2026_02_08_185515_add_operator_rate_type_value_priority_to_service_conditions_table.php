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
        Schema::table('service_conditions', function (Blueprint $table) {
            // Add operator for numeric/select/boolean comparisons
            if (!Schema::hasColumn('service_conditions', 'operator')) {
                $table->enum('operator', ['between', '=', '>', '<', '>=', '<='])
                    ->default('between')
                    ->after('max_value');
            }

            // Add rate_type for fixed/percentage support
            if (!Schema::hasColumn('service_conditions', 'rate_type')) {
                $table->enum('rate_type', ['fixed', 'percentage'])
                    ->default('fixed')
                    ->after('rate');
            }

            // Add value column for select or boolean condition types
            if (!Schema::hasColumn('service_conditions', 'value')) {
                $table->string('value')->nullable()->after('rate_type');
            }

            // Add priority column to resolve overlapping rules
            if (!Schema::hasColumn('service_conditions', 'priority')) {
                $table->integer('priority')->default(0)->after('unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_conditions', function (Blueprint $table) {
            if (Schema::hasColumn('service_conditions', 'operator')) {
                $table->dropColumn('operator');
            }
            if (Schema::hasColumn('service_conditions', 'rate_type')) {
                $table->dropColumn('rate_type');
            }
            if (Schema::hasColumn('service_conditions', 'value')) {
                $table->dropColumn('value');
            }
            if (Schema::hasColumn('service_conditions', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
