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
        Schema::create('expeditions', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('vendor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('weight', 10, 2);
            $table->integer('packages_number');
            $table->string('source_country');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->date('shipment_date');
            $table->dateTime('arrival_date')->nullable();
            $table->decimal('shipment_fees', 10, 2);
            $table->string('shipment_status');
            $table->string('approval_status');
            $table->string('transporter_reimbursement_status');
            $table->string('transporter_name');
            $table->string('tracking_number')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expeditions');
    }
};
