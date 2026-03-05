<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittance_order_charges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('remittance_order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->constrained();

            // pricing source
            // $table->enum('rate_source', ['vendor', 'global']);

            $table->enum('rate_type', ['fixed', 'percentage']);

            $table->decimal('rate_value', 10, 2);

            // final computed charge
            $table->decimal('amount', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittance_order_charges');
    }
};
