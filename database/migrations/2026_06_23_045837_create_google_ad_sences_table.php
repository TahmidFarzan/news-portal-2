<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ad_sences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slot_id');
            $table->string('client_id');
            $table->string('type');
            $table->string('position');
            $table->boolean('use_full_width_responsive')->default(true);
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug',255)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ad_sences');
    }
};
