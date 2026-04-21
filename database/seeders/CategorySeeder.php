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
            (object) [
                'name'           => 'National',
                'details'        => 'National news and updates',
                'sub_categories' => collect([
                    (object) ['name' => 'Politics', 'details' => 'Political news'],
                    (object) ['name' => 'Government', 'details' => 'Government activities'],
                    (object) ['name' => 'Law & Order', 'details' => 'Law enforcement news'],
                    (object) ['name' => 'Elections', 'details' => 'Election coverage'],
                    (object) ['name' => 'Public Policy', 'details' => 'Policy updates'],
                ]),
            ],
            (object) [
                'name'           => 'International',
                'details'        => 'Global news coverage',
                'sub_categories' => collect([
                    (object) ['name' => 'Global Politics', 'details' => 'World politics'],
                    (object) ['name' => 'Diplomacy', 'details' => 'Diplomatic relations'],
                    (object) ['name' => 'Conflicts', 'details' => 'Global conflicts'],
                    (object) ['name' => 'World Economy', 'details' => 'International economy'],
                    (object) ['name' => 'International Organizations', 'details' => 'UN, WTO, etc'],
                ]),
            ],
            (object) [
                'name'           => 'Business',
                'details'        => 'Business and finance news',
                'sub_categories' => collect([
                    (object) ['name' => 'Stock Market', 'details' => 'Stock updates'],
                    (object) ['name' => 'Banking', 'details' => 'Banking sector'],
                    (object) ['name' => 'Corporate', 'details' => 'Corporate news'],
                    (object) ['name' => 'Startups', 'details' => 'Startup ecosystem'],
                    (object) ['name' => 'Trade', 'details' => 'Trade news'],
                ]),
            ],
            (object) [
                'name'           => 'Technology',
                'details'        => 'Technology news',
                'sub_categories' => collect([
                    (object) ['name' => 'AI', 'details' => 'Artificial Intelligence'],
                    (object) ['name' => 'Gadgets', 'details' => 'Tech gadgets'],
                    (object) ['name' => 'Software', 'details' => 'Software news'],
                    (object) ['name' => 'Internet', 'details' => 'Internet trends'],
                    (object) ['name' => 'Cybersecurity', 'details' => 'Security news'],
                ]),
            ],
            (object) [
                'name'           => 'Sports',
                'details'        => 'Sports news',
                'sub_categories' => collect([
                    (object) ['name' => 'Cricket', 'details' => 'Cricket news'],
                    (object) ['name' => 'Football', 'details' => 'Football news'],
                    (object) ['name' => 'Tennis', 'details' => 'Tennis news'],
                    (object) ['name' => 'Local Sports', 'details' => 'Local games'],
                    (object) ['name' => 'International Sports', 'details' => 'Global sports'],
                ]),
            ],
            (object) [
                'name'           => 'Entertainment',
                'details'        => 'Entertainment news',
                'sub_categories' => collect([
                    (object) ['name' => 'Movies', 'details' => 'Movie news'],
                    (object) ['name' => 'Music', 'details' => 'Music industry'],
                    (object) ['name' => 'TV Shows', 'details' => 'TV content'],
                    (object) ['name' => 'Celebrities', 'details' => 'Celebrity news'],
                    (object) ['name' => 'OTT', 'details' => 'Streaming platforms'],
                ]),
            ],
            (object) [
                'name'           => 'Health',
                'details'        => 'Health and medical news',
                'sub_categories' => collect([
                    (object) ['name' => 'Medical News', 'details' => 'Medical updates'],
                    (object) ['name' => 'Public Health', 'details' => 'Public health info'],
                    (object) ['name' => 'Diseases', 'details' => 'Disease news'],
                    (object) ['name' => 'Nutrition', 'details' => 'Food & nutrition'],
                    (object) ['name' => 'Mental Health', 'details' => 'Mental wellness'],
                ]),
            ],
            (object) [
                'name'           => 'Education',
                'details'        => 'Education news',
                'sub_categories' => collect([
                    (object) ['name' => 'Schools', 'details' => 'School updates'],
                    (object) ['name' => 'Universities', 'details' => 'University news'],
                    (object) ['name' => 'Exams', 'details' => 'Exam info'],
                    (object) ['name' => 'Scholarships', 'details' => 'Scholarship news'],
                    (object) ['name' => 'Education Policy', 'details' => 'Policy updates'],
                ]),
            ],
            (object) [
                'name'           => 'Lifestyle',
                'details'        => 'Lifestyle news',
                'sub_categories' => collect([
                    (object) ['name' => 'Fashion', 'details' => 'Fashion trends'],
                    (object) ['name' => 'Travel', 'details' => 'Travel news'],
                    (object) ['name' => 'Food', 'details' => 'Food culture'],
                    (object) ['name' => 'Relationships', 'details' => 'Relationship advice'],
                    (object) ['name' => 'Culture', 'details' => 'Cultural news'],
                ]),
            ],
            (object) [
                'name'           => 'Science',
                'details'        => 'Science news',
                'sub_categories' => collect([
                    (object) ['name' => 'Space', 'details' => 'Space exploration'],
                    (object) ['name' => 'Research', 'details' => 'Scientific research'],
                    (object) ['name' => 'Innovation', 'details' => 'Innovations'],
                    (object) ['name' => 'Technology Research', 'details' => 'Tech science'],
                    (object) ['name' => 'Scientific Discoveries', 'details' => 'New discoveries'],
                ]),
            ],
            (object) [
                'name'           => 'Environment',
                'details'        => 'Environment news',
                'sub_categories' => collect([
                    (object) ['name' => 'Climate Change', 'details' => 'Climate issues'],
                    (object) ['name' => 'Pollution', 'details' => 'Pollution news'],
                    (object) ['name' => 'Wildlife', 'details' => 'Wildlife updates'],
                    (object) ['name' => 'Natural Disasters', 'details' => 'Disaster news'],
                    (object) ['name' => 'Sustainability', 'details' => 'Sustainability'],
                ]),
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
        ])->create();

        if (! empty($category->sub_categories)) {
            foreach ($category->sub_categories as $subCategory) {
                $this->createCategory($saveCategory, $subCategory, $language);
            }
        }

        return $saveCategory;
    }
}
