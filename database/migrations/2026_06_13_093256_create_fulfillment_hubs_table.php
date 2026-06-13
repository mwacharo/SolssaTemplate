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
        Schema::create('fulfillment_hubs', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('name');

            // Relationships
            $table->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('last_call_agent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Optional: if you want to track ownership
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_hubs');
    }
};
