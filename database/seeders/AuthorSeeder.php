<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Helpers\SystemHelper;
use App\Models\Author;
use App\Models\Language;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Author::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='authors'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Author::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Author::truncate();
        }

        $authors = collect([

            (object) ['name' => 'Tahmima Anam', 'details' => 'Bangladeshi novelist and writer', 'seo_keywords' => 'tahmima anam, bangladesh author, fiction'],
            (object) ['name' => 'Anisul Hoque', 'details' => 'Author and journalist', 'seo_keywords' => 'anisul hoque, bangla literature, columnist'],
            (object) ['name' => 'Zafar Sobhan', 'details' => 'Editor and columnist', 'seo_keywords' => 'zafar sobhan, dhaka tribune, journalism'],
            (object) ['name' => 'Syed Manzoorul Islam', 'details' => 'Writer and academic', 'seo_keywords' => 'syed manzoorul islam, bangla writer'],
            (object) ['name' => 'Muhammad Zafar Iqbal', 'details' => 'Science fiction writer and professor', 'seo_keywords' => 'zafar iqbal, science fiction, bangladesh'],
            (object) ['name' => 'Imdadul Haq Milon', 'details' => 'Novelist and journalist', 'seo_keywords' => 'imdadul haq milon, bangla novel'],
            (object) ['name' => 'Selina Hossain', 'details' => 'Author and novelist', 'seo_keywords' => 'selina hossain, bangla literature'],
            (object) ['name' => 'Farah Ghuznavi', 'details' => 'Writer and columnist', 'seo_keywords' => 'farah ghuznavi, bangladesh writer'],
            (object) ['name' => 'K. Anis Ahmed', 'details' => 'Writer and publisher', 'seo_keywords' => 'k anis ahmed, bangladesh author'],
            (object) ['name' => 'Arif Azad', 'details' => 'Writer and public intellectual', 'seo_keywords' => 'arif azad, bangla author'],

            (object) ['name' => 'Mahfuz Anam', 'details' => 'Editor and journalist', 'seo_keywords' => 'mahfuz anam, the daily star'],
            (object) ['name' => 'Shykh Seraj', 'details' => 'Journalist and media personality', 'seo_keywords' => 'shykh seraj, agriculture journalism'],
            (object) ['name' => 'Nurul Kabir', 'details' => 'Journalist and editor', 'seo_keywords' => 'nurul kabir, newspaper editor'],
            (object) ['name' => 'Afsan Chowdhury', 'details' => 'Writer and historian', 'seo_keywords' => 'afsan chowdhury, bangladesh history'],
            (object) ['name' => 'Shahidul Alam', 'details' => 'Photographer and writer', 'seo_keywords' => 'shahidul alam, drik gallery'],

            (object) ['name' => 'Rahnuma Ahmed', 'details' => 'Writer and academic', 'seo_keywords' => 'rahnuma ahmed, bangladesh scholar'],
            (object) ['name' => 'Syed Badrul Ahsan', 'details' => 'Journalist and columnist', 'seo_keywords' => 'syed badrul ahsan, political analysis'],
            (object) ['name' => 'Syed Ishtiaque Ahmed', 'details' => 'Researcher and writer', 'seo_keywords' => 'ishtiaque ahmed, computer science researcher'],
            (object) ['name' => 'Naomi Hossain', 'details' => 'Academic and development writer', 'seo_keywords' => 'naomi hossain, bangladesh development'],
            (object) ['name' => 'Ali Riaz', 'details' => 'Political scientist and author', 'seo_keywords' => 'ali riaz, politics bangladesh'],

            (object) ['name' => 'Niaz Zaman', 'details' => 'Writer and academic', 'seo_keywords' => 'niaz zaman, bangla literature researcher'],
            (object) ['name' => 'Shahriar Kabir', 'details' => 'Writer and activist', 'seo_keywords' => 'shahriar kabir, journalist bangladesh'],
            (object) ['name' => 'Haroon Habib', 'details' => 'Journalist and writer', 'seo_keywords' => 'haroon habib, bangladesh journalism'],
            (object) ['name' => 'Ruhul Amin', 'details' => 'Journalist and editor', 'seo_keywords' => 'ruhul amin, news editor bangladesh'],
            (object) ['name' => 'Tapan Kumar Bose', 'details' => 'Writer and researcher', 'seo_keywords' => 'tapan kumar bose, south asia studies'],

        ]);
        $language = Language::where("code", SystemHelper::DEFAULT_LANGUAGE_CODE)->first() ?? null;

        foreach ($authors as $author) {
            Author::factory()->state([
                'name'         => $author->name,
                'details'      => $author->details ?? null,
                "language_id"   => $language?->id ?? "1",

                'seo_title'    => $author->name,
                'seo_brief'    => $author->details ?? null,
                'seo_keywords' => $author->seo_keywords ?? null,
            ])->create();

        }

        $profileImageUrl = MediaHelper::defaultAuthImage("1:1", "user");
        if ($profileImageUrl) {
            $authors = Author::orderBy("id", "desc")->get();
            foreach ($authors as $author) {
                try {
                    $headers = get_headers($profileImageUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $profileImageExtension = pathinfo($profileImageUrl, PATHINFO_EXTENSION);
                        $profileImageExtension = in_array($profileImageExtension, ["png", "jpg", "jpeg"]) ? $profileImageExtension : "png";
                        $profileImageFileName  = MediaHelper::generateMediaName($author->name, $profileImageExtension, 200);
                        $author->addMediaFromUrl($profileImageUrl)
                            ->usingName($author->name)
                            ->usingFileName($profileImageFileName)
                            ->withCustomProperties(['caption' => $author->name, 'alt' => $author->name, "role" => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE])
                            ->toMediaCollection($author->media_collection_name);
                    } else {
                        Log::info("Image not accessable author {$author->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for author {$author->name}: {$ex->getMessage()}");
                }
            }
        }
    }
}
