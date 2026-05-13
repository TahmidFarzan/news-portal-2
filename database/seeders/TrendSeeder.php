<?php
namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Trend;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrendSeeder extends Seeder
{
    public function run(): void
    {
        // Trancate
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Trend::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='trends'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Trend::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Trend::truncate();
        }

        // Seeder
        $tags = Tag::orderBy('id', 'desc')->limit(15)->get();
        foreach ($tags as $tag) {
            Trend::factory()->state([
                'tag_id'        => $tag->id,
                'is_current'    => true,
            ])->create();
        }
    }
}
