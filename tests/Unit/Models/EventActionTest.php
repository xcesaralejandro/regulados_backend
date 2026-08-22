<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class EventActionTest extends TestCase
{
    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $modelKeys = array_keys(EventAction::factory()->create()->toArray());
        $visibleKeys = [
            'id',
            'event_id',
            'user_id',
            'title',
            'description',
            'order',
            'completed_by',
            'completed_at',
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
        $modelKeys = array_keys(EventAction::factory()->create()->toArray());
        $hiddenKeys = ['deleted_at'];
        // Assert
        $this->assertEmpty(array_intersect($hiddenKeys, $modelKeys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $model = new EventAction();
        $expectedFillable = [
            'event_id',
            'user_id',
            'title',
            'description',
            'order',
            'completed_by',
            'completed_at',
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
        EventAction::factory()->create();
        // Execute
        $eventAction = EventAction::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $eventAction['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        EventAction::factory()->create();
        // Execute
        $eventAction = EventAction::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $eventAction['updated_at']);
    }

    public function test_model_cast_completed_at(): void
    {
        // Prepare
        EventAction::factory()->create(['completed_at' => now()]);
        // Execute
        $eventAction = EventAction::first()->makeVisible(['completed_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $eventAction['completed_at']);
    }

    public function test_model_has_event_relationship(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $eventAction = EventAction::factory()->create(['event_id' => $event->id]);
        // Execute
        $eventAction = EventAction::with('event')->find($eventAction->id);
        // Assert
        $this->assertTrue($eventAction->relationLoaded('event'));
        $this->assertNotNull($eventAction->event);
        $this->assertEquals($event->id, $eventAction->event->id);
    }

    public function test_model_has_event_relationship_correctly_formed(): void
    {
        // Prepare
        $eventAction = new EventAction();
        // Execute
        $relation = $eventAction->event();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('event_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_model_has_user_relationship(): void
    {
        // Prepare
        $user = User::factory()->create();
        $eventAction = EventAction::factory()->create(['user_id' => $user->id]);
        // Execute
        $eventAction = EventAction::with('user')->find($eventAction->id);
        // Assert
        $this->assertTrue($eventAction->relationLoaded('user'));
        $this->assertNotNull($eventAction->user);
        $this->assertEquals($user->id, $eventAction->user->id);
    }

    public function test_model_has_user_relationship_correctly_formed(): void
    {
        // Prepare
        $eventAction = new EventAction();
        // Execute
        $relation = $eventAction->user();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_model_has_completed_by_relationship(): void
    {
        // Prepare
        $user = User::factory()->create();
        $eventAction = EventAction::factory()->create(['completed_by' => $user->id]);
        // Execute
        $eventAction = EventAction::with('completedBy')->find($eventAction->id);
        // Assert
        $this->assertTrue($eventAction->relationLoaded('completedBy'));
        $this->assertNotNull($eventAction->completedBy);
        $this->assertEquals($user->id, $eventAction->completedBy->id);
    }

    public function test_model_has_completed_by_relationship_correctly_formed(): void
    {
        // Prepare
        $eventAction = new EventAction();
        // Execute
        $relation = $eventAction->completedBy();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('completed_by', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_description_field_accepts_null(): void
    {
        // Prepare & Execute
        $eventAction = EventAction::factory()->create(['description' => null]);
        // Assert
        $this->assertNull($eventAction->description);
    }

    public function test_completed_by_field_accepts_null(): void
    {
        // Prepare & Execute
        $eventAction = EventAction::factory()->create(['completed_by' => null]);
        // Assert
        $this->assertNull($eventAction->completed_by);
    }

    public function test_completed_at_field_accepts_null(): void
    {
        // Prepare & Execute
        $eventAction = EventAction::factory()->create(['completed_at' => null]);
        // Assert
        $this->assertNull($eventAction->completed_at);
    }

    public function test_event_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventAction::factory()->create(['event_id' => null]);
    }

    public function test_user_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventAction::factory()->create(['user_id' => null]);
    }

    public function test_title_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventAction::factory()->create(['title' => null]);
    }

    public function test_order_field_defaults_to_zero(): void
    {
        // Prepare
        $event = Event::factory()->create();
        $user = User::factory()->create();
        // Execute
        $eventAction = EventAction::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'title' => 'Sample Action',
        ]);
        $eventAction = EventAction::find($eventAction->id);
        // Assert
        $this->assertEquals(0, $eventAction->order);
    }

    public function test_model_implements_soft_deletes(): void
    {
        // Prepare
        $eventAction = EventAction::factory()->create();
        // Execute
        $eventAction->delete();
        // Assert
        $this->assertSoftDeleted('event_actions', ['id' => $eventAction->id]);
        $this->assertNull(EventAction::find($eventAction->id));
        $this->assertNotNull(EventAction::withTrashed()->find($eventAction->id));
    }

    public function test_model_is_linked_to_correct_table(): void
    {
        // Prepare
        $model = new EventAction();
        // Execute
        $table = $model->getTable();
        // Assert
        $this->assertEquals('event_actions', $table);
    }
}
