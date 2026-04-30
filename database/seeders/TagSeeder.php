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
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Tag::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='tags'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Tag::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Tag::truncate();
        }

        $languages = Language::all()->keyBy('code');

        $tagsByLanguageGroups = $this->tagsByLanguageGroups();
        foreach ($tagsByLanguageGroups as $tagsByLanguageGroup) {
            $language = $languages[$tagsByLanguageGroup->language_code] ?? null;
            foreach ($tagsByLanguageGroup->tags as $tag) {
                Tag::factory()->state([
                    'name'         => $tag->name,
                    'details'      => $tag->details ?? null,

                    'language_id'  => $language?->id,
                    'seo_title'    => $tag->name,
                    'seo_brief'    => $tag->details ?? null,
                    'seo_keywords' => $tag->seo_keywords ?? null,
                ])->create();
            }
        }

    }

    private function tagsByLanguageGroups()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'tags'       => collect([

                    (object) ['name' => 'National', 'details' => 'National news', 'seo_keywords' => 'national news, country updates'],
                    (object) ['name' => 'Politics', 'details' => 'Political news', 'seo_keywords' => 'politics, elections, parliament'],
                    (object) ['name' => 'Government', 'details' => 'Government news', 'seo_keywords' => 'government, ministry'],
                    (object) ['name' => 'Law & Order', 'details' => 'Law enforcement', 'seo_keywords' => 'crime, police, law'],
                    (object) ['name' => 'Elections', 'details' => 'Election coverage', 'seo_keywords' => 'elections, voting'],
                    (object) ['name' => 'Public Policy', 'details' => 'Policy updates', 'seo_keywords' => 'policy, reforms'],

                    (object) ['name' => 'International', 'details' => 'Global news', 'seo_keywords' => 'international news'],
                    (object) ['name' => 'Global Politics', 'details' => 'World politics', 'seo_keywords' => 'geopolitics'],
                    (object) ['name' => 'Diplomacy', 'details' => 'Diplomatic relations', 'seo_keywords' => 'diplomacy'],
                    (object) ['name' => 'Conflicts', 'details' => 'Global conflicts', 'seo_keywords' => 'war, crisis'],
                    (object) ['name' => 'World Economy', 'details' => 'Global economy', 'seo_keywords' => 'economy, trade'],
                    (object) ['name' => 'International Organizations', 'details' => 'Global org', 'seo_keywords' => 'UN, WTO'],

                    (object) ['name' => 'Business', 'details' => 'Business news', 'seo_keywords' => 'business, finance'],
                    (object) ['name' => 'Stock Market', 'details' => 'Stock updates', 'seo_keywords' => 'stocks, trading'],
                    (object) ['name' => 'Banking', 'details' => 'Banking news', 'seo_keywords' => 'banking, loans'],
                    (object) ['name' => 'Corporate', 'details' => 'Corporate news', 'seo_keywords' => 'corporate'],
                    (object) ['name' => 'Startups', 'details' => 'Startup news', 'seo_keywords' => 'startup, entrepreneurship'],
                    (object) ['name' => 'Trade', 'details' => 'Trade news', 'seo_keywords' => 'trade'],

                    (object) ['name' => 'Technology', 'details' => 'Tech news', 'seo_keywords' => 'technology'],
                    (object) ['name' => 'AI', 'details' => 'Artificial Intelligence', 'seo_keywords' => 'AI, machine learning'],
                    (object) ['name' => 'Gadgets', 'details' => 'Devices', 'seo_keywords' => 'gadgets, devices'],
                    (object) ['name' => 'Software', 'details' => 'Software news', 'seo_keywords' => 'software'],
                    (object) ['name' => 'Internet', 'details' => 'Internet trends', 'seo_keywords' => 'internet'],
                    (object) ['name' => 'Cybersecurity', 'details' => 'Security news', 'seo_keywords' => 'cybersecurity'],

                    (object) ['name' => 'Sports', 'details' => 'Sports news', 'seo_keywords' => 'sports'],
                    (object) ['name' => 'Cricket', 'details' => 'Cricket news', 'seo_keywords' => 'cricket'],
                    (object) ['name' => 'Football', 'details' => 'Football news', 'seo_keywords' => 'football'],
                    (object) ['name' => 'Tennis', 'details' => 'Tennis news', 'seo_keywords' => 'tennis'],
                    (object) ['name' => 'Local Sports', 'details' => 'Local games', 'seo_keywords' => 'local sports'],
                    (object) ['name' => 'International Sports', 'details' => 'Global sports', 'seo_keywords' => 'global sports'],

                    (object) ['name' => 'Entertainment', 'details' => 'Entertainment news', 'seo_keywords' => 'entertainment'],
                    (object) ['name' => 'Movies', 'details' => 'Movies', 'seo_keywords' => 'movies'],
                    (object) ['name' => 'Music', 'details' => 'Music', 'seo_keywords' => 'music'],
                    (object) ['name' => 'TV Shows', 'details' => 'TV', 'seo_keywords' => 'tv shows'],
                    (object) ['name' => 'Celebrities', 'details' => 'Celebs', 'seo_keywords' => 'celebrities'],
                    (object) ['name' => 'OTT', 'details' => 'Streaming', 'seo_keywords' => 'OTT'],

                    (object) ['name' => 'Health', 'details' => 'Health news', 'seo_keywords' => 'health'],
                    (object) ['name' => 'Medical News', 'details' => 'Medical', 'seo_keywords' => 'medical'],
                    (object) ['name' => 'Public Health', 'details' => 'Public health', 'seo_keywords' => 'public health'],
                    (object) ['name' => 'Diseases', 'details' => 'Diseases', 'seo_keywords' => 'diseases'],
                    (object) ['name' => 'Nutrition', 'details' => 'Nutrition', 'seo_keywords' => 'nutrition'],
                    (object) ['name' => 'Mental Health', 'details' => 'Mental', 'seo_keywords' => 'mental health'],

                    (object) ['name' => 'Education', 'details' => 'Education news', 'seo_keywords' => 'education'],
                    (object) ['name' => 'Schools', 'details' => 'Schools', 'seo_keywords' => 'schools'],
                    (object) ['name' => 'Universities', 'details' => 'Universities', 'seo_keywords' => 'universities'],
                    (object) ['name' => 'Exams', 'details' => 'Exams', 'seo_keywords' => 'exams'],
                    (object) ['name' => 'Scholarships', 'details' => 'Scholarships', 'seo_keywords' => 'scholarships'],
                    (object) ['name' => 'Education Policy', 'details' => 'Policy', 'seo_keywords' => 'education policy'],

                    (object) ['name' => 'Lifestyle', 'details' => 'Lifestyle news', 'seo_keywords' => 'lifestyle'],
                    (object) ['name' => 'Fashion', 'details' => 'Fashion', 'seo_keywords' => 'fashion'],
                    (object) ['name' => 'Travel', 'details' => 'Travel', 'seo_keywords' => 'travel'],
                    (object) ['name' => 'Food', 'details' => 'Food', 'seo_keywords' => 'food'],
                    (object) ['name' => 'Relationships', 'details' => 'Relationships', 'seo_keywords' => 'relationships'],
                    (object) ['name' => 'Culture', 'details' => 'Culture', 'seo_keywords' => 'culture'],

                    (object) ['name' => 'Science', 'details' => 'Science news', 'seo_keywords' => 'science'],
                    (object) ['name' => 'Space', 'details' => 'Space', 'seo_keywords' => 'space'],
                    (object) ['name' => 'Research', 'details' => 'Research', 'seo_keywords' => 'research'],
                    (object) ['name' => 'Innovation', 'details' => 'Innovation', 'seo_keywords' => 'innovation'],
                    (object) ['name' => 'Technology Research', 'details' => 'Tech research', 'seo_keywords' => 'tech research'],
                    (object) ['name' => 'Scientific Discoveries', 'details' => 'Discoveries', 'seo_keywords' => 'discoveries'],

                    (object) ['name' => 'Environment', 'details' => 'Environment news', 'seo_keywords' => 'environment'],
                    (object) ['name' => 'Climate Change', 'details' => 'Climate', 'seo_keywords' => 'climate change'],
                    (object) ['name' => 'Pollution', 'details' => 'Pollution', 'seo_keywords' => 'pollution'],
                    (object) ['name' => 'Wildlife', 'details' => 'Wildlife', 'seo_keywords' => 'wildlife'],
                    (object) ['name' => 'Natural Disasters', 'details' => 'Disasters', 'seo_keywords' => 'disasters'],
                    (object) ['name' => 'Sustainability', 'details' => 'Sustainability', 'seo_keywords' => 'sustainability'],

                ]),
            ],

            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                'tags'       => collect([
                    (object) ['name' => 'জাতীয়', 'details' => 'জাতীয় সংবাদ', 'seo_keywords' => 'জাতীয় সংবাদ, দেশ আপডেট'],
                    (object) ['name' => 'রাজনীতি', 'details' => 'রাজনৈতিক সংবাদ', 'seo_keywords' => 'রাজনীতি, নির্বাচন, সংসদ'],
                    (object) ['name' => 'সরকার', 'details' => 'সরকারি সংবাদ', 'seo_keywords' => 'সরকার, মন্ত্রণালয়'],
                    (object) ['name' => 'আইন ও শৃঙ্খলা', 'details' => 'আইন শৃঙ্খলা সংবাদ', 'seo_keywords' => 'অপরাধ, পুলিশ, আইন'],
                    (object) ['name' => 'নির্বাচন', 'details' => 'নির্বাচন কভারেজ', 'seo_keywords' => 'ভোট, নির্বাচন'],
                    (object) ['name' => 'জননীতি', 'details' => 'নীতিমালা আপডেট', 'seo_keywords' => 'নীতি, সংস্কার'],

                    (object) ['name' => 'আন্তর্জাতিক', 'details' => 'বিশ্ব সংবাদ', 'seo_keywords' => 'আন্তর্জাতিক সংবাদ'],
                    (object) ['name' => 'বিশ্ব রাজনীতি', 'details' => 'বিশ্ব রাজনীতি', 'seo_keywords' => 'ভূ-রাজনীতি'],
                    (object) ['name' => 'কূটনীতি', 'details' => 'কূটনৈতিক সম্পর্ক', 'seo_keywords' => 'কূটনীতি'],
                    (object) ['name' => 'সংঘাত', 'details' => 'বিশ্ব সংঘাত', 'seo_keywords' => 'যুদ্ধ, সংকট'],
                    (object) ['name' => 'বিশ্ব অর্থনীতি', 'details' => 'আন্তর্জাতিক অর্থনীতি', 'seo_keywords' => 'অর্থনীতি, বাণিজ্য'],
                    (object) ['name' => 'আন্তর্জাতিক সংস্থা', 'details' => 'বিশ্ব সংস্থা', 'seo_keywords' => 'জাতিসংঘ, WTO'],

                    (object) ['name' => 'ব্যবসা', 'details' => 'ব্যবসা সংবাদ', 'seo_keywords' => 'ব্যবসা, অর্থনীতি'],
                    (object) ['name' => 'শেয়ার বাজার', 'details' => 'শেয়ার আপডেট', 'seo_keywords' => 'শেয়ার, ট্রেডিং'],
                    (object) ['name' => 'ব্যাংকিং', 'details' => 'ব্যাংকিং সংবাদ', 'seo_keywords' => 'ব্যাংক, ঋণ'],
                    (object) ['name' => 'কর্পোরেট', 'details' => 'কর্পোরেট সংবাদ', 'seo_keywords' => 'কোম্পানি'],
                    (object) ['name' => 'স্টার্টআপ', 'details' => 'স্টার্টআপ সংবাদ', 'seo_keywords' => 'উদ্যোক্তা'],
                    (object) ['name' => 'বাণিজ্য', 'details' => 'বাণিজ্য সংবাদ', 'seo_keywords' => 'আমদানি, রপ্তানি'],

                    (object) ['name' => 'প্রযুক্তি', 'details' => 'প্রযুক্তি সংবাদ', 'seo_keywords' => 'প্রযুক্তি'],
                    (object) ['name' => 'এআই', 'details' => 'কৃত্রিম বুদ্ধিমত্তা', 'seo_keywords' => 'এআই, মেশিন লার্নিং'],
                    (object) ['name' => 'গ্যাজেট', 'details' => 'ডিভাইস', 'seo_keywords' => 'ডিভাইস'],
                    (object) ['name' => 'সফটওয়্যার', 'details' => 'সফটওয়্যার সংবাদ', 'seo_keywords' => 'অ্যাপ'],
                    (object) ['name' => 'ইন্টারনেট', 'details' => 'ইন্টারনেট ট্রেন্ড', 'seo_keywords' => 'ওয়েব'],
                    (object) ['name' => 'সাইবার নিরাপত্তা', 'details' => 'নিরাপত্তা সংবাদ', 'seo_keywords' => 'হ্যাকিং, নিরাপত্তা'],

                    (object) ['name' => 'খেলাধুলা', 'details' => 'খেলা সংবাদ', 'seo_keywords' => 'খেলা'],
                    (object) ['name' => 'ক্রিকেট', 'details' => 'ক্রিকেট সংবাদ', 'seo_keywords' => 'ম্যাচ'],
                    (object) ['name' => 'ফুটবল', 'details' => 'ফুটবল সংবাদ', 'seo_keywords' => 'লীগ'],
                    (object) ['name' => 'টেনিস', 'details' => 'টেনিস সংবাদ', 'seo_keywords' => 'টুর্নামেন্ট'],
                    (object) ['name' => 'স্থানীয় খেলা', 'details' => 'স্থানীয় খেলা', 'seo_keywords' => 'আঞ্চলিক'],
                    (object) ['name' => 'আন্তর্জাতিক খেলা', 'details' => 'বিশ্ব খেলা', 'seo_keywords' => 'বিশ্ব খেলা'],

                    (object) ['name' => 'বিনোদন', 'details' => 'বিনোদন সংবাদ', 'seo_keywords' => 'বিনোদন'],
                    (object) ['name' => 'সিনেমা', 'details' => 'সিনেমা', 'seo_keywords' => 'ফিল্ম'],
                    (object) ['name' => 'সংগীত', 'details' => 'সংগীত', 'seo_keywords' => 'গান'],
                    (object) ['name' => 'টিভি শো', 'details' => 'টিভি কনটেন্ট', 'seo_keywords' => 'ড্রামা'],
                    (object) ['name' => 'তারকা', 'details' => 'তারকা সংবাদ', 'seo_keywords' => 'সেলিব্রিটি'],
                    (object) ['name' => 'ওটিটি', 'details' => 'স্ট্রিমিং', 'seo_keywords' => 'নেটফ্লিক্স'],

                    (object) ['name' => 'স্বাস্থ্য', 'details' => 'স্বাস্থ্য সংবাদ', 'seo_keywords' => 'স্বাস্থ্য'],
                    (object) ['name' => 'চিকিৎসা', 'details' => 'চিকিৎসা সংবাদ', 'seo_keywords' => 'মেডিকেল'],
                    (object) ['name' => 'জনস্বাস্থ্য', 'details' => 'জনস্বাস্থ্য', 'seo_keywords' => 'সচেতনতা'],
                    (object) ['name' => 'রোগ', 'details' => 'রোগ সংবাদ', 'seo_keywords' => 'রোগ'],
                    (object) ['name' => 'পুষ্টি', 'details' => 'পুষ্টি', 'seo_keywords' => 'ডায়েট'],
                    (object) ['name' => 'মানসিক স্বাস্থ্য', 'details' => 'মানসিক স্বাস্থ্য', 'seo_keywords' => 'স্ট্রেস'],

                    (object) ['name' => 'শিক্ষা', 'details' => 'শিক্ষা সংবাদ', 'seo_keywords' => 'শিক্ষা'],
                    (object) ['name' => 'স্কুল', 'details' => 'স্কুল', 'seo_keywords' => 'শিক্ষার্থী'],
                    (object) ['name' => 'বিশ্ববিদ্যালয়', 'details' => 'বিশ্ববিদ্যালয়', 'seo_keywords' => 'উচ্চশিক্ষা'],
                    (object) ['name' => 'পরীক্ষা', 'details' => 'পরীক্ষা', 'seo_keywords' => 'ফলাফল'],
                    (object) ['name' => 'বৃত্তি', 'details' => 'বৃত্তি', 'seo_keywords' => 'স্কলারশিপ'],
                    (object) ['name' => 'শিক্ষানীতি', 'details' => 'নীতি', 'seo_keywords' => 'নীতি'],

                    (object) ['name' => 'জীবনধারা', 'details' => 'লাইফস্টাইল', 'seo_keywords' => 'জীবনধারা'],
                    (object) ['name' => 'ফ্যাশন', 'details' => 'ফ্যাশন', 'seo_keywords' => 'স্টাইল'],
                    (object) ['name' => 'ভ্রমণ', 'details' => 'ভ্রমণ', 'seo_keywords' => 'ট্যুরিজম'],
                    (object) ['name' => 'খাবার', 'details' => 'খাবার', 'seo_keywords' => 'রেসিপি'],
                    (object) ['name' => 'সম্পর্ক', 'details' => 'সম্পর্ক', 'seo_keywords' => 'ভালোবাসা'],
                    (object) ['name' => 'সংস্কৃতি', 'details' => 'সংস্কৃতি', 'seo_keywords' => 'ঐতিহ্য'],

                    (object) ['name' => 'বিজ্ঞান', 'details' => 'বিজ্ঞান সংবাদ', 'seo_keywords' => 'বিজ্ঞান'],
                    (object) ['name' => 'মহাকাশ', 'details' => 'মহাকাশ', 'seo_keywords' => 'স্পেস'],
                    (object) ['name' => 'গবেষণা', 'details' => 'গবেষণা', 'seo_keywords' => 'স্টাডি'],
                    (object) ['name' => 'উদ্ভাবন', 'details' => 'উদ্ভাবন', 'seo_keywords' => 'ইনোভেশন'],
                    (object) ['name' => 'আবিষ্কার', 'details' => 'আবিষ্কার', 'seo_keywords' => 'ডিসকভারি'],

                    (object) ['name' => 'পরিবেশ', 'details' => 'পরিবেশ সংবাদ', 'seo_keywords' => 'পরিবেশ'],
                    (object) ['name' => 'জলবায়ু', 'details' => 'জলবায়ু পরিবর্তন', 'seo_keywords' => 'জলবায়ু'],
                    (object) ['name' => 'দূষণ', 'details' => 'দূষণ', 'seo_keywords' => 'পলিউশন'],
                    (object) ['name' => 'বন্যপ্রাণী', 'details' => 'বন্যপ্রাণী', 'seo_keywords' => 'প্রাণী'],
                    (object) ['name' => 'দুর্যোগ', 'details' => 'প্রাকৃতিক দুর্যোগ', 'seo_keywords' => 'দুর্যোগ'],
                    (object) ['name' => 'টেকসই উন্নয়ন', 'details' => 'টেকসই উন্নয়ন', 'seo_keywords' => 'সবুজ'],
                ]),
            ],

        ]);
    }
}
