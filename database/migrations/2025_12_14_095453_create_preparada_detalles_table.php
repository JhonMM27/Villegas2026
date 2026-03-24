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
        Schema::create('preparada_detalles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('preparada_id');
            $table->unsignedSmallInteger('producto_id');
            // Datos "congelados" del producto al momento de la compra
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();
            $table->decimal('salida_saco', 9, 4);
            $table->decimal('salida_kg', 9, 4);
            $table->decimal('salida_soles', 9, 4);
            $table->decimal('precio_unitario', 9, 4);

            $table->foreign('preparada_id')->references('id')->on('preparadas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preparada_detalles');
    }
};
