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
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_no', 64)->unique();
            $table->string('reference', 128)->nullable()->unique();

            // Order belongs to a customer
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            // Order also belongs to a vendor (user with role "vendor")
            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Optional warehouse
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->unsignedSmallInteger('country_id');

            $table->string('source', 50)->default('manual');
            $table->string('platform', 50)->nullable();
            $table->string('currency', 3)->default('KSH');

            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->decimal('shipping_charges', 10, 2)->default(0);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->decimal('weight', 10, 2)->default(0);

            $table->boolean('paid')->default(0);
            $table->string('tracking_no', 128)->nullable();
            $table->string('waybill_no', 128)->nullable();
            $table->decimal('distance', 8, 2)->nullable();
            $table->boolean('geocoded')->default(0);

            $table->dateTime('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'created_at'], 'idx_vendor_created');
            $table->index(['customer_id', 'created_at'], 'idx_customer_created');
            $table->index(['country_id', 'created_at'], 'idx_country_created');
            $table->index('tracking_no', 'idx_tracking');
            $table->index('waybill_no', 'idx_waybill');
            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
