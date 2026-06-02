<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breaking_newses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug')->unique();


            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('news_id')->nullable()->constrained('newses')->onDelete('cascade');
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();

            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breaking_newses');
    }
};
