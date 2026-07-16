<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documento_tipos', function (Blueprint $table) {
            $table->char('codigo', 2)->primary();
            $table->string('descripcion', 50);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('documento_tipos')->insert([
            ['codigo' => '00', 'descripcion' => 'Doc. Tributario no domiciliado', 'activo' => false],
            ['codigo' => '01', 'descripcion' => 'DNI', 'activo' => true],
            ['codigo' => '04', 'descripcion' => 'Carnet de extranjería', 'activo' => false],
            ['codigo' => '06', 'descripcion' => 'RUC', 'activo' => true],
            ['codigo' => '07', 'descripcion' => 'Pasaporte', 'activo' => false],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_tipos');
    }
};
