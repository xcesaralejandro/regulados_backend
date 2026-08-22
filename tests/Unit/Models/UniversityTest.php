<?php

namespace Tests\Unit\Models;

use App\Models\Program;
use App\Models\University;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class UniversityTest extends TestCase
{
    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $modelKeys = array_keys(University::factory()->create()->toArray());
        $visibleKeys = ['id', 'name', 'short_name'];
        // Assert
        sort($modelKeys);
        sort($visibleKeys);
        $this->assertEquals($visibleKeys, $modelKeys);
    }

    public function test_fields_defined_as_hidden_are_not_displayed(): void
    {
        // Prepare
        $modelKeys = array_keys(University::factory()->create()->toArray());
        $hiddenKeys = ['created_at', 'updated_at', 'deleted_at', 'canvas_domain_url', 'canvas_client_id', 'canvas_client_secret'];
        // Assert
        $this->assertEmpty(array_intersect($hiddenKeys, $modelKeys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $university = new University();
        $expectedFillable = ['name', 'short_name', 'canvas_domain_url', 'canvas_client_id', 'canvas_client_secret'];
        // Assert
        $this->assertEquals($expectedFillable, $university->getFillable());
    }

    public function test_model_is_linked_to_correct_table(): void
    {
        // Prepare
        $university = new University();
        // Execute
        $table = $university->getTable();
        // Assert
        $this->assertEquals('universities', $table);
    }

    public function test_model_cast_created_at(): void
    {
        // Prepare
        University::factory()->create();
        // Execute
        $university = University::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $university['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        University::factory()->create();
        // Execute
        $university = University::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $university['updated_at']);
    }

    public function test_model_has_programs_relationship(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        // Execute
        $university = University::with('programs')->find($university->id);
        // Assert
        $this->assertTrue($university->relationLoaded('programs'));
        $this->assertNotEmpty($university->programs);
        $this->assertEquals($program->id, $university->programs->first()->id);
    }

    public function test_model_load_correct_data_for_programs_relationship(): void
    {
        // Prepare
        University::factory(2)->create();
        Program::factory(3)->create();
        $university = University::factory()->create();
        $programs = Program::factory(3)->create(['university_id' => $university->id]);
        // Execute
        $university = University::with('programs')->find($university->id);
        // Assert
        $this->assertCount(3, $university->programs);
        $this->assertEquals($programs->pluck('id')->sort()->values(), $university->programs->pluck('id')->sort()->values());
    }

    public function test_model_has_programs_relationship_correctly_formed(): void
    {
        // Prepare
        $university = new University();
        // Execute
        $relation = $university->programs();
        // Assert
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('university_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getLocalKeyName());
    }

    public function test_name_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        University::factory()->create(['name' => null]);
    }

    public function test_short_name_field_is_required(): void
    {
        // Prepare & Execute
        $this->expectException(QueryException::class);
        // Assert
        University::factory()->create(['short_name' => null]);
    }

    public function test_canvas_domain_url_field_accepts_null(): void
    {
        // Prepare & Execute
        $university = University::factory()->create(['canvas_domain_url' => null]);
        // Assert
        $this->assertNull($university->canvas_domain_url);
    }

    public function test_canvas_client_id_field_accepts_null(): void
    {
        // Prepare & Execute
        $university = University::factory()->create(['canvas_client_id' => null]);
        // Assert
        $this->assertNull($university->canvas_client_id);
    }

    public function test_canvas_client_secret_field_accepts_null(): void
    {
        // Prepare & Execute
        $university = University::factory()->create(['canvas_client_secret' => null]);
        // Assert
        $this->assertNull($university->canvas_client_secret);
    }

    public function test_model_implements_soft_deletes(): void
    {
        // Prepare
        $university = University::factory()->create();
        // Execute
        $university->delete();
        // Assert
        $this->assertSoftDeleted('universities', ['id' => $university->id]);
        $this->assertNull(University::find($university->id));
        $this->assertNotNull(University::withTrashed()->find($university->id));
    }
}
