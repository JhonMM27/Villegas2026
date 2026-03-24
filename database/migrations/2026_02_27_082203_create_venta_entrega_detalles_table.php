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
        Schema::create('venta_entrega_detalles', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('venta_entrega_id');
            $table->unsignedInteger('venta_detalle_id');

            // Redundante útil (para reportes y validaciones rápidas)
            $table->unsignedSmallInteger('producto_id');
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();

            // Cantidades entregadas en este movimiento
            $table->decimal('cantidad', 8, 2)->default(0);
            $table->decimal('salida_kg', 8, 2)->default(0);

            // Índices
            $table->index('venta_entrega_id');
            $table->index('venta_detalle_id');
            $table->index(['producto_id', 'venta_entrega_id']);

            // Evita duplicar el mismo item en la misma entrega (opcional pero recomendado)
            $table->unique(['venta_entrega_id', 'venta_detalle_id']);

            // FKs
            $table->foreign('venta_entrega_id')->references('id')->on('venta_entregas')->onDelete('cascade');
            $table->foreign('venta_detalle_id')->references('id')->on('venta_detalles')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_entrega_detalles');
    }
};
