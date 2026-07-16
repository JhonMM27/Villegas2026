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
        Schema::create('costos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();
            $table->dateTime('fecha_costo');
            $table->string('descripcion', 100)->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('numero_recibo', 10)->nullable();
            $table->string('numero_interno', 10)->nullable();
            $table->unsignedInteger('costo_tipo_id')->nullable();
            $table->decimal('monto', 8, 2)->default(0);
            $table->decimal('importe_p', 8, 2)->default(0);
            $table->decimal('importe_d', 8, 2)->default(0);
            $table->decimal('importe_c', 8, 2)->default(0);
            $table->timestamps();

            $table->index('fecha_costo');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('costo_tipo_id')->references('id')->on('costo_tipos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costos');
    }
};
