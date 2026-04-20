<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropMorphs('model');

            $table->string('slug')->unique()->nullable();
            $table->nullableMorphs('model');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {

            $table->dropColumn([ 'created_by_id']);

            $table->dropMorphs('model');
            $table->morphs('model');
        });
    }
};
