<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   


    public function up(): void
{
    Schema::table('contacts', function (Blueprint $table) {

        // Who created the contact
        $table->foreignId('created_by_user_id')
              ->nullable()
              ->after('id')
              ->constrained('users')
              ->nullOnDelete();

        // Who is assigned to the contact
        $table->foreignId('assigned_to_user_id')
              ->nullable()
              ->after('created_by_user_id')
              ->constrained('users')
              ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('contacts', function (Blueprint $table) {
        $table->dropConstrainedForeignId('created_by_user_id');
        $table->dropConstrainedForeignId('assigned_to_user_id');
    });
}

};
