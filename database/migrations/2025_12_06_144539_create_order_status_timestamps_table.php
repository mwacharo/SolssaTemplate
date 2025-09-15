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
        Schema::create('order_status_timestamps', function (Blueprint $table) {
            $table->id();

            // Match orders.id (unsigned big int)
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Foreign key to statuses table
            $table->foreignId('status_id')
                ->nullable()
                ->constrained('statuses')
                ->nullOnDelete();

            $table->text('status_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Ensure each order can only have one timestamp per status
            // $table->unique(['order_id', 'status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_timestamps');
    }
};
