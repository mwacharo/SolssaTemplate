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
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('email')->unique()->nullable();
            // $table->string('vehicle_type')->nullable();
            // $table->string('license_plate')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('country_id');
            // $table->unsignedBigInteger('city_id')->nullable();
            // $table->unsignedBigInteger('zone_id')->nullable();
            $table->timestamps();
            $table->softDeletes(); // for soft delete functionality 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
