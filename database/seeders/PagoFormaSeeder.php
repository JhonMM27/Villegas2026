<?php

namespace Database\Seeders;

use App\Models\PagoForma;
use Illuminate\Database\Seeder;

class PagoFormaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pagoFormas = [
            ['codigo' => '1', 'descripcion' => 'Contado', 'dias' => 0],
            ['codigo' => '2', 'descripcion' => 'Crédito a 3 días', 'dias' => 3],
            ['codigo' => '3', 'descripcion' => 'Crédito a 5 días', 'dias' => 5],
            ['codigo' => '4', 'descripcion' => 'Crédito a 7 días', 'dias' => 7],
            ['codigo' => '5', 'descripcion' => 'Crédito a 15 días', 'dias' => 15],
            ['codigo' => '6', 'descripcion' => 'Crédito a 30 días', 'dias' => 30],
            ['codigo' => '7', 'descripcion' => 'Crédito a 20 días', 'dias' => 20],
        ];

        foreach ($pagoFormas as $pagoForma) {
            PagoForma::updateOrCreate(
                ['codigo' => $pagoForma['codigo']],
                $pagoForma
            );
        }
    }
}
