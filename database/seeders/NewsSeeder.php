<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Helpers\NewsHelper;
use App\Helpers\SystemHelper;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsType;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            News::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='newses'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            News::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            News::truncate();
        }

        $languages = Language::get();

        foreach ($languages as $language) {
            $parentCategoryIds          = Category::where("language_id", $language->id)->inRandomOrder()->whereNull("parent_id")->pluck("id");
            $randomNonParentCategoryIds = Category::where("language_id", $language->id)->inRandomOrder()->whereNotNull("parent_id")->limit(7)->pluck("id");

            $categoryIds = $parentCategoryIds
                ->merge($randomNonParentCategoryIds)
                ->unique()
                ->values();

            $categories     = Category::where("language_id", $language->id)->inRandomOrder()->whereIn("id", $categoryIds)->get();
            $tagIds         = $this->getRandomTagIds($language) ?? [];
            $event          = $this->getRandomEvent($language) ?? null;
            $contributorIds = $this->getRandomContributorIds($language) ?? [];
            $randomNewses   = $this->randomNewses($language, 13);

            foreach ($categories as $category) {
                $location = $this->getRandomLocation($language, $category) ?? null;

                $newsType = NewsType::inRandomOrder()->first();

                foreach ($randomNewses as $index => $randomNews) {

                    $news = News::factory()->state([
                        "news_type_id"     => $newsType->id,
                        "language_id"      => $language?->id ?? "1",
                        "category_id"      => $category?->id ?? "1",

                        "event_id"         => $event?->id ?? null,
                        "location_id"      => $location?->id ?? null,

                        "title"            => $randomNews->title,
                        "sub_title"        => $randomNews->sub_title,
                        "content_shoulder" => $randomNews->content_shoulder,
                        "brief"            => $randomNews->brief,

                        "body"             => ($newsType->name == NewsHelper::NEWS_TYPE_STORY) ? $randomNews->body : null,
                        "video_url"        => ($newsType->name == NewsHelper::NEWS_TYPE_VIDEO) ? $randomNews->video_url : null,

                        "seo_title"        => $randomNews->title,
                        "seo_brief"        => $randomNews->brief,
                        "seo_keywords"     => $randomNews->seo_keywords,

                        "is_published"     => true,
                        'writer'           => ($newsType->name == NewsHelper::NEWS_TYPE_STORY) ? "News Desk" : null,
                        'source'           => ($newsType->name == NewsHelper::NEWS_TYPE_STORY) ? null : null,
                        "created_at"       => $randomNews->published_at,
                        "updated_at"       => $randomNews->published_at,
                    ])->create();

                    if ($tagIds) {
                        $news->tags()->sync($tagIds);
                    }

                    if ($contributorIds && $index > 5 && $index < 10) {
                        $news->contributors()->sync($contributorIds);
                    }

                    $this->newsAddFeatureImage($news);
                    $this->newsAddFeatureImageMobile($news);

                    if ($newsType->name == NewsHelper::NEWS_TYPE_IMAGE_GALLERY) {
                        for ($i = 0; $i < 13; $i++) {
                            $this->newsAddGalleryImage($news, $i);
                        }
                    }
                }
            }

        }
    }

    private function getRandomLocation(Language $language, ?Category $category): ?Location
    {
        if (! $category) {
            return null;
        }

        $allowed = match ($language->code) {
            SystemHelper::DEFAULT_LANGUAGE_CODE     => "National",
            SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE => "জাতীয়",
            default                                 => null,
        };

        if ($category->name !== $allowed) {
            return null;
        }

        return Location::where("language_id", $language->id)->where("category_id", $category->id)->inRandomOrder()->first() ?? null;
    }

    private function getRandomTagIds(Language $language, $rangeStart = 3)
    {
        $rangeEnd = $rangeStart + 2;

        if ($rangeEnd % 2 === 0) {
            $rangeEnd++;
        }

        $limit = rand($rangeStart, $rangeEnd);

        if ($limit % 2 === 0) {
            $limit++;
        }

        return Tag::where("language_id", $language->id)->inRandomOrder()->limit($limit)->pluck('id') ?? [];
    }

    private function getRandomEvent(Language $language): ?Event
    {
        return Event::where("language_id", $language->id)->where("is_current", true)->inRandomOrder()->first() ?? null;
    }

    private function getRandomContributorIds(Language $language, int $rangeStart = 3)
    {
        $rangeEnd = $rangeStart + 2;

        if ($rangeEnd % 2 === 0) {
            $rangeEnd++;
        }

        $limit = rand($rangeStart, $rangeEnd);

        if ($limit % 2 === 0) {
            $limit++;
        }

        Contributor::where("language_id", $language->id)->inRandomOrder()->limit($limit)->pluck('id') ?? [];
    }

    private function newsAddFeatureImage(News $news)
    {
        $imageUrl = asset("uploads/images/news/feature-image.png");

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->title, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->title)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->title,
                    'alt'     => $news->title,
                    "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function newsAddFeatureImageMobile(News $news)
    {
        $imageUrl = asset("uploads/images/news/feature-image-mobile.png");

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->title, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->title)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->title,
                    'alt'     => $news->title,
                    "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE_MOBILE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function newsAddGalleryImage(News $news, int|string $imageSequence)
    {
        $imageUrl = asset("uploads/images/news/news-gallery-image-3_2.png");

        if (! (($imageSequence % 2) == 0)) {
            $imageUrl = asset("uploads/images/news/news-gallery-image-2_3.png");
        }

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->title, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->title)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->title,
                    'alt'     => $news->title,
                    "role"    => MediaHelper::MEDIA_ROLE_NEWS_GALLERY_IMAGE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function randomNewses(Language $language, int $limit = 10)
    {
        $newsGroup = $this->newsesByLanguageGroups()
            ->firstWhere('language_code', $language->code);

        if (! $newsGroup) {
            return collect();
        }

        return $newsGroup->newses
            ->shuffle()
            ->take($limit)
            ->values();
    }

    private function newsesByLanguageGroups()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'newses'        => collect([

                    (object) [
                        'title'            => "Demo City Opens New Public Service Help Desk",
                        'sub_title'        => "Pilot support booth launched for citizens",
                        'content_shoulder' => "",
                        'brief'            => "A new demo public service help desk opened to test faster citizen support and basic information delivery.",
                        'body'             => "Demo City opened a pilot public service help desk at the central civic building. The booth will provide sample information about certificates, applications, complaint submission and local service tracking for testing purposes.",
                        'seo_keywords'     => "demo public service, citizen support, service help desk",
                        'video_url'        => "https://www.youtube.com/embed/9nA5TAN4W8Y",
                        'published_at'     => "2026-01-03 09:00:00",
                    ],

                    (object) [
                        'title'            => "Sample District Starts Clean Street Awareness Week",
                        'sub_title'        => "",
                        'content_shoulder' => "Clean City",
                        'brief'            => "Sample District began a week-long awareness campaign encouraging cleaner streets and responsible waste disposal.",
                        'body'             => "Sample District authorities launched Clean Street Awareness Week as part of a demo civic engagement programme. Volunteers, students and local groups will join mock activities focused on waste sorting, public cleanliness and community responsibility.",
                        'seo_keywords'     => "clean street, cleanliness campaign, demo district",
                        'video_url'        => "https://www.youtube.com/embed/MHjxqZbnE6w",
                        'published_at'     => "2026-01-05 10:20:00",
                    ],

                    (object) [
                        'title'            => "River Park Bridge Lighting Trial Completed",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A lighting trial on River Park Bridge was completed to test visibility, safety and night-time movement.",
                        'body'             => "Engineers completed a demo lighting trial on River Park Bridge using energy-saving lamps and timed control systems. The test was designed to evaluate public safety, traffic visibility and maintenance response before a wider rollout.",
                        'seo_keywords'     => "bridge lighting, public safety, night visibility",
                        'video_url'        => "https://www.youtube.com/embed/JKJsmw8pWNo",
                        'published_at'     => "2026-01-07 11:30:00",
                    ],

                    (object) [
                        'title'            => "Green Valley Community Center Adds Digital Notice Board",
                        'sub_title'        => "",
                        'content_shoulder' => "Digital Service",
                        'brief'            => "A digital notice board was installed at Green Valley Community Center to show service updates and local announcements.",
                        'body'             => "Green Valley Community Center installed a digital notice board as part of a demo communication upgrade. The display will show sample public notices, event schedules, emergency messages and citizen service reminders.",
                        'seo_keywords'     => "digital notice board, community center, public update",
                        'video_url'        => "https://www.youtube.com/embed/8IvOF1m6Iz8",
                        'published_at'     => "2026-01-09 12:10:00",
                    ],

                    (object) [
                        'title'            => "North Plaza Hosts Demo Citizen Feedback Session",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "Residents joined a demo feedback session to share opinions on local services and public facilities.",
                        'body'             => "North Plaza hosted a citizen feedback session where participants discussed transport, drainage, park maintenance and public service response. The event was arranged as a fictional demo activity for testing news layout and display.",
                        'seo_keywords'     => "citizen feedback, public service, community meeting",
                        'video_url'        => "https://www.youtube.com/embed/RfFpQPoyz_Y",
                        'published_at'     => "2026-01-11 13:25:00",
                    ],

                    (object) [
                        'title'            => "Demo Youth Forum Calls for Transparent Local Debate",
                        'sub_title'        => "Young participants ask for issue-based discussion",
                        'content_shoulder' => "",
                        'brief'            => "A fictional youth forum called for transparent discussion on local development and public participation.",
                        'body'             => "Demo Youth Forum held a mock discussion on transparent decision-making, youth participation and responsible leadership. Speakers said issue-based dialogue can help citizens understand policy choices without personal attacks or misinformation.",
                        'seo_keywords'     => "youth forum, transparent debate, public discussion",
                        'video_url'        => "https://www.youtube.com/embed/8IvOF1m6Iz8",
                        'published_at'     => "2026-01-13 14:00:00",
                    ],

                    (object) [
                        'title'            => "Sample Civic Panel Discusses Public Trust in Institutions",
                        'sub_title'        => "",
                        'content_shoulder' => "Civic Dialogue",
                        'brief'            => "A sample civic panel discussed how fictional institutions can improve transparency and public confidence.",
                        'body'             => "A demo civic panel discussed public trust, accountability and communication between citizens and institutions. The fictional session highlighted the value of open meetings, clear rules and regular reporting.",
                        'seo_keywords'     => "civic panel, public trust, accountability",
                        'video_url'        => "https://www.youtube.com/embed/6VHlG0eZSws",
                        'published_at'     => "2026-01-15 15:10:00",
                    ],

                    (object) [
                        'title'            => "Mock Roundtable Highlights Peaceful Public Dialogue",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A mock roundtable highlighted respectful dialogue and peaceful discussion among demo participants.",
                        'body'             => "Participants at a fictional public roundtable discussed the importance of peaceful meetings, respectful speech and public-interest decisions. The demo event was created to test public discussion content without using real parties or real leaders.",
                        'seo_keywords'     => "mock roundtable, peaceful dialogue, demo discussion",
                        'video_url'        => "https://www.youtube.com/embed/Q3iGVTktupI",
                        'published_at'     => "2026-01-17 16:45:00",
                    ],

                    (object) [
                        'title'            => "Demo Office Launches Faster File Tracking System",
                        'sub_title'        => "New dashboard tested for official workflow",
                        'content_shoulder' => "",
                        'brief'            => "A fictional office launched a file tracking dashboard to test faster administrative workflow.",
                        'body'             => "Demo Administrative Office introduced a digital file tracking system for internal testing. The dashboard can show sample file status, responsible desk, expected completion time and request history.",
                        'seo_keywords'     => "file tracking, digital office, service dashboard",
                        'video_url'        => "https://www.youtube.com/embed/tPF_8P1hqZA",
                        'published_at'     => "2026-01-19 09:40:00",
                    ],

                    (object) [
                        'title'            => "Sample Service Portal Adds Online Appointment Feature",
                        'sub_title'        => "",
                        'content_shoulder' => "E-Service",
                        'brief'            => "A demo online portal added appointment booking to reduce waiting time at service counters.",
                        'body'             => "The Sample Service Portal added a fictional online appointment feature for testing user flow. Citizens can choose a service type, select a time slot and receive a mock confirmation message.",
                        'seo_keywords'     => "online appointment, service portal, digital service",
                        'video_url'        => "https://www.youtube.com/embed/9nA5TAN4W8Y",
                        'published_at'     => "2026-01-21 10:15:00",
                    ],

                    (object) [
                        'title'            => "Demo Authority Tests Queue Management at Service Center",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A queue management trial began at a demo service center to improve visitor movement and counter handling.",
                        'body'             => "Demo Authority started a queue management trial using token screens and counter alerts. The test is designed to measure visitor waiting time, service speed and staff coordination.",
                        'seo_keywords'     => "queue management, service center, token system",
                        'video_url'        => "https://www.youtube.com/embed/8a9AHbV3Jg4",
                        'published_at'     => "2026-01-23 11:50:00",
                    ],

                    (object) [
                        'title'            => "Public Office Simulation Reviews Citizen Complaint Process",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A public office simulation reviewed how citizen complaints are received, assigned and resolved.",
                        'body'             => "A fictional public office conducted a complaint process simulation to test response flow. The exercise reviewed complaint registration, officer assignment, status updates and final feedback collection.",
                        'seo_keywords'     => "citizen complaint, public office, service workflow",
                        'video_url'        => "https://www.youtube.com/embed/RfFpQPoyz_Y",
                        'published_at'     => "2026-01-25 12:35:00",
                    ],

                    (object) [
                        'title'            => "Demo Police Unit Runs Road Safety Drill",
                        'sub_title'        => "Mock traffic response tested in busy zone",
                        'content_shoulder' => "",
                        'brief'            => "A fictional safety unit ran a road safety drill to test traffic response and pedestrian support.",
                        'body'             => "Demo Police Unit carried out a mock road safety drill near Central Avenue. The exercise tested lane control, pedestrian crossing support, emergency response and coordination with traffic volunteers.",
                        'seo_keywords'     => "road safety drill, traffic safety, public safety",
                        'video_url'        => "https://www.youtube.com/embed/Yw_TaZCVuY4",
                        'published_at'     => "2026-01-27 13:00:00",
                    ],

                    (object) [
                        'title'            => "Sample Security Desk Opens Lost Item Reporting Booth",
                        'sub_title'        => "",
                        'content_shoulder' => "Public Safety",
                        'brief'            => "A demo reporting booth opened to help visitors submit lost item information in a structured way.",
                        'body'             => "Sample Security Desk opened a lost item reporting booth at a fictional transport terminal. The booth will collect sample reports, item descriptions and contact details for testing public safety workflows.",
                        'seo_keywords'     => "lost item report, public safety, reporting booth",
                        'video_url'        => "https://www.youtube.com/embed/Q3iGVTktupI",
                        'published_at'     => "2026-01-29 14:20:00",
                    ],

                    (object) [
                        'title'            => "Neighborhood Watch Demo Promotes Night Safety Awareness",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A fictional neighborhood watch demo promoted safe movement, lighting and emergency contact awareness.",
                        'body'             => "Residents of Model Lane joined a neighborhood watch awareness demo focused on night safety. Organisers discussed street lighting, emergency contacts, visitor records and safe reporting habits.",
                        'seo_keywords'     => "neighborhood watch, night safety, safety awareness",
                        'video_url'        => "https://www.youtube.com/embed/_NeEF1fwT4k",
                        'published_at'     => "2026-02-01 09:10:00",
                    ],

                    (object) [
                        'title'            => "Mock Voter Education Booth Opens at Learning Hall",
                        'sub_title'        => "Demo booth explains voting steps",
                        'content_shoulder' => "",
                        'brief'            => "A mock voter education booth opened to explain sample voting steps and ballot handling.",
                        'body'             => "A fictional voter education booth opened at Learning Hall to demonstrate registration checking, ballot marking and queue discipline. The programme was designed only for testing election-style content.",
                        'seo_keywords'     => "mock election, voter education, voting steps",
                        'video_url'        => "https://www.youtube.com/embed/8IvOF1m6Iz8",
                        'published_at'     => "2026-02-03 10:30:00",
                    ],

                    (object) [
                        'title'            => "Sample Election Center Tests Digital Result Board",
                        'sub_title'        => "",
                        'content_shoulder' => "Result Display",
                        'brief'            => "A sample election center tested a digital board for showing mock vote counts and turnout updates.",
                        'body'             => "Sample Election Center tested a digital result board using fictional vote data. The display showed mock turnout, candidate codes and result update times to evaluate layout and visibility.",
                        'seo_keywords'     => "digital result board, sample election, result display",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-05 11:45:00",
                    ],

                    (object) [
                        'title'            => "Demo Polling Staff Training Focuses on Queue Control",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A fictional polling staff training session focused on queue control, ballot secrecy and voter assistance.",
                        'body'             => "Demo polling staff attended a training session covering voter verification, queue control, booth management and accessibility support. All scenarios used fictional data for testing purposes.",
                        'seo_keywords'     => "polling staff, election training, queue control",
                        'video_url'        => "https://www.youtube.com/embed/6VHlG0eZSws",
                        'published_at'     => "2026-02-07 12:20:00",
                    ],

                    (object) [
                        'title'            => "Demo City Reviews New Footpath Use Guideline",
                        'sub_title'        => "Draft guideline focuses on pedestrian movement",
                        'content_shoulder' => "",
                        'brief'            => "A fictional city review discussed a draft guideline for safer footpath use and public movement.",
                        'body'             => "Demo City reviewed a draft footpath use guideline to balance pedestrian access, small vendors and public safety. The fictional policy discussion is suitable for testing public policy pages.",
                        'seo_keywords'     => "footpath guideline, pedestrian safety, public policy",
                        'video_url'        => "https://www.youtube.com/embed/sNFLoESvdXc",
                        'published_at'     => "2026-02-09 13:15:00",
                    ],

                    (object) [
                        'title'            => "Sample Transport Policy Suggests School-Zone Speed Limit",
                        'sub_title'        => "",
                        'content_shoulder' => "Road Safety Policy",
                        'brief'            => "A sample transport policy proposed lower speed limits around schools and learning centers.",
                        'body'             => "A fictional transport policy draft suggested special speed limits near schools, colleges and training centers. The draft also included zebra crossings, warning signs and volunteer support during opening and closing hours.",
                        'seo_keywords'     => "school zone, speed limit, road safety policy",
                        'video_url'        => "https://www.youtube.com/embed/_NeEF1fwT4k",
                        'published_at'     => "2026-02-11 14:40:00",
                    ],

                    (object) [
                        'title'            => "Community Water Use Policy Tested in Model Area",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A demo community water policy was tested to encourage careful use and faster leak reporting.",
                        'body'             => "A model area tested a fictional community water use policy. The policy encouraged scheduled supply notices, faster leak reporting, rainwater awareness and responsible household water use.",
                        'seo_keywords'     => "water policy, community service, environment awareness",
                        'video_url'        => "https://www.youtube.com/embed/6butzqRy6dg",
                        'published_at'     => "2026-02-13 15:05:00",
                    ],

                    (object) [
                        'title'            => "Demo Island Hosts Regional Innovation Fair",
                        'sub_title'        => "Fictional countries showcase green ideas",
                        'content_shoulder' => "",
                        'brief'            => "Demo Island hosted a fictional regional fair focused on green technology, education tools and small business ideas.",
                        'body'             => "Demo Island hosted a regional innovation fair where fictional countries presented sample projects on clean energy, digital learning and small business support. The event was created as international demo content.",
                        'seo_keywords'     => "regional innovation, green technology, innovation fair",
                        'video_url'        => "https://www.youtube.com/embed/00BtC_eJnf8",
                        'published_at'     => "2026-02-15 16:25:00",
                    ],

                    (object) [
                        'title'            => "Sample Nation Tests Smart Border Service App",
                        'sub_title'        => "",
                        'content_shoulder' => "Digital Border",
                        'brief'            => "Sample Nation tested a smart border service app for travel notices and document guidance.",
                        'body'             => "Sample Nation launched a demo smart border service app with fictional travel notices, document checklists and help desk options. The app is designed to test international-service news content.",
                        'seo_keywords'     => "smart border, travel service, digital guidance",
                        'video_url'        => "https://www.youtube.com/embed/PPPETTgFm4I",
                        'published_at'     => "2026-02-17 17:10:00",
                    ],

                    (object) [
                        'title'            => "Global Demo Forum Discusses Safe Digital Learning",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A fictional global forum discussed safe digital learning tools for students and teachers.",
                        'body'             => "The Global Demo Forum held a session on safe digital learning, online privacy and responsible classroom technology. Participants used sample cases to discuss how education platforms can protect learners.",
                        'seo_keywords'     => "digital learning, online education, learning safety",
                        'video_url'        => "https://www.youtube.com/embed/z5Jf56W5HR0",
                        'published_at'     => "2026-02-19 18:00:00",
                    ],

                    (object) [
                        'title'            => "Mock Summit Calls for Responsible Public Communication",
                        'sub_title'        => "",
                        'content_shoulder' => "Global Dialogue",
                        'brief'            => "A mock global summit called for responsible communication during policy debates and public crises.",
                        'body'             => "A fictional global summit discussed responsible public communication, fact-checking habits and transparent decision-making. The event used demo scenarios to avoid references to real governments or disputes.",
                        'seo_keywords'     => "public communication, global dialogue, responsible media",
                        'video_url'        => "https://www.youtube.com/embed/8IvOF1m6Iz8",
                        'published_at'     => "2026-02-21 09:35:00",
                    ],

                    (object) [
                        'title'            => "Demo Policy Network Reviews Youth Participation Trends",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A demo policy network reviewed how young people join civic discussions and community decision-making.",
                        'body'             => "Demo Policy Network released a fictional review on youth participation in civic life. The review highlighted student forums, volunteer groups, online discussions and local leadership training.",
                        'seo_keywords'     => "youth participation, civic engagement, policy network",
                        'video_url'        => "https://www.youtube.com/embed/WUBWEukYOeQ",
                        'published_at'     => "2026-02-23 10:50:00",
                    ],

                    (object) [
                        'title'            => "Demo Countries Sign Cultural Exchange Note",
                        'sub_title'        => "Fictional agreement focuses on students and artists",
                        'content_shoulder' => "",
                        'brief'            => "Two fictional countries signed a cultural exchange note for demo students, artists and researchers.",
                        'body'             => "Demo Republic and Sample Kingdom signed a fictional cultural exchange note covering student visits, art exhibitions and research workshops. The content is fully imaginary and suitable for testing diplomacy-style pages.",
                        'seo_keywords'     => "cultural exchange, student visits, diplomacy demo",
                        'video_url'        => "https://www.youtube.com/embed/AomIywrEOqQ",
                        'published_at'     => "2026-02-25 11:40:00",
                    ],

                    (object) [
                        'title'            => "Sample Embassy Opens Digital Help Corner",
                        'sub_title'        => "",
                        'content_shoulder' => "Embassy Service",
                        'brief'            => "A sample embassy opened a digital help corner for travel guidance and appointment support.",
                        'body'             => "A fictional embassy opened a digital help corner to provide sample travel guidance, appointment instructions and document checklists. The service is part of a demo diplomacy content set.",
                        'seo_keywords'     => "embassy help, travel guidance, digital service",
                        'video_url'        => "https://www.youtube.com/embed/F8C2C7vzuaY",
                        'published_at'     => "2026-02-27 12:15:00",
                    ],

                    (object) [
                        'title'            => "Demo Market Reports Higher Weekend Sales",
                        'sub_title'        => "Retailers see stronger visitor flow",
                        'content_shoulder' => "",
                        'brief'            => "Demo Market reported stronger weekend sales as shoppers returned to clothing, grocery and electronics stores.",
                        'body'             => "Demo Market recorded higher visitor flow during the weekend, according to fictional shop reports. Retailers said clothing, household goods and electronics counters saw improved sales compared with the previous demo week.",
                        'seo_keywords'     => "demo market, weekend sales, retail business",
                        'video_url'        => "https://www.youtube.com/embed/c3PLAAtDYzs",
                        'published_at'     => "2026-03-01 13:20:00",
                    ],

                    (object) [
                        'title'            => "Sample Export Hub Tests Faster Warehouse Check-In",
                        'sub_title'        => "",
                        'content_shoulder' => "Trade Logistics",
                        'brief'            => "A sample export hub tested a faster warehouse check-in system to improve loading and delivery records.",
                        'body'             => "Sample Export Hub tested a digital check-in process for trucks, warehouse staff and shipment records. The fictional trial measured gate time, loading queue and delivery confirmation speed.",
                        'seo_keywords'     => "export hub, warehouse check-in, trade logistics",
                        'video_url'        => "https://www.youtube.com/embed/2YU0b2wglTo",
                        'published_at'     => "2026-03-03 14:45:00",
                    ],

                    (object) [
                        'title'            => "Small Shops Join Demo Digital Payment Campaign",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "Small shops joined a demo campaign to promote digital payments and simple sales tracking.",
                        'body'             => "More than fifty fictional small shops joined a demo digital payment campaign in Model Bazaar. Shop owners tested QR payment posters, transaction logs and daily sales summaries.",
                        'seo_keywords'     => "digital payment, small shops, business campaign",
                        'video_url'        => "https://www.youtube.com/embed/y9jsygZW1vY",
                        'published_at'     => "2026-03-05 15:30:00",
                    ],

                    (object) [
                        'title'            => "Green Basket Store Opens New Demo Outlet",
                        'sub_title'        => "",
                        'content_shoulder' => "Retail Expansion",
                        'brief'            => "Green Basket Store opened a new demo outlet with fresh food, household items and express checkout.",
                        'body'             => "Green Basket Store opened a fictional outlet at Lake Road to test retail news content. The outlet includes sample fresh food shelves, household products, customer support desk and express checkout lanes.",
                        'seo_keywords'     => "retail outlet, green basket, small business",
                        'video_url'        => "https://www.youtube.com/embed/f-ZDjXiUjCk",
                        'published_at'     => "2026-03-07 16:10:00",
                    ],

                    (object) [
                        'title'            => "Demo Stock Index Ends Slightly Higher",
                        'sub_title'        => "Fictional market sees selective buying",
                        'content_shoulder' => "",
                        'brief'            => "A demo stock index ended slightly higher as fictional investors bought selected banking and technology shares.",
                        'body'             => "The Demo Stock Index closed with a small gain after selective buying in sample banking, technology and consumer shares. Analysts in the fictional market said investors remained cautious but active.",
                        'seo_keywords'     => "demo stock index, market gain, sample shares",
                        'video_url'        => "https://www.youtube.com/embed/ZCFkWDdmXG8",
                        'published_at'     => "2026-03-09 17:25:00",
                    ],

                    (object) [
                        'title'            => "Sample Exchange Tests Real-Time Price Alert Feature",
                        'sub_title'        => "",
                        'content_shoulder' => "Market Tech",
                        'brief'            => "Sample Exchange tested a real-time price alert feature for demo investors and brokerage dashboards.",
                        'body'             => "Sample Exchange tested a price alert system using fictional listed companies and mock trading data. The feature can notify users when a share crosses a selected demo price level.",
                        'seo_keywords'     => "price alert, sample exchange, stock market technology",
                        'video_url'        => "https://www.youtube.com/embed/bb6_M_srMBk",
                        'published_at'     => "2026-03-11 18:05:00",
                    ],

                    (object) [
                        'title'            => "Demo Bank Launches Student Savings Trial",
                        'sub_title'        => "New account type tested for young users",
                        'content_shoulder' => "",
                        'brief'            => "Demo Bank launched a fictional student savings trial with small deposit goals and digital statements.",
                        'body'             => "Demo Bank introduced a student savings account trial for testing banking content. The account includes mock savings goals, monthly statements, guardian alerts and simple financial education tips.",
                        'seo_keywords'     => "student savings, demo bank, financial education",
                        'video_url'        => "https://www.youtube.com/embed/I6swX8A2XsQ",
                        'published_at'     => "2026-03-13 09:00:00",
                    ],

                    (object) [
                        'title'            => "Sample Bank Tests Faster Loan Application Review",
                        'sub_title'        => "",
                        'content_shoulder' => "Loan Service",
                        'brief'            => "Sample Bank tested a faster review system for small personal and business loan applications.",
                        'body'             => "Sample Bank started a demo loan review workflow using fictional customer profiles. The process checks income details, repayment ability and document completeness before generating a sample decision status.",
                        'seo_keywords'     => "loan review, banking workflow, finance service",
                        'video_url'        => "https://www.youtube.com/embed/txro53HUrcM",
                        'published_at'     => "2026-03-15 10:40:00",
                    ],

                    (object) [
                        'title'            => "Demo Electronics Holds Annual Partner Meet",
                        'sub_title'        => "Retail partners review new showroom plan",
                        'content_shoulder' => "",
                        'brief'            => "Demo Electronics held a fictional partner meet to discuss showroom expansion and customer service goals.",
                        'body'             => "Demo Electronics hosted its annual partner meet with fictional distributors and retailers. The event covered new showroom design, warranty handling, product display and after-sales service improvement.",
                        'seo_keywords'     => "demo electronics, partner meet, corporate business",
                        'video_url'        => "https://www.youtube.com/embed/c3PLAAtDYzs",
                        'published_at'     => "2026-03-17 11:20:00",
                    ],

                    (object) [
                        'title'            => "Sample Foods Introduces Eco-Friendly Packaging Trial",
                        'sub_title'        => "",
                        'content_shoulder' => "Green Packaging",
                        'brief'            => "Sample Foods introduced a demo packaging trial using recyclable paper boxes and reduced plastic wrapping.",
                        'body'             => "Sample Foods started a fictional eco-friendly packaging trial for selected snack products. The company said the demo will help test customer response, shelf durability and packaging cost.",
                        'seo_keywords'     => "eco packaging, green packaging, corporate sustainability",
                        'video_url'        => "https://www.youtube.com/embed/6butzqRy6dg",
                        'published_at'     => "2026-03-19 12:45:00",
                    ],

                    (object) [
                        'title'            => "Blue Star Logistics Opens Training Room for Drivers",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "Blue Star Logistics opened a demo training room for driver safety, delivery tracking and customer handling.",
                        'body'             => "Blue Star Logistics opened a fictional driver training room at its sample depot. The training covers road safety, route planning, delivery app use and customer communication.",
                        'seo_keywords'     => "logistics training, driver safety, delivery service",
                        'video_url'        => "https://www.youtube.com/embed/Yw_TaZCVuY4",
                        'published_at'     => "2026-03-21 13:30:00",
                    ],

                    (object) [
                        'title'            => "Demo Startup Builds App for Local Repair Services",
                        'sub_title'        => "Platform connects users with technicians",
                        'content_shoulder' => "",
                        'brief'            => "A fictional startup built an app to connect users with electricians, plumbers and appliance technicians.",
                        'body'             => "FixMate Demo launched a local repair service app where users can book fictional electricians, plumbers and appliance technicians. The startup is testing service categories, ratings, appointment slots and payment flow.",
                        'seo_keywords'     => "repair app, demo startup, local service platform",
                        'video_url'        => "https://www.youtube.com/embed/sFzkjG05zsc",
                        'published_at'     => "2026-03-23 14:55:00",
                    ],

                    (object) [
                        'title'            => "Sample Agritech Tests Crop Advisory Chatbot",
                        'sub_title'        => "",
                        'content_shoulder' => "Agritech Demo",
                        'brief'            => "A sample agritech startup tested a chatbot that gives fictional crop care tips and weather reminders.",
                        'body'             => "Sample Agritech tested a crop advisory chatbot using fictional farm data. The chatbot provides demo tips on watering, pest alerts, fertiliser timing and harvest preparation.",
                        'seo_keywords'     => "agritech chatbot, crop advisory, startup technology",
                        'video_url'        => "https://www.youtube.com/embed/WUBWEukYOeQ",
                        'published_at'     => "2026-03-25 15:35:00",
                    ],

                    (object) [
                        'title'            => "Learning Startup Adds Mini Game Feature",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A demo learning startup added mini games to improve lesson practice and student engagement.",
                        'body'             => "BrightPath Demo added quiz games, word puzzles and score badges to its fictional learning app. The feature is designed to test how short interactive activities can support regular practice.",
                        'seo_keywords'     => "learning app, mini games, education startup",
                        'video_url'        => "https://www.youtube.com/embed/z5Jf56W5HR0",
                        'published_at'     => "2026-03-27 16:15:00",
                    ],

                    (object) [
                        'title'            => "Demo City Marathon Draws Record Local Runners",
                        'sub_title'        => "Community race promotes fitness",
                        'content_shoulder' => "",
                        'brief'            => "The Demo City Marathon drew a record number of fictional local runners in a community fitness event.",
                        'body'             => "The Demo City Marathon welcomed runners from schools, clubs and fitness groups. The fictional event included a 5K fun run, a 10K city route and a family walking segment.",
                        'seo_keywords'     => "demo marathon, local runners, fitness event",
                        'video_url'        => "https://www.youtube.com/embed/5ISTUD4t25I",
                        'published_at'     => "2026-03-29 17:00:00",
                    ],

                    (object) [
                        'title'            => "Sample Football Academy Opens Spring Camp",
                        'sub_title'        => "",
                        'content_shoulder' => "Youth Sports",
                        'brief'            => "Sample Football Academy opened a spring camp for young players to practice fitness, teamwork and basic skills.",
                        'body'             => "Sample Football Academy launched a fictional spring camp for junior players. Coaches will focus on passing, shooting, fitness drills, teamwork and match discipline.",
                        'seo_keywords'     => "football academy, youth football, sports camp",
                        'video_url'        => "https://www.youtube.com/embed/wWeZ7ScEq04",
                        'published_at'     => "2026-03-31 18:20:00",
                    ],

                    (object) [
                        'title'            => "River Club Wins Demo Volleyball Tournament",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "River Club won a fictional volleyball tournament after a close final against Green Court Club.",
                        'body'             => "River Club defeated Green Court Club in the final of a demo volleyball tournament. The fictional match featured strong serves, quick blocks and a lively crowd at Model Sports Complex.",
                        'seo_keywords'     => "volleyball tournament, river club, sports training",
                        'video_url'        => "https://www.youtube.com/embed/oC8_QCY8ZE0",
                        'published_at'     => "2026-04-02 09:15:00",
                    ],

                    (object) [
                        'title'            => "Demo Tigers Win Practice Match by Five Wickets",
                        'sub_title'        => "Young batters guide chase",
                        'content_shoulder' => "",
                        'brief'            => "Demo Tigers won a fictional practice cricket match by five wickets after a steady middle-order chase.",
                        'body'             => "Demo Tigers chased 146 in a fictional practice match at Central Ground. Two young batters built a calm partnership, while the bowlers earlier kept the target under control.",
                        'seo_keywords'     => "demo cricket, practice match, batting practice",
                        'video_url'        => "https://www.youtube.com/embed/FBxnHSXaZNs",
                        'published_at'     => "2026-04-04 10:35:00",
                    ],

                    (object) [
                        'title'            => "Sample Cricket League Announces Junior Draft",
                        'sub_title'        => "",
                        'content_shoulder' => "Junior Cricket",
                        'brief'            => "A sample cricket league announced a junior draft to select fictional players for four youth teams.",
                        'body'             => "The Sample Cricket League announced a junior draft for four fictional youth teams. Organisers said the draft will help test player profiles, team pages, fixtures and match result layouts.",
                        'seo_keywords'     => "junior cricket, cricket draft, youth cricket",
                        'video_url'        => "https://www.youtube.com/embed/-yUPd41eL-M",
                        'published_at'     => "2026-04-06 11:10:00",
                    ],

                    (object) [
                        'title'            => "Demo OTT Platform Releases Short Film Collection",
                        'sub_title'        => "Five fictional stories added for viewers",
                        'content_shoulder' => "",
                        'brief'            => "A demo OTT platform released a collection of five fictional short films for testing entertainment content.",
                        'body'             => "StreamBox Demo released a fictional short film collection covering comedy, family drama, mystery and youth stories. The release is designed for testing OTT pages, thumbnails and content summaries.",
                        'seo_keywords'     => "demo OTT, short films, streaming platform",
                        'video_url'        => "https://www.youtube.com/embed/aqz-KE-bpKQ",
                        'published_at'     => "2026-04-08 12:25:00",
                    ],

                    (object) [
                        'title'            => "Sample Web Series Trailer Gets Strong Demo Response",
                        'sub_title'        => "",
                        'content_shoulder' => "Web Series",
                        'brief'            => "A fictional web series trailer received strong demo response across social and streaming preview pages.",
                        'body'             => "The trailer of City Lights, a fictional web series, received strong demo engagement after release on a sample OTT platform. Viewers reacted to the mystery theme, fast editing and character-focused teaser.",
                        'seo_keywords'     => "web series, trailer response, OTT entertainment",
                        'video_url'        => "https://www.youtube.com/embed/Vpg9yizPP_g",
                        'published_at'     => "2026-04-10 13:40:00",
                    ],

                    (object) [
                        'title'            => "Demo Clinic Starts Free Blood Pressure Check Week",
                        'sub_title'        => "Health awareness booth opens for residents",
                        'content_shoulder' => "",
                        'brief'            => "A fictional clinic started a free blood pressure check week to promote basic health awareness.",
                        'body'             => "Demo Clinic opened a health awareness booth offering fictional blood pressure checks, weight measurement and lifestyle advice. The programme encourages residents to monitor basic health indicators regularly.",
                        'seo_keywords'     => "blood pressure check, demo clinic, health awareness",
                        'video_url'        => "https://www.youtube.com/embed/giyqe2A_uSs",
                        'published_at'     => "2026-04-12 14:20:00",
                    ],

                    (object) [
                        'title'            => "Sample Hospital Tests Digital Appointment Token",
                        'sub_title'        => "",
                        'content_shoulder' => "Hospital Service",
                        'brief'            => "Sample Hospital tested a digital appointment token system to reduce waiting time at outpatient counters.",
                        'body'             => "Sample Hospital tested a fictional digital appointment token for outpatient services. The system shows patient queue, doctor room number and estimated waiting time.",
                        'seo_keywords'     => "hospital token, appointment system, health service",
                        'video_url'        => "https://www.youtube.com/embed/cuLH7SyWXFk",
                        'published_at'     => "2026-04-14 15:45:00",
                    ],

                    (object) [
                        'title'            => "Green Park Hosts Morning Fitness Awareness Camp",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A demo fitness awareness camp encouraged walking, stretching and healthy breakfast habits.",
                        'body'             => "Green Park hosted a fictional morning fitness awareness camp for local residents. Trainers demonstrated walking routines, light stretching, hydration tips and balanced breakfast ideas.",
                        'seo_keywords'     => "fitness camp, health awareness, morning exercise",
                        'video_url'        => "https://www.youtube.com/embed/368ZynFYPHc",
                        'published_at'     => "2026-04-16 16:30:00",
                    ],

                    (object) [
                        'title'            => "Demo Board Publishes Practice Test Schedule",
                        'sub_title'        => "Mock exams to begin next week",
                        'content_shoulder' => "",
                        'brief'            => "A fictional education board published a practice test schedule for students preparing for mock examinations.",
                        'body'             => "Demo Education Board published a practice test schedule for fictional students. The routine includes language, mathematics, science and general knowledge papers arranged over one week.",
                        'seo_keywords'     => "practice test, exam schedule, education board",
                        'video_url'        => "https://www.youtube.com/embed/00BtC_eJnf8",
                        'published_at'     => "2026-04-18 17:05:00",
                    ],

                    (object) [
                        'title'            => "Sample Exam Center Tests Online Admit Card Check",
                        'sub_title'        => "",
                        'content_shoulder' => "Exam Service",
                        'brief'            => "A sample exam center tested an online admit card checking system before mock examinations.",
                        'body'             => "Sample Exam Center tested a fictional online admit card check feature. Students can enter a demo roll number to view seat plan, subject list and exam time.",
                        'seo_keywords'     => "admit card check, online exam, student service",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-04-20 18:25:00",
                    ],

                    (object) [
                        'title'            => "Mock Result Portal Opens for Demo Students",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A mock result portal opened to test grade display, subject marks and printable result pages.",
                        'body'             => "A fictional result portal opened for demo students with sample marks, grades and printable result sheets. The portal is useful for testing exam result layouts and student dashboard features.",
                        'seo_keywords'     => "mock result, result portal, exam result",
                        'video_url'        => "https://www.youtube.com/embed/z5Jf56W5HR0",
                        'published_at'     => "2026-04-22 09:35:00",
                    ],

                    (object) [
                        'title'            => "Demo Weather Desk Shares Heat Safety Reminder",
                        'sub_title'        => "",
                        'content_shoulder' => "Public Reminder",
                        'brief'            => "A fictional weather desk shared simple heat safety reminders for outdoor workers and students.",
                        'body'             => "Demo Weather Desk shared a public reminder about drinking water, using shade and avoiding long outdoor activity during peak heat. The fictional update is suitable for general news testing.",
                        'seo_keywords'     => "heat safety, public reminder, health safety",
                        'video_url'        => "https://www.youtube.com/embed/368ZynFYPHc",
                        'published_at'     => "2026-04-24 10:50:00",
                    ],

                    (object) [
                        'title'            => "Model Bakery Adds Online Pre-Order Option",
                        'sub_title'        => "",
                        'content_shoulder' => "Small Business",
                        'brief'            => "Model Bakery added a demo online pre-order option for cakes, bread and snacks.",
                        'body'             => "Model Bakery launched a fictional online pre-order feature to test small business content. Customers can choose demo products, pickup time and payment method before visiting the store.",
                        'seo_keywords'     => "online pre-order, model bakery, small business",
                        'video_url'        => "https://www.youtube.com/embed/y9jsygZW1vY",
                        'published_at'     => "2026-04-26 11:30:00",
                    ],

                    (object) [
                        'title'            => "Demo Delivery Startup Tests Bicycle Fleet",
                        'sub_title'        => "",
                        'content_shoulder' => "Green Delivery",
                        'brief'            => "A fictional delivery startup tested a bicycle fleet for short-distance food and parcel delivery.",
                        'body'             => "QuickWheel Demo tested a bicycle delivery fleet in a model neighborhood. The startup used fictional orders to measure delivery time, rider safety and customer tracking updates.",
                        'seo_keywords'     => "bicycle delivery, delivery startup, green delivery",
                        'video_url'        => "https://www.youtube.com/embed/sFzkjG05zsc",
                        'published_at'     => "2026-04-28 12:45:00",
                    ],

                    (object) [
                        'title'            => "Sample Telecom Opens Customer Experience Lab",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "Sample Telecom opened a fictional customer experience lab to test service quality and support scripts.",
                        'body'             => "Sample Telecom opened a demo customer experience lab for testing call center scripts, app navigation and complaint handling. The lab uses fictional customers and sample service issues.",
                        'seo_keywords'     => "customer experience, telecom lab, corporate service",
                        'video_url'        => "https://www.youtube.com/embed/8IvOF1m6Iz8",
                        'published_at'     => "2026-04-30 13:20:00",
                    ],

                    (object) [
                        'title'            => "Demo School Games Begin With Colorful Opening",
                        'sub_title'        => "",
                        'content_shoulder' => "School Sports",
                        'brief'            => "The Demo School Games began with football, athletics, badminton and table tennis events.",
                        'body'             => "The Demo School Games opened at Model Field with students joining football, athletics, badminton and table tennis. The fictional event promotes teamwork, discipline and fitness among young participants.",
                        'seo_keywords'     => "school games, student sports, sports tournament",
                        'video_url'        => "https://www.youtube.com/embed/wWeZ7ScEq04",
                        'published_at'     => "2026-05-01 14:10:00",
                    ],

                    (object) [
                        'title'            => "Sample Nutrition Camp Promotes Healthy Lunch Boxes",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "A fictional nutrition camp encouraged students to bring balanced lunch boxes with fruits, protein and water.",
                        'body'             => "Sample Nutrition Camp promoted healthy lunch boxes for school students. Organisers shared demo meal ideas including fruits, eggs, vegetables, rice, lentils and clean drinking water.",
                        'seo_keywords'     => "nutrition camp, healthy lunch, student health",
                        'video_url'        => "https://www.youtube.com/embed/giyqe2A_uSs",
                        'published_at'     => "2026-05-02 15:25:00",
                    ],

                    (object) [
                        'title'            => "Demo Music Show Streams First Episode",
                        'sub_title'        => "",
                        'content_shoulder' => "Streaming Show",
                        'brief'            => "A fictional music show streamed its first episode with young singers and acoustic performances.",
                        'body'             => "The first episode of Studio Night Demo streamed on a sample OTT platform. The fictional programme featured young singers, acoustic sets and behind-the-scenes conversations.",
                        'seo_keywords'     => "music show, streaming episode, OTT entertainment",
                        'video_url'        => "https://www.youtube.com/embed/aqz-KE-bpKQ",
                        'published_at'     => "2026-05-03 16:05:00",
                    ],

                    (object) [
                        'title'            => "Demo Question Bank Adds Chapter-Wise Practice",
                        'sub_title'        => "",
                        'content_shoulder' => "Study Support",
                        'brief'            => "A demo question bank added chapter-wise practice sets for students preparing for mock exams.",
                        'body'             => "The Demo Question Bank added chapter-wise practice sets for language, science, mathematics and social studies. The fictional feature helps test education content, exam tags and study support pages.",
                        'seo_keywords'     => "question bank, chapter practice, exam preparation",
                        'video_url'        => "https://www.youtube.com/embed/00BtC_eJnf8",
                        'published_at'     => "2026-05-04 17:40:00",
                    ],

                    (object) [
                        'title'            => "Sample Innovation Lab Tests Smart Classroom Board",
                        'sub_title'        => "",
                        'content_shoulder' => "Smart Learning",
                        'brief'            => "A sample innovation lab tested a smart classroom board for lessons, quizzes and attendance display.",
                        'body'             => "Sample Innovation Lab tested a fictional smart classroom board with lesson slides, quiz timers and student attendance display. The trial helps test education and technology-style news content.",
                        'seo_keywords'     => "smart classroom, innovation lab, education technology",
                        'video_url'        => "https://www.youtube.com/embed/1SOySp8QJtk",
                        'published_at'     => "2026-05-05 10:15:00",
                    ],

                    (object) [
                        'title'            => "Model Farmers Test Solar Irrigation Pump",
                        'sub_title'        => "",
                        'content_shoulder' => "Farm Technology",
                        'brief'            => "Model farmers tested a solar irrigation pump to reduce fuel use and improve watering schedules.",
                        'body'             => "A group of fictional model farmers tested a solar irrigation pump in a demo field. The trial measured water flow, energy savings and crop watering efficiency during daytime operation.",
                        'seo_keywords'     => "solar irrigation, farm technology, green agriculture",
                        'video_url'        => "https://www.youtube.com/embed/6butzqRy6dg",
                        'published_at'     => "2026-05-05 13:30:00",
                    ],

                    (object) [
                        'title'            => "Demo Tourism Desk Shares Weekend Travel Tips",
                        'sub_title'        => "",
                        'content_shoulder' => "Travel Tips",
                        'brief'            => "A fictional tourism desk shared weekend travel tips for safe transport, booking and local sightseeing.",
                        'body'             => "Demo Tourism Desk shared sample weekend travel tips for visitors. The tips included checking transport time, confirming hotel bookings, carrying water and respecting local rules at tourist spots.",
                        'seo_keywords'     => "travel tips, demo tourism, weekend guide",
                        'video_url'        => "https://www.youtube.com/embed/PPPETTgFm4I",
                        'published_at'     => "2026-05-06 09:20:00",
                    ],

                    (object) [
                        'title'            => "Sample App Update Adds Dark Mode and Faster Search",
                        'sub_title'        => "",
                        'content_shoulder' => "App Update",
                        'brief'            => "A sample app update added dark mode, faster search and improved profile settings for demo users.",
                        'body'             => "The Sample App released a fictional update with dark mode, faster search, improved profile settings and better notification controls. The update is suitable for testing technology-style news pages.",
                        'seo_keywords'     => "app update, dark mode, technology news",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-05-06 11:45:00",
                    ],

                    (object) [
                        'title'            => "Demo Community Library Adds Weekend Reading Club",
                        'sub_title'        => "",
                        'content_shoulder' => "Reading Club",
                        'brief'            => "A fictional community library added a weekend reading club for students, parents and young readers.",
                        'body'             => "Demo Community Library launched a weekend reading club with story sessions, book discussions and reading challenges. The fictional programme supports testing lifestyle, education and local news content.",
                        'seo_keywords'     => "reading club, community library, education activity",
                        'video_url'        => "https://www.youtube.com/embed/z5Jf56W5HR0",
                        'published_at'     => "2026-05-06 13:10:00",
                    ],

                ]),
            ],

            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                'newses'        => collect([

                    (object) [
                        'title'            => "ডেমো সিটিতে নতুন জনসেবা সহায়তা ডেস্ক চালু",
                        'sub_title'        => "নাগরিক সেবা দ্রুত করতে পরীক্ষামূলক উদ্যোগ",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো সিটিতে নাগরিকদের তথ্য, আবেদন ও অভিযোগ সহায়তার জন্য নতুন জনসেবা ডেস্ক চালু করা হয়েছে।",
                        'body'             => "ডেমো সিটির কেন্দ্রীয় নাগরিক ভবনে পরীক্ষামূলক জনসেবা সহায়তা ডেস্ক চালু করা হয়েছে। এখানে সনদ, আবেদন, অভিযোগ, স্থানীয় সেবা ও তথ্য অনুসন্ধান নিয়ে নাগরিকদের নমুনা সহায়তা দেওয়া হবে। উদ্যোগটি মূলত সেবা প্রক্রিয়া, কাউন্টার ব্যবস্থাপনা ও নাগরিক যোগাযোগ পরীক্ষা করার জন্য তৈরি করা হয়েছে।",
                        'seo_keywords'     => "জনসেবা, সহায়তা ডেস্ক, নাগরিক সেবা, ডেমো সংবাদ",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-03 09:00:00",
                    ],

                    (object) [
                        'title'            => "নমুনা জেলায় পরিচ্ছন্ন রাস্তা সচেতনতা সপ্তাহ শুরু",
                        'sub_title'        => "",
                        'content_shoulder' => "পরিচ্ছন্ন শহর",
                        'brief'            => "নমুনা জেলায় পরিচ্ছন্ন রাস্তা, বর্জ্য ব্যবস্থাপনা ও নাগরিক দায়িত্ব নিয়ে সপ্তাহব্যাপী সচেতনতা কর্মসূচি শুরু হয়েছে।",
                        'body'             => "নমুনা জেলা কর্তৃপক্ষ পরিচ্ছন্ন রাস্তা সচেতনতা সপ্তাহ শুরু করেছে। কর্মসূচিতে শিক্ষার্থী, স্বেচ্ছাসেবক ও স্থানীয় সংগঠনগুলো অংশ নিচ্ছে। রাস্তার ময়লা নির্দিষ্ট স্থানে ফেলা, প্লাস্টিক ব্যবহার কমানো এবং কমিউনিটি পরিচ্ছন্নতা বজায় রাখার বিষয়ে ডেমো কার্যক্রম চালানো হবে।",
                        'seo_keywords'     => "পরিচ্ছন্নতা, রাস্তা, বর্জ্য ব্যবস্থাপনা, সচেতনতা",
                        'video_url'        => "https://www.youtube.com/embed/S6rmWZQqfos",
                        'published_at'     => "2026-01-05 10:20:00",
                    ],

                    (object) [
                        'title'            => "রিভার পার্ক সেতুর আলোকসজ্জা পরীক্ষামূলকভাবে সম্পন্ন",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "রিভার পার্ক সেতুতে রাতের নিরাপত্তা ও চলাচলের সুবিধা পরীক্ষা করতে নতুন আলোকসজ্জা চালু করা হয়েছে।",
                        'body'             => "রিভার পার্ক সেতুতে জ্বালানি সাশ্রয়ী বাতি ও টাইম কন্ট্রোল সিস্টেম ব্যবহার করে আলোকসজ্জার পরীক্ষা সম্পন্ন হয়েছে। এই ডেমো পরীক্ষার মাধ্যমে রাতের দৃশ্যমানতা, পথচারীর নিরাপত্তা, যানবাহনের চলাচল এবং রক্ষণাবেক্ষণ ব্যবস্থা মূল্যায়ন করা হচ্ছে।",
                        'seo_keywords'     => "সেতু, আলোকসজ্জা, রাতের নিরাপত্তা, অবকাঠামো",
                        'video_url'        => "https://www.youtube.com/embed/yIXvXVETpZo",
                        'published_at'     => "2026-01-07 11:30:00",
                    ],

                    (object) [
                        'title'            => "গ্রিন ভ্যালি কমিউনিটি সেন্টারে ডিজিটাল নোটিশ বোর্ড",
                        'sub_title'        => "",
                        'content_shoulder' => "ডিজিটাল সেবা",
                        'brief'            => "কমিউনিটি সেন্টারে স্থানীয় ঘোষণা, জরুরি বার্তা ও ইভেন্ট তথ্য দেখানোর জন্য ডিজিটাল নোটিশ বোর্ড বসানো হয়েছে।",
                        'body'             => "গ্রিন ভ্যালি কমিউনিটি সেন্টারে একটি ডিজিটাল নোটিশ বোর্ড স্থাপন করা হয়েছে। এতে স্থানীয় সভা, স্বাস্থ্য ক্যাম্প, বিদ্যুৎ মেরামত, জরুরি সতর্কতা এবং নাগরিক সেবা সংক্রান্ত ডেমো তথ্য প্রদর্শন করা হবে।",
                        'seo_keywords'     => "ডিজিটাল নোটিশ, কমিউনিটি সেন্টার, নাগরিক তথ্য",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-09 12:10:00",
                    ],

                    (object) [
                        'title'            => "নর্থ প্লাজায় নাগরিক মতামত সভা অনুষ্ঠিত",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "স্থানীয় সেবা ও জনসুবিধা নিয়ে বাসিন্দাদের মতামত জানতে নর্থ প্লাজায় ডেমো নাগরিক সভা হয়েছে।",
                        'body'             => "নর্থ প্লাজায় অনুষ্ঠিত নাগরিক মতামত সভায় বাসিন্দারা সড়ক, ড্রেনেজ, পার্ক রক্ষণাবেক্ষণ, পানির সরবরাহ ও অনলাইন সেবা নিয়ে মতামত দেন। এই কাল্পনিক সভাটি সংবাদ সাইটের স্থানীয় জনসেবা কনটেন্ট পরীক্ষার জন্য তৈরি করা হয়েছে।",
                        'seo_keywords'     => "নাগরিক মতামত, জনসেবা, কমিউনিটি সভা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-11 13:25:00",
                    ],

                    (object) [
                        'title'            => "ডেমো যুব ফোরামে স্বচ্ছ স্থানীয় আলোচনার আহ্বান",
                        'sub_title'        => "ইস্যুভিত্তিক আলোচনায় তরুণদের আগ্রহ",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো যুব ফোরামে তরুণরা উন্নয়ন, সেবা ও সিদ্ধান্ত গ্রহণে স্বচ্ছতা বাড়ানোর আহ্বান জানিয়েছেন।",
                        'body'             => "ডেমো যুব ফোরামের আলোচনায় তরুণ অংশগ্রহণকারীরা স্থানীয় উন্নয়ন, নাগরিক সেবা ও দায়িত্বশীল নেতৃত্ব নিয়ে মতামত দেন। বক্তারা বলেন, ব্যক্তিগত আক্রমণ নয়, বরং তথ্যভিত্তিক ও ইস্যুভিত্তিক আলোচনা নাগরিকদের সিদ্ধান্ত বুঝতে সাহায্য করে।",
                        'seo_keywords'     => "যুব ফোরাম, স্বচ্ছতা, নাগরিক আলোচনা",
                        'video_url'        => "https://www.youtube.com/embed/WUBWEukYOeQ",
                        'published_at'     => "2026-01-13 14:00:00",
                    ],

                    (object) [
                        'title'            => "প্রতিষ্ঠানের ওপর জনআস্থা নিয়ে নমুনা নাগরিক প্যানেল",
                        'sub_title'        => "",
                        'content_shoulder' => "নাগরিক সংলাপ",
                        'brief'            => "একটি নমুনা নাগরিক প্যানেলে প্রতিষ্ঠানকে আরও স্বচ্ছ ও জবাবদিহিমূলক করার উপায় নিয়ে আলোচনা হয়েছে।",
                        'body'             => "নমুনা নাগরিক প্যানেলে বক্তারা জনআস্থা, জবাবদিহি, উন্মুক্ত বৈঠক ও নিয়মিত তথ্য প্রকাশের গুরুত্ব তুলে ধরেন। ডেমো আলোচনাটি সংবাদ সাইটে নাগরিক বিষয়ক কনটেন্ট প্রদর্শনের জন্য তৈরি করা হয়েছে।",
                        'seo_keywords'     => "জনআস্থা, নাগরিক প্যানেল, জবাবদিহি",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-15 15:10:00",
                    ],

                    (object) [
                        'title'            => "শান্তিপূর্ণ জনসংলাপ নিয়ে মক রাউন্ডটেবিল",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "মক রাউন্ডটেবিলে সম্মানজনক ভাষা, শান্তিপূর্ণ আলোচনা ও জনস্বার্থের সিদ্ধান্ত নিয়ে আলোচনা হয়েছে।",
                        'body'             => "মডেল হলে অনুষ্ঠিত মক রাউন্ডটেবিলে অংশগ্রহণকারীরা শান্তিপূর্ণ জনসংলাপ, সহনশীলতা এবং তথ্যভিত্তিক মতামতের গুরুত্ব তুলে ধরেন। এটি কোনো বাস্তব রাজনৈতিক ঘটনা নয়, বরং ডেমো কনটেন্ট পরীক্ষার জন্য তৈরি।",
                        'seo_keywords'     => "জনসংলাপ, রাউন্ডটেবিল, শান্তিপূর্ণ আলোচনা",
                        'video_url'        => "https://www.youtube.com/embed/WUBWEukYOeQ",
                        'published_at'     => "2026-01-17 16:45:00",
                    ],

                    (object) [
                        'title'            => "ডেমো অফিসে দ্রুত ফাইল ট্র্যাকিং সিস্টেম চালু",
                        'sub_title'        => "অফিস কার্যক্রমে নতুন ড্যাশবোর্ড পরীক্ষা",
                        'content_shoulder' => "",
                        'brief'            => "একটি ডেমো অফিসে ফাইলের অবস্থা, দায়িত্বপ্রাপ্ত ডেস্ক ও সম্ভাব্য সময় দেখাতে ডিজিটাল ট্র্যাকিং সিস্টেম চালু হয়েছে।",
                        'body'             => "ডেমো প্রশাসনিক অফিসে অভ্যন্তরীণ পরীক্ষার জন্য ফাইল ট্র্যাকিং ড্যাশবোর্ড চালু করা হয়েছে। নাগরিক আবেদন বা অফিস ফাইল কোন ডেস্কে আছে, কত সময় লাগতে পারে এবং পরবর্তী ধাপ কী—এসব তথ্য নমুনা হিসেবে দেখানো হবে।",
                        'seo_keywords'     => "ফাইল ট্র্যাকিং, ডিজিটাল অফিস, প্রশাসনিক সেবা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-19 09:40:00",
                    ],

                    (object) [
                        'title'            => "সার্ভিস পোর্টালে অনলাইন অ্যাপয়েন্টমেন্ট সুবিধা",
                        'sub_title'        => "",
                        'content_shoulder' => "ই-সেবা",
                        'brief'            => "সেবা কাউন্টারে অপেক্ষা কমাতে নমুনা সার্ভিস পোর্টালে অনলাইন অ্যাপয়েন্টমেন্ট ফিচার যোগ করা হয়েছে।",
                        'body'             => "নমুনা সার্ভিস পোর্টালে পরীক্ষামূলকভাবে অনলাইন অ্যাপয়েন্টমেন্ট সুবিধা চালু হয়েছে। ব্যবহারকারীরা সেবার ধরন, তারিখ ও সময় নির্বাচন করে একটি ডেমো কনফার্মেশন মেসেজ পেতে পারবেন।",
                        'seo_keywords'     => "অনলাইন অ্যাপয়েন্টমেন্ট, সার্ভিস পোর্টাল, ই-সেবা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-21 10:15:00",
                    ],

                    (object) [
                        'title'            => "সেবা কেন্দ্রে টোকেন ব্যবস্থাপনা পরীক্ষা",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "ভিজিটর চলাচল ও কাউন্টার সেবা সহজ করতে ডেমো সেবা কেন্দ্রে টোকেন ব্যবস্থাপনার পরীক্ষা চলছে।",
                        'body'             => "ডেমো সেবা কেন্দ্রে টোকেন স্ক্রিন, কাউন্টার অ্যালার্ট ও সারি ব্যবস্থাপনা পরীক্ষা করা হচ্ছে। এই পরীক্ষায় অপেক্ষার সময়, সেবার গতি এবং কর্মীদের সমন্বয় মূল্যায়ন করা হবে।",
                        'seo_keywords'     => "টোকেন সিস্টেম, সেবা কেন্দ্র, সারি ব্যবস্থাপনা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-23 11:50:00",
                    ],

                    (object) [
                        'title'            => "নাগরিক অভিযোগ প্রক্রিয়া নিয়ে অফিস সিমুলেশন",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "নাগরিক অভিযোগ কীভাবে গ্রহণ, বরাদ্দ ও নিষ্পত্তি করা হবে তা যাচাই করতে একটি অফিস সিমুলেশন হয়েছে।",
                        'body'             => "একটি কাল্পনিক জনসেবা অফিসে অভিযোগ গ্রহণ, দায়িত্বপ্রাপ্ত কর্মকর্তা নির্ধারণ, স্ট্যাটাস আপডেট এবং শেষ পর্যায়ে ফিডব্যাক নেওয়ার প্রক্রিয়া পরীক্ষা করা হয়েছে। এটি সেবা ব্যবস্থার ডেমো নিউজ কনটেন্ট হিসেবে ব্যবহারযোগ্য।",
                        'seo_keywords'     => "নাগরিক অভিযোগ, জনসেবা, অফিস সিমুলেশন",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-01-25 12:35:00",
                    ],

                    (object) [
                        'title'            => "ডেমো পুলিশ ইউনিটের সড়ক নিরাপত্তা মহড়া",
                        'sub_title'        => "ব্যস্ত এলাকায় মক ট্রাফিক রেসপন্স পরীক্ষা",
                        'content_shoulder' => "",
                        'brief'            => "পথচারী সহায়তা, লেন নিয়ন্ত্রণ ও জরুরি সাড়া যাচাই করতে ডেমো পুলিশ ইউনিট সড়ক নিরাপত্তা মহড়া করেছে।",
                        'body'             => "সেন্ট্রাল অ্যাভিনিউ এলাকায় ডেমো পুলিশ ইউনিট একটি মক সড়ক নিরাপত্তা মহড়া পরিচালনা করে। মহড়ায় পথচারী পারাপার, গাড়ির লেন নিয়ন্ত্রণ, দুর্ঘটনা-পরবর্তী দ্রুত সাড়া এবং ট্রাফিক স্বেচ্ছাসেবকদের সমন্বয় পরীক্ষা করা হয়।",
                        'seo_keywords'     => "সড়ক নিরাপত্তা, ট্রাফিক মহড়া, জননিরাপত্তা",
                        'video_url'        => "https://www.youtube.com/embed/S6rmWZQqfos",
                        'published_at'     => "2026-01-27 13:00:00",
                    ],

                    (object) [
                        'title'            => "হারানো জিনিস রিপোর্টের জন্য নমুনা নিরাপত্তা বুথ",
                        'sub_title'        => "",
                        'content_shoulder' => "জননিরাপত্তা",
                        'brief'            => "একটি কাল্পনিক টার্মিনালে হারানো জিনিসের তথ্য জমা দিতে নমুনা রিপোর্টিং বুথ চালু হয়েছে।",
                        'body'             => "নমুনা নিরাপত্তা ডেস্ক একটি কাল্পনিক পরিবহন টার্মিনালে হারানো জিনিস রিপোর্টের বুথ চালু করেছে। এখানে জিনিসের বিবরণ, সম্ভাব্য হারানোর সময়, যোগাযোগ নম্বর এবং পরিচয় যাচাইয়ের তথ্য ডেমোভাবে নেওয়া হবে।",
                        'seo_keywords'     => "হারানো জিনিস, রিপোর্ট বুথ, নিরাপত্তা ডেস্ক",
                        'video_url'        => "https://www.youtube.com/embed/yIXvXVETpZo",
                        'published_at'     => "2026-01-29 14:20:00",
                    ],

                    (object) [
                        'title'            => "রাতের নিরাপত্তা নিয়ে পাড়া নজরদারি সচেতনতা",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "মডেল লেনে রাতের নিরাপদ চলাচল, আলো ও জরুরি যোগাযোগ নিয়ে পাড়া নজরদারি সচেতনতা হয়েছে।",
                        'body'             => "মডেল লেনের বাসিন্দারা রাতের নিরাপত্তা সচেতনতা কার্যক্রমে অংশ নেন। আলোযুক্ত রাস্তা, জরুরি নম্বর, অপরিচিত চলাচল রিপোর্ট এবং নিরাপদ যাতায়াতের অভ্যাস নিয়ে আলোচনা করা হয়।",
                        'seo_keywords'     => "রাতের নিরাপত্তা, পাড়া নজরদারি, সচেতনতা",
                        'video_url'        => "https://www.youtube.com/embed/Gh4wduP5MlY",
                        'published_at'     => "2026-02-01 09:10:00",
                    ],

                    (object) [
                        'title'            => "লার্নিং হলে মক ভোটার শিক্ষা বুথ",
                        'sub_title'        => "ভোটের ধাপ বোঝাতে ডেমো প্রদর্শনী",
                        'content_shoulder' => "",
                        'brief'            => "ভোটার যাচাই, ব্যালট চিহ্নিত করা ও বুথ শৃঙ্খলা বোঝাতে মক ভোটার শিক্ষা বুথ চালু হয়েছে।",
                        'body'             => "লার্নিং হলে একটি মক ভোটার শিক্ষা বুথ চালু করা হয়েছে। এখানে কাল্পনিক ভোটার তালিকা, নমুনা ব্যালট, সারি ব্যবস্থাপনা এবং গোপন ভোট দেওয়ার প্রক্রিয়া দেখানো হয়।",
                        'seo_keywords'     => "মক ভোট, ভোটার শিক্ষা, ভোট প্রক্রিয়া",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-03 10:30:00",
                    ],

                    (object) [
                        'title'            => "নমুনা নির্বাচন কেন্দ্রে ডিজিটাল ফলাফল বোর্ড পরীক্ষা",
                        'sub_title'        => "",
                        'content_shoulder' => "ফলাফল প্রদর্শন",
                        'brief'            => "মক ভোটের হিসাব, উপস্থিতি ও আপডেট সময় দেখাতে নমুনা নির্বাচনী ফলাফল বোর্ড পরীক্ষা করা হয়েছে।",
                        'body'             => "নমুনা নির্বাচন কেন্দ্রে কাল্পনিক ভোটের তথ্য দিয়ে ডিজিটাল ফলাফল বোর্ড পরীক্ষা করা হয়েছে। বোর্ডে ভোটার উপস্থিতি, প্রার্থী কোড, গণনার সময় এবং ফলাফল আপডেটের নমুনা দেখানো হয়।",
                        'seo_keywords'     => "ডিজিটাল ফলাফল, মক নির্বাচন, ভোট গণনা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-05 11:45:00",
                    ],

                    (object) [
                        'title'            => "পোলিং স্টাফ প্রশিক্ষণে সারি নিয়ন্ত্রণে জোর",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো পোলিং স্টাফ প্রশিক্ষণে ভোটার যাচাই, বুথ ব্যবস্থাপনা ও সারি নিয়ন্ত্রণের অনুশীলন হয়েছে।",
                        'body'             => "ডেমো পোলিং স্টাফরা ভোটার যাচাই, বুথে সহায়তা, গোপনীয়তা রক্ষা, প্রতিবন্ধী ভোটার সহায়তা এবং সারি নিয়ন্ত্রণ বিষয়ে প্রশিক্ষণ নেন। পুরো প্রশিক্ষণটি কাল্পনিক ডেটা দিয়ে তৈরি।",
                        'seo_keywords'     => "পোলিং স্টাফ, নির্বাচন প্রশিক্ষণ, সারি নিয়ন্ত্রণ",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-07 12:20:00",
                    ],

                    (object) [
                        'title'            => "ফুটপাত ব্যবহারের নতুন নির্দেশিকা পর্যালোচনা",
                        'sub_title'        => "পথচারী চলাচল নিরাপদ করতে খসড়া নির্দেশিকা",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো সিটিতে পথচারী, ক্ষুদ্র দোকান ও জননিরাপত্তার ভারসাম্য রাখতে ফুটপাত নির্দেশিকা পর্যালোচনা হয়েছে।",
                        'body'             => "ডেমো সিটি কর্তৃপক্ষ ফুটপাত ব্যবহারের একটি খসড়া নির্দেশিকা পর্যালোচনা করেছে। এতে পথচারীর চলাচল, অস্থায়ী দোকান, প্রতিবন্ধী চলাচল, রাস্তার বাধা এবং নিরাপত্তা চিহ্ন নিয়ে প্রস্তাব রাখা হয়েছে।",
                        'seo_keywords'     => "ফুটপাত, পথচারী নিরাপত্তা, নগর নীতি",
                        'video_url'        => "https://www.youtube.com/embed/S6rmWZQqfos",
                        'published_at'     => "2026-02-09 13:15:00",
                    ],

                    (object) [
                        'title'            => "স্কুল জোনে গতি সীমা প্রস্তাব নমুনা পরিবহন নীতিতে",
                        'sub_title'        => "",
                        'content_shoulder' => "সড়ক নিরাপত্তা নীতি",
                        'brief'            => "স্কুল ও লার্নিং সেন্টারের আশপাশে যানবাহনের গতি কমাতে নমুনা পরিবহন নীতিতে প্রস্তাব করা হয়েছে।",
                        'body'             => "নমুনা পরিবহন নীতিতে স্কুল, কলেজ ও কোচিং এলাকার কাছে বিশেষ গতি সীমা, জেব্রা ক্রসিং, সতর্কতামূলক সাইনবোর্ড এবং স্বেচ্ছাসেবী সহায়তার প্রস্তাব দেওয়া হয়েছে।",
                        'seo_keywords'     => "স্কুল জোন, গতি সীমা, সড়ক নিরাপত্তা",
                        'video_url'        => "https://www.youtube.com/embed/yIXvXVETpZo",
                        'published_at'     => "2026-02-11 14:40:00",
                    ],

                    (object) [
                        'title'            => "মডেল এলাকায় কমিউনিটি পানি ব্যবহার নীতি পরীক্ষা",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "সতর্ক পানি ব্যবহার ও দ্রুত লিক রিপোর্ট উৎসাহিত করতে মডেল এলাকায় ডেমো পানি নীতি পরীক্ষা হয়েছে।",
                        'body'             => "মডেল এলাকায় একটি কমিউনিটি পানি ব্যবহার নীতি পরীক্ষামূলকভাবে চালু হয়েছে। এতে পানি সরবরাহের সময়সূচি, পাইপ লিক রিপোর্ট, বৃষ্টির পানি সংরক্ষণ এবং দায়িত্বশীল গৃহস্থালি ব্যবহার নিয়ে সচেতনতা চালানো হয়।",
                        'seo_keywords'     => "পানি ব্যবহার, কমিউনিটি নীতি, পরিবেশ সচেতনতা",
                        'video_url'        => "https://www.youtube.com/embed/6butzqRy6dg",
                        'published_at'     => "2026-02-13 15:05:00",
                    ],

                    (object) [
                        'title'            => "ডেমো আইল্যান্ডে আঞ্চলিক উদ্ভাবন মেলা",
                        'sub_title'        => "সবুজ প্রযুক্তি ও ছোট ব্যবসার ধারণা প্রদর্শন",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো আইল্যান্ডে সবুজ প্রযুক্তি, ডিজিটাল শিক্ষা ও ক্ষুদ্র ব্যবসা নিয়ে কাল্পনিক আঞ্চলিক মেলা হয়েছে।",
                        'body'             => "ডেমো আইল্যান্ডে আয়োজিত আঞ্চলিক উদ্ভাবন মেলায় কাল্পনিক দেশগুলো পরিচ্ছন্ন শক্তি, ডিজিটাল শিক্ষা, কৃষি প্রযুক্তি এবং ক্ষুদ্র ব্যবসা সহায়তা নিয়ে নমুনা প্রকল্প দেখায়।",
                        'seo_keywords'     => "উদ্ভাবন মেলা, সবুজ প্রযুক্তি, আঞ্চলিক সহযোগিতা",
                        'video_url'        => "https://www.youtube.com/embed/M0JipBDwNDE",
                        'published_at'     => "2026-02-15 16:25:00",
                    ],

                    (object) [
                        'title'            => "স্মার্ট বর্ডার সেবা অ্যাপ পরীক্ষা করল নমুনা দেশ",
                        'sub_title'        => "",
                        'content_shoulder' => "ডিজিটাল সীমান্ত",
                        'brief'            => "নমুনা দেশ ভ্রমণ নোটিশ, ডকুমেন্ট চেকলিস্ট ও হেল্পডেস্কসহ স্মার্ট বর্ডার অ্যাপ পরীক্ষা করেছে।",
                        'body'             => "নমুনা দেশ একটি স্মার্ট বর্ডার সেবা অ্যাপ পরীক্ষামূলকভাবে চালু করেছে। অ্যাপে ভ্রমণ সতর্কতা, প্রয়োজনীয় কাগজপত্র, অ্যাপয়েন্টমেন্ট নির্দেশনা এবং জরুরি সহায়তার ডেমো তথ্য যুক্ত করা হয়েছে।",
                        'seo_keywords'     => "স্মার্ট বর্ডার, ভ্রমণ সেবা, ডিজিটাল অ্যাপ",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-17 17:10:00",
                    ],

                    (object) [
                        'title'            => "নিরাপদ ডিজিটাল শিক্ষা নিয়ে গ্লোবাল ডেমো ফোরাম",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "শিক্ষার্থী ও শিক্ষকদের জন্য নিরাপদ ডিজিটাল লার্নিং টুল নিয়ে কাল্পনিক গ্লোবাল ফোরামে আলোচনা হয়েছে।",
                        'body'             => "গ্লোবাল ডেমো ফোরামে অনলাইন শিক্ষা, শিক্ষার্থীর গোপনীয়তা, ক্লাসরুম প্রযুক্তি এবং দায়িত্বশীল কনটেন্ট ব্যবহারের বিষয়ে আলোচনা হয়। আলোচনায় নমুনা কেস ব্যবহার করে ডিজিটাল লার্নিং সুরক্ষা দেখানো হয়েছে।",
                        'seo_keywords'     => "ডিজিটাল শিক্ষা, অনলাইন লার্নিং, নিরাপদ শিক্ষা",
                        'video_url'        => "https://www.youtube.com/embed/M0JipBDwNDE",
                        'published_at'     => "2026-02-19 18:00:00",
                    ],

                    (object) [
                        'title'            => "দায়িত্বশীল জনযোগাযোগের আহ্বান মক সম্মেলনে",
                        'sub_title'        => "",
                        'content_shoulder' => "গ্লোবাল সংলাপ",
                        'brief'            => "মক গ্লোবাল সম্মেলনে সংকট, নীতি ও জনআলোচনায় দায়িত্বশীল যোগাযোগের গুরুত্ব তুলে ধরা হয়েছে।",
                        'body'             => "একটি কাল্পনিক গ্লোবাল সম্মেলনে দায়িত্বশীল জনযোগাযোগ, তথ্য যাচাই, ভুল তথ্য প্রতিরোধ এবং স্বচ্ছ সিদ্ধান্ত গ্রহণ নিয়ে আলোচনা হয়। কোনো বাস্তব সরকার বা বাস্তব বিরোধকে কেন্দ্র না করে এটি ডেমো কনটেন্ট হিসেবে তৈরি।",
                        'seo_keywords'     => "জনযোগাযোগ, তথ্য যাচাই, গ্লোবাল সংলাপ",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-21 09:35:00",
                    ],

                    (object) [
                        'title'            => "যুব অংশগ্রহণের প্রবণতা পর্যালোচনা করল ডেমো নেটওয়ার্ক",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "তরুণরা কীভাবে নাগরিক আলোচনায় যুক্ত হচ্ছে তা নিয়ে ডেমো পলিসি নেটওয়ার্ক একটি কাল্পনিক পর্যালোচনা প্রকাশ করেছে।",
                        'body'             => "ডেমো পলিসি নেটওয়ার্ক তরুণদের নাগরিক অংশগ্রহণ নিয়ে একটি কাল্পনিক প্রতিবেদন প্রকাশ করেছে। এতে শিক্ষার্থী ফোরাম, স্বেচ্ছাসেবী সংগঠন, অনলাইন আলোচনা এবং স্থানীয় নেতৃত্ব প্রশিক্ষণের বিষয় তুলে ধরা হয়।",
                        'seo_keywords'     => "যুব অংশগ্রহণ, নাগরিক সম্পৃক্ততা, পলিসি নেটওয়ার্ক",
                        'video_url'        => "https://www.youtube.com/embed/WUBWEukYOeQ",
                        'published_at'     => "2026-02-23 10:50:00",
                    ],

                    (object) [
                        'title'            => "দুই ডেমো দেশের সাংস্কৃতিক বিনিময় নোট সই",
                        'sub_title'        => "শিক্ষার্থী ও শিল্পীদের জন্য কাল্পনিক চুক্তি",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো রিপাবলিক ও স্যাম্পল কিংডম শিক্ষার্থী, শিল্পী ও গবেষকদের জন্য সাংস্কৃতিক বিনিময় নোট সই করেছে।",
                        'body'             => "ডেমো রিপাবলিক ও স্যাম্পল কিংডম শিক্ষার্থী সফর, শিল্প প্রদর্শনী, গবেষণা কর্মশালা এবং সাংস্কৃতিক উৎসব নিয়ে একটি কাল্পনিক বিনিময় নোট সই করেছে। এটি কূটনীতি ধাঁচের ডেমো সংবাদ হিসেবে ব্যবহারযোগ্য।",
                        'seo_keywords'     => "সাংস্কৃতিক বিনিময়, শিক্ষার্থী সফর, ডেমো কূটনীতি",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-25 11:40:00",
                    ],

                    (object) [
                        'title'            => "নমুনা দূতাবাসে ডিজিটাল হেল্প কর্নার",
                        'sub_title'        => "",
                        'content_shoulder' => "দূতাবাস সেবা",
                        'brief'            => "ভ্রমণ নির্দেশনা, অ্যাপয়েন্টমেন্ট ও ডকুমেন্ট চেকলিস্ট দেখাতে নমুনা দূতাবাসে ডিজিটাল হেল্প কর্নার চালু হয়েছে।",
                        'body'             => "নমুনা দূতাবাসে ডিজিটাল হেল্প কর্নার চালু করা হয়েছে। এখানে ভিসা অ্যাপয়েন্টমেন্ট, ভ্রমণ নির্দেশনা, ডকুমেন্ট চেকলিস্ট এবং জরুরি যোগাযোগের ডেমো তথ্য দেখানো হবে।",
                        'seo_keywords'     => "দূতাবাস সেবা, ডিজিটাল হেল্প, ভ্রমণ নির্দেশনা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-02-27 12:15:00",
                    ],

                    (object) [
                        'title'            => "ডেমো মার্কেটে সপ্তাহান্তে বিক্রি বেড়েছে",
                        'sub_title'        => "দোকানগুলোতে ক্রেতা চলাচল বেশি",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো মার্কেটে পোশাক, নিত্যপণ্য ও ইলেকট্রনিকস দোকানে সপ্তাহান্তে ক্রেতা বেড়েছে বলে নমুনা রিপোর্টে বলা হয়েছে।",
                        'body'             => "ডেমো মার্কেটে সপ্তাহান্তে ক্রেতা চলাচল বেড়েছে। পোশাক, গৃহস্থালি পণ্য, ইলেকট্রনিকস এবং খাবারের দোকানে বিক্রি আগের ডেমো সপ্তাহের তুলনায় বেশি হয়েছে বলে কাল্পনিক দোকানিরা জানিয়েছেন।",
                        'seo_keywords'     => "ডেমো মার্কেট, খুচরা বিক্রি, ব্যবসা সংবাদ",
                        'video_url'        => "https://www.youtube.com/embed/f-ZDjXiUjCk",
                        'published_at'     => "2026-03-01 13:20:00",
                    ],

                    (object) [
                        'title'            => "স্যাম্পল এক্সপোর্ট হাবে দ্রুত গুদাম চেক-ইন পরীক্ষা",
                        'sub_title'        => "",
                        'content_shoulder' => "বাণিজ্য লজিস্টিকস",
                        'brief'            => "লোডিং ও ডেলিভারি রেকর্ড দ্রুত করতে স্যাম্পল এক্সপোর্ট হাবে গুদাম চেক-ইন সিস্টেম পরীক্ষা করা হয়েছে।",
                        'body'             => "স্যাম্পল এক্সপোর্ট হাবে ট্রাক, গুদামকর্মী ও শিপমেন্ট রেকর্ডের জন্য ডিজিটাল চেক-ইন প্রক্রিয়া পরীক্ষা হয়েছে। এতে গেট টাইম, লোডিং সারি এবং ডেলিভারি কনফার্মেশন মাপা হয়।",
                        'seo_keywords'     => "এক্সপোর্ট হাব, গুদাম চেক-ইন, লজিস্টিকস",
                        'video_url'        => "https://www.youtube.com/embed/f-ZDjXiUjCk",
                        'published_at'     => "2026-03-03 14:45:00",
                    ],

                    (object) [
                        'title'            => "ক্ষুদ্র দোকানে ডিজিটাল পেমেন্ট ক্যাম্পেইন",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "মডেল বাজারের ক্ষুদ্র দোকানগুলো ডিজিটাল পেমেন্ট ও সহজ বিক্রি হিসাবের ডেমো ক্যাম্পেইনে যুক্ত হয়েছে।",
                        'body'             => "মডেল বাজারের পঞ্চাশটির বেশি কাল্পনিক দোকান ডিজিটাল পেমেন্ট ক্যাম্পেইনে অংশ নিয়েছে। দোকানিরা কিউআর পেমেন্ট পোস্টার, লেনদেন লগ এবং দৈনিক বিক্রির সারাংশ পরীক্ষা করছেন।",
                        'seo_keywords'     => "ডিজিটাল পেমেন্ট, ক্ষুদ্র ব্যবসা, কিউআর পেমেন্ট",
                        'video_url'        => "https://www.youtube.com/embed/y9jsygZW1vY",
                        'published_at'     => "2026-03-05 15:30:00",
                    ],

                    (object) [
                        'title'            => "গ্রিন বাস্কেট স্টোরের নতুন ডেমো আউটলেট",
                        'sub_title'        => "",
                        'content_shoulder' => "খুচরা ব্যবসা",
                        'brief'            => "গ্রিন বাস্কেট স্টোর নতুন ডেমো আউটলেটে তাজা খাবার, গৃহস্থালি পণ্য ও দ্রুত চেকআউট সুবিধা এনেছে।",
                        'body'             => "গ্রিন বাস্কেট স্টোর লেক রোডে একটি কাল্পনিক নতুন আউটলেট খুলেছে। আউটলেটে তাজা খাবার, গৃহস্থালি পণ্য, কাস্টমার সাপোর্ট ডেস্ক এবং এক্সপ্রেস চেকআউট লেন রাখা হয়েছে।",
                        'seo_keywords'     => "রিটেইল আউটলেট, গ্রিন বাস্কেট, ক্ষুদ্র ব্যবসা",
                        'video_url'        => "https://www.youtube.com/embed/f-ZDjXiUjCk",
                        'published_at'     => "2026-03-07 16:10:00",
                    ],

                    (object) [
                        'title'            => "ডেমো স্টক ইনডেক্স সামান্য ঊর্ধ্বমুখী",
                        'sub_title'        => "নির্বাচিত শেয়ারে ক্রয়চাপ",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো স্টক ইনডেক্স ব্যাংকিং ও প্রযুক্তি শেয়ারে কাল্পনিক বিনিয়োগকারীদের ক্রয়ে সামান্য বেড়েছে।",
                        'body'             => "ডেমো স্টক ইনডেক্স দিনের লেনদেন শেষে সামান্য ঊর্ধ্বমুখী হয়েছে। ব্যাংকিং, প্রযুক্তি ও ভোক্তা খাতের নির্বাচিত শেয়ারে ক্রয়চাপ থাকলেও বিনিয়োগকারীরা সতর্ক অবস্থানে ছিলেন।",
                        'seo_keywords'     => "স্টক ইনডেক্স, শেয়ারবাজার, বিনিয়োগ",
                        'video_url'        => "https://www.youtube.com/embed/b0_CsTFjtus",
                        'published_at'     => "2026-03-09 17:25:00",
                    ],

                    (object) [
                        'title'            => "রিয়েল-টাইম প্রাইস অ্যালার্ট পরীক্ষা করল স্যাম্পল এক্সচেঞ্জ",
                        'sub_title'        => "",
                        'content_shoulder' => "মার্কেট টেক",
                        'brief'            => "স্যাম্পল এক্সচেঞ্জ ডেমো বিনিয়োগকারী ও ব্রোকারেজ ড্যাশবোর্ডে রিয়েল-টাইম প্রাইস অ্যালার্ট পরীক্ষা করেছে।",
                        'body'             => "স্যাম্পল এক্সচেঞ্জ কাল্পনিক তালিকাভুক্ত কোম্পানি ও মক ট্রেডিং ডেটা দিয়ে প্রাইস অ্যালার্ট ফিচার পরীক্ষা করেছে। নির্দিষ্ট ডেমো দামের ওপরে বা নিচে শেয়ার গেলে ব্যবহারকারী নোটিফিকেশন পাবে।",
                        'seo_keywords'     => "প্রাইস অ্যালার্ট, স্টক মার্কেট, ট্রেডিং টেকনোলজি",
                        'video_url'        => "https://www.youtube.com/embed/p7HKvqRI_Bo",
                        'published_at'     => "2026-03-11 18:05:00",
                    ],

                    (object) [
                        'title'            => "ডেমো ব্যাংকে শিক্ষার্থী সঞ্চয় হিসাব পরীক্ষা",
                        'sub_title'        => "তরুণ ব্যবহারকারীদের জন্য নতুন সেবা",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো ব্যাংক ছোট সঞ্চয় লক্ষ্য, ডিজিটাল স্টেটমেন্ট ও আর্থিক শিক্ষা সহ শিক্ষার্থী সঞ্চয় হিসাব পরীক্ষা করছে।",
                        'body'             => "ডেমো ব্যাংক শিক্ষার্থীদের জন্য একটি কাল্পনিক সঞ্চয় হিসাব চালু করেছে। এতে মাসিক সঞ্চয় লক্ষ্য, অভিভাবক অ্যালার্ট, ডিজিটাল স্টেটমেন্ট এবং সহজ আর্থিক শিক্ষা টিপস যুক্ত রয়েছে।",
                        'seo_keywords'     => "শিক্ষার্থী সঞ্চয়, ব্যাংকিং, আর্থিক শিক্ষা",
                        'video_url'        => "https://www.youtube.com/embed/b0_CsTFjtus",
                        'published_at'     => "2026-03-13 09:00:00",
                    ],

                    (object) [
                        'title'            => "ঋণ আবেদন দ্রুত পর্যালোচনা করছে স্যাম্পল ব্যাংক",
                        'sub_title'        => "",
                        'content_shoulder' => "ঋণ সেবা",
                        'brief'            => "ব্যক্তিগত ও ক্ষুদ্র ব্যবসার ঋণ আবেদন দ্রুত যাচাই করতে স্যাম্পল ব্যাংক নতুন ডেমো ওয়ার্কফ্লো পরীক্ষা করছে।",
                        'body'             => "স্যাম্পল ব্যাংক কাল্পনিক গ্রাহক প্রোফাইল ব্যবহার করে ঋণ আবেদন যাচাইয়ের ডেমো প্রক্রিয়া চালু করেছে। এতে আয়, ঋণ পরিশোধ সক্ষমতা, কাগজপত্রের পূর্ণতা এবং ঝুঁকি মূল্যায়ন দেখা হবে।",
                        'seo_keywords'     => "ঋণ আবেদন, ব্যাংকিং সেবা, ফাইন্যান্স",
                        'video_url'        => "https://www.youtube.com/embed/bb6_M_srMBk",
                        'published_at'     => "2026-03-15 10:40:00",
                    ],

                    (object) [
                        'title'            => "ডেমো ইলেকট্রনিকসের বার্ষিক পার্টনার মিট",
                        'sub_title'        => "রিটেইল পার্টনারদের সঙ্গে শোরুম পরিকল্পনা পর্যালোচনা",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো ইলেকট্রনিকস শোরুম সম্প্রসারণ, ওয়ারেন্টি ও গ্রাহকসেবা নিয়ে কাল্পনিক পার্টনার মিট করেছে।",
                        'body'             => "ডেমো ইলেকট্রনিকস কাল্পনিক পরিবেশক ও রিটেইল পার্টনারদের নিয়ে বার্ষিক মিট আয়োজন করেছে। অনুষ্ঠানে শোরুম ডিজাইন, পণ্য প্রদর্শন, ওয়ারেন্টি ব্যবস্থাপনা এবং বিক্রয়-পরবর্তী সেবা নিয়ে আলোচনা হয়।",
                        'seo_keywords'     => "ইলেকট্রনিকস, পার্টনার মিট, কর্পোরেট ব্যবসা",
                        'video_url'        => "https://www.youtube.com/embed/f-ZDjXiUjCk",
                        'published_at'     => "2026-03-17 11:20:00",
                    ],

                    (object) [
                        'title'            => "স্যাম্পল ফুডসের পরিবেশবান্ধব প্যাকেজিং পরীক্ষা",
                        'sub_title'        => "",
                        'content_shoulder' => "সবুজ প্যাকেজিং",
                        'brief'            => "স্যাম্পল ফুডস পুনর্ব্যবহারযোগ্য কাগজের বাক্স ও কম প্লাস্টিক ব্যবহার করে ডেমো প্যাকেজিং পরীক্ষা করছে।",
                        'body'             => "স্যাম্পল ফুডস নির্বাচিত স্ন্যাকস পণ্যে পরিবেশবান্ধব প্যাকেজিং পরীক্ষা শুরু করেছে। এই ডেমো পরীক্ষায় ক্রেতার প্রতিক্রিয়া, প্যাকেটের স্থায়িত্ব এবং উৎপাদন খরচ পর্যবেক্ষণ করা হবে।",
                        'seo_keywords'     => "সবুজ প্যাকেজিং, পরিবেশবান্ধব পণ্য, কর্পোরেট",
                        'video_url'        => "https://www.youtube.com/embed/6butzqRy6dg",
                        'published_at'     => "2026-03-19 12:45:00",
                    ],

                    (object) [
                        'title'            => "ব্লু স্টার লজিস্টিকসে চালক প্রশিক্ষণ কক্ষ",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "ব্লু স্টার লজিস্টিকস চালকদের সড়ক নিরাপত্তা, ডেলিভারি ট্র্যাকিং ও গ্রাহক আচরণ শেখাতে প্রশিক্ষণ কক্ষ খুলেছে।",
                        'body'             => "ব্লু স্টার লজিস্টিকস একটি কাল্পনিক ডিপোতে চালক প্রশিক্ষণ কক্ষ চালু করেছে। প্রশিক্ষণে সড়ক নিরাপত্তা, রুট পরিকল্পনা, ডেলিভারি অ্যাপ ব্যবহার এবং গ্রাহক যোগাযোগের বিষয় রাখা হয়েছে।",
                        'seo_keywords'     => "লজিস্টিকস, চালক প্রশিক্ষণ, ডেলিভারি সেবা",
                        'video_url'        => "https://www.youtube.com/embed/S6rmWZQqfos",
                        'published_at'     => "2026-03-21 13:30:00",
                    ],

                    (object) [
                        'title'            => "স্থানীয় মেরামত সেবার জন্য অ্যাপ বানাল ডেমো স্টার্টআপ",
                        'sub_title'        => "ব্যবহারকারী ও টেকনিশিয়ান সংযোগের প্ল্যাটফর্ম",
                        'content_shoulder' => "",
                        'brief'            => "একটি কাল্পনিক স্টার্টআপ ইলেকট্রিশিয়ান, প্লাম্বার ও যন্ত্র মেরামতকারীর সঙ্গে ব্যবহারকারীদের যুক্ত করার অ্যাপ বানিয়েছে।",
                        'body'             => "ফিক্সমেট ডেমো নামে একটি স্টার্টআপ স্থানীয় মেরামত সেবার অ্যাপ চালু করেছে। অ্যাপে টেকনিশিয়ান বুকিং, রেটিং, সময় নির্বাচন, সেবা ক্যাটাগরি এবং পেমেন্ট ফ্লো পরীক্ষা করা হচ্ছে।",
                        'seo_keywords'     => "স্টার্টআপ, মেরামত অ্যাপ, লোকাল সার্ভিস",
                        'video_url'        => "https://www.youtube.com/embed/ul37Ooeb-Mo",
                        'published_at'     => "2026-03-23 14:55:00",
                    ],

                    (object) [
                        'title'            => "ফসল পরামর্শ চ্যাটবট পরীক্ষা করল স্যাম্পল এগ্রিটেক",
                        'sub_title'        => "",
                        'content_shoulder' => "এগ্রিটেক ডেমো",
                        'brief'            => "স্যাম্পল এগ্রিটেক কাল্পনিক কৃষি ডেটা দিয়ে ফসল পরিচর্যা, পানি ও পোকামাকড় সতর্কতা চ্যাটবট পরীক্ষা করছে।",
                        'body'             => "স্যাম্পল এগ্রিটেক একটি ফসল পরামর্শ চ্যাটবট পরীক্ষা করছে। চ্যাটবটটি কৃষকদের পানি দেওয়া, পোকামাকড় সতর্কতা, সার প্রয়োগের সময় এবং ফসল কাটার প্রস্তুতি নিয়ে ডেমো টিপস দেবে।",
                        'seo_keywords'     => "এগ্রিটেক, কৃষি চ্যাটবট, ফসল পরামর্শ",
                        'video_url'        => "https://www.youtube.com/embed/XWRtG_PDRik",
                        'published_at'     => "2026-03-25 15:35:00",
                    ],

                    (object) [
                        'title'            => "লার্নিং স্টার্টআপে মিনি গেম ফিচার",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো লার্নিং স্টার্টআপ পাঠ অনুশীলন ও শিক্ষার্থী সম্পৃক্ততা বাড়াতে মিনি গেম যোগ করেছে।",
                        'body'             => "ব্রাইটপাথ ডেমো তার কাল্পনিক লার্নিং অ্যাপে কুইজ গেম, শব্দধাঁধা এবং স্কোর ব্যাজ যোগ করেছে। ছোট ইন্টারঅ্যাকটিভ কার্যক্রম শিক্ষার্থীদের নিয়মিত অনুশীলনে কীভাবে সাহায্য করে, তা পরীক্ষা করা হচ্ছে।",
                        'seo_keywords'     => "লার্নিং অ্যাপ, মিনি গেম, শিক্ষা স্টার্টআপ",
                        'video_url'        => "https://www.youtube.com/embed/M0JipBDwNDE",
                        'published_at'     => "2026-03-27 16:15:00",
                    ],

                    (object) [
                        'title'            => "ডেমো সিটি ম্যারাথনে রেকর্ড স্থানীয় অংশগ্রহণ",
                        'sub_title'        => "কমিউনিটি রেসে ফিটনেসের বার্তা",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো সিটি ম্যারাথনে স্কুল, ক্লাব ও ফিটনেস গ্রুপের রেকর্ডসংখ্যক কাল্পনিক দৌড়বিদ অংশ নিয়েছে।",
                        'body'             => "ডেমো সিটি ম্যারাথনে স্থানীয় স্কুল, ক্রীড়া ক্লাব ও ফিটনেস গ্রুপের দৌড়বিদরা অংশ নেন। কাল্পনিক ইভেন্টে ৫ কিলোমিটার ফান রান, ১০ কিলোমিটার সিটি রুট এবং পরিবারভিত্তিক হাঁটা পর্ব ছিল।",
                        'seo_keywords'     => "ম্যারাথন, স্থানীয় দৌড়বিদ, ফিটনেস ইভেন্ট",
                        'video_url'        => "https://www.youtube.com/embed/368ZynFYPHc",
                        'published_at'     => "2026-03-29 17:00:00",
                    ],

                    (object) [
                        'title'            => "স্যাম্পল ফুটবল একাডেমিতে স্প্রিং ক্যাম্প",
                        'sub_title'        => "",
                        'content_shoulder' => "যুব ক্রীড়া",
                        'brief'            => "স্যাম্পল ফুটবল একাডেমি তরুণ খেলোয়াড়দের ফিটনেস, দলীয় কাজ ও মৌলিক কৌশল শেখাতে স্প্রিং ক্যাম্প খুলেছে।",
                        'body'             => "স্যাম্পল ফুটবল একাডেমির কাল্পনিক স্প্রিং ক্যাম্পে জুনিয়র খেলোয়াড়দের পাসিং, শুটিং, ফিটনেস ড্রিল, দলীয় সমন্বয় এবং ম্যাচ শৃঙ্খলা শেখানো হবে।",
                        'seo_keywords'     => "ফুটবল একাডেমি, যুব ফুটবল, স্পোর্টস ক্যাম্প",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-03-31 18:20:00",
                    ],

                    (object) [
                        'title'            => "ডেমো ভলিবল টুর্নামেন্ট জিতল রিভার ক্লাব",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "গ্রিন কোর্ট ক্লাবের বিপক্ষে উত্তেজনাপূর্ণ ফাইনাল জিতে রিভার ক্লাব ডেমো ভলিবল টুর্নামেন্টের শিরোপা পেয়েছে।",
                        'body'             => "মডেল স্পোর্টস কমপ্লেক্সে অনুষ্ঠিত ডেমো ভলিবল টুর্নামেন্টের ফাইনালে রিভার ক্লাব গ্রিন কোর্ট ক্লাবকে হারায়। কাল্পনিক ম্যাচে শক্তিশালী সার্ভ, দ্রুত ব্লক এবং দর্শকদের উৎসাহ ছিল চোখে পড়ার মতো।",
                        'seo_keywords'     => "ভলিবল টুর্নামেন্ট, রিভার ক্লাব, ক্রীড়া সংবাদ",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-04-02 09:15:00",
                    ],

                    (object) [
                        'title'            => "পাঁচ উইকেটে ডেমো টাইগার্সের অনুশীলন ম্যাচ জয়",
                        'sub_title'        => "তরুণ ব্যাটারদের শান্ত জুটি",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো টাইগার্স কাল্পনিক অনুশীলন ম্যাচে পাঁচ উইকেটে জিতেছে, মাঝের সারির ব্যাটাররা জয়ের ভিত গড়েছে।",
                        'body'             => "সেন্ট্রাল গ্রাউন্ডে অনুষ্ঠিত কাল্পনিক অনুশীলন ম্যাচে ডেমো টাইগার্স ১৪৬ রানের লক্ষ্য তাড়া করে জেতে। বোলাররা শুরুতে রান নিয়ন্ত্রণে রাখে এবং পরে দুই তরুণ ব্যাটার শান্ত জুটি গড়ে ম্যাচ শেষ করে।",
                        'seo_keywords'     => "ডেমো ক্রিকেট, অনুশীলন ম্যাচ, ব্যাটিং",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-04-04 10:35:00",
                    ],

                    (object) [
                        'title'            => "জুনিয়র ড্রাফট ঘোষণা করল স্যাম্পল ক্রিকেট লিগ",
                        'sub_title'        => "",
                        'content_shoulder' => "জুনিয়র ক্রিকেট",
                        'brief'            => "চারটি যুব দলের জন্য কাল্পনিক খেলোয়াড় বাছাই করতে স্যাম্পল ক্রিকেট লিগ জুনিয়র ড্রাফট ঘোষণা করেছে।",
                        'body'             => "স্যাম্পল ক্রিকেট লিগ চারটি কাল্পনিক যুব দলের জন্য জুনিয়র ড্রাফট ঘোষণা করেছে। আয়োজকেরা বলছেন, খেলোয়াড় প্রোফাইল, দল পেজ, ফিক্সচার এবং ম্যাচ রেজাল্ট লেআউট পরীক্ষা করতেই এই ডেমো ড্রাফট।",
                        'seo_keywords'     => "জুনিয়র ক্রিকেট, ক্রিকেট ড্রাফট, যুব ক্রিকেট",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-04-06 11:10:00",
                    ],

                    (object) [
                        'title'            => "ডেমো ওটিটি প্ল্যাটফর্মে শর্ট ফিল্ম কালেকশন",
                        'sub_title'        => "দর্শকদের জন্য পাঁচ কাল্পনিক গল্প",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো ওটিটি প্ল্যাটফর্ম কমেডি, পরিবার, রহস্য ও তরুণদের গল্প নিয়ে পাঁচটি কাল্পনিক শর্ট ফিল্ম যুক্ত করেছে।",
                        'body'             => "স্ট্রিমবক্স ডেমো একটি শর্ট ফিল্ম কালেকশন প্রকাশ করেছে। এতে কমেডি, পারিবারিক নাটক, রহস্য এবং তরুণ প্রজন্মের গল্প রয়েছে। কনটেন্টটি ওটিটি পেজ, থাম্বনেইল ও সারাংশ পরীক্ষা করার জন্য তৈরি।",
                        'seo_keywords'     => "ওটিটি, শর্ট ফিল্ম, স্ট্রিমিং প্ল্যাটফর্ম",
                        'video_url'        => "https://www.youtube.com/embed/aqz-KE-bpKQ",
                        'published_at'     => "2026-04-08 12:25:00",
                    ],

                    (object) [
                        'title'            => "নমুনা ওয়েব সিরিজ ট্রেইলারে ডেমো সাড়া",
                        'sub_title'        => "",
                        'content_shoulder' => "ওয়েব সিরিজ",
                        'brief'            => "একটি কাল্পনিক ওয়েব সিরিজের ট্রেইলার সোশ্যাল ও স্ট্রিমিং প্রিভিউ পেজে ভালো ডেমো রেসপন্স পেয়েছে।",
                        'body'             => "সিটি লাইটস নামের কাল্পনিক ওয়েব সিরিজের ট্রেইলার একটি নমুনা ওটিটি প্ল্যাটফর্মে প্রকাশের পর ডেমো দর্শকদের সাড়া পায়। রহস্যধর্মী থিম, দ্রুত এডিটিং এবং চরিত্রভিত্তিক টিজার নিয়ে আলোচনা হয়।",
                        'seo_keywords'     => "ওয়েব সিরিজ, ট্রেইলার, ওটিটি বিনোদন",
                        'video_url'        => "https://www.youtube.com/embed/aqz-KE-bpKQ",
                        'published_at'     => "2026-04-10 13:40:00",
                    ],

                    (object) [
                        'title'            => "ডেমো ক্লিনিকে বিনামূল্যে রক্তচাপ পরীক্ষা সপ্তাহ",
                        'sub_title'        => "স্বাস্থ্য সচেতনতা বুথে বাসিন্দাদের অংশগ্রহণ",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো ক্লিনিক রক্তচাপ পরীক্ষা, ওজন মাপা ও জীবনযাপন পরামর্শ নিয়ে বিনামূল্যে স্বাস্থ্য সচেতনতা সপ্তাহ শুরু করেছে।",
                        'body'             => "ডেমো ক্লিনিকে স্বাস্থ্য সচেতনতা বুথ চালু হয়েছে। এখানে রক্তচাপ পরীক্ষা, ওজন মাপা, পানির অভ্যাস, খাবার নিয়ন্ত্রণ এবং নিয়মিত হাঁটার বিষয়ে নমুনা পরামর্শ দেওয়া হবে।",
                        'seo_keywords'     => "রক্তচাপ পরীক্ষা, স্বাস্থ্য সচেতনতা, ডেমো ক্লিনিক",
                        'video_url'        => "https://www.youtube.com/embed/368ZynFYPHc",
                        'published_at'     => "2026-04-12 14:20:00",
                    ],

                    (object) [
                        'title'            => "স্যাম্পল হাসপাতালে ডিজিটাল অ্যাপয়েন্টমেন্ট টোকেন",
                        'sub_title'        => "",
                        'content_shoulder' => "হাসপাতাল সেবা",
                        'brief'            => "আউটডোর কাউন্টারের অপেক্ষা কমাতে স্যাম্পল হাসপাতাল ডিজিটাল অ্যাপয়েন্টমেন্ট টোকেন পরীক্ষা করছে।",
                        'body'             => "স্যাম্পল হাসপাতাল পরীক্ষামূলক ডিজিটাল অ্যাপয়েন্টমেন্ট টোকেন চালু করেছে। এতে রোগীর সিরিয়াল, ডাক্তারের কক্ষ নম্বর এবং আনুমানিক অপেক্ষার সময় দেখা যাবে।",
                        'seo_keywords'     => "হাসপাতাল টোকেন, অ্যাপয়েন্টমেন্ট, স্বাস্থ্য সেবা",
                        'video_url'        => "https://www.youtube.com/embed/cuLH7SyWXFk",
                        'published_at'     => "2026-04-14 15:45:00",
                    ],

                    (object) [
                        'title'            => "গ্রিন পার্কে সকালবেলার ফিটনেস সচেতনতা ক্যাম্প",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "হাঁটা, হালকা স্ট্রেচিং ও স্বাস্থ্যকর নাশতার অভ্যাস নিয়ে গ্রিন পার্কে ডেমো ফিটনেস ক্যাম্প হয়েছে।",
                        'body'             => "গ্রিন পার্কে স্থানীয় বাসিন্দাদের জন্য সকালবেলার ফিটনেস ক্যাম্প আয়োজন করা হয়। প্রশিক্ষকেরা হাঁটার রুটিন, হালকা ব্যায়াম, পর্যাপ্ত পানি পান এবং ভারসাম্যপূর্ণ নাশতার বিষয়ে ডেমো নির্দেশনা দেন।",
                        'seo_keywords'     => "ফিটনেস ক্যাম্প, সকাল ব্যায়াম, স্বাস্থ্য সচেতনতা",
                        'video_url'        => "https://www.youtube.com/embed/pr29CWlkg8Y",
                        'published_at'     => "2026-04-16 16:30:00",
                    ],

                    (object) [
                        'title'            => "ডেমো বোর্ডের অনুশীলন পরীক্ষা সূচি প্রকাশ",
                        'sub_title'        => "আগামী সপ্তাহে শুরু হবে মক পরীক্ষা",
                        'content_shoulder' => "",
                        'brief'            => "ডেমো শিক্ষা বোর্ড ভাষা, গণিত, বিজ্ঞান ও সাধারণ জ্ঞানের অনুশীলন পরীক্ষা সূচি প্রকাশ করেছে।",
                        'body'             => "ডেমো শিক্ষা বোর্ড কাল্পনিক শিক্ষার্থীদের জন্য অনুশীলন পরীক্ষা সূচি প্রকাশ করেছে। এক সপ্তাহের রুটিনে ভাষা, গণিত, বিজ্ঞান, সামাজিক বিজ্ঞান এবং সাধারণ জ্ঞানের পরীক্ষা রাখা হয়েছে।",
                        'seo_keywords'     => "অনুশীলন পরীক্ষা, পরীক্ষার সূচি, শিক্ষা বোর্ড",
                        'video_url'        => "https://www.youtube.com/embed/M0JipBDwNDE",
                        'published_at'     => "2026-04-18 17:05:00",
                    ],

                    (object) [
                        'title'            => "অনলাইন অ্যাডমিট কার্ড যাচাই পরীক্ষা করল নমুনা কেন্দ্র",
                        'sub_title'        => "",
                        'content_shoulder' => "পরীক্ষা সেবা",
                        'brief'            => "মক পরীক্ষার আগে নমুনা পরীক্ষা কেন্দ্র অনলাইন অ্যাডমিট কার্ড যাচাই ফিচার পরীক্ষা করেছে।",
                        'body'             => "নমুনা পরীক্ষা কেন্দ্র অনলাইন অ্যাডমিট কার্ড যাচাই ফিচার পরীক্ষা করেছে। শিক্ষার্থীরা ডেমো রোল নম্বর দিলে আসন পরিকল্পনা, বিষয় তালিকা এবং পরীক্ষার সময় দেখতে পারবে।",
                        'seo_keywords'     => "অ্যাডমিট কার্ড, অনলাইন পরীক্ষা, শিক্ষার্থী সেবা",
                        'video_url'        => "https://www.youtube.com/embed/M7lc1UVf-VE",
                        'published_at'     => "2026-04-20 18:25:00",
                    ],

                    (object) [
                        'title'            => "ডেমো শিক্ষার্থীদের জন্য মক রেজাল্ট পোর্টাল",
                        'sub_title'        => "",
                        'content_shoulder' => "",
                        'brief'            => "গ্রেড, বিষয়ভিত্তিক নম্বর ও প্রিন্টযোগ্য ফলাফল পেজ পরীক্ষা করতে মক রেজাল্ট পোর্টাল চালু হয়েছে।",
                        'body'             => "ডেমো শিক্ষার্থীদের জন্য একটি কাল্পনিক রেজাল্ট পোর্টাল চালু করা হয়েছে। এতে নমুনা নম্বর, গ্রেড, বিষয়ভিত্তিক ফলাফল এবং প্রিন্টযোগ্য রেজাল্ট শিট দেখা যাবে।",
                        'seo_keywords'     => "মক রেজাল্ট, রেজাল্ট পোর্টাল, পরীক্ষার ফলাফল",
                        'video_url'        => "https://www.youtube.com/embed/M0JipBDwNDE",
                        'published_at'     => "2026-05-06 13:10:00",
                    ],

                ]),
            ],
        ]);
    }

}
