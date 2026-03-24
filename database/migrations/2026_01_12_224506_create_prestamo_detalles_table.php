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
        Schema::create('prestamo_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('prestamo_id');
            $table->unsignedSmallInteger('producto_id');
            // Datos "congelados" del producto al momento de la compra
            $table->string('producto_nombre', 50)->nullable();
            $table->char('unidad_codigo', 3);
            $table->string('unidad_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();

            $table->decimal('cantidad', 8, 2);
            $table->decimal('cantidad_kgm', 8, 2);
            $table->decimal('valor_unitario', 10, 4);
            $table->decimal('total', 8, 2);

            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamo_detalles');
    }
};
