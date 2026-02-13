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
        Schema::create('php artisan make:migration add_operator_rate_type_value_priority_to_service_conditions_table --table=service_conditions
', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            // $table->string('condition_type'); // e.g., weight, distance, region, flat_rate
            $table->foreignId('condition_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_value', 12, 2)->nullable();
            $table->decimal('max_value', 12, 2)->nullable();
            $table->decimal('rate', 12, 2)->default(0);
            $table->string('unit')->nullable(); // kg, km, %, flat
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_conditions');
    }
};
