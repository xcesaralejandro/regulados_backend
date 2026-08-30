<?php

namespace Tests\Feature\Controllers;

use App\Models\Program;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UniversityControllerTest extends TestCase
{

    public function test_index_route_is_public()
    {
        // Execute
        $response = $this->getJson('/api/universities');
        // Assert
        $response->assertOk();
    }

    public function test_index_returns_200_and_empty_array_when_no_records_exist()
    {
        // Prepare
        $user = User::factory()->create(['program_id' => null]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/universities');
        // Assert
        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_index_returns_200_with_exact_json_structure()
    {
        // Prepare
        $user = User::factory()->create(['program_id' => null]);
        Sanctum::actingAs($user);
        $university = University::factory()->create();
        Program::factory()->create(['university_id' => $university->id]);
        // Execute
        $response = $this->getJson('/api/universities');
        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'short_name',
                'programs' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);
    }

    public function test_index_returns_200_with_exact_json_data_matching_records()
    {
        // Prepare
        $user = User::factory()->create(['program_id' => null]);
        Sanctum::actingAs($user);
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        // Execute
        $response = $this->getJson('/api/universities');
        // Assert
        $response->assertOk();
        $response->assertExactJson([
            [
                'id' => $university->id,
                'name' => $university->name,
                'short_name' => $university->short_name,
                'programs' => [
                    [
                        'id' => $program->id,
                        'name' => $program->name,
                    ],
                ],
            ],
        ]);
    }

    public function test_index_loads_empty_programs_relationship_when_no_programs_exist()
    {
        // Prepare
        $user = User::factory()->create(['program_id' => null]);
        Sanctum::actingAs($user);
        $university = University::factory()->create();
        // Execute
        $response = $this->getJson('/api/universities');
        // Assert
        $response->assertOk();
        $response->assertExactJson([
            [
                'id' => $university->id,
                'name' => $university->name,
                'short_name' => $university->short_name,
                'programs' => [],
            ],
        ]);
    }
}
