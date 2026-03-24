<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unidad;

class UnidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $unidades = [
            ['codigo' => 'NIU', 'descripcion' => 'UNIDAD (BIENES)'],
            ['codigo' => 'SCO', 'descripcion' => 'SACO'],
            ['codigo' => 'BJ', 'descripcion' => 'BALDE'],
            ['codigo' => 'BG', 'descripcion' => 'BOLSA'],
            ['codigo' => 'KGM', 'descripcion' => 'KILOGRAMO'],
            ['codigo' => 'LBR', 'descripcion' => 'LIBRAS'],
            ['codigo' => 'LTR', 'descripcion' => 'LITRO'],          
            ['codigo' => 'ZZ',  'descripcion' => 'UNIDAD (SERVICIOS)'],
            ['codigo' => 'GLL', 'descripcion' => 'US GALÓN (3,7843 L)']
        ];
        foreach ($unidades as $unidad) {
            Unidad::updateOrCreate(
                ['codigo' => $unidad['codigo']],
                ['descripcion' => $unidad['descripcion']]
            );
        }
    }
}
