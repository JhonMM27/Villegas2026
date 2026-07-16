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
        Schema::create('formulaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('user_id');
            $table->string('user_nombre', 20)->nullable();
            $table->datetime('fecha');
            $table->unsignedSmallInteger('producto_id');
            $table->string('producto_nombre', 50)->nullable();
            $table->unsignedSmallInteger('producto_empaque')->nullable();
            $table->unsignedSmallInteger('cliente_id');
            $table->string('cliente_nombre', 100)->nullable();

            $table->decimal('salida_kg', 8, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Claves foráneas (si existen las tablas relacionadas)
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->foreign('cliente_id')->references('id')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulaciones');
    }
};
