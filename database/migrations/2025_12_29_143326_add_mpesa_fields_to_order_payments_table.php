<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            // STK identifiers
            $table->string('checkout_request_id', 191)->nullable()->after('transaction_id');
            $table->string('merchant_request_id', 191)->nullable()->after('checkout_request_id');

            // M-PESA details
            $table->string('mpesa_receipt', 64)->nullable()->after('merchant_request_id');
            $table->string('phone', 20)->nullable()->after('mpesa_receipt');

            // Callback result
            $table->integer('result_code')->nullable()->after('status');
            $table->string('result_desc')->nullable()->after('result_code');

            // Raw payloads
            $table->json('raw_response')->nullable()->after('meta');
            $table->json('raw_callback')->nullable()->after('raw_response');

            // Payment timestamp
            $table->timestamp('paid_at')->nullable()->after('updated_at');

            // Indexes
            $table->index('checkout_request_id', 'idx_checkout_request');
            $table->index('merchant_request_id', 'idx_merchant_request');
            $table->index('mpesa_receipt', 'idx_mpesa_receipt');
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropIndex('idx_checkout_request');
            $table->dropIndex('idx_merchant_request');
            $table->dropIndex('idx_mpesa_receipt');

            $table->dropColumn([
                'checkout_request_id',
                'merchant_request_id',
                'mpesa_receipt',
                'phone',
                'result_code',
                'result_desc',
                'raw_response',
                'raw_callback',
                'paid_at',
            ]);
        });
    }
};
