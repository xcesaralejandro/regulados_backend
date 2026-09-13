<?php

namespace Tests\Feature\Controllers;

use App\Models\Program;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{

    public function test_me_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare & Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertStatus(401);
    }

    public function test_me_returns_200_when_user_is_authenticated(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertStatus(200);
    }

    public function test_me_returns_authenticated_user_data(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertJsonFragment(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_me_does_not_return_other_users_data(): void
    {
        // Prepare
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertJsonMissing(['id' => $otherUser->id, 'email' => $otherUser->email]);
    }

    public function test_me_returns_exact_json_keys_structure(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        $expectedKeys = [
            'id',
            'semester',
            'name',
            'surname',
            'gender',
            'custom_gender',
            'email',
            'phone',
            'instagram',
            'discord',
            'birthdate',
            'preferred_start_time',
            'avatar',
            'program' => [
                'id',
                'name',
                'university' => [
                    'id',
                    'name',
                    'short_name',
                ],
            ],
        ];
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertJsonStructure($expectedKeys);
    }

    public function test_me_returns_exact_number_of_root_keys(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $this->assertCount(14, array_keys($response->json()));
    }

    public function test_me_exposes_made_visible_attributes(): void
    {
        // Prepare
        $user = User::factory()->create([
            'gender' => 'female',
            'custom_gender' => 'non-binary',
            'preferred_start_time' => 8,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertJsonFragment([
            'gender' => 'female',
            'custom_gender' => 'non-binary',
            'preferred_start_time' => 8,
        ]);
    }

    public function test_me_loads_program_relationship_with_expected_keys(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertJsonStructure([
            'program' => [
                'id',
                'name',
                'university',
            ],
        ]);
    }

    public function test_me_loads_university_relationship_inside_program_with_expected_keys(): void
    {
        // Prepare
        $university = University::factory()->create();
        $program = Program::factory()->create(['university_id' => $university->id]);
        $user = User::factory()->create(['program_id' => $program->id]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertJsonStructure([
            'program' => [
                'university' => [
                    'id',
                    'name',
                    'short_name',
                ],
            ],
        ]);
    }

    public function test_me_returns_null_program_when_user_has_no_program_associated(): void
    {
        // Prepare
        $user = User::factory()->create(['program_id' => null]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('program', null);
    }

    public function test_me_returns_exact_json_matching_all_data(): void
    {
        // Prepare
        $university = University::factory()->create([
            'name' => 'Universidad de Chile',
            'short_name' => 'UCHILE',
        ]);
        $program = Program::factory()->create([
            'university_id' => $university->id,
            'name' => 'Ingeniería Civil en Computación',
        ]);
        $user = User::factory()->create([
            'semester' => 1,
            'name' => 'Cesar',
            'surname' => 'Mora',
            'gender' => 'female',
            'custom_gender' => null,
            'email' => 'cmora@udec.cl',
            'phone' => null,
            'instagram' => null,
            'discord' => null,
            'birthdate' => '1995-12-20',
            'preferred_start_time' => 7,
            'avatar' => null,
            'program_id' => $program->id,
        ]);
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/users/me');
        // Assert
        $response->assertExactJson([
            'id' => $user->id,
            'semester' => 1,
            'name' => 'Cesar',
            'surname' => 'Mora',
            'gender' => 'female',
            'custom_gender' => null,
            'email' => 'cmora@udec.cl',
            'phone' => null,
            'instagram' => null,
            'discord' => null,
            'birthdate' => '1995-12-20',
            'preferred_start_time' => 7,
            'avatar' => null,
            'program' => [
                'id' => $program->id,
                'name' => 'Ingeniería Civil en Computación',
                'university' => [
                    'id' => $university->id,
                    'name' => 'Universidad de Chile',
                    'short_name' => 'UCHILE',
                ],
            ],
        ]);
    }
}
