<?php

namespace Database\Seeders;

use App\Models\OperacionTipo;
use Illuminate\Database\Seeder;

class OperacionTipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposOperacion = [
            ['codigo' => '0101', 'descripcion' => 'Venta interna', 'activo' => true],
            ['codigo' => '0200', 'descripcion' => 'Exportación de bienes', 'activo' => false],
            ['codigo' => '1001', 'descripcion' => 'Operación de importación', 'activo' => false],
        ];

        foreach ($tiposOperacion as $tipo) {
            OperacionTipo::updateOrCreate(
                ['codigo' => $tipo['codigo']],
                [
                    'descripcion' => $tipo['descripcion'],
                    'activo' => true,
                ]
            );
        }
    }
}
