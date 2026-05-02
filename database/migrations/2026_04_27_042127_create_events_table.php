<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('brief')->nullable();
            $table->string('banner_position')->nullable();
            $table->boolean('is_current')->default(false);
            $table->string('slug')->unique();

            $table->string('seo_title', 255)->nullable();
            $table->text('seo_brief')->nullable();
            $table->text('seo_keywords')->nullable();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
