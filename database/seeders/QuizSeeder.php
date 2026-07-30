<?php

namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Quiz;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizSeeder extends Seeder
{
    private const TOTAL_SCHEDULE_DAYS = 365;

    private const TEMPLATE_REUSE_GAP = 5;

    private array $templateHistory = [];

    private int $completedPeriods = 0;

    private ?Carbon $firstScheduleDate = null;

    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            Quiz::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='quizzes'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            Quiz::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            Quiz::truncate();
        }

        Language::query()
            ->orderBy('id')
            ->get()
            ->each(fn (Language $language) => $this->seedQuiz($language));
    }

    private function seedQuiz(Language $language): void
    {
        $quizTemplates = collect(SeederHelper::quizSeederData($language->code));

        if ($quizTemplates->isEmpty()) {
            return;
        }

        $startDate = now()->startOfDay();

        $this->firstScheduleDate ??= $startDate->copy();

        $lastAllowedDate = $this->firstScheduleDate
            ->copy()
            ->addDays(self::TOTAL_SCHEDULE_DAYS - 1);

        $duration = $this->calculateDuration($quizTemplates->count());

        $currentDate = $startDate->copy();

        while (true) {
            if ($currentDate->copy()->addDays($duration - 1)->gt($lastAllowedDate)) {
                break;
            }

            if ($this->isTransitionPeriod()) {
                $currentDate = $this->adjustTransitionDate($currentDate);

                if (!$this->createQuizPeriod(
                    $language,
                    $quizTemplates,
                    $currentDate,
                    $duration,
                    true,
                    $lastAllowedDate
                )) {
                    break;
                }

                if (!$this->createQuizPeriod(
                    $language,
                    $quizTemplates,
                    $currentDate,
                    $duration,
                    true,
                    $lastAllowedDate
                )) {
                    break;
                }
            } else {
                if (!$this->createQuizPeriod(
                    $language,
                    $quizTemplates,
                    $currentDate,
                    $duration,
                    true,
                    $lastAllowedDate
                )) {
                    break;
                }

                if ($this->shouldCreateFallback()) {
                    $this->createQuizPeriod(
                        $language,
                        $quizTemplates,
                        $currentDate,
                        $duration,
                        false,
                        $lastAllowedDate
                    );
                }
            }

            $currentDate->addDays($duration);

            $this->completedPeriods++;
        }
    }

    private function calculateDuration(int $templateCount): int
    {
        $availableCycles = max(
            1,
            intdiv(self::TOTAL_SCHEDULE_DAYS, max(1, $templateCount))
        );

        $duration = (int) ceil(
            self::TOTAL_SCHEDULE_DAYS / max(1, $templateCount * $availableCycles)
        );

        return max(1, $duration);
    }

    private function createQuizPeriod(
        Language $language,
        Collection $quizTemplates,
        Carbon $startDate,
        int $duration,
        bool $isActive,
        Carbon $lastAllowedDate
    ): bool {
        $endDate = $startDate->copy()->addDays($duration - 1);

        if ($endDate->gt($lastAllowedDate)) {
            return false;
        }

        $quizTemplate = $this->selectEligibleQuiz($quizTemplates);

        $this->createQuiz(
            $language,
            $quizTemplate,
            $startDate,
            $endDate,
            $isActive
        );

        return true;
    }

    private function selectEligibleQuiz(Collection $quizTemplates): array
    {
        $availableTemplates = $quizTemplates->filter(
            fn (array $template, int $index) => !in_array(
                $index,
                $this->templateHistory,
                true
            )
        );

        if ($availableTemplates->isEmpty()) {
            $availableTemplates = $quizTemplates;
        }

        $selectedIndex = $availableTemplates->keys()->first();

        $this->templateHistory[] = $selectedIndex;

        $this->templateHistory = array_slice(
            $this->templateHistory,
            -self::TEMPLATE_REUSE_GAP
        );

        return $availableTemplates->first();
    }

    private function createQuiz(
        Language $language,
        array $quizData,
        Carbon $startDate,
        Carbon $endDate,
        bool $isActive
    ): void {
        Quiz::factory()
            ->state([
                'language_id' => $language->id,
                'name' => $quizData['name'],
                'brief' => $quizData['brief'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => $isActive,
                'show_bellow_event' => false,
            ])
            ->create();
    }

    private function shouldCreateFallback(): bool
    {
        return random_int(1, 100) <= 15;
    }

    private function isTransitionPeriod(): bool
    {
        return $this->completedPeriods > 0
            && $this->completedPeriods % 10 === 9;
    }

    private function adjustTransitionDate(Carbon $date): Carbon
    {
        if ($this->firstScheduleDate === null) {
            return $date;
        }

        $firstDayParity = $this->firstScheduleDate->day % 2;

        if ($date->day % 2 !== $firstDayParity) {
            return $date->addDay();
        }

        return $date;
    }
}
