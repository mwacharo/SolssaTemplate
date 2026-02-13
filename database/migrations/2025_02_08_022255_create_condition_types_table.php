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
        Schema::create('condition_types', function (Blueprint $table) {
            $table->id();

            // Display name (Weight, Distance, Zone, Region, Parcel Value etc.)
            $table->string('name');

            // Unique system code (weight, distance, zone, order_value)
            $table->string('code')->unique();

            // How UI should render it
            // numeric | select | boolean | text | json
            $table->string('input_type');

            // Does it support min/max ranges?
            $table->boolean('supports_range')->default(false);

            // Optional unit (kg, km, KES, etc.)
            $table->string('unit')->nullable();

            // Extra configuration (for select options, validations etc.)
            $table->json('meta')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condition_types');
    }
};
