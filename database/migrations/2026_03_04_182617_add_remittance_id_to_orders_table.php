<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // Add column
            $table->unsignedBigInteger('remittance_id')
                ->nullable()
                ->after('id');

            // Foreign key
            $table->foreign('remittance_id')
                ->references('id')
                ->on('remittances')
                ->nullOnDelete();

            // Performance index (VERY important)
            $table->index(['vendor_id', 'remittance_id']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropForeign(['remittance_id']);
            $table->dropIndex(['vendor_id', 'remittance_id']);
            $table->dropColumn('remittance_id');
        });
    }
};
