<?php

namespace Database\Seeders;

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

        $language = Language::where("code", "en_us")->first() ?? null;

        $categories = collect([

            (object)[
                'name' => 'National',
                'details' => 'National news and updates',
                'seo_keywords' => 'national news, politics, government, law order, elections, policy',
                'sub_categories' => collect([
                    (object)['name'=>'Politics','details'=>'Political news','seo_keywords'=>'politics news, elections, parliament, governance'],
                    (object)['name'=>'Government','details'=>'Government activities','seo_keywords'=>'government news, ministries, public administration'],
                    (object)['name'=>'Law & Order','details'=>'Law enforcement news','seo_keywords'=>'law order, police, crime news, security'],
                    (object)['name'=>'Elections','details'=>'Election coverage','seo_keywords'=>'elections, voting, candidates, election results'],
                    (object)['name'=>'Public Policy','details'=>'Policy updates','seo_keywords'=>'public policy, reforms, legislation'],
                ])
            ],

            (object)[
                'name' => 'International',
                'details' => 'Global news coverage',
                'seo_keywords' => 'international news, world news, global politics, diplomacy, economy',
                'sub_categories' => collect([
                    (object)['name'=>'Global Politics','details'=>'World politics','seo_keywords'=>'global politics, world leaders, geopolitics'],
                    (object)['name'=>'Diplomacy','details'=>'Diplomatic relations','seo_keywords'=>'diplomacy, foreign relations, international talks'],
                    (object)['name'=>'Conflicts','details'=>'Global conflicts','seo_keywords'=>'war, conflicts, global crisis'],
                    (object)['name'=>'World Economy','details'=>'International economy','seo_keywords'=>'global economy, trade, financial markets'],
                    (object)['name'=>'International Organizations','details'=>'UN, WTO, etc','seo_keywords'=>'UN, WTO, NGOs, global organizations'],
                ])
            ],

            (object)[
                'name' => 'Business',
                'details' => 'Business and finance news',
                'seo_keywords' => 'business news, finance, corporate, startups, trade',
                'sub_categories' => collect([
                    (object)['name'=>'Stock Market','details'=>'Stock updates','seo_keywords'=>'stock market, shares, trading, investment'],
                    (object)['name'=>'Banking','details'=>'Banking sector','seo_keywords'=>'banking, loans, financial institutions'],
                    (object)['name'=>'Corporate','details'=>'Corporate news','seo_keywords'=>'corporate news, companies, CEOs'],
                    (object)['name'=>'Startups','details'=>'Startup ecosystem','seo_keywords'=>'startups, entrepreneurship, innovation'],
                    (object)['name'=>'Trade','details'=>'Trade news','seo_keywords'=>'trade, imports, exports, commerce'],
                ])
            ],

            (object)[
                'name' => 'Technology',
                'details' => 'Technology news',
                'seo_keywords' => 'technology, AI, gadgets, software, cybersecurity',
                'sub_categories' => collect([
                    (object)['name'=>'AI','details'=>'Artificial Intelligence','seo_keywords'=>'AI, machine learning, automation'],
                    (object)['name'=>'Gadgets','details'=>'Tech gadgets','seo_keywords'=>'gadgets, smartphones, devices'],
                    (object)['name'=>'Software','details'=>'Software news','seo_keywords'=>'software, apps, development'],
                    (object)['name'=>'Internet','details'=>'Internet trends','seo_keywords'=>'internet, social media, web'],
                    (object)['name'=>'Cybersecurity','details'=>'Security news','seo_keywords'=>'cybersecurity, hacking, data protection'],
                ])
            ],

            (object)[
                'name' => 'Sports',
                'details' => 'Sports news',
                'seo_keywords' => 'sports news, cricket, football, tennis, games',
                'sub_categories' => collect([
                    (object)['name'=>'Cricket','details'=>'Cricket news','seo_keywords'=>'cricket, matches, ICC, players'],
                    (object)['name'=>'Football','details'=>'Football news','seo_keywords'=>'football, FIFA, leagues'],
                    (object)['name'=>'Tennis','details'=>'Tennis news','seo_keywords'=>'tennis, ATP, tournaments'],
                    (object)['name'=>'Local Sports','details'=>'Local games','seo_keywords'=>'local sports, regional games'],
                    (object)['name'=>'International Sports','details'=>'Global sports','seo_keywords'=>'international sports, world tournaments'],
                ])
            ],

            (object)[
                'name' => 'Entertainment',
                'details' => 'Entertainment news',
                'seo_keywords' => 'entertainment, movies, music, celebrities, OTT',
                'sub_categories' => collect([
                    (object)['name'=>'Movies','details'=>'Movie news','seo_keywords'=>'movies, cinema, film industry'],
                    (object)['name'=>'Music','details'=>'Music industry','seo_keywords'=>'music, songs, artists'],
                    (object)['name'=>'TV Shows','details'=>'TV content','seo_keywords'=>'tv shows, series, drama'],
                    (object)['name'=>'Celebrities','details'=>'Celebrity news','seo_keywords'=>'celebrities, actors, influencers'],
                    (object)['name'=>'OTT','details'=>'Streaming platforms','seo_keywords'=>'OTT, netflix, streaming'],
                ])
            ],

            (object)[
                'name' => 'Health',
                'details' => 'Health and medical news',
                'seo_keywords' => 'health, medical, diseases, nutrition, mental health',
                'sub_categories' => collect([
                    (object)['name'=>'Medical News','details'=>'Medical updates','seo_keywords'=>'medical news, hospitals, treatments'],
                    (object)['name'=>'Public Health','details'=>'Public health info','seo_keywords'=>'public health, awareness, safety'],
                    (object)['name'=>'Diseases','details'=>'Disease news','seo_keywords'=>'diseases, outbreaks, infections'],
                    (object)['name'=>'Nutrition','details'=>'Food & nutrition','seo_keywords'=>'nutrition, diet, healthy food'],
                    (object)['name'=>'Mental Health','details'=>'Mental wellness','seo_keywords'=>'mental health, stress, wellness'],
                ])
            ],

            (object)[
                'name' => 'Education',
                'details' => 'Education news',
                'seo_keywords' => 'education, schools, universities, exams, scholarships',
                'sub_categories' => collect([
                    (object)['name'=>'Schools','details'=>'School updates','seo_keywords'=>'schools, students, education system'],
                    (object)['name'=>'Universities','details'=>'University news','seo_keywords'=>'universities, higher education'],
                    (object)['name'=>'Exams','details'=>'Exam info','seo_keywords'=>'exams, results, tests'],
                    (object)['name'=>'Scholarships','details'=>'Scholarship news','seo_keywords'=>'scholarships, funding, grants'],
                    (object)['name'=>'Education Policy','details'=>'Policy updates','seo_keywords'=>'education policy, reforms'],
                ])
            ],

            (object)[
                'name' => 'Lifestyle',
                'details' => 'Lifestyle news',
                'seo_keywords' => 'lifestyle, fashion, travel, food, culture',
                'sub_categories' => collect([
                    (object)['name'=>'Fashion','details'=>'Fashion trends','seo_keywords'=>'fashion, style, trends'],
                    (object)['name'=>'Travel','details'=>'Travel news','seo_keywords'=>'travel, tourism, destinations'],
                    (object)['name'=>'Food','details'=>'Food culture','seo_keywords'=>'food, recipes, cuisine'],
                    (object)['name'=>'Relationships','details'=>'Relationship advice','seo_keywords'=>'relationships, advice, love'],
                    (object)['name'=>'Culture','details'=>'Cultural news','seo_keywords'=>'culture, traditions, arts'],
                ])
            ],

            (object)[
                'name' => 'Science',
                'details' => 'Science news',
                'seo_keywords' => 'science, research, innovation, space, discoveries',
                'sub_categories' => collect([
                    (object)['name'=>'Space','details'=>'Space exploration','seo_keywords'=>'space, NASA, astronomy'],
                    (object)['name'=>'Research','details'=>'Scientific research','seo_keywords'=>'research, studies, experiments'],
                    (object)['name'=>'Innovation','details'=>'Innovations','seo_keywords'=>'innovation, new tech'],
                    (object)['name'=>'Technology Research','details'=>'Tech science','seo_keywords'=>'tech research, R&D'],
                    (object)['name'=>'Scientific Discoveries','details'=>'New discoveries','seo_keywords'=>'discoveries, science breakthroughs'],
                ])
            ],

            (object)[
                'name' => 'Environment',
                'details' => 'Environment news',
                'seo_keywords' => 'environment, climate change, pollution, wildlife, sustainability',
                'sub_categories' => collect([
                    (object)['name'=>'Climate Change','details'=>'Climate issues','seo_keywords'=>'climate change, global warming'],
                    (object)['name'=>'Pollution','details'=>'Pollution news','seo_keywords'=>'pollution, air quality, environment'],
                    (object)['name'=>'Wildlife','details'=>'Wildlife updates','seo_keywords'=>'wildlife, animals, conservation'],
                    (object)['name'=>'Natural Disasters','details'=>'Disaster news','seo_keywords'=>'disasters, floods, earthquakes'],
                    (object)['name'=>'Sustainability','details'=>'Sustainability','seo_keywords'=>'sustainability, green energy'],
                ])
            ],

        ]);

        foreach ($categories as $category) {
            $this->createCategory(null, $category, $language);
        }
    }

    public function createCategory(?Category $parent, $category, $language): Category
    {
        $saveCategory = Category::factory()->state([
            'name'        => $category->name,
            'details'     => $category->details ?? null,
            'parent_id'   => $parent?->id,
            'language_id' => $language?->id,
            'name_tree'   => ($parent ? $parent->name . ' - ' : '') . $category->name,
            'slug_tree'   => ($parent ? $parent->slug . '/' : '') . Str::slug($category->name),
            'seo_title'   => $category->name,
            'seo_brief'   => $category->details ?? null,
            'seo_keywords'=> $category->seo_keywords ?? null,
        ])->create();

        if (!empty($category->sub_categories)) {
            foreach ($category->sub_categories as $subCategory) {
                $this->createCategory($saveCategory, $subCategory, $language);
            }
        }

        return $saveCategory;
    }
}
