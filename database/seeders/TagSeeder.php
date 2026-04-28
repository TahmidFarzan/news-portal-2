<?php
namespace Database\Seeders;

use App\Models\Language;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\SystemHelper;

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

        $language = Language::where("code", SystemHelper::DEFAULT_LANGUAGE_CODE)->first() ?? null;
        $tags     = collect([

            (object) ['name' => 'National', 'details' => 'National news', 'seo_keywords' => 'national news, country updates'],
            (object) ['name' => 'Politics', 'details' => 'Political news', 'seo_keywords' => 'politics, elections, parliament'],
            (object) ['name' => 'Government', 'details' => 'Government news', 'seo_keywords' => 'government, ministry'],
            (object) ['name' => 'Law & Order', 'details' => 'Law enforcement', 'seo_keywords' => 'crime, police, law'],
            (object) ['name' => 'Elections', 'details' => 'Election coverage', 'seo_keywords' => 'elections, voting'],
            (object) ['name' => 'Public Policy', 'details' => 'Policy updates', 'seo_keywords' => 'policy, reforms'],

            // International
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

        ]);

        foreach ($tags as $tag) {
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
