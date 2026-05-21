<?php
namespace App\Jobs;

use App\Models\Event;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteEventRelationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function uniqueId(): string
    {
        return "delete-relations-event-{$this->eventId}";
    }

    public function retryAfter()
    {
        return 60;
    }

    public function backoff()
    {
        return [61, 123, 185];
    }

    public function handle(): void
    {
        $event = Event::find($this->eventId);

        if ($event && ($event->activityLogs()->exists()) || ($event->getMedia($event->media_collection_name)->count() > 0) || ($event->newses()->exists())) {
            try {

                DB::transaction(function () use ($event) {
                    if ($event->activityLogs()->exists()) {
                        $event->activityLogs()->delete();
                    }

                    if ($event->getMedia($event->media_collection_name)->count() > 0) {
                        $event->clearMediaCollection($event->media_collection_name);
                    }

                    if ($event->newses()->exists()) {
                        $event->newses()->delete();
                    }
                });

            } catch (Exception $ex) {

                Log::error("Fail to delete event relations.", [
                    'exception' => $ex,
                ]);

                throw $ex;
            }
        }
    }
}
