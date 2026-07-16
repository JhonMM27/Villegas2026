<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formula_alimento_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_alimento_id')
                ->constrained('formulas_alimento')
                ->cascadeOnDelete();
            $table->foreignId('ingrediente_id')
                ->constrained('ingrediente_datos_nutricionales')
                ->restrictOnDelete();
            $table->decimal('cantidad_kg', 10, 4)->default(0);
            $table->decimal('precio_kg', 10, 4)->default(0);
            $table->timestamps();
            $table->unique(['formula_alimento_id', 'ingrediente_id'], 'unique_formula_ingrediente');
            $table->index('formula_alimento_id', 'idx_formula');
            $table->index('ingrediente_id', 'idx_ingrediente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formula_alimento_detalles');
    }
};
