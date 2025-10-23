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
        Schema::create('call_histories', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('order_id')->constrained()->cascadeOnDelete(); // link to order
            // $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who made the call (agent)
            $table->foreignId('order_id')->nullable()->change();

            $table->foreignId('ivr_option_id')->nullable()->constrained('ivr_options')->nullOnDelete();


            // Call metadata
            $table->string('lastBridgeHangupCause')->nullable();
            $table->boolean('isActive')->default(false);
            $table->string('direction')->nullable(); // inbound, outbound
            $table->string('sessionId')->nullable()->index();
            $table->string('callerNumber')->nullable();
            $table->string('callerCountryCode')->nullable();
            $table->string('clientDialedNumber')->nullable();
            $table->string('callerCarrierName')->nullable();
            $table->string('destinationNumber')->nullable();
            $table->integer('durationInSeconds')->nullable();
            $table->string('currencyCode')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('recordingUrl')->nullable();
            $table->string('hangupCause')->nullable();

            // Admin/agent references
            $table->unsignedBigInteger('adminId')->nullable();
            $table->unsignedBigInteger('agentId')->nullable();
            $table->string('orderNo')->nullable();

            // Notes & workflow
            $table->longText('notes')->nullable();
            $table->string('nextCallStep')->nullable();
            $table->string('conference')->nullable();
            $table->string('status')->nullable();
            $table->string('callSessionState')->nullable();

            // Timestamps
            $table->timestamp('callStartTime')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();

            // Extra fields
            $table->string('call_uuid')->nullable()->index();
            $table->string('call_type')->nullable();   // voice, video
            $table->string('call_result')->nullable(); // completed, failed
            $table->string('customer_name')->nullable();
            $table->string('customer_number')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('call_source')->nullable(); // web, mobile
            $table->boolean('is_recorded')->default(false);
            $table->timestamp('call_started_at')->nullable();
            $table->timestamp('call_ended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['order_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_histories');
    }
};
