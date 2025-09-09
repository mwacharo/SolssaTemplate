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
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable(); // nullable for parcel-only
            $table->string('sku', 128)->nullable();
            $table->string('name', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->string('currency', 3)->default('KSH');
            $table->decimal('weight', 10, 2)->nullable();
        $table->unsignedInteger('delivered_quantity')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->nullable();
            $table->softDeletes();

            $table->index('order_id', 'idx_order');
            $table->index('product_id', 'idx_product');

            // Foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
