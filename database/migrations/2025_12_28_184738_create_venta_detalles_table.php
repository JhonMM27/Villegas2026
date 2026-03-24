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
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_id');
            $table->unsignedSmallInteger('detalle')->nullable();
            $table->unsignedSmallInteger('producto_id');
            // Datos "congelados" del producto al momento de la compra
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();
            $table->char('unidad_codigo', 3)->nullable();
            $table->decimal('salida_saco', 9, 4);
            $table->decimal('salida_kg', 8, 2);            
            $table->decimal('cantidad', 8, 2);
            $table->decimal('entregado', 8, 2);
            $table->decimal('saldo', 8, 2);
            $table->decimal('precio_unitario', 10, 4);
            $table->decimal('subtotal', 8, 2);
            $table->decimal('porcentaje_impuesto', 4, 2);
            $table->decimal('impuesto', 8, 2);
            $table->decimal('total', 8, 2);
            $table->decimal('costo_unitario', 10, 4)->default(0);
            $table->decimal('costo_total', 10, 4)->default(0);
            $table->decimal('rentabilidad', 10, 4)->default(0);
            

            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};
