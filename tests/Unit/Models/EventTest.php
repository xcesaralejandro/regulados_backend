<?php

namespace Tests\Unit\Models;

use App\Models\CustomPivots\EventUserMapping;
use App\Models\Event;
use App\Models\EventAction;
use App\Models\EventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $modelKeys = array_keys(Event::factory()->create()->toArray());
        $visibleKeys = [
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
        ];
        // Assert
        sort($modelKeys);
        sort($visibleKeys);
        $this->assertEquals($visibleKeys, $modelKeys);
    }

    public function test_fields_defined_as_hidden_are_not_displayed(): void
    {
        // Prepare
        $modelKeys = array_keys(Event::factory()->create()->toArray());
        $hiddenKeys = ['user_id', 'event_category_id', 'deleted_at'];
        // Assert
        $this->assertEmpty(array_intersect($hiddenKeys, $modelKeys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $model = new Event();
        $expectedFillable = [
            'user_id',
            'event_category_id',
            'repeat_code',
            'title',
            'description',
            'location',
            'notes',
            'visibility',
            'start_at',
            'end_at',
        ];
        // Execute
        $fillable = $model->getFillable();
        // Assert
        sort($expectedFillable);
        sort($fillable);
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_model_cast_created_at(): void
    {
        // Prepare
        Event::factory()->create();
        // Execute
        $event = Event::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $event['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        Event::factory()->create();
        // Execute
        $event = Event::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $event['updated_at']);
    }

    public function test_model_has_user_relationship(): void
    {
        // Prepare
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);
        // Execute
        $event = Event::with('user')->find($event->id);
        // Assert
        $this->assertTrue($event->relationLoaded('user'));
        $this->assertNotNull($event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    public function test_model_has_user_relationship_correctly_formed(): void
    {
        // Prepare
        $event = new Event();
        // Execute
        $relation = $event->user();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_model_has_category_relationship(): void
    {
        // Prepare
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        // Execute
        $event = Event::with('category')->find($event->id);
        // Assert
        $this->assertTrue($event->relationLoaded('category'));
        $this->assertNotNull($event->category);
        $this->assertEquals($category->id, $event->category->id);
    }

    public function test_model_has_category_relationship_correctly_formed(): void
    {
        // Prepare
        $event = new Event();
        // Execute
        $relation = $event->category();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('event_category_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_repeat_code_field_accepts_null(): void
    {
        // Prepare & Execute
        $event = Event::factory()->create(['repeat_code' => null]);
        // Assert
        $this->assertNull($event->repeat_code);
    }

    public function test_description_field_accepts_null(): void
    {
        // Prepare & Execute
        $event = Event::factory()->create(['description' => null]);
        // Assert
        $this->assertNull($event->description);
    }

    public function test_location_field_accepts_null(): void
    {
        // Prepare & Execute
        $event = Event::factory()->create(['location' => null]);
        // Assert
        $this->assertNull($event->location);
    }

    public function test_notes_field_accepts_null(): void
    {
        // Prepare & Execute
        $event = Event::factory()->create(['notes' => null]);
        // Assert
        $this->assertNull($event->notes);
    }

    public function test_user_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Event::factory()->create(['user_id' => null]);
    }

    public function test_event_category_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Event::factory()->create(['event_category_id' => null]);
    }

    public function test_title_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Event::factory()->create(['title' => null]);
    }

    public function test_start_at_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Event::factory()->create(['start_at' => null]);
    }

    public function test_end_at_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Event::factory()->create(['end_at' => null]);
    }

    public function test_visibility_field_accepts_public_value(): void
    {
        // Prepare
        $event = Event::factory()->create(['visibility' => 'public']);
        // Execute
        $event = Event::find($event->id);
        // Assert
        $this->assertEquals('public', $event->visibility);
    }

    public function test_visibility_field_accepts_contacts_value(): void
    {
        // Prepare
        $event = Event::factory()->create(['visibility' => 'contacts']);
        // Execute
        $event = Event::find($event->id);
        // Assert
        $this->assertEquals('contacts', $event->visibility);
    }

    public function test_visibility_field_accepts_private_value(): void
    {
        // Prepare
        $event = Event::factory()->create(['visibility' => 'private']);
        // Execute
        $event = Event::find($event->id);
        // Assert
        $this->assertEquals('private', $event->visibility);
    }

    public function test_visibility_field_only_accepts_valid_enum_values(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Event::factory()->create(['visibility' => 'invalid_enum_value']);
    }

    public function test_visibility_field_defaults_to_private(): void
    {
        // Prepare
        $user = User::factory()->create();
        $category = EventCategory::factory()->create();
        // Execute
        $event = Event::create([
            'user_id' => $user->id,
            'event_category_id' => $category->id,
            'title' => 'Default Visibility Event',
            'start_at' => '2026-08-22 10:00:00',
            'end_at' => '2026-08-22 11:00:00',
        ]);
        $event = Event::find($event->id);
        // Assert
        $this->assertEquals('private', $event->visibility);
    }

    public function test_model_implements_soft_deletes(): void
    {
        // Prepare
        $event = Event::factory()->create();
        // Execute
        $event->delete();
        // Assert
        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertNull(Event::find($event->id));
        $this->assertNotNull(Event::withTrashed()->find($event->id));
    }

    public function test_model_is_linked_to_correct_table(): void
    {
        // Prepare
        $model = new Event();
        // Execute
        $table = $model->getTable();
        // Assert
        $this->assertEquals('events', $table);
    }

    public function test_model_has_participants_relationship_correctly_formed(): void
    {
        // Prepare
        $event = new Event();

        // Execute
        $relation = $event->participants();

        // Assert
        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals('event_user_mapping', $relation->getTable());
        $this->assertEquals('event_id', $relation->getForeignPivotKeyName());
        $this->assertEquals('user_id', $relation->getRelatedPivotKeyName());
        $this->assertEquals(User::class, get_class($relation->getRelated()));
        $this->assertEquals(EventUserMapping::class, $relation->getPivotClass());
        $this->assertContains('workflow_state', $relation->getPivotColumns());
        $this->assertContains('role', $relation->getPivotColumns());
    }

    public function test_model_can_attach_and_retrieve_participants_with_pivot_data(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $user = User::factory()->create();
        // Execute
        $event->participants()->attach($user->id, ['workflow_state' => 'confirmed', 'role' => 'admin']);
        $event = Event::with('participants')->find($event->id);
        // Assert
        $this->assertTrue($event->relationLoaded('participants'));
        $this->assertCount(2, $event->participants);
        $participant = $event->participants->firstWhere('id', $user->id);
        $this->assertNotNull($participant);
        $this->assertInstanceOf(EventUserMapping::class, $participant->pivot);
        $this->assertEquals('confirmed', $participant->pivot->workflow_state);
        $this->assertEquals('admin', $participant->pivot->role);
    }

    public function test_participants_relationship_ignores_soft_deleted_pivot_records(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $userActive = User::factory()->create();
        $userDeleted = User::factory()->create();
        $event->participants()->attach($userActive->id, [
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        $event->participants()->attach($userDeleted->id, [
            'workflow_state' => 'pending',
            'role' => 'attendee',
        ]);
        EventUserMapping::where('event_id', $event->id)
            ->where('user_id', $userDeleted->id)
            ->delete();
        // Execute
        $event = Event::with('participants')->find($event->id);
        // Assert
        $this->assertCount(2, $event->participants);
        $this->assertTrue($event->participants->contains('id', $event->user_id));
        $this->assertTrue($event->participants->contains('id', $userActive->id));
        $this->assertFalse($event->participants->contains('id', $userDeleted->id));
    }

    public function test_creator_is_automatically_attached_as_confirmed_admin_participant_on_creation(): void
    {
        // Prepare
        $user = User::factory()->create();
        // Execute
        $event = Event::factory()->create(['user_id' => $user->id]);
        $event->load('participants');
        // Assert
        $this->assertCount(1, $event->participants);
        $creatorParticipant = $event->participants->first();
        $this->assertEquals($user->id, $creatorParticipant->id);
        $this->assertInstanceOf(EventUserMapping::class, $creatorParticipant->pivot);
        $this->assertEquals('admin', $creatorParticipant->pivot->role);
        $this->assertEquals('confirmed', $creatorParticipant->pivot->workflow_state);
    }

    public function test_additional_participants_can_be_attached_alongside_the_creator(): void
    {
        // Prepare
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        // Execute
        $event = Event::factory()->create(['user_id' => $creator->id]);
        $event->participants()->attach($otherUser->id, ['role' => 'attendee', 'workflow_state' => 'pending']);
        $event->load('participants');
        // Assert
        $this->assertCount(2, $event->participants);
        $creatorPivot = $event->participants->firstWhere('id', $creator->id)->pivot;
        $this->assertEquals('admin', $creatorPivot->role);
        $this->assertEquals('confirmed', $creatorPivot->workflow_state);
        $otherPivot = $event->participants->firstWhere('id', $otherUser->id)->pivot;
        $this->assertEquals('attendee', $otherPivot->role);
        $this->assertEquals('pending', $otherPivot->workflow_state);
    }
}
