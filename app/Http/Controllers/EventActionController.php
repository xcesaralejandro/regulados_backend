<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\EventAction\StoreEventActionRequest;
use App\Http\Requests\EventAction\UpdateEventActionRequest;
use App\Models\Event;
use App\Models\EventAction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class EventActionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['event_id' => 'required|exists:events,id']);
        $event = Event::where('id', $request->event_id)->whereParticipant(Auth::id())->firstOr(fn() => abort(403));
        $actions = EventAction::where('event_id', $event->id)->orderBy('order', 'asc')->get();
        return response()->json($actions, Response::HTTP_OK);
    }

    public function store(StoreEventActionRequest $request)
    {
        $event = Event::where('id', $request->event_id)->whereParticipant(Auth::id())->firstOr(fn() => abort(403));
        $fields = array_merge($request->validated(), [
            'user_id' => Auth::id(),
            'source' => 'user',
        ]);
        $action = EventAction::create($fields);
        $action = $action->refresh();
        return response()->json($action, Response::HTTP_CREATED);
    }

    public function update(UpdateEventActionRequest $request, int $event_action_id)
    {
        $action = EventAction::findOrFail($event_action_id);
        Event::where('id', $action->event_id)->whereParticipant(Auth::id())->firstOr(fn() => abort(403));
        $fields = $request->validated();
        if ($request->has('completed_at')) {
            $fields['completed_by'] = $request->filled('completed_at') ? Auth::id() : null;
        }
        $action->update($fields);
        $action->refresh();
        return response()->json($action, Response::HTTP_OK);
    }

    public function destroy(int $event_action_id)
    {
        $action = EventAction::findOrFail($event_action_id);
        Event::where('id', $action->event_id)->whereParticipant(Auth::id())->firstOr(fn() => abort(403));
        $action->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
