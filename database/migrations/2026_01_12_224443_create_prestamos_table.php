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
        Schema::create('prestamos', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();

            // PRESTAMO | DEVOLUCION
            $table->enum('movimiento_tipo', ['PA', 'DD', 'PD', 'DA']);

            // QUIÉN ENTREGA
            $table->unsignedSmallInteger('cliente_origen_id');

            // QUIÉN RECIBE
            $table->unsignedSmallInteger('cliente_destino_id');

            // Referencia al préstamo original (para devoluciones)
            $table->unsignedInteger('prestamo_referencia_id')->nullable();

            $table->char('comprobante_tipo_codigo', 2);
            $table->string('comprobante_tipo_nombre', 2)->nullable();
            $table->string('serie', 4);
            $table->integer('correlativo');

            $table->dateTime('fecha_prestamo');

            $table->decimal('total', 8, 2);

            $table->string('estado', 20)->default('REGISTRADO');
            // REGISTRADO | PARCIAL | CERRADO | ANULADO

            $table->timestamps();

            // FKs
            $table->foreign('cliente_origen_id')->references('id')->on('clientes');
            $table->foreign('cliente_destino_id')->references('id')->on('clientes');
            $table->foreign('comprobante_tipo_codigo')->references('codigo')->on('comprobante_tipos');
            $table->foreign('prestamo_referencia_id')->references('id')->on('prestamos');
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
