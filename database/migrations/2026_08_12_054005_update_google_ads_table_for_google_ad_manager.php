<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_ads', function (Blueprint $table) {
            $table->dropColumn([
                'client_id',
                'use_full_width_responsive',
            ]);

            $table->renameColumn('slot_id', 'gpt_slot_id');

            $table->string('ad_unit_code')->after('name');
            $table->json('ad_sizes')->nullable()->after('gpt_slot_id');

            $table->string('position')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('google_ads', function (Blueprint $table) {
            $table->dropColumn([
                'ad_unit_code',
                'ad_sizes',
            ]);

            $table->renameColumn('gpt_slot_id', 'slot_id');

            $table->string('client_id')->after('slot_id');
            $table->boolean('use_full_width_responsive')
                ->default(true)
                ->after('position');

            $table->string('position')->nullable(false)->change();
        });
    }
};
