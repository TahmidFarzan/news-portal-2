<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropUnique(['group', 'label']);
            $table->dropIndex(['group']);

            $table->dropColumn([
                'group',
                'label',
                'type',
                'value',
            ]);

            $table->string('name', 255)->unique()->after('id');
            $table->json('options')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn([
                'name',
                'options',
            ]);

            $table->string('group', 255)->after('id');
            $table->string('label', 255)->after('group');
            $table->string('type', 255)->after('label');
            $table->text('value')->nullable()->after('type');

            $table->unique(['group', 'label']);
            $table->index('group');
        });
    }
};
