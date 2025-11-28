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
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('expedition_id')
                ->constrained('expeditions')
                ->onDelete('cascade');

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict');

            // Quantity Fields
            $table->integer('quantity_sent')->default(0);
            $table->integer('quantity_received')->default(0)->nullable();

            // Financial Fields
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_value', 12, 2)->nullable();

            // Status and Notes
            $table->enum('status', ['pending', 'partial', 'received', 'damaged', 'missing'])
                ->default('pending');
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('expedition_id');
            $table->index('product_id');
            $table->index('status');
            $table->index(['expedition_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
