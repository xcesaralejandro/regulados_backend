<?php

namespace Tests\Feature\Controllers;

use App\Models\ContactRequest;
use App\Models\Program;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactRequestsControllerTest extends TestCase
{

    public function test_index_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare & Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(401);
    }

    public function test_index_returns_200_and_empty_array_when_user_has_no_contact_requests(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_index_returns_200_with_expected_exact_structure(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        $receiver = User::factory()->create(['program_id' => $program->id]);
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'workflow_state',
                'created_at',
                'updated_at',
                'direction',
                'user' => [
                    'id',
                    'semester',
                    'name',
                    'surname',
                    'email',
                    'phone',
                    'instagram',
                    'discord',
                    'birthdate',
                    'avatar',
                    'program' => [
                        'id',
                        'name',
                        'university' => [
                            'id',
                            'name',
                            'short_name',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_index_returns_200_and_exact_json_payload_matching_records(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        $receiver = User::factory()->create([
            'program_id' => $program->id,
            'phone' => null,
            'instagram' => null,
            'avatar' => null,
        ]);
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([
            [
                'id' => $contactRequest->id,
                'workflow_state' => 'pending',
                'created_at' => $contactRequest->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $contactRequest->updated_at->format('Y-m-d H:i:s'),
                'direction' => 'sent',
                'user' => [
                    'id' => $receiver->id,
                    'semester' => $receiver->semester,
                    'name' => $receiver->name,
                    'surname' => $receiver->surname,
                    'email' => $receiver->email,
                    'phone' => null,
                    'instagram' => null,
                    'discord' => $receiver->discord,
                    'birthdate' => $receiver->birthdate,
                    'avatar' => null,
                    'program' => [
                        'id' => $program->id,
                        'name' => $program->name,
                        'university' => [
                            'id' => $university->id,
                            'name' => $university->name,
                            'short_name' => $university->short_name,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_index_sets_direction_as_sent_when_authenticated_user_is_sender(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $contactRequest->id,
            'direction' => 'sent',
        ]);
    }

    public function test_index_sets_direction_as_received_when_authenticated_user_is_receiver(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $contactRequest->id,
            'direction' => 'received',
        ]);
    }

    public function test_index_loads_receiver_as_user_relation_when_authenticated_user_is_sender(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('0.user.id', $receiver->id);
    }

    public function test_index_loads_sender_as_user_relation_when_authenticated_user_is_receiver(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('0.user.id', $sender->id);
    }

    public function test_index_unsets_sender_relation_from_response(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonMissingPath('0.sender');
    }

    public function test_index_unsets_receiver_relation_from_response(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonMissingPath('0.receiver');
    }

    public function test_index_excludes_contact_requests_unrelated_to_authenticated_user(): void
    {
        // Prepare
        $user = User::factory()->create();
        $unrelatedSender = User::factory()->create();
        $unrelatedReceiver = User::factory()->create();
        $unrelatedRequest = ContactRequest::factory()->create([
            'sender_id' => $unrelatedSender->id,
            'receiver_id' => $unrelatedReceiver->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0);
        $response->assertJsonMissing(['id' => $unrelatedRequest->id]);
    }

    public function test_index_returns_both_sent_and_received_requests(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser1 = User::factory()->create();
        $otherUser2 = User::factory()->create();
        $sentRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser1->id,
        ]);
        $receivedRequest = ContactRequest::factory()->create([
            'sender_id' => $otherUser2->id,
            'receiver_id' => $user->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['id' => $sentRequest->id]);
        $response->assertJsonFragment(['id' => $receivedRequest->id]);
    }

    public function test_index_filters_requests_by_pending_workflow_state(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $pendingRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'pending',
        ]);
        $acceptedRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests?workflow_state=pending');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $pendingRequest->id);
        $response->assertJsonPath('0.workflow_state', 'pending');
    }

    public function test_index_filters_requests_by_accepted_workflow_state(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $acceptedRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'accepted',
        ]);
        $rejectedRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'rejected',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests?workflow_state=accepted');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $acceptedRequest->id);
        $response->assertJsonPath('0.workflow_state', 'accepted');
    }

    public function test_index_filters_requests_by_rejected_workflow_state(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $rejectedRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'rejected',
        ]);
        $pendingRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests?workflow_state=rejected');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $rejectedRequest->id);
        $response->assertJsonPath('0.workflow_state', 'rejected');
    }

    public function test_index_returns_422_when_workflow_state_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests?workflow_state=invalid_status');
        // Assert
        $response->assertStatus(422);
    }

    public function test_index_attaches_validation_error_to_workflow_state_when_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/contact-requests?workflow_state=invalid_status');
        // Assert
        $response->assertJsonValidationErrors(['workflow_state']);
    }
}
