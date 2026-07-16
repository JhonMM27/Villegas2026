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
        Schema::create('nucleo_preparada_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('nucleo_preparada_id');
            $table->unsignedSmallInteger('producto_id');
            // Datos "congelados" del producto al momento de la compra
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();
            $table->char('unidad_codigo', 3);
            $table->decimal('costo_unitario', 9, 4)->nullable();
            $table->decimal('cantidad_porcentaje', 8, 2)->nullable();
            $table->decimal('salida_kg', 9, 4)->nullable();
            $table->decimal('salida_soles', 9, 4)->nullable();

            $table->foreign('nucleo_preparada_id')->references('id')->on('nucleo_preparadas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nucleo_preparada_detalles');
    }
};
