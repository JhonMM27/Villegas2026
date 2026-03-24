<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AfectacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('afectacion_tipos')->updateOrInsert(
            ['codigo' => '10'],
            [
                'nombre'      => 'IGV',
                'descripcion' => 'OP. GRAVADAS',
                'letra'       => 'S',
                'porcentaje'  => 0.18,
            ]
        );

        DB::table('afectacion_tipos')->updateOrInsert(
            ['codigo' => '20'],
            [
                'nombre'      => 'EXO',
                'descripcion' => 'OP. EXONERADAS',
                'letra'       => 'E',
                'porcentaje'  => 0.00,
            ]
        );

        DB::table('afectacion_tipos')->updateOrInsert(
            ['codigo' => '30'],
            [
                'nombre'      => 'INA',
                'descripcion' => 'OP. INAFECTAS',
                'letra'       => 'O',
                'porcentaje'  => 0.00,
            ]
        );
    }
}