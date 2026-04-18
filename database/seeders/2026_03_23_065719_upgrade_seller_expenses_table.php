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
        //


        Schema::table('seller_expenses', function (Blueprint $table) {

            // Replace string expense_type with FK later
            $table->foreignId('expense_type_id')
                ->nullable()
                ->after('amount')
                ->constrained('expense_types')
                ->nullOnDelete();

            $table->decimal('vat_amount', 15, 2)->default(0)->after('amount');

            $table->string('currency', 10)->default('KES')->after('amount');

            // $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])
            //     ->default('pending')
            //     ->change();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            // $table->foreignId('country_id')
            //     ->nullable()
            //     ->constrained('countries')
            //     ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
