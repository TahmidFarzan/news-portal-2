<?php
namespace Database\Seeders;

use App\Helpers\PageHelper;
use App\Helpers\SystemHelper;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Page::query()->delete();
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Page::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Page::truncate();
        }

        $languages = Language::query()->get()->keyBy('code');

        $pagesByLanguageGroupsFromStaticData = $this->getPagesByLanguageGroupsFromStaticData();
        foreach ($pagesByLanguageGroupsFromStaticData as $pagesByLanguageGroup) {
            $language = $languages[$pagesByLanguageGroup->language_code] ?? null;

            foreach ($pagesByLanguageGroup->pages as $page) {
                $isDefault    = false;
                $defaultUseAs = null;

                if (($page->title == "Home") || ($page->title == "হোম")) {
                    $isDefault    = true;
                    $defaultUseAs = PageHelper::DAFAULT_USE_AS_HOME;
                }

                if (($page->title == "Latest") || ($page->title == "সর্বশেষ")) {
                    $isDefault    = true;
                    $defaultUseAs = PageHelper::DAFAULT_USE_AS_LATEST;
                }

                if (($page->title == "Search") || ($page->title == "অনুসন্ধান")) {
                    $isDefault    = true;
                    $defaultUseAs = PageHelper::DAFAULT_USE_AS_SEARCH;
                }

                Page::factory()->state([
                    'title'          => $page->title,
                    'brief'          => $page->brief ?? null,

                    'language_id'    => $language?->id,
                    'seo_title'      => $page->title,
                    'seo_brief'      => $page->brief ?? null,
                    'seo_keywords'   => $page->seo_keywords ?? null,

                    'is_default'     => $isDefault,
                    'default_use_as' => $defaultUseAs ?? null,

                    'parent_id'      => null,

                    'title_tree'     => $page->title,
                    'slug_tree'      => Str::slug($page->title),

                    'is_published'   => true,
                ])->create();
            }
        }

    }

    private function getPagesByLanguageGroupsFromStaticData()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::LANGUAGE_DEFAULT_CODE,
                'pages'         => collect([
                    (object) [
                        'title'        => 'Home',
                        'brief'        => 'Welcome to our news portal with the latest updates, featured stories, and important headlines.',
                        'seo_keywords' => 'home, news portal, latest news, breaking news, top headlines',
                    ],
                    (object) [
                        'title'        => 'Latest',
                        'brief'        => 'Read the most recent news updates, breaking stories, and current events from trusted sources.',
                        'seo_keywords' => 'latest news, recent updates, breaking news, current news, live news',
                    ],
                    (object) [
                        'title'        => 'Search',
                        'brief'        => 'Search and find news, articles, topics, categories, locations, and events quickly.',
                        'seo_keywords' => 'search news, find news, news search, article search, topic search',
                    ],
                    (object) [
                        'title'        => 'Contact',
                        'brief'        => 'Get in touch with us for inquiries, feedback, support, news tips, or business communication.',
                        'seo_keywords' => 'contact us, support, feedback, news tips, communication',
                    ],
                    (object) [
                        'title'        => 'About',
                        'brief'        => 'Learn more about our news platform, mission, values, editorial focus, and commitment to reliable journalism.',
                        'seo_keywords' => 'about us, news platform, our mission, journalism, media organization',
                    ],
                    (object) [
                        'title'        => 'Public Policy',
                        'brief'        => 'Read our public policy information, community standards, content guidelines, and platform responsibilities.',
                        'seo_keywords' => 'public policy, content policy, community guidelines, platform policy, user safety',
                    ],
                    (object) [
                        'title'        => 'Terms and Conditions',
                        'brief'        => 'Review the terms, conditions, rules, and responsibilities for using our website and services.',
                        'seo_keywords' => 'terms and conditions, website terms, user agreement, service terms, legal policy',
                    ],
                ]),
            ],

            (object) [
                'language_code' => SystemHelper::LANGUAGE_EXTRA_BN_CODE,
                'pages'         => collect([
                    (object) [
                        'title'        => 'হোম',
                        'brief'        => 'সর্বশেষ খবর, গুরুত্বপূর্ণ সংবাদ, আলোচিত প্রতিবেদন এবং প্রধান শিরোনাম একসাথে দেখুন।',
                        'seo_keywords' => 'হোম, সংবাদ পোর্টাল, সর্বশেষ খবর, ব্রেকিং নিউজ, প্রধান খবর',
                    ],
                    (object) [
                        'title'        => 'সর্বশেষ',
                        'brief'        => 'সাম্প্রতিক সংবাদ, চলমান ঘটনা, ব্রেকিং নিউজ এবং নতুন প্রকাশিত প্রতিবেদন পড়ুন।',
                        'seo_keywords' => 'সর্বশেষ খবর, সাম্প্রতিক সংবাদ, ব্রেকিং নিউজ, নতুন খবর, চলমান ঘটনা',
                    ],
                    (object) [
                        'title'        => 'অনুসন্ধান',
                        'brief'        => 'খবর, নিবন্ধ, বিষয়, বিভাগ, স্থান এবং ইভেন্ট দ্রুত খুঁজে বের করুন।',
                        'seo_keywords' => 'সংবাদ অনুসন্ধান, খবর খুঁজুন, নিবন্ধ অনুসন্ধান, বিষয় অনুসন্ধান, সার্চ নিউজ',
                    ],
                    (object) [
                        'title'        => 'যোগাযোগ',
                        'brief'        => 'জিজ্ঞাসা, মতামত, সহায়তা, সংবাদ তথ্য বা ব্যবসায়িক যোগাযোগের জন্য আমাদের সাথে যোগাযোগ করুন।',
                        'seo_keywords' => 'যোগাযোগ, আমাদের সাথে যোগাযোগ, সহায়তা, মতামত, সংবাদ তথ্য',
                    ],
                    (object) [
                        'title'        => 'আমাদের সম্পর্কে',
                        'brief'        => 'আমাদের সংবাদ প্ল্যাটফর্ম, লক্ষ্য, মূল্যবোধ, সম্পাদকীয় নীতি এবং নির্ভরযোগ্য সাংবাদিকতা সম্পর্কে জানুন।',
                        'seo_keywords' => 'আমাদের সম্পর্কে, সংবাদ প্ল্যাটফর্ম, আমাদের লক্ষ্য, সাংবাদিকতা, মিডিয়া প্রতিষ্ঠান',
                    ],
                    (object) [
                        'title'        => 'গোপনীয়তা নীতি',
                        'brief'        => 'আপনার তথ্য সংগ্রহ, ব্যবহার, সংরক্ষণ এবং সুরক্ষা সম্পর্কিত আমাদের গোপনীয়তা নীতি পড়ুন।',
                        'seo_keywords' => 'গোপনীয়তা নীতি, প্রাইভেসি পলিসি, ব্যক্তিগত তথ্য, তথ্য সুরক্ষা, ডেটা নীতি',
                    ],
                    (object) [
                        'title'        => 'শর্তাবলী',
                        'brief'        => 'আমাদের ওয়েবসাইট ও সেবা ব্যবহারের নিয়ম, শর্ত, দায়িত্ব এবং নীতিমালা পড়ুন।',
                        'seo_keywords' => 'শর্তাবলী, ব্যবহারের শর্ত, ওয়েবসাইট নীতি, ব্যবহারকারী চুক্তি, আইনি নীতি',
                    ],
                ]),
            ],
        ]);
    }
}
