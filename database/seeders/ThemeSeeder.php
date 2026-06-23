<?php
namespace Database\Seeders;

use App\Helpers\ThemeHelper;
use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Theme::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='themes'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Theme::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Theme::truncate();
        }

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_SHOW_NAME_ON_HEADER_MENU,
            'type'  => ThemeHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_SHOW_LOGO_ON_HEADER_MENU,
            'type'  => ThemeHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_SHOW_BREAKING_NEWS,
            'type'  => ThemeHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_MENU,
            'label' => ThemeHelper::OPTION_SHOW_TOPBAR_MENU,
            'type'  => ThemeHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_MENU,
            'label' => ThemeHelper::OPTION_SHOW_FOOTER_MENU,
            'type'  => ThemeHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_SHOW_GOOGLE_AD,
            'type'  => ThemeHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_SOCIAL_LINK,
            'label' => ThemeHelper::OPTION_FB_SOCIAL_LINK,
            'type'  => ThemeHelper::VALUE_TYPE_URL,
            'value' => "https://facebook.com",
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_SOCIAL_LINK,
            'label' => ThemeHelper::OPTION_YOUTUBE_SOCIAL_LINK,
            'type'  => ThemeHelper::VALUE_TYPE_URL,
            'value' => "https://youtube.com",
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_SOCIAL_LINK,
            'label' => ThemeHelper::OPTION_GOOGLE_NEWS_SOCIAL_LINK,
            'type'  => ThemeHelper::VALUE_TYPE_URL,
            'value' => "https://news.google.com",
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_GOOGLE_SEARCH_CONSOLE_HEADER,
            'type'  => ThemeHelper::VALUE_TYPE_TEXT,
            'value' => null,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_GOOGLE_ANALYTIC_HEADER,
            'type'  => ThemeHelper::VALUE_TYPE_TEXT,
            'value' => null,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_HEADER,
            'type'  => ThemeHelper::VALUE_TYPE_TEXT,
            'value' => null,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_BODY,
            'type'  => ThemeHelper::VALUE_TYPE_TEXT,
            'value' => null,
        ])->create();

        Theme::factory()->state([
            'group' => ThemeHelper::GROUP_APP,
            'label' => ThemeHelper::OPTION_GOOGLE_ADSENCE_CLIENT_ID,
            'type'  => ThemeHelper::VALUE_TYPE_TEXT,
            'value' => "ca-pub-3940256099942544",
        ])->create();

    }
}
