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

            $table->foreignId('dato_nutricional_id')
                ->nullable()
                ->after('clasificacion')
                ->constrained('ingrediente_datos_nutricionales')
                ->nullOnDelete();

            $table->decimal('precio_kg', 10, 4)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredientes');
    }
};
