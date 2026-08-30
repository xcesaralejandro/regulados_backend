<?php

namespace Tests\Unit\Models;

use App\Models\Program;
use App\Models\University;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    public function test_visible_fields_are_the_expected(): void
    {
        // Prepare
        $model_keys = array_keys(Program::factory()->create()->toArray());
        $visible_keys = ['id', 'name'];
        // Assert
        sort($model_keys);
        sort($visible_keys);
        $this->assertEquals($visible_keys, $model_keys);
    }

    public function test_fields_defined_as_hidden_are_not_displayed(): void
    {
        // Prepare
        $model_keys = array_keys(Program::factory()->create()->toArray());
        $hidden_keys = ['university_id', 'created_at', 'updated_at', 'deleted_at'];
        // Assert
        $this->assertEmpty(array_intersect($hidden_keys, $model_keys));
    }

    public function test_fillable_attributes_are_configured_correctly(): void
    {
        // Prepare
        $program = new Program();
        $expectedFillable = ['university_id', 'name'];
        // Execute
        $fillable = $program->getFillable();
        // Assert
        $this->assertEquals($expectedFillable, $fillable);
    }

    public function test_model_is_linked_to_correct_table(): void
    {
        // Prepare
        $program = new Program();
        // Execute
        $table = $program->getTable();
        // Assert
        $this->assertEquals('programs', $table);
    }

    public function test_model_cast_created_at(): void
    {
        // Prepare
        Program::factory()->create();
        // Execute
        $program = Program::first()->makeVisible(['created_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $program['created_at']);
    }

    public function test_model_cast_updated_at(): void
    {
        // Prepare
        Program::factory()->create();
        // Execute
        $program = Program::first()->makeVisible(['updated_at'])->toArray();
        // Assert
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $program['updated_at']);
    }

    public function test_model_has_university_relationship(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        // Execute
        $loadedProgram = Program::with('university')->find($program->id);
        // Assert
        $this->assertTrue($loadedProgram->relationLoaded('university'));
        $this->assertNotNull($loadedProgram->university);
        $this->assertEquals($university->id, $loadedProgram->university->id);
    }

    public function test_model_load_correct_data_for_university_relationship(): void
    {
        // Prepare
        University::factory(3)->create();
        Program::factory(3)->create();
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        // Execute
        $loadedProgram = Program::with('university')->find($program->id);
        // Assert
        $this->assertNotNull($loadedProgram->university);
        $this->assertEquals($university->id, $loadedProgram->university->id);
    }

    public function test_model_has_university_relationship_correctly_formed(): void
    {
        // Prepare
        $program = new Program();
        // Execute
        $relation = $program->university();
        // Assert
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('university_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
    }

    public function test_name_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Program::factory()->create(['name' => null]);
    }

    public function test_university_id_field_is_required(): void
    {
        // Assert
        $this->expectException(QueryException::class);
        // Execute
        Program::factory()->create(['university_id' => null]);
    }

    public function test_model_implements_soft_deletes(): void
    {
        // Prepare
        $program = Program::factory()->create();
        // Execute
        $program->delete();
        // Assert
        $this->assertSoftDeleted('programs', ['id' => $program->id]);
        $this->assertNull(Program::find($program->id));
        $this->assertNotNull(Program::withTrashed()->find($program->id));
    }
}
