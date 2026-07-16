<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PagoMedioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pago_medios')->insert([
            ['codigo' => '008', 'nombre' => 'Efectivo', 'descripcion' => 'Efectivo (sin obligación de medio de pago)', 'activo' => true],
            ['codigo' => '003', 'nombre' => 'Yape', 'descripcion' => 'Yape (transferencia de fondos)', 'activo' => true],
            ['codigo' => '003', 'nombre' => 'Plin', 'descripcion' => 'Plin (transferencia de fondos)', 'activo' => true],
            ['codigo' => '003', 'nombre' => 'POS', 'descripcion' => 'POS (Transferencia de fondos)', 'activo' => true],
            ['codigo' => '001', 'nombre' => 'Depósito', 'descripcion' => 'Depósito en cuenta', 'activo' => false],
            ['codigo' => '003', 'nombre' => 'Transferencia', 'descripcion' => 'Transferencia de fondos', 'activo' => true],
            ['codigo' => '002', 'nombre' => 'Giro', 'descripcion' => 'Giro', 'activo' => false],
            ['codigo' => '009', 'nombre' => 'Efectivo (otros casos)', 'descripcion' => 'Efectivo (otros casos)', 'activo' => false],
        ]);

    }
}
