<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $user_id = Auth::id();
        $events = Event::with('category')
            ->when($validated['from'] ?? null, function ($query, $from) {
                $query->where('start_at', '>=', Carbon::parse($from)->startOfDay());
            })
            ->when($validated['to'] ?? null, function ($query, $to) {
                $query->where('start_at', '<=', Carbon::parse($to)->endOfDay());
            })
            ->whereHas('participants', function ($query) use ($user_id) {
                $query->where('users.id', $user_id);
            })->get();
        return response()->json($events, Response::HTTP_OK);
    }

    public function store(StoreEventRequest $request)
    {
        $eventData = array_merge($request->validated(), [
            'user_id'     => Auth::id(),
            'repeat_code' => null,
        ]);
        $event = Event::create($eventData);
        $event = $event->fresh();
        return response()->json($event, Response::HTTP_CREATED);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }
        $event->update($request->validated());
        return response()->json($event, Response::HTTP_OK);
    }

    public function destroy(Event $event)
    {
        if ($event->user_id !== Auth::id()) {
            return response()->json(null, Response::HTTP_FORBIDDEN);
        }
        $event->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
