<?php





use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{ public function up()
    {
        // Drop team_id from model_has_roles
        if (Schema::hasColumn('model_has_roles', 'team_id')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropPrimary();   // remove old composite PK
                $table->dropColumn('team_id');
            });

            // restore correct PK
            DB::statement('ALTER TABLE model_has_roles ADD PRIMARY KEY(model_id, model_type, role_id)');
        }

        // Drop team_id from model_has_permissions
        if (Schema::hasColumn('model_has_permissions', 'team_id')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->dropPrimary();
                $table->dropColumn('team_id');
            });

            DB::statement('ALTER TABLE model_has_permissions ADD PRIMARY KEY(model_id, model_type, permission_id)');
        }
    }

    public function down()
    {
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable();
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable();
        });
    }
};
