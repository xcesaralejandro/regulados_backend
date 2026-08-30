<?php

namespace Tests\Feature\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Program;
use App\Models\University;
use App\Models\User;
use App\Services\EventSchedulerService;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Ramsey\Collection\Collection;
use Tests\TestCase;
use Illuminate\Support\Str;

class EventSeriesControllerTest extends TestCase
{

	public function test_store_returns_401_when_user_is_unauthenticated(): void
	{
		// Prepare & Execute
		$response = $this->postJson('/api/event-series', []);
		// Assert
		$response->assertStatus(401);
	}

	public function test_store_returns_422_when_required_fields_are_missing(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		// Execute
		$response = $this->postJson('/api/event-series', []);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors([
			'event_category_id',
			'title',
			'visibility',
			'start_at',
			'end_at',
			'repeat_from',
			'repeat_to',
			'repeat_days',
		]);
	}

	public function test_store_returns_422_when_event_category_does_not_exist(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$payload = [
			'event_category_id' => 999999,
			'title' => 'Weekly Planning',
			'visibility' => 'public',
			'start_at' => '09:00:00',
			'end_at' => '10:00:00',
			'repeat_from' => '2026-03-02',
			'repeat_to' => '2026-03-08',
			'repeat_days' => ['monday'],
		];

		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['event_category_id']);
	}

	public function test_store_returns_422_when_visibility_is_invalid(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();
		$payload = [
			'event_category_id' => $category->id,
			'title' => 'Weekly Planning',
			'visibility' => 'super-secret', // inválido
			'start_at' => '09:00:00',
			'end_at' => '10:00:00',
			'repeat_from' => '2026-03-02',
			'repeat_to' => '2026-03-08',
			'repeat_days' => ['monday'],
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['visibility']);
	}

	public function test_store_returns_422_when_end_at_is_before_or_equal_to_start_at(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();

		$payload = [
			'event_category_id' => $category->id,
			'title' => 'Weekly Planning',
			'visibility' => 'public',
			'start_at' => '10:00:00',
			'end_at' => '09:00:00', // anterior a start_at
			'repeat_from' => '2026-03-02',
			'repeat_to' => '2026-03-08',
			'repeat_days' => ['monday'],
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['end_at']);
	}

	public function test_store_returns_422_when_repeat_to_is_before_repeat_from(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();
		$payload = [
			'event_category_id' => $category->id,
			'title' => 'Weekly Planning',
			'visibility' => 'public',
			'start_at' => '09:00:00',
			'end_at' => '10:00:00',
			'repeat_from' => '2026-03-08',
			'repeat_to' => '2026-03-02', // anterior a repeat_from
			'repeat_days' => ['monday'],
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['repeat_to']);
	}

	public function test_store_returns_422_when_repeat_days_contains_invalid_day(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();
		$payload = [
			'event_category_id' => $category->id,
			'title' => 'Weekly Planning',
			'visibility' => 'public',
			'start_at' => '09:00:00',
			'end_at' => '10:00:00',
			'repeat_from' => '2026-03-02',
			'repeat_to' => '2026-03-08',
			'repeat_days' => ['monday', 'funday'], // 'funday' no permitido
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);

		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['repeat_days.1']);
	}

	public function test_store_returns_201_persists_events_and_assigns_authenticated_user_id(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();
		$payload = [
			'event_category_id' => $category->id,
			'title' => 'Weekly Sync',
			'description' => 'Team progress meeting',
			'location' => 'Room 101',
			'notes' => 'Bring notebook',
			'visibility' => 'public',
			'start_at' => '09:00:00',
			'end_at' => '10:30:00',
			'repeat_from' => '2026-03-02', // Lunes
			'repeat_to' => '2026-03-08',   // Domingo
			'repeat_days' => ['monday', 'wednesday', 'friday'],
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(201);
		$response->assertJsonCount(3);
		$this->assertDatabaseCount('events', 3);
		$this->assertDatabaseHas('events', [
			'user_id'           => $user->id,
			'event_category_id' => $category->id,
			'title'             => 'Weekly Sync',
			'description'       => 'Team progress meeting',
			'location'          => 'Room 101',
			'notes'             => 'Bring notebook',
			'visibility'        => 'public',
			'start_at'          => '2026-03-02 09:00:00',
			'end_at'            => '2026-03-02 10:30:00',
		]);
		$this->assertDatabaseHas('events', [
			'user_id'           => $user->id,
			'event_category_id' => $category->id,
			'title'             => 'Weekly Sync',
			'start_at'          => '2026-03-04 09:00:00',
			'end_at'            => '2026-03-04 10:30:00',
		]);
		$this->assertDatabaseHas('events', [
			'user_id'           => $user->id,
			'event_category_id' => $category->id,
			'title'             => 'Weekly Sync',
			'start_at'          => '2026-03-06 09:00:00',
			'end_at'            => '2026-03-06 10:30:00',
		]);
	}

	public function test_store_returns_201_with_empty_array_when_no_days_match_range(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();
		// Rango de fin de semana pero pidiendo días hábiles
		$payload = [
			'event_category_id' => $category->id,
			'title'             => 'Weekend Standup',
			'visibility'        => 'private',
			'start_at'          => '09:00:00',
			'end_at'            => '10:00:00',
			'repeat_from'       => '2026-03-07', // Sábado
			'repeat_to'         => '2026-03-08',   // Domingo
			'repeat_days'       => ['monday', 'tuesday'],
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(201);
		$response->assertExactJson([]);
		$this->assertDatabaseCount('events', 0);
	}

	public function test_store_rolls_back_database_transaction_when_saving_event_fails(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		$category = EventCategory::factory()->create();
		// El servicio retorna un modelo que forzará un error SQL en el save()
		$invalidEvent = new Event([
			'user_id' => $user->id,
			'event_category_id' => $category->id,
			'title' => 'Invalid Event',
			'start_at' => null, // Violación de constraint NOT NULL en DB
			'end_at' => '2026-03-02 10:00:00',
		]);
		$this->mock(EventSchedulerService::class, function (MockInterface $mock) use ($invalidEvent) {
			$mock->shouldReceive('generateSeries')
				->once()
				->andReturn(collect([$invalidEvent]));
		});
		$payload = [
			'event_category_id' => $category->id,
			'title' => 'Invalid Event',
			'visibility' => 'contacts',
			'start_at' => '09:00:00',
			'end_at' => '10:00:00',
			'repeat_from' => '2026-03-02',
			'repeat_to' => '2026-03-08',
			'repeat_days' => ['monday'],
		];
		// Execute
		$response = $this->postJson('/api/event-series', $payload);
		// Assert
		$response->assertStatus(500);
		$this->assertDatabaseCount('events', 0);
	}




	public function test_update_returns_401_when_user_is_unauthenticated(): void
	{
		// Prepare & Execute
		$response = $this->putJson('/api/event-series/some-uuid', []);
		// Assert
		$response->assertStatus(401);
	}

	public function test_update_returns_404_when_repeat_code_does_not_exist(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		// Execute
		$response = $this->putJson('/api/event-series/non-existent-uuid', [
			'title' => 'Updated Title',
		]);
		// Assert
		$response->assertStatus(404);
	}

	public function test_update_returns_404_when_series_belongs_to_another_user(): void
	{
		// Prepare
		$owner = User::factory()->create();
		$otherUser = User::factory()->create();
		$category = EventCategory::factory()->create();
		$repeatCode = (string) Str::uuid();

		Event::factory()->count(2)->create([
			'user_id' => $owner->id,
			'event_category_id' => $category->id,
			'repeat_code' => $repeatCode,
		]);
		Sanctum::actingAs($otherUser);
		// Execute
		$response = $this->putJson("/api/event-series/{$repeatCode}", [
			'title' => 'Hacked Title',
		]);
		// Assert
		$response->assertStatus(404);
		$this->assertDatabaseMissing('events', ['title' => 'Hacked Title']);
	}

	public function test_update_returns_422_when_only_one_time_boundary_is_provided(): void
	{
		// Prepare
		$user = User::factory()->create();
		$repeatCode = (string) Str::uuid();
		Sanctum::actingAs($user);
		// Execute
		$response = $this->putJson("/api/event-series/{$repeatCode}", [
			'start_at' => '10:00:00',
			// falta 'end_at'
		]);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['end_at']);
	}

	public function test_update_returns_422_when_end_at_is_before_start_at(): void
	{
		// Prepare
		$user = User::factory()->create();
		$repeatCode = (string) Str::uuid();
		Sanctum::actingAs($user);
		// Execute
		$response = $this->putJson("/api/event-series/{$repeatCode}", [
			'start_at' => '11:00:00',
			'end_at'   => '10:00:00',
		]);
		// Assert
		$response->assertStatus(422);
		$response->assertJsonValidationErrors(['end_at']);
	}

	public function test_update_returns_200_and_updates_data_without_changing_times(): void
	{
		// Prepare
		$user = User::factory()->create();
		$category = EventCategory::factory()->create();
		$newCategory = EventCategory::factory()->create();
		$repeatCode = (string) Str::uuid();
		$event1 = Event::factory()->create([
			'user_id' => $user->id,
			'event_category_id' => $category->id,
			'repeat_code' => $repeatCode,
			'title' => 'Old Title',
			'visibility' => 'private',
			'start_at' => '2026-03-02 09:00:00',
			'end_at' => '2026-03-02 10:00:00',
		]);
		$event2 = Event::factory()->create([
			'user_id' => $user->id,
			'event_category_id' => $category->id,
			'repeat_code' => $repeatCode,
			'title' => 'Old Title',
			'visibility' => 'private',
			'start_at' => '2026-03-04 09:00:00',
			'end_at' => '2026-03-04 10:00:00',
		]);
		Sanctum::actingAs($user);
		$payload = [
			'event_category_id' => $newCategory->id,
			'title' => 'New Synchronized Title',
			'visibility' => 'public',
			'location' => 'Auditorium A',
		];
		// Execute
		$response = $this->putJson("/api/event-series/{$repeatCode}", $payload);
		// Assert
		$response->assertStatus(200);
		$response->assertJsonCount(2);
		$this->assertDatabaseHas('events', [
			'id' => $event1->id,
			'event_category_id' => $newCategory->id,
			'title' => 'New Synchronized Title',
			'visibility' => 'public',
			'location' => 'Auditorium A',
			'start_at' => '2026-03-02 09:00:00',
			'end_at' => '2026-03-02 10:00:00',
		]);
		$this->assertDatabaseHas('events', [
			'id' => $event2->id,
			'event_category_id' => $newCategory->id,
			'title' => 'New Synchronized Title',
			'visibility' => 'public',
			'location' => 'Auditorium A',
			'start_at' => '2026-03-04 09:00:00',
			'end_at' => '2026-03-04 10:00:00',
		]);
	}

	public function test_update_returns_200_and_updates_time_boundaries_preserving_original_dates(): void
	{
		// Prepare
		$user = User::factory()->create();
		$category = EventCategory::factory()->create();
		$repeatCode = (string) Str::uuid();
		$event1 = Event::factory()->create([
			'user_id' => $user->id,
			'event_category_id' => $category->id,
			'repeat_code' => $repeatCode,
			'start_at' => '2026-03-02 08:00:00',
			'end_at' => '2026-03-02 09:00:00',
		]);
		$event2 = Event::factory()->create([
			'user_id' => $user->id,
			'event_category_id' => $category->id,
			'repeat_code' => $repeatCode,
			'start_at' => '2026-03-04 08:00:00',
			'end_at' => '2026-03-04 09:00:00',
		]);
		Sanctum::actingAs($user);
		$payload = [
			'title'    => 'Rescheduled Time',
			'start_at' => '14:30:00',
			'end_at'   => '16:00:00',
		];
		// Execute
		$response = $this->putJson("/api/event-series/{$repeatCode}", $payload);
		// Assert
		$response->assertStatus(200);
		$this->assertDatabaseHas('events', [
			'id'       => $event1->id,
			'title'    => 'Rescheduled Time',
			'start_at' => '2026-03-02 14:30:00',
			'end_at'   => '2026-03-02 16:00:00',
		]);
		$this->assertDatabaseHas('events', [
			'id'       => $event2->id,
			'title'    => 'Rescheduled Time',
			'start_at' => '2026-03-04 14:30:00',
			'end_at'   => '2026-03-04 16:00:00',
		]);
	}

	public function test_destroy_returns_401_when_user_is_unauthenticated(): void
	{
		// Prepare & Execute
		$response = $this->deleteJson('/api/event-series/some-uuid');
		// Assert
		$response->assertStatus(401);
	}

	public function test_destroy_returns_403_when_repeat_code_does_not_exist(): void
	{
		// Prepare
		$user = User::factory()->create();
		Sanctum::actingAs($user);
		// Execute
		$response = $this->deleteJson('/api/event-series/non-existent-uuid');
		// Assert
		$response->assertStatus(403);
	}

	public function test_destroy_returns_403_when_series_belongs_to_another_user(): void
	{
		// Prepare
		$owner = User::factory()->create();
		$otherUser = User::factory()->create();
		$category = EventCategory::factory()->create();
		$repeatCode = (string) Str::uuid();
		Event::factory()->count(3)->create([
			'user_id'           => $owner->id,
			'event_category_id' => $category->id,
			'repeat_code'       => $repeatCode,
		]);
		Sanctum::actingAs($otherUser);
		// Execute
		$response = $this->deleteJson("/api/event-series/{$repeatCode}");
		// Assert
		$response->assertStatus(403);
		$this->assertDatabaseCount('events', 3);
		$this->assertEquals(3, Event::where('repeat_code', $repeatCode)->count());
	}

	public function test_destroy_returns_204_and_deletes_all_events_in_the_series(): void
	{
		// Prepare
		$user = User::factory()->create();
		$category = EventCategory::factory()->create();
		$targetRepeatCode = (string) Str::uuid();
		$otherRepeatCode = (string) Str::uuid();
		$targetEvents = Event::factory()->count(3)->create([
			'user_id'           => $user->id,
			'event_category_id' => $category->id,
			'repeat_code'       => $targetRepeatCode,
		]);
		$otherEvents = Event::factory()->count(2)->create([
			'user_id'           => $user->id,
			'event_category_id' => $category->id,
			'repeat_code'       => $otherRepeatCode,
		]);
		Sanctum::actingAs($user);
		// Execute
		$response = $this->deleteJson("/api/event-series/{$targetRepeatCode}");
		// Assert
		$response->assertStatus(204);
		$this->assertEquals(0, Event::where('repeat_code', $targetRepeatCode)->count());
		$this->assertEquals(3, Event::withTrashed()->where('repeat_code', $targetRepeatCode)->count());
		$this->assertEquals(2, Event::where('repeat_code', $otherRepeatCode)->count());
	}
}
