<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulas_alimento_cerdos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->date('fecha')->nullable();
            $table->boolean('activo')->default(true);
            $table->decimal('kg_saco', 10, 2)->default(50);
            $table->decimal('saco_vacio', 10, 2)->default(1.50);
            $table->decimal('mano_obra', 10, 2)->default(0.50);
            $table->decimal('energia', 10, 2)->default(1.00);
            $table->decimal('merma', 10, 2)->default(0.50);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->timestamps();

            $table->unique('nombre', 'fac_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulas_alimento_cerdos');
    }
};
