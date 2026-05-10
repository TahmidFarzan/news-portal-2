<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_tag', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->foreignId('news_id')->constrained('newses')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['tag_id', 'news_id'], 'news_tag_pk');
        });

        Schema::create('contributor_news', function (Blueprint $table) {
            $table->foreignId('contributor_id')->constrained('contributors')->onDelete('cascade');
            $table->foreignId('news_id')->constrained('newses')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['contributor_id', 'news_id'], 'contributor_news_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_news');
        Schema::dropIfExists('news_tag');
        Schema::dropIfExists('contributor_news');
    }
};
