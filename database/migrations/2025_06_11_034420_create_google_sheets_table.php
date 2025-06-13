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
        Schema::create('google_sheets', function (Blueprint $table) {

            $table->id();
            $table->string('sheet_name')->nullable();
            $table->string('post_spreadsheet_id')->nullable();
            $table->boolean('active')->default(1);
            $table->boolean('auto_sync')->default(1);
            $table->boolean('sync_all')->default(0);
            $table->integer('sync_interval')->default(30);
            $table->dateTime('last_order_synced')->nullable();
            $table->dateTime('last_order_upload')->nullable();
            $table->dateTime('last_product_synced')->nullable();
            $table->boolean('is_current')->default(0);
            $table->string('order_prefix')->nullable();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('lastUpdatedOrderNumber')->nullable();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete()->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft delete column

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_sheets');
    }
};
