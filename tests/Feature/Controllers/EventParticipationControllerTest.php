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
}
