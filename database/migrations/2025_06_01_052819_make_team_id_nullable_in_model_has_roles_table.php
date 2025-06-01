<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable()->change();
    });
    
    Schema::table('model_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable()->change();
    });
}

public function down()
{
    Schema::table('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable(false)->change();
    });
    
    Schema::table('model_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('team_id')->nullable(false)->change();
    });
}
};
