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
}
