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
        Schema::create('productos', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->char('unidad_codigo', 3);
            $table->char('afectacion_tipo_codigo', 2);
            $table->unsignedSmallInteger('linea_id');
            $table->string('codigo', 50)->nullable(); // Código interno opcional
            $table->string('nombre', 50);
            $table->unsignedSmallInteger('empaque')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->string('imagen', 50)->nullable();
            $table->decimal('stock_almacen', 10, 4)->default(0); // stockalm
            $table->decimal('stock_minimo', 8, 2)->default(10); // stockalm
            $table->decimal('costo_unitario', 10, 4)->default(0); // costo
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('unidad_codigo')->references('codigo')->on('unidades');
            $table->foreign('afectacion_tipo_codigo')->references('codigo')->on('afectacion_tipos');
            $table->foreign('linea_id')->references('id')->on('lineas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
