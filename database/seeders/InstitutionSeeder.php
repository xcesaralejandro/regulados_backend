<?php

namespace Database\Seeders;

use App\Models\University;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $universities = [
            [
                'name' => 'Universidad de Chile',
                'short_name' => 'UCHILE',
                'canvas_domain_url' => null,
                'canvas_client_id' => null,
                'canvas_client_secret' => null,
                'programs' => ['Ingeniería Civil en Computación', 'Medicina', 'Derecho', 'Arquitectura', 'Psicología']
            ],
            [
                'name' => 'Pontificia Universidad Católica de Chile',
                'short_name' => 'UC',
                'canvas_domain_url' => null,
                'canvas_client_id' => null,
                'canvas_client_secret' => null,
                'programs' => ['Ingeniería Civil de Industrias', 'Medicina', 'Derecho', 'Comercial', 'Enfermería']
            ],
            [
                'name' => 'Universidad de Concepción',
                'short_name' => 'UDEC',
                'canvas_domain_url' => null,
                'canvas_client_id' => null,
                'canvas_client_secret' => null,
                'programs' => ['Ingeniería Civil Informática', 'Bioingeniería', 'Derecho', 'Odontología', 'Geología']
            ],
            [
                'name' => 'Universidad de Santiago de Chile',
                'short_name' => 'USACH',
                'canvas_domain_url' => null,
                'canvas_client_id' => null,
                'canvas_client_secret' => null,
                'programs' => ['Ingeniería Civil en Informática', 'Ingeniería Comercial', 'Química y Farmacia', 'Psicología']
            ],
            [
                'name' => 'Universidad Técnica Federico Santa María',
                'short_name' => 'USM',
                'canvas_domain_url' => null,
                'canvas_client_id' => null,
                'canvas_client_secret' => null,
                'programs' => ['Ingeniería Civil Informática', 'Ingeniería Civil Electrónica', 'Ingeniería Civil Mecánica', 'Astrofísica']
            ]
        ];

        foreach ($universities as $university) {
            $programs = $university['programs'];
            unset($university['programs']);
            $university = University::create($university);
            $programData = array_map(function ($name) {
                return ['name' => $name];
            }, $programs);
            $university->programs()->createMany($programData);
        }
    }
}
