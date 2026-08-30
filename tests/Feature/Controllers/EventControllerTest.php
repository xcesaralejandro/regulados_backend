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

    public function test_store_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $payload = [
            'event_category_id' => 1,
            'title' => 'Sample Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 12:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_store_creates_event_and_returns_201_with_valid_data(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Tech Conference 2026',
            'description' => 'Annual Developer Gathering',
            'location' => 'Convention Center',
            'notes' => 'Bring laptop',
            'visibility' => 'public',
            'start_at' => '2026-09-01 09:00:00',
            'end_at' => '2026-09-01 18:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'event_category_id' => $category->id,
            'title' => 'Tech Conference 2026',
            'description' => 'Annual Developer Gathering',
            'location' => 'Convention Center',
            'notes' => 'Bring laptop',
            'visibility' => 'public',
            'repeat_code' => null,
            'start_at' => '2026-09-01 09:00:00',
            'end_at' => '2026-09-01 18:00:00',
        ]);
    }

    public function test_store_returns_exact_json_structure(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Structure Verification Event',
            'description' => 'Testing JSON structure keys',
            'location' => 'Auditorium',
            'notes' => 'No notes',
            'visibility' => 'contacts',
            'start_at' => '2026-10-05 08:00:00',
            'end_at' => '2026-10-05 10:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
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
            'category',
        ]);
    }

    public function test_store_loads_category_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Category Test Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 12:00:00',
        ];
        $categoryKeys = ['id', 'name', 'description', 'icon', 'text_color', 'background_color'];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['category' => $categoryKeys]);
    }

    public function test_store_loads_participants_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Participants Test Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 12:00:00',
        ];
        $participantKeys = ['id', 'semester', 'name', 'surname', 'email', 'phone', 'instagram', 'discord', 'birthdate', 'avatar', 'pivot', 'program'];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['participants' => ['*' => $participantKeys]]);
    }

    public function test_store_loads_participants_pivot_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Participant Pivot Test Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 12:00:00',
        ];
        $pivotKeys = ['workflow_state', 'role'];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['participants' => ['*' => ['pivot' => $pivotKeys]]]);
    }

    public function test_store_loads_participants_program_relation_with_expected_keys(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Participant Program Test Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 12:00:00',
        ];
        $programKeys = ['id', 'name', 'university' => ['id', 'name', 'short_name']];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['participants' => ['*' => ['program' => $programKeys]]]);
    }

    public function test_store_loads_actions_relation_with_empty_array(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Actions Test Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 12:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $response->assertJsonIsArray('actions');
    }

    public function test_store_returns_exact_json_payload_from_fresh_instance(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Exact JSON Matching',
            'description' => 'Exact comparison test',
            'location' => 'Room 101',
            'notes' => 'Strict assertion',
            'visibility' => 'private',
            'start_at' => '2026-11-01 14:00:00',
            'end_at' => '2026-11-01 16:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $event = Event::first();
        $response->assertExactJson($event->fresh()->toArray());
    }

    public function test_store_overwrites_injected_user_id_with_authenticated_user_id(): void
    {
        // Prepare
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'user_id' => $anotherUser->id,
            'event_category_id' => $category->id,
            'title' => 'Spoof Test Event',
            'visibility' => 'public',
            'start_at' => '2026-09-10 10:00:00',
            'end_at' => '2026-09-10 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'title' => 'Spoof Test Event',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('events', [
            'title' => 'Spoof Test Event',
            'user_id' => $anotherUser->id,
        ]);
    }

    public function test_store_overwrites_injected_repeat_code_with_null(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'repeat_code' => 'CUSTOM_REPEAT_123',
            'event_category_id' => $category->id,
            'title' => 'Repeat Code Overwrite Event',
            'visibility' => 'public',
            'start_at' => '2026-09-10 10:00:00',
            'end_at' => '2026-09-10 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'title' => 'Repeat Code Overwrite Event',
            'repeat_code' => null,
        ]);
    }

    public function test_store_persists_nullable_fields_as_null_when_explicitly_sent(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Event With Nullables',
            'description' => null,
            'location' => null,
            'notes' => null,
            'visibility' => 'public',
            'start_at' => '2026-09-02 10:00:00',
            'end_at' => '2026-09-02 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'title' => 'Event With Nullables',
            'description' => null,
            'location' => null,
            'notes' => null,
        ]);
    }

    public function test_store_returns_422_when_event_category_id_is_missing(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $payload = [
            'title' => 'Valid Title',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event_category_id']);
    }

    public function test_store_returns_422_when_event_category_id_does_not_exist(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $payload = [
            'event_category_id' => 99999,
            'title' => 'Valid Title',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event_category_id']);
    }

    public function test_store_returns_422_when_title_is_missing(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_title_is_not_a_string(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 12345,
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_title_exceeds_max_length(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => str_repeat('a', 256),
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_description_is_not_a_string(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'description' => ['array', 'not', 'string'],
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    }

    public function test_store_returns_422_when_location_is_not_a_string(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'location' => ['not_a_string'],
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['location']);
    }

    public function test_store_returns_422_when_notes_is_not_a_string(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'notes' => ['not_a_string'],
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notes']);
    }

    public function test_store_returns_422_when_visibility_is_missing(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['visibility']);
    }

    public function test_store_returns_422_when_visibility_is_invalid(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'visibility' => 'unauthorized_visibility_option',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['visibility']);
    }

    public function test_store_returns_422_when_start_at_is_missing(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'visibility' => 'public',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_at']);
    }

    public function test_store_returns_422_when_start_at_is_not_a_valid_date(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'visibility' => 'public',
            'start_at' => 'invalid-date-format',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_at']);
    }

    public function test_store_returns_422_when_end_at_is_missing(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_store_returns_422_when_end_at_is_not_a_valid_date(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => 'invalid-date-format',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_store_returns_422_when_end_at_is_before_start_at(): void
    {
        // Prepare
        Sanctum::actingAs(User::factory()->create());
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Valid Title',
            'visibility' => 'public',
            'start_at' => '2026-09-01 12:00:00',
            'end_at' => '2026-09-01 11:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_store_accepts_end_at_equal_to_start_at(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $payload = [
            'event_category_id' => $category->id,
            'title' => 'Instant Event',
            'visibility' => 'public',
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 10:00:00',
        ];
        // Execute
        $response = $this->postJson('/api/events', $payload);
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 10:00:00',
        ]);
    }

    public function test_update_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $payload = ['title' => 'Updated Event Title'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(401);
    }

    public function test_update_returns_403_when_user_is_not_the_event_creator(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($otherUser);
        $payload = ['title' => 'Unauthorized Update Attempt'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(403);
    }

    public function test_update_modifies_event_and_returns_200_with_valid_data(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $oldCategory = EventCategory::factory()->create();
        $newCategory = EventCategory::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'event_category_id' => $oldCategory->id,
            'title' => 'Original Title',
            'visibility' => 'private',
        ]);
        $payload = [
            'event_category_id' => $newCategory->id,
            'title' => 'Updated Conference 2026',
            'description' => 'Updated Developer Gathering',
            'location' => 'Main Auditorium',
            'notes' => 'Updated notes',
            'visibility' => 'public',
            'start_at' => '2026-09-02 10:00:00',
            'end_at' => '2026-09-02 12:00:00',
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'user_id' => $user->id,
            'event_category_id' => $newCategory->id,
            'title' => 'Updated Conference 2026',
            'description' => 'Updated Developer Gathering',
            'location' => 'Main Auditorium',
            'notes' => 'Updated notes',
            'visibility' => 'public',
            'start_at' => '2026-09-02 10:00:00',
            'end_at' => '2026-09-02 12:00:00',
        ]);
    }

    public function test_update_returns_exact_json_structure(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['title' => 'Structure Verification Event Updated'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
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
            'category',
        ]);
    }

    public function test_update_loads_category_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'event_category_id' => $category->id,
        ]);
        $categoryKeys = ['id', 'name', 'description', 'icon', 'text_color', 'background_color'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", ['title' => 'Category Check']);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['category' => $categoryKeys]);
    }

    public function test_update_loads_participants_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $event->participants()->attach($user->id);
        $participantKeys = ['id', 'semester', 'name', 'surname', 'email', 'phone', 'instagram', 'discord', 'birthdate', 'avatar', 'pivot', 'program'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", ['title' => 'Participants Check']);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['participants' => ['*' => $participantKeys]]);
    }

    public function test_update_loads_participants_pivot_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $event->participants()->attach($user->id, ['workflow_state' => 'confirmed', 'role' => 'admin']);
        $pivotKeys = ['workflow_state', 'role'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", ['title' => 'Pivot Check']);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['participants' => ['*' => ['pivot' => $pivotKeys]]]);
    }

    public function test_update_loads_participants_program_relation_with_expected_keys(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $event->participants()->attach($user->id);
        $programKeys = ['id', 'name', 'university' => ['id', 'name', 'short_name']];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", ['title' => 'Program Relation Check']);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['participants' => ['*' => ['program' => $programKeys]]]);
    }

    public function test_update_loads_actions_relation_with_expected_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        EventAction::factory()->count(2)->create(['event_id' => $event->id]);
        $actionKeys = ['id', 'title', 'description', 'order', 'completed_at', 'created_at'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", ['title' => 'Actions Relation Check']);
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['actions' => ['*' => $actionKeys]]);
    }

    public function test_update_returns_422_when_event_category_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['event_category_id' => 99999];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['event_category_id']);
    }

    public function test_update_returns_422_when_title_is_empty(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['title' => ''];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_update_returns_422_when_visibility_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['visibility' => 'invalid_scope'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['visibility']);
    }

    public function test_update_returns_422_when_start_at_is_provided_without_end_at(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['start_at' => '2026-09-01 10:00:00'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_update_returns_422_when_end_at_is_provided_without_start_at(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['end_at' => '2026-09-01 12:00:00'];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_at']);
    }

    public function test_update_returns_422_when_end_at_is_before_start_at(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = [
            'start_at' => '2026-09-01 15:00:00',
            'end_at' => '2026-09-01 10:00:00',
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_update_returns_422_when_start_at_format_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = [
            'start_at' => 'not-a-valid-date',
            'end_at' => '2026-09-01 12:00:00',
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_at']);
    }

    public function test_update_returns_422_when_end_at_format_is_invalid(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = [
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => 'not-a-valid-date',
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_at']);
    }

    public function test_update_returns_422_when_title_exceeds_max_length(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = ['title' => str_repeat('a', 256)];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_update_allows_nullable_fields_to_be_set_to_null(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create([
            'user_id' => $user->id,
            'description' => 'Existing description',
            'location' => 'Room 101',
            'notes' => 'Existing notes',
        ]);
        $payload = [
            'description' => null,
            'location' => null,
            'notes' => null,
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'description' => null,
            'location' => null,
            'notes' => null,
        ]);
    }

    public function test_update_allows_end_at_equal_to_start_at(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = [
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 10:00:00',
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'start_at' => '2026-09-01 10:00:00',
            'end_at' => '2026-09-01 10:00:00',
        ]);
    }

    public function test_update_ignores_unauthorized_fields_like_user_id(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        $payload = [
            'title' => 'Updated Title',
            'user_id' => $otherUser->id,
        ];
        // Execute
        $response = $this->putJson("/api/events/{$event->id}", $payload);
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_update_returns_404_when_event_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $payload = ['title' => 'Non-existent Event'];
        // Execute
        $response = $this->putJson('/api/events/999999', $payload);
        // Assert
        $response->assertStatus(404);
    }


    public function test_destroy_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare
        $event = Event::factory()->create();
        // Execute
        $response = $this->deleteJson("/api/events/{$event->id}");
        // Assert
        $response->assertStatus(401);
    }

    public function test_destroy_returns_403_when_user_is_not_the_event_creator(): void
    {
        // Prepare
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($otherUser);
        // Execute
        $response = $this->deleteJson("/api/events/{$event->id}");
        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_destroy_deletes_event_and_returns_204_when_user_is_owner(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $event = Event::factory()->create(['user_id' => $user->id]);
        // Execute
        $response = $this->deleteJson("/api/events/{$event->id}");
        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_destroy_returns_404_when_event_does_not_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->deleteJson('/api/events/999999');
        // Assert
        $response->assertStatus(404);
    }
}
