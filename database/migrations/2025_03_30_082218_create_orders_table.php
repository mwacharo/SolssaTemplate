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
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedSmallInteger('country_id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('source', 50)->default('manual');
            $table->string('platform', 50)->nullable();
            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->decimal('shipping_charges', 10, 2)->default(0);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->decimal('weight', 10, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('delivery_status')->default(0);
            $table->boolean('paid')->default(0);
            $table->string('tracking_no', 128)->nullable();
            $table->string('waybill_no', 128)->nullable();
            $table->decimal('distance', 8, 2)->nullable();
            $table->boolean('geocoded')->default(0);
            $table->dateTime('archived_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Indexes
            $table->index(['customer_id', 'created_at'], 'idx_customer_created');
            $table->index(['vendor_id', 'status'], 'idx_vendor_status');
            $table->index(['warehouse_id', 'status'], 'idx_warehouse_status');
            $table->index(['country_id', 'created_at'], 'idx_country_created');
            $table->index('tracking_no', 'idx_tracking');
            $table->index('waybill_no', 'idx_waybill');
            $table->index(['status', 'delivery_status'], 'idx_status_delivery');
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
