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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('page_name');
            $table->json('page_slug');
            $table->json('page_content')->nullable();
            $table->json('page_meta_title')->nullable();
            $table->json('page_meta_description')->nullable();
            $table->json('page_meta_keywords')->nullable();
            $table->boolean('page_status')->default(1)->comment('1 = Publish, 0 = Draft');
            $table->string('theme_id')->nullable();
            $table->integer('store_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
