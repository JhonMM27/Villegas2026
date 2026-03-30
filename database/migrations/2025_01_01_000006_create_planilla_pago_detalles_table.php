<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_pago_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_pago_id')->constrained('planilla_pagos')->onDelete('cascade');
            $table->string('concepto');
            $table->decimal('monto', 10, 2);
            $table->enum('tipo', ['ingreso', 'descuento']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_pago_detalles');
    }
};
