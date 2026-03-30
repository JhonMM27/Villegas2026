<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('sueldo_base', 10, 2)->default(0);
            $table->decimal('horas_extras', 10, 2)->default(0);
            $table->decimal('adelantos', 10, 2)->default(0);
            $table->decimal('total_pagar', 10, 2);
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['empleado_id', 'mes', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_pagos');
    }
};
