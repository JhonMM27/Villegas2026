<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_prestamo_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_prestamo_id')->constrained('planilla_prestamos')->onDelete('cascade');
            $table->decimal('monto_pagado', 10, 2);
            $table->date('fecha_pago');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_prestamo_pagos');
    }
};
