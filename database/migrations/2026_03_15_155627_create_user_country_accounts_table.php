<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_country_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            // Per-country identity
            $table->string('client_name')->nullable();
            $table->string('token')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('alt_number')->nullable();
            $table->string('country_code', 5)->nullable();

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_country_accounts');
    }
};
