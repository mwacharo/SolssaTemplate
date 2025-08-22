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
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Size", "Color"
            $table->timestamps();
            $table->softDeletes();

            // Pivot table for many-to-many relationship with products
            // $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // $table->foreignId('attribute_value_id')->constrained('attribute_values')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
