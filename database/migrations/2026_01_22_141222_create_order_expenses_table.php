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
        Schema::create('order_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // optional user reference — expense may not belong to a user, so don't enforce FK
            $table->unsignedBigInteger('user_id')->nullable();
            // $table->string('expense_type');
            $table->foreignId('expense_type_id')
                ->constrained('expense_types')
                ->restrictOnDelete();

            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->date('incurred_on')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->softDeletes();
            $table->string('payment_reference')->nullable();
            $table->string('currency', 3)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_expenses');
    }
};
