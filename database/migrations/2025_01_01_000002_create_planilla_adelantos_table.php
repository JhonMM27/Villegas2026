<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_adelantos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->unsignedBigInteger('planilla_pago_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('planilla_pago_id')->references('id')->on('planilla_pagos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_adelantos');
    }
};
