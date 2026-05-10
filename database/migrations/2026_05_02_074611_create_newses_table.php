<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();

            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

            $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();

            $table->string('title', 255);

            $table->string('sub_title', 255)->nullable();
            $table->string('content_shoulder', 100)->nullable();
            $table->text('brief')->nullable();

            $table->longText('body')->nullable();


            $table->string('writer', 255)->nullable();

            $table->string('seo_title', 255)->nullable();
            $table->text('seo_brief')->nullable();
            $table->text('seo_keywords')->nullable();

            $table->string('source', 255)->nullable();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();

            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
