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
        Schema::create('order_status_timestamps', function (Blueprint $table) {
            $table->id();
            // foreign key to orders table
            $table->unsignedBigInteger('order_id')->unique();
            $table->timestamp('to_prepare_at')->nullable();
            $table->timestamp('undispatched_at')->nullable();
            $table->timestamp('awaiting_return_at')->nullable();
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('return_received_at')->nullable();
            $table->timestamp('awaiting_dispatch_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('new_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();  // shipped
            $table->timestamp('scheduled_at')->nullable();   // to_prepare
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('out_of_stock_at')->nullable();
            $table->timestamp('spam_at')->nullable();
            $table->timestamp('duplicated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_timestamps');
    }
};
