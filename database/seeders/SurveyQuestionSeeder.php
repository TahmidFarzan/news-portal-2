<?php

namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurveyQuestionSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            SurveyQuestion::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='survey_questions'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            SurveyQuestion::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            SurveyQuestion::truncate();
        }


        $languages = Language::all()->keyBy('code');


        $surveyQuestionsByLanguageGroupsFromStaticData = $this->getSurveyQuestionsByLanguageGroupsFromStaticData();


        foreach ($surveyQuestionsByLanguageGroupsFromStaticData as $questionGroup) {

            $language = $languages[$questionGroup->language_code] ?? null;

            if (!$language) {
                continue;
            }


            $surveys = Survey::where('language_id', $language->id)
                ->orderBy('id')
                ->get();


            foreach ($surveys as $index => $survey) {

                if (!isset($questionGroup->questions[$index])) {
                    continue;
                }

                foreach ($questionGroup->questions[$index] as $question) {

                    SurveyQuestion::factory()
                        ->state([
                            'survey_id' => $survey->id,
                            'question' => $question,
                        ])
                        ->create();
                }
            }
        }
    }


    private function getSurveyQuestionsByLanguageGroupsFromStaticData()
    {
        return collect([

            (object) [
                'language_code' => SeederHelper::LANGUAGE_EN_CODE,

                'questions' => [

                    [
                        'Do today’s headlines reflect public concerns?',
                        'Was today’s news coverage balanced?',
                        'Should more viewpoints be included?',
                    ],

                    [
                        'Was the breaking news coverage timely?',
                        'Did the report provide enough details?',
                        'Would you follow updates on this story?',
                    ],

                    [
                        'How satisfied are you with this week’s news?',
                        'Did news coverage feel objective?',
                        'Should weekly summaries continue?',
                    ],

                    [
                        'Do current affairs reports feel balanced?',
                        'Should more analysis be added?',
                        'Are these topics important to you?',
                    ],

                    [
                        'Are national issues covered fairly?',
                        'Should more expert opinions be included?',
                        'Do you follow national news regularly?',
                    ],

                    [
                        'Do you feel your opinion matters?',
                        'Should readers influence content selection?',
                        'Would you participate again?',
                    ],

                    [
                        'Are trending topics worth covering?',
                        'Should trending content appear more often?',
                        'Do trends influence your reading?',
                    ],

                    [
                        'Do editorials provide value?',
                        'Should editorials include more evidence?',
                        'Do you read editorial sections?',
                    ],

                    [
                        'Does news influence society positively?',
                        'Should impact stories increase?',
                        'Are social issues covered enough?',
                    ],

                    [
                        'Should political coverage expand?',
                        'Are social discussions balanced?',
                        'Do political stories affect you?',
                    ],

                    [
                        'Does this content match public interest?',
                        'Should public-interest stories increase?',
                        'Would you share such articles?',
                    ],

                    [
                        'Were today’s headlines useful?',
                        'Should headline summaries improve?',
                        'Did headlines attract your attention?',
                    ],

                    [
                        'Should citizen opinions appear more?',
                        'Do public voices matter?',
                        'Would you contribute opinions?',
                    ],

                    [
                        'How often do you read news?',
                        'Do you prefer digital news?',
                        'Should more formats be offered?',
                    ],

                    [
                        'Should community issues receive attention?',
                        'Do local stories matter more?',
                        'Would you participate in future polls?',
                    ],

                ],
            ],


            (object) [
                'language_code' => SeederHelper::LANGUAGE_BN_CODE,

                'questions' => [

                    [
                        'আজকের শিরোনাম কি জনমত প্রতিফলিত করে?',
                        'আজকের সংবাদ কভারেজ কি ভারসাম্যপূর্ণ ছিল?',
                        'আরও মতামত যুক্ত করা উচিত?',
                    ],

                    [
                        'ব্রেকিং নিউজ কি সময়মতো প্রকাশ হয়েছে?',
                        'প্রতিবেদনটি কি যথেষ্ট তথ্য দিয়েছে?',
                        'আপনি কি এই সংবাদের আপডেট অনুসরণ করবেন?',
                    ],

                    [
                        'এই সপ্তাহের সংবাদে আপনি কতটা সন্তুষ্ট?',
                        'সংবাদ কি নিরপেক্ষ মনে হয়েছে?',
                        'সাপ্তাহিক সারসংক্ষেপ চালু রাখা উচিত?',
                    ],

                    [
                        'চলমান বিষয়ের সংবাদ কি ভারসাম্যপূর্ণ?',
                        'আরও বিশ্লেষণ যুক্ত করা উচিত?',
                        'এই বিষয়গুলো কি আপনার কাছে গুরুত্বপূর্ণ?',
                    ],

                    [
                        'জাতীয় বিষয়গুলো কি সঠিকভাবে উপস্থাপিত হয়?',
                        'আরও বিশেষজ্ঞ মতামত যুক্ত করা উচিত?',
                        'আপনি কি নিয়মিত জাতীয় সংবাদ অনুসরণ করেন?',
                    ],

                    [
                        'আপনার কি মনে হয় আপনার মতামত গুরুত্বপূর্ণ?',
                        'পাঠকদের মতামত কি বিষয় নির্বাচন প্রভাবিত করবে?',
                        'আপনি কি আবার অংশগ্রহণ করবেন?',
                    ],

                    [
                        'ট্রেন্ডিং বিষয়গুলো কি কাভার করা উচিত?',
                        'এ ধরনের সংবাদ কি আরও প্রকাশ করা উচিত?',
                        'ট্রেন্ড কি আপনার পড়ায় প্রভাব ফেলে?',
                    ],

                    [
                        'সম্পাদকীয় কি উপকারী?',
                        'আরও তথ্যভিত্তিক হওয়া উচিত?',
                        'আপনি কি সম্পাদকীয় পড়েন?',
                    ],

                    [
                        'সংবাদ কি সমাজে ইতিবাচক প্রভাব ফেলে?',
                        'এ ধরনের প্রতিবেদন বাড়ানো উচিত?',
                        'সামাজিক বিষয়গুলো কি যথেষ্ট কাভার হয়?',
                    ],

                    [
                        'রাজনৈতিক কভারেজ বাড়ানো উচিত?',
                        'সামাজিক আলোচনা কি ভারসাম্যপূর্ণ?',
                        'রাজনৈতিক সংবাদ কি আপনাকে প্রভাবিত করে?',
                    ],

                    [
                        'এই সংবাদ কি জনস্বার্থে উপকারী?',
                        'এ ধরনের প্রতিবেদন বাড়ানো উচিত?',
                        'আপনি কি এই ধরনের সংবাদ শেয়ার করবেন?',
                    ],

                    [
                        'আজকের শিরোনাম কি উপকারী ছিল?',
                        'শিরোনাম আরও উন্নত করা উচিত?',
                        'শিরোনাম কি আপনার দৃষ্টি আকর্ষণ করেছে?',
                    ],

                    [
                        'নাগরিক মতামত কি আরও প্রকাশ করা উচিত?',
                        'জনমত কি গুরুত্বপূর্ণ?',
                        'আপনি কি মতামত দেবেন?',
                    ],

                    [
                        'আপনি কত ঘন ঘন সংবাদ পড়েন?',
                        'আপনি কি অনলাইন সংবাদ পছন্দ করেন?',
                        'আরও ফরম্যাট যুক্ত করা উচিত?',
                    ],

                    [
                        'কমিউনিটির বিষয়গুলো কি গুরুত্ব পাওয়া উচিত?',
                        'স্থানীয় সংবাদ কি বেশি গুরুত্বপূর্ণ?',
                        'আপনি কি ভবিষ্যতে অংশগ্রহণ করবেন?',
                    ],

                ],
            ],

        ]);
    }
}
