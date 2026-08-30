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

    public function test_store_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $payload = ['receiver_id' => 1];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_store_returns_422_when_receiver_id_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_store_returns_422_when_receiver_id_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => 999999];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_store_returns_422_when_user_adds_themselves_as_contact(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $user->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_store_creates_new_contact_request_and_returns_201_when_none_exists(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('contact_requests', [
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'pending',
        ]);
    }

    public function test_store_returns_exact_json_structure_on_successful_creation(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
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
        ]);
    }

    public function test_store_returns_exact_json_matching_created_record_from_fresh_instance(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create();
        $receiver = User::factory()->create(['program_id' => $program->id]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(201);
        $contactRequest = ContactRequest::first()->fresh();
        $freshReceiver = $receiver->fresh(['program.university']);
        $response->assertExactJson([
            'workflow_state' => 'pending',
            'updated_at' => $contactRequest->updated_at->format('Y-m-d H:i:s'),
            'created_at' => $contactRequest->created_at->format('Y-m-d H:i:s'),
            'id' => $contactRequest->id,
            'direction' => 'sent',
            'user' => [
                'id' => $freshReceiver->id,
                'semester' => $freshReceiver->semester,
                'name' => $freshReceiver->name,
                'surname' => $freshReceiver->surname,
                'email' => $freshReceiver->email,
                'phone' => $freshReceiver->phone,
                'instagram' => $freshReceiver->instagram,
                'discord' => $freshReceiver->discord,
                'birthdate' => $freshReceiver->birthdate,
                'avatar' => $freshReceiver->avatar,
                'program' => [
                    'id' => $freshReceiver->program->id,
                    'name' => $freshReceiver->program->name,
                    'university' => [
                        'id' => $freshReceiver->program->university->id,
                        'name' => $freshReceiver->program->university->name,
                        'short_name' => $freshReceiver->program->university->short_name,
                    ],
                ],
            ],
        ]);
    }

    public function test_store_updates_existing_request_to_accepted_when_has_pending_request_response(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $existingRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $sender->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_requests', [
            'id' => $existingRequest->id,
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'accepted',
        ]);
    }

    public function test_store_returns_200_without_modifying_state_when_user_is_original_sender(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $existingRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_requests', [
            'id' => $existingRequest->id,
            'workflow_state' => 'pending',
        ]);
    }

    public function test_store_returns_200_without_modifying_state_when_request_is_already_accepted(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $existingRequest = ContactRequest::factory()->create([
            'sender_id' => $receiver->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_requests', [
            'id' => $existingRequest->id,
            'workflow_state' => 'accepted',
        ]);
    }

    public function test_store_does_not_create_duplicate_contact_request_when_one_already_exists(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $otherUser->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('contact_requests', 1);
    }

    public function test_store_is_idempotent_when_sender_submits_multiple_times_consecutively(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $firstResponse = $this->postJson('/api/contact-requests', $payload);
        $secondResponse = $this->postJson('/api/contact-requests', $payload);
        $thirdResponse = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $firstResponse->assertStatus(201);
        $secondResponse->assertStatus(200);
        $thirdResponse->assertStatus(200);
        $this->assertDatabaseCount('contact_requests', 1);
        $this->assertDatabaseHas('contact_requests', [
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'pending',
        ]);
    }

    public function test_store_does_not_mutate_rejected_request_when_sender_resubmits(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $existingRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'rejected',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('id', $existingRequest->id);
        $response->assertJsonPath('workflow_state', 'rejected');
        $this->assertDatabaseCount('contact_requests', 1);
        $this->assertDatabaseHas('contact_requests', [
            'id' => $existingRequest->id,
            'workflow_state' => 'rejected',
        ]);
    }

    public function test_store_does_not_mutate_rejected_request_when_original_receiver_submits(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $existingRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'rejected',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $sender->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('id', $existingRequest->id);
        $response->assertJsonPath('workflow_state', 'rejected');
        $this->assertDatabaseHas('contact_requests', [
            'id' => $existingRequest->id,
            'workflow_state' => 'rejected',
        ]);
    }

    public function test_store_creates_new_request_when_previous_request_was_soft_deleted(): void
    {
        // Prepare
        $user = User::factory()->create();
        $receiver = User::factory()->create();
        $deletedRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'workflow_state' => 'pending',
            'deleted_at' => now(),
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $receiver->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('id', fn($id) => $id !== $deletedRequest->id);

        $this->assertDatabaseCount('contact_requests', 2);
    }

    public function test_store_returns_correct_direction_payload_when_accepting_inbound_request(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $existingRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['receiver_id' => $sender->id];
        // Execute
        $response = $this->postJson('/api/contact-requests', $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $existingRequest->id,
            'workflow_state' => 'accepted',
            'direction' => 'received',
            'user' => [
                'id' => $sender->id,
            ],
        ]);
    }

    public function test_update_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $sender = User::factory()->create();
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_update_returns_404_when_sender_user_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson('/api/contact-requests/999999', $payload);
        // Assert
        $response->assertStatus(404);
    }

    public function test_update_returns_404_when_contact_request_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(404);
        $response->assertExactJson([]);
    }

    public function test_update_returns_422_when_workflow_state_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_attaches_validation_error_to_workflow_state_when_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertJsonValidationErrors(['workflow_state']);
    }

    public function test_update_returns_422_when_workflow_state_value_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'invalid_state'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_attaches_validation_error_to_workflow_state_when_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'pending'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertJsonValidationErrors(['workflow_state']);
    }

    public function test_update_returns_200_when_workflow_state_is_accepted(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(200);
    }

    public function test_update_persists_accepted_workflow_state_in_database(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $this->assertDatabaseHas('contact_requests', [
            'id' => $contactRequest->id,
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'accepted',
        ]);
    }

    public function test_update_returns_200_when_workflow_state_is_rejected(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'rejected'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(200);
    }

    public function test_update_persists_rejected_workflow_state_in_database(): void
    {
        // Prepare
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'rejected'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $this->assertDatabaseHas('contact_requests', [
            'id' => $contactRequest->id,
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'rejected',
        ]);
    }

    public function test_update_returns_exact_json_keys_and_values_matching_formatted_contact_request(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $sender = User::factory()->create(['program_id' => $program->id]);
        $user = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$sender->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $freshContactRequest = $contactRequest->fresh();
        $freshSender = $sender->fresh(['program.university']);
        $response->assertExactJson([
            'id' => $freshContactRequest->id,
            'workflow_state' => 'accepted',
            'created_at' => $freshContactRequest->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $freshContactRequest->updated_at->format('Y-m-d H:i:s'),
            'direction' => 'received',
            'user' => [
                'id' => $freshSender->id,
                'semester' => $freshSender->semester,
                'name' => $freshSender->name,
                'surname' => $freshSender->surname,
                'email' => $freshSender->email,
                'phone' => $freshSender->phone,
                'instagram' => $freshSender->instagram,
                'discord' => $freshSender->discord,
                'birthdate' => $freshSender->birthdate,
                'avatar' => $freshSender->avatar,
                'program' => [
                    'id' => $freshSender->program->id,
                    'name' => $freshSender->program->name,
                    'university' => [
                        'id' => $freshSender->program->university->id,
                        'name' => $freshSender->program->university->name,
                        'short_name' => $freshSender->program->university->short_name,
                    ],
                ],
            ],
        ]);
    }

    public function test_update_returns_404_when_request_owner_tries_to_update_his_request(): void
    {
        // Prepare
        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($user);
        $payload = ['workflow_state' => 'accepted'];
        // Execute
        $response = $this->putJson("/api/contact-requests/{$targetUser->id}", $payload);
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare & Execute
        $response = $this->deleteJson('/api/contact-requests/1');
        // Assert
        $response->assertStatus(401);
    }

    public function test_destroy_returns_404_when_contact_request_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/contact-requests/99999');
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_returns_404_when_contact_request_belongs_to_other_users(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUserA = User::factory()->create();
        $otherUserB = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $otherUserA->id,
            'receiver_id' => $otherUserB->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/contact-requests/{$otherUserB->id}");
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_updates_deleted_by_with_authenticated_user_id(): void
    {
        // Prepare
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $contact->id,
            'deleted_by' => null,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $this->deleteJson("/api/contact-requests/{$contact->id}");
        // Assert
        $this->assertDatabaseHas('contact_requests', [
            'id' => $contactRequest->id,
            'deleted_by' => $user->id,
        ]);
    }

    public function test_destroy_deletes_contact_request_when_user_is_sender(): void
    {
        // Prepare
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $contact->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/contact-requests/{$contact->id}");
        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('contact_requests', [
            'id' => $contactRequest->id,
        ]);
    }

    public function test_destroy_deletes_contact_request_when_user_is_receiver(): void
    {
        // Prepare
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $contactRequest = ContactRequest::factory()->create([
            'sender_id' => $contact->id,
            'receiver_id' => $user->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/contact-requests/{$contact->id}");
        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('contact_requests', [
            'id' => $contactRequest->id,
        ]);
    }

    public function test_destroy_returns_empty_content(): void
    {
        // Prepare
        $user = User::factory()->create();
        $contact = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $contact->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/contact-requests/{$contact->id}");
        // Assert
        $response->assertNoContent();
    }
}
