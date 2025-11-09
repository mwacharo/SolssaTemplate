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
        Schema::create('shopifies', function (Blueprint $table) {
            $table->id();
            $table->string('shopify_key')->nullable();
            $table->string('shopify_secret')->nullable();
            $table->string('shopify_url')->nullable();
            $table->string('shopify_name')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('new_api')->default(false);
            $table->boolean('auto_sync')->default(false);
            $table->boolean('order_webhook')->default(false);
            $table->string('webhook_id')->nullable();
            $table->boolean('product_webhook')->default(false);
            $table->integer('sync_interval')->default(0);
            $table->timestamp('last_order_synced')->nullable();
            $table->timestamp('last_product_synced')->nullable();
            $table->string('order_prefix')->nullable();
            $table->string('sync_option')->nullable();
            $table->boolean('webhook_active')->default(false);
            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('country_id')->nullable();
                $table->softDeletes();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopifies');
    }
};
