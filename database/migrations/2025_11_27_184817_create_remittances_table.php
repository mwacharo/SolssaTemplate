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
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();

            // Basic identifiers
            $table->string('old_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date')->nullable();

            // Periods
            $table->date('payment_period_start')->nullable();
            $table->date('payment_period_end')->nullable();

            // Approval status
            $table->enum('approval_status', ['draft', 'manager_approved', 'cfo_approved', 'rejected'])
                ->default('draft');

            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamp('cfo_approved_at')->nullable();

            // Relationships
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_cfo_id')->nullable()->constrained('users')->nullOnDelete();

            // Financial totals
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_amount_mad', 15, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(0);
            $table->integer('conversion_rate')->default(1);

            // Fees
            $table->decimal('bonus_amount', 15, 2)->default(0);
            $table->decimal('confirmation_fee', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('fulfillement_fee', 15, 2)->default(0);
            $table->decimal('return_fee', 15, 2)->default(0);

            $table->decimal('inbound_return_fee', 15, 2)->default(0);
            $table->decimal('outbound_return_fee', 15, 2)->default(0);
            $table->decimal('inbound_shipping_fee', 15, 2)->default(0);
            $table->decimal('outbound_shipping_fee', 15, 2)->default(0);

            $table->decimal('cancelation_fee', 15, 2)->default(0);
            $table->decimal('percentage_fee', 10, 2)->default(0);
            $table->decimal('affiliate_fee', 15, 2)->default(0);
            $table->decimal('upsell_fee', 15, 2)->default(0);
            $table->decimal('additional_fees', 15, 2)->default(0);

            $table->decimal('total_marketplace_cost', 15, 2)->default(0);

            // Payment
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->timestamp('payment_date')->nullable();
            $table->string('payment_method')->nullable()->default('cash');

            // Debt
            $table->decimal('debt_amount', 15, 2)->default(0);
            $table->enum('debt_paid_status', ['paid', 'unpaid'])->default('paid');
            $table->foreignId('debt_invoice_id')->nullable()->constrained('remittances')->nullOnDelete();

            // Country
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();

            // Marketplace flag
            $table->boolean('is_marketplace')->default(false);

            // Reason on rejection
            $table->text('rejection_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
