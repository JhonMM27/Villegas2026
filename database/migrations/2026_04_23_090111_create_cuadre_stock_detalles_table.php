<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadre_stock_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cuadre_stock_id');
            $table->unsignedSmallInteger('producto_id');
            $table->decimal('stock_sistema', 10, 4);
            $table->decimal('stock_fisico', 10, 4);
            $table->decimal('diferencia', 10, 4);
            $table->string('tipo', 20);
            $table->timestamps();
            $table->foreign('cuadre_stock_id')->references('id')->on('cuadre_stocks')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadre_stock_detalles');
    }
};
