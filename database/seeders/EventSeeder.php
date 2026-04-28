<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Models\Event;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

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

        $language = Language::where("code", "en_us")->first() ?? null;

        $events = collect([

            (object) ['name' => 'May Day', 'start_date' => '2026-05-01', 'end_date' => '2026-05-01', 'details' => 'International Workers Day', 'seo_keywords' => 'labour day'],
            (object) ['name' => 'Eid-ul-Adha', 'start_date' => '2026-06-17', 'end_date' => '2026-06-20', 'details' => 'Festival of sacrifice', 'seo_keywords' => 'eid adha, qurbani'],
            (object) ['name' => 'Ashura', 'start_date' => '2026-07-27', 'end_date' => '2026-07-27', 'details' => 'Islamic remembrance day', 'seo_keywords' => 'ashura'],
            (object) ['name' => 'National Mourning Day', 'start_date' => '2026-08-15', 'end_date' => '2026-08-15', 'details' => 'Remembrance day', 'seo_keywords' => 'mourning day'],
            (object) ['name' => 'Durga Puja', 'start_date' => '2026-10-20', 'end_date' => '2026-10-24', 'details' => 'Hindu religious festival', 'seo_keywords' => 'durga puja'],
            (object) ['name' => 'Diwali', 'start_date' => '2026-11-08', 'end_date' => '2026-11-08', 'details' => 'Festival of lights', 'seo_keywords' => 'diwali'],
            (object) ['name' => 'Victory Day', 'start_date' => '2026-12-16', 'end_date' => '2026-12-16', 'details' => 'Victory celebration', 'seo_keywords' => 'victory day'],
            (object) ['name' => 'Christmas', 'start_date' => '2026-12-25', 'end_date' => '2026-12-25', 'details' => 'Christian festival', 'seo_keywords' => 'christmas'],

            (object) ['name' => 'April Fool’s Day', 'start_date' => '2026-04-01', 'end_date' => '2026-04-01', 'details' => 'Fun prank day', 'seo_keywords' => 'april fools'],
            (object) ['name' => 'Pohela Boishakh', 'start_date' => '2026-04-14', 'end_date' => '2026-04-14', 'details' => 'Bengali New Year', 'seo_keywords' => 'pohela boishakh'],
            (object) ['name' => 'World Health Day', 'start_date' => '2026-04-07', 'end_date' => '2026-04-07', 'details' => 'Health awareness', 'seo_keywords' => 'health day'],

            (object) ['name' => 'New Year', 'start_date' => '2026-01-01', 'end_date' => '2026-01-01', 'details' => 'New Year celebration', 'seo_keywords' => 'new year'],
            (object) ['name' => 'International Book Fair', 'start_date' => '2026-02-01', 'end_date' => '2026-02-28', 'details' => 'Annual book fair', 'seo_keywords' => 'book fair'],
            (object) ['name' => 'Valentine’s Day', 'start_date' => '2026-02-14', 'end_date' => '2026-02-14', 'details' => 'Day of love', 'seo_keywords' => 'valentine'],
            (object) ['name' => 'Language Martyrs Day', 'start_date' => '2026-02-21', 'end_date' => '2026-02-21', 'details' => 'International Mother Language Day', 'seo_keywords' => '21 february'],
            (object) ['name' => 'International Women’s Day', 'start_date' => '2026-03-08', 'end_date' => '2026-03-08', 'details' => 'Women empowerment day', 'seo_keywords' => 'women day'],
            (object) ['name' => 'Independence Day', 'start_date' => '2026-03-26', 'end_date' => '2026-03-26', 'details' => 'Independence celebration', 'seo_keywords' => 'independence day'],
            (object) ['name' => 'Shab-e-Barat', 'start_date' => '2026-02-26', 'end_date' => '2026-02-26', 'details' => 'Islamic holy night', 'seo_keywords' => 'shab e barat'],
            (object) ['name' => 'Eid-ul-Fitr', 'start_date' => '2026-04-10', 'end_date' => '2026-04-16', 'details' => 'End of Ramadan festival', 'seo_keywords' => 'eid fitr'],

            (object) ['name' => 'World Environment Day', 'start_date' => '2026-06-05', 'end_date' => '2026-06-05', 'details' => 'Environment awareness', 'seo_keywords' => 'environment day'],
            (object) ['name' => 'Father’s Day', 'start_date' => '2026-06-21', 'end_date' => '2026-06-21', 'details' => 'Honor fathers', 'seo_keywords' => 'fathers day'],
            (object) ['name' => 'Mother’s Day', 'start_date' => '2026-05-10', 'end_date' => '2026-05-10', 'details' => 'Honor mothers', 'seo_keywords' => 'mothers day'],
            (object) ['name' => 'Youth Day', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'details' => 'Youth celebration', 'seo_keywords' => 'youth day'],
            (object) ['name' => 'Teacher’s Day', 'start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'details' => 'Teachers appreciation', 'seo_keywords' => 'teachers day'],
            (object) ['name' => 'Shab-e-Qadr', 'start_date' => '2026-04-05', 'end_date' => '2026-04-05', 'details' => 'Night of power', 'seo_keywords' => 'lailatul qadr'],

        ]);
        foreach ($events as $event) {
            Event::factory()->state([
                'name'         => $event->name,
                'details'      => $event->details ?? null,
                'language_id'  => $language?->id,
                'is_current'   => false,
                'seo_title'    => $event->name,
                'seo_brief'    => $event->details ?? null,
                'seo_keywords' => $event->seo_keywords ?? null,
            ])->create();
        }

        $desktopBannerImageUrl = asset("uploads/images/event-desktop.png");
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

        $mobileBannerImageUrl = asset("uploads/images/event-mobile.png");
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

}
