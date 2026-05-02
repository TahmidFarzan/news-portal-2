<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->text('brief')->nullable();
            $table->longText('profile_details')->nullable();

            $table->string('seo_title', 255)->nullable();
            $table->text('seo_brief')->nullable();
            $table->text('seo_keywords')->nullable();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributors');
    }
};
