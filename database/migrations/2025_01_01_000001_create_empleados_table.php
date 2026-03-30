<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('dni', 8)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('correo')->nullable();
            $table->decimal('sueldo_planilla', 10, 2);
            $table->decimal('sueldo_real', 10, 2);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
