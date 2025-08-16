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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Link to users table
            $table->string('from'); // Sender email, can be a system email or user email
            $table->string('to'); // Recipient email
            $table->string('subject');
            $table->longText('body');
            $table->enum('status', ['draft', 'sent', 'failed', 'scheduled'])->default('draft');
            $table->timestamp('sent_at')->nullable(); // When the email was actually sent or scheduled
            $table->timestamps();
            $table->softDeletes(); // For soft delete functionality
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
