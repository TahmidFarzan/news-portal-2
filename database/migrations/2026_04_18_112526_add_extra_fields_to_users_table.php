<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->unique();
            $table->boolean('is_default')->default(false)->after('remember_token');
            $table->foreignId('user_role_id')->constrained('user_roles')->cascadeOnDelete();
            $table->date('birth_date')->nullable()->after('is_admin');
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Separated', 'Other'])->nullable()->after('birth_date');
            $table->enum('religion', ['Islam', 'Hindu', 'Christian', 'Other'])->nullable()->after('marital_status');
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('religion');

            $table->string('mobile', 20)->nullable()->after('religion');
            $table->text('address')->nullable()->after('religion');

            $table->foreignId('supervisor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->cascadeOnDelete();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_default',
                'is_supervisor',
                'is_admin',

                'marital_status',
                'religion',
                'gender',
                'mobile',
            ]);
            $table->dropForeign(['created_by_id']);
            $table->dropColumn('created_by_id');
        });
    }
};
