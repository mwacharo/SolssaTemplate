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
        Schema::table('roles', function (Blueprint $table) {
            //
            // In your roles migration
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->string('color')->default('blue');
            $table->string('icon')->default('mdi-account-group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            //

            $table->dropColumn(['description', 'active', 'color', 'icon']);
        });
    }
};
