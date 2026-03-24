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
        Schema::create('venta_entregas', function (Blueprint $table) {
            $table->increments('id');
            // Usuario que registra/realiza la entrega
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();

            // Relación principal
            $table->unsignedInteger('venta_id');

            // Datos del documento de entrega (opcional)
             $table->string('numero_recibo', 10)->nullable();
            $table->dateTime('fecha_entrega');

            // Estado del movimiento
            $table->string('estado', 20)->default('ENTREGADO'); // BORRADOR | CONFIRMADO | ANULADO

            $table->string('comentario', 200)->nullable();

            $table->timestamps();

            // Índices
            $table->index('fecha_entrega');
            $table->index(['venta_id', 'fecha_entrega']);

            // FKs
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_entregas');
    }
};
