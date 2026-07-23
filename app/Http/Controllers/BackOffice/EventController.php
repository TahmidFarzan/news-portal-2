<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Services\BackOffice\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class EventController extends Controller
{
    protected EventService $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(Request $request)
    {
        $event = $this->eventService->new();
        Gate::authorize('viewAny', $event);

        $events = $this->eventService->search($request);

        return Inertia::render('back-office/events/Index', [
            'events' => $events,
        ]);
    }

    public function details(string $slug)
    {
        $event = $this->eventService->find($slug);

        Gate::authorize('view', $event);

        return Inertia::render('back-office/events/Details', [
            'event' => $event,
        ]);
    }

    public function create()
    {
        $event = $this->eventService->new();
        Gate::authorize('create', $event);

        return Inertia::render('back-office/events/Create', [
            'event' => $event,
        ]);
    }

    public function edit(string $slug)
    {
        $event = $this->eventService->find($slug);

        Gate::authorize('update', $event);

        return Inertia::render('back-office/events/Create', [
            'event' => $event,
        ]);
    }

    public function save(EventRequest $request)
    {
        $event = $this->eventService->new();
        Gate::authorize('create', $event);

        $result = $this->eventService->save($request, $event);

        return to_route('back-office.events.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(EventRequest $request, string $slug)
    {
        $event = $this->eventService->find($slug);

        Gate::authorize('update', $event);

        $result = $this->eventService->save($request, $event);

        return to_route('back-office.events.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $event = $this->eventService->find($slug);

        Gate::authorize('delete', $event);

        $result = $this->eventService->delete($event);

        return to_route('back-office.events.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
