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
        // Schema::create('product_statistics', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });


        Schema::create('product_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('total_orders')->default(0);
            $table->unsignedBigInteger('total_pending')->default(0);
            $table->unsignedBigInteger('total_cancelled')->default(0);
            $table->unsignedBigInteger('total_confirmed')->default(0);
            $table->unsignedBigInteger('total_in_delivery')->default(0);
            $table->unsignedBigInteger('total_returned')->default(0);
            $table->unsignedBigInteger('total_delivered')->default(0);

            $table->decimal('delivery_rate', 5, 2)->default(0.00); // e.g. 87.50 %

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_statistics');
    }
};
