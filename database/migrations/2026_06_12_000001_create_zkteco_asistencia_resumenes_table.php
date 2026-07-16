<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_asistencia_resumenes', function (Blueprint $table) {
            $table->id();
            $table->string('device_sn', 100)->index();
            $table->string('pin', 50)->index();
            $table->unsignedBigInteger('empleado_id')->nullable()->index();
            $table->date('fecha')->index();
            $table->dateTime('primera_entrada')->nullable();
            $table->dateTime('ultima_salida')->nullable();
            $table->unsignedInteger('total_punches')->default(0);
            $table->unsignedInteger('minutos_tardanza')->default(0);
            $table->unsignedInteger('minutos_salida_temprana')->default(0);
            $table->unsignedInteger('minutos_extra')->default(0);
            $table->unsignedInteger('minutos_descanso')->default(0);
            $table->boolean('descanso_excedido')->default(false);
            $table->boolean('incompleto')->default(false);
            $table->string('horario', 50)->nullable();
            $table->dateTime('recalculated_at')->nullable();

            $table->unique(['device_sn', 'pin', 'fecha'], 'zkteco_resumen_unique');
            $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_asistencia_resumenes');
    }
};
