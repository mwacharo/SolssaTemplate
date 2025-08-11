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
        // Schema::create('call_transcripts', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });


                Schema::create('call_transcripts', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->nullable()->index(); // Africa's Talking call id
            $table->unsignedBigInteger('user_id')->nullable()->index(); // optional: agent or merchant id
            $table->string('recording_url')->nullable();
            $table->text('transcript')->nullable();
            $table->string('sentiment')->nullable(); // positive|neutral|negative
            $table->integer('fulfillment_score')->nullable(); // 0-100
            $table->tinyInteger('cs_rating')->nullable(); // 1-5
            $table->json('analysis')->nullable(); // store extras like intents, confidence, keywords
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // for soft delete functionality
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_transcripts');
    }
};
