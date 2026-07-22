<?php
namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\Language;
use Illuminate\Database\Seeder;

class UpdateLanguageDefaultSeeder extends Seeder
{
    public function run(): void
    {
        Language::query()
            ->where('code', SeederHelper::SITE_PUBLIC_DEFAULT_LANGUAGE)
            ->update([
                'is_default' => true,
            ]);
    }
}
