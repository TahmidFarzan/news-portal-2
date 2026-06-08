<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->json('boundary_geojson')->nullable()->after('longitude');

            $table->decimal('boundary_north', 10, 7)->nullable()->after('boundary_geojson');
            $table->decimal('boundary_south', 10, 7)->nullable()->after('boundary_north');
            $table->decimal('boundary_east', 10, 7)->nullable()->after('boundary_south');
            $table->decimal('boundary_west', 10, 7)->nullable()->after('boundary_east');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'boundary_geojson',
                'boundary_north',
                'boundary_south',
                'boundary_east',
                'boundary_west',
            ]);
        });
    }
};
