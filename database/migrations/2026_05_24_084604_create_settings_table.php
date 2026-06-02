<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 255);
            $table->string('label', 255);
            $table->string('type', 255);
            $table->text('value')->nullable();
            $table->string('slug', 255)->unique();

            $table->unique(['group', 'label']);
            $table->index('group');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
