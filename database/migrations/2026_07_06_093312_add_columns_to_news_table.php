<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {

            if (Schema::hasColumn('news', 'source')) {
                $table->unsignedBigInteger('hit_count')->default(0)->after('source');
            } else {
                $table->unsignedBigInteger('hit_count') ->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn([
                'hit_count',
            ]);
        });
    }
};
