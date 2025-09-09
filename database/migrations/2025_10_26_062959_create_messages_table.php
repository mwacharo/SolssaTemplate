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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // belongs to an order
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('messageable'); // adds messageable_id and messageable_type columns

            $table->string('channel')->nullable(); // SMS, Email, WhatsApp etc.
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('content')->nullable();
            $table->string('status')->default('sent'); // sent, delivered, failed
            $table->timestamp('sent_at')->nullable();
            $table->json('response_payload')->nullable();

            // Added fields
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->text('body')->nullable();
            $table->string('message_type')->nullable()->default('text');
            $table->text('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('message_status')->default('pending');
            $table->string('external_message_id')->nullable();
            $table->string('reply_to_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->enum('direction', ['incoming', 'outgoing'])->default('outgoing');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
