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
        Schema::create('nucleo_detalles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('nucleo_id');
            $table->unsignedSmallInteger('producto_id');

            // Campos del DBF
            $table->string('producto_nombre', 50);
            $table->unsignedSmallInteger('producto_empaque')->default(1);
            $table->string('unidad_codigo', 10)->nullable();
            $table->decimal('cantidad', 9, 4)->nullable();

            // Evita duplicados nucleo-producto
            $table->unique(['nucleo_id', 'producto_id']);

            $table->foreign('nucleo_id')->references('id')->on('nucleos')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nucleo_detalles');
    }
};
