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
        Schema::create('caja_pagos', function (Blueprint $table) {
            // 🔑 Identificación
            $table->increments('id');
            
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();

            // 📄 Movimiento
            $table->char('movimiento_tipo', 1); // V
            $table->string('numero_recibo', 10)->nullable();
            $table->string('numero_interno', 10)->nullable();

            $table->dateTime('fecha_caja');

            $table->string('situacion', 20)->nullable();

            $table->decimal('entrada', 9, 2)->default(0);
            $table->decimal('salida', 9, 2)->default(0);
            $table->decimal('saldo_anterior', 15, 2)->default(0);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->decimal('acuenta', 9, 2)->default(0);
            $table->decimal('contado', 9, 2)->default(0);

            $table->string('comentario', 100)->nullable();

            $table->char('pago_forma_codigo', 2);
            $table->string('pago_forma_nombre', 20);

            $table->char('persona_tipo', 1); // C
            $table->unsignedBigInteger('persona_id');
            $table->string('persona_nombre', 100);

            $table->char('comprobante_tipo_codigo', 2)->nullable();
            $table->string('comprobante_tipo_nombre', 2)->nullable();
            $table->string('serie', 4)->nullable();
            $table->integer('correlativo')->nullable();

            $table->unsignedSmallInteger('cobranza_tipo_id');
            $table->string('cobranza_tipo_nombre', 20)->nullable();

            $table->decimal('provisional', 9, 2)->default(0);
            $table->decimal('diferencia', 9, 2)->default(0);

            $table->decimal('importe1', 9, 2)->default(0);
            $table->decimal('importe2', 9, 2)->default(0);
            $table->decimal('importe3', 9, 2)->default(0);
            $table->decimal('total', 9, 2)->default(0);

    
            $table->timestamps();

            $table->index('fecha_caja');
            $table->index('pago_forma_codigo');
            $table->index('persona_id');

            $table->foreign('user_id')->references('id')->on('users');
            //$table->foreign('comprobante_tipo_codigo')->references('codigo')->on('comprobante_tipos');
            //$table->foreign('cobranza_tipo_id')->references('id')->on('cobranza_tipos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_pagos');
    }
};
