<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('position');
            $table->date('end_date')->nullable()->after('start_date');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('is_current', 'is_active');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('is_active', 'is_current');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'end_date',
            ]);
        });
    }
};