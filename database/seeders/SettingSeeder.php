<?php
namespace Database\Seeders;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Setting::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='settings'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Setting::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Setting::truncate();
        }

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_APP,
            'label' => SettingHelper::OPTION_SHOW_NAME_ON_HEADER_MENU,
            'type'  => SettingHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_APP,
            'label' => SettingHelper::OPTION_SHOW_LOGO_ON_HEADER_MENU,
            'type'  => SettingHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();


        Setting::factory()->state([
            'group' => SettingHelper::GROUP_APP,
            'label' => SettingHelper::OPTION_SHOW_BREAKING_NEWS,
            'type'  => SettingHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_MENU,
            'label' => SettingHelper::OPTION_SHOW_TOPBAR_MENU,
            'type'  => SettingHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_MENU,
            'label' => SettingHelper::OPTION_SHOW_FOOTER_MENU,
            'type'  => SettingHelper::VALUE_TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_SOCIAL_LINK,
            'label' => SettingHelper::OPTION_FB_SOCIAL_LINK,
            'type'  => SettingHelper::VALUE_TYPE_URL,
            'value' => "https://facebook.com",
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_SOCIAL_LINK,
            'label' => SettingHelper::OPTION_YOUTUBE_SOCIAL_LINK,
            'type'  => SettingHelper::VALUE_TYPE_URL,
            'value' => "https://youtube.com",
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_SOCIAL_LINK,
            'label' => SettingHelper::OPTION_GOOGLE_NEWS_SOCIAL_LINK,
            'type'  => SettingHelper::VALUE_TYPE_URL,
            'value' => "https://news.google.com",
        ])->create();

    }
}
