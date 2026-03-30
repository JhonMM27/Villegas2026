<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->decimal('monto_original', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_prestamo');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['activo', 'pagado', 'anulado'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_prestamos');
    }
};
