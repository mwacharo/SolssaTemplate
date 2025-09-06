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
       Schema::create('refunds', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('order_id');
          $table->unsignedBigInteger('payment_id')->nullable();
          $table->decimal('amount', 18, 2)->default(0);
          $table->unsignedTinyInteger('status')->default(0); // 0=requested,1=processed,2=failed
          $table->string('reason', 255)->nullable();
          $table->json('meta')->nullable();
          $table->timestamp('created_at')->useCurrent();
          $table->index('order_id', 'idx_order');
          $table->index('payment_id', 'idx_payment');
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
