<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('enable_result')
                ->default(false)
                ->after('show_bellow_event');

            $table->integer('max_winner')
                ->default(1)
                ->after('enable_result');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'enable_result',
                'max_winner',
            ]);
        });
    }
};
