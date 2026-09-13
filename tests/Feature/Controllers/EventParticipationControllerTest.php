<?php

namespace Tests\Feature\Controllers;

use App\Models\ContactRequest;
use App\Models\CustomPivots\EventEnroll;
use App\Models\Event;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventParticipationControllerTest extends TestCase
{

    public function test_store_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(401);
    }

    public function test_store_returns_422_when_event_id_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', []);
        // Assert
        $response->assertStatus(422);
    }

    public function test_store_returns_422_when_event_id_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => 999999]);
        // Assert
        $response->assertStatus(422);
    }

    public function test_store_returns_403_when_event_visibility_is_private(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['visibility' => 'private']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(403);
    }

    public function test_store_returns_403_when_event_visibility_is_contacts_and_user_is_not_contact(): void
    {
        // Prepare
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id, 'visibility' => 'contacts']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(403);
    }

    public function test_store_creates_participation_and_returns_201_when_event_visibility_is_public(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['visibility' => 'public']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_store_creates_participation_when_user_is_sender_of_accepted_contact_request(): void
    {
        // Prepare
        $user = User::factory()->create();
        $owner = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $user->id,
            'receiver_id' => $owner->id,
            'workflow_state' => 'accepted',
        ]);
        $event = Event::factory()->create(['user_id' => $owner->id, 'visibility' => 'contacts']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_store_creates_participation_when_user_is_receiver_of_accepted_contact_request(): void
    {
        // Prepare
        $user = User::factory()->create();
        $owner = User::factory()->create();
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $user->id,
            'workflow_state' => 'accepted',
        ]);
        $event = Event::factory()->create(['user_id' => $owner->id, 'visibility' => 'contacts']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_store_returns_exact_json_structure_on_creation(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['visibility' => 'public']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'workflow_state',
            'role',
        ]);
    }

    public function test_store_returns_exact_keys_matching_fresh_record_on_creation(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['visibility' => 'public']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(201);
        $enroll = EventEnroll::where('event_id', $event->id)->where('user_id', $user->id)->first();
        $response->assertExactJson([
            'id' => $enroll->id,
            'workflow_state' => $enroll->workflow_state,
            'role' => $enroll->role,
        ]);
    }

    public function test_store_returns_200_and_does_not_duplicate_when_participation_already_exists(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['visibility' => 'public']);
        $existing = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('event_enrolls', 2);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('event_enrolls', [
            'id' => $existing->id,
            'event_id' => $event->id,
            'user_id' => $user->id,
            'role' => 'attendee',
            'workflow_state' => 'confirmed',
        ]);
        $response->assertExactJson([
            'id' => $existing->id,
            'workflow_state' => $existing->workflow_state,
            'role' => $existing->role,
        ]);
    }

    public function test_store_restores_soft_deleted_participation_and_returns_200(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['visibility' => 'public']);
        $trashedEnroll = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'declined',
            'role' => 'attendee',
        ]);
        $trashedEnroll->delete();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->postJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('event_enrolls', 2);

        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('event_enrolls', [
            'id' => $trashedEnroll->id,
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_update_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_update_returns_422_when_event_id_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_event_id_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => 999999,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_workflow_state_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_workflow_state_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'invalid_state',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_404_when_user_has_no_participation_in_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(404);
    }

    public function test_update_returns_404_when_participation_belongs_to_another_user(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $otherUser->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(404);
    }

    public function test_update_returns_404_when_participation_is_soft_deleted(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $participation = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        $participation->delete();
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(404);
    }

    public function test_update_modifies_workflow_state_to_confirmed_and_returns_200(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
        ]);
    }

    public function test_update_modifies_workflow_state_to_declined_and_returns_200(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'declined',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'declined',
        ]);
    }

    public function test_update_modifies_workflow_state_to_pending_and_returns_200(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'pending',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
        ]);
    }

    public function test_update_returns_expected_json_structure(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'workflow_state',
            'role',
        ]);
    }

    public function test_update_returns_exact_json_payload_matching_fresh_record(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $participation = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson($participation->fresh()->toArray());
    }

    public function test_update_does_not_modify_unauthorized_fields(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ];
        // Execute
        $response = $this->putJson('/api/event-participations', $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'role' => 'attendee',
        ]);
    }

    public function test_destroy_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(401);
    }

    public function test_destroy_returns_422_when_event_id_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', []);
        // Assert
        $response->assertStatus(422);
    }

    public function test_destroy_returns_422_when_event_id_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => 999999]);
        // Assert
        $response->assertStatus(422);
    }

    public function test_destroy_returns_403_when_authenticated_user_is_event_owner(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_does_not_delete_participation_when_user_is_event_owner(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'deleted_at' => null,
        ]);
    }

    public function test_destroy_returns_404_when_user_has_no_enrollment_in_event(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_returns_404_when_participation_belongs_to_another_user(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $otherUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_returns_404_when_participation_is_already_soft_deleted(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        $participation = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        $participation->delete();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_returns_204_when_participation_is_successfully_deleted(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertStatus(204);
    }

    public function test_destroy_returns_empty_content_on_success(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-participations', ['event_id' => $event->id]);
        // Assert
        $response->assertNoContent();
    }

    public function test_remove_participant_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $targetUser = User::factory()->create();
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(401);
    }

    public function test_remove_participant_returns_404_when_event_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/999999/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(404);
    }

    public function test_remove_participant_returns_403_when_user_is_neither_owner_nor_admin(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $regularUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $regularUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($regularUser);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_remove_participant_returns_403_when_trying_to_remove_the_event_owner(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $adminUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $adminUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        Sanctum::actingAs($adminUser);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$owner->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_remove_participant_returns_403_when_owner_tries_to_remove_themselves(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$owner->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_remove_participant_returns_404_when_target_user_has_no_participation_in_event(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(404);
    }

    public function test_remove_participant_returns_404_when_target_user_participation_is_already_soft_deleted(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        $participation = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        $participation->delete();
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(404);
    }

    public function test_remove_participant_returns_204_when_caller_is_event_owner(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(204);
    }

    public function test_remove_participant_returns_204_when_caller_is_event_admin_but_no_owner(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $adminUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $adminUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($adminUser);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(204);
    }

    public function test_remove_participant_returns_empty_content_on_success(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->deleteJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertNoContent();
    }




















    public function test_add_participant_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $targetUser = User::factory()->create();
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(401);
    }

    public function test_add_participant_returns_404_when_event_does_not_exist(): void
    {
        // Prepare
        $caller = User::factory()->create();
        $targetUser = User::factory()->create();
        Sanctum::actingAs($caller);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/999999/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(404);
    }

    public function test_add_participant_returns_404_when_target_user_does_not_exist(): void
    {
        // Prepare
        $caller = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $caller->id]);
        Sanctum::actingAs($caller);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/999999");
        // Assert
        $response->assertStatus(404);
    }

    public function test_add_participant_returns_403_when_caller_is_neither_owner_nor_admin(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $caller = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $caller->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $caller->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($caller);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_add_participant_returns_403_when_target_user_is_not_a_contact(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_add_participant_returns_403_when_contact_request_is_pending(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'pending',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_add_participant_returns_403_when_contact_request_is_rejected(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'rejected',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_add_participant_creates_enrollment_and_returns_201_when_caller_is_event_owner(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_add_participant_creates_enrollment_and_returns_201_when_caller_is_event_admin(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $adminCaller = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $adminCaller->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        ContactRequest::factory()->create([
            'sender_id' => $adminCaller->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($adminCaller);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_add_participant_creates_enrollment_when_caller_is_receiver_of_contact_request(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $targetUser->id,
            'receiver_id' => $owner->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_enrolls', [
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_add_participant_returns_expected_json_structure_on_creation(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'workflow_state',
            'role',
        ]);
    }

    public function test_add_participant_returns_exact_json_matching_fresh_record_on_creation(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(201);
        $enroll = EventEnroll::where('event_id', $event->id)->where('user_id', $targetUser->id)->first();
        $response->assertExactJson($enroll->fresh()->toArray());
    }

    public function test_add_participant_returns_200_and_does_not_duplicate_when_participation_already_exists(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        $existingEnroll = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'attendee',
        ]);
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('event_enrolls', 2);
        $response->assertExactJson($existingEnroll->fresh()->toArray());
    }

    public function test_add_participant_restores_soft_deleted_participation_and_returns_200(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        $trashedEnroll = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        $trashedEnroll->delete();
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('event_enrolls', 2);
        $this->assertDatabaseHas('event_enrolls', [
            'id' => $trashedEnroll->id,
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'pending',
            'role' => 'attendee',
            'deleted_at' => null,
        ]);
    }

    public function test_add_participant_returns_exact_json_matching_fresh_record_on_restoration(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $targetUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        ContactRequest::factory()->create([
            'sender_id' => $owner->id,
            'receiver_id' => $targetUser->id,
            'workflow_state' => 'accepted',
        ]);
        $trashedEnroll = EventEnroll::create([
            'event_id' => $event->id,
            'user_id' => $targetUser->id,
            'workflow_state' => 'confirmed',
            'role' => 'admin',
        ]);
        $trashedEnroll->delete();
        Sanctum::actingAs($owner);
        // Execute
        $response = $this->postJson("/api/admin/event-participations/{$event->id}/users/{$targetUser->id}");
        // Assert
        $response->assertStatus(200);
        $trashedEnroll->refresh();
        $response->assertExactJson($trashedEnroll->fresh()->toArray());
    }
}
