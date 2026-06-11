<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('title', 255);

            $table->text('brief')->nullable();
            $table->longText('body')->nullable();

            $table->string('seo_title', 255)->nullable();
            $table->text('seo_brief')->nullable();
            $table->text('seo_keywords')->nullable();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug',255)->unique();

            $table->text('slug_tree');
            $table->text('title_tree');

            $table->string('default_use_as')->nullable()->default(null);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['language_id','default_use_as']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
