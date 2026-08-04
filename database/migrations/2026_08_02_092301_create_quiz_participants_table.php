<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_participants', function (Blueprint $table) {
            $table->id();

            $table->string('name', 255);

            $table->string('phone', 30)->nullable();
            $table->string('email', 255)->nullable();

            $table->text('address')->nullable();

            $table->ipAddress('ip');
            $table->json('device_info')->nullable();

            $table->string('slug')->unique();

            $table->timestamps();

            $table->index('phone');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_participants');
    }
};
