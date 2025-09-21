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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('base_price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->string('currency', 10);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
