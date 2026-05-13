<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('newses')->cascadeOnDelete();
            $table->string('page')->default("Home");
            $table->string('page_section')->nullable()->default("Lead");
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->timestamps();

            $table->index(['page', 'page_section', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_placements');
    }
};
