<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->cascadeOnDelete();

            $table->foreignId('quiz_participant_id')
                ->constrained('quiz_participants')
                ->cascadeOnDelete();

            $table->decimal('duration', 10, 2)->default(0);

            $table->decimal('total_point', 10, 2)->default(0);

            $table->string('slug')->unique();

            $table->timestamps();

            $table->index(['quiz_id', 'quiz_participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
