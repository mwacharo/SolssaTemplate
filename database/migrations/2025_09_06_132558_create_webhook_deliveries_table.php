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
       Schema::create('webhook_deliveries', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('webhook_subscription_id');
          $table->unsignedBigInteger('order_event_id');
          $table->json('payload');
          $table->integer('response_code')->nullable();
          $table->text('response_body')->nullable();
          $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
          $table->integer('attempt_count')->default(0);
          $table->dateTime('next_retry_at')->nullable();
          $table->dateTime('delivered_at')->nullable();
          $table->timestamp('created_at')->useCurrent();
          $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

          $table->index('webhook_subscription_id', 'idx_subscription');
          $table->index('order_event_id', 'idx_order_event');
          $table->index('status', 'idx_status');

          $table->foreign('webhook_subscription_id', 'fk_webhook_deliveries_subscription')
             ->references('id')->on('webhook_subscriptions')->onDelete('cascade');
          $table->foreign('order_event_id', 'fk_webhook_deliveries_event')
             ->references('id')->on('order_events')->onDelete('cascade');
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
