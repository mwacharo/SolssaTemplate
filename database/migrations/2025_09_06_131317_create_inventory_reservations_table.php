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
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 128);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('released_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sku', 'idx_sku');
            $table->index('order_id', 'idx_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
