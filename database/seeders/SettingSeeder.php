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
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_YOUTUBE_SOCIAL_LINK)),
            'label' => SettingHelper::FIELD_YOUTUBE_SOCIAL_LINK,
            'type'  => SettingHelper::TYPE_URL,
            'value' => "",
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_APP,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_SHOW_LOGO_ON_HEADER_MENU)),
            'label' => SettingHelper::FIELD_SHOW_LOGO_ON_HEADER_MENU,
            'type'  => SettingHelper::TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_MENU,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_SHOW_HEADER_MENU)),
            'label' => SettingHelper::FIELD_SHOW_HEADER_MENU,
            'type'  => SettingHelper::TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_MENU,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_SHOW_TOPBAR_MENU)),
            'label' => SettingHelper::FIELD_SHOW_TOPBAR_MENU,
            'type'  => SettingHelper::TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_MENU,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_SHOW_FOOTER_MENU)),
            'label' => SettingHelper::FIELD_SHOW_FOOTER_MENU,
            'type'  => SettingHelper::TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_MENU,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_SHOW_OFFCANVAS_MENU)),
            'label' => SettingHelper::FIELD_SHOW_OFFCANVAS_MENU,
            'type'  => SettingHelper::TYPE_BOOLEAN,
            'value' => true,
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_SOCIAL_LINK,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_FB_SOCIAL_LINK)),
            'label' => SettingHelper::FIELD_FB_SOCIAL_LINK,
            'type'  => SettingHelper::TYPE_URL,
            'value' => "",
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_SOCIAL_LINK,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_INSTAGRAM_SOCIAL_LINK)),
            'label' => SettingHelper::FIELD_INSTAGRAM_SOCIAL_LINK,
            'type'  => SettingHelper::TYPE_URL,
            'value' => "",
        ])->create();

        Setting::factory()->state([
            'group' => SettingHelper::GROUP_SOCIAL_LINK,
            'key'   => Str::lower(Str::snake(SettingHelper::FIELD_SHOW_NAME_ON_HEADER_MENU)),
            'label' => SettingHelper::FIELD_SHOW_NAME_ON_HEADER_MENU,
            'type'  => SettingHelper::TYPE_BOOLEAN,
            'value' => true,
        ])->create();
    }
}
