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
        Schema::create('producto_fracciones', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('producto_id');
            $table->string('producto_nombre', 50)->nullable(); // CODDET del DBF
            $table->char('unidad_codigo', 3); // Unidad de la fracción
            $table->unsignedSmallInteger('empaque')->default(1); // Factor de conversión
            $table->unsignedSmallInteger('codigo_detalle')->default(1);
            $table->decimal('precio_lista', 12, 2)->default(0); // P_LISTA del DBF
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('unidad_codigo')->references('codigo')->on('unidades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_fracciones');
    }
};
