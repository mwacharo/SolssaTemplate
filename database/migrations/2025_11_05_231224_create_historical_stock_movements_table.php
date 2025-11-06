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
        // Schema::create('historical_stock_movements', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });


        Schema::create('historical_stock_movements', function (Blueprint $table) {
            $table->id();

            // which product was affected
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // which order caused it (optional, because sometimes manual stock adjustment)
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();

            // which status change triggered it (the event)
            $table->foreignId('status_timestamp_id')
                ->nullable()
                ->constrained('order_status_timestamps')
                ->cascadeOnDelete();

            // type of movement: RESERVED, DEDUCTED, RETURNED, etc.
            $table->string('movement_type');

            // quantity affected (always positive)
            $table->integer('quantity');

            // Stock values before/after — for full audit trail
            $table->json('before_stock')->nullable();
            $table->json('after_stock')->nullable();

            // who triggered the movement (optional)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // prevent duplicate identical movement (idempotency)
            $table->unique(['product_id', 'order_id', 'status_timestamp_id', 'movement_type'], 'unique_stock_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_stock_movements');
    }
};
