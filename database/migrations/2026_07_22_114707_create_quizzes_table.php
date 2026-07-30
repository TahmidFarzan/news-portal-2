<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('brief')->nullable();

            $table->date('start_date')->default(DB::raw('CURRENT_DATE'));
            $table->date('end_date')->default(DB::raw('CURRENT_DATE'));

            $table->boolean('is_active')->default(false);
            $table->boolean('show_bellow_event')->default(false);

            $table->string('slug')->unique();

            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
