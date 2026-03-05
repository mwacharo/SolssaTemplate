<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittance_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('remittance_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // financial snapshot
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->decimal('total_charges', 12, 2)->default(0);
            $table->decimal('net_remit', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(['remittance_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittance_orders');
    }
};
