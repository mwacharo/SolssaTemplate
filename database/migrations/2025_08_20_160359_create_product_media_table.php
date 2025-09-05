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
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('media_type')->nullable(); // image, video, pdf, doc
            $table->string('url')->nullable();        // storage/public path or external link
            $table->string('alt_text')->nullable(); // for SEO & accessibility
            $table->boolean('is_primary')->default(false)->nullable(); // true if main image/video
            $table->integer('position')->default(0)->nullable(); // order in gallery
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
