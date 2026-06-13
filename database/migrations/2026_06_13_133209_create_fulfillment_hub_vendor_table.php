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
        Schema::create('fulfillment_hub_vendor', function (Blueprint $table) {
            // $table->id();
            // $table->timestamps();

            $table->foreignId('fulfillment_hub_id')
                ->constrained()
                ->cascadeOnDelete();


            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->softDeletes();
            // timestamps if you want to track when vendors were assigned/unassigned
            $table->timestamps();
            //  $table->unique(['fulfillment_hub_id', 'vendor_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_hub_vendor');
    }
};
