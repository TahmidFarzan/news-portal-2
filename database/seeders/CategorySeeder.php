<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Category::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='categories'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Category::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Category::truncate();
        }
        $languages = Language::all()->keyBy('code');

        $categoriesByLanguageGroups = $this->categoriesByLanguageGroups();
        foreach ($categoriesByLanguageGroups as $categoriesByLanguageGroup) {
            $language = $languages[$categoriesByLanguageGroup->language_code] ?? null;
            foreach ($categoriesByLanguageGroup->categories as $category) {
                $this->createCategory(null, $category, $language);
            }
        }
    }

    public function createCategory(?Category $parent, $category, ?Language $language): Category
    {
        $saveCategory = Category::factory()->state([
            'name'         => $category->name,
            'brief'      => $category->brief ?? null,
            'parent_id'    => $parent?->id,
            'language_id'  => $language?->id,
            'name_tree'    => ($parent ? $parent->name . ' - ' : '') . $category->name,
            'slug_tree'    => ($parent ? $parent->slug . '/' : '') . Str::slug($category->name),
            'seo_title'    => $category->name,
            'seo_brief'    => $category->brief ?? null,
            'seo_keywords' => $category->seo_keywords ?? null,
        ])->create();

        if (! empty($category->sub_categories)) {
            foreach ($category->sub_categories as $subCategory) {
                $this->createCategory($saveCategory, $subCategory, $language);
            }
        }

        return $saveCategory;
    }

    private function categoriesByLanguageGroups()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'categories'    => collect([

                    (object) [
                        'name'           => 'National',
                        'brief'        => 'National news and updates',
                        'seo_keywords'   => 'national news, politics, government, law order, elections, policy',
                        'sub_categories' => collect([
                            (object) ['name' => 'Politics', 'brief' => 'Political news', 'seo_keywords' => 'politics news, elections'],
                            (object) ['name' => 'Government', 'brief' => 'Government activities', 'seo_keywords' => 'government'],
                            (object) ['name' => 'Law & Order', 'brief' => 'Law enforcement news', 'seo_keywords' => 'law order'],
                            (object) ['name' => 'Elections', 'brief' => 'Election coverage', 'seo_keywords' => 'elections'],
                            (object) ['name' => 'Public Policy', 'brief' => 'Policy updates', 'seo_keywords' => 'policy'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'International',
                        'brief'        => 'Global news coverage',
                        'seo_keywords'   => 'international news',
                        'sub_categories' => collect([
                            (object) ['name' => 'Global Politics', 'brief' => 'World politics', 'seo_keywords' => 'politics'],
                            (object) ['name' => 'Diplomacy', 'brief' => 'Diplomatic relations', 'seo_keywords' => 'diplomacy'],
                            (object) ['name' => 'Conflicts', 'brief' => 'Global conflicts', 'seo_keywords' => 'war'],
                            (object) ['name' => 'World Economy', 'brief' => 'Global economy', 'seo_keywords' => 'economy'],
                            (object) ['name' => 'Organizations', 'brief' => 'Global organizations', 'seo_keywords' => 'UN'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Business',
                        'brief'        => 'Business and finance news',
                        'seo_keywords'   => 'business news',
                        'sub_categories' => collect([
                            (object) ['name' => 'Stock Market', 'brief' => 'Stock updates', 'seo_keywords' => 'stocks'],
                            (object) ['name' => 'Banking', 'brief' => 'Banking', 'seo_keywords' => 'bank'],
                            (object) ['name' => 'Corporate', 'brief' => 'Corporate', 'seo_keywords' => 'company'],
                            (object) ['name' => 'Startups', 'brief' => 'Startup news', 'seo_keywords' => 'startup'],
                            (object) ['name' => 'Trade', 'brief' => 'Trade', 'seo_keywords' => 'trade'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Technology',
                        'brief'        => 'Technology news',
                        'seo_keywords'   => 'technology',
                        'sub_categories' => collect([
                            (object) ['name' => 'AI', 'brief' => 'Artificial Intelligence', 'seo_keywords' => 'AI'],
                            (object) ['name' => 'Gadgets', 'brief' => 'Devices', 'seo_keywords' => 'gadgets'],
                            (object) ['name' => 'Software', 'brief' => 'Software', 'seo_keywords' => 'software'],
                            (object) ['name' => 'Internet', 'brief' => 'Internet', 'seo_keywords' => 'internet'],
                            (object) ['name' => 'Cybersecurity', 'brief' => 'Security', 'seo_keywords' => 'security'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Sports',
                        'brief'        => 'Sports news',
                        'seo_keywords'   => 'sports',
                        'sub_categories' => collect([
                            (object) ['name' => 'Cricket', 'brief' => 'Cricket news', 'seo_keywords' => 'cricket'],
                            (object) ['name' => 'Football', 'brief' => 'Football news', 'seo_keywords' => 'football'],
                            (object) ['name' => 'Tennis', 'brief' => 'Tennis news', 'seo_keywords' => 'tennis'],
                            (object) ['name' => 'Local Sports', 'brief' => 'Local sports', 'seo_keywords' => 'local'],
                            (object) ['name' => 'International Sports', 'brief' => 'Global sports', 'seo_keywords' => 'global'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Entertainment',
                        'brief'        => 'Entertainment news',
                        'seo_keywords'   => 'entertainment',
                        'sub_categories' => collect([
                            (object) ['name' => 'Movies', 'brief' => 'Movies', 'seo_keywords' => 'movies'],
                            (object) ['name' => 'Music', 'brief' => 'Music', 'seo_keywords' => 'music'],
                            (object) ['name' => 'TV Shows', 'brief' => 'TV', 'seo_keywords' => 'tv'],
                            (object) ['name' => 'Celebrities', 'brief' => 'Celebs', 'seo_keywords' => 'celebrities'],
                            (object) ['name' => 'OTT', 'brief' => 'Streaming', 'seo_keywords' => 'ott'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Health',
                        'brief'        => 'Health news',
                        'seo_keywords'   => 'health',
                        'sub_categories' => collect([
                            (object) ['name' => 'Medical News', 'brief' => 'Medical', 'seo_keywords' => 'medical'],
                            (object) ['name' => 'Public Health', 'brief' => 'Public health', 'seo_keywords' => 'public'],
                            (object) ['name' => 'Diseases', 'brief' => 'Diseases', 'seo_keywords' => 'disease'],
                            (object) ['name' => 'Nutrition', 'brief' => 'Food', 'seo_keywords' => 'nutrition'],
                            (object) ['name' => 'Mental Health', 'brief' => 'Mental health', 'seo_keywords' => 'mental'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Education',
                        'brief'        => 'Education news',
                        'seo_keywords'   => 'education',
                        'sub_categories' => collect([
                            (object) ['name' => 'Schools', 'brief' => 'School', 'seo_keywords' => 'school'],
                            (object) ['name' => 'Universities', 'brief' => 'University', 'seo_keywords' => 'university'],
                            (object) ['name' => 'Exams', 'brief' => 'Exams', 'seo_keywords' => 'exam'],
                            (object) ['name' => 'Scholarships', 'brief' => 'Scholarships', 'seo_keywords' => 'scholarship'],
                            (object) ['name' => 'Policy', 'brief' => 'Education policy', 'seo_keywords' => 'policy'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Lifestyle',
                        'brief'        => 'Lifestyle news',
                        'seo_keywords'   => 'lifestyle',
                        'sub_categories' => collect([
                            (object) ['name' => 'Fashion', 'brief' => 'Fashion', 'seo_keywords' => 'fashion'],
                            (object) ['name' => 'Travel', 'brief' => 'Travel', 'seo_keywords' => 'travel'],
                            (object) ['name' => 'Food', 'brief' => 'Food', 'seo_keywords' => 'food'],
                            (object) ['name' => 'Relationships', 'brief' => 'Relationships', 'seo_keywords' => 'relationship'],
                            (object) ['name' => 'Culture', 'brief' => 'Culture', 'seo_keywords' => 'culture'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Science',
                        'brief'        => 'Science news',
                        'seo_keywords'   => 'science',
                        'sub_categories' => collect([
                            (object) ['name' => 'Space', 'brief' => 'Space', 'seo_keywords' => 'space'],
                            (object) ['name' => 'Research', 'brief' => 'Research', 'seo_keywords' => 'research'],
                            (object) ['name' => 'Innovation', 'brief' => 'Innovation', 'seo_keywords' => 'innovation'],
                            (object) ['name' => 'Tech Research', 'brief' => 'Tech', 'seo_keywords' => 'tech'],
                            (object) ['name' => 'Discoveries', 'brief' => 'Discoveries', 'seo_keywords' => 'discoveries'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'Environment',
                        'brief'        => 'Environment news',
                        'seo_keywords'   => 'environment',
                        'sub_categories' => collect([
                            (object) ['name' => 'Climate Change', 'brief' => 'Climate', 'seo_keywords' => 'climate'],
                            (object) ['name' => 'Pollution', 'brief' => 'Pollution', 'seo_keywords' => 'pollution'],
                            (object) ['name' => 'Wildlife', 'brief' => 'Wildlife', 'seo_keywords' => 'wildlife'],
                            (object) ['name' => 'Disasters', 'brief' => 'Disasters', 'seo_keywords' => 'disaster'],
                            (object) ['name' => 'Sustainability', 'brief' => 'Sustainability', 'seo_keywords' => 'green'],
                        ]),
                    ],

                ]),
            ],

            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                'categories'    => collect([

                    (object) [
                        'name'           => 'Environment',
                        'brief'        => 'Environment news',
                        'seo_keywords'   => 'environment',
                        'sub_categories' => collect([
                            (object) ['name' => 'Climate Change', 'brief' => 'Climate', 'seo_keywords' => 'climate'],
                            (object) ['name' => 'Pollution', 'brief' => 'Pollution', 'seo_keywords' => 'pollution'],
                            (object) ['name' => 'Wildlife', 'brief' => 'Wildlife', 'seo_keywords' => 'wildlife'],
                            (object) ['name' => 'Disasters', 'brief' => 'Disasters', 'seo_keywords' => 'disaster'],
                            (object) ['name' => 'Sustainability', 'brief' => 'Sustainability', 'seo_keywords' => 'green'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'জাতীয়',
                        'brief'        => 'জাতীয় সংবাদ ও আপডেট',
                        'seo_keywords'   => 'জাতীয় সংবাদ, রাজনীতি, সরকার, আইন শৃঙ্খলা, নির্বাচন, নীতি',
                        'sub_categories' => collect([
                            (object) ['name' => 'রাজনীতি', 'brief' => 'রাজনৈতিক সংবাদ', 'seo_keywords' => 'রাজনীতি, নির্বাচন, সংসদ'],
                            (object) ['name' => 'সরকার', 'brief' => 'সরকারি কার্যক্রম', 'seo_keywords' => 'সরকার, মন্ত্রণালয়'],
                            (object) ['name' => 'আইন ও শৃঙ্খলা', 'brief' => 'আইন শৃঙ্খলা সংবাদ', 'seo_keywords' => 'পুলিশ, অপরাধ'],
                            (object) ['name' => 'নির্বাচন', 'brief' => 'নির্বাচন সংবাদ', 'seo_keywords' => 'ভোট, প্রার্থী'],
                            (object) ['name' => 'জননীতি', 'brief' => 'নীতি আপডেট', 'seo_keywords' => 'জননীতি, আইন'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'আন্তর্জাতিক',
                        'brief'        => 'বিশ্ব সংবাদ',
                        'seo_keywords'   => 'আন্তর্জাতিক সংবাদ, বিশ্ব রাজনীতি, কূটনীতি, অর্থনীতি',
                        'sub_categories' => collect([
                            (object) ['name' => 'বিশ্ব রাজনীতি', 'brief' => 'বিশ্ব রাজনীতি সংবাদ', 'seo_keywords' => 'রাজনীতি, বিশ্বনেতা'],
                            (object) ['name' => 'কূটনীতি', 'brief' => 'কূটনৈতিক সম্পর্ক', 'seo_keywords' => 'কূটনীতি, আন্তর্জাতিক সম্পর্ক'],
                            (object) ['name' => 'সংঘাত', 'brief' => 'বিশ্ব সংঘাত', 'seo_keywords' => 'যুদ্ধ, সংকট'],
                            (object) ['name' => 'বিশ্ব অর্থনীতি', 'brief' => 'আন্তর্জাতিক অর্থনীতি', 'seo_keywords' => 'অর্থনীতি, বাণিজ্য'],
                            (object) ['name' => 'আন্তর্জাতিক সংস্থা', 'brief' => 'জাতিসংঘ, WTO ইত্যাদি', 'seo_keywords' => 'জাতিসংঘ, এনজিও'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'ব্যবসা',
                        'brief'        => 'ব্যবসা ও অর্থনীতি সংবাদ',
                        'seo_keywords'   => 'ব্যবসা, অর্থনীতি, কর্পোরেট, স্টার্টআপ',
                        'sub_categories' => collect([
                            (object) ['name' => 'শেয়ার বাজার', 'brief' => 'শেয়ার আপডেট', 'seo_keywords' => 'শেয়ার, বিনিয়োগ'],
                            (object) ['name' => 'ব্যাংকিং', 'brief' => 'ব্যাংক খাত', 'seo_keywords' => 'ব্যাংক, ঋণ'],
                            (object) ['name' => 'কর্পোরেট', 'brief' => 'কর্পোরেট সংবাদ', 'seo_keywords' => 'কোম্পানি, সিইও'],
                            (object) ['name' => 'স্টার্টআপ', 'brief' => 'স্টার্টআপ ইকোসিস্টেম', 'seo_keywords' => 'স্টার্টআপ, উদ্ভাবন'],
                            (object) ['name' => 'বাণিজ্য', 'brief' => 'বাণিজ্য সংবাদ', 'seo_keywords' => 'আমদানি, রপ্তানি'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'প্রযুক্তি',
                        'brief'        => 'প্রযুক্তি সংবাদ',
                        'seo_keywords'   => 'প্রযুক্তি, AI, গ্যাজেট, সফটওয়্যার',
                        'sub_categories' => collect([
                            (object) ['name' => 'এআই', 'brief' => 'কৃত্রিম বুদ্ধিমত্তা', 'seo_keywords' => 'AI, মেশিন লার্নিং'],
                            (object) ['name' => 'গ্যাজেট', 'brief' => 'প্রযুক্তি ডিভাইস', 'seo_keywords' => 'মোবাইল, ডিভাইস'],
                            (object) ['name' => 'সফটওয়্যার', 'brief' => 'সফটওয়্যার সংবাদ', 'seo_keywords' => 'অ্যাপ, ডেভেলপমেন্ট'],
                            (object) ['name' => 'ইন্টারনেট', 'brief' => 'ইন্টারনেট ট্রেন্ড', 'seo_keywords' => 'সোশ্যাল মিডিয়া'],
                            (object) ['name' => 'সাইবার নিরাপত্তা', 'brief' => 'নিরাপত্তা সংবাদ', 'seo_keywords' => 'হ্যাকিং, ডাটা সুরক্ষা'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'খেলাধুলা',
                        'brief'        => 'খেলাধুলা সংবাদ',
                        'seo_keywords'   => 'খেলা, ক্রিকেট, ফুটবল',
                        'sub_categories' => collect([
                            (object) ['name' => 'ক্রিকেট', 'brief' => 'ক্রিকেট সংবাদ', 'seo_keywords' => 'ম্যাচ, খেলোয়াড়'],
                            (object) ['name' => 'ফুটবল', 'brief' => 'ফুটবল সংবাদ', 'seo_keywords' => 'লীগ, FIFA'],
                            (object) ['name' => 'টেনিস', 'brief' => 'টেনিস সংবাদ', 'seo_keywords' => 'টুর্নামেন্ট'],
                            (object) ['name' => 'স্থানীয় খেলা', 'brief' => 'স্থানীয় খেলা', 'seo_keywords' => 'আঞ্চলিক'],
                            (object) ['name' => 'আন্তর্জাতিক খেলা', 'brief' => 'বিশ্ব খেলা', 'seo_keywords' => 'টুর্নামেন্ট'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'বিনোদন',
                        'brief'        => 'বিনোদন সংবাদ',
                        'seo_keywords'   => 'সিনেমা, সংগীত, তারকা',
                        'sub_categories' => collect([
                            (object) ['name' => 'সিনেমা', 'brief' => 'সিনেমা সংবাদ', 'seo_keywords' => 'ফিল্ম'],
                            (object) ['name' => 'সংগীত', 'brief' => 'সংগীত শিল্প', 'seo_keywords' => 'গান'],
                            (object) ['name' => 'টিভি শো', 'brief' => 'টিভি কনটেন্ট', 'seo_keywords' => 'ড্রামা'],
                            (object) ['name' => 'তারকা', 'brief' => 'তারকা সংবাদ', 'seo_keywords' => 'অভিনেতা'],
                            (object) ['name' => 'ওটিটি', 'brief' => 'স্ট্রিমিং প্ল্যাটফর্ম', 'seo_keywords' => 'নেটফ্লিক্স'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'স্বাস্থ্য',
                        'brief'        => 'স্বাস্থ্য সংবাদ',
                        'seo_keywords'   => 'স্বাস্থ্য, রোগ, পুষ্টি',
                        'sub_categories' => collect([
                            (object) ['name' => 'চিকিৎসা সংবাদ', 'brief' => 'চিকিৎসা আপডেট', 'seo_keywords' => 'হাসপাতাল'],
                            (object) ['name' => 'জনস্বাস্থ্য', 'brief' => 'জনস্বাস্থ্য তথ্য', 'seo_keywords' => 'সচেতনতা'],
                            (object) ['name' => 'রোগ', 'brief' => 'রোগ সংবাদ', 'seo_keywords' => 'সংক্রমণ'],
                            (object) ['name' => 'পুষ্টি', 'brief' => 'খাদ্য ও পুষ্টি', 'seo_keywords' => 'ডায়েট'],
                            (object) ['name' => 'মানসিক স্বাস্থ্য', 'brief' => 'মানসিক সুস্থতা', 'seo_keywords' => 'স্ট্রেস'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'শিক্ষা',
                        'brief'        => 'শিক্ষা সংবাদ',
                        'seo_keywords'   => 'স্কুল, বিশ্ববিদ্যালয়, পরীক্ষা',
                        'sub_categories' => collect([
                            (object) ['name' => 'স্কুল', 'brief' => 'স্কুল আপডেট', 'seo_keywords' => 'শিক্ষার্থী'],
                            (object) ['name' => 'বিশ্ববিদ্যালয়', 'brief' => 'বিশ্ববিদ্যালয় সংবাদ', 'seo_keywords' => 'উচ্চশিক্ষা'],
                            (object) ['name' => 'পরীক্ষা', 'brief' => 'পরীক্ষা তথ্য', 'seo_keywords' => 'ফলাফল'],
                            (object) ['name' => 'বৃত্তি', 'brief' => 'বৃত্তি সংবাদ', 'seo_keywords' => 'গ্রান্ট'],
                            (object) ['name' => 'শিক্ষানীতি', 'brief' => 'নীতি আপডেট', 'seo_keywords' => 'সংস্কার'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'জীবনধারা',
                        'brief'        => 'জীবনধারা সংবাদ',
                        'seo_keywords'   => 'ফ্যাশন, ভ্রমণ, খাবার',
                        'sub_categories' => collect([
                            (object) ['name' => 'ফ্যাশন', 'brief' => 'ফ্যাশন ট্রেন্ড', 'seo_keywords' => 'স্টাইল'],
                            (object) ['name' => 'ভ্রমণ', 'brief' => 'ভ্রমণ সংবাদ', 'seo_keywords' => 'ট্যুরিজম'],
                            (object) ['name' => 'খাবার', 'brief' => 'খাদ্য সংস্কৃতি', 'seo_keywords' => 'রেসিপি'],
                            (object) ['name' => 'সম্পর্ক', 'brief' => 'সম্পর্ক পরামর্শ', 'seo_keywords' => 'ভালোবাসা'],
                            (object) ['name' => 'সংস্কৃতি', 'brief' => 'সাংস্কৃতিক সংবাদ', 'seo_keywords' => 'ঐতিহ্য'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'বিজ্ঞান',
                        'brief'        => 'বিজ্ঞান সংবাদ',
                        'seo_keywords'   => 'গবেষণা, মহাকাশ',
                        'sub_categories' => collect([
                            (object) ['name' => 'মহাকাশ', 'brief' => 'মহাকাশ অনুসন্ধান', 'seo_keywords' => 'NASA'],
                            (object) ['name' => 'গবেষণা', 'brief' => 'বৈজ্ঞানিক গবেষণা', 'seo_keywords' => 'স্টাডি'],
                            (object) ['name' => 'উদ্ভাবন', 'brief' => 'নতুন উদ্ভাবন', 'seo_keywords' => 'টেক'],
                            (object) ['name' => 'প্রযুক্তি গবেষণা', 'brief' => 'গবেষণা', 'seo_keywords' => 'R&D'],
                            (object) ['name' => 'বৈজ্ঞানিক আবিষ্কার', 'brief' => 'নতুন আবিষ্কার', 'seo_keywords' => 'ব্রেকথ্রু'],
                        ]),
                    ],

                    (object) [
                        'name'           => 'পরিবেশ',
                        'brief'        => 'পরিবেশ সংবাদ',
                        'seo_keywords'   => 'জলবায়ু, দূষণ',
                        'sub_categories' => collect([
                            (object) ['name' => 'জলবায়ু পরিবর্তন', 'brief' => 'জলবায়ু সমস্যা', 'seo_keywords' => 'গ্লোবাল ওয়ার্মিং'],
                            (object) ['name' => 'দূষণ', 'brief' => 'দূষণ সংবাদ', 'seo_keywords' => 'বায়ু'],
                            (object) ['name' => 'বন্যপ্রাণী', 'brief' => 'প্রাণী সংবাদ', 'seo_keywords' => 'সংরক্ষণ'],
                            (object) ['name' => 'প্রাকৃতিক দুর্যোগ', 'brief' => 'দুর্যোগ সংবাদ', 'seo_keywords' => 'বন্যা'],
                            (object) ['name' => 'টেকসই উন্নয়ন', 'brief' => 'সাস্টেইনেবিলিটি', 'seo_keywords' => 'সবুজ শক্তি'],
                        ]),
                    ],

                ]),
            ],

        ]);
    }
}
