<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Tag::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='tags'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Tag::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Tag::truncate();
        }

        $languages = Language::all()->keyBy('code');

        $tagsByLanguageGroupsFromStaticData = $this->getTagsByLanguageGroupsFromStaticData();
        foreach ($tagsByLanguageGroupsFromStaticData as $tagsByLanguageGroup) {
            $language = $languages[$tagsByLanguageGroup->language_code] ?? null;
            foreach ($tagsByLanguageGroup->tags as $tag) {
                Tag::factory()->state([
                    'name'         => $tag->name,
                    'brief'      => $tag->brief ?? null,

                    'language_id'  => $language?->id,
                    'seo_title'    => $tag->name,
                    'seo_brief'    => $tag->brief ?? null,
                    'seo_keywords' => $tag->seo_keywords ?? null,
                ])->create();
            }
        }

    }

    private function getTagsByLanguageGroupsFromStaticData()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'tags'       => collect([

                    (object) ['name' => 'National', 'brief' => 'National news', 'seo_keywords' => 'national news, country updates'],
                    (object) ['name' => 'Politics', 'brief' => 'Political news', 'seo_keywords' => 'politics, elections, parliament'],
                    (object) ['name' => 'Government', 'brief' => 'Government news', 'seo_keywords' => 'government, ministry'],
                    (object) ['name' => 'Law & Order', 'brief' => 'Law enforcement', 'seo_keywords' => 'crime, police, law'],
                    (object) ['name' => 'Elections', 'brief' => 'Election coverage', 'seo_keywords' => 'elections, voting'],
                    (object) ['name' => 'Public Policy', 'brief' => 'Policy updates', 'seo_keywords' => 'policy, reforms'],

                    (object) ['name' => 'International', 'brief' => 'Global news', 'seo_keywords' => 'international news'],
                    (object) ['name' => 'Global Politics', 'brief' => 'World politics', 'seo_keywords' => 'geopolitics'],
                    (object) ['name' => 'Diplomacy', 'brief' => 'Diplomatic relations', 'seo_keywords' => 'diplomacy'],
                    (object) ['name' => 'Conflicts', 'brief' => 'Global conflicts', 'seo_keywords' => 'war, crisis'],
                    (object) ['name' => 'World Economy', 'brief' => 'Global economy', 'seo_keywords' => 'economy, trade'],
                    (object) ['name' => 'International Organizations', 'brief' => 'Global org', 'seo_keywords' => 'UN, WTO'],

                    (object) ['name' => 'Business', 'brief' => 'Business news', 'seo_keywords' => 'business, finance'],
                    (object) ['name' => 'Stock Market', 'brief' => 'Stock updates', 'seo_keywords' => 'stocks, trading'],
                    (object) ['name' => 'Banking', 'brief' => 'Banking news', 'seo_keywords' => 'banking, loans'],
                    (object) ['name' => 'Corporate', 'brief' => 'Corporate news', 'seo_keywords' => 'corporate'],
                    (object) ['name' => 'Startups', 'brief' => 'Startup news', 'seo_keywords' => 'startup, entrepreneurship'],
                    (object) ['name' => 'Trade', 'brief' => 'Trade news', 'seo_keywords' => 'trade'],

                    (object) ['name' => 'Technology', 'brief' => 'Tech news', 'seo_keywords' => 'technology'],
                    (object) ['name' => 'AI', 'brief' => 'Artificial Intelligence', 'seo_keywords' => 'AI, machine learning'],
                    (object) ['name' => 'Gadgets', 'brief' => 'Devices', 'seo_keywords' => 'gadgets, devices'],
                    (object) ['name' => 'Software', 'brief' => 'Software news', 'seo_keywords' => 'software'],
                    (object) ['name' => 'Internet', 'brief' => 'Internet trends', 'seo_keywords' => 'internet'],
                    (object) ['name' => 'Cybersecurity', 'brief' => 'Security news', 'seo_keywords' => 'cybersecurity'],

                    (object) ['name' => 'Sports', 'brief' => 'Sports news', 'seo_keywords' => 'sports'],
                    (object) ['name' => 'Cricket', 'brief' => 'Cricket news', 'seo_keywords' => 'cricket'],
                    (object) ['name' => 'Football', 'brief' => 'Football news', 'seo_keywords' => 'football'],
                    (object) ['name' => 'Tennis', 'brief' => 'Tennis news', 'seo_keywords' => 'tennis'],
                    (object) ['name' => 'Local Sports', 'brief' => 'Local games', 'seo_keywords' => 'local sports'],
                    (object) ['name' => 'International Sports', 'brief' => 'Global sports', 'seo_keywords' => 'global sports'],

                    (object) ['name' => 'Entertainment', 'brief' => 'Entertainment news', 'seo_keywords' => 'entertainment'],
                    (object) ['name' => 'Movies', 'brief' => 'Movies', 'seo_keywords' => 'movies'],
                    (object) ['name' => 'Music', 'brief' => 'Music', 'seo_keywords' => 'music'],
                    (object) ['name' => 'TV Shows', 'brief' => 'TV', 'seo_keywords' => 'tv shows'],
                    (object) ['name' => 'Celebrities', 'brief' => 'Celebs', 'seo_keywords' => 'celebrities'],
                    (object) ['name' => 'OTT', 'brief' => 'Streaming', 'seo_keywords' => 'OTT'],

                    (object) ['name' => 'Health', 'brief' => 'Health news', 'seo_keywords' => 'health'],
                    (object) ['name' => 'Medical News', 'brief' => 'Medical', 'seo_keywords' => 'medical'],
                    (object) ['name' => 'Public Health', 'brief' => 'Public health', 'seo_keywords' => 'public health'],
                    (object) ['name' => 'Diseases', 'brief' => 'Diseases', 'seo_keywords' => 'diseases'],
                    (object) ['name' => 'Nutrition', 'brief' => 'Nutrition', 'seo_keywords' => 'nutrition'],
                    (object) ['name' => 'Mental Health', 'brief' => 'Mental', 'seo_keywords' => 'mental health'],

                    (object) ['name' => 'Education', 'brief' => 'Education news', 'seo_keywords' => 'education'],
                    (object) ['name' => 'Schools', 'brief' => 'Schools', 'seo_keywords' => 'schools'],
                    (object) ['name' => 'Universities', 'brief' => 'Universities', 'seo_keywords' => 'universities'],
                    (object) ['name' => 'Exams', 'brief' => 'Exams', 'seo_keywords' => 'exams'],
                    (object) ['name' => 'Scholarships', 'brief' => 'Scholarships', 'seo_keywords' => 'scholarships'],
                    (object) ['name' => 'Education Policy', 'brief' => 'Policy', 'seo_keywords' => 'education policy'],

                    (object) ['name' => 'Lifestyle', 'brief' => 'Lifestyle news', 'seo_keywords' => 'lifestyle'],
                    (object) ['name' => 'Fashion', 'brief' => 'Fashion', 'seo_keywords' => 'fashion'],
                    (object) ['name' => 'Travel', 'brief' => 'Travel', 'seo_keywords' => 'travel'],
                    (object) ['name' => 'Food', 'brief' => 'Food', 'seo_keywords' => 'food'],
                    (object) ['name' => 'Relationships', 'brief' => 'Relationships', 'seo_keywords' => 'relationships'],
                    (object) ['name' => 'Culture', 'brief' => 'Culture', 'seo_keywords' => 'culture'],

                    (object) ['name' => 'Science', 'brief' => 'Science news', 'seo_keywords' => 'science'],
                    (object) ['name' => 'Space', 'brief' => 'Space', 'seo_keywords' => 'space'],
                    (object) ['name' => 'Research', 'brief' => 'Research', 'seo_keywords' => 'research'],
                    (object) ['name' => 'Innovation', 'brief' => 'Innovation', 'seo_keywords' => 'innovation'],
                    (object) ['name' => 'Technology Research', 'brief' => 'Tech research', 'seo_keywords' => 'tech research'],
                    (object) ['name' => 'Scientific Discoveries', 'brief' => 'Discoveries', 'seo_keywords' => 'discoveries'],

                    (object) ['name' => 'Environment', 'brief' => 'Environment news', 'seo_keywords' => 'environment'],
                    (object) ['name' => 'Climate Change', 'brief' => 'Climate', 'seo_keywords' => 'climate change'],
                    (object) ['name' => 'Pollution', 'brief' => 'Pollution', 'seo_keywords' => 'pollution'],
                    (object) ['name' => 'Wildlife', 'brief' => 'Wildlife', 'seo_keywords' => 'wildlife'],
                    (object) ['name' => 'Natural Disasters', 'brief' => 'Disasters', 'seo_keywords' => 'disasters'],
                    (object) ['name' => 'Sustainability', 'brief' => 'Sustainability', 'seo_keywords' => 'sustainability'],

                ]),
            ],

            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                'tags'       => collect([
                    (object) ['name' => 'জাতীয়', 'brief' => 'জাতীয় সংবাদ', 'seo_keywords' => 'জাতীয় সংবাদ, দেশ আপডেট'],
                    (object) ['name' => 'রাজনীতি', 'brief' => 'রাজনৈতিক সংবাদ', 'seo_keywords' => 'রাজনীতি, নির্বাচন, সংসদ'],
                    (object) ['name' => 'সরকার', 'brief' => 'সরকারি সংবাদ', 'seo_keywords' => 'সরকার, মন্ত্রণালয়'],
                    (object) ['name' => 'আইন ও শৃঙ্খলা', 'brief' => 'আইন শৃঙ্খলা সংবাদ', 'seo_keywords' => 'অপরাধ, পুলিশ, আইন'],
                    (object) ['name' => 'নির্বাচন', 'brief' => 'নির্বাচন কভারেজ', 'seo_keywords' => 'ভোট, নির্বাচন'],
                    (object) ['name' => 'জননীতি', 'brief' => 'নীতিমালা আপডেট', 'seo_keywords' => 'নীতি, সংস্কার'],

                    (object) ['name' => 'আন্তর্জাতিক', 'brief' => 'বিশ্ব সংবাদ', 'seo_keywords' => 'আন্তর্জাতিক সংবাদ'],
                    (object) ['name' => 'বিশ্ব রাজনীতি', 'brief' => 'বিশ্ব রাজনীতি', 'seo_keywords' => 'ভূ-রাজনীতি'],
                    (object) ['name' => 'কূটনীতি', 'brief' => 'কূটনৈতিক সম্পর্ক', 'seo_keywords' => 'কূটনীতি'],
                    (object) ['name' => 'সংঘাত', 'brief' => 'বিশ্ব সংঘাত', 'seo_keywords' => 'যুদ্ধ, সংকট'],
                    (object) ['name' => 'বিশ্ব অর্থনীতি', 'brief' => 'আন্তর্জাতিক অর্থনীতি', 'seo_keywords' => 'অর্থনীতি, বাণিজ্য'],
                    (object) ['name' => 'আন্তর্জাতিক সংস্থা', 'brief' => 'বিশ্ব সংস্থা', 'seo_keywords' => 'জাতিসংঘ, WTO'],

                    (object) ['name' => 'ব্যবসা', 'brief' => 'ব্যবসা সংবাদ', 'seo_keywords' => 'ব্যবসা, অর্থনীতি'],
                    (object) ['name' => 'শেয়ার বাজার', 'brief' => 'শেয়ার আপডেট', 'seo_keywords' => 'শেয়ার, ট্রেডিং'],
                    (object) ['name' => 'ব্যাংকিং', 'brief' => 'ব্যাংকিং সংবাদ', 'seo_keywords' => 'ব্যাংক, ঋণ'],
                    (object) ['name' => 'কর্পোরেট', 'brief' => 'কর্পোরেট সংবাদ', 'seo_keywords' => 'কোম্পানি'],
                    (object) ['name' => 'স্টার্টআপ', 'brief' => 'স্টার্টআপ সংবাদ', 'seo_keywords' => 'উদ্যোক্তা'],
                    (object) ['name' => 'বাণিজ্য', 'brief' => 'বাণিজ্য সংবাদ', 'seo_keywords' => 'আমদানি, রপ্তানি'],

                    (object) ['name' => 'প্রযুক্তি', 'brief' => 'প্রযুক্তি সংবাদ', 'seo_keywords' => 'প্রযুক্তি'],
                    (object) ['name' => 'এআই', 'brief' => 'কৃত্রিম বুদ্ধিমত্তা', 'seo_keywords' => 'এআই, মেশিন লার্নিং'],
                    (object) ['name' => 'গ্যাজেট', 'brief' => 'ডিভাইস', 'seo_keywords' => 'ডিভাইস'],
                    (object) ['name' => 'সফটওয়্যার', 'brief' => 'সফটওয়্যার সংবাদ', 'seo_keywords' => 'অ্যাপ'],
                    (object) ['name' => 'ইন্টারনেট', 'brief' => 'ইন্টারনেট ট্রেন্ড', 'seo_keywords' => 'ওয়েব'],
                    (object) ['name' => 'সাইবার নিরাপত্তা', 'brief' => 'নিরাপত্তা সংবাদ', 'seo_keywords' => 'হ্যাকিং, নিরাপত্তা'],

                    (object) ['name' => 'খেলাধুলা', 'brief' => 'খেলা সংবাদ', 'seo_keywords' => 'খেলা'],
                    (object) ['name' => 'ক্রিকেট', 'brief' => 'ক্রিকেট সংবাদ', 'seo_keywords' => 'ম্যাচ'],
                    (object) ['name' => 'ফুটবল', 'brief' => 'ফুটবল সংবাদ', 'seo_keywords' => 'লীগ'],
                    (object) ['name' => 'টেনিস', 'brief' => 'টেনিস সংবাদ', 'seo_keywords' => 'টুর্নামেন্ট'],
                    (object) ['name' => 'স্থানীয় খেলা', 'brief' => 'স্থানীয় খেলা', 'seo_keywords' => 'আঞ্চলিক'],
                    (object) ['name' => 'আন্তর্জাতিক খেলা', 'brief' => 'বিশ্ব খেলা', 'seo_keywords' => 'বিশ্ব খেলা'],

                    (object) ['name' => 'বিনোদন', 'brief' => 'বিনোদন সংবাদ', 'seo_keywords' => 'বিনোদন'],
                    (object) ['name' => 'সিনেমা', 'brief' => 'সিনেমা', 'seo_keywords' => 'ফিল্ম'],
                    (object) ['name' => 'সংগীত', 'brief' => 'সংগীত', 'seo_keywords' => 'গান'],
                    (object) ['name' => 'টিভি শো', 'brief' => 'টিভি কনটেন্ট', 'seo_keywords' => 'ড্রামা'],
                    (object) ['name' => 'তারকা', 'brief' => 'তারকা সংবাদ', 'seo_keywords' => 'সেলিব্রিটি'],
                    (object) ['name' => 'ওটিটি', 'brief' => 'স্ট্রিমিং', 'seo_keywords' => 'নেটফ্লিক্স'],

                    (object) ['name' => 'স্বাস্থ্য', 'brief' => 'স্বাস্থ্য সংবাদ', 'seo_keywords' => 'স্বাস্থ্য'],
                    (object) ['name' => 'চিকিৎসা', 'brief' => 'চিকিৎসা সংবাদ', 'seo_keywords' => 'মেডিকেল'],
                    (object) ['name' => 'জনস্বাস্থ্য', 'brief' => 'জনস্বাস্থ্য', 'seo_keywords' => 'সচেতনতা'],
                    (object) ['name' => 'রোগ', 'brief' => 'রোগ সংবাদ', 'seo_keywords' => 'রোগ'],
                    (object) ['name' => 'পুষ্টি', 'brief' => 'পুষ্টি', 'seo_keywords' => 'ডায়েট'],
                    (object) ['name' => 'মানসিক স্বাস্থ্য', 'brief' => 'মানসিক স্বাস্থ্য', 'seo_keywords' => 'স্ট্রেস'],

                    (object) ['name' => 'শিক্ষা', 'brief' => 'শিক্ষা সংবাদ', 'seo_keywords' => 'শিক্ষা'],
                    (object) ['name' => 'স্কুল', 'brief' => 'স্কুল', 'seo_keywords' => 'শিক্ষার্থী'],
                    (object) ['name' => 'বিশ্ববিদ্যালয়', 'brief' => 'বিশ্ববিদ্যালয়', 'seo_keywords' => 'উচ্চশিক্ষা'],
                    (object) ['name' => 'পরীক্ষা', 'brief' => 'পরীক্ষা', 'seo_keywords' => 'ফলাফল'],
                    (object) ['name' => 'বৃত্তি', 'brief' => 'বৃত্তি', 'seo_keywords' => 'স্কলারশিপ'],
                    (object) ['name' => 'শিক্ষানীতি', 'brief' => 'নীতি', 'seo_keywords' => 'নীতি'],

                    (object) ['name' => 'জীবনধারা', 'brief' => 'লাইফস্টাইল', 'seo_keywords' => 'জীবনধারা'],
                    (object) ['name' => 'ফ্যাশন', 'brief' => 'ফ্যাশন', 'seo_keywords' => 'স্টাইল'],
                    (object) ['name' => 'ভ্রমণ', 'brief' => 'ভ্রমণ', 'seo_keywords' => 'ট্যুরিজম'],
                    (object) ['name' => 'খাবার', 'brief' => 'খাবার', 'seo_keywords' => 'রেসিপি'],
                    (object) ['name' => 'সম্পর্ক', 'brief' => 'সম্পর্ক', 'seo_keywords' => 'ভালোবাসা'],
                    (object) ['name' => 'সংস্কৃতি', 'brief' => 'সংস্কৃতি', 'seo_keywords' => 'ঐতিহ্য'],

                    (object) ['name' => 'বিজ্ঞান', 'brief' => 'বিজ্ঞান সংবাদ', 'seo_keywords' => 'বিজ্ঞান'],
                    (object) ['name' => 'মহাকাশ', 'brief' => 'মহাকাশ', 'seo_keywords' => 'স্পেস'],
                    (object) ['name' => 'গবেষণা', 'brief' => 'গবেষণা', 'seo_keywords' => 'স্টাডি'],
                    (object) ['name' => 'উদ্ভাবন', 'brief' => 'উদ্ভাবন', 'seo_keywords' => 'ইনোভেশন'],
                    (object) ['name' => 'আবিষ্কার', 'brief' => 'আবিষ্কার', 'seo_keywords' => 'ডিসকভারি'],

                    (object) ['name' => 'পরিবেশ', 'brief' => 'পরিবেশ সংবাদ', 'seo_keywords' => 'পরিবেশ'],
                    (object) ['name' => 'জলবায়ু', 'brief' => 'জলবায়ু পরিবর্তন', 'seo_keywords' => 'জলবায়ু'],
                    (object) ['name' => 'দূষণ', 'brief' => 'দূষণ', 'seo_keywords' => 'পলিউশন'],
                    (object) ['name' => 'বন্যপ্রাণী', 'brief' => 'বন্যপ্রাণী', 'seo_keywords' => 'প্রাণী'],
                    (object) ['name' => 'দুর্যোগ', 'brief' => 'প্রাকৃতিক দুর্যোগ', 'seo_keywords' => 'দুর্যোগ'],
                    (object) ['name' => 'টেকসই উন্নয়ন', 'brief' => 'টেকসই উন্নয়ন', 'seo_keywords' => 'সবুজ'],
                ]),
            ],

        ]);
    }
}
