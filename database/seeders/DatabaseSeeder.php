<?php

namespace Database\Seeders;

// use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    //use WithoutModelEvents;

    public function run(): void
    {
        $this->call(UserRoleSeeder::class);
        $this->call(UserSeeder::class);

        $this->call(LanguageSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(TagSeeder::class);

        $this->call(TrendSeeder::class);
        $this->call(LocationSeeder::class);
    }
}
