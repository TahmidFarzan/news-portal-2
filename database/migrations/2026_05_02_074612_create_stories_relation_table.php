<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_tag', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->foreignId('story_id')->constrained('stories')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['tag_id', 'story_id'], 'story_tag_pk');
        });

        Schema::create('contributor_story', function (Blueprint $table) {
            $table->foreignId('contributor_id')->constrained('contributors')->onDelete('cascade');
            $table->foreignId('story_id')->constrained('stories')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['contributor_id', 'story_id'], 'contributor_story_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_story');
        Schema::dropIfExists('story_tag');
        Schema::dropIfExists('contributor_story');
    }
};
