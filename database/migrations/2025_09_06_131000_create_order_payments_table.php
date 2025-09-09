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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('method', 64);
            $table->string('transaction_id', 191)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0); // 0=pending,1=confirmed,2=failed
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
            $table->index('order_id', 'idx_order');
            $table->index('transaction_id', 'idx_tx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
