<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
use App\Models\Language;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationMapInfoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $languages = Language::all()->keyBy('code');

            $adm2Features = $this->getAdm2Features();

            foreach ($this->getBangladeshLocationGroupsFromStaticData($adm2Features) as $group) {
                $language = $languages[$group->language_code] ?? null;

                if (! $language) {
                    continue;
                }

                foreach ($group->locations as $location) {
                    $this->updateLocationMapInfo(null, $location, $language);
                }
            }
        });
    }

    private function updateLocationMapInfo(?Location $parent, object $locationData, Language $language): ?Location
    {
        $location = Location::query()
            ->where('language_id', $language->id)
            ->where('name', $locationData->name)
            ->when($parent, fn($query) => $query->where('parent_id', $parent->id))
            ->when(! $parent, fn($query) => $query->whereNull('parent_id'))
            ->first();

        if (! $location) {
            return null;
        }

        $location->forceFill([
            'latitude'         => $locationData->latitude,
            'longitude'        => $locationData->longitude,
            'boundary_geojson' => $locationData->boundary_geojson,
            'boundary_north'   => $locationData->boundary_north,
            'boundary_south'   => $locationData->boundary_south,
            'boundary_east'    => $locationData->boundary_east,
            'boundary_west'    => $locationData->boundary_west,
        ])->save();

        foreach ($locationData->districts ?? collect() as $childLocation) {
            $this->updateLocationMapInfo($location, $childLocation, $language);
        }

        return $location;
    }

    private function getBangladeshLocationGroupsFromStaticData(array $adm2Features)
    {
        $rows = $this->getBangladeshRows();

        return collect([
            (object) [
                'language_code' => SystemHelper::DEFAULT_LANGUAGE_CODE,
                'locations'     => collect($rows)->map(fn($row) => $this->makeLocation($row, false, $adm2Features, 1)),
            ],
            (object) [
                'language_code' => SystemHelper::EXTRA_LANGUAGE_BN_CODE,
                'locations'     => collect($rows)->map(fn($row) => $this->makeLocation($row, true, $adm2Features, 1)),
            ],
        ]);
    }

    private function makeLocation(array $row, bool $bangla, array $adm2Features, int $level): object
    {
        $children = collect($row['districts'] ?? [])
            ->map(fn($district) => $this->makeLocation($district, $bangla, $adm2Features, 2));

        $name = $bangla ? $row['bn_name'] : $row['en_name'];

        if ($level === 2) {
            $feature = $this->findFeature($adm2Features, array_merge(
                [$row['en_name']],
                $row['aliases'] ?? [],
            ));
        } else {
            $childFeatures = $children
                ->pluck('boundary_geojson')
                ->filter()
                ->values()
                ->all();

            $feature = count($childFeatures)
                ? [
                'type'     => 'FeatureCollection',
                'features' => $childFeatures,
            ]
                : null;
        }

        $bounds = $feature
            ? $this->getGeoJsonBounds($feature)
            : $this->makeFallbackBounds($row, $children);

        return (object) [
            'name'             => $name,
            'latitude'         => $row['latitude'],
            'longitude'        => $row['longitude'],
            'boundary_geojson' => $feature,
            'boundary_north'   => $bounds['north'] ?? null,
            'boundary_south'   => $bounds['south'] ?? null,
            'boundary_east'    => $bounds['east'] ?? null,
            'boundary_west'    => $bounds['west'] ?? null,
            'districts'        => $children,
        ];
    }

    private function getAdm2Features(): array
    {
        $path = public_path('uploads/maps/bangladesh/geo-boundary.geojson');

        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if (! $content) {
            return [];
        }

        $geoJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $this->indexGeoJsonFeatures($geoJson);
    }

    private function indexGeoJsonFeatures(array $geoJson): array
    {
        $index = [];

        foreach ($geoJson['features'] ?? [] as $feature) {
            foreach ($this->getFeatureNames($feature) as $name) {
                $key = $this->normalizeName($name);

                if ($key) {
                    $index[$key] = $feature;
                }
            }
        }

        return $index;
    }

    private function getFeatureNames(array $feature): array
    {
        $properties = $feature['properties'] ?? [];

        return array_filter([
            $properties['shapeName'] ?? null,
            $properties['shapeISO'] ?? null,
            $properties['ADM2_EN'] ?? null,
            $properties['NAME_2'] ?? null,
            $properties['name'] ?? null,
            $properties['Name'] ?? null,
            $properties['NAME'] ?? null,
        ]);
    }

    private function findFeature(array $features, array $names): ?array
    {
        foreach ($names as $name) {
            $key = $this->normalizeName($name);

            if ($key && isset($features[$key])) {
                return $features[$key];
            }
        }

        return null;
    }

    private function normalizeName(?string $name): string
    {
        if (! $name) {
            return '';
        }

        $name = strtolower($name);

        $replace = [
            '&'        => 'and',
            "'"        => '',
            '’'        => '',
            '.'        => '',
            '-'        => ' ',
            '_'        => ' ',
            'district' => '',
            'division' => '',
            'zila'     => '',
            'zilla'    => '',
        ];

        $name = str_replace(array_keys($replace), array_values($replace), $name);

        return preg_replace('/[^a-z0-9]+/', '', $name) ?? '';
    }

    private function getGeoJsonBounds(array $geoJson): ?array
    {
        $pairs = [];

        if (($geoJson['type'] ?? null) === 'FeatureCollection') {
            foreach ($geoJson['features'] ?? [] as $feature) {
                $this->collectCoordinatePairs($feature['geometry']['coordinates'] ?? [], $pairs);
            }
        } elseif (($geoJson['type'] ?? null) === 'Feature') {
            $this->collectCoordinatePairs($geoJson['geometry']['coordinates'] ?? [], $pairs);
        } else {
            $this->collectCoordinatePairs($geoJson['coordinates'] ?? [], $pairs);
        }

        if (empty($pairs)) {
            return null;
        }

        $latitudes  = array_column($pairs, 'lat');
        $longitudes = array_column($pairs, 'lng');

        return [
            'north' => round(max($latitudes), 7),
            'south' => round(min($latitudes), 7),
            'east'  => round(max($longitudes), 7),
            'west'  => round(min($longitudes), 7),
        ];
    }

    private function collectCoordinatePairs($coordinates, array &$pairs): void
    {
        if (! is_array($coordinates)) {
            return;
        }

        if (
            isset($coordinates[0], $coordinates[1]) &&
            is_numeric($coordinates[0]) &&
            is_numeric($coordinates[1])
        ) {
            $pairs[] = [
                'lng' => (float) $coordinates[0],
                'lat' => (float) $coordinates[1],
            ];

            return;
        }

        foreach ($coordinates as $coordinate) {
            $this->collectCoordinatePairs($coordinate, $pairs);
        }
    }

    private function makeFallbackBounds(array $row, $children): array
    {
        if ($children->isNotEmpty()) {
            $north = [];
            $south = [];
            $east  = [];
            $west  = [];

            foreach ($children as $child) {
                if ($child->boundary_north && $child->boundary_south && $child->boundary_east && $child->boundary_west) {
                    $north[] = (float) $child->boundary_north;
                    $south[] = (float) $child->boundary_south;
                    $east[]  = (float) $child->boundary_east;
                    $west[]  = (float) $child->boundary_west;
                }
            }

            if (! empty($north)) {
                return [
                    'north' => round(max($north), 7),
                    'south' => round(min($south), 7),
                    'east'  => round(max($east), 7),
                    'west'  => round(min($west), 7),
                ];
            }
        }

        $latitude  = (float) $row['latitude'];
        $longitude = (float) $row['longitude'];
        $padding   = 0.18;

        return [
            'north' => round($latitude + $padding, 7),
            'south' => round($latitude - $padding, 7),
            'east'  => round($longitude + $padding, 7),
            'west'  => round($longitude - $padding, 7),
        ];
    }

    private function getBangladeshRows(): array
    {
        return [
            [
                'en_name'   => 'Dhaka',
                'bn_name'   => 'ঢাকা',
                'latitude'  => 23.8103,
                'longitude' => 90.4125,
                'aliases'   => ['Dhaka'],
                'districts' => [
                    ['en_name' => 'Dhaka', 'bn_name' => 'ঢাকা', 'latitude' => 23.8103, 'longitude' => 90.4125, 'aliases' => ['Dhaka']],
                    ['en_name' => 'Gazipur', 'bn_name' => 'গাজীপুর', 'latitude' => 24.0023, 'longitude' => 90.4264, 'aliases' => ['Gazipur']],
                    ['en_name' => 'Narayanganj', 'bn_name' => 'নারায়ণগঞ্জ', 'latitude' => 23.6238, 'longitude' => 90.5000, 'aliases' => ['Narayanganj']],
                    ['en_name' => 'Narsingdi', 'bn_name' => 'নরসিংদী', 'latitude' => 23.9322, 'longitude' => 90.7150, 'aliases' => ['Narsingdi']],
                    ['en_name' => 'Tangail', 'bn_name' => 'টাঙ্গাইল', 'latitude' => 24.2513, 'longitude' => 89.9167, 'aliases' => ['Tangail']],
                    ['en_name' => 'Kishoreganj', 'bn_name' => 'কিশোরগঞ্জ', 'latitude' => 24.4449, 'longitude' => 90.7766, 'aliases' => ['Kishoreganj']],
                    ['en_name' => 'Manikganj', 'bn_name' => 'মানিকগঞ্জ', 'latitude' => 23.8617, 'longitude' => 90.0003, 'aliases' => ['Manikganj']],
                    ['en_name' => 'Munshiganj', 'bn_name' => 'মুন্সিগঞ্জ', 'latitude' => 23.5422, 'longitude' => 90.5305, 'aliases' => ['Munshiganj']],
                    ['en_name' => 'Faridpur', 'bn_name' => 'ফরিদপুর', 'latitude' => 23.6071, 'longitude' => 89.8429, 'aliases' => ['Faridpur']],
                    ['en_name' => 'Gopalganj', 'bn_name' => 'গোপালগঞ্জ', 'latitude' => 23.0050, 'longitude' => 89.8266, 'aliases' => ['Gopalganj']],
                    ['en_name' => 'Madaripur', 'bn_name' => 'মাদারীপুর', 'latitude' => 23.1641, 'longitude' => 90.1897, 'aliases' => ['Madaripur']],
                    ['en_name' => 'Rajbari', 'bn_name' => 'রাজবাড়ী', 'latitude' => 23.7574, 'longitude' => 89.6440, 'aliases' => ['Rajbari']],
                    ['en_name' => 'Shariatpur', 'bn_name' => 'শরীয়তপুর', 'latitude' => 23.2423, 'longitude' => 90.4348, 'aliases' => ['Shariatpur']],
                ],
            ],
            [
                'en_name'   => 'Chattogram',
                'bn_name'   => 'চট্টগ্রাম',
                'latitude'  => 22.3569,
                'longitude' => 91.7832,
                'aliases'   => ['Chattogram', 'Chittagong'],
                'districts' => [
                    ['en_name' => 'Chattogram', 'bn_name' => 'চট্টগ্রাম', 'latitude' => 22.3569, 'longitude' => 91.7832, 'aliases' => ['Chattogram', 'Chittagong']],
                    ['en_name' => "Cox's Bazar", 'bn_name' => 'কক্সবাজার', 'latitude' => 21.4272, 'longitude' => 92.0058, 'aliases' => ["Cox's Bazar", 'Coxs Bazar', 'Cox Bazar']],
                    ['en_name' => 'Cumilla', 'bn_name' => 'কুমিল্লা', 'latitude' => 23.4607, 'longitude' => 91.1809, 'aliases' => ['Cumilla', 'Comilla']],
                    ['en_name' => 'Feni', 'bn_name' => 'ফেনী', 'latitude' => 23.0159, 'longitude' => 91.3976, 'aliases' => ['Feni']],
                    ['en_name' => 'Brahmanbaria', 'bn_name' => 'ব্রাহ্মণবাড়িয়া', 'latitude' => 23.9571, 'longitude' => 91.1115, 'aliases' => ['Brahmanbaria', 'Brahman Baria']],
                    ['en_name' => 'Chandpur', 'bn_name' => 'চাঁদপুর', 'latitude' => 23.2333, 'longitude' => 90.6713, 'aliases' => ['Chandpur']],
                    ['en_name' => 'Noakhali', 'bn_name' => 'নোয়াখালী', 'latitude' => 22.8696, 'longitude' => 91.0995, 'aliases' => ['Noakhali']],
                    ['en_name' => 'Lakshmipur', 'bn_name' => 'লক্ষ্মীপুর', 'latitude' => 22.9443, 'longitude' => 90.8282, 'aliases' => ['Lakshmipur', 'Laxmipur']],
                    ['en_name' => 'Khagrachari', 'bn_name' => 'খাগড়াছড়ি', 'latitude' => 23.1193, 'longitude' => 91.9847, 'aliases' => ['Khagrachari', 'Khagrachhari']],
                    ['en_name' => 'Rangamati', 'bn_name' => 'রাঙামাটি', 'latitude' => 22.7324, 'longitude' => 92.2985, 'aliases' => ['Rangamati']],
                    ['en_name' => 'Bandarban', 'bn_name' => 'বান্দরবান', 'latitude' => 22.1953, 'longitude' => 92.2184, 'aliases' => ['Bandarban']],
                ],
            ],
            [
                'en_name'   => 'Rajshahi',
                'bn_name'   => 'রাজশাহী',
                'latitude'  => 24.3745,
                'longitude' => 88.6042,
                'aliases'   => ['Rajshahi'],
                'districts' => [
                    ['en_name' => 'Rajshahi', 'bn_name' => 'রাজশাহী', 'latitude' => 24.3745, 'longitude' => 88.6042, 'aliases' => ['Rajshahi']],
                    ['en_name' => 'Bogura', 'bn_name' => 'বগুড়া', 'latitude' => 24.8510, 'longitude' => 89.3697, 'aliases' => ['Bogura', 'Bogra']],
                    ['en_name' => 'Pabna', 'bn_name' => 'পাবনা', 'latitude' => 24.0064, 'longitude' => 89.2372, 'aliases' => ['Pabna']],
                    ['en_name' => 'Natore', 'bn_name' => 'নাটোর', 'latitude' => 24.4206, 'longitude' => 89.0000, 'aliases' => ['Natore']],
                    ['en_name' => 'Naogaon', 'bn_name' => 'নওগাঁ', 'latitude' => 24.7936, 'longitude' => 88.9318, 'aliases' => ['Naogaon']],
                    ['en_name' => 'Chapainawabganj', 'bn_name' => 'চাঁপাইনবাবগঞ্জ', 'latitude' => 24.5965, 'longitude' => 88.2775, 'aliases' => ['Chapainawabganj', 'Chapai Nawabganj', 'Nawabganj']],
                    ['en_name' => 'Joypurhat', 'bn_name' => 'জয়পুরহাট', 'latitude' => 25.0968, 'longitude' => 89.0227, 'aliases' => ['Joypurhat', 'Jaipurhat']],
                    ['en_name' => 'Sirajganj', 'bn_name' => 'সিরাজগঞ্জ', 'latitude' => 24.4534, 'longitude' => 89.7007, 'aliases' => ['Sirajganj']],
                ],
            ],
            [
                'en_name'   => 'Khulna',
                'bn_name'   => 'খুলনা',
                'latitude'  => 22.8456,
                'longitude' => 89.5403,
                'aliases'   => ['Khulna'],
                'districts' => [
                    ['en_name' => 'Khulna', 'bn_name' => 'খুলনা', 'latitude' => 22.8456, 'longitude' => 89.5403, 'aliases' => ['Khulna']],
                    ['en_name' => 'Jessore', 'bn_name' => 'যশোর', 'latitude' => 23.1667, 'longitude' => 89.2167, 'aliases' => ['Jessore', 'Jashore']],
                    ['en_name' => 'Satkhira', 'bn_name' => 'সাতক্ষীরা', 'latitude' => 22.7185, 'longitude' => 89.0705, 'aliases' => ['Satkhira']],
                    ['en_name' => 'Bagerhat', 'bn_name' => 'বাগেরহাট', 'latitude' => 22.6516, 'longitude' => 89.7859, 'aliases' => ['Bagerhat']],
                    ['en_name' => 'Chuadanga', 'bn_name' => 'চুয়াডাঙ্গা', 'latitude' => 23.6402, 'longitude' => 88.8418, 'aliases' => ['Chuadanga']],
                    ['en_name' => 'Jhenaidah', 'bn_name' => 'ঝিনাইদহ', 'latitude' => 23.5446, 'longitude' => 89.1539, 'aliases' => ['Jhenaidah']],
                    ['en_name' => 'Magura', 'bn_name' => 'মাগুরা', 'latitude' => 23.4873, 'longitude' => 89.4190, 'aliases' => ['Magura']],
                    ['en_name' => 'Meherpur', 'bn_name' => 'মেহেরপুর', 'latitude' => 23.7622, 'longitude' => 88.6318, 'aliases' => ['Meherpur']],
                    ['en_name' => 'Narail', 'bn_name' => 'নড়াইল', 'latitude' => 23.1725, 'longitude' => 89.5127, 'aliases' => ['Narail']],
                    ['en_name' => 'Kushtia', 'bn_name' => 'কুষ্টিয়া', 'latitude' => 23.9013, 'longitude' => 89.1205, 'aliases' => ['Kushtia']],
                ],
            ],
            [
                'en_name'   => 'Barishal',
                'bn_name'   => 'বরিশাল',
                'latitude'  => 22.7010,
                'longitude' => 90.3535,
                'aliases'   => ['Barishal', 'Barisal'],
                'districts' => [
                    ['en_name' => 'Barishal', 'bn_name' => 'বরিশাল', 'latitude' => 22.7010, 'longitude' => 90.3535, 'aliases' => ['Barishal', 'Barisal']],
                    ['en_name' => 'Bhola', 'bn_name' => 'ভোলা', 'latitude' => 22.6859, 'longitude' => 90.6482, 'aliases' => ['Bhola']],
                    ['en_name' => 'Patuakhali', 'bn_name' => 'পটুয়াখালী', 'latitude' => 22.3596, 'longitude' => 90.3297, 'aliases' => ['Patuakhali']],
                    ['en_name' => 'Pirojpur', 'bn_name' => 'পিরোজপুর', 'latitude' => 22.5841, 'longitude' => 89.9720, 'aliases' => ['Pirojpur']],
                    ['en_name' => 'Jhalokati', 'bn_name' => 'ঝালকাঠি', 'latitude' => 22.6406, 'longitude' => 90.1987, 'aliases' => ['Jhalokati', 'Jhalakathi']],
                    ['en_name' => 'Barguna', 'bn_name' => 'বরগুনা', 'latitude' => 22.1592, 'longitude' => 90.1260, 'aliases' => ['Barguna']],
                ],
            ],
            [
                'en_name'   => 'Sylhet',
                'bn_name'   => 'সিলেট',
                'latitude'  => 24.8949,
                'longitude' => 91.8687,
                'aliases'   => ['Sylhet'],
                'districts' => [
                    ['en_name' => 'Sylhet', 'bn_name' => 'সিলেট', 'latitude' => 24.8949, 'longitude' => 91.8687, 'aliases' => ['Sylhet']],
                    ['en_name' => 'Moulvibazar', 'bn_name' => 'মৌলভীবাজার', 'latitude' => 24.4829, 'longitude' => 91.7774, 'aliases' => ['Moulvibazar', 'Maulvibazar', 'Moulvi Bazar']],
                    ['en_name' => 'Habiganj', 'bn_name' => 'হবিগঞ্জ', 'latitude' => 24.3740, 'longitude' => 91.4155, 'aliases' => ['Habiganj']],
                    ['en_name' => 'Sunamganj', 'bn_name' => 'সুনামগঞ্জ', 'latitude' => 25.0658, 'longitude' => 91.3950, 'aliases' => ['Sunamganj']],
                ],
            ],
            [
                'en_name'   => 'Rangpur',
                'bn_name'   => 'রংপুর',
                'latitude'  => 25.7439,
                'longitude' => 89.2752,
                'aliases'   => ['Rangpur'],
                'districts' => [
                    ['en_name' => 'Rangpur', 'bn_name' => 'রংপুর', 'latitude' => 25.7439, 'longitude' => 89.2752, 'aliases' => ['Rangpur']],
                    ['en_name' => 'Dinajpur', 'bn_name' => 'দিনাজপুর', 'latitude' => 25.6279, 'longitude' => 88.6332, 'aliases' => ['Dinajpur']],
                    ['en_name' => 'Kurigram', 'bn_name' => 'কুড়িগ্রাম', 'latitude' => 25.8054, 'longitude' => 89.6362, 'aliases' => ['Kurigram']],
                    ['en_name' => 'Gaibandha', 'bn_name' => 'গাইবান্ধা', 'latitude' => 25.3290, 'longitude' => 89.5430, 'aliases' => ['Gaibandha']],
                    ['en_name' => 'Nilphamari', 'bn_name' => 'নীলফামারী', 'latitude' => 25.9318, 'longitude' => 88.8560, 'aliases' => ['Nilphamari']],
                    ['en_name' => 'Panchagarh', 'bn_name' => 'পঞ্চগড়', 'latitude' => 26.3411, 'longitude' => 88.5542, 'aliases' => ['Panchagarh']],
                    ['en_name' => 'Thakurgaon', 'bn_name' => 'ঠাকুরগাঁও', 'latitude' => 26.0337, 'longitude' => 88.4617, 'aliases' => ['Thakurgaon']],
                    ['en_name' => 'Lalmonirhat', 'bn_name' => 'লালমনিরহাট', 'latitude' => 25.9923, 'longitude' => 89.2847, 'aliases' => ['Lalmonirhat']],
                ],
            ],
            [
                'en_name'   => 'Mymensingh',
                'bn_name'   => 'ময়মনসিংহ',
                'latitude'  => 24.7471,
                'longitude' => 90.4203,
                'aliases'   => ['Mymensingh'],
                'districts' => [
                    ['en_name' => 'Mymensingh', 'bn_name' => 'ময়মনসিংহ', 'latitude' => 24.7471, 'longitude' => 90.4203, 'aliases' => ['Mymensingh']],
                    ['en_name' => 'Jamalpur', 'bn_name' => 'জামালপুর', 'latitude' => 24.9375, 'longitude' => 89.9378, 'aliases' => ['Jamalpur']],
                    ['en_name' => 'Netrokona', 'bn_name' => 'নেত্রকোনা', 'latitude' => 24.8833, 'longitude' => 90.7333, 'aliases' => ['Netrokona', 'Netrakona']],
                    ['en_name' => 'Sherpur', 'bn_name' => 'শেরপুর', 'latitude' => 25.0205, 'longitude' => 90.0153, 'aliases' => ['Sherpur']],
                ],
            ],
        ];
    }
}
