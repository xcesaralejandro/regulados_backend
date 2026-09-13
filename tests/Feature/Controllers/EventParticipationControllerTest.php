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
}
