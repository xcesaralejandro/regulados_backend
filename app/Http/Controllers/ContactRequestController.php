<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest\StoreContactRequest;
use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactRequestController extends Controller
{

    private function formatContactRequest(ContactRequest $contactRequest, int $userId): ContactRequest
    {
        $isSent = $contactRequest->sender_id === $userId;
        $contactRequest->setAttribute('direction', $isSent ? 'sent' : 'received');
        $contactRequest->setRelation('user', $isSent ? $contactRequest->receiver : $contactRequest->sender);
        $contactRequest->unsetRelation('sender');
        $contactRequest->unsetRelation('receiver');
        return $contactRequest;
    }

    public function index(Request $request)
    {
        $request->validate(['workflow_state' => 'sometimes|in:pending,accepted,rejected']);
        $user = $request->user();
        $requests = ContactRequest::query()
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->when(
                $request->query('workflow_state'),
                fn($query, $state) => $query->where('workflow_state', $state)
            )
            ->with(['sender', 'receiver'])
            ->get();
        $requests->each(fn(ContactRequest $contactRequest) => $this->formatContactRequest($contactRequest, $user->id));
        return response()->json($requests, Response::HTTP_OK);
    }

    public function store(StoreContactRequest $request)
    {
        $user = $request->user();
        $receiverId = $request->integer('receiver_id');
        $existingRequest = ContactRequest::betweenUsers($user->id, $receiverId)->first();
        if ($existingRequest) {
            if ($existingRequest->receiver_id === $user->id && $existingRequest->workflow_state === 'pending') {
                $existingRequest->update(['workflow_state' => 'accepted']);
            }
            $existingRequest->load(['sender', 'receiver']);
            return response()->json(
                $this->formatContactRequest($existingRequest, $user->id),
                Response::HTTP_OK
            );
        }
        $contactRequest = ContactRequest::create([
            'sender_id'      => $user->id,
            'receiver_id'    => $receiverId,
            'workflow_state' => 'pending',
        ]);
        $contactRequest->load(['sender', 'receiver']);
        return response()->json(
            $this->formatContactRequest($contactRequest, $user->id),
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, int $sender_id)
    {
        $request->validate(['workflow_state' => 'required|in:accepted,rejected']);
        $sender = User::findOrFail($sender_id);
        $user = $request->user();
        $new_state = $request->input('workflow_state');
        $contact_request = ContactRequest::fromTo($sender->id, $user->id)->first();
        if (!$contact_request) {
            return response()->json(null, Response::HTTP_NOT_FOUND);
        }
        $contact_request->update(['workflow_state' => $new_state]);
        $contact_request->load(['sender', 'receiver']);
        return response()->json(
            $this->formatContactRequest($contact_request, $user->id),
            Response::HTTP_OK
        );
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
