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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // vendor_id points to users.id
            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

                // product belongs to a country
                $table->foreignId('country_id')
                    ->constrained('countries')
                    ->cascadeOnDelete();

            $table->string('product_name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('vendor_id');
            $table->index('category_id');
            $table->index('sku');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
