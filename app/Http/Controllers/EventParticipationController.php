<?php

namespace App\Http\Controllers;

use App\Models\CustomPivots\EventEnroll;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventParticipationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
        ]);
        $user = Auth::user();
        $event = Event::findOrFail($validated['event_id']);
        if ($event->visibility === 'private') {
            abort(403);
        }
        if ($event->visibility === 'contacts') {
            $is_contact = in_array($event->user_id, $user->getContactIds());
            if (! $is_contact && $event->user_id !== $user->id) {
                abort(403);
            }
        }
        $existing_participation = EventEnroll::withTrashed()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing_participation) {
            if (! $existing_participation->trashed()) {
                return response()->json($existing_participation, Response::HTTP_OK);
            }
            $existing_participation->restore();
            $existing_participation->update([
                'workflow_state' => 'confirmed',
                'role' => 'attendee',
            ]);
            return response()->json($existing_participation, Response::HTTP_OK);
        }
        $participation = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        return response()->json($participation, Response::HTTP_CREATED);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'workflow_state' => ['required', 'in:confirmed,declined,pending'],
        ]);
        $user_id = Auth::id();
        $participation = EventEnroll::where('event_id', $request->event_id)
            ->where('user_id', $user_id)
            ->firstOrFail();
        $participation->update([
            'workflow_state' => $validated['workflow_state'],
        ]);
        return response()->json($participation, Response::HTTP_OK);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'event_id' => ['required', 'exists:events,id'],
        ]);
        $event = Event::findOrFail($request->event_id);
        $user_id = Auth::id();
        if ($event->user_id === $user_id) {
            abort(403);
        }
        $participation = EventEnroll::where('event_id', $event->id)
            ->where('user_id', $user_id)
            ->firstOrFail();
        $participation->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function removeParticipant(Request $request, int $event_id, int $user_id)
    {
        $event = Event::findOrFail($event_id);
        $is_owner = $event->user_id === Auth::id();
        $is_admin = EventEnroll::where('event_id', $event->id)
            ->where('user_id', Auth::id())
            ->where('role', 'admin')
            ->exists();
        if (!$is_owner && !$is_admin) {
            abort(403);
        }
        if ($event->user_id === $user_id) {
            abort(403);
        }
        $participation = EventEnroll::where('event_id', $event->id)
            ->where('user_id', $user_id)
            ->firstOrFail();
        $participation->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function addParticipant(int $event_id, int $user_id)
    {
        $event = Event::findOrFail($event_id);
        $targetUser = User::findOrFail($user_id);
        $isOwner = $event->user_id === Auth::id();
        $isAdmin = EventEnroll::where('event_id', $event->id)
            ->where('user_id', Auth::id())
            ->where('role', 'admin')
            ->exists();
        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }
        if (! in_array($targetUser->id, Auth::user()->getContactIds())) {
            abort(403);
        }
        $participation = EventEnroll::withTrashed()
            ->where('event_id', $event->id)
            ->where('user_id', $targetUser->id)
            ->first();
        if ($participation) {
            if (!$participation->trashed()) {
                return response()->json($participation, Response::HTTP_OK);
            }
            $participation->restore();
            $participation->update([
                'workflow_state' => 'pending',
                'role' => 'attendee',
            ]);
            return response()->json($participation, Response::HTTP_OK);
        }
        $participation = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        return response()->json($participation, Response::HTTP_CREATED);
    }
}
