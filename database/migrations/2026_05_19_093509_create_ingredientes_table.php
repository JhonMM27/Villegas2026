<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('procedencia', ['Nacional', 'Importado'])->default('Nacional');
            $table->string('clasificacion', 50)->nullable();
            $table->string('nutriente_principal', 50)->nullable();
            $table->string('aporte_nutricional', 20)->nullable();
            $table->decimal('precio_kg', 10, 4)->nullable();
            $table->decimal('materia_seca_pct', 5, 2)->nullable();
            $table->decimal('proteina_cruda_pct', 5, 2)->nullable();
            $table->decimal('enl_mcal_kg', 6, 2)->nullable();
            $table->decimal('fdn_pct', 5, 2)->nullable();
            $table->decimal('grasa_pct', 5, 2)->nullable();
            $table->decimal('almidon_pct', 5, 2)->nullable();
            $table->decimal('azucar_pct', 5, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredientes');
    }
};
