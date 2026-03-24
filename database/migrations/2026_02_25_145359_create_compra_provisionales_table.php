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
        Schema::create('compra_provisionales', function (Blueprint $table) {
            // 🔑 Identificación
            $table->increments('id');

            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();

            // 📄 Movimiento
            $table->string('numero_recibo', 10)->nullable();
            $table->string('numero_interno', 10)->nullable();

            $table->dateTime('fecha_provisional');

            $table->decimal('monto', 9, 2)->default(0);
            $table->decimal('libre', 9, 2)->default(0);
            $table->string('tipo', 20)->default('ADELANTO');
            // Persona
            $table->unsignedSmallInteger('proveedor_id');
            $table->string('proveedor_nombre', 100);
            //Cobranza
            $table->decimal('importe_p', 8, 2);
            $table->decimal('importe_d', 8, 2);
            $table->decimal('importe_c', 8, 2);

            $table->timestamps();

            // Índices
            $table->index('fecha_provisional');

            // FK (opcional, si user_id siempre existe en users)
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('proveedor_id')->references('id')->on('proveedores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_provisionales');
    }
};
