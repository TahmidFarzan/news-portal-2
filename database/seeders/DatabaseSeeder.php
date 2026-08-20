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
        $this->call(UserPermissionSeeder::class);

        $this->call(UserSeeder::class);

        $this->call(ThemeSeeder::class);

        $this->call(LanguageSeeder::class);
        $this->call(UpdateLanguageDefaultSeeder::class);

        $this->call(NewsTypeSeeder::class);
        $this->call(MenuTypeSeeder::class);

        $this->call(CategorySeeder::class);
        $this->call(TagSeeder::class);
        $this->call(TrendSeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(EventSeeder::class);
        $this->call(ContributorSeeder::class);

        $this->call(LocationMapInfoSeeder::class);

        $this->call(PageSeeder::class);
        $this->call(MenuSeeder::class);

        $this->call(GoogleAdSeeder::class);

        $this->call(NewsSeeder::class);
        $this->call(NewsPlacementSeeder::class);
        $this->call(BreakingNewsSeeder::class);
        $this->call(RelatedNewsSyncSeeder::class);
        $this->call(RelevantNewsSyncSeeder::class);
        $this->call(NewsTagSyncSeeder::class);
        $this->call(NewsContributorSyncSeeder::class);
        $this->call(NewsMediaSeeder::class);

        $this->call(UpdateEventSeeder::class);


        $this->call(SurveySeeder::class);
        $this->call(SurveyQuestionSeeder::class);

        $this->call(QuizSeeder::class);
        $this->call(QuizQuestionSeeder::class);
        $this->call(QuizQuestionOptionSeeder::class);
        $this->call(QuizUpdateForShowResultAndMaxWinnerSeeder::class);
        $this->call(QuizParticipantSeeder::class);
    }
}
