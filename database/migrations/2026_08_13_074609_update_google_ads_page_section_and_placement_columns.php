<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_ads', function (Blueprint $table) {
            $table->string('page')->nullable()->after('type');
            $table->string('placement')->nullable()->after('page');

            $table->dropColumn('position');
        });
    }

    public function down(): void
    {
        Schema::table('google_ads', function (Blueprint $table) {
            $table->string('position')->nullable()->after('type');

            $table->dropColumn([
                'page',
                'placement',
            ]);
        });
    }
};
