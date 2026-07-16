<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingrediente_datos_nutricionales', function (Blueprint $table) {
            $table->id();
            $table->string('ingrediente', 100);
            $table->string('procedencia', 50)->nullable();
            $table->string('clasificacion', 50)->nullable();
            $table->string('nutriente', 100)->nullable();
            $table->decimal('materia_seca', 5, 2)->nullable();
            $table->decimal('proteina_cruda', 6, 2)->nullable();
            $table->decimal('enl', 6, 2)->nullable();
            $table->decimal('fdn', 5, 2)->nullable();
            $table->decimal('grasa', 5, 2)->nullable();
            $table->decimal('almidon', 5, 2)->nullable();
            $table->decimal('azucar', 5, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['ingrediente', 'procedencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingrediente_datos_nutricionales');
    }
};
