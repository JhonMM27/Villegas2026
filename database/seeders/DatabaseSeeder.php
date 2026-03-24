<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UnidadSeeder::class);
        $this->call(AfectacionSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(OperacionTipoSeeder::class);
        $this->call(PagoFormaSeeder::class);
        $this->call(PagoMedioSeeder::class);
    }
}
