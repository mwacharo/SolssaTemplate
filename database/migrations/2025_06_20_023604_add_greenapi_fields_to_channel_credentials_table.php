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
        Schema::table('channel_credentials', function (Blueprint $table) {
            //

            // Add Green-API fields; nullable so other channels aren’t affected
            $table->string('instance_id')->nullable()->after('phone_number');
            $table->string('api_token')->nullable()->after('instance_id');
            $table->string('api_url')->nullable()->after('api_token')
                ->default('https://api.green-api.com');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channel_credentials', function (Blueprint $table) {
            //
            $table->dropColumn(['instance_id', 'api_token', 'api_url']);
        });
    }
};
