<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 255);

            $table->text('brief')->nullable();

            $table->date('date')->useCurrent();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug', 255)->unique();

            $table->boolean('is_active')->default(false);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
