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
        Schema::create('nucleo_preparadas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();
            $table->unsignedSmallInteger('nucleo_id');
            $table->string('nucleo_nombre', 50)->nullable();
            $table->string('unidad_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();
            $table->string('numero_interno',10)->nullable();
            $table->datetime('fecha');
            $table->decimal('cantidad_porcentaje', 8, 2)->nullable();
            $table->decimal('costo_unitario', 9, 4)->nullable();
            $table->decimal('ingreso_saco', 9, 4)->nullable();
            $table->decimal('ingreso_kg', 9, 4)->nullable();
            $table->decimal('ingreso_soles', 9, 4)->nullable();
            $table->integer('items')->nullable();            
            $table->timestamps();

            // Índices
            $table->index('fecha');

            $table->foreign('nucleo_id')->references('id')->on('nucleos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nucleo_preparadas');
    }
};
