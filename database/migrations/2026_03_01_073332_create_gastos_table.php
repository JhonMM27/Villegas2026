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
        Schema::create('gastos', function (Blueprint $table) {
            // 🔑 Identificación
            $table->increments('id');

            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();

            // 📄 Movimiento
            $table->dateTime('fecha_gasto');
            $table->string('descripcion', 100)->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('numero_recibo', 10)->nullable();
            $table->string('numero_interno', 10)->nullable();
            $table->decimal('monto', 8, 2)->default(0);
            // Cobranza
            $table->decimal('importe_p', 8, 2)->default(0);
            $table->decimal('importe_d', 8, 2)->default(0);
            $table->decimal('importe_c', 8, 2)->default(0);

            $table->timestamps();

            // Índices
            $table->index('fecha_gasto');

            // FK (opcional, si user_id siempre existe en users)
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
