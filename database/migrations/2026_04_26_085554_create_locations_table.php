<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();

            $table->string('name', 255);
            $table->foreignId('parent_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->text('details')->nullable();
            $table->string('slug')->unique();

            $table->text('slug_tree');
            $table->text('name_tree');

            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();

            $table->string('seo_title', 255)->nullable();
            $table->text('seo_brief')->nullable();
            $table->text('seo_keywords')->nullable();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
