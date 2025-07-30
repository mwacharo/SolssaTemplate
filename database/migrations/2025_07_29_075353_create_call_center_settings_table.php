<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('call_center_settings', function (Blueprint $table) {
    //         $table->id();
    //         $table->timestamps();
    //     });
    // }




    public function up(): void
{
    Schema::create('call_center_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained()->onDelete('cascade');
        $table->string('username');
        $table->string('api_key');
        $table->string('phone');
        $table->boolean('sandbox')->default(true);

        $table->string('default_voice')->default('woman');
        $table->integer('timeout')->default(3);
        $table->boolean('recording_enabled')->default(true);

        $table->text('welcome_message')->nullable();
        $table->text('no_input_message')->nullable();
        $table->text('invalid_option_message')->nullable();
        $table->text('connecting_agent_message')->nullable();
        $table->text('agents_busy_message')->nullable();
        $table->text('voicemail_prompt')->nullable();

        $table->string('callback_url')->nullable();
        $table->string('event_callback_url')->nullable();
        $table->string('ringback_tone')->nullable();
        $table->string('voicemail_callback')->nullable();

        $table->string('fallback_number')->nullable();
        $table->string('default_forward_number')->nullable();

        $table->boolean('debug_mode')->default(false);
        $table->string('log_level')->default('info');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_center_settings');
    }
};
