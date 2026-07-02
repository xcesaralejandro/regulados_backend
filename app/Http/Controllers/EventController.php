<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;


class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('category')->where('user_id', Auth::id())->get();
        return response()->json($events, Response::HTTP_OK);
    }

    public function store(StoreEventRequest $request)
    {
        $eventData = array_merge($request->validated(), [
            'user_id'     => Auth::id(),
            'repeat_code' => null,
        ]);
        $event = Event::create($eventData);
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
