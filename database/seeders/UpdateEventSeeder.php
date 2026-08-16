<?php

namespace Database\Seeders;

use App\Helpers\EventHelper;
use App\Models\Event;
use App\Models\Language;
use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UpdateEventSeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::query()->get();

        foreach ($languages as $language) {
            $events = Event::query()
                ->where('language_id', $language->id)
                ->orderBy('id')
                ->get();

            if ($events->isEmpty()) {
                continue;
            }

            $startDate = now()->startOfDay();
            $endOfYear = $startDate->copy()->addYear()->subDay()->endOfDay();

            $totalEvents = $events->count();
            $remainingDays = $startDate->diffInDays($endOfYear) + 1;

            foreach ($events as $index => $event) {
                $remainingEvents = $totalEvents - $index;
                $duration = (int) ceil($remainingDays / $remainingEvents);

                $eventEndDate = $startDate
                    ->copy()
                    ->addDays($duration - 1)
                    ->endOfDay();

                if ($eventEndDate->greaterThan($endOfYear)) {
                    $eventEndDate = $endOfYear->copy();
                }

                $event->update([
                    'position' => fake()->randomElement([
                        EventHelper::POSITION_TOP,
                        EventHelper::POSITION_BOTTOM,
                    ]),
                    'start_date' => $startDate->copy()->startOfDay(),
                    'end_date' => $eventEndDate->copy()->endOfDay(),
                    'is_active' => true,
                ]);

                if (!$event->news()->exists()) {
                    $news = News::query()
                        ->where('language_id', $language->id)
                        ->whereNull('event_id')
                        ->where('is_published', true)
                        ->inRandomOrder()
                        ->limit(15)
                        ->get();

                    if ($news->isNotEmpty()) {
                        $news->update([
                            'event_id' => $event->id,
                        ]);
                    }
                }

                $startDate = $eventEndDate
                    ->copy()
                    ->addDay()
                    ->startOfDay();

                $remainingDays = $startDate->greaterThan($endOfYear)
                    ? 0
                    : $startDate->diffInDays($endOfYear) + 1;
            }
        }
    }
}
