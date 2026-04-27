<?php
namespace Database\Seeders;

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

        $language       = Language::where("code", "en_us")->first() ?? null;
        $nationCategory = Category::where("name", 'National')->first() ?? null;

        $locations = collect([

            (object) [
                'name'      => 'Dhaka',
                'details'   => 'Central division of Bangladesh',
                'latitude'  => 23.8103,
                'longitude' => 90.4125,
                'districts' => collect([
                    (object) ['name' => 'Dhaka', 'details' => 'Capital city', 'latitude' => 23.8103, 'longitude' => 90.4125],
                    (object) ['name' => 'Gazipur', 'details' => 'Industrial zone', 'latitude' => 24.0023, 'longitude' => 90.4264],
                    (object) ['name' => 'Narayanganj', 'details' => 'River port city', 'latitude' => 23.6238, 'longitude' => 90.5000],
                    (object) ['name' => 'Narsingdi', 'details' => 'Textile hub', 'latitude' => 23.9322, 'longitude' => 90.7150],
                    (object) ['name' => 'Tangail', 'details' => 'Handloom industry', 'latitude' => 24.2513, 'longitude' => 89.9167],
                    (object) ['name' => 'Kishoreganj', 'details' => 'Haor region', 'latitude' => 24.4449, 'longitude' => 90.7766],
                    (object) ['name' => 'Manikganj', 'details' => 'Agriculture area', 'latitude' => 23.8617, 'longitude' => 90.0003],
                    (object) ['name' => 'Munshiganj', 'details' => 'Historic Bikrampur', 'latitude' => 23.5422, 'longitude' => 90.5305],
                    (object) ['name' => 'Faridpur', 'details' => 'River-based district', 'latitude' => 23.6071, 'longitude' => 89.8429],
                    (object) ['name' => 'Gopalganj', 'details' => 'Birthplace of Bangabandhu', 'latitude' => 23.0050, 'longitude' => 89.8266],
                    (object) ['name' => 'Madaripur', 'details' => 'Padma river area', 'latitude' => 23.1641, 'longitude' => 90.1897],
                    (object) ['name' => 'Rajbari', 'details' => 'Agricultural district', 'latitude' => 23.7574, 'longitude' => 89.6440],
                    (object) ['name' => 'Shariatpur', 'details' => 'River delta region', 'latitude' => 23.2423, 'longitude' => 90.4348],
                ]),
            ],

            (object) [
                'name'      => 'Chattogram',
                'details'   => 'Port division of Bangladesh',
                'latitude'  => 22.3569,
                'longitude' => 91.7832,
                'districts' => collect([
                    (object) ['name' => 'Chattogram', 'details' => 'Main seaport', 'latitude' => 22.3569, 'longitude' => 91.7832],
                    (object) ['name' => "Cox's Bazar", 'details' => 'Sea beach', 'latitude' => 21.4272, 'longitude' => 92.0058],
                    (object) ['name' => 'Cumilla', 'details' => 'Historic city', 'latitude' => 23.4607, 'longitude' => 91.1809],
                    (object) ['name' => 'Feni', 'details' => 'Trade district', 'latitude' => 23.0159, 'longitude' => 91.3976],
                    (object) ['name' => 'Brahmanbaria', 'details' => 'Cultural district', 'latitude' => 23.9571, 'longitude' => 91.1115],
                    (object) ['name' => 'Noakhali', 'details' => 'Coastal district', 'latitude' => 22.8696, 'longitude' => 91.0995],
                    (object) ['name' => 'Lakshmipur', 'details' => 'Riverine area', 'latitude' => 22.9443, 'longitude' => 90.8282],
                    (object) ['name' => 'Khagrachari', 'details' => 'Hill district', 'latitude' => 23.1193, 'longitude' => 91.9847],
                    (object) ['name' => 'Rangamati', 'details' => 'Hill and lake district', 'latitude' => 22.7324, 'longitude' => 92.2985],
                    (object) ['name' => 'Bandarban', 'details' => 'Hilly tourism area', 'latitude' => 22.1953, 'longitude' => 92.2184],
                ]),
            ],

            (object) [
                'name'      => 'Rajshahi',
                'details'   => 'North-west division',
                'latitude'  => 24.3745,
                'longitude' => 88.6042,
                'districts' => collect([
                    (object) ['name' => 'Rajshahi', 'details' => 'Silk city', 'latitude' => 24.3745, 'longitude' => 88.6042],
                    (object) ['name' => 'Bogura', 'details' => 'Trade hub', 'latitude' => 24.8510, 'longitude' => 89.3697],
                    (object) ['name' => 'Pabna', 'details' => 'Agricultural region', 'latitude' => 24.0064, 'longitude' => 89.2372],
                    (object) ['name' => 'Natore', 'details' => 'Historic rajbari area', 'latitude' => 24.4206, 'longitude' => 89.0000],
                    (object) ['name' => 'Naogaon', 'details' => 'Rice production area', 'latitude' => 24.7936, 'longitude' => 88.9318],
                    (object) ['name' => 'Chapainawabganj', 'details' => 'Mango capital', 'latitude' => 24.5965, 'longitude' => 88.2775],
                    (object) ['name' => 'Joypurhat', 'details' => 'Small district', 'latitude' => 25.0968, 'longitude' => 89.0227],
                    (object) ['name' => 'Sirajganj', 'details' => 'Jamuna river area', 'latitude' => 24.4534, 'longitude' => 89.7007],
                ]),
            ],

            (object) [
                'name'      => 'Khulna',
                'details'   => 'South-west division',
                'latitude'  => 22.8456,
                'longitude' => 89.5403,
                'districts' => collect([
                    (object) ['name' => 'Khulna', 'details' => 'Gateway to Sundarbans', 'latitude' => 22.8456, 'longitude' => 89.5403],
                    (object) ['name' => 'Jessore', 'details' => 'Transport hub', 'latitude' => 23.1667, 'longitude' => 89.2167],
                    (object) ['name' => 'Satkhira', 'details' => 'Coastal district', 'latitude' => 22.7185, 'longitude' => 89.0705],
                    (object) ['name' => 'Bagerhat', 'details' => 'Historic mosque city', 'latitude' => 22.6516, 'longitude' => 89.7859],
                    (object) ['name' => 'Chuadanga', 'details' => 'Border district', 'latitude' => 23.6402, 'longitude' => 88.8418],
                    (object) ['name' => 'Jhenaidah', 'details' => 'Agricultural zone', 'latitude' => 23.5446, 'longitude' => 89.1539],
                    (object) ['name' => 'Magura', 'details' => 'Small district', 'latitude' => 23.4873, 'longitude' => 89.4190],
                    (object) ['name' => 'Meherpur', 'details' => 'Liberation war site', 'latitude' => 23.7622, 'longitude' => 88.6318],
                    (object) ['name' => 'Narail', 'details' => 'Cultural district', 'latitude' => 23.1725, 'longitude' => 89.5127],
                    (object) ['name' => 'Kushtia', 'details' => 'Lalon Shah mazar', 'latitude' => 23.9013, 'longitude' => 89.1205],
                ]),
            ],

            (object) [
                'name'      => 'Barishal',
                'details'   => 'Southern river division',
                'latitude'  => 22.7010,
                'longitude' => 90.3535,
                'districts' => collect([
                    (object) ['name' => 'Barishal', 'details' => 'City of rivers', 'latitude' => 22.7010, 'longitude' => 90.3535],
                    (object) ['name' => 'Bhola', 'details' => 'Island district', 'latitude' => 22.6859, 'longitude' => 90.6482],
                    (object) ['name' => 'Patuakhali', 'details' => 'Coastal tourism', 'latitude' => 22.3596, 'longitude' => 90.3297],
                    (object) ['name' => 'Pirojpur', 'details' => 'Riverine district', 'latitude' => 22.5841, 'longitude' => 89.9720],
                    (object) ['name' => 'Jhalokati', 'details' => 'Small river district', 'latitude' => 22.6406, 'longitude' => 90.1987],
                    (object) ['name' => 'Barguna', 'details' => 'Coastal belt', 'latitude' => 22.1592, 'longitude' => 90.1260],
                ]),
            ],

            (object) [
                'name'      => 'Sylhet',
                'details'   => 'Tea garden division',
                'latitude'  => 24.8949,
                'longitude' => 91.8687,
                'districts' => collect([
                    (object) ['name' => 'Sylhet', 'details' => 'Tea city', 'latitude' => 24.8949, 'longitude' => 91.8687],
                    (object) ['name' => 'Moulvibazar', 'details' => 'Tea estates', 'latitude' => 24.4829, 'longitude' => 91.7774],
                    (object) ['name' => 'Habiganj', 'details' => 'Gas field area', 'latitude' => 24.3740, 'longitude' => 91.4155],
                    (object) ['name' => 'Sunamganj', 'details' => 'Haor wetlands', 'latitude' => 25.0658, 'longitude' => 91.3950],
                ]),
            ],

            (object) [
                'name'      => 'Rangpur',
                'details'   => 'Northern division',
                'latitude'  => 25.7439,
                'longitude' => 89.2752,
                'districts' => collect([
                    (object) ['name' => 'Rangpur', 'details' => 'Agriculture zone', 'latitude' => 25.7439, 'longitude' => 89.2752],
                    (object) ['name' => 'Dinajpur', 'details' => 'Historic sites', 'latitude' => 25.6279, 'longitude' => 88.6332],
                    (object) ['name' => 'Kurigram', 'details' => 'Flood-prone area', 'latitude' => 25.8054, 'longitude' => 89.6362],
                    (object) ['name' => 'Gaibandha', 'details' => 'River basin', 'latitude' => 25.3290, 'longitude' => 89.5430],
                    (object) ['name' => 'Nilphamari', 'details' => 'Teesta region', 'latitude' => 25.9318, 'longitude' => 88.8560],
                    (object) ['name' => 'Panchagarh', 'details' => 'Northernmost district', 'latitude' => 26.3411, 'longitude' => 88.5542],
                    (object) ['name' => 'Thakurgaon', 'details' => 'Border district', 'latitude' => 26.0337, 'longitude' => 88.4617],
                    (object) ['name' => 'Lalmonirhat', 'details' => 'Railway hub', 'latitude' => 25.9923, 'longitude' => 89.2847],
                ]),
            ],

            (object) [
                'name'      => 'Mymensingh',
                'details'   => 'North-central division',
                'latitude'  => 24.7471,
                'longitude' => 90.4203,
                'districts' => collect([
                    (object) ['name' => 'Mymensingh', 'details' => 'Education hub', 'latitude' => 24.7471, 'longitude' => 90.4203],
                    (object) ['name' => 'Jamalpur', 'details' => 'Riverine district', 'latitude' => 24.9375, 'longitude' => 89.9378],
                    (object) ['name' => 'Netrokona', 'details' => 'Haor region', 'latitude' => 24.8833, 'longitude' => 90.7333],
                    (object) ['name' => 'Sherpur', 'details' => 'Border hills', 'latitude' => 25.0205, 'longitude' => 90.0153],
                ]),
            ],

        ]);

        foreach ($locations as $location) {
            $this->createLocation(null, $location, $language, $nationCategory);
        }
    }

    public function createLocation(?Location $parent, $location, $language, ?Category $category): Location
    {
        $saveLocation = Location::factory()->state([
            'name'         => $location->name,
            'details'      => $location->details ?? null,
            'parent_id'    => $parent?->id,
            'language_id'  => $language?->id,
            'category_id'  => $category?->id ?? null,
            'name_tree'    => ($parent ? $parent->name . ' - ' : '') . $location->name,
            'slug_tree'    => ($parent ? $parent->slug . '/' : '') . Str::slug($location->name),

            'seo_title'    => $location->name,
            'seo_brief'    => $location->details ?? null,
            'seo_keywords' => $location->seo_keywords ?? null,

            'latitude'     => $location->latitude ?? null,
            'longitude'    => $location->longitude ?? null,
        ])->create();

        if (! empty($location->sub_categories)) {
            foreach ($location->sub_categories as $subLocation) {
                $this->createLocation($saveLocation, $subLocation, $language, $category);
            }
        }

        return $saveLocation;
    }
}
