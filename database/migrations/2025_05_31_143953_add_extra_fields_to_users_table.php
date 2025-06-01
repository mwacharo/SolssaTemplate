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
        Schema::table('users', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('name');
            $table->string('address')->nullable()->after('client_name');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('token')->nullable()->after('state');
            $table->string('username')->nullable()->after('token');
            $table->string('phone_number')->nullable()->after('username');
            $table->string('alt_number')->nullable()->after('phone_number');
            $table->string('country_code', 5)->nullable()->after('alt_number');
            $table->string('time_zone')->default('UTC')->after('country_code');
            $table->string('language', 5)->default('en')->after('time_zone');
            $table->boolean('is_active')->default(true)->after('language');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->boolean('two_factor_enabled')->default(false)->after('last_login_ip');
            $table->string('timezone')->nullable()->after('two_factor_enabled');
            // $table->string('language', 5)->default('en')->after('timezone');

            // 
        });
    

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'client_name',
                'address',
                'city',
                'state',
                'token',
                'username',
                'phone_number',
                'alt_number',
                'country_code',
                'time_zone',
                'language',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'two_factor_enabled',
                'timezone',
                'language',
            ]);

         
        });
    }
};
