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

        $languages = Language::all()->keyBy('code');

        $newsStoriesByLanguageGroups = $this->newsStoriesByLanguageGroups();
        foreach ($newsStoriesByLanguageGroups as $newsStoriesByLanguageGroup) {
            $language = $languages[$newsStoriesByLanguageGroup->language_code] ?? null;
            foreach ($newsStoriesByLanguageGroup->newses as $news) {
                $category       = $this->getRandomCategory($language, $news->category_name) ?? null;
                $location       = $this->getRandomLocation($language, $category) ?? null;
                $tagIds         = $this->getRandomTagIds($language) ?? [];
                $event          = $this->getRandomEvent($language) ?? null;
                $contributorIds = $this->getRandomContributorIds($language) ?? [];

                $newsType = NewsHelper::NEWS_TYPE_STORY;

                $news = News::factory()->state([
                    "news_type"        => $newsType,

                    "language_id"      => $language?->id ?? "1",
                    "category_id"      => $category?->id ?? "1",

                    "event_id"         => $event?->id ?? null,
                    "location_id"      => $location?->id ?? null,

                    'heading'          => $news->headline,
                    'sub_heading'      => $news->sub_heading,
                    'content_shoulder' => $news->content_shoulder,
                    'brief'            => $news->brief,

                    "body"             => $news->body,

                    "video_url"        => null,

                    "page_section"     => null,

                    'seo_title'        => $news->headline,
                    'seo_brief'        => $news->brief,
                    'seo_keywords'     => $news->seo_keywords,

                    "is_published"     => true,
                    'created_at'       => $news->published_at,
                    'updated_at'       => $news->published_at,

                ])->create();

                if ($tagIds) {
                    $news->tags()->sync($tagIds);
                }

                if ($contributorIds && fake()->boolean(20)) {
                    $news->contributors()->sync($contributorIds);
                }

                $this->newsAddFeatureImage($news);
                $this->newsAddThumbnail($news);

            }
        }
    }

    private function getRandomCategory(Language $language, string $categoryName): ?Category
    {
        return Category::where("language_id", $language->id)->where("name", $categoryName)->inRandomOrder()->first() ?? null;
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
        $imageFileName  = MediaHelper::generateMediaName($news->headline, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->headline)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->headline,
                    'alt'     => $news->headline,
                    "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function newsAddThumbnail(News $news)
    {
        $imageUrl = asset("uploads/images/news/thumbnail.png");

        $imageExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
        $imageFileName  = MediaHelper::generateMediaName($news->headline, $imageExtension, 200);
        $news->addMediaFromUrl($imageUrl)
            ->usingName($news->headline)
            ->usingFileName($imageFileName)
            ->withCustomProperties(
                [
                    'caption' => $news->headline,
                    'alt'     => $news->headline,
                    "role"    => MediaHelper::MEDIA_ROLE_NEWS_THUMBNAIL_IMAGE,
                ]
            )
            ->toMediaCollection($news->media_collection_name);
    }

    private function newsStoriesByLanguageGroups()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'newses'        => collect([

                    (object) [
                        "category_name"    => "National",
                        'name'             => "PM Opens Four-Day DC Conference in Dhaka",
                        'sub_heading'      => "Field officials told to deliver government pledges",
                        'content_shoulder' => "",
                        'brief'            => "Prime Minister Tarique Rahman opened the DC Conference in Dhaka, directing field administrators to focus on public service delivery.",
                        "body"             => "Prime Minister Tarique Rahman inaugurated the four-day Deputy Commissioners Conference at Osmani Memorial Auditorium in Dhaka. He instructed deputy commissioners to work with accountability, transparency and efficiency while implementing the government’s public pledges. The conference will continue until May 6 and includes divisional commissioners and DCs from all 64 districts.",
                        'seo_keywords'     => "DC Conference, Bangladesh administration, Tarique Rahman",
                        'published_at'     => "2026-05-03 13:00:00",
                    ],

                    (object) [
                        "category_name"    => "National",
                        'name'             => "PM Says Government Inherited Tk30 Lakh Crore Debt Burden",
                        'sub_heading'      => "",
                        'content_shoulder' => "Debt Burden",
                        'brief'            => "The prime minister said the new government began work under heavy debt pressure while facing political and social challenges.",
                        "body"             => "Prime Minister Tarique Rahman said his government started its tenure with a debt burden of Tk30 lakh crore. Speaking at the opening of the DC Conference, he said officials must remain prepared to respond to political, social and economic challenges. He urged the administration to stay focused on public welfare and practical service delivery despite fiscal constraints.",
                        'seo_keywords'     => "Bangladesh debt, government finance, national economy",
                        'published_at'     => "2026-05-03 13:40:00",
                    ],

                    (object) [
                        "category_name"    => "National",
                        'name'             => "PM Urges DCs to Work With Spirit of Progress",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Deputy commissioners were asked to play a proactive role in fulfilling government promises through transparent administration.",
                        "body"             => "Prime Minister Tarique Rahman called on deputy commissioners to play an active role in implementing the promises made to citizens. He stressed the need for a people-friendly administration built on accountability, transparency and efficiency. The message came during the DC Conference, where field-level governance priorities were placed before senior administrators.",
                        'seo_keywords'     => "deputy commissioners, public service, Bangladesh governance",
                        'published_at'     => "2026-05-03 13:48:00",
                    ],

                    (object) [
                        "category_name"    => "National",
                        'name'             => "Dhaka-Sylhet Road and Rail Connectivity Upgrade Planned",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government plans to upgrade Dhaka-Sylhet road and rail links to improve travel, trade and regional economic connectivity.",
                        "body"             => "Prime Minister Tarique Rahman announced that the government will soon begin work to upgrade road and rail connectivity on the Dhaka-Sylhet route. The plan aims to ease passenger movement, improve trade flow and support regional economic activity. The announcement followed public concerns over difficult travel conditions on the route during recent political and public programmes.",
                        'seo_keywords'     => "Dhaka Sylhet road, rail connectivity, infrastructure",
                        'published_at'     => "2026-05-02 14:59:00",
                    ],

                    (object) [
                        "category_name"    => "National",
                        'name'             => "DC Conference Begins With 498 Public Service Proposals",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The annual DC Conference began with hundreds of proposals, with health-sector issues receiving the highest attention.",
                        "body"             => "The Deputy Commissioners Conference began with 498 proposals focusing on public service delivery across Bangladesh. According to reports, the highest number of proposals relate to the health sector, showing field administrators’ concern over healthcare access and service quality. The conference is expected to guide district-level policy implementation and coordination with ministries.",
                        'seo_keywords'     => "DC proposals, public service, health sector",
                        'published_at'     => "2026-05-02 21:07:00",
                    ],

                    (object) [
                        "category_name"    => "Politics",
                        'name'             => "Jamaat Secretary General Warns of Constitutional Crisis",
                        'sub_heading'      => "Party alleges referendum verdict is being ignored",
                        'content_shoulder' => "",
                        'brief'            => "Jamaat leader Mia Golam Porwar alleged the government is pushing Bangladesh toward a political and constitutional crisis.",
                        "body"             => "Bangladesh Jamaat-e-Islami Secretary General Mia Golam Porwar alleged that the government is steering the country toward a deep political and constitutional crisis. Speaking in Khulna, he claimed that the people’s referendum verdict is being denied under the cover of the July Charter. The statement reflects growing opposition pressure over reform implementation and constitutional questions.",
                        'seo_keywords'     => "Jamaat, constitutional crisis, Bangladesh politics",
                        'published_at'     => "2026-05-02 20:29:00",
                    ],

                    (object) [
                        "category_name"    => "Politics",
                        'name'             => "Jamaat Urges Government to Protect Press Freedom",
                        'sub_heading'      => "",
                        'content_shoulder' => "Press Freedom",
                        'brief'            => "Jamaat called for effective measures to ensure media freedom, journalist safety and transparency in information flow.",
                        "body"             => "Bangladesh Jamaat-e-Islami Secretary General Mia Golam Porwar urged the government to take effective steps to protect press freedom and journalist safety. He also called for transparency in the flow of information. The statement came around World Press Freedom Day, adding political weight to public debate over media independence and democratic accountability.",
                        'seo_keywords'     => "press freedom, Jamaat, journalist safety",
                        'published_at'     => "2026-05-02 23:39:00",
                    ],

                    (object) [
                        "category_name"    => "Politics",
                        'name'             => "Information Minister Says Free Media Is BNP Commitment",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Information Minister Zahir Uddin Swapon said free and independent media is a key commitment of the present government.",
                        "body"             => "Information and Broadcasting Minister Zahir Uddin Swapon said free and independent media is one of the key commitments of the BNP-led government. Speaking at a seminar marking World Press Freedom Day at the National Press Club, he said a modern democratic state requires responsible, independent journalism. The remarks came amid wider political debate over media rights.",
                        'seo_keywords'     => "BNP, free media, information minister",
                        'published_at'     => "2026-05-02 21:00:00",
                    ],

                    (object) [
                        "category_name"    => "Politics",
                        'name'             => "Mirza Fakhrul Says Bangladesh Is Rebounding From Past Crisis",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "BNP Secretary General Mirza Fakhrul Islam Alamgir said Bangladesh has begun recovering from years of political decline.",
                        "body"             => "BNP Secretary General and LGRD Minister Mirza Fakhrul Islam Alamgir said Bangladesh has begun to rise from what he described as the ruins left by the previous government. Speaking at a May Day rally in Dhaka, he connected workers’ rights with national rebuilding. His remarks carried both labour-focused and political messages for the new government’s agenda.",
                        'seo_keywords'     => "Mirza Fakhrul, BNP, May Day rally",
                        'published_at'     => "2026-05-01 19:00:00",
                    ],

                    (object) [
                        "category_name"    => "Politics",
                        'name'             => "Moyeen Khan Calls Workers the Nation’s Builders",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "BNP leader Abdul Moyeen Khan said workers are the true builders of the nation and central to Bangladesh’s development.",
                        "body"             => "BNP Standing Committee member and lawmaker Dr Abdul Moyeen Khan described workers as the real builders of Bangladesh. Addressing a May Day rally organised by Jatiyatabadi Sramik Dal at Nayapaltan, he said workers remain the driving force behind national development. The rally drew a large turnout despite rain in the capital.",
                        'seo_keywords'     => "Moyeen Khan, workers rally, Bangladesh politics",
                        'published_at'     => "2026-05-01 18:27:00",
                    ],

                    (object) [
                        "category_name"    => "Government",
                        'name'             => "PM Issues Directives on Government Priorities for Field Administration",
                        'sub_heading'      => "DCs told to make administration dynamic and people-friendly",
                        'content_shoulder' => "",
                        'brief'            => "The prime minister outlined government priorities for field administration during the opening of the DC Conference.",
                        "body"             => "Prime Minister Tarique Rahman issued directives on government policies and priorities for field administration while opening the annual DC Conference. He urged deputy commissioners to make the administration more dynamic and people-friendly. The conference is being used to align district-level work with the government’s broader agenda on service delivery and public accountability.",
                        'seo_keywords'     => "government priorities, field administration, DC Conference",
                        'published_at'     => "2026-05-03 12:34:00",
                    ],

                    (object) [
                        "category_name"    => "Government",
                        'name'             => "Government Measures Help Restore Economic Momentum",
                        'sub_heading'      => "",
                        'content_shoulder' => "Economic Momentum",
                        'brief'            => "A report says government actions on fuel supply, export financing and agro-industries are helping restore economic activity.",
                        "body"             => "Bangladesh’s economy and trade sector are gradually regaining momentum due to several government measures, according to BSS. The steps include resolving fuel supply issues, introducing low-interest export financing and prioritising agro-based industries. Officials say the policy mix is aimed at stabilising production, exports and employment after a difficult economic period.",
                        'seo_keywords'     => "economic momentum, export financing, government measures",
                        'published_at'     => "2026-05-03 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Government",
                        'name'             => "Government Approved Procurement of 9,000MT Lentils",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Cabinet purchase committee approved buying 9,000 metric tonnes of lentils to strengthen national reserves.",
                        "body"             => "The Cabinet Committee on Government Purchase approved a proposal to procure 9,000 metric tonnes of lentils under the Commerce Ministry. The procurement, valued at Tk72.53 crore, is intended to strengthen national reserves and improve food supply stability. The decision was chaired by Finance Adviser Amir Khasru Mahmud Chowdhury.",
                        'seo_keywords'     => "lentil procurement, food reserve, Bangladesh government",
                        'published_at'     => "2026-04-30 18:01:00",
                    ],

                    (object) [
                        "category_name"    => "Government",
                        'name'             => "Home Minister Visits Ramna Model Police Station",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Home Minister Salahuddin Ahmed visited Ramna Model Police Station to observe policing and service delivery activities.",
                        "body"             => "Home Minister Salahuddin Ahmed visited Ramna Model Police Station in Dhaka to observe law enforcement activities and public service delivery. The visit focused on how police stations are handling citizen services, routine policing and crime prevention. It also reflected the government’s stated emphasis on improving law and order through administrative supervision.",
                        'seo_keywords'     => "Home Minister, Ramna Police Station, law enforcement",
                        'published_at'     => "2026-05-02 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Government",
                        'name'             => "Chief Justice to Meet DCs and Divisional Commissioners",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Chief Justice Zubayer Rahman Chowdhury will meet divisional commissioners and deputy commissioners on May 5.",
                        "body"             => "Chief Justice Zubayer Rahman Chowdhury is scheduled to hold a courtesy meeting and directive session with divisional commissioners and deputy commissioners on May 5. The meeting will take place during the DC Conference period. It is expected to focus on coordination between judicial administration and field-level officials working across the country.",
                        'seo_keywords'     => "Chief Justice, deputy commissioners, judiciary",
                        'published_at'     => "2026-05-02 17:00:00",
                    ],

                    (object) [
                        "category_name"    => "Law & Order",
                        'name'             => "51 Crime Suspects Arrested in Tejgaon Police Drives",
                        'sub_heading'      => "DMP conducts coordinated drives in several crime-prone areas",
                        'content_shoulder' => "",
                        'brief'            => "Police arrested 51 suspects during routine operations in Tejgaon and nearby areas of Dhaka city.",
                        "body"             => "Dhaka Metropolitan Police arrested 51 individuals allegedly involved in different criminal activities during routine operations under Tejgaon Division. The drives covered Tejgaon, Sher-e-Bangla Nagar, Hatirjheel, Adabor, Mohammadpur and Tejgaon Industrial Area police stations. Police said the arrests were part of efforts to control crime in vulnerable urban areas.",
                        'seo_keywords'     => "Tejgaon arrests, DMP, crime suspects",
                        'published_at'     => "2026-05-02 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Law & Order",
                        'name'             => "Three Arrested Over Arson at Primary Education Store",
                        'sub_heading'      => "",
                        'content_shoulder' => "Arson Arrests",
                        'brief'            => "Police arrested three people in connection with an arson attack at a Directorate of Primary Education store in Mirpur.",
                        "body"             => "Police arrested three people over an arson attack at a store room of the Directorate of Primary Education in Mirpur-2, Dhaka. The arrestees were identified as Md Asmaul Islam, Md Jinnat Ali Biswas and Md Humayun Kabir Khan. The incident is being investigated under Mirpur Model Police Station as authorities assess damage and motive.",
                        'seo_keywords'     => "Mirpur arson, police arrest, primary education",
                        'published_at'     => "2026-05-02 22:00:00",
                    ],

                    (object) [
                        "category_name"    => "Law & Order",
                        'name'             => "Two Arrested in Demra With Firearms and Walkie-Talkie",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "RAB arrested two suspected robbers in Demra while they were allegedly preparing to commit dacoity.",
                        "body"             => "Rapid Action Battalion arrested two suspected robbers in Demra, Dhaka, with firearms, a handcuff and a walkie-talkie. RAB said the suspects were preparing to commit dacoity by impersonating law enforcement officials. The arrests were made during separate drives in the Mendipur area and near a CNG pump under Demra Police Station.",
                        'seo_keywords'     => "Demra arrest, firearms, RAB",
                        'published_at'     => "2026-05-02 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Law & Order",
                        'name'             => "PBI Arrests One in Tk60 Lakh Australia Job Scam",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Police Bureau of Investigation arrested a suspect linked to an alleged overseas job fraud ring.",
                        "body"             => "The Police Bureau of Investigation arrested a member of an organised fraud ring accused of embezzling around Tk60 lakh through fake Australia job offers. The fraud allegedly used Facebook advertisements to lure victims. The case was filed with Sarail Police Station on March 15 after a complaint from victim Mohammad Samal.",
                        'seo_keywords'     => "job scam, PBI arrest, Australia fraud",
                        'published_at'     => "2026-05-02 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Law & Order",
                        'name'             => "DB Arrests One With 1,000 Yaba Pills in Rajshahi",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Rajshahi DB police arrested a suspected drug dealer and recovered 1,000 yaba tablets during a raid.",
                        "body"             => "The Detective Branch of Rajshahi district police arrested a suspected drug dealer with 1,000 yaba tablets during an anti-drug raid. The operation was part of ongoing law enforcement activity against narcotics trafficking. Authorities said the suspect will face legal proceedings under the relevant narcotics control laws.",
                        'seo_keywords'     => "Rajshahi DB, yaba arrest, narcotics",
                        'published_at'     => "2026-05-02 13:00:00",
                    ],

                    (object) [
                        "category_name"    => "Elections",
                        'name'             => "EC Gazettes 49 Women MPs Elected Unopposed",
                        'sub_heading'      => "Reserved seat voting not required after uncontested nominations",
                        'content_shoulder' => "",
                        'brief'            => "The Election Commission gazetted 49 women as elected MPs in reserved seats of the 13th National Parliament.",
                        "body"             => "The Election Commission published a gazette declaring 49 women elected unopposed as Members of Parliament in reserved seats of the 13th National Parliament. No voting was required because there was no more than one candidate for the allocated seats within the alliances. The development finalises a major part of the reserved-seat process after the national election.",
                        'seo_keywords'     => "reserved seats, women MPs, Election Commission",
                        'published_at'     => "2026-04-30 19:55:00",
                    ],

                    (object) [
                        "category_name"    => "Elections",
                        'name'             => "EC Sets May 12 Poll Date for Women’s Reserved Seats",
                        'sub_heading'      => "",
                        'content_shoulder' => "Reserved Seats",
                        'brief'            => "The Election Commission announced the schedule for women’s reserved seats, setting May 12 as the voting date.",
                        "body"             => "The Election Commission announced the election schedule for women’s reserved seats of the 13th Parliament and set May 12 as the voting date. The schedule followed the formation of the new parliament after the February general election. The process is designed to fill reserved seats according to parliamentary strength and alliance allocations.",
                        'seo_keywords'     => "Election Commission, reserved seats, women MPs",
                        'published_at'     => "2026-04-08 21:08:00",
                    ],

                    (object) [
                        "category_name"    => "Elections",
                        'name'             => "EC Publishes Voter List for Women’s Reserved Seat Election",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Election Commission published the voter list for the women’s reserved seat election process in parliament.",
                        "body"             => "The Election Commission published the voter list for the women’s reserved seat election in the 13th Parliament. The list is part of the formal process for filling reserved seats after elected lawmakers take office. Publication of the list helped prepare the ground for nomination, scrutiny and possible voting under the reserved-seat election schedule.",
                        'seo_keywords'     => "voter list, women reserved seats, EC",
                        'published_at'     => "2026-04-06 21:02:00",
                    ],

                    (object) [
                        "category_name"    => "Elections",
                        'name'             => "Low Turnout and Irregularity Claims Mark Bogura-6 By-Polls",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Reports from Bogura-6 by-polls highlighted low voter turnout and allegations of irregularities during voting.",
                        "body"             => "The Bogura-6 by-polls were marked by low voter turnout and allegations of irregularities, according to election updates. Voting was held alongside the Sherpur-3 election. The reports reflected voter apathy in some constituencies after the national election and renewed attention on how the Election Commission manages smaller electoral contests.",
                        'seo_keywords'     => "Bogura-6 by-polls, voter turnout, election irregularities",
                        'published_at'     => "2026-04-09 16:04:00",
                    ],

                    (object) [
                        "category_name"    => "Elections",
                        'name'             => "59.44% Turnout Recorded in 13th National Election",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Election Commission confirmed 59.44 percent voter turnout in Bangladesh’s 13th National Parliamentary Election.",
                        "body"             => "The Election Commission confirmed that voter turnout in the 13th National Parliamentary Election reached 59.44 percent after postal votes were counted. The election was held on February 12 across the country. The final turnout figure became an important measure for assessing participation in the first national vote after the political transition.",
                        'seo_keywords'     => "national election, voter turnout, Bangladesh EC",
                        'published_at'     => "2026-02-13 12:19:00",
                    ],

                    (object) [
                        "category_name"    => "Public Policy",
                        'name'             => "Government Opens DC Conference With Policy Directives",
                        'sub_heading'      => "Field administration asked to align work with national priorities",
                        'content_shoulder' => "",
                        'brief'            => "The prime minister used the DC Conference to outline policy priorities for district-level administration.",
                        "body"             => "Prime Minister Tarique Rahman opened the DC Conference with directives on government policy priorities for field administration. The instructions focused on making public offices more responsive, efficient and people-friendly. Deputy commissioners are expected to coordinate with ministries and implement national policy decisions at district level throughout the year.",
                        'seo_keywords'     => "public policy, DC Conference, district administration",
                        'published_at'     => "2026-05-03 12:34:00",
                    ],

                    (object) [
                        "category_name"    => "Public Policy",
                        'name'             => "DSCC Launches Hawker Rehabilitation Under New Policy Framework",
                        'sub_heading'      => "",
                        'content_shoulder' => "Hawker Policy",
                        'brief'            => "Dhaka South City Corporation launched a hawker rehabilitation programme under a proposed city vending policy.",
                        "body"             => "Dhaka South City Corporation launched a modern hawker rehabilitation programme under the proposed Dhaka City Hawker Management Policy-2026. The policy framework aims to keep footpaths clear for pedestrians while bringing street vending into an organised system. The initiative is part of a broader urban-management effort to balance livelihoods and public mobility.",
                        'seo_keywords'     => "hawker policy, DSCC, urban management",
                        'published_at'     => "2026-04-30 21:32:00",
                    ],

                    (object) [
                        "category_name"    => "Public Policy",
                        'name'             => "JS Removes Age Limits for BSEC and IDRA Appointments",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Parliament passed amendments removing age caps for top appointments at BSEC and insurance regulator IDRA.",
                        "body"             => "Parliament passed amendments removing age limits for appointments to the Bangladesh Securities and Exchange Commission and the Insurance Development and Regulatory Authority. The BSEC amendment abolishes the existing 65-year age cap for the chair and commissioners, while the IDRA amendment removes the 67-year limit for its chair and members.",
                        'seo_keywords'     => "BSEC amendment, IDRA, parliament",
                        'published_at'     => "2026-04-30 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Public Policy",
                        'name'             => "Government Cuts Office Hours to Save Energy",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government announced austerity measures including shorter office hours and early market closures to save energy.",
                        "body"             => "The government announced austerity measures to reduce energy consumption amid pressure on fuel and power supply. Office hours were changed to 9am to 4pm, while shopping malls and markets were ordered to close by 6pm. The measures also include cutting fuel, power and gas expenses in government offices and suspending government-funded foreign training.",
                        'seo_keywords'     => "austerity measures, energy saving, office hours",
                        'published_at'     => "2026-04-03 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Public Policy",
                        'name'             => "WASA Amendment Bill Gives Government Appointment Powers",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Parliament passed a WASA amendment bill allowing government to appoint, remove officials and dissolve boards.",
                        "body"             => "Parliament passed the Water Supply and Sewerage Authority Amendment Bill, 2026, giving the government wider power to appoint and remove top officials and dissolve boards under special circumstances. The bill seeks to amend the existing WASA law and allow quicker administrative intervention in the public interest when water and sewerage authorities face governance problems.",
                        'seo_keywords'     => "WASA bill, public policy, parliament",
                        'published_at'     => "2026-04-08 14:19:00",
                    ],

                    (object) [
                        "category_name"    => "International",
                        'name'             => "Senior Iranian Officer Says Fresh Conflict With US Likely",
                        'sub_heading'      => "Iran’s latest proposal fails to calm regional tensions",
                        'content_shoulder' => "",
                        'brief'            => "A senior Iranian officer warned that renewed conflict with the US remains likely as Trump cast doubt on Tehran’s latest peace proposal through Pakistan.",
                        "body"             => "Iran’s senior military figure Mohammad Jafar Asadi said renewed fighting with the United States was likely, shortly after President Donald Trump expressed dissatisfaction with Tehran’s latest proposal. Iran delivered the draft through mediator Pakistan, but talks remain fragile after a war launched by the United States and Israel and a tense pause in direct fighting.",
                        'seo_keywords'     => "Iran, United States, Middle East war",
                        'published_at'     => "2026-05-02 22:18:00",
                    ],

                    (object) [
                        "category_name"    => "International",
                        'name'             => "Israel Says Two Gaza Flotilla Activists Taken for Questioning",
                        'sub_heading'      => "",
                        'content_shoulder' => "Gaza Flotilla",
                        'brief'            => "Israel said two activists from a Gaza-bound aid flotilla were brought in for questioning after more than 50 vessels were intercepted at sea by forces.",
                        "body"             => "Israel’s foreign ministry said two activists from a Gaza-bound aid flotilla were brought into the country for questioning after Israeli forces intercepted more than 50 vessels. The flotilla had sailed from European ports aiming to break the blockade and deliver aid to Gaza. Organisers disputed Israel’s account and accused Israeli personnel of detaining activists unlawfully.",
                        'seo_keywords'     => "Gaza flotilla, Israel, humanitarian aid",
                        'published_at'     => "2026-05-02 20:37:00",
                    ],

                    (object) [
                        "category_name"    => "International",
                        'name'             => "Iran War Pushes Up Travel Costs and Airline Pressure",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Jet fuel prices have surged since the Iran war began, raising airline operating costs and pushing up fares for travellers across major routes worldwide.",
                        "body"             => "Airlines are facing rising pressure as the Iran war disrupts fuel supply through the Strait of Hormuz. Reports cited aviation experts saying jet fuel prices in Europe more than doubled from February to late April. Airlines have warned that high fuel costs may force surcharges, flight cuts and fewer cheap seats, directly affecting travellers worldwide.",
                        'seo_keywords'     => "airline industry, Iran war, jet fuel",
                        'published_at'     => "2026-05-02 20:15:00",
                    ],

                    (object) [
                        "category_name"    => "International",
                        'name'             => "Trump Says US Navy Acting Like Pirates Amid Iran Blockade",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Donald Trump described a US Navy seizure of cargo and oil during the Iran blockade as tensions continue around the strategic Strait of Hormuz.",
                        "body"             => "US President Donald Trump said the US Navy was acting “like pirates” while describing a military seizure of ship cargo and oil linked to the Iran blockade. His remarks came amid legal and diplomatic concerns over Iran’s closure of the Strait of Hormuz and Washington’s counter-blockade on Iranian ports during the continuing Middle East crisis.",
                        'seo_keywords'     => "Trump, US Navy, Iran blockade",
                        'published_at'     => "2026-05-02 18:26:00",
                    ],

                    (object) [
                        "category_name"    => "International",
                        'name'             => "Nato Seeks Details on US Troop Cut From Germany",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Nato said it is working with Washington to understand a decision to cut US troops in Germany amid a dispute over Iran policy and defence burden.",
                        "body"             => "Nato said it was working with the United States to understand Washington’s plan to withdraw 5,000 troops from Germany. The move followed tension between President Donald Trump and German Chancellor Friedrich Merz over the Iran conflict. Nato said the decision underlined Europe’s need to invest more in defence while keeping alliance deterrence strong.",
                        'seo_keywords'     => "Nato, Germany, US troops",
                        'published_at'     => "2026-05-02 15:42:00",
                    ],

                    (object) [
                        "category_name"    => "Global Politics",
                        'name'             => "Trump Administration Accused of Defying Lower Court Rulings",
                        'sub_heading'      => "Judicial tensions grow over immigration, spending and executive power",
                        'content_shoulder' => "",
                        'brief'            => "The Trump administration faces accusations of repeated noncompliance with lower court rulings across immigration, spending and staffing cases.",
                        "body"             => "A review cited by Dhaka Tribune said the Trump administration has been accused of ignoring or resisting lower court rulings in immigration, deportation, spending and staffing disputes. Judges reportedly found violations of court orders in dozens of lawsuits. The dispute has deepened debate over executive power and the authority of federal courts.",
                        'seo_keywords'     => "Trump administration, court rulings, executive power",
                        'published_at'     => "2026-05-02 17:49:00",
                    ],

                    (object) [
                        "category_name"    => "Global Politics",
                        'name'             => "US Plans to Withdraw 5,000 Troops From Germany",
                        'sub_heading'      => "",
                        'content_shoulder' => "Troop Drawdown",
                        'brief'            => "The Pentagon said about 5,000 US troops will leave Germany within the next year as Washington reviews its wider European force posture.",
                        "body"             => "The Pentagon announced that around 5,000 US troops will be withdrawn from Germany over six to twelve months. Officials linked the decision to a force posture review, while reports said the move followed tensions between Donald Trump and German Chancellor Friedrich Merz over the Iran war. The decision has triggered concern across Europe.",
                        'seo_keywords'     => "US troops, Germany, European security",
                        'published_at'     => "2026-05-02 09:40:00",
                    ],

                    (object) [
                        "category_name"    => "Global Politics",
                        'name'             => "May Day Protests Worldwide Demand Relief From Rising Costs",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Workers and activists held May Day rallies across the world demanding higher wages, better conditions and relief from inflation and fuel costs.",
                        "body"             => "Workers and activists marked International Workers’ Day with demonstrations across Asia, Europe and the United States. Protesters demanded higher wages, better labour protections and relief from rising energy and living costs. The Iran war and higher fuel prices were repeatedly linked to reduced purchasing power and growing pressure on working families.",
                        'seo_keywords'     => "May Day, workers protest, global inflation",
                        'published_at'     => "2026-05-01 21:25:00",
                    ],

                    (object) [
                        "category_name"    => "Global Politics",
                        'name'             => "UK PM Eyes New Powers to Ban Some Pro-Palestinian Marches",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "British Prime Minister Keir Starmer said some pro-Palestinian marches could be banned amid rising antisemitism and public safety concerns.",
                        "body"             => "UK Prime Minister Keir Starmer said the government could seek new powers to ban some pro-Palestinian marches where their cumulative effect threatens the Jewish community. He said peaceful protest must be protected but certain chants should be prosecuted. The comments followed the stabbing of two Jewish men in London and a raised terror threat level.",
                        'seo_keywords'     => "Keir Starmer, Palestine protests, UK politics",
                        'published_at'     => "2026-05-02 20:44:00",
                    ],

                    (object) [
                        "category_name"    => "Global Politics",
                        'name'             => "Abortion Pill Makers Ask US Supreme Court to Restore Mail Access",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Two drugmakers asked the US Supreme Court to restore mail-order access to mifepristone after an appeals court paused deliveries nationwide.",
                        "body"             => "Danco Laboratories and GenBioPro asked the US Supreme Court to restore mail-order access to mifepristone after an appeals court temporarily blocked deliveries. The case has become a major front in US abortion politics after the overturning of Roe v Wade, with states, regulators and drugmakers fighting over access to medication abortion.",
                        'seo_keywords'     => "US Supreme Court, mifepristone, abortion access",
                        'published_at'     => "2026-05-03 09:51:00",
                    ],

                    (object) [
                        "category_name"    => "Diplomacy",
                        'name'             => "Dhaka and EU Seek Stronger Ties and Broader Cooperation",
                        'sub_heading'      => "Bangladesh and EU review trade, investment and partnership agenda",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh and the European Union discussed stronger relations, trade, investment and wider cooperation during a meeting in Dhaka this week.",
                        "body"             => "Bangladesh and the European Union stressed deeper ties during a meeting between Foreign Affairs Adviser Humayun Kabir and EEAS Managing Director Erik Kurzweil. The two sides discussed trade, investment, development cooperation, regional issues and the Bangladesh-EU Comprehensive Partnership Agreement, while the EU assured continued support.",
                        'seo_keywords'     => "Bangladesh EU, diplomacy, cooperation",
                        'published_at'     => "2026-04-30 13:17:00",
                    ],

                    (object) [
                        "category_name"    => "Diplomacy",
                        'name'             => "Bangladesh and India Move to Restore Full Visa Services",
                        'sub_heading'      => "",
                        'content_shoulder' => "Visa Normalisation",
                        'brief'            => "Bangladesh and India are moving toward full-scale visa operations after months of strained ties and limited processing for travellers.",
                        "body"             => "Bangladesh and India have taken steps to resume full-scale visa operations after a long pause caused by bilateral tension. Dhaka has resumed all categories of visas, while Delhi is expected to expand processing in phases. Visa normalisation was reportedly discussed during Foreign Minister Khalilur Rahman’s recent visit to India.",
                        'seo_keywords'     => "Bangladesh India, visa services, diplomacy",
                        'published_at'     => "2026-05-02 16:54:00",
                    ],

                    (object) [
                        "category_name"    => "Diplomacy",
                        'name'             => "Iran Sends New US Talks Proposal Through Pakistan",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Iran delivered a new proposal for talks with the United States through mediator Pakistan as war-related negotiations remain stalled.",
                        "body"             => "Iran delivered its latest negotiating proposal to Pakistan for onward mediation with the United States. The content of the proposal was not disclosed, but it came after one round of talks and weeks of stalled diplomacy. Iran’s foreign minister also contacted counterparts in several regional countries to discuss initiatives aimed at ending the war.",
                        'seo_keywords'     => "Iran proposal, Pakistan mediator, US talks",
                        'published_at'     => "2026-05-01 20:34:00",
                    ],

                    (object) [
                        "category_name"    => "Diplomacy",
                        'name'             => "Bangladesh FM Urges Global Action to Contain Energy Crisis",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Foreign Minister Khalilur Rahman called for coordinated global action to contain energy shocks affecting developing economies and trade.",
                        "body"             => "Foreign Minister Dr Khalilur Rahman urged coordinated international action to contain the global energy crisis, warning that developing countries could face serious shocks if disruptions continue. Speaking around the Antalya Diplomacy Forum, he said Bangladesh remains vulnerable as an energy-importing country dependent on open sea lanes for oil, LPG and fertiliser.",
                        'seo_keywords'     => "energy crisis, Khalilur Rahman, global diplomacy",
                        'published_at'     => "2026-04-29 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Diplomacy",
                        'name'             => "Russian Ambassador Meets Jatiya Sangsad Speaker",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Russian ambassador and the parliament speaker discussed cooperation in education, energy, manpower and artificial intelligence in Dhaka.",
                        "body"             => "Russian Ambassador Alexander Grigoryevich Khozin met Jatiya Sangsad Speaker Hafiz Uddin Ahmad at parliament. The speaker recalled Russia’s role in Bangladesh’s Liberation War and post-war port clearance. Both sides discussed enhancing cooperation in education, skilled manpower export, energy, artificial intelligence and parliamentary exchanges.",
                        'seo_keywords'     => "Russia Bangladesh, JS Speaker, bilateral ties",
                        'published_at'     => "2026-04-30 18:49:00",
                    ],

                    (object) [
                        "category_name"    => "Conflicts",
                        'name'             => "Fresh Middle East War Developments Include Lebanon Strikes",
                        'sub_heading'      => "Israeli strikes in southern Lebanon kill three amid fragile truce",
                        'content_shoulder' => "",
                        'brief'            => "Fresh updates from the Middle East war reported renewed Lebanon strikes, Iran talks uncertainty and US troop plans in Europe amid tension.",
                        "body"             => "Fresh developments in the Middle East war included deadly Israeli strikes in southern Lebanon, continued uncertainty over Iran’s peace proposal and US moves affecting troop levels in Germany. Lebanon’s state media reported deaths in the Tyre district, while Israel said it targeted Hezbollah-linked military structures despite a fragile ceasefire.",
                        'seo_keywords'     => "Middle East war, Lebanon strikes, Iran",
                        'published_at'     => "2026-05-03 08:22:00",
                    ],

                    (object) [
                        "category_name"    => "Conflicts",
                        'name'             => "Russia Says Ukrainian Drone Attack Killed Two Teenagers",
                        'sub_heading'      => "",
                        'content_shoulder' => "Drone Attack",
                        'brief'            => "Russian officials said a Ukrainian drone attack killed two teenagers on a motorcycle in Belgorod near the Ukraine border, according to reports.",
                        "body"             => "Russia said a Ukrainian drone strike killed two teenagers riding a motorcycle in the Belgorod region. Governor Vyacheslav Gladkov said the young men, aged 18 and 15, died at the scene. Kyiv says its drone attacks mainly target Russian energy infrastructure, while Moscow regularly accuses Ukraine of strikes that kill civilians.",
                        'seo_keywords'     => "Russia Ukraine, drone attack, Belgorod",
                        'published_at'     => "2026-05-01 08:40:00",
                    ],

                    (object) [
                        "category_name"    => "Conflicts",
                        'name'             => "Two Gaza Flotilla Activists Questioned After Israeli Interception",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Israeli authorities questioned two Gaza flotilla activists after intercepting aid vessels bound for the Palestinian territory, officials said.",
                        "body"             => "Israel said two activists from a Gaza-bound flotilla were brought in for questioning after the vessels were stopped by Israeli forces. The flotilla had aimed to challenge the Gaza blockade and deliver aid. Organisers said more activists were detained than Israel acknowledged, while Israel alleged links between some organisers and sanctioned groups.",
                        'seo_keywords'     => "Gaza blockade, flotilla activists, Israel",
                        'published_at'     => "2026-05-02 20:37:00",
                    ],

                    (object) [
                        "category_name"    => "Conflicts",
                        'name'             => "Mali Jihadists Call for Unified Front and Begin Bamako Blockade",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Mali jihadist groups reportedly called for a unified front and began a blockade around Bamako amid worsening insecurity in the Sahel region.",
                        "body"             => "Reports from West Africa said Mali jihadists called for a unified front and began a blockade of Bamako. The development adds pressure on Mali’s military-led authorities as armed groups expand operations. The blockade threatens movement, supplies and security around the capital, highlighting the fragile conflict situation in the Sahel region.",
                        'seo_keywords'     => "Mali, jihadists, Bamako blockade",
                        'published_at'     => "2026-05-02 14:00:00",
                    ],

                    (object) [
                        "category_name"    => "Conflicts",
                        'name'             => "Renewed US-Iran Fighting Seen as Likely by Iranian Officer",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "An Iranian military figure said renewed fighting with the US was likely as Trump questioned Tehran’s new negotiating proposal through Pakistan.",
                        "body"             => "A senior Iranian military figure said renewed conflict with the United States was likely after Washington expressed dissatisfaction with Tehran’s latest negotiating proposal. The statement came during a fragile pause in fighting that followed the US-Israel war against Iran. The Strait of Hormuz blockade and stalled peace talks continue to heighten the risk of escalation.",
                        'seo_keywords'     => "US Iran conflict, peace talks, Hormuz",
                        'published_at'     => "2026-05-02 22:18:00",
                    ],

                    (object) [
                        "category_name"    => "World Economy",
                        'name'             => "Iran War Raises Airline Costs and Fares Worldwide",
                        'sub_heading'      => "Fuel prices and Hormuz disruption squeeze airline operations",
                        'content_shoulder' => "",
                        'brief'            => "The Iran war has sharply increased jet fuel prices, forcing airlines to consider surcharges, cancellations and higher fares for travellers.",
                        "body"             => "The Iran war has pushed jet fuel prices sharply higher by disrupting supply through the Strait of Hormuz. Aviation experts said fuel represents a major share of airline operating costs, so prolonged high prices could threaten weaker carriers. Some airlines have already imposed surcharges or announced service cuts, while travellers face fewer seats and fewer bargains.",
                        'seo_keywords'     => "jet fuel, airline fares, Iran war",
                        'published_at'     => "2026-05-02 20:15:00",
                    ],

                    (object) [
                        "category_name"    => "World Economy",
                        'name'             => "UN Chief Warns Hormuz Closure Is Strangling Global Economy",
                        'sub_heading'      => "",
                        'content_shoulder' => "Hormuz Closure",
                        'brief'            => "UN Secretary-General Antonio Guterres warned the prolonged closure of the Strait of Hormuz is hurting the global economy and energy flows.",
                        "body"             => "UN Secretary-General Antonio Guterres warned that the closure of the Strait of Hormuz was strangling the global economy as the Middle East war continued to disrupt energy flows. The waterway is critical for oil and gas transport, and its disruption has pushed up prices, threatened trade routes and intensified pressure on energy-importing countries.",
                        'seo_keywords'     => "Hormuz, global economy, energy crisis",
                        'published_at'     => "2026-05-01 08:00:00",
                    ],

                    (object) [
                        "category_name"    => "World Economy",
                        'name'             => "WTO Talks Fail to Extend E-Commerce Duty Moratorium",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The WTO failed to extend a long-running ban on customs duties for cross-border digital transmissions after divided talks in Cameroon.",
                        "body"             => "A World Trade Organization ministerial meeting ended without agreement on extending the moratorium that exempted cross-border digital transmissions from customs duties. The lapse does not automatically impose tariffs, but it creates uncertainty for digital trade. The US trade representative criticised the WTO after the talks failed amid wider trade tension.",
                        'seo_keywords'     => "WTO, e-commerce duties, digital trade",
                        'published_at'     => "2026-03-31 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "World Economy",
                        'name'             => "Berkshire Hathaway Profit Rises as Investment Gains Improve",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Berkshire Hathaway reported higher profit, supported by investment gains and stronger performance in key operating businesses, reports said.",
                        "body"             => "Berkshire Hathaway reported a rise in quarterly profit as investment gains and operating businesses helped the conglomerate. The update arrived as investors monitored global markets, earnings and the economic effect of high energy prices. Berkshire’s result remains closely watched because of its broad exposure to insurance, rail, energy and consumer businesses.",
                        'seo_keywords'     => "Berkshire Hathaway, earnings, global markets",
                        'published_at'     => "2026-05-03 10:41:00",
                    ],

                    (object) [
                        "category_name"    => "World Economy",
                        'name'             => "Global Markets Tread Water on Earnings and Jobs Outlook",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Global markets stayed cautious as investors weighed corporate earnings, jobs data, energy costs and geopolitical risks from the Middle East.",
                        "body"             => "Global markets moved cautiously as investors weighed corporate earnings, jobs data and risks from the Middle East conflict. Rising energy costs, uncertainty over Hormuz shipping and changing interest-rate expectations kept traders defensive. The market mood reflected how geopolitical tension and economic indicators are shaping near-term investment decisions.",
                        'seo_keywords'     => "global markets, earnings, jobs data",
                        'published_at'     => "2026-05-02 10:26:00",
                    ],

                    (object) [
                        "category_name"    => "Organizations",
                        'name'             => "UN Extends South Sudan Peacekeeping Mission But Shrinks It",
                        'sub_heading'      => "Security Council renews UNMISS while reducing the troop ceiling",
                        'content_shoulder' => "",
                        'brief'            => "The UN Security Council extended its South Sudan mission for one year but reduced its authorized military size after a compromise vote.",
                        "body"             => "The UN Security Council extended the UNMISS peacekeeping mission in South Sudan until April 30, 2027, while lowering its authorised troop ceiling. The resolution passed with 13 votes in favour and abstentions from Russia and China. Some members criticised the reduction, warning it could weaken civilian protection and humanitarian support.",
                        'seo_keywords'     => "UNMISS, South Sudan, peacekeeping",
                        'published_at'     => "2026-05-01 08:38:00",
                    ],

                    (object) [
                        "category_name"    => "Organizations",
                        'name'             => "Nato Reviews US Decision to Cut Germany Troop Presence",
                        'sub_heading'      => "",
                        'content_shoulder' => "Nato Review",
                        'brief'            => "Nato said it is working with the United States to understand the planned troop reduction in Germany and its wider impact on European defence.",
                        "body"             => "Nato said it was working with Washington to understand the US decision to cut 5,000 troops from Germany. The alliance said the shift showed the need for Europe to continue increasing defence investment and taking greater responsibility for shared security. Officials still expressed confidence in Nato’s deterrence and defence posture.",
                        'seo_keywords'     => "Nato, Germany troops, defence",
                        'published_at'     => "2026-05-02 15:42:00",
                    ],

                    (object) [
                        "category_name"    => "Organizations",
                        'name'             => "WTO Faces Criticism After E-Commerce Moratorium Talks Fail",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The WTO faced US criticism after members failed to extend the moratorium on duties for electronic transmissions amid global trade tension.",
                        "body"             => "The World Trade Organization faced criticism from the US Trade Representative after ministers failed to extend a long-running moratorium on customs duties for digital transmissions. The failure exposed deep divisions among members and may push some countries to seek trade arrangements outside the WTO framework for digital commerce.",
                        'seo_keywords'     => "WTO, global trade, e-commerce",
                        'published_at'     => "2026-03-31 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Organizations",
                        'name'             => "UN Chief Raises Alarm Over Hormuz Impact on Global Economy",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "UN chief Antonio Guterres warned that the Strait of Hormuz crisis is worsening global economic stress during the Middle East war.",
                        "body"             => "UN Secretary-General Antonio Guterres warned that the closure of the Strait of Hormuz was having severe global economic effects. His warning came as energy prices rose and shipping routes faced disruption during the Middle East war. The UN message added pressure on parties to keep strategic waterways open for commerce and navigation.",
                        'seo_keywords'     => "United Nations, Hormuz, global economy",
                        'published_at'     => "2026-05-01 08:00:00",
                    ],

                    (object) [
                        "category_name"    => "Organizations",
                        'name'             => "IAEA Role Highlighted as Iran Defends Enrichment Supervision",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Iran’s UN mission referred to IAEA supervision while defending its position on uranium enrichment during the crisis and new US talks.",
                        "body"             => "Iran’s mission to the United Nations pointed to International Atomic Energy Agency supervision while defending Tehran’s position on uranium enrichment. The statement came as the United States reviewed Iran’s latest proposal and as the nuclear issue returned to the centre of negotiations. The IAEA’s monitoring role remains central to diplomatic debate.",
                        'seo_keywords'     => "IAEA, Iran nuclear, United Nations",
                        'published_at'     => "2026-05-02 22:18:00",
                    ],

                    (object) [
                        "category_name"    => "Business",
                        'name'             => "Bangladesh Economy and Trade Regain Momentum After Policy Steps",
                        'sub_heading'      => "Fuel supply, export financing and agro-industry support help recovery",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s economy and trade sector are regaining momentum after fuel, export finance and agro-industry support measures.",
                        "body"             => "Bangladesh’s economy, including the trade sector, is gradually regaining momentum after several government initiatives. Officials cited progress in resolving fuel supply disruptions, introducing low-interest export financing and prioritising agro-based industries. The measures are aimed at improving business confidence, export activity and supply stability at a time when global energy pressure and Middle East tensions continue to affect production costs.",
                        'seo_keywords'     => "Bangladesh economy, trade sector, export finance",
                        'published_at'     => "2026-05-03 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Business",
                        'name'             => "Haor Farmers Forced to Sell Wet Boro Paddy at Lower Prices",
                        'sub_heading'      => "",
                        'content_shoulder' => "Paddy Price Pressure",
                        'brief'            => "Haor farmers are selling wet Boro paddy at lower prices as harvest pressure, debt and storage limits weaken their bargaining power.",
                        "body"             => "Farmers in haor areas are facing financial pressure as they sell wet Boro paddy at lower prices. Many growers are under pressure from debts, harvest losses and limited drying or storage facilities. Traders are taking advantage of the situation by offering reduced prices, while farmers say they cannot wait for better rates because they need quick cash to repay loans and meet household expenses.",
                        'seo_keywords'     => "Boro paddy, haor farmers, agriculture business",
                        'published_at'     => "2026-05-03 00:40:00",
                    ],

                    (object) [
                        "category_name"    => "Business",
                        'name'             => "Biman Signs Boeing Deal to Buy 14 New Aircraft",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Biman Bangladesh Airlines signed a Boeing deal to buy 14 aircraft as the national carrier moves ahead with fleet expansion plans.",
                        "body"             => "Biman Bangladesh Airlines signed an agreement with Boeing to purchase 14 aircraft as part of its fleet expansion plan. The deal is estimated at about $3.7 billion. The national carrier said the agreement was signed in Dhaka, marking one of its largest aircraft purchase moves. The expansion is expected to strengthen Biman’s long-haul and regional operations in the coming years.",
                        'seo_keywords'     => "Biman, Boeing deal, aircraft purchase",
                        'published_at'     => "2026-04-30 22:09:00",
                    ],

                    (object) [
                        "category_name"    => "Business",
                        'name'             => "Beef Prices Rise in Dhaka as Food Costs Increase",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Beef prices in Dhaka rose by Tk20 per kg after vegetables, chicken, eggs and soybean oil also became costlier in city markets.",
                        "body"             => "Beef prices in Dhaka increased as butchers began charging Tk820 per kg, up by Tk20. Traders blamed higher transport costs and wider food price pressure for the increase. The rise follows recent surges in the prices of vegetables, chicken, eggs and soybean oil. Consumers are facing greater strain in kitchen markets as weekend demand for beef also remains high.",
                        'seo_keywords'     => "beef price, Dhaka market, food inflation",
                        'published_at'     => "2026-05-01 22:39:00",
                    ],

                    (object) [
                        "category_name"    => "Business",
                        'name'             => "Chattogram Port Records Highest Single-Day Container Handling",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Newmooring Container Terminal handled 5,709 TEUs in a single day, setting a record for container operations at Chattogram Port.",
                        "body"             => "The Newmooring Container Terminal at Chattogram Port handled a record 5,709 TEUs in a single day. Port officials said it was the highest single-day container handling figure in the terminal’s history. The achievement is significant for Bangladesh’s trade logistics because faster port handling can reduce congestion, improve export-import movement and support supply chain efficiency.",
                        'seo_keywords'     => "Chattogram Port, container handling, trade logistics",
                        'published_at'     => "2026-05-01 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Stock Market",
                        'name'             => "DSE Index Falls Despite Higher Trading Activity",
                        'sub_heading'      => "Investors stay cautious as selling pressure hits listed shares",
                        'content_shoulder' => "",
                        'brief'            => "Dhaka stocks ended lower despite higher turnover as selling pressure and investor caution pulled the DSE indices down.",
                        "body"             => "Trading increased at the Dhaka Stock Exchange, but the market ended lower due to selling pressure. The DSEX index fell as most companies saw share prices decline. Market observers said investors remained cautious because of uncertainty over listed company earnings, banking sector stress and external economic pressure. The movement reflected fragile confidence in the capital market.",
                        'seo_keywords'     => "DSE, stock market, DSEX",
                        'published_at'     => "2026-03-30 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Stock Market",
                        'name'             => "Dhaka Stocks Jump 161 Points After Iran Ceasefire News",
                        'sub_heading'      => "",
                        'content_shoulder' => "DSE Rally",
                        'brief'            => "Bangladesh’s stock market surged after Iran ceasefire news, with DSEX gaining 161 points and market value rising sharply.",
                        "body"             => "The Dhaka Stock Exchange rallied after news of an Iran-US ceasefire lifted investor sentiment. The DSEX, the main index of the DSE, rose by 161 points in a single day. Market capitalisation increased by Tk95 billion as investors returned to equities. The rally showed how geopolitical events and energy-related uncertainty can quickly influence Bangladesh’s capital market.",
                        'seo_keywords'     => "Dhaka stocks, Iran ceasefire, DSE rally",
                        'published_at'     => "2026-04-08 17:19:00",
                    ],

                    (object) [
                        "category_name"    => "Stock Market",
                        'name'             => "DSE Bars Trading of Five Islamic Banks Before Merger",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Dhaka Stock Exchange barred share trading of five Islamic banks ahead of their planned merger into a new banking entity.",
                        "body"             => "The Dhaka Stock Exchange barred trading of shares of five Islamic banks before their planned merger. The move followed regulatory steps linked to the formation of Sammilito Islamic Bank PLC. Investors affected by the merger process are watching closely as regulators and banks consider how to handle shareholding, valuation and possible compensation in the restructuring.",
                        'seo_keywords'     => "Islamic banks, DSE trading, bank merger",
                        'published_at'     => "2026-03-29 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Stock Market",
                        'name'             => "BSEC Explores Flexible IPO Proceeds Rules",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "BSEC discussed allowing more flexibility in using IPO funds for debt repayment to make the capital market more attractive.",
                        "body"             => "The Bangladesh Securities and Exchange Commission held a strategic dialogue on allowing greater flexibility in the use of IPO proceeds. Stakeholders urged the regulator to permit listed companies to use IPO funds for regularised loan repayment and issuer investments. Business leaders said such flexibility could make the stock market more competitive and aligned with regional practices.",
                        'seo_keywords'     => "BSEC, IPO rules, capital market",
                        'published_at'     => "2026-04-23 19:03:00",
                    ],

                    (object) [
                        "category_name"    => "Stock Market",
                        'name'             => "Stocks Manage Modest Gain as Buyers Return",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh stocks posted a modest gain as buyers returned to selected shares amid hopes of stronger corporate earnings.",
                        "body"             => "Stocks managed a modest gain in the opening session of the week as buyers returned to selected December-closing shares. Expectations of favourable corporate earnings helped improve sentiment, though concerns over Middle East conflict and market uncertainty remained. The movement showed investors are still selective, focusing on companies with stronger earnings prospects.",
                        'seo_keywords'     => "Bangladesh stocks, corporate earnings, DSE",
                        'published_at'     => "2026-04-12 17:00:00",
                    ],

                    (object) [
                        "category_name"    => "Banking",
                        'name'             => "More Than Half of Local Banks Ineligible for Dividend Payouts",
                        'sub_heading'      => "Rising bad loans and provisioning gaps weaken bank finances",
                        'content_shoulder' => "",
                        'brief'            => "More than half of Bangladesh’s scheduled banks may not pay dividends this year due to bad loans and provisioning shortfalls.",
                        "body"             => "More than half of Bangladesh’s scheduled banks are reportedly ineligible to pay dividends this year because of rising bad loans and provisioning shortfalls. The problem reflects deep stress in the banking sector, where default loans have weakened profitability and capital strength. Analysts say many banks need stronger governance, loan recovery and capital rebuilding before returning to sustainable shareholder payouts.",
                        'seo_keywords'     => "bank dividends, default loans, Bangladesh banks",
                        'published_at'     => "2026-05-01 00:00:00",
                    ],

                    (object) [
                        "category_name"    => "Banking",
                        'name'             => "Bangladesh Bank Waives Provisioning for Funds Stuck in Five Banks",
                        'sub_heading'      => "",
                        'content_shoulder' => "Provision Relief",
                        'brief'            => "Bangladesh Bank waived provisioning requirements for funds stuck in five merging Shariah-based banks to ease pressure.",
                        "body"             => "Bangladesh Bank waived the requirement for banks and non-bank financial institutions to maintain provisions against funds stuck in five merging Shariah-based banks. The move is expected to ease temporary financial pressure on affected institutions. The decision comes as the banking regulator manages a complex merger process involving troubled Islamic banks and the proposed Sammilito Islamic Bank PLC.",
                        'seo_keywords'     => "Bangladesh Bank, provisioning, Islamic banks",
                        'published_at'     => "2026-05-02 20:57:00",
                    ],

                    (object) [
                        "category_name"    => "Banking",
                        'name'             => "Sammilito Islamic Bank Merger Faces Structural Questions",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The planned merger of five Shariah-based banks into Sammilito Islamic Bank has entered a new phase of structural review.",
                        "body"             => "The planned merger of five struggling Shariah-based banks into Sammilito Islamic Bank PLC has entered a new phase of strategic recalibration. Reports say recent requests from partner banks to reconsider inclusion have raised questions about the final structure of the new entity. The outcome will be important for depositors, investors and wider confidence in the banking sector.",
                        'seo_keywords'     => "Sammilito Islamic Bank, bank merger, Shariah banking",
                        'published_at'     => "2026-05-03 08:36:00",
                    ],

                    (object) [
                        "category_name"    => "Banking",
                        'name'             => "BB Revises Interest Rates on Long-Term Financing Facility",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Bank revised interest rates under its long-term financing facility to support industrial and investment activity.",
                        "body"             => "Bangladesh Bank revised interest rates on the Long-Term Financing Facility to support industrial and investment sectors. Participating banks and financial institutions will receive funds at different rates based on CAMELS ratings. The revised structure is intended to improve access to long-term finance while linking benefits to institutional strength and risk standards.",
                        'seo_keywords'     => "BB LTFF, long-term finance, industrial investment",
                        'published_at'     => "2026-05-02 14:28:00",
                    ],

                    (object) [
                        "category_name"    => "Banking",
                        'name'             => "Bangladesh Bank Allows Staff Bonuses at Some Banks",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Bank eased restrictions so commercial banks with operating profit can reward staff for special achievements.",
                        "body"             => "Bangladesh Bank relaxed restrictions on incentive bonuses, allowing commercial banks with operating profit to pay staff up to one month’s basic salary for special achievements. The change applies even when banks do not record year-end net profit. The regulator’s move gives banks limited flexibility to retain and motivate employees while financial-sector pressure remains high.",
                        'seo_keywords'     => "bank bonus, Bangladesh Bank, banking rules",
                        'published_at'     => "2026-04-28 21:43:00",
                    ],

                    (object) [
                        "category_name"    => "Corporate",
                        'name'             => "Walton Holds Global Distributor Meet 2026 in Nepal",
                        'sub_heading'      => "Bangladeshi electronics brand gathers over 250 global partners",
                        'content_shoulder' => "",
                        'brief'            => "Walton held its Global Distributor Meet 2026 in Kathmandu, bringing together more than 250 partners and distributors.",
                        "body"             => "Walton held its Global Distributor Meet 2026 in Kathmandu, Nepal, with more than 250 distributors and business partners joining the event. The programme highlighted the company’s international expansion efforts and export ambitions. Walton used the gathering to strengthen relationships with overseas partners, showcase products and discuss future business opportunities in global electronics markets.",
                        'seo_keywords'     => "Walton, distributor meet, Bangladesh electronics",
                        'published_at'     => "2026-05-02 23:00:00",
                    ],

                    (object) [
                        "category_name"    => "Corporate",
                        'name'             => "Unique Hotel CEO Wins Who’s Who Bangladesh Award 2026",
                        'sub_heading'      => "",
                        'content_shoulder' => "Corporate Recognition",
                        'brief'            => "Unique Hotel CEO Md Shakawath Hossain received the Who’s Who Bangladesh Award 2026 in the Professionals category.",
                        "body"             => "Md Shakawath Hossain, CEO of Unique Hotel & Resorts PLC, received the Who’s Who Bangladesh Award 2026 in the Professionals category. Unique Hotel operates major hospitality brands including The Westin Dhaka, Sheraton Dhaka and HANSA. The recognition highlights leadership in Bangladesh’s hospitality sector and the company’s contribution to premium hotel services.",
                        'seo_keywords'     => "Unique Hotel, Shakawath Hossain, corporate award",
                        'published_at'     => "2026-05-02 12:03:00",
                    ],

                    (object) [
                        "category_name"    => "Corporate",
                        'name'             => "Unilever Plans Small Price Hikes Amid Iran War Cost Pressure",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Unilever said it will raise prices in small doses to manage higher costs driven by uncertainty from the Iran war.",
                        "body"             => "Unilever said it would raise prices in small doses to soften the impact of higher-than-expected costs driven by the Iran war. The company kept its 2026 sales and profit margin outlook unchanged despite economic uncertainty. The update matters for Bangladesh consumers and retailers because global price movements by large consumer goods companies can affect local market pricing.",
                        'seo_keywords'     => "Unilever, price hike, consumer goods",
                        'published_at'     => "2026-04-30 22:31:00",
                    ],

                    (object) [
                        "category_name"    => "Corporate",
                        'name'             => "Electromart Wins Three Bangladesh Retail Awards",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Electromart won three Bangladesh Retail Awards 2026, including recognition in electronics retail and new market penetration.",
                        "body"             => "Electromart received three awards at the Bangladesh Retail Awards 2026. The company won Best Retailer in the electronics category, Emerging Retailer of the Year and Most Admired Retailer in New Market Penetration. The recognition strengthens Electromart’s corporate brand position in Bangladesh’s fast-growing consumer electronics retail sector.",
                        'seo_keywords'     => "Electromart, retail awards, electronics retailer",
                        'published_at'     => "2026-04-21 16:35:00",
                    ],

                    (object) [
                        "category_name"    => "Corporate",
                        'name'             => "Biman Boeing Purchase Signals Major Corporate Expansion",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Biman’s $3.7 billion Boeing aircraft purchase plan marks a major expansion move for the national carrier’s corporate strategy.",
                        "body"             => "Biman Bangladesh Airlines’ Boeing deal for 14 aircraft marks a major corporate expansion plan for the national flag carrier. The purchase is expected to cost about $3.7 billion and support long-term fleet renewal. Business observers see the move as important for route expansion, aviation competitiveness and Bangladesh’s broader transport and tourism-linked economy.",
                        'seo_keywords'     => "Biman expansion, Boeing aircraft, aviation business",
                        'published_at'     => "2026-04-30 22:09:00",
                    ],

                    (object) [
                        "category_name"    => "Startups",
                        'name'             => "iFarmer Secures $1.5 Million From Symbiotics",
                        'sub_heading'      => "International capital continues backing Bangladesh agritech infrastructure",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi agritech startup iFarmer secured $1.5 million from Symbiotics to strengthen its agriculture financing model.",
                        "body"             => "Bangladeshi agritech startup iFarmer secured $1.5 million from Symbiotics, showing continued international investor interest in agriculture technology infrastructure. The funding is expected to support iFarmer’s work in farmer financing, input access and agriculture supply-chain services. The deal comes during a difficult period for many local startups facing weaker funding conditions and slower growth.",
                        'seo_keywords'     => "iFarmer, agritech, startup funding",
                        'published_at'     => "2026-04-16 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Startups",
                        'name'             => "Bangladesh Startups Face Funding Crunch as Boom Slows",
                        'sub_heading'      => "",
                        'content_shoulder' => "Funding Crunch",
                        'brief'            => "Bangladesh’s startup ecosystem is facing a funding crunch as the growth story slows and investors become more cautious.",
                        "body"             => "Bangladesh’s startup ecosystem is facing a tougher funding environment as the earlier boom slows. Analysts say global interest-rate changes, reduced venture appetite and local operating challenges have made capital harder to secure. The pressure is affecting growth-stage companies, early-stage founders and digital businesses that depend on external funding to scale operations.",
                        'seo_keywords'     => "Bangladesh startups, funding crunch, venture capital",
                        'published_at'     => "2026-05-01 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Startups",
                        'name'             => "Gold Kinen Recognised at Bangladesh Retail Awards 2026",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Gold Kinen, Bangladesh’s first gold savings app, received recognition in the Best Retail Startup category this year.",
                        "body"             => "Gold Kinen, Bangladesh’s first gold savings app, received an honourable mention in the Best Retail Startup of the Year category at the Bangladesh Retail Awards 2026. The company said the recognition reflected its effort to build a trusted and transparent digital platform for gold savings. The award places fintech-linked retail innovation in the spotlight.",
                        'seo_keywords'     => "Gold Kinen, retail startup, fintech",
                        'published_at'     => "2026-04-20 16:06:00",
                    ],

                    (object) [
                        "category_name"    => "Startups",
                        'name'             => "SBAC Bank Transfers Funds to Bangladesh Startup Investment Company",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "SBAC Bank transferred over Tk2.58 crore to Bangladesh Startup Investment Company to support innovative entrepreneurs.",
                        "body"             => "SBAC Bank PLC transferred funds to Bangladesh Startup Investment Company PLC, an entity established to make equity investment more accessible for technology-based and innovative entrepreneurs. The bank handed over more than Tk2.58 crore from its investable fund. The initiative is designed to support startup financing, entrepreneurship and innovation-driven business growth in Bangladesh.",
                        'seo_keywords'     => "SBAC Bank, startup investment, entrepreneurs",
                        'published_at'     => "2026-03-10 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Startups",
                        'name'             => "Bangladesh EV Market Opens Space for Startup Innovation",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s electric vehicle market is creating startup opportunities in logistics, gig work and last-mile fleet services.",
                        "body"             => "Bangladesh’s electric vehicle market is creating opportunities for startup-led innovation, especially in logistics, e-commerce delivery and gig-economy transport. Analysts note that companies such as Daraz, Pickaboo, Foodpanda, Pathao and Chaldal have created sustained demand for last-mile electric two-wheelers. Fleet electrification could become a growth area for technology and mobility startups.",
                        'seo_keywords'     => "EV market, mobility startup, last-mile delivery",
                        'published_at'     => "2026-04-28 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Trade",
                        'name'             => "Exports Unlikely to Recover Without Middle East Crisis Solution",
                        'sub_heading'      => "Energy prices and supply concerns weigh on Bangladesh’s export outlook",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s exports may struggle to recover unless the Middle East crisis eases and energy-related cost pressure declines.",
                        "body"             => "Bangladesh’s export recovery may remain difficult unless the Middle East crisis is resolved, according to industry views. Higher fuel prices are increasing the cost of doing business, while supply-chain uncertainty threatens production and shipment planning. Exporters say competitors are also facing price pressure, but Bangladesh remains vulnerable because of energy dependence and logistics challenges.",
                        'seo_keywords'     => "Bangladesh exports, Middle East crisis, trade",
                        'published_at'     => "2026-05-02 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Trade",
                        'name'             => "Bangladesh Looks Beyond Hormuz for Fertiliser Imports",
                        'sub_heading'      => "",
                        'content_shoulder' => "Import Diversification",
                        'brief'            => "Bangladesh is exploring fertiliser import options beyond Hormuz routes as the Middle East crisis threatens key supply channels.",
                        "body"             => "Bangladesh is looking beyond the Strait of Hormuz for fertiliser imports as the Middle East crisis threatens shipping and supply channels. The move reflects concerns over energy-linked import routes and agricultural input security. Diversifying suppliers and routes could help reduce disruption risk for fertiliser supply, crop production and food security during a period of global instability.",
                        'seo_keywords'     => "fertiliser import, Hormuz, trade route",
                        'published_at'     => "2026-05-01 19:08:00",
                    ],

                    (object) [
                        "category_name"    => "Trade",
                        'name'             => "Low-Interest Pre-Shipment Credit Relaunched for Exporters",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Bank relaunched low-interest pre-shipment credit to help exporters manage global uncertainty and trade pressure.",
                        "body"             => "Bangladesh Bank reintroduced low-interest pre-shipment credit for exporters to reinvigorate export trade amid global uncertainty. Experts said the facility could help businesses secure working capital before shipment, manage production costs and remain competitive. The policy is especially important as exporters face weak demand, rising energy costs and volatile international trade conditions.",
                        'seo_keywords'     => "pre-shipment credit, export finance, Bangladesh Bank",
                        'published_at'     => "2026-04-24 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Trade",
                        'name'             => "Rising Energy Costs Threaten Export Competitiveness",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Rising global energy prices and domestic fuel adjustments are increasing production and transport costs for exporters.",
                        "body"             => "Rising global energy prices, driven by geopolitical tensions, and domestic fuel price adjustments are threatening Bangladesh’s export competitiveness. A government economic update noted that export performance weakened in the first quarter of 2026 after a stronger start. Higher production and transport costs could reduce margins for manufacturers and make Bangladeshi goods less competitive abroad.",
                        'seo_keywords'     => "energy costs, export competitiveness, Bangladesh trade",
                        'published_at'     => "2026-04-30 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Trade",
                        'name'             => "Finance Minister Promises Reforms to Ease Business and Expand Incentives",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Finance Minister Amir Khosru Mahmud Chowdhury pledged reforms to improve ease of doing business and expand export incentives.",
                        "body"             => "Finance Minister Amir Khosru Mahmud Chowdhury pledged sweeping reforms to improve Bangladesh’s business environment and expand export incentives. He said the government wants to remove major hurdles facing the private sector. The promise comes as exporters and investors seek faster approvals, easier compliance, stronger infrastructure and policy support to recover from trade and energy shocks.",
                        'seo_keywords'     => "business reform, export incentives, trade policy",
                        'published_at'     => "2026-04-29 14:02:00",
                    ],

                    (object) [
                        "category_name"    => "Technology",
                        'name'             => "Smart Classrooms Project Set to Modernize Secondary Education",
                        'sub_heading'      => "Government moves to make classrooms more digital and interactive",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh has taken an initiative to introduce smart classrooms in selected secondary institutions to modernize teaching.",
                        "body"             => "The government has taken an initiative to introduce smart classrooms in selected secondary educational institutions across Bangladesh. The project aims to modernize teaching methods, expand the use of digital content and make classroom learning more interactive. Officials expect the initiative to help students develop stronger technology skills while supporting a more globally competitive education system.",
                        'seo_keywords'     => "smart classrooms, digital education, Bangladesh technology",
                        'published_at'     => "2026-05-03 11:00:00",
                    ],

                    (object) [
                        "category_name"    => "Technology",
                        'name'             => "Hour of AI Campaign Launched in Secondary Schools Nationwide",
                        'sub_heading'      => "",
                        'content_shoulder' => "AI Learning Push",
                        'brief'            => "A month-long Hour of AI campaign began in secondary schools to develop coding, programming and artificial intelligence skills.",
                        "body"             => "The month-long Hour of AI in Bangladesh 2026 campaign has been launched in all secondary schools across the country. The campaign began on April 26 and will continue until May 26. It is designed to introduce students to artificial intelligence, coding and programming skills, helping them prepare for a knowledge-based and innovation-driven future.",
                        'seo_keywords'     => "Hour of AI, coding skills, secondary schools",
                        'published_at'     => "2026-04-27 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Technology",
                        'name'             => "Digital Device and Innovation Expo Showcases Local Tech Ambitions",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s major technology showcase highlighted student innovations, digital devices, startups and local manufacturing capacity.",
                        "body"             => "The Digital Device and Innovation Expo 2026 opened in Dhaka as one of Bangladesh’s largest technology showcases. Organised by the ICT Division, Bangladesh Hi-Tech Park Authority and Bangladesh Computer Samity, the event focused on student innovation, local technology products, digital devices, startup ideas and business-to-business engagement between local and foreign technology companies.",
                        'seo_keywords'     => "digital device expo, local tech, innovation",
                        'published_at'     => "2026-01-28 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Technology",
                        'name'             => "Civic Tech Innovation Expo Highlights Youth-Led Digital Governance",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "UNDP and the ICT Division hosted a civic technology expo to support digital governance, inclusion and youth-led innovation.",
                        "body"             => "UNDP and the ICT Division hosted a civic technology innovation expo in Dhaka, highlighting Bangladesh’s first youth-led civic technology ecosystem. The event brought together government officials, development partners and young innovators to support digital governance, inclusion and public-service innovation. The expo was part of a broader project focused on a more tolerant and inclusive Bangladesh.",
                        'seo_keywords'     => "civic tech, digital governance, youth innovation",
                        'published_at'     => "2026-04-13 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "Technology",
                        'name'             => "Broadband Expo Places Internet at Center of Smart Bangladesh Vision",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Broadband Expo 2026 highlighted internet service providers, technology firms and policy goals for a smarter Bangladesh.",
                        "body"             => "Broadband Expo 2026 brought together internet service providers, technology companies and sector stakeholders to discuss the future of connectivity in Bangladesh. Officials said broadband remains a main driving force on the path to Smart Bangladesh. The event also highlighted policy priorities including One Citizen, One ID, One Wallet and artificial intelligence applications in public services.",
                        'seo_keywords'     => "Broadband Expo, Smart Bangladesh, connectivity",
                        'published_at'     => "2026-04-15 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "AI",
                        'name'             => "Hour of AI Campaign Begins Across Secondary Schools",
                        'sub_heading'      => "Students to receive basic exposure to AI, coding and programming",
                        'content_shoulder' => "",
                        'brief'            => "The national Hour of AI campaign has started in schools to introduce students to artificial intelligence and programming.",
                        "body"             => "The Hour of AI in Bangladesh 2026 campaign has begun across secondary schools to equip students with basic artificial intelligence, coding and programming skills. The campaign will run for one month and is expected to support the government’s goal of building a knowledge-based, innovative and digitally skilled generation for future economic growth.",
                        'seo_keywords'     => "AI education, Hour of AI, coding",
                        'published_at'     => "2026-04-27 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "AI",
                        'name'             => "AI to Feature in Viva for Private Institution Head Recruitment",
                        'sub_heading'      => "",
                        'content_shoulder' => "AI Viva Assessment",
                        'brief'            => "AI will help assess appearance, body language and knowledge during viva exams for private educational institution head recruitment.",
                        "body"             => "Artificial intelligence will be used in the viva stage for recruiting heads and assistant heads of private educational institutions. Candidates who pass the MCQ test will face oral examinations where AI tools will help assess appearance, body language and knowledge. The move shows how AI is entering administrative and recruitment processes in Bangladesh’s education sector.",
                        'seo_keywords'     => "AI recruitment, education jobs, viva exam",
                        'published_at'     => "2026-04-21 14:32:00",
                    ],

                    (object) [
                        "category_name"    => "AI",
                        'name'             => "EWU Seminar Discusses AI and Cybersecurity Skills",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "East West University held a seminar on artificial intelligence and cybersecurity, urging students to build strong technical foundations.",
                        "body"             => "East West University held a seminar on artificial intelligence and cybersecurity, where speakers highlighted the need for advanced technical skills. They advised students to build strong foundational knowledge rather than focusing only on specific tools. The discussion connected AI opportunities with cyber risks, showing how the two fields are becoming closely linked in modern technology careers.",
                        'seo_keywords'     => "AI seminar, cybersecurity skills, EWU",
                        'published_at'     => "2026-04-23 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "AI",
                        'name'             => "Experts Warn Bangladesh Must Close AI Skills Gap",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Experts say weak data governance, limited research links and policy delays could slow Bangladesh’s AI readiness.",
                        "body"             => "Experts have warned that Bangladesh faces a serious challenge in closing its artificial intelligence skills gap. They said weak data governance, limited research collaboration and policy delays could leave the country behind as AI adoption accelerates globally. The warning highlights the need for updated curricula, better industry-academia links and stronger digital infrastructure.",
                        'seo_keywords'     => "AI skills gap, data governance, Bangladesh AI",
                        'published_at'     => "2026-01-15 10:04:00",
                    ],

                    (object) [
                        "category_name"    => "AI",
                        'name'             => "AI Automation Puts Workforce Skills in Focus",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "AI automation is pushing Bangladesh to rethink workforce training as industries prepare for productivity changes and job shifts.",
                        "body"             => "Artificial intelligence automation is putting workforce skills in focus across Bangladesh. Experts say AI can increase productivity and competitiveness, but without timely skills development it may reduce future job opportunities in some sectors. The discussion is especially important for industries such as garments, services and business process outsourcing, where automation can reshape work patterns.",
                        'seo_keywords'     => "AI automation, workforce skills, future jobs",
                        'published_at'     => "2026-01-14 10:02:00",
                    ],

                    (object) [
                        "category_name"    => "Gadgets",
                        'name'             => "Mobile Phone Prices Set to Fall After Import Duty Cut",
                        'sub_heading'      => "NBR cuts customs duty on imported handsets to ease consumer costs",
                        'content_shoulder' => "",
                        'brief'            => "Imported mobile phone prices are expected to fall after the National Board of Revenue reduced handset customs duty.",
                        "body"             => "Prices of imported mobile phones in Bangladesh are expected to fall after the National Board of Revenue cut customs duty on finished handsets from 25 percent to 10 percent. The government also reduced duty on imported components used by local assemblers. Officials said the move should make smartphones more affordable and support access to digital services.",
                        'seo_keywords'     => "mobile phone price, import duty, smartphone",
                        'published_at'     => "2026-01-13 09:09:00",
                    ],

                    (object) [
                        "category_name"    => "Gadgets",
                        'name'             => "Digital Device Expo Opens Space for Smart Devices and Innovation",
                        'sub_heading'      => "",
                        'content_shoulder' => "Device Showcase",
                        'brief'            => "Digital Device and Innovation Expo 2026 displayed smart devices, student projects and local technology products in Dhaka.",
                        "body"             => "The Digital Device and Innovation Expo 2026 featured smart devices, student innovations, mobile technology, e-sports and business-to-business zones. The event promoted local technology product manufacturers and gave students a platform to display creative projects. Organisers said the expo aimed to encourage youth participation, employment, entrepreneurship and investment in Bangladesh’s ICT sector.",
                        'seo_keywords'     => "smart devices, gadget expo, technology products",
                        'published_at'     => "2026-01-28 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Gadgets",
                        'name'             => "Government Legalizes Stock-Lot Phones Within BTRC Timeline",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government approved legalization of stock-lot mobile phones imported within the BTRC deadline under the NEIR system.",
                        "body"             => "The government approved the legalization of stock-lot mobile phones imported within the timeframe fixed by the Bangladesh Telecommunication Regulatory Commission. These handsets will be approved under the National Equipment Identity Register system without additional customs duty. The decision responded to demands from mobile traders and was linked to wider handset-market regulation.",
                        'seo_keywords'     => "stock-lot phones, BTRC, NEIR",
                        'published_at'     => "2026-01-01 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "Gadgets",
                        'name'             => "NEIR Rollout Pushes Mobile Market Toward Formal Regulation",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s NEIR rollout is expected to reduce illegal handset sales and support formal smartphone market regulation.",
                        "body"             => "Bangladesh’s National Equipment Identity Register rollout is pushing the mobile phone market toward stricter formal regulation. The system is designed to identify legal and illegal handsets and reduce grey-market device sales. Industry stakeholders say lower duties and proper NEIR implementation could help stabilise smartphone prices while protecting local assemblers and legal importers.",
                        'seo_keywords'     => "NEIR, handset market, legal phones",
                        'published_at'     => "2026-01-01 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Gadgets",
                        'name'             => "Local Tech Products Gain Spotlight at Dhaka Innovation Expo",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Local technology products and student-built devices received attention during the Digital Device and Innovation Expo in Dhaka.",
                        "body"             => "Local technology products received special attention at the Digital Device and Innovation Expo 2026 in Dhaka. Organisers said the event focused on student innovations and domestic technology product manufacturers. The exhibition also included mobile, innovation and digital device zones, giving visitors a view of Bangladesh’s growing device ecosystem and future manufacturing potential.",
                        'seo_keywords'     => "local devices, tech products, innovation expo",
                        'published_at'     => "2026-01-30 08:40:00",
                    ],

                    (object) [
                        "category_name"    => "Software",
                        'name'             => "National Source Code Policy Draft Published by ICT Division",
                        'sub_heading'      => "Public Money, Public Code policy targets transparency and reuse",
                        'content_shoulder' => "",
                        'brief'            => "The ICT Division published the draft National Source Code Policy to treat publicly funded software as a national resource.",
                        "body"             => "The ICT Division published the draft National Source Code Policy 2025 with the tagline Public Money, Public Code. The policy aims to recognise government-funded software as a national resource and ensure ownership, transparency, security and reusability of software developed with public funds. It would apply to software, applications, APIs and digital services funded through public resources.",
                        'seo_keywords'     => "source code policy, public software, ICT Division",
                        'published_at'     => "2025-12-04 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Software",
                        'name'             => "Rajshahi University Medical Centre Launches Automation Software",
                        'sub_heading'      => "",
                        'content_shoulder' => "Healthcare Automation",
                        'brief'            => "Rajshahi University Medical Centre launched automation software and an online portal to make healthcare services faster.",
                        "body"             => "The University of Rajshahi Medical Centre launched automation software and an online portal to make healthcare services more accessible, faster and modern for teachers, students, officers and staff. The system is expected to improve service tracking, appointment handling and internal management, showing how software automation is spreading into public university healthcare services.",
                        'seo_keywords'     => "automation software, online portal, Rajshahi University",
                        'published_at'     => "2026-02-19 10:45:00",
                    ],

                    (object) [
                        "category_name"    => "Software",
                        'name'             => "ICT Ministerial Recommendations Emphasize Data Governance",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Former ICT adviser Faiz Ahmad Taiyeb left recommendations covering broadband, data governance and digital transformation.",
                        "body"             => "Former special assistant Faiz Ahmad Taiyeb left a set of recommendations for the new ICT minister, focusing on broadband expansion, data governance and digital transformation. The recommendations point to the need for stronger digital infrastructure, better public-data management and clearer policy direction. They also reflect Bangladesh’s growing dependence on software systems for service delivery.",
                        'seo_keywords'     => "data governance, ICT policy, digital transformation",
                        'published_at'     => "2026-03-08 16:58:00",
                    ],

                    (object) [
                        "category_name"    => "Software",
                        'name'             => "Government Email Software Tender Signals Service Upgrade",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A tender update for government email software points to efforts to strengthen official digital communication systems.",
                        "body"             => "A tender update for supply, installation and commissioning of an email software solution indicates efforts to strengthen Bangladesh’s government email service. Reliable official email infrastructure is important for secure communication, record keeping and digital governance. The move fits within broader reforms around public-sector software, cybersecurity and government technology modernisation.",
                        'seo_keywords'     => "email software, government ICT, digital service",
                        'published_at'     => "2026-04-27 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Software",
                        'name'             => "Civic Tech Expo Promotes Software Solutions for Public Inclusion",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The civic technology expo promoted software-driven solutions for digital governance, inclusion and youth participation.",
                        "body"             => "The civic technology innovation expo in Dhaka promoted software-driven ideas for public-service improvement, digital governance and inclusion. Young innovators presented civic technology solutions under a project jointly implemented by UNDP and the ICT Division’s Bangladesh Computer Council. The event showed how software platforms can support citizen engagement, public accountability and community-level problem solving.",
                        'seo_keywords'     => "civic software, public service, digital governance",
                        'published_at'     => "2026-04-13 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "Internet",
                        'name'             => "Broadband Seen as Main Driving Force for Smart Bangladesh",
                        'sub_heading'      => "Expo highlights connectivity, AI use and digital public-service goals",
                        'content_shoulder' => "",
                        'brief'            => "Broadband Expo 2026 highlighted high-speed internet as a key driver of Smart Bangladesh and digital public services.",
                        "body"             => "Broadband Expo 2026 placed high-speed internet at the centre of Bangladesh’s Smart Bangladesh vision. Government officials and industry stakeholders discussed broadband expansion, digital services and the use of artificial intelligence in health, education and the public sector. The event showed that stronger internet infrastructure remains essential for digital inclusion and economic transformation.",
                        'seo_keywords'     => "broadband internet, Smart Bangladesh, digital services",
                        'published_at'     => "2026-04-15 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Internet",
                        'name'             => "Bangladesh Calls for Digital Push at UN Forum",
                        'sub_heading'      => "",
                        'content_shoulder' => "Digital Divide",
                        'brief'            => "Bangladesh called for coordinated global action to bridge the digital divide and strengthen data systems at a UN forum.",
                        "body"             => "Bangladesh called for coordinated global action to strengthen data systems, bridge the digital divide and expand technology transfer during a UN forum in New York. The envoy highlighted that one-third of the world still remains without internet access. The message connected Bangladesh’s national digital goals with broader global demands for affordable connectivity and inclusion.",
                        'seo_keywords'     => "digital divide, UN forum, internet access",
                        'published_at'     => "2026-04-16 09:12:00",
                    ],

                    (object) [
                        "category_name"    => "Internet",
                        'name'             => "Starlink Service Keeps Satellite Internet in Bangladesh Debate",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Starlink’s Bangladesh launch kept satellite internet in focus as users discussed rural access, cost and service reliability.",
                        "body"             => "Starlink’s satellite-based internet service remains part of Bangladesh’s wider connectivity debate after its launch in the country. The service offers unlimited data and is designed to reach remote and underserved areas. Users and policymakers continue to weigh its cost, reliability and potential to support education, business and emergency communications outside traditional network coverage.",
                        'seo_keywords'     => "Starlink Bangladesh, satellite internet, rural connectivity",
                        'published_at'     => "2025-05-21 02:37:00",
                    ],

                    (object) [
                        "category_name"    => "Internet",
                        'name'             => "BSCCL Internet Price Cut Expected to Reduce Connectivity Costs",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Submarine Cable Company decided to reduce internet prices by 10 percent for different service categories.",
                        "body"             => "Bangladesh Submarine Cable Company Limited decided to reduce prices of all types of internet by 10 percent. The decision was expected to reduce costs for mobile internet and broadband service providers. Lower wholesale internet costs can help improve affordability if operators pass benefits to consumers and businesses across the country.",
                        'seo_keywords'     => "BSCCL, internet price, broadband cost",
                        'published_at'     => "2025-03-23 15:35:00",
                    ],

                    (object) [
                        "category_name"    => "Internet",
                        'name'             => "Carnival Internet Expands Rural Broadband Marketplace",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Carnival Internet launched an ecosystem marketplace app after expanding broadband to hundreds of upazilas and villages.",
                        "body"             => "Carnival Internet launched an ecosystem marketplace app after expanding its broadband network across 403 upazilas, more than 31,000 villages and nearly 400,000 rural households. The company said the platform is part of its broader connectivity ecosystem. The expansion reflects rising demand for rural broadband and digital services outside major cities.",
                        'seo_keywords'     => "Carnival Internet, rural broadband, marketplace app",
                        'published_at'     => "2025-11-27 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Cybersecurity",
                        'name'             => "EWU Seminar Highlights AI and Cybersecurity Threat Readiness",
                        'sub_heading'      => "Speakers urge students to build stronger technical foundations",
                        'content_shoulder' => "",
                        'brief'            => "A seminar at East West University discussed how AI and cybersecurity skills are becoming essential for modern digital threats.",
                        "body"             => "East West University hosted a seminar on artificial intelligence and cybersecurity, where speakers stressed the importance of advanced technical skills for addressing modern digital threats. They advised students to build strong foundational knowledge instead of depending only on specific tools. The seminar reflected rising demand for cybersecurity awareness as AI changes the threat landscape.",
                        'seo_keywords'     => "cybersecurity, AI threats, EWU seminar",
                        'published_at'     => "2026-04-23 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Cybersecurity",
                        'name'             => "Evilginx Pro Raises Alarm Over Two-Factor Authentication Safety",
                        'sub_heading'      => "",
                        'content_shoulder' => "2FA Phishing Threat",
                        'brief'            => "A new phishing tool called Evilginx Pro is raising concern because it can bypass two-factor authentication by stealing session cookies.",
                        "body"             => "A new cyber threat called Evilginx Pro is raising alarm because it can bypass two-factor authentication by stealing session cookies. Traditional phishing attacks usually target passwords, but session-cookie theft can allow attackers to take over accounts even after users complete 2FA. The threat highlights the need for stronger login monitoring and anti-phishing awareness.",
                        'seo_keywords'     => "Evilginx Pro, phishing, two-factor authentication",
                        'published_at'     => "2026-02-27 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Cybersecurity",
                        'name'             => "Cybersecurity Threats Grow More Dangerous in the Age of AI",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Cybersecurity experts warn that AI-driven attacks, fraud and digital risks are becoming more sophisticated in 2026.",
                        "body"             => "Cybersecurity experts warn that artificial intelligence is changing the nature of digital attacks. AI-driven fraud, automated phishing and more advanced identity theft are creating new risks for businesses and individuals. The concerns are especially relevant for Bangladesh as more services, payments, education platforms and public systems move online.",
                        'seo_keywords'     => "AI cybersecurity, digital fraud, cyber threats",
                        'published_at'     => "2026-02-08 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Cybersecurity",
                        'name'             => "Election Digital Preparedness Faces AI and Cybersecurity Questions",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Experts raised concerns about AI risks and cybersecurity gaps in election-related digital infrastructure before national polls.",
                        "body"             => "Experts raised concerns over artificial intelligence risks and cybersecurity gaps in the Election Commission’s digital preparedness ahead of national polls. They warned that technology is no longer only a support tool but a core part of modern electoral infrastructure. The concerns included misinformation, system security, digital monitoring and resilience against cyber disruption.",
                        'seo_keywords'     => "election cybersecurity, AI risk, digital preparedness",
                        'published_at'     => "2026-02-06 00:00:00",
                    ],

                    (object) [
                        "category_name"    => "Cybersecurity",
                        'name'             => "Public Software Policy Stresses Security and Reusability",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The draft National Source Code Policy emphasizes security, transparency and reuse of publicly funded software systems.",
                        "body"             => "Bangladesh’s draft National Source Code Policy places strong emphasis on security, transparency and reusability of publicly funded software. By treating government-funded code as a national resource, the policy could improve oversight of public digital systems. Cybersecurity experts see source-code governance as important for reducing hidden vulnerabilities and strengthening trust in digital public infrastructure.",
                        'seo_keywords'     => "source code security, public software, cybersecurity",
                        'published_at'     => "2025-12-04 15:00:00",
                    ],

                    (object) [
                        "category_name"    => "Sports",
                        'name'             => "Nationwide Notun Kuri Sports Begins With Over 160,000 Registrations",
                        'sub_heading'      => "Youth talent hunt starts across eight sporting disciplines",
                        'content_shoulder' => "",
                        'brief'            => "Notun Kuri Sports began with over 160,000 adolescent registrations across football, cricket, chess, martial arts, badminton, athletics and kabaddi.",
                        "body"             => "The nationwide Notun Kuri Sports programme began as a major youth talent hunt covering eight disciplines across Bangladesh. The initiative includes football, cricket, chess, martial arts, badminton, athletics and kabaddi. More than 160,000 adolescents registered online, including boys and girls from across the country, with competitions planned through upazila, district, regional and national phases.",
                        'seo_keywords'     => "Notun Kuri Sports, youth sports, Bangladesh sports",
                        'published_at'     => "2026-04-29 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Sports",
                        'name'             => "New Zealand Beat Bangladesh to Level T20I Series",
                        'sub_heading'      => "",
                        'content_shoulder' => "Series Levelled",
                        'brief'            => "New Zealand defeated Bangladesh by six wickets in a rain-curtailed third T20I, levelling the three-match series 1-1 in Dhaka.",
                        "body"             => "New Zealand beat Bangladesh by six wickets in the rain-curtailed third T20I at Mirpur, ensuring the three-match series finished level at 1-1. Bangladesh were bowled out for 102 after losing momentum following a rain delay. Shoriful Islam gave the hosts hope with early wickets, but Bevon Jacobs’ unbeaten attacking innings guided New Zealand to victory under the DLS method.",
                        'seo_keywords'     => "Bangladesh cricket, New Zealand T20I, Mirpur",
                        'published_at'     => "2026-05-02 20:31:00",
                    ],

                    (object) [
                        "category_name"    => "Sports",
                        'name'             => "Dhaka Premier League Returns With New Rules and Player Facilities",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Dhaka Premier League will return on May 4 with new rules focused on professionalism, player welfare and match integrity.",
                        "body"             => "The 2025-26 Dhaka Premier League will begin on May 4 with new regulations introduced by the Bangladesh Cricket Board. The changes include tighter dressing-room rules, a ban on mobile phone use inside dressing rooms and improved facilities for players. BCB officials said the reforms are intended to enhance professionalism, protect match integrity and restore the importance of domestic one-day cricket.",
                        'seo_keywords'     => "Dhaka Premier League, BCB, domestic cricket",
                        'published_at'     => "2026-05-02 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "Sports",
                        'name'             => "Bangladesh Championship League Final Round Starts Sunday",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The final round of Bangladesh Championship League football begins with two matches at Bashundhara and Gazipur venues.",
                        "body"             => "The final round of Bangladesh Championship League football begins on Sunday with Little Friends Club facing Dhaka Wanderers Club at Bashundhara Kings arena practice ground. Dhaka Rangers Football Club will take on BRTC Sports Club at Shaheed Barkat Stadium in Gazipur. More final-round fixtures are scheduled for Monday, making the closing phase important for club standings.",
                        'seo_keywords'     => "Bangladesh Championship League, BCL football, local football",
                        'published_at'     => "2026-05-02 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Sports",
                        'name'             => "Sinner and Zverev Reach Madrid Open Final",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Jannik Sinner defeated Arthur Fils to reach the Madrid Open final, where he will face second seed Alexander Zverev.",
                        "body"             => "World number one Jannik Sinner reached the Madrid Open final after beating Arthur Fils 6-2, 6-4. Alexander Zverev also advanced after defeating Alexander Blockx in straight sets. Sinner extended his winning run and will aim for another Masters 1000 title, while Zverev enters the final looking to stop the Italian’s dominant run on the tour.",
                        'seo_keywords'     => "Madrid Open, Jannik Sinner, Alexander Zverev",
                        'published_at'     => "2026-05-02 20:54:00",
                    ],

                    (object) [
                        "category_name"    => "Cricket",
                        'name'             => "Jacobs’ Blitz Denies Bangladesh as T20I Series Ends Level",
                        'sub_heading'      => "New Zealand chase revised target after Bangladesh batting collapse",
                        'content_shoulder' => "",
                        'brief'            => "Bevon Jacobs struck an unbeaten 62 as New Zealand beat Bangladesh by six wickets and levelled the T20I series in Dhaka.",
                        "body"             => "Bevon Jacobs played a match-winning unbeaten 62 from 31 balls as New Zealand chased a revised target of 103 to beat Bangladesh in the final T20I. Bangladesh lost their last six wickets for 26 runs and were bowled out for 102 in 14.2 overs. Shoriful Islam gave Bangladesh early hope with three wickets, but Jacobs shifted the pressure with powerful hitting.",
                        'seo_keywords'     => "Bevon Jacobs, Bangladesh T20I, New Zealand cricket",
                        'published_at'     => "2026-05-02 20:31:00",
                    ],

                    (object) [
                        "category_name"    => "Cricket",
                        'name'             => "Tigresses Suffer T20I Whitewash After Narrow Sri Lanka Defeat",
                        'sub_heading'      => "",
                        'content_shoulder' => "Tigresses Whitewashed",
                        'brief'            => "Bangladesh Women suffered a 3-0 T20I whitewash after losing to Sri Lanka by three runs in a rain-shortened match in Sylhet.",
                        "body"             => "Bangladesh Women suffered a 3-0 T20I series whitewash after losing the third match to Sri Lanka by three runs at Sylhet International Cricket Stadium. The match was reduced to nine overs per side, with Sri Lanka posting 87 for 6. Bangladesh reached 84 for 6, falling just short despite contributions from Sobhana Mostari, Juairia Ferdous and Nigar Sultana.",
                        'seo_keywords'     => "Bangladesh Women, Sri Lanka cricket, Tigresses",
                        'published_at'     => "2026-05-02 21:03:00",
                    ],

                    (object) [
                        "category_name"    => "Cricket",
                        'name'             => "DPL to Kick Off With Phone Ban and Integrity Measures",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Dhaka Premier League will begin with strict dressing-room rules, including a mobile phone ban for players and officials.",
                        "body"             => "The Dhaka Premier League will return with new integrity measures, including a ban on mobile phone use in dressing rooms. BCB ad-hoc committee president Tamim Iqbal said players and officials must deposit phones before entering the ground. The measure is part of wider efforts to align the league with anti-corruption standards and improve professionalism in domestic cricket.",
                        'seo_keywords'     => "DPL, mobile phone ban, cricket integrity",
                        'published_at'     => "2026-05-02 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "Cricket",
                        'name'             => "CCDM Confirms Single-League Format for DPDCL Season",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Dhaka Premier Division Cricket League will follow a single-league format this season, removing Super League and relegation stages.",
                        "body"             => "The Cricket Committee of Dhaka Metropolis confirmed the format for the 2025-26 Dhaka Premier Division Cricket League. The competition will run from May 4 to June 11 across six venues. A total of 66 matches will be played, while the Super League and Relegation League stages have been removed. The top team will be declared champion.",
                        'seo_keywords'     => "DPDCL, CCDM, Dhaka cricket league",
                        'published_at'     => "2026-05-01 20:09:00",
                    ],

                    (object) [
                        "category_name"    => "Cricket",
                        'name'             => "Liton Says Lower-Order Collapse Is a Growing Concern",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh T20 captain Liton Das said repeated lower-order failures remain a serious concern after the New Zealand series.",
                        "body"             => "Bangladesh T20 captain Liton Das said the team’s lower-order batting failures remain a serious concern after the final T20I against New Zealand. Bangladesh were bowled out for 102, losing seven wickets for 39 runs in a decisive collapse. Liton said modern T20 cricket demands meaningful contributions from batters at number seven and below, especially under pressure.",
                        'seo_keywords'     => "Liton Das, Bangladesh T20, lower order",
                        'published_at'     => "2026-05-02 21:14:00",
                    ],

                    (object) [
                        "category_name"    => "Football",
                        'name'             => "Bangladesh Drawn With India and Maldives in SAFF Women’s Championship",
                        'sub_heading'      => "Defending champions placed in Group B for Goa tournament",
                        'content_shoulder' => "",
                        'brief'            => "Defending champions Bangladesh were drawn with India and Maldives in Group B of the SAFF Women’s Championship 2026.",
                        "body"             => "Bangladesh have been drawn with hosts India and Maldives in Group B of the SAFF Women’s Championship 2026. The tournament will be held in Goa from May 25 to June 7. Bangladesh enter the competition as defending champions after winning the last two editions. The top two teams from each group will qualify for the semi-finals.",
                        'seo_keywords'     => "SAFF Women, Bangladesh football, India football",
                        'published_at'     => "2026-04-22 18:50:00",
                    ],

                    (object) [
                        "category_name"    => "Football",
                        'name'             => "Kool-BSJA Media Cup Football Draw and Jerseys Unveiled",
                        'sub_heading'      => "",
                        'content_shoulder' => "Media Football Festival",
                        'brief'            => "The Kool-BSJA Media Cup Football Tournament draw and jersey unveiling were held before the five-day competition in Dhaka.",
                        "body"             => "The draw and jersey unveiling ceremony for the Kool-BSJA Media Cup Football Tournament 2026 was held at the Bangladesh Olympic Association building. The five-day tournament will run from May 3 to 7 at the Bangladesh Football Federation artificial turf. The event will feature 32 media houses and is considered one of the country’s major sporting gatherings for journalists.",
                        'seo_keywords'     => "BSJA Media Cup, football tournament, BFF turf",
                        'published_at'     => "2026-04-30 21:26:00",
                    ],

                    (object) [
                        "category_name"    => "Football",
                        'name'             => "Final Round of Bangladesh Championship League Begins",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The last round of Bangladesh Championship League football begins with fixtures at Bashundhara Kings arena and Gazipur.",
                        "body"             => "The last round of Bangladesh Championship League football begins with Little Friends Club facing Dhaka Wanderers Club and Dhaka Rangers Football Club meeting BRTC Sports Club. Matches will be held at Bashundhara Kings arena practice ground and Shaheed Barkat Stadium in Gazipur. The remaining final-round games will follow on Monday at separate venues.",
                        'seo_keywords'     => "BCL football, Bangladesh football, club football",
                        'published_at'     => "2026-05-02 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Football",
                        'name'             => "City Club Thrash Chittagong Abahani as Saheed Hits Hat-Trick",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Saheed Mia scored a hat-trick as City Club beat Chittagong Abahani 6-0 in Bangladesh Championship League football.",
                        "body"             => "City Club registered a commanding 6-0 win over Chittagong Abahani Limited in Bangladesh Championship League football. Saheed Mia delivered the standout performance with a hat-trick at Bashundhara Kings arena practice ground. The result strengthened City Club’s position in the league and added another high-scoring fixture to the closing phase of the competition.",
                        'seo_keywords'     => "City Club, Chittagong Abahani, Saheed Mia",
                        'published_at'     => "2026-04-30 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Football",
                        'name'             => "SAFF U-20 Winning Bangladesh Team Gets Reception Plan",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s SAFF U-20 winning football team is set to receive a public reception after beating India in the final.",
                        "body"             => "The Bangladesh Football Federation planned a reception for the SAFF U-20 Championship winning team after their dramatic final victory over India. Bangladesh beat India 4-3 on penalties in the final in Male, Maldives. The celebration was arranged to recognise a major achievement for the country’s youth football and to invite supporters to join the occasion.",
                        'seo_keywords'     => "SAFF U-20, Bangladesh football, India final",
                        'published_at'     => "2026-04-04 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Tennis",
                        'name'             => "Sinner Beats Fils to Reach Madrid Open Final Against Zverev",
                        'sub_heading'      => "World number one extends winning run before Masters final",
                        'content_shoulder' => "",
                        'brief'            => "Jannik Sinner defeated Arthur Fils in straight sets to reach the Madrid Open final, where he will face Alexander Zverev.",
                        "body"             => "Jannik Sinner beat Arthur Fils 6-2, 6-4 to reach the Madrid Open final, extending his strong run on the ATP tour. Alexander Zverev joined him in the title match after defeating Alexander Blockx 6-2, 7-5. Sinner is chasing another Masters 1000 crown, while Zverev will try to stop the world number one’s dominant form in Madrid.",
                        'seo_keywords'     => "Sinner, Zverev, Madrid Open",
                        'published_at'     => "2026-05-02 20:54:00",
                    ],

                    (object) [
                        "category_name"    => "Tennis",
                        'name'             => "Rybakina Crashes Out as Sabalenka Reaches Madrid Quarter-Finals",
                        'sub_heading'      => "",
                        'content_shoulder' => "Madrid Upset",
                        'brief'            => "Elena Rybakina suffered a shock Madrid Open defeat, while Aryna Sabalenka battled past Naomi Osaka to reach the quarter-finals.",
                        "body"             => "Australian Open champion Elena Rybakina crashed out of the Madrid Open after losing to Anastasia Potapova in the fourth round. Earlier, world number one Aryna Sabalenka recovered from a set down to beat Naomi Osaka and reach the quarter-finals. Coco Gauff also exited after a three-set battle against Linda Noskova, making it a dramatic day in Madrid.",
                        'seo_keywords'     => "Rybakina, Sabalenka, Madrid Open",
                        'published_at'     => "2026-04-28 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Tennis",
                        'name'             => "Kostyuk Wins First WTA 1000 Title in Madrid",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Marta Kostyuk claimed the biggest title of her career by defeating Mirra Andreeva in the Madrid Open women’s final.",
                        "body"             => "Marta Kostyuk won her first WTA 1000 title by defeating Mirra Andreeva in the Madrid Open women’s final. The Ukrainian player controlled key moments of the match and completed a breakthrough run on clay. The victory marked the biggest title of her career and lifted her standing among the top contenders heading into the next phase of the clay season.",
                        'seo_keywords'     => "Marta Kostyuk, Mirra Andreeva, WTA 1000",
                        'published_at'     => "2026-05-03 16:40:00",
                    ],

                    (object) [
                        "category_name"    => "Tennis",
                        'name'             => "Zarif Abrar Reaches Quarter-Finals at World Tennis Tour Junior Dhaka",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s Zarif Abrar reached the quarter-finals of the World Tennis Tour Junior J30 Dhaka after beating Arjan Singh.",
                        "body"             => "Bangladeshi junior tennis player Zarif Abrar reached the quarter-finals of the World Tennis Tour Junior J30 Dhaka event after defeating Singapore’s Arjan Singh. Abrar won the opening set comfortably and handled resistance in the second set to seal victory. His progress gave Bangladesh a strong result in a tournament featuring junior players from several countries.",
                        'seo_keywords'     => "Zarif Abrar, junior tennis, Dhaka J30",
                        'published_at'     => "2025-10-14 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Tennis",
                        'name'             => "Bangladeshi Tennis Coaches Build Strong Reputation in China",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi tennis coaches are earning recognition in China, where nearly 50 coaches now work as skilled trainers.",
                        "body"             => "Bangladeshi tennis coaches are building a strong reputation in China, where nearly 50 trainers from Bangladesh are now working across the country. The trend began with coach Sukumar Roy’s move to China in 2005. The growing demand reflects China’s expanding tennis market and has created a new professional pathway for Bangladeshi sports trainers abroad.",
                        'seo_keywords'     => "Bangladeshi tennis coaches, China tennis, sports trainers",
                        'published_at'     => "2025-10-29 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Local Sports",
                        'name'             => "Notun Kuri Sports Inaugurated Across Bangladesh",
                        'sub_heading'      => "Youth competition begins with live district-level opening events",
                        'content_shoulder' => "",
                        'brief'            => "Notun Kuri Sports-2026 was inaugurated nationwide, with the main ceremony held at Sylhet District Stadium and events across districts.",
                        "body"             => "Notun Kuri Sports-2026 was inaugurated across Bangladesh as a nationwide competition for children and adolescents. The main ceremony was held at Sylhet District Stadium and broadcast on LED screens in district stadiums. The initiative aims to promote healthy lifestyles, discover sporting talent and build a structured pathway from local competition to regional and national stages.",
                        'seo_keywords'     => "Notun Kuri, local sports, youth talent",
                        'published_at'     => "2026-05-02 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Local Sports",
                        'name'             => "Notun Kuri Sports-2026 Officially Opens in Rajshahi",
                        'sub_heading'      => "",
                        'content_shoulder' => "Rajshahi Opening",
                        'brief'            => "Rajshahi joined the nationwide inauguration of Notun Kuri Sports-2026 through an event at Muktijuddho Smriti Stadium.",
                        "body"             => "Notun Kuri Sports-2026 was officially inaugurated in Rajshahi along with the rest of the country. The main ceremony from Sylhet District Stadium was shown live on a large screen at Rajshahi District Muktijuddho Smriti Stadium. Local officials, athletes and organisers joined the event as part of the nationwide sports talent hunt programme.",
                        'seo_keywords'     => "Rajshahi sports, Notun Kuri, district sports",
                        'published_at'     => "2026-05-02 17:00:00",
                    ],

                    (object) [
                        "category_name"    => "Local Sports",
                        'name'             => "BSJA Media Cup to Feature 32 Media Houses in Dhaka",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Kool-BSJA Media Cup Football Tournament will feature 32 media houses in a five-day event at the BFF artificial turf.",
                        "body"             => "The Kool-BSJA Media Cup Football Tournament will feature 32 leading media houses in a five-day competition at the Bangladesh Football Federation artificial turf. The tournament draw and jersey unveiling were held at the Bangladesh Olympic Association building. Organisers said the event is designed to promote sportsmanship and build community among journalists through football.",
                        'seo_keywords'     => "BSJA Media Cup, media football, Dhaka sports",
                        'published_at'     => "2026-04-30 21:26:00",
                    ],

                    (object) [
                        "category_name"    => "Local Sports",
                        'name'             => "Khelaghar and Chattogram City FC Win BCL Matches",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Khelaghar Samaj Kalyan Samity and Chattogram City Football Club won their Bangladesh Championship League fixtures.",
                        "body"             => "Khelaghar Samaj Kalyan Samity and Chattogram City Football Club recorded wins in Bangladesh Championship League football at separate venues. The results added momentum to the closing rounds of the competition. Local club football remains an important platform for players outside the national team setup, helping teams test depth and compete for promotion-related goals.",
                        'seo_keywords'     => "Khelaghar, Chattogram City FC, BCL",
                        'published_at'     => "2026-04-27 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Local Sports",
                        'name'             => "City Club Register Big BCL Win With Saheed Hat-Trick",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "City Club beat Chittagong Abahani 6-0 as Saheed Mia scored a hat-trick in Bangladesh Championship League football.",
                        "body"             => "City Club produced one of the biggest wins of the Bangladesh Championship League round by defeating Chittagong Abahani Limited 6-0. Saheed Mia scored a brilliant hat-trick at Bashundhara Kings arena practice ground. The result highlighted City Club’s attacking strength and added excitement to the final stretch of the domestic football season.",
                        'seo_keywords'     => "City Club, BCL, Saheed Mia",
                        'published_at'     => "2026-04-30 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "International Sports",
                        'name'             => "Mohamed Salah to Miss Rest of Liverpool Season With Hamstring Injury",
                        'sub_heading'      => "Egypt winger expected to recover before 2026 World Cup",
                        'content_shoulder' => "",
                        'brief'            => "Mohamed Salah will miss the rest of Liverpool’s season after suffering a hamstring injury during a Premier League match.",
                        "body"             => "Liverpool forward Mohamed Salah will miss the rest of the season after suffering a hamstring injury in a Premier League match against Crystal Palace. Egypt national team officials said the winger would require four weeks of treatment. Salah is still expected to be fit for the 2026 World Cup, where Egypt will face Belgium, New Zealand and Iran.",
                        'seo_keywords'     => "Mohamed Salah, Liverpool, World Cup",
                        'published_at'     => "2026-04-26 10:08:00",
                    ],

                    (object) [
                        "category_name"    => "International Sports",
                        'name'             => "Barcelona Beat Osasuna to Move Closer to LaLiga Title",
                        'sub_heading'      => "",
                        'content_shoulder' => "LaLiga Race",
                        'brief'            => "Barcelona moved closer to the LaLiga title after beating Osasuna, extending their lead near the end of the season.",
                        "body"             => "Barcelona moved closer to defending their LaLiga title after beating Osasuna away from home. With four games remaining, Barcelona strengthened their position at the top of the table and increased pressure on second-placed Real Madrid. The result kept Hansi Flick’s side on course for a domestic league triumph as the Spanish season entered its final stretch.",
                        'seo_keywords'     => "Barcelona, Osasuna, LaLiga",
                        'published_at'     => "2026-05-03 09:59:00",
                    ],

                    (object) [
                        "category_name"    => "International Sports",
                        'name'             => "FIFA Reopens Last-Minute Sales for 2026 World Cup Tickets",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "FIFA reopened last-minute ticket sales for all 104 matches of the 2026 World Cup across the United States, Mexico and Canada.",
                        "body"             => "FIFA reopened its last-minute ticket sales phase for the 2026 World Cup, with tickets available for all 104 matches on a first-come, first-served basis. The tournament will begin on June 11 across the United States, Mexico and Canada. FIFA said more than five million tickets had already been sold before the final sales phase reopened.",
                        'seo_keywords'     => "FIFA World Cup, tickets, 2026 World Cup",
                        'published_at'     => "2026-04-22 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "International Sports",
                        'name'             => "Bayern Rescue Late Draw Against Heidenheim",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bayern Munich rescued a late draw against Heidenheim after rotating heavily following their Champions League defeat.",
                        "body"             => "Bayern Munich rescued a late draw against Heidenheim after a difficult domestic outing. Coach Vincent Kompany rotated his side heavily following a high-scoring Champions League defeat to PSG, and Bayern struggled to control the match. The late equaliser prevented a damaging loss but raised questions about squad rhythm during a demanding run of fixtures.",
                        'seo_keywords'     => "Bayern Munich, Heidenheim, Bundesliga",
                        'published_at'     => "2026-05-03 08:00:00",
                    ],

                    (object) [
                        "category_name"    => "International Sports",
                        'name'             => "Iran Officials Seek FIFA Meeting Over World Cup Issues",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Iran football officials are seeking talks with FIFA over World Cup-related issues after travel and airport complications.",
                        "body"             => "Iran football officials are seeking a meeting with FIFA to discuss World Cup-related issues after complications involving travel and airport access. The matter has added pressure before the 2026 tournament in North America. Iranian officials reportedly want discussions with FIFA leadership to resolve logistical concerns and protect the team’s preparation before the global event.",
                        'seo_keywords'     => "Iran football, FIFA, World Cup issues",
                        'published_at'     => "2026-05-02 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Entertainment",
                        'name'             => "Dhallywood Finds Fresh Momentum as Cinema Halls Draw Crowds",
                        'sub_heading'      => "Audience turnout signals renewed confidence in local cinema",
                        'content_shoulder' => "",
                        'brief'            => "Dhallywood is seeing renewed audience interest as cinema halls report stronger turnout and fresh energy around local film releases.",
                        "body"             => "Bangladesh’s film industry is showing signs of revival as audiences return to cinema halls and local releases regain public attention. Reports describe this moment as more than a temporary comeback, pointing to stronger viewer engagement, better promotion and renewed confidence among producers. The trend is important for Dhallywood because cinema halls had struggled for years with declining footfall, limited releases and competition from digital platforms.",
                        'seo_keywords'     => "Dhallywood, Bangladesh cinema, entertainment",
                        'published_at'     => "2026-04-18 16:32:00",
                    ],

                    (object) [
                        "category_name"    => "Entertainment",
                        'name'             => "Afran Nisho-Starrer Domm Continues Strong Run in Cineplexes",
                        'sub_heading'      => "",
                        'content_shoulder' => "Houseful Screenings",
                        'brief'            => "Bangladeshi film Domm continues to attract large audiences in cineplexes and multiplexes after its successful theatrical release.",
                        "body"             => "Bangladeshi film Domm, starring Afran Nisho, Puja Chery and Chanchal Chowdhury, has continued to perform strongly at cineplexes and multiplexes. The film, based on a true story, has been drawing large crowds with many screenings running houseful. Its success has encouraged more shows and strengthened the sense that local films can still create major theatrical demand when story, casting and audience interest align.",
                        'seo_keywords'     => "Domm, Afran Nisho, Bangladeshi film",
                        'published_at'     => "2026-03-26 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Entertainment",
                        'name'             => "Filmmakers Prepare Crowded Eid Release Lineup",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi filmmakers are preparing a busy Eid release season, with several commercial titles planned for cinema halls.",
                        "body"             => "Bangladeshi filmmakers are preparing a crowded Eid release lineup, with several titles being discussed for theatrical release. The expected slate includes films from established directors and commercial production houses, creating competition for screen space during the festival season. Eid remains the biggest window for Dhallywood business, and a strong lineup can help cinemas attract family audiences and generate wider publicity for local films.",
                        'seo_keywords'     => "Eid films, Dhallywood releases, cinema halls",
                        'published_at'     => "2026-02-21 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Entertainment",
                        'name'             => "Warfaze Partners With Zippo for Collectible Music Editions",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi rock band Warfaze partnered with Zippo to launch collectible editions inspired by the band’s music legacy.",
                        "body"             => "Iconic Bangladeshi rock band Warfaze has partnered with global lighter brand Zippo to launch collectible editions inspired by the band’s music. The collaboration has been described as a cultural milestone, connecting Bangla rock nostalgia with lifestyle merchandise. For fans, the release represents more than a product launch; it celebrates Warfaze’s long influence on Bangladesh’s music scene and its emotional link with generations of listeners.",
                        'seo_keywords'     => "Warfaze, Zippo, Bangla rock",
                        'published_at'     => "2026-04-24 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Entertainment",
                        'name'             => "BTV Plans Special Band Music Show for Eid Viewers",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Television will air a special band music programme during Eid featuring several popular local bands.",
                        "body"             => "Bangladesh Television has planned a special band music programme for Eid celebrations, aiming to bring festive energy to viewers through performances by popular Bangladeshi bands. The programme reflects BTV’s continued use of music specials during major national and religious festivals. For television audiences, such shows remain a familiar part of Eid entertainment, especially for families watching from home.",
                        'seo_keywords'     => "BTV, Eid music, Bangladeshi bands",
                        'published_at'     => "2026-03-16 12:11:00",
                    ],

                    (object) [
                        "category_name"    => "Movies",
                        'name'             => "Domm Set for International Premiere After Domestic Success",
                        'sub_heading'      => "True-story film gains overseas attention after strong local run",
                        'content_shoulder' => "",
                        'brief'            => "Afran Nisho-starrer Domm is set for international premiere after drawing strong crowds in Bangladesh cineplexes.",
                        "body"             => "Bangladeshi film Domm is set for an international premiere after building strong momentum in domestic theatres. The film stars Afran Nisho, Puja Chery and Chanchal Chowdhury, and its story is inspired by real events. Its growing show count and strong audience response suggest renewed appetite for serious, performance-driven Bangladeshi cinema, especially when films combine strong casting with a compelling real-life narrative.",
                        'seo_keywords'     => "Domm premiere, Afran Nisho, Bangladeshi movie",
                        'published_at'     => "2026-03-26 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "Movies",
                        'name'             => "Dhallywood Comeback Raises Hope for Cinema Hall Business",
                        'sub_heading'      => "",
                        'content_shoulder' => "Cinema Revival",
                        'brief'            => "Dhallywood’s recent audience growth is raising hopes that cinema halls can recover through stronger local releases.",
                        "body"             => "Dhallywood’s renewed momentum is raising hope for Bangladesh’s cinema hall business after years of declining attendance. Recent reports point to longer queues, stronger audience emotion and a more confident release environment. Producers and exhibitors are watching closely because a sustained revival could support better investment in films, improved hall facilities and more frequent theatrical releases across the country.",
                        'seo_keywords'     => "Dhallywood revival, cinema halls, local films",
                        'published_at'     => "2026-04-18 16:32:00",
                    ],

                    (object) [
                        "category_name"    => "Movies",
                        'name'             => "Manusher Bagan Becomes First 2026 Film to Receive Certification",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Impress Telefilm’s Manusher Bagan became the first Bangladeshi movie of 2026 to receive certification from the film board.",
                        "body"             => "Impress Telefilm Ltd’s Manusher Bagan became the first Bangladeshi film of 2026 to receive certification from the Bangladesh Film Certification Board. Directed by Nurul Alam Atique, the film has cleared the official approval process and is expected to move toward theatrical release. The certification marks an important early-year development for Bangladesh’s film release calendar.",
                        'seo_keywords'     => "Manusher Bagan, film certification, Bangladeshi cinema",
                        'published_at'     => "2026-02-12 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Movies",
                        'name'             => "Top Dhallywood Stars Line Up for Eid 2026 Releases",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Top Dhallywood stars are preparing major Eid 2026 releases, including commercial films expected to draw festival crowds.",
                        "body"             => "Top Dhallywood stars are preparing to lead the Eid 2026 movie season, with several commercial films planned for release. Shakib Khan’s Prince has been highlighted as one of the major titles, while other films are expected to compete for screens. Eid remains Bangladesh’s biggest theatrical window, and star-driven releases can strongly influence box office performance and cinema hall attendance.",
                        'seo_keywords'     => "Eid 2026 films, Shakib Khan, Dhallywood",
                        'published_at'     => "2026-02-28 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Movies",
                        'name'             => "Three Bangladeshi Films Enter Asia’s Top Cinema List",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Three Bangladeshi films were named among Asia’s best films of 2025, giving local cinema wider regional recognition.",
                        "body"             => "Three Bangladeshi films secured places on a list of Asia’s best films of 2025, giving the country’s cinema wider regional recognition. Mahde Hasan’s Balur Nogorite, Soumitra Dastidar’s July 36: Rastro Banam Nagorik and Nuhash Humayun’s Dui Shaw were named on the list. The recognition highlights the growing visibility of Bangladeshi filmmakers beyond the local market.",
                        'seo_keywords'     => "Bangladeshi films, Asian cinema, Nuhash Humayun",
                        'published_at'     => "2026-01-27 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "Music",
                        'name'             => "Miles Set to Perform at BMBAA Unplugged Event",
                        'sub_heading'      => "Legendary Bangla band joins exclusive music night in Dhaka",
                        'content_shoulder' => "",
                        'brief'            => "Iconic Bangla band Miles is set to perform at the BMBAA Unplugged event organised by the Bangladesh MBA Association.",
                        "body"             => "Miles, one of Bangladesh’s most iconic bands, is set to perform at the upcoming BMBAA Unplugged event organised by the Bangladesh MBA Association. The performance adds another major appearance to the band’s long-running musical journey. For fans of Bangla rock and pop, the event offers a chance to experience a legacy act whose songs remain deeply connected to several generations of listeners.",
                        'seo_keywords'     => "Miles, BMBAA Unplugged, Bangla band",
                        'published_at'     => "2026-03-29 19:51:00",
                    ],

                    (object) [
                        "category_name"    => "Music",
                        'name'             => "Fuad Reimagines Prothom Bangladesh for New Generation",
                        'sub_heading'      => "",
                        'content_shoulder' => "Patriotic Song Revival",
                        'brief'            => "Composer Fuad Almuktadir released a reimagined version of Prothom Bangladesh on Independence Day for younger listeners.",
                        "body"             => "Composer Fuad Almuktadir released a reimagined version of the patriotic anthem Prothom Bangladesh on Independence Day. The original song was sung by Shahnaz Rahmatullah, with lyrics by Moniruzzaman Monir and composition by Alauddin Ali. The new version also marked the debut of music and creative production platform Fireworks, aiming to connect a classic patriotic song with a new generation.",
                        'seo_keywords'     => "Prothom Bangladesh, Fuad Almuktadir, patriotic song",
                        'published_at'     => "2026-03-26 18:22:00",
                    ],

                    (object) [
                        "category_name"    => "Music",
                        'name'             => "Warfaze Marks Cultural Milestone With Zippo Collaboration",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Warfaze’s collectible Zippo collaboration places Bangla rock culture into a new lifestyle merchandise space.",
                        "body"             => "Warfaze’s partnership with Zippo has placed Bangla rock culture into a new lifestyle merchandise space. The collectible editions are inspired by the band’s music and legacy, creating a rare crossover between sound, memory and fan culture. The launch shows how Bangladeshi bands can extend their identity beyond concerts and recordings into branded cultural objects that fans value emotionally.",
                        'seo_keywords'     => "Warfaze, rock music, collectible edition",
                        'published_at'     => "2026-04-24 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Music",
                        'name'             => "Eid Celebrations Bring Wave of New Music Releases",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Eid festivities are being marked by a wide range of new songs from established and emerging Bangladeshi artistes.",
                        "body"             => "Eid festivities in Bangladesh are being accompanied by a wave of new music releases from both established and emerging artistes. Songs are being released through production houses and artistes’ own YouTube channels, reflecting how digital platforms now drive festive music distribution. The trend shows that while traditional album culture has changed, Eid remains an important release window for Bangla music.",
                        'seo_keywords'     => "Eid music, Bangla songs, music releases",
                        'published_at'     => "2026-03-17 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Music",
                        'name'             => "Bangladesh Releases First Fully AI-Generated Cinematic Music Video",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh entered a new phase of music innovation with Nijhum Raat, described as its first fully AI-generated cinematic music video.",
                        "body"             => "Bangladesh has entered a new chapter of music innovation with the release of Nijhum Raat, described as the country’s first fully AI-generated cinematic music video. The project highlights how artificial intelligence is beginning to influence music production, visual storytelling and digital entertainment. It also shows how local creators are experimenting with new tools to reach audiences in a changing media landscape.",
                        'seo_keywords'     => "AI music video, Nijhum Raat, Bangla music",
                        'published_at'     => "2026-02-20 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "TV Shows",
                        'name'             => "BTV Launches New Magazine Show Abhinandan",
                        'sub_heading'      => "Programme honours talent, dedication and inspiring journeys",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Television launched Abhinandan, a new magazine show focused on talent, dedication and inspiring life journeys.",
                        "body"             => "Bangladesh Television launched Abhinandan, a new magazine show dedicated to honouring talent, dedication and inspiring journeys from across the country. The first episode was scheduled to air on Friday at 9pm. The programme adds to BTV’s long tradition of magazine-format shows, where interviews, performances and human-interest segments are used to highlight cultural and social achievement.",
                        'seo_keywords'     => "BTV, Abhinandan, magazine show",
                        'published_at'     => "2026-01-29 20:59:00",
                    ],

                    (object) [
                        "category_name"    => "TV Shows",
                        'name'             => "Duronto TV Rolls Out Pahela Baishakh Special Programmes",
                        'sub_heading'      => "",
                        'content_shoulder' => "Baishakh Specials",
                        'brief'            => "Duronto TV prepared special Pahela Baishakh programmes including drama, dance and children-focused festive content.",
                        "body"             => "Duronto TV rolled out special programming for Pahela Baishakh, including the festival drama Hoi Hoi Halla – Season 3. The drama follows children-centered events around New Year celebrations and a planned trip to a village home. The lineup reflects Duronto TV’s focus on family and children’s entertainment during national cultural occasions.",
                        'seo_keywords'     => "Duronto TV, Pahela Baishakh, children show",
                        'published_at'     => "2026-04-12 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "TV Shows",
                        'name'             => "Duronto TV Plans International Mother Language Day Specials",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Duronto TV announced special programmes for International Mother Language Day, including films, dance and telefilm content.",
                        "body"             => "Duronto TV announced a special lineup for International Mother Language Day, including the programme Mayer Bhasha, dance show A A K Kha, the telefilm Jhutum Pakhir Katha and selected films. The schedule was designed for young viewers and families, combining entertainment with language and cultural remembrance on a nationally important day.",
                        'seo_keywords'     => "Duronto TV, Mother Language Day, TV special",
                        'published_at'     => "2026-02-19 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "TV Shows",
                        'name'             => "BTV to Air Eid Band Show With Popular Bangladeshi Groups",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Television will broadcast a special Eid band show featuring popular local bands and festive performances.",
                        "body"             => "Bangladesh Television will air a special band music show during Eid, bringing together several popular Bangladeshi bands for festive performances. Such programmes remain a core part of BTV’s holiday schedule and help bring music to family audiences across the country. The show also gives bands a wider television platform beyond concerts and digital channels.",
                        'seo_keywords'     => "BTV Eid show, band music, television",
                        'published_at'     => "2026-03-16 12:11:00",
                    ],

                    (object) [
                        "category_name"    => "TV Shows",
                        'name'             => "Behind the Fame Podcast Opens With Mehazabien Chowdhury",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Rumman Rashid Khan launched celebrity podcast Behind the Fame with Mehazabien Chowdhury as the first guest.",
                        "body"             => "Rumman Rashid Khan launched the celebrity podcast Behind the Fame with RRK, opening the series with popular actress Mehazabien Chowdhury as the first guest. The show adds to the growing space for long-form digital celebrity conversations in Bangladesh. It also reflects how entertainment interviews are shifting beyond traditional television into podcasts and online video platforms.",
                        'seo_keywords'     => "Behind the Fame, Mehazabien, celebrity podcast",
                        'published_at'     => "2025-08-01 05:34:00",
                    ],

                    (object) [
                        "category_name"    => "Celebrities",
                        'name'             => "Actor Shams Suman Dies at 61 in Dhaka",
                        'sub_heading'      => "National Award-winning actor remembered for stage, TV and film work",
                        'content_shoulder' => "",
                        'brief'            => "National Award-winning actor Shams Suman died while undergoing treatment at a private hospital in Dhaka at age 61.",
                        "body"             => "National Award-winning actor Shams Suman died while undergoing treatment at a private hospital in Dhaka. He was 61. Once a familiar face on television, stage and film, Shams Suman had also served as Station Chief at Radio Bhumi. His death drew tributes from colleagues and cultural personalities who remembered his contribution to Bangladesh’s performing arts scene.",
                        'seo_keywords'     => "Shams Suman, Bangladeshi actor, celebrity death",
                        'published_at'     => "2026-03-17 20:01:00",
                    ],

                    (object) [
                        "category_name"    => "Celebrities",
                        'name'             => "Veteran Actor Ilias Javed Passes Away in Uttara",
                        'sub_heading'      => "",
                        'content_shoulder' => "Veteran Actor Dies",
                        'brief'            => "Veteran Bangladeshi film actor Ilias Javed passed away at his Uttara residence after suffering from illness for a long time.",
                        "body"             => "Veteran Bangladeshi film actor Ilias Javed passed away at his residence in Uttara after suffering from illness for a long time. His death was confirmed by the Bangladesh Film Directors’ Association. Ilias Javed was part of Bangladesh’s film industry for decades, and his passing was noted by artistes and film professionals who remembered his contribution to local cinema.",
                        'seo_keywords'     => "Ilias Javed, Bangladeshi actor, Dhallywood",
                        'published_at'     => "2026-01-21 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "Celebrities",
                        'name'             => "Jaya Ahsan Mourns Rahul Arunoday Banerjee’s Death",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Jaya Ahsan shared an emotional post after the death of Tollywood actor Rahul Arunoday Banerjee during a shoot.",
                        "body"             => "Bangladeshi actress Jaya Ahsan shared an emotional post after the death of Tollywood actor Rahul Arunoday Banerjee, who drowned at Talsari Beach in Digha during a shoot. His death shocked the Tollywood film community. Jaya’s reaction showed the close professional and emotional connections Bangladeshi artistes maintain with Kolkata’s Bengali entertainment industry.",
                        'seo_keywords'     => "Jaya Ahsan, Rahul Banerjee, Tollywood",
                        'published_at'     => "2026-03-31 10:50:00",
                    ],

                    (object) [
                        "category_name"    => "Celebrities",
                        'name'             => "Actor Tinu Karim Dies After Casting Vote in Barishal",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Television actor Tinu Karim passed away in Barishal after suffering a heart attack shortly after casting his vote.",
                        "body"             => "Television actor Tinu Karim passed away in Barishal after suffering a heart attack shortly after casting his vote in the 13th national parliamentary election. His death came as artistes and public figures were participating in the polls. Tinu Karim was known for his work in television drama, and colleagues remembered his presence in Bangladesh’s entertainment industry.",
                        'seo_keywords'     => "Tinu Karim, TV actor, Bangladeshi celebrity",
                        'published_at'     => "2026-02-12 17:37:00",
                    ],

                    (object) [
                        "category_name"    => "Celebrities",
                        'name'             => "Artistes Turn Up at Polls and Share Voting Moments Online",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi artistes and cultural figures joined the national election day by voting and sharing moments with followers.",
                        "body"             => "Bangladeshi artistes and cultural figures turned up at polling centres during the 13th National Parliamentary Election and shared voting moments online. There had been concern that some celebrities might avoid polling centres because of safety fears or travel abroad, but many eventually participated. Their presence created entertainment-page attention around election-day civic participation.",
                        'seo_keywords'     => "Bangladeshi artistes, celebrities voting, election",
                        'published_at'     => "2026-02-12 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "OTT",
                        'name'             => "Saif Ali Khan Returns to Netflix With Kartavya",
                        'sub_heading'      => "Crime drama expected to premiere on Netflix in mid-May",
                        'content_shoulder' => "",
                        'brief'            => "Saif Ali Khan is set to return to Netflix with Kartavya, a brooding crime drama reportedly eyeing a May premiere.",
                        "body"             => "Saif Ali Khan is set to return to Netflix with Kartavya, a crime drama that has been building quiet interest before release. Industry reports suggest the series is eyeing a May 15 premiere, though official confirmation is still awaited. The project adds to the growing flow of South Asian crime thrillers on streaming platforms that attract viewers in Bangladesh and across the region.",
                        'seo_keywords'     => "Netflix, Kartavya, Saif Ali Khan",
                        'published_at'     => "2026-05-02 16:30:00",
                    ],

                    (object) [
                        "category_name"    => "OTT",
                        'name'             => "HBO Max Launch Plan Includes Bangladesh and Asia Pacific Markets",
                        'sub_heading'      => "",
                        'content_shoulder' => "Streaming Expansion",
                        'brief'            => "HBO Max plans to launch in Bangladesh and other Asia Pacific markets, expanding access to major global entertainment brands.",
                        "body"             => "HBO Max plans to launch in Bangladesh and other Asia Pacific markets as part of a wider regional expansion. The platform brings together HBO, Harry Potter, DC Universe, Cartoon Network, Max Originals, Hollywood films and Discovery brand shows. Its entry would add another major global streaming option for Bangladeshi viewers, increasing competition in the country’s OTT entertainment space.",
                        'seo_keywords'     => "HBO Max, Bangladesh OTT, streaming platform",
                        'published_at'     => "2025-09-17 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "OTT",
                        'name'             => "Omimangshito Finally Set for Release After Certification Dispute",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Raihan Rafi’s OTT content Omimangshito is finally set for release after delays linked to certification review.",
                        "body"             => "Raihan Rafi’s Omimangshito is finally set for release after a long delay linked to certification review. The project was originally planned for OTT release, but clearance became mandatory after the teaser appeared. The case drew attention because it showed how Bangladesh’s certification rules can affect digital content, not only traditional theatrical films.",
                        'seo_keywords'     => "Omimangshito, Raihan Rafi, OTT release",
                        'published_at'     => "2026-01-10 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "OTT",
                        'name'             => "Toffee’s Concert Streaming Model Shows Demand for Digital Events",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Toffee’s earlier live concert streaming model continues to show how Bangladeshi audiences use OTT platforms for music events.",
                        "body"             => "Toffee’s live streaming of major music events has shown how Bangladeshi OTT and digital entertainment platforms can serve audiences beyond physical venues. By streaming concerts online, platforms can reach viewers across the country and abroad. The model remains relevant as music, celebrity content and live entertainment increasingly move into hybrid formats combining stage shows with digital access.",
                        'seo_keywords'     => "Toffee, live streaming, OTT events",
                        'published_at'     => "2025-11-10 15:30:00",
                    ],

                    (object) [
                        "category_name"    => "OTT",
                        'name'             => "Celebrity Podcasts Add New Digital Entertainment Format",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Celebrity podcast Behind the Fame reflects growing viewer interest in long-form digital entertainment and online interviews.",
                        "body"             => "Behind the Fame with RRK reflects a growing shift toward long-form celebrity content on digital platforms. With Mehazabien Chowdhury as the first guest, the show placed celebrity conversation in a podcast-style format that can reach online viewers outside conventional television. Such formats are becoming an important part of Bangladesh’s OTT-adjacent entertainment ecosystem.",
                        'seo_keywords'     => "celebrity podcast, digital entertainment, OTT content",
                        'published_at'     => "2025-08-01 05:34:00",
                    ],

                    (object) [

                        "category_name"    => "Health",

                        'name'             => "Health card plan aims to widen basic healthcare access",
                        'sub_heading'      => "Government priority on marginalised patients",
                        'content_shoulder' => "",
                        'brief'            => "A planned health card and stronger hospital monitoring may expand affordable healthcare access for marginalised families across Bangladesh.",

                        "body"             => "The government is planning to introduce a health card to improve access to healthcare services for the general public, especially marginalised communities. Officials also stressed hospital monitoring, child health awareness, free medical services and medicine distribution as part of a wider push to make basic care more reachable. The initiative signals a stronger focus on reducing service gaps for families who often struggle with treatment costs and distance.",

                        'seo_keywords'     => "health card, healthcare access, Bangladesh health",
                        'published_at'     => "2026-05-02 23:55:00",

                    ],

                    (object) [

                        "category_name"    => "Health",

                        'name'             => "Government moves to tighten animal feed safety",
                        'sub_heading'      => "",
                        'content_shoulder' => "Farm to table",
                        'brief'            => "Bangladesh plans stronger animal feed controls to protect food safety, livestock quality, and public health from farm to table.",

                        "body"             => "Authorities have moved to strengthen control over animal feed as part of a broader public health and food safety effort. The plan focuses on safer feed for cattle and poultry so that meat, milk and eggs reaching consumers are less exposed to contamination risks. Officials also discussed expanding support for livestock entrepreneurs, including women, youth and expatriate investors, linking agricultural quality with everyday health protection.",

                        'seo_keywords'     => "food safety, animal feed, public health",
                        'published_at'     => "2026-05-03 13:32:00",

                    ],

                    (object) [

                        "category_name"    => "Health",

                        'name'             => "Heart disease prevention gets renewed policy attention",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Officials and cardiology experts urged lifestyle awareness, prevention, and stronger collaboration to reduce heart disease risks nationwide.",

                        "body"             => "Heart disease remains one of Bangladesh’s major public health concerns, according to officials speaking at a cardiovascular conference in Dhaka. The discussion highlighted the need for prevention, healthier lifestyles, awareness campaigns and better cooperation among heart specialists. Speakers also stressed that care for women and children should be strengthened, as early detection and timely intervention can reduce long-term complications and treatment burdens.",

                        'seo_keywords'     => "heart disease, prevention, cardiology",
                        'published_at'     => "2026-05-02 13:07:00",

                    ],

                    (object) [

                        "category_name"    => "Health",

                        'name'             => "New maternal hospital opens in Cox’s Bazar refugee camp",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A new maternal hospital in Cox’s Bazar is offering emergency obstetric care, C-sections, ultrasound, and newborn services.",

                        "body"             => "A maternal and child hospital has opened inside the Rohingya refugee camp in Cox’s Bazar, giving pregnant women access to safer delivery support within the camp. The facility provides round-the-clock emergency obstetric care, C-sections, ultrasound and routine maternal services. Health workers said the hospital can reduce dangerous delays for women who previously had to travel long distances during complications, often under severe resource constraints.",

                        'seo_keywords'     => "maternal health, Cox Bazar, refugee healthcare",
                        'published_at'     => "2026-05-01 10:00:00",

                    ],

                    (object) [

                        "category_name"    => "Health",

                        'name'             => "District proposals expose rural healthcare shortages",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Deputy commissioners have proposed ICU, manpower, ambulance, equipment, and hospital capacity upgrades for rural healthcare.",

                        "body"             => "More than 80 health-related proposals from deputy commissioners and divisional commissioners have highlighted deep shortages in Bangladesh’s rural healthcare system. District hospitals and upazila facilities face gaps in doctors, nurses, ICUs, ambulances, oxygen systems and trained personnel. Several proposals call for water ambulances, medicine supply, manpower deployment and better equipment so that patients outside major cities can receive urgent care closer to home.",

                        'seo_keywords'     => "rural healthcare, hospital shortage, Bangladesh",
                        'published_at'     => "2026-04-28 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Medical News",

                        'name'             => "Medical university expansion stalled by project delays",
                        'sub_heading'      => "Postgraduate training faces infrastructure gap",
                        'content_shoulder' => "",
                        'brief'            => "Several public medical universities remain unable to offer full postgraduate courses because of delayed infrastructure and staffing.",

                        "body"             => "The expansion of higher medical education has slowed as several public medical universities continue to struggle with infrastructure and administrative gaps. Rajshahi Medical University, established in 2016, still lacks the capacity to offer postgraduate courses, while universities in Chattogram, Sylhet and Khulna face similar limitations. The delay affects specialist training, research capacity and the wider health workforce needed to improve healthcare delivery.",

                        'seo_keywords'     => "medical education, medical university, postgraduate training",
                        'published_at'     => "2026-04-17 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Medical News",

                        'name'             => "New resident doctors urged to serve with compassion",
                        'sub_heading'      => "",
                        'content_shoulder' => "Doctor training",
                        'brief'            => "More than one thousand resident doctors joined postgraduate training with calls for compassion, skill, and research-oriented service.",

                        "body"             => "A total of 1,306 newly admitted resident doctors took oath at the Residency Induction Programme 2026. Health officials urged them to combine technical skill with compassion toward patients, while also focusing on research-oriented medical practice. The induction is important for Bangladesh’s specialist pipeline, as trained resident doctors are expected to support hospitals, improve diagnosis, and strengthen patient care across multiple departments.",

                        'seo_keywords'     => "resident doctors, medical training, Bangladesh doctors",
                        'published_at'     => "2026-03-01 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Medical News",

                        'name'             => "BMU vice-chancellor stresses modern diagnostic labs",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Medical University leaders said hospital quality depends heavily on modern laboratories and accurate diagnosis.",

                        "body"             => "Bangladesh Medical University’s vice-chancellor said modern and standard laboratory services are essential for accurate and timely disease diagnosis. Speaking on World Laboratory Day 2026, he noted that the overall standard of a hospital depends greatly on the quality of its laboratory system. Better labs can help doctors detect diseases earlier, reduce wrong treatment, and improve patient confidence in both public and private healthcare facilities.",

                        'seo_keywords'     => "diagnostic lab, BMU, medical diagnosis",
                        'published_at'     => "2026-04-24 18:33:00",

                    ],

                    (object) [

                        "category_name"    => "Medical News",

                        'name'             => "Mobile cardiac units planned for village-level care",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Advanced mobile medical and surgical units may help deliver cardiac care to rural patients who cannot easily reach major hospitals.",

                        "body"             => "Health officials discussed mobile medical and surgical units designed to deliver advanced cardiac care in villages. The initiative is expected to bring specialist services closer to rural patients who often face delays, transport costs and limited access to major hospitals. If deployed effectively, mobile units could support early screening, referral, emergency response and follow-up care for patients with heart conditions outside urban centres.",

                        'seo_keywords'     => "cardiac care, mobile medical unit, rural health",
                        'published_at'     => "2026-04-22 21:16:00",

                    ],

                    (object) [

                        "category_name"    => "Medical News",

                        'name'             => "Cervical cancer awareness highlighted as lifesaving tool",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Health experts said awareness, screening, and early prevention are essential to reduce cervical cancer deaths among women.",

                        "body"             => "Health experts have renewed calls for stronger awareness about cervical cancer, noting that it remains a serious cause of cancer-related deaths among women in Bangladesh and worldwide. Awareness programmes can encourage screening, vaccination knowledge, early consultation and timely treatment. Public campaigns are especially important for women who delay care because of stigma, cost, lack of information or limited access to specialist services.",

                        'seo_keywords'     => "cervical cancer, women health, cancer awareness",
                        'published_at'     => "2026-04-21 17:05:00",

                    ],

                    (object) [

                        "category_name"    => "Public Health",

                        'name'             => "Measles-rubella campaign expands nationwide from May",
                        'sub_heading'      => "Emergency drive targets missed children",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s emergency measles-rubella campaign targets young children after a surge in infections and immunity gaps.",

                        "body"             => "Bangladesh launched an emergency measles-rubella vaccination campaign with support from UNICEF, WHO and Gavi. The campaign began in high-risk upazilas and city corporation areas and was planned for nationwide expansion from May 3. It prioritises children aged six months to five years who missed routine immunisation, especially in densely populated and high-risk settings where severe complications can spread quickly.",

                        'seo_keywords'     => "measles vaccine, rubella campaign, immunisation",
                        'published_at'     => "2026-04-05 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Public Health",

                        'name'             => "Suspected measles deaths keep pressure on health system",
                        'sub_heading'      => "",
                        'content_shoulder' => "Outbreak response",
                        'brief'            => "DGHS data showed fresh suspected measles deaths and more than one thousand suspected cases in a day, raising response concerns.",

                        "body"             => "The health authorities reported four child deaths from suspected measles in the 24 hours ending 8am on May 2, pushing the suspected measles death count to 235 since March 15. DGHS also recorded 1,024 suspected patients during that period, with 72 confirmed patients admitted to hospitals. The figures show the continued pressure on hospitals and the urgency of vaccination, isolation, awareness and fast referral.",

                        'seo_keywords'     => "measles outbreak, DGHS, public health",
                        'published_at'     => "2026-05-02 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Public Health",

                        'name'             => "Rainfall raises concern over dengue transmission",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Experts warned that ongoing rainfall may increase Aedes breeding and dengue transmission risk across Bangladesh in coming months.",

                        "body"             => "Public health experts warned that ongoing rainfall across Bangladesh could raise dengue transmission risk in the coming months if preventive action is not strengthened. Rainwater can collect in containers, construction sites and household spaces, creating ideal breeding grounds for Aedes mosquitoes. Experts advised urgent source reduction, early warning, local government preparation and proper insecticide application before infections rise during peak months.",

                        'seo_keywords'     => "dengue risk, rainfall, mosquito control",
                        'published_at'     => "2026-04-30 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Public Health",

                        'name'             => "DGHS records 14 fresh dengue cases overnight",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Health officials reported new dengue hospitalisations across several divisions as Bangladesh monitors early-season transmission.",

                        "body"             => "Health officials recorded 14 fresh dengue cases in the 24 hours ending 8am on April 30. The new hospitalisations were reported from Barishal, Chattogram, Dhaka North City Corporation, Dhaka South City Corporation, Rajshahi and Rangpur. DGHS figures showed Bangladesh had recorded four dengue deaths and 2,463 infections since the start of the year, keeping mosquito control a key public health priority.",

                        'seo_keywords'     => "dengue cases, DGHS, Bangladesh dengue",
                        'published_at'     => "2026-04-30 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Public Health",

                        'name'             => "Vaccine procurement warning adds urgency to measles response",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Reports on measles vaccine procurement gaps intensified concern over coverage shortfalls and outbreak control in Bangladesh.",

                        "body"             => "A report said UNICEF had warned the interim government about measles procurement concerns as the outbreak spread. By the end of March, government figures cited in the report showed only 59 percent of eligible children had received measles vaccination in 2025. Public health experts fear that fast transmission may limit the immediate effect of emergency inoculation unless coverage, hospital readiness and public awareness improve quickly.",

                        'seo_keywords'     => "vaccine procurement, measles coverage, Bangladesh immunisation",
                        'published_at'     => "2026-05-02 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Diseases",

                        'name'             => "Cumilla reports another child death with measles-like symptoms",
                        'sub_heading'      => "District death count rises",
                        'content_shoulder' => "",
                        'brief'            => "A nine-month-old child died with measles-like symptoms in Cumilla, raising concern over child infection and hospital response.",

                        "body"             => "A nine-month-old child died with symptoms consistent with measles in Cumilla, taking the number of similar child deaths in the district to six. Hospital sources said the child’s condition deteriorated rapidly before death. The case reflects the dangerous impact of the current measles-like illness among infants and young children, many of whom may be vulnerable because of missed vaccination or delayed medical care.",

                        'seo_keywords'     => "measles symptoms, Cumilla, child health",
                        'published_at'     => "2026-05-03 11:59:00",

                    ],

                    (object) [

                        "category_name"    => "Diseases",

                        'name'             => "Rangpur child dies after measles-like illness",
                        'sub_heading'      => "",
                        'content_shoulder' => "Child infection",
                        'brief'            => "A child admitted to Rangpur Medical College Hospital with measles-like symptoms died as new cases appeared in the division.",

                        "body"             => "A child undergoing treatment with measles-like symptoms at Rangpur Medical College Hospital died in the paediatric isolation ward. Health officials said 49 new patients with symptoms consistent with measles were identified across Rangpur division in the previous 24 hours, with admissions in Dinajpur General Hospital and Rangpur Medical College Hospital. The report adds to concern over disease spread beyond Dhaka.",

                        'seo_keywords'     => "Rangpur measles, child death, infectious disease",
                        'published_at'     => "2026-05-03 12:42:00",

                    ],

                    (object) [

                        "category_name"    => "Diseases",

                        'name'             => "National suspected measles toll reaches alarming level",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s suspected measles figures continued rising with new deaths, confirmed admissions, and thousands of hospital recoveries.",

                        "body"             => "DGHS data showed four children died from suspected measles in one day, while 1,024 suspected patients were reported during the same 24-hour period. Authorities also said 72 confirmed measles patients were admitted to hospitals. Since March 15, tens of thousands of suspected patients have been admitted and many have recovered, but the death count shows the outbreak remains a serious disease emergency.",

                        'seo_keywords'     => "suspected measles, disease outbreak, DGHS",
                        'published_at'     => "2026-05-02 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Diseases",

                        'name'             => "Early dengue pattern keeps hospitals on alert",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Fresh dengue cases across multiple divisions indicate that hospitals and local authorities need early mosquito-control preparation.",

                        "body"             => "Bangladesh recorded new dengue infections across several regions in late April, including Barishal, Chattogram, Dhaka city corporations, Rajshahi and Rangpur. Although the numbers are still lower than peak-season waves, health officials are watching the pattern closely because rainfall and standing water can quickly accelerate Aedes mosquito breeding. Hospitals and municipalities are expected to prepare early to prevent a larger outbreak.",

                        'seo_keywords'     => "dengue, mosquito disease, hospital alert",
                        'published_at'     => "2026-04-30 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Diseases",

                        'name'             => "Heart disease described as major public health challenge",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Officials warned that heart disease remains a major challenge and urged preventive habits, awareness, and stronger specialist cooperation.",

                        "body"             => "Heart disease was described as one of Bangladesh’s major public health challenges during a scientific conference in Dhaka. Speakers urged prevention through healthy lifestyle choices, awareness, early checkups and stronger coordination among cardiology professionals. The discussion also focused on improving services for women and children, who may face delayed diagnosis or uneven access to specialised cardiovascular care.",

                        'seo_keywords'     => "heart disease, cardiovascular disease, prevention",
                        'published_at'     => "2026-05-02 13:07:00",

                    ],

                    (object) [

                        "category_name"    => "Nutrition",

                        'name'             => "Experts urge nutrition to be placed at centre of health systems",
                        'sub_heading'      => "Malnutrition weakens treatment outcomes",
                        'content_shoulder' => "",
                        'brief'            => "Nutrition experts called for a multisector approach linking health, climate, development, and infectious disease response.",

                        "body"             => "Experts at a roundtable in Dhaka called for nutrition to be placed at the centre of Bangladesh’s health, climate and development agenda. They said malnutrition weakens the body’s ability to absorb medicines and recover from illness, making it harder to fight infectious diseases. The discussion also highlighted the need to update nutrition policy, improve coordination and develop better real-time information systems.",

                        'seo_keywords'     => "nutrition, malnutrition, health systems",
                        'published_at'     => "2026-04-26 09:06:00",

                    ],

                    (object) [

                        "category_name"    => "Nutrition",

                        'name'             => "Food insecurity warning raises nutrition concerns",
                        'sub_heading'      => "",
                        'content_shoulder' => "Household diets",
                        'brief'            => "Bangladesh’s high food insecurity numbers show that affordability, income, and diet quality remain serious nutrition risks.",

                        "body"             => "A recent analysis of the 2026 Global Report on Food Crises warned that around 1.6 crore people in Bangladesh faced crisis-level food insecurity or worse during the 2025 peak. Although the situation improved from 2024, the large number of affected people shows that nutrition risks are linked not only to food production but also to income, purchasing power, food inflation, social protection and climate vulnerability.",

                        'seo_keywords'     => "food insecurity, nutrition risk, Bangladesh food",
                        'published_at'     => "2026-04-27 09:00:00",

                    ],

                    (object) [

                        "category_name"    => "Nutrition",

                        'name'             => "Food safety debate links affordability with nutrition quality",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Food crisis analysis says Bangladesh is not facing famine, but millions still struggle with adequate and nutritious diets.",

                        "body"             => "A food safety and food security commentary noted that Bangladesh is not facing famine, yet it remains among countries with a large number of people experiencing high levels of acute food insecurity. The concern is not only whether food exists in markets, but whether households can afford nutritious diets. Poor families may reduce protein intake, borrow money, delay health spending and rely on cheaper staples.",

                        'seo_keywords'     => "food safety, nutritious diet, food crisis",
                        'published_at'     => "2026-04-30 05:01:00",

                    ],

                    (object) [

                        "category_name"    => "Nutrition",

                        'name'             => "Animal feed control tied to safer meat milk and eggs",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Government animal feed regulation aims to improve the safety of meat, milk, and eggs consumed by Bangladeshi families.",

                        "body"             => "The government’s move to tighten animal feed control has direct nutrition and food safety implications. Officials said safer feed for livestock and poultry can improve the quality of meat, milk and eggs reaching consumers. Better control can reduce contamination risks, protect children and families from unsafe food chains, and support healthier diets while also strengthening the livestock economy.",

                        'seo_keywords'     => "animal feed, nutrition safety, meat milk eggs",
                        'published_at'     => "2026-05-03 13:32:00",

                    ],

                    (object) [

                        "category_name"    => "Nutrition",

                        'name'             => "Malnutrition remains priority during National Nutrition Week",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Health officials said stunting and poor nutrition still require stronger coordination, data systems, and policy action.",

                        "body"             => "During National Nutrition Week 2026, experts said Bangladesh has made progress but malnutrition remains a pressing concern, with stunting still affecting many children. They recommended a stronger multisector approach that links nutrition with infectious disease control, climate resilience, development and primary healthcare. Officials also said improved data systems can help identify gaps faster and guide targeted interventions.",

                        'seo_keywords'     => "National Nutrition Week, stunting, child nutrition",
                        'published_at'     => "2026-04-26 19:07:00",

                    ],

                    (object) [

                        "category_name"    => "Mental Health",

                        'name'             => "Government launches programme to reduce adolescent suicide risk",
                        'sub_heading'      => "Teachers and students to receive training",
                        'content_shoulder' => "",
                        'brief'            => "A pilot programme will train students and teachers to reduce adolescent suicide risk and support emotional well-being.",

                        "body"             => "The government has launched a pilot training programme to address the rising risk of suicide among adolescents and strengthen emotional well-being. The initiative is being implemented under the supervision of the Directorate of Secondary and Higher Education, with teachers and students receiving awareness training. The programme reflects growing concern about student mental health, early warning signs, school support systems and the need for safe conversations.",

                        'seo_keywords'     => "adolescent suicide, mental health, student wellbeing",
                        'published_at'     => "2026-05-01 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Mental Health",

                        'name'             => "Bangladesh mental health crisis deepens amid treatment gaps",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Stigma, few specialists, Dhaka-centred services, and low awareness are pushing many mental health patients toward unsafe options.",

                        "body"             => "Bangladesh’s mental health crisis is deepening as stigma, lack of specialists and low awareness keep many people away from professional care. Reports show patients often self-medicate, depend on online advice or visit traditional healers instead of trained psychologists or psychiatrists. Experts said services remain heavily Dhaka-centred, while many districts have very few specialists, creating a major treatment access gap.",

                        'seo_keywords'     => "mental health crisis, treatment gap, Bangladesh",
                        'published_at'     => "2026-03-24 01:56:00",

                    ],

                    (object) [

                        "category_name"    => "Mental Health",

                        'name'             => "Mental health rights protection needs stronger implementation",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Legal experts said Bangladesh needs stronger rights-based mental health protection, policy implementation, and stigma reduction.",

                        "body"             => "A legal analysis warned that Bangladesh’s mental health situation remains critical despite existing laws and policy frameworks. It cited WHO-related figures showing high risk among adults and children and a large treatment gap among adults with mental disorders. The article argued that policy implementation remains weak and that stigma in schools, workplaces and families prevents many people from seeking timely professional help.",

                        'seo_keywords'     => "mental health rights, stigma, mental healthcare",
                        'published_at'     => "2026-04-22 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Mental Health",

                        'name'             => "DSHE orders mental health programme in all schools and colleges",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Schools and colleges have been instructed to introduce scientific lifestyle and mental health services programmes nationwide.",

                        "body"             => "The Directorate of Secondary and Higher Education directed schools and colleges to gradually implement a scientific lifestyle development and mental health services programme. The initiative includes free one-day training for teachers and students, monitoring pools in institutions and possible guardian participation. Officials said the programme aims to support physical and mental well-being while also helping prevent non-communicable diseases.",

                        'seo_keywords'     => "school mental health, DSHE, student wellness",
                        'published_at'     => "2026-03-08 20:56:00",

                    ],

                    (object) [

                        "category_name"    => "Mental Health",

                        'name'             => "Experts warn stigma keeps youth from mental healthcare",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Mental health specialists say stigma and fear of judgment stop many students and workers from seeking treatment early.",

                        "body"             => "Mental health experts warned that stigma remains one of the biggest barriers to treatment in Bangladesh. Adolescents may hesitate to speak openly about anxiety, depression or suicidal thoughts, while workers may avoid care because of fear of criticism or workplace vulnerability. Without early support, many people turn to self-medication or informal advice, increasing risks and delaying recovery from treatable mental health conditions.",

                        'seo_keywords'     => "mental health stigma, youth mental health, counselling",
                        'published_at'     => "2026-04-22 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Education",

                        'name'             => "Rohingya camp volunteer teachers receive formal certificates",
                        'sub_heading'      => "Training aims to improve classroom learning",
                        'content_shoulder' => "",
                        'brief'            => "Around 1,100 Rohingya camp volunteer teachers received formal certificates after training to improve classroom learning quality.",

                        "body"             => "Around 1,100 volunteer teachers from Rohingya camps received their first formal certificates under a UNICEF-AUW training programme. The initiative focused on improving classroom learning under the Myanmar Curriculum in Cox’s Bazar and Bhasan Char. Education partners said certified training can help teachers manage classrooms better, support displaced children, and strengthen structured learning in camp-based schools.",

                        'seo_keywords'     => "Rohingya education, teacher training, Cox Bazar",
                        'published_at'     => "2026-04-29 20:41:00",

                    ],

                    (object) [

                        "category_name"    => "Education",

                        'name'             => "Government to publish white paper on education sector",
                        'sub_heading'      => "",
                        'content_shoulder' => "Sector accountability",
                        'brief'            => "The education ministry plans a white paper on alleged irregularities and corruption in the education sector during past rule.",

                        "body"             => "Education Minister ANM Ehsanul Hoque Milon told parliament that the government will publish a white paper on irregularities and corruption in the education sector. The review is expected to cover alleged mismanagement during previous years and may examine spending, recruitment, infrastructure projects and institutional governance. The move places accountability and transparency at the centre of current education reform discussions.",

                        'seo_keywords'     => "education white paper, education corruption, Bangladesh education",
                        'published_at'     => "2026-04-24 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Education",

                        'name'             => "Free uniforms shoes and bags to reach students from July",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government will begin giving free uniforms, shoes and school bags to selected primary and secondary students from July.",

                        "body"             => "The government will start providing free uniforms, shoes and school bags to students from July under a pilot initiative. Education officials said the programme will initially cover selected institutions before wider expansion. The plan aims to reduce family education costs, improve student attendance and support children from low-income households who often struggle with basic school materials.",

                        'seo_keywords'     => "free school uniform, school bags, student support",
                        'published_at'     => "2026-04-29 17:09:00",

                    ],

                    (object) [

                        "category_name"    => "Education",

                        'name'             => "HSC and equivalent examinations scheduled to begin on July 2",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The 2026 HSC and equivalent examinations will begin on July 2, with authorities aiming to complete exams within five weeks.",

                        "body"             => "The Higher Secondary Certificate and equivalent examinations for 2026 are scheduled to begin on July 2. Education board officials said the revised schedule aims to complete the examinations within around five weeks. The announcement gives students, teachers and institutions a clearer timeline for final preparation, routine management and practical examination planning across general education boards.",

                        'seo_keywords'     => "HSC exam, exam routine, education board",
                        'published_at'     => "2026-04-18 15:35:00",

                    ],

                    (object) [

                        "category_name"    => "Education",

                        'name'             => "UGC prepares policy for ethical AI use in higher education",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The University Grants Commission is preparing a national policy to ensure ethical AI use in higher education institutions.",

                        "body"             => "The University Grants Commission has taken steps to formulate a policy for ethical use of artificial intelligence in higher education. Officials said universities need clear guidance as AI tools enter teaching, assessment, research and administration. The policy is expected to address responsible use, plagiarism risks, academic honesty, teacher readiness and student learning standards in a rapidly changing digital education environment.",

                        'seo_keywords'     => "UGC AI policy, higher education, ethical AI",
                        'published_at'     => "2026-04-23 23:02:00",

                    ],

                    (object) [

                        "category_name"    => "Schools",

                        'name'             => "Primary students to get dress shoes and jute bags from July",
                        'sub_heading'      => "Pilot support starts in selected schools",
                        'content_shoulder' => "",
                        'brief'            => "Class-I students in selected government primary schools will receive free dress, shoes and jute school bags from July.",

                        "body"             => "Education officials said Class-I students of selected government primary schools will receive one set of school dress, shoes and a jute bag from July. The initiative will begin as a pilot in two government primary schools in each upazila. Authorities said the programme will be expanded gradually to more government primary schools and later to madrasa and ebtedayi students.",

                        'seo_keywords'     => "primary students, school dress, jute bags",
                        'published_at'     => "2026-04-29 17:09:00",

                    ],

                    (object) [

                        "category_name"    => "Schools",

                        'name'             => "Selected Dhaka schools begin blended classroom model",
                        'sub_heading'      => "",
                        'content_shoulder' => "Hybrid classes",
                        'brief'            => "Selected Dhaka schools are shifting to three days online and three days in-person classes under a pilot education model.",

                        "body"             => "The government introduced a blended learning model in selected Dhaka schools, combining online and in-person classes. Under the proposed schedule, students attend physical classes on Saturday, Monday and Wednesday, while online classes take place on Sunday, Tuesday and Thursday. Officials said the pilot is designed to reduce traffic pressure, save energy and test technology-driven classroom continuity.",

                        'seo_keywords'     => "blended learning, Dhaka schools, online classes",
                        'published_at'     => "2026-04-10 03:57:00",

                    ],

                    (object) [

                        "category_name"    => "Schools",

                        'name'             => "Teacher absenteeism hurts primary education in Hatiya",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Inspections in Hatiya found empty classrooms, weak oversight and falling attendance in remote island primary schools.",

                        "body"             => "The primary education system in Hatiya, a remote island upazila of Noakhali, is facing serious pressure because of teacher absenteeism. Inspections reportedly found empty classrooms, poor monitoring and falling student attendance. Education observers said schools in hard-to-reach areas need stronger supervision, teacher accountability and local support so that children are not left behind because of distance and weak administration.",

                        'seo_keywords'     => "teacher absenteeism, Hatiya schools, primary education",
                        'published_at'     => "2026-04-11 16:33:00",

                    ],

                    (object) [

                        "category_name"    => "Schools",

                        'name'             => "Religious teacher recruitment plan targets primary schools",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government is considering recruiting around 9,000 religious teachers to strengthen moral education in primary schools.",

                        "body"             => "Education Minister ANM Ehsanul Hoque Milon told parliament that the government is considering recruiting around 9,000 religious teachers for primary schools. The plan is aimed at strengthening moral and religious education while respecting different religious sentiments. Officials also acknowledged recruitment complications, including certificate recognition issues, which will need to be resolved before implementation.",

                        'seo_keywords'     => "religious teachers, primary schools, moral education",
                        'published_at'     => "2026-03-15 18:58:00",

                    ],

                    (object) [

                        "category_name"    => "Schools",

                        'name'             => "Free schooling age extension under government discussion",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government is considering extending free schooling to children up to age 13 to support skill-based education goals.",

                        "body"             => "The government is considering extending free schooling to children up to age 13 as part of a broader plan to build a skill-based and career-oriented education system. Officials said the move could reduce dropout risk and give children more time to build foundational learning. If implemented, the change may require stronger school capacity, teacher planning, curriculum support and budget allocation.",

                        'seo_keywords'     => "free schooling, school reform, skill education",
                        'published_at'     => "2026-04-22 14:01:00",

                    ],

                    (object) [

                        "category_name"    => "Universities",

                        'name'             => "Former DU VC joins Grameen University as senior adviser",
                        'sub_heading'      => "New campus being built in Gazipur",
                        'content_shoulder' => "",
                        'brief'            => "Former Dhaka University vice-chancellor Niaz Ahmed has joined Grameen University as senior adviser amid campus development.",

                        "body"             => "Former Dhaka University vice-chancellor Niaz Ahmed has joined Grameen University as a senior adviser. The university is building its campus on around 33 acres in Gazipur, making the appointment important for academic planning and institutional development. University stakeholders expect experienced leadership to support curriculum design, governance, faculty recruitment and long-term higher education strategy.",

                        'seo_keywords'     => "Grameen University, Dhaka University, higher education",
                        'published_at'     => "2026-04-29 01:26:00",

                    ],

                    (object) [

                        "category_name"    => "Universities",

                        'name'             => "Education minister urges universities to protect intellectual property",
                        'sub_heading'      => "",
                        'content_shoulder' => "Research protection",
                        'brief'            => "The education minister called for stronger university action to protect innovation, research and intellectual property rights.",

                        "body"             => "Education Minister ANM Ehsanul Hoque Milon urged universities and relevant stakeholders to take stronger initiatives to protect intellectual property. He said higher education must become world-standard and connected to innovation. The call highlights the need for universities to support research commercialisation, patent awareness, technology transfer and stronger protection for student and faculty innovations.",

                        'seo_keywords'     => "intellectual property, university research, innovation",
                        'published_at'     => "2026-04-26 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Universities",

                        'name'             => "Gazipur Agricultural University leads agri universities in Asia ranking",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Gazipur Agricultural University secured the top position among Bangladesh agricultural universities in THE Asia rankings.",

                        "body"             => "Gazipur Agricultural University secured first position among Bangladesh’s agricultural universities in the 2026 Times Higher Education Asia University Rankings. The university was placed in the 401–500 band, according to the ranking report. The achievement highlights growing attention to agricultural research, teaching quality and institutional performance in a sector central to Bangladesh’s food security and rural economy.",

                        'seo_keywords'     => "GAU ranking, agricultural university, THE ranking",
                        'published_at'     => "2026-04-25 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Universities",

                        'name'             => "Modern labs and library opened at DU pharmacy faculty",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Dhaka University pharmacy faculty inaugurated two modern laboratories and a seminar library to support education and research.",

                        "body"             => "Dhaka University’s pharmacy faculty inaugurated two modern laboratories and a seminar library to strengthen education and research. The new facilities are expected to support practical learning, advanced laboratory work and academic collaboration among students and faculty members. University officials said infrastructure improvement is essential for modern pharmacy education and research quality.",

                        'seo_keywords'     => "Dhaka University, pharmacy faculty, research labs",
                        'published_at'     => "2026-04-23 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Universities",

                        'name'             => "Dhaka Central University ordinance formally issued",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The government issued an ordinance forming Dhaka Central University to oversee seven public colleges in the capital.",

                        "body"             => "The government issued an ordinance forming Dhaka Central University to oversee higher education and research at seven public colleges in Dhaka. The decision is expected to reshape governance, academic coordination and administrative control over the colleges. Education observers said the new university structure may help reduce long-running session delays, improve research supervision and create clearer institutional accountability.",

                        'seo_keywords'     => "Dhaka Central University, seven colleges, higher education",
                        'published_at'     => "2026-02-09 11:29:00",

                    ],

                    (object) [

                        "category_name"    => "Exams",

                        'name'             => "Nearly 28,000 absent on fifth day of SSC exams",
                        'sub_heading'      => "Absence remains high in public examinations",
                        'content_shoulder' => "",
                        'brief'            => "A total of 27,891 examinees were absent on the fifth day of the ongoing SSC and equivalent examinations nationwide.",

                        "body"             => "A total of 27,891 examinees were absent on the fifth day of the ongoing Secondary School Certificate and equivalent examinations. The figure again raised concern over attendance, exam readiness and student participation. Education officials are monitoring board-wise exam operations while institutions continue to support candidates through schedule reminders, centre discipline and preparation guidance.",

                        'seo_keywords'     => "SSC exam, absent examinees, Bangladesh exams",
                        'published_at'     => "2026-04-30 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Exams",

                        'name'             => "Over 32,000 absent from SSC English second paper test",
                        'sub_heading'      => "",
                        'content_shoulder' => "Exam attendance",
                        'brief'            => "More than 32,000 candidates were absent from the English second paper test of SSC and equivalent examinations.",

                        "body"             => "A total of 32,392 candidates were absent from the English second paper test of the ongoing SSC and equivalent examinations. The absence figure reflects a continuing challenge in public examination participation. Education boards are expected to review attendance patterns, centre reports and candidate support needs as the exams continue across the country.",

                        'seo_keywords'     => "SSC English exam, exam absence, public exams",
                        'published_at'     => "2026-04-29 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Exams",

                        'name'             => "More than 34,000 absent on third day of SSC exams",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The third day of SSC and equivalent examinations saw 34,054 candidates absent, keeping attendance concerns in focus.",

                        "body"             => "A total of 34,054 examinees were absent on the third day of the ongoing Secondary School Certificate and equivalent examinations. The figure came as education authorities continued to conduct exams across multiple boards. High absence numbers may reflect preparation gaps, financial hardship, migration, illness or other barriers that prevent students from sitting for major public examinations.",

                        'seo_keywords'     => "SSC absence, exam candidates, education board",
                        'published_at'     => "2026-04-27 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Exams",

                        'name'             => "Wrong question set leads to action against centre officials",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Three centre officials were relieved after around 100 Habiganj Dakhil candidates received the wrong question paper.",

                        "body"             => "Three centre officials were relieved from duty after around 100 candidates reportedly received an incorrect question set during a Habiganj Dakhil examination. Authorities said fair evaluation would be ensured for the affected students. The incident underlines the need for stricter centre management, question paper handling, invigilator training and quick response systems during public examinations.",

                        'seo_keywords'     => "Dakhil exam, wrong question, exam centre",
                        'published_at'     => "2026-04-23 10:30:00",

                    ],

                    (object) [

                        "category_name"    => "Exams",

                        'name'             => "HSC routine published for nine general education boards",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The HSC routine was published for nine general education boards, with written examinations scheduled to start on July 2.",

                        "body"             => "The Dhaka education board published the HSC examination routine for nine general boards, including Dhaka, Rajshahi, Jashore, Cumilla, Chattogram, Barishal, Sylhet, Dinajpur and Mymensingh. The written examinations are scheduled to begin on July 2. Students can now plan revision, practical preparation and subject-wise study schedules based on the official timetable.",

                        'seo_keywords'     => "HSC routine, education boards, exam schedule",
                        'published_at'     => "2026-04-22 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Scholarships",

                        'name'             => "Sixteen DU students receive Tk 2.5 lakh in scholarships",
                        'sub_heading'      => "Memorial trust funds support students",
                        'content_shoulder' => "",
                        'brief'            => "Sixteen Dhaka University students received around Tk 2.5 lakh in scholarships under two memorial trust funds.",

                        "body"             => "Sixteen students of Dhaka University received scholarships worth around Tk 2.5 lakh under two memorial trust funds. The scholarship ceremony recognised student achievement and financial need while encouraging continued academic effort. University officials said such trust-based scholarships can help students manage study expenses, remain focused on learning and pursue stronger academic performance.",

                        'seo_keywords'     => "DU scholarships, student support, memorial trust",
                        'published_at'     => "2026-04-29 00:38:00",

                    ],

                    (object) [

                        "category_name"    => "Scholarships",

                        'name'             => "Foundation awards scholarships to DU business students",
                        'sub_heading'      => "",
                        'content_shoulder' => "Merit support",
                        'brief'            => "The Dabir-Sohani-Suraiya Foundation awarded scholarships to financially challenged DU business studies students.",

                        "body"             => "The Dabir-Sohani-Suraiya Foundation awarded scholarships to meritorious and financially challenged students of Dhaka University’s Faculty of Business Studies. The ceremony was held at Professor Dr Abdullah Farouk Multipurpose Hall. Faculty leaders and foundation representatives said the support can reduce financial pressure, reward achievement and encourage students to continue higher education with confidence.",

                        'seo_keywords'     => "DU business studies, scholarship, student finance",
                        'published_at'     => "2026-04-21 17:26:00",

                    ],

                    (object) [

                        "category_name"    => "Scholarships",

                        'name'             => "Primary scholarship examination begins under national schedule",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The Primary Scholarship Examination 2025 began with the Bangla test and continued under the nationwide examination schedule.",

                        "body"             => "The Primary Scholarship Examination 2025 began with the Bangla test and was scheduled to continue under the nationwide routine. The examination gives high-performing students a chance to receive recognition and financial support. Schools and guardians are closely monitoring the process because scholarship exams can motivate students, but also require fair administration and balanced academic pressure.",

                        'seo_keywords'     => "primary scholarship, scholarship exam, student merit",
                        'published_at'     => "2026-04-15 11:28:00",

                    ],

                    (object) [

                        "category_name"    => "Scholarships",

                        'name'             => "Malaysia considers more scholarships for Bangladeshi students",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Malaysia expressed readiness to consider expanding scholarship allocations for Bangladeshi students in higher education.",

                        "body"             => "Malaysia has expressed readiness to consider expanding scholarship allocations for Bangladeshi students under existing and future higher education programmes. The interest was conveyed during a bilateral meeting focused on education, labour and employment cooperation. Expanded scholarships could help more Bangladeshi students access Malaysian universities, skills programmes and international academic pathways.",

                        'seo_keywords'     => "Malaysia scholarships, Bangladeshi students, study abroad",
                        'published_at'     => "2026-04-11 03:23:00",

                    ],

                    (object) [

                        "category_name"    => "Scholarships",

                        'name'             => "Government considers doubling scholarship amounts",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A draft proposal would double scholarship amounts across primary, junior, SSC, HSC and honours education levels.",

                        "body"             => "The government is considering a proposal to double scholarship amounts across several education levels. The draft would raise monthly merit and general scholarship rates in primary, junior, SSC, HSC and honours levels. Education stakeholders said higher scholarship amounts could reduce financial pressure on students, reward merit and support quality education if implemented with transparent selection and timely payment.",

                        'seo_keywords'     => "scholarship amount, student stipend, education support",
                        'published_at'     => "2026-04-18 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Policy",

                        'name'             => "White paper planned on past education sector irregularities",
                        'sub_heading'      => "Investigation scope may be wide",
                        'content_shoulder' => "",
                        'brief'            => "The education ministry plans to publish a white paper examining alleged irregularities and corruption in the sector.",

                        "body"             => "The government plans to publish a white paper outlining alleged irregularities and corruption in the education sector during previous years. Officials said the scope may be wide, covering multiple fiscal years and different parts of education administration. The policy move could influence future reform, procurement review, recruitment accountability and public confidence in education governance.",

                        'seo_keywords'     => "education policy, white paper, governance",
                        'published_at'     => "2026-04-23 23:16:00",

                    ],

                    (object) [

                        "category_name"    => "Policy",

                        'name'             => "UGC drafts ethical AI policy for universities",
                        'sub_heading'      => "",
                        'content_shoulder' => "AI governance",
                        'brief'            => "The University Grants Commission is drafting a national policy for ethical artificial intelligence use in universities.",

                        "body"             => "The University Grants Commission is formulating a policy to ensure ethical use of artificial intelligence in higher education. The initiative comes as students and teachers increasingly use AI tools for writing, research, coding and learning support. A clear policy could help universities address plagiarism, data privacy, assessment fairness and responsible innovation without blocking useful digital learning.",

                        'seo_keywords'     => "AI policy, UGC, university governance",
                        'published_at'     => "2026-04-23 23:02:00",

                    ],

                    (object) [

                        "category_name"    => "Policy",

                        'name'             => "Education ministry weighs free schooling up to age 13",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Officials are considering free schooling up to age 13 as part of a skill-based and career-oriented education reform plan.",

                        "body"             => "The education ministry is considering extending free schooling to children up to age 13. The proposal is linked to a broader goal of making education more skill-based, career-oriented and inclusive. Policy experts say implementation would require careful planning around school capacity, teacher deployment, curriculum design, financing and support for children at risk of dropping out.",

                        'seo_keywords'     => "free education, education reform, school policy",
                        'published_at'     => "2026-04-22 14:01:00",

                    ],

                    (object) [

                        "category_name"    => "Policy",

                        'name'             => "Third language plan raises school curriculum debate",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A proposed third language at secondary level has opened debate over implementation, teacher capacity and student workload.",

                        "body"             => "The government’s plan to introduce a third language at the secondary level has triggered discussion over realistic implementation. Supporters say language skills can help students access global education, employment and scholarships, while critics warn that teacher shortages and weak planning could turn the idea into an added burden. A phased approach may be needed to avoid harming core learning.",

                        'seo_keywords'     => "third language, curriculum reform, secondary education",
                        'published_at'     => "2026-03-31 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Policy",

                        'name'             => "UGC drafting anti-plagiarism policy for higher education",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The UGC is collecting stakeholder opinions on an anti-plagiarism policy for academic and research reports in universities.",

                        "body"             => "The University Grants Commission is drafting a policy to prevent plagiarism in academic and research reports in higher education. Stakeholder opinions are being collected from university teachers, researchers, administrators and other relevant groups before finalisation. The policy could strengthen research ethics, protect academic integrity and improve confidence in university publications and student work.",

                        'seo_keywords'     => "plagiarism policy, research ethics, higher education",
                        'published_at'     => "2026-03-09 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Lifestyle",

                        'name'             => "Shompa Kabir showcases Bangladeshi food on MasterChef USA",
                        'sub_heading'      => "Halibut bhuna earns global attention",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi-origin chef Shompa Kabir brought halibut bhuna to MasterChef USA, giving local cuisine global attention.",

                        "body"             => "Bangladeshi-origin chef and Brooklyn entrepreneur Shompa Kabir secured a MasterChef USA apron after presenting halibut bhuna with basmati rice and daal. Her appearance turned a familiar Bangladeshi comfort dish into a proud cultural statement on an international cooking stage. The moment also highlighted how diaspora cooks are using global platforms to preserve memory, identity and everyday Bengali flavours.",

                        'seo_keywords'     => "Shompa Kabir, MasterChef USA, Bangladeshi food",
                        'published_at'     => "2026-04-30 20:24:00",

                    ],

                    (object) [

                        "category_name"    => "Lifestyle",

                        'name'             => "Sylhet rainy season travel gains fresh appeal",
                        'sub_heading'      => "",
                        'content_shoulder' => "Monsoon escape",
                        'brief'            => "Sylhet’s Ratargul, Jaflong and rainy landscapes are being promoted as ideal monsoon escapes for Bangladeshi travellers.",

                        "body"             => "Sylhet is being highlighted as one of Bangladesh’s most rewarding rainy-season destinations, with Ratargul Swamp Forest, Jaflong and misty hill views offering a dramatic monsoon experience. Travel writers advise visitors to prepare for wet roads, slippery paths and sudden downpours, while also enjoying local food, boat rides and greener landscapes that become more vibrant during rain.",

                        'seo_keywords'     => "Sylhet travel, monsoon tourism, Bangladesh travel",
                        'published_at'     => "2026-04-30 20:30:00",

                    ],

                    (object) [

                        "category_name"    => "Lifestyle",

                        'name'             => "Sabina Khan brings Bangladeshi flavours to MasterChef UK",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi-origin Sabina Khan entered MasterChef UK 2026 with a cooking style rooted in memory and sustainability.",

                        "body"             => "Bangladeshi-origin cook Sabina Khan, known for Sabina’s Flavour Lab, has entered the MasterChef UK 2026 spotlight with a style shaped by heritage, sustainability and experimentation. Her cooking blends Bangladeshi memories with global techniques while keeping waste reduction at the centre. Her journey reflects how Bangladeshi food culture is gaining new visibility through diaspora creators.",

                        'seo_keywords'     => "Sabina Khan, MasterChef UK, Bangladeshi cuisine",
                        'published_at'     => "2026-04-28 20:55:00",

                    ],

                    (object) [

                        "category_name"    => "Lifestyle",

                        'name'             => "Asad Sattar redefines youth culture through Arka",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Entrepreneur Asad Sattar is reshaping Dhaka youth lifestyle by blending fashion, community events and culture.",

                        "body"             => "Entrepreneur Asad Sattar’s work through Arka, Ami Dhaka and Kathal is being recognised for reshaping how young Bangladeshis experience culture. His projects combine fashion, events, commerce and community into spaces that feel contemporary but still rooted in South Asian identity. The approach shows how urban youth lifestyle is moving beyond simple shopping into shared cultural participation.",

                        'seo_keywords'     => "Asad Sattar, Arka, Dhaka lifestyle",
                        'published_at'     => "2026-04-26 19:08:00",

                    ],

                    (object) [

                        "category_name"    => "Lifestyle",

                        'name'             => "Art galleries become meaningful dating spaces in Dhaka",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Young couples are turning to art galleries as calmer, conversation-friendly alternatives to cafés and restaurants.",

                        "body"             => "Art galleries are being described as a new lifestyle choice for dating, especially among young urban Bangladeshis looking for deeper conversations. Instead of sitting across from each other in crowded cafés, couples can walk, observe paintings and respond to shared experiences. The trend points to a quieter form of connection where culture, curiosity and compatibility shape modern relationships.",

                        'seo_keywords'     => "dating spots, art galleries, Dhaka lifestyle",
                        'published_at'     => "2026-04-23 17:56:00",

                    ],

                    (object) [

                        "category_name"    => "Fashion",

                        'name'             => "Teep returns as a bold urban fashion statement",
                        'sub_heading'      => "Traditional mark gains modern styling",
                        'content_shoulder' => "",
                        'brief'            => "The Bengali teep is being styled with jeans, sneakers and office wear, turning tradition into modern self-expression.",

                        "body"             => "The teep is no longer limited to festive sarees or ritual dress. In Bangladesh’s urban fashion scene, young women are wearing black, maroon and colourful teeps with jeans, oversized shirts, office outfits and fusion wear. The accessory now works as a bridge between cultural memory and personal style, proving that tradition can remain recognisable while adapting to modern wardrobes.",

                        'seo_keywords'     => "teep fashion, Bengali style, women fashion",
                        'published_at'     => "2026-04-21 16:16:00",

                    ],

                    (object) [

                        "category_name"    => "Fashion",

                        'name'             => "Slow fashion gains ground among conscious shoppers",
                        'sub_heading'      => "",
                        'content_shoulder' => "Buy less better",
                        'brief'            => "Slow fashion is encouraging Bangladeshi consumers to choose durable fabrics, local craft and versatile clothing.",

                        "body"             => "Slow fashion is gaining attention as Bangladeshi consumers rethink fast-changing trends and overflowing wardrobes. The movement encourages people to buy fewer but better-made clothes, prioritise cotton, linen, silk and handwoven textiles, and support local artisans. It also promotes restyling old sarees, kurtas and co-ord sets so that fashion becomes more affordable, ethical and long-lasting.",

                        'seo_keywords'     => "slow fashion, sustainable fashion, local artisans",
                        'published_at'     => "2026-03-14 13:42:00",

                    ],

                    (object) [

                        "category_name"    => "Fashion",

                        'name'             => "Bangladeshi designer Tanvir Mahidy debuts at London Fashion Week",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Designer Tanvir Mahidy’s London Fashion Week debut placed Bangladeshi identity and denim activism on a global runway.",

                        "body"             => "Manchester-based Bangladeshi designer Tanvir Mahidy made a historic debut at London Fashion Week 2026, presenting a collection that combined skilled tailoring, bold patterns and a message about denim waste. His runway moment gave Bangladeshi fashion wider global visibility and showed how designers from the diaspora can connect style with environmental awareness and cultural storytelling.",

                        'seo_keywords'     => "Tanvir Mahidy, London Fashion Week, Bangladeshi designer",
                        'published_at'     => "2026-02-24 18:15:00",

                    ],

                    (object) [

                        "category_name"    => "Fashion",

                        'name'             => "Spring saree trends favour florals and bold prints",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bright floral sarees, contrasting colours and playful blouse styles are shaping spring fashion in Bangladesh.",

                        "body"             => "Spring saree fashion in Bangladesh is leaning toward bright florals, bold prints, cheerful colours and playful blouse pairings. The trend reflects a broader return to festive but wearable ethnic fashion, where traditional drapes are updated with expressive styling. Designers and shoppers are using colour, contrast and accessories to make sarees feel fresh for seasonal gatherings and everyday celebration.",

                        'seo_keywords'     => "spring saree, floral saree, Bangladesh fashion",
                        'published_at'     => "2026-02-16 19:43:00",

                    ],

                    (object) [

                        "category_name"    => "Fashion",

                        'name'             => "Panjabi and Kabuli sets shape Eid menswear",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Traditional panjabi looks and modern Kabuli sets remained key Eid fashion choices for Bangladeshi men this season.",

                        "body"             => "Eid menswear in Bangladesh continued to mix traditional panjabi styles with modern Kabuli sets, koti layers and refined colour palettes. Sage, azure and neutral tones appeared alongside slim-fit cuts, giving festive clothing a polished urban look. The trend shows how men’s ethnic fashion is evolving from simple occasion wear into more styled, layered and personality-driven outfits.",

                        'seo_keywords'     => "panjabi fashion, Eid menswear, Kabuli set",
                        'published_at'     => "2026-03-09 09:00:00",

                    ],

                    (object) [

                        "category_name"    => "Travel",

                        'name'             => "Sylhet monsoon guide highlights Ratargul and Jaflong",
                        'sub_heading'      => "Rain transforms popular nature spots",
                        'content_shoulder' => "",
                        'brief'            => "Sylhet’s rainy season travel guide highlights Ratargul Swamp Forest, Jaflong and local food as key attractions.",

                        "body"             => "A new Sylhet monsoon travel guide highlights Ratargul Swamp Forest, Jaflong, haor landscapes and rainy roadside food experiences as reasons to visit during the wet season. The guide also reminds travellers to use waterproof bags, grippy shoes and careful planning. For Bangladeshis seeking domestic nature trips, Sylhet offers a mix of mist, waterfalls, river views and seasonal adventure.",

                        'seo_keywords'     => "Sylhet monsoon, Ratargul, Jaflong",
                        'published_at'     => "2026-04-30 20:30:00",

                    ],

                    (object) [

                        "category_name"    => "Travel",

                        'name'             => "Nepal trekking guide targets first-time Bangladeshi travellers",
                        'sub_heading'      => "",
                        'content_shoulder' => "Beginner trek tips",
                        'brief'            => "A Nepal trekking guide advises Bangladeshi beginners to choose reliable boots, backpacks and realistic routes.",

                        "body"             => "A beginner-focused Nepal trekking guide is speaking directly to Bangladeshi travellers who want their first Himalayan experience. The guide warns against poor gear choices and encourages reliable hiking boots, ergonomic backpacks, weather preparation and realistic route planning. Nepal remains attractive for Bangladeshis because it is nearby, comparatively accessible and offers a gateway into mountain trekking culture.",

                        'seo_keywords'     => "Nepal trekking, Bangladeshi travellers, hiking gear",
                        'published_at'     => "2026-04-23 18:11:00",

                    ],

                    (object) [

                        "category_name"    => "Travel",

                        'name'             => "Hajj flights set to carry Bangladeshi pilgrims",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh is preparing hajj flights for 78,500 pilgrims, making pilgrimage travel a major seasonal movement.",

                        "body"             => "Bangladesh prepared to begin hajj flight operations for the 2026 pilgrimage season, with 78,500 pilgrims expected to travel to Saudi Arabia. The launch is one of the country’s largest annual travel movements and requires coordination among airlines, agencies, immigration, health teams and religious authorities. Pilgrims and families are now focusing on documents, schedules, luggage rules and final preparation.",

                        'seo_keywords'     => "hajj flights, Bangladesh pilgrims, Saudi travel",
                        'published_at'     => "2026-04-16 20:08:00",

                    ],

                    (object) [

                        "category_name"    => "Travel",

                        'name'             => "Quick international getaways become popular from Dhaka",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Short international trips from Dhaka are being promoted for workers and students seeking quick breaks abroad.",

                        "body"             => "Short international getaways are gaining attention among Bangladeshis who want three-to-five-day breaks without exhausting leave, budget or energy. Destinations near Dhaka can offer beaches, mountains, city skylines, food and culture within a compact schedule. The trend reflects a growing travel preference for efficient planning, affordable packages and experiences that fit around work, study and urban fatigue.",

                        'seo_keywords'     => "Dhaka travel, short getaway, international trip",
                        'published_at'     => "2026-03-26 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Travel",

                        'name'             => "Netrokona promoted as quiet nature escape",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Netrokona’s China Clay Hills, Birishiri Lake and quieter landscapes are being promoted for crowd-free domestic travel.",

                        "body"             => "Netrokona is being promoted as a quieter domestic travel option for Bangladeshis who want nature without heavy crowds. The area’s China Clay Hills, Birishiri Lake and scenic rural routes offer a different experience from busier tourist hubs. Travel writers suggest the district for visitors seeking calm landscapes, photography spots, local history and a slower pace that still feels adventurous.",

                        'seo_keywords'     => "Netrokona travel, Birishiri, China Clay Hills",
                        'published_at'     => "2026-03-01 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Food",

                        'name'             => "Shompa Kabir wins MasterChef apron with halibut bhuna",
                        'sub_heading'      => "Bangladeshi dish impresses global judges",
                        'content_shoulder' => "",
                        'brief'            => "Shompa Kabir’s halibut bhuna with rice and daal earned a MasterChef USA apron and celebrated Bangladeshi cuisine.",

                        "body"             => "Shompa Kabir’s halibut bhuna, served with basmati rice and daal, earned praise from MasterChef USA judges and secured her a place in the competition. The dish carried the memory of Bangladeshi seafood cooking and turned a household favourite into a polished plate for global television. Her success adds momentum to the international recognition of Bangladeshi home-style flavours.",

                        'seo_keywords'     => "halibut bhuna, MasterChef, Bangladeshi cuisine",
                        'published_at'     => "2026-04-30 20:24:00",

                    ],

                    (object) [

                        "category_name"    => "Food",

                        'name'             => "Bhorta celebrated as Bangladesh’s humble comfort food",
                        'sub_heading'      => "",
                        'content_shoulder' => "Simple food joy",
                        'brief'            => "Bhorta is being celebrated as a humble Bangladeshi food that proves flavour does not need expensive ingredients.",

                        "body"             => "Bhorta has returned to the food conversation as proof that Bangladeshi happiness often comes from simple, hand-mashed ingredients. Mustard oil, raw onion, green chilli, mashed fish, vegetables or lentils can create a powerful dish without expensive presentation. The renewed attention to bhorta reflects a wider appreciation for everyday Bengali food, memory and the emotional comfort of home cooking.",

                        'seo_keywords'     => "bhorta, Bengali food, comfort food",
                        'published_at'     => "2026-04-13 19:56:00",

                    ],

                    (object) [

                        "category_name"    => "Food",

                        'name'             => "Baishakh sweets keep family food memories alive",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Traditional Baishakh sweets are being remembered as edible family memories passed through generations in Bangladesh.",

                        "body"             => "Baishakh sweets are being celebrated not just as festive treats but as edible memories passed down through Bangladeshi families. Crunchy peanut sweets, sugar drops and other nostalgic items carry the warmth of home and the excitement of the Bengali New Year. These recipes remind readers that food culture is often preserved through small seasonal rituals, family kitchens and shared celebration.",

                        'seo_keywords'     => "Baishakh sweets, Bengali New Year, festive food",
                        'published_at'     => "2026-04-12 21:37:00",

                    ],

                    (object) [

                        "category_name"    => "Food",

                        'name'             => "Nayeem Ashraf returns to build culinary industry",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Chef Nayeem Ashraf returned to Bangladesh with plans to strengthen professional culinary training and hospitality.",

                        "body"             => "Chef Nayeem Ashraf’s return to Bangladesh has drawn attention to the country’s growing culinary industry. After choosing restaurant kitchens over a more predictable career abroad, he is now focused on building training, hospitality skills and professional food culture. His story reflects how Bangladesh’s food sector is moving beyond home cooking and restaurant trends toward formal culinary education and career development.",

                        'seo_keywords'     => "Nayeem Ashraf, culinary industry, chef training",
                        'published_at'     => "2026-04-05 17:17:00",

                    ],

                    (object) [

                        "category_name"    => "Food",

                        'name'             => "High-protein iftar ideas use everyday Bangladeshi foods",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Everyday items like eggs, lentils, chickpeas, yoghurt, fish and chicken are being promoted for healthier iftar meals.",

                        "body"             => "High-protein iftar ideas are encouraging Bangladeshi families to use familiar foods more thoughtfully during Ramadan. Eggs, lentils, chickpeas, yoghurt, chicken and fish are already available in many homes, but preparation and balance can make meals healthier. The guidance suggests that one protein-rich item per iftar can support energy and reduce dependence on fried snacks and sweet drinks.",

                        'seo_keywords'     => "high protein iftar, Ramadan food, healthy diet",
                        'published_at'     => "2026-02-16 19:30:00",

                    ],

                    (object) [

                        "category_name"    => "Relationships",

                        'name'             => "Art galleries become calmer dating spots for young couples",
                        'sub_heading'      => "Culture helps start better conversations",
                        'content_shoulder' => "",
                        'brief'            => "Young couples are choosing art galleries for quieter dates that encourage conversation, observation and compatibility.",

                        "body"             => "Art galleries are becoming attractive dating spaces for young Bangladeshis who want something calmer than cafés or restaurants. Walking through exhibitions gives couples natural conversation starters and reduces the pressure of forced small talk. The trend suggests that modern relationships are increasingly shaped by shared experiences, emotional curiosity and compatibility rather than only formal outings or online chats.",

                        'seo_keywords'     => "dating culture, art galleries, relationship tips",
                        'published_at'     => "2026-04-23 17:56:00",

                    ],

                    (object) [

                        "category_name"    => "Relationships",

                        'name'             => "Money secrecy raises relationship red flag debate",
                        'sub_heading'      => "",
                        'content_shoulder' => "Financial honesty",
                        'brief'            => "A relationship debate asks how much financial information partners should share before trust becomes affected.",

                        "body"             => "A recent relationship discussion focused on whether hiding money from a partner should be treated as a serious red flag. The issue touches rent, shared bills, subscriptions, debts, savings and long-term planning. For Bangladeshi couples, where family expectations and social pressure often surround marriage, financial honesty can become a major part of trust, responsibility and emotional security.",

                        'seo_keywords'     => "financial honesty, relationship advice, money trust",
                        'published_at'     => "2026-02-14 18:20:00",

                    ],

                    (object) [

                        "category_name"    => "Relationships",

                        'name'             => "Love marriage and arranged marriage debate continues in Dhaka",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Young Dhakaites are debating whether love marriage or modern arranged marriage better fits today’s expectations.",

                        "body"             => "The love marriage versus arranged marriage debate remains lively among young people in Dhaka. Some see love marriage as a sign of independence and emotional courage, while others view modern arranged marriage as a curated introduction where children still hold the final choice. The discussion shows that marriage in Bangladesh is rarely just about two people; it still involves families, expectations and compromise.",

                        'seo_keywords'     => "love marriage, arranged marriage, Dhaka youth",
                        'published_at'     => "2026-02-14 18:15:00",

                    ],

                    (object) [

                        "category_name"    => "Relationships",

                        'name'             => "Marriage expenses reshape family decisions in Bangladesh",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Inflation and rising event costs are changing how middle-class families plan weddings and relationship milestones.",

                        "body"             => "Rising wedding costs are reshaping family decisions across Bangladesh, especially for middle and lower-middle-income households. Venue, decoration, food, photography, clothing and jewellery expenses have made weddings harder to manage without debt or compromise. The shift is affecting not only ceremonies but also how couples and families discuss expectations, affordability, social status and the meaning of a successful marriage.",

                        'seo_keywords'     => "wedding cost, marriage planning, Bangladesh families",
                        'published_at'     => "2025-12-17 20:35:00",

                    ],

                    (object) [

                        "category_name"    => "Relationships",

                        'name'             => "Young generation questions pressure around marriage",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Many young Bangladeshis feel exhausted by family expectations, social judgment and pressure linked to marriage.",

                        "body"             => "A growing relationship conversation asks why many young Bangladeshis feel exhausted by the idea of marriage. Family expectations, public judgment, financial pressure and the fear of merging two households can make marriage feel overwhelming rather than romantic. The discussion reflects a generational shift where young adults want partnership, emotional safety and choice, not only ceremony and approval.",

                        'seo_keywords'     => "marriage pressure, young adults, relationship culture",
                        'published_at'     => "2025-12-25 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Culture",

                        'name'             => "Ethnological museum in Chattogram faces declining visitors",
                        'sub_heading'      => "Rare cultural collection needs revival",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s only ethnological museum is facing neglect, fewer visitors and calls for digital preservation.",

                        "body"             => "Bangladesh’s only ethnological museum in Agrabad, Chattogram, is facing declining visitors and concerns over neglect. The museum holds thousands of relics linked to Bangla culture and ethnic communities, but experts say many groups remain underrepresented. Suggestions include digital archiving, interactive displays, QR codes, virtual reality experiences and stronger conservation work to reconnect younger audiences with cultural diversity.",

                        'seo_keywords'     => "ethnological museum, Chattogram, cultural heritage",
                        'published_at'     => "2026-04-23 13:35:00",

                    ],

                    (object) [

                        "category_name"    => "Culture",

                        'name'             => "Boishakhi Shobhajatra name chosen for New Year procession",
                        'sub_heading'      => "",
                        'content_shoulder' => "Festival identity",
                        'brief'            => "The culture ministry announced that the Pohela Boishakh procession would be renamed Boishakhi Shobhajatra.",

                        "body"             => "The culture ministry announced that the traditional Pohela Boishakh procession would be called Boishakhi Shobhajatra, saying the name was chosen to end debate around the event. The procession remains one of Bangladesh’s most visible cultural celebrations, bringing together art, masks, music, colour and public participation. The naming decision shows how cultural symbols continue to generate discussion around heritage and identity.",

                        'seo_keywords'     => "Boishakhi Shobhajatra, Pohela Boishakh, culture ministry",
                        'published_at'     => "2026-04-05 15:50:00",

                    ],

                    (object) [

                        "category_name"    => "Culture",

                        'name'             => "Dhaka amateur radio community keeps global links alive",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s amateur radio operators are keeping a technical culture alive through rooftop communication and global contact.",

                        "body"             => "Bangladesh’s amateur radio community is gaining renewed attention through operators who connect with the world from small rooms and rooftops in Dhaka. Their work depends on ingenuity, physics, equipment knowledge and persistence rather than ordinary internet communication. The story highlights a niche but meaningful technical culture that combines hobby, emergency communication skills and global friendship.",

                        'seo_keywords'     => "amateur radio, Dhaka culture, radio operators",
                        'published_at'     => "2026-04-06 19:00:00",

                    ],

                    (object) [

                        "category_name"    => "Culture",

                        'name'             => "Bangladeshi wedding culture changes under inflation pressure",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Inflation is changing wedding culture as families balance social expectations with rising costs and limited income.",

                        "body"             => "Bangladesh’s wedding culture is changing as inflation and reduced purchasing power affect how families plan ceremonies. Weddings that once worked as community festivals have increasingly become expensive social productions involving venues, photography, outfits, jewellery and curated decoration. Many middle-income families are now cutting costs while still trying to protect dignity, hospitality and cultural expectations.",

                        'seo_keywords'     => "wedding culture, inflation, Bangladesh society",
                        'published_at'     => "2025-12-17 20:35:00",

                    ],

                    (object) [

                        "category_name"    => "Culture",

                        'name'             => "Rooftop radio story reflects Bangladesh’s hidden hobby culture",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A Dhaka rooftop radio story shows how dedicated hobbyists preserve community, science and communication culture.",

                        "body"             => "The story of Bangladesh’s amateur radio operators reveals a hidden hobby culture built on patience, learning and community. From Mohammadpur rooftops and cluttered rooms, operators communicate across borders without relying on the usual online platforms. Their practice connects science, emergency readiness, technical curiosity and a sense of belonging, showing that culture can also grow through specialised skills and shared passion.",

                        'seo_keywords'     => "hobby culture, radio community, Bangladesh culture",
                        'published_at'     => "2026-04-07 22:23:00",

                    ],

                    (object) [

                        "category_name"    => "Science",

                        'name'             => "Young Scientist Congress puts AI at centre of science agenda",
                        'sub_heading'      => "Researchers showcase future-focused ideas",
                        'content_shoulder' => "",
                        'brief'            => "Young scientists from Bangladesh showcased AI-focused research and innovation at a national science congress in Dhaka.",

                        "body"             => "The 6th Young Scientist Congress placed artificial intelligence at the centre of Bangladesh’s science and technology discussion. Young researchers from universities, research institutions and technology organisations presented ideas, projects and future prospects linked to AI. Officials said the participation of young scientists could accelerate scientific advancement and help Bangladesh build a more knowledge-based and innovation-driven economy.",

                        'seo_keywords'     => "Young Scientist Congress, AI research, Bangladesh science",
                        'published_at'     => "2026-04-04 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Science",

                        'name'             => "National AI institute plan gains fresh policy attention",
                        'sub_heading'      => "",
                        'content_shoulder' => "AI future",
                        'brief'            => "The government has discussed establishing a national AI institute to support data security, research and applied innovation.",

                        "body"             => "Science and technology officials said the government has taken initiatives to establish a national artificial intelligence institute. The plan is expected to focus on data security and AI applications in agriculture, healthcare, education and industry. Experts believe a dedicated institute could support research, skilled manpower, responsible technology use and Bangladesh’s preparation for the Fourth Industrial Revolution.",

                        'seo_keywords'     => "AI institute, data security, science policy",
                        'published_at'     => "2026-04-04 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Science",

                        'name'             => "Science and technology fair begins in Gaibandha",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A weeklong science and technology fair in Gaibandha is encouraging students and citizens to explore practical innovation.",

                        "body"             => "A weeklong National Science and Technology Fair began at Sadar Upazila Parishad in Gaibandha, drawing students, teachers and visitors. Organisers said the fair aims to popularise scientific thinking, show practical applications of technology and encourage innovation-minded citizens. The event’s theme highlights science and technology as key tools for building an innovation-based Bangladesh.",

                        'seo_keywords'     => "science fair, Gaibandha, student innovation",
                        'published_at'     => "2026-04-09 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Science",

                        'name'             => "Bangladesh Academy of Sciences hosts young researchers",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Academy of Sciences gathered young researchers to strengthen science, technology and innovation capacity.",

                        "body"             => "Bangladesh Academy of Sciences organised the 6th Young Scientist Congress at the National Science and Technology Complex in Dhaka. The event included technical sessions, research presentations, panel discussions and technology exhibitions. Organisers said young researchers need stronger access to frontline science, technology and innovation tools so they can solve national problems and prepare for future challenges.",

                        'seo_keywords'     => "Bangladesh Academy of Sciences, young researchers, STI",
                        'published_at'     => "2026-04-05 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Science",

                        'name'             => "Officials call science vital for development and security",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Science and technology leaders said research, STEM education and innovation are essential for national progress and security.",

                        "body"             => "Science and technology leaders said Bangladesh must expand STEM education, commercialise research, increase investment in advanced technologies and strengthen the startup ecosystem. The remarks came during a national science gathering in Dhaka. Speakers said young scientists should use research, innovation and creativity to support national development, economic growth, security and sustainable progress.",

                        'seo_keywords'     => "STEM education, science technology, national development",
                        'published_at'     => "2026-04-05 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Space",

                        'name'             => "Lalmonirhat aerospace university aims to produce astronauts",
                        'sub_heading'      => "Aviation campus highlights space ambition",
                        'content_shoulder' => "",
                        'brief'            => "Lalmonirhat’s aviation and aerospace university has been described as a future hub for astronauts and aviation experts.",

                        "body"             => "Officials said Lalmonirhat’s aviation and aerospace university could help Bangladesh produce future astronauts, aviation specialists and space-focused researchers. The institution is expected to support education in aerospace engineering, aviation management and advanced technology. The statement reflects Bangladesh’s growing interest in space education, satellite systems and skilled manpower for future high-tech industries.",

                        'seo_keywords'     => "aerospace university, Lalmonirhat, Bangladesh space",
                        'published_at'     => "2026-05-02 19:09:00",

                    ],

                    (object) [

                        "category_name"    => "Space",

                        'name'             => "Bangladesh astronomy enthusiasts prepare for national round",
                        'sub_heading'      => "",
                        'content_shoulder' => "Young stargazers",
                        'brief'            => "The national round of BDOAA 2026 gathered bright young astronomy enthusiasts from across Bangladesh at IUB.",

                        "body"             => "The national round of BDOAA 2026 was scheduled at Independent University, Bangladesh, bringing together young astronomy enthusiasts from across the country. The event helps students develop interest in astronomy, astrophysics, observation and problem-solving. Such competitions are important for building early scientific curiosity and preparing Bangladeshi students for international astronomy platforms.",

                        'seo_keywords'     => "BDOAA 2026, astronomy olympiad, Bangladeshi students",
                        'published_at'     => "2026-04-16 15:45:00",

                    ],

                    (object) [

                        "category_name"    => "Space",

                        'name'             => "Eta Aquarid meteor shower draws attention from skywatchers",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Skywatchers in Bangladesh can follow the Eta Aquarid meteor shower, though bright moonlight may reduce visibility.",

                        "body"             => "The Eta Aquarid meteor shower, created by debris from Halley’s Comet, is expected to peak in early May. Bangladeshi skywatchers may look toward the eastern sky before dawn, although bright moonlight can reduce the number of visible meteors. The event offers a useful opportunity for astronomy clubs, students and science enthusiasts to discuss comets, meteor trails and night-sky observation.",

                        'seo_keywords'     => "Eta Aquarid, meteor shower, Bangladesh sky",
                        'published_at'     => "2026-05-03 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Space",

                        'name'             => "Bangladeshi students’ space settlement success inspires schools",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladeshi students’ international space settlement design win continues to inspire school-level science learning.",

                        "body"             => "Bangladeshi students from Glenrich International School, Uttara won first place in the NSS Gerard K. O’Neill Space Settlement Contest with their project on future space living. The achievement continues to inspire science clubs and schools because it shows how young learners can think about engineering, life support, habitat design and human survival beyond Earth.",

                        'seo_keywords'     => "space settlement, student science, Bangladesh astronomy",
                        'published_at'     => "2025-05-17 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Space",

                        'name'             => "Bangladesh’s Artemis Accords role keeps space cooperation in focus",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s signing of the Artemis Accords remains a major step in peaceful international space cooperation.",

                        "body"             => "Bangladesh’s entry into the Artemis Accords placed the country within an international framework for peaceful civil space exploration. The agreement focuses on cooperation, transparency and responsible activity in outer space. Although Bangladesh’s space sector is still developing, the accord remains important for future satellite policy, space education, research collaboration and global scientific engagement.",

                        'seo_keywords'     => "Artemis Accords, NASA, Bangladesh space cooperation",
                        'published_at'     => "2025-04-08 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Research",

                        'name'             => "BAU scientist develops chemical-free paddy protection device",
                        'sub_heading'      => "Grain Guard targets storage losses",
                        'content_shoulder' => "",
                        'brief'            => "A Bangladesh Agricultural University scientist has introduced an ultrasonic device to protect stored paddy without chemicals.",

                        "body"             => "Bangladesh Agricultural University scientist Professor Dr Md Abdul Awal introduced a smart ultrasonic device called Grain Guard to protect stored paddy without harmful chemicals. The device is designed to help farmers reduce storage loss and protect grain quality. The research could be useful for agriculture, food security and safer post-harvest management in rural Bangladesh.",

                        'seo_keywords'     => "BAU research, Grain Guard, paddy storage",
                        'published_at'     => "2026-04-19 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Research",

                        'name'             => "BLRI researches climate-resilient fodder crops",
                        'sub_heading'      => "",
                        'content_shoulder' => "Livestock science",
                        'brief'            => "Bangladesh Livestock Research Institute is studying fodder crops that can withstand drought, salinity and changing weather.",

                        "body"             => "Bangladesh Livestock Research Institute has launched research into climate-resilient fodder crops that can survive drought, salinity and changing weather conditions. The work also includes improved grass preservation methods. Researchers say safer and more resilient animal feed can support livestock nutrition, protect farmers and strengthen food safety through better-quality meat, milk and eggs.",

                        'seo_keywords'     => "BLRI, fodder research, climate resilient crops",
                        'published_at'     => "2026-05-03 13:32:00",

                    ],

                    (object) [

                        "category_name"    => "Research",

                        'name'             => "IRRI partnership pushes next-generation rice research",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s agriculture ministry signalled stronger cooperation with IRRI for next-generation rice research and food security.",

                        "body"             => "Bangladesh’s agriculture leadership has signalled a stronger push for next-generation rice research through deeper partnership with the International Rice Research Institute. The focus includes improved rice varieties, food security, climate resilience and agricultural productivity. For Bangladesh, rice research remains central because changing weather, salinity and population pressure continue to affect farmers and consumers.",

                        'seo_keywords'     => "IRRI, rice research, food security",
                        'published_at'     => "2026-04-27 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Research",

                        'name'             => "Dhaka University urged to become research-oriented",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A government adviser called for transforming Dhaka University into a stronger research-oriented institution.",

                        "body"             => "A senior government adviser called for transforming Dhaka University into a more research-oriented institution during the DU Chemistry Alumni Association’s annual event. He stressed skills development, innovation and stronger academia-industry collaboration. The remarks underline a wider concern that Bangladesh needs stronger university research capacity to support science, industry and national competitiveness.",

                        'seo_keywords'     => "Dhaka University, research institution, academia industry",
                        'published_at'     => "2026-04-18 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Research",

                        'name'             => "Pharma sector urged to strengthen innovation capacity",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s pharmaceutical sector may use extra transition time to strengthen research, innovation and global readiness.",

                        "body"             => "Bangladesh’s pharmaceutical sector is expected to gain an important window if the proposed three-year deferment of LDC graduation is approved. Industry observers said the time could help companies improve global competitiveness, strengthen innovation capacity and prepare for tougher patent and regulatory conditions. Research and development will be important if the sector wants to move beyond generic production.",

                        'seo_keywords'     => "pharma research, innovation capacity, Bangladesh medicine",
                        'published_at'     => "2026-04-26 08:19:00",

                    ],

                    (object) [

                        "category_name"    => "Innovation",

                        'name'             => "Government promises proper recognition for innovators",
                        'sub_heading'      => "IP services to be modernised",
                        'content_shoulder' => "",
                        'brief'            => "The government said it is working to recognise innovators, researchers and creative people through easier service systems.",

                        "body"             => "The government said it is working to ensure proper recognition of innovators, researchers, entrepreneurs and creative individuals by building a modern and effective service system. Measures include online registration, faster services, stronger legal protection and action against counterfeit products. Officials said protecting innovation and intellectual property is important for national development and youth creativity.",

                        'seo_keywords'     => "innovators, intellectual property, creative economy",
                        'published_at'     => "2026-04-25 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Innovation",

                        'name'             => "Civic tech expo highlights youth-led digital solutions",
                        'sub_heading'      => "",
                        'content_shoulder' => "Digital inclusion",
                        'brief'            => "UNDP and the ICT Division hosted a civic technology expo highlighting youth-led solutions for digital governance.",

                        "body"             => "UNDP and the ICT Division hosted a civic technology innovation expo in Dhaka, highlighting Bangladesh’s first youth-led civic technology ecosystem. The event brought together government officials, development partners and young innovators. Organisers said civic tech can support digital governance, inclusion and public service delivery while giving young developers a platform to solve social problems.",

                        'seo_keywords'     => "civic tech, youth innovation, digital governance",
                        'published_at'     => "2026-04-13 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Innovation",

                        'name'             => "Student innovation signals future potential in Hathazari",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Student projects and local awards in Hathazari highlighted how young innovators can support future development.",

                        "body"             => "A local programme in Hathazari highlighted student innovation as a sign of Bangladesh’s future potential. Officials said technology-based initiatives and an entrepreneurial mindset are essential for sustainable development. Awarding students and encouraging practical problem-solving can help young learners connect classroom knowledge with real-life needs in energy, agriculture, environment and community services.",

                        'seo_keywords'     => "student innovation, Hathazari, youth technology",
                        'published_at'     => "2026-04-11 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Innovation",

                        'name'             => "Solar power proposed as escape from energy trap",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Experts said solar energy could help Bangladesh address fuel pressure and move toward a more resilient power system.",

                        "body"             => "Energy experts said solar power could offer Bangladesh a transformative way out of its long-running fuel crisis. They warned that the country risks falling deeper into a structural energy trap without systemic reform and renewable investment. Innovation in solar financing, grid integration, rooftop systems and local technology could help reduce import dependence and strengthen energy security.",

                        'seo_keywords'     => "solar power, renewable innovation, energy security",
                        'published_at'     => "2026-05-02 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Innovation",

                        'name'             => "Bangladesh losing innovation race sparks fresh debate",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A policy debate warned that low research spending is holding back Bangladesh’s innovation potential against regional competitors.",

                        "body"             => "A recent policy discussion warned that Bangladesh is falling behind in innovation because research and development investment remains very low compared with regional competitors. The debate pointed to the need for stronger research institutions, university-industry collaboration, startup support and long-term funding. Without institutional support, individual talent may struggle to become scalable national innovation.",

                        'seo_keywords'     => "innovation race, R&D spending, Bangladesh startups",
                        'published_at'     => "2026-04-23 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Tech Research",

                        'name'             => "AI Olympiad regional round draws young tech enthusiasts",
                        'sub_heading'      => "Dhaka event shows AI education momentum",
                        'content_shoulder' => "",
                        'brief'            => "The Dhaka regional round of Bangladesh AI Olympiad 2026 gathered young technology learners at BUBT.",

                        "body"             => "The Dhaka regional round of the 3rd Bangladesh AI Olympiad 2026 brought hundreds of young technology enthusiasts to Bangladesh University of Business and Technology. The event showed growing national interest in artificial intelligence education, coding, data science and problem-solving. Organisers said competitions like this can prepare students for future research, innovation and global technology careers.",

                        'seo_keywords'     => "AI Olympiad, tech research, young learners",
                        'published_at'     => "2026-05-02 18:30:00",

                    ],

                    (object) [

                        "category_name"    => "Tech Research",

                        'name'             => "Kaggle workshop trains future AI leaders",
                        'sub_heading'      => "",
                        'content_shoulder' => "Data skills",
                        'brief'            => "A Bangladesh AI Olympiad-linked Kaggle workshop focused on building practical machine learning and data science skills.",

                        "body"             => "A Kaggle workshop linked with Bangladesh AI Olympiad 2026 trained young learners under the theme of shaping tomorrow’s AI leaders. The session focused on practical data science, machine learning workflows and competition-based learning. Such workshops can help students move from theory to applied research by using datasets, models and problem-solving methods familiar to global AI communities.",

                        'seo_keywords'     => "Kaggle workshop, machine learning, AI education",
                        'published_at'     => "2026-04-28 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Tech Research",

                        'name'             => "UGC prepares ethical AI policy for higher education",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The University Grants Commission is preparing guidance for ethical AI use in teaching, research and assessment.",

                        "body"             => "The University Grants Commission is preparing a policy for ethical use of artificial intelligence in higher education institutions. The policy is expected to address responsible use, plagiarism risks, academic honesty, data privacy and assessment fairness. As AI tools become common in classrooms and research, universities need clear rules that support innovation without weakening academic integrity.",

                        'seo_keywords'     => "UGC AI policy, ethical AI, higher education",
                        'published_at'     => "2026-04-23 23:02:00",

                    ],

                    (object) [

                        "category_name"    => "Tech Research",

                        'name'             => "Bangladesh urges stronger data systems at UN forum",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh called for bridging the digital divide and strengthening data systems through international cooperation.",

                        "body"             => "Bangladesh urged stronger international cooperation, technology transfer and capacity building to bridge the digital divide and improve data systems at a United Nations forum. The statement connects technology research with population planning, development and public policy. Stronger data infrastructure can help Bangladesh improve decision-making in health, education, climate adaptation and social protection.",

                        'seo_keywords'     => "data systems, digital divide, technology transfer",
                        'published_at'     => "2026-04-16 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Tech Research",

                        'name'             => "Renewable transition research warns energy crisis may persist",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "CPD warned Bangladesh’s energy crisis cannot be solved fully without a decisive shift to renewable power systems.",

                        "body"             => "The Centre for Policy Dialogue warned that Bangladesh’s energy crisis may ease temporarily but cannot be fully resolved without a decisive shift toward renewable energy. The analysis called for stronger Bangladesh-China energy collaboration and long-term planning. Research into solar, grid storage, transmission and financing models will be important for building a cleaner and more reliable energy system.",

                        'seo_keywords'     => "renewable energy, CPD, energy research",
                        'published_at'     => "2026-04-27 13:34:00",

                    ],

                    (object) [

                        "category_name"    => "Discoveries",

                        'name'             => "Grain Guard discovery offers safer paddy storage option",
                        'sub_heading'      => "Ultrasonic device avoids harmful chemicals",
                        'content_shoulder' => "",
                        'brief'            => "A BAU researcher’s ultrasonic Grain Guard device offers farmers a chemical-free way to protect stored paddy.",

                        "body"             => "The Grain Guard device developed by a Bangladesh Agricultural University scientist offers a practical discovery for post-harvest crop protection. By using ultrasonic technology instead of harmful chemicals, it aims to protect stored paddy from damage while improving food safety. If scaled properly, the device could reduce losses for farmers and make grain storage safer across rural Bangladesh.",

                        'seo_keywords'     => "Grain Guard, agricultural discovery, paddy protection",
                        'published_at'     => "2026-04-19 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Discoveries",

                        'name'             => "Climate-resilient fodder research points to safer livestock future",
                        'sub_heading'      => "",
                        'content_shoulder' => "Farm resilience",
                        'brief'            => "BLRI’s work on drought and salinity-tolerant fodder may improve livestock nutrition under changing climate conditions.",

                        "body"             => "Bangladesh Livestock Research Institute’s work on climate-resilient fodder crops is an important applied discovery for livestock farming. Researchers are focusing on varieties that can withstand drought, salinity and changing weather patterns. Better fodder can improve animal nutrition, reduce production risk and support safer food chains for meat, milk and eggs consumed by Bangladeshi families.",

                        'seo_keywords'     => "fodder discovery, livestock research, climate adaptation",
                        'published_at'     => "2026-05-03 13:32:00",

                    ],

                    (object) [

                        "category_name"    => "Discoveries",

                        'name'             => "Next-generation rice work targets climate pressure",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s renewed rice research push may help farmers respond to climate stress, salinity and food security pressure.",

                        "body"             => "Bangladesh’s renewed cooperation with IRRI for next-generation rice research could lead to important discoveries for farmers facing climate stress. Improved varieties may help address salinity, heat, floods, pests and yield pressure. Because rice remains central to Bangladesh’s food system, scientific advances in seed development and crop management can directly affect national food security.",

                        'seo_keywords'     => "rice discovery, IRRI, climate smart agriculture",
                        'published_at'     => "2026-04-27 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Discoveries",

                        'name'             => "Astronomy olympiad reveals strong student interest in space science",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s astronomy competition scene is revealing growing student interest in space science and observation.",

                        "body"             => "The BDOAA 2026 national round showed that astronomy is gaining attention among Bangladeshi students. Competitions focused on astronomy and astrophysics help young learners understand observation, celestial mechanics, data reasoning and scientific curiosity. These early discoveries of talent are important because Bangladesh needs more students interested in space science, physics and advanced research careers.",

                        'seo_keywords'     => "astronomy talent, student discovery, space science",
                        'published_at'     => "2026-04-16 15:45:00",

                    ],

                    (object) [

                        "category_name"    => "Discoveries",

                        'name'             => "Science fairs uncover practical student innovations",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Local science fairs are helping uncover student-made projects that connect classroom ideas with community needs.",

                        "body"             => "Science and technology fairs across Bangladesh are helping students present practical projects and small innovations connected to daily life. The Gaibandha fair highlighted how local exhibitions can uncover young talent, encourage problem-solving and make science more accessible to communities. Such events often reveal early ideas in agriculture, environment, energy, robotics and public service technology.",

                        'seo_keywords'     => "science fair, student discoveries, practical innovation",
                        'published_at'     => "2026-04-09 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Environment",

                        'name'             => "April rainfall rises far above normal across Bangladesh",
                        'sub_heading'      => "BMD warns of heatwaves and low pressure",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh saw April rainfall 75.7 percent above normal, with Sylhet recording extreme rain and BMD warning of heatwaves in May.",

                        "body"             => "Bangladesh Meteorological Department data showed April rainfall was 75.7 percent higher than normal across the country. Sylhet recorded 603mm of rain, more than double its usual April level, while Dhaka, Barishal, Rangpur and Khulna also received unusually high rainfall. BMD said rainfall may return closer to normal in May, but warned that low-pressure systems could form in the Bay of Bengal and heatwaves may affect several parts of the country.",

                        'seo_keywords'     => "Bangladesh rainfall, BMD forecast, environment",
                        'published_at'     => "2026-05-03 16:20:00",

                    ],

                    (object) [

                        "category_name"    => "Environment",

                        'name'             => "Hill cutting and forest land sales continue in Cox’s Bazar",
                        'sub_heading'      => "",
                        'content_shoulder' => "Forest crime watch",
                        'brief'            => "Protected forest land in Cox’s Bazar’s Khurushkul is being cut, occupied and sold, raising fresh environmental concern.",

                        "body"             => "Large areas of protected forest land in Khurushkul union of Cox’s Bazar Sadar upazila are reportedly being cut, occupied and sold in small plots. Forest officials said about 30 percent of the union is designated protected forest, but local sources alleged that hills have been flattened, trees removed and settlements built on cleared land. The situation shows how weak enforcement, land pressure and organised occupation can rapidly damage forest cover near a growing coastal town.",

                        'seo_keywords'     => "hill cutting, Cox Bazar, forest land",
                        'published_at'     => "2026-05-01 19:43:00",

                    ],

                    (object) [

                        "category_name"    => "Environment",

                        'name'             => "Barishal water plant returns to reduce groundwater pressure",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Rupatali surface water treatment plant resumed operation in Barishal to provide safer water and reduce groundwater extraction.",

                        "body"             => "The Rupatali 1 Surface Water Treatment Plant in Barishal resumed operations after remaining inactive for a long period. The plant can treat around 16 million litres of water daily and is expected to ease the city’s chronic water shortage. Officials said the reopening will reduce dependence on groundwater, improve access to safe drinking water and support a more sustainable urban water supply system for Barishal residents.",

                        'seo_keywords'     => "Barishal water plant, groundwater, safe water",
                        'published_at'     => "2026-04-29 22:31:00",

                    ],

                    (object) [

                        "category_name"    => "Environment",

                        'name'             => "Police move to form special unit for environmental crimes",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh Police is discussing a dedicated environmental crime unit to tackle river grabbing, pollution and hill cutting.",

                        "body"             => "Bangladesh Police has moved to discuss the formation of a dedicated unit to address environmental crimes across the country. The proposed unit would focus on river encroachment, industrial pollution, deforestation, illegal hill cutting and extraction of natural resources. Environmental campaigners have long argued that ordinary enforcement is not enough because many environmental crimes involve organised networks, local influence and repeated violations.",

                        'seo_keywords'     => "environmental crime, Bangladesh Police, pollution control",
                        'published_at'     => "2026-04-20 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Environment",

                        'name'             => "Clean and green Dhaka plan puts pollution control in focus",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A 12-point plan for Dhaka includes tree planting, electric buses, ETP monitoring and action against illegal brick kilns.",

                        "body"             => "The government has outlined a 12-point plan to make Dhaka cleaner and greener. The plan includes tree plantation on medians, open spaces and canal banks, introduction of 250 electric buses, action against smoke-emitting vehicles, drives against illegal brick kilns and stronger monitoring of effluent treatment plants. Officials also said pollution sources in rivers and canals around Dhaka have been identified, making enforcement a major test.",

                        'seo_keywords'     => "green Dhaka, pollution control, electric buses",
                        'published_at'     => "2026-04-29 14:56:00",

                    ],

                    (object) [

                        "category_name"    => "Climate Change",

                        'name'             => "Bangladesh faces new climate reality as heatwaves intensify",
                        'sub_heading'      => "Longer hotter spells worry experts",
                        'content_shoulder' => "",
                        'brief'            => "Meteorologists warn that Bangladesh heatwaves are lasting longer, covering wider areas and reaching higher temperatures.",

                        "body"             => "Bangladesh is facing a changing climate pattern as heatwaves become longer, wider and more intense. Meteorologists noted that April 2024 recorded the country’s longest heatwave, while recent patterns suggest that seasonal relief from nor’wester rains is becoming less predictable. Experts say the trend creates serious risks for outdoor workers, children, elderly people, crop production, electricity demand and public health planning in cities and rural areas.",

                        'seo_keywords'     => "heatwave, climate change, Bangladesh weather",
                        'published_at'     => "2026-04-24 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Climate Change",

                        'name'             => "Rising heat may sharply increase deaths by 2050",
                        'sub_heading'      => "",
                        'content_shoulder' => "Heat mortality risk",
                        'brief'            => "A global climate mortality report projects Bangladesh among countries most affected by future heat-related deaths.",

                        "body"             => "A Climate Impact Lab report projects that Bangladesh could see 24 additional deaths per 100,000 people each year by 2050 because of rising temperatures. Khulna may face an even higher projected burden, while Dhaka and Chattogram are also expected to experience serious heat-related health risks. The findings show why cooling centres, urban planning, heat alerts, workplace protection and public health investment are becoming urgent adaptation needs.",

                        'seo_keywords'     => "heat deaths, climate mortality, climate adaptation",
                        'published_at'     => "2026-03-25 13:02:00",

                    ],

                    (object) [

                        "category_name"    => "Climate Change",

                        'name'             => "BMD warns low pressure systems may form in Bay",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "After unusually heavy April rainfall, BMD warned that one or two low-pressure systems may form in the Bay of Bengal.",

                        "body"             => "BMD’s May outlook warned that one to two low-pressure systems could form in the Bay of Bengal, with at least one possibly strengthening into a depression or cyclone. The warning came after April rainfall was far above normal in many divisions. Climate observers say such weather volatility shows the importance of early warning systems, cyclone shelter readiness, crop protection and local disaster preparedness in vulnerable coastal and haor regions.",

                        'seo_keywords'     => "Bay of Bengal, low pressure, cyclone risk",
                        'published_at'     => "2026-05-03 16:20:00",

                    ],

                    (object) [

                        "category_name"    => "Climate Change",

                        'name'             => "Children remain highly exposed to climate shocks",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Children in Bangladesh remain highly exposed to floods, heat, storms and climate-linked health and education disruption.",

                        "body"             => "Climate change continues to place Bangladeshi children at high risk because floods, cyclones, heatwaves and disease outbreaks can disrupt schooling, nutrition, safety and healthcare access. Bangladesh ranks high in children’s climate vulnerability indexes, and experts warn that poverty, displacement and weak infrastructure can increase long-term harm. Stronger child-focused adaptation planning is needed in schools, health centres and disaster shelters.",

                        'seo_keywords'     => "children climate risk, Bangladesh climate, climate vulnerability",
                        'published_at'     => "2026-04-16 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Climate Change",

                        'name'             => "Heatwave forecasts keep public health services on alert",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "BMD forecasts in April warned of possible heatwaves and monitored Bay systems as temperatures rose across Bangladesh.",

                        "body"             => "Weather forecasts in April warned that temperatures could rise again, with possible heatwaves affecting parts of Bangladesh. BMD also monitored low-pressure activity over the Bay of Bengal while scattered rain brought temporary relief in some areas. Public health experts say recurring heat alerts require drinking water access, workplace rest periods, school precautions and faster communication with vulnerable groups before dangerous temperatures peak.",

                        'seo_keywords'     => "heat alert, BMD, climate health",
                        'published_at'     => "2026-04-20 15:21:00",

                    ],

                    (object) [

                        "category_name"    => "Pollution",

                        'name'             => "Noise Awareness Day puts invisible pollution in spotlight",
                        'sub_heading'      => "Mobile courts and rallies held nationwide",
                        'content_shoulder' => "",
                        'brief'            => "International Noise Awareness Day was observed across Bangladesh with rallies, discussions and mobile court drives.",

                        "body"             => "International Noise Awareness Day 2026 was observed across Bangladesh through rallies, discussions and mobile court drives. The Department of Environment said noise pollution is often ignored because it is invisible, even though it creates serious health risks. Officials urged public awareness, social media campaigns and stronger traffic enforcement. Dhaka traffic police have already filed thousands of cases under noise pollution rules, showing enforcement is becoming more active.",

                        'seo_keywords'     => "noise pollution, Department of Environment, Dhaka traffic",
                        'published_at'     => "2026-04-30 00:04:00",

                    ],

                    (object) [

                        "category_name"    => "Pollution",

                        'name'             => "Dhaka air again ranks among world’s worst",
                        'sub_heading'      => "",
                        'content_shoulder' => "Urban air risk",
                        'brief'            => "Dhaka’s air quality again ranked among the world’s worst, keeping particulate pollution a major urban health concern.",

                        "body"             => "Dhaka again ranked among cities with the worst air quality, with pollution levels repeatedly reaching unhealthy ranges in recent weeks. Air pollution in Bangladesh is mainly measured through particulate matter, nitrogen dioxide, carbon monoxide, sulphur dioxide and ozone. Experts say construction dust, vehicle emissions, brick kilns, waste burning and industrial pollution remain key sources, while rain can provide only temporary relief before dry conditions return.",

                        'seo_keywords'     => "Dhaka air, AQI, air pollution",
                        'published_at'     => "2026-04-30 09:00:00",

                    ],

                    (object) [

                        "category_name"    => "Pollution",

                        'name'             => "Industrial waste threatens rivers in Narsingdi",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Unchecked industrial pollution is endangering rivers in Narsingdi and affecting nearby communities and ecosystems.",

                        "body"             => "Rivers flowing through Narsingdi are under severe pressure from unchecked industrial pollution. Local communities living beside the rivers are exposed to polluted water, damaged ecosystems and declining aquatic life. Environmental observers say factory discharge, weak monitoring and poor waste treatment continue to threaten river health. Without stronger enforcement and working effluent treatment systems, pollution may keep spreading through connected waterways.",

                        'seo_keywords'     => "Narsingdi rivers, industrial waste, water pollution",
                        'published_at'     => "2026-04-05 02:40:00",

                    ],

                    (object) [

                        "category_name"    => "Pollution",

                        'name'             => "ETP monitoring planned for industries near Dhaka rivers",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Authorities say ETPs are mandatory for polluting industries and real-time monitoring cameras are being installed.",

                        "body"             => "The government said effluent treatment plants have been made mandatory for industries discharging liquid waste into rivers, canals and water bodies around Dhaka. Officials also said 248 establishments have installed ETPs, while cameras for real-time monitoring are being introduced. The step is important because the Buriganga, Shitalakshya, Balu and Turag rivers have long suffered from untreated industrial and urban waste.",

                        'seo_keywords'     => "ETP monitoring, river pollution, Dhaka rivers",
                        'published_at'     => "2026-04-29 14:56:00",

                    ],

                    (object) [

                        "category_name"    => "Pollution",

                        'name'             => "Mobile courts target black smoke and brick kiln pollution",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Regular mobile court drives are targeting black smoke vehicles, construction dust and illegal brick kilns around Dhaka.",

                        "body"             => "Authorities have increased mobile court drives against vehicles emitting black smoke, construction-related pollution and illegal brick kilns around Dhaka. Savar has been declared a degraded airshed, with restrictions on brick kilns and open waste burning. Environmental officials say air pollution control will require continuous enforcement, better construction management, clean public transport and real accountability for repeat violators.",

                        'seo_keywords'     => "black smoke, brick kiln, Dhaka pollution",
                        'published_at'     => "2026-04-29 14:56:00",

                    ],

                    (object) [

                        "category_name"    => "Wildlife",

                        'name'             => "Dead elephant found mutilated in Rangamati forest",
                        'sub_heading'      => "Experts allege poor carcass protection",
                        'content_shoulder' => "",
                        'brief'            => "A 60-year-old wild elephant found dead in Rangamati was later discovered mutilated, raising conservation concerns.",

                        "body"             => "A dead Asian wild elephant in Rangamati’s Langadu upazila was found mutilated after flesh was reportedly removed from its legs and trunk. Forest officials said the elephant had been sick and injured, while wildlife experts criticised the lack of carcass protection. The incident has raised concern about negligence, possible illegal demand for animal parts and weak monitoring of endangered wildlife in remote forest areas.",

                        'seo_keywords'     => "Rangamati elephant, wildlife crime, elephant conservation",
                        'published_at'     => "2026-04-27 16:21:00",

                    ],

                    (object) [

                        "category_name"    => "Wildlife",

                        'name'             => "Rare freshwater crocodile caught safely in Padma",
                        'sub_heading'      => "",
                        'content_shoulder' => "Rare river rescue",
                        'brief'            => "A rare freshwater crocodile caught in a fishing trap in Faridpur’s Padma River was secured and handed to authorities.",

                        "body"             => "A rare freshwater crocodile was caught in a fisherman’s traditional spear-trap line in the Padma River at Charbhadrasan upazila of Faridpur. The fisherman did not harm the animal and brought it ashore so it could be handed over to authorities. The incident highlights both the presence of rare river wildlife and the need for safe rescue systems when protected species become trapped in fishing gear.",

                        'seo_keywords'     => "freshwater crocodile, Padma River, wildlife rescue",
                        'published_at'     => "2026-04-27 15:19:00",

                    ],

                    (object) [

                        "category_name"    => "Wildlife",

                        'name'             => "Old wild elephant dies after injuries in Rangamati",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A 60-year-old male elephant died in Rangamati after long-standing injuries, with another elephant seen guarding the carcass.",

                        "body"             => "A 60-year-old male elephant died in Bhashannadam area of Rangamati’s Langadu upazila after suffering from long-standing injuries. Forest officials said the elephant had been weakened and had received treatment several times. Members of the Elephant Response Team reported that a female elephant stayed beside the carcass and reacted aggressively when people came near, showing the social behaviour and stress involved in elephant deaths.",

                        'seo_keywords'     => "Asian elephant, Rangamati, elephant response team",
                        'published_at'     => "2026-04-26 20:24:00",

                    ],

                    (object) [

                        "category_name"    => "Wildlife",

                        'name'             => "Endangered Bengal Slow Loris rescued in Habiganj",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Forest officials rescued an endangered Bengal Slow Loris from a house in Habiganj and planned release into Satchari forest.",

                        "body"             => "Forest officials rescued an endangered Bengal Slow Loris from a house in Chunarughat upazila of Habiganj after locals restrained the animal. Officials said the adult male loris had wandered into a social forestry area from nearby forest land and was healthy enough for release into Satchari Reserve Forest. Conservationists warned that habitat loss, tourist disturbance and illegal trafficking continue to threaten this nocturnal primate.",

                        'seo_keywords'     => "Bengal Slow Loris, Habiganj, Satchari forest",
                        'published_at'     => "2026-04-13 22:16:00",

                    ],

                    (object) [

                        "category_name"    => "Wildlife",

                        'name'             => "Sundarbans tigers face uncertain future despite conservation work",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Bangladesh’s Sundarbans tiger conservation faces pressure from habitat loss, salinity, storms, poaching and human conflict.",

                        "body"             => "Bangladesh’s Sundarbans tiger population remains under pressure despite ongoing conservation projects. Conservation officials and experts say habitat loss, poaching, rising salinity, stronger storms and human movement into tiger territory all threaten long-term recovery. The slow growth in tiger numbers has raised questions about whether current funding, monitoring and community-based protection are enough to safeguard the country’s most iconic wild animal.",

                        'seo_keywords'     => "Sundarbans tiger, tiger conservation, wildlife protection",
                        'published_at'     => "2026-04-28 00:00:00",

                    ],

                    (object) [

                        "category_name"    => "Disasters",

                        'name'             => "Lightning strikes kill ten people across five districts",
                        'sub_heading'      => "Storms also kill dozens of cattle",
                        'content_shoulder' => "",
                        'brief'            => "Lightning strikes killed ten people and more than 50 cattle in Patuakhali, Jamalpur, Mymensingh, Barguna and Shariatpur.",

                        "body"             => "Lightning strikes killed ten people and more than 50 cattle across five districts, including Patuakhali, Jamalpur, Mymensingh, Barguna and Shariatpur. Several victims were working in fields, fishing or tending cattle during storms. Farmers in Kalapara reported heavy livestock losses ahead of Eid-ul-Azha. The disaster highlights the need for lightning awareness, safe shelters in open fields and early warning messages for rural workers.",

                        'seo_keywords'     => "lightning deaths, natural disaster, Bangladesh storm",
                        'published_at'     => "2026-04-29 22:54:00",

                    ],

                    (object) [

                        "category_name"    => "Disasters",

                        'name'             => "Khowai embankment breach floods haor croplands",
                        'sub_heading'      => "",
                        'content_shoulder' => "Haor flood risk",
                        'brief'            => "A 50-metre breach in the Khowai River embankment flooded haor croplands in Habiganj after sudden water pressure.",

                        "body"             => "A section of the Khowai River embankment collapsed in Habiganj after a strong wave of flooding, sending water into Bhangabil and Rowarbil haors. Officials said around 50 metres of the embankment failed, affecting about 2,710 hectares of agricultural land. The incident came at a sensitive time for farmers and shows why haor embankment maintenance, monitoring and emergency repair capacity are crucial before flash floods peak.",

                        'seo_keywords'     => "Khowai embankment, haor flood, Habiganj",
                        'published_at'     => "2026-04-29 22:39:00",

                    ],

                    (object) [

                        "category_name"    => "Disasters",

                        'name'             => "Wall collapse during heavy rain kills woman in Chattogram",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "A woman died after a wall collapsed during heavy rain in Chattogram’s Karnaphuli, showing urban disaster risk.",

                        "body"             => "A woman was killed in Chattogram’s Karnaphuli area after a wall collapsed during heavy rainfall. The incident occurred as rain and storms affected different parts of the country, creating waterlogging, structural risks and travel disruption. Urban disaster specialists often warn that poorly built walls, blocked drains and weak slope management can turn heavy rain into a fatal hazard for low-income households and roadside communities.",

                        'seo_keywords'     => "Chattogram rain, wall collapse, urban disaster",
                        'published_at'     => "2026-04-29 19:02:00",

                    ],

                    (object) [

                        "category_name"    => "Disasters",

                        'name'             => "Rain and landslides strand hundreds in Moulvibazar",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Heavy rain, storms and landslides battered Moulvibazar, stranding residents and raising fresh flood fears.",

                        "body"             => "Heavy rain, storms and landslides battered parts of Moulvibazar, stranding hundreds of residents and raising concerns about possible flooding. Such events are particularly dangerous in hilly and low-lying areas, where blocked roads, river swelling and soil movement can quickly cut off communities. Disaster managers need fast local communication, shelter readiness and road clearance capacity during repeated rainfall spells.",

                        'seo_keywords'     => "Moulvibazar landslide, heavy rain, flood fear",
                        'published_at'     => "2026-04-29 12:16:00",

                    ],

                    (object) [

                        "category_name"    => "Disasters",

                        'name'             => "BMD forecasts heavy rain and thunderstorm risk nationwide",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "BMD forecast rain, thunderstorms, lightning and gusty winds across Bangladesh, with heavy rain likely in three divisions.",

                        "body"             => "Bangladesh Meteorological Department forecast light to moderate rain or thundershowers with temporary gusty or squally winds and lightning in different parts of the country. Many places in Mymensingh, Dhaka, Barishal, Chattogram and Sylhet divisions were expected to see rain, while heavy to very heavy rainfall was likely in parts of Mymensingh, Chattogram and Sylhet. The alert matters because repeated storms can trigger flooding, landslides and lightning deaths.",

                        'seo_keywords'     => "BMD rain forecast, thunderstorm, disaster alert",
                        'published_at'     => "2026-05-02 12:08:00",

                    ],

                    (object) [

                        "category_name"    => "Sustainability",

                        'name'             => "SMEs could cut over 14 million tons of carbon yearly",
                        'sub_heading'      => "Study highlights solar and factory efficiency",
                        'content_shoulder' => "",
                        'brief'            => "A study says Bangladesh SMEs could reduce 14.09 million tons of carbon emissions annually through targeted energy transition.",

                        "body"             => "A Change Initiative study found that strategic interventions in Bangladesh’s SME sector could reduce more than 14.09 million tons of carbon emissions every year. The study focused on leather, plastic manufacturing, packaging and light engineering clusters. Researchers said rooftop solar, renewable systems in industrial parks, energy audits and affordable financing could reduce costs, support export competitiveness and help Bangladesh meet climate commitments.",

                        'seo_keywords'     => "SME carbon, renewable energy, sustainability",
                        'published_at'     => "2026-03-28 18:42:00",

                    ],

                    (object) [

                        "category_name"    => "Sustainability",

                        'name'             => "Solar irrigation seen as path out of energy pressure",
                        'sub_heading'      => "",
                        'content_shoulder' => "Clean farm power",
                        'brief'            => "Energy experts say converting diesel irrigation pumps to solar could reduce fuel use, imports and farmer costs.",

                        "body"             => "Energy experts have argued that Bangladesh can reduce pressure from the energy crisis by converting diesel-powered irrigation pumps to solar systems. Solar irrigation can lower diesel imports, reduce operating costs for farmers and support cleaner agricultural production. The idea is especially important because irrigation demand is large, fuel prices are volatile and rural farmers need reliable power during crop seasons.",

                        'seo_keywords'     => "solar irrigation, clean energy, farmer sustainability",
                        'published_at'     => "2026-05-03 08:00:00",

                    ],

                    (object) [

                        "category_name"    => "Sustainability",

                        'name'             => "Rupatali water plant supports sustainable urban supply",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Barishal’s reopened surface water treatment plant may reduce groundwater extraction and improve sustainable water access.",

                        "body"             => "The reopening of Barishal’s Rupatali 1 Surface Water Treatment Plant is an important sustainability step because it shifts part of the city’s supply away from groundwater extraction. The facility can treat around 16 million litres of water daily and uses multiple stages of filtration and disinfection. Officials said upgrades, pipeline repair and a new maintenance framework should help provide safer water while reducing pressure on underground reserves.",

                        'seo_keywords'     => "surface water, sustainable water, Barishal",
                        'published_at'     => "2026-04-29 22:31:00",

                    ],

                    (object) [

                        "category_name"    => "Sustainability",

                        'name'             => "Dhaka green plan includes trees electric buses and ETPs",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "The clean Dhaka plan combines tree planting, electric buses, zero-soil spaces and industrial waste treatment monitoring.",

                        "body"             => "Dhaka’s clean and green city plan combines several sustainability measures, including tree plantation, Miyawaki forests, zero-soil green spaces, electric buses and monitoring of industrial effluent treatment plants. Officials said 41,565 saplings have already been planted under joint agency work, while DNCC has a five-year target to plant 5 lakh trees. The plan links air pollution control with urban greening and transport reform.",

                        'seo_keywords'     => "green city, electric buses, urban sustainability",
                        'published_at'     => "2026-04-29 14:56:00",

                    ],

                    (object) [

                        "category_name"    => "Sustainability",

                        'name'             => "RMG green transition debate urges buyer responsibility",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "Experts say global buyers must share costs as Bangladesh’s garment sector faces pressure to become greener.",

                        "body"             => "Bangladesh’s garment sector is facing growing pressure to reduce emissions, improve energy efficiency and comply with global sustainability standards. Industry voices have argued that global buyers must share the cost of the green transition instead of pushing all expenses onto local manufacturers. Without financing support, small and medium suppliers may struggle to invest in cleaner technology while staying competitive in export markets.",

                        'seo_keywords'     => "RMG sustainability, green transition, global buyers",
                        'published_at'     => "2026-04-23 09:00:00",

                    ],

                ]),
            ],

            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                'newses'        => collect([
                    (object) [
                        "category_name"    => "জাতীয়",
                        'name'             => "ডিসি সম্মেলনে মাঠ প্রশাসনকে জনবান্ধব হওয়ার নির্দেশ",
                        'sub_heading'      => "প্রশাসনে স্বচ্ছতা ও জবাবদিহি বাড়ানোর ওপর জোর",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকায় ডিসি সম্মেলনের উদ্বোধনে মাঠ প্রশাসনকে জনসেবা সহজ, হয়রানিমুক্ত ও রাজনৈতিক পরিচয়ের ঊর্ধ্বে রাখার নির্দেশনা দিয়েছেন প্রধানমন্ত্রী।",
                        "body"             => "রাজধানীর ওসমানী স্মৃতি মিলনায়তনে চার দিনব্যাপী জেলা প্রশাসক সম্মেলনের উদ্বোধনে প্রধানমন্ত্রী মাঠ প্রশাসনের কর্মকর্তাদের জনস্বার্থকে অগ্রাধিকার দেওয়ার আহ্বান জানান। তিনি বলেন, সরকারি সেবা যেন রাজনৈতিক পরিচয়, ব্যক্তিগত প্রভাব বা আমলাতান্ত্রিক জটিলতার কারণে বাধাগ্রস্ত না হয়। জেলা প্রশাসকদের ন্যায্য অভিযোগ দ্রুত দেখা, প্রকল্প বাস্তবায়নে সময় মেনে চলা এবং স্থানীয় পর্যায়ে সুশাসন নিশ্চিত করার নির্দেশ দেওয়া হয়েছে।",
                        'seo_keywords'     => "ডিসি সম্মেলন, মাঠ প্রশাসন, জনসেবা, প্রধানমন্ত্রী",
                        'published_at'     => "2026-05-03 13:52:00",
                    ],

                    (object) [
                        "category_name"    => "জাতীয়",
                        'name'             => "ঋণের বোঝা নিয়েই কাজ শুরু করেছে সরকার",
                        'sub_heading'      => "",
                        'content_shoulder' => "অর্থনীতি ও প্রশাসন",
                        'brief'            => "সরকারি দায়িত্ব নেওয়ার পর বড় ঋণচাপ, দুর্বল অর্থনীতি ও প্রশাসনিক চ্যালেঞ্জের মধ্যেই কাজ শুরু করার কথা জানিয়েছেন প্রধানমন্ত্রী।",
                        "body"             => "ডিসি সম্মেলনের উদ্বোধনী অনুষ্ঠানে প্রধানমন্ত্রী জানান, বড় অঙ্কের ঋণের চাপ, আমদানিনির্ভরতা ও দুর্বল প্রতিষ্ঠানগত কাঠামোর মধ্যেই বর্তমান সরকার দায়িত্ব নিয়েছে। তিনি জেলা প্রশাসকদের সততা, দক্ষতা ও পেশাদারিত্বের সঙ্গে কাজ করার আহ্বান জানান। পদোন্নতি বা বদলির স্বার্থে পেশাদারিত্ব বিসর্জন দিলে তা রাষ্ট্রের জন্য ক্ষতিকর বলে তিনি সতর্ক করেন। প্রশাসনের নিরপেক্ষতা বজায় রাখাকে সরকারের অন্যতম নীতি হিসেবে তুলে ধরা হয়।",
                        'seo_keywords'     => "ঋণ, সরকার, প্রশাসন, অর্থনীতি",
                        'published_at'     => "2026-05-03 13:02:00",
                    ],

                    (object) [
                        "category_name"    => "জাতীয়",
                        'name'             => "এআই ও ডিজিটাল দক্ষতায় উন্নতির তাগিদ প্রধানমন্ত্রীর",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চতুর্থ শিল্পবিপ্লবের যুগে জনপ্রশাসনকে দক্ষ, স্বচ্ছ ও সময়োপযোগী করতে এআই ও ডিজিটাল প্রযুক্তিতে এগোনোর তাগিদ দেওয়া হয়েছে।",
                        "body"             => "ডিসি সম্মেলনে প্রধানমন্ত্রী বলেন, বিশ্ব এখন কৃত্রিম বুদ্ধিমত্তা, অটোমেশন ও ডিজিটাল প্রযুক্তির যুগে প্রবেশ করেছে। এই বাস্তবতায় মাঠ প্রশাসনের কাজ আরও দ্রুত, স্বচ্ছ ও জবাবদিহিমূলক করতে প্রযুক্তি ব্যবহারের বিকল্প নেই। জেলা প্রশাসকদের স্থানীয় পর্যায়ের সমস্যা শনাক্ত, তথ্যভিত্তিক সিদ্ধান্ত গ্রহণ এবং নাগরিকসেবা সহজ করতে ডিজিটাল সক্ষমতা বাড়ানোর আহ্বান জানানো হয়।",
                        'seo_keywords'     => "এআই, জনপ্রশাসন, ডিজিটাল বাংলাদেশ, ডিসি",
                        'published_at'     => "2026-05-03 13:46:00",
                    ],

                    (object) [
                        "category_name"    => "জাতীয়",
                        'name'             => "গণমাধ্যমের স্বাধীনতা ও আস্থা পুনর্গঠনে আলোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গণমাধ্যমের বিশ্বাসযোগ্যতা, স্বাধীনভাবে কাজের পরিবেশ ও সাংবাদিকদের নিরাপত্তা নিয়ে ঢাকায় আলোচনায় উদ্বেগ প্রকাশ করা হয়েছে।",
                        "body"             => "ঢাকায় গণমাধ্যমের স্বাধীনতা ও গণতান্ত্রিক জবাবদিহিতা বিষয়ক আলোচনায় বক্তারা বলেন, স্বাধীনভাবে কাজ করতে না পারা, অপতথ্যের বিস্তার এবং অর্থনৈতিক নির্ভরশীলতা গণমাধ্যমের বিশ্বাসযোগ্যতা ক্ষতিগ্রস্ত করছে। সাংবাদিকদের নিরাপত্তা, তথ্যের নির্ভুলতা ও পেশাগত স্বাধীনতা নিশ্চিত করার ওপর গুরুত্ব দেওয়া হয়। আলোচনায় বলা হয়, নতুন রাজনৈতিক বাস্তবতায় গণমাধ্যমের ওপর জনআস্থা পুনর্গঠনের সুযোগ তৈরি হয়েছে।",
                        'seo_keywords'     => "গণমাধ্যম, সাংবাদিকতা, স্বাধীনতা, অপতথ্য",
                        'published_at'     => "2026-05-03 13:17:00",
                    ],

                    (object) [
                        "category_name"    => "জাতীয়",
                        'name'             => "ডিসি সম্মেলনে ৪৯৮ প্রস্তাব নিয়ে আলোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চার দিনের জেলা প্রশাসক সম্মেলনে সারা দেশের বিভাগীয় কমিশনার ও ডিসিদের পাঠানো ৪৯৮টি প্রস্তাব নিয়ে আলোচনা হচ্ছে।",
                        "body"             => "জেলা প্রশাসক সম্মেলনে এবার বিভাগীয় কমিশনার ও জেলা প্রশাসকদের পাঠানো ১ হাজার ৭২৯টি প্রস্তাবের মধ্য থেকে ৪৯৮টি আলোচনার জন্য নির্ধারণ করা হয়েছে। প্রস্তাবগুলোর মধ্যে রয়েছে বাজেট অর্থ দ্রুত ছাড়, সড়ক উন্নয়ন, হাসপাতাল, অর্থনৈতিক অঞ্চল, শিক্ষা ও আইন মন্ত্রণালয় সংশ্লিষ্ট নানা বিষয়। সম্মেলনে মন্ত্রী ও সচিবদের উপস্থিতিতে মাঠপর্যায়ের সমস্যা, উন্নয়ন কর্মসূচি ও নীতিগত সিদ্ধান্ত নিয়ে আলোচনা হবে।",
                        'seo_keywords'     => "ডিসি সম্মেলন, ৪৯৮ প্রস্তাব, উন্নয়ন, প্রশাসন",
                        'published_at'     => "2026-05-02 00:16:00",
                    ],

                    (object) [
                        "category_name"    => "রাজনীতি",
                        'name'             => "সংস্কার বাস্তবায়নে সরকারকে চাপ দেওয়ার আহ্বান এনসিপির",
                        'sub_heading'      => "জুলাই সনদ বাস্তবায়ন না হলে কর্তৃত্ববাদ ফেরার আশঙ্কা",
                        'content_shoulder' => "",
                        'brief'            => "এনসিপির জাতীয় কনভেনশনে বক্তারা অভিযোগ করেছেন, নির্বাচনী প্রতিশ্রুতি অনুযায়ী সংস্কার বাস্তবায়নে সরকার পিছিয়ে যাচ্ছে।",
                        "body"             => "রাজধানীতে এনসিপির সংস্কার বাস্তবায়ন কমিটির জাতীয় কনভেনশনে বক্তারা বলেন, নির্বাচনের আগে দেওয়া সংস্কার প্রতিশ্রুতি বাস্তবায়নে সরকারকে বাধ্য করতে হবে। তাঁদের দাবি, জুলাই সনদ রাষ্ট্রের প্রধান অঙ্গগুলোর মধ্যে ক্ষমতার ভারসাম্য প্রতিষ্ঠার একটি গুরুত্বপূর্ণ দলিল। বক্তারা অভিযোগ করেন, প্রতিশ্রুতি থেকে সরে গেলে রাজনৈতিক ব্যবস্থা আবার ক্ষমতাকেন্দ্রিক ও কর্তৃত্ববাদী হয়ে উঠতে পারে।",
                        'seo_keywords'     => "এনসিপি, জুলাই সনদ, সংস্কার, রাজনীতি",
                        'published_at'     => "2026-05-03 14:29:00",
                    ],

                    (object) [
                        "category_name"    => "রাজনীতি",
                        'name'             => "ক্ষমতার বাহানায় সংস্কার না করার অভিযোগ আখতারের",
                        'sub_heading'      => "",
                        'content_shoulder' => "সংস্কার বিতর্ক",
                        'brief'            => "এনসিপি নেতা আখতার হোসেন অভিযোগ করেছেন, নির্বাচিত প্রতিনিধিদের দায়িত্বকে সরকার ক্ষমতার দৃষ্টিতে দেখছে।",
                        "body"             => "এনসিপির সদস্য সচিব ও সংসদ সদস্য আখতার হোসেন কনভেনশনে বলেন, সরকার সংস্কারের বদলে ক্ষমতাকে নিরঙ্কুশভাবে উপভোগ করতে চাইছে। তাঁর বক্তব্যে ঐকমত্য কমিশন, নোট অব ডিসেন্ট এবং রাজনৈতিক দলগুলোর সম্মত সিদ্ধান্ত বাস্তবায়নের প্রসঙ্গ উঠে আসে। তিনি দাবি করেন, নির্বাচনের মাধ্যমে পাওয়া দায়িত্বকে ক্ষমতার সুবিধা হিসেবে দেখা হলে গণতান্ত্রিক জবাবদিহি দুর্বল হয়।",
                        'seo_keywords'     => "আখতার হোসেন, এনসিপি, সংস্কার, বিএনপি",
                        'published_at'     => "2026-05-03 12:53:00",
                    ],

                    (object) [
                        "category_name"    => "রাজনীতি",
                        'name'             => "এনসিপি ও এবি পার্টি একীভূত হওয়া নিয়ে আলোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এনসিপি ও এবি পার্টির মধ্যে আদর্শগত মিল, জোট রাজনীতি ও নেতাকর্মী যোগদান ঘিরে একীভূত হওয়ার গুঞ্জন তৈরি হয়েছে।",
                        "body"             => "রাজনৈতিক অঙ্গনে এনসিপি ও এবি পার্টির সম্ভাব্য একীভূত হওয়া নিয়ে আলোচনা চলছে। দুই দলই বৃহত্তর জোট রাজনীতির অভিজ্ঞতা রাখে এবং সাম্প্রতিক সময়ে এবি পার্টির কয়েকজন নেতা এনসিপিতে যোগ দিয়েছেন বলে জানা গেছে। তবে দুই দলের শীর্ষ পর্যায় থেকে এখনো নির্দিষ্ট রোডম্যাপ ঘোষণা করা হয়নি। কেউ সম্ভাবনা নাকচ করেননি, আবার কেউ বলছেন আলোচনা থাকলেও সিদ্ধান্তের সময় আসেনি।",
                        'seo_keywords'     => "এনসিপি, এবি পার্টি, জোট রাজনীতি, একীভূত",
                        'published_at'     => "2026-05-03 00:01:00",
                    ],

                    (object) [
                        "category_name"    => "রাজনীতি",
                        'name'             => "ছাত্রদলের একযোগে ২৯ কমিটি অনুমোদন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দেশের বিশ্ববিদ্যালয়, কলেজ, মেডিক্যাল কলেজ, জেলা ও মহানগর পর্যায়ে ছাত্রদলের ২৯টি কমিটি অনুমোদন দেওয়া হয়েছে।",
                        "body"             => "বাংলাদেশ জাতীয়তাবাদী ছাত্রদল একদিনে সাতটি সরকারি-বেসরকারি বিশ্ববিদ্যালয়সহ মোট ২৯টি শিক্ষাপ্রতিষ্ঠান, জেলা ও মহানগর কমিটির অনুমোদন দিয়েছে। এর মধ্যে কিছু কমিটি নতুনভাবে গঠন করা হয়েছে, আর কিছু কমিটি পূর্ণাঙ্গ, আংশিক পূর্ণাঙ্গ বা বর্ধিত করা হয়েছে। সংগঠনটির এই সিদ্ধান্তকে তৃণমূল পুনর্গঠন ও ক্যাম্পাসভিত্তিক রাজনৈতিক কার্যক্রম জোরদারের অংশ হিসেবে দেখা হচ্ছে।",
                        'seo_keywords'     => "ছাত্রদল, কমিটি, রাজনীতি, ক্যাম্পাস",
                        'published_at'     => "2026-05-03 01:04:00",
                    ],

                    (object) [
                        "category_name"    => "রাজনীতি",
                        'name'             => "দলিত ও হরিজনদের নিয়ে এনসিপি সমর্থিত সংগঠনের আত্মপ্রকাশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "প্রান্তিক জনগোষ্ঠীর অধিকার প্রতিষ্ঠায় এনসিপির সমর্থনে জাতীয় জনজাতি জোট নামে নতুন সংগঠনের আত্মপ্রকাশ হয়েছে।",
                        "body"             => "দলিত, হরিজন ও অন্যান্য প্রান্তিক জনগোষ্ঠীকে কেন্দ্র করে এনসিপি সমর্থিত জাতীয় জনজাতি জোটের আত্মপ্রকাশ হয়েছে। সংগঠনটি সাংবিধানিক, সামাজিক, অর্থনৈতিক ও রাজনৈতিক অধিকার প্রতিষ্ঠায় কাজ করবে বলে জানানো হয়েছে। রাজনৈতিক বিশ্লেষকদের মতে, প্রান্তিক জনগোষ্ঠীকে সংগঠিত করার এই উদ্যোগ নতুন দলগুলোর সামাজিক ভিত্তি বিস্তারের প্রচেষ্টার অংশ হতে পারে।",
                        'seo_keywords'     => "এনসিপি, দলিত, হরিজন, প্রান্তিক জনগোষ্ঠী",
                        'published_at'     => "2026-05-03 02:52:00",
                    ],

                    (object) [
                        "category_name"    => "সরকার",
                        'name'             => "রাজনৈতিক পরিচয়ে বৈষম্য না করার নির্দেশ",
                        'sub_heading'      => "সরকারি সেবা সবার জন্য সমান রাখতে মাঠ প্রশাসনকে বার্তা",
                        'content_shoulder' => "",
                        'brief'            => "প্রধানমন্ত্রী বলেছেন, মতভেদ থাকলেও সরকারি সেবা, ন্যায়বিচার ও সুযোগ-সুবিধায় রাজনৈতিক পরিচয় যেন কোনো বাধা না হয়।",
                        "body"             => "ডিসি সম্মেলনে প্রধানমন্ত্রী বলেন, জাতীয় ঐক্যই দেশের সবচেয়ে বড় শক্তি এবং দেশের স্বার্থে সবাইকে আগে বাংলাদেশকে বিবেচনায় রাখতে হবে। তিনি মাঠ প্রশাসনকে সরকারি সেবা হয়রানিমুক্ত করা, ন্যায়বিচার প্রাপ্তি সহজ করা এবং সবার জন্য সমান সুযোগ নিশ্চিত করার নির্দেশ দেন। রাজনৈতিক পরিচয় বা মতভেদের কারণে যেন কেউ প্রশাসনিক সেবা থেকে বঞ্চিত না হন, সে বিষয়ে সতর্ক থাকার আহ্বান জানানো হয়।",
                        'seo_keywords'     => "সরকার, রাজনৈতিক বৈষম্য, জনসেবা, ডিসি",
                        'published_at'     => "2026-05-03 13:52:00",
                    ],

                    (object) [
                        "category_name"    => "সরকার",
                        'name'             => "মোবাইল কোর্ট জোরদারে জেলা প্রশাসকদের নির্দেশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "আইন প্রয়োগ",
                        'brief'            => "বিদ্যুৎ সাশ্রয়, খাদ্যে ভেজাল রোধ, বাল্যবিবাহ বন্ধ ও সাম্প্রদায়িক সম্প্রীতি রক্ষায় মোবাইল কোর্ট জোরদারের নির্দেশ দেওয়া হয়েছে।",
                        "body"             => "প্রধানমন্ত্রী ডিসি সম্মেলনে জেলা প্রশাসকদের মোবাইল কোর্টের কার্যক্রম বাড়ানোর নির্দেশ দেন। তিনি বলেন, বিদ্যুৎ সাশ্রয়ে সরকারের সিদ্ধান্ত মাঠপর্যায়ে বাস্তবায়ন করতে হবে। পাশাপাশি জনগণের ন্যায্য অভিযোগ গুরুত্ব দিয়ে দেখা, সাম্প্রদায়িক সম্প্রীতি রক্ষা, বাল্যবিবাহ বন্ধে কঠোর ব্যবস্থা এবং খাদ্যে ভেজালকারীদের বিরুদ্ধে ছাড়হীন অবস্থান নিতে বলা হয়েছে।",
                        'seo_keywords'     => "মোবাইল কোর্ট, খাদ্যে ভেজাল, বাল্যবিবাহ, সরকার",
                        'published_at'     => "2026-05-03 13:09:00",
                    ],

                    (object) [
                        "category_name"    => "সরকার",
                        'name'             => "ডিসি সম্মেলনে সরকারের নীতি ও উন্নয়ন কর্মসূচি পর্যালোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সরকারের নীতি, উন্নয়ন কর্মসূচি ও মাঠ প্রশাসনের জনস্বার্থ সংশ্লিষ্ট প্রস্তাব নিয়ে জেলা প্রশাসক সম্মেলনে আলোচনা শুরু হয়েছে।",
                        "body"             => "ডিসি সম্মেলনকে সরকারের নীতি বাস্তবায়ন ও মাঠপর্যায়ের বাস্তবতা জানার গুরুত্বপূর্ণ প্ল্যাটফর্ম হিসেবে দেখা হচ্ছে। চার দিনের এই সম্মেলনে উন্নয়ন কর্মসূচি, মন্ত্রণালয়ভিত্তিক সমস্যা, জেলা পর্যায়ের প্রস্তাব এবং প্রশাসনিক সিদ্ধান্ত নিয়ে আলোচনা হবে। জেলা প্রশাসকরা সরকারের প্রতিনিধি হিসেবে স্থানীয় সমন্বয়, নীতিনির্ধারণ ও জনস্বার্থ সংশ্লিষ্ট কার্যক্রম বাস্তবায়নে ভূমিকা রাখেন।",
                        'seo_keywords'     => "সরকারি নীতি, উন্নয়ন, ডিসি সম্মেলন, মাঠ প্রশাসন",
                        'published_at'     => "2026-05-03 08:19:00",
                    ],

                    (object) [
                        "category_name"    => "সরকার",
                        'name'             => "প্রশাসনে সততা, মেধা ও দক্ষতাকে নীতি বললেন প্রধানমন্ত্রী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "প্রশাসনে পদোন্নতি ও বদলির ক্ষেত্রে সততা, মেধা ও দক্ষতাকে গুরুত্ব দেওয়ার কথা বলেছেন প্রধানমন্ত্রী।",
                        "body"             => "ডিসি সম্মেলনে প্রধানমন্ত্রী বলেন, প্রশাসনে সততা, মেধা ও দক্ষতাই সরকারের নীতি। তিনি সতর্ক করে বলেন, সাময়িক সুবিধার জন্য পেশাদারিত্বের সঙ্গে আপস করলে তা জনপ্রশাসনকে দুর্নীতির দিকে ঠেলে দেয় এবং রাষ্ট্রের নিরপেক্ষতা প্রশ্নবিদ্ধ করে। জেলা প্রশাসকদের উন্নয়ন, দুর্যোগ মোকাবিলা ও জনসেবা নিশ্চিত করতে পেশাদার অবস্থান বজায় রাখার আহ্বান জানানো হয়।",
                        'seo_keywords'     => "জনপ্রশাসন, সততা, দক্ষতা, সরকার",
                        'published_at'     => "2026-05-03 13:02:00",
                    ],

                    (object) [
                        "category_name"    => "সরকার",
                        'name'             => "গণপরিবহনে শৃঙ্খলা আনতে নতুন পরিকল্পনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকার গণপরিবহনে শৃঙ্খলা ফেরাতে পিংক বাস, বাইসাইকেল রাইড শেয়ারিং, ই-বাস ও মনোরেলসহ নানা পরিকল্পনা নেওয়া হয়েছে।",
                        "body"             => "ঢাকার গণপরিবহন ব্যবস্থায় শৃঙ্খলা আনতে স্বল্প, মধ্য ও দীর্ঘমেয়াদি পরিকল্পনা নেওয়া হয়েছে। পরিকল্পনার মধ্যে নারীদের জন্য পিংক বাস, বাইসাইকেল রাইড শেয়ারিং, বাসরুট রেশনালাইজেশন আইন, বিদ্যুৎচালিত বাস এবং মেট্রোরেলের পাশাপাশি মনোরেল চালুর উদ্যোগ রয়েছে। বিশেষজ্ঞরা বলছেন, রাজধানীর যানজট কমাতে বিচ্ছিন্ন উদ্যোগ নয়, সমন্বিত পরিবহন ব্যবস্থাই সবচেয়ে জরুরি।",
                        'seo_keywords'     => "গণপরিবহন, ঢাকা, পিংক বাস, মনোরেল",
                        'published_at'     => "2026-05-03 09:45:00",
                    ],

                    (object) [
                        "category_name"    => "আইন ও শৃঙ্খলা",
                        'name'             => "মোহাম্মদপুরের অপরাধ দমনে রায়েরবাজারে নতুন থানা হবে",
                        'sub_heading'      => "ডিএমপি বলছে চাঁদাবাজ, অস্ত্রধারী ও মাদকচক্রের বিরুদ্ধে অভিযান চলছে",
                        'content_shoulder' => "",
                        'brief'            => "মোহাম্মদপুর ও আশপাশের অপরাধ নিয়ন্ত্রণে রায়েরবাজার এলাকায় নতুন থানা করার কথা জানিয়েছে ঢাকা মহানগর পুলিশ।",
                        "body"             => "ঢাকা মহানগর পুলিশ জানিয়েছে, রাজধানীজুড়ে চাঁদাবাজ, অস্ত্রধারী সন্ত্রাসী, মাদক কারবারি ও অনলাইন জুয়া চক্রের বিরুদ্ধে সাঁড়াশি অভিযান চলছে। গত ৪৮ ঘণ্টায় চাঁদাবাজ ও তাদের সহযোগীদের গ্রেপ্তারের তথ্য দেওয়া হয়। ডিএমপি কমিশনার বলেন, মোহাম্মদপুর ও আশপাশের এলাকার অপরাধ দমনে রায়েরবাজারে একটি থানা করা হবে। প্রযুক্তির মাধ্যমে অপরাধীদের গতিবিধি নজরদারির কথাও জানানো হয়।",
                        'seo_keywords'     => "মোহাম্মদপুর, রায়েরবাজার, ডিএমপি, অপরাধ",
                        'published_at'     => "2026-05-03 14:30:00",
                    ],

                    (object) [
                        "category_name"    => "আইন ও শৃঙ্খলা",
                        'name'             => "৩০ লাখ টাকার কাপড় লুটের ঘটনায় পাঁচজন গ্রেপ্তার",
                        'sub_heading'      => "",
                        'content_shoulder' => "ঢাকায় অভিযান",
                        'brief'            => "বিমানবন্দর এলাকায় চালককে বিভ্রান্ত করে আমদানিকৃত গার্মেন্টস কাপড় লুটের ঘটনায় পাঁচজনকে গ্রেপ্তার করেছে পুলিশ।",
                        "body"             => "রাজধানীর বিমানবন্দর এলাকায় চলন্ত গাড়ির চালককে বিভ্রান্ত করে চীন থেকে আমদানি করা প্রায় ৩০ লাখ টাকার গার্মেন্টস কাপড় লুটের ঘটনায় পুলিশ পাঁচজনকে গ্রেপ্তার করেছে। অভিযানে লুট হওয়া কাপড় এবং বহনকারী কাভার্ডভ্যান উদ্ধার করা হয়েছে। ডিএমপির উত্তরা বিভাগের বিমানবন্দর জোনের কর্মকর্তারা জানান, ঘটনাটি পরিকল্পিতভাবে করা হয়েছিল এবং জড়িতদের শনাক্ত করে অভিযান চালানো হয়।",
                        'seo_keywords'     => "কাপড় লুট, বিমানবন্দর, গ্রেপ্তার, ডিএমপি",
                        'published_at'     => "2026-05-03 15:30:00",
                    ],

                    (object) [
                        "category_name"    => "আইন ও শৃঙ্খলা",
                        'name'             => "নৌ পুলিশের ওপর গুলির মামলার আসামি ঢাকায় গ্রেপ্তার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কুষ্টিয়ার পদ্মা নদীতে নৌ পুলিশের ওপর গুলির ঘটনায় করা মামলার প্রধান আসামিকে রাজধানী থেকে গ্রেপ্তার করেছে র‍্যাব।",
                        "body"             => "কুষ্টিয়ার পদ্মা নদীতে নৌ পুলিশের ওপর গুলিবর্ষণের ঘটনায় করা মামলার প্রধান আসামি আব্দুল আলীম ওরফে চিকন আলীমকে র‍্যাব ঢাকার কাফরুল এলাকা থেকে গ্রেপ্তার করেছে। র‍্যাব জানায়, তিনি কালু বাহিনীর সঙ্গে যুক্ত ছিলেন এবং কুষ্টিয়ার কুমারখালী-খোকসা ও পদ্মা নদী এলাকায় প্রভাব বিস্তারের সন্ত্রাসী কর্মকাণ্ডে জড়িত ছিলেন। ঘটনার পর থেকেই আসামিদের ধরতে অভিযান জোরদার করা হয়েছিল।",
                        'seo_keywords'     => "নৌ পুলিশ, কুষ্টিয়া, র‍্যাব, গ্রেপ্তার",
                        'published_at'     => "2026-05-03 15:47:00",
                    ],

                    (object) [
                        "category_name"    => "আইন ও শৃঙ্খলা",
                        'name'             => "তেজগাঁও বিভাগে অভিযানে ৫১ জন গ্রেপ্তার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা মহানগর পুলিশের তেজগাঁও বিভাগের বিভিন্ন অপরাধপ্রবণ এলাকায় নিয়মিত অভিযানে ৫১ জনকে গ্রেপ্তার করা হয়েছে।",
                        "body"             => "ঢাকা মহানগর পুলিশের তেজগাঁও বিভাগের বিভিন্ন থানা এলাকায় অভিযান চালিয়ে নানা অপরাধে জড়িত ৫১ জনকে গ্রেপ্তার করা হয়েছে। ডিএমপি জানায়, তেজগাঁও, শেরেবাংলা নগর, হাতিরঝিল, আদাবর, মোহাম্মদপুর ও তেজগাঁও শিল্পাঞ্চল থানা এলাকায় এই অভিযান পরিচালিত হয়। অপরাধপ্রবণ এলাকায় নিয়মিত অভিযানের অংশ হিসেবে গ্রেপ্তারদের বিরুদ্ধে আইনগত ব্যবস্থা নেওয়া হচ্ছে।",
                        'seo_keywords'     => "তেজগাঁও, গ্রেপ্তার, ডিএমপি, অভিযান",
                        'published_at'     => "2026-05-02 16:38:00",
                    ],

                    (object) [
                        "category_name"    => "আইন ও শৃঙ্খলা",
                        'name'             => "ঘুষের অভিযোগের পর মিরপুর থানার ওসি প্রত্যাহার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঘুষ নেওয়ার অভিযোগ ওঠার কয়েক দিনের মধ্যে মিরপুর মডেল থানার ওসিকে ডিএমপি সদর দপ্তরে সংযুক্ত করা হয়েছে।",
                        "body"             => "রাজধানীর মিরপুর মডেল থানার ওসি মোহাম্মদ গোলাম আজমকে প্রত্যাহার করে ডিএমপি সদর দপ্তরের প্রশাসন বিভাগের কেন্দ্রীয় সংরক্ষণ দপ্তরে সংযুক্ত করা হয়েছে। এর আগে এক নারী মার্কেট দখল উদ্ধারের কথা বলে পাঁচ লাখ টাকা ঘুষ নেওয়ার অভিযোগ করেন। ডিএমপি কর্মকর্তারা জানিয়েছেন, অভিযোগের সঙ্গে প্রত্যাহারের সম্পর্ক আছে কি না তা তদন্ত শেষ না হওয়া পর্যন্ত নিশ্চিত করে বলা যাবে না।",
                        'seo_keywords'     => "মিরপুর থানা, ওসি প্রত্যাহার, ঘুষ অভিযোগ, ডিএমপি",
                        'published_at'     => "2026-05-02 23:00:00",
                    ],

                    (object) [
                        "category_name"    => "নির্বাচন",
                        'name'             => "সংরক্ষিত নারী আসনে নুসরাত তাবাসসুমের মনোনয়ন বৈধ দাবি",
                        'sub_heading'      => "নির্ধারিত সময়ের পরে জমা দেওয়া মনোনয়ন নিয়ে আইনি জটিলতা ছিল",
                        'content_shoulder' => "",
                        'brief'            => "সংরক্ষিত নারী আসনে এনসিপি নেত্রী নুসরাত তাবাসসুমের মনোনয়নপত্র বৈধ ঘোষণা করা হয়েছে বলে দাবি করেছে দলটি।",
                        "body"             => "জাতীয় নাগরিক পার্টি দাবি করেছে, সংরক্ষিত নারী আসনে দলের যুগ্ম আহ্বায়ক নুসরাত তাবাসসুমের মনোনয়নপত্র নির্বাচন কমিশন বৈধ ঘোষণা করেছে। নির্ধারিত সময়ের পরে মনোনয়নপত্র জমা দেওয়ায় তা আগে গ্রহণ করা হয়নি। পরে প্রার্থিতা ফিরে পেতে তিনি হাইকোর্টে রিট করেন এবং আদালত নির্বাচন কমিশনকে মনোনয়নপত্র গ্রহণ করে আইন অনুযায়ী ব্যবস্থা নিতে নির্দেশ দেন। বিষয়টি সংরক্ষিত আসনের নির্বাচনী প্রক্রিয়ায় আলোচনার জন্ম দিয়েছে।",
                        'seo_keywords'     => "নুসরাত তাবাসসুম, সংরক্ষিত নারী আসন, নির্বাচন কমিশন, এনসিপি",
                        'published_at'     => "2026-05-02 12:16:00",
                    ],

                    (object) [
                        "category_name"    => "নির্বাচন",
                        'name'             => "গণভোটের রায় মানার আহ্বান ১১ দলীয় নেতাদের",
                        'sub_heading'      => "",
                        'content_shoulder' => "গণভোট বিতর্ক",
                        'brief'            => "চট্টগ্রামের গণমিছিলে ১১ দলীয় ঐক্যের নেতারা জুলাই সনদ ও গণভোটের রায় বাস্তবায়নের দাবি জানিয়েছেন।",
                        "body"             => "চট্টগ্রামের কেরানীহাটে ১১ দলীয় ঐক্যের আয়োজিত গণমিছিলে বক্তারা বলেন, গণভোটের রায় মেনে জুলাই সনদের পক্ষে দাঁড়াতে হবে। জামায়াত নেতা শাহজাহান চৌধুরী অভিযোগ করেন, নির্বাচনের আগে প্রতিশ্রুতি দেওয়া হলেও এখন জুলাই সনদ বাস্তবায়নে সরকার পিছিয়ে যাচ্ছে। বক্তারা দাবি করেন, জনগণের রায় উপেক্ষা করা হলে সংসদের ভেতরে ও বাইরে আন্দোলন চলবে।",
                        'seo_keywords'     => "গণভোট, জুলাই সনদ, নির্বাচন, ১১ দলীয় ঐক্য",
                        'published_at'     => "2026-05-03 06:17:00",
                    ],

                    (object) [
                        "category_name"    => "নির্বাচন",
                        'name'             => "এনসিপির আসন হিসাব ঘিরে সংরক্ষিত নারী আসনের আলোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ত্রয়োদশ সংসদ নির্বাচনের পর এনসিপির আসনসংখ্যা ও সংরক্ষিত নারী আসনের হিসাব নতুন রাজনৈতিক আলোচনার জন্ম দিয়েছে।",
                        "body"             => "ত্রয়োদশ জাতীয় সংসদ নির্বাচনের পর এনসিপি শাপলা কলি প্রতীকে ২৯টি আসনে প্রতিদ্বন্দ্বিতা করে কয়েকটি আসনে জয় পাওয়ার দাবি ঘিরে রাজনৈতিক আলোচনা তৈরি হয়েছে। দলটির আসনের আনুপাতিক হিসাব অনুযায়ী সংরক্ষিত নারী আসন পাওয়ার বিষয়ও আলোচনায় আসে। একই সঙ্গে জোটসঙ্গীদের সঙ্গে ছাড়, দলীয় শক্তি পুনর্গঠন এবং ভবিষ্যৎ নির্বাচনকেন্দ্রিক কৌশল নিয়ে রাজনৈতিক অঙ্গনে নানা ব্যাখ্যা চলছে।",
                        'seo_keywords'     => "এনসিপি, সংরক্ষিত আসন, নির্বাচন, সংসদ",
                        'published_at'     => "2026-05-03 00:01:00",
                    ],

                    (object) [
                        "category_name"    => "নির্বাচন",
                        'name'             => "স্থানীয় সরকার অধ্যাদেশ নিয়ে নির্বাচিত প্রতিনিধিদের অপসারণ বিতর্ক",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "স্থানীয় সরকার প্রতিনিধিদের অপসারণের ক্ষমতা নিয়ে রাজনৈতিক নেতারা সংসদ, সরকার ও নির্বাচনী জবাবদিহি প্রশ্ন তুলেছেন।",
                        "body"             => "এনসিপি নেতা আব্দুল হান্নান মাসউদ অভিযোগ করেছেন, স্থানীয় সরকার অধ্যাদেশ আইনে রূপান্তরের ফলে নির্বাচিত জনপ্রতিনিধিদের অপসারণ সহজ হয়ে যেতে পারে। তাঁর বক্তব্যে বলা হয়, কোনো বিরোধী দলীয় মেয়র বা চেয়ারম্যানকে সরকার রাজনৈতিক কারণে অপসারণ করে প্রশাসক বসাতে পারে—এমন আশঙ্কা তৈরি হয়েছে। বিষয়টি স্থানীয় নির্বাচন, জনপ্রতিনিধির অধিকার ও ক্ষমতার ভারসাম্য নিয়ে নতুন বিতর্ক তৈরি করেছে।",
                        'seo_keywords'     => "স্থানীয় সরকার, নির্বাচন, জনপ্রতিনিধি, অধ্যাদেশ",
                        'published_at'     => "2026-05-03 12:29:00",
                    ],

                    (object) [
                        "category_name"    => "নির্বাচন",
                        'name'             => "জোট রাজনীতির পরে দলীয় পুনর্গঠনে নজর নতুন দলগুলোর",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নির্বাচনের পর এনসিপি, এবি পার্টি ও জোটসঙ্গীদের মধ্যে সাংগঠনিক পুনর্গঠন ও সম্ভাব্য মেরুকরণ নিয়ে আলোচনা বাড়ছে।",
                        "body"             => "জাতীয় নির্বাচনের পর রাজনৈতিক দলগুলোর মধ্যে নতুন মেরুকরণের আলোচনা শুরু হয়েছে। এনসিপি ও এবি পার্টির সম্পর্ক, জোট রাজনীতিতে তাদের অবস্থান এবং নেতাকর্মীদের দলবদল নির্বাচনের পরবর্তী রাজনীতিতে গুরুত্ব পাচ্ছে। পর্যবেক্ষকদের মতে, নির্বাচনী সাফল্য, সংরক্ষিত আসনের হিসাব এবং মাঠপর্যায়ের সংগঠন বিস্তার—এই তিনটি বিষয় নতুন দলগুলোর ভবিষ্যৎ কৌশল নির্ধারণে বড় ভূমিকা রাখবে।",
                        'seo_keywords'     => "জোট রাজনীতি, নির্বাচন পরবর্তী, এনসিপি, এবি পার্টি",
                        'published_at'     => "2026-05-03 00:45:00",
                    ],

                    (object) [
                        "category_name"    => "জননীতি",
                        'name'             => "বাজেট অর্থ ১৫ এপ্রিলের মধ্যে ছাড়ের প্রস্তাব ডিসিদের",
                        'sub_heading'      => "ডিসি সম্মেলনে উন্নয়ন প্রকল্প বাস্তবায়ন দ্রুত করার আলোচনা",
                        'content_shoulder' => "",
                        'brief'            => "ডিসিদের প্রস্তাবে বাজেটের অর্থ দ্রুত ছাড়, সড়ক উন্নয়ন, শিক্ষা ও স্বাস্থ্যসেবা নিয়ে একাধিক জননীতি প্রস্তাব এসেছে।",
                        "body"             => "জেলা প্রশাসক সম্মেলনে আলোচনার জন্য নির্ধারিত প্রস্তাবের মধ্যে জাতীয় বাজেটের সব অর্থ ১৫ এপ্রিলের মধ্যে ছাড় করার বিষয়টি রয়েছে। এর পাশাপাশি ঢাকা-ময়মনসিংহ সড়ক আট লেনে উন্নীত করা, শিক্ষা অবৈতনিক করা, মাতৃভাষায় পাঠদান নিশ্চিতে শিক্ষক নিয়োগ, কওমি মাদ্রাসার নীতিমালা এবং স্বাস্থ্য অবকাঠামো উন্নয়নের প্রস্তাবও রয়েছে। এসব প্রস্তাব মাঠপর্যায়ের জনসেবা ও উন্নয়ন বাস্তবায়নের সঙ্গে সরাসরি যুক্ত।",
                        'seo_keywords'     => "জননীতি, বাজেট, ডিসি প্রস্তাব, উন্নয়ন",
                        'published_at'     => "2026-05-02 00:16:00",
                    ],

                    (object) [
                        "category_name"    => "জননীতি",
                        'name'             => "ঢাকার গণপরিবহনে পিংক বাস ও মনোরেলের পরিকল্পনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "নগর নীতি",
                        'brief'            => "রাজধানীর গণপরিবহন ব্যবস্থায় নারী যাত্রী, ই-বাস, সাইকেল সেবা ও মনোরেলসহ সমন্বিত পরিকল্পনার কথা উঠে এসেছে।",
                        "body"             => "ঢাকার গণপরিবহন নীতিতে শৃঙ্খলা আনতে নারীদের জন্য পিংক বাস, বাইসাইকেল রাইড শেয়ারিং, বিদ্যুৎচালিত বাস, বাসরুট রেশনালাইজেশন আইন এবং মনোরেল চালুর পরিকল্পনা গুরুত্ব পাচ্ছে। বিশেষজ্ঞরা বলছেন, এলোমেলো বাস থামা, দুর্বল রুট ব্যবস্থাপনা এবং যানজটের সমস্যা সমাধানে পরিবহনকে একক প্রকল্প নয়, সমন্বিত ব্যবস্থাপনা হিসেবে দেখতে হবে।",
                        'seo_keywords'     => "নগর নীতি, গণপরিবহন, পিংক বাস, মনোরেল",
                        'published_at'     => "2026-05-03 09:45:00",
                    ],

                    (object) [
                        "category_name"    => "জননীতি",
                        'name'             => "খাদ্যে ভেজাল ও বাল্যবিবাহে কঠোর হওয়ার নির্দেশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জনস্বার্থ রক্ষায় খাদ্যে ভেজাল, বাল্যবিবাহ, বিদ্যুৎ সাশ্রয় ও সাম্প্রদায়িক সম্প্রীতি নিয়ে মাঠ প্রশাসনকে সক্রিয় হতে বলা হয়েছে।",
                        "body"             => "মাঠ প্রশাসনের জন্য দেওয়া নির্দেশনায় খাদ্যে ভেজালকারীদের বিরুদ্ধে কঠোর ব্যবস্থা, বাল্যবিবাহ বন্ধ, সাম্প্রদায়িক সম্প্রীতি রক্ষা এবং বিদ্যুৎ সাশ্রয়ের সিদ্ধান্ত বাস্তবায়নের বিষয় গুরুত্ব পেয়েছে। এসব নীতিকে শুধু আইন প্রয়োগ নয়, জনস্বাস্থ্য, সামাজিক নিরাপত্তা ও স্থানীয় শাসনের অংশ হিসেবে দেখা হচ্ছে। জেলা প্রশাসকদের স্থানীয় অভিযোগ গুরুত্ব দিয়ে নিষ্পত্তির নির্দেশও দেওয়া হয়েছে।",
                        'seo_keywords'     => "খাদ্যে ভেজাল, বাল্যবিবাহ, জনস্বাস্থ্য, জননীতি",
                        'published_at'     => "2026-05-03 13:09:00",
                    ],

                    (object) [
                        "category_name"    => "জননীতি",
                        'name'             => "প্রতিবন্ধী শিক্ষার্থীদের শিক্ষা অবৈতনিক করার প্রস্তাব",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ডিসিদের প্রস্তাবে দরিদ্র পরিবারের প্রতিবন্ধী শিক্ষার্থীদের জন্য অবৈতনিক শিক্ষা ও অন্তর্ভুক্তিমূলক শিক্ষাক্রমের বিষয় এসেছে।",
                        "body"             => "ডিসি সম্মেলনের আলোচ্য প্রস্তাবের মধ্যে জেলাভিত্তিক দরিদ্র পরিবারের প্রতিবন্ধী শিক্ষার্থীদের শিক্ষা অবৈতনিক করা এবং সব শিশুর শিখন নিশ্চিত করতে একীভূত শিক্ষাক্রম ও মূল্যায়নব্যবস্থা চালুর বিষয় রয়েছে। ক্ষুদ্র জাতিগোষ্ঠীর শিক্ষার্থীদের মাতৃভাষায় পাঠদান নিশ্চিতে সংশ্লিষ্ট ভাষায় শিক্ষক নিয়োগের প্রস্তাবও এসেছে। এগুলো শিক্ষা ন্যায্যতা ও অন্তর্ভুক্তিমূলক জননীতির গুরুত্বপূর্ণ অংশ।",
                        'seo_keywords'     => "প্রতিবন্ধী শিক্ষা, অন্তর্ভুক্তিমূলক শিক্ষা, জননীতি, ডিসি প্রস্তাব",
                        'published_at'     => "2026-05-02 00:16:00",
                    ],

                    (object) [
                        "category_name"    => "জননীতি",
                        'name'             => "পুলিশ সপ্তাহে ১০৭ সদস্যকে পদক দেওয়ার প্রস্তুতি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পুলিশ সপ্তাহে বিপিএম ও পিপিএম পদকের জন্য ১০৭ জন সদস্য মনোনীত হওয়ায় আইনশৃঙ্খলা বাহিনীর স্বীকৃতি নীতিও আলোচনায় এসেছে।",
                        "body"             => "আগামী পুলিশ সপ্তাহে বাংলাদেশ পুলিশ পদক ও রাষ্ট্রপতির পুলিশ পদকের জন্য ১০৭ জন সদস্যকে মনোনীত করা হয়েছে। পুলিশ ছাড়াও র‍্যাবে দায়িত্ব পালনকারী কয়েকজন সেনা ও বিমান বাহিনীর কর্মকর্তাও পদক পাচ্ছেন বলে জানা গেছে। আইনশৃঙ্খলা বাহিনীর পেশাগত অবদানকে স্বীকৃতি দেওয়ার এই প্রক্রিয়া নিরাপত্তা প্রশাসন, মনোবল এবং জনসেবার মানোন্নয়নের সঙ্গে যুক্ত জননীতি হিসেবে গুরুত্ব পাচ্ছে।",
                        'seo_keywords'     => "পুলিশ সপ্তাহ, বিপিএম, পিপিএম, আইনশৃঙ্খলা",
                        'published_at'     => "2026-05-01 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক",
                        'name'             => "ইসরায়েলি আদালতে তোলা হচ্ছে সুমুদ ফ্লোটিলার দুই অ্যাক্টিভিস্টকে",
                        'sub_heading'      => "গাজাগামী ত্রাণবহর আটক ঘিরে নতুন আন্তর্জাতিক চাপ",
                        'content_shoulder' => "",
                        'brief'            => "গাজা অভিমুখী সুমুদ ফ্লোটিলা থেকে আটক দুই বিদেশি অ্যাক্টিভিস্টকে ইসরায়েলি আদালতে হাজির করা হচ্ছে, মানবিক ত্রাণ ইস্যুতে বিতর্ক বাড়ছে।",
                        "body"             => "গাজা অভিমুখী মানবিক ত্রাণবহর সুমুদ ফ্লোটিলা থেকে জিজ্ঞাসাবাদের জন্য ইসরায়েলে নেওয়া দুই বিদেশি অ্যাক্টিভিস্টকে আদালতে হাজির করার উদ্যোগ নেওয়া হয়েছে। ত্রাণবহরটি গাজায় মানবিক সহায়তা পৌঁছানোর উদ্দেশ্যে এগোচ্ছিল বলে আয়োজকদের দাবি। ইসরায়েলি কর্তৃপক্ষ নিরাপত্তা যুক্তি দেখালেও মানবাধিকারকর্মীরা বলছেন, যুদ্ধবিধ্বস্ত গাজায় সহায়তা প্রবেশে বাধা দেওয়া পরিস্থিতিকে আরও জটিল করছে। ঘটনাটি আন্তর্জাতিক মানবিক আইন, অবরোধনীতি ও বেসামরিক মানুষের সহায়তা পাওয়ার অধিকার নিয়ে নতুন আলোচনার জন্ম দিয়েছে।",
                        'seo_keywords'     => "সুমুদ ফ্লোটিলা, গাজা, ইসরায়েল, মানবিক ত্রাণ",
                        'published_at'     => "2026-05-03 16:32:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক",
                        'name'             => "ইরান নিয়ে ট্রাম্পের সামনে দুটি পথ, দাবি আইআরজিসির",
                        'sub_heading'      => "",
                        'content_shoulder' => "তেহরানের কঠোর বার্তা",
                        'brief'            => "ইরানের আইআরজিসি দাবি করেছে, যুক্তরাষ্ট্রের সামনে এখন সামরিক অভিযান অথবা কঠিন চুক্তি—এই দুই পথের বাইরে বাস্তব বিকল্প কমে গেছে।",
                        "body"             => "ইরানের ইসলামিক রেভল্যুশনারি গার্ড কর্পসের গোয়েন্দা শাখা দাবি করেছে, যুক্তরাষ্ট্রের প্রেসিডেন্ট ডোনাল্ড ট্রাম্পের সিদ্ধান্ত নেওয়ার সুযোগ ক্রমেই সংকুচিত হচ্ছে। তাদের ভাষ্য অনুযায়ী, ওয়াশিংটনের সামনে এখন হয় ঝুঁকিপূর্ণ সামরিক অভিযান, নয়তো ইরানের সঙ্গে অস্বস্তিকর কূটনৈতিক সমঝোতা—এই দুই বাস্তবতার একটিতে যেতে হবে। ইরান যুদ্ধ, হরমুজ প্রণালি এবং যুক্তরাষ্ট্রের সামরিক অবস্থান ঘিরে এই বক্তব্যকে চাপ সৃষ্টির কৌশল হিসেবেও দেখা হচ্ছে।",
                        'seo_keywords'     => "ইরান, ট্রাম্প, আইআরজিসি, যুক্তরাষ্ট্র",
                        'published_at'     => "2026-05-03 16:06:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক",
                        'name'             => "গাজার ধ্বংসস্তূপের নিচে এখনও আট হাজার মরদেহ থাকার আশঙ্কা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গাজায় ধ্বংসস্তূপ অপসারণের ধীরগতির কারণে হাজারো ফিলিস্তিনির মরদেহ এখনও চাপা পড়ে আছে বলে জাতিসংঘ কর্মকর্তার বরাতে খবর প্রকাশিত হয়েছে।",
                        "body"             => "দীর্ঘ হামলায় গাজা উপত্যকার বহু এলাকা ধ্বংসস্তূপে পরিণত হয়েছে। জাতিসংঘের এক কর্মকর্তার উদ্ধৃতি দিয়ে প্রকাশিত খবরে বলা হয়েছে, এসব ধ্বংসস্তূপের নিচে এখনও অন্তত আট হাজার ফিলিস্তিনির মরদেহ থাকতে পারে। উদ্ধারকাজ ধীর হওয়ায় বহু পরিবার স্বজনের দেহও খুঁজে পাচ্ছে না। মানবিক সংস্থাগুলো বলছে, ধ্বংসাবশেষ সরানো, চিকিৎসা সহায়তা দেওয়া এবং নিরাপদ দাফনের ব্যবস্থা করা এখন জরুরি অগ্রাধিকার হওয়া উচিত।",
                        'seo_keywords'     => "গাজা, ফিলিস্তিন, জাতিসংঘ, ধ্বংসস্তূপ",
                        'published_at'     => "2026-05-03 09:07:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক",
                        'name'             => "ইরানের ১৪ দফা শান্তি প্রস্তাব পর্যালোচনায় যুক্তরাষ্ট্র",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চলমান যুদ্ধ অবসানের লক্ষ্যে ইরানের পাঠানো ১৪ দফা শান্তি প্রস্তাব নিয়ে ওয়াশিংটনে পর্যালোচনা শুরু হলেও ট্রাম্প সন্দিহান।",
                        "body"             => "ইরান চলমান সংঘাতের স্থায়ী সমাধানের দাবি তুলে যুক্তরাষ্ট্রের কাছে ১৪ দফা শান্তি প্রস্তাব পাঠিয়েছে। প্রস্তাবটি সাময়িক যুদ্ধবিরতির বাইরে গিয়ে অবরোধ, হরমুজ প্রণালি, সামরিক উত্তেজনা এবং আঞ্চলিক নিরাপত্তা প্রশ্নে কাঠামোগত সমাধানের কথা বলছে বলে দাবি তেহরানের। তবে যুক্তরাষ্ট্রের প্রেসিডেন্ট ডোনাল্ড ট্রাম্প শুরুতেই প্রস্তাব গ্রহণযোগ্য হবে কি না তা নিয়ে সংশয় প্রকাশ করেছেন। ফলে কূটনৈতিক পথ খোলা থাকলেও দুই পক্ষের অবিশ্বাস এখনও বড় বাধা।",
                        'seo_keywords'     => "ইরান শান্তি প্রস্তাব, ট্রাম্প, যুদ্ধবিরতি, কূটনীতি",
                        'published_at'     => "2026-05-03 06:55:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক",
                        'name'             => "লেবাননে ইসরায়েলি হামলায় ২৪ ঘণ্টায় নিহত ৪১",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "লেবাননের দক্ষিণাঞ্চলে ইসরায়েলি বিমান হামলায় ২৪ ঘণ্টায় অন্তত ৪১ জন নিহতের খবর এসেছে, যুদ্ধবিরতি লঙ্ঘনের অভিযোগও উঠেছে।",
                        "body"             => "লেবাননের দক্ষিণাঞ্চলজুড়ে ইসরায়েলি বাহিনীর বিমান হামলায় ২৪ ঘণ্টায় অন্তত ৪১ জন নিহত হয়েছেন বলে আন্তর্জাতিক সংবাদমাধ্যমে খবর প্রকাশ হয়েছে। দুই সপ্তাহ আগে ঘোষিত যুদ্ধবিরতি কার্যকর হওয়ার পরও হামলা অব্যাহত থাকায় পরিস্থিতি আরও অস্থির হচ্ছে। স্থানীয় সূত্রগুলো বলছে, বেসামরিক বসতি, অবকাঠামো ও সীমান্তবর্তী এলাকা ক্ষতিগ্রস্ত হয়েছে। হামলাকে ঘিরে ইসরায়েল-হিজবুল্লাহ উত্তেজনা এবং বৃহত্তর মধ্যপ্রাচ্য সংঘাতের ঝুঁকি আবার সামনে এসেছে।",
                        'seo_keywords'     => "লেবানন, ইসরায়েলি হামলা, হিজবুল্লাহ, যুদ্ধবিরতি",
                        'published_at'     => "2026-05-03 02:08:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব রাজনীতি",
                        'name'             => "মার্কিন সেনা প্রত্যাহার নিয়ে জার্মানি ও ন্যাটোর উদ্বেগ",
                        'sub_heading'      => "ইউরোপের নিরাপত্তা ব্যয় বাড়ানোর চাপ আরও স্পষ্ট",
                        'content_shoulder' => "",
                        'brief'            => "জার্মানি থেকে পাঁচ হাজার মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তকে ঘিরে বার্লিন ও ন্যাটোতে নিরাপত্তা, প্রতিরক্ষা ব্যয় ও জোটের ভবিষ্যৎ নিয়ে উদ্বেগ তৈরি হয়েছে।",
                        "body"             => "জার্মানি থেকে পাঁচ হাজার মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তকে ‘অনুমিত’ বললেও বার্লিন জানিয়েছে, ইউরোপে যুক্তরাষ্ট্রের সামরিক উপস্থিতি দুই পক্ষের স্বার্থেই গুরুত্বপূর্ণ। ন্যাটোর পক্ষ থেকেও বলা হয়েছে, এই সিদ্ধান্ত ইউরোপীয় দেশগুলোর প্রতিরক্ষা বিনিয়োগ বাড়ানো এবং নিরাপত্তার দায় ভাগ করে নেওয়ার প্রয়োজনীয়তা সামনে এনেছে। ইরান যুদ্ধ নিয়ে ওয়াশিংটন ও ইউরোপীয় মিত্রদের মতবিরোধের প্রেক্ষাপটে এই পদক্ষেপকে বিশ্ব রাজনীতির বড় পরিবর্তনের ইঙ্গিত হিসেবে দেখা হচ্ছে।",
                        'seo_keywords'     => "জার্মানি, ন্যাটো, মার্কিন সেনা, ইউরোপ",
                        'published_at'     => "2026-05-03 15:20:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব রাজনীতি",
                        'name'             => "তাইওয়ানের প্রেসিডেন্টকে ‘ইঁদুর’ বলল চীন",
                        'sub_heading'      => "",
                        'content_shoulder' => "চীন-তাইওয়ান উত্তেজনা",
                        'brief'            => "এসোয়াতিনি সফর ঘিরে তাইওয়ানের প্রেসিডেন্ট লাই চিং-তেকে কড়া ভাষায় আক্রমণ করেছে চীন, পাল্টা জবাব দিয়েছে তাইপে।",
                        "body"             => "তাইওয়ানের প্রেসিডেন্ট লাই চিং-তের আফ্রিকার দেশ এসোয়াতিনি সফরকে কেন্দ্র করে বেইজিং ও তাইপের মধ্যে কূটনৈতিক উত্তেজনা বেড়েছে। চীনের তাইওয়ানবিষয়ক দপ্তর লাইকে ‘ইঁদুর’ বলে আক্রমণ করে দাবি করেছে, তাঁর এই সফর আন্তর্জাতিকভাবে উপহাসের মুখে পড়বে। জবাবে তাইওয়ান বলেছে, প্রেসিডেন্ট কোথায় যাবেন তার জন্য বেইজিংয়ের অনুমতির প্রয়োজন নেই। এই ঘটনা তাইওয়ানের আন্তর্জাতিক স্বীকৃতি, সার্বভৌমত্ব দাবি এবং চীনের চাপনীতি নিয়ে নতুন বিতর্ক তৈরি করেছে।",
                        'seo_keywords'     => "তাইওয়ান, চীন, লাই চিং-তে, এসোয়াতিনি",
                        'published_at'     => "2026-05-03 13:10:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব রাজনীতি",
                        'name'             => "যুক্তরাষ্ট্র থেকে আরও যুদ্ধবিমান কিনবে ইসরায়েল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইরান যুদ্ধের অভিজ্ঞতার পর যুক্তরাষ্ট্র থেকে এফ-৩৫ ও এফ-১৫আই যুদ্ধবিমানের নতুন স্কোয়াড্রন কেনার পরিকল্পনায় এগোচ্ছে ইসরায়েল।",
                        "body"             => "ইরানের সঙ্গে সাম্প্রতিক যুদ্ধের অভিজ্ঞতার ভিত্তিতে ইসরায়েল যুক্তরাষ্ট্র থেকে আরও এফ-৩৫ ও এফ-১৫আই যুদ্ধবিমান কেনার পরিকল্পনায় চূড়ান্ত অনুমোদন দিয়েছে। ইসরায়েলি প্রতিরক্ষামন্ত্রী দাবি করেছেন, সাম্প্রতিক সংঘাতে বিমানবাহিনীর সক্ষমতা আবারও প্রমাণিত হয়েছে। বিশ্লেষকদের মতে, এই সিদ্ধান্ত শুধু সামরিক কেনাকাটা নয়; এটি ইসরায়েল-যুক্তরাষ্ট্র নিরাপত্তা সম্পর্ক, ইরানকে ঘিরে প্রতিরোধ কৌশল এবং মধ্যপ্রাচ্যের শক্তির ভারসাম্যের সঙ্গে সরাসরি যুক্ত।",
                        'seo_keywords'     => "ইসরায়েল, এফ-৩৫, যুক্তরাষ্ট্র, প্রতিরক্ষা",
                        'published_at'     => "2026-05-03 16:21:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব রাজনীতি",
                        'name'             => "পোস্টাল ব্যালটে কারসাজির অভিযোগে নির্বাচন কমিশনে তৃণমূল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কলকাতায় ইভিএম স্ট্রংরুমে পোস্টাল ব্যালট নিয়ে কারসাজির অভিযোগ তুলে নির্বাচন কমিশনে অভিযোগ জানিয়েছে তৃণমূল কংগ্রেস।",
                        "body"             => "কলকাতায় ইভিএম স্ট্রংরুমে পোস্টাল ব্যালটের খাম অবৈধভাবে সরানো ও বাছাইয়ের অভিযোগ তুলেছে তৃণমূল কংগ্রেস। দলটি নির্বাচন কমিশনে অভিযোগ জানিয়ে দাবি করেছে, ভোটের স্বচ্ছতা ও গণতান্ত্রিক আস্থার প্রশ্নে বিষয়টি গুরুতর। অভিযোগ ঘিরে বিভিন্ন এলাকায় রাজনৈতিক উত্তেজনা ও বিক্ষোভের খবরও এসেছে। ভারতের রাজ্য রাজনীতিতে নির্বাচনী প্রক্রিয়া, বিরোধী দলগুলোর অভিযোগ এবং কমিশনের ভূমিকা আবারও আলোচনায় এসেছে।",
                        'seo_keywords'     => "তৃণমূল, পোস্টাল ব্যালট, নির্বাচন কমিশন, কলকাতা",
                        'published_at'     => "2026-05-02 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব রাজনীতি",
                        'name'             => "চীনা শোধনাগারে মার্কিন নিষেধাজ্ঞা অমান্যের নির্দেশ বেইজিংয়ের",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইরান থেকে তেল কেনার অভিযোগে চীনা শোধনাগারের ওপর দেওয়া মার্কিন নিষেধাজ্ঞা অমান্যের নির্দেশ দিয়েছে বেইজিং।",
                        "body"             => "চীনের বাণিজ্য মন্ত্রণালয় ইরান থেকে তেল কেনার অভিযোগে কয়েকটি চীনা তেল শোধনাগারের ওপর দেওয়া মার্কিন নিষেধাজ্ঞা অমান্যের নির্দেশ দিয়েছে। বেইজিং বলেছে, এই নিষেধাজ্ঞা আন্তর্জাতিক আইন ও সম্পর্কের মৌলিক নীতির সঙ্গে সাংঘর্ষিক এবং চীনের সার্বভৌম স্বার্থে তা মানা যাবে না। যুক্তরাষ্ট্রের চাপ, ইরানের তেল বাণিজ্য এবং চীনের জ্বালানি নিরাপত্তা একসঙ্গে যুক্ত হওয়ায় ঘটনাটি বৈশ্বিক শক্তির রাজনীতিতে নতুন উত্তাপ তৈরি করেছে।",
                        'seo_keywords'     => "চীন, মার্কিন নিষেধাজ্ঞা, ইরান তেল, বিশ্ব রাজনীতি",
                        'published_at'     => "2026-05-03 15:29:00",
                    ],

                    (object) [
                        "category_name"    => "কূটনীতি",
                        'name'             => "পূর্ণাঙ্গ ভিসা চালুর পথে ঢাকা-দিল্লি সম্পর্ক",
                        'sub_heading'      => "টানাপোড়েন কাটিয়ে ভিসা কার্যক্রম স্বাভাবিক করার উদ্যোগ",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা ও দিল্লির সাম্প্রতিক টানাপোড়েন কাটাতে ভারত বাংলাদেশিদের জন্য পূর্ণাঙ্গ ভিসা কার্যক্রম চালুর পথে হাঁটছে।",
                        "body"             => "বাংলাদেশ ও ভারতের সাম্প্রতিক সম্পর্কের টানাপোড়েন কমাতে ভিসা কার্যক্রম পূর্ণমাত্রায় চালুর উদ্যোগ নেওয়া হয়েছে। বাংলাদেশ ইতোমধ্যে ভারতীয় নাগরিকদের জন্য সব ধরনের ভিসা সেবা পুনরায় চালু করেছে। ভারতও আগামী কয়েক সপ্তাহের মধ্যে বাংলাদেশিদের জন্য ভিসা কার্যক্রম স্বাভাবিক করার লক্ষ্য নিয়েছে বলে খবর এসেছে। কূটনীতিকরা মনে করছেন, মানুষে মানুষে যোগাযোগ, চিকিৎসা, শিক্ষা ও বাণিজ্যিক যাতায়াত পুনরায় সচল করা দুই দেশের সম্পর্ক স্বাভাবিক করার গুরুত্বপূর্ণ ধাপ।",
                        'seo_keywords'     => "ঢাকা-দিল্লি, ভারত ভিসা, বাংলাদেশ, কূটনীতি",
                        'published_at'     => "2026-05-02 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "কূটনীতি",
                        'name'             => "ইরানের জন্য সীমান্ত পথ খুলে দিলো পাকিস্তান",
                        'sub_heading'      => "",
                        'content_shoulder' => "আঞ্চলিক কূটনীতি",
                        'brief'            => "সমুদ্রবন্দর অবরোধের মুখে থাকা ইরানের জন্য নিজেদের স্থল বাণিজ্য পথ খুলে দিয়েছে পাকিস্তান, যুক্তরাষ্ট্রের প্রতিক্রিয়া নিয়ে প্রশ্ন উঠেছে।",
                        "body"             => "ইসরায়েল ও যুক্তরাষ্ট্রের সঙ্গে যুদ্ধ এবং সমুদ্রবন্দরে অবরোধের মুখে থাকা ইরানের জন্য পাকিস্তান স্থল বাণিজ্য পথ খুলে দিয়েছে। এর ফলে পাকিস্তানের ওপর দিয়ে ইরানের পণ্য পরিবহন বা ট্রানজিট সুবিধা পাওয়া যাবে। পদক্ষেপটিকে যুদ্ধে ক্ষতিগ্রস্ত ইরানের অর্থনীতির জন্য স্বস্তি হিসেবে দেখা হচ্ছে। তবে ওয়াশিংটন যখন তেহরানকে অর্থনৈতিক ও সামরিকভাবে চাপে রাখতে চাইছে, তখন ইসলামাবাদের এই সিদ্ধান্ত যুক্তরাষ্ট্র-পাকিস্তান সম্পর্কেও নতুন কূটনৈতিক হিসাব তৈরি করতে পারে।",
                        'seo_keywords'     => "পাকিস্তান, ইরান, সীমান্ত পথ, কূটনীতি",
                        'published_at'     => "2026-05-02 11:52:00",
                    ],

                    (object) [
                        "category_name"    => "কূটনীতি",
                        'name'             => "ইরানের শান্তি প্রস্তাবে পাকিস্তানের মধ্যস্থতার ইঙ্গিত",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইরানের ১৪ দফা শান্তি পরিকল্পনা পাকিস্তানের মাধ্যমে যুক্তরাষ্ট্রের কাছে পৌঁছেছে বলে খবর, মধ্যস্থতা কূটনীতিতে ইসলামাবাদের ভূমিকা বাড়ছে।",
                        "body"             => "ইরান যুক্তরাষ্ট্রের কাছে যে ১৪ দফা শান্তি পরিকল্পনা পাঠিয়েছে, সেটি পাকিস্তানের মধ্যস্থতায় পৌঁছেছে বলে বিভিন্ন প্রতিবেদনে বলা হয়েছে। তেহরান বলছে, পরিকল্পনার লক্ষ্য চাপিয়ে দেওয়া যুদ্ধের স্থায়ী অবসান এবং আঞ্চলিক স্থিতিশীলতা। পাকিস্তান একদিকে ইরানের প্রতিবেশী, অন্যদিকে যুক্তরাষ্ট্রের সঙ্গেও যোগাযোগ বজায় রাখে—এই অবস্থান তাকে সম্ভাব্য মধ্যস্থতাকারী হিসেবে গুরুত্বপূর্ণ করে তুলছে। তবে প্রস্তাব গ্রহণযোগ্য হবে কি না, তা নির্ভর করবে ওয়াশিংটন ও তেহরানের রাজনৈতিক সদিচ্ছার ওপর।",
                        'seo_keywords'     => "ইরান, পাকিস্তান, শান্তি প্রস্তাব, মধ্যস্থতা",
                        'published_at'     => "2026-05-03 08:17:00",
                    ],

                    (object) [
                        "category_name"    => "কূটনীতি",
                        'name'             => "জার্মানি থেকে সেনা প্রত্যাহার ইস্যুতে ন্যাটোর সঙ্গে আলোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জার্মানি থেকে মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তের বিস্তারিত জানতে যুক্তরাষ্ট্রের সঙ্গে কাজ করছে ন্যাটো, জোটে সমন্বয়ের চেষ্টা চলছে।",
                        "body"             => "জার্মানি থেকে পাঁচ হাজার মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তের পর ন্যাটো জানিয়েছে, বিষয়টির বিস্তারিত জানতে তারা যুক্তরাষ্ট্রের সঙ্গে কাজ করছে। জোটের মুখপাত্রের মতে, এই সিদ্ধান্ত ইউরোপের নিরাপত্তা কাঠামোতে নতুন দায়িত্ববণ্টনের প্রশ্ন তুলেছে। জার্মানি, পোল্যান্ড, ফ্রান্স ও যুক্তরাজ্যের মতো দেশগুলো প্রতিরক্ষা সমন্বয় বাড়ানোর প্রয়োজনীয়তা দেখছে। কূটনৈতিক পর্যায়ে এখন বড় প্রশ্ন হলো, এই সিদ্ধান্ত ন্যাটোর ঐক্য দুর্বল করবে নাকি ইউরোপকে নিজস্ব নিরাপত্তা সক্ষমতা বাড়াতে বাধ্য করবে।",
                        'seo_keywords'     => "ন্যাটো, জার্মানি, যুক্তরাষ্ট্র, প্রতিরক্ষা কূটনীতি",
                        'published_at'     => "2026-05-03 15:20:00",
                    ],

                    (object) [
                        "category_name"    => "কূটনীতি",
                        'name'             => "ইসরায়েলের কর্মকাণ্ডের নিন্দা জানালো ফ্রান্স",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পশ্চিম জেরুজালেমে ফ্রেঞ্চ ক্যাথলিক নানের ওপর হামলার পর ইসরায়েলের খ্রিষ্টানবিরোধী কর্মকাণ্ডের নিন্দা জানিয়েছে ফ্রান্স।",
                        "body"             => "পশ্চিম জেরুজালেমে এক ফ্রেঞ্চ ক্যাথলিক নানের ওপর হামলার ঘটনায় ফ্রান্স ইসরায়েলের একের পর এক খ্রিষ্টানবিরোধী কর্মকাণ্ডের নিন্দা জানিয়েছে। প্যারিসের প্রতিক্রিয়া শুধু একটি বিচ্ছিন্ন হামলার প্রতিবাদ নয়; এটি জেরুজালেমের ধর্মীয় সংখ্যালঘু, পবিত্র স্থান এবং ইউরোপ-ইসরায়েল কূটনৈতিক সম্পর্কের সংবেদনশীল প্রশ্নকে সামনে এনেছে। গাজা যুদ্ধ ও আঞ্চলিক উত্তেজনার মধ্যে ধর্মীয় নিরাপত্তা ইস্যু এখন আরও বড় কূটনৈতিক আলোচনায় পরিণত হচ্ছে।",
                        'seo_keywords'     => "ফ্রান্স, ইসরায়েল, জেরুজালেম, কূটনীতি",
                        'published_at'     => "2026-05-02 18:30:00",
                    ],

                    (object) [
                        "category_name"    => "সংঘাত",
                        'name'             => "লেবাননে ইসরায়েলি হামলায় যুদ্ধবিরতি প্রশ্নের মুখে",
                        'sub_heading'      => "দক্ষিণ লেবাননে প্রাণহানি বাড়ায় আঞ্চলিক সংঘাতের ঝুঁকি",
                        'content_shoulder' => "",
                        'brief'            => "দক্ষিণ লেবাননে ইসরায়েলি হামলায় ২৪ ঘণ্টায় ৪১ জন নিহতের খবরে যুদ্ধবিরতি বাস্তবতা ও সীমান্ত নিরাপত্তা আবার প্রশ্নের মুখে পড়েছে।",
                        "body"             => "দক্ষিণ লেবাননে ইসরায়েলি হামলায় ২৪ ঘণ্টায় অন্তত ৪১ জন নিহত হওয়ার খবর নতুন করে যুদ্ধবিরতি কার্যকারিতা নিয়ে প্রশ্ন তুলেছে। ইসরায়েলি বাহিনী হামলাকে নিরাপত্তা অভিযান বললেও লেবাননের স্থানীয় সূত্র ও মানবিক সংগঠনগুলো বেসামরিক ক্ষয়ক্ষতির কথা বলছে। হিজবুল্লাহর সঙ্গে ইসরায়েলের দীর্ঘ উত্তেজনা, সীমান্তে পাল্টাপাল্টি হামলা এবং মধ্যপ্রাচ্যের বৃহত্তর যুদ্ধপরিস্থিতি এই সংঘাতকে আরও বিস্তৃত করতে পারে বলে আশঙ্কা করা হচ্ছে।",
                        'seo_keywords'     => "লেবানন সংঘাত, ইসরায়েল, হিজবুল্লাহ, সীমান্ত হামলা",
                        'published_at'     => "2026-05-03 02:08:00",
                    ],

                    (object) [
                        "category_name"    => "সংঘাত",
                        'name'             => "গাজার ধ্বংসস্তূপ সরাতে বছরের পর বছর লাগার আশঙ্কা",
                        'sub_heading'      => "",
                        'content_shoulder' => "গাজা মানবিক সংকট",
                        'brief'            => "গাজায় ধ্বংসস্তূপের নিচে হাজারো মরদেহ থাকার আশঙ্কা এবং অপসারণে দীর্ঘ সময় লাগার তথ্য মানবিক বিপর্যয়কে আরও স্পষ্ট করছে।",
                        "body"             => "গাজা উপত্যকার বিপুল ধ্বংসস্তূপের নিচে এখনও হাজারো ফিলিস্তিনির মরদেহ চাপা পড়ে থাকার আশঙ্কা করা হচ্ছে। জাতিসংঘ সংশ্লিষ্ট তথ্য অনুযায়ী, ধ্বংসাবশেষ অপসারণের কাজ খুব ধীরগতিতে চলছে এবং বর্তমান গতি বজায় থাকলে পুরো এলাকা পরিষ্কার করতে বহু বছর লাগতে পারে। যুদ্ধের সরাসরি প্রাণহানির পাশাপাশি নিখোঁজ ব্যক্তিদের সন্ধান, নিরাপদ দাফন, রোগের ঝুঁকি ও পুনর্গঠন—সব মিলিয়ে গাজা সংঘাত এখন দীর্ঘমেয়াদি মানবিক বিপর্যয়ে রূপ নিয়েছে।",
                        'seo_keywords'     => "গাজা, মানবিক সংকট, ধ্বংসস্তূপ, সংঘাত",
                        'published_at'     => "2026-05-03 09:07:00",
                    ],

                    (object) [
                        "category_name"    => "সংঘাত",
                        'name'             => "যুক্তরাষ্ট্রের সঙ্গে ফের যুদ্ধের আশঙ্কা জানালো ইরান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইরানের সামরিক নেতৃত্ব সতর্ক করেছে, যুক্তরাষ্ট্র কোনো চুক্তিতে স্থায়ীভাবে প্রতিশ্রুতিবদ্ধ না থাকলে সংঘাত আবার শুরু হতে পারে।",
                        "body"             => "ইরানের সশস্ত্র বাহিনীর পক্ষ থেকে দাবি করা হয়েছে, যুক্তরাষ্ট্রের সঙ্গে যুদ্ধ আবার শুরু হতে পারে। তেহরানের অভিযোগ, ওয়াশিংটন কোনো চুক্তি বা প্রতিশ্রুতি স্থায়ীভাবে মানে না, তাই সাময়িক বিরতি দীর্ঘস্থায়ী শান্তির নিশ্চয়তা নয়। যুক্তরাষ্ট্র-ইসরায়েল হামলা, হরমুজ প্রণালি, অবরোধ এবং ইরানের পাল্টা অবস্থান—সব মিলিয়ে সংঘাতের আগুন পুরোপুরি নেভেনি। কূটনৈতিক প্রস্তাবের পাশাপাশি সামরিক প্রস্তুতিও জারি থাকায় পরিস্থিতি ভঙ্গুর রয়ে গেছে।",
                        'seo_keywords'     => "ইরান যুদ্ধ, যুক্তরাষ্ট্র, হরমুজ, সংঘাত",
                        'published_at'     => "2026-05-02 15:20:00",
                    ],

                    (object) [
                        "category_name"    => "সংঘাত",
                        'name'             => "ইয়েমেন উপকূলে তেলবাহী জাহাজ ছিনতাইয়ের খবর",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "হরমুজ সংকটের মধ্যেই ইয়েমেন উপকূলে এমটি ইউরেকা নামের তেলবাহী জাহাজ ছিনতাইয়ের খবর সামুদ্রিক নিরাপত্তা উদ্বেগ বাড়িয়েছে।",
                        "body"             => "ইয়েমেন উপকূলে অজ্ঞাত সশস্ত্র ব্যক্তিরা এমটি ইউরেকা নামের একটি তেলবাহী জাহাজ ছিনতাই করেছে বলে ইয়েমেনের কোস্টগার্ড জানিয়েছে। জাহাজটি নিয়ন্ত্রণে নেওয়ার পর সেটিকে এডেন উপসাগর হয়ে সোমালিয়ার জলসীমার দিকে নেওয়া হয়েছে বলে খবরে বলা হয়। হরমুজ প্রণালি সংকট, ইরান যুদ্ধ এবং মধ্যপ্রাচ্যের জ্বালানি পরিবহন অনিশ্চয়তার মধ্যে এই ঘটনা আন্তর্জাতিক সামুদ্রিক বাণিজ্য ও তেল সরবরাহের নিরাপত্তা ঝুঁকি আরও বাড়িয়েছে।",
                        'seo_keywords'     => "ইয়েমেন, তেলবাহী জাহাজ, এডেন উপসাগর, সংঘাত",
                        'published_at'     => "2026-05-02 19:00:00",
                    ],

                    (object) [
                        "category_name"    => "সংঘাত",
                        'name'             => "গাজাগামী ত্রাণবহর আটক ঘিরে ইসরায়েলে আদালত প্রক্রিয়া",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সুমুদ ফ্লোটিলার অ্যাক্টিভিস্টদের আদালতে তোলার ঘটনা গাজায় অবরোধ, ত্রাণ প্রবেশ ও মানবিক আইনের বিতর্ককে সামনে এনেছে।",
                        "body"             => "গাজাগামী সুমুদ ফ্লোটিলার দুই অ্যাক্টিভিস্টকে ইসরায়েলি আদালতে হাজির করার ঘটনা অবরোধ ও মানবিক সহায়তা প্রবেশের প্রশ্নকে নতুনভাবে আলোচনায় এনেছে। অ্যাক্টিভিস্টদের দাবি, তাঁদের উদ্দেশ্য ছিল গাজায় আটকে পড়া মানুষের কাছে ত্রাণ পৌঁছানো। ইসরায়েল নিরাপত্তা কারণ দেখালেও সমালোচকেরা বলছেন, যুদ্ধ পরিস্থিতিতে বেসামরিক জনগণের সহায়তা পাওয়ার অধিকার অস্বীকার করা যায় না। এই ঘটনা সংঘাতক্ষেত্রে মানবিক উদ্যোগের সীমা ও রাষ্ট্রীয় নিরাপত্তার ভারসাম্য নিয়ে বিতর্ক তৈরি করেছে।",
                        'seo_keywords'     => "সুমুদ ফ্লোটিলা, গাজা অবরোধ, ইসরায়েল, মানবিক সহায়তা",
                        'published_at'     => "2026-05-03 16:32:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব অর্থনীতি",
                        'name'             => "চীনা শোধনাগারে মার্কিন নিষেধাজ্ঞা অমান্যের নির্দেশ",
                        'sub_heading'      => "ইরানি তেল বাণিজ্য ঘিরে ওয়াশিংটন-বেইজিং অর্থনৈতিক টানাপোড়েন",
                        'content_shoulder' => "",
                        'brief'            => "ইরান থেকে তেল কেনার অভিযোগে চীনা শোধনাগারের ওপর মার্কিন নিষেধাজ্ঞা জারির পর তা অমান্যের নির্দেশ দিয়েছে বেইজিং।",
                        "body"             => "ইরান থেকে তেল কেনার অভিযোগে কয়েকটি চীনা তেল শোধনাগারের ওপর মার্কিন নিষেধাজ্ঞা দেওয়া হলে বেইজিং তা অমান্যের নির্দেশ জারি করেছে। চীনের বাণিজ্য মন্ত্রণালয় বলেছে, জাতিসংঘের অনুমোদন ছাড়া একতরফা নিষেধাজ্ঞা আন্তর্জাতিক বাণিজ্য ও তৃতীয় দেশের প্রতিষ্ঠানকে ক্ষতিগ্রস্ত করছে। চীন পশ্চিম এশিয়া থেকে বিপুল পরিমাণ জ্বালানি আমদানি করে, যার বড় অংশ ইরানের সঙ্গে যুক্ত। ফলে নিষেধাজ্ঞা শুধু কূটনীতি নয়, জ্বালানি নিরাপত্তা, বাজারদর ও বৈশ্বিক সরবরাহ ব্যবস্থার ওপরও প্রভাব ফেলতে পারে।",
                        'seo_keywords'     => "চীন, তেল শোধনাগার, মার্কিন নিষেধাজ্ঞা, বিশ্ব অর্থনীতি",
                        'published_at'     => "2026-05-03 15:29:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব অর্থনীতি",
                        'name'             => "স্পিরিট এয়ারলাইন্স বন্ধে জ্বালানি বাজারের চাপ সামনে",
                        'sub_heading'      => "",
                        'content_shoulder' => "জ্বালানি দামের ধাক্কা",
                        'brief'            => "ইরান যুদ্ধের প্রভাবে জেট ফুয়েলের দাম বাড়ায় সংকটে পড়া স্পিরিট এয়ারলাইন্স কার্যক্রম বন্ধের সিদ্ধান্ত নিয়েছে বলে খবর এসেছে।",
                        "body"             => "যুক্তরাষ্ট্রের স্বল্পমূল্যের বিমান সংস্থা স্পিরিট এয়ারলাইন্স জেট ফুয়েলের দাম বৃদ্ধি ও আর্থিক সংকটের কারণে কার্যক্রম বন্ধের সিদ্ধান্ত নিয়েছে বলে খবরে বলা হয়েছে। ইরান যুদ্ধের প্রভাবে বিশ্ববাজারে জ্বালানি তেলের দাম বেড়ে যাওয়ায় বিমান চলাচল খাতে ব্যয় হঠাৎ বেড়েছে। স্পিরিট একসময় যুক্তরাষ্ট্রের মোট ফ্লাইটের উল্লেখযোগ্য অংশ পরিচালনা করত এবং কম ভাড়ার বাজারে প্রতিযোগিতা তৈরি করেছিল। সংস্থাটির সংকট দেখাচ্ছে, ভূরাজনৈতিক সংঘাত কীভাবে সরাসরি পরিবহন, পর্যটন ও ভোক্তা ব্যয়ের ওপর চাপ তৈরি করে।",
                        'seo_keywords'     => "স্পিরিট এয়ারলাইন্স, জেট ফুয়েল, তেলের দাম, অর্থনীতি",
                        'published_at'     => "2026-05-02 15:51:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব অর্থনীতি",
                        'name'             => "চার দেশে ৮৬০ কোটি ডলারের অস্ত্র বিক্রির অনুমোদন যুক্তরাষ্ট্রের",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইসরায়েল, কাতার, কুয়েত ও আমিরাতের কাছে ৮৬০ কোটি ডলারের অস্ত্র বিক্রির অনুমোদন দিয়েছে যুক্তরাষ্ট্রের পররাষ্ট্র দপ্তর।",
                        "body"             => "ইসরায়েল, কাতার, কুয়েত ও সংযুক্ত আরব আমিরাতের কাছে ৮৬০ কোটি ডলারের অস্ত্র বিক্রির প্রস্তাব অনুমোদন করেছে যুক্তরাষ্ট্রের স্টেট ডিপার্টমেন্ট। ইরান যুদ্ধ ও আঞ্চলিক নিরাপত্তা সংকটের মধ্যে এই অস্ত্র বিক্রিকে প্রতিরক্ষা বাজারের বড় অর্থনৈতিক ঘটনা হিসেবে দেখা হচ্ছে। অস্ত্র বাণিজ্য শুধু সামরিক সক্ষমতা নয়, রপ্তানি আয়, প্রতিরক্ষা শিল্পের উৎপাদন, জোট রাজনীতি এবং মধ্যপ্রাচ্যের শক্তির ভারসাম্যের সঙ্গেও যুক্ত।",
                        'seo_keywords'     => "অস্ত্র বিক্রি, যুক্তরাষ্ট্র, মধ্যপ্রাচ্য, প্রতিরক্ষা অর্থনীতি",
                        'published_at'     => "2026-05-02 14:30:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব অর্থনীতি",
                        'name'             => "পাকিস্তানের স্থলপথে ইরানের বাণিজ্য নতুন স্বস্তি পেল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সমুদ্রবন্দর অবরোধে ক্ষতিগ্রস্ত ইরানের জন্য পাকিস্তানের স্থল ট্রানজিট সুবিধা বাণিজ্য ও সরবরাহ ব্যবস্থায় নতুন সুযোগ তৈরি করেছে।",
                        "body"             => "পাকিস্তান ইরানের জন্য স্থল বাণিজ্য পথ খুলে দেওয়ায় অবরোধে বিপর্যস্ত তেহরানের জন্য নতুন সরবরাহ পথ তৈরি হয়েছে। সমুদ্রবন্দর সীমিত বা অবরুদ্ধ থাকলে স্থল ট্রানজিট পণ্য পরিবহন, খাদ্য, শিল্প কাঁচামাল ও রপ্তানি আয়ের ক্ষেত্রে বড় ভূমিকা রাখতে পারে। তবে যুক্তরাষ্ট্রের নিষেধাজ্ঞা কাঠামো, আঞ্চলিক নিরাপত্তা ঝুঁকি এবং সীমান্ত ব্যবস্থাপনা এই বাণিজ্যপথের বাস্তব কার্যকারিতা নির্ধারণ করবে। ঘটনাটি দেখাচ্ছে, যুদ্ধ পরিস্থিতিতে প্রতিবেশী দেশের রুট অর্থনীতির জন্য কৌশলগত হয়ে ওঠে।",
                        'seo_keywords'     => "ইরান বাণিজ্য, পাকিস্তান, ট্রানজিট, বিশ্ব অর্থনীতি",
                        'published_at'     => "2026-05-02 11:52:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ব অর্থনীতি",
                        'name'             => "ইসরায়েলের যুদ্ধবিমান কেনা প্রতিরক্ষা বাজারে বড় লেনদেন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এফ-৩৫ ও এফ-১৫আই যুদ্ধবিমানের নতুন স্কোয়াড্রন কেনার ইসরায়েলি সিদ্ধান্ত যুক্তরাষ্ট্রের প্রতিরক্ষা শিল্পের জন্য বড় বাজার সংকেত।",
                        "body"             => "ইসরায়েল যুক্তরাষ্ট্র থেকে এফ-৩৫ ও এফ-১৫আই যুদ্ধবিমানের নতুন স্কোয়াড্রন কেনার পরিকল্পনা চূড়ান্ত করায় প্রতিরক্ষা বাজারে বড় লেনদেনের ইঙ্গিত মিলেছে। সাম্প্রতিক ইরান যুদ্ধের অভিজ্ঞতা এই সিদ্ধান্তে প্রভাব ফেলেছে বলে ইসরায়েলি পক্ষ জানিয়েছে। প্রতিরক্ষা অর্থনীতিতে এ ধরনের ক্রয় শুধু অস্ত্র সরবরাহ নয়; এর সঙ্গে দীর্ঘমেয়াদি রক্ষণাবেক্ষণ, প্রশিক্ষণ, প্রযুক্তি সহায়তা ও কৌশলগত নির্ভরতা যুক্ত থাকে। ফলে এটি সামরিক ও অর্থনৈতিক উভয় দিকেই গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "এফ-৩৫, এফ-১৫আই, প্রতিরক্ষা বাজার, ইসরায়েল",
                        'published_at'     => "2026-05-03 16:21:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক সংস্থা",
                        'name'             => "গাজা ধ্বংসস্তূপ নিয়ে জাতিসংঘ কর্মকর্তার সতর্কতা",
                        'sub_heading'      => "ধ্বংসাবশেষের নিচে মরদেহ ও পুনর্গঠনের দীর্ঘ সংকট",
                        'content_shoulder' => "",
                        'brief'            => "জাতিসংঘ সংশ্লিষ্ট কর্মকর্তার উদ্ধৃতিতে গাজায় ধ্বংসস্তূপের নিচে হাজারো মরদেহ থাকার আশঙ্কা মানবিক সংস্থাগুলোর উদ্বেগ বাড়িয়েছে।",
                        "body"             => "গাজা উপত্যকার ধ্বংসস্তূপের নিচে এখনও অন্তত আট হাজার ফিলিস্তিনির মরদেহ থাকতে পারে বলে জাতিসংঘ সংশ্লিষ্ট এক কর্মকর্তার উদ্ধৃতি দিয়ে খবর প্রকাশ হয়েছে। ধ্বংসাবশেষ অপসারণের গতি ধীর হওয়ায় উদ্ধার, শনাক্তকরণ ও দাফনপ্রক্রিয়া দীর্ঘ হচ্ছে। আন্তর্জাতিক সংস্থাগুলো বলছে, গাজায় শুধু ত্রাণ নয়; ভারী যন্ত্রপাতি, নিরাপদ করিডর, চিকিৎসা সহায়তা এবং পুনর্গঠন পরিকল্পনাও জরুরি। এই সংকট জাতিসংঘের মানবিক ব্যবস্থার ওপরও চাপ তৈরি করছে।",
                        'seo_keywords'     => "জাতিসংঘ, গাজা, মানবিক সংস্থা, ধ্বংসস্তূপ",
                        'published_at'     => "2026-05-03 09:07:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক সংস্থা",
                        'name'             => "ন্যাটো বলছে ইউরোপকে নিরাপত্তার দায় বেশি নিতে হবে",
                        'sub_heading'      => "",
                        'content_shoulder' => "জোট নিরাপত্তা বার্তা",
                        'brief'            => "জার্মানি থেকে মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তের পর ন্যাটো বলেছে, ইউরোপের প্রতিরক্ষা বিনিয়োগ ও দায়িত্ববণ্টন বাড়ানো দরকার।",
                        "body"             => "জার্মানি থেকে মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তের পর ন্যাটো জানিয়েছে, ইউরোপের জন্য প্রতিরক্ষা বিনিয়োগ বাড়ানো এবং যৌথ নিরাপত্তার দায় ভাগ করে নেওয়ার প্রয়োজনীয়তা আরও স্পষ্ট হয়েছে। ন্যাটোর মুখপাত্র যুক্তরাষ্ট্রের সঙ্গে সিদ্ধান্তের বিস্তারিত জানতে কাজ করার কথা বলেছেন। জোটের ৩২ সদস্যের মধ্যে নিরাপত্তা ব্যয়, সামরিক প্রস্তুতি এবং যুক্তরাষ্ট্রের ওপর নির্ভরতা নিয়ে যে দীর্ঘ বিতর্ক ছিল, এই সিদ্ধান্ত সেটিকে আরও তীব্র করেছে।",
                        'seo_keywords'     => "ন্যাটো, ইউরোপ, প্রতিরক্ষা ব্যয়, আন্তর্জাতিক সংস্থা",
                        'published_at'     => "2026-05-03 15:20:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক সংস্থা",
                        'name'             => "জাতিসংঘ অনুমোদনহীন নিষেধাজ্ঞার বিরোধিতা করল চীন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চীনের বাণিজ্য মন্ত্রণালয় বলেছে, জাতিসংঘের অনুমোদনহীন একতরফা মার্কিন নিষেধাজ্ঞা আন্তর্জাতিক আইনবিরোধী।",
                        "body"             => "চীনা তেল শোধনাগারের ওপর মার্কিন নিষেধাজ্ঞা অমান্যের নির্দেশ দিতে গিয়ে বেইজিং বলেছে, জাতিসংঘের অনুমোদন ছাড়া একতরফা নিষেধাজ্ঞা আন্তর্জাতিক আইনের ভিত্তি ও সম্পর্কের মৌলিক নীতির সঙ্গে যায় না। চীন দাবি করছে, এসব নিষেধাজ্ঞা তৃতীয় দেশের প্রতিষ্ঠান ও বৈধ বাণিজ্যকে বাধাগ্রস্ত করে। আন্তর্জাতিক সংস্থাভিত্তিক নিয়ম বনাম একতরফা নিষেধাজ্ঞা—এই বিতর্ক এখন ইরান তেল বাণিজ্যকে কেন্দ্র করে আবারও সামনে এসেছে।",
                        'seo_keywords'     => "জাতিসংঘ, নিষেধাজ্ঞা, চীন, আন্তর্জাতিক আইন",
                        'published_at'     => "2026-05-03 15:29:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক সংস্থা",
                        'name'             => "ইউরোপীয় নিরাপত্তায় জোটভিত্তিক সমন্বয়ের তাগিদ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মার্কিন সেনা কমানোর সম্ভাবনার মধ্যে জার্মানি ব্রিটেন, ফ্রান্স, পোল্যান্ড ও ইতালির সঙ্গে প্রতিরক্ষা সমন্বয় বাড়াতে চাইছে।",
                        "body"             => "মার্কিন সেনা প্রত্যাহারের সিদ্ধান্তের পর জার্মানি জানিয়েছে, নিজেদের নিরাপত্তা নিশ্চিত করতে ইউরোপীয় মিত্রদের সঙ্গে ঘনিষ্ঠভাবে কাজ করবে। ব্রিটেন, ফ্রান্স, পোল্যান্ড ও ইতালির সঙ্গে সমন্বয় বাড়ানোর কথা বলা হয়েছে। ন্যাটোর কাঠামোর ভেতরে থেকেও ইউরোপীয় দেশগুলো আলাদা প্রতিরক্ষা সক্ষমতা বাড়াতে চাইছে। আন্তর্জাতিক সংস্থা ও সামরিক জোটের ভূমিকা এখানে গুরুত্বপূর্ণ, কারণ ইউরোপের নিরাপত্তা এখন শুধু জাতীয় সিদ্ধান্ত নয়, সমন্বিত কৌশলের বিষয়।",
                        'seo_keywords'     => "ইউরোপীয় নিরাপত্তা, জার্মানি, ন্যাটো, প্রতিরক্ষা",
                        'published_at'     => "2026-05-02 20:00:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক সংস্থা",
                        'name'             => "মানবিক ত্রাণবহর আটক নিয়ে আন্তর্জাতিক সংগঠনগুলোর নজর",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গাজাগামী সুমুদ ফ্লোটিলার অ্যাক্টিভিস্টদের আদালতে তোলার ঘটনায় মানবিক ত্রাণ ও আন্তর্জাতিক আইন নিয়ে চাপ বাড়ছে।",
                        "body"             => "সুমুদ ফ্লোটিলার দুই অ্যাক্টিভিস্টকে ইসরায়েলি আদালতে হাজির করার ঘটনায় আন্তর্জাতিক মানবিক সংগঠনগুলোর দৃষ্টি আবার গাজার দিকে ফিরেছে। ত্রাণবহরটি গাজায় সহায়তা পৌঁছানোর চেষ্টা করছিল বলে আয়োজকেরা জানিয়েছেন। অবরোধ, নিরাপত্তা, সমুদ্রপথে ত্রাণ প্রবেশ এবং বেসামরিক মানুষের সহায়তা পাওয়ার অধিকার—এসব প্রশ্ন এখন আন্তর্জাতিক সংস্থা, মানবাধিকার সংগঠন ও কূটনৈতিক মহলে আলোচনার বিষয়। ঘটনাটি যুদ্ধক্ষেত্রে মানবিক করিডরের প্রয়োজনীয়তাও সামনে এনেছে।",
                        'seo_keywords'     => "মানবিক সংস্থা, সুমুদ ফ্লোটিলা, গাজা, আন্তর্জাতিক আইন",
                        'published_at'     => "2026-05-03 16:32:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যবসা",
                        'name'             => "দেশে লিড সনদ পাওয়া পোশাক কারখানা এখন ২৮৪টি",
                        'sub_heading'      => "সবুজ শিল্পায়নে নতুন মাইলফলক ছুঁল তৈরি পোশাক খাত",
                        'content_shoulder' => "",
                        'brief'            => "তৈরি পোশাক খাতে নতুন চার কারখানা লিড সনদ পাওয়ায় দেশে পরিবেশবান্ধব পোশাক কারখানার সংখ্যা বেড়ে ২৮৪টিতে দাঁড়িয়েছে।",
                        "body"             => "বাংলাদেশের তৈরি পোশাক খাতে পরিবেশবান্ধব উৎপাদনের অগ্রগতি আরও দৃশ্যমান হয়েছে। নতুন করে চারটি কারখানা লিড সনদ অর্জন করায় দেশে লিড সনদপ্রাপ্ত পোশাক কারখানার সংখ্যা দাঁড়িয়েছে ২৮৪টিতে। এর মধ্যে প্লাটিনাম ও গোল্ড রেটিং পাওয়া কারখানার সংখ্যাও উল্লেখযোগ্য। আন্তর্জাতিক ক্রেতাদের কাছে টেকসই উৎপাদন, জ্বালানি সাশ্রয়, নিরাপদ কর্মপরিবেশ ও কম কার্বন নিঃসরণের গুরুত্ব বাড়তে থাকায় এই অর্জন বাংলাদেশের রপ্তানি খাতের প্রতিযোগিতা শক্তিশালী করতে পারে।",
                        'seo_keywords'     => "লিড সনদ, পোশাক খাত, সবুজ শিল্প, ব্যবসা",
                        'published_at'     => "2026-05-03 14:40:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যবসা",
                        'name'             => "বৃষ্টির প্রভাবে রাজধানীর বাজারে সবজির দাম বেড়েছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "বাজারে দামের চাপ",
                        'brief'            => "টানা বৃষ্টি ও পরিবহন ব্যয় বাড়ার কারণে রাজধানীর বাজারে সবজি সরবরাহ কমেছে, ক্রেতাদের খরচও সপ্তাহের ব্যবধানে বেড়েছে।",
                        "body"             => "রাজধানীর বিভিন্ন কাঁচাবাজারে টানা বৃষ্টির প্রভাব পড়েছে নিত্যপণ্যের দামে। বিক্রেতারা বলছেন, বৃষ্টির কারণে উৎপাদন এলাকা থেকে ঢাকায় সবজি আনা কঠিন হয়েছে এবং পরিবহন খরচও বেড়েছে। ফলে অনেক সবজির দাম কেজিতে ১০ থেকে ১৫ টাকা পর্যন্ত বেড়েছে। ক্রেতাদের অভিযোগ, আয় না বাড়লেও বাজার ব্যয় প্রতিদিন বাড়ছে। বৃষ্টি, জ্বালানি ব্যয় ও সরবরাহ সংকট মিলিয়ে খুচরা বাজারে অস্থিরতা তৈরি হয়েছে।",
                        'seo_keywords'     => "সবজির দাম, বাজার দর, বৃষ্টি, নিত্যপণ্য",
                        'published_at'     => "2026-05-01 17:12:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যবসা",
                        'name'             => "মেহেরপুরে ৫৫ কোটি টাকার লিচু বাণিজ্যের সম্ভাবনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চাহিদা ও উৎপাদন বাড়ায় মেহেরপুরে চলতি মৌসুমে প্রায় ৫৫ কোটি টাকার লিচু বিক্রির আশা করছেন স্থানীয় চাষি ও ব্যবসায়ীরা।",
                        "body"             => "মেহেরপুরের লিচু স্বাদ ও গুণগত মানের কারণে দেশের বিভিন্ন অঞ্চলে জনপ্রিয়। চলতি মৌসুমে আবহাওয়া অনুকূলে থাকায় গাছে ফলন ভালো হয়েছে এবং আবাদও বেড়েছে। কৃষি বিভাগের তথ্য অনুযায়ী, গত বছরের তুলনায় এবার লিচুর আবাদ প্রায় ৮০০ হেক্টরে পৌঁছেছে। বাজারদর ভালো থাকলে চাষি ও ব্যবসায়ীরা প্রায় ৫৫ কোটি টাকার লিচু বিক্রির আশা করছেন। তবে পরিবহন, সংরক্ষণ ও বাজার ব্যবস্থাপনা উন্নত না হলে লাভের একটি অংশ কমে যেতে পারে।",
                        'seo_keywords'     => "মেহেরপুর লিচু, কৃষি ব্যবসা, ফল বাজার, বাণিজ্য",
                        'published_at'     => "2026-05-03 15:15:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যবসা",
                        'name'             => "নতুন বাজেটে করের বাড়তি চাপ নিয়ে ব্যবসায় উদ্বেগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নতুন বাজেটে উত্তরাধিকার কর ও অন্যান্য কর কাঠামো নিয়ে আলোচনার কারণে ব্যবসায়ী ও সাধারণ করদাতাদের মধ্যে খরচ বাড়ার শঙ্কা তৈরি হয়েছে।",
                        "body"             => "আসন্ন বাজেটকে ঘিরে কর কাঠামোতে সম্ভাব্য পরিবর্তন নিয়ে ব্যবসায়ী মহলে উদ্বেগ দেখা দিয়েছে। উত্তরাধিকার করের নতুন স্তর, লেনদেনভিত্তিক কর ও বাড়তি করভার নিয়ে আলোচনা বিনিয়োগ সিদ্ধান্তে প্রভাব ফেলতে পারে বলে ব্যবসায়ীরা মনে করছেন। তাঁদের মতে, উৎপাদন ব্যয়, ব্যাংক ঋণের সুদ ও বাজারে চাহিদা কমার সঙ্গে করের চাপ বাড়লে নতুন বিনিয়োগ আরও ধীর হতে পারে। তাই করনীতি সহজ, পূর্বানুমেয় ও বিনিয়োগবান্ধব করার দাবি উঠছে।",
                        'seo_keywords'     => "বাজেট, করনীতি, ব্যবসা, বিনিয়োগ",
                        'published_at'     => "2026-04-30 10:30:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যবসা",
                        'name'             => "বিদেশি ঋণ পরিশোধে চাপ বাড়ছে অর্থনীতিতে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চলতি অর্থবছরের প্রথম ৯ মাসে বিদেশি ঋণ পরিশোধ সাড়ে তিন বিলিয়ন ডলার ছাড়ানোয় বৈদেশিক মুদ্রা ব্যবস্থাপনায় চাপ বেড়েছে।",
                        "body"             => "বিদেশি ঋণ পরিশোধের পরিমাণ দ্রুত বাড়ায় দেশের বৈদেশিক খাতে চাপ তৈরি হচ্ছে। অর্থনীতিবিদদের মতে, উন্নয়ন প্রকল্প, বাজেট সহায়তা ও পূর্বের ঋণের কিস্তি পরিশোধের সময় ঘনিয়ে আসায় বৈদেশিক মুদ্রার চাহিদা বেড়েছে। রপ্তানি আয়, রেমিট্যান্স ও রিজার্ভ ব্যবস্থাপনা ভালো না হলে এই চাপ আরও বাড়তে পারে। ঋণ পরিশোধের পাশাপাশি নতুন ঋণের শর্ত, সুদের হার ও প্রকল্পের উৎপাদনশীলতা এখন অর্থনীতির জন্য গুরুত্বপূর্ণ প্রশ্ন হয়ে উঠেছে।",
                        'seo_keywords'     => "বিদেশি ঋণ, অর্থনীতি, রিজার্ভ, ব্যবসা",
                        'published_at'     => "2026-05-03 10:20:00",
                    ],

                    (object) [
                        "category_name"    => "শেয়ার বাজার",
                        'name'             => "তালিকাভুক্ত সাত ব্যাংকের মুনাফা ও লভ্যাংশ ঘোষণা",
                        'sub_heading'      => "সাউথইস্ট, যমুনা ও ব্যাংক এশিয়ার মুনাফায় বড় প্রবৃদ্ধি",
                        'content_shoulder' => "",
                        'brief'            => "শেয়ারবাজারে তালিকাভুক্ত সাত ব্যাংক ২০২৫ সালের মুনাফা ও লভ্যাংশ ঘোষণা করেছে, বেশির ভাগ ব্যাংকের মুনাফা বেড়েছে।",
                        "body"             => "শেয়ারবাজারে তালিকাভুক্ত আরও সাতটি ব্যাংক ২০২৫ সাল শেষে নিট মুনাফা ও লভ্যাংশের তথ্য প্রকাশ করেছে। ব্যাংকগুলোর মধ্যে সাউথইস্ট, যমুনা ও ব্যাংক এশিয়ার মুনাফায় বড় প্রবৃদ্ধি দেখা গেছে। যমুনা ব্যাংক ২৯ শতাংশ নগদ লভ্যাংশ ঘোষণা করেছে, যা ব্যাংকগুলোর মধ্যে বেশি আলোচিত হয়েছে। তবে ইসলামী ব্যাংক ও স্ট্যান্ডার্ড ইসলামী ব্যাংক শেয়ারধারীদের লভ্যাংশ দেয়নি। ব্যাংক খাতের এই ফলাফল বিনিয়োগকারীদের আগ্রহ ও সতর্কতা—দুটিই বাড়িয়েছে।",
                        'seo_keywords'     => "শেয়ারবাজার, ব্যাংক মুনাফা, লভ্যাংশ, ডিএসই",
                        'published_at'     => "2026-05-02 06:30:00",
                    ],

                    (object) [
                        "category_name"    => "শেয়ার বাজার",
                        'name'             => "ইসলামী ব্যাংক ‘এ’ থেকে ‘জেড’ ক্যাটাগরিতে নেমেছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "বিনিয়োগকারীর সতর্কতা",
                        'brief'            => "লভ্যাংশ না দেওয়াসহ আর্থিক দুর্বলতার কারণে ইসলামী ব্যাংককে শেয়ারবাজারে ‘এ’ থেকে ‘জেড’ ক্যাটাগরিতে নামানো হয়েছে।",
                        "body"             => "পুঁজিবাজার নিয়ন্ত্রক সংস্থা ইসলামী ব্যাংক বাংলাদেশ পিএলসিকে ‘এ’ ক্যাটাগরি থেকে ‘জেড’ ক্যাটাগরিতে নামিয়েছে। ব্যাংকটির খেলাপি ঋণ, নিরাপত্তা সঞ্চিতি ও লভ্যাংশ না দেওয়ার মতো বিষয় বিনিয়োগকারীদের নজরে এসেছে। শেয়ারবাজারে কোনো কোম্পানি জেড ক্যাটাগরিতে গেলে সাধারণত বিনিয়োগকারীরা ঝুঁকি বেশি বিবেচনা করেন। ফলে ব্যাংকটির শেয়ারের ওপর চাপ এবং সামগ্রিক ব্যাংক খাতে আস্থার প্রশ্ন নতুন করে সামনে এসেছে।",
                        'seo_keywords'     => "ইসলামী ব্যাংক, জেড ক্যাটাগরি, শেয়ারবাজার, বিএসইসি",
                        'published_at'     => "2026-04-30 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "শেয়ার বাজার",
                        'name'             => "পুঁজিবাজারের অর্থায়ন মোট স্থায়ী মূলধনের মাত্র ৬ শতাংশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশে দীর্ঘমেয়াদি শিল্প অর্থায়নে পুঁজিবাজারের অবদান খুব কম, মোট স্থায়ী মূলধনের তুলনায় তা মাত্র ৬ শতাংশের কাছাকাছি।",
                        "body"             => "দেশের শিল্পায়নে দীর্ঘমেয়াদি মূলধনের বড় অংশ এখনও ব্যাংকনির্ভর। পুঁজিবাজার থেকে ইস্যুকৃত মূলধনের পরিমাণ ১ লাখ কোটি টাকা ছাড়ালেও মোট স্থায়ী মূলধনের তুলনায় এর অবদান প্রায় ৬ শতাংশ। বাজারসংশ্লিষ্টরা বলছেন, নতুন আইপিও কমে যাওয়া, বিনিয়োগকারীর আস্থাহীনতা, কারসাজির অভিযোগ ও নিম্নমুখী বাজারের কারণে ভালো কোম্পানি তালিকাভুক্ত হতে আগ্রহী নয়। ফলে শিল্প অর্থায়নের জন্য ব্যাংকের ওপর চাপ বাড়ছে।",
                        'seo_keywords'     => "পুঁজিবাজার, শিল্প অর্থায়ন, আইপিও, বিনিয়োগ",
                        'published_at'     => "2026-05-03 02:18:00",
                    ],

                    (object) [
                        "category_name"    => "শেয়ার বাজার",
                        'name'             => "রেকিট বেনকিজার প্রতিশেয়ারে ১৭৩ টাকা লভ্যাংশ দেবে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শেয়ারবাজারে তালিকাভুক্ত রেকিট বেনকিজার ২০২৫ সালের জন্য শেয়ারপ্রতি ১৭৩ টাকা নগদ লভ্যাংশ দেওয়ার ঘোষণা দিয়েছে।",
                        "body"             => "তালিকাভুক্ত বহুজাতিক কোম্পানি রেকিট বেনকিজার ২০২৫ সালের আর্থিক ফলাফলের ভিত্তিতে শেয়ারপ্রতি ১৭৩ টাকা নগদ লভ্যাংশ ঘোষণা করেছে। কোম্পানিটির শেয়ারপ্রতি মুনাফা ১৭২ টাকা ৯৩ পয়সা এবং শেয়ারপ্রতি সম্পদমূল্য ১৮৭ টাকা ৫৮ পয়সা হয়েছে বলে ডিএসই সূত্রে জানানো হয়েছে। উচ্চ লভ্যাংশের ঘোষণায় বিনিয়োগকারীদের আগ্রহ বাড়তে পারে, যদিও এ ধরনের কোম্পানির শেয়ারদর সাধারণত আগেই প্রত্যাশা প্রতিফলিত করে।",
                        'seo_keywords'     => "রেকিট বেনকিজার, লভ্যাংশ, ডিএসই, শেয়ার",
                        'published_at'     => "2026-05-03 11:00:00",
                    ],

                    (object) [
                        "category_name"    => "শেয়ার বাজার",
                        'name'             => "ডিএসইতে সূচক ইতিবাচক, লেনদেনে সতর্ক বিনিয়োগকারী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা স্টক এক্সচেঞ্জে দিনের লেনদেনের শুরুতে সূচক ইতিবাচক থাকলেও বিনিয়োগকারীদের মধ্যে সতর্ক অবস্থান দেখা গেছে।",
                        "body"             => "ঢাকা স্টক এক্সচেঞ্জে দিনের লেনদেনের শুরুতে প্রধান সূচক ডিএসইএক্স কিছুটা বেড়েছে। একই সময়ে শরিয়াহ সূচক ও ডিএস৩০ সূচকেও ইতিবাচক প্রবণতা দেখা যায়। তবে বাজারসংশ্লিষ্টরা বলছেন, সাম্প্রতিক অস্থিরতা, কোম্পানির আর্থিক ফলাফল এবং ব্যাংক খাতের খবর বিনিয়োগকারীদের সতর্ক রেখেছে। লেনদেন বাড়লেও বাজারের স্থিতিশীলতা নির্ভর করছে ভালো মৌলভিত্তির শেয়ারে দীর্ঘমেয়াদি আগ্রহ তৈরির ওপর।",
                        'seo_keywords'     => "ডিএসই, সূচক, লেনদেন, বিনিয়োগকারী",
                        'published_at'     => "2026-05-03 11:42:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যাংকিং",
                        'name'             => "বন্ধ কারখানা চালু করতে বিশেষ তহবিল আনছে বাংলাদেশ ব্যাংক",
                        'sub_heading'      => "চলতি মূলধন সহায়তায় উৎপাদন ও কর্মসংস্থান ফেরানোর উদ্যোগ",
                        'content_shoulder' => "",
                        'brief'            => "বাজার চাহিদা ও ক্রয়াদেশ থাকা বন্ধ কারখানা চালু করতে কম সুদের বিশেষ তহবিল গঠনের উদ্যোগ নিয়েছে বাংলাদেশ ব্যাংক।",
                        "body"             => "বন্ধ হয়ে যাওয়া শিল্পকারখানা চালু করতে বাংলাদেশ ব্যাংক বিশেষ তহবিল গঠনের উদ্যোগ নিয়েছে। যেসব কারখানার বাজার চাহিদা ও ক্রয়াদেশ আছে, তাদের চলতি মূলধন সহায়তা দিয়ে আবার উৎপাদনে ফেরানোর পরিকল্পনা করা হচ্ছে। ব্যাংকগুলোর কাছে বন্ধ ও আংশিক বন্ধ কারখানার তালিকা চাওয়া হয়েছে। কারখানাগুলো ঋণখেলাপি হলে সহজ শর্তে পুনঃতফসিলের সুবিধা দেওয়া হতে পারে। এই উদ্যোগ সফল হলে উৎপাদন, রপ্তানি ও কর্মসংস্থানে ইতিবাচক প্রভাব পড়বে।",
                        'seo_keywords'     => "বাংলাদেশ ব্যাংক, বিশেষ তহবিল, বন্ধ কারখানা, ব্যাংকিং",
                        'published_at'     => "2026-05-03 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যাংকিং",
                        'name'             => "২০২৫ সালে কয়েকটি ব্যাংকের মুনাফায় বড় উল্লম্ফন",
                        'sub_heading'      => "",
                        'content_shoulder' => "ব্যাংক মুনাফা",
                        'brief'            => "পূবালী, ডাচ্-বাংলা ও প্রাইমসহ কয়েকটি ব্যাংক ২০২৫ সালে বড় মুনাফা দেখিয়েছে, তবে ঋণ প্রবৃদ্ধি নিয়ে উদ্বেগ রয়ে গেছে।",
                        "body"             => "২০২৫ সালের আর্থিক ফলাফলে দেশের কয়েকটি বেসরকারি ব্যাংক বড় মুনাফা দেখিয়েছে। পূবালী ব্যাংক হাজার কোটি টাকার মুনাফার ক্লাবে যুক্ত হয়েছে, ডাচ্-বাংলা ও প্রাইম ব্যাংকের মুনাফাও উল্লেখযোগ্যভাবে বেড়েছে। ব্যাংক কর্মকর্তারা সুশাসন, সুদ আয়, সরকারি বিল-বন্ডে বিনিয়োগ এবং সঞ্চিতি সুবিধাকে মুনাফা বৃদ্ধির কারণ হিসেবে দেখছেন। তবে অর্থনীতিবিদরা বলছেন, ঋণ প্রবৃদ্ধি কম থাকলে শুধু হিসাবের মুনাফা দিয়ে ব্যাংক খাতের দীর্ঘমেয়াদি শক্তি বোঝা যাবে না।",
                        'seo_keywords'     => "ব্যাংক মুনাফা, পূবালী ব্যাংক, প্রাইম ব্যাংক, ব্যাংকিং",
                        'published_at'     => "2026-05-03 09:00:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যাংকিং",
                        'name'             => "সিটি ব্যাংকের প্রথম প্রান্তিকে নিট মুনাফা বেড়েছে ১৬২ শতাংশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চলতি বছরের প্রথম প্রান্তিকে সিটি ব্যাংকের নিট মুনাফা ২৪১ কোটি টাকায় উঠেছে, আগের বছরের একই সময়ে ছিল ৯২ কোটি টাকা।",
                        "body"             => "সিটি ব্যাংক পিএলসি ২০২৬ সালের প্রথম প্রান্তিকে ২৪১ কোটি টাকা নিট মুনাফা করেছে। আগের বছরের একই সময়ে ব্যাংকটির নিট মুনাফা ছিল ৯২ কোটি টাকা। সুদ আয়, বিনিয়োগ আয়, বৈদেশিক মুদ্রা বিনিময় আয়, কার্ড ফি ও ট্রেড কমিশন বৃদ্ধিতে এই প্রবৃদ্ধি এসেছে। একই সঙ্গে কস্ট-টু-ইনকাম অনুপাত কমেছে। তবে ব্যাংকটির শীর্ষ নির্বাহী ঋণ প্রবৃদ্ধি কমে যাওয়াকে খাতের জন্য দুশ্চিন্তার বিষয় হিসেবে দেখেছেন।",
                        'seo_keywords'     => "সিটি ব্যাংক, নিট মুনাফা, ব্যাংকিং, প্রথম প্রান্তিক",
                        'published_at'     => "2026-05-01 01:52:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যাংকিং",
                        'name'             => "সম্মিলিত ইসলামী ব্যাংক উদ্যোগ অনিশ্চয়তায়",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দুর্বল পাঁচ ইসলামি ব্যাংক একীভূত করে সম্মিলিত ইসলামী ব্যাংক গঠনের উদ্যোগ নতুন করে অনিশ্চয়তার মুখে পড়েছে।",
                        "body"             => "দেশের ব্যাংকিং খাতের আলোচিত একীভূত উদ্যোগ সম্মিলিত ইসলামী ব্যাংকের ভবিষ্যৎ নিয়ে অনিশ্চয়তা তৈরি হয়েছে। দুর্বল পাঁচটি ইসলামি ব্যাংককে একীভূত করার পরিকল্পনা থাকলেও প্রত্যাশিত আর্থিক স্থিতি আসেনি এবং অংশীদার ব্যাংকগুলোর মধ্যে কেউ কেউ সরে দাঁড়ানোর আগ্রহ দেখাচ্ছে। ব্যাংকিং খাতের বিশেষজ্ঞরা বলছেন, দুর্বল ব্যাংক একীভূত করতে হলে মূলধন, আমানতকারীর আস্থা, সুশাসন ও নিয়ন্ত্রক সিদ্ধান্ত—সবকিছুর স্পষ্ট রূপরেখা দরকার।",
                        'seo_keywords'     => "ইসলামী ব্যাংক, ব্যাংক একীভূতকরণ, সুশাসন, ব্যাংকিং",
                        'published_at'     => "2026-05-02 08:00:00",
                    ],

                    (object) [
                        "category_name"    => "ব্যাংকিং",
                        'name'             => "ডলারের দাম নিয়ন্ত্রণে ফরওয়ার্ড বুকিংয়ে কড়াকড়ি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বিনিময় হারজনিত ঝুঁকি কমাতে গ্রাহকের প্রকৃত প্রয়োজনের বিপরীতে ফরওয়ার্ড বৈদেশিক মুদ্রা বিক্রির নির্দেশ দেওয়া হয়েছে।",
                        "body"             => "ডলারের বাজারে অস্থিরতা নিয়ন্ত্রণে ব্যাংকগুলোর ফরওয়ার্ড বৈদেশিক মুদ্রা লেনদেনে সতর্কতা বাড়ানো হয়েছে। বাংলাদেশ ব্যাংকের নির্দেশনা অনুযায়ী, অনুমোদিত ডিলার ব্যাংকগুলো শুধু গ্রাহকের প্রকৃত প্রয়োজনের বিপরীতে ফরওয়ার্ড বিক্রয় করতে পারবে। আমদানি, ঋণ পরিশোধ বা বৈধ বৈদেশিক দায় মেটানোর প্রয়োজন ছাড়া এই সুবিধা ব্যবহার করা যাবে না। এতে মুদ্রা বাজারে জল্পনা কমানো এবং ব্যবসার বিনিময় হারজনিত ঝুঁকি নিয়ন্ত্রণের লক্ষ্য রয়েছে।",
                        'seo_keywords'     => "ডলার, ফরওয়ার্ড বুকিং, বাংলাদেশ ব্যাংক, বৈদেশিক মুদ্রা",
                        'published_at'     => "2026-04-18 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "কর্পোরেট",
                        'name'             => "বিএটিবির ঘোষিত ৩০ শতাংশ নগদ লভ্যাংশ অনুমোদন",
                        'sub_heading'      => "অনলাইন এজিএমে শেয়ারধারীদের সম্মতি পেল কোম্পানি",
                        'content_shoulder' => "",
                        'brief'            => "ব্রিটিশ আমেরিকান টোব্যাকো বাংলাদেশ ২০২৫ সালের জন্য ঘোষিত ৩০ শতাংশ নগদ লভ্যাংশ এজিএমে অনুমোদন পেয়েছে।",
                        "body"             => "ব্রিটিশ আমেরিকান টোব্যাকো বাংলাদেশ ২০২৫ সালের জন্য ঘোষিত ৩০ শতাংশ নগদ লভ্যাংশ শেয়ারধারীদের অনুমোদন পেয়েছে। অনলাইনে অনুষ্ঠিত কোম্পানির ৫৩তম বার্ষিক সাধারণ সভায় এই সিদ্ধান্ত নেওয়া হয়। তালিকাভুক্ত বড় কর্পোরেট কোম্পানিগুলোর লভ্যাংশ ঘোষণা বিনিয়োগকারীদের আস্থা ও নগদ রিটার্নের প্রত্যাশার সঙ্গে যুক্ত। তবে কর, বাজার চাহিদা ও নীতিগত পরিবর্তন ভবিষ্যৎ মুনাফায় প্রভাব ফেলতে পারে বলে বিশ্লেষকেরা মনে করেন।",
                        'seo_keywords'     => "বিএটিবি, নগদ লভ্যাংশ, এজিএম, কর্পোরেট",
                        'published_at'     => "2026-05-02 16:34:00",
                    ],

                    (object) [
                        "category_name"    => "কর্পোরেট",
                        'name'             => "৯ মাসে ৬৪৩ কোটি টাকা মুনাফা করেছে ওয়ালটন",
                        'sub_heading'      => "",
                        'content_shoulder' => "কর্পোরেট ফলাফল",
                        'brief'            => "ওয়ালটনের নয় মাসের আর্থিক প্রতিবেদনে মুনাফা ৬৪৩ কোটি টাকা হলেও ভ্যাট বৃদ্ধির কারণে লাভে কিছু চাপ দেখা গেছে।",
                        "body"             => "ওয়ালটন হাইটেক ইন্ডাস্ট্রিজ চলতি হিসাব বছরের প্রথম ৯ মাসে ৬৪৩ কোটি টাকা মুনাফা করেছে। কোম্পানির আয় বাড়লেও ভ্যাট বৃদ্ধির কারণে আগের বছরের তুলনায় মুনাফায় কিছুটা চাপ পড়েছে। তবে পরিচালন নগদ প্রবাহে উন্নতি কোম্পানির আর্থিক সক্ষমতার জন্য ইতিবাচক সংকেত। ইলেকট্রনিকস ও হোম অ্যাপ্লায়েন্স খাতে দেশীয় ব্র্যান্ড হিসেবে ওয়ালটনের ফলাফল কর্পোরেট খাতের উৎপাদন ব্যয়, ভোক্তা চাহিদা ও রপ্তানি সম্ভাবনার সঙ্গে সম্পর্কিত।",
                        'seo_keywords'     => "ওয়ালটন, কর্পোরেট মুনাফা, ভ্যাট, আর্থিক প্রতিবেদন",
                        'published_at'     => "2026-04-29 13:00:00",
                    ],

                    (object) [
                        "category_name"    => "কর্পোরেট",
                        'name'             => "যুক্তরাষ্ট্রের ব্যবসাপ্রতিষ্ঠান বাংলাদেশের উন্নয়ন প্রকল্পে আগ্রহী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশের অবকাঠামো ও উন্নয়ন প্রকল্পে অংশ নিতে যুক্তরাষ্ট্রের ব্যবসাপ্রতিষ্ঠানগুলো আগ্রহ দেখিয়েছে বলে আলোচনায় উঠে এসেছে।",
                        "body"             => "বাংলাদেশের উন্নয়ন প্রকল্পে কাজ করার আগ্রহ জানিয়েছে যুক্তরাষ্ট্রের ব্যবসাপ্রতিষ্ঠানগুলো। অবকাঠামো, প্রযুক্তি, জ্বালানি, লজিস্টিকস ও সেবা খাতে বিদেশি কোম্পানির অংশগ্রহণ বাড়লে বিনিয়োগ, দক্ষতা ও প্রযুক্তি স্থানান্তরের সুযোগ তৈরি হতে পারে। ব্যবসায়ী মহল মনে করছে, নীতি স্থিতিশীলতা, স্বচ্ছ দরপত্র, দ্রুত অনুমোদন ও মুদ্রা ব্যবস্থাপনা নিশ্চিত করা গেলে বড় বিদেশি কর্পোরেট বিনিয়োগ আকর্ষণ সহজ হবে।",
                        'seo_keywords'     => "যুক্তরাষ্ট্র, কর্পোরেট বিনিয়োগ, উন্নয়ন প্রকল্প, বাংলাদেশ",
                        'published_at'     => "2026-04-27 17:30:00",
                    ],

                    (object) [
                        "category_name"    => "কর্পোরেট",
                        'name'             => "কর না কমালে বিনিয়োগে গতি আসবে না, ব্যবসায়ীদের মত",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মেট্রো চেম্বার ও ব্যবসায়ী মহলের আলোচনায় করভার কমানো না হলে কর্পোরেট বিনিয়োগ আরও কমে যাওয়ার আশঙ্কা জানানো হয়েছে।",
                        "body"             => "দেশে বিনিয়োগে স্থবিরতা কাটাতে কর্পোরেট করভার কমানো এবং নীতিগত অনিশ্চয়তা দূর করার দাবি জানিয়েছেন ব্যবসায়ী প্রতিনিধিরা। তাঁদের মতে, করপোরেট করহার ধাপে ধাপে কমলেও মোট করভার এখনও অনেক বেশি। এর সঙ্গে উচ্চ সুদহার, জ্বালানি ব্যয় ও বাজারে চাহিদা কমার চাপ যুক্ত হয়েছে। নতুন করে লেনদেন কর বাড়ানো হলে কোম্পানির চলতি মূলধনে চাপ তৈরি হবে এবং শিল্প সম্প্রসারণের সিদ্ধান্ত পিছিয়ে যেতে পারে।",
                        'seo_keywords'     => "কর্পোরেট কর, বিনিয়োগ, ব্যবসায়ী, করনীতি",
                        'published_at'     => "2026-04-20 03:30:00",
                    ],

                    (object) [
                        "category_name"    => "কর্পোরেট",
                        'name'             => "পোশাক খাতে বৈশ্বিক সংকট ও বাণিজ্যনীতির চাপ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "রপ্তানিমুখী পোশাক খাতে বৈশ্বিক চাহিদা, বাণিজ্যনীতি ও উৎপাদন ব্যয়ের চাপ কর্পোরেট পরিকল্পনায় নতুন ঝুঁকি তৈরি করছে।",
                        "body"             => "বাংলাদেশের তৈরি পোশাক খাত বৈশ্বিক বাজারে চাহিদা কমা, বাণিজ্যনীতির পরিবর্তন, জ্বালানি ও কাঁচামাল ব্যয় বৃদ্ধির চাপের মুখে রয়েছে। কর্পোরেট পর্যায়ে উৎপাদন পরিকল্পনা, ক্রয়াদেশ ধরে রাখা এবং নতুন বাজার খোঁজার প্রয়োজনীয়তা বাড়ছে। এলডিসি উত্তরণের পর শুল্ক সুবিধা কমে গেলে প্রতিযোগিতা আরও কঠিন হতে পারে। তাই উৎপাদনশীলতা, সবুজ কারখানা, শ্রমমান ও বাজার বৈচিত্র্য এখন পোশাক কোম্পানিগুলোর জন্য বড় কৌশলগত বিষয়।",
                        'seo_keywords'     => "পোশাক খাত, কর্পোরেট, রপ্তানি, বাণিজ্যনীতি",
                        'published_at'     => "2026-05-03 12:30:00",
                    ],

                    (object) [
                        "category_name"    => "স্টার্টআপ",
                        'name'             => "৩৯ ব্যাংকের ৬০০ কোটি টাকায় স্টার্টআপ বিনিয়োগ কোম্পানি",
                        'sub_heading'      => "তরুণ উদ্যোক্তাদের পুঁজি ও পরামর্শ দিতে বিএসআইসির যাত্রা",
                        'content_shoulder' => "",
                        'brief'            => "দেশের ৩৯টি ব্যাংকের উদ্যোগে ৬০০ কোটি টাকা মূলধনে বাংলাদেশ স্টার্টআপ ইনভেস্টমেন্ট কোম্পানি গঠিত হয়েছে।",
                        "body"             => "বাংলাদেশের প্রযুক্তিভিত্তিক নতুন উদ্যোগে অর্থায়ন বাড়াতে ৩৯টি ব্যাংকের যৌথ উদ্যোগে বাংলাদেশ স্টার্টআপ ইনভেস্টমেন্ট কোম্পানি গঠিত হয়েছে। ৬০০ কোটি টাকা মূলধনের এই প্রতিষ্ঠান উদীয়মান স্টার্টআপে পুঁজি দেবে, তবে একদম শুরু নয়—কিছুটা বিকশিত হওয়ার সম্ভাবনা দেখা গেলে বিনিয়োগ করবে। অর্থায়নের পাশাপাশি তদারকি, পরামর্শ ও বিদেশি ভেঞ্চার ক্যাপিটাল আনার লক্ষ্যও রয়েছে। এতে স্থানীয় স্টার্টআপ ইকোসিস্টেমে নতুন গতি আসতে পারে।",
                        'seo_keywords'     => "স্টার্টআপ, বিএসআইসি, ভেঞ্চার ক্যাপিটাল, ব্যাংক",
                        'published_at'     => "2026-04-02 04:30:00",
                    ],

                    (object) [
                        "category_name"    => "স্টার্টআপ",
                        'name'             => "প্রাইম ব্যাংক স্টার্টআপে সর্বোচ্চ ৮ কোটি টাকা ঋণ দেবে",
                        'sub_heading'      => "",
                        'content_shoulder' => "উদ্যোক্তা অর্থায়ন",
                        'brief'            => "স্টার্টআপ তহবিলের আওতায় প্রাইম ব্যাংক নতুন ও বিকাশমান উদ্যোগকে বয়সভেদে সর্বোচ্চ ৮ কোটি টাকা পর্যন্ত ঋণ দেবে।",
                        "body"             => "বাংলাদেশ ব্যাংকের স্টার্টআপ নীতিমালার আওতায় প্রাইম ব্যাংক প্রযুক্তিভিত্তিক ও উদ্ভাবনী উদ্যোগে ঋণ সহায়তা দেবে। দুই বছরের কম বয়সী উদ্যোগ ২ কোটি টাকা পর্যন্ত, ২ থেকে ৬ বছর বয়সী উদ্যোগ ৫ কোটি টাকা পর্যন্ত এবং ৬ থেকে ১২ বছর বয়সী উদ্যোগ সর্বোচ্চ ৮ কোটি টাকা পর্যন্ত ঋণ পেতে পারে। এই ঋণের সুদ ৪ শতাংশের কাছাকাছি হওয়ায় তরুণ উদ্যোক্তাদের জন্য এটি বড় সুযোগ হতে পারে। তবে ট্রেড লাইসেন্স, নিবন্ধন ও ব্যবসার সম্ভাবনা দেখাতে হবে।",
                        'seo_keywords'     => "প্রাইম ব্যাংক, স্টার্টআপ ঋণ, উদ্যোক্তা, অর্থায়ন",
                        'published_at'     => "2026-03-30 06:00:00",
                    ],

                    (object) [
                        "category_name"    => "স্টার্টআপ",
                        'name'             => "স্টার্টআপ খাতকে কাঠামোবদ্ধ করতে স্কেলআপ প্রোগ্রাম",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দেশের স্টার্টআপ খাতকে টেকসই ও বিনিয়োগ প্রস্তুত করতে স্টার্টআপ অ্যান্ড স্কেলআপ প্রোগ্রাম নিয়ে কাজ চলছে।",
                        "body"             => "বাংলাদেশের স্টার্টআপ খাত নতুন বাস্তবতায় প্রবেশ করছে। স্টার্টআপ অ্যান্ড স্কেলআপ প্রোগ্রামের মাধ্যমে উদ্যোক্তাদের দক্ষতা বৃদ্ধি, বাজার সংযোগ, বিনিয়োগ প্রস্তুতি এবং ব্যবসার সম্প্রসারণে সহায়তা দেওয়ার উদ্যোগ নেওয়া হয়েছে। স্থানীয় উদ্যোক্তারা অনেক সময় পুঁজি, মেন্টরশিপ, বাজার তথ্য ও কর্পোরেট সংযোগের অভাবে পিছিয়ে পড়েন। এই ধরনের কাঠামোবদ্ধ প্রোগ্রাম সফল হলে প্রযুক্তি উদ্যোগগুলো দ্রুত বড় হতে পারবে।",
                        'seo_keywords'     => "স্কেলআপ, স্টার্টআপ ইকোসিস্টেম, উদ্যোক্তা, বিনিয়োগ",
                        'published_at'     => "2026-04-20 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "স্টার্টআপ",
                        'name'             => "স্টার্টআপ বাংলাদেশ ও লাইটক্যাসলের কৌশলগত জোট",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশের স্টার্টআপ ইকোসিস্টেম এগিয়ে নিতে স্টার্টআপ বাংলাদেশ ও লাইটক্যাসল পার্টনার্স কৌশলগত সমঝোতায় যুক্ত হয়েছে।",
                        "body"             => "সরকারি ভেঞ্চার ক্যাপিটাল প্রতিষ্ঠান স্টার্টআপ বাংলাদেশ লিমিটেড এবং গবেষণা ও পরামর্শক প্রতিষ্ঠান লাইটক্যাসল পার্টনার্স বাংলাদেশের স্টার্টআপ ইকোসিস্টেম শক্তিশালী করতে কৌশলগত সমঝোতা করেছে। এই জোটের লক্ষ্য বিনিয়োগ প্রস্তুতি, বাজার বিশ্লেষণ, উদ্যোক্তা সক্ষমতা বৃদ্ধি এবং আন্তর্জাতিক সংযোগ তৈরি করা। স্থানীয় স্টার্টআপের জন্য শুধু অর্থ নয়, সঠিক ডেটা, পরামর্শ ও বাজারে প্রবেশের সহায়তাও গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "স্টার্টআপ বাংলাদেশ, লাইটক্যাসল, ভেঞ্চার ক্যাপিটাল, ইকোসিস্টেম",
                        'published_at'     => "2026-03-09 11:00:00",
                    ],

                    (object) [
                        "category_name"    => "স্টার্টআপ",
                        'name'             => "অনলাইন গ্রোসারি স্টার্টআপগুলোর টিকে থাকা কঠিন হচ্ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "লজিস্টিকস ব্যয়, গ্রাহক ধরে রাখা ও মুনাফা অর্জনের চাপের কারণে দেশের অনলাইন গ্রোসারি স্টার্টআপগুলো কঠিন সময় পার করছে।",
                        "body"             => "বাংলাদেশের অনলাইন গ্রোসারি স্টার্টআপগুলো বাজার সম্ভাবনা থাকা সত্ত্বেও বড় চ্যালেঞ্জের মুখে আছে। দ্রুত ডেলিভারি, গুদাম ব্যবস্থাপনা, পণ্য নষ্ট হওয়ার ঝুঁকি, গ্রাহক ছাড় এবং লজিস্টিকস ব্যয়—সব মিলিয়ে ব্যবসা লাভজনক করা কঠিন। চালডালের মতো বড় প্ল্যাটফর্ম প্রযুক্তিনির্ভর ওয়্যারহাউস মডেল তৈরি করলেও খাতজুড়ে স্কেল ও মুনাফার ভারসাম্য এখনো বড় প্রশ্ন। বিনিয়োগ কমে গেলে ছোট প্ল্যাটফর্মের টিকে থাকা আরও কঠিন হতে পারে।",
                        'seo_keywords'     => "ই-গ্রোসারি, স্টার্টআপ, চালডাল, লজিস্টিকস",
                        'published_at'     => "2026-03-18 10:00:00",
                    ],

                    (object) [
                        "category_name"    => "বাণিজ্য",
                        'name'             => "দোকানপাট রাত ৯টা পর্যন্ত খোলা রাখার দাবি দোকানমালিকদের",
                        'sub_heading'      => "ঈদ বাজারে বিক্রি বাড়াতে সময় বাড়ানোর অনুরোধ",
                        'content_shoulder' => "",
                        'brief'            => "ঈদ উপলক্ষে ক্রেতার চাপ ও ব্যবসায়িক ক্ষতি পুষিয়ে নিতে দোকানপাট রাত ৯টা পর্যন্ত খোলা রাখার দাবি জানিয়েছেন দোকানমালিকরা।",
                        "body"             => "ঈদকে সামনে রেখে দোকানপাট রাত ৯টা পর্যন্ত খোলা রাখার দাবি জানিয়েছেন দোকানমালিকরা। তাঁদের মতে, রোজার বাজার, পোশাক, জুতা, প্রসাধনী ও উপহার সামগ্রীর বিক্রি সাধারণত সন্ধ্যার পর বেশি হয়। বিদ্যুৎ সাশ্রয় ও নিরাপত্তার বিষয় বিবেচনায় সরকার সময়সীমা নির্ধারণ করলেও ব্যবসায়ীরা বলছেন, সীমিত সময়ের কারণে ক্ষুদ্র দোকানদাররা লোকসান পোষাতে পারছেন না। তাই ঈদ মৌসুমে নিয়ন্ত্রিতভাবে সময় বাড়ানোর প্রস্তাব এসেছে।",
                        'seo_keywords'     => "ঈদ বাজার, দোকানপাট, ব্যবসা, বাণিজ্য",
                        'published_at'     => "2026-05-03 09:30:00",
                    ],

                    (object) [
                        "category_name"    => "বাণিজ্য",
                        'name'             => "ঢাকা আন্তর্জাতিক বাণিজ্য মেলায় বিক্রি ৩৯৩ কোটি টাকা",
                        'sub_heading'      => "",
                        'content_shoulder' => "মেলায় রপ্তানি আদেশ",
                        'brief'            => "৩০তম ঢাকা আন্তর্জাতিক বাণিজ্য মেলায় ৩৯৩ কোটি টাকার পণ্য বিক্রি হয়েছে এবং ২২৪ কোটি টাকার রপ্তানি আদেশ মিলেছে।",
                        "body"             => "৩০তম ঢাকা আন্তর্জাতিক বাণিজ্য মেলায় মাসব্যাপী আয়োজনে ৩৯৩ কোটি টাকার পণ্য বিক্রি হয়েছে এবং ২২৪ কোটি টাকার রপ্তানি আদেশ পাওয়া গেছে। বহুমুখী পাটপণ্য, ইলেকট্রিক ও ইলেকট্রনিক্স, হোম অ্যাপ্লায়েন্স, কসমেটিকস, হাইজিন পণ্য, প্রক্রিয়াজাত খাদ্য, হ্যান্ডলুম ও হোম টেক্সটাইলসহ বিভিন্ন খাত রপ্তানি আদেশ পেয়েছে। মেলাটি দেশীয় পণ্য প্রদর্শন, ব্র্যান্ডিং ও আন্তর্জাতিক বাজার সংযোগের গুরুত্বপূর্ণ প্ল্যাটফর্ম হিসেবে কাজ করেছে।",
                        'seo_keywords'     => "বাণিজ্য মেলা, রপ্তানি আদেশ, পণ্য বিক্রি, বাণিজ্য",
                        'published_at'     => "2026-01-31 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "বাণিজ্য",
                        'name'             => "বাণিজ্য ঘাটতি বেড়ে ১৬.৯১ বিলিয়ন ডলারে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চলতি অর্থবছরের প্রথম আট মাসে আমদানি ও রপ্তানির ব্যবধানে দেশের বাণিজ্য ঘাটতি বেড়ে ১৬.৯১ বিলিয়ন ডলারে দাঁড়িয়েছে।",
                        "body"             => "চলতি অর্থবছরের প্রথম আট মাসে দেশের পণ্য বাণিজ্যে ঘাটতি উল্লেখযোগ্যভাবে বেড়েছে। কেন্দ্রীয় ব্যাংকের তথ্য অনুযায়ী, এই সময়ে বাণিজ্য ঘাটতি প্রায় ১৬.৯১ বিলিয়ন ডলারে দাঁড়িয়েছে, যা আগের অর্থবছরের একই সময়ের তুলনায় অনেক বেশি। খাদ্যপণ্য, সার ও জ্বালানি আমদানি বাড়ার পাশাপাশি রপ্তানি আয় প্রত্যাশামতো না বাড়ায় ঘাটতি বেড়েছে। চলতি হিসাব কিছুটা উন্নত হলেও দীর্ঘমেয়াদে রপ্তানি বৈচিত্র্য ও আমদানি ব্যবস্থাপনা গুরুত্বপূর্ণ হয়ে উঠেছে।",
                        'seo_keywords'     => "বাণিজ্য ঘাটতি, আমদানি, রপ্তানি, বৈদেশিক খাত",
                        'published_at'     => "2026-04-25 09:00:00",
                    ],

                    (object) [
                        "category_name"    => "বাণিজ্য",
                        'name'             => "দাম বাড়তেই বাজারে ফিরেছে সয়াবিন তেল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নতুন দামে বোতলজাত ও খোলা সয়াবিন তেলের দাম বাড়ার পর বাজারে সরবরাহ স্বাভাবিক হতে শুরু করেছে বলে ব্যবসায়ীরা বলছেন।",
                        "body"             => "সরকারি সিদ্ধান্তে সয়াবিন তেলের দাম বাড়ার পর বাজারে সরবরাহ আবার দৃশ্যমান হয়েছে। নতুন দামে বোতলজাত সয়াবিন তেলের লিটার ১৯৯ টাকা এবং খোলা সয়াবিন ১৭৯ টাকা নির্ধারণের পর আগে যেসব বাজারে তেল পাওয়া যাচ্ছিল না, সেখানে পণ্য ফিরতে শুরু করেছে। ভোক্তাদের অভিযোগ, দাম বাড়ানোর আগে সরবরাহ কমে যাওয়ায় বাজারে কৃত্রিম সংকটের ধারণা তৈরি হয়েছিল। নিত্যপণ্যের বাজারে স্বচ্ছ মজুত তথ্য ও নিয়মিত তদারকি জরুরি বলে মনে করছেন বিশ্লেষকেরা।",
                        'seo_keywords'     => "সয়াবিন তেল, বাজার দর, নিত্যপণ্য, বাণিজ্য",
                        'published_at'     => "2026-04-30 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "বাণিজ্য",
                        'name'             => "আদার বাজারে অস্থিরতা, দুই সপ্তাহে কেজিতে বেড়েছে ২০ টাকা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আমদানি নির্ভরতা ও সরবরাহ চাপের কারণে আদার বাজারে অস্থিরতা তৈরি হয়েছে, দুই সপ্তাহে কেজিতে দাম বেড়েছে প্রায় ২০ টাকা।",
                        "body"             => "নিত্যপণ্যের বাজারে আদার দাম নতুন করে বেড়েছে। ব্যবসায়ীদের মতে, আমদানি ব্যয়, পাইকারি বাজারে সরবরাহ কমে যাওয়া এবং পরিবহন খরচ বৃদ্ধির কারণে দুই সপ্তাহে কেজিপ্রতি দাম প্রায় ২০ টাকা বেড়েছে। ভোক্তারা বলছেন, একদিকে সবজি ও তেলের দাম বাড়ছে, অন্যদিকে মসলা পণ্যের দামও চাপ তৈরি করছে। বাজার নিয়ন্ত্রণে আমদানির সময়সূচি, মজুত তদারকি ও পাইকারি-খুচরা দামের ব্যবধান নিয়মিত পর্যবেক্ষণ করা দরকার।",
                        'seo_keywords'     => "আদা, মসলা বাজার, আমদানি, নিত্যপণ্য",
                        'published_at'     => "2026-05-03 11:06:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি",
                        'name'             => "জেমিনি চ্যাটবটে সরাসরি ফাইল তৈরির সুবিধা যুক্ত",
                        'sub_heading'      => "পিডিএফ, ওয়ার্ড ও এক্সেল ফাইল বানানো যাবে চ্যাট থেকেই",
                        'content_shoulder' => "",
                        'brief'            => "গুগল জেমিনিতে নতুন সুবিধা যুক্ত করেছে, যার মাধ্যমে ব্যবহারকারীরা চ্যাটের মধ্যেই পিডিএফ, ওয়ার্ড, এক্সেলসহ নানা ফাইল তৈরি করতে পারবেন।",
                        "body"             => "গুগল তাদের কৃত্রিম বুদ্ধিমত্তাভিত্তিক চ্যাটবট জেমিনিতে নতুন ফাইল তৈরির সুবিধা যুক্ত করেছে। এর ফলে ব্যবহারকারীরা আলাদা সফটওয়্যারে কপি-পেস্ট না করেই চ্যাটের নির্দেশনা থেকে সরাসরি পিডিএফ, ডকএক্স, এক্সএলএসএক্স, সিএসভি, টেক্সট, মার্কডাউন ও ল্যাটেক্স ফাইল তৈরি করতে পারবেন। গুগল ডকস, শিটস ও স্লাইডস তৈরির সুবিধাও যুক্ত হয়েছে। ব্যক্তিগত কাজ, অফিস রিপোর্ট, ডেটা টেবিল ও শিক্ষামূলক নথি তৈরিতে এই সুবিধা সময় বাঁচাবে বলে মনে করা হচ্ছে।",
                        'seo_keywords'     => "জেমিনি, গুগল, এআই ফাইল, প্রযুক্তি",
                        'published_at'     => "2026-05-03 14:18:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি",
                        'name'             => "ঢাকায় অনুষ্ঠিত হলো বাংলাদেশ এআই অলিম্পিয়াডের আঞ্চলিক পর্ব",
                        'sub_heading'      => "",
                        'content_shoulder' => "শিক্ষার্থীদের এআই উৎসব",
                        'brief'            => "বিইউবিটিতে তৃতীয় বাংলাদেশ এআই অলিম্পিয়াডের ঢাকা আঞ্চলিক পর্বে কয়েক শ শিক্ষার্থী কুইজ ও কোডিং চ্যালেঞ্জে অংশ নিয়েছে।",
                        "body"             => "বাংলাদেশ ইউনিভার্সিটি অব বিজনেস অ্যান্ড টেকনোলজিতে তৃতীয় বাংলাদেশ এআই অলিম্পিয়াড ২০২৬-এর ঢাকা আঞ্চলিক পর্ব অনুষ্ঠিত হয়েছে। ঢাকাসহ পার্শ্ববর্তী জেলার কয়েক শ শিক্ষার্থী এআই কুইজ ও এআই কোডিং চ্যালেঞ্জে অংশ নেয়। আয়োজকেরা জানান, ভালো করা শিক্ষার্থীরা ১৬ মে অনুষ্ঠেয় জাতীয় পর্বে অংশ নেওয়ার সুযোগ পাবে। এই আয়োজনের মাধ্যমে তরুণদের কৃত্রিম বুদ্ধিমত্তা, গাণিতিক যুক্তি, প্রোগ্রামিং ও ডেটাভিত্তিক সমস্যা সমাধানে আগ্রহ বাড়ছে।",
                        'seo_keywords'     => "এআই অলিম্পিয়াড, বিইউবিটি, শিক্ষার্থী, প্রযুক্তি",
                        'published_at'     => "2026-05-02 19:45:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি",
                        'name'             => "ঢাকায় বিওয়াইডি বাংলাদেশের এক্সক্লুসিভ শোরুম চালু",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নিউ এনার্জি ভেহিকেল বাজারে উপস্থিতি বাড়াতে ঢাকায় বিওয়াইডি বাংলাদেশের সবচেয়ে বড় এক্সক্লুসিভ শোরুম উদ্বোধন করেছে।",
                        "body"             => "বিশ্বের নিউ এনার্জি ভেহিকেল নির্মাতা প্রতিষ্ঠান বিওয়াইডি ঢাকায় বাংলাদেশের সবচেয়ে বড় এক্সক্লুসিভ শোরুম চালু করেছে। একই সঙ্গে ক্রেতাদের গাড়ি ডেলিভারি অনুষ্ঠানও আয়োজন করা হয়। বৈদ্যুতিক ও প্লাগ-ইন হাইব্রিড গাড়ির বাজারে বাংলাদেশে ক্রমবর্ধমান আগ্রহের প্রেক্ষাপটে এই শোরুম চালু করা হয়েছে। প্রতিষ্ঠানটি বলছে, পরিবেশবান্ধব যাতায়াত, উন্নত ব্যাটারি প্রযুক্তি ও গ্রাহক অভিজ্ঞতা বাড়ানো তাদের মূল লক্ষ্য।",
                        'seo_keywords'     => "বিওয়াইডি, ইভি, শোরুম, প্রযুক্তি",
                        'published_at'     => "2026-05-02 15:28:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি",
                        'name'             => "অস্কারে এআই দিয়ে তৈরি অভিনেতা ও চিত্রনাট্য অযোগ্য ঘোষণা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "অস্কারের নতুন নিয়মে স্পষ্ট করা হয়েছে, অভিনয় ও চিত্রনাট্য অস্কার বিবেচনায় আসতে হলে তা মানুষের কাজ হিসেবে প্রমাণযোগ্য হতে হবে।",
                        "body"             => "দ্য একাডেমি অব মোশন পিকচার আর্টস অ্যান্ড সায়েন্সেস অস্কারের যোগ্যতা নীতিমালায় নতুন পরিবর্তন এনেছে। নিয়ম অনুযায়ী, অস্কারের জন্য অভিনয় অবশ্যই মানুষের মাধ্যমে সম্পাদিত হতে হবে এবং চিত্রনাট্যও মানুষকে লিখতে হবে। তবে চলচ্চিত্র নির্মাণের অন্যান্য অংশে এআই টুল ব্যবহারে সরাসরি নিষেধাজ্ঞা দেওয়া হয়নি। প্রযুক্তি ও সৃজনশীলতার সীমা কোথায় টানা হবে, এই সিদ্ধান্ত সেই বিতর্ককে নতুনভাবে সামনে এনেছে।",
                        'seo_keywords'     => "অস্কার, এআই, চলচ্চিত্র, প্রযুক্তি",
                        'published_at'     => "2026-05-03 15:34:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি",
                        'name'             => "রেডিটের সার্চ ইঞ্জিন ব্যবহারকারী বেড়েছে ৩০ শতাংশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এআইভিত্তিক অনুসন্ধান সুবিধা ও ব্যবহারবান্ধব সার্চ উন্নয়নের কারণে রেডিটের নিজস্ব সার্চ ইঞ্জিনে ব্যবহারকারী উল্লেখযোগ্যভাবে বেড়েছে।",
                        "body"             => "সামাজিক আলোচনা প্ল্যাটফর্ম রেডিট তাদের সার্চ ইঞ্জিনে কয়েক বছর ধরে বিনিয়োগ বাড়িয়েছে। ব্যবহারকারীরা যেন প্রয়োজনীয় আলোচনা, উত্তর ও কমিউনিটি পোস্ট সহজে খুঁজে পান, সে জন্য এআইভিত্তিক সুবিধাও যুক্ত করা হয়েছে। সাম্প্রতিক তথ্য অনুযায়ী, রেডিটের সার্চ ইঞ্জিন ব্যবহারকারীর সংখ্যা ৩০ শতাংশ বেড়েছে। প্রযুক্তি বিশ্লেষকেরা বলছেন, সাধারণ সার্চ ইঞ্জিনের বাইরে নির্দিষ্ট কমিউনিটি জ্ঞানভান্ডার হিসেবে রেডিটের গুরুত্ব বাড়ছে।",
                        'seo_keywords'     => "রেডিট, সার্চ ইঞ্জিন, এআই, প্রযুক্তি",
                        'published_at'     => "2026-05-03 13:40:00",
                    ],

                    (object) [
                        "category_name"    => "এআই",
                        'name'             => "বাংলাদেশ এআই অলিম্পিয়াডের জাতীয় পর্ব হবে ১৬ মে",
                        'sub_heading'      => "আঞ্চলিক পর্ব শেষে ঢাকায় চূড়ান্ত প্রতিযোগিতা",
                        'content_shoulder' => "",
                        'brief'            => "শিক্ষার্থীদের এআই দক্ষতা বাড়াতে তৃতীয় বাংলাদেশ এআই অলিম্পিয়াডের জাতীয় পর্ব ১৬ মে ঢাকার বিইউবিটিতে আয়োজন করা হবে।",
                        "body"             => "বাংলাদেশ ওপেন সোর্স নেটওয়ার্কের আয়োজনে তৃতীয় বাংলাদেশ এআই অলিম্পিয়াডের জাতীয় পর্ব ১৬ মে ঢাকায় অনুষ্ঠিত হবে। জাতীয় পর্বের আগে ঢাকা, রংপুর, রাজশাহী, চট্টগ্রাম ও খুলনা বিভাগে আঞ্চলিক পর্ব আয়োজন করা হচ্ছে। প্রতিযোগিতায় এআই চ্যালেঞ্জ ও এআই কুইজ—দুটি বিভাগ রাখা হয়েছে। আয়োজকেরা বলছেন, এআই শেখা শুধু কোডিং নয়; বরং সমস্যা সমাধান, যুক্তি, ডেটা বোঝা ও ভবিষ্যৎ কর্মদক্ষতা তৈরির একটি গুরুত্বপূর্ণ ধাপ।",
                        'seo_keywords'     => "বাংলাদেশ এআই অলিম্পিয়াড, এআই, শিক্ষার্থী, বিডিওএসএন",
                        'published_at'     => "2026-04-24 18:20:00",
                    ],

                    (object) [
                        "category_name"    => "এআই",
                        'name'             => "বাংলায় বিনামূল্যে এআই শেখার কোর্স চালু",
                        'sub_heading'      => "",
                        'content_shoulder' => "এআই শিক্ষা",
                        'brief'            => "বাংলাদেশের শিক্ষার্থী, শিক্ষক ও কর্মজীবীদের জন্য বাংলায় তৈরি বিনামূল্যের এআই শেখার কোর্স আনুষ্ঠানিকভাবে চালু হয়েছে।",
                        "body"             => "বাংলাদেশের তরুণ, শিক্ষক, ফ্রিল্যান্সার ও কর্মজীবীদের এআই সম্পর্কে মৌলিক ও ব্যবহারিক ধারণা দিতে বাংলায় বিনামূল্যে এআই শেখার কোর্স চালু করা হয়েছে। ডিজিটাল লার্নিং প্রতিষ্ঠান শিখোর তৈরি এই কোর্সে মেটা ও লাইটক্যাসল পার্টনার্স সহযোগিতা করেছে। কোর্সে জেনারেটিভ এআই, নৈতিক ব্যবহার, এআই টুলের কাজের ধরন এবং বাস্তব প্রয়োগ শেখানো হবে। প্রযুক্তি জানা না থাকলেও অংশগ্রহণকারীরা কোর্সটি করতে পারবেন।",
                        'seo_keywords'     => "এআই কোর্স, বাংলা এআই, শিখো, মেটা",
                        'published_at'     => "2026-05-02 12:30:00",
                    ],

                    (object) [
                        "category_name"    => "এআই",
                        'name'             => "চ্যাটজিপিটি ইমেজেস ২.০ বাস্তবসম্মত ছবি তৈরিতে উন্নতি এনেছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ওপেনএআইয়ের নতুন ছবি তৈরির মডেল আরও বাস্তবসম্মত আলো, টেক্সচার, ভাষা বোঝা ও নকশার সূক্ষ্মতা ধরে রাখতে সক্ষম।",
                        "body"             => "ওপেনএআই চ্যাটজিপিটি ইমেজেস ২.০ নামের নতুন ছবি তৈরির প্রযুক্তি উন্মোচন করেছে। নতুন সংস্করণে বাস্তবসম্মত আলো-ছায়া, টেক্সচার, সূক্ষ্ম অপূর্ণতা ও কম্পোজিশনের ওপর বেশি জোর দেওয়া হয়েছে। বাংলা, হিন্দি, জাপানি, কোরিয়ান ও চীনা ভাষার লেখা–সংবলিত ছবি তৈরিতেও উন্নতি এসেছে। বিপণন, নকশা, উপস্থাপনা, কনটেন্ট তৈরি ও সৃজনশীল কাজে এ ধরনের মডেলের ব্যবহার আরও বাড়তে পারে।",
                        'seo_keywords'     => "চ্যাটজিপিটি ইমেজেস, ওপেনএআই, এআই ছবি, জেনারেটিভ এআই",
                        'published_at'     => "2026-04-25 10:15:00",
                    ],

                    (object) [
                        "category_name"    => "এআই",
                        'name'             => "এআই বদলে দিচ্ছে আট ঘণ্টার কাজের হিসাব",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সিলিকন ভ্যালির এআই স্টার্টআপগুলোতে দীর্ঘ কর্মঘণ্টা ও ৯৯৬ সংস্কৃতি নিয়ে শ্রম অধিকার ও প্রযুক্তি প্রতিযোগিতা প্রশ্নে আলোচনা বাড়ছে।",
                        "body"             => "এআইভিত্তিক প্রযুক্তির দ্রুত প্রতিযোগিতা সিলিকন ভ্যালির স্টার্টআপগুলোতে কাজের সংস্কৃতি বদলে দিচ্ছে। কয়েকটি প্রতিষ্ঠানে সকাল ৯টা থেকে রাত ৯টা, সপ্তাহে ৬ দিন কাজের ৯৯৬ সংস্কৃতি নিয়ে আলোচনা চলছে। কর্মীরা বেশি সময় কাজ করছেন শুধু অর্থের জন্য নয়, বরং দ্রুত বদলে যাওয়া এআই বাজারে টিকে থাকার চাপ থেকেও। প্রযুক্তির অগ্রগতি উৎপাদনশীলতা বাড়ালেও মানুষের বিশ্রাম, শ্রম অধিকার ও কাজ-জীবনের ভারসাম্য নিয়ে নতুন প্রশ্ন তৈরি করছে।",
                        'seo_keywords'     => "এআই শ্রম, ৯৯৬ সংস্কৃতি, সিলিকন ভ্যালি, কর্মঘণ্টা",
                        'published_at'     => "2026-05-01 08:00:00",
                    ],

                    (object) [
                        "category_name"    => "এআই",
                        'name'             => "ক্রোম ব্রাউজারের এআই মোডে স্প্লিট-স্ক্রিন সুবিধা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গুগল ক্রোমের এআই মোডে নতুন স্প্লিট-স্ক্রিন ও মাল্টিমোডাল অনুসন্ধান সুবিধা যুক্ত করেছে, যাতে তথ্য খোঁজা সহজ হয়।",
                        "body"             => "গুগল ক্রোম ব্রাউজারের এআই মোডে নতুন হালনাগাদ এনেছে। এতে কোনো লিংকে ক্লিক করলে তা আলাদা ট্যাবে না গিয়ে একই পর্দায় এআই উইন্ডোর পাশে দেখা যাবে। ব্যবহারকারীরা খোলা ট্যাব, ছবি বা পিডিএফ ফাইল থেকেও তথ্য নিয়ে অনুসন্ধান করতে পারবেন। গুগলের দাবি, নতুন স্প্লিট-স্ক্রিন ও মাল্টিমোডাল সুবিধা তথ্য খোঁজা, তুলনা করা ও বিশ্লেষণের কাজকে দ্রুত করবে। শুরুতে সুবিধাটি যুক্তরাষ্ট্রের ডেস্কটপ ব্যবহারকারীরা পাবেন।",
                        'seo_keywords'     => "ক্রোম এআই, গুগল, ব্রাউজার, মাল্টিমোডাল",
                        'published_at'     => "2026-04-20 05:00:00",
                    ],

                    (object) [
                        "category_name"    => "গ্যাজেট",
                        'name'             => "বিওয়াইডি আনছে প্লাগ-ইন হাইব্রিড সেডান",
                        'sub_heading'      => "জ্বালানি ছাড়াই ৮০ কিলোমিটার চলার দাবি",
                        'content_shoulder' => "",
                        'brief'            => "বিওয়াইডি দেশের বাজারে নতুন প্লাগ-ইন হাইব্রিড সেডান আনছে, যা পূর্ণ বৈদ্যুতিক মোডে ৮০ কিলোমিটার চলতে পারে বলে দাবি করা হয়েছে।",
                        "body"             => "চীনা বৈদ্যুতিক গাড়ি নির্মাতা বিওয়াইডি বাংলাদেশের বাজারে নতুন প্লাগ-ইন হাইব্রিড সেডান আনার প্রস্তুতি নিয়েছে। গাড়িটি কোনো জ্বালানি খরচ না করেই ৮০ কিলোমিটার পথ পাড়ি দিতে পারে বলে প্রতিষ্ঠানটি জানিয়েছে। জ্বালানি সাশ্রয়, কম নিঃসরণ ও শহুরে যাতায়াতের সুবিধার কারণে এ ধরনের গাড়ির প্রতি ক্রেতাদের আগ্রহ বাড়ছে। বাংলাদেশের যানজটপূর্ণ নগরপরিবেশে হাইব্রিড ও বৈদ্যুতিক গাড়ির বাস্তব ব্যবহার কতটা সুবিধাজনক হবে, সেটিও এখন আলোচনায় আছে।",
                        'seo_keywords'     => "বিওয়াইডি, হাইব্রিড গাড়ি, ইভি, গ্যাজেট",
                        'published_at'     => "2026-05-03 10:58:00",
                    ],

                    (object) [
                        "category_name"    => "গ্যাজেট",
                        'name'             => "টিম কুকের বিদায়বেলায় আইফোনের চাহিদায় নতুন রেকর্ড",
                        'sub_heading'      => "",
                        'content_shoulder' => "অ্যাপল গ্যাজেট বাজার",
                        'brief'            => "অ্যাপলের নেতৃত্ব পরিবর্তন নিয়ে জল্পনার মধ্যেই আইফোনের চাহিদা নতুন রেকর্ডে পৌঁছেছে বলে প্রযুক্তি বাজারে আলোচনা তৈরি হয়েছে।",
                        "body"             => "অ্যাপলের প্রধান নির্বাহী টিম কুককে ঘিরে ভবিষ্যৎ নেতৃত্বের আলোচনা চললেও আইফোনের চাহিদা প্রযুক্তি বাজারে নতুন রেকর্ডে পৌঁছেছে বলে খবর এসেছে। আইফোন এখনও প্রিমিয়াম স্মার্টফোন বাজারে শক্ত অবস্থান ধরে রেখেছে। বিশ্লেষকেরা বলছেন, হার্ডওয়্যার, সফটওয়্যার ইকোসিস্টেম, ক্যামেরা, নিরাপত্তা ও ব্র্যান্ড আস্থাই অ্যাপলকে এগিয়ে রাখছে। একই সঙ্গে কৃত্রিম বুদ্ধিমত্তা ফিচার ও ডিভাইস আপগ্রেডের চাহিদাও বাজারকে প্রভাবিত করছে।",
                        'seo_keywords'     => "আইফোন, অ্যাপল, টিম কুক, গ্যাজেট",
                        'published_at'     => "2026-05-03 12:50:00",
                    ],

                    (object) [
                        "category_name"    => "গ্যাজেট",
                        'name'             => "বাংলাদেশে আইফোন ১৬ প্রো ম্যাক্সের চাহিদা বেশি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দাম বেশি হলেও বাংলাদেশে আইফোন ১৬ সিরিজের মধ্যে প্রো ম্যাক্স মডেলের চাহিদা সবচেয়ে বেশি বলে মোবাইল বাজারের বিক্রেতারা জানিয়েছেন।",
                        "body"             => "ঢাকার মোবাইল বাজারে আইফোন ১৬ সিরিজের চাহিদা এখনও বেশি। বিশেষ করে আইফোন ১৬ প্রো ম্যাক্স মডেলটি ক্রেতাদের মধ্যে বেশি জনপ্রিয়। অফিশিয়াল ওয়ারেন্টিযুক্ত আইফোনের দাম তুলনামূলক বেশি হওয়ায় আনঅফিশিয়াল আইফোনের বাজারও বড়। বিক্রেতারা বলছেন, ক্যামেরা, পারফরম্যান্স, ব্র্যান্ড ভ্যালু ও নতুন মডেলের আগ্রহের কারণে দাম বেশি হলেও ক্রেতারা এই সিরিজ কিনছেন। তবে আনঅফিশিয়াল পণ্যে ওয়ারেন্টি ও বৈধতার ঝুঁকি থাকে।",
                        'seo_keywords'     => "আইফোন ১৬, প্রো ম্যাক্স, স্মার্টফোন, বাংলাদেশ",
                        'published_at'     => "2026-05-01 11:20:00",
                    ],

                    (object) [
                        "category_name"    => "গ্যাজেট",
                        'name'             => "টেকনোর নতুন স্মার্টফোন সিরিজ বাংলাদেশের বাজারে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশের মোবাইল বাজারে টেকনোর নতুন স্মার্টফোন সিরিজ আসায় বাজেট ও মিডরেঞ্জ ক্রেতাদের জন্য আরও বিকল্প তৈরি হয়েছে।",
                        "body"             => "বাংলাদেশের স্মার্টফোন বাজারে টেকনোর নতুন সিরিজ এসেছে। বাজেট ও মিডরেঞ্জ সেগমেন্টে ক্যামেরা, ব্যাটারি, ডিসপ্লে ও ডিজাইনকে সামনে রেখে ব্র্যান্ডগুলো প্রতিযোগিতা বাড়াচ্ছে। তরুণ ব্যবহারকারী, অনলাইন কনটেন্ট দেখা, মোবাইল ফটোগ্রাফি এবং দীর্ঘ ব্যাটারি ব্যাকআপ—এসব চাহিদাকে কেন্দ্র করে নতুন স্মার্টফোন বাজারজাত করা হচ্ছে। বাজার বিশ্লেষকেরা বলছেন, মূল্যসচেতন ক্রেতাদের জন্য দেশীয় বাজারে এ ধরনের পণ্যের গুরুত্ব বাড়ছে।",
                        'seo_keywords'     => "টেকনো, স্মার্টফোন, বাংলাদেশ, মোবাইল",
                        'published_at'     => "2026-05-02 16:20:00",
                    ],

                    (object) [
                        "category_name"    => "গ্যাজেট",
                        'name'             => "মেমোরি চিপ সংকটে স্মার্টফোনের দাম বাড়ার আশঙ্কা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বিশ্ববাজারে মেমোরি চিপ সংকটের প্রভাব বাংলাদেশেও পড়তে পারে, ফলে স্মার্টফোনের দাম ১০ থেকে ২৫ শতাংশ বাড়ার আশঙ্কা রয়েছে।",
                        "body"             => "মেমোরি চিপের বৈশ্বিক সংকটের কারণে স্মার্টফোন উৎপাদন ব্যয় বাড়ছে। বাংলাদেশে আমদানিনির্ভর মোবাইল বাজারে এর প্রভাব পড়লে নতুন স্মার্টফোনের দাম ১০ থেকে ২৫ শতাংশ পর্যন্ত বাড়তে পারে বলে ব্যবসায়ীরা আশঙ্কা করছেন। চাহিদা বাড়লেও সরবরাহ সীমিত হলে বাজেট ফোনের দামও বাড়তে পারে। স্মার্টফোন এখন শিক্ষা, ফ্রিল্যান্সিং, অনলাইন ব্যবসা ও যোগাযোগের গুরুত্বপূর্ণ মাধ্যম হওয়ায় দামের এই চাপ সাধারণ ক্রেতাদের ওপর সরাসরি প্রভাব ফেলবে।",
                        'seo_keywords'     => "মেমোরি চিপ, স্মার্টফোন দাম, গ্যাজেট, মোবাইল বাজার",
                        'published_at'     => "2026-05-02 14:10:00",
                    ],

                    (object) [
                        "category_name"    => "সফটওয়্যার",
                        'name'             => "হোয়াটসঅ্যাপে নতুন স্পয়লার ফিচার আসছে",
                        'sub_heading'      => "ট্যাপ না করলে লুকানো বার্তা দেখা যাবে না",
                        'content_shoulder' => "",
                        'brief'            => "হোয়াটসঅ্যাপ এমন একটি স্পয়লার ফিচার আনছে, যেখানে ব্যবহারকারী ট্যাপ না করা পর্যন্ত নির্দিষ্ট বার্তা বা তথ্য দেখা যাবে না।",
                        "body"             => "মেটার মেসেজিং অ্যাপ হোয়াটসঅ্যাপে নতুন স্পয়লার ফিচার আসছে। এই সুবিধায় বার্তার কোনো অংশ লুকিয়ে রাখা যাবে এবং প্রাপক ট্যাপ না করা পর্যন্ত সেটি দেখা যাবে না। সিনেমা, খেলা, সিরিজ বা সংবেদনশীল আলোচনায় আগেভাগে তথ্য প্রকাশ ঠেকাতে ফিচারটি কার্যকর হতে পারে। টেলিগ্রামের মতো কিছু প্ল্যাটফর্মে এ ধরনের সুবিধা আগেই ছিল। হোয়াটসঅ্যাপে যুক্ত হলে ব্যক্তিগত ও গ্রুপ চ্যাটে ব্যবহারকারীর নিয়ন্ত্রণ আরও বাড়বে।",
                        'seo_keywords'     => "হোয়াটসঅ্যাপ, স্পয়লার ফিচার, সফটওয়্যার, মেটা",
                        'published_at'     => "2026-02-25 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "সফটওয়্যার",
                        'name'             => "আইওএস ২৭-এ আসছে এআইনির্ভর ফটো এডিটিং টুল",
                        'sub_heading'      => "",
                        'content_shoulder' => "অ্যাপল সফটওয়্যার",
                        'brief'            => "অ্যাপলের পরবর্তী আইওএস সংস্করণে এআইনির্ভর নতুন ফটো এডিটিং টুল যুক্ত হতে পারে, যা ছবি সম্পাদনাকে আরও সহজ করবে।",
                        "body"             => "অ্যাপলের পরবর্তী আইওএস সংস্করণে এআইনির্ভর নতুন ফটো এডিটিং টুল যুক্ত হওয়ার খবর প্রযুক্তি দুনিয়ায় আলোচনা তৈরি করেছে। ব্যবহারকারীরা ছবি থেকে অবাঞ্ছিত বস্তু সরানো, আলো-ছায়া ঠিক করা, স্বয়ংক্রিয় রিটাচ এবং দৃশ্যভিত্তিক সম্পাদনার সুবিধা পেতে পারেন। স্মার্টফোন ক্যামেরা এখন শুধু ছবি তোলার মাধ্যম নয়, বরং সম্পূর্ণ কনটেন্ট তৈরির টুলে পরিণত হয়েছে। তাই মোবাইল সফটওয়্যারে এআই ফিচার যুক্ত করা ব্র্যান্ডগুলোর বড় প্রতিযোগিতার জায়গা।",
                        'seo_keywords'     => "আইওএস ২৭, ফটো এডিটিং, এআই, সফটওয়্যার",
                        'published_at'     => "2026-05-03 13:25:00",
                    ],

                    (object) [
                        "category_name"    => "সফটওয়্যার",
                        'name'             => "ইনস্টাগ্রামে অন্যের ছবি বা পোস্ট শেয়ারে কড়াকড়ি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইনস্টাগ্রামে অন্যের ছবি, পোস্ট বা কনটেন্ট শেয়ারের ক্ষেত্রে নতুন কড়াকড়ি আসায় কপিরাইট ও গোপনীয়তা নিয়ে আলোচনা বাড়ছে।",
                        "body"             => "ইনস্টাগ্রামে অন্যের ছবি বা পোস্ট শেয়ার করার ক্ষেত্রে নতুন কড়াকড়ি আসছে বলে প্রযুক্তি সংবাদে বলা হয়েছে। কনটেন্ট মালিকানা, ব্যক্তিগত ছবি, অনুমতি ছাড়া পুনঃপ্রকাশ এবং প্ল্যাটফর্মে কপিরাইট সুরক্ষা এখন আরও গুরুত্বপূর্ণ বিষয় হয়ে উঠেছে। নতুন নিয়ম ব্যবহারকারীদের দায়িত্বশীল শেয়ারিংয়ে উৎসাহিত করতে পারে। তবে ক্রিয়েটর, ব্র্যান্ড ও সাধারণ ব্যবহারকারীদের জন্য কীভাবে রি-পোস্ট বা সহযোগী কনটেন্ট শেয়ার করা যাবে, সে বিষয়ে পরিষ্কার নির্দেশনা প্রয়োজন।",
                        'seo_keywords'     => "ইনস্টাগ্রাম, কপিরাইট, পোস্ট শেয়ার, সফটওয়্যার",
                        'published_at'     => "2026-05-03 12:35:00",
                    ],

                    (object) [
                        "category_name"    => "সফটওয়্যার",
                        'name'             => "ইউটিউবে ব্যবহারকারীদের জন্য নতুন আকর্ষণীয় ফিচার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইউটিউব ব্যবহারকারীদের ভিডিও দেখা, খোঁজা ও কনটেন্ট ব্যবস্থাপনা সহজ করতে নতুন ফিচার নিয়ে আসছে বলে প্রযুক্তি খবরে জানানো হয়েছে।",
                        "body"             => "ইউটিউব ব্যবহারকারীদের অভিজ্ঞতা উন্নত করতে নতুন ফিচার যুক্ত করার পরিকল্পনা করছে। ভিডিও খোঁজা, ডাবিং, কনটেন্ট সাজানো, নির্মাতাদের সঙ্গে দর্শকের সংযোগ এবং ব্যক্তিগত সুপারিশ—এসব ক্ষেত্রে সফটওয়্যার উন্নয়ন চলছে। ভিডিও প্ল্যাটফর্মগুলোর প্রতিযোগিতা বাড়ায় ইউটিউব এখন কেবল ভিডিও দেখার জায়গা নয়; শিক্ষা, বিনোদন, লাইভ স্ট্রিমিং ও ক্রিয়েটর আয়ের সমন্বিত প্ল্যাটফর্মে পরিণত হচ্ছে। নতুন ফিচারগুলো দর্শক ধরে রাখতে গুরুত্বপূর্ণ হবে।",
                        'seo_keywords'     => "ইউটিউব, নতুন ফিচার, সফটওয়্যার, ভিডিও প্ল্যাটফর্ম",
                        'published_at'     => "2026-05-02 18:00:00",
                    ],

                    (object) [
                        "category_name"    => "সফটওয়্যার",
                        'name'             => "হারানো এয়ারপড খুঁজে দিতে বিশেষ অ্যাপের সুবিধা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "হারানো এয়ারপড বা ছোট ব্লুটুথ ডিভাইস খুঁজে পেতে বিশেষ অ্যাপ ব্যবহারকারীদের অবস্থান শনাক্তে সহায়তা করতে পারে।",
                        "body"             => "ছোট ব্লুটুথ ডিভাইস হারিয়ে গেলে তা খুঁজে পাওয়া অনেক সময় কঠিন হয়। এয়ারপডের মতো ডিভাইসের অবস্থান শনাক্তে বিশেষ অ্যাপ ব্যবহারকারীদের সহায়তা করতে পারে। এসব অ্যাপ সাধারণত ব্লুটুথ সিগন্যালের শক্তি, শেষ সংযুক্ত অবস্থান এবং ডিভাইসের কাছাকাছি যাওয়ার নির্দেশনা ব্যবহার করে। তবে অ্যাপ ব্যবহারের সময় গোপনীয়তা অনুমতি, লোকেশন অ্যাক্সেস ও ডিভাইস নিরাপত্তা ভালোভাবে দেখা জরুরি। স্মার্ট অ্যাকসেসরির বাজার বাড়ায় এ ধরনের সফটওয়্যারের প্রয়োজনও বাড়ছে।",
                        'seo_keywords'     => "এয়ারপড, ব্লুটুথ অ্যাপ, সফটওয়্যার, ডিভাইস ট্র্যাকার",
                        'published_at'     => "2026-05-02 11:30:00",
                    ],

                    (object) [
                        "category_name"    => "ইন্টারনেট",
                        'name'             => "জুলাই অভ্যুত্থানে সরকারের সিদ্ধান্তে ইন্টারনেট সেবা বন্ধ হয়েছিল",
                        'sub_heading'      => "জবানবন্দিতে ব্যান্ডউইথ সরবরাহ বন্ধের তথ্য উঠে এসেছে",
                        'content_shoulder' => "",
                        'brief'            => "জুলাই অভ্যুত্থানের সময় সারাদেশে ইন্টারনেট সেবা বন্ধ হওয়া নিয়ে জবানবন্দিতে সরকারের সিদ্ধান্তের বিষয়টি উঠে এসেছে।",
                        "body"             => "জুলাই অভ্যুত্থানের সময় দেশজুড়ে ইন্টারনেট সেবা বন্ধ হওয়ার বিষয়ে নতুন তথ্য সামনে এসেছে। জবানবন্দিতে বলা হয়েছে, ইন্টারনেট সেবাদাতা পর্যায়ের ওপরের স্তর থেকে ব্যান্ডউইথ সরবরাহ বন্ধ থাকায় সেবা বন্ধ হয়ে যায়। আইআইজি, সাবমেরিন কেবল ও আইটিসি স্তরের ভূমিকা নিয়ে আলোচনায় বোঝা যায়, ইন্টারনেট অবকাঠামো শুধু প্রযুক্তিগত নয়, প্রশাসনিক সিদ্ধান্তের সঙ্গেও যুক্ত। এই ঘটনা ডিজিটাল অধিকার, তথ্যপ্রবাহ ও নাগরিক যোগাযোগের নিরাপত্তা নিয়ে প্রশ্ন তুলেছে।",
                        'seo_keywords'     => "ইন্টারনেট বন্ধ, জুলাই অভ্যুত্থান, ব্যান্ডউইথ, ডিজিটাল অধিকার",
                        'published_at'     => "2026-04-13 16:00:00",
                    ],

                    (object) [
                        "category_name"    => "ইন্টারনেট",
                        'name'             => "রেডিট সার্চে এআই সুবিধা যোগে ব্যবহারকারী বাড়ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "অনলাইন অনুসন্ধান",
                        'brief'            => "রেডিট সার্চে এআই সুবিধা যুক্ত হওয়ায় ব্যবহারকারীরা কমিউনিটির তথ্য, আলোচনার উত্তর ও অভিজ্ঞতা আরও সহজে খুঁজে পাচ্ছেন।",
                        "body"             => "রেডিটের নিজস্ব সার্চ ইঞ্জিন ব্যবহারকারী বাড়ার পেছনে এআইভিত্তিক অনুসন্ধানকে বড় কারণ হিসেবে দেখা হচ্ছে। ব্যবহারকারীরা এখন নির্দিষ্ট কমিউনিটি পোস্ট, অভিজ্ঞতা, প্রশ্নোত্তর ও আলোচনার সারাংশ সহজে খুঁজে পাচ্ছেন। সাধারণ সার্চ ইঞ্জিনে যেখানে অনেক সময় বিজ্ঞাপন বা SEO-নির্ভর ফলাফল বেশি দেখা যায়, রেডিটে বাস্তব ব্যবহারকারীর অভিজ্ঞতাভিত্তিক তথ্য পাওয়া যায়। তাই ইন্টারনেট অনুসন্ধানের ধরনও ধীরে ধীরে পরিবর্তিত হচ্ছে।",
                        'seo_keywords'     => "রেডিট সার্চ, ইন্টারনেট, এআই অনুসন্ধান, অনলাইন কমিউনিটি",
                        'published_at'     => "2026-05-03 13:40:00",
                    ],

                    (object) [
                        "category_name"    => "ইন্টারনেট",
                        'name'             => "এক্সের কমিউনিটিজ ফিচার বন্ধ হচ্ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জনপ্রিয়তা না পাওয়ায় সামাজিক যোগাযোগমাধ্যম এক্স কমিউনিটিজ ফিচার বন্ধ করার সিদ্ধান্ত নিয়েছে বলে প্রযুক্তি প্রতিবেদনে জানা গেছে।",
                        "body"             => "সামাজিক যোগাযোগমাধ্যম এক্স কমিউনিটিজ ফিচার বন্ধ করার সিদ্ধান্ত নিয়েছে। টুইটার যুগে চালু হওয়া এই ফিচার নির্দিষ্ট আগ্রহভিত্তিক আলোচনার জন্য তৈরি করা হয়েছিল। কিন্তু পর্যাপ্ত জনপ্রিয়তা না পাওয়া, ব্যবহারকারীর কম অংশগ্রহণ এবং প্ল্যাটফর্মে অগ্রাধিকার বদলে যাওয়ার কারণে ফিচারটি বন্ধ হচ্ছে। ইন্টারনেট প্ল্যাটফর্মগুলোর জন্য এটি একটি গুরুত্বপূর্ণ শিক্ষা—নতুন ফিচার চালু করলেই হয় না, ব্যবহারকারীর প্রকৃত অভ্যাসের সঙ্গে মিল না থাকলে তা টেকে না।",
                        'seo_keywords'     => "এক্স, কমিউনিটিজ, ইন্টারনেট, সোশ্যাল মিডিয়া",
                        'published_at'     => "2026-04-25 12:00:00",
                    ],

                    (object) [
                        "category_name"    => "ইন্টারনেট",
                        'name'             => "মোবাইল ডাটার দাম দেড় বছরে ৫০ শতাংশ পর্যন্ত বেড়েছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দেড় বছরে মোবাইল ডাটার দাম ৫০ শতাংশ পর্যন্ত বাড়ার খবর গ্রাহক, শিক্ষার্থী ও অনলাইন কাজনির্ভর মানুষের ব্যয় বাড়িয়েছে।",
                        "body"             => "বাংলাদেশে মোবাইল ডাটার দাম দেড় বছরে ৫০ শতাংশ পর্যন্ত বেড়েছে বলে প্রযুক্তি খবরে উল্লেখ করা হয়েছে। অনলাইন ক্লাস, ফ্রিল্যান্সিং, ভিডিও কল, মোবাইল ব্যাংকিং, ই-কমার্স ও কনটেন্ট ব্যবহারে ইন্টারনেট এখন অপরিহার্য। ফলে ডাটার দাম বাড়লে সাধারণ ব্যবহারকারীর মাসিক ব্যয় সরাসরি বৃদ্ধি পায়। বিশেষজ্ঞরা বলছেন, গ্রাহকবান্ধব প্যাকেজ, মানসম্মত সেবা, নেটওয়ার্ক বিনিয়োগ ও প্রতিযোগিতামূলক বাজার নিশ্চিত করা জরুরি।",
                        'seo_keywords'     => "মোবাইল ডাটা, ইন্টারনেট দাম, বাংলাদেশ, টেলিকম",
                        'published_at'     => "2026-05-02 15:45:00",
                    ],

                    (object) [
                        "category_name"    => "ইন্টারনেট",
                        'name'             => "এনইআইআর পরীক্ষায় সাময়িকভাবে মোবাইল সংযোগ বন্ধ হতে পারে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এনইআইআর পরীক্ষামূলক কার্যক্রমের সময় কিছু অনিবন্ধিত মোবাইল সংযোগ সাময়িকভাবে বন্ধ হতে পারে বলে প্রযুক্তি খবরে জানানো হয়েছে।",
                        "body"             => "মোবাইল ফোনের বৈধতা যাচাইয়ের জন্য এনইআইআর ব্যবস্থা পরীক্ষার সময় কিছু সংযোগ সাময়িকভাবে বন্ধ হতে পারে বলে ব্যবহারকারীদের সতর্ক করা হয়েছে। অনিবন্ধিত বা অবৈধভাবে আমদানি করা ফোন শনাক্ত করা এই ব্যবস্থার মূল লক্ষ্য। এর মাধ্যমে মোবাইল চুরি, অবৈধ ডিভাইস ব্যবহার ও রাজস্ব ফাঁকি কমানো সম্ভব হতে পারে। তবে গ্রাহকদের যেন অযথা হয়রানির মুখে পড়তে না হয়, সে জন্য নিবন্ধন, ডি-রেজিস্ট্রেশন ও অভিযোগ নিষ্পত্তির প্রক্রিয়া সহজ রাখা জরুরি।",
                        'seo_keywords'     => "এনইআইআর, মোবাইল সংযোগ, ইন্টারনেট, বিটিআরসি",
                        'published_at'     => "2026-05-01 18:20:00",
                    ],

                    (object) [
                        "category_name"    => "সাইবার নিরাপত্তা",
                        'name'             => "সাইবার নিরাপত্তা বিল ২০২৬ সংসদে পাস",
                        'sub_heading'      => "নতুন আইনি কাঠামোর পথে ডিজিটাল নিরাপত্তা খাত",
                        'content_shoulder' => "",
                        'brief'            => "সাইবার সিকিউরিটি অর্ডিন্যান্স ২০২৫ বাতিল করে সাইবার নিরাপত্তা বিল ২০২৬ জাতীয় সংসদে কণ্ঠভোটে পাস হয়েছে।",
                        "body"             => "জাতীয় সংসদে সাইবার নিরাপত্তা বিল ২০২৬ পাস হয়েছে। ডাক, টেলিযোগাযোগ ও তথ্যপ্রযুক্তি মন্ত্রণালয়ের পক্ষ থেকে বিলটি উত্থাপনের পর কণ্ঠভোটে তা পাস করা হয়। নতুন আইনি কাঠামো সাইবার অপরাধ, ডিজিটাল অবকাঠামো, অনলাইন নিরাপত্তা ও তথ্যপ্রযুক্তি ব্যবহারের ক্ষেত্রে গুরুত্বপূর্ণ প্রভাব ফেলতে পারে। তবে আইন প্রয়োগে নাগরিক অধিকার, মতপ্রকাশের স্বাধীনতা, তথ্য সুরক্ষা ও অপরাধ তদন্তের ভারসাম্য বজায় রাখা জরুরি বলে বিশেষজ্ঞরা মনে করছেন।",
                        'seo_keywords'     => "সাইবার নিরাপত্তা বিল, সংসদ, ডিজিটাল নিরাপত্তা, আইন",
                        'published_at'     => "2026-04-11 18:10:00",
                    ],

                    (object) [
                        "category_name"    => "সাইবার নিরাপত্তা",
                        'name'             => "ব্রিটেনের ৪০ শতাংশ প্রতিষ্ঠানেই এক বছরে সাইবার হামলা",
                        'sub_heading'      => "",
                        'content_shoulder' => "প্রতিষ্ঠানে নিরাপত্তা ঝুঁকি",
                        'brief'            => "ব্রিটেনের উল্লেখযোগ্য সংখ্যক প্রতিষ্ঠান এক বছরে সাইবার হামলার শিকার হওয়ায় কর্পোরেট নিরাপত্তা ও ডেটা সুরক্ষা নিয়ে উদ্বেগ বেড়েছে।",
                        "body"             => "ব্রিটেনের ৪০ শতাংশ প্রতিষ্ঠান এক বছরে সাইবার হামলার শিকার হয়েছে বলে প্রযুক্তি খবরে জানানো হয়েছে। ফিশিং, র‍্যানসমওয়্যার, ডেটা চুরি, ব্যবসায়িক ই-মেইল প্রতারণা ও ক্লাউড অ্যাকাউন্ট দখলের মতো হামলা কর্পোরেট খাতের জন্য বড় ঝুঁকি হয়ে উঠেছে। বাংলাদেশসহ বিশ্বের প্রতিষ্ঠানগুলোর জন্য এই অভিজ্ঞতা সতর্কবার্তা। ডিজিটাল সেবা বাড়ার সঙ্গে সঙ্গে নিয়মিত নিরাপত্তা অডিট, কর্মীদের প্রশিক্ষণ, ব্যাকআপ ও মাল্টি-ফ্যাক্টর অথেনটিকেশন বাধ্যতামূলক করা দরকার।",
                        'seo_keywords'     => "সাইবার হামলা, ডেটা সুরক্ষা, ফিশিং, নিরাপত্তা",
                        'published_at'     => "2026-05-03 13:05:00",
                    ],

                    (object) [
                        "category_name"    => "সাইবার নিরাপত্তা",
                        'name'             => "টিকটক ক্লোন অ্যাপ থেকে ব্যাংক তথ্য চুরির সতর্কতা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নকল টিকটক অ্যাপ ইনস্টল করলে ব্যাংক ও অনলাইন লেনদেনের তথ্য চুরি হতে পারে বলে সাইবার নিরাপত্তা সতর্কতা দেওয়া হয়েছে।",
                        "body"             => "সাইবার নিরাপত্তা কর্তৃপক্ষ নকল টিকটক ক্লোন অ্যাপ নিয়ে সতর্ক করেছে। অনেক ব্যবহারকারী আসল অ্যাপ ভেবে এই ধরনের অ্যাপ ইনস্টল করলে ডিভাইসের অনুমতি, এসএমএস, কন্ট্যাক্ট, ব্যাংকিং অ্যাপ ও অনলাইন লেনদেনের তথ্য ঝুঁকিতে পড়তে পারে। অপরিচিত লিংক, তৃতীয় পক্ষের এপিকে ফাইল ও অস্বাভাবিক অনুমতি চাওয়া অ্যাপ থেকে দূরে থাকার পরামর্শ দেওয়া হয়েছে। স্মার্টফোন ব্যবহারকারীদের অ্যাপ ইনস্টল করার আগে উৎস, রিভিউ ও অনুমতি যাচাই করা জরুরি।",
                        'seo_keywords'     => "টিকটক ক্লোন, সাইবার প্রতারণা, ব্যাংক তথ্য, অ্যাপ নিরাপত্তা",
                        'published_at'     => "2026-05-02 16:10:00",
                    ],

                    (object) [
                        "category_name"    => "সাইবার নিরাপত্তা",
                        'name'             => "৪০ শতাংশ অ্যান্ড্রয়েড ফোন ঝুঁকিতে, সতর্ক করল গুগল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পুরোনো নিরাপত্তা প্যাচ, সন্দেহজনক অ্যাপ ও দুর্বল কনফিগারেশনের কারণে বহু অ্যান্ড্রয়েড ফোন সাইবার ঝুঁকিতে আছে।",
                        "body"             => "গুগল অ্যান্ড্রয়েড ফোনের নিরাপত্তা ঝুঁকি নিয়ে সতর্ক করেছে। অনেক ব্যবহারকারী নিয়মিত সফটওয়্যার আপডেট না করায় পুরোনো নিরাপত্তা দুর্বলতা থেকে যায়। এর সঙ্গে অনিরাপদ অ্যাপ, অজানা উৎস থেকে ইনস্টলেশন, দুর্বল পাসওয়ার্ড ও অনুমতি ব্যবস্থাপনার ভুল যুক্ত হলে ডিভাইস সহজেই ম্যালওয়্যার বা ডেটা চুরির ঝুঁকিতে পড়ে। ব্যবহারকারীদের নিয়মিত আপডেট, প্লে প্রোটেক্ট, শক্তিশালী স্ক্রিন লক ও সন্দেহজনক লিংক এড়িয়ে চলার পরামর্শ দেওয়া হয়েছে।",
                        'seo_keywords'     => "অ্যান্ড্রয়েড নিরাপত্তা, গুগল, ম্যালওয়্যার, সাইবার ঝুঁকি",
                        'published_at'     => "2026-05-02 12:40:00",
                    ],

                    (object) [
                        "category_name"    => "সাইবার নিরাপত্তা",
                        'name'             => "হাইব্রিড কর্মপরিবেশে নিরাপত্তা জোরদারে সফোসের সমাধান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "রিমোট ও হাইব্রিড কাজের নিরাপত্তা ঝুঁকি মোকাবিলায় সফোস ওয়ার্কস্পেস প্রোটেকশন নামে নতুন সাইবার সমাধান আনছে।",
                        "body"             => "হাইব্রিড ও রিমোট কাজের পরিবেশে সাইবার নিরাপত্তা ঝুঁকি মোকাবিলায় সফোস ওয়ার্কস্পেস প্রোটেকশন নামের নতুন সমাধান আনছে। ঘরে বসে কাজ, ব্যক্তিগত ডিভাইস ব্যবহার, ক্লাউড অ্যাপ, পাবলিক নেটওয়ার্ক ও দূরবর্তী লগইনের কারণে প্রতিষ্ঠানের তথ্যভান্ডার ঝুঁকিতে পড়ে। এই ধরনের নিরাপত্তা সমাধান এন্ডপয়েন্ট, পরিচয় যাচাই, নেটওয়ার্ক ট্রাফিক ও সন্দেহজনক কার্যক্রম পর্যবেক্ষণে সহায়তা করতে পারে। বাংলাদেশের প্রতিষ্ঠানগুলোর জন্যও হাইব্রিড কাজের নিরাপত্তা এখন গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "সফোস, ওয়ার্কস্পেস প্রোটেকশন, হাইব্রিড কাজ, সাইবার নিরাপত্তা",
                        'published_at'     => "2026-02-03 11:00:00",
                    ],

                    (object) [
                        "category_name"    => "খেলাধুলা",
                        'name'             => "ক্রীড়া কূটনীতিতে জোর দিচ্ছে সরকার",
                        'sub_heading'      => "তৃণমূল থেকে প্রতিভা তুলে এনে বিশ্বমঞ্চে বাংলাদেশকে এগিয়ে নেওয়ার লক্ষ্য",
                        'content_shoulder' => "",
                        'brief'            => "যুব ও ক্রীড়া প্রতিমন্ত্রী বলেছেন, তৃণমূল থেকে প্রতিভাবান খেলোয়াড় খুঁজে বের করে আন্তর্জাতিক ক্রীড়াঙ্গনে বাংলাদেশকে এগিয়ে নিতে কাজ চলছে।",
                        "body"             => "বাংলাদেশের ক্রীড়াঙ্গনকে শুধু মাঠের সাফল্যে সীমাবদ্ধ না রেখে ক্রীড়া কূটনীতির মাধ্যমে আন্তর্জাতিক যোগাযোগ বাড়ানোর ওপর গুরুত্ব দিচ্ছে সরকার। যুব ও ক্রীড়া প্রতিমন্ত্রী আমিনুল হক বলেছেন, জেলা ও তৃণমূল পর্যায় থেকে প্রতিভাবান খেলোয়াড় তুলে এনে তাদের প্রশিক্ষণ, সুযোগ ও আন্তর্জাতিক প্রতিযোগিতার সঙ্গে যুক্ত করা হবে। ক্রীড়া সাংবাদিকদের একটি ফুটবল আয়োজনের উদ্বোধনে তিনি বলেন, খেলাধুলা তরুণ সমাজকে ইতিবাচক পথে রাখে এবং দেশের ভাবমূর্তি উন্নত করতে বড় ভূমিকা রাখে।",
                        'seo_keywords'     => "ক্রীড়া কূটনীতি, আমিনুল হক, খেলাধুলা, বাংলাদেশ",
                        'published_at'     => "2026-05-03 15:58:00",
                    ],

                    (object) [
                        "category_name"    => "খেলাধুলা",
                        'name'             => "চীন বিশ্বকাপে যাচ্ছেন বাংলাদেশের ১৩ আর্চার",
                        'sub_heading'      => "",
                        'content_shoulder' => "বিশ্বকাপ আর্চারি",
                        'brief'            => "চীনের সাংহাইয়ে বিশ্বকাপ আর্চারি স্টেজ-২ চ্যাম্পিয়নশিপে রিকার্ভ ও কম্পাউন্ড মিলিয়ে ১০ ইভেন্টে অংশ নেবে বাংলাদেশ।",
                        "body"             => "চীনের সাংহাইয়ে ৫ থেকে ১০ মে অনুষ্ঠেয় বিশ্বকাপ আর্চারি স্টেজ-২ চ্যাম্পিয়নশিপে বাংলাদেশ রিকার্ভ ও কম্পাউন্ড মিলিয়ে ১০ ইভেন্টে অংশ নিতে যাচ্ছে। লাল-সবুজের হয়ে মোট ১৩ জন আর্চার খেলবেন। প্রতিযোগিতাটিকে এশিয়ান গেমসের প্রস্তুতির বড় সুযোগ হিসেবে দেখছে দল। কোচিং স্টাফ মনে করছে, আন্তর্জাতিক মানের প্রতিপক্ষের বিপক্ষে নিয়মিত খেললে বাংলাদেশের আর্চারদের স্কোর, আত্মবিশ্বাস ও চাপ সামলানোর ক্ষমতা বাড়বে।",
                        'seo_keywords'     => "আর্চারি, বিশ্বকাপ, বাংলাদেশ, সাংহাই",
                        'published_at'     => "2026-05-03 09:18:00",
                    ],

                    (object) [
                        "category_name"    => "খেলাধুলা",
                        'name'             => "সন্তান রেখে বিশ্বকাপে যাচ্ছেন আর্চার নাসরিন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "১৩ মাসের সন্তান রেখে দেশের হয়ে বিশ্বকাপ আর্চারিতে অংশ নিতে যাচ্ছেন নাসরিন আক্তার, পরিবার ও কোচের সহায়তায় প্রস্তুতি নিচ্ছেন তিনি।",
                        "body"             => "বাংলাদেশের নারী আর্চার নাসরিন আক্তার ১৩ মাসের সন্তান রেখে চীনের সাংহাইয়ে বিশ্বকাপ আর্চারি স্টেজ-২ চ্যাম্পিয়নশিপে অংশ নিতে যাচ্ছেন। মা হিসেবে সিদ্ধান্তটি কঠিন হলেও দেশের হয়ে খেলার সুযোগকে তিনি বড় দায়িত্ব হিসেবে দেখছেন। নাসরিন জানিয়েছেন, স্বামী ও পরিবারের সহায়তা ছাড়া এই যাত্রা সম্ভব হতো না। কোচ মার্টিন ফ্রেডরিক তাঁর দৃঢ়তা ও স্কোর ধরে রাখার সক্ষমতার প্রশংসা করেছেন।",
                        'seo_keywords'     => "নাসরিন আক্তার, নারী আর্চার, বিশ্বকাপ, বাংলাদেশ",
                        'published_at'     => "2026-05-03 09:18:00",
                    ],

                    (object) [
                        "category_name"    => "খেলাধুলা",
                        'name'             => "আজ টিভিতে পিএসএল ফাইনালসহ ব্যস্ত খেলার দিন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আজ টিভিতে ক্রিকেট, ফুটবল ও আন্তর্জাতিক লিগের বেশ কয়েকটি গুরুত্বপূর্ণ ম্যাচ দেখা যাবে, পিএসএল ফাইনাল ঘিরে বাড়তি আগ্রহ তৈরি হয়েছে।",
                        "body"             => "আজকের টিভি সূচিতে ক্রিকেট ও ফুটবলের একাধিক গুরুত্বপূর্ণ ম্যাচ রয়েছে। পাকিস্তান সুপার লিগের ফাইনাল, আইপিএল, লা লিগা ও ইংলিশ প্রিমিয়ার লিগের ম্যাচ ঘিরে দর্শকদের আগ্রহ বেশি। বাংলাদেশের দর্শকদের জন্য টি স্পোর্টস, নাগরিক, স্টার স্পোর্টস ও বিভিন্ন ডিজিটাল প্ল্যাটফর্মে ম্যাচ দেখার সুযোগ আছে। বড় টুর্নামেন্টের ফাইনাল, শীর্ষ লিগের শিরোপা লড়াই এবং বাংলাদেশি ক্রিকেটারদের অংশগ্রহণ—সব মিলিয়ে দিনটি ক্রীড়াপ্রেমীদের জন্য ব্যস্ত।",
                        'seo_keywords'     => "টিভিতে খেলা, পিএসএল ফাইনাল, আইপিএল, ফুটবল",
                        'published_at'     => "2026-05-03 06:00:00",
                    ],

                    (object) [
                        "category_name"    => "খেলাধুলা",
                        'name'             => "এশিয়া কাপ আর্চারিতে সাফল্য পাওয়া আর্চারদের পুরস্কার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এশিয়া কাপ আর্চারিতে স্বর্ণজয়ী বাংলাদেশি আর্চার ও কোচিং স্টাফদের নগদ পুরস্কার দেওয়া হয়েছে, উৎসাহ বাড়ছে খেলোয়াড়দের।",
                        "body"             => "এশিয়া কাপ আর্চারিতে স্বর্ণজয়ী বাংলাদেশি আর্চারদের নগদ পুরস্কার দেওয়া হয়েছে। হিমু বাছাড়, ঐশ্বর্য্য রহমান ও নেওয়াজ আহমেদ রাকিবকে ৪০০ ডলার করে এবং তিন কোচিং স্টাফকে ২০০ ডলার করে পুরস্কৃত করা হয়েছে। আন্তর্জাতিক প্রতিযোগিতায় সাফল্যের পর এমন পুরস্কার খেলোয়াড়দের মনোবল বাড়াতে পারে। ফেডারেশন ও ক্রীড়া সংগঠকদের মতে, ধারাবাহিক প্রণোদনা, ভালো প্রশিক্ষণ ও নিয়মিত বিদেশি প্রতিযোগিতা বাংলাদেশের আর্চারিকে আরও এগিয়ে নেবে।",
                        'seo_keywords'     => "আর্চারি পুরস্কার, এশিয়া কাপ, বাংলাদেশ আর্চারি, খেলাধুলা",
                        'published_at'     => "2026-05-03 10:56:00",
                    ],

                    (object) [
                        "category_name"    => "ক্রিকেট",
                        'name'             => "হারের পর ব্যাটিং নিয়ে লিটনের আক্ষেপ",
                        'sub_heading'      => "নিউজিল্যান্ডের বিপক্ষে সিরিজ নির্ধারণী ম্যাচে বড় সংগ্রহ গড়তে পারেনি বাংলাদেশ",
                        'content_shoulder' => "",
                        'brief'            => "নিউজিল্যান্ডের কাছে তৃতীয় টি-টোয়েন্টিতে ৬ উইকেটে হারের পর বাংলাদেশ অধিনায়ক লিটন দাস ব্যাটিং ব্যর্থতার কথা স্বীকার করেছেন।",
                        "body"             => "নিউজিল্যান্ডের বিপক্ষে সিরিজের তৃতীয় টি-টোয়েন্টিতে ৬ উইকেটে হারের পর বাংলাদেশ অধিনায়ক লিটন দাস বলেছেন, দল বড় সংগ্রহ গড়ার মতো ব্যাটিং করতে পারেনি। শুরুতে উইকেট হারানো এবং মাঝের ওভারে রান তোলার গতি কমে যাওয়ায় বাংলাদেশ চাপ থেকে বের হতে পারেনি। বোলারদের লড়াই সত্ত্বেও স্কোরবোর্ডে পর্যাপ্ত রান না থাকায় ম্যাচ নিয়ন্ত্রণে রাখা কঠিন হয়ে যায়। লিটন মনে করেন, আন্তর্জাতিক ক্রিকেটে সিরিজ জিততে হলে ব্যাটারদের দায়িত্ব আরও ভালোভাবে নিতে হবে।",
                        'seo_keywords'     => "লিটন দাস, বাংলাদেশ ক্রিকেট, নিউজিল্যান্ড, টি-টোয়েন্টি",
                        'published_at'     => "2026-05-02 21:56:00",
                    ],

                    (object) [
                        "category_name"    => "ক্রিকেট",
                        'name'             => "শেষ ম্যাচ জিতে সিরিজে সমতা ফেরাল নিউজিল্যান্ড",
                        'sub_heading'      => "",
                        'content_shoulder' => "সিরিজ হাতছাড়া বাংলাদেশ",
                        'brief'            => "মিরপুরে শেষ টি-টোয়েন্টিতে বাংলাদেশকে হারিয়ে নিউজিল্যান্ড সিরিজে সমতা ফিরিয়েছে, ইতিহাস গড়ার সুযোগ হারিয়েছে স্বাগতিকরা।",
                        "body"             => "চট্টগ্রামে দ্বিতীয় টি-টোয়েন্টি বৃষ্টিতে ভেসে যাওয়ার পর মিরপুরের শেষ ম্যাচটি ছিল সিরিজ নির্ধারণী। প্রথম ম্যাচ জিতে এগিয়ে থাকা বাংলাদেশ এই ম্যাচে জিতলে নিউজিল্যান্ডের বিপক্ষে এক সফরে একাধিক সিরিজ জয়ের কীর্তি গড়তে পারত। কিন্তু টস হেরে ব্যাটিংয়ে নেমে দ্রুত উইকেট হারানো এবং বড় জুটি গড়তে না পারায় স্বাগতিকরা কাঙ্ক্ষিত সংগ্রহ পায়নি। নিউজিল্যান্ড লক্ষ্য তাড়া করে সিরিজে সমতা ফেরায়।",
                        'seo_keywords'     => "বাংলাদেশ নিউজিল্যান্ড, টি-টোয়েন্টি সিরিজ, মিরপুর, ক্রিকেট",
                        'published_at'     => "2026-05-02 18:20:00",
                    ],

                    (object) [
                        "category_name"    => "ক্রিকেট",
                        'name'             => "বাংলাদেশ নারী দলকে হোয়াইটওয়াশ করেছে শ্রীলঙ্কা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সিলেটে তৃতীয় ও শেষ টি-টোয়েন্টিতে ৩ রানে হেরে বাংলাদেশ নারী দল শ্রীলঙ্কার কাছে ৩-০ ব্যবধানে সিরিজ হারল।",
                        "body"             => "সিলেটে বৃষ্টিবিঘ্নিত তৃতীয় ও শেষ টি-টোয়েন্টিতে আশা জাগিয়েও ৩ রানে হেরেছে বাংলাদেশ নারী ক্রিকেট দল। এই জয়ে শ্রীলঙ্কা সিরিজ ৩-০ ব্যবধানে জিতে বাংলাদেশকে হোয়াইটওয়াশ করেছে। শেষ দিকে সমীকরণ কঠিন হলেও বাংলাদেশের ব্যাটাররা ম্যাচ জেতানোর মতো স্থিরতা দেখাতে পারেননি। সিরিজজুড়ে ব্যাটিং ধারাবাহিকতা, চাপের মুহূর্তে সিদ্ধান্ত এবং ডেথ ওভারে রান তোলার ব্যর্থতা বাংলাদেশের জন্য বড় শিক্ষা হয়ে থাকল।",
                        'seo_keywords'     => "বাংলাদেশ নারী ক্রিকেট, শ্রীলঙ্কা, টি-টোয়েন্টি, হোয়াইটওয়াশ",
                        'published_at'     => "2026-05-02 18:58:00",
                    ],

                    (object) [
                        "category_name"    => "ক্রিকেট",
                        'name'             => "ওয়ানডে র‌্যাঙ্কিংয়ে বাংলাদেশের তিন পেসারের বড় লাফ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নিউজিল্যান্ডের বিপক্ষে ওয়ানডে সিরিজ জয়ের পর শরিফুল, মোস্তাফিজ ও নাহিদ রানা আইসিসি র‌্যাঙ্কিংয়ে বড় উন্নতি করেছেন।",
                        "body"             => "ঘরের মাঠে নিউজিল্যান্ডের বিপক্ষে তিন ম্যাচের ওয়ানডে সিরিজ ২-১ ব্যবধানে জয়ের পর বাংলাদেশের পেসারদের র‌্যাঙ্কিংয়ে বড় উন্নতি হয়েছে। সিরিজজুড়ে শরিফুল ইসলাম, মোস্তাফিজুর রহমান ও নাহিদ রানা বল হাতে দাপট দেখিয়েছেন। তিন পেসার মিলে উল্লেখযোগ্য উইকেট নিয়ে বাংলাদেশের সিরিজ জয়ে ভূমিকা রাখেন। আইসিসির হালনাগাদ র‌্যাঙ্কিংয়ে শরিফুল, মোস্তাফিজ ও নাহিদের অবস্থান উন্নত হওয়া বাংলাদেশের পেস আক্রমণের ধারাবাহিক উন্নতির ইঙ্গিত দিচ্ছে।",
                        'seo_keywords'     => "আইসিসি র‌্যাঙ্কিং, শরিফুল ইসলাম, মোস্তাফিজ, নাহিদ রানা",
                        'published_at'     => "2026-04-29 17:20:00",
                    ],

                    (object) [
                        "category_name"    => "ক্রিকেট",
                        'name'             => "পিএসএলের ফাইনালে খেলার অনুমতি পেলেন নাহিদ রানা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশ ক্রিকেট বোর্ড আগের সিদ্ধান্ত পুনর্বিবেচনা করে নাহিদ রানাকে পিএসএল ফাইনালে পেশোয়ার জালমির হয়ে খেলার অনুমতি দিয়েছে।",
                        "body"             => "পাকিস্তান সুপার লিগের ফাইনালে পেশোয়ার জালমির হয়ে খেলার জন্য পেসার নাহিদ রানাকে অনুমতি দিয়েছে বাংলাদেশ ক্রিকেট বোর্ড। আগে তাঁর অনাপত্তিপত্র নিয়ে অনিশ্চয়তা থাকলেও বিসিবি সিদ্ধান্তটি পুনর্বিবেচনা করে। সাম্প্রতিক সময়ে জাতীয় দলের হয়ে ভালো পারফরম্যান্স করা নাহিদের জন্য পিএসএলের ফাইনাল বড় মঞ্চ। তবে ফ্র্যাঞ্চাইজি ক্রিকেটে অংশগ্রহণের পাশাপাশি জাতীয় দলের সূচি, ফিটনেস ও ওয়ার্কলোড ব্যবস্থাপনাও গুরুত্বপূর্ণ বলে মনে করছেন সংশ্লিষ্টরা।",
                        'seo_keywords'     => "নাহিদ রানা, পিএসএল, পেশোয়ার জালমি, বিসিবি",
                        'published_at'     => "2026-04-29 16:07:00",
                    ],

                    (object) [
                        "category_name"    => "ফুটবল",
                        'name'             => "স্বপ্ন এখনও বেঁচে আছে, বললেন আর্সেনাল কোচ",
                        'sub_heading'      => "ফুলহামকে হারিয়ে প্রিমিয়ার লিগের শিরোপা দৌড়ে নিজেদের আশা ধরে রেখেছে আর্সেনাল",
                        'content_shoulder' => "",
                        'brief'            => "ফুলহামকে ৩-০ গোলে হারানোর পর আর্সেনাল কোচ মিকেল আর্তেতা বলেছেন, শিরোপার স্বপ্ন এখনও বেঁচে আছে।",
                        "body"             => "ইংলিশ প্রিমিয়ার লিগে ফুলহামকে ৩-০ গোলে হারিয়ে শিরোপা দৌড়ে নিজেদের আশা আরও জোরালো করেছে আর্সেনাল। ম্যাচের পর কোচ মিকেল আর্তেতা বলেন, দলের স্বপ্ন এখনও বেঁচে আছে এবং খেলোয়াড়রা শেষ পর্যন্ত লড়াই চালিয়ে যাবে। মৌসুমের গুরুত্বপূর্ণ সময়ে এমন জয় দলের আত্মবিশ্বাস বাড়িয়েছে। তবে শিরোপা নিশ্চিত করতে হলে বাকি ম্যাচগুলোতেও ধারাবাহিকতা ধরে রাখতে হবে এবং প্রতিদ্বন্দ্বী দলের ফলের দিকেও তাকিয়ে থাকতে হবে।",
                        'seo_keywords'     => "আর্সেনাল, মিকেল আর্তেতা, প্রিমিয়ার লিগ, ফুটবল",
                        'published_at'     => "2026-05-03 14:19:00",
                    ],

                    (object) [
                        "category_name"    => "ফুটবল",
                        'name'             => "৩ গোলে এগিয়ে থেকেও হারল মেসির ইন্টার মায়ামি",
                        'sub_heading'      => "",
                        'content_shoulder' => "মায়ামির বড় ধাক্কা",
                        'brief'            => "অরল্যান্ডো সিটির বিপক্ষে ৩-০ গোলে এগিয়ে থেকেও ৪-৩ ব্যবধানে হেরে গেছে ইন্টার মায়ামি, হতাশ হয়েছেন লিওনেল মেসি।",
                        "body"             => "মেজর লিগ সকারে অরল্যান্ডো সিটির বিপক্ষে ৩-০ গোলে এগিয়ে গিয়েও শেষ পর্যন্ত ৪-৩ ব্যবধানে হেরেছে লিওনেল মেসির ইন্টার মায়ামি। ম্যাচে বড় লিড নেওয়ার পরও রক্ষণভাগের ভুল, গতি হারানো এবং শেষ দিকে চাপ সামলাতে না পারায় মায়ামি তিন পয়েন্ট হারায়। মেসি ম্যাচ শেষে হতাশা প্রকাশ করেছেন এবং দলের পারফরম্যান্সকে অগ্রহণযোগ্য বলে সতীর্থদের বার্তা দিয়েছেন। নতুন স্টেডিয়ামে প্রথম জয়ের অপেক্ষা আরও বাড়ল মায়ামির।",
                        'seo_keywords'     => "লিওনেল মেসি, ইন্টার মায়ামি, অরল্যান্ডো সিটি, এমএলএস",
                        'published_at'     => "2026-05-03 12:06:00",
                    ],

                    (object) [
                        "category_name"    => "ফুটবল",
                        'name'             => "লা লিগা শিরোপার আরও কাছে বার্সেলোনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ওসাসুনাকে ২-১ গোলে হারিয়ে লা লিগার শীর্ষে ব্যবধান বাড়িয়েছে বার্সেলোনা, রিয়ালের ফলের ওপর নির্ভর করছে উৎসবের সময়।",
                        "body"             => "ওসাসুনার মাঠে কঠিন লড়াইয়ে ২-১ গোলের জয়ে লা লিগা শিরোপার আরও কাছে পৌঁছেছে বার্সেলোনা। এই জয়ে শীর্ষে তাদের পয়েন্ট ব্যবধান আরও বেড়েছে। রিয়াল মাদ্রিদ পরের ম্যাচে পয়েন্ট হারালে বার্সেলোনার শিরোপা নিশ্চিত হতে পারে, আর রিয়াল জিতলে অপেক্ষা বাড়বে। কোচ হান্সি ফ্লিকের দল মৌসুমজুড়ে ধারাবাহিক ফুটবল খেলেছে এবং শিরোপা নিশ্চিত না হওয়া পর্যন্ত সতর্ক থাকার কথা বলছে।",
                        'seo_keywords'     => "বার্সেলোনা, লা লিগা, ওসাসুনা, ফুটবল",
                        'published_at'     => "2026-05-03 11:45:00",
                    ],

                    (object) [
                        "category_name"    => "ফুটবল",
                        'name'             => "মামুনুলের বিদায়ী ম্যাচ জয়ে রাঙাল ফর্টিস",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশ ফুটবলের অভিজ্ঞ মিডফিল্ডার মামুনুল ইসলামের বিদায়ী ম্যাচে রহমতগঞ্জকে ৩-২ গোলে হারিয়েছে ফর্টিস এফসি।",
                        "body"             => "বাংলাদেশ ফুটবলের পরিচিত মুখ মামুনুল ইসলাম পেশাদার ফুটবল থেকে বিদায়ের ঘোষণা দিয়েছেন। তাঁর বিদায়ী ম্যাচে ফর্টিস এফসি রহমতগঞ্জকে ৩-২ গোলে হারিয়ে দিনটি স্মরণীয় করে রাখে। সতীর্থরা তাঁকে গার্ড অব অনার দেন। দীর্ঘ ক্যারিয়ারে জাতীয় দল ও ক্লাব ফুটবলে মামুনুলের অবদান উল্লেখযোগ্য। বিদায়ের দিনে জয় পাওয়ায় আবেগঘন পরিবেশ তৈরি হয়। একই দিনে আবাহনীও জয়ে মাঠ ছাড়ে, যা ঘরোয়া ফুটবল সূচিকে আলোচনায় আনে।",
                        'seo_keywords'     => "মামুনুল ইসলাম, ফর্টিস এফসি, ঘরোয়া ফুটবল, বিদায়ী ম্যাচ",
                        'published_at'     => "2026-05-01 19:39:00",
                    ],

                    (object) [
                        "category_name"    => "ফুটবল",
                        'name'             => "বিএসজেএ মিডিয়া কাপ ফুটবলে ৩২ সংবাদমাধ্যম",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কুল-বিএসজেএ মিডিয়া কাপ ফুটবল টুর্নামেন্টে দেশের ৩২ সংবাদমাধ্যম অংশ নিচ্ছে, ড্র ও জার্সি উন্মোচন সম্পন্ন হয়েছে।",
                        "body"             => "কুল-বিএসজেএ মিডিয়া কাপ ফুটবল টুর্নামেন্ট-২০২৬ ঘিরে সাংবাদিকদের ক্রীড়া মিলনমেলা শুরু হচ্ছে। বাংলাদেশ ফুটবল ফেডারেশনের আর্টিফিশিয়াল টার্ফে ৩ থেকে ৭ মে পর্যন্ত আয়োজনে দেশের ৩২টি সংবাদমাধ্যম অংশ নেবে। সংবাদ সম্মেলনে টুর্নামেন্টের জার্সি উন্মোচন, গ্রুপিং ও ড্র অনুষ্ঠিত হয়েছে। এই আয়োজন পেশাগত ব্যস্ততার বাইরে সাংবাদিকদের মধ্যে সৌহার্দ্য, ফিটনেস সচেতনতা ও ক্রীড়া সংস্কৃতি বাড়াতে ভূমিকা রাখবে।",
                        'seo_keywords'     => "বিএসজেএ, মিডিয়া কাপ, ফুটবল, সাংবাদিক",
                        'published_at'     => "2026-04-30 15:30:00",
                    ],

                    (object) [
                        "category_name"    => "টেনিস",
                        'name'             => "ফ্রেঞ্চ ওপেনে খেলছেন না কার্লোস আলকারেজ",
                        'sub_heading'      => "কব্জির চোটে বর্তমান চ্যাম্পিয়নের রোলাঁ গারো মিশন শেষ হওয়ার আগেই থেমে গেল",
                        'content_shoulder' => "",
                        'brief'            => "ডান হাতের কব্জির চোটের কারণে এবারের ফ্রেঞ্চ ওপেনে খেলবেন না বর্তমান চ্যাম্পিয়ন কার্লোস আলকারেজ।",
                        "body"             => "ফ্রেঞ্চ ওপেনের বর্তমান চ্যাম্পিয়ন কার্লোস আলকারেজ কব্জির চোটের কারণে এবারের আসরে অংশ নিতে পারছেন না। স্প্যানিশ তারকা জানিয়েছেন, দীর্ঘ ক্যারিয়ারের কথা ভেবে শতভাগ ফিট না হয়ে তাড়াহুড়া করে কোর্টে ফিরতে চান না। রোলাঁ গারোতে তাঁর অনুপস্থিতি পুরুষ এককের লড়াইকে নতুনভাবে খুলে দেবে। আলকারেজ না থাকায় অন্য শীর্ষ খেলোয়াড়দের সুযোগ বাড়বে, তবে টেনিসপ্রেমীরা বর্তমান চ্যাম্পিয়নের লড়াই না দেখতে পাওয়ায় হতাশ।",
                        'seo_keywords'     => "কার্লোস আলকারেজ, ফ্রেঞ্চ ওপেন, টেনিস, কব্জির চোট",
                        'published_at'     => "2026-04-25 12:13:00",
                    ],

                    (object) [
                        "category_name"    => "টেনিস",
                        'name'             => "লরিয়াস বর্ষসেরা আলকারেজ ও সাবালেঙ্কা",
                        'sub_heading'      => "",
                        'content_shoulder' => "টেনিসের বড় স্বীকৃতি",
                        'brief'            => "লরিয়াস অ্যাওয়ার্ডসে বর্ষসেরা পুরুষ ও নারী ক্রীড়াবিদের পুরস্কার জিতেছেন কার্লোস আলকারেজ ও আরিনা সাবালেঙ্কা।",
                        "body"             => "মাদ্রিদে লরিয়াস ওয়ার্ল্ড স্পোর্টস অ্যাওয়ার্ডসের মঞ্চে টেনিসের দাপট দেখা গেছে। বর্ষসেরা পুরুষ ক্রীড়াবিদের পুরস্কার পেয়েছেন কার্লোস আলকারেজ, আর নারী বিভাগে সেরা হয়েছেন আরিনা সাবালেঙ্কা। ২০২৫ মৌসুমে ধারাবাহিক পারফরম্যান্স, বড় শিরোপা এবং র‌্যাঙ্কিংয়ের শীর্ষে থাকা—এসবের স্বীকৃতি হিসেবেই তাঁরা এই সম্মান পেয়েছেন। টেনিসে নতুন প্রজন্মের প্রভাব যে আরও শক্তিশালী হচ্ছে, এই পুরস্কার সেটিও মনে করিয়ে দেয়।",
                        'seo_keywords'     => "লরিয়াস অ্যাওয়ার্ডস, আলকারেজ, সাবালেঙ্কা, টেনিস",
                        'published_at'     => "2026-04-21 12:07:00",
                    ],

                    (object) [
                        "category_name"    => "টেনিস",
                        'name'             => "টেবিল টেনিসে খই খইদের জন্য জাপানের কোচ ঢাকায়",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশের টেবিল টেনিস খেলোয়াড়দের মানোন্নয়নে জাপানের অভিজ্ঞ কোচ ইউতাকা নাকানো দুই বছরের জন্য ঢাকায় এসেছেন।",
                        "body"             => "বাংলাদেশের টেবিল টেনিসে সাম্প্রতিক সাফল্যের ধারাবাহিকতা ধরে রাখতে জাপানের অভিজ্ঞ কোচ ইউতাকা নাকানোকে দুই বছরের জন্য নিয়োগ দেওয়া হয়েছে। খই খই সাই মারমা, জাভেদ ও অন্যান্য খেলোয়াড়দের টেকনিক, ফিটনেস ও ম্যাচ টেম্পারামেন্ট উন্নয়নে তিনি কাজ করবেন। আন্তর্জাতিক প্রতিযোগিতায় ভালো করতে হলে দীর্ঘমেয়াদি কোচিং, নিয়মিত ট্রেনিং পার্টনার এবং বৈজ্ঞানিক প্রস্তুতি জরুরি। ফেডারেশন আশা করছে, বিদেশি কোচের অভিজ্ঞতা খেলোয়াড়দের মান বাড়াবে।",
                        'seo_keywords'     => "টেবিল টেনিস, ইউতাকা নাকানো, খই খই, বাংলাদেশ",
                        'published_at'     => "2026-04-15 10:30:00",
                    ],

                    (object) [
                        "category_name"    => "টেনিস",
                        'name'             => "আইটিএফ এশিয়া অনূর্ধ্ব-১৪ টেনিসে সেমিফাইনালে থামল বাংলাদেশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কম্বোডিয়ায় আইটিএফ এশিয়া অনূর্ধ্ব-১৪ ডেভেলপমেন্ট টেনিসে বাংলাদেশের বালিকা দ্বৈত জুটি সেমিফাইনালে হেরে বিদায় নিয়েছে।",
                        "body"             => "আইটিএফ এশিয়া অনূর্ধ্ব-১৪ ডেভেলপমেন্ট চ্যাম্পিয়নশিপস টেনিসে বাংলাদেশের বালিকা দ্বৈত জুটি সেমিফাইনাল থেকে বিদায় নিয়েছে। কম্বোডিয়ায় অনুষ্ঠিত প্রতিযোগিতায় বাংলাদেশের মাসতুরা আফরিন ও তাঁর সঙ্গী সিঙ্গাপুরের বিপক্ষে লড়াই করলেও শেষ পর্যন্ত জিততে পারেননি। বয়সভিত্তিক আন্তর্জাতিক প্রতিযোগিতায় এমন অভিজ্ঞতা তরুণ খেলোয়াড়দের জন্য গুরুত্বপূর্ণ। ম্যাচের চাপ, কোর্টের গতি ও ভিন্ন প্রতিপক্ষের কৌশল শেখার সুযোগ ভবিষ্যৎ উন্নতিতে কাজে লাগবে।",
                        'seo_keywords'     => "আইটিএফ টেনিস, অনূর্ধ্ব-১৪, বাংলাদেশ টেনিস, সেমিফাইনাল",
                        'published_at'     => "2026-02-05 17:20:00",
                    ],

                    (object) [
                        "category_name"    => "টেনিস",
                        'name'             => "অস্ট্রেলিয়ান ওপেনে ইতিহাস গড়েছিলেন আলকারেজ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নোভাক জোকোভিচকে হারিয়ে অস্ট্রেলিয়ান ওপেন জিতে সবচেয়ে কম বয়সে ক্যারিয়ার গ্র্যান্ড স্লাম পূর্ণ করেছিলেন আলকারেজ।",
                        "body"             => "চলতি বছরের শুরুতে অস্ট্রেলিয়ান ওপেন জিতে টেনিস ইতিহাসে নতুন অধ্যায় লিখেছিলেন কার্লোস আলকারেজ। ফাইনালে নোভাক জোকোভিচকে হারিয়ে তিনি ক্যারিয়ার গ্র্যান্ড স্লাম পূর্ণ করেন এবং সবচেয়ে কম বয়সী পুরুষ খেলোয়াড় হিসেবে এই কীর্তি গড়েন। অস্ট্রেলিয়ান ওপেন জয়ের মধ্য দিয়ে ইউএস ওপেন, উইম্বলডন ও ফ্রেঞ্চ ওপেনের সঙ্গে তাঁর শিরোপার তালিকা পূর্ণতা পায়। তবে পরে কব্জির চোট তাঁকে ফ্রেঞ্চ ওপেন থেকে ছিটকে দেওয়ায় মৌসুমের ধারাবাহিকতা থমকে গেছে।",
                        'seo_keywords'     => "অস্ট্রেলিয়ান ওপেন, আলকারেজ, জোকোভিচ, গ্র্যান্ড স্লাম",
                        'published_at'     => "2026-02-01 10:05:00",
                    ],

                    (object) [
                        "category_name"    => "স্থানীয় খেলা",
                        'name'             => "মিডিয়া কাপ ফুটবলে সংবাদমাধ্যমগুলোর উৎসব শুরু",
                        'sub_heading'      => "বাফুফে টার্ফে ৩২ দলের অংশগ্রহণে সাংবাদিকদের ফুটবল আয়োজন",
                        'content_shoulder' => "",
                        'brief'            => "বিএসজেএ মিডিয়া কাপ ফুটবল টুর্নামেন্টে দেশের ৩২ সংবাদমাধ্যম অংশ নিচ্ছে, স্থানীয় ক্রীড়া আয়োজনে তৈরি হয়েছে উৎসবের আমেজ।",
                        "body"             => "বাংলাদেশ ফুটবল ফেডারেশনের আর্টিফিশিয়াল টার্ফে শুরু হতে যাচ্ছে কুল-বিএসজেএ মিডিয়া কাপ ফুটবল টুর্নামেন্ট। স্থানীয় ক্রীড়া আয়োজন হিসেবে এটি সাংবাদিকদের মধ্যে সবচেয়ে পরিচিত ফুটবল প্রতিযোগিতাগুলোর একটি। ৩২ সংবাদমাধ্যমের অংশগ্রহণে টুর্নামেন্ট ঘিরে গ্রুপিং, ড্র ও জার্সি উন্মোচন সম্পন্ন হয়েছে। আয়োজকেরা মনে করছেন, পেশাগত সম্পর্কের বাইরে মাঠের বন্ধুত্ব, সৌহার্দ্য ও স্বাস্থ্যসচেতনতা বাড়াতে এই প্রতিযোগিতা গুরুত্বপূর্ণ ভূমিকা রাখবে।",
                        'seo_keywords'     => "স্থানীয় খেলা, মিডিয়া কাপ, বিএসজেএ, ফুটবল",
                        'published_at'     => "2026-04-30 15:30:00",
                    ],

                    (object) [
                        "category_name"    => "স্থানীয় খেলা",
                        'name'             => "মামুনুলের বিদায়ে আবেগঘন ঘরোয়া ফুটবল",
                        'sub_heading'      => "",
                        'content_shoulder' => "দেশীয় ফুটবলের বিদায়",
                        'brief'            => "দীর্ঘদিনের পরিচিত ফুটবলার মামুনুল ইসলামের বিদায়ী ম্যাচ ঘিরে ঘরোয়া ফুটবলে আবেগঘন পরিবেশ তৈরি হয়েছে।",
                        "body"             => "বাংলাদেশের ঘরোয়া ফুটবলে মামুনুল ইসলামের বিদায় একটি আবেগঘন মুহূর্ত হয়ে উঠেছে। বিকেএসপি থেকে ফুটবলে উঠে আসা মামুনুল জাতীয় দল ও বিভিন্ন ক্লাবে দীর্ঘ সময় খেলেছেন। তাঁর বিদায়ী দিনে সতীর্থদের গার্ড অব অনার, সমর্থকদের শুভেচ্ছা এবং ফর্টিসের জয় স্থানীয় ফুটবলপ্রেমীদের আলোচনার কেন্দ্রে ছিল। তরুণ ফুটবলারদের জন্য মামুনুলের ক্যারিয়ার একটি উদাহরণ—দীর্ঘ প্রস্তুতি, শৃঙ্খলা ও ক্লাব ফুটবলে ধারাবাহিকতা কীভাবে একজন খেলোয়াড়কে পরিচিত করে তোলে।",
                        'seo_keywords'     => "মামুনুল ইসলাম, স্থানীয় ফুটবল, ঘরোয়া লিগ, ফর্টিস",
                        'published_at'     => "2026-05-01 19:39:00",
                    ],

                    (object) [
                        "category_name"    => "স্থানীয় খেলা",
                        'name'             => "নারী হকির সাফল্যে স্থানীয় খেলাধুলায় নতুন আলো",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এশিয়ান গেমস বাছাইয়ে বাংলাদেশ নারী হকি দলের ফাইনালে ওঠা স্থানীয় হকি অবকাঠামো ও মেয়েদের অংশগ্রহণ নিয়ে নতুন আশাবাদ তৈরি করেছে।",
                        "body"             => "এশিয়ান গেমস বাছাইয়ে বাংলাদেশ নারী হকি দলের ফাইনালে ওঠা দেশের স্থানীয় হকি সংস্কৃতিতে নতুন আগ্রহ তৈরি করেছে। সিঙ্গাপুরকে হারিয়ে প্রথমবার অংশ নিয়েই ফাইনালে ওঠার ঘটনা দেখিয়েছে, পর্যাপ্ত সুযোগ পেলে মেয়েরাও আন্তর্জাতিক পর্যায়ে সাফল্য আনতে পারে। স্থানীয় পর্যায়ে নারী হকি খেলোয়াড় তৈরির জন্য স্কুল, জেলা ও ক্লাবভিত্তিক প্রতিযোগিতা বাড়ানো জরুরি। ফেডারেশন ও ক্রীড়া সংগঠকরা এই সাফল্যকে দীর্ঘমেয়াদি পরিকল্পনায় রূপ দিতে পারলে হকিতে নতুন প্রজন্ম তৈরি হবে।",
                        'seo_keywords'     => "নারী হকি, স্থানীয় খেলা, বাংলাদেশ হকি, এশিয়ান গেমস",
                        'published_at'     => "2026-04-28 18:17:00",
                    ],

                    (object) [
                        "category_name"    => "স্থানীয় খেলা",
                        'name'             => "দেশীয় আর্চারদের পুরস্কার স্থানীয় প্রতিভাদের অনুপ্রেরণা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আন্তর্জাতিক সাফল্যের পর দেশীয় আর্চারদের নগদ পুরস্কার স্থানীয় পর্যায়ের তরুণ খেলোয়াড়দের অনুপ্রাণিত করবে বলে মনে করছেন কোচরা।",
                        "body"             => "এশিয়া কাপ আর্চারিতে সাফল্য পাওয়া বাংলাদেশি আর্চারদের নগদ পুরস্কার দেওয়াকে স্থানীয় ক্রীড়া উন্নয়নের ইতিবাচক উদ্যোগ হিসেবে দেখা হচ্ছে। জেলা ও একাডেমি পর্যায়ে আর্চারি শেখা তরুণরা আন্তর্জাতিক পদকজয়ীদের দেখে অনুপ্রাণিত হয়। পুরস্কার খেলোয়াড়দের আর্থিক সহায়তা দেওয়ার পাশাপাশি তাদের পরিশ্রমের স্বীকৃতিও দেয়। স্থানীয় প্রতিভা ধরে রাখতে নিয়মিত প্রতিযোগিতা, মানসম্মত সরঞ্জাম, কোচিং এবং সাফল্যের পর যথাযথ সম্মান জরুরি।",
                        'seo_keywords'     => "স্থানীয় আর্চারি, তরুণ খেলোয়াড়, পুরস্কার, বাংলাদেশ",
                        'published_at'     => "2026-05-03 10:56:00",
                    ],

                    (object) [
                        "category_name"    => "স্থানীয় খেলা",
                        'name'             => "তৃণমূল থেকে খেলোয়াড় বাছাইয়ে গুরুত্ব বাড়ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সরকার ও ক্রীড়া সংগঠকেরা তৃণমূল থেকে প্রতিভাবান খেলোয়াড় খুঁজে বের করে জাতীয় পর্যায়ে তুলে আনার পরিকল্পনায় জোর দিচ্ছেন।",
                        "body"             => "দেশের স্থানীয় খেলাধুলা শক্তিশালী করতে তৃণমূল পর্যায় থেকে খেলোয়াড় বাছাইকে অগ্রাধিকার দেওয়া হচ্ছে। জেলা, উপজেলা, স্কুল ও ক্লাবভিত্তিক প্রতিযোগিতা বাড়ালে ক্রিকেট-ফুটবলের বাইরে আর্চারি, হকি, টেবিল টেনিস ও অ্যাথলেটিকসেও নতুন প্রতিভা পাওয়া সম্ভব। ক্রীড়া প্রতিমন্ত্রী বলেছেন, প্রতিভাবান খেলোয়াড়দের খুঁজে বের করে প্রশিক্ষণ ও আন্তর্জাতিক মঞ্চে পাঠানোর ওপর সরকার গুরুত্ব দিচ্ছে। এই উদ্যোগ সফল করতে নিয়মিত বাজেট, কোচ ও অবকাঠামো দরকার।",
                        'seo_keywords'     => "তৃণমূল খেলা, স্থানীয় ক্রীড়া, খেলোয়াড় বাছাই, বাংলাদেশ",
                        'published_at'     => "2026-05-03 15:58:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক খেলা",
                        'name'             => "অরল্যান্ডোর বিপক্ষে হার মেনে নিতে পারছেন না মেসি",
                        'sub_heading'      => "৩ গোলে এগিয়েও হারের পর ইন্টার মায়ামির ড্রেসিংরুমে কঠোর বার্তা",
                        'content_shoulder' => "",
                        'brief'            => "অরল্যান্ডো সিটির বিপক্ষে ৪-৩ গোলে হারের পর ইন্টার মায়ামির পারফরম্যান্সকে অগ্রহণযোগ্য বলেছেন লিওনেল মেসি।",
                        "body"             => "ইন্টার মায়ামি ৩-০ গোলে এগিয়ে থেকেও অরল্যান্ডো সিটির কাছে ৪-৩ ব্যবধানে হেরে যাওয়ায় অধিনায়ক লিওনেল মেসি ক্ষুব্ধ হয়েছেন। ম্যাচ শেষে তিনি ড্রেসিংরুমে সতীর্থদের সঙ্গে কথা বলেন এবং এমন পারফরম্যান্সকে অগ্রহণযোগ্য বলে বার্তা দেন। অরল্যান্ডোর মার্টিন ওহেদা হ্যাটট্রিক করে ম্যাচে দলকে ফেরান, আর যোগ করা সময়ে টাইরেস স্পাইসার জয়সূচক গোল করেন। আন্তর্জাতিক ফুটবল অনুসারীদের কাছে ম্যাচটি মায়ামির রক্ষণ দুর্বলতার বড় উদাহরণ হয়ে থাকল।",
                        'seo_keywords'     => "মেসি, ইন্টার মায়ামি, অরল্যান্ডো সিটি, আন্তর্জাতিক ফুটবল",
                        'published_at'     => "2026-05-03 12:40:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক খেলা",
                        'name'             => "বার্সেলোনার চোখ এখন লা লিগা উৎসবে",
                        'sub_heading'      => "",
                        'content_shoulder' => "ইউরোপিয়ান ফুটবল",
                        'brief'            => "লা লিগায় শীর্ষে বড় ব্যবধান গড়ার পর বার্সেলোনা শিরোপা উৎসবের অপেক্ষায়, তবে খেলোয়াড়রা এখনও সতর্ক থাকার কথা বলছেন।",
                        "body"             => "লা লিগায় ৩৪ ম্যাচে ৮৮ পয়েন্ট নিয়ে শীর্ষে থাকা বার্সেলোনা এখন শিরোপা উৎসবের খুব কাছে। রিয়াল মাদ্রিদ পয়েন্ট হারালে শিরোপা নিশ্চিত হতে পারে, আর তা না হলে পরের ম্যাচে অপেক্ষা বাড়বে। বার্সেলোনার খেলোয়াড়রা বলছেন, শিরোপা নিশ্চিত হওয়ার আগ পর্যন্ত উদযাপনের কথা ভাবা ঠিক হবে না। মৌসুমজুড়ে আক্রমণাত্মক ফুটবল, ধারাবাহিক জয় এবং প্রতিদ্বন্দ্বীদের ওপর চাপ ধরে রাখাই কাতালানদের সাফল্যের মূল কারণ।",
                        'seo_keywords'     => "বার্সেলোনা, লা লিগা, রিয়াল মাদ্রিদ, ইউরোপিয়ান ফুটবল",
                        'published_at'     => "2026-05-03 13:20:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক খেলা",
                        'name'             => "শিরোপার কাছে এসে পয়েন্ট হারাল পিএসজি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ফরাসি লিগে শিরোপা নিশ্চিতের খুব কাছে এসে ঘরের মাঠে পয়েন্ট হারিয়েছে পিএসজি, লিগের শেষ ভাগে চাপ বেড়েছে।",
                        "body"             => "ফরাসি লিগ ওয়ানে শিরোপা নিশ্চিত করার খুব কাছে এসে ঘরের মাঠে পয়েন্ট হারিয়েছে পিএসজি। লুইস এনরিকের দল ম্যাচে প্রত্যাশিত আধিপত্য দেখালেও জয় তুলতে পারেনি। মৌসুমের শেষ ভাগে এমন হোঁচট শিরোপা দৌড়কে পুরোপুরি বদলে না দিলেও দলের মনোযোগ ও ধারাবাহিকতা নিয়ে প্রশ্ন তুলেছে। ইউরোপীয় ফুটবলে বড় ক্লাবগুলোর জন্য শিরোপা নির্ধারণী সময়ে প্রতিটি পয়েন্ট গুরুত্বপূর্ণ, বিশেষ করে চ্যাম্পিয়নস লিগের ব্যস্ত সূচির মধ্যেও।",
                        'seo_keywords'     => "পিএসজি, লিগ ওয়ান, আন্তর্জাতিক খেলা, ইউরোপিয়ান ফুটবল",
                        'published_at'     => "2026-05-02 23:40:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক খেলা",
                        'name'             => "পিএসজি ম্যাচের আগে বায়ার্ন মিউনিখের হোঁচট",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গুরুত্বপূর্ণ ইউরোপীয় ম্যাচের আগে ঘরোয়া লিগে বায়ার্ন মিউনিখের পয়েন্ট হারানো দলের প্রস্তুতি নিয়ে প্রশ্ন তুলেছে।",
                        "body"             => "পিএসজির বিপক্ষে গুরুত্বপূর্ণ ম্যাচের আগে বায়ার্ন মিউনিখ ঘরোয়া লিগে প্রত্যাশিত ফল পায়নি। বড় ম্যাচের আগে এমন হোঁচট দলের আত্মবিশ্বাস, রোটেশন এবং খেলোয়াড়দের ফিটনেস নিয়ে আলোচনা তৈরি করেছে। ইউরোপীয় ফুটবলে শীর্ষ ক্লাবগুলোর জন্য একই সঙ্গে লিগ ও মহাদেশীয় প্রতিযোগিতা সামলানো বড় চ্যালেঞ্জ। কোচিং স্টাফের সামনে এখন প্রশ্ন, গুরুত্বপূর্ণ ম্যাচে সেরা একাদশ কীভাবে প্রস্তুত রাখা হবে এবং সাম্প্রতিক ভুলগুলো কত দ্রুত ঠিক করা যাবে।",
                        'seo_keywords'     => "বায়ার্ন মিউনিখ, পিএসজি, ইউরোপিয়ান ফুটবল, আন্তর্জাতিক খেলা",
                        'published_at'     => "2026-05-02 22:55:00",
                    ],

                    (object) [
                        "category_name"    => "আন্তর্জাতিক খেলা",
                        'name'             => "ফিফা বলছে যুক্তরাষ্ট্রে বিশ্বকাপ খেলবে ইরান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ইরানের বিশ্বকাপে অংশগ্রহণ নিয়ে আলোচনা চললেও ফিফা সভাপতি বলেছেন, নির্ধারিত সূচি অনুযায়ী যুক্তরাষ্ট্রে খেলবে ইরান।",
                        "body"             => "বিশ্বকাপে ইরানের অংশগ্রহণ নিয়ে নানা আলোচনা ও রাজনৈতিক অনিশ্চয়তার মধ্যেও ফিফা সভাপতি জিয়ান্নি ইনফান্তিনো বলেছেন, সূচি অনুযায়ী যুক্তরাষ্ট্রে টুর্নামেন্টে অংশ নেবে ইরান। আন্তর্জাতিক ফুটবলে রাজনৈতিক উত্তেজনা, ভিসা জটিলতা ও নিরাপত্তা প্রশ্ন প্রায়ই বড় টুর্নামেন্টের আগে আলোচনায় আসে। তবে ফিফা সাধারণত ক্রীড়াকে রাজনৈতিক বিরোধের বাইরে রাখতে চায়। ইরান অংশ নিলে মাঠের লড়াইয়ের পাশাপাশি কূটনৈতিক বাস্তবতাও বিশ্বকাপ আলোচনায় থাকবে।",
                        'seo_keywords'     => "ফিফা, ইরান, বিশ্বকাপ, আন্তর্জাতিক ফুটবল",
                        'published_at'     => "2026-05-01 16:40:00",
                    ],

                    (object) [
                        "category_name"    => "বিনোদন",
                        'name'             => "ইউটিউব চ্যানেল খুললেন জয়া আহসান",
                        'sub_heading'      => "ভক্তদের সঙ্গে সরাসরি যোগাযোগের নতুন প্ল্যাটফর্ম",
                        'content_shoulder' => "",
                        'brief'            => "দুই বাংলার জনপ্রিয় অভিনেত্রী জয়া আহসান এবার নিজের ইউটিউব চ্যানেল চালু করেছেন, যেখানে কাজের পেছনের গল্প ও ব্যক্তিগত সৃজনশীল মুহূর্ত তুলে ধরবেন।",
                        "body"             => "অভিনয়ের পাশাপাশি ডিজিটাল প্ল্যাটফর্মেও নিজের উপস্থিতি আরও শক্ত করতে নতুন ইউটিউব চ্যানেল চালু করেছেন জয়া আহসান। এই চ্যানেলে তিনি ভক্তদের সঙ্গে সরাসরি যোগাযোগ রাখতে চান। পর্দায় দেখা চরিত্রের বাইরে শুটিং প্রস্তুতি, সৃজনশীল ভাবনা, কাজের পেছনের অভিজ্ঞতা এবং জীবনের নানা অজানা মুহূর্ত দর্শকের কাছে তুলে ধরার পরিকল্পনা রয়েছে। ভক্তদের জন্য এটি জয়ার কাজকে আরও কাছ থেকে দেখার নতুন সুযোগ তৈরি করবে।",
                        'seo_keywords'     => "জয়া আহসান, ইউটিউব চ্যানেল, বিনোদন, বাংলাদেশি অভিনেত্রী, তারকা",
                        'published_at'     => "2026-05-03 15:40",
                    ],

                    (object) [
                        "category_name"    => "বিনোদন",
                        'name'             => "ছেলেকে ড্রাম শেখাচ্ছেন জেমস, ভিডিও ভাইরাল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নগরবাউল জেমসের পারিবারিক একটি ভিডিও সামাজিক মাধ্যমে ভাইরাল হয়েছে, যেখানে তিনি ছেলে জিবরানকে ড্রাম বাজানো শেখাচ্ছেন।",
                        "body"             => "নগরবাউল জেমস সাধারণত ব্যক্তিগত জীবন আড়ালে রাখতেই পছন্দ করেন। তাই ছেলে জিবরানকে ড্রাম শেখানোর ভিডিওটি প্রকাশ্যে আসতেই ভক্তদের আগ্রহ তৈরি হয়েছে। ভিডিওতে দেখা যায়, গানের মানুষ জেমস এবার গিটার নয়, ড্রামের কাঠি হাতে ছেলের সঙ্গে সময় কাটাচ্ছেন। বাবা-ছেলের এই পারিবারিক মুহূর্ত ভক্তদের কাছে আবেগময় হয়ে উঠেছে এবং সামাজিক যোগাযোগমাধ্যমে দ্রুত ছড়িয়ে পড়েছে।",
                        'seo_keywords'     => "জেমস, নগরবাউল, ড্রাম, ভাইরাল ভিডিও, সংগীত",
                        'published_at'     => "2026-05-03 16:11",
                    ],

                    (object) [
                        "category_name"    => "বিনোদন",
                        'name'             => "‘কন্ট্র্যাক্ট ম্যারেজ’ সিনেমা দিয়ে ফিরছেন মৌসুমী",
                        'sub_heading'      => "",
                        'content_shoulder' => "প্রবাস জীবনের গল্প",
                        'brief'            => "দীর্ঘদিন যুক্তরাষ্ট্রে থাকা চিত্রনায়িকা মৌসুমী নতুন সিনেমা ‘কন্ট্র্যাক্ট ম্যারেজ’ দিয়ে আবার আলোচনায় এসেছেন; ছবিটি মুক্তির অপেক্ষায়।",
                        "body"             => "ঢালিউডের জনপ্রিয় অভিনেত্রী মৌসুমী দীর্ঘদিন ধরে যুক্তরাষ্ট্রে বসবাস করছেন। তবে অভিনয় থেকে পুরোপুরি দূরে যাননি তিনি। প্রবাসজীবন, সম্পর্কের টানাপোড়েন এবং নারীর মর্যাদাকে কেন্দ্র করে নির্মিত ‘কন্ট্র্যাক্ট ম্যারেজ’ সিনেমায় তাঁকে কেন্দ্রীয় চরিত্রে দেখা যাবে। পরিচালক হাসান জাহাঙ্গীরের এই ছবির বড় অংশের শুটিং হয়েছে যুক্তরাষ্ট্রে। সিনেমাটি প্রবাসী বাংলাদেশিদের বাস্তবতা ও পারিবারিক সংকটকে সামনে আনতে চায়।",
                        'seo_keywords'     => "মৌসুমী, কন্ট্র্যাক্ট ম্যারেজ, ঢালিউড, বাংলা সিনেমা, বিনোদন",
                        'published_at'     => "2026-05-02 20:41",
                    ],

                    (object) [
                        "category_name"    => "বিনোদন",
                        'name'             => "‘এটা আমাদেরই গল্প’ শেষ হচ্ছে ৮ মে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জনপ্রিয় পারিবারিক ধারাবাহিক ‘এটা আমাদেরই গল্প’ ৫২তম পর্বে শেষ হচ্ছে; শেষ পর্বটি হবে ৯২ মিনিটের বিশেষ মেগা পর্ব।",
                        "body"             => "মুহাম্মদ মোস্তফা কামাল রাজ পরিচালিত ‘এটা আমাদেরই গল্প’ দর্শকের আবেগ ছুঁয়ে আলোচনায় ছিল দীর্ঘ সময়। ধারাবাহিকটির ৫২তম পর্বের মাধ্যমে গল্পের ইতি টানা হবে। শেষ পর্বের দৈর্ঘ্য রাখা হয়েছে ৯২ মিনিট, যা নিয়মিত পর্বের চেয়ে অনেক বড়। পারিবারিক সম্পর্ক, বাবা-মায়ের প্রতি আবেগ এবং বাস্তব জীবনের কাছাকাছি গল্প বলার কারণে নাটকটি দেশে-বিদেশে বাংলা ভাষার দর্শকের কাছে জনপ্রিয়তা পেয়েছে।",
                        'seo_keywords'     => "এটা আমাদেরই গল্প, মোস্তফা কামাল রাজ, ধারাবাহিক নাটক, টিভি শো, বিনোদন",
                        'published_at'     => "2026-05-02 19:14",
                    ],

                    (object) [
                        "category_name"    => "বিনোদন",
                        'name'             => "চলচ্চিত্র পরিচালনায় আসছেন জিয়াউল হক পলাশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "‘ব্যাচেলর পয়েন্ট’-এর কাবিলা চরিত্রে জনপ্রিয় জিয়াউল হক পলাশ এবার নতুন পরিচয়ে চলচ্চিত্র পরিচালনায় আসার প্রস্তুতি নিচ্ছেন।",
                        "body"             => "অভিনেতা জিয়াউল হক পলাশ টানা কয়েক বছর ‘ব্যাচেলর পয়েন্ট’-এর কাবিলা চরিত্র দিয়ে দর্শকের কাছে আলাদা পরিচিতি তৈরি করেছেন। এবার তিনি অভিনয়ের পাশাপাশি চলচ্চিত্র পরিচালনার দিকে এগোচ্ছেন। নতুন এই যাত্রা তাঁর ক্যারিয়ারে বড় পরিবর্তন আনতে পারে। ছোট পর্দায় দীর্ঘ অভিজ্ঞতা, দর্শকের রুচি বোঝার ক্ষমতা এবং গল্প বলার আগ্রহ তাঁকে নির্মাণে নতুন সম্ভাবনা তৈরি করতে সাহায্য করবে বলে মনে করছেন ভক্তরা।",
                        'seo_keywords'     => "জিয়াউল হক পলাশ, কাবিলা, চলচ্চিত্র পরিচালক, ব্যাচেলর পয়েন্ট, বিনোদন",
                        'published_at'     => "2026-05-02 15:06",
                    ],

                    (object) [
                        "category_name"    => "সিনেমা",
                        'name'             => "দেশে প্রথমবার ‘মাইগ্রেশন ফিল্ম ফেস্ট’, চলচ্চিত্র জমা নেওয়া শুরু",
                        'sub_heading'      => "অভিবাসনের বাস্তব গল্প নিয়ে নতুন চলচ্চিত্র আয়োজন",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশে প্রথমবারের মতো মাইগ্রেশন ফিল্ম ফেস্ট আয়োজন করা হচ্ছে, যেখানে তরুণ নির্মাতা ও শিক্ষার্থীদের স্বল্পদৈর্ঘ্য চলচ্চিত্র জমা দিতে বলা হয়েছে।",
                        "body"             => "অভিবাসনকে কেন্দ্র করে দেশে প্রথমবারের মতো আয়োজন করা হচ্ছে ‘মাইগ্রেশন ফিল্ম ফেস্ট’। তরুণ নির্মাতা ও শিক্ষার্থীরা ফিকশন, অ্যানিমেশন বা ডকুমেন্টারি ঘরানার স্বল্পদৈর্ঘ্য চলচ্চিত্র জমা দিতে পারবেন। চলচ্চিত্রের মূল বিষয় হতে হবে অভিবাসন, যেখানে প্রবাসীর সাফল্য, দালালের প্রতারণা, মানবপাচার, ঝুঁকিপূর্ণ যাত্রা, বিদেশে মৃত্যু বা পরিবারের মানসিক চাপের মতো বাস্তব গল্প উঠে আসতে পারে। জমা নেওয়ার সময় চলবে ২৫ মে পর্যন্ত।",
                        'seo_keywords'     => "মাইগ্রেশন ফিল্ম ফেস্ট, স্বল্পদৈর্ঘ্য চলচ্চিত্র, বাংলাদেশি সিনেমা, অভিবাসন, চলচ্চিত্র উৎসব",
                        'published_at'     => "2026-05-01 20:21",
                    ],

                    (object) [
                        "category_name"    => "সিনেমা",
                        'name'             => "জার্মানিতে পুরস্কার জিতল বাংলাদেশের ‘আলী’",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আদনান আল রাজীবের স্বল্পদৈর্ঘ্য চলচ্চিত্র ‘আলী’ জার্মানির ফিল্মফেস্ট ব্রেমেন ২০২৬-এ ডাইভার্সিটি অ্যাওয়ার্ড অর্জন করেছে।",
                        "body"             => "বাংলাদেশি নির্মাতা আদনান আল রাজীবের স্বল্পদৈর্ঘ্য চলচ্চিত্র ‘আলী’ আন্তর্জাতিক অঙ্গনে নতুন সাফল্য পেয়েছে। জার্মানির ফিল্মফেস্ট ব্রেমেন ২০২৬-এ চলচ্চিত্রটি ডাইভার্সিটি অ্যাওয়ার্ড জিতেছে। উপকূলীয় এলাকার এক কিশোরের জীবন, সামাজিক বাধা এবং সংগীতের মাধ্যমে নিজের পরিচয় গড়ে তোলার গল্প ছবিটির কেন্দ্রে। এর আগে কান চলচ্চিত্র উৎসবেও ছবিটি আলোচনায় আসে, ফলে বাংলাদেশের স্বল্পদৈর্ঘ্য চলচ্চিত্রের আন্তর্জাতিক অবস্থান আরও দৃঢ় হচ্ছে।",
                        'seo_keywords'     => "আলী, আদনান আল রাজীব, ফিল্মফেস্ট ব্রেমেন, বাংলাদেশি চলচ্চিত্র, পুরস্কার",
                        'published_at'     => "2026-04-21 10:55",
                    ],

                    (object) [
                        "category_name"    => "সিনেমা",
                        'name'             => "‘বনলতা এক্সপ্রেস’ উত্তর আমেরিকায় ৪ লাখ ডলারের ক্লাবে",
                        'sub_heading'      => "",
                        'content_shoulder' => "বক্স অফিস সাফল্য",
                        'brief'            => "তানিম নূরের ‘বনলতা এক্সপ্রেস’ উত্তর আমেরিকায় চার লাখ ডলারের বেশি আয় করে বাংলাদেশি সিনেমার জন্য নতুন মাইলফলক তৈরি করেছে।",
                        "body"             => "‘বনলতা এক্সপ্রেস’ উত্তর আমেরিকার বক্স অফিসে উল্লেখযোগ্য সাফল্য পেয়েছে। কানাডা ও যুক্তরাষ্ট্রে মুক্তির পর সিনেমাটি ২১টি হলে প্রদর্শিত হচ্ছে এবং প্রথম বাংলাদেশি সিনেমা হিসেবে চার লাখ ডলারের ক্লাবে প্রবেশ করেছে বলে জানানো হয়েছে। মোশাররফ করিম, চঞ্চল চৌধুরী, বাঁধন, মম, সাবিলা নূরসহ তারকাবহুল অভিনয়শিল্পীর উপস্থিতি এবং তানিম নূরের নির্মাণ ছবিটিকে প্রবাসী দর্শকের মধ্যেও আলোচনায় এনেছে।",
                        'seo_keywords'     => "বনলতা এক্সপ্রেস, তানিম নূর, উত্তর আমেরিকা, ঢালিউড, বক্স অফিস",
                        'published_at'     => "2026-04-22 11:36",
                    ],

                    (object) [
                        "category_name"    => "সিনেমা",
                        'name'             => "ভিয়েনা চলচ্চিত্র উৎসবে বাংলাদেশি চলচ্চিত্র ‘দেলুপি’",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নেদারল্যান্ডসের পর এবার অস্ট্রিয়ার ভিয়েনায় রেড লোটাস এশিয়ান ফিল্ম ফেস্টিভ্যালে নির্বাচিত হয়েছে বাংলাদেশি চলচ্চিত্র ‘দেলুপি’।",
                        "body"             => "মোহাম্মদ তাওকীর ইসলামের বাংলাদেশি চলচ্চিত্র ‘দেলুপি’ আন্তর্জাতিক উৎসব সফরে আরেক ধাপ এগিয়েছে। নেদারল্যান্ডসের রটারড্যাম উৎসবে প্রদর্শনের পর এবার অস্ট্রিয়ার ভিয়েনায় রেড লোটাস এশিয়ান ফিল্ম ফেস্টিভ্যালে ছবিটি নির্বাচিত হয়েছে। নির্মাতা জানিয়েছেন, ইউরোপে একাধিক উৎসবে ছবিটির উপস্থিতি পুরো দলের জন্য আনন্দের। বাংলাদেশের গল্প, ভাষা ও চলচ্চিত্রভাষাকে আন্তর্জাতিক দর্শকের সামনে তুলে ধরার সুযোগ হিসেবেও এটি গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "দেলুপি, ভিয়েনা চলচ্চিত্র উৎসব, বাংলাদেশি সিনেমা, তাওকীর ইসলাম, ফিল্ম ফেস্টিভ্যাল",
                        'published_at'     => "2026-04-07 12:09",
                    ],

                    (object) [
                        "category_name"    => "সিনেমা",
                        'name'             => "মস্কো চলচ্চিত্র উৎসবে বাংলাদেশের দুই সিনেমা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "৪৮তম মস্কো আন্তর্জাতিক চলচ্চিত্র উৎসবে বাংলাদেশের ‘অতল’ ও ‘কিং ইন দ্য ল্যান্ড অব দ্য প্রিন্সেস’ নির্বাচিত হয়েছে।",
                        "body"             => "মস্কো আন্তর্জাতিক চলচ্চিত্র উৎসবের ৪৮তম আয়োজনে বাংলাদেশের দুই সিনেমা জায়গা পেয়েছে। যুবরাজ শামীমের ‘অতল’ প্রতিযোগিতা শাখার বাইরে ‘অ্যাপক্যালিপ্স নাউ’ সেকশনে এবং আসিফ ইসলামের ‘কিং ইন দ্য ল্যান্ড অব দ্য প্রিন্সেস’ আর্টকোর বিভাগে প্রদর্শিত হবে। ‘অতল’ অস্তিত্বসংকটে ভোগা এক মানুষের মনস্তত্ত্ব নিয়ে নির্মিত, আর ‘প্রিন্সেস’ যাত্রাপালার পরিবর্তিত বাস্তবতা ও বিনোদনরুচির গল্প বলে।",
                        'seo_keywords'     => "মস্কো চলচ্চিত্র উৎসব, অতল, কিং ইন দ্য ল্যান্ড অব দ্য প্রিন্সেস, যুবরাজ শামীম, ভাবনা",
                        'published_at'     => "2026-04-১৩ 02:00",
                    ],

                    (object) [
                        "category_name"    => "সংগীত",
                        'name'             => "প্রকাশিত হলো আলিফ আলাউদ্দীনের চতুর্থ অ্যালবাম",
                        'sub_heading'      => "ছয়টি গান নিয়ে নতুন একক অ্যালবাম",
                        'content_shoulder' => "",
                        'brief'            => "জনপ্রিয় সংগীতশিল্পী আলিফ আলাউদ্দীনের চতুর্থ একক অ্যালবাম প্রকাশ হয়েছে, যেখানে মৌলিক গান ও পারিবারিক ট্রিবিউট রাখা হয়েছে।",
                        "body"             => "আলিফ আলাউদ্দীনের চতুর্থ একক অ্যালবাম ‘আলিফ আলাউদ্দীন’ প্রকাশ হয়েছে ছয়টি গান নিয়ে। অ্যালবামে চারটি মৌলিক গান এবং দুটি ট্রিবিউট গান রাখা হয়েছে। ট্রিবিউট গান দুটি তাঁর মা সালমা সুলতানা, বাবা আলাউদ্দীন আলী এবং বাংলা সংগীতের স্মরণীয় স্রষ্টাদের প্রতি শ্রদ্ধা হিসেবে সাজানো। গানগুলোর লিরিক ভিডিও ও একটি মিউজিক ভিডিও ইউটিউবে প্রকাশ হয়েছে, পাশাপাশি স্পটিফাইতেও শোনা যাচ্ছে।",
                        'seo_keywords'     => "আলিফ আলাউদ্দীন, বাংলা গান, নতুন অ্যালবাম, সংগীত, ইউটিউব",
                        'published_at'     => "2026-04-22 21:07",
                    ],

                    (object) [
                        "category_name"    => "সংগীত",
                        'name'             => "রুনা লায়লা ও বাপ্পা মজুমদারের প্রথম বাংলা গজল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দেশের দুই সংগীত তারকা রুনা লায়লা ও বাপ্পা মজুমদার প্রথমবারের মতো একসঙ্গে বাংলা গজল নিয়ে আসছেন বলে ঘোষণা এসেছে।",
                        "body"             => "রুনা লায়লা ও বাপ্পা মজুমদারকে প্রথমবারের মতো একসঙ্গে শোনা যাবে একটি বাংলা গজলে। সৈয়দ গালিব হাসানের কথায় তৈরি এই বিশেষ গানের ঘোষণা রুনা লায়লা নিজেই ভিডিও বার্তায় দিয়েছেন। বাংলা গানের প্রচলিত ধারার বাইরে গজল আঙ্গিকে দুই প্রজন্মের দুই শিল্পীর একসঙ্গে আসা শ্রোতাদের জন্য নতুন অভিজ্ঞতা হতে পারে। গানটির ঘোষণার পর থেকেই সংগীতপ্রেমীদের মধ্যে আগ্রহ তৈরি হয়েছে।",
                        'seo_keywords'     => "রুনা লায়লা, বাপ্পা মজুমদার, বাংলা গজল, সংগীত, নতুন গান",
                        'published_at'     => "2026-04-২৫ 14:58",
                    ],

                    (object) [
                        "category_name"    => "সংগীত",
                        'name'             => "শাকিব খানের পারফর্মে প্রকাশ পেল ‘জয়ধ্বনি’",
                        'sub_heading'      => "",
                        'content_shoulder' => "উদযাপনের গান",
                        'brief'            => "ঢাকাই সিনেমার তারকা শাকিব খানের পারফর্মে এম-রাইজ নিবেদিত ‘জয়ধ্বনি’ গান প্রকাশ হয়েছে, নির্মাণ করেছেন আদনান আল রাজীব।",
                        "body"             => "শাকিব খানের পারফর্ম করা উদযাপনধর্মী গান ‘জয়ধ্বনি’ প্রকাশের পর ভক্তদের মধ্যে আলোচনা তৈরি হয়েছে। এম-রাইজ নিবেদিত গানটি নির্মাণ করেছেন জনপ্রিয় নির্মাতা আদনান আল রাজীব। ঢালিউড তারকার উপস্থিতি, উৎসবমুখর ভিজ্যুয়াল এবং উদ্দীপনাময় উপস্থাপনা গানটিকে প্রচারণা ও বিনোদন—দুই দিক থেকেই আলাদা গুরুত্ব দিয়েছে। সংগীত ভিডিওর মাধ্যমে শাকিব খানের তারকাখ্যাতি নতুনভাবে ব্যবহারের চেষ্টা দেখা গেছে।",
                        'seo_keywords'     => "শাকিব খান, জয়ধ্বনি, মিউজিক ভিডিও, আদনান আল রাজীব, বাংলা গান",
                        'published_at'     => "2026-04-24 16:23",
                    ],

                    (object) [
                        "category_name"    => "সংগীত",
                        'name'             => "১৮ বছর পর নতুন রূপে ‘বাজেরে বাজে ঢোল আর ঢাক’",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জনপ্রিয় গান ‘বাজেরে বাজে ঢোল আর ঢাক’ নতুন সংগীতায়োজন ও নতুন কণ্ঠে আবার প্রকাশের উদ্যোগ নিয়েছে প্রযোজনা প্রতিষ্ঠান গানচিল।",
                        "body"             => "দেড় যুগ আগে সোনিয়ার কণ্ঠে জনপ্রিয় হওয়া ‘বাজেরে বাজে ঢোল আর ঢাক’ গানটি নতুন প্রজন্মের কাছে পৌঁছে দিতে নতুনভাবে তৈরি হয়েছে। শওকত আলী ইমন মূল আবহ ধরে রেখে আধুনিক সাউন্ডে গানটির পুনর্গঠনে যুক্ত হয়েছেন। নতুন সংস্করণে তাঁর সঙ্গে ঝিলিক, লুইপা, আনিসা ও কর্নিয়ার কণ্ঠ থাকছে। বাংলা নববর্ষকে সামনে রেখে গানটির ভিডিও নির্মাণ করা হয়েছে, যা পুরোনো শ্রোতা ও নতুন শ্রোতা—দুই পক্ষকেই লক্ষ্য করছে।",
                        'seo_keywords'     => "বাজেরে বাজে ঢোল আর ঢাক, শওকত আলী ইমন, বাংলা গান, গানচিল, নববর্ষ",
                        'published_at'     => "2026-04-07 01:00",
                    ],

                    (object) [
                        "category_name"    => "সংগীত",
                        'name'             => "বাদশাহ–নোরা ফাতেহির গানে বাংলাদেশের তাশফী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশি তরুণ কণ্ঠশিল্পী তাশফী বলিউডের বাদশাহ ও নোরা ফাতেহির সঙ্গে ‘সাজনা রে’ গানে কণ্ঠ দিয়ে আলোচনায় এসেছেন।",
                        "body"             => "বাংলাদেশি কণ্ঠশিল্পী তাশফী বলিউডের বড় প্রযোজনা প্রতিষ্ঠান টি-সিরিজের গানে যুক্ত হয়ে আন্তর্জাতিক আলোচনায় এসেছেন। ‘সাজনা রে’ শিরোনামের গানটিতে বাদশাহ ও নোরা ফাতেহির সঙ্গে তাঁর কণ্ঠ শোনা যায়। গানটির সংগীত পরিচালনায় ছিলেন বাংলাদেশি তরুণ সঞ্জয়। বড় আয়োজন, আন্তর্জাতিক তারকা এবং দ্রুত ভিউ বাড়ার কারণে গানটি তাশফীর ক্যারিয়ারে গুরুত্বপূর্ণ অধ্যায় হয়ে উঠেছে। তিনি এটিকে সামনে আরও ভালো কাজের শুরু হিসেবে দেখছেন।",
                        'seo_keywords'     => "তাশফী, সাজনা রে, বাদশাহ, নোরা ফাতেহি, টি-সিরিজ",
                        'published_at'     => "2026-04-০৬ 18:15",
                    ],

                    (object) [
                        "category_name"    => "টিভি শো",
                        'name'             => "‘এটা আমাদেরই গল্প’র শুটিং শেষ",
                        'sub_heading'      => "৫২ পর্বে শেষ হচ্ছে আলোচিত ধারাবাহিক",
                        'content_shoulder' => "",
                        'brief'            => "জনপ্রিয় ধারাবাহিক ‘এটা আমাদেরই গল্প’র শেষ পর্বের শুটিং শেষ হয়েছে; ৫২তম পর্বের মাধ্যমে ধারাবাহিকটির সমাপ্তি হবে।",
                        "body"             => "হালের আলোচিত পারিবারিক ধারাবাহিক ‘এটা আমাদেরই গল্প’র শুটিং শেষ হয়েছে। ৫২ পর্বের এই ধারাবাহিকের ৫০টি পর্ব ইতিমধ্যে প্রচারিত হয়েছে এবং শেষ দুই পর্ব নিয়ে দর্শকের আগ্রহ আরও বেড়েছে। পারিবারিক সম্পর্ক, আবেগ, বাস্তব জীবনের টানাপোড়েন এবং চরিত্রের বিশ্বাসযোগ্য উপস্থাপনার কারণে নাটকটি ইউটিউব ও টেলিভিশন—দুই মাধ্যমেই আলোচনায় ছিল। শেষ পর্বে গল্পের আবেগঘন সমাপ্তি দেখার অপেক্ষায় দর্শকেরা।",
                        'seo_keywords'     => "এটা আমাদেরই গল্প, ধারাবাহিক নাটক, টিভি শো, মোস্তফা কামাল রাজ, বাংলা নাটক",
                        'published_at'     => "2026-05-02 18:27",
                    ],

                    (object) [
                        "category_name"    => "টিভি শো",
                        'name'             => "বাবার মৃত্যু দৃশ্যে কাঁদছেন ‘এটা আমাদেরই গল্প’র দর্শক",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "‘এটা আমাদেরই গল্প’ ধারাবাহিকের আবেগঘন পর্ব দর্শকদের গভীরভাবে নাড়া দিয়েছে; ৫০তম পর্ব দ্রুত বিপুল ভিউ পেয়েছে।",
                        "body"             => "মুহাম্মদ মোস্তফা কামাল রাজ পরিচালিত ‘এটা আমাদেরই গল্প’ ধারাবাহিকের বাবার মৃত্যু ঘিরে নির্মিত পর্ব দর্শকদের মধ্যে ব্যাপক আবেগ তৈরি করেছে। ইরফান সাজ্জাদ ও খায়রুল বাসারের চরিত্রকে কেন্দ্র করে এই দৃশ্য শুধু গল্পের মোড় বদলায়নি, অনেক দর্শকের ব্যক্তিগত স্মৃতিও জাগিয়ে দিয়েছে। নির্মাতার মতে, জীবনের কাছাকাছি গল্প বলার চেষ্টাই দর্শকের কান্না ও ভালোবাসার কারণ। ধারাবাহিকটির শেষ পর্ব হবে বিশেষ মেগা পর্ব।",
                        'seo_keywords'     => "এটা আমাদেরই গল্প, ইরফান সাজ্জাদ, খায়রুল বাসার, বাংলা ধারাবাহিক, টিভি নাটক",
                        'published_at'     => "2026-05-03 00:00",
                    ],

                    (object) [
                        "category_name"    => "টিভি শো",
                        'name'             => "দর্শকের পছন্দে এগিয়ে ‘পরান পাখি’",
                        'sub_heading'      => "",
                        'content_shoulder' => "মানবিক গল্প",
                        'brief'            => "অসুস্থ কন্যাকে বাঁচানোর মানবিক গল্প নিয়ে নির্মিত ‘পরান পাখি’ নাটক দর্শকের পছন্দের শীর্ষে জায়গা করে নিয়েছে।",
                        "body"             => "এপ্রিলের শুরু থেকে অনলাইন নাটকের দর্শকপ্রিয়তায় এগিয়ে ছিল ‘পরান পাখি’। অসুস্থ কন্যাকে বাঁচানোর গল্প, পারিবারিক আবেগ এবং মানবিক বার্তার কারণে নাটকটি দ্রুত দর্শকের মনোযোগ পায়। মুশফিক ফারহান ও কেয়া পায়েল অভিনীত নাটকটি পরিচালনা করেছেন মিফতাহ্ আনান। ভাইরাল হওয়া বা কেবল বিনোদনের বাইরে গিয়ে মানবিকতা ও পারিবারিক দায়িত্বের গল্প বলার কারণে দর্শকেরা নাটকটিকে আলাদা করে দেখেছেন।",
                        'seo_keywords'     => "পরান পাখি, মুশফিক ফারহান, কেয়া পায়েল, বাংলা নাটক, টিভি শো",
                        'published_at'     => "2026-04-১৪ 11:45",
                    ],

                    (object) [
                        "category_name"    => "টিভি শো",
                        'name'             => "প্রচারে আসছে নতুন ধারাবাহিক ‘কিচেন পলিটিক্স’",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নতুন ধারাবাহিক ‘কিচেন পলিটিক্স’ প্রচারে আসছে; নাটকটিতে পারিবারিক সম্পর্ক ও ঘরোয়া দ্বন্দ্বের গল্প তুলে ধরা হবে।",
                        "body"             => "নতুন ধারাবাহিক নাটক ‘কিচেন পলিটিক্স’ প্রচারের জন্য প্রস্তুত। নাটকটির নাম থেকেই বোঝা যায়, ঘরের ভেতরের সম্পর্ক, ছোটখাটো ক্ষমতার লড়াই, পারিবারিক টানাপোড়েন এবং হাস্যরসাত্মক পরিস্থিতি গল্পের কেন্দ্রে থাকবে। এতে দিলারা জামান, মাসুম বাসার, মিলি বাসার, নাবিলা ইসলাম, জয়রাজসহ একাধিক অভিনয়শিল্পী অভিনয় করেছেন। পারিবারিক ধারাবাহিকের দর্শকদের লক্ষ্য করে নাটকটি নির্মিত হয়েছে।",
                        'seo_keywords'     => "কিচেন পলিটিক্স, বাংলা ধারাবাহিক, টিভি নাটক, দিলারা জামান, পারিবারিক নাটক",
                        'published_at'     => "2026-04-03 14:09",
                    ],

                    (object) [
                        "category_name"    => "টিভি শো",
                        'name'             => "নয় বছর পর প্রচারে আসছে শখ-নিলয়ের ‘কিডন্যাপ’",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শখ ও নিলয়ের অভিনীত ‘কিডন্যাপ’ নাটক নয় বছর পর টিভি পর্দায় আসছে, যা পুরোনো জুটির ভক্তদের মধ্যে আগ্রহ তৈরি করেছে।",
                        "body"             => "জনপ্রিয় জুটি শখ ও নিলয়ের অভিনীত নাটক ‘কিডন্যাপ’ দীর্ঘ নয় বছর পর প্রচারে আসছে। একসময় ছোট পর্দায় এই জুটি দর্শকের কাছে বেশ জনপ্রিয় ছিল। তাই পুরোনো কাজ নতুন করে প্রচারের খবর ভক্তদের মধ্যে নস্টালজিয়া তৈরি করেছে। নাটকটির পুনঃপ্রচার বা দেরিতে প্রচারে আসা একই সঙ্গে দর্শকের পুরোনো স্মৃতি ফিরিয়ে আনবে এবং নতুন প্রজন্মকে শখ-নিলয় জুটির কাজ দেখার সুযোগ করে দেবে।",
                        'seo_keywords'     => "শখ, নিলয়, কিডন্যাপ, বাংলা নাটক, টিভি শো",
                        'published_at'     => "2026-03-২৫ 20:24",
                    ],

                    (object) [
                        "category_name"    => "তারকা",
                        'name'             => "মেরিল-প্রথম আলো পুরস্কারে কার হাতে কোন সম্মাননা",
                        'sub_heading'      => "টিভি, ওয়েব ও চলচ্চিত্রের সেরা কাজের স্বীকৃতি",
                        'content_shoulder' => "",
                        'brief'            => "মেরিল-প্রথম আলো পুরস্কারের ২৭তম আসরে টিভি, ওয়েব, নাটক ও চলচ্চিত্র অঙ্গনের বিভিন্ন শিল্পী ও নির্মাতাকে সম্মাননা দেওয়া হয়েছে।",
                        "body"             => "দেশের বিনোদন অঙ্গনের বড় আয়োজন মেরিল-প্রথম আলো পুরস্কারের ২৭তম আসরে বিভিন্ন শাখায় সেরা শিল্পী ও নির্মাতাদের হাতে পুরস্কার উঠেছে। সীমিতদৈর্ঘ্য কাহিনিচিত্রে চিত্রনাট্য, পরিচালনা, অভিনয়সহ একাধিক বিভাগে পুরস্কার দেওয়া হয়। তানজিম সাইয়ারা তটিনী, এফ এস নাঈম, মুহাম্মদ মোস্তফা কামাল রাজসহ বিভিন্ন শিল্পী ও নির্মাতা আলোচনায় আসেন। এই আয়োজন দেশের তারকাদের কাজের স্বীকৃতির গুরুত্বপূর্ণ মঞ্চ হিসেবে বিবেচিত।",
                        'seo_keywords'     => "মেরিল প্রথম আলো পুরস্কার, তারকা, তটিনী, এফ এস নাঈম, বিনোদন",
                        'published_at'     => "2026-04-২৫ 00:00",
                    ],

                    (object) [
                        "category_name"    => "তারকা",
                        'name'             => "লালগালিচায় তারার মেলা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মেরিল-প্রথম আলো পুরস্কারের লালগালিচায় হাজির হন দেশের বিনোদনজগতের তারকারা, পোশাক ও উপস্থিতি ঘিরে তৈরি হয় আলাদা আলোচনাও।",
                        "body"             => "মেরিল-প্রথম আলো পুরস্কারের লালগালিচা বরাবরের মতোই তারকাদের উপস্থিতিতে জমে ওঠে। অভিনেত্রী, অভিনেতা, সংগীতশিল্পী ও নির্মাতারা নানা সাজে অনুষ্ঠানে হাজির হন। মূল আয়োজনের আগেই লালগালিচা হয়ে ওঠে দর্শক ও গণমাধ্যমের আগ্রহের কেন্দ্র। পোশাক, স্টাইল, তারকাদের পারস্পরিক দেখা-সাক্ষাৎ এবং পুরস্কার ঘিরে প্রত্যাশা—সব মিলিয়ে অনুষ্ঠানটি বিনোদন অঙ্গনের উৎসবমুখর সন্ধ্যায় পরিণত হয়।",
                        'seo_keywords'     => "লালগালিচা, মেরিল পুরস্কার, তারকা, বিনোদন অনুষ্ঠান, সেলিব্রিটি",
                        'published_at'     => "2026-04-24 00:00",
                    ],

                    (object) [
                        "category_name"    => "তারকা",
                        'name'             => "চলচ্চিত্র পরিচালনায় নতুন পরিচয়ে পলাশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "অভিনেতা থেকে নির্মাতা",
                        'brief'            => "জিয়াউল হক পলাশ অভিনয়ের জনপ্রিয়তা পেরিয়ে এবার চলচ্চিত্র পরিচালনায় নামার প্রস্তুতি নিচ্ছেন, যা তাঁর ক্যারিয়ারে নতুন বাঁক।",
                        "body"             => "জিয়াউল হক পলাশ ছোট পর্দায় অভিনয়ের মাধ্যমে বড় জনপ্রিয়তা পেয়েছেন। বিশেষ করে ‘ব্যাচেলর পয়েন্ট’-এর কাবিলা চরিত্র তাঁকে দর্শকের ঘরে ঘরে পরিচিত করেছে। এবার তিনি চলচ্চিত্র পরিচালনার মাধ্যমে নিজের সৃজনশীল পরিচয় আরও প্রসারিত করতে চাইছেন। অভিনয়ের অভিজ্ঞতা, কমেডি টাইমিং এবং দর্শক বোঝার ক্ষমতা তাঁর নির্মাণে কাজে আসতে পারে। ফলে পলাশের নতুন পরিচয় নিয়ে ভক্তদের আগ্রহ স্বাভাবিকভাবেই বেড়েছে।",
                        'seo_keywords'     => "জিয়াউল হক পলাশ, কাবিলা, অভিনেতা, চলচ্চিত্র নির্মাতা, তারকা",
                        'published_at'     => "2026-05-02 15:06",
                    ],

                    (object) [
                        "category_name"    => "তারকা",
                        'name'             => "জয়ার নতুন ডিজিটাল যাত্রা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "অভিনেত্রী জয়া আহসান ইউটিউব চ্যানেল চালু করে ভক্তদের সঙ্গে নিজের কাজ ও জীবনের ভেতরের গল্প ভাগ করার পরিকল্পনা করেছেন।",
                        "body"             => "জয়া আহসান দীর্ঘদিন ধরে দুই বাংলার দর্শকের কাছে জনপ্রিয়। এবার ইউটিউব চ্যানেলের মাধ্যমে তিনি ভক্তদের সঙ্গে আরও সরাসরি যোগাযোগ গড়ে তুলতে চান। সিনেমা, চরিত্র, শুটিং প্রস্তুতি এবং শিল্পীজীবনের নানা অভিজ্ঞতা এই প্ল্যাটফর্মে তুলে ধরার ইঙ্গিত দিয়েছেন তিনি। অভিনয়শিল্পীদের জন্য নিজস্ব ডিজিটাল প্ল্যাটফর্ম এখন গুরুত্বপূর্ণ হয়ে উঠছে; জয়ার এই উদ্যোগ সেই পরিবর্তিত বিনোদন বাস্তবতার সঙ্গে সামঞ্জস্যপূর্ণ।",
                        'seo_keywords'     => "জয়া আহসান, ইউটিউব, তারকা, ডিজিটাল প্ল্যাটফর্ম, অভিনেত্রী",
                        'published_at'     => "2026-05-03 15:40",
                    ],

                    (object) [
                        "category_name"    => "তারকা",
                        'name'             => "মৌসুমীর প্রত্যাবর্তনে প্রবাসজীবনের গল্প",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "চিত্রনায়িকা মৌসুমী ‘কন্ট্র্যাক্ট ম্যারেজ’ সিনেমায় কেন্দ্রীয় চরিত্রে ফিরছেন, যেখানে প্রবাসী নারীর জীবনসংগ্রাম উঠে আসবে।",
                        "body"             => "দীর্ঘদিন ধরে যুক্তরাষ্ট্রে থাকা মৌসুমী নতুন সিনেমা ‘কন্ট্র্যাক্ট ম্যারেজ’ দিয়ে আলোচনায় এসেছেন। ছবিতে তাঁকে এমন এক গল্পের কেন্দ্রে দেখা যাবে, যেখানে বিদেশে টিকে থাকা, সম্পর্কের জটিলতা এবং নারীর সম্মানের প্রশ্ন সামনে আসে। ঢালিউডে একসময় নিয়মিত আলোচনায় থাকা মৌসুমীর পর্দায় ফেরা তাঁর ভক্তদের জন্য বিশেষ খবর। একই সঙ্গে প্রবাসী বাংলাদেশিদের বাস্তবতা নিয়ে নির্মিত ছবিটি সামাজিক বার্তাও বহন করতে পারে।",
                        'seo_keywords'     => "মৌসুমী, কন্ট্র্যাক্ট ম্যারেজ, তারকা, প্রবাসজীবন, ঢালিউড",
                        'published_at'     => "2026-05-02 20:41",
                    ],

                    (object) [
                        "category_name"    => "ওটিটি",
                        'name'             => "মে মাসে দেখতে পারেন নতুন ৭ সিনেমা–সিরিজ",
                        'sub_heading'      => "প্রেক্ষাগৃহ ও ওটিটির নতুন মুক্তির তালিকা",
                        'content_shoulder' => "",
                        'brief'            => "মে মাসে প্রেক্ষাগৃহ ও ওটিটিতে মুক্তি পাওয়া বা মুক্তির অপেক্ষায় থাকা সাতটি আলোচিত বিদেশি সিনেমা ও সিরিজের তালিকা প্রকাশ হয়েছে।",
                        "body"             => "মে মাসে প্রেক্ষাগৃহ ও ওটিটি প্ল্যাটফর্মে একাধিক সিনেমা ও সিরিজ মুক্তি পাচ্ছে। তালিকায় রয়েছে ‘দ্য ডেভিল ওয়্যারস প্রাডা ২’, ‘মরটাল কমব্যাট’, ‘স্টার ওয়ারস: দ্য ম্যান্ডালোরিয়ান অ্যান্ড গ্রোগু’, ‘সিটাডেল’–এর নতুন মৌসুম, ‘দ্য পানিশার: ওয়ান লাস্ট কিল’ এবং নেটফ্লিক্সের নতুন ওয়েব সিনেমা ‘কর্তব্য’। বাংলাদেশের দর্শকেরাও ঢাকার প্রেক্ষাগৃহ বা আন্তর্জাতিক স্ট্রিমিং প্ল্যাটফর্মে এসব কনটেন্ট অনুসরণ করতে পারবেন।",
                        'seo_keywords'     => "ওটিটি, মে মাসের সিরিজ, নেটফ্লিক্স, অ্যামাজন প্রাইম, ওয়েব সিরিজ",
                        'published_at'     => "2026-05-03 01:00",
                    ],

                    (object) [
                        "category_name"    => "ওটিটি",
                        'name'             => "ওটিটিতে ফিরছে ‘সিটাডেল’–এর দ্বিতীয় মৌসুম",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "রুশো ভ্রাতৃদ্বয়ের স্পাই থ্রিলার সিরিজ ‘সিটাডেল’ দ্বিতীয় মৌসুম নিয়ে ফিরছে, যেখানে পুরোনো এজেন্টদের নতুন হুমকির মুখোমুখি দেখা যাবে।",
                        "body"             => "প্রায় তিন বছর বিরতির পর ‘সিটাডেল’ দ্বিতীয় মৌসুম নিয়ে ফিরছে। অ্যামাজন প্রাইম ভিডিওতে মুক্তির অপেক্ষায় থাকা সিরিজটির সাত পর্বে রিচার্ড ম্যাডেন, প্রিয়াঙ্কা চোপড়া জোনাস ও স্ট্যানলি টুচির অভিনয় থাকছে। গল্পে দেখা যাবে, ধ্বংস হয়ে যাওয়া গোপন গোয়েন্দা সংস্থার পুরোনো এজেন্টদের স্মৃতি মুছে ফেলা হয়েছে। কিন্তু নতুন হুমকি সামনে এলে তাদের আবার মাঠে নামতে হয়। স্পাই থ্রিলারপ্রেমীদের জন্য এটি মে মাসের বড় আকর্ষণ।",
                        'seo_keywords'     => "সিটাডেল, ওটিটি, অ্যামাজন প্রাইম, প্রিয়াঙ্কা চোপড়া, ওয়েব সিরিজ",
                        'published_at'     => "2026-05-03 01:00",
                    ],

                    (object) [
                        "category_name"    => "ওটিটি",
                        'name'             => "নেটফ্লিক্সে আসছে সাইফ আলী খানের ‘কর্তব্য’",
                        'sub_heading'      => "",
                        'content_shoulder' => "পুলিশ চরিত্রে সাইফ",
                        'brief'            => "নেটফ্লিক্সের নতুন ওয়েব সিনেমা ‘কর্তব্য’-তে সাইফ আলী খানকে আবার পুলিশের চরিত্রে দেখা যাবে, মুক্তি পাবে ১৫ মে।",
                        "body"             => "সাইফ আলী খানকে নেটফ্লিক্সের নতুন ওয়েব সিনেমা ‘কর্তব্য’-তে আবার পুলিশের চরিত্রে দেখা যাবে। ‘সেক্রেড গেমস’-এর পর পুলিশ চরিত্রে তাঁর উপস্থিতি দর্শকের কাছে আলাদা আগ্রহ তৈরি করেছে। সিনেমার গল্পে অপরাধীদের বিরুদ্ধে লড়াইয়ের পাশাপাশি পরিবারকে রক্ষা করা, হুমকি সামলানো এবং কঠিন সিদ্ধান্ত নেওয়ার দ্বন্দ্ব সামনে আসবে। পুলকিত পরিচালিত এই সিনেমায় রসিকা দুগল, সঞ্জয় মিশ্র, জাকির হুসেনসহ আরও শিল্পী অভিনয় করেছেন।",
                        'seo_keywords'     => "কর্তব্য, নেটফ্লিক্স, সাইফ আলী খান, ওটিটি সিনেমা, ওয়েব ফিল্ম",
                        'published_at'     => "2026-05-03 01:00",
                    ],

                    (object) [
                        "category_name"    => "ওটিটি",
                        'name'             => "ওটিটিতে আলোচিত নতুন সিরিজের তালিকা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নিয়মিত ওটিটি আয়োজনে সপ্তাহের নতুন ওয়েব সিরিজ, সিনেমা, প্রামাণ্যচিত্র ও রিয়েলিটি শো নিয়ে দর্শকের জন্য বাছাই তালিকা প্রকাশ হয়েছে।",
                        "body"             => "ওটিটি প্ল্যাটফর্মে প্রতি সপ্তাহেই নতুন সিরিজ, সিনেমা, প্রামাণ্যচিত্র ও রিয়েলিটি শো মুক্তি পাচ্ছে। দর্শকদের আগ্রহ বিবেচনায় নিয়মিত তালিকায় আলোচিত কনটেন্টগুলো তুলে ধরা হচ্ছে। আন্তর্জাতিক সিরিজ থেকে শুরু করে নেটফ্লিক্স, প্রাইম ভিডিও, ডিজনি প্লাস ও অন্যান্য প্ল্যাটফর্মের কনটেন্ট এখন বাংলাদেশি দর্শকের দেখার তালিকাতেও জায়গা করে নিচ্ছে। ফলে ওটিটি এখন বিনোদনের বড় অংশ হয়ে উঠেছে।",
                        'seo_keywords'     => "ওটিটি, ওয়েব সিরিজ, নতুন সিরিজ, স্ট্রিমিং, বিনোদন",
                        'published_at'     => "2026-04-১৪ 17:৫৯",
                    ],

                    (object) [
                        "category_name"    => "ওটিটি",
                        'name'             => "ওটিটি দর্শকের জন্য মে মাসের থ্রিলার ও অ্যাকশন কনটেন্ট",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মে মাসে স্ট্রিমিং প্ল্যাটফর্মে অ্যাকশন, স্পাই থ্রিলার ও সুপারহিরো ঘরানার একাধিক কনটেন্ট দর্শকের নজরে থাকবে।",
                        "body"             => "মে মাসের ওটিটি ও প্রেক্ষাগৃহ তালিকায় অ্যাকশন, স্পাই থ্রিলার ও সুপারহিরো ঘরানার বেশ কিছু কনটেন্ট আছে। ‘সিটাডেল’–এর নতুন মৌসুম, ‘দ্য পানিশার: ওয়ান লাস্ট কিল’, ‘মরটাল কমব্যাট’ এবং ‘স্টার ওয়ারস’–এর নতুন কনটেন্ট দর্শকের আগ্রহ বাড়াচ্ছে। স্ট্রিমিং প্ল্যাটফর্মগুলো এখন সিনেমা হলের পাশাপাশি বড় বাজেটের কনটেন্ট দেখার গুরুত্বপূর্ণ মাধ্যম হয়ে উঠেছে, তাই বাংলাদেশি দর্শকেরাও একই তালিকা অনুসরণ করছেন।",
                        'seo_keywords'     => "ওটিটি থ্রিলার, অ্যাকশন সিরিজ, দ্য পানিশার, মরটাল কমব্যাট, স্ট্রিমিং",
                        'published_at'     => "2026-05-03 01:00",
                    ],

                    (object) [
                        "category_name"    => "স্বাস্থ্য",
                        'name'             => "ডেঙ্গু প্রতিরোধে পরিচ্ছন্নতায় জোর দিলেন স্বাস্থ্যমন্ত্রী",
                        'sub_heading'      => "চিকিৎসার চেয়ে প্রতিরোধকে গুরুত্ব দেওয়ার আহ্বান",
                        'content_shoulder' => "",
                        'brief'            => "ডেঙ্গুর সম্ভাব্য ঝুঁকি সামনে রেখে পরিষ্কার-পরিচ্ছন্নতা ও আগাম প্রতিরোধমূলক ব্যবস্থায় জোর দেওয়ার কথা বলেছেন স্বাস্থ্যমন্ত্রী।",
                        "body"             => "ডেঙ্গুর মৌসুম সামনে রেখে স্বাস্থ্য ও পরিবার কল্যাণমন্ত্রী সরদার মো. সাখাওয়াত হোসেন পরিষ্কার-পরিচ্ছন্নতা ও আগাম প্রতিরোধকে সবচেয়ে গুরুত্বপূর্ণ বলে উল্লেখ করেছেন। ডিসি সম্মেলনের ব্রিফিংয়ে তিনি বলেন, ডেঙ্গু মোকাবিলায় শুধু চিকিৎসা প্রস্তুতি যথেষ্ট নয়; এডিস মশার বিস্তার ঠেকাতে স্থানীয় প্রশাসন, স্বাস্থ্য বিভাগ ও নাগরিকদের সমন্বিত কাজ করতে হবে। হাসপাতালের ওষুধ ঘাটতি, অ্যাম্বুলেন্স সংকট ও চিকিৎসকদের উপস্থিতির বিষয়ও আলোচনায় এসেছে।",
                        'seo_keywords'     => "ডেঙ্গু, স্বাস্থ্য, স্বাস্থ্যমন্ত্রী, প্রতিরোধ, বাংলাদেশ",
                        'published_at'     => "2026-05-03 18:11",
                    ],

                    (object) [
                        "category_name"    => "স্বাস্থ্য",
                        'name'             => "স্বাস্থ্যকেন্দ্রে ১০ জন করে আনসার মোতায়েনের উদ্যোগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উপজেলা স্বাস্থ্য কমপ্লেক্সসহ বিভিন্ন স্বাস্থ্যকেন্দ্রে নিরাপত্তা জোরদারে ১০ জন করে আনসার সদস্য মোতায়েনের উদ্যোগ নেওয়া হয়েছে।",
                        "body"             => "দেশের উপজেলা স্বাস্থ্য কমপ্লেক্সসহ বিভিন্ন স্বাস্থ্যকেন্দ্রে নিরাপত্তা বাড়ানোর উদ্যোগ নিয়েছে স্বাস্থ্য মন্ত্রণালয়। স্বাস্থ্যমন্ত্রী জানিয়েছেন, প্রতিটি কেন্দ্রে একজন প্লাটুন কমান্ডারসহ ১০ জন করে আনসার সদস্য দেওয়া হবে এবং এটি দ্রুত বাস্তবায়নের পরিকল্পনা রয়েছে। চিকিৎসক, নার্স, রোগী ও হাসপাতালের সামগ্রিক পরিবেশ নিরাপদ রাখতে এ পদক্ষেপ নেওয়া হচ্ছে। একই সঙ্গে অসমাপ্ত হাসপাতাল ভবন চালু, বিদ্যুৎ সমস্যা ও লেবার রুমের কার্যকারিতা নিয়েও আলোচনা হয়েছে।",
                        'seo_keywords'     => "স্বাস্থ্যকেন্দ্র, আনসার, হাসপাতাল, নিরাপত্তা, চিকিৎসা",
                        'published_at'     => "2026-05-03 18:09",
                    ],

                    (object) [
                        "category_name"    => "স্বাস্থ্য",
                        'name'             => "ভেজাল ওষুধ ও অপচিকিৎসা ঠেকাতে বাড়বে মোবাইল টিম",
                        'sub_heading'      => "",
                        'content_shoulder' => "কঠোর নজরদারি",
                        'brief'            => "ভেজাল ওষুধ বিক্রি, অবৈধ ক্লিনিক ও অপচিকিৎসা ঠেকাতে সারাদেশে মোবাইল টিমের কার্যক্রম জোরদার করার কথা জানানো হয়েছে।",
                        "body"             => "ভেজাল ওষুধ ও অপচিকিৎসা রোধে সারাদেশে মোবাইল টিমের কার্যক্রম আরও শক্তিশালী করার ঘোষণা দিয়েছেন স্বাস্থ্যমন্ত্রী। তিনি বলেন, অবৈধ ও অপরিকল্পিত ক্লিনিকগুলোতে নজরদারি বাড়ানো হবে, যাতে রোগী হয়রানি ও অপচিকিৎসা বন্ধ করা যায়। ডিসপেনসারিতে ভেজাল ওষুধ বিক্রির বিরুদ্ধেও কঠোর ব্যবস্থা নেওয়ার কথা বলা হয়েছে। জেলা প্রশাসকদের সহযোগিতা নিয়ে স্বাস্থ্যখাতের সীমাবদ্ধতা মোকাবিলার বিষয়েও ব্রিফিংয়ে আলোচনা হয়েছে।",
                        'seo_keywords'     => "ভেজাল ওষুধ, অপচিকিৎসা, মোবাইল টিম, ক্লিনিক, স্বাস্থ্য",
                        'published_at'     => "2026-05-03 17:52",
                    ],

                    (object) [
                        "category_name"    => "স্বাস্থ্য",
                        'name'             => "হামের টিকা পেয়েছে ৮১ শতাংশ শিশু",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "হাম প্রতিরোধে চলমান টিকাদান কর্মসূচিতে ৮১ শতাংশের বেশি শিশুকে টিকার আওতায় আনা হয়েছে বলে জানিয়েছেন স্বাস্থ্যমন্ত্রী।",
                        "body"             => "হাম প্রতিরোধে চলমান টিকাদান কর্মসূচিতে ৮১ শতাংশের বেশি শিশুকে টিকা দেওয়া হয়েছে বলে জানিয়েছেন স্বাস্থ্য ও পরিবার কল্যাণমন্ত্রী। তিনি বলেন, বাকি শিশুদেরও দ্রুত টিকার আওতায় আনার কাজ চলছে এবং শতভাগ কভারেজ নিশ্চিত করা হবে। মন্ত্রীর দাবি, পর্যাপ্ত টিকা আছে এবং সারাদেশে সরবরাহ ঠিক রয়েছে। হামের সংক্রমণ নিয়ন্ত্রণে আনতে নিয়মিত টিকাদান, বিশেষ ক্যাম্পেইন ও মাঠপর্যায়ের নজরদারিকে গুরুত্ব দেওয়া হচ্ছে।",
                        'seo_keywords'     => "হাম, টিকা, শিশু স্বাস্থ্য, ভ্যাকসিন, বাংলাদেশ",
                        'published_at'     => "2026-05-03 17:42",
                    ],

                    (object) [
                        "category_name"    => "স্বাস্থ্য",
                        'name'             => "হাম ও উপসর্গে আরও ১০ জনের মৃত্যু",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গত ২৪ ঘণ্টায় হামে আক্রান্ত ও হামের উপসর্গ নিয়ে সারাদেশে আরও ১০ জনের মৃত্যু হয়েছে বলে জানিয়েছে স্বাস্থ্য অধিদপ্তর।",
                        "body"             => "স্বাস্থ্য অধিদপ্তরের তথ্য অনুযায়ী, গত ২৪ ঘণ্টায় হামে আক্রান্ত ও হামের উপসর্গ নিয়ে সারাদেশে আরও ১০ জন মারা গেছেন। এর মধ্যে একজন নিশ্চিত হামে এবং বাকি নয়জন হামজনিত উপসর্গে মারা গেছেন। একই সময়ে নতুন রোগী শনাক্ত ও ভর্তির সংখ্যাও বেড়েছে। ১৫ মার্চের পর থেকে নিশ্চিত হামে মৃত্যু ৫০ জনে এবং সন্দেহভাজন হামজনিত মৃত্যু ২৪৪ জনে দাঁড়িয়েছে। শিশুদের দ্রুত টিকার আওতায় আনা ও উপসর্গ দেখা দিলে চিকিৎসা নেওয়ার ওপর জোর দেওয়া হচ্ছে।",
                        'seo_keywords'     => "হাম, শিশু মৃত্যু, স্বাস্থ্য অধিদপ্তর, রোগ, বাংলাদেশ",
                        'published_at'     => "2026-05-03 16:14",
                    ],

                    (object) [
                        "category_name"    => "চিকিৎসা সংবাদ",
                        'name'             => "রাজধানীর টিবি হাসপাতালে ৩ মিলিয়ন ডলারের সরঞ্জাম দিল যুক্তরাষ্ট্র",
                        'sub_heading'      => "যক্ষ্মা শনাক্ত ও শিশুচিকিৎসায় নতুন সহায়তা",
                        'content_shoulder' => "",
                        'brief'            => "শ্যামলীর ২৫০ শয্যাবিশিষ্ট টিবি হাসপাতালে যুক্তরাষ্ট্র সরকার যক্ষ্মা প্রতিরোধী ওষুধ ও অত্যাধুনিক চিকিৎসা সরঞ্জাম দিয়েছে।",
                        "body"             => "রাজধানীর শ্যামলীর ২৫০ শয্যাবিশিষ্ট টিবি হাসপাতালে যুক্তরাষ্ট্র সরকার প্রায় ৩ মিলিয়ন ডলার মূল্যের যক্ষ্মা প্রতিরোধী ওষুধ ও চিকিৎসা সরঞ্জাম হস্তান্তর করেছে। অনুদানের মধ্যে রয়েছে ৬ লাখ জিনএক্সপার্ট কার্টিজ, যা দ্রুত ও নির্ভুলভাবে যক্ষ্মা শনাক্তে ব্যবহৃত হবে, এবং শিশুদের জন্য ১১ হাজার কোর্স পেডিয়াট্রিক টিবি ওষুধ। স্বাস্থ্য মন্ত্রণালয় মনে করছে, এই সহায়তা যক্ষ্মা শনাক্ত ও চিকিৎসা ব্যবস্থাকে আরও শক্তিশালী করবে।",
                        'seo_keywords'     => "টিবি হাসপাতাল, যক্ষ্মা, চিকিৎসা সরঞ্জাম, যুক্তরাষ্ট্র, স্বাস্থ্য",
                        'published_at'     => "2026-05-01 11:00",
                    ],

                    (object) [
                        "category_name"    => "চিকিৎসা সংবাদ",
                        'name'             => "মোবাইল ইউনিটে গ্রামেই হবে হৃদরোগের জটিল চিকিৎসা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মোবাইল মেডিকেল ও সার্জিক্যাল ইউনিটের মাধ্যমে হৃদরোগের মতো জটিল চিকিৎসা ঢাকার বাইরে পৌঁছে দেওয়ার পরিকল্পনা নেওয়া হয়েছে।",
                        "body"             => "স্বাস্থ্য প্রতিমন্ত্রী জানিয়েছেন, মোবাইল মেডিকেল ও সার্জিক্যাল ইউনিটের মাধ্যমে হৃদরোগসহ জটিল রোগের চিকিৎসা প্রত্যন্ত গ্রামাঞ্চলে পৌঁছে দেওয়া হবে। এনআইসিভিডির সেবা শুধু ঢাকায় সীমাবদ্ধ না রেখে ঢাকার বাইরের মানুষকে চিকিৎসার আওতায় আনার লক্ষ্য নেওয়া হয়েছে। অসংক্রামক ব্যাধির কারণে মৃত্যু ও পঙ্গুত্ব কমানোকে সরকারের বড় লক্ষ্য হিসেবে উল্লেখ করা হয়েছে। এ উদ্যোগ বাস্তবায়ন হলে জটিল রোগের চিকিৎসায় শহরনির্ভরতা কিছুটা কমতে পারে।",
                        'seo_keywords'     => "হৃদরোগ, মোবাইল ইউনিট, চিকিৎসা, গ্রাম, এনআইসিভিডি",
                        'published_at'     => "2026-04-22 00:00",
                    ],

                    (object) [
                        "category_name"    => "চিকিৎসা সংবাদ",
                        'name'             => "ক্যানসার মানেই মৃত্যু নয়: মেয়র শাহাদাত",
                        'sub_heading'      => "",
                        'content_shoulder' => "চিকিৎসায় আশা",
                        'brief'            => "চট্টগ্রামের মেয়র ও চিকিৎসক শাহাদাত হোসেন বলেছেন, আধুনিক চিকিৎসায় অনেক ক্যানসার রোগী সুস্থ জীবনে ফিরছেন।",
                        "body"             => "চট্টগ্রাম সিটি করপোরেশনের মেয়র ও চিকিৎসক শাহাদাত হোসেন বলেছেন, ক্যানসার মানেই মৃত্যু—এ ধারণা সঠিক নয়। অস্ত্রোপচার, ওষুধ ও রেডিয়েশনের সমন্বিত চিকিৎসায় অনেক রোগী সুস্থ হয়ে স্বাভাবিক জীবনে ফিরছেন। বৈশাখী মিলনমেলায় বক্তব্যে তিনি ক্যানসার নিয়ে ভয় না পেয়ে দ্রুত শনাক্তকরণ ও চিকিৎসার ওপর গুরুত্ব দেন। চিকিৎসকদের মতে, সময়মতো পরীক্ষা, সঠিক পরামর্শ এবং ধারাবাহিক চিকিৎসা ক্যানসার রোগীর বেঁচে থাকার সম্ভাবনা বাড়ায়।",
                        'seo_keywords'     => "ক্যানসার, চিকিৎসা, শাহাদাত হোসেন, চট্টগ্রাম, স্বাস্থ্য",
                        'published_at'     => "2026-05-01 00:00",
                    ],

                    (object) [
                        "category_name"    => "চিকিৎসা সংবাদ",
                        'name'             => "চিকিৎসক আছে, রোগী আছে, নেই রোগ নির্ণয়ের যন্ত্র",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "খুলনার বটিয়াঘাটা উপজেলা স্বাস্থ্য কমপ্লেক্সে প্রয়োজনীয় রোগ নির্ণয়ের যন্ত্রপাতির অভাবে রোগীরা ভোগান্তিতে পড়ছেন।",
                        "body"             => "খুলনার বটিয়াঘাটা উপজেলা স্বাস্থ্য কমপ্লেক্সে চিকিৎসক থাকলেও প্রয়োজনীয় রোগ নির্ণয়ের যন্ত্রপাতির অভাবে রোগীরা ভোগান্তিতে পড়ছেন। স্থানীয় মানুষকে অনেক সময় পরীক্ষা-নিরীক্ষার জন্য খুলনা শহরে যেতে হচ্ছে, যা সময় ও খরচ দুটোই বাড়াচ্ছে। চিকিৎসা নিতে আসা রোগীরা বলছেন, উপজেলা পর্যায়ে যন্ত্রপাতি সচল থাকলে প্রাথমিক রোগনির্ণয় দ্রুত হতো এবং অযথা শহরমুখী চাপ কমত। স্বাস্থ্যসেবার বিকেন্দ্রীকরণে এসব ঘাটতি দ্রুত সমাধান জরুরি।",
                        'seo_keywords'     => "উপজেলা স্বাস্থ্য কমপ্লেক্স, রোগ নির্ণয়, খুলনা, চিকিৎসা, হাসপাতাল",
                        'published_at'     => "2026-05-03 16:00",
                    ],

                    (object) [
                        "category_name"    => "চিকিৎসা সংবাদ",
                        'name'             => "জেলা হাসপাতালকে রেফারেল সেন্টার করতে চায় সরকার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জেলা হাসপাতালগুলোতে হৃদরোগ, ক্যানসার, ডায়ালাইসিস, ট্রমা কেয়ার ও আইসিইউ সেবা যুক্ত করে রেফারেল সেন্টার গড়ার পরিকল্পনা নেওয়া হয়েছে।",
                        "body"             => "স্বাস্থ্য প্রতিমন্ত্রী জানিয়েছেন, দেশের জেলা হাসপাতালগুলোকে বিশেষ রেফারেল সেন্টার হিসেবে গড়ে তুলতে চায় সরকার। পরিকল্পনায় হৃদরোগ, ক্যানসার, কিডনি ডায়ালাইসিস, ট্রমা কেয়ার ও আইসিইউ সেবার মতো জরুরি চিকিৎসা অন্তর্ভুক্ত করার কথা বলা হয়েছে। উপজেলা ও জেলা পর্যায়ের মধ্যে সমন্বিত সেকেন্ডারি হেলথ নেটওয়ার্ক তৈরি হলে রোগীদের ঢাকায় আসার চাপ কমবে এবং স্থানীয়ভাবে জটিল চিকিৎসার সুযোগ বাড়বে।",
                        'seo_keywords'     => "জেলা হাসপাতাল, রেফারেল সেন্টার, আইসিইউ, ডায়ালাইসিস, চিকিৎসা",
                        'published_at'     => "2026-04-১৮ 00:00",
                    ],

                    (object) [
                        "category_name"    => "জনস্বাস্থ্য",
                        'name'             => "হামের টিকাদানে ৮১ শতাংশ অগ্রগতি",
                        'sub_heading'      => "শতভাগ শিশুকে টিকার আওতায় আনার লক্ষ্য",
                        'content_shoulder' => "",
                        'brief'            => "হাম প্রতিরোধে চলমান টিকাদান কর্মসূচিতে ৮১ শতাংশ অগ্রগতি হয়েছে এবং দ্রুত শতভাগ কভারেজ নিশ্চিতের লক্ষ্য নেওয়া হয়েছে।",
                        "body"             => "জনস্বাস্থ্য সুরক্ষায় হাম প্রতিরোধী টিকাদান কর্মসূচিকে সবচেয়ে অগ্রাধিকার দেওয়া হচ্ছে। স্বাস্থ্যমন্ত্রী জানিয়েছেন, টিকা নেওয়ার উপযোগী ৮১ শতাংশের বেশি শিশুকে ইতিমধ্যে টিকা দেওয়া হয়েছে। বাকি শিশুদেরও দ্রুত টিকার আওতায় আনা হবে। পর্যাপ্ত টিকা এবং সরবরাহ ব্যবস্থা ঠিক আছে বলেও জানানো হয়েছে। হামের মতো অতিসংক্রামক রোগ নিয়ন্ত্রণে উচ্চ টিকা কভারেজ, মাঠপর্যায়ের সচেতনতা ও দ্রুত চিকিৎসা ব্যবস্থা অত্যন্ত জরুরি বলে বিশেষজ্ঞরা মনে করেন।",
                        'seo_keywords'     => "জনস্বাস্থ্য, হাম টিকা, শিশু, ভ্যাকসিন, বাংলাদেশ",
                        'published_at'     => "2026-05-03 17:42",
                    ],

                    (object) [
                        "category_name"    => "জনস্বাস্থ্য",
                        'name'             => "ডিএনসিসির ৫৯৯ কেন্দ্রে হাম-রুবেলার টিকা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা উত্তর সিটি করপোরেশনের ১০টি অঞ্চলে স্থায়ী ও অস্থায়ী মিলিয়ে ৫৯৯ কেন্দ্রে জরুরি হাম-রুবেলা টিকাদান চলছে।",
                        "body"             => "শিশুদের হাম-রুবেলা থেকে সুরক্ষা দিতে ঢাকা উত্তর সিটি করপোরেশনের ১০টি অঞ্চলে স্থায়ী ও অস্থায়ী মিলিয়ে ৫৯৯টি কেন্দ্রে জরুরি টিকাদান ক্যাম্পেইন শুরু হয়েছে। এই কর্মসূচির মাধ্যমে প্রায় ৫ লাখ শিশুকে টিকা দেওয়ার লক্ষ্য নেওয়া হয়েছে। হামের সাম্প্রতিক সংক্রমণ বৃদ্ধির প্রেক্ষাপটে নগর এলাকায় দ্রুত টিকা কভারেজ বাড়ানো জনস্বাস্থ্য সুরক্ষার জন্য গুরুত্বপূর্ণ। অভিভাবকদের নির্ধারিত বয়সী শিশুকে নিকটস্থ কেন্দ্রে নিয়ে যাওয়ার আহ্বান জানানো হয়েছে।",
                        'seo_keywords'     => "ডিএনসিসি, হাম রুবেলা, টিকাদান, জনস্বাস্থ্য, শিশু",
                        'published_at'     => "2026-04-১২ 00:00",
                    ],

                    (object) [
                        "category_name"    => "জনস্বাস্থ্য",
                        'name'             => "কলেরা নিয়ন্ত্রণে পরীক্ষামূলক গবেষণা শুরু করবে আইসিডিডিআরবি",
                        'sub_heading'      => "",
                        'content_shoulder' => "ঝুঁকিপূর্ণ এলাকা",
                        'brief'            => "নারায়ণগঞ্জ ও সীতাকুণ্ডের ঝুঁকিপূর্ণ এলাকায় কলেরা প্রতিরোধ কৌশল নির্ধারণে পরীক্ষামূলক গবেষণা শুরু করতে যাচ্ছে আইসিডিডিআরবি।",
                        "body"             => "বাংলাদেশে কলেরা এখনো বড় জনস্বাস্থ্য সমস্যা হিসেবে বিদ্যমান। রোগটি নিয়ন্ত্রণে অগ্রগতি হলেও পুরোপুরি নির্মূল হয়নি। এ পরিস্থিতিতে নারায়ণগঞ্জ ও সীতাকুণ্ডের ঝুঁকিপূর্ণ এলাকা বিবেচনা করে কলেরা প্রতিরোধ কৌশল নির্ধারণে পরীক্ষামূলক গবেষণা শুরু করতে যাচ্ছে আইসিডিডিআরবি। গবেষণার লক্ষ্য হলো ক্ষুদ্র ঝুঁকিপূর্ণ এলাকা চিহ্নিত করে সেখানে লক্ষ্যভিত্তিক হস্তক্ষেপের মাধ্যমে প্রাদুর্ভাবের ঝুঁকি কমানো।",
                        'seo_keywords'     => "কলেরা, আইসিডিডিআরবি, জনস্বাস্থ্য, গবেষণা, রোগ প্রতিরোধ",
                        'published_at'     => "2026-05-01 20:47",
                    ],

                    (object) [
                        "category_name"    => "জনস্বাস্থ্য",
                        'name'             => "ই–সিগারেটের নিষেধাজ্ঞামুক্তি নিয়ে জনস্বাস্থ্যবিদদের উদ্বেগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ই–সিগারেটের আমদানি ও বাজারজাতকরণে নিষেধাজ্ঞা তুলে নেওয়ায় তরুণদের স্বাস্থ্যঝুঁকি বাড়তে পারে বলে জনস্বাস্থ্যবিদেরা সতর্ক করেছেন।",
                        "body"             => "ই–সিগারেট বা ভ্যাপ পণ্যের নিষেধাজ্ঞা প্রত্যাহার নিয়ে জনস্বাস্থ্যবিদদের মধ্যে উদ্বেগ তৈরি হয়েছে। বিশেষজ্ঞরা বলছেন, এ ধরনের পণ্যে নিকোটিনসহ বিভিন্ন রাসায়নিক থাকতে পারে এবং তরুণদের মধ্যে আসক্তি বাড়ানোর ঝুঁকি রয়েছে। বিশ্ব স্বাস্থ্য সংস্থা ও ইউনিসেফও সিদ্ধান্তটি নিয়ে উদ্বেগ জানিয়েছে। জনস্বাস্থ্যবিদেরা মনে করেন, কার্যকর নিয়ন্ত্রণ কাঠামো ছাড়া ই–সিগারেট সহজলভ্য হলে কিশোর ও তরুণদের দীর্ঘমেয়াদি স্বাস্থ্যঝুঁকি বাড়বে।",
                        'seo_keywords'     => "ই-সিগারেট, ভ্যাপ, জনস্বাস্থ্য, নিকোটিন, তরুণ",
                        'published_at'     => "2026-04-27 08:42",
                    ],

                    (object) [
                        "category_name"    => "জনস্বাস্থ্য",
                        'name'             => "জলবায়ু পরিবর্তনে সংক্রামক রোগের ঝুঁকি বাড়ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জলবায়ু পরিবর্তন ও নগরায়ণের কারণে সংক্রামক রোগের ঝুঁকি বাড়ছে, তাই নজরদারি ও দ্রুত প্রতিক্রিয়ার ওপর জোর দেওয়া হয়েছে।",
                        "body"             => "চট্টগ্রাম বিভাগীয় স্বাস্থ্য প্রশাসনের আলোচনায় বলা হয়েছে, জলবায়ু পরিবর্তন ও নগরায়ণ সংক্রামক রোগের ঝুঁকি বাড়াচ্ছে। জনস্বাস্থ্য সুরক্ষায় কার্যকর টিকাদান, শক্তিশালী নজরদারি, তথ্য বিশ্লেষণ ও দ্রুত প্রতিক্রিয়া ব্যবস্থা প্রয়োজন। বাংলাদেশ জাতীয় টিকাদান কর্মসূচির মাধ্যমে হাম, ডিপথেরিয়া ও পোলিও নিয়ন্ত্রণে সাফল্য পেলেও নতুন ও উদীয়মান রোগ মোকাবিলায় স্বাস্থ্যব্যবস্থাকে আরও প্রস্তুত করার কথা বলা হয়েছে।",
                        'seo_keywords'     => "জলবায়ু পরিবর্তন, সংক্রামক রোগ, জনস্বাস্থ্য, টিকাদান, নজরদারি",
                        'published_at'     => "2026-04-০৬ 00:00",
                    ],

                    (object) [
                        "category_name"    => "রোগ",
                        'name'             => "হাম ও উপসর্গে মৃতের সংখ্যা বাড়ছে",
                        'sub_heading'      => "শিশুদের টিকাদান ও দ্রুত চিকিৎসার আহ্বান",
                        'content_shoulder' => "",
                        'brief'            => "হাম ও হামজনিত উপসর্গে মৃত্যু বাড়ায় শিশুদের দ্রুত টিকা, উপসর্গ শনাক্ত এবং হাসপাতালে চিকিৎসা নেওয়ার ওপর জোর দেওয়া হচ্ছে।",
                        "body"             => "দেশে হাম পরিস্থিতি উদ্বেগজনক পর্যায়ে রয়েছে। স্বাস্থ্য অধিদপ্তরের তথ্য অনুযায়ী, সাম্প্রতিক ২৪ ঘণ্টায় হামে ও হামের উপসর্গে আরও ১০ জনের মৃত্যু হয়েছে। নিশ্চিত হাম, সন্দেহজনক হাম, হাসপাতালে ভর্তি এবং ছাড়পত্র পাওয়া রোগীর সংখ্যাও নিয়মিত প্রকাশ করা হচ্ছে। বিশেষজ্ঞরা বলছেন, হাম অত্যন্ত সংক্রামক রোগ; তাই জ্বর, র‍্যাশ, কাশি বা চোখ লাল হওয়ার মতো লক্ষণ দেখা দিলে দ্রুত চিকিৎসা নিতে হবে এবং শিশুদের টিকাদান নিশ্চিত করতে হবে।",
                        'seo_keywords'     => "হাম, রোগ, শিশু স্বাস্থ্য, উপসর্গ, টিকা",
                        'published_at'     => "2026-05-03 15:54",
                    ],

                    (object) [
                        "category_name"    => "রোগ",
                        'name'             => "হাম ও রুবেলার পার্থক্য জানালেন বিশেষজ্ঞ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "হাম ও রুবেলাকে অনেকেই এক মনে করলেও জ্বর, র‍্যাশের সময়কাল ও জটিলতার ক্ষেত্রে দুই রোগের মধ্যে গুরুত্বপূর্ণ পার্থক্য আছে।",
                        "body"             => "বিশেষজ্ঞরা জানিয়েছেন, হাম ও রুবেলা দুটিই ভাইরাসজনিত সংক্রামক রোগ হলেও এদের উপসর্গ ও জটিলতা আলাদা। হামে জ্বর বেশি তীব্র হয়, র‍্যাশ ৫ থেকে ৭ দিন থাকতে পারে এবং নিউমোনিয়া বা ডায়রিয়ার ঝুঁকি থাকে। রুবেলায় জ্বর তুলনামূলক মৃদু, র‍্যাশ সাধারণত তিন দিন থাকে এবং গর্ভবতী নারী আক্রান্ত হলে গর্ভস্থ শিশুর মারাত্মক ক্ষতি হতে পারে। রোগ দুটির পার্থক্য জানা, টিকা নেওয়া ও উপসর্গ দেখা দিলে চিকিৎসকের পরামর্শ নেওয়া জরুরি।",
                        'seo_keywords'     => "হাম, রুবেলা, ভাইরাস, রোগ, টিকা",
                        'published_at'     => "2026-04-০৬ 02:00",
                    ],

                    (object) [
                        "category_name"    => "রোগ",
                        'name'             => "হিট স্ট্রোকের আগে শরীর যে সংকেত দেয়",
                        'sub_heading'      => "",
                        'content_shoulder' => "গরমে সতর্কতা",
                        'brief'            => "প্রচণ্ড গরমে হিট স্ট্রোকের আগে মুখ-গলা শুকানো, তীব্র তৃষ্ণা, প্রস্রাব কমে যাওয়া ও অস্বাভাবিক দুর্বলতার মতো সংকেত দেখা দিতে পারে।",
                        "body"             => "চিকিৎসকেরা বলছেন, হিট স্ট্রোক হঠাৎ ঘটে না; এর আগে শরীর বিভিন্ন সতর্ক সংকেত দেয়। অতিরিক্ত গরমে পানিশূন্যতা হলে মুখ ও গলা শুকিয়ে আসে, তীব্র তৃষ্ণা লাগে, প্রস্রাবের রং গাঢ় হয় এবং প্রস্রাব কমে যেতে পারে। হিট স্ট্রোক মেডিকেল ইমার্জেন্সি, তাই এসব লক্ষণ দেখা দিলে দ্রুত ঠান্ডা স্থানে বিশ্রাম, পানি বা ওরস্যালাইন পান, শরীরে পানি ছিটানো এবং প্রয়োজনে চিকিৎসকের কাছে নেওয়া জরুরি।",
                        'seo_keywords'     => "হিট স্ট্রোক, গরম, পানিশূন্যতা, রোগ, স্বাস্থ্য",
                        'published_at'     => "2026-04-22 02:00",
                    ],

                    (object) [
                        "category_name"    => "রোগ",
                        'name'             => "পা ফোলা কি গুরুতর রোগের লক্ষণ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "হঠাৎ ব্যথার সঙ্গে এক পা ফুলে শক্ত হয়ে গেলে ডিপ ভেইন থ্রম্বোসিসসহ গুরুতর রোগের লক্ষণ হতে পারে বলে সতর্ক করেছেন চিকিৎসকেরা।",
                        "body"             => "পা ফোলা অনেক সময় সাধারণ সমস্যা মনে হলেও কিছু ক্ষেত্রে এটি গুরুতর রোগের লক্ষণ হতে পারে। চিকিৎসকেরা বলছেন, শরীরের নিচের অংশ থেকে রক্ত ফেরত নেওয়া শিরায় রক্ত জমাট বাঁধলে ডিপ ভেইন থ্রম্বোসিস হতে পারে। এতে হঠাৎ ব্যথার সঙ্গে এক পা ফুলে শক্ত হয়ে যায়। রক্তপিণ্ড হার্ট বা ফুসফুসে গেলে মারাত্মক জটিলতা তৈরি হতে পারে। দীর্ঘদিন বিছানায় থাকা, ক্যানসার, আঘাত বা দীর্ঘ ভ্রমণের পর এমন লক্ষণ দেখা দিলে দ্রুত চিকিৎসা নেওয়া প্রয়োজন।",
                        'seo_keywords'     => "পা ফোলা, ডিভিটি, রক্ত জমাট, রোগ, চিকিৎসা",
                        'published_at'     => "2026-04-১৭ 06:00",
                    ],

                    (object) [
                        "category_name"    => "রোগ",
                        'name'             => "টেস্টোস্টেরন কমে গেলে যেসব সমস্যা হতে পারে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পুরুষ হরমোন টেস্টোস্টেরনের মাত্রা কমে গেলে ক্লান্তি, পেশিশক্তি কমা, হাড় দুর্বল হওয়া ও ঘুমের সমস্যার মতো উপসর্গ দেখা দিতে পারে।",
                        "body"             => "পুরুষের শারীরিক ও মানসিক সুস্থতার জন্য টেস্টোস্টেরনের স্বাভাবিক মাত্রা গুরুত্বপূর্ণ। বিশেষজ্ঞরা বলছেন, এই হরমোন কমে গেলে অস্বাভাবিক ক্লান্তি, শারীরিক শক্তি কমে যাওয়া, পেশির পুরুত্ব ও শক্তি কমা, হাড় দুর্বল হওয়া, মেদ জমা, ঘুমের ব্যাঘাত এবং অস্থিরতার মতো সমস্যা দেখা দিতে পারে। জীবনযাপন, ঘুম, সুষম খাবার ও মানসিক প্রশান্তির দিকে খেয়াল রাখতে বলা হয়েছে; উপসর্গ দীর্ঘস্থায়ী হলে চিকিৎসকের পরামর্শ নেওয়া জরুরি।",
                        'seo_keywords'     => "টেস্টোস্টেরন, হরমোন, পুরুষ স্বাস্থ্য, রোগ, ক্লান্তি",
                        'published_at'     => "2026-05-02 04:00",
                    ],

                    (object) [
                        "category_name"    => "পুষ্টি",
                        'name'             => "জাতীয় পুষ্টি সপ্তাহ শুরু",
                        'sub_heading'      => "পুষ্টি বৈষম্য দূর করে স্বনির্ভর বাংলাদেশ গড়ার বার্তা",
                        'content_shoulder' => "",
                        'brief'            => "রাজধানীসহ সারাদেশে নানা কর্মসূচির মধ্য দিয়ে জাতীয় পুষ্টি সপ্তাহ শুরু হয়েছে, এবারের প্রতিপাদ্য পুষ্টি বৈষম্য দূর করা।",
                        "body"             => "জাতীয় পুষ্টি সপ্তাহ-২০২৬ রাজধানীসহ সারাদেশে নানা কর্মসূচির মধ্য দিয়ে শুরু হয়েছে। এবারের প্রতিপাদ্য ‘পুষ্টি বৈষম্যের দিন শেষ, গড়বো স্বনির্ভর বাংলাদেশ’। জাতীয় বার্ন অ্যান্ড প্লাস্টিক সার্জারি ইনস্টিটিউটে সপ্তাহটির উদ্বোধন করেন স্বাস্থ্যমন্ত্রী। পুষ্টি বিশেষজ্ঞরা বলছেন, শিশু, কিশোরী, গর্ভবতী নারী ও প্রবীণদের পুষ্টি নিশ্চিত করা না গেলে দীর্ঘমেয়াদে স্বাস্থ্যঝুঁকি বাড়ে। তাই পুষ্টিসচেতনতা শুধু ব্যক্তিগত নয়, জাতীয় জনস্বাস্থ্যের অংশ।",
                        'seo_keywords'     => "জাতীয় পুষ্টি সপ্তাহ, পুষ্টি, স্বাস্থ্য, বাংলাদেশ, সচেতনতা",
                        'published_at'     => "2026-04-23 00:00",
                    ],

                    (object) [
                        "category_name"    => "পুষ্টি",
                        'name'             => "প্যাকেটজাত খাদ্যে সহজবোধ্য পুষ্টি লেবেলিংয়ের দাবি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "স্বাস্থ্যকর খাদ্যাভ্যাস গড়তে প্যাকেটজাত খাদ্যে সহজবোধ্য পুষ্টি লেবেলিং চালু জরুরি বলে মত দিয়েছেন বিশেষজ্ঞরা।",
                        "body"             => "প্যাকেটজাত খাদ্যে সহজবোধ্য পুষ্টি লেবেলিং বা এফওপিএল চালুর ওপর জোর দিয়েছেন বিশেষজ্ঞরা। তাঁদের মতে, ভোক্তারা অনেক সময় প্যাকেটের তথ্য বুঝতে পারেন না, ফলে অতিরিক্ত চিনি, লবণ, চর্বি বা ক্যালরিযুক্ত খাবার অজান্তেই বেশি খাওয়া হয়। ঢাকা আহ্ছানিয়া মিশনের স্বাস্থ্য সেক্টর আয়োজিত ওরিয়েন্টেশনে বলা হয়, ভবিষ্যতে নিরাপদ খাদ্য ও পুষ্টি নিশ্চিত করতে এফওপিএল নীতি জরুরি। তরুণদের খাদ্যাভ্যাস পরিবর্তনেও স্পষ্ট লেবেলিং গুরুত্বপূর্ণ ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "পুষ্টি লেবেলিং, প্যাকেটজাত খাবার, নিরাপদ খাদ্য, স্বাস্থ্যকর খাদ্য, পুষ্টি",
                        'published_at'     => "2026-04-27 00:00",
                    ],

                    (object) [
                        "category_name"    => "পুষ্টি",
                        'name'             => "মাতৃদুগ্ধ পান বাড়াতে ইউনিয়ন পর্যায়ে কাউন্সেলিংয়ের নির্দেশনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "শিশু পুষ্টি",
                        'brief'            => "মাতৃস্বাস্থ্য ও শিশুর পুষ্টি নিশ্চিত করতে ব্রেস্টফিডিং বিষয়ে ইউনিয়ন পর্যায়ে কাউন্সেলিং জোরদারের ওপর গুরুত্ব দেওয়া হয়েছে।",
                        "body"             => "মাতৃস্বাস্থ্য ও শিশুর পুষ্টি নিশ্চিত করতে ব্রেস্টফিডিং বা মাতৃদুগ্ধ পান বাড়ানোর বিষয়ে সচেতনতা জোরদারের কথা বলেছেন স্বাস্থ্যমন্ত্রী। ইউনিয়ন পর্যায়ে এ বিষয়ে কাউন্সেলিং কার্যক্রম বাড়ানোর নির্দেশনা দেওয়া হয়েছে। বিশেষজ্ঞরা মনে করেন, জন্মের পর শিশুর জন্য মায়ের দুধ সবচেয়ে গুরুত্বপূর্ণ পুষ্টি ও রোগপ্রতিরোধের উৎস। পরিবার, স্বাস্থ্যকর্মী ও স্থানীয় প্রশাসনের সমন্বিত সহায়তা পেলে নবজাতক ও শিশুর পুষ্টি নিরাপত্তা আরও শক্তিশালী হবে।",
                        'seo_keywords'     => "মাতৃদুগ্ধ, শিশুপুষ্টি, ব্রেস্টফিডিং, মাতৃস্বাস্থ্য, পুষ্টি",
                        'published_at'     => "2026-05-03 17:52",
                    ],

                    (object) [
                        "category_name"    => "পুষ্টি",
                        'name'             => "পান্তাভাতে পুষ্টিগুণ বাড়ে, জানালেন পুষ্টিবিদ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পান্তাভাতে ফারমেন্টেশনের কারণে আয়রন, ক্যালসিয়াম, ম্যাগনেশিয়াম, পটাশিয়াম ও ভালো ব্যাকটেরিয়ার উপস্থিতি বাড়তে পারে।",
                        "body"             => "পুষ্টিবিদদের মতে, পান্তাভাত শুধু ঐতিহ্যবাহী খাবার নয়, পুষ্টিগুণের দিক থেকেও গুরুত্বপূর্ণ। ভাত ৮ থেকে ১২ ঘণ্টা পানিতে ভিজিয়ে রাখলে ফারমেন্টেশন শুরু হয় এবং এতে আয়রন, ক্যালসিয়াম, ম্যাগনেশিয়াম, পটাশিয়াম ও জিংকের মতো অণুপুষ্টির সহজপ্রাপ্যতা বাড়তে পারে। পান্তায় প্রোবায়োটিক ব্যাকটেরিয়া তৈরি হতে পারে, যা অন্ত্রের স্বাস্থ্য ও হজমশক্তির জন্য উপকারী। তবে পরিচ্ছন্নতা বজায় রেখে নিরাপদ পানি ব্যবহার করা জরুরি।",
                        'seo_keywords'     => "পান্তাভাত, পুষ্টি, ফারমেন্টেশন, প্রোবায়োটিক, খাবার",
                        'published_at'     => "2026-04-০৮ 13:44",
                    ],

                    (object) [
                        "category_name"    => "পুষ্টি",
                        'name'             => "সকালের নাশতায় ফাইবার ও উদ্ভিজ্জ প্রোটিনের পরামর্শ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দিনের শুরুতে ফাইবার ও উদ্ভিজ্জ প্রোটিনসমৃদ্ধ নাশতা শরীরের শক্তি, বিপাকক্রিয়া ও দীর্ঘমেয়াদি সুস্থতায় সহায়ক হতে পারে।",
                        "body"             => "সকালের নাশতাকে দিনের গুরুত্বপূর্ণ খাবার হিসেবে দেখছেন পুষ্টিবিদেরা। বিশেষজ্ঞদের মতে, সকালে ফাইবার বেশি আছে এবং উদ্ভিজ্জ প্রোটিনসমৃদ্ধ খাবার খেলে শরীর প্রয়োজনীয় পুষ্টি পায়, শক্তি বাড়ে এবং সারা দিন ভালোভাবে কাজ করা যায়। ওটমিল, প্রাকৃতিক দই, বেরি, মিশ্র বাদাম ও বীজের মতো খাবার স্বাস্থ্যকর নাশতার অংশ হতে পারে। সুষম নাশতা দৈনিক ক্যালরির বড় অংশ পূরণ করলেও খাবারের মান বেছে নেওয়া সবচেয়ে গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "সকালের নাশতা, ফাইবার, প্রোটিন, পুষ্টি, সুস্বাস্থ্য",
                        'published_at'     => "2026-05-01 02:00",
                    ],

                    (object) [
                        "category_name"    => "মানসিক স্বাস্থ্য",
                        'name'             => "বাংলাদেশে মানসিক স্বাস্থ্য সংকট গভীর হচ্ছে",
                        'sub_heading'      => "চিকিৎসার বাইরে রয়ে যাচ্ছে বড় অংশ",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশে প্রাপ্তবয়স্ক ও শিশুদের মধ্যে মানসিক স্বাস্থ্য সমস্যা থাকলেও বড় অংশ চিকিৎসা বা কাউন্সেলিংয়ের বাইরে রয়ে যাচ্ছে।",
                        "body"             => "বাংলাদেশে মানসিক স্বাস্থ্য সংকট গভীর হচ্ছে বলে বিশেষজ্ঞরা সতর্ক করছেন। জাতীয় মানসিক স্বাস্থ্য ইনস্টিটিউটের তথ্য উদ্ধৃত করে প্রতিবেদনে বলা হয়েছে, প্রাপ্তবয়স্ক ও শিশুদের উল্লেখযোগ্য অংশ মানসিক স্বাস্থ্য সমস্যায় ভুগলেও অধিকাংশই চিকিৎসা ও কাউন্সেলিংয়ের বাইরে থাকে। সামাজিক লজ্জা, ভুল ধারণা, সেবার ঘাটতি এবং পেশাদার সহায়তা নিতে অনীহা পরিস্থিতিকে জটিল করছে। বিশেষজ্ঞরা মনে করেন, দ্রুত হস্তক্ষেপ, সহজলভ্য কাউন্সেলিং ও সচেতনতা বাড়ানো জরুরি।",
                        'seo_keywords'     => "মানসিক স্বাস্থ্য, বাংলাদেশ, কাউন্সেলিং, বিষণ্নতা, চিকিৎসা",
                        'published_at'     => "2026-03-24 00:00",
                    ],

                    (object) [
                        "category_name"    => "মানসিক স্বাস্থ্য",
                        'name'             => "স্নায়ু শিথিল রাখতে পাঁচ সহজ অভ্যাস",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আধুনিক জীবনের চাপ কমাতে গভীর শ্বাস, সঠিক পুষ্টি, ডিজিটাল ডিটক্স, প্রকৃতির সান্নিধ্য ও ক্যাফেইন নিয়ন্ত্রণের পরামর্শ দেওয়া হয়েছে।",
                        "body"             => "চিকিৎসকেরা বলছেন, দীর্ঘমেয়াদি মানসিক চাপ স্নায়ুতন্ত্রের ওপর নেতিবাচক প্রভাব ফেলে এবং উদ্বেগ, অনিদ্রা, হজমের সমস্যা ও উচ্চ রক্তচাপের মতো জটিলতা বাড়াতে পারে। স্নায়ু শিথিল রাখতে গভীর শ্বাসপ্রশ্বাস, ম্যাগনেশিয়ামসমৃদ্ধ খাবার, ঘুমের আগে ডিজিটাল ডিভাইস থেকে দূরে থাকা, প্রকৃতির সান্নিধ্যে হাঁটা, যোগব্যায়াম এবং অতিরিক্ত ক্যাফেইন ও চিনিযুক্ত পানীয় কমানোর পরামর্শ দেওয়া হয়েছে। ছোট অভ্যাস নিয়মিত চর্চা করলে মানসিক প্রশান্তি বাড়তে পারে।",
                        'seo_keywords'     => "স্নায়ু, মানসিক চাপ, ঘুম, ডিজিটাল ডিটক্স, মানসিক স্বাস্থ্য",
                        'published_at'     => "2026-05-01 12:00",
                    ],

                    (object) [
                        "category_name"    => "মানসিক স্বাস্থ্য",
                        'name'             => "ঘুম থেকে উঠেই ফোন দেখা মনোযোগ কমাতে পারে",
                        'sub_heading'      => "",
                        'content_shoulder' => "সকালের অভ্যাস",
                        'brief'            => "সকালের শুরুতেই ফোন, নোটিফিকেশন ও চাপপূর্ণ কাজ মস্তিষ্কের শান্ত অবস্থা ভেঙে উদ্বেগ ও ক্লান্তি বাড়াতে পারে।",
                        "body"             => "ঘুম থেকে উঠেই ফোন দেখা বা নোটিফিকেশন সামলানো মানসিক শক্তি ও মনোযোগে নেতিবাচক প্রভাব ফেলতে পারে বলে স্নায়ুবিজ্ঞানভিত্তিক আলোচনায় বলা হয়েছে। ঘুম থেকে ওঠার সময় মস্তিষ্ক ধীরে ধীরে শান্ত ও সৃজনশীল অবস্থায় প্রবেশ করে। কিন্তু ফোনের তথ্য, শব্দ ও প্রতিক্রিয়ার চাপ সেই প্রক্রিয়া ভেঙে দেয়। ফলে দিন শুরুর আগেই উদ্বেগ, তাড়াহুড়া ও ক্লান্তি বাড়তে পারে। বিশেষজ্ঞরা সকালে কিছু সময় ফোন থেকে দূরে থেকে ধীরে দিন শুরু করার পরামর্শ দেন।",
                        'seo_keywords'     => "সকালের অভ্যাস, ফোন, উদ্বেগ, মনোযোগ, মানসিক স্বাস্থ্য",
                        'published_at'     => "2026-04-03 14:00",
                    ],

                    (object) [
                        "category_name"    => "মানসিক স্বাস্থ্য",
                        'name'             => "বুননের কাজ মানসিক অস্থিরতা কমাতে সহায়ক হতে পারে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নিটিং বা ক্রোশেইং শুধু শখ নয়, মানসিক অস্থিরতা কমানো ও মনোযোগ ধরে রাখার সম্ভাব্য সহায়ক পদ্ধতি হিসেবেও আলোচিত হচ্ছে।",
                        "body"             => "সুই-সুতা বা বুননের কাজ সাম্প্রতিক সময়ে মানসিক স্বাস্থ্য ভালো রাখার সম্ভাব্য সহায়ক পদ্ধতি হিসেবে আলোচনায় এসেছে। নিটিং বা ক্রোশেইং মনোযোগ ধরে রাখতে, হাতকে ব্যস্ত রাখতে এবং অস্থির আচরণ কমাতে সাহায্য করতে পারে বলে গবেষক ও অভিজ্ঞরা বলছেন। সামাজিক যোগাযোগমাধ্যমে তরুণদের মধ্যেও এই কাজে আগ্রহ বাড়ছে। যদিও এ বিষয়ে গবেষণা এখনো সীমিত, বিশেষজ্ঞরা মনে করেন, পুনরাবৃত্তিমূলক সৃজনশীল কাজ মানসিক প্রশান্তি ও আত্মনিয়ন্ত্রণে সহায়ক হতে পারে।",
                        'seo_keywords'     => "বুনন, নিটিং, মানসিক অস্থিরতা, ক্রোশেইং, মানসিক স্বাস্থ্য",
                        'published_at'     => "2026-03-27 02:00",
                    ],

                    (object) [
                        "category_name"    => "মানসিক স্বাস্থ্য",
                        'name'             => "মস্তিষ্ক সুস্থ রাখতে ঘুম, সামাজিক জীবন ও স্ট্রেস নিয়ন্ত্রণ জরুরি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বয়স বাড়ার সঙ্গে মস্তিষ্ক সুস্থ রাখতে ঘুম, ব্যায়াম, নতুন কিছু শেখা, সামাজিক সম্পর্ক ও মানসিক চাপ নিয়ন্ত্রণের পরামর্শ দেওয়া হয়েছে।",
                        "body"             => "বয়স বাড়ার সঙ্গে স্মৃতি, মনোযোগ ও শেখার ক্ষমতায় পরিবর্তন আসতে পারে। বিশেষজ্ঞভিত্তিক পরামর্শে বলা হয়েছে, মস্তিষ্ক সুস্থ রাখতে পাজল বা নতুন দক্ষতা শেখা, হাঁটা বা কার্ডিও ব্যায়াম, স্বাস্থ্যকর খাবার, পর্যাপ্ত ঘুম, সামাজিক সম্পর্ক, স্ট্রেস নিয়ন্ত্রণ এবং নিয়মিত পানিপান গুরুত্বপূর্ণ। দীর্ঘদিনের দুশ্চিন্তা বা নেতিবাচক চিন্তা স্মৃতি ও মনোযোগে প্রভাব ফেলতে পারে। তাই কৃতজ্ঞতা চর্চা, প্রার্থনা, ধ্যান বা রিল্যাক্সেশন পদ্ধতি মস্তিষ্কের জন্য সহায়ক হতে পারে।",
                        'seo_keywords'     => "মস্তিষ্ক, স্ট্রেস, ঘুম, স্মৃতি, মানসিক স্বাস্থ্য",
                        'published_at'     => "2026-04-09 14:23",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষা",
                        'name'             => "জাতীয় বিশ্ববিদ্যালয়ের ভর্তি পরীক্ষার ফল প্রকাশ",
                        'sub_heading'      => "দুইভাবে ফল জানতে পারছেন ভর্তিচ্ছুরা",
                        'content_shoulder' => "",
                        'brief'            => "জাতীয় বিশ্ববিদ্যালয়ের ২০২৫-২৬ শিক্ষাবর্ষের স্নাতক ভর্তি পরীক্ষার ফল প্রকাশ হয়েছে, শিক্ষার্থীরা ওয়েবসাইট ও এসএমএসে ফল জানতে পারছেন।",
                        "body"             => "জাতীয় বিশ্ববিদ্যালয়ের অধীনে ২০২৫-২৬ শিক্ষাবর্ষের স্নাতক সম্মান প্রথম বর্ষের ভর্তি পরীক্ষার ফল প্রকাশ করা হয়েছে। গত ২৫ এপ্রিল দেশের ১৩৮টি কেন্দ্রে পরীক্ষা অনুষ্ঠিত হয়েছিল। পরীক্ষার মাত্র কয়েক দিনের মধ্যে ফল প্রকাশ হওয়ায় ভর্তি কার্যক্রম দ্রুত শুরু করা সম্ভব হবে। শিক্ষার্থীরা জাতীয় বিশ্ববিদ্যালয়ের নির্ধারিত ওয়েবসাইটে গিয়ে ফল দেখতে পারবেন। পাশাপাশি মোবাইল ফোনে এসএমএস পাঠিয়েও ফল জানা যাচ্ছে। প্রথম মেধাতালিকা প্রকাশের পর কলেজভিত্তিক ভর্তি প্রক্রিয়া শুরু হবে।",
                        'seo_keywords'     => "জাতীয় বিশ্ববিদ্যালয়, ভর্তি ফল, স্নাতক ভর্তি, শিক্ষা, বাংলাদেশ",
                        'published_at'     => "2026-05-03 09:09",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষা",
                        'name'             => "জাতীয় বিশ্ববিদ্যালয়ে স্নাতক প্রথম বর্ষের ক্লাস শুরু ২ জুন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জাতীয় বিশ্ববিদ্যালয়ের ২০২৫-২৬ শিক্ষাবর্ষে স্নাতক সম্মান প্রথম বর্ষের ক্লাস আগামী ২ জুন থেকে শুরু হবে বলে জানানো হয়েছে।",
                        "body"             => "জাতীয় বিশ্ববিদ্যালয়ের ২০২৫-২৬ শিক্ষাবর্ষে স্নাতক সম্মান প্রথম বর্ষের ক্লাস আগামী ২ জুন থেকে শুরু হবে। প্রথম মেধাতালিকা প্রকাশের পর ভর্তিচ্ছু শিক্ষার্থীরা নির্ধারিত সময়ের মধ্যে ভর্তি কার্যক্রম সম্পন্ন করবেন। জাতীয় বিশ্ববিদ্যালয়ের অধীন কলেজগুলোতে বিপুলসংখ্যক শিক্ষার্থী ভর্তি হওয়ায় ক্লাস শুরুর তারিখ শিক্ষার্থীদের জন্য গুরুত্বপূর্ণ। ভর্তি, বিষয় নির্বাচন ও কলেজভিত্তিক নির্দেশনা অনুসরণ করে নতুন শিক্ষাবর্ষের একাডেমিক কার্যক্রম শুরু হবে।",
                        'seo_keywords'     => "জাতীয় বিশ্ববিদ্যালয়, স্নাতক ক্লাস, প্রথম বর্ষ, ভর্তি, শিক্ষা",
                        'published_at'     => "2026-05-03 16:43",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষা",
                        'name'             => "২০২৫-২৬ শিক্ষাবর্ষের মেডিকেল শিক্ষার্থীদের ক্লাস শুরু",
                        'sub_heading'      => "",
                        'content_shoulder' => "নবীন ডাক্তারদের যাত্রা",
                        'brief'            => "সারাদেশের ১১২টি মেডিকেল কলেজে ২০২৫-২৬ শিক্ষাবর্ষের প্রথম বর্ষ এমবিবিএস শিক্ষার্থীদের ক্লাস একযোগে শুরু হয়েছে।",
                        "body"             => "২০২৫-২৬ শিক্ষাবর্ষের প্রথম বর্ষ এমবিবিএস শিক্ষার্থীদের ক্লাস সারাদেশের ১১২টি মেডিকেল কলেজে একযোগে শুরু হয়েছে। নতুন শিক্ষার্থীদের জন্য এটি পেশাগত চিকিৎসাশিক্ষার আনুষ্ঠানিক সূচনা। সরকারি ও বেসরকারি মেডিকেল কলেজগুলোতে ওরিয়েন্টেশন, একাডেমিক নির্দেশনা এবং ক্লাস রুটিন অনুযায়ী কার্যক্রম শুরু হচ্ছে। স্বাস্থ্যশিক্ষা খাতে নতুন ব্যাচ যুক্ত হওয়ায় ভবিষ্যৎ চিকিৎসক তৈরির প্রক্রিয়ায় আরেকটি ধাপ এগোল।",
                        'seo_keywords'     => "মেডিকেল কলেজ, এমবিবিএস, স্বাস্থ্য শিক্ষা, ক্লাস শুরু, বাংলাদেশ",
                        'published_at'     => "2026-05-03 00:00",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষা",
                        'name'             => "শাবিপ্রবির ১৮৬ শিক্ষার্থী পেল গবেষণা অনুদান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শিক্ষকদের পাশাপাশি শাহজালাল বিজ্ঞান ও প্রযুক্তি বিশ্ববিদ্যালয়ের ১৮৬ শিক্ষার্থীকে গবেষণা অনুদান দিয়েছে বিশ্ববিদ্যালয় প্রশাসন।",
                        "body"             => "শাহজালাল বিজ্ঞান ও প্রযুক্তি বিশ্ববিদ্যালয়ের বিভিন্ন অনুষদের ১৮৬ শিক্ষার্থীকে গবেষণা অনুদান দেওয়া হয়েছে। বিশ্ববিদ্যালয়ের গবেষণা কেন্দ্রের উদ্যোগে শিক্ষকদের পাশাপাশি শিক্ষার্থীদের গবেষণায় উৎসাহ দিতে এ অনুদান দেওয়া হয়। উচ্চশিক্ষায় গবেষণাচর্চা বাড়াতে শিক্ষার্থীদের সরাসরি অনুদান গুরুত্বপূর্ণ ভূমিকা রাখতে পারে। এতে তরুণ গবেষকেরা নিজ নিজ বিষয়ের সমস্যা, উদ্ভাবন ও বৈজ্ঞানিক অনুসন্ধানে আরও সক্রিয় হতে পারবেন।",
                        'seo_keywords'     => "শাবিপ্রবি, গবেষণা অনুদান, শিক্ষার্থী, বিশ্ববিদ্যালয়, শিক্ষা",
                        'published_at'     => "2026-05-03 16:20",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষা",
                        'name'             => "ব্রিটিশ কাউন্সিলের পার্টনার স্কুলস নেটওয়ার্কে দক্ষতা উন্নয়ন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশে ইংরেজি মাধ্যম শিক্ষায় ব্রিটিশ কাউন্সিলের পার্টনার স্কুলস নেটওয়ার্ক শিক্ষার্থীদের বৈশ্বিক দক্ষতা বাড়াচ্ছে।",
                        "body"             => "বাংলাদেশে ইংরেজি মাধ্যম শিক্ষা ও আন্তর্জাতিক মানদণ্ডভিত্তিক দক্ষতা উন্নয়নে ব্রিটিশ কাউন্সিল দীর্ঘদিন ধরে কাজ করছে। তাদের পার্টনার স্কুলস নেটওয়ার্ক শিক্ষার্থীদের পরীক্ষা, ভাষা দক্ষতা, যোগাযোগ সক্ষমতা ও বৈশ্বিক নাগরিকত্বের ধারণার সঙ্গে যুক্ত করছে। আধুনিক শিক্ষাব্যবস্থায় আন্তর্জাতিক স্বীকৃতি, মূল্যায়ন পদ্ধতি এবং শিক্ষকদের পেশাগত উন্নয়নও গুরুত্বপূর্ণ হয়ে উঠেছে। এ ধরনের উদ্যোগ শিক্ষার্থীদের উচ্চশিক্ষা ও বৈশ্বিক প্রতিযোগিতায় প্রস্তুত করতে সহায়ক।",
                        'seo_keywords'     => "ব্রিটিশ কাউন্সিল, ইংরেজি মাধ্যম, পার্টনার স্কুলস, শিক্ষা, দক্ষতা",
                        'published_at'     => "2026-05-03 12:00",
                    ],

                    (object) [
                        "category_name"    => "স্কুল",
                        'name'             => "এসএসসি পরীক্ষার্থী ১৮ লাখ ৫৭ হাজারের বেশি",
                        'sub_heading'      => "সারাদেশে ১১ শিক্ষা বোর্ডের অধীনে পরীক্ষা",
                        'content_shoulder' => "",
                        'brief'            => "২০২৬ সালের এসএসসি ও সমমান পরীক্ষায় ১১টি শিক্ষা বোর্ডের অধীনে ১৮ লাখ ৫৭ হাজারের বেশি শিক্ষার্থী অংশ নিচ্ছে।",
                        "body"             => "২০২৬ সালের এসএসসি ও সমমান পরীক্ষায় ১১টি শিক্ষা বোর্ডের অধীনে ১৮ লাখ ৫৭ হাজার ৩৪৪ জন শিক্ষার্থী অংশ নিচ্ছে। সাধারণ শিক্ষা বোর্ড, মাদরাসা শিক্ষা বোর্ড এবং কারিগরি শিক্ষা বোর্ড মিলিয়ে সারাদেশে হাজারো কেন্দ্রে পরীক্ষা অনুষ্ঠিত হচ্ছে। এ বছর শিক্ষাপ্রতিষ্ঠানের সংখ্যা, বোর্ডভিত্তিক পরীক্ষার্থী এবং ছাত্র-ছাত্রীর অনুপাত নিয়ে আলাদা আলোচনা তৈরি হয়েছে। পরীক্ষাকে প্রশ্নফাঁসমুক্ত ও সুশৃঙ্খল রাখতে শিক্ষা প্রশাসন বিশেষ নজরদারির কথা জানিয়েছে।",
                        'seo_keywords'     => "এসএসসি পরীক্ষা, স্কুল, শিক্ষা বোর্ড, পরীক্ষার্থী, বাংলাদেশ",
                        'published_at'     => "2026-04-19 00:00",
                    ],

                    (object) [
                        "category_name"    => "স্কুল",
                        'name'             => "নিবন্ধনের পরও এসএসসি দিচ্ছে না সাড়ে ৪ লাখ শিক্ষার্থী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নিবন্ধন করেও সাধারণ, মাদরাসা ও কারিগরি বোর্ডের বহু শিক্ষার্থী এ বছর এসএসসি ও সমমান পরীক্ষায় অংশ নিচ্ছে না।",
                        "body"             => "নবম শ্রেণিতে নিবন্ধন করলেও এ বছর এসএসসি ও সমমান পরীক্ষায় অংশ নিচ্ছে না প্রায় সাড়ে ৪ লাখ শিক্ষার্থী। সাধারণ শিক্ষা বোর্ড, মাদরাসা বোর্ড ও কারিগরি বোর্ডের তথ্য বিশ্লেষণে এ চিত্র উঠে এসেছে। সাধারণ বোর্ডে নিবন্ধিত শিক্ষার্থীদের একটি অংশ ফরম পূরণ করেনি। মাদরাসা ও কারিগরি ধারায় ঝরে পড়ার হারও উদ্বেগজনক। শিক্ষা সংশ্লিষ্টরা বলছেন, দারিদ্র্য, পারিবারিক চাপ, বাল্যবিবাহ, কর্মসংস্থানের চাপ এবং শিক্ষার ধারাবাহিকতা ধরে রাখার দুর্বলতা এর বড় কারণ হতে পারে।",
                        'seo_keywords'     => "এসএসসি, ঝরে পড়া, স্কুল শিক্ষার্থী, নিবন্ধন, শিক্ষা",
                        'published_at'     => "2026-04-20 00:00",
                    ],

                    (object) [
                        "category_name"    => "স্কুল",
                        'name'             => "স্কুল ফিডিংয়ের খাবারে অসুস্থতার ঘটনায় কঠোর নির্দেশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "নিরাপদ খাবার",
                        'brief'            => "স্কুল ফিডিংয়ের খাবার খেয়ে শিক্ষার্থী অসুস্থ হওয়ার ঘটনায় দায়ীদের বিরুদ্ধে কঠোর ব্যবস্থা নিতে নির্দেশ দেওয়া হয়েছে।",
                        "body"             => "স্কুল ফিডিং কর্মসূচির খাবার খেয়ে শিক্ষার্থী অসুস্থ হওয়ার ঘটনায় শিক্ষা প্রশাসন কঠোর অবস্থান নিয়েছে। সংশ্লিষ্টদের বিরুদ্ধে ব্যবস্থা নেওয়ার নির্দেশ দেওয়া হয়েছে এবং খাবারের মান, সরবরাহ প্রক্রিয়া ও তদারকি জোরদারের ওপর গুরুত্ব দেওয়া হয়েছে। স্কুল ফিডিং কর্মসূচি দরিদ্র ও প্রান্তিক শিক্ষার্থীদের পুষ্টি ও উপস্থিতি বাড়াতে গুরুত্বপূর্ণ। কিন্তু খাদ্য নিরাপত্তা নিশ্চিত না হলে এ কর্মসূচির ওপর অভিভাবকদের আস্থা কমে যেতে পারে। তাই মান নিয়ন্ত্রণ, নিয়মিত পরীক্ষা ও দায়বদ্ধতা নিশ্চিত করা জরুরি।",
                        'seo_keywords'     => "স্কুল ফিডিং, শিক্ষার্থী, খাবার, স্কুল, নিরাপত্তা",
                        'published_at'     => "2026-04-27 00:26",
                    ],

                    (object) [
                        "category_name"    => "স্কুল",
                        'name'             => "দারুননাজাত একাডেমিতে ইংরেজি বিতর্ক প্রতিযোগিতা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শিক্ষার্থীদের ভাষা দক্ষতা, যুক্তি উপস্থাপন ও নেতৃত্বগুণ বিকাশে দারুননাজাত একাডেমিতে ইংরেজি বিতর্ক প্রতিযোগিতা হয়েছে।",
                        "body"             => "দারুননাজাত একাডেমিতে ইংরেজি বিতর্ক প্রতিযোগিতা অনুষ্ঠিত হয়েছে। আয়োজকেরা বলেছেন, ইংরেজি বিতর্ক শুধু ভাষা শেখার মাধ্যম নয়; এটি শিক্ষার্থীদের যুক্তি উপস্থাপন, সমালোচনামূলক চিন্তা, আত্মবিশ্বাস ও নেতৃত্বগুণ বিকাশে গুরুত্বপূর্ণ ভূমিকা রাখে। বিশ্বায়নের যুগে ইংরেজিতে সাবলীলভাবে মতামত প্রকাশের সক্ষমতা শিক্ষার্থীদের উচ্চশিক্ষা ও ভবিষ্যৎ কর্মজীবনে সহায়তা করতে পারে। এ ধরনের সহশিক্ষা কার্যক্রম স্কুল পর্যায়ে শিক্ষার মান বাড়াতে সহায়ক।",
                        'seo_keywords'     => "ইংরেজি বিতর্ক, স্কুল, শিক্ষার্থী, নেতৃত্ব, শিক্ষা",
                        'published_at'     => "2026-05-02 15:00",
                    ],

                    (object) [
                        "category_name"    => "স্কুল",
                        'name'             => "প্রাথমিক বিদ্যালয়ে বিনা মূল্যে ইউনিফর্ম দেওয়ার উদ্যোগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দেশের সব প্রাথমিক বিদ্যালয়ের শিক্ষার্থীদের পর্যায়ক্রমে বিনা মূল্যে ইউনিফর্ম দেওয়ার পরিকল্পনার কথা জানানো হয়েছে।",
                        "body"             => "দেশের সব প্রাথমিক বিদ্যালয়ের শিক্ষার্থীদের বিনা মূল্যে ইউনিফর্ম দেওয়ার উদ্যোগের কথা জানিয়েছে প্রাথমিক ও গণশিক্ষা মন্ত্রণালয়। প্রতিমন্ত্রী জানিয়েছেন, বছরে একবার শিক্ষার্থীদের ইউনিফর্ম দেওয়ার লক্ষ্য নিয়ে কাজ শুরু হয়েছে। পাশাপাশি প্রাথমিক বিদ্যালয়ে মিড ডে মিল চালুর বিষয়েও পরিকল্পনা এগোচ্ছে। দরিদ্র পরিবারের শিশুদের স্কুলে ধরে রাখা, উপস্থিতি বাড়ানো এবং শিক্ষায় সমতা নিশ্চিত করতে ইউনিফর্ম ও খাদ্য সহায়তা গুরুত্বপূর্ণ ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "প্রাথমিক বিদ্যালয়, ইউনিফর্ম, স্কুল শিক্ষার্থী, প্রাথমিক শিক্ষা, বাংলাদেশ",
                        'published_at'     => "2026-04-13 00:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ববিদ্যালয়",
                        'name'             => "রুয়েটে প্রথম বর্ষের ক্লাস শুরু ১০ মে",
                        'sub_heading'      => "নবীন শিক্ষার্থীদের ওরিয়েন্টেশন ৯ মে",
                        'content_shoulder' => "",
                        'brief'            => "রাজশাহী প্রকৌশল ও প্রযুক্তি বিশ্ববিদ্যালয়ে ২০২৫-২৬ শিক্ষাবর্ষের স্নাতক প্রথম বর্ষের ক্লাস ১০ মে শুরু হবে।",
                        "body"             => "রাজশাহী প্রকৌশল ও প্রযুক্তি বিশ্ববিদ্যালয়ে ২০২৫-২৬ শিক্ষাবর্ষের স্নাতক প্রথম বর্ষের ভর্তি কার্যক্রম শেষের দিকে। বিশ্ববিদ্যালয় প্রশাসন জানিয়েছে, নবীন শিক্ষার্থীদের ওরিয়েন্টেশন ৯ মে এবং আনুষ্ঠানিক ক্লাস ১০ মে শুরু হবে। প্রকৌশল শিক্ষায় ভর্তি হওয়া শিক্ষার্থীদের জন্য ওরিয়েন্টেশন একাডেমিক নিয়ম, বিভাগীয় পরিচিতি, ক্লাস রুটিন ও বিশ্ববিদ্যালয় জীবনের আচরণবিধি জানার গুরুত্বপূর্ণ সুযোগ। নতুন ব্যাচ যুক্ত হওয়ার মাধ্যমে রুয়েটের একাডেমিক কার্যক্রম আরও বিস্তৃত হবে।",
                        'seo_keywords'     => "রুয়েট, বিশ্ববিদ্যালয়, প্রথম বর্ষ, ক্লাস শুরু, প্রকৌশল শিক্ষা",
                        'published_at'     => "2026-05-02 00:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ববিদ্যালয়",
                        'name'             => "ঢাকা সেন্ট্রাল ইউনিভার্সিটির ভর্তি আবেদন মে মাসে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা সেন্ট্রাল ইউনিভার্সিটির ২০২৫-২৬ শিক্ষাবর্ষের ভর্তি আবেদন মে মাসের প্রথম সপ্তাহে শুরু হতে পারে।",
                        "body"             => "ঢাকা সেন্ট্রাল ইউনিভার্সিটির ২০২৫-২৬ শিক্ষাবর্ষের ভর্তি প্রক্রিয়া মে মাসের প্রথম সপ্তাহে শুরু হতে পারে বলে বিশ্ববিদ্যালয় কর্তৃপক্ষ জানিয়েছে। ভর্তি পরীক্ষা জুনের মাঝামাঝি আয়োজনের পরিকল্পনা রয়েছে। বিশ্ববিদ্যালয়ের লোগো তৈরি, শিক্ষার্থীদের আইডি কার্ড এবং প্রাতিষ্ঠানিক প্রস্তুতির কাজও এগোচ্ছে। নতুন বিশ্ববিদ্যালয় হিসেবে ভর্তি প্রক্রিয়ার সময়সূচি, আসনসংখ্যা, ইউনিটভিত্তিক পরীক্ষা এবং একাডেমিক কাঠামো শিক্ষার্থীদের আগ্রহের কেন্দ্রে রয়েছে।",
                        'seo_keywords'     => "ঢাকা সেন্ট্রাল ইউনিভার্সিটি, ভর্তি আবেদন, বিশ্ববিদ্যালয়, ভর্তি পরীক্ষা, শিক্ষা",
                        'published_at'     => "2026-04-23 00:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ববিদ্যালয়",
                        'name'             => "উচ্চশিক্ষার হিট প্রকল্প দ্রুত বাস্তবায়নের নির্দেশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "মানোন্নয়ন প্রকল্প",
                        'brief'            => "উচ্চশিক্ষার মানোন্নয়নে চলমান হায়ার এডুকেশন এক্সিলারেশন অ্যান্ড ট্রান্সফরমেশন প্রকল্প দ্রুত বাস্তবায়নের নির্দেশ দেওয়া হয়েছে।",
                        "body"             => "উচ্চশিক্ষার মানোন্নয়নে চলমান হায়ার এডুকেশন এক্সিলারেশন অ্যান্ড ট্রান্সফরমেশন বা হিট প্রকল্প দ্রুত বাস্তবায়নের নির্দেশ দিয়েছেন শিক্ষামন্ত্রী। প্রকল্পের অর্থের সঠিক ব্যবহার, বিশ্ববিদ্যালয়গুলোর সক্ষমতা বৃদ্ধি এবং গবেষণা ও উদ্ভাবনভিত্তিক শিক্ষার প্রসারকে গুরুত্ব দেওয়া হচ্ছে। উচ্চশিক্ষায় আন্তর্জাতিক মান, প্রযুক্তি ব্যবহারের সক্ষমতা, শিক্ষক-শিক্ষার্থীর গবেষণা সহায়তা এবং প্রাতিষ্ঠানিক জবাবদিহি নিশ্চিত করা এ ধরনের প্রকল্পের বড় লক্ষ্য।",
                        'seo_keywords'     => "হিট প্রকল্প, উচ্চশিক্ষা, বিশ্ববিদ্যালয়, গবেষণা, শিক্ষা মন্ত্রণালয়",
                        'published_at'     => "2026-05-01 00:00",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ববিদ্যালয়",
                        'name'             => "গ্রামীণ বিশ্ববিদ্যালয়ের পরামর্শক হলেন নিয়াজ আহমদ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা বিশ্ববিদ্যালয়ের সাবেক উপাচার্য অধ্যাপক নিয়াজ আহমদ গ্রামীণ বিশ্ববিদ্যালয়ের পরামর্শক হিসেবে দায়িত্ব পেয়েছেন।",
                        "body"             => "ঢাকা বিশ্ববিদ্যালয়ের সাবেক উপাচার্য অধ্যাপক নিয়াজ আহমদ গ্রামীণ বিশ্ববিদ্যালয়ের পরামর্শক হিসেবে দায়িত্ব পেয়েছেন। বিশ্ববিদ্যালয় পর্যায়ে প্রশাসনিক অভিজ্ঞতা, একাডেমিক পরিকল্পনা ও উচ্চশিক্ষা ব্যবস্থাপনায় তাঁর দীর্ঘ সম্পৃক্ততা নতুন দায়িত্বে কাজে লাগতে পারে। বেসরকারি বিশ্ববিদ্যালয়গুলোর মান উন্নয়ন, গবেষণা পরিবেশ, পাঠ্যক্রম ও প্রশাসনিক কাঠামো শক্তিশালী করতে অভিজ্ঞ শিক্ষাবিদের পরামর্শ গুরুত্বপূর্ণ ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "গ্রামীণ বিশ্ববিদ্যালয়, নিয়াজ আহমদ, বিশ্ববিদ্যালয়, উপাচার্য, উচ্চশিক্ষা",
                        'published_at'     => "2026-04-29 08:58",
                    ],

                    (object) [
                        "category_name"    => "বিশ্ববিদ্যালয়",
                        'name'             => "জাতীয় বিশ্ববিদ্যালয়ে ৮৮০ কলেজে ভর্তি কার্যক্রম শুরু হচ্ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জাতীয় বিশ্ববিদ্যালয়ের অধীন ৮৮০ কলেজে স্নাতক সম্মান প্রথম বর্ষে ভর্তির জন্য মেধাতালিকা প্রকাশের পর কার্যক্রম শুরু হচ্ছে।",
                        "body"             => "জাতীয় বিশ্ববিদ্যালয়ের অধীন ৮৮০টি কলেজে ২০২৫-২৬ শিক্ষাবর্ষের স্নাতক সম্মান প্রথম বর্ষে ভর্তি কার্যক্রম শুরু হচ্ছে। ভর্তি পরীক্ষার ফল ও প্রথম মেধাতালিকা প্রকাশের পর ভর্তিচ্ছু শিক্ষার্থীরা কলেজভিত্তিক আসন, বিষয় এবং নির্দেশনা অনুসরণ করে ভর্তি সম্পন্ন করবেন। জাতীয় বিশ্ববিদ্যালয় দেশের উচ্চশিক্ষার বড় অংশ বহন করে। তাই দ্রুত ফল প্রকাশ ও ক্লাস শুরুর তারিখ নির্ধারণ শিক্ষার্থীদের শিক্ষাজীবনের সময় ব্যবস্থাপনায় গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "জাতীয় বিশ্ববিদ্যালয়, ৮৮০ কলেজ, স্নাতক ভর্তি, মেধাতালিকা, বিশ্ববিদ্যালয়",
                        'published_at'     => "2026-05-03 14:00",
                    ],

                    (object) [
                        "category_name"    => "পরীক্ষা",
                        'name'             => "এইচএসসি পরীক্ষা শুরু হবে ২ জুলাই",
                        'sub_heading'      => "প্রায় পাঁচ সপ্তাহ ধরে চলবে পরীক্ষা",
                        'content_shoulder' => "",
                        'brief'            => "২০২৬ সালের এইচএসসি ও সমমান পরীক্ষা ২ জুলাই থেকে শুরু হবে এবং প্রায় পাঁচ সপ্তাহ ধরে পরীক্ষা চলবে বলে সিদ্ধান্ত হয়েছে।",
                        "body"             => "২০২৬ সালের এইচএসসি ও সমমান পরীক্ষা আগামী ২ জুলাই থেকে শুরু হবে। শিক্ষা মন্ত্রণালয়ের সিদ্ধান্ত অনুযায়ী পরীক্ষা প্রায় পাঁচ সপ্তাহ ধরে চলবে। পরীক্ষার্থীরা প্রস্তুতির সময় কম পাচ্ছে—এমন উদ্বেগ, সময়সূচি ও শিক্ষার্থীদের মানসিক চাপ বিবেচনায় নিয়ে পরীক্ষা পেছানোর সিদ্ধান্ত হয়েছে। পূর্ণাঙ্গ রুটিন প্রকাশের পর শিক্ষার্থীদের বিষয়ভিত্তিক প্রস্তুতি আরও সুনির্দিষ্ট হবে। উচ্চশিক্ষায় ভর্তি প্রস্তুতির সঙ্গে এইচএসসি পরীক্ষার সময়সূচি সরাসরি যুক্ত থাকায় সিদ্ধান্তটি শিক্ষার্থী ও অভিভাবকদের জন্য গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "এইচএসসি পরীক্ষা, পরীক্ষা রুটিন, শিক্ষা বোর্ড, শিক্ষার্থী, বাংলাদেশ",
                        'published_at'     => "2026-04-18 14:33",
                    ],

                    (object) [
                        "category_name"    => "পরীক্ষা",
                        'name'             => "এসএসসি পরীক্ষা শুরু, মানতে হবে ১৪ নির্দেশনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "২০২৬ সালের এসএসসি ও সমমান পরীক্ষা ২১ এপ্রিল শুরু হয়েছে, পরীক্ষার্থীদের জন্য ১৪টি বিশেষ নির্দেশনা দেওয়া হয়েছে।",
                        "body"             => "২০২৬ সালের এসএসসি ও সমমান পরীক্ষা ২১ এপ্রিল শুরু হয়েছে। পরীক্ষা চলবে ২০ মে পর্যন্ত। পরীক্ষার্থীদের জন্য প্রবেশপত্র, নির্ধারিত সময়ে কেন্দ্রে উপস্থিতি, পরীক্ষার সরঞ্জাম, নিষিদ্ধ ডিভাইস ব্যবহার না করা এবং শৃঙ্খলা বজায় রাখাসহ ১৪টি বিশেষ নির্দেশনা দেওয়া হয়েছে। পরীক্ষা সুষ্ঠু ও প্রশ্নফাঁসমুক্ত রাখতে কেন্দ্র, বোর্ড ও শিক্ষা প্রশাসন সমন্বিতভাবে কাজ করছে। শিক্ষার্থীদের মানসিক চাপ কমিয়ে নিয়ম মেনে পরীক্ষায় অংশ নেওয়ার পরামর্শ দেওয়া হয়েছে।",
                        'seo_keywords'     => "এসএসসি ২০২৬, পরীক্ষা নির্দেশনা, মাধ্যমিক পরীক্ষা, শিক্ষার্থী, শিক্ষা",
                        'published_at'     => "2026-04-20 08:32",
                    ],

                    (object) [
                        "category_name"    => "পরীক্ষা",
                        'name'             => "এসএসসির দ্বিতীয় দিনে অনুপস্থিত ২৬ হাজারের বেশি",
                        'sub_heading'      => "",
                        'content_shoulder' => "পরীক্ষা নজরদারি",
                        'brief'            => "এসএসসি ও সমমান পরীক্ষার দ্বিতীয় দিনে ১১টি শিক্ষা বোর্ডে ২৬ হাজার ৮৬৫ জন অনুপস্থিত এবং ১৯ জন বহিষ্কার হয়েছে।",
                        "body"             => "এসএসসি ও সমমান পরীক্ষার দ্বিতীয় দিনে ১১টি শিক্ষা বোর্ডে ২৬ হাজার ৮৬৫ জন পরীক্ষার্থী অনুপস্থিত ছিল এবং ১৯ জন বহিষ্কার হয়েছে। অনুপস্থিতির সংখ্যা পরীক্ষায় শিক্ষার্থীদের ধারাবাহিক অংশগ্রহণ নিয়ে প্রশ্ন তৈরি করছে। শিক্ষা বোর্ডগুলো কেন্দ্রভিত্তিক তথ্য সংগ্রহ করে নিয়মিত প্রতিবেদন দিচ্ছে। পরীক্ষায় নকল, অসদুপায়, প্রশ্নফাঁসের গুজব এবং কেন্দ্রের শৃঙ্খলা ভঙ্গ ঠেকাতে প্রশাসন কঠোর নজরদারি রাখছে।",
                        'seo_keywords'     => "এসএসসি, অনুপস্থিত, বহিষ্কার, পরীক্ষা, শিক্ষা বোর্ড",
                        'published_at'     => "2026-04-23 00:00",
                    ],

                    (object) [
                        "category_name"    => "পরীক্ষা",
                        'name'             => "এসএসসির ফল ৬০ দিনের আগেই প্রকাশের লক্ষ্য",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "২০২৬ সালের এসএসসি ও সমমান পরীক্ষার ফল ৬০ দিনের আগেই প্রকাশের লক্ষ্যে শিক্ষা বোর্ডগুলো প্রস্তুতি নিচ্ছে।",
                        "body"             => "২০২৬ সালের এসএসসি ও সমমান পরীক্ষার ফল ৬০ দিনের আগেই প্রকাশের লক্ষ্য নিয়ে শিক্ষা বোর্ডগুলো কাজ করছে। পরীক্ষার খাতা মূল্যায়ন, নম্বরফর্দ প্রস্তুত, ব্যবহারিক পরীক্ষার নম্বর পাঠানো এবং বোর্ডভিত্তিক যাচাই প্রক্রিয়া দ্রুত সম্পন্ন করার ওপর গুরুত্ব দেওয়া হচ্ছে। শিক্ষার্থীদের পরবর্তী ভর্তি প্রক্রিয়া, কলেজ নির্বাচন এবং একাডেমিক পরিকল্পনার জন্য সময়মতো ফল প্রকাশ গুরুত্বপূর্ণ। ফল প্রকাশে গতি আনলেও মূল্যায়নের স্বচ্ছতা ও নির্ভুলতা বজায় রাখাকে অগ্রাধিকার দেওয়ার কথা বলা হয়েছে।",
                        'seo_keywords'     => "এসএসসি ফল, পরীক্ষা ফলাফল, শিক্ষা বোর্ড, শিক্ষার্থী, বাংলাদেশ",
                        'published_at'     => "2026-04-20 00:00",
                    ],

                    (object) [
                        "category_name"    => "পরীক্ষা",
                        'name'             => "পাবলিক পরীক্ষায় ‘নীরব বহিষ্কার’ প্রথা বাদ দেওয়ার উদ্যোগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পাবলিক পরীক্ষায় ‘নীরব বহিষ্কার’ নামে পরিচিত পুরোনো নীতিমালা পরিবর্তনের নির্দেশ দিয়েছেন শিক্ষামন্ত্রী।",
                        "body"             => "পাবলিক পরীক্ষায় ‘নীরব বহিষ্কার’ নামে পরিচিত পুরোনো নীতিমালা বাদ দেওয়ার উদ্যোগ নেওয়া হয়েছে। শিক্ষামন্ত্রী বলেছেন, পরীক্ষাসংক্রান্ত বিধিতে অনুচিত বা অস্পষ্ট কোনো ব্যবস্থা থাকা উচিত নয়। বর্তমান পরীক্ষাকেন্দ্রের পরিস্থিতি ও আইনগত কাঠামো বিবেচনায় এ ধরনের নীতিমালা আর প্রযোজ্য নয় বলে তিনি মন্তব্য করেন। দ্রুত নীতিমালা পরিবর্তনের নির্দেশ দেওয়া হয়েছে। পরীক্ষায় শৃঙ্খলা বজায় রাখার পাশাপাশি পরীক্ষার্থীর অধিকার ও স্বচ্ছ প্রক্রিয়া নিশ্চিত করাও গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "নীরব বহিষ্কার, পাবলিক পরীক্ষা, পরীক্ষা নীতিমালা, শিক্ষামন্ত্রী, শিক্ষা",
                        'published_at'     => "2026-04-18 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৃত্তি",
                        'name'             => "এইচএসসি শিক্ষার্থীদের ভর্তি সহায়তার আবেদনের সময় বাড়ল",
                        'sub_heading'      => "অসচ্ছল মেধাবীরা পাবে ৬ হাজার টাকা",
                        'content_shoulder' => "",
                        'brief'            => "উচ্চমাধ্যমিক ও সমমান পর্যায়ে ভর্তি হওয়া অসচ্ছল মেধাবী শিক্ষার্থীদের ভর্তি সহায়তার অনলাইন আবেদনের সময় বাড়ানো হয়েছে।",
                        "body"             => "উচ্চমাধ্যমিক ও সমমান পর্যায়ে ২০২৫-২৬ শিক্ষাবর্ষে ভর্তি হওয়া আর্থিকভাবে অসচ্ছল ও মেধাবী শিক্ষার্থীদের ভর্তি সহায়তার অনলাইন আবেদনের সময় বাড়ানো হয়েছে। নির্বাচিত শিক্ষার্থীরা ৬ হাজার টাকা ভর্তি সহায়তা পাবে। কার্যক্রমটি অনলাইনভিত্তিক হওয়ায় আবেদনকারীদের হার্ড কপি পাঠানোর প্রয়োজন নেই। আর্থিকভাবে পিছিয়ে থাকা শিক্ষার্থীদের কলেজভর্তি ব্যয় সামলাতে এ ধরনের সহায়তা গুরুত্বপূর্ণ ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "ভর্তি সহায়তা, এইচএসসি, বৃত্তি, শিক্ষার্থী, আর্থিক সহায়তা",
                        'published_at'     => "2026-05-02 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৃত্তি",
                        'name'             => "ব্র্যাকের মেধাবিকাশ বৃত্তির সময় বৃদ্ধি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ব্র্যাকের মেধাবিকাশ বৃত্তিতে আবেদনের সময় বাড়ানো হয়েছে, নির্বাচিত শিক্ষার্থীরা এককালীন ও মাসিক সহায়তা পাবেন।",
                        "body"             => "ব্র্যাকের মেধাবিকাশ বৃত্তিতে আবেদনের সময় বৃদ্ধি করা হয়েছে। ২০২৫-২৬ শিক্ষাবর্ষে সরকারি বিশ্ববিদ্যালয়ে ভর্তির জন্য নির্বাচিত শিক্ষার্থীরা এ বৃত্তির জন্য আবেদন করতে পারবেন। নির্বাচিত শিক্ষার্থী এককালীন ২০ হাজার টাকা এবং মাসে ৮ হাজার টাকা বৃত্তি পাবেন। অর্থনৈতিকভাবে পিছিয়ে থাকা মেধাবী শিক্ষার্থীদের উচ্চশিক্ষা চালিয়ে যেতে এ সহায়তা গুরুত্বপূর্ণ। এসএসসি ও এইচএসসি পর্যায়ে ভালো ফল, পারিবারিক আয় এবং সামাজিক প্রেক্ষাপট বিবেচনায় নির্বাচন করা হবে।",
                        'seo_keywords'     => "ব্র্যাক, মেধাবিকাশ বৃত্তি, বিশ্ববিদ্যালয়, বৃত্তি, শিক্ষার্থী",
                        'published_at'     => "2026-04-29 05:45",
                    ],

                    (object) [
                        "category_name"    => "বৃত্তি",
                        'name'             => "কারিগরি ও মাদ্রাসা শিক্ষার্থীদের বিশেষ মঞ্জুরি",
                        'sub_heading'      => "",
                        'content_shoulder' => "অনলাইন আবেদন",
                        'brief'            => "কারিগরি ও মাদ্রাসা শিক্ষা বিভাগের অধীন শিক্ষাপ্রতিষ্ঠান, শিক্ষক-কর্মচারী ও শিক্ষার্থীদের বিশেষ মঞ্জুরির আবেদন শেষ হচ্ছে।",
                        "body"             => "কারিগরি ও মাদ্রাসা শিক্ষা বিভাগের অধীন শিক্ষাপ্রতিষ্ঠান, শিক্ষক-কর্মচারী ও শিক্ষার্থীদের বিশেষ মঞ্জুরির আবেদন প্রক্রিয়া চলছে। শিক্ষার্থীরা জটিল ও ব্যয়বহুল রোগ, দৈব দুর্ঘটনা এবং শিক্ষাব্যয় নির্বাহের জন্য আবেদন করতে পারবেন। প্রতিবন্ধী, দরিদ্র, মেধাবী ও অনগ্রসর সম্প্রদায়ের শিক্ষার্থীদের অগ্রাধিকার দেওয়া হবে। আবেদন প্রক্রিয়া অনলাইনে সম্পন্ন করতে হবে এবং প্রতিষ্ঠানপ্রধানের প্রত্যয়নসহ প্রয়োজনীয় প্রমাণকপত্র সংযুক্ত করতে হবে।",
                        'seo_keywords'     => "কারিগরি শিক্ষা, মাদ্রাসা, বিশেষ মঞ্জুরি, বৃত্তি, আর্থিক অনুদান",
                        'published_at'     => "2026-04-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৃত্তি",
                        'name'             => "বাংলাদেশিদের জন্য যুক্তরাজ্যে স্কলারশিপ বাড়ানোর আহ্বান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "যুক্তরাজ্যে বাংলাদেশি শিক্ষার্থীদের জন্য স্কলারশিপ বাড়াতে ব্রিটিশ কূটনীতিকদের প্রতি আহ্বান জানিয়েছেন শিক্ষামন্ত্রী।",
                        "body"             => "যুক্তরাজ্যে বাংলাদেশি শিক্ষার্থীদের জন্য স্কলারশিপ বাড়াতে ব্রিটিশ কূটনীতিকদের প্রতি আহ্বান জানিয়েছেন শিক্ষামন্ত্রী। সচিবালয়ে ব্রিটিশ হাইকমিশনের প্রতিনিধির সঙ্গে সাক্ষাতে উচ্চশিক্ষা, শিক্ষার্থী বিনিময় এবং বৃত্তির সুযোগ প্রসারের বিষয় আলোচনায় আসে। যুক্তরাজ্য দীর্ঘদিন ধরে বাংলাদেশি শিক্ষার্থীদের উচ্চশিক্ষার জনপ্রিয় গন্তব্য। স্কলারশিপ বাড়লে মেধাবী কিন্তু আর্থিকভাবে সীমাবদ্ধ শিক্ষার্থীদের আন্তর্জাতিক শিক্ষার সুযোগ আরও প্রসারিত হতে পারে।",
                        'seo_keywords'     => "যুক্তরাজ্য স্কলারশিপ, বাংলাদেশি শিক্ষার্থী, বৃত্তি, উচ্চশিক্ষা, শিক্ষামন্ত্রী",
                        'published_at'     => "2026-04-23 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৃত্তি",
                        'name'             => "স্কুল-কলেজ-মাদ্রাসা ও বিশ্ববিদ্যালয়ে শিক্ষাবৃত্তি বাড়ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শিক্ষার্থীদের মাসিক বৃত্তি ও এককালীন অনুদানের পরিমাণ বাড়ানোর খসড়া প্রস্তাবনায় বিভিন্ন স্তরের সহায়তা বৃদ্ধির কথা বলা হয়েছে।",
                        "body"             => "স্কুল, কলেজ, মাদ্রাসা ও বিশ্ববিদ্যালয় পর্যায়ে শিক্ষাবৃত্তির পরিমাণ বাড়ানোর খসড়া প্রস্তাবনা আলোচনায় এসেছে। প্রাথমিক, জুনিয়র ও উচ্চতর পর্যায়ের মাসিক বৃত্তির পাশাপাশি এককালীন অনুদানও বাড়ানোর পরিকল্পনা রয়েছে। দরিদ্র ও মেধাবী শিক্ষার্থীদের শিক্ষায় ধরে রাখতে আর্থিক সহায়তা গুরুত্বপূর্ণ। বৃত্তির পরিমাণ বাস্তব ব্যয়ের সঙ্গে সামঞ্জস্যপূর্ণ হলে শিক্ষার্থীদের বই, যাতায়াত, ভর্তি ও অন্যান্য শিক্ষাব্যয় সামলানো সহজ হবে।",
                        'seo_keywords'     => "শিক্ষাবৃত্তি, অনুদান, স্কুল কলেজ, বিশ্ববিদ্যালয়, শিক্ষার্থী",
                        'published_at'     => "2026-04-18 00:00",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষানীতি",
                        'name'             => "ঢাকার কিছু প্রতিষ্ঠানে অনলাইন-অফলাইন ক্লাসের পরীক্ষামূলক পরিকল্পনা",
                        'sub_heading'      => "সপ্তাহে তিন দিন অনলাইন, তিন দিন সশরীর ক্লাস",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকার সক্ষম কিছু শিক্ষাপ্রতিষ্ঠানে পরীক্ষামূলকভাবে অনলাইন ও সশরীর ক্লাসের সমন্বিত পদ্ধতি চালুর পরিকল্পনা হয়েছে।",
                        "body"             => "ঢাকার সক্ষম কিছু শিক্ষাপ্রতিষ্ঠানে পরীক্ষামূলকভাবে অনলাইন ও সশরীর ক্লাসের সমন্বিত পদ্ধতি চালুর পরিকল্পনা করেছে শিক্ষা মন্ত্রণালয়। প্রস্তাবিত মডেলে শিক্ষার্থীরা সপ্তাহে তিন দিন স্কুলে এসে ক্লাস করবে এবং তিন দিন বাসা থেকে অনলাইনে ক্লাসে অংশ নেবে। শিক্ষকেরা প্রতিষ্ঠানে উপস্থিত থেকে অনলাইন ক্লাস নেবেন। ট্রাফিক চাপ, জ্বালানি ব্যয়, শিক্ষার্থীর উপস্থিতি এবং প্রযুক্তিনির্ভর শিক্ষার প্রস্তুতি বিবেচনায় এ সিদ্ধান্ত নেওয়া হচ্ছে।",
                        'seo_keywords'     => "অনলাইন ক্লাস, অফলাইন ক্লাস, শিক্ষানীতি, ঢাকা, শিক্ষা মন্ত্রণালয়",
                        'published_at'     => "2026-04-09 12:51",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষানীতি",
                        'name'             => "প্রাথমিক শিক্ষা ধাপে ধাপে অষ্টম শ্রেণি পর্যন্ত করার পরিকল্পনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "প্রাথমিক শিক্ষা ধাপে ধাপে অষ্টম শ্রেণি পর্যন্ত বিস্তৃত করার পরিকল্পনার কথা জানিয়েছেন প্রাথমিক ও গণশিক্ষা প্রতিমন্ত্রী।",
                        "body"             => "প্রাথমিক শিক্ষা ধাপে ধাপে অষ্টম শ্রেণি পর্যন্ত বিস্তৃত করার পরিকল্পনার কথা জানিয়েছেন প্রাথমিক ও গণশিক্ষা প্রতিমন্ত্রী। তিনি ৪ থেকে ১৩ বছর বয়সী শিশুদের জন্য অবৈতনিক শিক্ষার লক্ষ্য নিয়েও কথা বলেন। প্রাথমিক স্তর বাড়ানো হলে পাঠ্যক্রম, শিক্ষক নিয়োগ, অবকাঠামো, মূল্যায়ন পদ্ধতি এবং প্রশাসনিক কাঠামোতে বড় পরিবর্তন আনতে হবে। শিক্ষাবিদদের মতে, পরিকল্পনাটি বাস্তবায়নে পর্যাপ্ত বাজেট, শিক্ষক প্রশিক্ষণ এবং বিদ্যালয়ভিত্তিক সক্ষমতা জরুরি।",
                        'seo_keywords'     => "প্রাথমিক শিক্ষা, অষ্টম শ্রেণি, শিক্ষানীতি, অবৈতনিক শিক্ষা, বাংলাদেশ",
                        'published_at'     => "2026-04-21 00:00",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষানীতি",
                        'name'             => "ভর্তি পরীক্ষার দীর্ঘসূত্রতায় সময় অপচয় কমাতে সমন্বিত ব্যবস্থার তাগিদ",
                        'sub_heading'      => "",
                        'content_shoulder' => "ভর্তি সংস্কার",
                        'brief'            => "এইচএসসি ও বিশ্ববিদ্যালয় ভর্তি পরীক্ষার দীর্ঘসূত্রতা কমাতে সমন্বিত ভর্তি ব্যবস্থা চালুর প্রয়োজনীয়তার কথা বলেছেন শিক্ষামন্ত্রী।",
                        "body"             => "এইচএসসি ও বিশ্ববিদ্যালয় ভর্তি পরীক্ষার দীর্ঘসূত্রতা ও সমন্বয়হীনতার কারণে শিক্ষার্থীদের বড় সময় অপচয় হচ্ছে বলে মন্তব্য করেছেন শিক্ষামন্ত্রী। তিনি বলেন, ভর্তি প্রক্রিয়ার জটিলতা শিক্ষার্থীদের একাডেমিক অগ্রগতি বিলম্বিত করে এবং জাতীয় পর্যায়ে উৎপাদনশীল সময়ের ক্ষতি করে। এ পরিস্থিতি কাটাতে সমন্বিত ভর্তি ব্যবস্থা চালুর প্রয়োজনীয়তার কথা বলা হয়েছে। ভর্তি পরীক্ষা, ফল প্রকাশ, ক্লাস শুরু এবং বিশ্ববিদ্যালয়ভিত্তিক সিদ্ধান্তের মধ্যে সমন্বয় আনলে শিক্ষার্থীদের অনিশ্চয়তা কমতে পারে।",
                        'seo_keywords'     => "ভর্তি পরীক্ষা, শিক্ষানীতি, সমন্বিত ভর্তি, বিশ্ববিদ্যালয়, শিক্ষামন্ত্রী",
                        'published_at'     => "2026-04-26 00:00",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষানীতি",
                        'name'             => "প্রশ্নফাঁস ও কোচিং বাণিজ্য নিয়ন্ত্রণে কঠোর অবস্থান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "প্রশ্নফাঁস, নকল ও কোচিং বাণিজ্য নিয়ন্ত্রণে শিক্ষা মন্ত্রণালয় কঠোর অবস্থানের কথা জানিয়েছে।",
                        "body"             => "প্রশ্নফাঁস, নকল ও কোচিং বাণিজ্য নিয়ন্ত্রণে শিক্ষা মন্ত্রণালয় কঠোর অবস্থান নিয়েছে। শিক্ষামন্ত্রী বলেছেন, পরীক্ষায় প্রশ্নফাঁস বা নকল কোনোভাবেই বরদাশত করা হবে না। একই সঙ্গে শিক্ষকদের কোচিং বাণিজ্য নিয়ন্ত্রণে ইন-হাউজ কোচিং ব্যবস্থা চালুর কথা বলা হয়েছে। প্রতিষ্ঠানের ভেতরে নিয়ন্ত্রিত সহায়তামূলক পাঠদান হলে শিক্ষকের সম্মানী দেওয়ার সুযোগ রাখা যেতে পারে, তবে প্রতিষ্ঠানের বাইরে অনিয়ন্ত্রিত কোচিং করালে ব্যবস্থা নেওয়ার কথা জানানো হয়েছে।",
                        'seo_keywords'     => "প্রশ্নফাঁস, কোচিং বাণিজ্য, শিক্ষানীতি, নকল, শিক্ষা মন্ত্রণালয়",
                        'published_at'     => "2026-04-10 00:00",
                    ],

                    (object) [
                        "category_name"    => "শিক্ষানীতি",
                        'name'             => "বুলিং ও ইভটিজিং ঠেকাতে ৫১৭ শিক্ষাপ্রতিষ্ঠানে সিসিটিভি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বুলিং ও ইভটিজিং ঠেকাতে ৫১৭টি শিক্ষাপ্রতিষ্ঠানে সিসিটিভি বসানোর উদ্যোগ নিয়েছে শিক্ষা মন্ত্রণালয়।",
                        "body"             => "বুলিং ও ইভটিজিং প্রতিরোধে ৫১৭টি শিক্ষাপ্রতিষ্ঠানে সিসিটিভি বসানোর উদ্যোগ নেওয়া হয়েছে। শিক্ষা মন্ত্রণালয়ের নির্দেশিকা অনুযায়ী জেলা প্রশাসকদের প্রয়োজনীয় ব্যবস্থা নিতে বলা হয়েছে। শিক্ষাপ্রতিষ্ঠানে নিরাপদ পরিবেশ নিশ্চিত করা শিক্ষার্থীর মানসিক স্বাস্থ্য, উপস্থিতি এবং শেখার আগ্রহের সঙ্গে সরাসরি সম্পর্কিত। তবে সিসিটিভির পাশাপাশি সচেতনতা, অভিযোগ ব্যবস্থাপনা, কাউন্সেলিং এবং শিক্ষক-অভিভাবক সমন্বয়ও জরুরি বলে বিশেষজ্ঞরা মনে করেন।",
                        'seo_keywords'     => "বুলিং, ইভটিজিং, সিসিটিভি, শিক্ষাপ্রতিষ্ঠান, শিক্ষানীতি",
                        'published_at'     => "2026-04-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "জীবনধারা",
                        'name'             => "কান ফোঁড়ানোর আগে-পরে যত্ন না নিলে বাড়তে পারে সংক্রমণ",
                        'sub_heading'      => "সৌন্দর্যচর্চায় পরিচ্ছন্নতা ও সঠিক পরবর্তী যত্ন জরুরি",
                        'content_shoulder' => "",
                        'brief'            => "কান ফোঁড়ানো সাধারণ সৌন্দর্যচর্চা হলেও জীবাণুমুক্ত পরিবেশ, পরিচ্ছন্ন যন্ত্র ও পরবর্তী যত্ন না মানলে সংক্রমণ হতে পারে।",
                        "body"             => "কান ফোঁড়ানো অনেকের কাছে সৌন্দর্যচর্চার স্বাভাবিক অংশ। তবে বিশেষজ্ঞরা বলছেন, কাজটি করার আগে পরিচ্ছন্নতা, জীবাণুমুক্ত যন্ত্র এবং অভিজ্ঞ ব্যক্তির সহায়তা নিশ্চিত করা জরুরি। ফোঁড়ানোর পর জায়গাটি নিয়মিত পরিষ্কার রাখা, অযথা হাত না দেওয়া এবং ব্যথা, ফুলে যাওয়া বা পুঁজ দেখা দিলে দ্রুত চিকিৎসকের পরামর্শ নেওয়া দরকার। ছোট অসতর্কতা থেকেও সংক্রমণ বড় সমস্যা তৈরি করতে পারে।",
                        'seo_keywords'     => "কান ফোঁড়ানো, সৌন্দর্যচর্চা, লাইফস্টাইল, সংক্রমণ, যত্ন",
                        'published_at'     => "2026-05-03 00:00",
                    ],

                    (object) [
                        "category_name"    => "জীবনধারা",
                        'name'             => "ঘরে বানানো টার্মারিক বুস্টারে বাড়তে পারে রোগপ্রতিরোধ ক্ষমতা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "হলুদ, লেবু, গোলমরিচ ও আদা দিয়ে তৈরি টার্মারিক বুস্টার শরীরকে সতেজ রাখতে এবং প্রদাহ কমাতে সহায়ক হতে পারে।",
                        "body"             => "হলুদের সঙ্গে আদা, লেবু, গোলমরিচ ও মধুর মতো উপাদান মিশিয়ে ঘরে সহজেই টার্মারিক বুস্টার তৈরি করা যায়। হলুদে থাকা কারকিউমিন প্রদাহ কমাতে সহায়ক হিসেবে পরিচিত। তবে এটি কোনো চিকিৎসার বিকল্প নয়; বরং স্বাস্থ্যকর খাবার ও জীবনযাপনের অংশ হিসেবে গ্রহণ করা যেতে পারে। যাদের গ্যাস্ট্রিক, অ্যালার্জি বা নিয়মিত ওষুধ সেবনের ইতিহাস আছে, তাদের পরিমিতি মেনে খাওয়া ভালো।",
                        'seo_keywords'     => "টার্মারিক বুস্টার, হলুদ, স্বাস্থ্যকর পানীয়, জীবনধারা, রোগপ্রতিরোধ",
                        'published_at'     => "2026-05-02 11:47",
                    ],

                    (object) [
                        "category_name"    => "জীবনধারা",
                        'name'             => "ব্যস্ততার ভিড়ে হারিয়ে যাচ্ছে নিজের জন্য সময়",
                        'sub_heading'      => "",
                        'content_shoulder' => "নিজের সময়",
                        'brief'            => "কাজ, পরিবার ও ডিজিটাল ব্যস্ততার চাপে ব্যক্তিগত সময় কমে যাচ্ছে, ফলে মানসিক চাপ ও ক্লান্তি বাড়ছে।",
                        "body"             => "আধুনিক জীবনের কাজের চাপ, সামাজিক দায়িত্ব ও মোবাইলনির্ভর ব্যস্ততায় অনেকেই নিজের জন্য আলাদা সময় রাখতে পারছেন না। দিনের শেষে বিশ্রাম, শখ, পড়া, হাঁটা বা নীরবতায় থাকা—এসব ছোট অভ্যাস মানসিক ভারসাম্যের জন্য গুরুত্বপূর্ণ। ব্যক্তিগত সময় না থাকলে বিরক্তি, ক্লান্তি, মনোযোগ কমে যাওয়া ও সম্পর্কের টানাপোড়েন বাড়তে পারে। তাই প্রতিদিন কিছু সময় নিজের জন্য আলাদা রাখাকে জীবনধারার প্রয়োজনীয় অংশ হিসেবে দেখার পরামর্শ দেওয়া হচ্ছে।",
                        'seo_keywords'     => "ব্যক্তিগত সময়, মানসিক চাপ, জীবনধারা, ব্যস্ততা, সুস্থতা",
                        'published_at'     => "2026-05-01 12:19",
                    ],

                    (object) [
                        "category_name"    => "জীবনধারা",
                        'name'             => "গরমে মেকআপ দীর্ঘস্থায়ী রাখতে হালকা বেসের পরামর্শ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গরমে ঘাম ও আর্দ্রতার কারণে মেকআপ নষ্ট হতে পারে, তাই হালকা বেস, সেটিং পাউডার ও ওয়াটারপ্রুফ পণ্য ব্যবহার করা ভালো।",
                        "body"             => "গরমে মেকআপ ধরে রাখা অনেকের জন্য কঠিন হয়ে ওঠে। বিশেষজ্ঞদের মতে, ভারী ফাউন্ডেশনের বদলে হালকা বেস, প্রাইমার, সেটিং পাউডার এবং ওয়াটারপ্রুফ আই মেকআপ ব্যবহার করলে মেকআপ তুলনামূলক বেশি সময় টিকে থাকে। মেকআপের আগে ত্বক পরিষ্কার ও ময়েশ্চারাইজ করা জরুরি। অতিরিক্ত পণ্য ব্যবহার না করে ত্বকের ধরন অনুযায়ী সাজ নিলে গরমেও সতেজ ও স্বাভাবিক লুক বজায় রাখা সম্ভব।",
                        'seo_keywords'     => "গরমের মেকআপ, বিউটি টিপস, লাইফস্টাইল, ত্বক, ফ্যাশন",
                        'published_at'     => "2026-04-25 10:28",
                    ],

                    (object) [
                        "category_name"    => "জীবনধারা",
                        'name'             => "ত্বক ও চুলের যত্নে আমলকী ব্যবহারে আগ্রহ বাড়ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "প্রাকৃতিক উপাদান হিসেবে আমলকী ত্বক ও চুলের যত্নে ব্যবহৃত হচ্ছে, এতে ভিটামিন সি ও অ্যান্টিঅক্সিডেন্ট থাকে।",
                        "body"             => "ত্বক ও চুলের বিভিন্ন সমস্যায় প্রাকৃতিক উপাদান ব্যবহারের প্রবণতা বাড়ছে। আমলকী ভিটামিন সি ও অ্যান্টিঅক্সিডেন্টসমৃদ্ধ হওয়ায় চুলের উজ্জ্বলতা, স্ক্যাল্পের যত্ন এবং ত্বক সতেজ রাখতে অনেকেই এটি ব্যবহার করেন। আমলকীর তেল, প্যাক বা খাদ্যতালিকায় আমলকী যুক্ত করার আগে নিজের ত্বক ও শরীরের সহনশীলতা বুঝে নেওয়া দরকার। সংবেদনশীল ত্বকে সরাসরি ব্যবহার করলে আগে প্যাচ টেস্ট করা ভালো।",
                        'seo_keywords'     => "আমলকী, ত্বকের যত্ন, চুলের যত্ন, প্রাকৃতিক যত্ন, জীবনধারা",
                        'published_at'     => "2026-04-18 16:51",
                    ],

                    (object) [
                        "category_name"    => "ফ্যাশন",
                        'name'             => "সারা লাইফস্টাইলের ঈদুল আজহা কালেকশনে বৈচিত্র্যময় পোশাক",
                        'sub_heading'      => "সব বয়স ও রুচির ক্রেতার জন্য সাজানো উৎসব সংগ্রহ",
                        'content_shoulder' => "",
                        'brief'            => "ঈদুল আজহা উপলক্ষে সারা লাইফস্টাইল পুরুষ, নারী ও শিশুদের জন্য নতুন পোশাকের বৈচিত্র্যময় সংগ্রহ বাজারে এনেছে।",
                        "body"             => "আসন্ন ঈদুল আজহা ঘিরে সারা লাইফস্টাইল নতুন পোশাকের কালেকশন এনেছে। সংগ্রহে নারীদের টপস, সিঙ্গেল-পিস আউটফিট, থ্রি-পিস, ডেনিম ও অন্যান্য বটম ওয়্যার রয়েছে। পুরুষ ও শিশুদের জন্যও আলাদা ডিজাইনের পোশাক রাখা হয়েছে। উৎসবের পোশাকে আরামদায়ক কাপড়, ব্যবহারযোগ্য কাটিং ও সমসাময়িক ডিজাইনকে গুরুত্ব দেওয়া হয়েছে। ঈদকে সামনে রেখে দেশীয় ফ্যাশন বাজারে এ ধরনের কালেকশন ক্রেতাদের আগ্রহ বাড়াচ্ছে।",
                        'seo_keywords'     => "সারা লাইফস্টাইল, ঈদ কালেকশন, ফ্যাশন, পোশাক, বাংলাদেশ",
                        'published_at'     => "2026-04-30 00:00",
                    ],

                    (object) [
                        "category_name"    => "ফ্যাশন",
                        'name'             => "মেরিল-প্রথম আলো পুরস্কারে তারকাদের ব্লাউজ নকশায় নতুনত্ব",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মেরিল-প্রথম আলো পুরস্কারের লালগালিচায় তারকাদের শাড়ি ও ব্লাউজের নকশা বিশেষভাবে নজর কাড়ে।",
                        "body"             => "মেরিল-প্রথম আলো পুরস্কারের ২৭তম আসরে লালগালিচায় তারকাদের পোশাক নিয়ে আলাদা আলোচনা তৈরি হয়। বিশেষ করে শাড়ির সঙ্গে মিলিয়ে ব্লাউজের কাট, পুঁতির কাজ, ঝুলন্ত অলঙ্করণ ও রঙের ব্যবহারে নতুনত্ব দেখা যায়। বাংলাদেশি তারকাদের উৎসব ও পুরস্কার আয়োজনে দেশীয় ফ্যাশনের উপস্থাপন এখন আরও সচেতন ও পরিকল্পিত হচ্ছে। এতে ডিজাইনার, স্টাইলিস্ট ও মেকআপ শিল্পীদের কাজও সামনে আসছে।",
                        'seo_keywords'     => "মেরিল পুরস্কার, ব্লাউজ ডিজাইন, তারকা ফ্যাশন, লালগালিচা, ফ্যাশন",
                        'published_at'     => "2026-05-01 15:14",
                    ],

                    (object) [
                        "category_name"    => "ফ্যাশন",
                        'name'             => "ছোট চুলে নজর কাড়লেন মডেল ও অভিনেত্রী পিয়া",
                        'sub_heading'      => "",
                        'content_shoulder' => "হেয়ারস্টাইল ট্রেন্ড",
                        'brief'            => "তারকাদের সাম্প্রতিক সাজে পোশাকের পাশাপাশি ছোট চুলের স্টাইলও আলোচনায় এসেছে, বিশেষ নজর কেড়েছেন জান্নাতুল পিয়া।",
                        "body"             => "ফ্যাশন আলোচনায় এখন পোশাকের পাশাপাশি হেয়ারস্টাইলও বড় জায়গা করে নিচ্ছে। সাম্প্রতিক এক তারকা আয়োজনে মডেল ও অভিনেত্রী জান্নাতুল পিয়ার ছোট চুল বিশেষ নজর কাড়ে। সাদা ও নীল পোশাক, গয়না ও সাজের সঙ্গে তাঁর নতুন চুলের কাট সামগ্রিক লুকে আলাদা মাত্রা দেয়। ছোট চুল অনেকের কাছে আরামদায়ক, স্মার্ট এবং আধুনিক স্টাইল হিসেবে জনপ্রিয় হচ্ছে।",
                        'seo_keywords'     => "ছোট চুল, জান্নাতুল পিয়া, হেয়ারস্টাইল, তারকা ফ্যাশন, ফ্যাশন",
                        'published_at'     => "2026-05-02 00:00",
                    ],

                    (object) [
                        "category_name"    => "ফ্যাশন",
                        'name'             => "বৈশাখের রঙিন সাজে নতুন তাঁতের পোশাক",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পহেলা বৈশাখ ঘিরে দেশি ফ্যাশন ব্র্যান্ডগুলো তাঁত, সুতির কাপড় ও ঐতিহ্যনির্ভর ডিজাইনের পোশাক এনেছে।",
                        "body"             => "পহেলা বৈশাখকে কেন্দ্র করে দেশীয় ফ্যাশন বাজারে তাঁতের পোশাকের চাহিদা বেড়েছে। গ্রামীণ চেকসহ বিভিন্ন ফ্যাশন ব্র্যান্ড হালকা, আরামদায়ক ও রঙিন সুতির পোশাকের সংগ্রহ এনেছে। বৈশাখী পোশাকে লাল-সাদা রঙ, লোকঐতিহ্য, গ্রামীণ মোটিফ এবং ব্যবহারযোগ্য কাটকে গুরুত্ব দেওয়া হয়েছে। গরমের সময় উৎসব হওয়ায় আরামদায়ক কাপড় ও ঢিলেঢালা ডিজাইন ক্রেতাদের কাছে বেশি গ্রহণযোগ্য।",
                        'seo_keywords'     => "বৈশাখী পোশাক, তাঁত, গ্রামীণ চেক, দেশি ফ্যাশন, পহেলা বৈশাখ",
                        'published_at'     => "2026-04-13 00:00",
                    ],

                    (object) [
                        "category_name"    => "ফ্যাশন",
                        'name'             => "প্যান্ট ও ব্যাগের চেইনে YKK লেখা কেন থাকে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পোশাক ও ব্যাগের চেইনে দেখা YKK আসলে জাপানি একটি জিপার নির্মাতা প্রতিষ্ঠানের ব্র্যান্ড চিহ্ন।",
                        "body"             => "অনেক প্যান্ট, জ্যাকেট বা ব্যাগের চেইনে YKK লেখা দেখা যায়। এটি কোনো গোপন কোড নয়, বরং জাপানের জিপার নির্মাতা প্রতিষ্ঠান ইয়োশিদা কোগিও কাবুশিকিকাইশার ব্র্যান্ড চিহ্ন। ১৯৩৪ সালে তাদাও ইয়োশিদা প্রতিষ্ঠানটি শুরু করেন। বর্তমানে পোশাক, ব্যাগ, জুতা ও বিভিন্ন অ্যাকসেসরিজে তাদের তৈরি চেইন ব্যবহৃত হয়। পোশাকের ছোট অংশ হলেও চেইনের মান ব্যবহারযোগ্যতা ও স্থায়িত্বে গুরুত্বপূর্ণ ভূমিকা রাখে।",
                        'seo_keywords'     => "YKK, জিপার, পোশাক, ব্যাগ, ফ্যাশন",
                        'published_at'     => "2026-04-23 00:00",
                    ],

                    (object) [
                        "category_name"    => "ভ্রমণ",
                        'name'             => "ঢাকার কাছেই সবুজে ঘেরা জিন্দা পার্কে পরিবার নিয়ে ঘোরার সুযোগ",
                        'sub_heading'      => "শহরের কোলাহল থেকে স্বল্প দূরত্বের প্রকৃতি ভ্রমণ",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকার কাছাকাছি সবুজ পরিবেশ, গ্রামীণ আবহ ও পরিবারবান্ধব সুবিধার কারণে জিন্দা পার্ক জনপ্রিয় গন্তব্য হয়ে উঠছে।",
                        "body"             => "ঢাকার কাছাকাছি স্বল্প সময়ে ঘুরে আসার মতো জায়গা খুঁজলে জিন্দা পার্ক হতে পারে ভালো বিকল্প। সবুজে ঘেরা প্রাঙ্গণে আছে গ্রামীণ আবহ, খেলার মাঠ, মসজিদ, ঈদগাহ, রেস্তোরাঁ, রেস্টহাউস, লাইব্রেরি ও স্বাস্থ্যসেবা কেন্দ্র। পরিবার নিয়ে শান্ত পরিবেশে সময় কাটানোর জন্য জায়গাটি জনপ্রিয়। শহরের ব্যস্ততা থেকে সাময়িক বিরতি নিতে প্রকৃতির সান্নিধ্য, হাঁটাহাঁটি ও শিশুদের খেলাধুলার সুযোগ ভ্রমণটিকে আরামদায়ক করে।",
                        'seo_keywords'     => "জিন্দা পার্ক, ঢাকা ভ্রমণ, পরিবার ভ্রমণ, প্রকৃতি, ভ্রমণ",
                        'published_at'     => "2026-04-10 06:00",
                    ],

                    (object) [
                        "category_name"    => "ভ্রমণ",
                        'name'             => "পদ্মায় হাউসবোট ভ্রমণে বাড়ছে পর্যটকদের আগ্রহ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "মুন্সিগঞ্জ ও মাওয়া ঘাট ঘিরে পদ্মা নদীর হাউসবোট ভ্রমণ শহুরে পর্যটকদের জন্য নতুন অভিজ্ঞতা তৈরি করছে।",
                        "body"             => "পদ্মা নদীর বুকে হাউসবোট ভ্রমণ এখন ঢাকার কাছাকাছি জনপ্রিয় অবকাশের অভিজ্ঞতা হয়ে উঠছে। মুন্সিগঞ্জের লৌহজং, বেজগাঁও, শিমুলিয়া ঘাট ও দোহার এলাকার বিভিন্ন ঘাট থেকে হাউসবোট ছাড়ে। পদ্মা সেতু দেখা, নদীর বুকের চর, পাখি, সূর্যাস্ত ও ইলিশভোজ—সব মিলিয়ে দিনব্যাপী ভ্রমণটি পরিবার ও বন্ধুদের জন্য আকর্ষণীয়। সুবিধা অনুযায়ী প্যাকেজের খরচ ভিন্ন হয়।",
                        'seo_keywords'     => "পদ্মা হাউসবোট, মুন্সিগঞ্জ, মাওয়া, নদী ভ্রমণ, পর্যটন",
                        'published_at'     => "2026-03-15 04:58",
                    ],

                    (object) [
                        "category_name"    => "ভ্রমণ",
                        'name'             => "ঢাকা ট্রাভেল মার্টে অংশ নেয় ৫০টির বেশি প্রতিষ্ঠান",
                        'sub_heading'      => "",
                        'content_shoulder' => "পর্যটন মেলা",
                        'brief'            => "রাজধানীতে ঢাকা ট্রাভেল মার্ট ২০২৬ আয়োজনের মাধ্যমে ভ্রমণ, বিমান ও আতিথেয়তা খাতের নানা সেবা তুলে ধরা হয়।",
                        "body"             => "ঢাকা ট্রাভেল মার্ট ২০২৬ রাজধানীতে ভ্রমণপ্রেমীদের জন্য বড় আয়োজন হিসেবে অনুষ্ঠিত হয়েছে। আন্তর্জাতিক পর্যটন মেলায় ভ্রমণ, বিমান চলাচল, হোটেল, রিসোর্ট ও আতিথেয়তা খাতের ৫০টির বেশি প্রতিষ্ঠান অংশ নেয়। দেশি-বিদেশি পর্যটন প্যাকেজ, এয়ারলাইনস অফার, হোটেল সুবিধা ও ছুটির পরিকল্পনা নিয়ে দর্শনার্থীদের আগ্রহ দেখা যায়। পর্যটন খাতের পুনরুদ্ধার ও বাজার সম্প্রসারণে এ ধরনের মেলা গুরুত্বপূর্ণ ভূমিকা রাখে।",
                        'seo_keywords'     => "ঢাকা ট্রাভেল মার্ট, পর্যটন মেলা, ভ্রমণ, এয়ারলাইনস, বাংলাদেশ",
                        'published_at'     => "2026-04-08 00:00",
                    ],

                    (object) [
                        "category_name"    => "ভ্রমণ",
                        'name'             => "নববর্ষে হোটেল-রিসোর্টে বৈশাখী আয়োজন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পহেলা বৈশাখ উপলক্ষে ঢাকাসহ বিভিন্ন শহরের হোটেল ও রিসোর্টে বুফে, মেলা ও লোকজ আয়োজন রাখা হয়।",
                        "body"             => "বাংলা নববর্ষকে কেন্দ্র করে ঢাকাসহ দেশের বিভিন্ন হোটেল ও রিসোর্টে বৈশাখী আয়োজন করা হয়। ইন্টারকন্টিনেন্টাল ঢাকা, শেরাটন ঢাকা, ঢাকা রিজেন্সি, দ্য ওয়েস্টিন ঢাকা, গ্র্যান্ড সিলেট, বরিশাল ও চট্টগ্রামের বিভিন্ন হোটেলে বুফে, মেলা, পুতুলনাচ, বায়োস্কোপ, পিঠা, ফুচকা-চটপটি ও লোকজ বিনোদনের ব্যবস্থা ছিল। শহরের ভেতরেই উৎসবমুখর অবকাশ খুঁজছেন এমন পরিবারগুলোর জন্য এসব আয়োজন আকর্ষণ তৈরি করে।",
                        'seo_keywords'     => "বৈশাখী আয়োজন, হোটেল, রিসোর্ট, নববর্ষ, ভ্রমণ",
                        'published_at'     => "2026-04-13 00:00",
                    ],

                    (object) [
                        "category_name"    => "ভ্রমণ",
                        'name'             => "কক্সবাজারকে বিশ্বমানের পর্যটন গন্তব্য করার প্রত্যাশা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পরিকল্পিত উন্নয়ন, নিরাপদ পরিবেশ ও প্রাকৃতিক সৌন্দর্য রক্ষা করলে কক্সবাজার বিশ্বমানের পর্যটন গন্তব্য হতে পারে।",
                        "body"             => "কক্সবাজার বাংলাদেশের সবচেয়ে জনপ্রিয় পর্যটন গন্তব্যগুলোর একটি। পর্যটনসংশ্লিষ্ট আলোচনায় বলা হচ্ছে, দেশি-বিদেশি পর্যটকের জন্য নিরাপদ, পরিষ্কার ও সুশৃঙ্খল পরিবেশ তৈরি করা গেলে কক্সবাজার আরও বড় সম্ভাবনা তৈরি করতে পারে। নিজস্ব সংস্কৃতি, সমুদ্রসৈকত, খাবার, স্থানীয় পণ্য ও প্রাকৃতিক সৌন্দর্যকে পরিকল্পিতভাবে উপস্থাপন করা জরুরি। অবকাঠামো উন্নয়ন হলেও পরিবেশ রক্ষা ও স্থানীয় মানুষের অংশগ্রহণ নিশ্চিত করাও গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "কক্সবাজার, পর্যটন, সমুদ্রসৈকত, বাংলাদেশ ভ্রমণ, ট্যুরিজম",
                        'published_at'     => "2026-04-18 00:00",
                    ],

                    (object) [
                        "category_name"    => "খাবার",
                        'name'             => "পান্তা খাওয়ার নানা স্বাদে বৈশাখী খাবারের স্মৃতি",
                        'sub_heading'      => "ইলিশ, কচুর মুখি ও শুকনা মরিচে পান্তার ভিন্ন স্বাদ",
                        'content_shoulder' => "",
                        'brief'            => "পান্তা ভাতের সঙ্গে ইলিশের ঝোল, কচুর মুখি, শুকনা মরিচ ও ভর্তার মিশ্রণ বৈশাখী খাবারে নতুন স্বাদ যোগ করে।",
                        "body"             => "পান্তা ভাত বাংলা খাদ্যসংস্কৃতির পরিচিত খাবার। বৈশাখে পান্তার সঙ্গে ইলিশ, কচুর মুখি, শুকনা মরিচ, ভর্তা বা ডাল মিশিয়ে নানা স্বাদ তৈরি করা হয়। আগের রাতের রান্না করা ইলিশের ঝোল পান্তার সঙ্গে মেশালে স্বাদে আলাদা গভীরতা আসে। তবে পান্তা বানাতে নিরাপদ পানি, পরিষ্কার পাত্র এবং সঠিক সংরক্ষণ জরুরি। খাবারটি ঐতিহ্যের অংশ হলেও স্বাস্থ্যবিধি মানলে তা আরও নিরাপদ ও উপভোগ্য হয়।",
                        'seo_keywords'     => "পান্তা ভাত, বৈশাখী খাবার, ইলিশ, বাংলা খাবার, রেসিপি",
                        'published_at'     => "2026-04-13 00:00",
                    ],

                    (object) [
                        "category_name"    => "খাবার",
                        'name'             => "কাঁচা আমের শরবতে গরমে স্বস্তির স্বাদ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কাঁচা আম, পুদিনা, বিট লবণ, জিরা ও লেবুর রস দিয়ে তৈরি শরবত গরমে সতেজতা এনে দিতে পারে।",
                        "body"             => "গরমে কাঁচা আমের শরবত অনেকের পছন্দের পানীয়। কাঁচা আমের কুচি, চিনি, পুদিনা পাতা, বিট লবণ, জিরার গুঁড়া, কাঁচা মরিচ, লেবুর রস ও ঠান্ডা পানি দিয়ে সহজেই এটি তৈরি করা যায়। টক-মিষ্টি স্বাদ শরীরকে সতেজ অনুভূতি দেয়। তবে অতিরিক্ত চিনি ব্যবহার না করা, নিরাপদ পানি নেওয়া এবং বরফ পরিষ্কার কিনা নিশ্চিত করা জরুরি। ঘরে বানানো শরবত বাজারের মিষ্টি পানীয়ের তুলনায় বেশি নিয়ন্ত্রণযোগ্য।",
                        'seo_keywords'     => "কাঁচা আমের শরবত, গরমের পানীয়, রেসিপি, খাবার, পুদিনা",
                        'published_at'     => "2026-03-12 00:00",
                    ],

                    (object) [
                        "category_name"    => "খাবার",
                        'name'             => "টার্মারিক বুস্টার ঘরেই বানিয়ে পান করতে পারেন",
                        'sub_heading'      => "",
                        'content_shoulder' => "স্বাস্থ্যকর পানীয়",
                        'brief'            => "হলুদ, আদা, লেবু ও গোলমরিচের মিশ্রণে তৈরি টার্মারিক বুস্টার খাবার তালিকায় স্বাস্থ্যকর পানীয় হিসেবে যুক্ত হতে পারে।",
                        "body"             => "টার্মারিক বুস্টার এখন ঘরোয়া স্বাস্থ্যকর পানীয় হিসেবে জনপ্রিয় হচ্ছে। হলুদ, আদা, লেবু, গোলমরিচ, মধু ও কুসুম গরম পানি দিয়ে এটি তৈরি করা যায়। গোলমরিচ হলুদের কারকিউমিন শোষণে সহায়ক বলে ধারণা করা হয়। তবে পানীয়টি নিয়মিত চিকিৎসার বিকল্প নয় এবং অতিরিক্ত খেলে পেটের অস্বস্তি হতে পারে। স্বাস্থ্যকর জীবনযাপনের অংশ হিসেবে পরিমিতভাবে এমন পানীয় গ্রহণ করা যেতে পারে।",
                        'seo_keywords'     => "টার্মারিক বুস্টার, হলুদ পানীয়, খাবার, রেসিপি, স্বাস্থ্যকর",
                        'published_at'     => "2026-05-02 11:47",
                    ],

                    (object) [
                        "category_name"    => "খাবার",
                        'name'             => "ঈদ স্পেশাল আয়োজনে কাচ্চি, রেজালা ও বোরহানি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঈদে অতিথি আপ্যায়নে কাচ্চি বিরিয়ানি, খাসির রেজালা, মোরগ পোলাও ও বোরহানির মতো খাবার জনপ্রিয়।",
                        "body"             => "ঈদের খাবার আয়োজনে কাচ্চি বিরিয়ানি, খাসির মাংসের রেজালা, মোরগ পোলাও ও বোরহানি অনেক পরিবারের পছন্দের তালিকায় থাকে। বিশেষ দিনে ভারী খাবারের সঙ্গে বোরহানি হজমে সহায়ক পানীয় হিসেবে পরিবেশন করা হয়। দই, পুদিনা, সরিষা, বিট লবণ ও মসলার মিশ্রণে তৈরি বোরহানি কাচ্চি বা বিরিয়ানির সঙ্গে ভালো মানায়। তবে অতিরিক্ত তেল-মসলা ও মিষ্টি খাবার পরিমিত খাওয়া স্বাস্থ্যকর।",
                        'seo_keywords'     => "কাচ্চি বিরিয়ানি, রেজালা, বোরহানি, ঈদ খাবার, রেসিপি",
                        'published_at'     => "2026-03-21 00:00",
                    ],

                    (object) [
                        "category_name"    => "খাবার",
                        'name'             => "মিন্ট লেমন বানিয়ে ঘরে পান করুন সতেজ শরবত",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পুদিনা, লেবু, চিনি, লবণ ও ঠান্ডা পানি দিয়ে তৈরি মিন্ট লেমন গরমের দিনে সহজ ও সতেজ ঘরোয়া পানীয়।",
                        "body"             => "গরমে দ্রুত সতেজতা পেতে ঘরে মিন্ট লেমন বানানো যায়। পুদিনা পাতা ধুয়ে পানি ঝরিয়ে নিতে হবে, এরপর লেবুর রস, চিনি, লবণ, ঠান্ডা পানি ও পুদিনা একসঙ্গে ব্লেন্ড করে ছেঁকে পরিবেশন করা যায়। চাইলে বরফ যোগ করা যেতে পারে। পানীয়টি স্বাদে সতেজ হলেও চিনি পরিমিত ব্যবহার করা ভালো। বাইরে থেকে কেনা পানীয়ের বদলে ঘরে বানালে উপকরণ ও পরিচ্ছন্নতা নিজের নিয়ন্ত্রণে থাকে।",
                        'seo_keywords'     => "মিন্ট লেমন, লেবুর শরবত, গরমের পানীয়, রেসিপি, খাবার",
                        'published_at'     => "2026-03-01 00:00",
                    ],

                    (object) [
                        "category_name"    => "সম্পর্ক",
                        'name'             => "মানুষ জীবনে গড়ে দুইবার গভীর প্রেমে পড়ে",
                        'sub_heading'      => "গবেষণায় প্রেমের অভিজ্ঞতা নিয়ে নতুন আলোচনা",
                        'content_shoulder' => "",
                        'brief'            => "সম্পর্কবিষয়ক গবেষণায় বলা হয়েছে, মানুষ জীবনে গড়ে দুইবার গভীর প্রেমে পড়তে পারে, তবে অভিজ্ঞতা ব্যক্তি ভেদে ভিন্ন।",
                        "body"             => "প্রেম মানুষের জীবনের গভীর আবেগের অভিজ্ঞতা। সম্পর্কবিষয়ক এক গবেষণার আলোচনায় বলা হয়েছে, মানুষ জীবনে গড়ে দুইবার গভীর প্রেমে পড়ে। তবে এই সংখ্যা সবার জন্য এক নয়। ব্যক্তিত্ব, সামাজিক পরিবেশ, বয়স, সম্পর্কের অভিজ্ঞতা ও মানসিক প্রস্তুতির ওপর প্রেমের গভীরতা নির্ভর করে। কারও জীবনে একাধিকবার গভীর ভালোবাসা আসে, আবার কেউ খুব কম অভিজ্ঞতা পান। তাই প্রেমকে সংখ্যার চেয়ে অনুভূতি, সম্মান ও দায়িত্ব দিয়ে বোঝা বেশি গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "প্রেম, সম্পর্ক, ভালোবাসা, গবেষণা, জীবনধারা",
                        'published_at'     => "2026-04-12 00:00",
                    ],

                    (object) [
                        "category_name"    => "সম্পর্ক",
                        'name'             => "সংসারের অশান্তি কমাতে আলোচনায় ৫-৫-৫ ফর্মুলা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "দাম্পত্য সম্পর্কে ছোট ছোট ভালোবাসার প্রকাশ, সময় দেওয়া ও যোগাযোগ বাড়ানোর জন্য ৫-৫-৫ ফর্মুলা আলোচিত হচ্ছে।",
                        "body"             => "দাম্পত্য সম্পর্কে দৈনন্দিন ব্যস্ততা, ভুল বোঝাবুঝি ও মানসিক দূরত্ব অশান্তি বাড়াতে পারে। ৫-৫-৫ ফর্মুলায় প্রতিদিন ছোট ছোট ভালোবাসার ইশারা, কিছু সময় মনোযোগ দিয়ে কথা বলা এবং সঙ্গীর অনুভূতি বোঝার চেষ্টা করার কথা বলা হয়। আলিঙ্গন, হাত ধরা, পাশে বসা বা আন্তরিকভাবে খোঁজ নেওয়ার মতো ছোট আচরণ সম্পর্ককে উষ্ণ রাখে। সম্পর্ক টিকিয়ে রাখতে নিয়মিত যোগাযোগ, সম্মান ও ধৈর্য সবচেয়ে গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "দাম্পত্য, সম্পর্ক, ভালোবাসা, ৫-৫-৫ ফর্মুলা, যোগাযোগ",
                        'published_at'     => "2026-04-18 00:00",
                    ],

                    (object) [
                        "category_name"    => "সম্পর্ক",
                        'name'             => "যৌথ পরিবারে দাম্পত্য শান্তির জন্য দরকার স্পষ্ট যোগাযোগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "পারিবারিক বোঝাপড়া",
                        'brief'            => "যৌথ পরিবারে স্বামী-স্ত্রীর মধ্যে স্পষ্ট যোগাযোগ, সীমারেখা ও পারস্পরিক সম্মান বজায় রাখলে অশান্তি কমে।",
                        "body"             => "যৌথ পরিবারে একসঙ্গে থাকা অনেক সময় আনন্দের হলেও ছোটখাটো বিষয়ে ভুল বোঝাবুঝি তৈরি হতে পারে। সম্পর্ক বিশেষজ্ঞদের পরামর্শ হলো, স্বামী-স্ত্রীর মধ্যে সুস্পষ্ট যোগাযোগ থাকা জরুরি। পরিবারের অন্য সদস্যদের সম্মান করার পাশাপাশি নিজেদের ব্যক্তিগত সময়, সিদ্ধান্ত ও সীমারেখা নিয়ে স্পষ্ট বোঝাপড়া থাকা দরকার। প্রতিদিন কিছু সময় একান্ত কথা বলা, অভিযোগ জমিয়ে না রাখা এবং সমস্যাকে সম্মানের সঙ্গে সমাধান করার অভ্যাস দাম্পত্য শান্তি বাড়াতে পারে।",
                        'seo_keywords'     => "যৌথ পরিবার, দাম্পত্য, সম্পর্ক, পারিবারিক শান্তি, যোগাযোগ",
                        'published_at'     => "2026-03-20 00:00",
                    ],

                    (object) [
                        "category_name"    => "সম্পর্ক",
                        'name'             => "ঈদের ছুটি হতে পারে পরিবারের যত্ন নেওয়ার সময়",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উৎসবের ছুটি শুধু খাবার ও পোশাকের নয়, বরং পরিবারের সঙ্গে সময় কাটিয়ে সম্পর্ক নতুন করে গড়ার সুযোগ।",
                        "body"             => "ব্যস্ত জীবনে পরিবারকে সময় দেওয়া অনেকের পক্ষেই কঠিন হয়ে যায়। ঈদের মতো দীর্ঘ ছুটি তাই প্রিয়জনদের সঙ্গে সম্পর্ক ঘনিষ্ঠ করার ভালো সুযোগ। একসঙ্গে খাবার খাওয়া, গল্প করা, বয়োজ্যেষ্ঠদের খোঁজ নেওয়া, শিশুদের সময় দেওয়া এবং পরিবারের পুরোনো স্মৃতি ভাগ করে নেওয়া সম্পর্ককে উষ্ণ করে। উৎসবের আনন্দ তখনই পূর্ণ হয়, যখন কাছের মানুষদের প্রতি যত্ন ও মনোযোগ থাকে। ছোট সময়ও আন্তরিক হলে সম্পর্কের দূরত্ব কমাতে পারে।",
                        'seo_keywords'     => "ঈদের ছুটি, পরিবার, সম্পর্ক, প্রিয়জন, জীবনধারা",
                        'published_at'     => "2026-03-16 00:00",
                    ],

                    (object) [
                        "category_name"    => "সম্পর্ক",
                        'name'             => "শহুরে একাকীত্বে ফ্ল্যাটবাড়ির মানুষও বিচ্ছিন্ন হয়ে পড়ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শহরের আলো, কাজ ও প্রযুক্তির ভিড়ে অনেক মানুষ সামাজিকভাবে বিচ্ছিন্ন হয়ে পড়ছে, যা সম্পর্কের উষ্ণতা কমায়।",
                        "body"             => "শহুরে জীবনে মানুষ একই ভবনে থাকলেও অনেক সময় একে অপরের খবর রাখে না। ফ্ল্যাটবাড়ির ব্যস্ততা, কাজের চাপ, মোবাইলনির্ভর যোগাযোগ এবং ব্যক্তিগত গণ্ডির কারণে একাকীত্ব নীরবে বাড়ছে। এই একাকীত্ব সম্পর্কের ওপরও প্রভাব ফেলে। পরিবার, প্রতিবেশী ও বন্ধুদের সঙ্গে নিয়মিত যোগাযোগ, ছোট সামাজিক আড্ডা এবং সহানুভূতিশীল আচরণ শহুরে বিচ্ছিন্নতা কমাতে সাহায্য করতে পারে। সম্পর্ক শুধু অনলাইনে নয়, বাস্তব যত্নেও টিকে থাকে।",
                        'seo_keywords'     => "শহুরে একাকীত্ব, সম্পর্ক, ফ্ল্যাটবাড়ি, সামাজিক জীবন, মানসিক স্বাস্থ্য",
                        'published_at'     => "2026-03-01 00:00",
                    ],

                    (object) [
                        "category_name"    => "সংস্কৃতি",
                        'name'             => "গানে গানে শুরু হয় প্রথম আলো বৈশাখী উৎসব",
                        'sub_heading'      => "নাগরদোলা, গান ও লোকজ আয়োজনে নববর্ষের আবহ",
                        'content_shoulder' => "",
                        'brief'            => "বাংলা নববর্ষ ১৪৩৩ বরণে রাজধানীতে প্রথম আলো বৈশাখী উৎসব শুরু হয় জাতীয় সংগীত ও বৈশাখের গানের মধ্য দিয়ে।",
                        "body"             => "বাংলা নববর্ষ ১৪৩৩ উপলক্ষে রাজধানীর ইউনাইটেড সিটির মাদানী অ্যাভিনিউয়ে প্রথম আলো বৈশাখী উৎসব আয়োজন করা হয়। জাতীয় সংগীত ও বৈশাখের গান দিয়ে উৎসব শুরু হয়। শিশুদের অংশগ্রহণ, নাগরদোলা, লোকজ পরিবেশনা, মেলা ও সাংস্কৃতিক আয়োজনে উৎসবস্থল বাঙালির ঐতিহ্যের আবহে ভরে ওঠে। নগরজীবনের ভেতর এমন আয়োজন মানুষকে শিকড়, ভাষা, সংগীত ও উৎসবের সঙ্গে নতুন করে যুক্ত করে।",
                        'seo_keywords'     => "প্রথম আলো বৈশাখী উৎসব, বাংলা নববর্ষ, সংস্কৃতি, পহেলা বৈশাখ, লোকজ উৎসব",
                        'published_at'     => "2026-04-14 09:00",
                    ],

                    (object) [
                        "category_name"    => "সংস্কৃতি",
                        'name'             => "রমনার বটমূলে সুরের মূর্ছনায় নতুন বছর বরণ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ছায়ানটের ঐতিহ্যবাহী প্রভাতি আয়োজনে রমনার বটমূলে গান ও সাংস্কৃতিক পরিবেশনায় বাংলা নববর্ষকে বরণ করা হয়।",
                        "body"             => "রমনার বটমূলে ছায়ানটের প্রভাতি আয়োজন বাংলা নববর্ষ উদযাপনের অন্যতম প্রধান সাংস্কৃতিক অনুষঙ্গ। ১৯৬৭ সালে শুরু হওয়া এই অনুষ্ঠান বাঙালির সাংস্কৃতিক পরিচয়কে সংগীতের মাধ্যমে তুলে ধরার ঐতিহ্য বহন করে। এবারও গান, আবৃত্তি ও সম্মিলিত পরিবেশনায় নতুন বছরকে বরণ করা হয়। সব ধর্ম ও সম্প্রদায়ের মানুষের মিলনমেলা হিসেবে অনুষ্ঠানটি সংস্কৃতির অন্তর্ভুক্তিমূলক চরিত্রকে সামনে আনে।",
                        'seo_keywords'     => "রমনা বটমূল, ছায়ানট, বাংলা নববর্ষ, সংস্কৃতি, বৈশাখ",
                        'published_at'     => "2026-04-14 00:00",
                    ],

                    (object) [
                        "category_name"    => "সংস্কৃতি",
                        'name'             => "নববর্ষের শোভাযাত্রার নাম হলো বৈশাখী শোভাযাত্রা",
                        'sub_heading'      => "",
                        'content_shoulder' => "নামের পরিবর্তন",
                        'brief'            => "পহেলা বৈশাখে ঢাকা বিশ্ববিদ্যালয়ের চারুকলা থেকে বের হওয়া শোভাযাত্রার নাম বৈশাখী শোভাযাত্রা করা হয়েছে।",
                        "body"             => "পহেলা বৈশাখ উপলক্ষে ঢাকা বিশ্ববিদ্যালয়ের চারুকলা অনুষদ থেকে বের হওয়া শোভাযাত্রার নাম পরিবর্তন করে ‘বৈশাখী শোভাযাত্রা’ করা হয়েছে বলে সংস্কৃতিবিষয়ক মন্ত্রণালয় জানিয়েছে। বাংলা নববর্ষ ও ক্ষুদ্র নৃগোষ্ঠীর নববর্ষ উদযাপন নিয়ে আয়োজিত সংবাদ সম্মেলনে বিষয়টি জানানো হয়। শোভাযাত্রা দীর্ঘদিন ধরে শিল্প, লোকজ প্রতীক, রঙ, মুখোশ ও সামষ্টিক অংশগ্রহণের মাধ্যমে নববর্ষ উদযাপনের গুরুত্বপূর্ণ অংশ।",
                        'seo_keywords'     => "বৈশাখী শোভাযাত্রা, চারুকলা, পহেলা বৈশাখ, সংস্কৃতি, ঢাকা বিশ্ববিদ্যালয়",
                        'published_at'     => "2026-04-05 00:00",
                    ],

                    (object) [
                        "category_name"    => "সংস্কৃতি",
                        'name'             => "জাতীয়ভাবে বাংলা নববর্ষ উদ্‌যাপনে বিস্তৃত কর্মসূচি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলা নববর্ষ ১৪৩৩ উদ্‌যাপনে জেলা-উপজেলায় র‍্যালি, সাংস্কৃতিক অনুষ্ঠান, লোকজ মেলা ও বিশেষ আয়োজন রাখা হয়।",
                        "body"             => "বাংলা নববর্ষ ১৪৩৩ উদ্‌যাপনে জাতীয়ভাবে বিস্তৃত কর্মসূচি নেওয়া হয়। কর্মসূচির মধ্যে ছিল রাষ্ট্রীয় বাণী ও বিশেষ ক্রোড়পত্র, ঢাকা বিশ্ববিদ্যালয়ের চারুকলার শোভাযাত্রা, ছায়ানটসহ সাংস্কৃতিক সংগঠনের বর্ষবরণ, জেলা-উপজেলায় বৈশাখী র‍্যালি, সাংস্কৃতিক অনুষ্ঠান ও লোকজ মেলা। বাংলাদেশ শিল্পকলা একাডেমি, শিশু একাডেমি, শিক্ষাপ্রতিষ্ঠান, কারাগার ও হাসপাতালেও বিশেষ আয়োজনের কথা বলা হয়।",
                        'seo_keywords'     => "বাংলা নববর্ষ, জাতীয় কর্মসূচি, লোকজ মেলা, বৈশাখী র‍্যালি, সংস্কৃতি",
                        'published_at'     => "2026-03-30 00:00",
                    ],

                    (object) [
                        "category_name"    => "সংস্কৃতি",
                        'name'             => "শিল্পকলা একাডেমির পাঁচ দিনব্যাপী বৈশাখী উৎসব",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশ শিল্পকলা একাডেমির পাঁচ দিনব্যাপী বৈশাখী উৎসবকে কেন্দ্র করে সাংস্কৃতিক অঙ্গনে স্বতঃস্ফূর্ততা দেখা যায়।",
                        "body"             => "বাংলাদেশ শিল্পকলা একাডেমি এবার পাঁচ দিনব্যাপী বৈশাখী উৎসব আয়োজন করে। সংস্কৃতিবিষয়ক মন্ত্রণালয়ের আলোচনায় বলা হয়, পহেলা বৈশাখ ঘিরে নানা আশঙ্কা থাকলেও উৎসবটি স্বতঃস্ফূর্ত ও বর্ণাঢ্যভাবে পালিত হয়েছে। গান, নৃত্য, আবৃত্তি, লোকজ উপস্থাপনা ও বিভিন্ন জাতিগোষ্ঠীর সাংস্কৃতিক পরিবেশনা বাংলা নববর্ষকে আরও অন্তর্ভুক্তিমূলক করে। এ ধরনের আয়োজন দেশের সাংস্কৃতিক বৈচিত্র্যকে সামনে আনে।",
                        'seo_keywords'     => "শিল্পকলা একাডেমি, বৈশাখী উৎসব, বাংলা নববর্ষ, সংস্কৃতি, লোকজ পরিবেশনা",
                        'published_at'     => "2026-04-21 00:00",
                    ],

                    (object) [
                        "category_name"    => "বিজ্ঞান",
                        'name'             => "ব্যাটারির চার্জ শেষ হলে নিজেই বদলে নেয় রোবট",
                        'sub_heading'      => "রোবট প্রযুক্তিতে স্বয়ংসম্পূর্ণতার নতুন উদাহরণ",
                        'content_shoulder' => "",
                        'brief'            => "চীনের ইউবিটেক রোবটিক্সের তৈরি ওয়াকার এস২ রোবট ব্যাটারি কমে গেলে নিজেই পুরোনো ব্যাটারি খুলে নতুন ব্যাটারি লাগাতে পারে।",
                        "body"             => "রোবট প্রযুক্তিতে নতুন আলোচনার জন্ম দিয়েছে চীনের ইউবিটেক রোবটিক্সের ওয়াকার এস২। মানুষের মতো দেখতে এই রোবট ব্যাটারির চার্জ কমে গেলে নিজেই সিদ্ধান্ত নিয়ে রিপ্লেসমেন্ট স্টেশনে যায়। এরপর পুরোনো ব্যাটারি খুলে চার্জিংয়ে রেখে নতুন ব্যাটারি লাগিয়ে আবার কাজে ফিরে আসে। ডাবল ব্যাটারি সিস্টেম ব্যবহারের কারণে এটি দীর্ঘ সময় বিরতিহীনভাবে কাজ করতে পারে। বিশেষজ্ঞদের মতে, শিল্প, সেবা ও নিরাপত্তা খাতে এমন স্বয়ংসম্পূর্ণ রোবটের ব্যবহার ভবিষ্যতে দ্রুত বাড়তে পারে।",
                        'seo_keywords'     => "রোবট, ব্যাটারি, বিজ্ঞান, প্রযুক্তি, উদ্ভাবন",
                        'published_at'     => "2026-05-03 13:46",
                    ],

                    (object) [
                        "category_name"    => "বিজ্ঞান",
                        'name'             => "সিলিং ফ্যানে ক্যাপাসিটরের কাজ কী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সিলিং ফ্যান চালু করতে ও ঘূর্ণন স্থিতিশীল রাখতে ক্যাপাসিটর গুরুত্বপূর্ণ ভূমিকা রাখে, এটি নষ্ট হলে ফ্যান ধীরে ঘোরে।",
                        "body"             => "বৈদ্যুতিক যন্ত্রপাতির মধ্যে সিলিং ফ্যান খুব পরিচিত হলেও এর ভেতরের ছোট একটি অংশ ক্যাপাসিটর নিয়ে অনেকের কৌতূহল আছে। সিঙ্গেল ফেজ ইনডাকশন মোটর নিজে নিজে ঘুরতে শুরু করতে পারে না। ক্যাপাসিটর বিদ্যুতের ফেজ অ্যাঙ্গেল পরিবর্তন করে প্রাথমিক টর্ক তৈরি করে, ফলে ফ্যান ঘুরতে শুরু করে। ক্যাপাসিটর দুর্বল বা নষ্ট হলে ফ্যান ধীরে ঘোরে অথবা একেবারেই চালু হয় না। তাই ফ্যানের স্বাভাবিক গতি ও স্থায়িত্বে ক্যাপাসিটর অপরিহার্য।",
                        'seo_keywords'     => "ক্যাপাসিটর, সিলিং ফ্যান, বিদ্যুৎ, বিজ্ঞান, মোটর",
                        'published_at'     => "2026-04-30 12:35",
                    ],

                    (object) [
                        "category_name"    => "বিজ্ঞান",
                        'name'             => "মেগা এল নিনো নিয়ে বিজ্ঞানীদের সতর্কতা",
                        'sub_heading'      => "",
                        'content_shoulder' => "জলবায়ু সতর্কতা",
                        'brief'            => "২০২৬ থেকে ২০২৭ সালের মধ্যে শক্তিশালী এল নিনোর প্রভাবে খরা, তাপপ্রবাহ ও বৃষ্টির বৈপরীত্য বাড়তে পারে বলে আশঙ্কা।",
                        "body"             => "জলবায়ু পরিবর্তনের প্রেক্ষাপটে শক্তিশালী মেগা এল নিনো নিয়ে বিজ্ঞানীদের উদ্বেগ বাড়ছে। প্রশান্ত মহাসাগরের পৃষ্ঠতলের তাপমাত্রা স্বাভাবিকের চেয়ে অনেক বেশি হলে এর প্রভাব বিশ্বজুড়ে আবহাওয়ায় দেখা যায়। বাংলাদেশ ও দক্ষিণ এশিয়ায় তীব্র গরম, কালবৈশাখীর ঘাটতি, আর্দ্রতা বৃদ্ধি ও কৃষিতে চাপ তৈরি হতে পারে। অন্যদিকে বিশ্বের কিছু অঞ্চলে অতিবৃষ্টি ও বন্যার ঝুঁকিও বাড়ে। আবহাওয়া বিজ্ঞানীরা তাপপ্রবাহে দুপুরের রোদ এড়িয়ে চলা ও পর্যাপ্ত পানি পানের পরামর্শ দিচ্ছেন।",
                        'seo_keywords'     => "এল নিনো, জলবায়ু, তাপপ্রবাহ, বিজ্ঞান, বাংলাদেশ",
                        'published_at'     => "2026-04-24 14:22",
                    ],

                    (object) [
                        "category_name"    => "বিজ্ঞান",
                        'name'             => "স্যাটেলাইট ছবিতে সবুজ আভায় দেখা গেল বাংলাদেশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "রাশিয়ার আবহাওয়া স্যাটেলাইটে ধারণ করা ছবিতে বাংলাদেশসহ দক্ষিণ এশিয়ার অংশ রহস্যময় সবুজ আভায় দেখা গেছে।",
                        "body"             => "রাশিয়ার মহাকাশ গবেষণা সংস্থা রসকসমসের প্রকাশ করা এক স্যাটেলাইট ছবি মহাকাশপ্রেমীদের মধ্যে আলোচনার জন্ম দিয়েছে। ইলেকট্রো-এল আবহাওয়া স্যাটেলাইটের ওয়াইড-অ্যাঙ্গেল ছবিতে বাংলাদেশসহ দক্ষিণ এশিয়ার বিশাল অংশকে সবুজ আভায় দেখা যায়। ছবিটি পৃথিবীর বায়ুমণ্ডল, আলো প্রতিফলন, মেঘস্তর ও সেন্সরভিত্তিক রঙ উপস্থাপনার কারণে ভিন্নভাবে দেখা যেতে পারে। এ ধরনের ছবি আবহাওয়া পর্যবেক্ষণ, ভূপৃষ্ঠ বিশ্লেষণ ও মহাকাশভিত্তিক ডেটার গুরুত্বকে সাধারণ মানুষের সামনে নতুনভাবে তুলে ধরে।",
                        'seo_keywords'     => "স্যাটেলাইট ছবি, বাংলাদেশ, রসকসমস, মহাকাশ, বিজ্ঞান",
                        'published_at'     => "2026-04-28 00:00",
                    ],

                    (object) [
                        "category_name"    => "বিজ্ঞান",
                        'name'             => "জাতীয় বিজ্ঞান ও প্রযুক্তি সপ্তাহে উদ্ভাবনের বার্তা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উদ্ভাবননির্ভর বাংলাদেশ গঠনের প্রতিপাদ্যে বিভিন্ন জেলায় জাতীয় বিজ্ঞান ও প্রযুক্তি সপ্তাহ আয়োজন করা হচ্ছে।",
                        "body"             => "উদ্ভাবননির্ভর বাংলাদেশ গঠনে বিজ্ঞান ও প্রযুক্তির গুরুত্ব তুলে ধরতে ৪৭তম জাতীয় বিজ্ঞান ও প্রযুক্তি সপ্তাহ আয়োজন করা হচ্ছে। মাগুরাসহ বিভিন্ন জেলায় বিজ্ঞান মেলা, প্রকল্প প্রদর্শনী, কুইজ ও আলোচনার মাধ্যমে শিক্ষার্থীদের বিজ্ঞানচর্চায় উৎসাহ দেওয়া হচ্ছে। স্থানীয় পর্যায়ে তরুণদের উদ্ভাবনী ধারণা, প্রযুক্তি ব্যবহার ও সমস্যা সমাধানের দক্ষতা বাড়াতে এ ধরনের আয়োজন গুরুত্বপূর্ণ। বিজ্ঞানকে শুধু বইয়ের বিষয় হিসেবে নয়, বাস্তব জীবনের সমাধান তৈরির হাতিয়ার হিসেবে দেখানোর সুযোগ তৈরি হয়।",
                        'seo_keywords'     => "বিজ্ঞান সপ্তাহ, প্রযুক্তি, উদ্ভাবন, শিক্ষার্থী, বাংলাদেশ",
                        'published_at'     => "2026-04-16 00:00",
                    ],

                    (object) [
                        "category_name"    => "মহাকাশ",
                        'name'             => "ঢাকার বিজ্ঞান জাদুঘরে ইউরি গ্যাগারিনের আবক্ষ মূর্তি",
                        'sub_heading'      => "মানব মহাকাশযাত্রার ৬৫ বছর পূর্তিতে বিশেষ আয়োজন",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকার জাতীয় বিজ্ঞান ও প্রযুক্তি জাদুঘরে মহাকাশ অভিযাত্রার পথিকৃৎ ইউরি গ্যাগারিনের আবক্ষ মূর্তি উন্মোচন করা হয়েছে।",
                        "body"             => "মানবজাতির প্রথম মহাকাশযাত্রার ৬৫তম বার্ষিকী উপলক্ষে ঢাকার জাতীয় বিজ্ঞান ও প্রযুক্তি জাদুঘরে ইউরি গ্যাগারিনের আবক্ষ মূর্তি উন্মোচন করা হয়েছে। ১৯৬১ সালের ১২ এপ্রিল গ্যাগারিন প্রথম মানুষ হিসেবে মহাকাশে যান। ঢাকার এই আয়োজনের মাধ্যমে শিক্ষার্থী ও দর্শনার্থীদের সামনে মহাকাশ ইতিহাসকে নতুনভাবে তুলে ধরা হয়েছে। অনুষ্ঠানে মহাকাশবিষয়ক ছবি, স্বল্পদৈর্ঘ্য চলচ্চিত্র ও সাংস্কৃতিক পরিবেশনা ছিল, যা শিশু-কিশোরদের বিজ্ঞান ও মহাকাশ গবেষণায় আগ্রহী করতে পারে।",
                        'seo_keywords'     => "ইউরি গ্যাগারিন, মহাকাশ, বিজ্ঞান জাদুঘর, ঢাকা, রাশিয়া",
                        'published_at'     => "2026-04-13 00:00",
                    ],

                    (object) [
                        "category_name"    => "মহাকাশ",
                        'name'             => "মহাকাশ থেকে লিরিড উল্কাবৃষ্টির বিরল ছবি প্রকাশ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আন্তর্জাতিক মহাকাশ স্টেশন থেকে তোলা লিরিড উল্কাবৃষ্টির ছবি প্রকাশের পর মহাকাশপ্রেমীদের মধ্যে আগ্রহ তৈরি হয়েছে।",
                        "body"             => "লিরিড উল্কাবৃষ্টি পৃথিবীর আকাশে প্রতিবছরই মহাকাশপ্রেমীদের আকর্ষণ করে। এবার আন্তর্জাতিক মহাকাশ স্টেশন থেকে মহাকাশচারীরা এই উল্কাবৃষ্টির বিরল দৃশ্য ধারণ করেছেন। নাসা ছবিগুলো প্রকাশ করার পর সামাজিক মাধ্যমে আলোচনার সৃষ্টি হয়। পৃথিবীর বায়ুমণ্ডলে ধূলিকণা প্রবেশের সময় জ্বলে ওঠা আলোর রেখা আকাশে উল্কার মতো দেখা যায়। মহাকাশ স্টেশনের কাপোলা অবজারভেশন মডিউল থেকে তোলা ছবি মহাজাগতিক ঘটনাকে ভিন্ন দৃষ্টিকোণ থেকে দেখার সুযোগ দিয়েছে।",
                        'seo_keywords'     => "লিরিড, উল্কাবৃষ্টি, নাসা, মহাকাশ, আইএসএস",
                        'published_at'     => "2026-04-24 10:18",
                    ],

                    (object) [
                        "category_name"    => "মহাকাশ",
                        'name'             => "কালপুরুষ চিনতে আকাশ পর্যবেক্ষণের সহজ উপায়",
                        'sub_heading'      => "",
                        'content_shoulder' => "আকাশ দেখা",
                        'brief'            => "দক্ষিণ-পশ্চিম আকাশে তিনটি সরলরেখার নক্ষত্র দেখে কালপুরুষ নক্ষত্রমণ্ডল সহজে শনাক্ত করা যায়।",
                        "body"             => "রাতের আকাশে কালপুরুষ বা ওরিয়ন নক্ষত্রমণ্ডল সবচেয়ে পরিচিত দৃশ্যগুলোর একটি। এটি খুঁজতে হলে প্রথমে বেল্ট বা কোমরবন্ধনীর মতো একই সরলরেখায় থাকা তিনটি নক্ষত্র শনাক্ত করতে হয়। আলনিটাক, আলনিলাম ও মিনটাকা নামের এই তিন নক্ষত্র কালপুরুষের পরিচয় দেয়। সূর্যাস্তের এক বা দুই ঘণ্টা পর দক্ষিণ-পশ্চিম আকাশে এটি সবচেয়ে ভালো দেখা যায়। আকাশ পর্যবেক্ষণের সহজ কৌশল শিক্ষার্থীদের জ্যোতির্বিজ্ঞান শেখার আগ্রহ বাড়াতে পারে।",
                        'seo_keywords'     => "কালপুরুষ, নক্ষত্র, জ্যোতির্বিজ্ঞান, মহাকাশ, আকাশ",
                        'published_at'     => "2026-05-02 00:00",
                    ],

                    (object) [
                        "category_name"    => "মহাকাশ",
                        'name'             => "ধ্রুবতারা চিনে উত্তর দিক নির্ণয়ের পাঠ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উত্তর গোলার্ধের আকাশে পোলারিস বা ধ্রুবতারা স্থির অবস্থানের কারণে দিক নির্ণয়ে গুরুত্বপূর্ণ নক্ষত্র হিসেবে পরিচিত।",
                        "body"             => "ধ্রুবতারা বা পোলারিস উত্তর গোলার্ধের আকাশে দিক নির্ণয়ের জন্য বিশেষভাবে পরিচিত। এটি ভৌগোলিক উত্তর দিকের প্রায় সরাসরি ওপরে অবস্থান করে, তাই সারা বছর একই জায়গায় স্থির মনে হয়। অতীতে নাবিক ও ভ্রমণকারীরা দিক খুঁজতে ধ্রুবতারার সাহায্য নিতেন। আজও জ্যোতির্বিজ্ঞান শেখার প্রাথমিক ধাপে ধ্রুবতারা চেনা গুরুত্বপূর্ণ। শহরের আলোকদূষণ কম হলে উত্তর আকাশে এটি খুঁজে পাওয়া তুলনামূলক সহজ।",
                        'seo_keywords'     => "ধ্রুবতারা, পোলারিস, উত্তর দিক, মহাকাশ, জ্যোতির্বিজ্ঞান",
                        'published_at'     => "2026-04-26 00:00",
                    ],

                    (object) [
                        "category_name"    => "মহাকাশ",
                        'name'             => "আর্টেমিস-২ মিশন ঘিরে চাঁদে ফেরার প্রস্তুতি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "অর্ধশতাব্দীর বেশি সময় পর চাঁদের কাছাকাছি মানুষ পাঠানোর প্রস্তুতি হিসেবে নাসার আর্টেমিস-২ মিশন আলোচনায়।",
                        "body"             => "নাসার আর্টেমিস-২ মিশনকে চাঁদে মানুষের প্রত্যাবর্তনের গুরুত্বপূর্ণ ধাপ হিসেবে দেখা হচ্ছে। দীর্ঘ ৫০ বছরের বেশি সময় পর মানুষকে চাঁদের কাছাকাছি পাঠানোর প্রস্তুতি মহাকাশ গবেষণায় নতুন অধ্যায় তৈরি করছে। মিশনটি শুধু চাঁদে যাওয়ার প্রতীকী পদক্ষেপ নয়; ভবিষ্যতে চাঁদে স্থায়ী গবেষণা, মহাকাশ প্রযুক্তির পরীক্ষা এবং মঙ্গল অভিযানের প্রস্তুতির অংশ হিসেবেও গুরুত্বপূর্ণ। বাংলাদেশের বিজ্ঞানপ্রেমীদের মধ্যেও আন্তর্জাতিক মহাকাশ অভিযানের এসব খবর আগ্রহ তৈরি করছে।",
                        'seo_keywords'     => "আর্টেমিস-২, নাসা, চাঁদ, মহাকাশ অভিযান, বিজ্ঞান",
                        'published_at'     => "2026-04-02 00:00",
                    ],

                    (object) [
                        "category_name"    => "গবেষণা",
                        'name'             => "শাবিপ্রবির ১৮৬ শিক্ষার্থী পেল গবেষণা অনুদান",
                        'sub_heading'      => "শিক্ষার্থীদের গবেষণায় উৎসাহ দিতে প্রশাসনের উদ্যোগ",
                        'content_shoulder' => "",
                        'brief'            => "শাহজালাল বিজ্ঞান ও প্রযুক্তি বিশ্ববিদ্যালয়ের বিভিন্ন অনুষদের ১৮৬ শিক্ষার্থীকে গবেষণা অনুদান দিয়েছে কর্তৃপক্ষ।",
                        "body"             => "শাহজালাল বিজ্ঞান ও প্রযুক্তি বিশ্ববিদ্যালয়ের বিভিন্ন অনুষদের ১৮৬ শিক্ষার্থীকে গবেষণা অনুদান দেওয়া হয়েছে। সাধারণত শিক্ষক গবেষণায় অনুদান বেশি আলোচনায় এলেও শিক্ষার্থীদের সরাসরি সহায়তা তরুণ গবেষণা সংস্কৃতি গড়ে তুলতে গুরুত্বপূর্ণ। বিশ্ববিদ্যালয় পর্যায়ে গবেষণার অভ্যাস তৈরি হলে শিক্ষার্থীরা বাস্তব সমস্যা শনাক্ত, তথ্য সংগ্রহ, বিশ্লেষণ ও সমাধান প্রস্তাব করার দক্ষতা অর্জন করে। বিজ্ঞান, প্রকৌশল, জীববিজ্ঞান ও সামাজিক গবেষণায় এমন উদ্যোগ দীর্ঘমেয়াদে দেশের জ্ঞানভিত্তিক উন্নয়নে ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "শাবিপ্রবি, গবেষণা অনুদান, শিক্ষার্থী, গবেষণা, বিশ্ববিদ্যালয়",
                        'published_at'     => "2026-05-03 16:20",
                    ],

                    (object) [
                        "category_name"    => "গবেষণা",
                        'name'             => "উত্তরা ইউনিভার্সিটিতে ৪০টি গবেষণা প্রস্তাবনা উপস্থাপন",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উত্তরা ইউনিভার্সিটির সিআরটি প্রপোজাল ডিফেন্সে বিজ্ঞান, প্রকৌশল ও সামাজিক বিজ্ঞানের ৪০টি গবেষণা প্রস্তাবনা উপস্থাপন করা হয়েছে।",
                        "body"             => "উত্তরা ইউনিভার্সিটিতে সিআরটি প্রপোজাল ডিফেন্স ২০২৬ আয়োজন করা হয়েছে। এতে বিজ্ঞান, প্রকৌশল, সামাজিক বিজ্ঞান ও মানবিক অনুষদের বিভিন্ন বিষয়ের ওপর মোট ৪০টি গবেষণা প্রস্তাবনা উপস্থাপন করেন ৫৫ জন গবেষক। উচ্চশিক্ষা প্রতিষ্ঠানে গবেষণা প্রস্তাবনা উপস্থাপনের মাধ্যমে গবেষণার লক্ষ্য, পদ্ধতি, সম্ভাব্য ফলাফল এবং বাস্তব প্রয়োগ নিয়ে আলোচনা হয়। এতে গবেষকেরা বিশেষজ্ঞ মতামত পান এবং গবেষণার মান উন্নত করার সুযোগ তৈরি হয়।",
                        'seo_keywords'     => "উত্তরা ইউনিভার্সিটি, গবেষণা, প্রস্তাবনা, বিজ্ঞান, উচ্চশিক্ষা",
                        'published_at'     => "2026-04-24 00:00",
                    ],

                    (object) [
                        "category_name"    => "গবেষণা",
                        'name'             => "এআই গবেষণা ও স্টার্টআপে ড্যাফোডিলে হ্যাকাথন",
                        'sub_heading'      => "",
                        'content_shoulder' => "এআই গবেষণা",
                        'brief'            => "ড্যাফোডিল বিশ্ববিদ্যালয়ে এআই হ্যাকাথনের মাধ্যমে তরুণদের গবেষণা, উদ্ভাবন ও স্টার্টআপ ভাবনা উৎসাহিত করা হয়েছে।",
                        "body"             => "ড্যাফোডিল বিশ্ববিদ্যালয়ে এআই হ্যাকাথন ২০২৬ আয়োজনের মাধ্যমে তরুণদের কৃত্রিম বুদ্ধিমত্তাভিত্তিক গবেষণা ও স্টার্টআপ ভাবনায় উৎসাহিত করা হয়েছে। বিশ্ববিদ্যালয় কর্তৃপক্ষের মতে, এ ধরনের আয়োজন প্রযুক্তি ব্যবহারের দক্ষতার পাশাপাশি সমস্যা সমাধান ও উদ্ভাবক হিসেবে গড়ে ওঠার সুযোগ তৈরি করে। এআই এখন স্বাস্থ্য, শিক্ষা, কৃষি, ভাষা প্রযুক্তি ও ব্যবসায়িক বিশ্লেষণে গুরুত্বপূর্ণ হয়ে উঠছে। তাই শিক্ষার্থীদের হাতে-কলমে গবেষণা ও প্রোটোটাইপ তৈরির অভিজ্ঞতা ভবিষ্যৎ কর্মক্ষেত্রে সহায়ক হতে পারে।",
                        'seo_keywords'     => "এআই, হ্যাকাথন, গবেষণা, ড্যাফোডিল, স্টার্টআপ",
                        'published_at'     => "2026-04-15 00:00",
                    ],

                    (object) [
                        "category_name"    => "গবেষণা",
                        'name'             => "আইএইএর সঙ্গে বাংলাদেশের পরমাণু সহযোগিতা জোরদার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আন্তর্জাতিক পরমাণু শক্তি সংস্থার সঙ্গে বাংলাদেশের সহযোগিতা বিজ্ঞান, নিরাপত্তা ও গবেষণায় নতুন সুযোগ তৈরি করতে পারে।",
                        "body"             => "আন্তর্জাতিক পরমাণু শক্তি সংস্থা বা আইএইএর সঙ্গে বাংলাদেশের সহযোগিতা বিজ্ঞান ও প্রযুক্তি খাতে গুরুত্বপূর্ণ। পরমাণু প্রযুক্তির শান্তিপূর্ণ ব্যবহার, নিরাপত্তা, চিকিৎসা, কৃষি, শিল্প ও গবেষণায় আন্তর্জাতিক মানদণ্ড মেনে চলা জরুরি। বিজ্ঞান ও প্রযুক্তি মন্ত্রণালয়ের সংশ্লিষ্ট উদ্যোগ বাংলাদেশে পরমাণু প্রযুক্তির সক্ষমতা, প্রশিক্ষণ ও গবেষণার পরিসর বাড়াতে ভূমিকা রাখতে পারে। নিরাপত্তা ও স্বচ্ছতা বজায় রেখে এসব সহযোগিতা দীর্ঘমেয়াদে বিজ্ঞানভিত্তিক উন্নয়নে সহায়ক হতে পারে।",
                        'seo_keywords'     => "আইএইএ, পরমাণু, গবেষণা, বিজ্ঞান, বাংলাদেশ",
                        'published_at'     => "2026-03-21 00:00",
                    ],

                    (object) [
                        "category_name"    => "গবেষণা",
                        'name'             => "মরুভূমিকে উর্বর মাটিতে রূপান্তরের গবেষণা আলোচনায়",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সায়ানোব্যাকটেরিয়া ব্যবহার করে মরুভূমির বালুকে স্থিতিশীল মাটিতে রূপান্তরের গবেষণা জলবায়ু অভিযোজনে সম্ভাবনা দেখাচ্ছে।",
                        "body"             => "সায়ানোব্যাকটেরিয়া ব্যবহার করে মরুভূমির বালুকে দ্রুত উর্বর মাটির মতো স্থিতিশীল স্তরে রূপান্তরের গবেষণা বিজ্ঞানীদের আগ্রহের কেন্দ্রবিন্দুতে এসেছে। গবেষকেরা একধরনের জৈব মৃত্তিকা স্তর তৈরি করেছেন, যা বালুর কণাগুলোকে আঠালো পদার্থের মাধ্যমে আটকে রাখে। প্রাকৃতিকভাবে মাটি তৈরি হতে দীর্ঘ সময় লাগে, কিন্তু এ পদ্ধতিতে সময় অনেক কমতে পারে। জলবায়ু পরিবর্তন, মরুকরণ ও খাদ্যনিরাপত্তা সংকট মোকাবিলায় এ ধরনের গবেষণা ভবিষ্যতে গুরুত্বপূর্ণ হতে পারে।",
                        'seo_keywords'     => "সায়ানোব্যাকটেরিয়া, মরুভূমি, গবেষণা, মাটি, বিজ্ঞান",
                        'published_at'     => "2026-04-21 00:00",
                    ],

                    (object) [
                        "category_name"    => "উদ্ভাবন",
                        'name'             => "পানি দিয়ে চলবে গ্রিন হাইড্রোজেন স্টোভ",
                        'sub_heading'      => "গ্যাসের বিকল্প রান্নার প্রযুক্তি নিয়ে নতুন আলোচনা",
                        'content_shoulder' => "",
                        'brief'            => "পানি থেকে গ্রিন হাইড্রোজেন তৈরি করে রান্নার চুলা চালানোর প্রযুক্তি পরিবেশবান্ধব বিকল্প হিসেবে আলোচনায় এসেছে।",
                        "body"             => "গ্রিন হাইড্রোজেন স্টোভ নিয়ে প্রযুক্তি দুনিয়ায় নতুন আলোচনা চলছে। ধারণাটি হলো, বিশুদ্ধ পানি থেকে ইলেকট্রোলাইজার প্রযুক্তির মাধ্যমে হাইড্রোজেন তৈরি করে সেটিকে জ্বালানি হিসেবে ব্যবহার করা। এতে ধোঁয়া বা কার্বন-ডাই-অক্সাইড দূষণ কম হতে পারে। প্রাথমিকভাবে প্রযুক্তিটি ব্যয়বহুল হলেও ভবিষ্যতে উৎপাদন বাড়লে দাম কমার সম্ভাবনা রয়েছে। গ্যাসের দাম, সরবরাহ সংকট ও পরিবেশগত চাপে রান্নার বিকল্প জ্বালানি হিসেবে এমন উদ্ভাবন গুরুত্বপূর্ণ হতে পারে।",
                        'seo_keywords'     => "গ্রিন হাইড্রোজেন, চুলা, উদ্ভাবন, জ্বালানি, বিজ্ঞান",
                        'published_at'     => "2026-04-17 09:55",
                    ],

                    (object) [
                        "category_name"    => "উদ্ভাবন",
                        'name'             => "বিশ্বের প্রথম কোয়ান্টাম ব্যাটারির নমুনা তৈরি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কোয়ান্টাম ব্যাটারির প্রাথমিক নমুনা ভবিষ্যতে বৈদ্যুতিক গাড়ি ও স্মার্টফোন চার্জিংয়ে বড় পরিবর্তনের সম্ভাবনা দেখাচ্ছে।",
                        "body"             => "কোয়ান্টাম ব্যাটারির প্রাথমিক নমুনা তৈরি নিয়ে বিজ্ঞান দুনিয়ায় আগ্রহ তৈরি হয়েছে। গবেষকদের লক্ষ্য হলো এমন প্রযুক্তি তৈরি করা, যা প্রচলিত ব্যাটারির তুলনায় দ্রুত ও ভিন্ন পদ্ধতিতে শক্তি সংরক্ষণ ও সরবরাহ করতে পারে। ভবিষ্যতে বৈদ্যুতিক গাড়ি বা স্মার্টফোন চার্জ দেওয়ার জন্য প্লাগের প্রয়োজন কমে যেতে পারে বলে ধারণা করা হচ্ছে। লেজার বা তড়িৎ-চুম্বকীয় বিকিরণের মাধ্যমে দূর থেকে চার্জিংয়ের সম্ভাবনাও আলোচনায় আছে। যদিও প্রযুক্তিটি এখনো প্রাথমিক পর্যায়ে, তবু এটি শক্তি প্রযুক্তির ভবিষ্যতে বড় ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "কোয়ান্টাম ব্যাটারি, উদ্ভাবন, শক্তি, চার্জিং, বিজ্ঞান",
                        'published_at'     => "2026-04-12 00:00",
                    ],

                    (object) [
                        "category_name"    => "উদ্ভাবন",
                        'name'             => "ভাইটাল আইডি পাসওয়ার্ডের বদলে খুলির কম্পন ব্যবহার করবে",
                        'sub_heading'      => "",
                        'content_shoulder' => "নতুন লগইন",
                        'brief'            => "বিজ্ঞানীদের উদ্ভাবিত ভাইটাল আইডি প্রযুক্তি মাথার খুলির কম্পন ব্যবহার করে নিরাপদ লগইনের নতুন ধারণা দিচ্ছে।",
                        "body"             => "পাসওয়ার্ড, আঙুলের ছাপ বা মুখ শনাক্তের বাইরে নতুন লগইন পদ্ধতি হিসেবে ভাইটাল আইডি আলোচনায় এসেছে। বিজ্ঞানীরা এমন প্রযুক্তির কথা বলছেন, যেখানে মাথার খুলির অনন্য কম্পন ব্যবহার করে পরিচয় যাচাই করা যাবে। প্রতিটি মানুষের শারীরিক গঠন আলাদা হওয়ায় এই কম্পনও ভিন্ন হতে পারে। তবে বাস্তব ব্যবহারের আগে নিরাপত্তা, গোপনীয়তা, নির্ভুলতা ও ব্যবহারযোগ্যতা নিয়ে আরও পরীক্ষা জরুরি। ডিজিটাল নিরাপত্তায় বায়োমেট্রিক প্রযুক্তির নতুন দিগন্ত হিসেবে এটি গুরুত্ব পেতে পারে।",
                        'seo_keywords'     => "ভাইটাল আইডি, পাসওয়ার্ড, বায়োমেট্রিক, উদ্ভাবন, নিরাপত্তা",
                        'published_at'     => "2026-04-05 05:00",
                    ],

                    (object) [
                        "category_name"    => "উদ্ভাবন",
                        'name'             => "ইনকিউবেশন হাব চালু হলো রাজশাহী বিশ্ববিদ্যালয়ে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "রাজশাহী বিশ্ববিদ্যালয়ে জেনেটিক ইঞ্জিনিয়ারিং ও বায়োটেকনোলজি বিভাগের উদ্যোগে ইনকিউবেশন হাব চালু হয়েছে।",
                        "body"             => "রাজশাহী বিশ্ববিদ্যালয়ে প্রথমবারের মতো ইনকিউবেশন হাব চালু হয়েছে। জেনেটিক ইঞ্জিনিয়ারিং অ্যান্ড বায়োটেকনোলজি বিভাগের মলিকুলার বায়োলজি অ্যান্ড প্রোটিন সায়েন্স ল্যাবরেটরির উদ্যোগে এই হাব প্রতিষ্ঠা করা হয়েছে। বিশ্ববিদ্যালয়ভিত্তিক ইনকিউবেশন হাব শিক্ষার্থীদের গবেষণা ধারণাকে প্রোটোটাইপ, পণ্য বা স্টার্টআপে রূপ দিতে সহায়তা করতে পারে। বিজ্ঞানভিত্তিক উদ্যোক্তা গড়ে তুলতে ল্যাব সুবিধা, পরামর্শ, দল গঠন ও বাজার সংযোগ জরুরি।",
                        'seo_keywords'     => "ইনকিউবেশন হাব, রাজশাহী বিশ্ববিদ্যালয়, বায়োটেকনোলজি, উদ্ভাবন, গবেষণা",
                        'published_at'     => "2026-03-00 00:00",
                    ],

                    (object) [
                        "category_name"    => "উদ্ভাবন",
                        'name'             => "দৃষ্টিপ্রতিবন্ধীদের জন্য স্মার্ট গ্লাস নিয়ে বিশ্বমঞ্চে শাবিপ্রবি দল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "এআইভিত্তিক স্মার্ট গ্লাস উদ্ভাবন নিয়ে শাবিপ্রবির শিক্ষার্থী দল আন্তর্জাতিক হ্যাকাথনে অংশ নেওয়ার সুযোগ পেয়েছে।",
                        "body"             => "দৃষ্টিপ্রতিবন্ধী মানুষের চলাচল ও দৈনন্দিন কাজ সহজ করতে এআইভিত্তিক স্মার্ট গ্লাস নিয়ে কাজ করছে শাহজালাল বিজ্ঞান ও প্রযুক্তি বিশ্ববিদ্যালয়ের একদল শিক্ষার্থী। তাদের দল ‘ভিশন এআই’ উজবেকিস্তানের এআই ইন হেলথ কেয়ার হ্যাকাথন ২০২৬-এ অংশ নেওয়ার জন্য মনোনীত হয়েছে। স্মার্ট গ্লাসে কৃত্রিম বুদ্ধিমত্তা ব্যবহার করে আশপাশের বস্তু, পথ বা সম্ভাব্য বাধা শনাক্তের ধারণা থাকলে তা সহায়ক প্রযুক্তিতে বড় পরিবর্তন আনতে পারে।",
                        'seo_keywords'     => "স্মার্ট গ্লাস, শাবিপ্রবি, এআই, উদ্ভাবন, দৃষ্টিপ্রতিবন্ধী",
                        'published_at'     => "2026-03-00 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি গবেষণা",
                        'name'             => "স্মার্টফোনের দাম ২৫০০ টাকায় আনতে চায় সরকার",
                        'sub_heading'      => "ডিজিটাল অন্তর্ভুক্তি বাড়াতে কমদামি ডিভাইসের পরিকল্পনা",
                        'content_shoulder' => "",
                        'brief'            => "সরকার স্মার্টফোনের দাম আড়াই হাজার থেকে তিন হাজার টাকার মধ্যে আনতে চায় বলে জানিয়েছেন সংশ্লিষ্ট উপদেষ্টা।",
                        "body"             => "ডিজিটাল সেবা সবার হাতে পৌঁছে দিতে কমদামি স্মার্টফোনের পরিকল্পনা আলোচনায় এসেছে। ডাক, টেলিযোগাযোগ ও তথ্যপ্রযুক্তি মন্ত্রণালয় এবং বিজ্ঞান ও প্রযুক্তি মন্ত্রণালয় বিষয়ক উপদেষ্টা জানিয়েছেন, সরকার স্মার্টফোনের দাম আড়াই হাজার থেকে তিন হাজার টাকার মধ্যে আনতে চায়। শিক্ষা, স্বাস্থ্য, কৃষি, ব্যাংকিং ও সরকারি সেবায় স্মার্টফোন এখন গুরুত্বপূর্ণ মাধ্যম। স্থানীয় উৎপাদন, কর কাঠামো ও প্রযুক্তি গবেষণার সহায়তা থাকলে কমদামি ডিভাইস ডিজিটাল অন্তর্ভুক্তি বাড়াতে পারে।",
                        'seo_keywords'     => "স্মার্টফোন, প্রযুক্তি গবেষণা, ডিজিটাল সেবা, সরকার, বাংলাদেশ",
                        'published_at'     => "2026-04-25 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি গবেষণা",
                        'name'             => "ঢাবির ১০ বিভাগ কিউএস র‍্যাঙ্কিংয়ে বিশ্বসেরার তালিকায়",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কিউএস সাবজেক্ট র‍্যাঙ্কিংয়ে ঢাকা বিশ্ববিদ্যালয়ের ১০টি বিভাগ জায়গা পেয়েছে, প্রযুক্তি ও প্রকৌশল ক্ষেত্রও তালিকায় আছে।",
                        "body"             => "কিউএস র‍্যাঙ্কিংয়ে ঢাকা বিশ্ববিদ্যালয়ের ১০টি বিভাগ বিশ্বসেরার তালিকায় স্থান পেয়েছে। ইঞ্জিনিয়ারিং অ্যান্ড টেকনোলজি ক্যাটাগরিতে বিশ্ববিদ্যালয়ের অবস্থান উল্লেখযোগ্য। কম্পিউটার সায়েন্স, ইলেকট্রিক্যাল অ্যান্ড ইলেকট্রনিক ইঞ্জিনিয়ারিং এবং মেকানিক্যাল সংশ্লিষ্ট বিষয়ও তালিকায় রয়েছে। আন্তর্জাতিক র‍্যাঙ্কিং গবেষণা, একাডেমিক সুনাম, উদ্ধৃতি ও বৈশ্বিক দৃশ্যমানতার সঙ্গে যুক্ত। ফলে প্রযুক্তি গবেষণায় বিশ্ববিদ্যালয়ের শক্তি ও দুর্বলতা চিহ্নিত করার সুযোগ তৈরি হয়।",
                        'seo_keywords'     => "ঢাকা বিশ্ববিদ্যালয়, কিউএস র‍্যাঙ্কিং, প্রযুক্তি গবেষণা, প্রকৌশল, উচ্চশিক্ষা",
                        'published_at'     => "2026-03-27 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি গবেষণা",
                        'name'             => "রূপপুর পারমাণবিক বিদ্যুৎকেন্দ্রের অগ্রগতি আলোচনায়",
                        'sub_heading'      => "",
                        'content_shoulder' => "পারমাণবিক প্রযুক্তি",
                        'brief'            => "রূপপুর পারমাণবিক বিদ্যুৎকেন্দ্র বাংলাদেশের প্রযুক্তি গবেষণা, প্রকৌশল দক্ষতা ও নিরাপত্তা সক্ষমতার বড় প্রকল্প হিসেবে বিবেচিত।",
                        "body"             => "রূপপুর পারমাণবিক বিদ্যুৎকেন্দ্র বাংলাদেশের বিজ্ঞান ও প্রযুক্তি খাতের সবচেয়ে বড় প্রকল্পগুলোর একটি। এই প্রকল্পে পারমাণবিক প্রযুক্তি, প্রকৌশল, নিরাপত্তা সংস্কৃতি, প্রশিক্ষণ ও আন্তর্জাতিক সহযোগিতা একসঙ্গে যুক্ত। বিদ্যুৎ উৎপাদনের পাশাপাশি এটি স্থানীয় প্রকৌশলী, বিজ্ঞানী ও প্রযুক্তিবিদদের দক্ষতা বাড়ানোর সুযোগ তৈরি করছে। তবে পারমাণবিক প্রযুক্তিতে নিরাপত্তা, বর্জ্য ব্যবস্থাপনা, রক্ষণাবেক্ষণ ও জনসচেতনতা অত্যন্ত গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "রূপপুর, পারমাণবিক বিদ্যুৎ, প্রযুক্তি গবেষণা, বিজ্ঞান, বাংলাদেশ",
                        'published_at'     => "2026-04-24 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি গবেষণা",
                        'name'             => "বিজ্ঞান ও প্রযুক্তি মন্ত্রণালয়ে নতুন গবেষণা প্রস্তাবনার প্রস্তুতি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "২০২৬-২৭ অর্থবছরের গবেষণা ও উন্নয়ন প্রস্তাবনা নিয়ে বিজ্ঞান ও প্রযুক্তি খাতের প্রতিষ্ঠানগুলো প্রস্তুতি নিচ্ছে।",
                        "body"             => "২০২৬-২৭ অর্থবছরের গবেষণা ও উন্নয়ন প্রস্তাবনা উপস্থাপন নিয়ে বিজ্ঞান ও প্রযুক্তি সংশ্লিষ্ট প্রতিষ্ঠানগুলো প্রস্তুতি নিচ্ছে। গবেষণা প্রস্তাবনার মাধ্যমে নতুন প্রযুক্তি, শিল্পপ্রয়োগ, স্থানীয় সমস্যা সমাধান এবং বৈজ্ঞানিক সক্ষমতা বৃদ্ধির পরিকল্পনা নির্ধারিত হয়। সরকারি গবেষণা প্রতিষ্ঠানে সঠিক পরিকল্পনা, বাজেট, মূল্যায়ন ও ফলাফলভিত্তিক পর্যবেক্ষণ থাকলে গবেষণার বাস্তব প্রভাব বাড়ে। কৃষি, স্বাস্থ্য, জ্বালানি, পরিবেশ ও শিল্প খাতে গবেষণা উন্নয়নের সুযোগ তৈরি করতে পারে।",
                        'seo_keywords'     => "গবেষণা উন্নয়ন, আরঅ্যান্ডডি, প্রযুক্তি গবেষণা, বিজ্ঞান মন্ত্রণালয়, বাংলাদেশ",
                        'published_at'     => "2026-04-00 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রযুক্তি গবেষণা",
                        'name'             => "ন্যানো-ডোওপ অ্যাপে উপকূলের জীববৈচিত্র্যের তথ্য",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উপকূলের জীববৈচিত্র্য ও পরিবেশগত তথ্য সংগ্রহে মোবাইল অ্যাপ ব্যবহারের উদ্যোগ গবেষণা ও পর্যবেক্ষণে সহায়ক হতে পারে।",
                        "body"             => "উপকূলীয় জীববৈচিত্র্যের তথ্য সংগ্রহ ও ব্যবস্থাপনায় মোবাইল অ্যাপ ব্যবহারের উদ্যোগ প্রযুক্তি গবেষণার নতুন প্রয়োগ দেখাচ্ছে। ন্যানো-ডোওপ সিফোরসিইএম অ্যাপ ব্যবহার করে বাংলা ও ইংরেজি দুই ভাষায় তথ্য সংগ্রহের সুযোগ রয়েছে। উপকূলীয় পরিবেশ, প্রাণবৈচিত্র্য ও জলবায়ু পরিবর্তনের প্রভাব বুঝতে নিয়মিত ডেটা অত্যন্ত গুরুত্বপূর্ণ। মাঠপর্যায়ের তথ্য ডিজিটালভাবে সংরক্ষণ করলে গবেষক, প্রশাসন ও পরিবেশকর্মীরা দ্রুত সিদ্ধান্ত নিতে পারেন।",
                        'seo_keywords'     => "মোবাইল অ্যাপ, জীববৈচিত্র্য, উপকূল, প্রযুক্তি গবেষণা, ডেটা",
                        'published_at'     => "2026-04-18 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৈজ্ঞানিক আবিষ্কার",
                        'name'             => "৬ হাজারের বেশি এক্সোপ্ল্যানেট নিশ্চিত করেছেন বিজ্ঞানীরা",
                        'sub_heading'      => "সৌরজগতের বাইরে প্রাণের সম্ভাবনা নিয়ে নতুন আগ্রহ",
                        'content_shoulder' => "",
                        'brief'            => "নাসার বিভিন্ন অভিযানে সৌরজগতের বাইরে ৬ হাজারের বেশি এক্সোপ্ল্যানেটের অস্তিত্ব নিশ্চিত হওয়ায় প্রাণের অনুসন্ধান জোরদার হয়েছে।",
                        "body"             => "মহাবিশ্বে আমরা একা কি না—এই প্রশ্ন বিজ্ঞানীদের দীর্ঘদিনের অনুসন্ধানের কেন্দ্র। নাসার বিভিন্ন মহাকাশ অভিযানের মাধ্যমে সৌরজগতের বাইরে ৬ হাজারের বেশি এক্সোপ্ল্যানেটের অস্তিত্ব নিশ্চিত হয়েছে। আরও অনেক গ্রহ যাচাইয়ের অপেক্ষায় আছে। এসব গ্রহের মধ্যে কিছু পৃথিবীর মতো আকারের এবং তাদের নক্ষত্রের বাসযোগ্য অঞ্চলে অবস্থান করতে পারে, যেখানে তরল পানির সম্ভাবনা থাকে। প্রাণের সরাসরি প্রমাণ এখনো পাওয়া যায়নি, তবে এক্সোপ্ল্যানেট আবিষ্কার মহাকাশ গবেষণায় নতুন দিগন্ত খুলেছে।",
                        'seo_keywords'     => "এক্সোপ্ল্যানেট, নাসা, মহাবিশ্ব, আবিষ্কার, মহাকাশ",
                        'published_at'     => "2026-04-27 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৈজ্ঞানিক আবিষ্কার",
                        'name'             => "মাথার খুলির কম্পনে পরিচয় শনাক্তের নতুন পদ্ধতি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ভাইটাল আইডি প্রযুক্তিতে মাথার খুলির অনন্য কম্পন ব্যবহার করে পরিচয় যাচাইয়ের সম্ভাবনা দেখছেন গবেষকেরা।",
                        "body"             => "ডিজিটাল পরিচয় সুরক্ষায় বিজ্ঞানীরা নতুন পদ্ধতি হিসেবে মাথার খুলির কম্পন ব্যবহার করার ধারণা দিয়েছেন। ভাইটাল আইডি নামে পরিচিত এই প্রযুক্তিতে প্রত্যেক মানুষের শারীরিক গঠন থেকে সৃষ্ট অনন্য কম্পন শনাক্ত করা হয়। পাসওয়ার্ড চুরি, মুখের ছবি নকল বা আঙুলের ছাপ জালিয়াতির ঝুঁকি কমাতে এমন পদ্ধতি ভবিষ্যতে কাজে লাগতে পারে। তবে বাস্তব ব্যবহারের আগে এর নির্ভুলতা, ডেটা নিরাপত্তা ও ব্যবহারকারীর গোপনীয়তা নিয়ে আরও গবেষণা প্রয়োজন।",
                        'seo_keywords'     => "বৈজ্ঞানিক আবিষ্কার, ভাইটাল আইডি, পরিচয় শনাক্ত, বায়োমেট্রিক, নিরাপত্তা",
                        'published_at'     => "2026-04-05 05:00",
                    ],

                    (object) [
                        "category_name"    => "বৈজ্ঞানিক আবিষ্কার",
                        'name'             => "সায়ানোব্যাকটেরিয়ায় মরুভূমির বালু স্থিতিশীল করার পদ্ধতি",
                        'sub_heading'      => "",
                        'content_shoulder' => "মাটি তৈরির পথ",
                        'brief'            => "সায়ানোব্যাকটেরিয়া দিয়ে বালুর কণা আটকে জৈব মৃত্তিকা স্তর তৈরির পদ্ধতি মরুকরণ ঠেকাতে সম্ভাবনাময় আবিষ্কার।",
                        "body"             => "গবেষকেরা সায়ানোব্যাকটেরিয়া ব্যবহার করে মরুভূমির বালুর ওপর জৈব মৃত্তিকা স্তর তৈরির পদ্ধতি দেখিয়েছেন। এই অণুজীব সালোকসংশ্লেষণ করে এবং চিনিভিত্তিক আঠালো পদার্থ নিঃসরণ করে, যা বালুর কণাকে একসঙ্গে ধরে রাখতে সাহায্য করে। এর ফলে বাতাসে উড়ে যাওয়া বালু স্থিতিশীল স্তরে পরিণত হতে পারে। মরুকরণ, ভূমিক্ষয় ও খাদ্য উৎপাদনের সংকট মোকাবিলায় এ ধরনের বৈজ্ঞানিক আবিষ্কার ভবিষ্যতে কার্যকর ভূমিকা রাখতে পারে।",
                        'seo_keywords'     => "সায়ানোব্যাকটেরিয়া, বৈজ্ঞানিক আবিষ্কার, মরুভূমি, মাটি, পরিবেশ",
                        'published_at'     => "2026-04-21 00:00",
                    ],

                    (object) [
                        "category_name"    => "বৈজ্ঞানিক আবিষ্কার",
                        'name'             => "নিঃশব্দ সুপারসনিক জেট এক্স-৫৯ পরীক্ষায় সফল",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নাসার এক্স-৫৯ সুপারসনিক জেট প্রথম পরীক্ষামূলক উড্ডয়নে সফল হয়ে শব্দদূষণ কমানোর সম্ভাবনা দেখিয়েছে।",
                        "body"             => "নাসা ও লকহিড মার্টিনের তৈরি পরীক্ষামূলক সুপারসনিক জেট এক্স-৫৯ প্রথম উড্ডয়নে সফল হয়েছে। শব্দের গতিবেগ অতিক্রম করলে সাধারণত প্রচণ্ড সনিক বুম তৈরি হয়, যা মানুষের জন্য বিরক্তিকর ও ঝুঁকিপূর্ণ হতে পারে। এক্স-৫৯ এমনভাবে নকশা করা হয়েছে, যাতে সাউন্ড ব্যারিয়ার ভাঙলেও শব্দের ধাক্কা অনেক কম থাকে। ভবিষ্যতে এই প্রযুক্তি সফল হলে দ্রুতগতির যাত্রীবাহী উড়োজাহাজের নতুন সম্ভাবনা তৈরি হতে পারে।",
                        'seo_keywords'     => "এক্স-৫৯, নাসা, সুপারসনিক, বৈজ্ঞানিক আবিষ্কার, উড়োজাহাজ",
                        'published_at'     => "2026-03-07 17:30",
                    ],

                    (object) [
                        "category_name"    => "বৈজ্ঞানিক আবিষ্কার",
                        'name'             => "কোয়ান্টাম ব্যাটারি শক্তি সংরক্ষণে নতুন দিগন্ত দেখাচ্ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কোয়ান্টাম নীতিভিত্তিক ব্যাটারি প্রযুক্তি ভবিষ্যতে দ্রুত চার্জিং ও শক্তি সংরক্ষণে বড় পরিবর্তন আনতে পারে।",
                        "body"             => "কোয়ান্টাম ব্যাটারির নমুনা শক্তি সংরক্ষণ প্রযুক্তিতে নতুন দিগন্ত দেখাচ্ছে। প্রচলিত ব্যাটারি রাসায়নিক বিক্রিয়ার ওপর নির্ভর করলেও কোয়ান্টাম ব্যাটারি ক্ষুদ্র কণার বিশেষ ভৌত আচরণ কাজে লাগানোর ধারণা নিয়ে এগোচ্ছে। গবেষকেরা আশা করছেন, ভবিষ্যতে দ্রুত চার্জিং, দূর থেকে শক্তি সরবরাহ এবং উচ্চ দক্ষতার বিদ্যুৎ ব্যবস্থায় এ প্রযুক্তি ভূমিকা রাখতে পারে। যদিও এটি এখনো গবেষণার প্রাথমিক পর্যায়ে, তবু শক্তি সংকট ও বৈদ্যুতিক যানবাহনের যুগে আবিষ্কারটি গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "কোয়ান্টাম ব্যাটারি, শক্তি, বৈজ্ঞানিক আবিষ্কার, চার্জিং, প্রযুক্তি",
                        'published_at'     => "2026-04-12 00:00",
                    ],

                    (object) [
                        "category_name"    => "পরিবেশ",
                        'name'             => "টানা বৃষ্টির পরও ঢাকার বাতাস অস্বাস্থ্যকর",
                        'sub_heading'      => "বৃষ্টির পরও কমছে না রাজধানীর বায়ুদূষণ",
                        'content_shoulder' => "",
                        'brief'            => "টানা বৃষ্টির পরও ঢাকার বাতাসের মান অস্বাস্থ্যকর পর্যায়ে থাকায় নগরবাসীর স্বাস্থ্যঝুঁকি নিয়ে নতুন উদ্বেগ তৈরি হয়েছে।",
                        "body"             => "রাজধানীতে কয়েক দিন ধরে থেমে থেমে বৃষ্টি হলেও বায়ুদূষণ উল্লেখযোগ্যভাবে কমেনি। সকালে বিশ্বের দূষিত নগরীর তালিকায় ঢাকার অবস্থান ছিল ওপরের দিকে এবং বাতাসের মান অস্বাস্থ্যকর হিসেবে চিহ্নিত হয়। বিশেষজ্ঞরা বলছেন, নির্মাণকাজের ধুলা, যানবাহনের ধোঁয়া, শিল্পকারখানার নির্গমন ও বর্জ্য পোড়ানো বন্ধ না হলে শুধু বৃষ্টির ওপর নির্ভর করে দূষণ কমানো সম্ভব নয়। শিশু, বৃদ্ধ ও শ্বাসকষ্টের রোগীদের মাস্ক ব্যবহার ও বাইরে কম থাকার পরামর্শ দেওয়া হয়েছে।",
                        'seo_keywords'     => "ঢাকা, বায়ুদূষণ, পরিবেশ, বৃষ্টি, স্বাস্থ্যঝুঁকি",
                        'published_at'     => "2026-05-03 03:18",
                    ],

                    (object) [
                        "category_name"    => "পরিবেশ",
                        'name'             => "সপ্তাহজুড়ে রোদ-বৃষ্টির পূর্বাভাস",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "আবহাওয়া অধিদপ্তর জানিয়েছে, চলতি সপ্তাহে দেশের বিভিন্ন স্থানে কখনো রোদ, কখনো বৃষ্টি থাকতে পারে।",
                        "body"             => "টানা তাপপ্রবাহের পর দেশের বিভিন্ন এলাকায় বৃষ্টির প্রবণতা দেখা যাচ্ছে। আবহাওয়া অধিদপ্তরের পূর্বাভাস অনুযায়ী, চলতি সপ্তাহে দেশের বিভিন্ন স্থানে বিচ্ছিন্নভাবে বৃষ্টি হতে পারে, তবে একটানা ভারী বৃষ্টির সম্ভাবনা কম। দক্ষিণ-পূর্বাঞ্চল ও সিলেট অঞ্চলে বৃষ্টি তুলনামূলক বেশি হতে পারে। অন্যদিকে রাজশাহী ও রংপুরে বৃষ্টির পরিমাণ কম থাকতে পারে। বৃষ্টির কারণে অন্তত চলতি সপ্তাহে তাপপ্রবাহের চাপ কিছুটা কম থাকতে পারে।",
                        'seo_keywords'     => "আবহাওয়া, বৃষ্টি, পরিবেশ, তাপপ্রবাহ, বাংলাদেশ",
                        'published_at'     => "2026-05-03 06:30",
                    ],

                    (object) [
                        "category_name"    => "পরিবেশ",
                        'name'             => "মে মাসে কালবৈশাখী ও লঘুচাপের সম্ভাবনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "মৌসুমি সতর্কতা",
                        'brief'            => "মে মাসে এক থেকে দুটি লঘুচাপ, কয়েকটি কালবৈশাখী এবং মৃদু থেকে মাঝারি তাপপ্রবাহের পূর্বাভাস দেওয়া হয়েছে।",
                        "body"             => "আবহাওয়া অধিদপ্তরের মে মাসের পূর্বাভাসে বলা হয়েছে, এ মাসে স্বাভাবিক বৃষ্টির সম্ভাবনা আছে। বঙ্গোপসাগরে এক থেকে দুটি লঘুচাপ তৈরি হতে পারে এবং সেগুলোর একটি নিম্নচাপ বা দুর্বল ঘূর্ণিঝড়ে রূপ নিতে পারে। তবে ঘূর্ণিঝড়ের সম্ভাবনা তুলনামূলক কম। এ মাসে ৫ থেকে ৬টি কালবৈশাখী হতে পারে, যার মধ্যে কয়েকটি তীব্র হতে পারে। একই সঙ্গে মৃদু থেকে মাঝারি তাপপ্রবাহের সম্ভাবনাও রয়েছে।",
                        'seo_keywords'     => "কালবৈশাখী, লঘুচাপ, আবহাওয়া, পরিবেশ, মে মাস",
                        'published_at'     => "2026-05-03 10:15",
                    ],

                    (object) [
                        "category_name"    => "পরিবেশ",
                        'name'             => "ভোলায় কালবৈশাখী ঝড়ে অর্ধশতাধিক ঘর ক্ষতিগ্রস্ত",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ভোলার ওপর দিয়ে বয়ে যাওয়া আকস্মিক কালবৈশাখী ঝড়ে অর্ধশতাধিক ঘর বিধ্বস্ত ও বহু পরিবার দুর্ভোগে পড়েছে।",
                        "body"             => "ভোলার বিভিন্ন এলাকায় আকস্মিক কালবৈশাখী ঝড়ে অর্ধশতাধিক ঘর ক্ষতিগ্রস্ত হয়েছে। ঝড়ে গাছ উপড়ে পড়ে সড়ক যোগাযোগ বিঘ্নিত হয় এবং অনেক পরিবার খোলা আকাশের নিচে আশ্রয় নিতে বাধ্য হয়। স্থানীয় প্রশাসন ক্ষয়ক্ষতির তথ্য সংগ্রহ করছে। উপকূলীয় জেলাগুলোতে ঝড়ের মৌসুমে দুর্বল ঘরবাড়ি, গাছপালা ও বিদ্যুৎ সংযোগ বেশি ঝুঁকিতে থাকে। তাই আগাম সতর্কতা, নিরাপদ আশ্রয় ও দ্রুত সহায়তা ব্যবস্থাকে জরুরি হিসেবে দেখা হচ্ছে।",
                        'seo_keywords'     => "ভোলা, কালবৈশাখী, ঝড়, পরিবেশ, দুর্যোগ",
                        'published_at'     => "2026-05-02 19:10",
                    ],

                    (object) [
                        "category_name"    => "পরিবেশ",
                        'name'             => "জাতীয় বৃক্ষরোপণ অভিযান ও বৃক্ষমেলার প্রস্তুতি",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জাতীয় বৃক্ষরোপণ অভিযান ও বৃক্ষমেলা ২০২৬ সামনে রেখে স্টল বরাদ্দের আবেদন আহ্বান করেছে বন অধিদপ্তর।",
                        "body"             => "জাতীয় বৃক্ষরোপণ অভিযান ও বৃক্ষমেলা ২০২৬ উপলক্ষে বন অধিদপ্তর স্টল বরাদ্দের আবেদন আহ্বান করেছে। পরিবেশ সংরক্ষণ, নগর সবুজায়ন, ফলদ ও বনজ গাছের চারা বিতরণ এবং জনসচেতনতা বাড়াতে বৃক্ষমেলা গুরুত্বপূর্ণ ভূমিকা রাখে। জলবায়ু পরিবর্তন, তাপদাহ ও নগর দূষণের প্রেক্ষাপটে বৃক্ষরোপণ এখন শুধু সৌন্দর্য বাড়ানোর কাজ নয়; এটি তাপমাত্রা নিয়ন্ত্রণ, কার্বন শোষণ ও জীববৈচিত্র্য রক্ষার কার্যকর উপায়।",
                        'seo_keywords'     => "বৃক্ষরোপণ, বৃক্ষমেলা, বন অধিদপ্তর, পরিবেশ, সবুজায়ন",
                        'published_at'     => "2026-04-30 00:00",
                    ],

                    (object) [
                        "category_name"    => "জলবায়ু পরিবর্তন",
                        'name'             => "বাংলাদেশে শীতকাল ছোট হয়ে আসছে",
                        'sub_heading'      => "গবেষণায় ভবিষ্যৎ জলবায়ুর উদ্বেগজনক চিত্র",
                        'content_shoulder' => "",
                        'brief'            => "বাংলাদেশ আবহাওয়া অধিদপ্তর ও নরওয়ের গবেষকদের প্রতিবেদনে শীতের ব্যাপ্তি কমে যাওয়ার প্রবণতা উঠে এসেছে।",
                        "body"             => "বাংলাদেশের ভবিষ্যৎ জলবায়ু নিয়ে সাম্প্রতিক গবেষণায় দেখা গেছে, শীতকালের ব্যাপ্তি ধীরে ধীরে কমছে এবং ভবিষ্যতে এ প্রবণতা আরও বাড়তে পারে। প্রতিবেদনে ২০৪১ থেকে ২০৭০ এবং ২০৭১ থেকে ২১০০ সময়পর্ব ধরে তাপমাত্রা বৃদ্ধি, শীত কমে যাওয়া ও অঞ্চলভিত্তিক ঝুঁকির বিশ্লেষণ করা হয়েছে। গবেষকেরা বলছেন, কার্বন নিঃসরণ নিয়ন্ত্রণ না করা গেলে শীত ঋতু আরও সংকুচিত হতে পারে, যার প্রভাব কৃষি, স্বাস্থ্য ও নগরজীবনে পড়বে।",
                        'seo_keywords'     => "জলবায়ু পরিবর্তন, শীতকাল, তাপমাত্রা, গবেষণা, বাংলাদেশ",
                        'published_at'     => "2026-01-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "জলবায়ু পরিবর্তন",
                        'name'             => "রাতের তাপমাত্রা দ্রুত বাড়া স্বাস্থ্যঝুঁকি বাড়াচ্ছে",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "গবেষণা বলছে, দিনের তুলনায় রাতের তাপমাত্রা দ্রুত বাড়লে ঘুম, হৃদ্‌রোগ ও শ্বাসকষ্টের ঝুঁকি বাড়তে পারে।",
                        "body"             => "জলবায়ু গবেষণায় রাতের তাপমাত্রা বৃদ্ধিকে বড় স্বাস্থ্যঝুঁকি হিসেবে দেখা হচ্ছে। রাতে গরম থাকলে শরীর পর্যাপ্তভাবে ঠান্ডা হতে পারে না, ফলে ঘুমের ব্যাঘাত, ক্লান্তি, হৃদ্‌রোগ ও শ্বাসকষ্টের ঝুঁকি বাড়ে। বাংলাদেশের মতো উচ্চ আর্দ্রতা ও ঘনবসতিপূর্ণ দেশে এই ঝুঁকি আরও বেশি। বিশেষ করে শহরে কংক্রিট, যানবাহন, কম সবুজ এলাকা ও জলাশয় ভরাটের কারণে তাপ আটকে থাকে, যা নগরবাসীর দৈনন্দিন জীবনকে প্রভাবিত করে।",
                        'seo_keywords'     => "রাতের তাপমাত্রা, জলবায়ু পরিবর্তন, স্বাস্থ্যঝুঁকি, ঢাকা, তাপ",
                        'published_at'     => "2026-01-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "জলবায়ু পরিবর্তন",
                        'name'             => "তাপপ্রবাহের ঝুঁকিতে রংপুর, রাজশাহী ও খুলনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "তাপঝুঁকির মানচিত্র",
                        'brief'            => "আবহাওয়া গবেষণায় রংপুর, রাজশাহী ও খুলনাকে ভবিষ্যৎ তাপপ্রবাহের বেশি ঝুঁকিপূর্ণ বিভাগ হিসেবে দেখা হচ্ছে।",
                        "body"             => "বাংলাদেশের ভবিষ্যৎ জলবায়ু বিশ্লেষণে দেখা যাচ্ছে, তাপপ্রবাহ শুধু পশ্চিমাঞ্চলে সীমিত থাকবে না; বিভিন্ন অঞ্চলে এর বিস্তার বাড়তে পারে। তবে রংপুর, রাজশাহী ও খুলনা বিভাগকে তুলনামূলক বেশি ঝুঁকিপূর্ণ হিসেবে বিবেচনা করা হয়েছে। উচ্চ নিঃসরণ পরিস্থিতিতে তাপপ্রবাহের স্থায়িত্ব ও ঘনত্ব বাড়তে পারে। নগর পরিকল্পনা, সবুজ এলাকা বৃদ্ধি, জলাশয় রক্ষা এবং তাপ-সহনশীল অবকাঠামো তৈরি এখন জরুরি হয়ে উঠছে।",
                        'seo_keywords'     => "তাপপ্রবাহ, জলবায়ু পরিবর্তন, রংপুর, রাজশাহী, খুলনা",
                        'published_at'     => "2026-01-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "জলবায়ু পরিবর্তন",
                        'name'             => "তাপমাত্রা বৃদ্ধিতে কৃষি ও মৎস্য খাতে ক্ষতির আশঙ্কা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "অতিরিক্ত গরমে ফসল, মাছ, পানির প্রাপ্যতা ও রোগবালাইয়ের ওপর চাপ বাড়তে পারে বলে গবেষকেরা সতর্ক করেছেন।",
                        "body"             => "জলবায়ু পরিবর্তনে তাপমাত্রা বৃদ্ধি বাংলাদেশের কৃষি ও মৎস্য খাতে বড় চাপ তৈরি করতে পারে। গবেষণায় বলা হয়েছে, অতিরিক্ত গরমে ফসলের বৃদ্ধি ব্যাহত হয়, পানি সংকট বাড়ে, পোকামাকড় ও রোগের প্রকোপ বৃদ্ধি পায় এবং মাছ উৎপাদনেও প্রভাব পড়ে। গমসহ কিছু রবিশস্যের জন্য নির্দিষ্ট সময় শীতল আবহাওয়া প্রয়োজন, যা কমে গেলে ফলন ক্ষতিগ্রস্ত হতে পারে। তাই তাপসহনশীল জাত, পানি ব্যবস্থাপনা ও কৃষি অভিযোজন কৌশল জরুরি।",
                        'seo_keywords'     => "কৃষি, মৎস্য, জলবায়ু পরিবর্তন, তাপমাত্রা, খাদ্যনিরাপত্তা",
                        'published_at'     => "2026-01-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "জলবায়ু পরিবর্তন",
                        'name'             => "ঋতুচক্র অস্বাভাবিক হচ্ছে, বাড়ছে নগর দুর্যোগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "অপরিকল্পিত নগরায়ণ, জলাশয় ভরাট ও জলবায়ু পরিবর্তনের প্রভাবে নগরে জলাবদ্ধতা ও দুর্যোগ ঝুঁকি বাড়ছে।",
                        "body"             => "জলবায়ু পরিবর্তনের প্রভাবে ঋতুচক্র অস্বাভাবিক হয়ে ওঠার পাশাপাশি অপরিকল্পিত নগরায়ণও নতুন দুর্যোগ তৈরি করছে। খাল-নালা দখল, পুকুর ও জলাশয় ভরাট, অপর্যাপ্ত ড্রেনেজ এবং অতিবৃষ্টির কারণে শহরে জলাবদ্ধতা দ্রুত বাড়ে। নগর বিশেষজ্ঞরা বলছেন, জলবায়ু পরিবর্তনের সঙ্গে খাপ খাইয়ে নিতে হলে শুধু আবহাওয়া পূর্বাভাস নয়, বরং নগর পরিকল্পনা, সবুজ এলাকা, পানি নিষ্কাশন ও জলাশয় রক্ষাকে অগ্রাধিকার দিতে হবে।",
                        'seo_keywords'     => "ঋতুচক্র, জলাবদ্ধতা, জলবায়ু পরিবর্তন, নগর দুর্যোগ, চট্টগ্রাম",
                        'published_at'     => "2026-04-05 00:00",
                    ],

                    (object) [
                        "category_name"    => "দূষণ",
                        'name'             => "ঢাকার বায়ুমান ১৫৫, অস্বাস্থ্যকর অবস্থায় রাজধানী",
                        'sub_heading'      => "আইকিউএয়ারের সূচকে দূষণের উচ্চ ঝুঁকি",
                        'content_shoulder' => "",
                        'brief'            => "রোববার সকালে ঢাকার বায়ুমান ১৫৫ রেকর্ড হওয়ায় বাতাসকে অস্বাস্থ্যকর হিসেবে চিহ্নিত করা হয়েছে।",
                        "body"             => "আইকিউএয়ারের তথ্য অনুযায়ী, রোববার সকালে ঢাকার বায়ুমান ১৫৫ ছিল, যা অস্বাস্থ্যকর পর্যায়ে পড়ে। বৃষ্টির পরও রাজধানীর বাতাসে দূষণ কমে না আসায় নাগরিকদের মধ্যে উদ্বেগ তৈরি হয়েছে। শহরের বিভিন্ন এলাকায় ধুলা, নির্মাণকাজ, যানবাহনের ধোঁয়া ও বর্জ্য পোড়ানো দূষণের বড় কারণ হিসেবে দেখা হয়। শ্বাসকষ্ট, অ্যালার্জি, হাঁপানি ও হৃদ্‌রোগের ঝুঁকি কমাতে সংবেদনশীল মানুষকে বাইরে কম থাকা, মাস্ক ব্যবহার ও জানালা বন্ধ রাখার পরামর্শ দেওয়া হয়।",
                        'seo_keywords'     => "বায়ুমান, ঢাকা দূষণ, আইকিউএয়ার, দূষণ, স্বাস্থ্য",
                        'published_at'     => "2026-05-03 03:18",
                    ],

                    (object) [
                        "category_name"    => "দূষণ",
                        'name'             => "উত্তর বাড্ডায় বায়ুমান খুব অস্বাস্থ্যকর",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকার উত্তর বাড্ডার আবদুল্লাহবাগ এলাকায় বায়ুমান ২০২ রেকর্ড হওয়ায় এলাকাটি খুব অস্বাস্থ্যকর পর্যায়ে ছিল।",
                        "body"             => "রাজধানীর বিভিন্ন এলাকার মধ্যে উত্তর বাড্ডার আবদুল্লাহবাগে বায়ুদূষণ সবচেয়ে বেশি ছিল। ওই এলাকার বায়ুমান ২০২ রেকর্ড হয়, যা খুব অস্বাস্থ্যকর হিসেবে ধরা হয়। বেচারাম দেউড়ী, ধানমন্ডি, গুলশান ও পল্লবীর কিছু এলাকাতেও বাতাসের মান খারাপ ছিল। শহরের ভেতরে এলাকায় এলাকায় দূষণের এমন পার্থক্য স্থানীয় নির্মাণকাজ, যানজট, রাস্তার ধুলা ও সবুজ এলাকার ঘাটতির সঙ্গে যুক্ত হতে পারে।",
                        'seo_keywords'     => "উত্তর বাড্ডা, আবদুল্লাহবাগ, বায়ুদূষণ, ঢাকা, দূষণ",
                        'published_at'     => "2026-05-03 03:18",
                    ],

                    (object) [
                        "category_name"    => "দূষণ",
                        'name'             => "নির্মাণসামগ্রী দিয়ে বায়ুদূষণে মোবাইল কোর্ট",
                        'sub_heading'      => "",
                        'content_shoulder' => "আইনি অভিযান",
                        'brief'            => "পরিবেশ অধিদপ্তর নির্মাণসামগ্রী থেকে বায়ুদূষণের দায়ে শেরপুরে মোবাইল কোর্ট পরিচালনা করে জরিমানা করেছে।",
                        "body"             => "পরিবেশ অধিদপ্তরের মার্চের কার্যক্রমে নির্মাণসামগ্রী দ্বারা বায়ুদূষণের বিরুদ্ধে মোবাইল কোর্ট পরিচালনার তথ্য প্রকাশ করা হয়েছে। বায়ুদূষণ নিয়ন্ত্রণ বিধিমালা অনুযায়ী নির্মাণসামগ্রী খোলা অবস্থায় রাখা, ধুলা উড়ানো বা সঠিক ব্যবস্থাপনা না করলে জরিমানার বিধান আছে। শহর ও জেলা পর্যায়ে নির্মাণকাজের ধুলা বায়ুদূষণের বড় উৎস। নিয়মিত তদারকি, নির্মাণসামগ্রী ঢেকে রাখা এবং রাস্তা পরিষ্কার রাখলে দূষণ কমানো সম্ভব।",
                        'seo_keywords'     => "নির্মাণসামগ্রী, বায়ুদূষণ, মোবাইল কোর্ট, পরিবেশ অধিদপ্তর, দূষণ",
                        'published_at'     => "2026-04-02 00:00",
                    ],

                    (object) [
                        "category_name"    => "দূষণ",
                        'name'             => "ইটিপি ছাড়া কারখানায় নদী দূষণবিরোধী অভিযান",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নরসিংদী ও নারায়ণগঞ্জে ইটিপিবিহীন কারখানার বিরুদ্ধে নদী দূষণ রোধে অভিযান পরিচালনা করেছে পরিবেশ অধিদপ্তর।",
                        "body"             => "পরিবেশ অধিদপ্তর নরসিংদী ও নারায়ণগঞ্জে ইটিপিবিহীন কয়েকটি কারখানায় নদী দূষণবিরোধী অভিযান পরিচালনা করেছে। শিল্পকারখানার অপরিশোধিত বর্জ্য সরাসরি নদী বা খালে গেলে পানি দূষিত হয়, মাছ ও জলজ প্রাণী ক্ষতিগ্রস্ত হয় এবং মানুষের স্বাস্থ্যঝুঁকি বাড়ে। ইটিপি স্থাপন ও সচল রাখা শিল্প দূষণ নিয়ন্ত্রণে অত্যন্ত গুরুত্বপূর্ণ। বিশেষজ্ঞরা বলছেন, শুধু অভিযান নয়, নিয়মিত নজরদারি ও কঠোর শাস্তিও দরকার।",
                        'seo_keywords'     => "ইটিপি, নদী দূষণ, কারখানা, পরিবেশ অধিদপ্তর, দূষণ",
                        'published_at'     => "2026-04-05 00:00",
                    ],

                    (object) [
                        "category_name"    => "দূষণ",
                        'name'             => "শব্দদূষণ নিয়ন্ত্রণে প্রশিক্ষণ ও সচেতনতা কার্যক্রম",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শব্দদূষণ নিয়ন্ত্রণে পরিবেশ অধিদপ্তরের কর্মকর্তা ও অংশীজনদের নিয়ে প্রশিক্ষণ ও সচেতনতা কার্যক্রম চলছে।",
                        "body"             => "শব্দদূষণ শহুরে জীবনের বড় সমস্যা হয়ে উঠেছে। যানবাহনের হর্ন, নির্মাণকাজ, মাইকিং ও শিল্পকারখানার শব্দ মানুষের ঘুম, মনোযোগ ও মানসিক স্বাস্থ্যে প্রভাব ফেলতে পারে। পরিবেশ অধিদপ্তরের বিভিন্ন প্রশিক্ষণ ও প্রকল্পে শব্দদূষণ নিয়ন্ত্রণ, আইন প্রয়োগ এবং অংশীজনদের সমন্বিত ভূমিকার ওপর জোর দেওয়া হচ্ছে। বিশেষজ্ঞরা বলছেন, হাসপাতাল, শিক্ষাপ্রতিষ্ঠান ও আবাসিক এলাকায় শব্দের মাত্রা নিয়ন্ত্রণে কঠোর নজরদারি দরকার।",
                        'seo_keywords'     => "শব্দদূষণ, পরিবেশ অধিদপ্তর, প্রশিক্ষণ, দূষণ, নগর",
                        'published_at'     => "2026-04-29 00:00",
                    ],

                    (object) [
                        "category_name"    => "বন্যপ্রাণী",
                        'name'             => "বন্যপ্রাণী সুরক্ষায় নতুন অধ্যাদেশ জারি",
                        'sub_heading'      => "জীববৈচিত্র্য সংরক্ষণে আইনি কাঠামো শক্তিশালী",
                        'content_shoulder' => "",
                        'brief'            => "বন্যপ্রাণী সংরক্ষণ ও নিরাপত্তা অধ্যাদেশ ২০২৬ জারির মাধ্যমে বন্যপ্রাণী সুরক্ষায় নতুন আইনি কাঠামো যুক্ত হয়েছে।",
                        "body"             => "দেশের জীববৈচিত্র্য ও বন্যপ্রাণী সুরক্ষায় ‘বন্যপ্রাণী সংরক্ষণ ও নিরাপত্তা অধ্যাদেশ ২০২৬’ গেজেট আকারে প্রকাশ করা হয়েছে। এতে বন্যপ্রাণী উদ্ধার, চিকিৎসা, আবাসস্থল ও করিডোর সুরক্ষা, অপরাধ নিয়ন্ত্রণ, জরিমানা ও দণ্ডসহ নানা বিষয় অন্তর্ভুক্ত করা হয়েছে। সামাজিক যোগাযোগমাধ্যমে বন্যপ্রাণীর বিজ্ঞাপন বা নিষ্ঠুর আচরণ প্রকাশের বিরুদ্ধেও বিধান রাখা হয়েছে। আইনের কার্যকর প্রয়োগ হলে বন ও জীববৈচিত্র্য সংরক্ষণে সহায়তা মিলতে পারে।",
                        'seo_keywords'     => "বন্যপ্রাণী, অধ্যাদেশ, জীববৈচিত্র্য, বন, সংরক্ষণ",
                        'published_at'     => "2026-01-09 00:00",
                    ],

                    (object) [
                        "category_name"    => "বন্যপ্রাণী",
                        'name'             => "শেরপুরে হাতির ভয়ে আগাম ধান কাটছেন কৃষকরা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "শেরপুর সীমান্তে বন্য হাতির পাল ফসল নষ্ট করায় কৃষকেরা কাঁচা ও আধাপাকা ধান আগেভাগেই কাটতে বাধ্য হচ্ছেন।",
                        "body"             => "শেরপুরের শ্রীবরদী, ঝিনাইগাতী ও নালিতাবাড়ী সীমান্ত এলাকায় বন্য হাতির আতঙ্কে কৃষকেরা আগেভাগেই ধান কাটছেন। গত এক সপ্তাহে হাতির দল কয়েক একর জমির ধান খেয়ে ও মাড়িয়ে নষ্ট করেছে বলে স্থানীয়রা জানিয়েছেন। বন বিভাগের তথ্য অনুযায়ী, ৪০ থেকে ৪৫টি হাতির একটি দলে শাবকও রয়েছে। মানুষ-হাতি সংঘাত কমাতে এলিফ্যান্ট রেসপন্স টিম ও গ্রামবাসী পাহারায় আছে। তবে ফসল ক্ষতি ও কৃষকের আর্থিক নিরাপত্তা বড় উদ্বেগ হয়ে উঠেছে।",
                        'seo_keywords'     => "বন্য হাতি, শেরপুর, বন্যপ্রাণী, ফসল, কৃষক",
                        'published_at'     => "2026-05-03 11:30",
                    ],

                    (object) [
                        "category_name"    => "বন্যপ্রাণী",
                        'name'             => "রামুতে বন্য হাতির আক্রমণে মা-মেয়ের মৃত্যু",
                        'sub_heading'      => "",
                        'content_shoulder' => "মানুষ-হাতি সংঘাত",
                        'brief'            => "কক্সবাজারের রামুতে বন্য হাতির আক্রমণে এক নারী ও তার শিশু কন্যার মৃত্যুতে এলাকায় শোক ও আতঙ্ক ছড়িয়েছে।",
                        "body"             => "কক্সবাজারের রামু উপজেলার খুনিয়াপালং এলাকায় বন্য হাতির আক্রমণে এক মা ও তার তিন বছর বয়সী শিশুকন্যার মৃত্যু হয়েছে। বনাঞ্চল সংলগ্ন বসতি, ফসলি জমি ও হাতির চলাচলপথ সংকুচিত হওয়ায় মানুষ-হাতি সংঘাত বাড়ছে বলে বিশেষজ্ঞরা মনে করেন। শুধু ক্ষতিপূরণ নয়, হাতির করিডোর রক্ষা, আগাম সতর্কতা, নিরাপদ বসতি পরিকল্পনা এবং স্থানীয় মানুষকে সম্পৃক্ত করে সংঘাত কমানোর দীর্ঘমেয়াদি ব্যবস্থা প্রয়োজন।",
                        'seo_keywords'     => "রামু, বন্য হাতি, বন্যপ্রাণী, কক্সবাজার, মানুষ-হাতি সংঘাত",
                        'published_at'     => "2026-04-30 00:00",
                    ],

                    (object) [
                        "category_name"    => "বন্যপ্রাণী",
                        'name'             => "সুন্দরবনে চোরা শিকারিদের ফাঁদে বিপন্ন প্রাণী",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "সুন্দরবনে হরিণ শিকারের ফাঁদে বাঘ, বানরসহ নানা প্রাণী আটকা পড়ায় বন্যপ্রাণী সুরক্ষা নিয়ে উদ্বেগ বেড়েছে।",
                        "body"             => "সুন্দরবনে চোরা শিকারিদের উপদ্রবে হরিণসহ নানা বন্যপ্রাণী ঝুঁকিতে পড়ছে। বনজুড়ে হরিণ ধরার জন্য ফাঁদ পাতা হলেও তাতে বাঘ, বানরসহ অন্যান্য প্রাণীও আটকা পড়ছে। বন বিভাগের তথ্য অনুযায়ী, গত দুই বছরে বিপুল পরিমাণ হরিণের মাংস জব্দ করা হয়েছে এবং শিকারিদের বিরুদ্ধে মামলা ও গ্রেপ্তার হয়েছে। কর্মকর্তারা বলছেন, পর্যাপ্ত জনবল, নৌযান, টহল বৃদ্ধি এবং স্থানীয় সচেতনতা ছাড়া সুন্দরবনের প্রাণী সুরক্ষা কঠিন।",
                        'seo_keywords'     => "সুন্দরবন, চোরা শিকার, হরিণ, বন্যপ্রাণী, বন বিভাগ",
                        'published_at'     => "2026-04-28 17:49",
                    ],

                    (object) [
                        "category_name"    => "বন্যপ্রাণী",
                        'name'             => "কুয়াকাটায় আবারও মৃত ডলফিন ভেসে এলো",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "কুয়াকাটা সৈকতে জোয়ারের সঙ্গে মৃত ডলফিন ভেসে আসায় সামুদ্রিক প্রাণী সুরক্ষা নিয়ে উদ্বেগ বেড়েছে।",
                        "body"             => "পটুয়াখালীর কুয়াকাটা সৈকতে আবারও একটি মৃত ডলফিন ভেসে এসেছে। স্থানীয়রা জানান, ডলফিনটির শরীরে আঘাতের চিহ্ন ছিল, যা অস্বাভাবিক মৃত্যুর ইঙ্গিত দিতে পারে। ডলফিন রক্ষা কমিটি, পৌরসভা, বন বিভাগ ও স্বেচ্ছাসেবীরা মৃত প্রাণীটি মাটি চাপা দেন। সাম্প্রতিক সময়ে উপকূলে ডলফিন ও অন্যান্য সামুদ্রিক প্রাণীর মৃতদেহ ভেসে আসা সাগরের প্রতিবেশ, জাল, নৌযান, দূষণ ও জলবায়ু পরিবর্তনের সম্ভাব্য প্রভাব নিয়ে প্রশ্ন তুলছে।",
                        'seo_keywords'     => "কুয়াকাটা, ডলফিন, সামুদ্রিক প্রাণী, বন্যপ্রাণী, উপকূল",
                        'published_at'     => "2026-04-17 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রাকৃতিক দুর্যোগ",
                        'name'             => "মে মাসে লঘুচাপ, কালবৈশাখী ও তাপপ্রবাহের পূর্বাভাস",
                        'sub_heading'      => "আবহাওয়ার অস্থিরতায় প্রস্তুতির পরামর্শ",
                        'content_shoulder' => "",
                        'brief'            => "মে মাসে লঘুচাপ, কালবৈশাখী, তাপপ্রবাহ ও স্বাভাবিক বৃষ্টির সম্ভাবনা থাকায় সতর্ক থাকতে বলছে আবহাওয়া অধিদপ্তর।",
                        "body"             => "মে মাসের আবহাওয়া পূর্বাভাসে লঘুচাপ, কালবৈশাখী, তাপপ্রবাহ ও স্বাভাবিক বৃষ্টির সম্ভাবনা উল্লেখ করা হয়েছে। বঙ্গোপসাগরে এক থেকে দুটি লঘুচাপ তৈরি হতে পারে এবং একটি নিম্নচাপ বা দুর্বল ঘূর্ণিঝড়ে রূপ নিতে পারে। একই সঙ্গে ৫ থেকে ৬টি কালবৈশাখী হতে পারে, যার মধ্যে কয়েক দিন তীব্র ঝড়ের আশঙ্কা আছে। দুর্যোগপ্রবণ এলাকায় মানুষকে আবহাওয়ার সতর্কবার্তা অনুসরণ, দুর্বল ঘর মেরামত ও নিরাপদ আশ্রয়ের প্রস্তুতি নিতে বলা হচ্ছে।",
                        'seo_keywords'     => "প্রাকৃতিক দুর্যোগ, কালবৈশাখী, লঘুচাপ, তাপপ্রবাহ, মে মাস",
                        'published_at'     => "2026-05-03 10:15",
                    ],

                    (object) [
                        "category_name"    => "প্রাকৃতিক দুর্যোগ",
                        'name'             => "সমুদ্রবন্দরে সতর্কসংকেত, চার বিভাগে ভারী বৃষ্টির পূর্বাভাস",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ঢাকা, ময়মনসিংহ, চট্টগ্রাম ও সিলেট বিভাগের কোথাও কোথাও ভারী থেকে অতিভারী বৃষ্টির পূর্বাভাস দেওয়া হয়েছে।",
                        "body"             => "আবহাওয়া অধিদপ্তর ঢাকা, ময়মনসিংহ, চট্টগ্রাম ও সিলেট বিভাগের কোথাও কোথাও ভারী থেকে অতিভারী বর্ষণের পূর্বাভাস দিয়েছে। সমুদ্রবন্দরগুলোতেও সতর্কসংকেত দেখাতে বলা হয়েছে। ভারী বৃষ্টি, দমকা হাওয়া ও বজ্রপাতের সময় উপকূলীয় ও নদীবন্দর এলাকায় ঝুঁকি বাড়ে। পাহাড়ি ঢল, জলাবদ্ধতা ও ভূমিধসপ্রবণ এলাকায় স্থানীয় প্রশাসনকে প্রস্তুত থাকার প্রয়োজন হয়। সাধারণ মানুষকে ঝড়বৃষ্টির সময় খোলা জায়গা, গাছ ও বিদ্যুতের খুঁটির কাছ থেকে দূরে থাকতে বলা হয়।",
                        'seo_keywords'     => "ভারী বৃষ্টি, সতর্কসংকেত, সমুদ্রবন্দর, প্রাকৃতিক দুর্যোগ, আবহাওয়া",
                        'published_at'     => "2026-04-28 11:30",
                    ],

                    (object) [
                        "category_name"    => "প্রাকৃতিক দুর্যোগ",
                        'name'             => "কুমিল্লায় দেড় ঘণ্টার বৃষ্টিতে ভয়াবহ জলাবদ্ধতা",
                        'sub_heading'      => "",
                        'content_shoulder' => "নগর জলাবদ্ধতা",
                        'brief'            => "কুমিল্লায় অল্প সময়ে ১০২ মিলিমিটার বৃষ্টিতে নগরের বিভিন্ন এলাকায় জলাবদ্ধতা তৈরি হয়ে জনজীবন ব্যাহত হয়েছে।",
                        "body"             => "কুমিল্লায় মাত্র দেড় ঘণ্টায় ১০২ মিলিমিটার বৃষ্টিপাতের ফলে নগরের বিভিন্ন এলাকায় ভয়াবহ জলাবদ্ধতা সৃষ্টি হয়। কিছু শিক্ষাপ্রতিষ্ঠানে পরীক্ষার্থীদের কক্ষে পানি ঢুকে পড়ার খবরও পাওয়া যায়। নগরে ড্রেনেজ দুর্বলতা, খাল-নালা দখল ও অতিবৃষ্টির সমন্বয়ে এমন দুর্যোগ আরও ঘন ঘন হতে পারে। জলাবদ্ধতা শুধু চলাচলই ব্যাহত করে না, পানিবাহিত রোগ, বিদ্যুৎ দুর্ঘটনা ও সম্পদ ক্ষতির ঝুঁকিও বাড়ায়।",
                        'seo_keywords'     => "কুমিল্লা, জলাবদ্ধতা, ভারী বৃষ্টি, প্রাকৃতিক দুর্যোগ, নগর",
                        'published_at'     => "2026-04-28 00:00",
                    ],

                    (object) [
                        "category_name"    => "প্রাকৃতিক দুর্যোগ",
                        'name'             => "নোয়াখালীতে ঝড়ে ঘর হারিয়ে খোলা আকাশের নিচে পরিবার",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "নোয়াখালীর উপকূলীয় এলাকায় ঝড়ে ক্ষতিগ্রস্ত এক পরিবার ঘর হারিয়ে খোলা আকাশের নিচে দিন কাটাচ্ছে।",
                        "body"             => "নোয়াখালীর সুবর্ণচর এলাকায় ঝড়ে ঘর ক্ষতিগ্রস্ত হওয়ায় একটি পরিবার খোলা আকাশের নিচে দিন কাটাচ্ছে। স্থানীয়রা জানান, নদীভাঙনে জমি হারানো অনেক পরিবার বেড়িবাঁধের পাশে অস্থায়ী ঘর তুলে থাকে এবং প্রাকৃতিক দুর্যোগে তারাই বেশি ক্ষতিগ্রস্ত হয়। উপজেলা প্রশাসন ক্ষতিগ্রস্ত পরিবারকে টিন, প্রয়োজনীয় সামগ্রী ও খাদ্য সহায়তা দেওয়ার কথা জানিয়েছে। উপকূলে দুর্বল আবাসন, নদীভাঙন ও ঝড় একসঙ্গে জীবন-জীবিকাকে অনিশ্চিত করে তুলছে।",
                        'seo_keywords'     => "নোয়াখালী, ঝড়, উপকূল, প্রাকৃতিক দুর্যোগ, ক্ষতিগ্রস্ত পরিবার",
                        'published_at'     => "2026-05-03 08:00",
                    ],

                    (object) [
                        "category_name"    => "প্রাকৃতিক দুর্যোগ",
                        'name'             => "ভোলায় আকস্মিক ঝড়ে গাছ উপড়ে যান চলাচল ব্যাহত",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "ভোলার কালবৈশাখী ঝড়ে ঘরবাড়ির পাশাপাশি সড়কে গাছ উপড়ে পড়ে যান চলাচলও ব্যাহত হয়েছে।",
                        "body"             => "ভোলার ওপর দিয়ে বয়ে যাওয়া আকস্মিক কালবৈশাখী ঝড়ে অর্ধশতাধিক ঘরবাড়ি ক্ষতিগ্রস্ত হওয়ার পাশাপাশি বিভিন্ন সড়কে গাছ উপড়ে পড়ে যান চলাচল ব্যাহত হয়। দুর্যোগের পর ক্ষতিগ্রস্ত পরিবারগুলো জরুরি সহায়তার অপেক্ষায় আছে। কালবৈশাখী মৌসুমে হঠাৎ দমকা হাওয়া, বজ্রপাত ও ভারী বৃষ্টির ঝুঁকি বেশি থাকে। তাই দুর্বল ঘরবাড়ি মেরামত, গাছ ছাঁটাই, বিদ্যুৎ সংযোগে সতর্কতা এবং আশ্রয়কেন্দ্র প্রস্তুত রাখা গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "ভোলা, কালবৈশাখী, গাছ উপড়ে, প্রাকৃতিক দুর্যোগ, ঝড়",
                        'published_at'     => "2026-05-02 19:10",
                    ],

                    (object) [
                        "category_name"    => "টেকসই উন্নয়ন",
                        'name'             => "নবায়নযোগ্য জ্বালানিতে টেকসই ভবিষ্যতের বার্তা",
                        'sub_heading'      => "জ্বালানি নিরাপত্তায় সৌর, বায়ু ও বায়োমাসের গুরুত্ব",
                        'content_shoulder' => "",
                        'brief'            => "নবায়নযোগ্য জ্বালানির ব্যবহার বাড়িয়ে জ্বালানি নিরাপত্তা ও পরিবেশবান্ধব উন্নয়ন নিশ্চিত করার তাগিদ দেওয়া হয়েছে।",
                        "body"             => "বাংলাদেশের জ্বালানি খাতকে টেকসই ও আধুনিক ভিত্তির ওপর গড়ে তুলতে নবায়নযোগ্য জ্বালানির গুরুত্ব বাড়ছে। সৌরশক্তি, বায়ুশক্তি ও বায়োমাস স্থানীয় সম্পদ হিসেবে জ্বালানি আমদানির চাপ কমাতে পারে। একই সঙ্গে কার্বন নিঃসরণ কমানো ও জলবায়ু লক্ষ্য অর্জনে সহায়ক হতে পারে। গ্রামীণ এলাকায় সৌরচালিত সেচ, সোলার হোম সিস্টেম এবং শিল্পে পরিচ্ছন্ন জ্বালানির ব্যবহার বাড়ানো হলে অর্থনীতি ও পরিবেশ—দুই ক্ষেত্রেই সুফল পাওয়া সম্ভব।",
                        'seo_keywords'     => "নবায়নযোগ্য জ্বালানি, টেকসই উন্নয়ন, সৌরশক্তি, জ্বালানি নিরাপত্তা, বাংলাদেশ",
                        'published_at'     => "2026-05-02 17:00",
                    ],

                    (object) [
                        "category_name"    => "টেকসই উন্নয়ন",
                        'name'             => "উপকূলে লবণাক্ততা টেকসই উন্নয়নের বড় চ্যালেঞ্জ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "উপকূলীয় অঞ্চলে লবণাক্ততা বৃদ্ধি ও মাটি-পানির দূষণ টেকসই উন্নয়নের জন্য বড় চ্যালেঞ্জ বলে সতর্ক করা হয়েছে।",
                        "body"             => "পরিবেশ, বন ও জলবায়ু পরিবর্তন প্রতিমন্ত্রী বলেছেন, উপকূলীয় এলাকায় লবণাক্ততা বৃদ্ধি ও মাটি-পানির দূষণ টেকসই উন্নয়নের ক্ষেত্রে বড় চ্যালেঞ্জ। বিশেষ করে রামপাল, মোংলা, কয়রা ও সাতক্ষীরা অঞ্চলে সমস্যা প্রকট। লবণাক্ততা কৃষি, পানীয় জল, স্বাস্থ্য ও জীবিকায় সরাসরি প্রভাব ফেলে। প্রকৃতি-নির্ভর সমাধান, খাল-পুকুর পুনরুদ্ধার, মিঠা পানির সংরক্ষণ এবং জলবায়ু-সহনশীল কৃষি ব্যবস্থা এই অঞ্চলের টেকসই উন্নয়নে গুরুত্বপূর্ণ।",
                        'seo_keywords'     => "লবণাক্ততা, উপকূল, টেকসই উন্নয়ন, পানি দূষণ, জলবায়ু",
                        'published_at'     => "2026-04-17 00:00",
                    ],

                    (object) [
                        "category_name"    => "টেকসই উন্নয়ন",
                        'name'             => "সেন্টমার্টিন রক্ষায় টেকসই মাস্টারপ্ল্যানের তাগিদ",
                        'sub_heading'      => "",
                        'content_shoulder' => "দ্বীপ সংরক্ষণ",
                        'brief'            => "সেন্টমার্টিন দ্বীপ রক্ষায় দীর্ঘমেয়াদি টেকসই ব্যবস্থাপনা পরিকল্পনা ও মাস্টারপ্ল্যান চূড়ান্ত করার ওপর জোর দেওয়া হয়েছে।",
                        "body"             => "সেন্টমার্টিন দ্বীপকে ইকোলজিক্যালি ক্রিটিক্যাল এরিয়া হিসেবে বিবেচনা করে দীর্ঘমেয়াদি টেকসই ব্যবস্থাপনা পরিকল্পনা নেওয়ার ওপর গুরুত্ব দেওয়া হয়েছে। দ্বীপে পর্যটন, স্থানীয় জীবিকা, প্রবাল প্রতিবেশ, বর্জ্য ব্যবস্থাপনা ও জলবায়ু অভিযোজনকে সমন্বিতভাবে দেখতে হবে। প্রস্তাবিত মাস্টারপ্ল্যানে পরিবেশ সংরক্ষণ, টেকসই পর্যটন ও কমিউনিটি-ভিত্তিক ব্যবস্থাপনা যুক্ত করার কথা বলা হয়েছে। দ্বীপের পরিবেশ রক্ষা ছাড়া পর্যটনও দীর্ঘমেয়াদে টিকবে না।",
                        'seo_keywords'     => "সেন্টমার্টিন, টেকসই উন্নয়ন, দ্বীপ, পরিবেশ সংরক্ষণ, পর্যটন",
                        'published_at'     => "2026-01-06 00:00",
                    ],

                    (object) [
                        "category_name"    => "টেকসই উন্নয়ন",
                        'name'             => "বিশ্বব্যাংকের সঙ্গে পরিবেশ ও টেকসই উন্নয়ন আলোচনা",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "পরিবেশ সংরক্ষণ, দূষণ নিয়ন্ত্রণ, নবায়নযোগ্য জ্বালানি ও এআইভিত্তিক পর্যবেক্ষণ নিয়ে বিশ্বব্যাংকের সঙ্গে আলোচনা হয়েছে।",
                        "body"             => "পরিবেশমন্ত্রীর সঙ্গে বিশ্বব্যাংক প্রতিনিধিদের বৈঠকে পরিবেশ সংরক্ষণ, টেকসই উন্নয়ন, দূষণ নিয়ন্ত্রণ, নবায়নযোগ্য জ্বালানির ব্যবহার বৃদ্ধি এবং চলমান প্রকল্পের অগ্রগতি নিয়ে আলোচনা হয়েছে। বায়ু ও পানি দূষণ পর্যবেক্ষণে কৃত্রিম বুদ্ধিমত্তা প্রযুক্তির ব্যবহার নিয়েও মতবিনিময় হয়। উন্নয়ন প্রকল্পে পরিবেশগত ঝুঁকি কমানো, তথ্যভিত্তিক নজরদারি এবং প্রতিষ্ঠানগত সক্ষমতা বাড়ানো টেকসই উন্নয়নের জন্য গুরুত্বপূর্ণ হিসেবে বিবেচিত।",
                        'seo_keywords'     => "বিশ্বব্যাংক, টেকসই উন্নয়ন, দূষণ নিয়ন্ত্রণ, নবায়নযোগ্য জ্বালানি, এআই",
                        'published_at'     => "2026-04-27 00:00",
                    ],

                    (object) [
                        "category_name"    => "টেকসই উন্নয়ন",
                        'name'             => "নিম্ন-কার্বন উন্নয়ন কৌশল প্রণয়নের উদ্যোগ",
                        'sub_heading'      => "",
                        'content_shoulder' => "",
                        'brief'            => "জলবায়ু পরিবর্তনের প্রভাব মোকাবিলায় নিম্ন-কার্বন উন্নয়ন কৌশল ও অভিযোজন পরিকল্পনা বাস্তবায়নে উদ্যোগ নেওয়া হয়েছে।",
                        "body"             => "জলবায়ু পরিবর্তনের প্রভাব মোকাবিলায় সরকার জাতীয় অভিযোজন পরিকল্পনা, জাতীয়ভাবে নির্ধারিত অবদান এবং দীর্ঘমেয়াদি স্বল্প-নির্গমন উন্নয়ন কৌশল প্রণয়ন ও বাস্তবায়নে কাজ করছে। নিম্ন-কার্বন উন্নয়ন কৌশল শিল্প, জ্বালানি, পরিবহন, কৃষি ও নগর খাতে কার্বন নিঃসরণ কমাতে সহায়ক হতে পারে। জলবায়ু সহনশীল অবকাঠামো, নবায়নযোগ্য জ্বালানি, দূষণ নিয়ন্ত্রণ ও আন্তর্জাতিক জলবায়ু অর্থায়ন টেকসই উন্নয়নের গুরুত্বপূর্ণ অংশ।",
                        'seo_keywords'     => "নিম্ন-কার্বন, টেকসই উন্নয়ন, জলবায়ু অর্থায়ন, অভিযোজন, বাংলাদেশ",
                        'published_at'     => "2026-04-15 00:00",
                    ],

                ]),
            ],

        ]);
    }

}
