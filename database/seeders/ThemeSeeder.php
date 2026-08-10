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

        $themes = [
            [
                'name' => ThemeHelper::NAME_HEADER_MENU,
                'options' => [
                    ThemeHelper::OPTION_SHOW_LOGO_ON_HEADER_MENU => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                    ThemeHelper::OPTION_SHOW_NAME_ON_HEADER_MENU => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                ],
            ],

            [
                'name' =>ThemeHelper::NAME_GOOGLE_AD,
                'options' => [
                    ThemeHelper::OPTION_GOOGLE_AD_ENABLE => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                    ThemeHelper::OPTION_GOOGLE_ADSENSE_CLIENT_ID => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_STRING,
                        'value' => 'ca-pub-3940256099942544',
                    ],
                ],
            ],

            [
                'name' =>  ThemeHelper::NAME_SITE_EXTRA_FEATURE,
                'options' => [
                    ThemeHelper::OPTION_SHOW_BREAKING_NEWS => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                    ThemeHelper::OPTION_SHOW_TRENDS => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                    ThemeHelper::OPTION_SHOW_SURVEYS => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                    ThemeHelper::OPTION_SHOW_QUIZZES => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                ],
            ],

            [
                'name' => ThemeHelper::NAME_TOPBAR_FOOTER_MENU,
                'options' => [
                    ThemeHelper::OPTION_SHOW_TOPBAR_MENU => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                    ThemeHelper::OPTION_SHOW_FOOTER_MENU => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
                        'value' => true,
                    ],
                ],
            ],

            [
                'name' => ThemeHelper::NAME_SOCIAL_LINK,
                'options' => [
                    ThemeHelper::OPTION_FB_SOCIAL_LINK => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_URL,
                        'value' => 'https://facebook.com',
                    ],
                    ThemeHelper::OPTION_YOUTUBE_SOCIAL_LINK => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_URL,
                        'value' => 'https://youtube.com',
                    ],
                    ThemeHelper::OPTION_GOOGLE_NEWS_SOCIAL_LINK => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_URL,
                        'value' => 'https://news.google.com',
                    ],
                ],
            ],

            [
                'name' => ThemeHelper::NAME_GOOGLE_SEO_SERVICE,
                'options' => [
                    ThemeHelper::OPTION_GOOGLE_SEARCH_CONSOLE_HEADER => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_TEXT,
                        'value' => null,
                    ],
                    ThemeHelper::OPTION_GOOGLE_ANALYTIC_HEADER => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_TEXT,
                        'value' => null,
                    ],
                    ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_HEADER => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_TEXT,
                        'value' => null,
                    ],
                    ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_BODY => [
                        'valueType' => ThemeHelper::OPTION_VALUE_TYPE_TEXT,
                        'value' => null,
                    ],
                ],
            ],
        ];

        foreach ($themes as $theme) {
            Theme::factory()->create($theme);
        }
    }
}
