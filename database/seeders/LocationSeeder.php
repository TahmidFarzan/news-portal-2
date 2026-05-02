<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
use App\Models\Category;
use App\Models\Language;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Location::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='locations'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Location::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Location::truncate();
        }

        $languages                 = Language::all()->keyBy('code');
        $locationsByLanguageGroups = $this->locationsByLanguageGroups();

        foreach ($locationsByLanguageGroups as $locationsByLanguageGroup) {
            $language = $languages[$locationsByLanguageGroup->language_code] ?? null;

            foreach ($locationsByLanguageGroup->locations as $location) {
                $category = Category::where("name", 'National')->first() ?? null;

                if ($language->code == SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE) {
                    $category = Category::where("name", 'জাতীয়')->first() ?? null;
                }

                $this->createLocation(null, $location, $language, $category);
            }
        }
    }

    public function createLocation(?Location $parent, $location, Language $language, ?Category $category): Location
    {
        $saveLocation = Location::factory()->state([
            'name'         => $location->name,
            'brief'      => $location->brief ?? null,
            'parent_id'    => $parent?->id,
            'language_id'  => $language?->id,
            'category_id'  => $category?->id ?? null,
            'name_tree'    => ($parent ? $parent->name . ' - ' : '') . $location->name,
            'slug_tree'    => ($parent ? $parent->slug . '/' : '') . Str::slug($location->name),

            'seo_title'    => $location->name,
            'seo_brief'    => $location->brief ?? null,
            'seo_keywords' => $location->seo_keywords ?? null,

            'latitude'     => $location->latitude ?? null,
            'longitude'    => $location->longitude ?? null,
        ])->create();

        if (! empty($location->sub_locations)) {
            foreach ($location->sub_locations as $subLocation) {
                $this->createLocation($saveLocation, $subLocation, $language, $category);
            }
        }

        return $saveLocation;
    }

    private function locationsByLanguageGroups()
    {
        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'locations'     => collect([
                    (object) [
                        'name'      => 'Dhaka',
                        'brief'   => 'Central division of Bangladesh',
                        'latitude'  => 23.8103,
                        'longitude' => 90.4125,
                        'districts' => collect([
                            (object) ['name' => 'Dhaka', 'brief' => 'Capital city', 'latitude' => 23.8103, 'longitude' => 90.4125],
                            (object) ['name' => 'Gazipur', 'brief' => 'Industrial zone', 'latitude' => 24.0023, 'longitude' => 90.4264],
                            (object) ['name' => 'Narayanganj', 'brief' => 'River port city', 'latitude' => 23.6238, 'longitude' => 90.5000],
                            (object) ['name' => 'Narsingdi', 'brief' => 'Textile hub', 'latitude' => 23.9322, 'longitude' => 90.7150],
                            (object) ['name' => 'Tangail', 'brief' => 'Handloom industry', 'latitude' => 24.2513, 'longitude' => 89.9167],
                            (object) ['name' => 'Kishoreganj', 'brief' => 'Haor region', 'latitude' => 24.4449, 'longitude' => 90.7766],
                            (object) ['name' => 'Manikganj', 'brief' => 'Agriculture area', 'latitude' => 23.8617, 'longitude' => 90.0003],
                            (object) ['name' => 'Munshiganj', 'brief' => 'Historic Bikrampur', 'latitude' => 23.5422, 'longitude' => 90.5305],
                            (object) ['name' => 'Faridpur', 'brief' => 'River-based district', 'latitude' => 23.6071, 'longitude' => 89.8429],
                            (object) ['name' => 'Gopalganj', 'brief' => 'Birthplace of Bangabandhu', 'latitude' => 23.0050, 'longitude' => 89.8266],
                            (object) ['name' => 'Madaripur', 'brief' => 'Padma river area', 'latitude' => 23.1641, 'longitude' => 90.1897],
                            (object) ['name' => 'Rajbari', 'brief' => 'Agricultural district', 'latitude' => 23.7574, 'longitude' => 89.6440],
                            (object) ['name' => 'Shariatpur', 'brief' => 'River delta region', 'latitude' => 23.2423, 'longitude' => 90.4348],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Chattogram',
                        'brief'   => 'Port division of Bangladesh',
                        'latitude'  => 22.3569,
                        'longitude' => 91.7832,
                        'districts' => collect([
                            (object) ['name' => 'Chattogram', 'brief' => 'Main seaport', 'latitude' => 22.3569, 'longitude' => 91.7832],
                            (object) ["name" => "Cox's Bazar", 'brief' => 'Sea beach', 'latitude' => 21.4272, 'longitude' => 92.0058],
                            (object) ['name' => 'Cumilla', 'brief' => 'Historic city', 'latitude' => 23.4607, 'longitude' => 91.1809],
                            (object) ['name' => 'Feni', 'brief' => 'Trade district', 'latitude' => 23.0159, 'longitude' => 91.3976],
                            (object) ['name' => 'Brahmanbaria', 'brief' => 'Cultural district', 'latitude' => 23.9571, 'longitude' => 91.1115],
                            (object) ['name' => 'Noakhali', 'brief' => 'Coastal district', 'latitude' => 22.8696, 'longitude' => 91.0995],
                            (object) ['name' => 'Lakshmipur', 'brief' => 'Riverine area', 'latitude' => 22.9443, 'longitude' => 90.8282],
                            (object) ['name' => 'Khagrachari', 'brief' => 'Hill district', 'latitude' => 23.1193, 'longitude' => 91.9847],
                            (object) ['name' => 'Rangamati', 'brief' => 'Hill and lake district', 'latitude' => 22.7324, 'longitude' => 92.2985],
                            (object) ['name' => 'Bandarban', 'brief' => 'Hilly tourism area', 'latitude' => 22.1953, 'longitude' => 92.2184],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Rajshahi',
                        'brief'   => 'North-west division',
                        'latitude'  => 24.3745,
                        'longitude' => 88.6042,
                        'districts' => collect([
                            (object) ['name' => 'Rajshahi', 'brief' => 'Silk city', 'latitude' => 24.3745, 'longitude' => 88.6042],
                            (object) ['name' => 'Bogura', 'brief' => 'Trade hub', 'latitude' => 24.8510, 'longitude' => 89.3697],
                            (object) ['name' => 'Pabna', 'brief' => 'Agricultural region', 'latitude' => 24.0064, 'longitude' => 89.2372],
                            (object) ['name' => 'Natore', 'brief' => 'Historic rajbari area', 'latitude' => 24.4206, 'longitude' => 89.0000],
                            (object) ['name' => 'Naogaon', 'brief' => 'Rice production area', 'latitude' => 24.7936, 'longitude' => 88.9318],
                            (object) ['name' => 'Chapainawabganj', 'brief' => 'Mango capital', 'latitude' => 24.5965, 'longitude' => 88.2775],
                            (object) ['name' => 'Joypurhat', 'brief' => 'Small district', 'latitude' => 25.0968, 'longitude' => 89.0227],
                            (object) ['name' => 'Sirajganj', 'brief' => 'Jamuna river area', 'latitude' => 24.4534, 'longitude' => 89.7007],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Khulna',
                        'brief'   => 'South-west division',
                        'latitude'  => 22.8456,
                        'longitude' => 89.5403,
                        'districts' => collect([
                            (object) ['name' => 'Khulna', 'brief' => 'Gateway to Sundarbans', 'latitude' => 22.8456, 'longitude' => 89.5403],
                            (object) ['name' => 'Jessore', 'brief' => 'Transport hub', 'latitude' => 23.1667, 'longitude' => 89.2167],
                            (object) ['name' => 'Satkhira', 'brief' => 'Coastal district', 'latitude' => 22.7185, 'longitude' => 89.0705],
                            (object) ['name' => 'Bagerhat', 'brief' => 'Historic mosque city', 'latitude' => 22.6516, 'longitude' => 89.7859],
                            (object) ['name' => 'Chuadanga', 'brief' => 'Border district', 'latitude' => 23.6402, 'longitude' => 88.8418],
                            (object) ['name' => 'Jhenaidah', 'brief' => 'Agricultural zone', 'latitude' => 23.5446, 'longitude' => 89.1539],
                            (object) ['name' => 'Magura', 'brief' => 'Small district', 'latitude' => 23.4873, 'longitude' => 89.4190],
                            (object) ['name' => 'Meherpur', 'brief' => 'Liberation war site', 'latitude' => 23.7622, 'longitude' => 88.6318],
                            (object) ['name' => 'Narail', 'brief' => 'Cultural district', 'latitude' => 23.1725, 'longitude' => 89.5127],
                            (object) ['name' => 'Kushtia', 'brief' => 'Lalon Shah mazar', 'latitude' => 23.9013, 'longitude' => 89.1205],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Barishal',
                        'brief'   => 'Southern river division',
                        'latitude'  => 22.7010,
                        'longitude' => 90.3535,
                        'districts' => collect([
                            (object) ['name' => 'Barishal', 'brief' => 'City of rivers', 'latitude' => 22.7010, 'longitude' => 90.3535],
                            (object) ['name' => 'Bhola', 'brief' => 'Island district', 'latitude' => 22.6859, 'longitude' => 90.6482],
                            (object) ['name' => 'Patuakhali', 'brief' => 'Coastal tourism', 'latitude' => 22.3596, 'longitude' => 90.3297],
                            (object) ['name' => 'Pirojpur', 'brief' => 'Riverine district', 'latitude' => 22.5841, 'longitude' => 89.9720],
                            (object) ['name' => 'Jhalokati', 'brief' => 'Small river district', 'latitude' => 22.6406, 'longitude' => 90.1987],
                            (object) ['name' => 'Barguna', 'brief' => 'Coastal belt', 'latitude' => 22.1592, 'longitude' => 90.1260],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Sylhet',
                        'brief'   => 'Tea garden division',
                        'latitude'  => 24.8949,
                        'longitude' => 91.8687,
                        'districts' => collect([
                            (object) ['name' => 'Sylhet', 'brief' => 'Tea city', 'latitude' => 24.8949, 'longitude' => 91.8687],
                            (object) ['name' => 'Moulvibazar', 'brief' => 'Tea estates', 'latitude' => 24.4829, 'longitude' => 91.7774],
                            (object) ['name' => 'Habiganj', 'brief' => 'Gas field area', 'latitude' => 24.3740, 'longitude' => 91.4155],
                            (object) ['name' => 'Sunamganj', 'brief' => 'Haor wetlands', 'latitude' => 25.0658, 'longitude' => 91.3950],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Rangpur',
                        'brief'   => 'Northern division',
                        'latitude'  => 25.7439,
                        'longitude' => 89.2752,
                        'districts' => collect([
                            (object) ['name' => 'Rangpur', 'brief' => 'Agriculture zone', 'latitude' => 25.7439, 'longitude' => 89.2752],
                            (object) ['name' => 'Dinajpur', 'brief' => 'Historic sites', 'latitude' => 25.6279, 'longitude' => 88.6332],
                            (object) ['name' => 'Kurigram', 'brief' => 'Flood-prone area', 'latitude' => 25.8054, 'longitude' => 89.6362],
                            (object) ['name' => 'Gaibandha', 'brief' => 'River basin', 'latitude' => 25.3290, 'longitude' => 89.5430],
                            (object) ['name' => 'Nilphamari', 'brief' => 'Teesta region', 'latitude' => 25.9318, 'longitude' => 88.8560],
                            (object) ['name' => 'Panchagarh', 'brief' => 'Northernmost district', 'latitude' => 26.3411, 'longitude' => 88.5542],
                            (object) ['name' => 'Thakurgaon', 'brief' => 'Border district', 'latitude' => 26.0337, 'longitude' => 88.4617],
                            (object) ['name' => 'Lalmonirhat', 'brief' => 'Railway hub', 'latitude' => 25.9923, 'longitude' => 89.2847],
                        ]),
                    ],

                    (object) [
                        'name'      => 'Mymensingh',
                        'brief'   => 'North-central division',
                        'latitude'  => 24.7471,
                        'longitude' => 90.4203,
                        'districts' => collect([
                            (object) ['name' => 'Mymensingh', 'brief' => 'Education hub', 'latitude' => 24.7471, 'longitude' => 90.4203],
                            (object) ['name' => 'Jamalpur', 'brief' => 'Riverine district', 'latitude' => 24.9375, 'longitude' => 89.9378],
                            (object) ['name' => 'Netrokona', 'brief' => 'Haor region', 'latitude' => 24.8833, 'longitude' => 90.7333],
                            (object) ['name' => 'Sherpur', 'brief' => 'Border hills', 'latitude' => 25.0205, 'longitude' => 90.0153],
                        ]),
                    ],

                ]),
            ],

            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                'locations'     => collect([

                    (object) [
                        'name'      => 'ঢাকা',
                        'brief'   => 'বাংলাদেশের কেন্দ্রীয় বিভাগ',
                        'latitude'  => 23.8103,
                        'longitude' => 90.4125,
                        'districts' => collect([
                            (object) ['name' => 'ঢাকা', 'briefজধানী শহর', 'latitude' => 23.8103, 'longitude' => 90.4125],
                            (object) ['name' => 'গাজীপুর', 'brief' => 'শিল্প এলাকা', 'latitude' => 24.0023, 'longitude' => 90.4264],
                            (object) ['name' => 'নারায়ণগঞ্জ', 'brief' => 'নদীবন্দর শহর', 'latitude' => 23.6238, 'longitude' => 90.5000],
                            (object) ['name' => 'নরসিংদী', 'brief' => 'টেক্সটাইল হাব', 'latitude' => 23.9322, 'longitude' => 90.7150],
                            (object) ['name' => 'টাঙ্গাইল', 'brief' => 'হস্তশিল্প এলাকা', 'latitude' => 24.2513, 'longitude' => 89.9167],
                            (object) ['name' => 'কিশোরগঞ্জ', 'brief' => 'হাওর অঞ্চল', 'latitude' => 24.4449, 'longitude' => 90.7766],
                            (object) ['name' => 'মানিকগঞ্জ', 'brief' => 'কৃষি এলাকা', 'latitude' => 23.8617, 'longitude' => 90.0003],
                            (object) ['name' => 'মুন্সিগঞ্জ', 'brief' => 'ঐতিহাসিক বিক্রমপুর', 'latitude' => 23.5422, 'longitude' => 90.5305],
                            (object) ['name' => 'ফরিদপুর', 'brief' => 'নদীবেষ্টিত জেলা', 'latitude' => 23.6071, 'longitude' => 89.8429],
                            (object) ['name' => 'গোপালগঞ্জ', 'brief' => 'বঙ্গবন্ধুর জন্মস্থান', 'latitude' => 23.0050, 'longitude' => 89.8266],
                            (object) ['name' => 'মাদারীপুর', 'brief' => 'পদ্মা নদী অঞ্চল', 'latitude' => 23.1641, 'longitude' => 90.1897],
                            (object) ['name' => 'রাজবাড়ী', 'brief' => 'কৃষি জেলা', 'latitude' => 23.7574, 'longitude' => 89.6440],
                            (object) ['name' => 'শরীয়তপুর', 'brief' => 'নদী ব-দ্বীপ অঞ্চল', 'latitude' => 23.2423, 'longitude' => 90.4348],
                        ]),
                    ],

                    (object) [
                        'name'      => 'চট্টগ্রাম',
                        'brief'   => 'বাংলাদেশের বন্দর বিভাগ',
                        'latitude'  => 22.3569,
                        'longitude' => 91.7832,
                        'districts' => collect([
                            (object) ['name' => 'চট্টগ্রাম', 'brief' => 'প্রধান সমুদ্র বন্দর', 'latitude' => 22.3569, 'longitude' => 91.7832],
                            (object) ['name' => 'কক্সবাজার', 'brief' => 'সমুদ্র সৈকত', 'latitude' => 21.4272, 'longitude' => 92.0058],
                            (object) ['name' => 'কুমিল্লা', 'brief' => 'ঐতিহাসিক শহর', 'latitude' => 23.4607, 'longitude' => 91.1809],
                            (object) ['name' => 'ফেনী', 'brief' => 'বাণিজ্য জেলা', 'latitude' => 23.0159, 'longitude' => 91.3976],
                            (object) ['name' => 'ব্রাহ্মণবাড়িয়া', 'brief' => 'সাংস্কৃতিক জেলা', 'latitude' => 23.9571, 'longitude' => 91.1115],
                            (object) ['name' => 'নোয়াখালী', 'brief' => 'উপকূলীয় জেলা', 'latitude' => 22.8696, 'longitude' => 91.0995],
                            (object) ['name' => 'লক্ষ্মীপুর', 'brief' => 'নদীবেষ্টিত এলাকা', 'latitude' => 22.9443, 'longitude' => 90.8282],
                            (object) ['name' => 'খাগড়াছড়ি', 'brief' => 'পার্বত্য জেলা', 'latitude' => 23.1193, 'longitude' => 91.9847],
                            (object) ['name' => 'রাঙামাটি', 'brief' => 'পাহাড় ও হ্রদের জেলা', 'latitude' => 22.7324, 'longitude' => 92.2985],
                            (object) ['name' => 'বান্দরবান', 'brief' => 'পার্বত্য পর্যটন এলাকা', 'latitude' => 22.1953, 'longitude' => 92.2184],
                        ]),
                    ],

                    (object) [
                        'name'      => 'রাজশাহী',
                        'brief'   => 'উত্তর-পশ্চিম বিভাগ',
                        'latitude'  => 24.3745,
                        'longitude' => 88.6042,
                        'districts' => collect([
                            (object) ['name' => 'রাজশাহী', 'brief' => 'রেশম নগরী', 'latitude' => 24.3745, 'longitude' => 88.6042],
                            (object) ['name' => 'বগুড়া', 'brief' => 'বাণিজ্য কেন্দ্র', 'latitude' => 24.8510, 'longitude' => 89.3697],
                            (object) ['name' => 'পাবনা', 'brief' => 'কৃষি অঞ্চল', 'latitude' => 24.0064, 'longitude' => 89.2372],
                            (object) ['name' => 'নাটোর', 'brief' => 'ঐতিহাসিক রাজবাড়ী এলাকা', 'latitude' => 24.4206, 'longitude' => 89.0000],
                            (object) ['name' => 'নওগাঁ', 'brief' => 'ধান উৎপাদন এলাকা', 'latitude' => 24.7936, 'longitude' => 88.9318],
                            (object) ['name' => 'চাঁপাইনবাবগঞ্জ', 'brief' => 'আমের রাজধানী', 'latitude' => 24.5965, 'longitude' => 88.2775],
                            (object) ['name' => 'জয়পুরহাট', 'brief' => 'ছোট জেলা', 'latitude' => 25.0968, 'longitude' => 89.0227],
                            (object) ['name' => 'সিরাজগঞ্জ', 'brief' => 'যমুনা নদী এলাকা', 'latitude' => 24.4534, 'longitude' => 89.7007],
                        ]),
                    ],

                    (object) [
                        'name'      => 'খুলনা',
                        'brief'   => 'দক্ষিণ-পশ্চিম বিভাগ',
                        'latitude'  => 22.8456,
                        'longitude' => 89.5403,
                        'districts' => collect([
                            (object) ['name' => 'খুলনা', 'brief' => 'সুন্দরবনের প্রবেশদ্বার', 'latitude' => 22.8456, 'longitude' => 89.5403],
                            (object) ['name' => 'যশোর', 'brief' => 'যোগাযোগ কেন্দ্র', 'latitude' => 23.1667, 'longitude' => 89.2167],
                            (object) ['name' => 'সাতক্ষীরা', 'brief' => 'উপকূলীয় জেলা', 'latitude' => 22.7185, 'longitude' => 89.0705],
                            (object) ['name' => 'বাগেরহাট', 'brief' => 'ঐতিহাসিক মসজিদ শহর', 'latitude' => 22.6516, 'longitude' => 89.7859],
                            (object) ['name' => 'চুয়াডাঙ্গা', 'brief' => 'সীমান্ত জেলা', 'latitude' => 23.6402, 'longitude' => 88.8418],
                            (object) ['name' => 'ঝিনাইদহ', 'brief' => 'কৃষি অঞ্চল', 'latitude' => 23.5446, 'longitude' => 89.1539],
                            (object) ['name' => 'মাগুরা', 'brief' => 'ছোট জেলা', 'latitude' => 23.4873, 'longitude' => 89.4190],
                            (object) ['name' => 'মেহেরপুর', 'brief' => 'মুক্তিযুদ্ধের স্থান', 'latitude' => 23.7622, 'longitude' => 88.6318],
                            (object) ['name' => 'নড়াইল', 'brief' => 'সাংস্কৃতিক জেলা', 'latitude' => 23.1725, 'longitude' => 89.5127],
                            (object) ['name' => 'কুষ্টিয়া', 'brief' => 'লালন শাহ মাজার', 'latitude' => 23.9013, 'longitude' => 89.1205],
                        ]),
                    ],

                    (object) [
                        'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                        'name'          => 'বরিশাল',
                        'brief'       => 'দক্ষিণাঞ্চলের নদীমাতৃক বিভাগ',
                        'latitude'      => 22.7010,
                        'longitude'     => 90.3535,
                        'districts'     => collect([
                            (object) ['name' => 'বরিশাল', 'brief' => 'নদীর শহর', 'latitude' => 22.7010, 'longitude' => 90.3535],
                            (object) ['name' => 'ভোলা', 'brief' => 'দ্বীপ জেলা', 'latitude' => 22.6859, 'longitude' => 90.6482],
                            (object) ['name' => 'পটুয়াখালী', 'brief' => 'উপকূলীয় পর্যটন', 'latitude' => 22.3596, 'longitude' => 90.3297],
                            (object) ['name' => 'পিরোজপুর', 'brief' => 'নদীবেষ্টিত জেলা', 'latitude' => 22.5841, 'longitude' => 89.9720],
                            (object) ['name' => 'ঝালকাঠি', 'brief' => 'ছোট নদী জেলা', 'latitude' => 22.6406, 'longitude' => 90.1987],
                            (object) ['name' => 'বরগুনা', 'brief' => 'উপকূলীয় এলাকা', 'latitude' => 22.1592, 'longitude' => 90.1260],
                        ]),
                    ],

                    (object) [
                        'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                        'name'          => 'সিলেট',
                        'brief'       => 'চা বাগান ও হাওর অঞ্চল',
                        'latitude'      => 24.8949,
                        'longitude'     => 91.8687,
                        'districts'     => collect([
                            (object) ['name' => 'সিলেট', 'brief' => 'চা নগরী', 'latitude' => 24.8949, 'longitude' => 91.8687],
                            (object) ['name' => 'মৌলভীবাজার', 'brief' => 'চা বাগান এলাকা', 'latitude' => 24.4829, 'longitude' => 91.7774],
                            (object) ['name' => 'হবিগঞ্জ', 'brief' => 'গ্যাস সমৃদ্ধ এলাকা', 'latitude' => 24.3740, 'longitude' => 91.4155],
                            (object) ['name' => 'সুনামগঞ্জ', 'brief' => 'হাওর অঞ্চল', 'latitude' => 25.0658, 'longitude' => 91.3950],
                        ]),
                    ],

                    (object) [
                        'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                        'name'          => 'রংপুর',
                        'brief'       => 'উত্তরাঞ্চলের কৃষি বিভাগ',
                        'latitude'      => 25.7439,
                        'longitude'     => 89.2752,
                        'districts'     => collect([
                            (object) ['name' => 'রংপুর', 'brief' => 'কৃষি অঞ্চল', 'latitude' => 25.7439, 'longitude' => 89.2752],
                            (object) ['name' => 'দিনাজপুর', 'brief' => 'ঐতিহাসিক এলাকা', 'latitude' => 25.6279, 'longitude' => 88.6332],
                            (object) ['name' => 'কুড়িগ্রাম', 'brief' => 'বন্যাপ্রবণ এলাকা', 'latitude' => 25.8054, 'longitude' => 89.6362],
                            (object) ['name' => 'গাইবান্ধা', 'brief' => 'নদীবেষ্টিত এলাকা', 'latitude' => 25.3290, 'longitude' => 89.5430],
                            (object) ['name' => 'নীলফামারী', 'brief' => 'তিস্তা নদী অঞ্চল', 'latitude' => 25.9318, 'longitude' => 88.8560],
                            (object) ['name' => 'পঞ্চগড়', 'brief' => 'উত্তরের শেষ জেলা', 'latitude' => 26.3411, 'longitude' => 88.5542],
                            (object) ['name' => 'ঠাকুরগাঁও', 'brief' => 'সীমান্ত জেলা', 'latitude' => 26.0337, 'longitude' => 88.4617],
                            (object) ['name' => 'লালমনিরহাট', 'brief' => 'রেলওয়ে হাব', 'latitude' => 25.9923, 'longitude' => 89.2847],
                        ]),
                    ],

                    (object) [
                        'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_BD_CODE,
                        'name'          => 'ময়মনসিংহ',
                        'brief'       => 'উত্তর-মধ্যাঞ্চলের বিভাগ',
                        'latitude'      => 24.7471,
                        'longitude'     => 90.4203,
                        'districts'     => collect([
                            (object) ['name' => 'ময়মনসিংহ', 'brief' => 'শিক্ষা নগরী', 'latitude' => 24.7471, 'longitude' => 90.4203],
                            (object) ['name' => 'জামালপুর', 'brief' => 'নদীবেষ্টিত জেলা', 'latitude' => 24.9375, 'longitude' => 89.9378],
                            (object) ['name' => 'নেত্রকোনা', 'brief' => 'হাওর অঞ্চল', 'latitude' => 24.8833, 'longitude' => 90.7333],
                            (object) ['name' => 'শেরপুর', 'brief' => 'সীমান্ত পাহাড়ি এলাকা', 'latitude' => 25.0205, 'longitude' => 90.0153],
                        ]),
                    ],
                ]),
            ],

        ]);
    }
}
