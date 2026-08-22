<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Program;
use App\Models\University;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class EventCategoryTest extends TestCase
{
    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $model_keys = array_keys(EventCategory::factory()->create()->toArray());
        $visible_keys = ['id', 'name', 'description', 'icon', 'text_color', 'background_color'];
        // Assert
        sort($model_keys);
        sort($visible_keys);
        $this->assertEquals($visible_keys, $model_keys);
    }

    public function test_fields_defined_as_hidden_are_not_displayed(): void
    {
        // Prepare
        $model_keys = array_keys(EventCategory::factory()->create()->toArray());
        $hidden_keys = ['created_at', 'updated_at', 'deleted_at'];
        // Assert
        $this->assertEmpty(array_intersect($hidden_keys, $model_keys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $category = new EventCategory();
        $expectedFillable = ['name', 'description', 'icon', 'text_color', 'background_color'];
        // Execute
        $fillable = $category->getFillable();
        // Assert
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_model_is_linked_to_correct_table(): void
    {
        // Prepare
        $category = new EventCategory();
        // Execute
        $table = $category->getTable();
        // Assert
        $this->assertEquals('event_categories', $table);
    }

    public function test_model_cast_created_at(): void
    {
        // Prepare
        EventCategory::factory()->create();
        // Execute
        $category = EventCategory::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $category['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        EventCategory::factory()->create();
        // Execute
        $category = EventCategory::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $category['updated_at']);
    }

    public function test_model_has_events_relationship(): void
    {
        // Prepare
        $category = EventCategory::factory()->create();
        $event = Event::factory()->create(['event_category_id' => $category->id]);
        // Execute
        $loadedCategory = EventCategory::with('events')->find($category->id);
        // Assert
        $this->assertTrue($loadedCategory->relationLoaded('events'));
        $this->assertNotEmpty($loadedCategory->events);
        $this->assertEquals($event->id, $loadedCategory->events->first()->id);
    }

    public function test_model_load_correct_data_for_events_relationship(): void
    {
        // Prepare
        EventCategory::factory(2)->create();
        Event::factory(4)->create();
        $category = EventCategory::factory()->create();
        $events = Event::factory(3)->create(['event_category_id' => $category->id]);
        // Execute
        $loadedCategory = EventCategory::with('events')->find($category->id);
        // Assert
        $this->assertCount(3, $loadedCategory->events);
        $this->assertEquals(
            $events->pluck('id')->sort()->values(),
            $loadedCategory->events->pluck('id')->sort()->values()
        );
    }

    public function test_model_has_events_relationship_correctly_formed(): void
    {
        // Prepare
        $category = new EventCategory();
        // Execute
        $relation = $category->events();
        // Assert
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('event_category_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getLocalKeyName());
    }

    public function test_name_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventCategory::factory()->create(['name' => null]);
    }

    public function test_description_field_accepts_null(): void
    {
        // Prepare & Execute
        $category = EventCategory::factory()->create(['description' => null]);
        // Assert
        $this->assertNull($category->description);
    }

    public function test_icon_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventCategory::factory()->create(['icon' => null]);
    }

    public function test_text_color_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventCategory::factory()->create(['text_color' => null]);
    }

    public function test_background_color_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        EventCategory::factory()->create(['background_color' => null]);
    }

    public function test_model_implements_soft_deletes(): void
    {
        // Prepare
        $category = EventCategory::factory()->create();
        // Execute
        $category->delete();
        // Assert
        $this->assertSoftDeleted('event_categories', ['id' => $category->id]);
        $this->assertNull(EventCategory::find($category->id));
        $this->assertNotNull(EventCategory::withTrashed()->find($category->id));
    }
}
