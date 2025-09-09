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
       Schema::create('cities', function (Blueprint $table) {
          $table->id();
          $table->string('name');
          $table->unsignedBigInteger('country_id');
          // $table->unsignedBigInteger('state_id')->nullable();
          $table->decimal('latitude', 10, 7)->nullable();
          $table->decimal('longitude', 10, 7)->nullable();
          $table->unsignedBigInteger('population')->nullable();
            $table->boolean('inbound')->default(false);
          $table->timestamps();
          $table->softDeletes();

          $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
          // $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
