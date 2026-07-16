<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formula_alimento_cerdo_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formula_alimento_cerdo_id');
            $table->unsignedBigInteger('ingrediente_id');
            $table->decimal('cantidad_kg', 10, 4)->default(0);
            $table->decimal('precio_kg', 10, 4)->default(0);
            $table->timestamps();

            $table->unique(
                ['formula_alimento_cerdo_id', 'ingrediente_id'],
                'facd_formula_ingrediente_unique'
            );

            $table->foreign('formula_alimento_cerdo_id')
                ->references('id')
                ->on('formulas_alimento_cerdos')
                ->cascadeOnDelete();

            $table->foreign('ingrediente_id')
                ->references('id')
                ->on('ingrediente_datos_nutricionales')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formula_alimento_cerdo_detalles');
    }
};
