<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EventCategory::factory()->create(['name' => 'Estudio', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Clase', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Traslado', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Comida', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Descanso', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Tarea', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Lectura', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Ejercicios', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Proyecto', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Evaluación', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Reunion', 'description' => null]);
        EventCategory::factory()->create(['name' => 'Tutoria', 'description' => null]);
    }
}
