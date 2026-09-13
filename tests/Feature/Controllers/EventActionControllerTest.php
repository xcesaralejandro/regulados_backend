<?php

namespace Tests\Feature\Controllers;

use App\Models\Event;
use App\Models\EventAction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventActionControllerTest extends TestCase
{
    public function test_index_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare & Execute
        $response = $this->getJson('/api/event-actions');
        // Assert
        $response->assertStatus(401);
    }

    public function test_index_returns_422_when_event_id_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/event-actions');
        // Assert
        $response->assertStatus(422);
    }

    public function test_index_returns_404_when_event_id_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/event-actions?event_id=999999');
        // Assert
        $response->assertStatus(422);
    }

    public function test_index_returns_403_when_user_is_not_participant_of_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($otherUser->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_index_returns_200_and_empty_array_when_event_has_no_actions(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_index_returns_200_when_event_has_actions(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertStatus(200);
    }

    public function test_index_returns_exact_json_structure_for_actions(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $expectedStructure = [
            'id',
            'title',
            'description',
            'order',
            'completed_at',
            'source',
            'created_at',
            'updated_at',
        ];
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertJsonStructure(['*' => $expectedStructure]);
    }

    public function test_index_returns_actions_ordered_by_order_ascending(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $secondAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'order' => 2,
        ]);
        $firstAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'order' => 1,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('0.id', $firstAction->id);
        $response->assertJsonPath('1.id', $secondAction->id);
    }

    public function test_index_returns_only_actions_belonging_to_the_requested_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $event->participants()->attach($user->id);
        $otherEvent->participants()->attach($user->id);
        $action = EventAction::factory()->create(['event_id' => $event->id]);
        $otherAction = EventAction::factory()->create(['event_id' => $otherEvent->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $action->id]);
        $response->assertJsonMissing(['id' => $otherAction->id]);
    }

    public function test_index_returns_exact_json_matching_records(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $action = EventAction::factory()->create([
            'event_id' => $event->id,
            'title' => 'tres',
            'description' => 'tresxd',
            'order' => 1,
            'completed_at' => null,
            'source' => 'user',
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson("/api/event-actions?event_id={$event->id}");
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([
            $action->fresh()->toArray(),
        ]);
    }

    public function test_store_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $payload = [
            'event_id' => 1,
            'title' => 'Sample Action',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_store_returns_403_when_user_is_not_participant_of_the_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($otherUser->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Unauthorized Action',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(403);
    }

    public function test_store_creates_event_action_and_returns_201_with_valid_data(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'tres',
            'description' => 'tresxd',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_actions', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'title' => 'tres',
            'description' => 'tresxd',
            'order' => 1,
            'source' => 'user',
        ]);
    }

    public function test_store_returns_exact_json_structure(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Structure Action',
            'description' => 'Verifying keys',
            'order' => 1,
        ];
        $expectedKeys = [
            'id',
            'title',
            'description',
            'order',
            'completed_at',
            'source',
            'created_at',
            'updated_at',
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure($expectedKeys);
    }

    public function test_store_returns_exact_json_payload_from_refreshed_instance(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Exact Action',
            'description' => 'Testing exact response',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $action = EventAction::latest('id')->first();
        $response->assertExactJson($action->fresh()->toArray());
    }

    public function test_store_overwrites_injected_user_id_with_authenticated_user_id(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'user_id' => $otherUser->id,
            'title' => 'Spoof Attempt',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_actions', [
            'title' => 'Spoof Attempt',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('event_actions', [
            'title' => 'Spoof Attempt',
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_store_sets_source_to_user_ignoring_payload_value(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Source Action',
            'order' => 1,
            'source' => 'system',
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_actions', [
            'title' => 'Source Action',
            'source' => 'user',
        ]);
        $this->assertDatabaseMissing('event_actions', [
            'title' => 'Source Action',
            'source' => 'system',
        ]);
    }

    public function test_store_persists_nullable_description_as_null_when_explicitly_sent(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Action Without Description',
            'description' => null,
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_actions', [
            'event_id' => $event->id,
            'title' => 'Action Without Description',
            'description' => null,
        ]);
    }

    public function test_store_returns_422_when_event_id_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'title' => 'Action Missing Event',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_store_returns_422_when_event_id_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => 999999,
            'title' => 'Action Nonexistent Event',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_store_returns_422_when_title_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_store_attaches_validation_error_to_title_when_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_order_is_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Action Missing Order',
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_store_attaches_validation_error_to_order_when_missing(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Action Missing Order',
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertJsonValidationErrors(['order']);
    }

    public function test_store_creates_event_action_when_description_is_omitted(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Action Without Description Key',
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('event_actions', [
            'event_id' => $event->id,
            'title' => 'Action Without Description Key',
            'description' => null,
        ]);
    }

    public function test_store_returns_422_when_title_is_not_a_string(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => ['invalid', 'array'],
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_title_exceeds_maximum_length(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => str_repeat('a', 256),
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_description_is_not_a_string(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Action Invalid Description',
            'description' => ['invalid', 'array'],
            'order' => 1,
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    public function test_store_returns_422_when_order_is_not_an_integer(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        $payload = [
            'event_id' => $event->id,
            'title' => 'Action Invalid Order',
            'order' => 'not-an-integer',
        ];
        // Execute
        $response = $this->postJson('/api/event-actions', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order']);
    }

    public function test_update_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $eventAction = EventAction::factory()->create();
        $payload = ['title' => 'Updated Title'];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_update_returns_404_when_event_action_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['title' => 'Updated Title'];
        // Execute
        $response = $this->putJson('/api/event-actions/999999', $payload);
        // Assert
        $response->assertStatus(404);
    }

    public function test_update_returns_403_when_user_is_not_participant_of_the_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($otherUser->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['title' => 'Updated Title'];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(403);
    }

    public function test_update_modifies_event_action_and_returns_200_with_valid_data(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'order' => 1,
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'order' => 2,
        ];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_actions', [
            'id' => $eventAction->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'order' => 2,
        ]);
    }

    public function test_update_returns_exact_json_structure(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $expectedKeys = [
            'id',
            'title',
            'description',
            'order',
            'completed_at',
            'source',
            'created_at',
            'updated_at',
        ];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", ['title' => 'New Title']);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure($expectedKeys);
    }

    public function test_update_returns_exact_json_payload_from_refreshed_instance(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'title' => 'Before Refresh',
            'description' => 'Exact payload check',
            'order' => 1,
        ]);
        Sanctum::actingAs($user);
        $payload = [
            'title' => 'After Refresh',
            'order' => 5,
        ];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson($eventAction->fresh()->toArray());
    }

    public function test_update_sets_completed_by_to_authenticated_user_when_completed_at_is_filled(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'completed_at' => null,
            'completed_by' => null,
        ]);
        Sanctum::actingAs($user);
        $payload = ['completed_at' => '2026-09-13 16:00:00'];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_actions', [
            'id' => $eventAction->id,
            'completed_at' => '2026-09-13 16:00:00',
            'completed_by' => $user->id,
        ]);
    }

    public function test_update_sets_completed_by_to_null_when_completed_at_is_explicitly_null(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'completed_at' => '2026-09-13 10:00:00',
            'completed_by' => $user->id,
        ]);
        Sanctum::actingAs($user);
        $payload = ['completed_at' => null];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_actions', [
            'id' => $eventAction->id,
            'completed_at' => null,
            'completed_by' => null,
        ]);
    }

    public function test_update_does_not_change_completed_by_when_completed_at_is_not_sent(): void
    {
        // Prepare
        $user = User::factory()->create();
        $originalCompleter = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'completed_at' => '2026-09-10 12:00:00',
            'completed_by' => $originalCompleter->id,
        ]);
        Sanctum::actingAs($user);
        $payload = ['title' => 'Updated Title Only'];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_actions', [
            'id' => $eventAction->id,
            'completed_at' => '2026-09-10 12:00:00',
            'completed_by' => $originalCompleter->id,
        ]);
    }

    public function test_update_allows_nullable_description_to_be_persisted_as_null(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create([
            'event_id' => $event->id,
            'description' => 'Original Non Null Description',
        ]);
        Sanctum::actingAs($user);
        $payload = ['description' => null];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_actions', [
            'id' => $eventAction->id,
            'description' => null,
        ]);
    }

    public function test_update_ignores_unauthorized_fields_like_event_id(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = [
            'title' => 'Valid Title',
            'event_id' => $otherEvent->id,
        ];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('event_actions', [
            'id' => $eventAction->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_update_returns_422_when_title_is_provided_as_empty_string(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['title' => ''];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_title_is_not_a_string(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['title' => 123456];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_title_exceeds_max_length(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['title' => str_repeat('a', 256)];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_description_is_not_a_string(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['description' => ['invalid', 'array']];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_order_is_not_an_integer(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['order' => 'not-an-integer'];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_update_returns_422_when_completed_at_format_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        $payload = ['completed_at' => '2026-09-13'];
        // Execute
        $response = $this->putJson("/api/event-actions/{$eventAction->id}", $payload);
        // Assert
        $response->assertStatus(422);
    }

    public function test_destroy_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $eventAction = EventAction::factory()->create();
        // Execute
        $response = $this->deleteJson("/api/event-actions/{$eventAction->id}");
        // Assert
        $response->assertStatus(401);
    }

    public function test_destroy_returns_404_when_event_action_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/event-actions/999999');
        // Assert
        $response->assertStatus(404);
    }

    public function test_destroy_returns_403_when_user_is_not_participant_of_the_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($otherUser->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/event-actions/{$eventAction->id}");
        // Assert
        $response->assertStatus(403);
    }

    public function test_destroy_prevents_database_deletion_when_user_is_unauthorized(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($otherUser->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        // Execute
        $this->deleteJson("/api/event-actions/{$eventAction->id}");
        // Assert
        $this->assertDatabaseHas('event_actions', ['id' => $eventAction->id]);
    }

    public function test_destroy_returns_204_when_user_is_participant_of_the_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson("/api/event-actions/{$eventAction->id}");
        // Assert
        $response->assertStatus(204);
    }
}
