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
       Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->string('full_name')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->string('alt_phone')->nullable();
    $table->string('address')->nullable();
    // $table->string('city')->nullable();
    $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
    $table->string('region')->nullable();

    // vendor is a user with role "vendor"
    $table->foreignId('vendor_id')
          ->nullable()
          ->constrained('users')
          ->cascadeOnDelete();

    $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();

    $table->string('zipcode')->nullable();

    // spam flag
    $table->boolean('is_spam')->default(0);

    $table->timestamps();
    $table->softDeletes();

    $table->index('vendor_id');
    $table->index('country_id');
    $table->index('zone_id');
});

    }

    /**
     * Reverse the migrations.  
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
