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
        Schema::create('preparadas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();
            $table->datetime('fecha');
            $table->unsignedInteger('numero_interno');
            $table->unsignedInteger('formulacion_id');
            $table->unsignedSmallInteger('producto_id');
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();
            $table->decimal('costo_unitario', 10, 4)->default(0); // costo

            $table->unsignedSmallInteger('cliente_id');
            $table->string('cliente_nombre', 200)->nullable();
                     
            $table->decimal('ingreso_saco', 9, 4);
            $table->decimal('ingreso_kg', 9, 4);
            $table->decimal('ingreso_soles', 10, 4);
            $table->unsignedSmallInteger('items')->nullable();
            $table->timestamps();

            // Índices
            $table->index('fecha');

            // Claves foráneas (si existen las tablas relacionadas)
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('cliente_id')->references('id')->on('clientes');
            //$table->foreign('formulacion_id')->references('id')->on('formulaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preparadas');
    }
};
