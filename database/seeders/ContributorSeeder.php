<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Helpers\SystemHelper;
use App\Models\Contributor;
use App\Models\Language;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContributorSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Contributor::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='contributors'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Contributor::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Contributor::truncate();
        }

        $languages = Language::all()->keyBy('code');

        $contributorsByLanguageGroupsFromStaticData = $this->getContributorsByLanguageGroupsFromStaticData();
        foreach ($contributorsByLanguageGroupsFromStaticData as $contributorsByLanguageGroup) {
            $language = $languages[$contributorsByLanguageGroup->language_code] ?? null;
            foreach ($contributorsByLanguageGroup->contributors as $contributor) {
                Contributor::factory()->state([
                    'name'            => $contributor->name,
                    'brief'           => $contributor->brief ?? null,
                    'profile_details' => $contributor->profile_details ?? null,
                    "language_id"     => $language?->id ?? "1",

                    'seo_title'       => $contributor->name,
                    'seo_brief'       => $contributor->brief ?? null,
                    'seo_keywords'    => $contributor->seo_keywords ?? null,
                ])->create();
            }
        }

        $profileImageUrl = MediaHelper::defaultAuthImage("1:1", "user");
        if ($profileImageUrl) {
            $contributors = Contributor::orderBy("id", "desc")->get();
            foreach ($contributors as $contributor) {
                try {
                    $headers = get_headers($profileImageUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $profileImageExtension = pathinfo($profileImageUrl, PATHINFO_EXTENSION);
                        $profileImageExtension = in_array($profileImageExtension, ["png", "jpg", "jpeg"]) ? $profileImageExtension : "png";
                        $profileImageFileName  = MediaHelper::generateMediaName($contributor->name, $profileImageExtension, 200);
                        $contributor->addMediaFromUrl($profileImageUrl)
                            ->usingName($contributor->name)
                            ->usingFileName($profileImageFileName)
                            ->withCustomProperties(['caption' => $contributor->name, 'alt' => $contributor->name, "role" => MediaHelper::ROLE_PROFILE_IMAGE])
                            ->toMediaCollection($contributor->media_collection_name);
                    } else {
                        Log::info("Image not accessable contributor {$contributor->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for contributor {$contributor->name}: {$ex->getMessage()}");
                }
            }
        }
    }
    private function getContributorsByLanguageGroupsFromStaticData()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::LANGUAGE_DEFAULT_CODE,
                'contributors'       => collect([

                    (object) [
                        'name'            => 'Tahmima Anam',
                        'brief'           => 'Bangladeshi novelist and writer',
                        'profile_details' => '<h3>Tahmima Anam</h3><p>Tahmima Anam is a Bangladeshi novelist and writer known for her contribution to contemporary literature.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Novelist and writer</li><li><strong>Country:</strong> Bangladesh</li></ul>',
                        'seo_keywords'    => 'tahmima anam, bangladesh author, fiction',
                    ],
                    (object) [
                        'name'            => 'Anisul Hoque',
                        'brief'           => 'Author and journalist',
                        'profile_details' => '<h3>Anisul Hoque</h3><p>Anisul Hoque is an author and journalist recognized for his writing, columns, and literary contribution.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Author and journalist</li><li><strong>Field:</strong> Literature and journalism</li></ul>',
                        'seo_keywords'    => 'anisul hoque, bangla literature, columnist',
                    ],
                    (object) [
                        'name'            => 'Zafar Sobhan',
                        'brief'           => 'Editor and columnist',
                        'profile_details' => '<h3>Zafar Sobhan</h3><p>Zafar Sobhan is an editor and columnist associated with journalism, opinion writing, and public commentary.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Editor and columnist</li><li><strong>Field:</strong> Journalism</li></ul>',
                        'seo_keywords'    => 'zafar sobhan, dhaka tribune, journalism',
                    ],
                    (object) [
                        'name'            => 'Syed Manzoorul Islam',
                        'brief'           => 'Writer and academic',
                        'profile_details' => '<h3>Syed Manzoorul Islam</h3><p>Syed Manzoorul Islam is a writer and academic known for his work in literature, education, and cultural discussion.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and academic</li><li><strong>Field:</strong> Literature and education</li></ul>',
                        'seo_keywords'    => 'syed manzoorul islam, bangla writer',
                    ],
                    (object) [
                        'name'            => 'Muhammad Zafar Iqbal',
                        'brief'           => 'Science fiction writer and professor',
                        'profile_details' => '<h3>Muhammad Zafar Iqbal</h3><p>Muhammad Zafar Iqbal is a science fiction writer and professor known for popular science, fiction, and educational writing.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and professor</li><li><strong>Genre:</strong> Science fiction</li></ul>',
                        'seo_keywords'    => 'zafar iqbal, science fiction, bangladesh',
                    ],
                    (object) [
                        'name'            => 'Imdadul Haq Milon',
                        'brief'           => 'Novelist and journalist',
                        'profile_details' => '<h3>Imdadul Haq Milon</h3><p>Imdadul Haq Milon is a novelist and journalist recognized for his contribution to Bangla novels and newstelling.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Novelist and journalist</li><li><strong>Field:</strong> Bangla literature</li></ul>',
                        'seo_keywords'    => 'imdadul haq milon, bangla novel',
                    ],
                    (object) [
                        'name'            => 'Selina Hossain',
                        'brief'           => 'Author and novelist',
                        'profile_details' => '<h3>Selina Hossain</h3><p>Selina Hossain is an author and novelist known for her literary works and contribution to Bangla literature.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Author and novelist</li><li><strong>Field:</strong> Bangla literature</li></ul>',
                        'seo_keywords'    => 'selina hossain, bangla literature',
                    ],
                    (object) [
                        'name'            => 'Farah Ghuznavi',
                        'brief'           => 'Writer and columnist',
                        'profile_details' => '<h3>Farah Ghuznavi</h3><p>Farah Ghuznavi is a writer and columnist known for fiction, essays, and thoughtful commentary.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and columnist</li><li><strong>Field:</strong> Literature and commentary</li></ul>',
                        'seo_keywords'    => 'farah ghuznavi, bangladesh writer',
                    ],
                    (object) [
                        'name'            => 'K. Anis Ahmed',
                        'brief'           => 'Writer and publisher',
                        'profile_details' => '<h3>K. Anis Ahmed</h3><p>K. Anis Ahmed is a writer and publisher associated with literature, publishing, and cultural work.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and publisher</li><li><strong>Field:</strong> Literature and publishing</li></ul>',
                        'seo_keywords'    => 'k anis ahmed, bangladesh author',
                    ],
                    (object) [
                        'name'            => 'Arif Azad',
                        'brief'           => 'Writer and public intellectual',
                        'profile_details' => '<h3>Arif Azad</h3><p>Arif Azad is a writer and public intellectual known for his books, ideas, and public discussions.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and public intellectual</li><li><strong>Field:</strong> Bangla writing</li></ul>',
                        'seo_keywords'    => 'arif azad, bangla author',
                    ],

                    (object) [
                        'name'            => 'Mahfuz Anam',
                        'brief'           => 'Editor and journalist',
                        'profile_details' => '<h3>Mahfuz Anam</h3><p>Mahfuz Anam is an editor and journalist known for his role in newspaper journalism and media leadership.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Editor and journalist</li><li><strong>Field:</strong> Journalism</li></ul>',
                        'seo_keywords'    => 'mahfuz anam, the daily star',
                    ],
                    (object) [
                        'name'            => 'Shykh Seraj',
                        'brief'           => 'Journalist and media personality',
                        'profile_details' => '<h3>Shykh Seraj</h3><p>Shykh Seraj is a journalist and media personality known for agriculture-focused journalism and public communication.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Journalist and media personality</li><li><strong>Field:</strong> Agriculture journalism</li></ul>',
                        'seo_keywords'    => 'shykh seraj, agriculture journalism',
                    ],
                    (object) [
                        'name'            => 'Nurul Kabir',
                        'brief'           => 'Journalist and editor',
                        'profile_details' => '<h3>Nurul Kabir</h3><p>Nurul Kabir is a journalist and editor associated with newspaper journalism, analysis, and editorial leadership.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Journalist and editor</li><li><strong>Field:</strong> Newspaper journalism</li></ul>',
                        'seo_keywords'    => 'nurul kabir, newspaper editor',
                    ],
                    (object) [
                        'name'            => 'Afsan Chowdhury',
                        'brief'           => 'Writer and historian',
                        'profile_details' => '<h3>Afsan Chowdhury</h3><p>Afsan Chowdhury is a writer and historian known for historical writing, research, and analysis.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and historian</li><li><strong>Field:</strong> Hinews and research</li></ul>',
                        'seo_keywords'    => 'afsan chowdhury, bangladesh hinews',
                    ],
                    (object) [
                        'name'            => 'Shahidul Alam',
                        'brief'           => 'Photographer and writer',
                        'profile_details' => '<h3>Shahidul Alam</h3><p>Shahidul Alam is a photographer and writer known for visual newstelling, photography, and social documentation.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Photographer and writer</li><li><strong>Field:</strong> Photography and writing</li></ul>',
                        'seo_keywords'    => 'shahidul alam, drik gallery',
                    ],

                    (object) [
                        'name'            => 'Rahnuma Ahmed',
                        'brief'           => 'Writer and academic',
                        'profile_details' => '<h3>Rahnuma Ahmed</h3><p>Rahnuma Ahmed is a writer and academic known for research, essays, and scholarly contribution.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and academic</li><li><strong>Field:</strong> Research and writing</li></ul>',
                        'seo_keywords'    => 'rahnuma ahmed, bangladesh scholar',
                    ],
                    (object) [
                        'name'            => 'Syed Badrul Ahsan',
                        'brief'           => 'Journalist and columnist',
                        'profile_details' => '<h3>Syed Badrul Ahsan</h3><p>Syed Badrul Ahsan is a journalist and columnist known for political writing, commentary, and analysis.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Journalist and columnist</li><li><strong>Field:</strong> Political analysis</li></ul>',
                        'seo_keywords'    => 'syed badrul ahsan, political analysis',
                    ],
                    (object) [
                        'name'            => 'Syed Ishtiaque Ahmed',
                        'brief'           => 'Researcher and writer',
                        'profile_details' => '<h3>Syed Ishtiaque Ahmed</h3><p>Syed Ishtiaque Ahmed is a researcher and writer associated with academic research, technology, and social impact.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Researcher and writer</li><li><strong>Field:</strong> Computer science research</li></ul>',
                        'seo_keywords'    => 'ishtiaque ahmed, computer science researcher',
                    ],
                    (object) [
                        'name'            => 'Naomi Hossain',
                        'brief'           => 'Academic and development writer',
                        'profile_details' => '<h3>Naomi Hossain</h3><p>Naomi Hossain is an academic and development writer known for work related to development, policy, and society.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Academic and development writer</li><li><strong>Field:</strong> Development studies</li></ul>',
                        'seo_keywords'    => 'naomi hossain, bangladesh development',
                    ],
                    (object) [
                        'name'            => 'Ali Riaz',
                        'brief'           => 'Political scientist and author',
                        'profile_details' => '<h3>Ali Riaz</h3><p>Ali Riaz is a political scientist and author known for research and writing on politics, governance, and society.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Political scientist and author</li><li><strong>Field:</strong> Politics and governance</li></ul>',
                        'seo_keywords'    => 'ali riaz, politics bangladesh',
                    ],

                    (object) [
                        'name'            => 'Niaz Zaman',
                        'brief'           => 'Writer and academic',
                        'profile_details' => '<h3>Niaz Zaman</h3><p>Niaz Zaman is a writer and academic known for literary research, translation, and academic contribution.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and academic</li><li><strong>Field:</strong> Literature research</li></ul>',
                        'seo_keywords'    => 'niaz zaman, bangla literature researcher',
                    ],
                    (object) [
                        'name'            => 'Shahriar Kabir',
                        'brief'           => 'Writer and activist',
                        'profile_details' => '<h3>Shahriar Kabir</h3><p>Shahriar Kabir is a writer and activist known for writing, public engagement, and social commentary.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and activist</li><li><strong>Field:</strong> Writing and activism</li></ul>',
                        'seo_keywords'    => 'shahriar kabir, journalist bangladesh',
                    ],
                    (object) [
                        'name'            => 'Haroon Habib',
                        'brief'           => 'Journalist and writer',
                        'profile_details' => '<h3>Haroon Habib</h3><p>Haroon Habib is a journalist and writer known for reporting, writing, and media contribution.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Journalist and writer</li><li><strong>Field:</strong> Journalism</li></ul>',
                        'seo_keywords'    => 'haroon habib, bangladesh journalism',
                    ],
                    (object) [
                        'name'            => 'Ruhul Amin',
                        'brief'           => 'Journalist and editor',
                        'profile_details' => '<h3>Ruhul Amin</h3><p>Ruhul Amin is a journalist and editor associated with news editing, reporting, and editorial work.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Journalist and editor</li><li><strong>Field:</strong> News editing</li></ul>',
                        'seo_keywords'    => 'ruhul amin, news editor bangladesh',
                    ],
                    (object) [
                        'name'            => 'Tapan Kumar Bose',
                        'brief'           => 'Writer and researcher',
                        'profile_details' => '<h3>Tapan Kumar Bose</h3><p>Tapan Kumar Bose is a writer and researcher known for research-focused writing and regional studies.</p><ul><li><strong>Birth Date:</strong> Not specified</li><li><strong>Gender:</strong> Not specified</li><li><strong>Profession:</strong> Writer and researcher</li><li><strong>Field:</strong> South Asia studies</li></ul>',
                        'seo_keywords'    => 'tapan kumar bose, south asia studies',
                    ],
                ]),
            ],

            (object) [
                'language_code' => SystemHelper::LANGUAGE_EXTRA_BN_CODE,
                'contributors'       => collect([
                    (object) [
                        'name'            => 'তাহমিমা আনাম',
                        'brief'           => 'বাংলাদেশি ঔপন্যাসিক ও লেখিকা',
                        'profile_details' => '<h3>তাহমিমা আনাম</h3><p>তাহমিমা আনাম একজন বাংলাদেশি ঔপন্যাসিক ও লেখিকা, যিনি সমসাময়িক সাহিত্যে অবদানের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> ঔপন্যাসিক ও লেখিকা</li><li><strong>দেশ:</strong> বাংলাদেশ</li></ul>',
                        'seo_keywords'    => 'তাহমিমা আনাম, বাংলাদেশি লেখক, উপন্যাস',
                    ],
                    (object) [
                        'name'            => 'আনিসুল হক',
                        'brief'           => 'লেখক ও সাংবাদিক',
                        'profile_details' => '<h3>আনিসুল হক</h3><p>আনিসুল হক একজন লেখক ও সাংবাদিক, যিনি সাহিত্য, কলাম এবং সাংবাদিকতায় অবদানের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও সাংবাদিক</li><li><strong>ক্ষেত্র:</strong> সাহিত্য ও সাংবাদিকতা</li></ul>',
                        'seo_keywords'    => 'আনিসুল হক, বাংলা সাহিত্য, কলামিস্ট',
                    ],
                    (object) [
                        'name'            => 'জাফর সোবহান',
                        'brief'           => 'সম্পাদক ও কলামিস্ট',
                        'profile_details' => '<h3>জাফর সোবহান</h3><p>জাফর সোবহান একজন সম্পাদক ও কলামিস্ট, যিনি সাংবাদিকতা, মতামতধর্মী লেখা এবং জনআলোচনার সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সম্পাদক ও কলামিস্ট</li><li><strong>ক্ষেত্র:</strong> সাংবাদিকতা</li></ul>',
                        'seo_keywords'    => 'জাফর সোবহান, ঢাকা ট্রিবিউন, সাংবাদিকতা',
                    ],
                    (object) [
                        'name'            => 'সৈয়দ মনজুরুল ইসলাম',
                        'brief'           => 'লেখক ও অধ্যাপক',
                        'profile_details' => '<h3>সৈয়দ মনজুরুল ইসলাম</h3><p>সৈয়দ মনজুরুল ইসলাম একজন লেখক ও অধ্যাপক, যিনি সাহিত্য, শিক্ষা এবং সাংস্কৃতিক আলোচনায় পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও অধ্যাপক</li><li><strong>ক্ষেত্র:</strong> সাহিত্য ও শিক্ষা</li></ul>',
                        'seo_keywords'    => 'সৈয়দ মনজুরুল ইসলাম, বাংলা লেখক',
                    ],
                    (object) [
                        'name'            => 'মুহাম্মদ জাফর ইকবাল',
                        'brief'           => 'বিজ্ঞান কল্পকাহিনী লেখক ও অধ্যাপক',
                        'profile_details' => '<h3>মুহাম্মদ জাফর ইকবাল</h3><p>মুহাম্মদ জাফর ইকবাল একজন বিজ্ঞান কল্পকাহিনী লেখক ও অধ্যাপক, যিনি জনপ্রিয় বিজ্ঞান, গল্প এবং শিক্ষামূলক লেখার জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও অধ্যাপক</li><li><strong>ধরণ:</strong> বিজ্ঞান কল্পকাহিনী</li></ul>',
                        'seo_keywords'    => 'জাফর ইকবাল, বিজ্ঞান কল্পকাহিনী, বাংলাদেশ',
                    ],
                    (object) [
                        'name'            => 'ইমদাদুল হক মিলন',
                        'brief'           => 'ঔপন্যাসিক ও সাংবাদিক',
                        'profile_details' => '<h3>ইমদাদুল হক মিলন</h3><p>ইমদাদুল হক মিলন একজন ঔপন্যাসিক ও সাংবাদিক, যিনি বাংলা উপন্যাস ও গল্প বলার ধারায় অবদান রেখেছেন।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> ঔপন্যাসিক ও সাংবাদিক</li><li><strong>ক্ষেত্র:</strong> বাংলা সাহিত্য</li></ul>',
                        'seo_keywords'    => 'ইমদাদুল হক মিলন, বাংলা উপন্যাস',
                    ],
                    (object) [
                        'name'            => 'সেলিনা হোসেন',
                        'brief'           => 'লেখিকা ও ঔপন্যাসিক',
                        'profile_details' => '<h3>সেলিনা হোসেন</h3><p>সেলিনা হোসেন একজন লেখিকা ও ঔপন্যাসিক, যিনি বাংলা সাহিত্য ও সৃজনশীল লেখায় অবদানের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখিকা ও ঔপন্যাসিক</li><li><strong>ক্ষেত্র:</strong> বাংলা সাহিত্য</li></ul>',
                        'seo_keywords'    => 'সেলিনা হোসেন, বাংলা সাহিত্য',
                    ],
                    (object) [
                        'name'            => 'ফারাহ গজনভী',
                        'brief'           => 'লেখিকা ও কলামিস্ট',
                        'profile_details' => '<h3>ফারাহ গজনভী</h3><p>ফারাহ গজনভী একজন লেখিকা ও কলামিস্ট, যিনি গল্প, প্রবন্ধ এবং মতামতধর্মী লেখার সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখিকা ও কলামিস্ট</li><li><strong>ক্ষেত্র:</strong> সাহিত্য ও কলাম</li></ul>',
                        'seo_keywords'    => 'ফারাহ গজনভী, বাংলাদেশ লেখক',
                    ],
                    (object) [
                        'name'            => 'কে. আনিস আহমেদ',
                        'brief'           => 'লেখক ও প্রকাশক',
                        'profile_details' => '<h3>কে. আনিস আহমেদ</h3><p>কে. আনিস আহমেদ একজন লেখক ও প্রকাশক, যিনি সাহিত্য, প্রকাশনা এবং সাংস্কৃতিক কার্যক্রমের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও প্রকাশক</li><li><strong>ক্ষেত্র:</strong> সাহিত্য ও প্রকাশনা</li></ul>',
                        'seo_keywords'    => 'কে আনিস আহমেদ, বাংলাদেশ লেখক',
                    ],
                    (object) [
                        'name'            => 'আরিফ আজাদ',
                        'brief'           => 'লেখক ও বুদ্ধিজীবী',
                        'profile_details' => '<h3>আরিফ আজাদ</h3><p>আরিফ আজাদ একজন লেখক ও বুদ্ধিজীবী, যিনি বই, চিন্তা এবং জনআলোচনার জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও বুদ্ধিজীবী</li><li><strong>ক্ষেত্র:</strong> বাংলা লেখা</li></ul>',
                        'seo_keywords'    => 'আরিফ আজাদ, বাংলা লেখক',
                    ],

                    (object) [
                        'name'            => 'মাহফুজ আনাম',
                        'brief'           => 'সম্পাদক ও সাংবাদিক',
                        'profile_details' => '<h3>মাহফুজ আনাম</h3><p>মাহফুজ আনাম একজন সম্পাদক ও সাংবাদিক, যিনি পত্রিকা সাংবাদিকতা এবং গণমাধ্যম নেতৃত্বের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সম্পাদক ও সাংবাদিক</li><li><strong>ক্ষেত্র:</strong> সাংবাদিকতা</li></ul>',
                        'seo_keywords'    => 'মাহফুজ আনাম, দ্য ডেইলি স্টার',
                    ],
                    (object) [
                        'name'            => 'শাইখ সিরাজ',
                        'brief'           => 'সাংবাদিক ও মিডিয়া ব্যক্তিত্ব',
                        'profile_details' => '<h3>শাইখ সিরাজ</h3><p>শাইখ সিরাজ একজন সাংবাদিক ও মিডিয়া ব্যক্তিত্ব, যিনি কৃষিভিত্তিক সাংবাদিকতা ও জনযোগাযোগের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সাংবাদিক ও মিডিয়া ব্যক্তিত্ব</li><li><strong>ক্ষেত্র:</strong> কৃষি সাংবাদিকতা</li></ul>',
                        'seo_keywords'    => 'শাইখ সিরাজ, কৃষি সাংবাদিকতা',
                    ],
                    (object) [
                        'name'            => 'নুরুল কবির',
                        'brief'           => 'সাংবাদিক ও সম্পাদক',
                        'profile_details' => '<h3>নুরুল কবির</h3><p>নুরুল কবির একজন সাংবাদিক ও সম্পাদক, যিনি পত্রিকা সাংবাদিকতা, বিশ্লেষণ এবং সম্পাদকীয় কাজের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সাংবাদিক ও সম্পাদক</li><li><strong>ক্ষেত্র:</strong> পত্রিকা সাংবাদিকতা</li></ul>',
                        'seo_keywords'    => 'নুরুল কবির, পত্রিকা সম্পাদক',
                    ],
                    (object) [
                        'name'            => 'আফসান চৌধুরী',
                        'brief'           => 'লেখক ও ইতিহাসবিদ',
                        'profile_details' => '<h3>আফসান চৌধুরী</h3><p>আফসান চৌধুরী একজন লেখক ও ইতিহাসবিদ, যিনি ইতিহাসভিত্তিক লেখা, গবেষণা এবং বিশ্লেষণের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও ইতিহাসবিদ</li><li><strong>ক্ষেত্র:</strong> ইতিহাস ও গবেষণা</li></ul>',
                        'seo_keywords'    => 'আফসান চৌধুরী, বাংলাদেশ ইতিহাস',
                    ],
                    (object) [
                        'name'            => 'শাহিদুল আলম',
                        'brief'           => 'ফটোগ্রাফার ও লেখক',
                        'profile_details' => '<h3>শাহিদুল আলম</h3><p>শাহিদুল আলম একজন ফটোগ্রাফার ও লেখক, যিনি ভিজ্যুয়াল গল্প বলা, ফটোগ্রাফি এবং সামাজিক দলিলীকরণের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> ফটোগ্রাফার ও লেখক</li><li><strong>ক্ষেত্র:</strong> ফটোগ্রাফি ও লেখা</li></ul>',
                        'seo_keywords'    => 'শাহিদুল আলম, ড্রিক গ্যালারি',
                    ],

                    (object) [
                        'name'            => 'রহনুমা আহমেদ',
                        'brief'           => 'লেখিকা ও গবেষক',
                        'profile_details' => '<h3>রহনুমা আহমেদ</h3><p>রহনুমা আহমেদ একজন লেখিকা ও গবেষক, যিনি গবেষণা, প্রবন্ধ এবং বিদ্যাচর্চার সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখিকা ও গবেষক</li><li><strong>ক্ষেত্র:</strong> গবেষণা ও লেখা</li></ul>',
                        'seo_keywords'    => 'রহানুমা আহমেদ, বাংলাদেশ গবেষক',
                    ],
                    (object) [
                        'name'            => 'সৈয়দ বদরুল আহসান',
                        'brief'           => 'সাংবাদিক ও কলামিস্ট',
                        'profile_details' => '<h3>সৈয়দ বদরুল আহসান</h3><p>সৈয়দ বদরুল আহসান একজন সাংবাদিক ও কলামিস্ট, যিনি রাজনৈতিক লেখা, মন্তব্য এবং বিশ্লেষণের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সাংবাদিক ও কলামিস্ট</li><li><strong>ক্ষেত্র:</strong> রাজনৈতিক বিশ্লেষণ</li></ul>',
                        'seo_keywords'    => 'সৈয়দ বদরুল আহসান, রাজনৈতিক বিশ্লেষণ',
                    ],
                    (object) [
                        'name'            => 'সৈয়দ ইশতিয়াক আহমেদ',
                        'brief'           => 'গবেষক ও লেখক',
                        'profile_details' => '<h3>সৈয়দ ইশতিয়াক আহমেদ</h3><p>সৈয়দ ইশতিয়াক আহমেদ একজন গবেষক ও লেখক, যিনি একাডেমিক গবেষণা, প্রযুক্তি এবং সামাজিক প্রভাবের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> গবেষক ও লেখক</li><li><strong>ক্ষেত্র:</strong> কম্পিউটার বিজ্ঞান গবেষণা</li></ul>',
                        'seo_keywords'    => 'ইশতিয়াক আহমেদ, কম্পিউটার বিজ্ঞান গবেষক',
                    ],
                    (object) [
                        'name'            => 'নাওমি হোসেন',
                        'brief'           => 'শিক্ষাবিদ ও উন্নয়ন লেখিকা',
                        'profile_details' => '<h3>নাওমি হোসেন</h3><p>নাওমি হোসেন একজন শিক্ষাবিদ ও উন্নয়ন লেখিকা, যিনি উন্নয়ন, নীতি এবং সমাজবিষয়ক লেখার সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> শিক্ষাবিদ ও উন্নয়ন লেখিকা</li><li><strong>ক্ষেত্র:</strong> উন্নয়ন অধ্যয়ন</li></ul>',
                        'seo_keywords'    => 'নাওমি হোসেন, বাংলাদেশ উন্নয়ন',
                    ],
                    (object) [
                        'name'            => 'আলী রিয়াজ',
                        'brief'           => 'রাষ্ট্রবিজ্ঞানী ও লেখক',
                        'profile_details' => '<h3>আলী রিয়াজ</h3><p>আলী রিয়াজ একজন রাষ্ট্রবিজ্ঞানী ও লেখক, যিনি রাজনীতি, শাসনব্যবস্থা এবং সমাজ নিয়ে গবেষণা ও লেখালেখির জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> রাষ্ট্রবিজ্ঞানী ও লেখক</li><li><strong>ক্ষেত্র:</strong> রাজনীতি ও শাসনব্যবস্থা</li></ul>',
                        'seo_keywords'    => 'আলী রিয়াজ, বাংলাদেশ রাজনীতি',
                    ],

                    (object) [
                        'name'            => 'নিয়াজ জামান',
                        'brief'           => 'লেখিকা ও গবেষক',
                        'profile_details' => '<h3>নিয়াজ জামান</h3><p>নিয়াজ জামান একজন লেখিকা ও গবেষক, যিনি সাহিত্য গবেষণা, অনুবাদ এবং একাডেমিক অবদানের জন্য পরিচিত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখিকা ও গবেষক</li><li><strong>ক্ষেত্র:</strong> সাহিত্য গবেষণা</li></ul>',
                        'seo_keywords'    => 'নিয়াজ জামান, বাংলা সাহিত্য গবেষক',
                    ],
                    (object) [
                        'name'            => 'শাহরিয়ার কবির',
                        'brief'           => 'লেখক ও মানবাধিকার কর্মী',
                        'profile_details' => '<h3>শাহরিয়ার কবির</h3><p>শাহরিয়ার কবির একজন লেখক ও মানবাধিকার কর্মী, যিনি লেখা, জনসম্পৃক্ততা এবং সামাজিক আলোচনার সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও মানবাধিকার কর্মী</li><li><strong>ক্ষেত্র:</strong> লেখা ও সমাজকর্ম</li></ul>',
                        'seo_keywords'    => 'শাহরিয়ার কবির, সাংবাদিক বাংলাদেশ',
                    ],
                    (object) [
                        'name'            => 'হারুন হাবিব',
                        'brief'           => 'সাংবাদিক ও লেখক',
                        'profile_details' => '<h3>হারুন হাবিব</h3><p>হারুন হাবিব একজন সাংবাদিক ও লেখক, যিনি রিপোর্টিং, লেখালেখি এবং গণমাধ্যমে অবদানের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সাংবাদিক ও লেখক</li><li><strong>ক্ষেত্র:</strong> সাংবাদিকতা</li></ul>',
                        'seo_keywords'    => 'হারুন হাবিব, বাংলাদেশ সাংবাদিকতা',
                    ],
                    (object) [
                        'name'            => 'রুহুল আমিন',
                        'brief'           => 'সাংবাদিক ও সম্পাদক',
                        'profile_details' => '<h3>রুহুল আমিন</h3><p>রুহুল আমিন একজন সাংবাদিক ও সম্পাদক, যিনি সংবাদ সম্পাদনা, রিপোর্টিং এবং সম্পাদকীয় কাজের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> সাংবাদিক ও সম্পাদক</li><li><strong>ক্ষেত্র:</strong> সংবাদ সম্পাদনা</li></ul>',
                        'seo_keywords'    => 'রুহুল আমিন, সংবাদ সম্পাদক বাংলাদেশ',
                    ],
                    (object) [
                        'name'            => 'তপন কুমার বসু',
                        'brief'           => 'লেখক ও গবেষক',
                        'profile_details' => '<h3>তপন কুমার বসু</h3><p>তপন কুমার বসু একজন লেখক ও গবেষক, যিনি গবেষণামূলক লেখা এবং আঞ্চলিক অধ্যয়নের সঙ্গে যুক্ত।</p><ul><li><strong>জন্ম তারিখ:</strong> উল্লেখ করা হয়নি</li><li><strong>লিঙ্গ:</strong> উল্লেখ করা হয়নি</li><li><strong>পেশা:</strong> লেখক ও গবেষক</li><li><strong>ক্ষেত্র:</strong> দক্ষিণ এশিয়া গবেষণা</li></ul>',
                        'seo_keywords'    => 'তপন কুমার বসু, দক্ষিণ এশিয়া গবেষণা',
                    ],
                ]),
            ],

        ]);
    }
}
