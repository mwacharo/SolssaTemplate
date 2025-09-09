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
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();    
            $table->string('callback_url', 512);
            $table->json('events')->nullable();
            $table->string('secret', 191)->nullable();
            $table->boolean('active')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
