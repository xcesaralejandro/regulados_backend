<?php

namespace Tests\Feature\Controllers;

use App\Models\EventCategory;
use App\Models\Program;
use App\Models\University;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventCategoryControllerTest extends TestCase
{

    public function test_index_returns_401_when_user_is_unauthenticated(): void
    {
        // Prepare & Execute
        $response = $this->getJson('/api/event-categories');
        // Assert
        $response->assertStatus(401);
    }

    public function test_index_returns_200_and_empty_array_when_no_categories_exist(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        // Execute
        $response = $this->getJson('/api/event-categories');
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_index_returns_200_with_exact_json_structure(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        EventCategory::factory()->create();
        $expectedKeys = [
            'id',
            'name',
            'description',
            'icon',
            'text_color',
            'background_color',
        ];
        // Execute
        $response = $this->getJson('/api/event-categories');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure(['*' => $expectedKeys]);
    }

    public function test_index_returns_200_with_exact_json_matching_records(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $categories = EventCategory::factory()->count(2)->create();
        // Execute
        $response = $this->getJson('/api/event-categories');
        // Assert
        $response->assertStatus(200);
        $response->assertExactJson($categories->fresh()->toArray());
    }

    public function test_index_returns_all_persisted_categories(): void
    {
        // Prepare
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        EventCategory::factory()->count(3)->create();
        // Execute
        $response = $this->getJson('/api/event-categories');
        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }
}
