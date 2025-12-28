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
        Schema::table('order_status_timestamps', function (Blueprint $table) {
            $table->foreignId('status_category_id')
                ->nullable()
                ->after('status_id') // optional: controls column position
                ->constrained('status_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_timestamps', function (Blueprint $table) {
            $table->dropForeign(['status_category_id']);
            $table->dropColumn('status_category_id');
        });
    }
};
