<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->date('start_date')->useCurrent()->change();
            $table->date('end_date')->useCurrent()->change();
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->date('start_date')
                ->default(DB::raw('CURRENT_DATE'))
                ->change();

            $table->date('end_date')
                ->default(DB::raw('CURRENT_DATE'))
                ->change();
        });
    }
};
