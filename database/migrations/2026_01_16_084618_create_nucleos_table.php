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
        Schema::create('nucleos', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();

            $table->foreign('id')
              ->references('id')
              ->on('productos')
              ->cascadeOnDelete();

            // Campos del SELECT
            $table->string('nombre',50);
            $table->string('unidad_codigo', 10)->default('SCO');
            $table->string('unidad_nombre', 50)->nullable();
            $table->string('empaque', 50)->nullable();
            $table->decimal('cantidad_porcentaje', 9, 4)->nullable();
            $table->integer('items')->nullable();
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nucleos');
    }
};
