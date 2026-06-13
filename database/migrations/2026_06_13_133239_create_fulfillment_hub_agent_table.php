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
        Schema::create('fulfillment_hub_agent', function (Blueprint $table) {


            $table->foreignId('fulfillment_hub_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_hub_agent');
    }
};
