<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Helpers\SystemHelper;
use App\Models\Event;
use App\Models\Language;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Event::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='events'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Event::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Event::truncate();
        }

        $languages = Language::all()->keyBy('code');

        $eventsByLanguageGroupsFromStaticData = $this->getEventsByLanguageGroupsFromStaticData();
        foreach ($eventsByLanguageGroupsFromStaticData as $eventsByLanguageGroup) {
            $language = $languages[$eventsByLanguageGroup->language_code] ?? null;
            foreach ($eventsByLanguageGroup->events as $event) {
                Event::factory()->state([
                    'name'         => $event->name,
                    'brief'      => $event->brief ?? null,
                    'language_id'  => $language?->id,
                    'is_current'   => false,
                    'seo_title'    => $event->name,
                    'seo_brief'    => $event->brief ?? null,
                    'seo_keywords' => $event->seo_keywords ?? null,
                ])->create();
            }
        }

        $desktopBannerImageUrl = asset("uploads/images/event/desktop.png");
        if ($desktopBannerImageUrl) {
            $events = Event::orderBy("id", "desc")->get();
            foreach ($events as $event) {
                try {
                    $headers = get_headers($desktopBannerImageUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $desktopBannerImageExtension = pathinfo($desktopBannerImageUrl, PATHINFO_EXTENSION);
                        $desktopBannerImageExtension = in_array($desktopBannerImageExtension, ["png", "jpg", "jpeg"]) ? $desktopBannerImageExtension : "png";
                        $desktopBannerImageFileName  = MediaHelper::generateMediaName($event->name, $desktopBannerImageExtension, 200);
                        $event->addMediaFromUrl($desktopBannerImageUrl)
                            ->usingName($event->name)
                            ->usingFileName($desktopBannerImageFileName)
                            ->withCustomProperties(['caption' => $event->name, 'alt' => $event->name, "role" => MediaHelper::MEDIA_ROLE_EVENT_DESKTOP_BANNER_IMAGE])
                            ->toMediaCollection($event->media_collection_name);
                    } else {
                        Log::info("Image not accessable user: {$event->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for user {$event->name}: {$ex->getMessage()}");
                }
            }
        }

        $mobileBannerImageUrl = asset("uploads/images/event/mobile.png");
        if ($mobileBannerImageUrl) {
            $events = Event::orderBy("id", "desc")->get();
            foreach ($events as $event) {
                try {
                    $headers = get_headers($mobileBannerImageUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $mobileBannerImageExtension = pathinfo($mobileBannerImageUrl, PATHINFO_EXTENSION);
                        $mobileBannerImageExtension = in_array($mobileBannerImageExtension, ["png", "jpg", "jpeg"]) ? $mobileBannerImageExtension : "png";
                        $mobileBannerImageFileName  = MediaHelper::generateMediaName($event->name, $mobileBannerImageExtension, 200);
                        $event->addMediaFromUrl($mobileBannerImageUrl)
                            ->usingName($event->name)
                            ->usingFileName($mobileBannerImageFileName)
                            ->withCustomProperties(['caption' => $event->name, 'alt' => $event->name, "role" => MediaHelper::MEDIA_ROLE_EVENT_MOBILE_BANNER_IMAGE])
                            ->toMediaCollection($event->media_collection_name);
                    } else {
                        Log::info("Image not accessable user: {$event->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for user {$event->name}: {$ex->getMessage()}");
                }
            }
        }
    }

    private function getEventsByLanguageGroupsFromStaticData()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::LANGUAGE_DEFAULT_CODE,
                'events'        => collect([
                    (object) ['name' => 'May Day', 'start_date' => '2026-05-01', 'end_date' => '2026-05-01', 'brief' => 'International Workers Day', 'seo_keywords' => 'labour day'],
                    (object) ['name' => 'Eid-ul-Adha', 'start_date' => '2026-06-17', 'end_date' => '2026-06-20', 'brief' => 'Festival of sacrifice', 'seo_keywords' => 'eid adha, qurbani'],
                    (object) ['name' => 'Ashura', 'start_date' => '2026-07-27', 'end_date' => '2026-07-27', 'brief' => 'Islamic remembrance day', 'seo_keywords' => 'ashura'],
                    (object) ['name' => 'National Mourning Day', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15', 'brief' => 'Remembrance day', 'seo_keywords' => 'mourning day'],
                    (object) ['name' => 'Durga Puja', 'start_date' => '2026-10-20', 'end_date' => '2026-10-24', 'brief' => 'Hindu religious festival', 'seo_keywords' => 'durga puja'],
                    (object) ['name' => 'Diwali', 'start_date' => '2026-11-08', 'end_date' => '2026-11-08', 'brief' => 'Festival of lights', 'seo_keywords' => 'diwali'],
                    (object) ['name' => 'Victory Day', 'start_date' => '2026-12-16', 'end_date' => '2026-12-16', 'brief' => 'Victory celebration', 'seo_keywords' => 'victory day'],
                    (object) ['name' => 'Christmas', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'brief' => 'Christian festival', 'seo_keywords' => 'christmas'],

                    (object) ['name' => 'April Fool’s Day', 'start_date' => '2026-04-01', 'end_date' => '2026-04-01', 'brief' => 'Fun prank day', 'seo_keywords' => 'april fools'],
                    (object) ['name' => 'Pohela Boishakh', 'start_date' => '2026-04-14', 'end_date' => '2026-04-14', 'brief' => 'Bengali New Year', 'seo_keywords' => 'pohela boishakh'],
                    (object) ['name' => 'World Health Day', 'start_date' => '2026-04-07', 'end_date' => '2026-04-07', 'brief' => 'Health awareness', 'seo_keywords' => 'health day'],

                    (object) ['name' => 'New Year', 'start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'brief' => 'New Year celebration', 'seo_keywords' => 'new year'],
                    (object) ['name' => 'International Book Fair', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28', 'brief' => 'Annual book fair', 'seo_keywords' => 'book fair'],
                    (object) ['name' => 'Valentine’s Day', 'start_date' => '2026-02-14', 'end_date' => '2026-02-14', 'brief' => 'Day of love', 'seo_keywords' => 'valentine'],
                    (object) ['name' => 'Language Martyrs Day', 'start_date' => '2026-02-21', 'end_date' => '2026-02-21', 'brief' => 'International Mother Language Day', 'seo_keywords' => '21 february'],
                    (object) ['name' => 'International Women’s Day', 'start_date' => '2026-03-08', 'end_date' => '2026-03-08', 'brief' => 'Women empowerment day', 'seo_keywords' => 'women day'],
                    (object) ['name' => 'Independence Day', 'start_date' => '2026-03-26', 'end_date' => '2026-03-26', 'brief' => 'Independence celebration', 'seo_keywords' => 'independence day'],
                    (object) ['name' => 'Shab-e-Barat', 'start_date' => '2026-02-26', 'end_date' => '2026-02-26', 'brief' => 'Islamic holy night', 'seo_keywords' => 'shab e barat'],
                    (object) ['name' => 'Eid-ul-Fitr', 'start_date' => '2026-04-10', 'end_date' => '2026-04-16', 'brief' => 'End of Ramadan festival', 'seo_keywords' => 'eid fitr'],

                    (object) ['name' => 'World Environment Day', 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'brief' => 'Environment awareness', 'seo_keywords' => 'environment day'],
                    (object) ['name' => 'Father’s Day', 'start_date' => '2026-06-21', 'end_date' => '2026-06-21', 'brief' => 'Honor fathers', 'seo_keywords' => 'fathers day'],
                    (object) ['name' => 'Mother’s Day', 'start_date' => '2026-05-10', 'end_date' => '2026-05-10', 'brief' => 'Honor mothers', 'seo_keywords' => 'mothers day'],
                    (object) ['name' => 'Youth Day', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'brief' => 'Youth celebration', 'seo_keywords' => 'youth day'],
                    (object) ['name' => 'Teacher’s Day', 'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'brief' => 'Teachers appreciation', 'seo_keywords' => 'teachers day'],
                    (object) ['name' => 'Shab-e-Qadr', 'start_date' => '2026-04-05', 'end_date' => '2026-04-05', 'brief' => 'Night of power', 'seo_keywords' => 'lailatul qadr'],
                ]),
            ],

            (object) [
                'language_code' => SystemHelper::LANGUAGE_EXTRA_BN_CODE,
                'events'        => collect([
                    (object) ['name' => 'মে দিবস', 'start_date' => '2026-05-01', 'end_date' => '2026-05-01', 'brief' => 'আন্তর্জাতিক শ্রমিক দিবস', 'seo_keywords' => 'শ্রমিক দিবস'],
                    (object) ['name' => 'ঈদুল আজহা', 'start_date' => '2026-06-17', 'end_date' => '2026-06-20', 'brief' => 'কুরবানির উৎসব', 'seo_keywords' => 'ঈদুল আজহা, কুরবানি'],
                    (object) ['name' => 'আশুরা', 'start_date' => '2026-07-27', 'end_date' => '2026-07-27', 'brief' => 'ইসলামিক স্মরণ দিবস', 'seo_keywords' => 'আশুরা'],
                    (object) ['name' => 'জাতীয় শোক দিবস', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15', 'brief' => 'শ্রদ্ধা নিবেদনের দিন', 'seo_keywords' => 'শোক দিবস'],
                    (object) ['name' => 'দুর্গাপূজা', 'start_date' => '2026-10-20', 'end_date' => '2026-10-24', 'brief' => 'হিন্দু ধর্মীয় উৎসব', 'seo_keywords' => 'দুর্গাপূজা'],
                    (object) ['name' => 'দীপাবলি', 'start_date' => '2026-11-08', 'end_date' => '2026-11-08', 'brief' => 'আলোর উৎসব', 'seo_keywords' => 'দীপাবলি'],
                    (object) ['name' => 'বিজয় দিবস', 'start_date' => '2026-12-16', 'end_date' => '2026-12-16', 'brief' => 'বিজয়ের উদযাপন', 'seo_keywords' => 'বিজয় দিবস'],
                    (object) ['name' => 'বড়দিন', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'brief' => 'খ্রিস্টান ধর্মীয় উৎসব', 'seo_keywords' => 'বড়দিন'],

                    (object) ['name' => 'এপ্রিল ফুলস ডে', 'start_date' => '2026-04-01', 'end_date' => '2026-04-01', 'brief' => 'মজার ঠাট্টার দিন', 'seo_keywords' => 'এপ্রিল ফুল'],
                    (object) ['name' => 'পহেলা বৈশাখ', 'start_date' => '2026-04-14', 'end_date' => '2026-04-14', 'brief' => 'বাংলা নববর্ষ', 'seo_keywords' => 'পহেলা বৈশাখ'],
                    (object) ['name' => 'বিশ্ব স্বাস্থ্য দিবস', 'start_date' => '2026-04-07', 'end_date' => '2026-04-07', 'brief' => 'স্বাস্থ্য সচেতনতা দিবস', 'seo_keywords' => 'স্বাস্থ্য দিবস'],

                    (object) ['name' => 'নববর্ষ', 'start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'brief' => 'নতুন বছরের উদযাপন', 'seo_keywords' => 'নববর্ষ'],
                    (object) ['name' => 'আন্তর্জাতিক বইমেলা', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28', 'brief' => 'বার্ষিক বইমেলা', 'seo_keywords' => 'বইমেলা'],
                    (object) ['name' => 'ভ্যালেন্টাইনস ডে', 'start_date' => '2026-02-14', 'end_date' => '2026-02-14', 'brief' => 'ভালোবাসার দিন', 'seo_keywords' => 'ভ্যালেন্টাইন'],
                    (object) ['name' => 'ভাষা শহীদ দিবস', 'start_date' => '2026-02-21', 'end_date' => '2026-02-21', 'brief'=> 'আন্তর্জাতিক মাতৃভাষা দিবস', 'seo_keywords' => '২১ ফেব্রুয়ারি'],
                    (object) ['name' => 'আন্তর্জাতিক নারী দিবস', 'start_date' => '2026-03-08', 'end_date' => '2026-03-08', 'brief' => 'নারী ক্ষমতায়ন দিবস', 'seo_keywords' => 'নারী দিবস'],
                    (object) ['name' => 'স্বাধীনতা দিবস', 'start_date' => '2026-03-26', 'end_date' => '2026-03-26', 'brief' => 'স্বাধীনতার উদযাপন', 'seo_keywords' => 'স্বাধীনতা দিবস'],
                    (object) ['name' => 'শবে বরাত', 'start_date' => '2026-02-26', 'end_date' => '2026-02-26', 'brief' => 'ইসলামিক পবিত্র রাত', 'seo_keywords' => 'শবে বরাত'],
                    (object) ['name' => 'ঈদুল ফিতর', 'start_date' => '2026-04-10', 'end_date' => '2026-04-16', 'brief' => 'রমজান শেষে উৎসব', 'seo_keywords' => 'ঈদুল ফিতর'],

                    (object) ['name' => 'বিশ্ব পরিবেশ দিবস', 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'brief' => 'পরিবেশ সচেতনতা', 'seo_keywords' => 'পরিবেশ দিবস'],
                    (object) ['name' => 'বাবা দিবস', 'start_date' => '2026-06-21', 'end_date' => '2026-06-21', 'brief' => 'বাবাদের সম্মান জানানো', 'seo_keywords' => 'বাবা দিবস'],
                    (object) ['name' => 'মা দিবস', 'start_date' => '2026-05-10', 'end_date' => '2026-05-10', 'brief' => 'মায়েদের সম্মান', 'seo_keywords' => 'মা দিবস'],
                    (object) ['name' => 'যুব দিবস', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'brief' => 'যুবকদের উদযাপন', 'seo_keywords' => 'যুব দিবস'],
                    (object) ['name' => 'শিক্ষক দিবস', 'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'brief' => 'শিক্ষকদের সম্মান', 'seo_keywords' => 'শিক্ষক দিবস'],
                    (object) ['name' => 'শবে কদর', 'start_date' => '2026-04-05', 'end_date' => '2026-04-05', 'brief' => 'পবিত্র ক্ষমার রাত', 'seo_keywords' => 'লাইলাতুল কদর'],
                ]),
            ],

        ]);
    }

}
