<?php

namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Survey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            Survey::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='surveys'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            Survey::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Survey::truncate();
        }


        $languages = Language::all()->keyBy('code');


        $surveysByLanguageGroupsFromStaticData = $this->getSurveysByLanguageGroupsFromStaticData();


        $durations = [
            30,
            15,
            30,
            15,
            30,
            15,
            30,
            15,
            30,
            15,
            30,
            15,
            30,
            15,
            30,
        ];

        foreach ($surveysByLanguageGroupsFromStaticData as $surveyGroup) {

            $language = $languages[$surveyGroup->language_code] ?? null;


            if (!$language) {
                continue;
            }


            $startDate = now()->startOfDay();


            foreach ($surveyGroup->surveys as $index => $survey) {


                $endDate = $startDate
                    ->copy()
                    ->addDays($durations[$index] - 1);


                Survey::factory()
                    ->state([
                        'language_id' => $language->id,

                        'name' => $survey->name,

                        'start_date' => $startDate,

                        'end_date' => $endDate,

                        'is_active' => true,
                    ])
                    ->create();


                $startDate = $endDate
                    ->copy()
                    ->addDay();
            }
        }
    }


    private function getSurveysByLanguageGroupsFromStaticData()
    {
        return collect([

            (object) [
                'language_code' => SeederHelper::LANGUAGE_EN_CODE,

                'surveys' => collect([

                    (object) [
                        'name' => 'Public Opinion on Daily Headlines',
                    ],

                    (object) [
                        'name' => 'Reader Reaction to Breaking News',
                    ],

                    (object) [
                        'name' => 'Weekly News Sentiment Survey',
                    ],

                    (object) [
                        'name' => 'Current Affairs Public Feedback',
                    ],

                    (object) [
                        'name' => 'National Issues Opinion Poll',
                    ],

                    (object) [
                        'name' => 'Reader Voice Survey',
                    ],

                    (object) [
                        'name' => 'Trending Topics Public Survey',
                    ],

                    (object) [
                        'name' => 'Editorial Feedback Survey',
                    ],

                    (object) [
                        'name' => 'Social Impact Opinion Survey',
                    ],

                    (object) [
                        'name' => 'Politics and Society Poll',
                    ],

                    (object) [
                        'name' => 'Public Interest News Survey',
                    ],

                    (object) [
                        'name' => 'Today’s Headlines Feedback',
                    ],

                    (object) [
                        'name' => 'Citizen Perspective Survey',
                    ],

                    (object) [
                        'name' => 'News Consumption Survey',
                    ],

                    (object) [
                        'name' => 'Community Opinion Poll',
                    ],

                ]),
            ],


            (object) [
                'language_code' => SeederHelper::LANGUAGE_BN_CODE,

                'surveys' => collect([

                    (object) [
                        'name' => 'দৈনিক শিরোনাম সম্পর্কে জনমত',
                    ],

                    (object) [
                        'name' => 'ব্রেকিং নিউজ সম্পর্কে পাঠকের প্রতিক্রিয়া',
                    ],

                    (object) [
                        'name' => 'সাপ্তাহিক সংবাদ মতামত জরিপ',
                    ],

                    (object) [
                        'name' => 'চলমান বিষয় সম্পর্কে জনমত',
                    ],

                    (object) [
                        'name' => 'জাতীয় বিষয় মতামত জরিপ',
                    ],

                    (object) [
                        'name' => 'পাঠকের মতামত জরিপ',
                    ],

                    (object) [
                        'name' => 'ট্রেন্ডিং বিষয় জনমত জরিপ',
                    ],

                    (object) [
                        'name' => 'সম্পাদকীয় মতামত জরিপ',
                    ],

                    (object) [
                        'name' => 'সামাজিক প্রভাব মতামত জরিপ',
                    ],

                    (object) [
                        'name' => 'রাজনীতি ও সমাজ জরিপ',
                    ],

                    (object) [
                        'name' => 'জনস্বার্থ সংবাদ জরিপ',
                    ],

                    (object) [
                        'name' => 'আজকের শিরোনাম মতামত',
                    ],

                    (object) [
                        'name' => 'নাগরিক মতামত জরিপ',
                    ],

                    (object) [
                        'name' => 'সংবাদ গ্রহণ জরিপ',
                    ],

                    (object) [
                        'name' => 'কমিউনিটি মতামত জরিপ',
                    ],

                ]),
            ],

        ]);
    }
}
