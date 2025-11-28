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
        Schema::create('seller_expenses', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('vendor_id');
            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('expense_type')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('status')->default('pending');
            $table->date('incurred_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_expenses');
    }
};
