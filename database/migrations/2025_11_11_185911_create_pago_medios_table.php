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
        Schema::create('pago_medios', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 10)->nullable()->comment('Código según Catálogo 59 de SUNAT');
            $table->string('nombre', 50)->comment('Nombre del medio de pago');
            $table->string('descripcion', 70)->nullable()->comment('Descripción del medio de pago');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_medios');
    }
};
