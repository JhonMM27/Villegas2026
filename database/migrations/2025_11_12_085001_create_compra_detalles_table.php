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
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('compra_id');
            $table->unsignedSmallInteger('producto_id');
            // Datos "congelados" del producto al momento de la compra
            $table->string('producto_nombre', 50)->nullable();
            $table->char('unidad_codigo', 3);
            $table->unsignedSmallInteger('producto_empaque')->nullable();

            $table->decimal('cantidad', 8, 2);
            $table->decimal('cantidad_kgm', 8, 2);
            $table->decimal('costo_unitario', 10, 4);
            $table->decimal('costo_unitario_servicio', 8, 2)->default(0);
            $table->decimal('subtotal', 8, 2);
            $table->decimal('porcentaje_impuesto', 4, 2);
            $table->decimal('impuesto', 8, 2);
            $table->decimal('total', 8, 2);

            $table->foreign('compra_id')->references('id')->on('compras')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_detalles');
    }
};
