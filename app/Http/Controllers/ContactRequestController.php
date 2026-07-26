<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest\StoreContactRequest;
use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactRequestController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'workflow_state' => 'sometimes|in:pending,accepted,rejected'
        ]);
        $user = $request->user();
        $state = $request->query('workflow_state');
        $sentQuery = $user->sentContactRequests()
            ->select('contact_requests.*')
            ->selectRaw("'sent' as direction")
            ->with('receiver');
        $receivedQuery = $user->receivedContactRequests()
            ->select('contact_requests.*')
            ->selectRaw("'received' as direction")
            ->with('sender');
        if ($state) {
            $sentQuery->where('workflow_state', $state);
            $receivedQuery->where('workflow_state', $state);
        }
        $requests = $sentQuery->unionAll($receivedQuery)->get();
        return response()->json($requests, Response::HTTP_OK);
    }

    public function store(StoreContactRequest $request)
    {
        $user = $request->user();
        $receiver_id = $request->integer('receiver_id');
        $existing_request = ContactRequest::betweenUsers($user->id, $receiver_id)->first();
        if ($existing_request) {
            if ($existing_request->receiver_id === $user->id && $existing_request->workflow_state === 'pending') {
                $existing_request->update(['workflow_state' => 'accepted']);
                return response()->json($existing_request->fresh(), Response::HTTP_OK);
            }
            return response()->json($existing_request->fresh(), Response::HTTP_OK);
        }
        $contact_request = ContactRequest::create([
            'sender_id'      => $user->id,
            'receiver_id'    => $receiver_id,
            'workflow_state' => 'pending',
        ]);
        return response()->json($contact_request->fresh(), Response::HTTP_CREATED);
    }

    public function update(Request $request, int $sender_id)
    {
        $request->validate(['workflow_state' => 'required|in:accepted,rejected']);
        $sender = User::findOrFail($sender_id);
        $user = $request->user();
        $new_state = $request->input('workflow_state');
        $contact_request = ContactRequest::fromTo($sender->id, $user->id)
            ->where('workflow_state', 'pending')
            ->first();
        if (!$contact_request) {
            return response()->json(null, Response::HTTP_NOT_FOUND);
        }
        $contact_request->update(['workflow_state' => $new_state]);
        return response()->json($contact_request->fresh(), Response::HTTP_OK);
    }

    public function destroy(Request $request, int $contactId)
    {
        $user = $request->user();
        $contact_request = ContactRequest::betweenUsers($user->id, $contactId)->first();
        if (!$contact_request) {
            return response()->json(null, Response::HTTP_NOT_FOUND);
        }
        $contact_request->update(['deleted_by' => $user->id]);
        $contact_request->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
