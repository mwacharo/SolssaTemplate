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
        Schema::table('permissions', function (Blueprint $table) {
            //


            // Add custom fields to permissions table
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->string('color')->default('green');
            $table->string('icon')->default('mdi-key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            //

            $table->dropColumn(['description', 'active', 'color', 'icon']);
        });
    }
};
