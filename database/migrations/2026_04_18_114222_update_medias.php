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

            $table->nullableMorphs('model');

            $table->string('file_name')->nullable()->change();
            $table->unsignedBigInteger('size')->nullable()->change();

            $table->string('slug');
            $table->enum('media_type', ['Url', 'Upload'])->default('Upload');
            $table->string('url')->nullable()->default(null);
            $table->unsignedBigInteger('created_by_id')->nullable()->default(null);

            $table->foreign('created_by_id', 'media_created_by_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign('media_created_by_fk');

            $table->dropColumn(['slug', 'media_type', 'url', 'created_by_id']);

            $table->dropMorphs('model');
            $table->morphs('model');
            $table->string('file_name')->nullable(false)->change();
            $table->unsignedBigInteger('size')->nullable(false)->change();
        });
    }
};
