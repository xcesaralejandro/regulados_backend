<?php

namespace Tests\Feature\Controllers;

use App\Models\Event;
use App\Models\EventAction;
use App\Models\EventCategory;
use App\Models\Program;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventControllerTest extends TestCase
{

    public function test_index_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare & Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertStatus(401);
    }

    public function test_index_returns_200_and_empty_array_when_user_has_no_events(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_index_returns_200_when_user_has_associated_events(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $event->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertStatus(200);
    }

    public function test_index_returns_200_and_only_events_where_user_is_participant(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $userEvent = Event::factory()->create();
        $userEvent->participants()->attach($user->id);
        $otherEvent = Event::factory()->create();
        $otherEvent->participants()->attach($otherUser->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $userEvent->id]);
        $response->assertJsonMissing(['id' => $otherEvent->id]);
    }

    public function test_index_returns_expected_keys_for_event(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id);
        $eventKeys = [
            'id',
            'repeat_code',
            'title',
            'description',
            'location',
            'notes',
            'visibility',
            'start_at',
            'end_at',
            'created_at',
            'updated_at',
            'participants',
            'actions',
            'category'
        ];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonStructure(['*' => $eventKeys]);
    }

    public function test_index_load_category_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id);
        $categoryKeys = ['id', 'name', 'description', 'icon', 'text_color', 'background_color'];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonStructure(['*' => ['category' => $categoryKeys]]);
    }

    public function test_index_load_participants_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id);
        $participantKeys = ['id', 'semester', 'name', 'surname', 'email', 'phone', 'instagram', 'discord', 'birthdate', 'avatar', 'pivot', 'program'];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonStructure(['*' => ['participants' => ['*' => $participantKeys]]]);
    }

    public function test_index_load_participants_pivot_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id, ['workflow_state' => 'confirmed', 'role' => 'admin']);
        $pivotKeys = ['workflow_state', 'role'];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonStructure(['*' => ['participants' => ['*' => ['pivot' => $pivotKeys]]]]);
    }

    public function test_index_load_participants_program_relation_with_expected_keys(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id);
        $programKeys = ['id', 'name', 'university' => ['id', 'name', 'short_name']];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonStructure(['*' => ['participants' => ['*' => ['program' => $programKeys]]]]);
    }

    public function test_index_load_actions_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        EventAction::factory()->count(3)->create(['event_id' => $event->id]);
        $event->participants()->attach($user->id);
        $actionKeys = ['id', 'title', 'description', 'order', 'completed_at', 'created_at'];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonStructure(['*' => ['actions' => ['*' => $actionKeys]]]);
    }

    public function test_index_returns_multiple_events_when_user_participates_in_many(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $events = Event::factory()->count(3)->create(['event_category_id' => $category->id]);
        foreach ($events as $event) {
            $event->participants()->attach($user->id);
        }
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonCount(3);
    }

    public function test_index_returns_event_when_user_participation_workflow_state_is_declined(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id, ['workflow_state' => 'declined', 'role' => 'attendee']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonFragment(['id' => $event->id]);
    }

    public function test_index_returns_event_when_user_participation_workflow_state_is_pending(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id, ['workflow_state' => 'pending', 'role' => 'attendee']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonFragment(['id' => $event->id]);
    }

    public function test_index_returns_events_when_user_has_participations_with_mixed_workflow_states(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $firstEvent = Event::factory()->create(['event_category_id' => $category->id]);
        $firstEvent->participants()->attach($user->id, ['workflow_state' => 'pending', 'role' => 'attendee']);
        $secondEvent = Event::factory()->create(['event_category_id' => $category->id]);
        $secondEvent->participants()->attach($user->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonFragment(['id' => $firstEvent->id]);
        $response->assertJsonFragment(['id' => $secondEvent->id]);
    }

    public function test_index_returns_event_when_user_participation_role_is_admin(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id, ['workflow_state' => 'confirmed', 'role' => 'admin']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonFragment(['id' => $event->id]);
    }

    public function test_index_returns_event_when_user_participation_role_is_attendee(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        $event->participants()->attach($user->id, ['workflow_state' => 'confirmed', 'role' => 'attendee']);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events');
        // Assert
        $response->assertJsonFragment(['id' => $event->id]);
    }

    public function test_index_returns_422_when_from_date_format_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=invalid-date');
        // Assert
        $response->assertStatus(422);
    }

    public function test_index_attaches_validation_error_to_from_field_when_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=invalid-date');
        // Assert
        $response->assertJsonValidationErrors(['from']);
    }

    public function test_index_returns_422_when_to_date_format_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?to=invalid-date');
        // Assert
        $response->assertStatus(422);
    }

    public function test_index_attaches_validation_error_to_to_field_when_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?to=invalid-date');
        // Assert
        $response->assertJsonValidationErrors(['to']);
    }

    public function test_index_returns_422_when_to_is_before_from(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-10&to=2026-07-01');
        // Assert
        $response->assertStatus(422);
    }

    public function test_index_attaches_validation_error_to_to_field_when_to_is_before_from(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-10&to=2026-07-01');
        // Assert
        $response->assertJsonValidationErrors(['to']);
    }

    public function test_index_returns_200_when_filtering_with_both_from_and_to_dates(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $inRangeEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-05 10:00:00',
        ]);
        $inRangeEvent->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-01&to=2026-07-10');
        // Assert
        $response->assertStatus(200);
    }

    public function test_index_returns_only_events_inside_range_when_filtering_with_from_and_to(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $beforeEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-06-30 23:59:59',
        ]);
        $beforeEvent->participants()->attach($user->id);
        $inRangeEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-05 10:00:00',
        ]);
        $inRangeEvent->participants()->attach($user->id);
        $afterEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-11 00:00:00',
        ]);
        $afterEvent->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-01&to=2026-07-10');
        // Assert
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $inRangeEvent->id]);
    }

    public function test_index_excludes_out_of_range_events_when_filtering_with_from_and_to(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $beforeEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-06-30 23:59:59',
        ]);
        $beforeEvent->participants()->attach($user->id);
        $afterEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-11 00:00:00',
        ]);
        $afterEvent->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-01&to=2026-07-10');
        // Assert
        $response->assertJsonMissing(['id' => $beforeEvent->id]);
        $response->assertJsonMissing(['id' => $afterEvent->id]);
    }

    public function test_index_returns_200_when_filtering_with_from_date_only(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $futureEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-01 00:00:00',
        ]);
        $futureEvent->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-01');
        // Assert
        $response->assertStatus(200);
    }

    public function test_index_includes_only_matching_events_when_filtering_with_from_date(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $pastEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-06-30 23:59:59',
        ]);
        $pastEvent->participants()->attach($user->id);
        $futureEvent = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-01 00:00:00',
        ]);
        $futureEvent->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?from=2026-07-01');
        // Assert
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $futureEvent->id]);
    }

    public function test_index_returns_only_events_up_to_end_date(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $eventBeforeLimit = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-10 23:59:59',
        ]);
        $eventBeforeLimit->participants()->attach($user->id);
        $eventAfterLimit = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-11 00:00:00',
        ]);
        $eventAfterLimit->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?to=2026-07-10');
        // Assert
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $eventBeforeLimit->id]);
    }

    public function test_index_excludes_subsequent_events_when_filtering_with_to_date(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        $eventAfterLimit = Event::factory()->create([
            'event_category_id' => $category->id,
            'start_at' => '2026-07-11 00:00:00',
        ]);
        $eventAfterLimit->participants()->attach($user->id);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/events?to=2026-07-10');
        // Assert
        $response->assertJsonMissing(['id' => $eventAfterLimit->id]);
    }
}
