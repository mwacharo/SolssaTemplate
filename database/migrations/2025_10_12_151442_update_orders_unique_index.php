<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old unique index if it exists
        $indexes = collect(DB::select('SHOW INDEX FROM orders'))
            ->pluck('Key_name')
            ->unique();

        if ($indexes->contains('orders_order_no_unique')) {
            DB::statement('DROP INDEX orders_order_no_unique ON orders');
        }

        // ✅ Create a normal (non-unique) index instead
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_no', 'orders_order_no_index');
        });
    }

    public function down(): void
    {
        // Drop the normal index
        $indexes = collect(DB::select('SHOW INDEX FROM orders'))
            ->pluck('Key_name')
            ->unique();

        if ($indexes->contains('orders_order_no_index')) {
            DB::statement('DROP INDEX orders_order_no_index ON orders');
        }

        // Optionally recreate the old strict unique index
        DB::statement('CREATE UNIQUE INDEX orders_order_no_unique ON orders (order_no)');
    }
};
